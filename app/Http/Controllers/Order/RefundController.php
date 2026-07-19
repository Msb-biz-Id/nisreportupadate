<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Models\Order\Refund;
use App\Services\NumberGenerator;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use App\Models\Master\JenisMasalah;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RefundController extends Controller
{
    public function __construct(private readonly NumberGenerator $numbers) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $selectedBrandId = $request->input('brand_id');
        if (is_null($selectedBrandId) || $selectedBrandId === '' || $selectedBrandId === 'all') {
            $selectedBrandId = 'all';
        }

        // Brand authorization — validate selected brand is accessible
        $isAllBrandsRole = $user->isSuperadmin() || $user->hasRole(['owner', 'admin_keuangan']);
        $accessibleBrandIds = $isAllBrandsRole
            ? null // null = no restriction
            : $user->brands()->pluck('brands.id')->toArray();

        if ($selectedBrandId && $selectedBrandId !== 'all' && !$isAllBrandsRole) {
            abort_unless($user->hasAccessToBrand($selectedBrandId), 403, 'Anda tidak memiliki akses ke brand tersebut.');
        }

        // admin_reseller on hub context: expand to hub + all branch IDs
        $effectiveIds = null;
        if ($user->hasRole('admin_reseller') && ($selectedBrandId === 'all' || empty($selectedBrandId))) {
            $effectiveIds = BrandContext::effectiveBrandIds($request);
        }

        $query = $this->buildRefundQuery($request, $selectedBrandId, $accessibleBrandIds, $effectiveIds);
        $allFiltered = $this->getExportableRefunds($query);

        $perPage = in_array((int) $request->input('per_page', 15), [10, 15, 25, 50, 100])
            ? (int) $request->input('per_page', 15)
            : 15;
        $refunds = $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();

        $isAllBrandsRole = $user->isSuperadmin() || $user->hasRole(['owner', 'admin_keuangan']);
        $brands = $isAllBrandsRole
            ? \App\Models\Brand::orderBy('nama_brand')->get(['id', 'nama_brand', 'kode'])
            : $user->brands()->orderBy('nama_brand')->get(['brands.id', 'nama_brand', 'kode']);

        $rawBankAccounts = \App\Models\Master\BankAccount::where('is_active', true)
            ->get(['id', 'bank', 'atas_nama', 'nomor_rekening', 'brand_id']);

        $bankAccounts = $this->resolveBankAccounts($request, $brands, $rawBankAccounts);

        return Inertia::render('Finance/RefundIndex', [
            'refunds' => $refunds,
            'all_filtered_refunds' => $allFiltered,
            'brands' => $brands,
            'bank_accounts' => $bankAccounts,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
                'brand_id' => $selectedBrandId,
                'start_date' => $request->string('start_date')->toString(),
                'end_date' => $request->string('end_date')->toString(),
                'per_page' => $perPage,
            ],
            'statuses' => Refund::STATUSES,
            'jenis_options' => JenisMasalah::where('is_active', true)->orderBy('nama')->pluck('nama')->toArray(),
            'can' => [
                'create' => $request->user()->can('order.refund'),
                'review' => $request->user() && ($request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan')),
            ],
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('order.refund');

        if ($request->has('nominal_refund')) {
            $nominal = $request->input('nominal_refund');
            if (is_string($nominal)) {
                $cleaned = preg_replace('/[^\d,.-]/', '', $nominal);
                if (strpos($cleaned, '.') !== false && strpos($cleaned, ',') !== false) {
                    $cleaned = str_replace('.', '', $cleaned);
                    $cleaned = str_replace(',', '.', $cleaned);
                } elseif (strpos($cleaned, '.') !== false && preg_match('/^\d+(\.\d{3})+$/', $cleaned)) {
                    $cleaned = str_replace('.', '', $cleaned);
                } elseif (strpos($cleaned, ',') !== false && preg_match('/^\d+(,\d{3})+$/', $cleaned)) {
                    $cleaned = str_replace(',', '', $cleaned);
                } elseif (strpos($cleaned, ',') !== false) {
                    $cleaned = str_replace(',', '.', $cleaned);
                }
                $request->merge(['nominal_refund' => $cleaned]);
            }
        }

        // Resolve order_id from PO Number, Link (URL), or raw UUID
        $orderInput = trim($request->input('order_id'));
        $resolvedOrderId = null;

        if ($orderInput) {
            // 1. If it's a URL, parse it and extract the last segment
            if (filter_var($orderInput, FILTER_VALIDATE_URL)) {
                $path = parse_url($orderInput, PHP_URL_PATH);
                $segments = explode('/', trim($path, '/'));
                $orderInput = end($segments);
            }

            // 2. Try to find by UUID directly
            if (\Illuminate\Support\Str::isUuid($orderInput)) {
                $resolvedOrderId = Order::where('id', $orderInput)->value('id');
            } else {
                // 3. Search by exact or partial PO Number
                $resolvedOrderId = Order::where('no_po', $orderInput)->value('id');
                if (!$resolvedOrderId) {
                    $resolvedOrderId = Order::where('no_po', 'like', "%{$orderInput}%")->value('id');
                }
            }

            if ($resolvedOrderId) {
                $request->merge(['order_id' => $resolvedOrderId]);
            }
        }

        $data = $request->validate([
            'order_id'      => ['required', 'uuid', 'exists:orders,id'],
            'alasan'        => ['required', 'string', 'min:5'],
            'jenis_masalah' => [
                'required',
                'string',
                Rule::exists('jenis_masalahs', 'nama')->where(fn ($q) => $q->where('is_active', true))
            ],
            'jumlah_item'   => ['required', 'integer', 'min:1'],
            'nominal_refund'=> ['required', 'numeric', 'min:0'],
            'catatan'       => ['nullable', 'string'],
            'bukti_files.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'customer_bank_name'    => ['required', 'string', 'max:255'],
            'customer_bank_account' => ['required', 'string', 'max:255'],
        ], [
            'order_id.exists'   => 'Nomor PO, Link PO, atau UUID PO tidak ditemukan dalam sistem.',
            'order_id.required' => 'Nomor PO, Link PO, atau UUID PO wajib diisi.',
            'order_id.uuid'     => 'Format PO ID tidak valid.',
            'bukti_files.*.mimes' => 'File bukti harus berformat jpg, png, webp, atau pdf.',
            'bukti_files.*.max'   => 'Ukuran file maksimal 10 MB.',
            'customer_bank_name.required' => 'Bank Customer (Tujuan Transfer) wajib diisi.',
            'customer_bank_account.required' => 'Nomor Rekening Customer wajib diisi.',
        ]);

        $order = Order::findOrFail($data['order_id']);
        if ($order->isDraft()) {
            return back()->withErrors(['order_id' => 'PO draft tidak bisa di-refund.']);
        }

        if (! $request->user()->hasAccessToBrand($order->brand_id)) abort(403);

        $existingRefundsSum = Refund::where('order_id', $order->id)
            ->whereIn('status', ['pending_review', 'approved', 'published'])
            ->sum('nominal_refund');

        $maxRefundable = $order->total_tagihan - $existingRefundsSum;

        if ($data['nominal_refund'] > $maxRefundable) {
            return back()->withErrors(['nominal_refund' => 'Nominal refund melebihi batas yang dapat direfund (Maksimal sisa: ' . number_format(max(0, $maxRefundable), 0, ',', '.') . ').']);
        }

        // Process bukti (evidence): upload files to Cloudflare R2
        $bukti = [];
        // Determine storage disk: use R2 if configured, fallback to public
        $usesR2 = ! empty(config('filesystems.disks.r2.key'));
        $disk   = $usesR2 ? 'r2' : 'public';

        if ($request->hasFile('bukti_files')) {
            foreach ($request->file('bukti_files') as $file) {
                $path = $file->store('refunds', $disk);

                if ($usesR2 && env('R2_URL')) {
                    $url = rtrim(env('R2_URL'), '/') . '/' . $path;
                } elseif ($usesR2) {
                    // Generate temporary URL (15 min) for private R2 bucket
                    /** @var \Illuminate\Filesystem\FilesystemAdapter $r2Disk */
                    $r2Disk = Storage::disk('r2');
                    $url = $r2Disk->temporaryUrl($path, now()->addMinutes(15));
                } else {
                    $url = '/storage/' . ltrim($path, '/');
                }

                $bukti[] = [
                    'type'  => 'file',
                    'url'   => $url,
                    'path'  => $path,
                    'disk'  => $disk,
                    'name'  => $file->getClientOriginalName(),
                    'mime'  => $file->getMimeType(),
                ];
            }
        }

        $refund = Refund::create([
            'order_id'      => $data['order_id'],
            'alasan'        => $data['alasan'],
            'jenis_masalah' => $data['jenis_masalah'],
            'jumlah_item'   => $data['jumlah_item'],
            'nominal_refund'=> $data['nominal_refund'],
            'catatan'       => $data['catatan'] ?? null,
            'bukti'         => !empty($bukti) ? $bukti : null,
            'brand_id'      => $order->brand_id,
            'refund_number' => $this->numbers->generateRefundNumber($order->brand),
            'status'        => 'pending_review',
            'created_by'    => $request->user()->id,
            'customer_bank_name'    => $data['customer_bank_name'],
            'customer_bank_account' => $data['customer_bank_account'],
        ]);

        return back()->with('success', 'Refund berhasil diajukan dan menunggu review keuangan.');
    }


    public function publish(Request $request, Refund $refund)
    {
        abort_unless(
            $request->user() && ($request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan')),
            403, 'Hanya Admin Keuangan dan Superadmin yang dapat menerbitkan refund.'
        );
        abort_unless(in_array($refund->status, ['pending_review', 'approved'], true), 422);

        $data = $request->validate([
            'bank_id' => ['required', 'uuid', 'exists:bank_accounts,id'],
        ], [
            'bank_id.required' => 'Rekening Bank Pengirim (Brand) wajib dipilih.',
        ]);

        $refund->update([
            'status' => 'published',
            'published_by' => $request->user()->id,
            'published_at' => now(),
            'bank_id' => $data['bank_id'],
        ]);

        \App\Services\ActivityLogger::log('publish', 'refund', $refund, "Terbitkan refund {$refund->refund_number}");

        return back()->with('success', "Refund {$refund->refund_number} diterbitkan.");
    }

    public function reject(Request $request, Refund $refund)
    {
        abort_unless(
            $request->user() && ($request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan')),
            403, 'Hanya Admin Keuangan dan Superadmin yang dapat menolak refund.'
        );
        abort_unless($refund->status === 'pending_review', 422);

        $data = $request->validate([
            'rejected_reason' => ['required', 'string', 'min:5'],
        ]);

        $refund->update([
            'status' => 'rejected',
            'rejected_reason' => $data['rejected_reason'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('info', 'Refund ditolak. Pengaju dapat revisi dan ajukan ulang.');
    }

    public function lookupOrder(Request $request)
    {
        $user = $request->user();
        $orderInput = trim($request->input('query'));
        if (!$orderInput) {
            return response()->json(['success' => false, 'message' => 'Query wajib diisi.']);
        }

        if (filter_var($orderInput, FILTER_VALIDATE_URL)) {
            $path = parse_url($orderInput, PHP_URL_PATH);
            $segments = explode('/', trim($path, '/'));
            $orderInput = end($segments);
        }

        $order = null;
        if (\Illuminate\Support\Str::isUuid($orderInput)) {
            $order = Order::with(['items', 'pelanggan'])->find($orderInput);
        } else {
            $order = Order::with(['items', 'pelanggan'])->where('no_po', $orderInput)->first();
            if (!$order) {
                $order = Order::with(['items', 'pelanggan'])->where('no_po', 'like', "%{$orderInput}%")->first();
            }
        }

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'PO tidak ditemukan dalam sistem.']);
        }

        if (!$user->hasAccessToBrand($order->brand_id)) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses ke PO dari brand ini.']);
        }

        if ($order->isDraft()) {
            return response()->json(['success' => false, 'message' => 'PO draft tidak bisa di-refund.']);
        }

        $existingRefundsSum = Refund::where('order_id', $order->id)
            ->whereIn('status', ['pending_review', 'approved', 'published'])
            ->sum('nominal_refund');

        $maxRefundable = max(0, $order->total_tagihan - $existingRefundsSum);

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'no_po' => $order->no_po,
                'nama_po' => $order->nama_po,
                'pelanggan_nama' => $order->pelanggan?->nama ?? '—',
                'total_tagihan' => (float)$order->total_tagihan,
                'max_refundable' => $maxRefundable,
                'items' => $order->items->map(fn($item) => [
                    'id' => $item->id,
                    'nama_produk' => $item->nama_produk,
                    'quantity' => (int)$item->quantity,
                    'harga_satuan' => (float)$item->harga_satuan,
                    'subtotal' => (float)$item->subtotal,
                ])
            ]
        ]);
    }

    private function buildRefundQuery(Request $request, ?string $selectedBrandId, ?array $accessibleBrandIds, ?array $effectiveIds): \Illuminate\Database\Eloquent\Builder
    {
        $query = Refund::query()
            ->when($selectedBrandId && $selectedBrandId !== 'all', fn ($q) => $q->where('brand_id', $selectedBrandId))
            ->when($effectiveIds, fn ($q) => $q->whereIn('brand_id', $effectiveIds))
            ->when(! $effectiveIds && (! $selectedBrandId || $selectedBrandId === 'all') && $accessibleBrandIds !== null,
                fn ($q) => $q->whereIn('brand_id', $accessibleBrandIds))
            ->with([
                'order:id,no_po,nama_po,pelanggan_id,brand_id,total_tagihan,is_special_order',
                'order.pelanggan:id,nama',
                'brand:id,nama_brand,kode',
                'creator:id,name',
                'reviewer:id,name',
                'publisher:id,name'
            ]);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('refund_number', 'like', "%{$search}%")
                  ->orWhereHas('order', fn ($x) => $x->where('no_po', 'like', "%{$search}%"));
            });
        }
        if ($startDate = $request->string('start_date')->toString()) {
            $query->where('created_at', '>=', \Illuminate\Support\Carbon::parse($startDate)->startOfDay());
        }
        if ($endDate = $request->string('end_date')->toString()) {
            $query->where('created_at', '<=', \Illuminate\Support\Carbon::parse($endDate)->endOfDay());
        }

        return $query;
    }

    private function getExportableRefunds(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Support\Collection
    {
        return (clone $query)->without(['order.pelanggan', 'brand', 'creator', 'reviewer', 'publisher'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($ref) => [
                'refund_number' => $ref->refund_number,
                'no_po' => $ref->order?->no_po ?? '—',
                'jenis_masalah' => $ref->jenis_masalah,
                'nominal_refund' => $ref->nominal_refund,
                'created_at' => $ref->created_at->toDateString(),
                'status' => $ref->status,
                'customer_bank_name' => $ref->customer_bank_name,
                'customer_bank_account' => $ref->customer_bank_account,
            ]);
    }

    private function resolveBankAccounts(Request $request, \Illuminate\Support\Collection $brands, \Illuminate\Support\Collection $rawBankAccounts): \Illuminate\Support\Collection
    {
        $bankAccounts = collect();
        foreach ($brands as $brand) {
            $brandId = $brand->id;
            $masterBrandId = BrandContext::masterDataId($request, $brandId);

            foreach ($rawBankAccounts as $bank) {
                if ($bank->brand_id === $brandId) {
                    $bankAccounts->push($bank);
                } elseif ($masterBrandId && $bank->brand_id === $masterBrandId) {
                    $cloned = clone $bank;
                    $cloned->brand_id = $brandId;
                    $bankAccounts->push($cloned);
                }
            }
        }

        return $bankAccounts->unique(function ($item) {
            return $item->id . '-' . $item->brand_id;
        })->values();
    }
}
