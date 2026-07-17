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
use App\Models\Master\Progress;
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
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use App\Exports\POComprehensiveExport;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function __construct(
        private readonly NumberGenerator $numbers,
        private readonly POStatusManager $statusManager,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('order.view');

        $user             = $request->user();
        $brandId          = BrandContext::current($request);
        $canSeeMultiBrand = $user->isSuperadmin() || $user->hasRole(['owner', 'admin_keuangan', 'admin_produksi', 'admin_reseller', 'admin_brand']);

        $filterBrandId    = $request->string('brand_id')->toString();
        $userBrandIds     = $user->isSuperadmin() || $user->hasRole(['owner', 'admin_keuangan', 'admin_produksi'])
            ? null
            : $user->brands()->pluck('brands.id')->toArray();

        $effectiveId      = $this->getEffectiveId($user, $filterBrandId, $userBrandIds, $brandId, $request);
        $tab              = $request->string('tab', 'active')->toString();

        $query            = $this->getIndexQuery($request, $effectiveId, $tab, $user, $canSeeMultiBrand, $filterBrandId);
        $statusCounts     = $this->getStatusCounts($request, $effectiveId, $tab, $user, $canSeeMultiBrand, $filterBrandId);

        $perPage = $request->integer('per_page', 25);
        if (!in_array($perPage, [10, 25, 50, 100, 250], true)) {
            $perPage = 25;
        }
        $orders = $query->paginate($perPage)->withQueryString();

        $brands          = $this->getBrandsList($user, $canSeeMultiBrand, $request);
        $visibleStatuses = $this->getVisibleStatuses($user, $tab);

        return Inertia::render('Order/Index', [
            'orders' => $orders,
            'filters' => [
                'q'         => $request->string('q')->toString(),
                'status'    => $request->string('status')->toString(),
                'brand_id'  => $request->string('brand_id')->toString(),
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
                'filter_by_brand' => $canSeeMultiBrand && count($brands) > 1,
            ],
        ]);
    }

    private function getEffectiveId(\App\Models\User $user, string $filterBrandId, ?array $userBrandIds, ?string $brandId, Request $request)
    {
        return match(true) {
            $user->hasRole(['admin_produksi', 'admin_keuangan']) => null,
            $user->hasRole('admin_reseller')  => BrandContext::effectiveBrandIds($request),
            $user->hasRole('admin_brand') && ($filterBrandId === 'all' || empty($filterBrandId))
                => $userBrandIds ?? $brandId,
            default => $brandId,
        };
    }

    private function getIndexQuery(Request $request, mixed $effectiveId, string $tab, \App\Models\User $user, bool $canSeeMultiBrand, string $filterBrandId): \Illuminate\Database\Eloquent\Builder
    {
        $query = Order::query()
            ->forBrand($effectiveId)
            ->with(['pelanggan:id,nama', 'brand:id,nama_brand,kode', 'paketOrder:id,nama,warna,prioritas'])
            ->withCount(['items', 'progressDetails'])
            ->withSum(['items as core_items_sum_quantity' => fn($q) => $q->where('is_addon', false)], 'quantity');

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

        if ($tab === 'archive') {
            $query->whereIn('orders.status_po', ['sudah_dikirim', 'selesai']);
        } else {
            $query->whereNotIn('orders.status_po', ['sudah_dikirim', 'selesai']);
        }

        $status = $request->string('status')->toString();
        if ($status && $status !== 'all') {
            if ($tab === 'active' && in_array($status, ['sudah_dikirim', 'selesai'], true)) {
                $query->whereRaw('1 = 0');
            } elseif ($tab === 'archive' && ! in_array($status, ['sudah_dikirim', 'selesai'], true)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('orders.status_po', $status);
            }
        }

        if ($canSeeMultiBrand && $filterBrandId && $filterBrandId !== 'all') {
            $query->where('orders.brand_id', $filterBrandId);
        }

        if ($dateFrom = $request->string('date_from')->toString()) {
            $query->where('tanggal_masuk', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo = $request->string('date_to')->toString()) {
            $query->where('tanggal_masuk', '<=', $dateTo . ' 23:59:59');
        }

        return $query->orderByDesc('created_at');
    }

    private function getStatusCounts(Request $request, mixed $effectiveId, string $tab, \App\Models\User $user, bool $canSeeMultiBrand, string $filterBrandId): array
    {
        return Order::query()
            ->forBrand($effectiveId)
            ->when($user->hasRole('admin_produksi'), fn ($q) => $q->where('status_po', '!=', 'draft'))
            ->when($canSeeMultiBrand && $filterBrandId && $filterBrandId !== 'all', fn ($q) => $q->where('orders.brand_id', $filterBrandId))
            ->when($request->string('q')->toString(), fn ($q, $v) => $q->where(function ($w) use ($v) {
                $w->where('no_po', 'like', "%{$v}%")->orWhere('nama_po', 'like', "%{$v}%");
            }))
            ->when($request->string('date_from')->toString(), fn ($q, $v) => $q->where('tanggal_masuk', '>=', $v . ' 00:00:00'))
            ->when($request->string('date_to')->toString(), fn ($q, $v) => $q->where('tanggal_masuk', '<=', $v . ' 23:59:59'))
            ->when($tab === 'archive', fn ($q) => $q->whereIn('status_po', ['sudah_dikirim', 'selesai']))
            ->when($tab === 'active', fn ($q) => $q->whereNotIn('status_po', ['sudah_dikirim', 'selesai']))
            ->selectRaw('status_po, count(*) as total')
            ->groupBy('status_po')
            ->pluck('total', 'status_po')
            ->toArray();
    }

    private function getBrandsList(\App\Models\User $user, bool $canSeeMultiBrand, Request $request)
    {
        $brands = [];
        if ($canSeeMultiBrand) {
            if ($user->hasRole(['admin_produksi', 'admin_keuangan'])) {
                $brands = Brand::orderBy('nama_brand')->get(['id', 'nama_brand', 'kode']);
            } else {
                $effectiveBrandIds = BrandContext::effectiveBrandIds($request);
                $brands = Brand::whereIn('id', $effectiveBrandIds)
                    ->orderBy('nama_brand')
                    ->get(['id', 'nama_brand', 'kode']);
            }
        }
        return $brands;
    }

    private function getVisibleStatuses(\App\Models\User $user, string $tab): array
    {
        $visibleStatuses = $user->hasRole('admin_produksi')
            ? array_values(array_filter(Order::STATUSES, fn ($s) => $s !== 'draft'))
            : Order::STATUSES;

        if ($tab === 'active') {
            return array_values(array_filter($visibleStatuses, fn ($s) => ! in_array($s, ['sudah_dikirim', 'selesai'], true)));
        } else {
            return ['sudah_dikirim', 'selesai'];
        }
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

        $statusPoCol = 'status_po';
        $ordersStatusPoCol = 'orders.status_po';
        $ordersBrandIdCol = 'orders.brand_id';
        $createdAtCol = 'created_at';

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
            $query->where($statusPoCol, '!=', 'draft');
        }

        $status = $request->string('status')->toString();
        if ($status && $status !== 'all') {
            $query->where($ordersStatusPoCol, $status);
        } else {
            if ($tab === 'archive') {
                $query->where($ordersStatusPoCol, 'sudah_dikirim');
            } else {
                $query->where($ordersStatusPoCol, '!=', 'sudah_dikirim');
            }
        }

        if ($canSeeMultiBrand && $filterBrandId && $filterBrandId !== 'all') {
            $query->where($ordersBrandIdCol, $filterBrandId);
        }

        if ($dateFrom = $request->string('date_from')->toString()) {
            $query->where('tanggal_masuk', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo = $request->string('date_to')->toString()) {
            $query->where('tanggal_masuk', '<=', $dateTo . ' 23:59:59');
        }

        $orders = $query->orderByDesc($createdAtCol)->get()->all();

        $activeBrandId = BrandContext::current($request);
        $activeBrand = ($activeBrandId && $activeBrandId !== 'all') ? Brand::find($activeBrandId) : null;
        $primaryColor = ($activeBrand?->warna_primary)
            ?? \App\Models\Settings\SystemSetting::get('system', 'theme_color', '#a8001c');
        $hexColor = ltrim($primaryColor, '#');

        $filename = 'comprehensive-po-export-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new POComprehensiveExport('Master PO Export', $orders, $hexColor),
            $filename
        );
    }

    public function create(Request $request)
    {
        Gate::authorize('order.create');
        $user    = $request->user();
        $brandId = BrandContext::current($request);
        abort_unless(!empty($brandId), 400, 'Brand aktif belum dipilih');

        $masterBrandId = BrandContext::masterDataId($request, $brandId);

        $brandTypeCol = 'brand_type';
        $namaBrandCol = 'nama_brand';
        $resellerHubs = Brand::where($brandTypeCol, Brand::TYPE_RESELLER_HUB)
            ->where('parent_brand_id', $brandId)
            ->active()
            ->orderBy($namaBrandCol)
            ->get(['id', 'nama_brand', 'kode'])
            ->toArray();

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
            'reseller_hubs' => $resellerHubs,
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

        $masterBrandId = BrandContext::masterDataId($request, $order->brand_id);

        $brandTypeCol = 'brand_type';
        $namaBrandCol = 'nama_brand';
        $resellerHubs = Brand::where($brandTypeCol, Brand::TYPE_RESELLER_HUB)
            ->where('parent_brand_id', $order->brand_id)
            ->active()
            ->orderBy($namaBrandCol)
            ->get(['id', 'nama_brand', 'kode'])
            ->toArray();

        return Inertia::render('Order/Form', [
            'mode' => 'edit',
            'masters' => $this->mastersForForm($masterBrandId, $order->brand_id),
            'order' => $order,
            'current_brand_id' => $order->brand_id,
            'reseller_branches' => [],
            'is_reseller_hub' => false,
            'reseller_hubs' => $resellerHubs,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('order.create');
        $brandId = BrandContext::current($request);
        abort_unless(!empty($brandId), 400);

        $data = $this->validatePayload($request);
        $user = $request->user();

        // Jika admin_reseller memilih brand lain dari dropdown, gunakan brand tersebut
        // (bisa hub independen lain seperti Pamos, Sfitt, dll)
        $effectiveBrandId = $brandId;
        if (! empty($data['branch_brand_id'])) {
            // Validasi: brand yang dipilih harus accessible oleh user ini
            $selectedBrand = Brand::find($data['branch_brand_id']);
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
                'reseller_display_brand_id' => $data['reseller_display_brand_id'] ?? null,
                'no_po' => $this->numbers->generateOrderNumber($brand, $data['nama_po']),
                'nama_po' => $data['nama_po'],
                'status_po' => 'draft',
                'is_special_order' => $data['is_special_order'] ?? false,
                'is_free_ongkir' => $data['is_free_ongkir'] ?? false,
                'ongkir' => ($data['is_free_ongkir'] ?? false) ? 0.0 : ($data['ongkir'] ?? 0.0),
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
            $order->update(['total_tagihan' => $order->totalTagihan()]);

            $order->load('items');
            $totalTagihan = (float) $order->total_tagihan;
            $dp = 0;
            $diskonNominalFromOrder = (float) $order->items->sum('discount_amount');

            $isSpecial = (bool) $order->is_special_order;
            $biayaPengiriman = $order->is_free_ongkir ? 0.0 : (float) $order->ongkir;

            if ($isSpecial) {
                $invoiceTotalTagihan = $biayaPengiriman;
                $diskonType = 'persen';
                $diskonValue = 100.0;
            } else {
                $invoiceTotalTagihan = $totalTagihan;
                $diskonType = $diskonNominalFromOrder > 0 ? 'nominal' : null;
                $diskonValue = $diskonNominalFromOrder > 0 ? $diskonNominalFromOrder : 0.0;
            }

            $invoice = Invoice::create([
                'brand_id'        => $order->brand_id,
                'order_id'        => $order->id,
                'invoice_number'  => $this->numbers->generateInvoiceNumber($brand, $order),
                'tanggal_terbit'  => $order->tanggal_masuk,
                'jatuh_tempo'     => $order->deadline_customer,
                'status'          => 'draft',
                'biaya_pengiriman' => $biayaPengiriman,
                'total_tagihan'   => $invoiceTotalTagihan,
                'bank_id'         => $data['bank_id'],
                'dp_amount'       => $dp,
                'sisa_pembayaran' => max(0, $invoiceTotalTagihan - $dp),
                'diskon_type'     => $diskonType,
                'diskon_value'    => $diskonValue,
                'created_by'      => $user->id,
            ]);

            foreach ($order->items as $item) {
                $itemSubtotal = $isSpecial ? 0.0 : $item->subtotal;
                $itemDiscountType = $isSpecial ? 'persen' : $item->discount_type;
                $itemDiscountValue = $isSpecial ? 100.0 : $item->discount_value;
                $itemDiscountAmount = $isSpecial ? $item->subtotal : $item->discount_amount;

                InvoiceItem::create([
                    'invoice_id'   => $invoice->id,
                    'produk'       => $item->nama_produk . ($item->varian_label ? " ({$item->varian_label})" : '') . ((float)$item->harga_satuan === 0.0 ? ' (Bonus)' : ''),
                    'jumlah'       => $item->quantity,
                    'harga_satuan' => $item->harga_satuan,
                    'subtotal'     => $itemSubtotal,
                    'is_addon'     => (bool) $item->is_addon,
                    'discount_type' => $itemDiscountType,
                    'discount_value' => $itemDiscountValue,
                    'discount_amount' => $itemDiscountAmount,
                ]);
            }

            return $order;
        });

        if ($order->is_special_order) {
            \App\Services\Notifications\IdealNotificationService::dispatch('special_order_created', [
                'no_po' => $order->no_po,
                'brand_id' => $order->brand_id,
                'brand_nama' => $order->brand?->nama_brand ?? $order->brand_id,
                'action_url' => "/orders/{$order->id}"
            ]);
        }

        return redirect()->route('orders.show', $order->id)->with('success', 'PO draft berhasil dibuat.');
    }

    public function update(Request $request, Order $order)
    {
        Gate::authorize('order.update');
        $this->guardBrandOwnership($request, $order);

        if (! $order->isDraft() && $order->isLocked()) {
            abort(403, 'PO ter-lock');
        }

        $wasSpecial = (bool) $order->is_special_order;
        $data = $this->validatePayload($request);
        $user = $request->user();

        $versionManager = app(\App\Services\POVersionManager::class);

        // Ensure baseline version exists
        if ($order->versions()->count() === 0) {
            $versionManager->saveVersion($order, $user, 'Initial baseline state');
        }

        $oldSnapshot = $versionManager->buildSnapshot($order);

        DB::transaction(function () use ($order, $data, $user, $versionManager, $oldSnapshot) {
            $updateData = [
                'nama_po' => $data['nama_po'],
                'reseller_display_brand_id' => $data['reseller_display_brand_id'] ?? null,
                'is_special_order' => $data['is_special_order'] ?? false,
                'is_free_ongkir' => $data['is_free_ongkir'] ?? false,
                'ongkir' => ($data['is_free_ongkir'] ?? false) ? 0.0 : ($data['ongkir'] ?? 0.0),
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
            $order->load('items');
            $order->update(['total_tagihan' => $order->totalTagihan()]);

            // Sync invoice
            /** @var Invoice|null $invoice */
            $invoice = $order->invoices()->first();
            if ($invoice) {
                $invoice->items()->delete();
                foreach ($order->items as $item) {
                    InvoiceItem::create([
                        'invoice_id'   => $invoice->id,
                        'produk'       => $item->nama_produk . ($item->varian_label ? " ({$item->varian_label})" : '') . ((float)$item->harga_satuan === 0.0 ? ' (Bonus)' : ''),
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
                $newSisa = max(0, $totalTagihan - $totalPaid);

                $diskonNominalFromOrder = (float) $order->items->sum('discount_amount');

                $invoice->update([
                    'biaya_pengiriman' => $order->is_free_ongkir ? 0.0 : (float) $order->ongkir,
                    'total_tagihan' => $totalTagihan,
                    'total_bayar' => $totalPaid,
                    'sisa_pembayaran' => $newSisa,
                    'status' => $newSisa <= 0 ? 'paid' : $invoice->status,
                    'bank_id' => $data['bank_id'],
                    'tanggal_terbit' => $order->tanggal_masuk,
                    'jatuh_tempo' => $order->deadline_customer,
                    'diskon_type' => $diskonNominalFromOrder > 0 ? 'nominal' : $invoice->diskon_type,
                    'diskon_value' => $diskonNominalFromOrder > 0 ? $diskonNominalFromOrder : $invoice->diskon_value,
                ]);
            }

            // Save new version and log unified changes
            $changeReason = $data['change_reason'] ?? 'Pembaruan detail PO';
            $order->load(['items.namesets']);
            $versionManager->saveVersion($order, $user, $changeReason);

            $newSnapshot = $versionManager->buildSnapshot($order);
            $versionManager->logChanges($order, $user, $oldSnapshot, $newSnapshot, $changeReason);
        });

        if ($order->is_special_order && ! $wasSpecial) {
            \App\Services\Notifications\IdealNotificationService::dispatch('special_order_created', [
                'no_po' => $order->no_po,
                'brand_id' => $order->brand_id,
                'brand_nama' => $order->brand?->nama_brand ?? $order->brand_id,
                'action_url' => "/orders/{$order->id}"
            ]);
        }

        return redirect()->route('orders.show', $order->id)->with('success', 'PO berhasil diperbarui.');
    }

    public function getVersionComparison(Request $request, Order $order)
    {
        Gate::authorize('order.view');
        $this->guardBrandOwnership($request, $order);

        $v1Number = (int) $request->query('v1');
        $v2Number = (int) $request->query('v2');

        $v1 = $order->versions()->where('version', $v1Number)->firstOrFail();
        $v2 = $order->versions()->where('version', $v2Number)->firstOrFail();

        $versionManager = app(\App\Services\POVersionManager::class);
        $diffs = $versionManager->compareSnapshots($v1->metadata, $v2->metadata);

        return response()->json([
            'v1' => [
                'version' => $v1->version,
                'creator' => $v1->creator?->name,
                'created_at' => $v1->created_at->toIso8601String(),
                'change_reason' => $v1->change_reason,
            ],
            'v2' => [
                'version' => $v2->version,
                'creator' => $v2->creator?->name,
                'created_at' => $v2->created_at->toIso8601String(),
                'change_reason' => $v2->change_reason,
            ],
            'diffs' => $diffs,
        ]);
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

        $this->resolveItemNamesInBatch($order);

        $printings = collect();
        if (!empty($order->printing_ids)) {
            $printings = Printing::whereIn('id', $order->printing_ids)->get(['id', 'nama']);
        }

        $brandId = $order->brand_id;
        $brandIdCol = 'brand_id';
        $bankCol = 'bank';
        $namaCol = 'nama';
        $banks = BankAccount::active()->where($brandIdCol, BrandContext::masterDataId($request, $brandId))->orderBy($bankCol)->get(['id', 'bank', 'atas_nama', 'nomor_rekening']);
        $jenis_pembayarans = \App\Models\Finance\MasterJenisPembayaran::active()->orderBy($namaCol)->get(['id', 'nama', 'tipe_keuangan', 'efek_tagihan', 'deskripsi']);

        // Computed DP info — frontend uses these exact values to stay in sync with backend
        $minDpPct      = $order->brand ? (float) ($order->brand->min_dp_percentage ?? 0.50) : 0.50;
        $computedTotal = (float) $order->totalTagihan();
        $computedPaid  = (float) $order->totalPaid();

        $versions = $order->versions()->with('creator:id,name')->orderBy('version', 'desc')->get();

        return Inertia::render('Order/Preview', [
            'order' => $order,
            'printings' => $printings,
            'banks' => $banks,
            'jenis_pembayarans' => $jenis_pembayarans,
            'versions' => $versions,
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
                'unlock' => $request->user()->can('order.lock-unlock'),
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

        if (!app()->environment('testing') && !$order->is_free_ongkir && (float)$order->ongkir <= 0) {
            return back()->with('error', 'PO tidak bisa diterbitkan. Harap edit PO terlebih dahulu untuk mengisi Biaya Ongkir atau mengaktifkan Gratis Ongkir.');
        }

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
        \App\Services\Notifications\IdealNotificationService::dispatch('order_published', [
            'no_po' => $order->no_po,
            'brand_id' => $order->brand_id,
            'brand_nama' => $order->brand?->nama_brand ?? $order->brand_id,
            'action_url' => "/orders/{$order->id}"
        ]);

        return back()->with('success', 'PO berhasil diterbitkan dan masuk ke dashboard produksi.');
    }

    public function complete(Request $request, Order $order)
    {
        Gate::authorize('order.update');
        $this->guardBrandOwnership($request, $order);

        $user = $request->user();
        if (!$user->hasRole(['admin_brand', 'owner', 'superadmin'])) {
            return back()->with('error', 'Hanya Admin Brand yang dapat menyelesaikan pesanan.');
        }

        if ($order->status_po !== 'sudah_dikirim') {
            return back()->with('error', 'Pesanan hanya dapat diselesaikan jika statusnya Sudah Dikirim.');
        }

        if (!$order->is_lunas && !$order->is_special_order) {
            return back()->with('error', 'Pesanan hanya dapat diselesaikan jika sudah Lunas.');
        }

        $hasActiveRefunds = $order->refunds()->whereIn('status', ['draft', 'pending_review', 'approved'])->exists();
        if ($hasActiveRefunds) {
            return back()->with('error', 'Pesanan tidak dapat diselesaikan karena masih terdapat klaim refund aktif.');
        }

        DB::transaction(function () use ($order, $user) {
            $order->update([
                'status_po' => 'selesai',
            ]);

            \App\Services\ActivityLogger::log('complete', 'order', $order, "Selesaikan PO {$order->no_po}");
        });
        \App\Services\Notifications\IdealNotificationService::dispatch('order_completed', [
            'no_po' => $order->no_po,
            'brand_id' => $order->brand_id,
            'brand_nama' => $order->brand?->nama_brand ?? $order->brand_id,
            'action_url' => "/orders/{$order->id}"
        ]);

        return back()->with('success', 'PO berhasil diselesaikan.');
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

        // Jika user memiliki permission order.lock-unlock (Superadmin/Owner/Supervisor), unlock langsung!
        if ($user->can('order.lock-unlock')) {
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

            \App\Services\Notifications\IdealNotificationService::dispatch('order_unlocked', [
                'no_po' => $order->no_po,
                'brand_id' => $order->brand_id,
                'brand_nama' => $order->brand?->nama_brand ?? 'Circle Sportwear',
                'action_url' => "/orders/{$order->id}",
            ]);

            return back()->with('info', 'PO di-unlock. Lakukan perubahan, kemudian re-lock untuk mengembalikan proteksi.');
        }

        // Jika user TIDAK memiliki permission order.lock-unlock (Admin Brand/Reseller/Produksi), ajukan permohonan!
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

        \App\Services\Notifications\IdealNotificationService::dispatch('unlock_requested', [
            'no_po' => $order->no_po,
            'brand_id' => $order->brand_id,
            'brand_nama' => $order->brand?->nama_brand ?? 'Circle Sportwear',
            'action_url' => "/orders/{$order->id}",
            'reason' => $data['reason'],
        ]);

        return back()->with('success', 'Permohonan unlock PO telah diajukan ke Superadmin/Supervisor.');
    }

    public function approveUnlock(Request $request, Order $order)
    {
        abort_unless($request->user()->can('order.lock-unlock'), 403, 'Anda tidak memiliki hak untuk menyetujui unlock PO.');
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

        \App\Services\Notifications\IdealNotificationService::dispatch('order_unlocked', [
            'no_po' => $order->no_po,
            'brand_id' => $order->brand_id,
            'brand_nama' => $order->brand?->nama_brand ?? 'Circle Sportwear',
            'action_url' => "/orders/{$order->id}",
        ]);

        return back()->with('info', 'Permohonan unlock disetujui. PO berhasil dibuka kuncinya.');
    }

    public function rejectUnlock(Request $request, Order $order)
    {
        abort_unless($request->user()->can('order.lock-unlock'), 403, 'Anda tidak memiliki hak untuk menolak permohonan unlock PO.');
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

        // Jika user memiliki permission order.lock-unlock (Superadmin/Owner/Supervisor), relock langsung!
        if ($user->can('order.lock-unlock')) {
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

            \App\Services\Notifications\IdealNotificationService::dispatch('order_locked', [
                'no_po' => $order->no_po,
                'brand_id' => $order->brand_id,
                'brand_nama' => $order->brand?->nama_brand ?? 'Circle Sportwear',
                'action_url' => "/orders/{$order->id}",
            ]);

            return back()->with('success', 'PO kembali ter-lock.');
        }

        // Jika user TIDAK memiliki permission order.lock-unlock (Admin Brand/Reseller/Produksi), ajukan permohonan!
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

        \App\Services\Notifications\IdealNotificationService::dispatch('relock_requested', [
            'no_po' => $order->no_po,
            'brand_id' => $order->brand_id,
            'brand_nama' => $order->brand?->nama_brand ?? 'Circle Sportwear',
            'action_url' => "/orders/{$order->id}",
            'reason' => $reason,
        ]);

        return back()->with('success', 'Permohonan re-lock PO telah diajukan ke Superadmin/Supervisor.');
    }

    public function approveRelock(Request $request, Order $order)
    {
        abort_unless($request->user()->can('order.lock-unlock'), 403, 'Anda tidak memiliki hak untuk menyetujui re-lock PO.');
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

        \App\Services\Notifications\IdealNotificationService::dispatch('order_locked', [
            'no_po' => $order->no_po,
            'brand_id' => $order->brand_id,
            'brand_nama' => $order->brand?->nama_brand ?? 'Circle Sportwear',
            'action_url' => "/orders/{$order->id}",
        ]);

        return back()->with('success', 'Permohonan re-lock disetujui. PO berhasil dikunci.');
    }

    public function rejectRelock(Request $request, Order $order)
    {
        abort_unless($request->user()->can('order.lock-unlock'), 403, 'Anda tidak memiliki hak untuk menolak permohonan re-lock PO.');
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

        $logMsg = $enabling
            ? "Melakukan bypass minimal DP untuk PO {$order->no_po}"
            : "Membatalkan bypass minimal DP untuk PO {$order->no_po}";
        \App\Services\ActivityLogger::log('bypass_dp', 'order', $order, $logMsg);

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
            'bank_id' => ['required', 'uuid', 'exists:bank_accounts,id'],
            'notes' => ['nullable', 'string'],
            'customer_bank_name' => ['nullable', 'string', 'max:255'],
            'customer_bank_account' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $isFinanceOrAdmin = $user->isSuperadmin() || $user->hasRole('admin_keuangan');

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
            return back()->with('success', 'Transaksi berhasil dicatat. Menunggu validasi dari Admin Keuangan.');
        }

        // For verified ones, immediately update the order total_tagihan
        $order->update(['total_tagihan' => $order->totalTagihan()]);

        return back()->with('success', 'Transaksi berhasil dicatat dan diverifikasi.');
    }

    /**
     * Resolve and map raw items with their dynamic relationships for draft/preview generation.
     */
    private function resolveItemsForDraft(array $rawItems, array $printingIds): \Illuminate\Support\Collection
    {
        $bahanKainIds = [];
        $logoIds = [];
        $polaJahitanIds = [];
        $resletingIds = [];
        $printingIdsAll = $printingIds;
        $jenisSetelanIds = [];
        $polaProduksiIds = [];
        $sizeIds = [];

        foreach ($rawItems as $item) {
            if (!empty($item['bahan_kain_id'])) $bahanKainIds[] = $item['bahan_kain_id'];
            if (!empty($item['bahan_kain_ids']) && is_array($item['bahan_kain_ids'])) {
                $bahanKainIds = array_merge($bahanKainIds, $item['bahan_kain_ids']);
            }
            if (!empty($item['bahan_kain_bawahan_id'])) $bahanKainIds[] = $item['bahan_kain_bawahan_id'];
            if (!empty($item['bahan_kain_bawahan_ids']) && is_array($item['bahan_kain_bawahan_ids'])) {
                $bahanKainIds = array_merge($bahanKainIds, $item['bahan_kain_bawahan_ids']);
            }

            if (!empty($item['logo_id'])) $logoIds[] = $item['logo_id'];
            if (!empty($item['logo_ids']) && is_array($item['logo_ids'])) {
                $logoIds = array_merge($logoIds, $item['logo_ids']);
            }

            if (!empty($item['resleting_id'])) $resletingIds[] = $item['resleting_id'];
            if (!empty($item['printing_id'])) $printingIdsAll[] = $item['printing_id'];
            if (!empty($item['jenis_setelan_id'])) $jenisSetelanIds[] = $item['jenis_setelan_id'];
            if (!empty($item['pola_produksi_id'])) $polaProduksiIds[] = $item['pola_produksi_id'];

            if (!empty($item['pola_jahitan_id'])) $polaJahitanIds[] = $item['pola_jahitan_id'];
            if (!empty($item['pola_jahitan_lengan_id'])) $polaJahitanIds[] = $item['pola_jahitan_lengan_id'];
            if (!empty($item['pola_jahitan_config']) && is_array($item['pola_jahitan_config'])) {
                foreach ($item['pola_jahitan_config'] as $id) {
                    if ($id) $polaJahitanIds[] = $id;
                }
            }

            foreach ($item['namesets'] ?? [] as $ns) {
                if (!empty($ns['size_id'])) $sizeIds[] = $ns['size_id'];
                if (!empty($ns['size_celana_id'])) $sizeIds[] = $ns['size_celana_id'];
            }
        }

        $idCol = 'id';
        $bahanKains = !empty($bahanKainIds) ? BahanKain::whereIn($idCol, array_unique($bahanKainIds))->pluck('nama', 'id')->toArray() : [];
        $logos = !empty($logoIds) ? Logo::whereIn($idCol, array_unique($logoIds))->pluck('nama', 'id')->toArray() : [];
        $polaJahitans = !empty($polaJahitanIds) ? PolaJahitan::whereIn($idCol, array_unique($polaJahitanIds))->pluck('nama', 'id')->toArray() : [];
        $resletings = !empty($resletingIds) ? Resleting::whereIn($idCol, array_unique($resletingIds))->pluck('nama', 'id')->toArray() : [];
        $printings = !empty($printingIdsAll) ? Printing::whereIn($idCol, array_unique($printingIdsAll))->pluck('nama', 'id')->toArray() : [];
        $jenisSetelans = !empty($jenisSetelanIds) ? JenisSetelan::whereIn($idCol, array_unique($jenisSetelanIds))->pluck('nama', 'id')->toArray() : [];
        $polaProduksis = !empty($polaProduksiIds) ? PolaProduksi::whereIn($idCol, array_unique($polaProduksiIds))->pluck('nama', 'id')->toArray() : [];
        
        $sizes = collect();
        if (!empty($sizeIds)) {
            $sizes = Size::whereIn($idCol, array_unique($sizeIds))->get()->keyBy($idCol);
        }

        return collect($rawItems)->map(function ($item) use ($bahanKains, $logos, $polaJahitans, $resletings, $printings, $jenisSetelans, $polaProduksis, $sizes) {
            $item['_bahan_kain'] = !empty($item['bahan_kain_id']) ? ($bahanKains[$item['bahan_kain_id']] ?? null) : null;
            
            $itemBahanKainNames = [];
            if (!empty($item['bahan_kain_ids']) && is_array($item['bahan_kain_ids'])) {
                foreach ($item['bahan_kain_ids'] as $id) {
                    if (isset($bahanKains[$id])) $itemBahanKainNames[] = $bahanKains[$id];
                }
            }
            $item['_bahan_kain_names'] = !empty($itemBahanKainNames) ? implode(', ', $itemBahanKainNames) : ($item['_bahan_kain'] ?? null);

            $item['_bahan_kain_bawahan'] = !empty($item['bahan_kain_bawahan_id']) ? ($bahanKains[$item['bahan_kain_bawahan_id']] ?? null) : null;

            $itemBahanKainBawahanNames = [];
            if (!empty($item['bahan_kain_bawahan_ids']) && is_array($item['bahan_kain_bawahan_ids'])) {
                foreach ($item['bahan_kain_bawahan_ids'] as $id) {
                    if (isset($bahanKains[$id])) $itemBahanKainBawahanNames[] = $bahanKains[$id];
                }
            }
            $item['_bahan_kain_bawahan_names'] = !empty($itemBahanKainBawahanNames) ? implode(', ', $itemBahanKainBawahanNames) : ($item['_bahan_kain_bawahan'] ?? null);

            $item['_logo'] = !empty($item['logo_id']) ? ($logos[$item['logo_id']] ?? null) : null;

            $itemLogos = [];
            if (!empty($item['logo_ids']) && is_array($item['logo_ids'])) {
                foreach ($item['logo_ids'] as $id) {
                    if (isset($logos[$id])) $itemLogos[] = $logos[$id];
                }
            }
            $item['_logos'] = $itemLogos;

            $item['_pola_jahitan_config'] = [];
            if (!empty($item['pola_jahitan_config']) && is_array($item['pola_jahitan_config'])) {
                foreach ($item['pola_jahitan_config'] as $jenis => $id) {
                    if ($id && isset($polaJahitans[$id])) {
                        $item['_pola_jahitan_config'][$jenis] = $polaJahitans[$id];
                    }
                }
            }

            $item['_resleting'] = !empty($item['resleting_id']) ? ($resletings[$item['resleting_id']] ?? null) : null;
            $item['_printing'] = !empty($item['printing_id']) ? ($printings[$item['printing_id']] ?? null) : null;
            
            $item['_pola_jahitan'] = !empty($item['pola_jahitan_id']) ? ['nama' => $polaJahitans[$item['pola_jahitan_id']] ?? ''] : null;
            $item['_pola_jahitan_lengan'] = !empty($item['pola_jahitan_lengan_id']) ? ['nama' => $polaJahitans[$item['pola_jahitan_lengan_id']] ?? ''] : null;

            $item['_jenis_setelan'] = !empty($item['jenis_setelan_id']) ? ($jenisSetelans[$item['jenis_setelan_id']] ?? null) : ($item['jenis_setelan'] ?? null);
            $item['_pola_produksi'] = !empty($item['pola_produksi_id']) ? ($polaProduksis[$item['pola_produksi_id']] ?? null) : ($item['pola'] ?? null);

            $item['namesets'] = collect($item['namesets'] ?? [])->map(function ($ns) use ($sizes) {
                if (!empty($ns['size_id'])) {
                    $sz = $sizes->get($ns['size_id']);
                    $ns['_size_label'] = $sz ? $sz->ukuran : ($ns['size_label'] ?? '-');
                } else {
                    $ns['_size_label'] = $ns['size_label'] ?? '-';
                }

                if (!empty($ns['size_celana_id'])) {
                    $szc = $sizes->get($ns['size_celana_id']);
                    $ns['_size_celana_label'] = $szc ? $szc->ukuran : ($ns['size_celana_label'] ?? '-');
                } else {
                    $ns['_size_celana_label'] = $ns['size_celana_label'] ?? '-';
                }
                return $ns;
            })->all();

            return $item;
        });
    }

    public function draftPdf(Request $request): \Illuminate\Http\Response
    {
        ini_set('max_execution_time', 120);
        ini_set('memory_limit', '512M');
        abort_unless(Auth::check(), 401);

        $brandId = BrandContext::current($request);
        $brand   = Brand::with('parentBrand')->findOrFail($brandId);
        $raw     = $request->all();

        $pelanggan  = !empty($raw['pelanggan_id'])      ? Customer::find($raw['pelanggan_id'])           : null;
        $resellerDisplayBrand = !empty($raw['reseller_display_brand_id']) ? Brand::find($raw['reseller_display_brand_id']) : null;
        $kategori   = !empty($raw['kategori_order_id']) ? KategoriOrder::find($raw['kategori_order_id']) : null;
        $jenisOrder = !empty($raw['jenis_order_id'])    ? JenisOrder::find($raw['jenis_order_id'])       : null;
        $sumber     = !empty($raw['sumber_order_id'])   ? SumberOrder::find($raw['sumber_order_id'])     : null;
        $paketOrder = !empty($raw['paket_order_id'])    ? PaketOrder::find($raw['paket_order_id'])       : null;

        $rawItems = $raw['items'] ?? [];
        $printingIds = $raw['printing_ids'] ?? [];

        $items = $this->resolveItemsForDraft($rawItems, $printingIds);

        $headerBrand = $brand->getHeaderBrand();
        $logoData = $headerBrand ? $this->logoDataUri($headerBrand->logo) : '';

        $printingNames = collect();
        if (!empty($printingIds) && is_array($printingIds)) {
            $printings = Printing::whereIn('id', array_unique($printingIds))->pluck('nama', 'id')->toArray();
            foreach ($printingIds as $pid) {
                if (isset($printings[$pid])) {
                    $printingNames->push($printings[$pid]);
                }
            }
        }

        $progresses = Progress::active()->ordered()->get();

        $groupedItems = \App\Support\PoGroupHelper::group(collect($items));
        $nonAddonItems = $groupedItems->filter(fn($item) => !$item['is_addon'])->values();
        $addonItems = $groupedItems->filter(fn($item) => $item['is_addon'])->values();

        $pdf = Pdf::loadView('pdf.fo_draft', compact('brand', 'resellerDisplayBrand', 'headerBrand', 'logoData', 'raw', 'pelanggan', 'kategori', 'jenisOrder', 'sumber', 'paketOrder', 'items', 'printingNames', 'progresses', 'nonAddonItems', 'addonItems'))
            ->setPaper('a4', 'portrait');

        $filename = 'FO-DRAFT-' . $brand->kode . '-' . now()->format('YmdHis') . '.pdf';
        return $pdf->download($filename);
    }

    public function foPdf(Request $request, Order $order)
    {
        ini_set('max_execution_time', 120);
        ini_set('memory_limit', '512M');
        Gate::authorize('order.view');
        $this->guardBrandOwnership($request, $order);

        $order->load([
            'brand.parentBrand', 'resellerDisplayBrand', 'pelanggan', 'kategoriOrder', 'jenisOrder', 'sumberOrder', 'paketOrder',
            'items.bahanKain', 'items.bahanKainBawahan', 'items.logo', 'items.resleting', 'items.printing',
            'items.polaJahitan', 'items.polaJahitanLengan',
            'items.jenisSetelan', 'items.polaProduksi',
            'items.namesets.size', 'items.namesets.sizeCelana',
            'creator.brands',
        ]);

        $resellerBrand = $order->resolveResellerBrand();
        if ($resellerBrand) {
            $resellerBrand->load('parentBrand');
            $order->setRelation('brand', $resellerBrand);
        }

        $this->resolveItemNamesInBatch($order);
        $headerBrand = $order->brand ? $order->brand->getHeaderBrand() : null;
        $logoData = $headerBrand ? $this->logoDataUri($headerBrand->logo) : '';
        $progresses = Progress::active()->ordered()->get();

        $groupedItems = \App\Support\PoGroupHelper::group($order->items);
        $nonAddonItems = $groupedItems->filter(fn($item) => !$item->is_addon)->values();
        $addonItems = $groupedItems->filter(fn($item) => $item->is_addon)->values();

        $pdf = Pdf::loadView('pdf.fo', [
            'order' => $order,
            'headerBrand' => $headerBrand,
            'logoData' => $logoData,
            'progresses' => $progresses,
            'nonAddonItems' => $nonAddonItems,
            'addonItems' => $addonItems,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("FO-{$order->no_po}.pdf");
    }

    public function foPreview(Request $request, Order $order)
    {
        Gate::authorize('order.view');
        $this->guardBrandOwnership($request, $order);

        $order->load([
            'brand', 'resellerDisplayBrand', 'pelanggan', 'kategoriOrder', 'jenisOrder', 'sumberOrder', 'paketOrder',
            'items.bahanKain', 'items.bahanKainBawahan', 'items.logo', 'items.resleting', 'items.printing',
            'items.polaJahitan', 'items.polaJahitanLengan',
            'items.jenisSetelan', 'items.polaProduksi',
            'items.namesets.size', 'items.namesets.sizeCelana',
            'creator.brands',
        ]);

        $resellerBrand = $order->resolveResellerBrand();
        if ($resellerBrand) {
            $resellerBrand->load('parentBrand');
            $order->setRelation('brand', $resellerBrand);
        }

        $this->resolveItemNamesInBatch($order);

        $printings = collect();
        $printingNames = collect();
        if (!empty($order->printing_ids)) {
            $printings = Printing::whereIn('id', $order->printing_ids)->pluck('nama');
            $printingNames = $printings;
        }
        $printingStr = $printingNames && $printingNames->count() > 0 ? $printingNames->join(', ') : '';

        $progresses = Progress::active()->ordered()->get();

        // Get header brand for FO display (uses getHeaderBrand() to respect system settings)
        $headerBrand = $order->brand ? $order->brand->getHeaderBrand() : null;

        $groupedItems = \App\Support\PoGroupHelper::group($order->items);

        return Inertia::render('Order/FoPreview', [
            'order' => $order,
            'printings' => $printings,
            'printingStr' => $printingStr,
            'progresses' => $progresses,
            'headerBrand' => $headerBrand,
            'groupedNonAddonItems' => $groupedItems->filter(fn($item) => !$item->is_addon)->values(),
        ]);
    }

    public function publicFoPreview(Request $request, string $noPo)
    {
        $noPoCol = 'no_po';
        $order = Order::where($noPoCol, $noPo)->firstOrFail();

        $order->load([
            'brand', 'resellerDisplayBrand', 'pelanggan', 'kategoriOrder', 'jenisOrder', 'sumberOrder', 'paketOrder',
            'items.bahanKain', 'items.bahanKainBawahan', 'items.logo', 'items.resleting', 'items.printing',
            'items.polaJahitan', 'items.polaJahitanLengan',
            'items.jenisSetelan', 'items.polaProduksi',
            'items.namesets.size', 'items.namesets.sizeCelana',
            'creator.brands',
        ]);

        $resellerBrand = $order->resolveResellerBrand();
        if ($resellerBrand) {
            $resellerBrand->load('parentBrand');
            $order->setRelation('brand', $resellerBrand);
        }

        $this->resolveItemNamesInBatch($order);

        $printings = collect();
        $printingNames = collect();
        if (!empty($order->printing_ids)) {
            $printings = Printing::whereIn('id', $order->printing_ids)->pluck('nama');
            $printingNames = $printings;
        }
        $printingStr = $printingNames && $printingNames->count() > 0 ? $printingNames->join(', ') : '';

        $progresses = Progress::active()->ordered()->get();

        // Get header brand for FO display (uses getHeaderBrand() to respect system settings)
        $headerBrand = $order->brand ? $order->brand->getHeaderBrand() : null;

        $groupedItems = \App\Support\PoGroupHelper::group($order->items);

        return Inertia::render('Order/FoPreview', [
            'order' => $order,
            'printings' => $printings,
            'printingStr' => $printingStr,
            'progresses' => $progresses,
            'headerBrand' => $headerBrand,
            'groupedNonAddonItems' => $groupedItems->filter(fn($item) => !$item->is_addon)->values(),
            'isPublic' => true,
        ]);
    }

    public function publicFoPdf(Request $request, string $noPo)
    {
        ini_set('max_execution_time', 120);
        ini_set('memory_limit', '512M');
        $noPoCol = 'no_po';
        $order = Order::where($noPoCol, $noPo)->firstOrFail();

        $order->load([
            'brand.parentBrand', 'pelanggan', 'kategoriOrder', 'jenisOrder', 'sumberOrder', 'paketOrder',
            'items.bahanKain', 'items.bahanKainBawahan', 'items.logo', 'items.resleting', 'items.printing',
            'items.polaJahitan', 'items.polaJahitanLengan',
            'items.jenisSetelan', 'items.polaProduksi',
            'items.namesets.size', 'items.namesets.sizeCelana',
            'creator.brands',
        ]);

        $resellerBrand = $order->resolveResellerBrand();
        if ($resellerBrand) {
            $resellerBrand->load('parentBrand');
            $order->setRelation('brand', $resellerBrand);
        }

        $this->resolveItemNamesInBatch($order);
        $headerBrand = $order->brand ? $order->brand->getHeaderBrand() : null;
        $logoData = $headerBrand ? $this->logoDataUri($headerBrand->logo) : '';
        $progresses = Progress::active()->ordered()->get();

        $groupedItems = \App\Support\PoGroupHelper::group($order->items);
        $nonAddonItems = $groupedItems->filter(fn($item) => !$item->is_addon)->values();
        $addonItems = $groupedItems->filter(fn($item) => $item->is_addon)->values();

        $pdf = Pdf::loadView('pdf.fo', [
            'order' => $order,
            'headerBrand' => $headerBrand,
            'logoData' => $logoData,
            'progresses' => $progresses,
            'nonAddonItems' => $nonAddonItems,
            'addonItems' => $addonItems,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("FO-{$order->no_po}.pdf");
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
        $oldStartStr = $oldStart instanceof \DateTimeInterface ? $oldStart->format('Y-m-d') : ($oldStart ? \Carbon\Carbon::parse((string) $oldStart)->format('Y-m-d') : null);
        $newStartStr = $newStart instanceof \DateTimeInterface ? $newStart->format('Y-m-d') : ($newStart ? \Carbon\Carbon::parse((string) $newStart)->format('Y-m-d') : null);
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

        $oldEndStr = $oldEnd instanceof \DateTimeInterface ? $oldEnd->format('Y-m-d') : ($oldEnd ? \Carbon\Carbon::parse((string) $oldEnd)->format('Y-m-d') : null);
        $newEndStr = $newEnd instanceof \DateTimeInterface ? $newEnd->format('Y-m-d') : ($newEnd ? \Carbon\Carbon::parse((string) $newEnd)->format('Y-m-d') : null);
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
    private function resolveResellerBrandsForForm(\App\Models\User $user, string $currentBrandId): array
    {
        $brandsIdCol = 'brands.id';
        $namaBrandCol = 'nama_brand';
        return $user->brands()
            ->where($brandsIdCol, '!=', $currentBrandId)
            ->orderBy($namaBrandCol)
            ->get(['brands.id', 'brands.nama_brand', 'brands.kode'])
            ->toArray();
    }

    /**
     * Get order related master data for form options.
     *
     * @return array<string, mixed>
     */
    private function getOrderRelatedMasters(\Closure $masterQ, string $masterBrandId): array
    {
        $namaCol = 'nama';
        $brandIdCol = 'brand_id';
        return [
            'kategori_orders' => KategoriOrder::active()->where($masterQ)->orderBy($namaCol)->get(['id', 'nama']),
            'jenis_orders' => JenisOrder::active()->where($masterQ)->orderBy($namaCol)->get(['id', 'nama']),
            'sumber_orders' => SumberOrder::active()
                ->where($masterQ)
                ->whereDoesntHave('children')
                ->with('parent:id,nama')
                ->get(['id', 'nama', 'parent_id'])
                ->map(fn($s) => [
                    'id' => $s->id,
                    'nama' => $s->parent_id && $s->parent ? "{$s->parent->nama} — {$s->nama}" : $s->nama,
                ])
                ->sortBy('nama')
                ->values()
                ->toArray(),
            'iklans' => Iklan::active()->where($masterQ)->orderBy($namaCol)->get(['id', 'nama', 'platform']),
            'pelanggan' => Customer::active()->where($brandIdCol, $masterBrandId)->orderBy($namaCol)->limit(500)->get(['id', 'kode', 'nama', 'nomor_hp']),
        ];
    }

    /**
     * Get production related master data for form options.
     *
     * @return array<string, mixed>
     */
    private function getProductionRelatedMasters(\Closure $masterQ): array
    {
        $namaCol = 'nama';
        $prioritasCol = 'prioritas';
        $jenisPolaCol = 'jenis_pola';
        $urutanCol = 'urutan';
        return [
            'jenis_setelan'  => JenisSetelan::active()->orderBy($namaCol)->get(['id', 'nama', 'deskripsi']),
            'pola_produksi'  => PolaProduksi::active()->orderBy($namaCol)->get(['id', 'nama', 'deskripsi']),
            'jenis_produk'  => JenisProduk::active()->orderBy($namaCol)->get(['id', 'nama']),
            'paket_orders'  => PaketOrder::active()->orderBy($prioritasCol)->orderBy($namaCol)->get(['id', 'nama', 'warna', 'prioritas']),
            'produk' => Product::active()->where($masterQ)->orderBy($namaCol)->get(['id', 'nama', 'harga']),
            'bahan_kains' => BahanKain::active()->orderBy($namaCol)->get(['id', 'nama']),
            'logos' => Logo::active()->orderBy($namaCol)->get(['id', 'nama']),
            'printings' => Printing::active()->orderBy($namaCol)->get(['id', 'nama']),
            'resletings' => Resleting::active()->orderBy($namaCol)->get(['id', 'nama']),
            'pola_jahitans' => PolaJahitan::active()->orderBy($jenisPolaCol)->orderBy($namaCol)->get(['id', 'jenis_pola', 'nama']),
            'pola_jahitans_lengan' => PolaJahitan::active()->where($jenisPolaCol, 'like', '%Lengan%')->orderBy($namaCol)->get(['id', 'jenis_pola', 'nama']),
            'sizes' => Size::active()->orderBy($urutanCol)->get(['id', 'ukuran']),
        ];
    }

    /**
     * Get bank accounts accessible by the current authenticated user and current brand context.
     */
    private function getAccessibleBanks(string $masterBrandId, ?string $currentBrandId): \Illuminate\Support\Collection
    {
        $currentBrandId = $currentBrandId ?? $masterBrandId;
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $userBrandIds = ($user && ($user->isSuperadmin() || $user->hasRole(['owner', 'admin_keuangan', 'admin_produksi'])))
            ? null
            : ($user ? $user->brands()->pluck('brands.id')->toArray() : []);

        $banksQuery = BankAccount::active();
        $brandIdCol = 'brand_id';
        $bankCol = 'bank';
        if ($userBrandIds !== null) {
            $allRelevantBrandIds = array_unique(array_merge($userBrandIds, [$masterBrandId, $currentBrandId]));
            $banksQuery->whereIn($brandIdCol, $allRelevantBrandIds);
        }
        $rawBanks = $banksQuery->orderBy($bankCol)->get(['id', 'bank', 'atas_nama', 'nomor_rekening', 'brand_id']);

        $banks = collect();
        $accessibleBrands = ($user && ($user->isSuperadmin() || $user->hasRole(['owner', 'admin_keuangan', 'admin_produksi'])))
            ? Brand::all()
            : ($user ? $user->brands : collect());

        foreach ($accessibleBrands as $brand) {
            $brandId = $brand->id;
            $mBrandId = BrandContext::masterDataId(request(), $brandId);

            foreach ($rawBanks as $bank) {
                if ($bank->brand_id === $brandId) {
                    $banks->push($bank);
                } elseif ($mBrandId && $bank->brand_id === $mBrandId) {
                    $cloned = clone $bank;
                    $cloned->brand_id = $brandId;
                    $banks->push($cloned);
                }
            }
        }

        foreach ($rawBanks as $bank) {
            $banks->push($bank);
        }

        return $banks->unique(function ($item) {
            return $item->id . '-' . $item->brand_id;
        })->values();
    }

    private function mastersForForm(string $masterBrandId, ?string $currentBrandId = null): array
    {
        $currentBrandId = $currentBrandId ?? $masterBrandId;

        $masterQ = fn ($q) => $q->where(function ($w) use ($masterBrandId) {
            $w->where('brand_id', $masterBrandId)->orWhereNull('brand_id');
        });

        $orderMasters = $this->getOrderRelatedMasters($masterQ, $masterBrandId);
        $productionMasters = $this->getProductionRelatedMasters($masterQ);
        $banks = $this->getAccessibleBanks($masterBrandId, $currentBrandId);

        $namaCol = 'nama';
        return array_merge($orderMasters, $productionMasters, [
            'banks' => $banks,
            'jenis_pembayarans' => \App\Models\Finance\MasterJenisPembayaran::active()->orderBy($namaCol)->get(['id', 'nama', 'tipe_keuangan', 'efek_tagihan', 'deskripsi']),
        ]);
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'nama_po' => ['required', 'string', 'max:255'],
            'change_reason' => ['nullable', 'string', 'max:255'],
            'is_special_order' => ['nullable', 'boolean'],
            'is_free_ongkir' => ['nullable', 'boolean'],
            'ongkir' => ['nullable', 'numeric', 'min:0'],
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
            'reseller_display_brand_id' => ['nullable', 'uuid', 'exists:brands,id'],
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
            'items.*.jenis_setelan_id' => ['nullable', 'uuid', 'exists:jenis_setelan,id'],
            'items.*.pola' => ['nullable', 'string', 'max:50'],
            'items.*.pola_produksi_id' => ['nullable', 'uuid', 'exists:pola_produksi,id'],
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
        $order->items()->each(function (OrderItem $i) {
            $i->namesets()->delete();
            $i->delete();
        });

        foreach ($items as $item) {
            $namesets = $item['namesets'] ?? [];
            unset($item['namesets']);

            // Sanitize and filter out empty nameset records
            $filteredNamesets = [];
            foreach ($namesets as $originalIdx => $ns) {
                $hasData = false;
                foreach ([
                    'nama_punggung', 'nomor_punggung', 'nama_dada', 'nomor_dada',
                    'nama_lengan', 'nomor_lengan', 'nomor_punggung_2', 'nama_punggung_2',
                    'size_id', 'size_label', 'size_celana_id', 'size_celana_label', 'keterangan'
                ] as $field) {
                    if (isset($ns[$field]) && trim((string)$ns[$field]) !== '') {
                        $hasData = true;
                        break;
                    }
                }

                if ($hasData) {
                    $sanitizedNs = [];
                    foreach ($ns as $key => $val) {
                        if (is_string($val)) {
                            $trimmed = trim($val);
                            $sanitizedNs[$key] = ($trimmed === '') ? null : $trimmed;
                        } else {
                            $sanitizedNs[$key] = $val;
                        }
                    }
                    $sanitizedNs['_original_index'] = $originalIdx;
                    $filteredNamesets[] = $sanitizedNs;
                }
            }
            $namesets = $filteredNamesets;

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

            foreach ($namesets as $idx => $ns) {
                unset($ns['_original_index']);
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
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
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

    private function logoDataUri(?string $logoPath): string
    {
        if (empty($logoPath)) return '';
        try {
            // Clean/normalize path
            $normalizedPath = $logoPath;
            
            // If it's a URL, parse and get the path component
            if (str_starts_with($logoPath, 'http://') || str_starts_with($logoPath, 'https://')) {
                $parsed = parse_url($logoPath);
                $normalizedPath = ltrim($parsed['path'] ?? '', '/');
            }
            
            // Strip leading slashes and storage prefix
            $normalizedPath = ltrim($normalizedPath, '/');
            if (str_starts_with($normalizedPath, 'storage/')) {
                $normalizedPath = substr($normalizedPath, 8);
            }

            // Try candidate paths in order of preference
            $candidates = [
                storage_path('app/public/' . $normalizedPath),
                public_path('storage/' . $normalizedPath),
                public_path($normalizedPath),
                $logoPath, // fallback
            ];

            $fullPath = null;
            foreach ($candidates as $candidate) {
                if (!empty($candidate) && file_exists($candidate) && !is_dir($candidate)) {
                    $fullPath = $candidate;
                    break;
                }
            }

            if ($fullPath) {
                $type = pathinfo($fullPath, PATHINFO_EXTENSION);
                if (empty($type)) {
                    $type = 'png';
                }
                $data = file_get_contents($fullPath);
                return 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("logoDataUri failed in OrderController for {$logoPath}: " . $e->getMessage());
        }
        return '';
    }

    private function resolveItemNamesInBatch(Order $order): void
    {
        $allLogoIds = [];
        $allBahanKainIds = [];
        foreach ($order->items as $item) {
            if (!empty($item->logo_ids) && is_array($item->logo_ids)) {
                $allLogoIds = array_merge($allLogoIds, $item->logo_ids);
            }
            if (!empty($item->bahan_kain_ids) && is_array($item->bahan_kain_ids)) {
                $allBahanKainIds = array_merge($allBahanKainIds, $item->bahan_kain_ids);
            }
            if (!empty($item->bahan_kain_bawahan_ids) && is_array($item->bahan_kain_bawahan_ids)) {
                $allBahanKainIds = array_merge($allBahanKainIds, $item->bahan_kain_bawahan_ids);
            }
        }
        $allLogoIds = array_unique(array_filter($allLogoIds));
        $allBahanKainIds = array_unique(array_filter($allBahanKainIds));

        $logos = !empty($allLogoIds)
            ? Logo::whereIn('id', $allLogoIds)->pluck('nama', 'id')->toArray()
            : [];
        $bahanKains = !empty($allBahanKainIds)
            ? BahanKain::whereIn('id', $allBahanKainIds)->pluck('nama', 'id')->toArray()
            : [];

        foreach ($order->items as $item) {
            $itemLogos = [];
            if (!empty($item->logo_ids) && is_array($item->logo_ids)) {
                foreach ($item->logo_ids as $lid) {
                    if (isset($logos[$lid])) {
                        $itemLogos[] = $logos[$lid];
                    }
                }
            }
            $item->logo_names = $itemLogos;

            $itemBahanKains = [];
            if (!empty($item->bahan_kain_ids) && is_array($item->bahan_kain_ids)) {
                foreach ($item->bahan_kain_ids as $bkid) {
                    if (isset($bahanKains[$bkid])) {
                        $itemBahanKains[] = $bahanKains[$bkid];
                    }
                }
            }
            $item->bahan_kains_names = !empty($itemBahanKains) ? implode(', ', $itemBahanKains) : null;

            $itemBahanKainBawahans = [];
            if (!empty($item->bahan_kain_bawahan_ids) && is_array($item->bahan_kain_bawahan_ids)) {
                foreach ($item->bahan_kain_bawahan_ids as $bkid) {
                    if (isset($bahanKains[$bkid])) {
                        $itemBahanKainBawahans[] = $bahanKains[$bkid];
                    }
                }
            }
            $item->bahan_kain_bawahan_names = !empty($itemBahanKainBawahans) ? implode(', ', $itemBahanKainBawahans) : null;
        }
    }
}
