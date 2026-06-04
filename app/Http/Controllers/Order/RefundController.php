<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Models\Order\Refund;
use App\Services\NumberGenerator;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use App\Services\Notifications\DynamicNotificationService;
use Inertia\Inertia;

class RefundController extends Controller
{
    public function __construct(private readonly NumberGenerator $numbers) {}

    public function index(Request $request)
    {
        $brandId = BrandContext::current($request);
        $selectedBrandId = $request->input('brand_id', $brandId);

        $query = Refund::query()
            ->when($selectedBrandId && $selectedBrandId !== 'all', fn ($q) => $q->where('brand_id', $selectedBrandId))
            ->with(['order:id,no_po,nama_po,pelanggan_id', 'order.pelanggan:id,nama', 'creator:id,name']);

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
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->string('end_date')->toString()) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $allFiltered = (clone $query)->orderByDesc('created_at')->get()->map(fn ($ref) => [
            'refund_number' => $ref->refund_number,
            'no_po' => $ref->order?->no_po ?? '—',
            'jenis_masalah' => $ref->jenis_masalah,
            'nominal_refund' => $ref->nominal_refund,
            'created_at' => $ref->created_at->toDateString(),
            'status' => $ref->status,
        ]);

        $refunds = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $user = $request->user();
        $brands = $user->isSuperadmin()
            ? \App\Models\Brand::orderBy('nama_brand')->get(['id', 'nama_brand', 'kode'])
            : $user->brands()->orderBy('nama_brand')->get(['brands.id', 'nama_brand', 'kode']);

        return Inertia::render('Finance/RefundIndex', [
            'refunds' => $refunds,
            'all_filtered_refunds' => $allFiltered,
            'brands' => $brands,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
                'brand_id' => $request->string('brand_id', $selectedBrandId)->toString(),
                'start_date' => $request->string('start_date')->toString(),
                'end_date' => $request->string('end_date')->toString(),
            ],
            'statuses' => Refund::STATUSES,
            'jenis_options' => Refund::JENIS_MASALAH,
            'can' => [
                'create' => $request->user()->can('order.refund'),
                'review' => $request->user()->can('finance.manage-refund'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('order.refund');

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
            'order_id' => ['required', 'uuid', 'exists:orders,id'],
            'alasan' => ['required', 'string', 'min:5'],
            'jenis_masalah' => ['required', Rule::in(Refund::JENIS_MASALAH)],
            'jumlah_item' => ['required', 'integer', 'min:1'],
            'nominal_refund' => ['required', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string'],
        ], [
            'order_id.exists' => 'Nomor PO, Link PO, atau UUID PO tidak ditemukan dalam sistem.',
            'order_id.required' => 'Nomor PO, Link PO, atau UUID PO wajib diisi.',
            'order_id.uuid' => 'Format PO ID tidak valid.',
        ]);

        $order = Order::findOrFail($data['order_id']);
        abort_if($order->isDraft(), 422, 'PO draft tidak bisa di-refund.');

        $brandId = BrandContext::current($request);
        if (! $request->user()->isSuperadmin() && $order->brand_id !== $brandId) abort(403);

        if ($data['nominal_refund'] > $order->total_tagihan) {
            return back()->withErrors(['nominal_refund' => 'Nominal refund tidak boleh melebihi total tagihan PO.']);
        }

        $refund = Refund::create([
            ...$data,
            'brand_id' => $order->brand_id,
            'refund_number' => $this->numbers->generateRefundNumber($order->brand),
            'status' => 'pending_review',
            'created_by' => $request->user()->id,
        ]);

        DynamicNotificationService::dispatch('refund_submitted', [
            'no_po' => $order->no_po,
            'brand_id' => $order->brand_id,
            'brand_nama' => $order->brand?->nama_brand ?? $order->brand_id,
            'action_url' => '/refunds'
        ]);

        return back()->with('success', 'Refund berhasil diajukan dan menunggu review keuangan.');
    }

    public function publish(Request $request, Refund $refund)
    {
        Gate::authorize('finance.manage-refund');
        abort_unless(in_array($refund->status, ['pending_review', 'approved'], true), 422);

        $refund->update([
            'status' => 'published',
            'published_by' => $request->user()->id,
            'published_at' => now(),
        ]);

        \App\Services\ActivityLogger::log('publish', 'refund', $refund, "Terbitkan refund {$refund->refund_number}");

        DynamicNotificationService::dispatch('refund_processed', [
            'no_po' => $refund->order?->no_po,
            'brand_id' => $refund->brand_id,
            'brand_nama' => $refund->order?->brand?->nama_brand ?? $refund->brand_id,
            'status' => 'Diterima (Published)',
            'action_url' => '/refunds'
        ]);

        return back()->with('success', "Refund {$refund->refund_number} diterbitkan.");
    }

    public function reject(Request $request, Refund $refund)
    {
        Gate::authorize('finance.manage-refund');
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

        DynamicNotificationService::dispatch('refund_processed', [
            'no_po' => $refund->order?->no_po,
            'brand_id' => $refund->brand_id,
            'brand_nama' => $refund->order?->brand?->nama_brand ?? $refund->brand_id,
            'status' => 'Ditolak',
            'action_url' => '/refunds'
        ]);

        return back()->with('info', 'Refund ditolak. Pengaju dapat revisi dan ajukan ulang.');
    }
}
