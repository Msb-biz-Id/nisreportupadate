<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Master\BahanKain;
use App\Models\Master\JenisProduk;
use App\Models\Master\JenisSetelan;
use App\Models\Master\PaketOrder;
use App\Models\Master\PolaProduksi;
use App\Models\Master\BankAccount;
use App\Models\Master\Customer;
use App\Models\Master\Iklan;
use App\Models\Master\JenisOrder;
use App\Models\Master\KategoriOrder;
use App\Models\Master\Logo;
use App\Models\Master\PolaJahitan;
use App\Models\Master\Printing;
use App\Models\Master\Product;
use App\Models\Master\Resleting;
use App\Models\Master\Size;
use App\Models\Master\SumberOrder;
use App\Models\Order\Invoice;
use App\Models\Order\InvoiceItem;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\OrderNameset;
use App\Models\Order\OrderPayment;
use App\Services\NumberGenerator;
use App\Services\POStatusManager;
use App\Services\Notifications\DynamicNotificationService;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use App\Exports\POComprehensiveExport;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
{
    public function __construct(
        private readonly NumberGenerator $numbers,
        private readonly POStatusManager $statusManager,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('order.view');

        $user      = $request->user();
        $brandId   = BrandContext::current($request);
        $canSeeMultiBrand = $user->isSuperadmin() || $user->hasRole(['owner', 'admin_keuangan', 'admin_produksi', 'admin_reseller', 'admin_brand']);

        // Admin Brand dengan multiple brand: jika pilih brand_id=all → tampil semua brand mereka
        $filterBrandId = $request->string('brand_id')->toString();
        $userBrandIds  = $user->isSuperadmin() || $user->hasRole(['owner', 'admin_keuangan', 'admin_produksi'])
            ? null
            : $user->brands()->pluck('brands.id')->toArray();

        // Resolusi brand untuk base query
        $effectiveId = match(true) {
            $user->hasRole(['admin_produksi', 'admin_keuangan']) => null,
            $user->hasRole('admin_reseller')  => BrandContext::effectiveBrandIds($request),
            // admin_brand "Semua Brand": tampilkan semua brand yang di-assign ke mereka
            $user->hasRole('admin_brand') && ($filterBrandId === 'all' || empty($filterBrandId))
                => $userBrandIds ?? $brandId,
            default => $brandId,
        };

        $tab = $request->string('tab', 'active')->toString();

        $query = Order::query()
            ->forBrand($effectiveId)
            ->with(['pelanggan:id,nama', 'brand:id,nama_brand,kode', 'paketOrder:id,nama,warna,prioritas'])
            ->withCount(['items', 'progressDetails']);

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('no_po', 'like', "%{$search}%")
                  ->orWhere('nama_po', 'like', "%{$search}%")
                  ->orWhereHas('pelanggan', fn ($x) => $x->where('nama', 'like', "%{$search}%"));
            });
        }

        if ($user->hasRole('admin_produksi')) {
            $query->where('status_po', '!=', 'draft');
        }

        // Filter berdasarkan tab terlebih dahulu
        if ($tab === 'archive') {
            $query->where('orders.status_po', 'sudah_dikirim');
        } else {
            $query->where('orders.status_po', '!=', 'sudah_dikirim');
        }

        // Kemudian filter status jika dispesifikasikan (dan valid untuk tab tersebut)
        $status = $request->string('status')->toString();
        if ($status && $status !== 'all') {
            if ($tab === 'active' && $status === 'sudah_dikirim') {
                $query->whereRaw('1 = 0');
            } elseif ($tab === 'archive' && $status !== 'sudah_dikirim') {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('orders.status_po', $status);
            }
        }

        // Jika ada filter brand spesifik (bukan 'all') → drill-down ke brand tertentu
        if ($canSeeMultiBrand && $filterBrandId && $filterBrandId !== 'all') {
            $query->where('orders.brand_id', $filterBrandId);
        }

        if ($dateFrom = $request->string('date_from')->toString()) {
            $query->whereDate('tanggal_masuk', '>=', $dateFrom);
        }
        if ($dateTo = $request->string('date_to')->toString()) {
            $query->whereDate('tanggal_masuk', '<=', $dateTo);
        }

        // Summary per status — query terpisah tanpa with/withCount agar GROUP BY aman
        $statusCounts = Order::query()
            ->forBrand($effectiveId)
            ->when($user->hasRole('admin_produksi'), fn ($q) => $q->where('status_po', '!=', 'draft'))
            ->when($canSeeMultiBrand && $filterBrandId && $filterBrandId !== 'all', fn ($q) => $q->where('orders.brand_id', $filterBrandId))
            ->when($request->string('q')->toString(), fn ($q, $v) => $q->where(function ($w) use ($v) {
                $w->where('no_po', 'like', "%{$v}%")->orWhere('nama_po', 'like', "%{$v}%");
            }))
            ->when($request->string('date_from')->toString(), fn ($q, $v) => $q->whereDate('tanggal_masuk', '>=', $v))
            ->when($request->string('date_to')->toString(), fn ($q, $v) => $q->whereDate('tanggal_masuk', '<=', $v))
            ->when($tab === 'archive', fn ($q) => $q->where('status_po', 'sudah_dikirim'))
            ->when($tab === 'active', fn ($q) => $q->where('status_po', '!=', 'sudah_dikirim'))
            ->selectRaw('status_po, count(*) as total')
            ->groupBy('status_po')
            ->pluck('total', 'status_po')
            ->toArray();

        $orders = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        // admin_reseller only sees their own branches; superadmin/owner see all
        $brands = match (true) {
            ! $canSeeMultiBrand => [],
            $user->isSuperadmin() || $user->hasRole(['owner', 'admin_keuangan', 'admin_produksi'])
                => Brand::orderBy('nama_brand')->get(['id', 'nama_brand', 'kode']),
            default => $user->brands()->orderBy('nama_brand')->get(['brands.id', 'brands.nama_brand', 'brands.kode']),
        };

        $visibleStatuses = $user->hasRole('admin_produksi')
            ? array_values(array_filter(Order::STATUSES, fn ($s) => $s !== 'draft'))
            : Order::STATUSES;

        if ($tab === 'active') {
            $visibleStatuses = array_values(array_filter($visibleStatuses, fn ($s) => $s !== 'sudah_dikirim'));
        } else {
            $visibleStatuses = ['sudah_dikirim'];
        }

        return Inertia::render('Order/Index', [
            'orders' => $orders,
            'filters' => [
                'q'        => $request->string('q')->toString(),
                'status'   => $request->string('status')->toString(),
                'brand_id' => $request->string('brand_id')->toString(),
                'date_from' => $request->string('date_from')->toString(),
                'date_to'   => $request->string('date_to')->toString(),
                'tab'       => $tab,
            ],
            'statuses'     => $visibleStatuses,
            'statusCounts' => $statusCounts,
            'brands'       => $brands,
            'can' => [
                'create'          => $user->can('order.create'),
                'update'          => $user->can('order.update'),
                'delete'          => $user->can('order.delete'),
                'publish'         => $user->can('order.publish'),
                'filter_by_brand' => $canSeeMultiBrand,
            ],
        ]);
    }

    public function exportComprehensive(Request $request)
    {
        Gate::authorize('order.view');

        $user      = $request->user();
        $brandId   = BrandContext::current($request);
        $canSeeMultiBrand = $user->isSuperadmin() || $user->hasRole(['owner', 'admin_keuangan', 'admin_produksi', 'admin_reseller', 'admin_brand']);

        $filterBrandId = $request->string('brand_id')->toString();
        $userBrandIds  = $user->isSuperadmin() || $user->hasRole(['owner', 'admin_keuangan', 'admin_produksi'])
            ? null
            : $user->brands()->pluck('brands.id')->toArray();

        $effectiveId = match(true) {
            $user->hasRole(['admin_produksi', 'admin_keuangan']) => null,
            $user->hasRole('admin_reseller')  => BrandContext::effectiveBrandIds($request),
            $user->hasRole('admin_brand') && ($filterBrandId === 'all' || empty($filterBrandId))
                => $userBrandIds ?? $brandId,
            default => $brandId,
        };

        $tab = $request->string('tab', 'active')->toString();

        $query = Order::query()
            ->forBrand($effectiveId)
            ->with([
                'brand:id,nama_brand,kode',
                'pelanggan:id,nama',
                'progressDetails.progress',
                'rijeks.progress',
                'payments.bank',
                'payments.masterJenisPembayaran'
            ]);

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('no_po', 'like', "%{$search}%")
                  ->orWhere('nama_po', 'like', "%{$search}%")
                  ->orWhereHas('pelanggan', fn ($x) => $x->where('nama', 'like', "%{$search}%"));
            });
        }

        if ($user->hasRole('admin_produksi')) {
            $query->where('status_po', '!=', 'draft');
        }

        $status = $request->string('status')->toString();
        if ($status && $status !== 'all') {
            $query->where('orders.status_po', $status);
        } else {
            if ($tab === 'archive') {
                $query->where('orders.status_po', 'sudah_dikirim');
            } else {
                $query->where('orders.status_po', '!=', 'sudah_dikirim');
            }
        }

        if ($canSeeMultiBrand && $filterBrandId && $filterBrandId !== 'all') {
            $query->where('orders.brand_id', $filterBrandId);
        }

        if ($dateFrom = $request->string('date_from')->toString()) {
            $query->whereDate('tanggal_masuk', '>=', $dateFrom);
        }
        if ($dateTo = $request->string('date_to')->toString()) {
            $query->whereDate('tanggal_masuk', '<=', $dateTo);
        }

        $orders = $query->orderByDesc('created_at')->get()->all();

        $filename = 'comprehensive-po-export-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new POComprehensiveExport('Master PO Export', $orders),
            $filename
        );
    }

    public function create(Request $request)
    {
        Gate::authorize('order.create');
        $user    = $request->user();
        $brandId = BrandContext::current($request);
        abort_unless($brandId, 400, 'Brand aktif belum dipilih');

        $masterBrandId = BrandContext::masterDataId($request, $brandId);

        return Inertia::render('Order/Form', [
            'mode'    => 'create',
            'masters' => $this->mastersForForm($masterBrandId, $brandId),
            'order'   => null,
            'current_brand_id' => $brandId,
            // Untuk admin_reseller: kirim brand lain yang mereka kelola sebagai opsi di form
            'reseller_branches' => $user->hasRole('admin_reseller')
                ? $this->resolveResellerBrandsForForm($user, $brandId)
                : [],
            'is_reseller_hub' => $user->hasRole('admin_reseller') && $user->brands()->count() > 1,
        ]);
    }

    public function edit(Request $request, Order $order)
    {
        Gate::authorize('order.update');
        $this->guardBrandOwnership($request, $order);

        if (! $order->isDraft() && $order->isLocked()) {
            return redirect()->route('orders.show', $order->id)
                ->with('error', 'PO sudah ter-lock. Untuk edit, lakukan unlock dengan alasan dari halaman preview.');
        }

        $order->load(['items.namesets', 'items.polaJahitan', 'items.polaJahitanLengan', 'items.bahanKain', 'items.bahanKainBawahan', 'items.logo', 'payments.bank']);
        $order->bank_id = $order->invoices()->first()?->bank_id;

        $orderBrand = Brand::find($order->brand_id);
        $masterBrandId = $orderBrand?->isResellerBranch() && $orderBrand->parent_brand_id
            ? $orderBrand->parent_brand_id
            : $order->brand_id;

        return Inertia::render('Order/Form', [
            'mode' => 'edit',
            'masters' => $this->mastersForForm($masterBrandId, $order->brand_id),
            'order' => $order,
            'current_brand_id' => $order->brand_id,
            'reseller_branches' => [],
            'is_reseller_hub' => false,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('order.create');
        $brandId = BrandContext::current($request);
        abort_unless($brandId, 400);

        $data = $this->validatePayload($request);
        $user = $request->user();

        // Jika admin_reseller memilih brand lain dari dropdown, gunakan brand tersebut
        // (bisa hub independen lain seperti Pamos, Sfitt, dll)
        $effectiveBrandId = $brandId;
        if (! empty($data['branch_brand_id'])) {
            // Validasi: brand yang dipilih harus accessible oleh user ini
            $selectedBrand = Brand::where('id', $data['branch_brand_id'])->first();
            abort_unless(
                $selectedBrand && ($user->isSuperadmin() || $user->hasAccessToBrand($selectedBrand->id)),
                403, 'Tidak memiliki akses ke brand tersebut.'
            );
            $effectiveBrandId = $selectedBrand->id;
        }

        $brand = Brand::findOrFail($effectiveBrandId);

        $order = DB::transaction(function () use ($brand, $data, $user) {
            $order = Order::create([
                'brand_id' => $brand->id,
                'no_po' => $this->numbers->generateOrderNumber($brand, $data['nama_po']),
                'nama_po' => $data['nama_po'],
                'status_po' => 'draft',
                'is_special_order' => $data['is_special_order'] ?? false,
                'tanggal_masuk' => $data['tanggal_masuk'],
                'deadline_customer' => $data['deadline_customer'],
                'kategori_order_id' => $data['kategori_order_id'] ?? null,
                'jenis_order_id' => $data['jenis_order_id'] ?? null,
                'sumber_order_id' => $data['sumber_order_id'] ?? null,
                'paket_order_id'   => $data['paket_order_id'] ?? null,
                'jenis_setelan_id' => $data['jenis_setelan_id'] ?? null,
                'pola_produksi_id' => $data['pola_produksi_id'] ?? null,
                'pelanggan_id' => $data['pelanggan_id'],
                'printing_ids' => $data['printing_ids'] ?? null,
                'iklan_id' => $data['iklan_id'] ?? null,
                'catatan' => $data['catatan'] ?? null,
                'created_by' => $user->id,
            ]);

            $this->syncItems($order, $data['items'] ?? []);
            $order->update(['total_tagihan' => $order->is_special_order ? 0.0 : $order->items()->sum('subtotal')]);

            $order->load('items');
            $totalTagihan = (float) $order->total_tagihan;
            $dp = 0;

            $invoice = Invoice::create([
                'brand_id'        => $order->brand_id,
                'order_id'        => $order->id,
                'invoice_number'  => $this->numbers->generateInvoiceNumber($brand, $order),
                'tanggal_terbit'  => now()->toDateString(),
                'jatuh_tempo'     => now()->addDays(14)->toDateString(),
                'status'          => 'draft',
                'total_tagihan'   => $totalTagihan,
                'bank_id'         => $data['bank_id'],
                'dp_amount'       => $dp,
                'sisa_pembayaran' => $order->is_special_order ? 0.0 : max(0, $totalTagihan - $dp),
                'created_by'      => $user->id,
            ]);

            foreach ($order->items as $item) {
                InvoiceItem::create([
                    'invoice_id'   => $invoice->id,
                    'produk'       => $item->nama_produk . ($item->varian_label ? " ({$item->varian_label})" : ''),
                    'jumlah'       => $item->quantity,
                    'harga_satuan' => $item->harga_satuan,
                    'subtotal'     => $item->subtotal,
                    'is_addon'     => (bool) $item->is_addon,
                    'discount_type' => $item->discount_type,
                    'discount_value' => $item->discount_value,
                    'discount_amount' => $item->discount_amount,
                ]);
            }

            return $order;
        });

        return redirect()->route('orders.show', $order->id)->with('success', 'PO draft berhasil dibuat.');
    }

    public function update(Request $request, Order $order)
    {
        Gate::authorize('order.update');
        $this->guardBrandOwnership($request, $order);

        if (! $order->isDraft() && $order->isLocked()) {
            abort(403, 'PO ter-lock');
        }

        $data = $this->validatePayload($request);
        $user = $request->user();

        DB::transaction(function () use ($order, $data, $user) {
            $updateData = [
                'nama_po' => $data['nama_po'],
                'is_special_order' => $data['is_special_order'] ?? false,
                'tanggal_masuk' => $data['tanggal_masuk'],
                'deadline_customer' => $data['deadline_customer'],
                'kategori_order_id' => $data['kategori_order_id'] ?? null,
                'jenis_order_id' => $data['jenis_order_id'] ?? null,
                'sumber_order_id' => $data['sumber_order_id'] ?? null,
                'paket_order_id'   => $data['paket_order_id'] ?? null,
                'jenis_setelan_id' => $data['jenis_setelan_id'] ?? null,
                'pola_produksi_id' => $data['pola_produksi_id'] ?? null,
                'pelanggan_id' => $data['pelanggan_id'],
                'printing_ids' => $data['printing_ids'] ?? null,
                'iklan_id' => $data['iklan_id'] ?? null,
                'catatan' => $data['catatan'] ?? null,
                'updated_by' => $user->id,
            ];


            $order->update($updateData);

            $this->syncItems($order, $data['items'] ?? []);
            $order->update(['total_tagihan' => $order->is_special_order ? 0.0 : $order->items()->sum('subtotal')]);

            // Sync invoice
            $invoice = $order->invoices()->first();
            if ($invoice) {
                $invoice->items()->delete();
                foreach ($order->items as $item) {
                    InvoiceItem::create([
                        'invoice_id'   => $invoice->id,
                        'produk'       => $item->nama_produk . ($item->varian_label ? " ({$item->varian_label})" : ''),
                        'jumlah'       => $item->quantity,
                        'harga_satuan' => $item->harga_satuan,
                        'subtotal'     => $item->subtotal,
                        'is_addon'     => (bool) $item->is_addon,
                        'discount_type' => $item->discount_type,
                        'discount_value' => $item->discount_value,
                        'discount_amount' => $item->discount_amount,
                    ]);
                }

                $totalTagihan = $order->totalTagihan();
                $totalPaid = $order->totalPaid();
                $newSisa = $order->is_special_order ? 0.0 : max(0, $totalTagihan - $totalPaid);

                $invoice->update([
                    'total_tagihan' => $totalTagihan,
                    'total_bayar' => $totalPaid,
                    'sisa_pembayaran' => $newSisa,
                    'status' => $newSisa <= 0 ? 'paid' : $invoice->status,
                    'bank_id' => $data['bank_id'],
                ]);
            }
        });

        return redirect()->route('orders.show', $order->id)->with('success', 'PO berhasil diperbarui.');
    }

    public function show(Request $request, Order $order)
    {
        Gate::authorize('order.view');
        $this->guardBrandOwnership($request, $order);

        if ($request->user()->hasRole('admin_produksi') && $order->status_po === 'draft') {
            abort(403, 'Draft PO tidak dapat diakses oleh Admin Produksi.');
        }

        $order->load([
            'brand', 'pelanggan', 'kategoriOrder', 'jenisOrder', 'sumberOrder', 'paketOrder', 'iklan',
            'items.namesets.size', 'items.namesets.sizeCelana',
            'items.bahanKain', 'items.bahanKainBawahan',
            'items.logo', 'items.printing', 'items.resleting',
            'items.polaJahitan', 'items.polaJahitanLengan',
            'items.jenisProduk', 'items.jenisSetelan', 'items.polaProduksi',
            'payments.bank', 'payments.recorder', 'payments.verifier', 'payments.masterJenisPembayaran',
            'progressDetails.progress', 'progressDetails.updater',
            'rijeks.progress', 'rijeks.creator',
            'lockStatus.lockedBy', 'lockStatus.unlockRequestedBy.roles', 'lockStatus.relockRequestedBy.roles',
            'changeLogs.changer.roles',
            'invoices',
            'refunds.creator', 'refunds.publisher',
            'repeats', 'repeatFrom',
        ]);

        foreach ($order->items as $item) {
            // Resolve multi-field names untuk Preview
            $item->logo_names = !empty($item->logo_ids)
                ? \App\Models\Master\Logo::whereIn('id', $item->logo_ids)->pluck('nama')->toArray()
                : [];
            $item->bahan_kains_names = !empty($item->bahan_kain_ids)
                ? \App\Models\Master\BahanKain::whereIn('id', $item->bahan_kain_ids)->pluck('nama')->implode(', ')
                : null;
            $item->bahan_kain_bawahan_names = !empty($item->bahan_kain_bawahan_ids)
                ? \App\Models\Master\BahanKain::whereIn('id', $item->bahan_kain_bawahan_ids)->pluck('nama')->implode(', ')
                : null;
        }

        $printings = collect();
        if (!empty($order->printing_ids)) {
            $printings = \App\Models\Master\Printing::whereIn('id', $order->printing_ids)->get(['id', 'nama']);
        }

        $brandId = $order->brand_id;
        $banks = BankAccount::active()->where('brand_id', $brandId)->orderBy('bank')->get(['id', 'bank', 'atas_nama', 'nomor_rekening']);
        $jenis_pembayarans = \App\Models\Finance\MasterJenisPembayaran::active()->orderBy('nama')->get(['id', 'nama']);

        // Computed DP info — frontend uses these exact values to stay in sync with backend
        $minDpPct      = $order->brand ? (float) ($order->brand->min_dp_percentage ?? 0.50) : 0.50;
        $computedTotal = (float) $order->totalTagihan();
        $computedPaid  = (float) $order->totalPaid();

        return Inertia::render('Order/Preview', [
            'order' => $order,
            'printings' => $printings,
            'banks' => $banks,
            'jenis_pembayarans' => $jenis_pembayarans,
            'dp_info' => [
                'total_tagihan'    => $computedTotal,
                'total_paid'       => $computedPaid,
                'min_dp_percentage' => $minDpPct,
                'min_dp'           => $computedTotal * $minDpPct,
                'is_sufficient'    => $computedPaid >= ($computedTotal * $minDpPct) || $order->is_dp_bypassed,
            ],
            'can' => [
                'edit' => $request->user()->can('order.update') && ($order->isDraft() || ! $order->isLocked()),
                'delete' => $request->user()->can('order.delete') && $order->isDraft(),
                'publish' => $request->user()->can('order.publish') && $order->isDraft(),
                'unlock' => $request->user()->can('order.unlock'),
                'bypass_dp' => $request->user()->hasRole('superadmin') || $request->user()->hasRole('owner') || $request->user()->hasRole('admin_keuangan'),
                'repeat' => $request->user()->can('order.create') && ! $order->isDraft(),
                'manage_invoice' => $request->user()->can('finance.manage-invoice'),
                'delete_payment' => $request->user()->can('finance.manage-invoice') && !$request->user()->hasRole('admin_brand'),
                'edit_payment' => $request->user()->can('finance.manage-invoice') && !$request->user()->hasRole('admin_brand'),
                'add_payment' => ! $request->user()->hasRole('admin_produksi'),
                'edit_timeline' => $request->user()->hasRole('admin_produksi'),
                'mark_lunas' => $request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan'),
            ],
        ]);
    }

    public function publish(Request $request, Order $order)
    {
        Gate::authorize('order.publish');
        $this->guardBrandOwnership($request, $order);
        abort_unless($order->isDraft(), 422, 'PO sudah diterbitkan.');
        abort_if($order->items()->count() === 0, 422, 'PO tanpa produk tidak bisa diterbitkan.');

        // DP check: total verified payments must reach the brand's minimum DP percentage.
        // Invoice is NOT required — production can start once DP is validated by admin keuangan.
        $brand = $order->brand;
        $minDpPercentage = $brand ? (float) ($brand->min_dp_percentage ?? 0.50) : 0.50;

        $totalTagihan = (float) $order->totalTagihan();
        $totalPaid    = (float) $order->totalPaid();
        $minDp        = $totalTagihan * $minDpPercentage;
        $minDpPercent = number_format($minDpPercentage * 100, 0) . '%';

        if ($order->is_special_order) {
            if (! $order->is_dp_bypassed) {
                return back()->with('error', 'PO tidak bisa diterbitkan. Untuk Special Order, silakan hubungi Admin Keuangan untuk menyetujui/melakukan bypass terlebih dahulu.');
            }
        } else {
            if ($totalPaid < $minDp && ! $order->is_dp_bypassed) {
                return back()->with('error',
                    'PO tidak bisa diterbitkan. Pembayaran terverifikasi Rp ' .
                    number_format($totalPaid, 0, ',', '.') .
                    ' belum mencapai minimal ' . $minDpPercent . ' DP (Rp ' .
                    number_format($minDp, 0, ',', '.') . '). ' .
                    'Minta Admin Keuangan memvalidasi pembayaran DP atau lakukan bypass.'
                );
            }
        }

        $this->statusManager->publish($order, $request->user());

        \App\Services\ActivityLogger::log('publish', 'order', $order, "Publish PO {$order->no_po}");

        DynamicNotificationService::dispatch('order_published', [
            'no_po' => $order->no_po,
            'brand_id' => $order->brand_id,
            'brand_nama' => $order->brand?->nama_brand ?? $order->brand_id,
            'action_url' => "/orders/{$order->id}"
        ]);

        return back()->with('success', 'PO berhasil diterbitkan dan masuk ke dashboard produksi.');
    }

    public function repeat(Request $request, Order $order)
    {
        Gate::authorize('order.create');
        $this->guardBrandOwnership($request, $order);

        $user = $request->user();
        $brand = $order->brand;

        $newOrder = DB::transaction(function () use ($order, $user, $brand) {
            $clone = $order->replicate([
                'no_po', 'status_po', 'is_repeat_order', 'repeat_from_po_id',
                'published_at', 'published_by', 'created_at', 'updated_at',
            ]);
            $clone->no_po = $this->numbers->generateOrderNumber($brand, $order->nama_po);
            $clone->status_po = 'draft';
            $clone->is_repeat_order = true;
            $clone->repeat_from_po_id = $order->id;
            $clone->tanggal_masuk = now()->toDateString();
            $clone->published_at = null;
            $clone->published_by = null;
            $clone->created_by = $user->id;
            $clone->updated_by = null;
            $clone->save();

            foreach ($order->items as $item) {
                $newItem = $item->replicate(['order_id']);
                $newItem->order_id = $clone->id;
                $newItem->save();

                foreach ($item->namesets as $ns) {
                    $newNs = $ns->replicate(['order_item_id']);
                    $newNs->order_item_id = $newItem->id;
                    $newNs->save();
                }
            }
            $clone->update(['total_tagihan' => $clone->items()->sum('subtotal')]);

            return $clone;
        });

        return redirect()->route('orders.edit', $newOrder->id)
            ->with('success', 'PO baru hasil repeat order. Silakan review & terbitkan.');
    }

    public function destroy(Request $request, Order $order)
    {
        Gate::authorize('order.delete');
        $this->guardBrandOwnership($request, $order);
        abort_unless($order->isDraft(), 422, 'Hanya PO draft yang bisa dihapus.');

        if ($order->payments()->exists()) {
            return back()->with('error', 'PO draft yang sudah memiliki transaksi pembayaran tidak dapat dihapus. Anda harus menyelesaikan transaksi pembayaran tersebut (misalnya, dengan menghapus pembayaran jika belum diverifikasi, atau memproses pengembalian dana/refund jika pembayaran sudah diverifikasi).');
        }

        $order->delete();
        return redirect()->route('orders.index')->with('success', 'PO draft berhasil dihapus.');
    }

    public function unlock(Request $request, Order $order)
    {
        $this->guardBrandOwnership($request, $order);
        abort_if($order->isDraft(), 422);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:8', 'max:1000'],
        ]);

        $user = $request->user();

        // Jika user memiliki permission order.unlock (Superadmin/Owner/Supervisor), unlock langsung!
        if ($user->can('order.unlock')) {
            $this->statusManager->unlock($order, $user);
            
            // Clear any pending unlock requests
            $lock = $order->lockStatus;
            if ($lock) {
                $lock->update([
                    'unlock_requested_by' => null,
                    'unlock_request_reason' => null,
                    'unlock_requested_at' => null,
                ]);
            }

            $this->statusManager->logChange($order, $user, $data['reason'], '_unlock', 'locked', 'unlocked');
            \App\Services\ActivityLogger::log('unlock', 'order', $order, "Unlock PO {$order->no_po} secara langsung dengan alasan: " . $data['reason']);

            return back()->with('info', 'PO di-unlock. Lakukan perubahan, kemudian re-lock untuk mengembalikan proteksi.');
        }

        // Jika user TIDAK memiliki permission order.unlock (Admin Brand/Reseller/Produksi), ajukan permohonan!
        $lock = $order->lockStatus;
        if ($lock) {
            $lock->update([
                'unlock_requested_by' => $user->id,
                'unlock_request_reason' => $data['reason'],
                'unlock_requested_at' => now(),
            ]);
        } else {
            $order->lockStatus()->create([
                'is_locked' => true,
                'locked_at' => now(),
                'locked_by' => $user->id,
                'unlock_requested_by' => $user->id,
                'unlock_request_reason' => $data['reason'],
                'unlock_requested_at' => now(),
            ]);
        }

        \App\Services\ActivityLogger::log('unlock_request', 'order', $order, "Mengajukan permohonan unlock PO {$order->no_po} dengan alasan: " . $data['reason']);

        return back()->with('success', 'Permohonan unlock PO telah diajukan ke Superadmin/Supervisor.');
    }

    public function approveUnlock(Request $request, Order $order)
    {
        abort_unless($request->user()->can('order.unlock'), 403, 'Anda tidak memiliki hak untuk menyetujui unlock PO.');
        $this->guardBrandOwnership($request, $order);

        $lock = $order->lockStatus;
        if (!$lock || !$lock->unlock_requested_by) {
            return back()->with('error', 'Tidak ada permohonan unlock aktif.');
        }

        $requester = \App\Models\User::find($lock->unlock_requested_by);
        $reason = $lock->unlock_request_reason;

        $this->statusManager->unlock($order, $request->user());

        // Log change with requester as changer and approver as approved_by
        \App\Models\Order\POChangeLog::create([
            'order_id' => $order->id,
            'changed_by' => $requester->id,
            'approved_by' => $request->user()->id,
            'change_reason' => $reason,
            'field_changed' => '_unlock',
            'old_value' => 'locked',
            'new_value' => 'unlocked',
        ]);

        // Clear request
        $lock->update([
            'unlock_requested_by' => null,
            'unlock_request_reason' => null,
            'unlock_requested_at' => null,
        ]);

        \App\Services\ActivityLogger::log('unlock_approve', 'order', $order, "Menyetujui permohonan unlock PO {$order->no_po} oleh {$requester->name}");

        return back()->with('info', 'Permohonan unlock disetujui. PO berhasil dibuka kuncinya.');
    }

    public function rejectUnlock(Request $request, Order $order)
    {
        abort_unless($request->user()->can('order.unlock'), 403, 'Anda tidak memiliki hak untuk menolak permohonan unlock PO.');
        $this->guardBrandOwnership($request, $order);

        $lock = $order->lockStatus;
        if (!$lock || !$lock->unlock_requested_by) {
            return back()->with('error', 'Tidak ada permohonan unlock aktif.');
        }

        $requester = \App\Models\User::find($lock->unlock_requested_by);

        // Clear request
        $lock->update([
            'unlock_requested_by' => null,
            'unlock_request_reason' => null,
            'unlock_requested_at' => null,
        ]);

        \App\Services\ActivityLogger::log('unlock_reject', 'order', $order, "Menolak permohonan unlock PO {$order->no_po} oleh {$requester->name}");

        return back()->with('info', 'Permohonan unlock PO telah ditolak.');
    }

    public function relock(Request $request, Order $order)
    {
        $this->guardBrandOwnership($request, $order);
        $user = $request->user();

        // Jika user memiliki permission order.unlock (Superadmin/Owner/Supervisor), relock langsung!
        if ($user->can('order.unlock')) {
            $this->statusManager->relock($order, $user);

            // Clear any pending relock requests
            $lock = $order->lockStatus;
            if ($lock) {
                $lock->update([
                    'relock_requested_by' => null,
                    'relock_request_reason' => null,
                    'relock_requested_at' => null,
                ]);
            }

            $this->statusManager->logChange($order, $user, 'Re-lock PO secara langsung', '_relock', 'unlocked', 'locked');
            \App\Services\ActivityLogger::log('relock', 'order', $order, "Re-lock PO {$order->no_po} secara langsung");

            return back()->with('success', 'PO kembali ter-lock.');
        }

        // Jika user TIDAK memiliki permission order.unlock (Admin Brand/Reseller/Produksi), ajukan permohonan!
        $reason = $request->input('reason', 'Re-lock PO requested by Admin');
        $lock = $order->lockStatus;
        if ($lock) {
            $lock->update([
                'relock_requested_by' => $user->id,
                'relock_request_reason' => $reason,
                'relock_requested_at' => now(),
            ]);
        } else {
            $order->lockStatus()->create([
                'is_locked' => false,
                'locked_at' => now(),
                'locked_by' => $user->id,
                'relock_requested_by' => $user->id,
                'relock_request_reason' => $reason,
                'relock_requested_at' => now(),
            ]);
        }

        \App\Services\ActivityLogger::log('relock_request', 'order', $order, "Mengajukan permohonan re-lock PO {$order->no_po}");

        return back()->with('success', 'Permohonan re-lock PO telah diajukan ke Superadmin/Supervisor.');
    }

    public function approveRelock(Request $request, Order $order)
    {
        abort_unless($request->user()->can('order.unlock'), 403, 'Anda tidak memiliki hak untuk menyetujui re-lock PO.');
        $this->guardBrandOwnership($request, $order);

        $lock = $order->lockStatus;
        if (!$lock || !$lock->relock_requested_by) {
            return back()->with('error', 'Tidak ada permohonan re-lock aktif.');
        }

        $requester = \App\Models\User::find($lock->relock_requested_by);
        $reason = $lock->relock_request_reason;

        $this->statusManager->relock($order, $request->user());

        // Log change
        \App\Models\Order\POChangeLog::create([
            'order_id' => $order->id,
            'changed_by' => $requester->id,
            'approved_by' => $request->user()->id,
            'change_reason' => $reason,
            'field_changed' => '_relock',
            'old_value' => 'unlocked',
            'new_value' => 'locked',
        ]);

        // Clear request
        $lock->update([
            'relock_requested_by' => null,
            'relock_request_reason' => null,
            'relock_requested_at' => null,
        ]);

        \App\Services\ActivityLogger::log('relock_approve', 'order', $order, "Menyetujui permohonan re-lock PO {$order->no_po} oleh {$requester->name}");

        return back()->with('success', 'Permohonan re-lock disetujui. PO berhasil dikunci.');
    }

    public function rejectRelock(Request $request, Order $order)
    {
        abort_unless($request->user()->can('order.unlock'), 403, 'Anda tidak memiliki hak untuk menolak permohonan re-lock PO.');
        $this->guardBrandOwnership($request, $order);

        $lock = $order->lockStatus;
        if (!$lock || !$lock->relock_requested_by) {
            return back()->with('error', 'Tidak ada permohonan re-lock aktif.');
        }

        $requester = \App\Models\User::find($lock->relock_requested_by);

        // Clear request
        $lock->update([
            'relock_requested_by' => null,
            'relock_request_reason' => null,
            'relock_requested_at' => null,
        ]);

        \App\Services\ActivityLogger::log('relock_reject', 'order', $order, "Menolak permohonan re-lock PO {$order->no_po} oleh {$requester->name}");

        return back()->with('info', 'Permohonan re-lock PO telah ditolak.');
    }

    public function bypassDp(Request $request, Order $order)
    {
        $user = $request->user();
        abort_unless(
            $user->hasRole('superadmin') || $user->hasRole('owner') || $user->hasRole('admin_keuangan'),
            403, 'Anda tidak memiliki akses bypass DP.'
        );
        abort_unless($order->isDraft(), 422, 'Bypass DP hanya berlaku untuk PO yang masih draft.');
        $this->guardBrandOwnership($request, $order);

        $enabling = ! $order->is_dp_bypassed;

        $order->update([
            'is_dp_bypassed' => $enabling,
            'dp_bypassed_by' => $enabling ? $user->id : null,
            'dp_bypassed_at' => $enabling ? now()      : null,
        ]);

        $msg = $enabling
            ? 'Bypass DP diaktifkan. Admin Brand sekarang dapat menerbitkan PO meskipun DP belum ' . number_format(($order->brand?->min_dp_percentage ?? 0.5) * 100, 0) . '%.'
            : 'Bypass DP dinonaktifkan. Syarat DP minimal berlaku kembali.';

        return back()->with('success', $msg);
    }

    public function addPayment(Request $request, Order $order)
    {
        Gate::authorize('order.update');
        $this->guardBrandOwnership($request, $order);

        $data = $request->validate([
            'master_jenis_pembayaran_id' => ['required', 'uuid', 'exists:master_jenis_pembayarans,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date'],
            'bank_id' => ['nullable', 'uuid', 'exists:bank_accounts,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $isFinanceOrAdmin = $user->hasRole('superadmin') || $user->hasRole('owner') || $user->hasRole('admin_keuangan');

        $payment = OrderPayment::create([
            ...$data,
            'order_id' => $order->id,
            'recorded_by' => $user->id,
            'verified_by' => $isFinanceOrAdmin ? $user->id : null,
            'verified_at' => $isFinanceOrAdmin ? now() : null,
        ]);

        // Also dynamically recalculate/update order's total_tagihan field to match dynamic totalTagihan()
        if ($payment->verified_at !== null) {
            $order->update(['total_tagihan' => $order->totalTagihan()]);
        }

        if (!$isFinanceOrAdmin) {
            \App\Services\Notifications\DynamicNotificationService::dispatch('payment_submitted', [
                'no_po' => $order->no_po,
                'brand_id' => $order->brand_id,
                'brand_nama' => $order->brand?->nama_brand ?? $order->brand_id,
                'nominal' => 'Rp ' . number_format($payment->amount, 0, ',', '.'),
                'action_url' => "/invoices"
            ]);
            return back()->with('success', 'Transaksi berhasil dicatat. Menunggu validasi dari Admin Keuangan.');
        }

        // For verified ones, immediately update the order total_tagihan
        $order->update(['total_tagihan' => $order->totalTagihan()]);

        return back()->with('success', 'Transaksi berhasil dicatat dan diverifikasi.');
    }

    public function draftPdf(Request $request): \Illuminate\Http\Response
    {
        abort_unless(auth()->check(), 401);

        $brandId = BrandContext::current($request);
        $brand   = Brand::findOrFail($brandId);
        $raw     = $request->all();

        $pelanggan  = !empty($raw['pelanggan_id'])      ? Customer::find($raw['pelanggan_id'])           : null;
        $kategori   = !empty($raw['kategori_order_id']) ? KategoriOrder::find($raw['kategori_order_id']) : null;
        $jenisOrder = !empty($raw['jenis_order_id'])    ? JenisOrder::find($raw['jenis_order_id'])       : null;
        $sumber     = !empty($raw['sumber_order_id'])   ? SumberOrder::find($raw['sumber_order_id'])     : null;
        $paketOrder = !empty($raw['paket_order_id'])    ? PaketOrder::find($raw['paket_order_id'])       : null;

        $items = collect($raw['items'] ?? [])->map(function ($item) {
            $item['_bahan_kain']   = !empty($item['bahan_kain_id'])   ? BahanKain::find($item['bahan_kain_id'])?->nama   : null;
            // Multi bahan atasan — gabungkan nama
            $item['_bahan_kain_names'] = !empty($item['bahan_kain_ids'])
                ? BahanKain::whereIn('id', $item['bahan_kain_ids'])->pluck('nama')->implode(', ')
                : ($item['_bahan_kain'] ?? null);
            $item['_bahan_kain_bawahan'] = !empty($item['bahan_kain_bawahan_id']) ? BahanKain::find($item['bahan_kain_bawahan_id'])?->nama : null;
            $item['_bahan_kain_bawahan_names'] = !empty($item['bahan_kain_bawahan_ids'])
                ? BahanKain::whereIn('id', $item['bahan_kain_bawahan_ids'])->pluck('nama')->implode(', ')
                : ($item['_bahan_kain_bawahan'] ?? null);
            $item['_logo']         = !empty($item['logo_id'])         ? Logo::find($item['logo_id'])?->nama             : null;
            $item['_logos']        = !empty($item['logo_ids'])        ? Logo::whereIn('id', $item['logo_ids'])->pluck('nama')->toArray() : [];
            // Pola Jahitan Config: { "Kerah": "uuid", ... } → { "Kerah": "nama pola" }
            $item['_pola_jahitan_config'] = [];
            if (!empty($item['pola_jahitan_config']) && is_array($item['pola_jahitan_config'])) {
                foreach ($item['pola_jahitan_config'] as $jenis => $id) {
                    if ($id) {
                        $pola = PolaJahitan::find($id);
                        if ($pola) $item['_pola_jahitan_config'][$jenis] = $pola->nama;
                    }
                }
            }
            $item['_resleting']    = !empty($item['resleting_id'])    ? Resleting::find($item['resleting_id'])?->nama    : null;
            $item['_printing']     = !empty($item['printing_id'])     ? Printing::find($item['printing_id'])?->nama     : null;
            $item['_pola_jahitan'] = !empty($item['pola_jahitan_id']) ? PolaJahitan::find($item['pola_jahitan_id']) : null;
            $item['_pola_jahitan_lengan'] = !empty($item['pola_jahitan_lengan_id']) ? PolaJahitan::find($item['pola_jahitan_lengan_id']) : null;
            $item['_jenis_setelan']  = !empty($item['jenis_setelan_id'])  ? \App\Models\Master\JenisSetelan::find($item['jenis_setelan_id'])?->nama  : ($item['jenis_setelan'] ?? null);
            $item['_pola_produksi']  = !empty($item['pola_produksi_id'])  ? \App\Models\Master\PolaProduksi::find($item['pola_produksi_id'])?->nama   : ($item['pola'] ?? null);

            $item['namesets'] = collect($item['namesets'] ?? [])->map(function ($ns) {
                if (!empty($ns['size_id'])) {
                    $sz = Size::find($ns['size_id']);
                    $ns['_size_label'] = $sz ? "{$sz->kategori_size} - {$sz->ukuran}" : ($ns['size_label'] ?? '-');
                } else {
                    $ns['_size_label'] = $ns['size_label'] ?? '-';
                }

                if (!empty($ns['size_celana_id'])) {
                    $szc = Size::find($ns['size_celana_id']);
                    $ns['_size_celana_label'] = $szc ? "{$szc->kategori_size} - {$szc->ukuran}" : ($ns['size_celana_label'] ?? '-');
                } else {
                    $ns['_size_celana_label'] = $ns['size_celana_label'] ?? '-';
                }
                return $ns;
            })->all();

            return $item;
        });

        $pdf = Pdf::loadView('pdf.spk_draft', compact('brand', 'raw', 'pelanggan', 'kategori', 'jenisOrder', 'sumber', 'paketOrder', 'items'))
            ->setPaper('a4', 'portrait');

        $filename = 'SPK-DRAFT-' . $brand->kode . '-' . now()->format('YmdHis') . '.pdf';
        return $pdf->download($filename);
    }

    public function spkPdf(Request $request, Order $order)
    {
        Gate::authorize('order.view');
        $this->guardBrandOwnership($request, $order);

        $order->load([
            'brand', 'pelanggan', 'kategoriOrder', 'jenisOrder', 'sumberOrder', 'paketOrder',
            'items.bahanKain', 'items.bahanKainBawahan', 'items.logo', 'items.resleting', 'items.printing',
            'items.polaJahitan', 'items.polaJahitanLengan',
            'items.jenisSetelan', 'items.polaProduksi',
            'items.namesets.size', 'items.namesets.sizeCelana',
        ]);

        $pdf = Pdf::loadView('pdf.spk', ['order' => $order])->setPaper('a4', 'portrait');

        return $pdf->download("SPK-{$order->no_po}.pdf");
    }

    public function spkPreview(Request $request, Order $order)
    {
        Gate::authorize('order.view');
        $this->guardBrandOwnership($request, $order);

        $order->load([
            'brand', 'pelanggan', 'kategoriOrder', 'jenisOrder', 'sumberOrder', 'paketOrder',
            'items.bahanKain', 'items.bahanKainBawahan', 'items.logo', 'items.resleting', 'items.printing',
            'items.polaJahitan', 'items.polaJahitanLengan',
            'items.jenisSetelan', 'items.polaProduksi',
            'items.namesets.size', 'items.namesets.sizeCelana',
        ]);

        return view('pdf.spk', [
            'order' => $order,
            'isWebPreview' => true,
        ]);
    }

    public function updateTimeline(Request $request, Order $order)
    {
        Gate::authorize('production.update-progress');
        $this->guardBrandOwnership($request, $order);

        $data = $request->validate([
            'start_production_date' => ['nullable', 'date'],
            'end_production_date'   => ['nullable', 'date', 'after_or_equal:start_production_date'],
        ]);

        $oldStart = $order->start_production_date;
        $oldEnd = $order->end_production_date;

        $order->update($data);

        $newStart = $order->fresh()->start_production_date;
        $newEnd = $order->fresh()->end_production_date;

        $user = $request->user();

        // Format dates consistently to check for changes
        $oldStartStr = $oldStart ? \Carbon\Carbon::parse($oldStart)->toDateString() : null;
        $newStartStr = $newStart ? \Carbon\Carbon::parse($newStart)->toDateString() : null;
        if ($oldStartStr !== $newStartStr) {
            \App\Models\Order\POChangeLog::create([
                'order_id' => $order->id,
                'changed_by' => $user->id,
                'field_changed' => 'start_production_date',
                'old_value' => $oldStartStr,
                'new_value' => $newStartStr,
                'change_reason' => 'Perubahan tanggal mulai produksi',
            ]);
        }

        $oldEndStr = $oldEnd ? \Carbon\Carbon::parse($oldEnd)->toDateString() : null;
        $newEndStr = $newEnd ? \Carbon\Carbon::parse($newEnd)->toDateString() : null;
        if ($oldEndStr !== $newEndStr) {
            \App\Models\Order\POChangeLog::create([
                'order_id' => $order->id,
                'changed_by' => $user->id,
                'field_changed' => 'end_production_date',
                'old_value' => $oldEndStr,
                'new_value' => $newEndStr,
                'change_reason' => 'Perubahan tanggal selesai produksi',
            ]);
        }

        return back()->with('success', 'Timeline produksi berhasil diperbarui.');
    }

    public function markLunas(Request $request, Order $order)
    {
        abort_if($order->is_special_order, 422, 'Special Order tidak memerlukan konfirmasi lunas.');
        abort_unless(
            $request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan'),
            403
        );
        $this->guardBrandOwnership($request, $order);

        $toggle = ! $order->is_lunas;

        $order->update([
            'is_lunas'  => $toggle,
            'lunas_at'  => $toggle ? now() : null,
            'lunas_by'  => $toggle ? $request->user()->id : null,
        ]);

        return back()->with('success', $toggle ? 'Order ditandai LUNAS.' : 'Status lunas dibatalkan.');
    }

    /**
     * @param string $masterBrandId  Brand ID untuk lookup master data (hub jika reseller branch)
     * @param string $currentBrandId Brand ID aktif (untuk pelanggan & bank yang tetap per-branch/hub)
     */
    /**
     * Untuk admin_reseller: kumpulkan semua brand yang bisa dipilih di form order.
     * Exclude brand yang sedang aktif (current) karena sudah jadi default.
     */
    private function resolveResellerBrandsForForm($user, string $currentBrandId): array
    {
        return $user->brands()
            ->where('brands.id', '!=', $currentBrandId)
            ->orderBy('nama_brand')
            ->get(['brands.id', 'brands.nama_brand', 'brands.kode'])
            ->toArray();
    }

    private function mastersForForm(string $masterBrandId, ?string $currentBrandId = null): array
    {
        $currentBrandId = $currentBrandId ?? $masterBrandId;

        $masterQ = fn ($q) => $q->where(function ($w) use ($masterBrandId) {
            $w->where('brand_id', $masterBrandId)->orWhereNull('brand_id');
        });

        $user = auth()->user();
        $userBrandIds = ($user && ($user->isSuperadmin() || $user->hasRole(['owner', 'admin_keuangan', 'admin_produksi'])))
            ? null
            : ($user ? $user->brands()->pluck('brands.id')->toArray() : []);

        $banksQuery = BankAccount::active();
        if ($userBrandIds !== null) {
            $allRelevantBrandIds = array_unique(array_merge($userBrandIds, [$masterBrandId, $currentBrandId]));
            $banksQuery->whereIn('brand_id', $allRelevantBrandIds);
        }
        $banks = $banksQuery->orderBy('bank')->get(['id', 'bank', 'atas_nama', 'nomor_rekening', 'brand_id']);

        return [
            'kategori_orders' => KategoriOrder::active()->where($masterQ)->orderBy('nama')->get(['id', 'nama']),
            'jenis_orders' => JenisOrder::active()->where($masterQ)->orderBy('nama')->get(['id', 'nama']),
            'sumber_orders' => SumberOrder::active()->where($masterQ)->orderBy('nama')->get(['id', 'nama']),
            'iklans' => Iklan::active()->where($masterQ)->orderBy('nama')->get(['id', 'nama', 'platform']),
            'pelanggan' => Customer::active()->where('brand_id', $masterBrandId)->orderBy('nama')->limit(500)->get(['id', 'kode', 'nama', 'nomor_hp']),
            // Master Produksi dinamis
            'jenis_setelan'  => JenisSetelan::active()->orderBy('nama')->get(['id', 'nama', 'deskripsi']),
            'pola_produksi'  => PolaProduksi::active()->orderBy('nama')->get(['id', 'nama', 'deskripsi']),
            // jenis_produk: template produksi global (tanpa harga) — untuk checkbox Buka Modul
            'jenis_produk'  => JenisProduk::active()->orderBy('nama')->get(['id', 'nama']),
            // paket_orders: dengan warna & prioritas untuk display di form
            'paket_orders'  => PaketOrder::active()->orderBy('prioritas')->orderBy('nama')
                ->get(['id', 'nama', 'warna', 'prioritas']),
            // produk: katalog brand dengan harga — untuk referensi harga di item
            'produk' => Product::active()->where($masterQ)->orderBy('nama')->get(['id', 'nama', 'harga']),
            'bahan_kains' => BahanKain::active()->orderBy('nama')->get(['id', 'nama']),
            'logos' => Logo::active()->orderBy('nama')->get(['id', 'nama']),
            'printings' => Printing::active()->orderBy('nama')->get(['id', 'nama']),
            'resletings' => Resleting::active()->orderBy('nama')->get(['id', 'nama']),
            'pola_jahitans' => PolaJahitan::active()->orderBy('jenis_pola')->orderBy('nama')
                ->get(['id', 'jenis_pola', 'nama']),
            // Khusus untuk Jahitan List Lengan
            'pola_jahitans_lengan' => PolaJahitan::active()
                ->where('jenis_pola', 'like', '%Lengan%')
                ->orderBy('nama')->get(['id', 'jenis_pola', 'nama']),
            'sizes' => Size::active()->orderBy('kategori_size')->orderBy('urutan')->get(['id', 'kategori_size', 'ukuran']),
            'banks' => $banks,
            'jenis_pembayarans' => \App\Models\Finance\MasterJenisPembayaran::active()->orderBy('nama')->get(['id', 'nama']),
        ];
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'nama_po' => ['required', 'string', 'max:255'],
            'is_special_order' => ['boolean'],
            'tanggal_masuk' => ['required', 'date'],
            'deadline_customer' => ['required', 'date', 'after_or_equal:tanggal_masuk'],
            'kategori_order_id' => ['nullable', 'uuid'],
            'jenis_order_id' => ['nullable', 'uuid'],
            'sumber_order_id' => ['nullable', 'uuid'],
            'paket_order_id'    => ['nullable', 'uuid', 'exists:paket_orders,id'],
            'jenis_setelan_id'  => ['nullable', 'uuid', 'exists:jenis_setelan,id'],
            'pola_produksi_id'  => ['nullable', 'uuid', 'exists:pola_produksi,id'],
            'pelanggan_id' => ['required', 'uuid', 'exists:customers,id'],
            'branch_brand_id' => ['nullable', 'uuid', 'exists:brands,id'],
            'printing_ids' => ['nullable', 'array'],
            'printing_ids.*' => ['uuid', 'exists:printings,id'],
            'iklan_id' => ['nullable', 'uuid', 'exists:iklans,id'],
            'catatan' => ['nullable', 'string'],
            'items' => ['array'],
            'items.*.is_addon' => ['nullable', 'boolean'],
            'items.*.product_id' => ['nullable', 'uuid'],
            'items.*.jenis_produk_id' => ['nullable', 'uuid', 'exists:jenis_produks,id'],
            'items.*.nama_produk' => ['required', 'string', 'max:255'],
            'items.*.varian_label' => ['nullable', 'string', 'max:100'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.harga_satuan' => ['required', 'numeric', 'min:0'],
            'items.*.discount_type' => ['nullable', 'string', Rule::in(['persen', 'nominal', ''])],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.bahan_kain_id' => ['nullable', 'uuid'],
            'items.*.bahan_kain_ids' => ['nullable', 'array'],
            'items.*.bahan_kain_ids.*' => ['uuid', 'exists:bahan_kains,id'],
            'items.*.bahan_kain_bawahan_id' => ['nullable', 'uuid'],
            'items.*.bahan_kain_bawahan_ids' => ['nullable', 'array'],
            'items.*.bahan_kain_bawahan_ids.*' => ['uuid', 'exists:bahan_kains,id'],
            'items.*.jenis_setelan' => ['nullable', Rule::in(['stell', 'non_stell', 'atasan_saja', 'bawahan_saja'])],
            'items.*.pola' => ['nullable', 'string', 'max:50'],
            'items.*.logo_id' => ['nullable', 'uuid'],
            'items.*.logo_ids' => ['nullable', 'array'],
            'items.*.logo_ids.*' => ['uuid', 'exists:logos,id'],
            'items.*.printing_id' => ['nullable', 'uuid'],
            'items.*.resleting_id' => ['nullable', 'uuid'],
            'items.*.jenis_rib' => ['nullable', 'string', 'max:100'],
            'items.*.tutup_kerah' => ['nullable', 'string', 'max:100'],
            'items.*.list_kerah' => ['nullable', 'string', 'max:100'],
            'items.*.list_lengan' => ['nullable', 'string', 'max:100'],
            'items.*.list_samping_celana' => ['nullable', 'string', 'max:100'],
            'items.*.list_bawah_celana' => ['nullable', 'string', 'max:100'],
            'items.*.pola_jahitan_lengan_id' => ['nullable', 'uuid'],
            'items.*.pola_jahitan_kerah_id' => ['nullable', 'uuid'],
            'items.*.pola_jahitan_bawah_id' => ['nullable', 'uuid'],
            'items.*.pola_jahitan_pundak_id' => ['nullable', 'uuid'],
            'items.*.pola_jahitan_id' => ['nullable', 'uuid'],
            'items.*.pola_jahitan_config' => ['nullable', 'array'],
            'items.*.jahitan_list_lengan' => ['nullable', Rule::in(['overdeck', 'stick'])],
            'items.*.warna' => ['nullable', 'string', 'max:100'],
            'items.*.jml_atasan' => ['nullable', 'string', 'max:100'],
            'items.*.jml_bawahan' => ['nullable', 'string', 'max:100'],
            'items.*.jenis_kerah' => ['nullable', 'string', 'max:100'],
            'items.*.catatan' => ['nullable', 'string'],
            'items.*.gambar_desain' => ['nullable', 'string', 'max:255'],
            'items.*.ket_atasan' => ['nullable', 'string'],
            'items.*.ket_bawahan' => ['nullable', 'string'],
            'items.*.gambar_kerah' => ['nullable', 'string', 'max:255'],
            'items.*.gambar_ket_tambahan' => ['nullable', 'string', 'max:255'],
            'items.*.namesets' => ['array'],
            'items.*.namesets.*.nama_punggung' => ['nullable', 'string', 'max:100'],
            'items.*.namesets.*.nomor_punggung' => ['nullable', 'string', 'max:20'],
            'items.*.namesets.*.nama_dada' => ['nullable', 'string', 'max:100'],
            'items.*.namesets.*.nomor_dada' => ['nullable', 'string', 'max:20'],
            'items.*.namesets.*.nama_lengan' => ['nullable', 'string', 'max:100'],
            'items.*.namesets.*.nomor_lengan' => ['nullable', 'string', 'max:20'],
            'items.*.namesets.*.nomor_punggung_2' => ['nullable', 'string', 'max:20'],
            'items.*.namesets.*.nama_punggung_2' => ['nullable', 'string', 'max:100'],
            'items.*.namesets.*.size_id' => ['nullable', 'uuid'],
            'items.*.namesets.*.size_label' => ['nullable', 'string', 'max:50'],
            'items.*.namesets.*.size_celana_id' => ['nullable', 'uuid'],
            'items.*.namesets.*.size_celana_label' => ['nullable', 'string', 'max:50'],
            'items.*.namesets.*.keterangan' => ['nullable', 'string'],
            'bank_id' => ['required', 'uuid', 'exists:bank_accounts,id'],
        ]);
    }

    private function syncItems(Order $order, array $items): void
    {
        $order->items()->each(function ($i) {
            $i->namesets()->delete();
            $i->delete();
        });

        $sizeIds = [];
        foreach ($items as $item) {
            foreach ($item['namesets'] ?? [] as $ns) {
                if (!empty($ns['size_id'])) {
                    $sizeIds[] = $ns['size_id'];
                }
                if (!empty($ns['size_celana_id'])) {
                    $sizeIds[] = $ns['size_celana_id'];
                }
            }
        }
        $sizesMap = [];
        if (!empty($sizeIds)) {
            $sizesMap = \App\Models\Master\Size::whereIn('id', array_unique($sizeIds))->get()->keyBy('id');
        }

        $categoryPriority = [
            'ANAK' => 1,
            'LAKI-LAKI' => 2,
            'UNISEX' => 3,
            'PEREMPUAN' => 4,
            'CUSTOM' => 5,
        ];

        foreach ($items as $item) {
            $namesets = $item['namesets'] ?? [];
            unset($item['namesets']);

            $item['order_id'] = $order->id;

            $qty = $item['quantity'] ?? 0;
            $price = $item['harga_satuan'] ?? 0;
            $raw = $qty * $price;

            $discountType = $item['discount_type'] ?? '';
            $discountValue = $item['discount_value'] ?? 0;

            $discountAmount = 0;
            if ($discountType === 'persen') {
                $discountAmount = $raw * ($discountValue / 100);
            } elseif ($discountType === 'nominal') {
                $discountAmount = $qty * $discountValue;
            }

            $item['discount_amount'] = $discountAmount;
            $item['subtotal'] = max(0, $raw - $discountAmount);

            $created = OrderItem::create($item);

            // Sort namesets by size (atasan) and secondarily by size (celana)
            usort($namesets, function ($a, $b) use ($sizesMap, $categoryPriority) {
                // Atasan A
                $sizeIdA = $a['size_id'] ?? null;
                $sizeA = $sizeIdA && isset($sizesMap[$sizeIdA]) ? $sizesMap[$sizeIdA] : null;
                $catPriA = $sizeA ? ($categoryPriority[strtoupper($sizeA->kategori_size)] ?? 99) : 999;
                $urutanA = $sizeA ? ($sizeA->urutan ?? 9999) : 999999;

                // Atasan B
                $sizeIdB = $b['size_id'] ?? null;
                $sizeB = $sizeIdB && isset($sizesMap[$sizeIdB]) ? $sizesMap[$sizeIdB] : null;
                $catPriB = $sizeB ? ($categoryPriority[strtoupper($sizeB->kategori_size)] ?? 99) : 999;
                $urutanB = $sizeB ? ($sizeB->urutan ?? 9999) : 999999;

                if ($catPriA !== $catPriB) {
                    return $catPriA <=> $catPriB;
                }
                if ($urutanA !== $urutanB) {
                    return $urutanA <=> $urutanB;
                }

                // Celana A
                $sizeCelanaIdA = $a['size_celana_id'] ?? null;
                $sizeCelanaA = $sizeCelanaIdA && isset($sizesMap[$sizeCelanaIdA]) ? $sizesMap[$sizeCelanaIdA] : null;
                $catPriCelanaA = $sizeCelanaA ? ($categoryPriority[strtoupper($sizeCelanaA->kategori_size)] ?? 99) : 999;
                $urutanCelanaA = $sizeCelanaA ? ($sizeCelanaA->urutan ?? 9999) : 999999;

                // Celana B
                $sizeCelanaIdB = $b['size_celana_id'] ?? null;
                $sizeCelanaB = $sizeCelanaIdB && isset($sizesMap[$sizeCelanaIdB]) ? $sizesMap[$sizeCelanaIdB] : null;
                $catPriCelanaB = $sizeCelanaB ? ($categoryPriority[strtoupper($sizeCelanaB->kategori_size)] ?? 99) : 999;
                $urutanCelanaB = $sizeCelanaB ? ($sizeCelanaB->urutan ?? 9999) : 999999;

                if ($catPriCelanaA !== $catPriCelanaB) {
                    return $catPriCelanaA <=> $catPriCelanaB;
                }
                if ($urutanCelanaA !== $urutanCelanaB) {
                    return $urutanCelanaA <=> $urutanCelanaB;
                }

                return 0;
            });

            foreach ($namesets as $idx => $ns) {
                $ns['order_item_id'] = $created->id;
                $ns['urutan'] = $idx;
                OrderNameset::create($ns);
            }
        }
    }

    private function syncPayments(Order $order, array $payments, int $userId): void
    {
        // Untuk simplicity, payment hanya ditambah (tidak overwrite existing).
        // CRUD pembayaran lengkap tersedia via addPayment().
        $user = auth()->user();
        $isFinanceOrAdmin = $user && ($user->hasRole('superadmin') || $user->hasRole('owner') || $user->hasRole('admin_keuangan'));

        foreach ($payments as $p) {
            OrderPayment::create([
                ...$p,
                'order_id' => $order->id,
                'recorded_by' => $userId,
                'verified_by' => $isFinanceOrAdmin ? $userId : null,
                'verified_at' => $isFinanceOrAdmin ? now() : null,
            ]);
        }
    }

    private function guardBrandOwnership(Request $request, Order $order): void
    {
        $user = $request->user();
        if ($user->isSuperadmin()) return;

        // owner & other roles that can see all their brands
        if ($user->hasRole('owner')) {
            abort_unless($user->hasAccessToBrand($order->brand_id), 403);
            return;
        }

        // admin_reseller can access orders on any brand they have access to
        // (hub context + all its branches, or a specific branch context)
        abort_unless($user->hasAccessToBrand($order->brand_id), 403);
    }
}
