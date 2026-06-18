<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Master\Customer;
use App\Models\Order\Invoice;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\OrderPayment;
use App\Models\Order\Refund;
use App\Models\Order\Rijek;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    // ----- query helpers -----

    /** Filter brand_id column (direct column on the model's table) */
    private function bf(string|array|null $brandId): \Closure
    {
        return fn ($q) => is_array($brandId)
            ? $q->whereIn('brand_id', $brandId)
            : $q->when($brandId && $brandId !== 'all', fn ($q2) => $q2->where('brand_id', $brandId));
    }

    /** Filter orders.brand_id (for join queries where orders is joined) */
    private function obf(string|array|null $brandId): \Closure
    {
        return fn ($q) => is_array($brandId)
            ? $q->whereIn('orders.brand_id', $brandId)
            : $q->when($brandId && $brandId !== 'all', fn ($q2) => $q2->where('orders.brand_id', $brandId));
    }

    /**
     * Expand hub brand IDs → hub + semua branch-nya.
     * Hub bisa punya order langsung, jadi ID hub ikut disertakan.
     * Regular brands dan branches dikembalikan as-is.
     */
    private function expandToOperationalIds(array $brandIds): array
    {
        $result = [];
        foreach ($brandIds as $id) {
            $brand = Brand::select('id', 'brand_type')->find($id);
            if ($brand && $brand->brand_type === Brand::TYPE_RESELLER_HUB) {
                // Hub sendiri + semua branch-nya
                $branches = Brand::where('parent_brand_id', $id)->pluck('id')->toArray();
                $result[] = $id; // hub ikut
                array_push($result, ...$branches);
            } else {
                $result[] = $id;
            }
        }
        return array_unique($result);
    }

    // ----- public stats -----

    public function adminBrandStats(string|array|null $brandId): array
    {
        $base = Order::query()->when($brandId, $this->bf($brandId));

        $today = (clone $base)->whereDate('tanggal_masuk', today())->count();
        $week  = (clone $base)->whereBetween('tanggal_masuk', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $month = (clone $base)->whereMonth('tanggal_masuk', now()->month)->whereYear('tanggal_masuk', now()->year)->count();

        $totalProdukDiOrder = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->when($brandId, $this->obf($brandId))
            ->distinct('order_items.product_id')->count('order_items.product_id');

        $totalPelanggan = Customer::query()->when($brandId, $this->bf($brandId))->count();

        $totalWilayah = Customer::query()
            ->when($brandId, $this->bf($brandId))
            ->whereNotNull('kabupaten_code')->distinct('kabupaten_code')->count('kabupaten_code');

        $dpPendingCount = \App\Models\Order\DesignDeposit::query()
            ->when($brandId, $this->bf($brandId))
            ->where('status', 'pending')
            ->count();

        $refundPendingCount = \App\Models\Order\Refund::query()
            ->when($brandId, $this->bf($brandId))
            ->where('status', 'pending_review')
            ->count();

        return [
            'cards' => [
                ['label' => 'Order Hari Ini', 'value' => $today, 'icon' => 'Calendar', 'accent' => 'blue'],
                ['label' => 'Order Minggu Ini', 'value' => $week, 'icon' => 'CalendarRange', 'accent' => 'emerald'],
                ['label' => 'Order Bulan Ini', 'value' => $month, 'icon' => 'CalendarDays', 'accent' => 'violet'],
                ['label' => 'Produk Di-order', 'value' => $totalProdukDiOrder, 'icon' => 'Package', 'accent' => 'amber'],
                ['label' => 'Total Pelanggan', 'value' => $totalPelanggan, 'icon' => 'Users', 'accent' => 'pink'],
                ['label' => 'Wilayah Tercakup', 'value' => $totalWilayah, 'icon' => 'MapPin', 'accent' => 'cyan'],
                ['label' => 'Tanda Jadi Pending', 'value' => $dpPendingCount, 'icon' => 'Sparkles', 'accent' => 'amber'],
                ['label' => 'Refund Pending', 'value' => $refundPendingCount, 'icon' => 'RotateCcw', 'accent' => 'red'],
            ],
            'status_breakdown'              => $this->statusBreakdown($brandId),
            'trend_harian'                  => $this->trendHarian($brandId, 14),
            'produk_terpopuler'             => $this->produkTerpopuler($brandId, 10),
            'kategori_distribusi'           => $this->kategoriDistribusi($brandId),
            'sumber_distribusi'             => $this->sumberDistribusi($brandId),
            'kategori_pelanggan_distribusi' => $this->kategoriPelangganDistribusi($brandId),
            'wilayah_top'                   => $this->wilayahTop($brandId, 8),
            'top_pelanggan'                 => $this->topPelanggan($brandId, 5),
            'po_terbaru'                    => $this->poTerbaru($brandId, 10),
            'deadline_mendekat'             => $this->deadlineMendekat($brandId, 5),
            'po_terlambat'                  => $this->poTerlambat($brandId, 5),
            'trend_bulanan'                 => $this->trendBulanan($brandId),
            'target_progress'               => $this->getTargetProgress($brandId),
            'dp_pending_list' => \App\Models\Order\DesignDeposit::query()
                ->when($brandId, $this->bf($brandId))
                ->where('status', 'pending')
                ->with(['customer:id,nama', 'brand:id,nama_brand,kode'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(),
            'refund_pending_list' => \App\Models\Order\Refund::query()
                ->when($brandId, $this->bf($brandId))
                ->where('status', 'pending_review')
                ->with(['order:id,no_po', 'creator:id,name'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(),
        ];
    }

    public function adminProduksiStats(string|array|null $brandId): array
    {
        $base = Order::query()->when($brandId, $this->bf($brandId));

        $dalamProses  = (clone $base)->where('status_po', 'on_progress')->count();
        $selesaiToday = (clone $base)->where('status_po', 'selesai_produksi')
            ->whereDate('updated_at', today())->count();
        $deadlineDekat = (clone $base)
            ->whereIn('status_po', ['published', 'on_progress'])
            ->whereBetween('deadline_customer', [now(), now()->addDays(7)])
            ->count();

        $totalRijek = Rijek::query()
            ->join('orders', 'orders.id', '=', 'rijeks.order_id')
            ->when($brandId, $this->obf($brandId))
            ->sum('rijeks.jumlah');
        $totalProduksi = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->when($brandId, $this->obf($brandId))
            ->where('orders.status_po', '!=', 'draft')
            ->sum('order_items.quantity');
        $rijekRate = $totalProduksi > 0 ? round(($totalRijek / $totalProduksi) * 100, 2) : 0;

        return [
            'cards' => [
                ['label' => 'Order Dalam Proses', 'value' => $dalamProses,    'icon' => 'Settings2',     'accent' => 'amber'],
                ['label' => 'Selesai Hari Ini',   'value' => $selesaiToday,   'icon' => 'CheckCircle2',  'accent' => 'emerald'],
                ['label' => 'Deadline ≤ 7 Hari',  'value' => $deadlineDekat,  'icon' => 'AlarmClock',    'accent' => 'orange'],
                ['label' => 'Rijek Rate',          'value' => $rijekRate.'%',  'icon' => 'AlertTriangle', 'accent' => 'red'],
            ],
            'status_breakdown'     => $this->statusBreakdown($brandId),
            'rijek_by_jenis'       => $this->rijekByJenis($brandId),
            'rijek_by_tingkat'     => $this->rijekByTingkat($brandId),
            'progress_distribution' => $this->progressDistribution($brandId),
            'deadline_mendekat'    => $this->deadlineMendekat($brandId, 10),
            'po_terlambat'         => $this->poTerlambat($brandId, 10),
        ];
    }

    public function superadminStats(string|array|null $brandId = null): array
    {
        $totalBrandAktif = Brand::active()->count();
        $totalUser       = \App\Models\User::count();
        
        $baseOrder = Order::query()->when($brandId && $brandId !== 'all', $this->bf($brandId));
        $totalOrder      = (clone $baseOrder)->count();
        $totalRevenue    = (clone $baseOrder)->sum('total_tagihan');

        $perBrand = Order::query()
            ->when($brandId && $brandId !== 'all', $this->bf($brandId))
            ->leftJoin(DB::raw('(SELECT order_id, SUM(quantity) as qty FROM order_items GROUP BY order_id) as items_sum'), 'items_sum.order_id', '=', 'orders.id')
            ->select('orders.brand_id', DB::raw('COUNT(orders.id) as total'), DB::raw('SUM(orders.total_tagihan) as revenue'), DB::raw('COALESCE(SUM(items_sum.qty), 0) as total_pcs'))
            ->groupBy('orders.brand_id')
            ->with('brand:id,nama_brand,kode,warna_primary')
            ->get()
            ->map(fn ($r) => [
                'brand'     => $r->brand?->nama_brand ?? '-',
                'kode'      => $r->brand?->kode,
                'warna'     => $r->brand?->warna_primary,
                'total'     => (int) $r->total,
                'revenue'   => (float) $r->revenue,
                'total_pcs' => (int) $r->total_pcs,
            ]);

        return [
            'cards' => [
                ['label' => 'Total Brand Aktif', 'value' => $totalBrandAktif, 'icon' => 'Building2',   'accent' => 'blue'],
                ['label' => 'Total User',         'value' => $totalUser,       'icon' => 'Users',        'accent' => 'emerald'],
                ['label' => 'Total Order',        'value' => $totalOrder,     'icon' => 'Package',      'accent' => 'violet'],
                ['label' => 'Total Revenue',       'value' => $totalRevenue,   'currency' => true, 'icon' => 'TrendingUp', 'accent' => 'amber'],
            ],
            'brand_performance' => $perBrand,
            'status_breakdown'  => $this->statusBreakdown($brandId),
            'trend_harian'      => $this->trendHarian($brandId, 14),
            'po_terbaru'        => $this->poTerbaru($brandId, 10),
            'trend_bulanan'     => $this->trendBulanan($brandId),
            'target_progress'   => $this->getTargetProgress($brandId ?: Brand::active()->pluck('id')->toArray()),
        ];
    }

    public function ownerStats(User $user, ?string $filterBrand): array
    {
        $ownedBrandIds = $user->brands()->pluck('brands.id')->all();

        // Expand reseller hubs to include hub itself + all branch IDs
        $allOpIds = $this->expandToOperationalIds($ownedBrandIds);

        if ($filterBrand && $filterBrand !== 'all') {
            $fb = Brand::select('id', 'brand_type')->find($filterBrand);
            $opBrandIds = ($fb && $fb->brand_type === Brand::TYPE_RESELLER_HUB)
                ? Brand::where('parent_brand_id', $filterBrand)->pluck('id')->toArray()
                : [$filterBrand];
        } else {
            $opBrandIds = $allOpIds;
        }

        $base         = Order::query()->whereIn('brand_id', $opBrandIds);
        $totalRevenue = (clone $base)->sum('total_tagihan');
        $totalPo      = (clone $base)->count();
        $outstanding  = $totalRevenue - OrderPayment::whereHas(
            'order', fn ($q) => $q->whereIn('brand_id', $opBrandIds)
        )->sum('amount');
        $rejectRate = $this->calculateRejectRate($opBrandIds);

        return [
            'cards' => [
                ['label' => 'Total PO',             'value' => $totalPo,                  'icon' => 'Package',       'accent' => 'blue'],
                ['label' => 'Total Revenue',         'value' => $totalRevenue, 'currency' => true, 'icon' => 'TrendingUp',    'accent' => 'emerald'],
                ['label' => 'Outstanding Payment',   'value' => max(0, $outstanding), 'currency' => true, 'icon' => 'CreditCard',    'accent' => 'orange'],
                ['label' => 'Rijek Rate',            'value' => $rejectRate.'%',           'icon' => 'AlertTriangle', 'accent' => 'red'],
            ],
            'owned_brands' => Brand::whereIn('id', $ownedBrandIds)->get(['id', 'nama_brand', 'kode', 'warna_primary']),
            'brand_performance' => Order::query()
                ->whereIn('orders.brand_id', $opBrandIds)
                ->leftJoin(DB::raw('(SELECT order_id, SUM(quantity) as qty FROM order_items GROUP BY order_id) as items_sum'), 'items_sum.order_id', '=', 'orders.id')
                ->select('orders.brand_id', DB::raw('COUNT(orders.id) as total'), DB::raw('SUM(orders.total_tagihan) as revenue'), DB::raw('COALESCE(SUM(items_sum.qty), 0) as total_pcs'))
                ->groupBy('orders.brand_id')
                ->with('brand:id,nama_brand,kode,warna_primary')
                ->get()
                ->map(fn ($r) => [
                    'brand'     => $r->brand?->nama_brand,
                    'kode'      => $r->brand?->kode,
                    'warna'     => $r->brand?->warna_primary,
                    'total'     => (int) $r->total,
                    'revenue'   => (float) $r->revenue,
                    'total_pcs' => (int) $r->total_pcs,
                ]),
            'status_breakdown'              => $this->statusBreakdown($opBrandIds),
            'trend_harian'                  => $this->trendHarian($opBrandIds, 14),
            // Marketing analytics — sama seperti AdminBrand
            'produk_terpopuler'             => $this->produkTerpopuler($opBrandIds, 10),
            'kategori_distribusi'           => $this->kategoriDistribusi($opBrandIds),
            'sumber_distribusi'             => $this->sumberDistribusi($opBrandIds),
            'kategori_pelanggan_distribusi' => $this->kategoriPelangganDistribusi($opBrandIds),
            'wilayah_top'                   => $this->wilayahTop($opBrandIds, 8),
            'top_pelanggan'                 => $this->topPelanggan($opBrandIds, 5),
            'trend_bulanan'                 => $this->trendBulanan($opBrandIds),
            'po_terbaru'                    => $this->poTerbaru($opBrandIds, 10),
            'deadline_mendekat'             => $this->deadlineMendekat($opBrandIds, 5),
            'po_terlambat'                  => $this->poTerlambat($opBrandIds, 5),
            'target_progress'               => $this->getTargetProgress($opBrandIds),
            'current_brand_id'              => $filterBrand ?: 'all',
        ];
    }

    public function financeStats(string|array|null $brandId): array
    {
        $invoices = Invoice::query()->when($brandId && $brandId !== 'all', $this->bf($brandId));
        $refunds  = Refund::query()->when($brandId && $brandId !== 'all', $this->bf($brandId));

        $invoicePending      = (clone $invoices)->whereIn('status', ['draft', 'validated'])->count();
        $invoiceToday        = (clone $invoices)->whereDate('tanggal_terbit', today())->count();
        $totalTagihanPending = (clone $invoices)->whereIn('status', ['draft', 'validated', 'published'])->sum('sisa_pembayaran');

        $paidToday = OrderPayment::query()
            ->whereDate('payment_date', today())
            ->when($brandId && $brandId !== 'all', fn ($q) => $q->whereHas('order', $this->bf($brandId)))
            ->sum('amount');

        $refundPending        = (clone $refunds)->where('status', 'pending_review')->count();
        $refundPublished      = (clone $refunds)->where('status', 'published');
        $refundPublishedCount  = (clone $refundPublished)->count();
        $refundPublishedAmount = (clone $refundPublished)->sum('nominal_refund');

        // Total payments verified
        $totalPayments = OrderPayment::query()
            ->when($brandId && $brandId !== 'all', fn ($q) => $q->whereHas('order', $this->bf($brandId)))
            ->whereNotNull('verified_at')
            ->sum('amount');

        // Total refunds published
        $totalRefunds = Refund::query()
            ->when($brandId && $brandId !== 'all', $this->bf($brandId))
            ->where('status', 'published')
            ->sum('nominal_refund');

        $outstandingTotal = Order::query()
            ->when($brandId && $brandId !== 'all', $this->bf($brandId))
            ->where('status_po', '!=', 'draft')
            ->sum('total_tagihan')
            - ($totalPayments - $totalRefunds);

        $dpPendingCount = \App\Models\Order\DesignDeposit::query()
            ->when($brandId && $brandId !== 'all', $this->bf($brandId))
            ->where('status', 'pending')
            ->count();

        $dpVerifiedAmount = \App\Models\Order\DesignDeposit::query()
            ->when($brandId && $brandId !== 'all', $this->bf($brandId))
            ->where('status', 'verified')
            ->sum('amount');

        $bankAccountsSummary = \App\Models\Master\BankAccount::query()
            ->when($brandId && $brandId !== 'all', $this->bf($brandId))
            ->with('brand:id,nama_brand,kode')
            ->get()
            ->map(function ($bank) use ($brandId) {
                // Sum verified payments
                $totalReceived = OrderPayment::where('bank_id', $bank->id)
                    ->whereNotNull('verified_at')
                    ->when($brandId && $brandId !== 'all', fn ($q) => $q->whereHas('order', $this->bf($brandId)))
                    ->sum('amount');

                // Last 15 transactions for matching bank statement (rekening koran)
                $recentTransactions = OrderPayment::where('bank_id', $bank->id)
                    ->when($brandId && $brandId !== 'all', fn ($q) => $q->whereHas('order', $this->bf($brandId)))
                    ->with(['order:id,no_po,nama_po,pelanggan_id', 'order.pelanggan:id,nama', 'masterJenisPembayaran:id,nama'])
                    ->orderByDesc('payment_date')
                    ->orderByDesc('created_at')
                    ->limit(15)
                    ->get()
                    ->map(fn ($p) => [
                        'id' => $p->id,
                        'no_po' => $p->order?->no_po,
                        'nama_po' => $p->order?->nama_po,
                        'pelanggan' => $p->order?->pelanggan?->nama ?? '-',
                        'tipe' => $p->masterJenisPembayaran?->nama ?? $p->payment_type,
                        'amount' => (float) $p->amount,
                        'payment_date' => $p->payment_date?->toDateString(),
                        'verified' => !empty($p->verified_at),
                        'notes' => $p->notes,
                    ]);

                return [
                    'id' => $bank->id,
                    'bank' => $bank->bank,
                    'atas_nama' => $bank->atas_nama,
                    'nomor_rekening' => $bank->nomor_rekening,
                    'brand_name' => $bank->brand?->nama_brand ?? 'General',
                    'brand_kode' => $bank->brand?->kode ?? 'GEN',
                    'total_received' => (float) $totalReceived,
                    'recent_transactions' => $recentTransactions,
                ];
            });

        $brandFinancialReports = \App\Models\Brand::active()
            ->when($brandId && $brandId !== 'all', fn ($q) => is_array($brandId) ? $q->whereIn('id', $brandId) : $q->where('id', $brandId))
            ->get()
            ->map(function ($brand) {
                // Total revenue (omset) based on published POs
                $totalRevenue = Order::where('brand_id', $brand->id)
                    ->where('status_po', '!=', 'draft')
                    ->sum('total_tagihan');

                // Total received payments (verified only)
                $totalPayments = OrderPayment::whereHas('order', fn ($q) => $q->where('brand_id', $brand->id))
                    ->whereNotNull('verified_at')
                    ->sum('amount');

                // Total refunds published
                $totalRefunds = Refund::where('brand_id', $brand->id)
                    ->where('status', 'published')
                    ->sum('nominal_refund');

                // Net payments received
                $netPayments = $totalPayments - $totalRefunds;

                // Total outstanding
                $outstanding = max(0, $totalRevenue - $netPayments);

                // Payments by payment types for this brand
                $paymentTypeBreakdown = DB::table('order_payments')
                    ->join('orders', 'orders.id', '=', 'order_payments.order_id')
                    ->leftJoin('master_jenis_pembayarans', 'master_jenis_pembayarans.id', '=', 'order_payments.master_jenis_pembayaran_id')
                    ->where('orders.brand_id', $brand->id)
                    ->select('master_jenis_pembayarans.nama', DB::raw('SUM(order_payments.amount) as total'))
                    ->groupBy('master_jenis_pembayarans.nama')
                    ->get()
                    ->map(fn ($r) => [
                        'nama' => $r->nama ?? 'Lainnya',
                        'total' => (float) $r->total,
                    ]);

                return [
                    'id' => $brand->id,
                    'nama_brand' => $brand->nama_brand,
                    'kode' => $brand->kode,
                    'warna' => $brand->warna_primary,
                    'total_revenue' => (float) $totalRevenue,
                    'total_payments' => (float) $totalPayments,
                    'outstanding' => (float) $outstanding,
                    'payment_type_breakdown' => $paymentTypeBreakdown,
                ];
            })
            ->filter(fn ($b) => $b['total_revenue'] > 0 || $b['total_payments'] > 0)
            ->values();

        $allActiveBrands = \App\Models\Brand::active()->get(['id', 'nama_brand', 'kode']);

        return [
            'cards' => [
                ['label' => 'Invoice Pending',      'value' => $invoicePending,       'icon' => 'FileText',    'accent' => 'amber'],
                ['label' => 'Invoice Hari Ini',     'value' => $invoiceToday,         'icon' => 'FileCheck',   'accent' => 'emerald'],
                ['label' => 'Tagihan Pending',       'value' => $totalTagihanPending,  'currency' => true, 'icon' => 'CreditCard',  'accent' => 'blue'],
                ['label' => 'Pembayaran Hari Ini',  'value' => $paidToday,            'currency' => true, 'icon' => 'TrendingUp',  'accent' => 'emerald'],
                ['label' => 'Outstanding Total',    'value' => max(0, $outstandingTotal), 'currency' => true, 'icon' => 'AlertCircle', 'accent' => 'red'],
                ['label' => 'Refund Pending',       'value' => $refundPending,        'icon' => 'RotateCcw',   'accent' => 'orange'],
                ['label' => 'Refund Diterbitkan',   'value' => $refundPublishedCount,  'icon' => 'CheckCircle2', 'accent' => 'violet'],
                ['label' => 'Total Refund',         'value' => $refundPublishedAmount, 'currency' => true, 'icon' => 'TrendingDown', 'accent' => 'pink'],
                ['label' => 'DP Desain Pending',     'value' => $dpPendingCount,       'icon' => 'Sparkles',    'accent' => 'amber'],
                ['label' => 'DP Desain Terverifikasi', 'value' => $dpVerifiedAmount,     'currency' => true, 'icon' => 'CheckCircle', 'accent' => 'indigo'],
            ],
            'invoice_pending_list' => (clone $invoices)
                ->whereIn('status', ['draft', 'validated'])
                ->with(['order:id,no_po,pelanggan_id', 'order.pelanggan:id,nama'])
                ->orderByDesc('created_at')->limit(10)->get(),
            'refund_pending_list' => (clone $refunds)
                ->where('status', 'pending_review')
                ->with(['order:id,no_po', 'creator:id,name'])
                ->orderByDesc('created_at')->limit(10)->get(),
            'dp_pending_list' => \App\Models\Order\DesignDeposit::query()
                ->when($brandId && $brandId !== 'all', $this->bf($brandId))
                ->where('status', 'pending')
                ->with(['customer:id,nama', 'brand:id,nama_brand,kode'])
                ->orderByDesc('created_at')->limit(10)->get(),
            'payment_status' => $this->paymentStatusBreakdown($brandId),
            'bank_accounts_summary' => $bankAccountsSummary,
            'brand_financial_reports' => $brandFinancialReports,
            'brands' => $allActiveBrands,
            'current_brand_id' => $brandId ?: 'all',
        ];
    }

    // ----- private helpers -----

    private function statusBreakdown(string|array|null $brandId): array
    {
        $rows = Order::query()
            ->when($brandId, $this->bf($brandId))
            ->select('status_po', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status_po')
            ->pluck('cnt', 'status_po')
            ->toArray();

        $statuses = [
            'draft'            => ['label' => 'Draft',            'color' => '#9CA3AF'],
            'published'        => ['label' => 'PO Masuk',         'color' => '#3B82F6'],
            'on_progress'      => ['label' => 'On Progress',      'color' => '#F59E0B'],
            'selesai_produksi' => ['label' => 'Selesai Produksi', 'color' => '#22C55E'],
            'siap_dikirim'     => ['label' => 'Siap Dikirim',     'color' => '#06B6D4'],
            'sudah_dikirim'    => ['label' => 'Sudah Dikirim',    'color' => '#8B5CF6'],
            'delay'            => ['label' => 'Delay',            'color' => '#EF4444'],
            'hold'             => ['label' => 'Hold',             'color' => '#F97316'],
        ];

        $out = [];
        foreach ($statuses as $key => $meta) {
            $out[] = ['key' => $key, 'label' => $meta['label'], 'color' => $meta['color'], 'count' => (int) ($rows[$key] ?? 0)];
        }
        return $out;
    }

    private function trendHarian(string|array|null $brandId, int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $rows = Order::query()
            ->when($brandId, $this->bf($brandId))
            ->where('tanggal_masuk', '>=', $from->toDateString())
            ->select(DB::raw('DATE(tanggal_masuk) as d'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('d')->pluck('cnt', 'd')->toArray();

        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $d     = $from->copy()->addDays($i)->toDateString();
            $out[] = ['date' => $d, 'count' => (int) ($rows[$d] ?? 0)];
        }
        return $out;
    }

    private function produkTerpopuler(string|array|null $brandId, int $limit): array
    {
        return OrderItem::query()
            ->when($brandId, fn ($q) => $q->whereHas('order', $this->bf($brandId)))
            ->select('nama_produk', DB::raw('SUM(quantity) as total_qty'), DB::raw('COUNT(DISTINCT order_id) as total_order'))
            ->groupBy('nama_produk')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => ['nama' => $r->nama_produk, 'total_qty' => (int) $r->total_qty, 'total_order' => (int) $r->total_order])
            ->all();
    }

    private function kategoriDistribusi(string|array|null $brandId): array
    {
        return Order::query()
            ->when($brandId, $this->bf($brandId))
            ->whereNotNull('kategori_order_id')
            ->with('kategoriOrder:id,nama')
            ->select('kategori_order_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('kategori_order_id')
            ->get()
            ->map(fn ($r) => ['label' => $r->kategoriOrder?->nama ?? '-', 'count' => (int) $r->cnt])
            ->all();
    }

    private function sumberDistribusi(string|array|null $brandId): array
    {
        return Order::query()
            ->when($brandId, $this->bf($brandId))
            ->whereNotNull('sumber_order_id')
            ->with('sumberOrder:id,nama')
            ->select('sumber_order_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('sumber_order_id')
            ->get()
            ->map(fn ($r) => ['label' => $r->sumberOrder?->nama ?? '-', 'count' => (int) $r->cnt])
            ->all();
    }

    private function kategoriPelangganDistribusi(string|array|null $brandId): array
    {
        return Order::query()
            ->when($brandId, $this->obf($brandId))
            ->join('customers', 'customers.id', '=', 'orders.pelanggan_id')
            ->leftJoin('customer_types', 'customer_types.id', '=', 'customers.type_pelanggan_id')
            ->select(DB::raw('COALESCE(customer_types.nama, "Tanpa Kategori") as label'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('label')
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'count' => (int) $r->cnt])
            ->all();
    }

    private function wilayahTop(string|array|null $brandId, int $limit): array
    {
        return Order::query()
            ->when($brandId, $this->obf($brandId))
            ->join('customers', 'customers.id', '=', 'orders.pelanggan_id')
            ->whereNotNull('customers.kabupaten_nama')
            ->select('customers.kabupaten_nama as nama', 'customers.provinsi_nama', DB::raw('COUNT(*) as cnt'))
            ->groupBy('customers.kabupaten_nama', 'customers.provinsi_nama')
            ->orderByDesc('cnt')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => ['nama' => $r->nama, 'provinsi' => $r->provinsi_nama, 'count' => (int) $r->cnt])
            ->all();
    }

    private function topPelanggan(string|array|null $brandId, int $limit): array
    {
        return Order::query()
            ->when($brandId, $this->bf($brandId))
            ->select('pelanggan_id', DB::raw('COUNT(*) as total_order'), DB::raw('SUM(total_tagihan) as total_value'))
            ->groupBy('pelanggan_id')
            ->with('pelanggan:id,nama,kode,nomor_hp')
            ->orderByDesc('total_value')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'nama'        => $r->pelanggan?->nama,
                'kode'        => $r->pelanggan?->kode,
                'total_order' => (int) $r->total_order,
                'total_value' => (float) $r->total_value,
            ])
            ->all();
    }

    private function poTerbaru(string|array|null $brandId, int $limit): array
    {
        return Order::query()
            ->when($brandId, $this->bf($brandId))
            ->with(['pelanggan:id,nama', 'brand:id,nama_brand,kode'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($o) => [
                'id'           => $o->id,
                'no_po'        => $o->no_po,
                'nama_po'      => $o->nama_po,
                'pelanggan'    => $o->pelanggan?->nama,
                'brand'        => $o->brand?->kode,
                'status'       => $o->status_po,
                'total_tagihan' => (float) $o->total_tagihan,
                'tanggal_masuk' => $o->tanggal_masuk?->toDateString(),
            ])
            ->all();
    }

    private function deadlineMendekat(string|array|null $brandId, int $limit): array
    {
        return Order::query()
            ->when($brandId, $this->bf($brandId))
            ->whereIn('status_po', ['published', 'on_progress'])
            ->whereBetween('deadline_customer', [now(), now()->addDays(7)])
            ->with(['pelanggan:id,nama'])
            ->orderBy('deadline_customer')
            ->limit($limit)
            ->get()
            ->map(fn ($o) => [
                'id'             => $o->id,
                'no_po'          => $o->no_po,
                'nama_po'        => $o->nama_po,
                'pelanggan'      => $o->pelanggan?->nama,
                'deadline'       => $o->deadline_customer?->toDateString(),
                'days_remaining' => now()->startOfDay()->diffInDays($o->deadline_customer, false),
                'status'         => $o->status_po,
            ])
            ->all();
    }

    private function poTerlambat(string|array|null $brandId, int $limit): array
    {
        return Order::query()
            ->when($brandId, $this->bf($brandId))
            ->whereNotIn('status_po', ['draft', 'sudah_dikirim'])
            ->where('deadline_customer', '<', today())
            ->with(['pelanggan:id,nama'])
            ->orderBy('deadline_customer')
            ->limit($limit)
            ->get()
            ->map(fn ($o) => [
                'id'        => $o->id,
                'no_po'     => $o->no_po,
                'nama_po'   => $o->nama_po,
                'pelanggan' => $o->pelanggan?->nama,
                'deadline'  => $o->deadline_customer?->toDateString(),
                'days_late' => abs(now()->startOfDay()->diffInDays($o->deadline_customer, false)),
                'status'    => $o->status_po,
            ])
            ->all();
    }

    private function rijekByJenis(string|array|null $brandId): array
    {
        return Rijek::query()
            ->when($brandId, fn ($q) => $q->whereHas('order', $this->bf($brandId)))
            ->select('jenis', DB::raw('SUM(jumlah) as total'))
            ->groupBy('jenis')
            ->get()
            ->map(fn ($r) => ['label' => $r->jenis, 'count' => (int) $r->total])
            ->all();
    }

    private function rijekByTingkat(string|array|null $brandId): array
    {
        return Rijek::query()
            ->when($brandId, fn ($q) => $q->whereHas('order', $this->bf($brandId)))
            ->select('tingkat', DB::raw('SUM(jumlah) as total'))
            ->groupBy('tingkat')
            ->get()
            ->map(fn ($r) => ['label' => $r->tingkat, 'count' => (int) $r->total])
            ->all();
    }

    private function progressDistribution(string|array|null $brandId): array
    {
        return DB::table('order_progress_details')
            ->join('progress', 'progress.id', '=', 'order_progress_details.progress_id')
            ->join('orders', 'orders.id', '=', 'order_progress_details.order_id')
            ->when($brandId, $this->obf($brandId))
            ->where('order_progress_details.status', 'on_progress')
            ->select('progress.nama_progress as label', DB::raw('COUNT(*) as count'))
            ->groupBy('progress.nama_progress', 'progress.urutan')
            ->orderBy('progress.urutan')
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'count' => (int) $r->count])
            ->all();
    }

    private function paymentStatusBreakdown(string|array|null $brandId): array
    {
        $sub = DB::table('orders')
            ->leftJoin('order_payments', 'order_payments.order_id', '=', 'orders.id')
            ->when($brandId, $this->obf($brandId))
            ->where('orders.status_po', '!=', 'draft')
            ->select('orders.id', 'orders.total_tagihan', DB::raw('COALESCE(SUM(order_payments.amount), 0) as paid'))
            ->groupBy('orders.id', 'orders.total_tagihan');

        $row = DB::table(DB::raw("({$sub->toSql()}) as o"))
            ->mergeBindings($sub)
            ->selectRaw('SUM(CASE WHEN paid >= total_tagihan THEN 1 ELSE 0 END) as lunas')
            ->selectRaw('SUM(CASE WHEN paid > 0 AND paid < total_tagihan THEN 1 ELSE 0 END) as partial')
            ->selectRaw('SUM(CASE WHEN paid = 0 THEN 1 ELSE 0 END) as belum_bayar')
            ->first();

        return [
            ['label' => 'Lunas',      'value' => (int) ($row->lunas      ?? 0)],
            ['label' => 'Partial',    'value' => (int) ($row->partial    ?? 0)],
            ['label' => 'Belum Bayar', 'value' => (int) ($row->belum_bayar ?? 0)],
        ];
    }

    public function trendBulanan(string|array|null $brandId, ?int $year = null): array
    {
        $year = $year ?: (int) now()->year;

        $isSqlite       = DB::connection()->getDriverName() === 'sqlite';
        $monthExpr      = $isSqlite ? 'CAST(strftime("%m", tanggal_masuk) AS INTEGER)'        : 'MONTH(tanggal_masuk)';
        $orderMonthExpr = $isSqlite ? 'CAST(strftime("%m", orders.tanggal_masuk) AS INTEGER)' : 'MONTH(orders.tanggal_masuk)';

        $orders = Order::query()
            ->when($brandId, $this->bf($brandId))
            ->whereYear('tanggal_masuk', $year)
            ->where('status_po', '!=', 'draft')
            ->select(
                DB::raw("$monthExpr as bulan"),
                DB::raw('COUNT(*) as total_po'),
                DB::raw('SUM(total_tagihan) as total_omset')
            )
            ->groupBy('bulan')
            ->get()->keyBy('bulan');

        $items = OrderItem::query()
            ->whereHas('order', function ($q) use ($brandId, $year) {
                $q->when($brandId, $this->bf($brandId))
                  ->whereYear('tanggal_masuk', $year)
                  ->where('status_po', '!=', 'draft');
            })
            ->select(
                DB::raw("$orderMonthExpr as bulan"),
                DB::raw('SUM(order_items.quantity) as total_pcs')
            )
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->groupBy('bulan')
            ->get()->keyBy('bulan');

        $targets = DB::table('brand_targets')
            ->when($brandId && $brandId !== 'all', function ($q) use ($brandId) {
                return is_array($brandId)
                    ? $q->whereIn('brand_id', $brandId)
                    : $q->where('brand_id', $brandId);
            })
            ->where('year', $year)
            ->select('month', DB::raw('SUM(target_revenue) as target_revenue'), DB::raw('SUM(target_pcs) as target_pcs'))
            ->groupBy('month')
            ->get()->keyBy('month');

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',    4 => 'April',
            5 => 'Mei',     6 => 'Juni',      7 => 'Juli',     8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $out = [];
        foreach ($months as $num => $name) {
            $o     = $orders->get($num);
            $item  = $items->get($num);
            $tgt   = $targets->get($num);
            $out[] = [
                'bulan_num'  => $num,
                'bulan'      => $name,
                'total_po'   => (int)   ($o    ? $o->total_po    : 0),
                'total_omset' => (float) ($o    ? $o->total_omset : 0),
                'total_pcs'  => (int)   ($item ? $item->total_pcs : 0),
                'target_revenue' => (float) ($tgt ? $tgt->target_revenue : 0),
                'target_pcs'  => (int)   ($tgt ? $tgt->target_pcs : 0),
            ];
        }
        return $out;
    }

    private function calculateRejectRate(array $brandIds): float
    {
        $totalRijek = Rijek::query()
            ->join('orders', 'orders.id', '=', 'rijeks.order_id')
            ->whereIn('orders.brand_id', $brandIds)
            ->sum('rijeks.jumlah');
        $totalProduksi = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.brand_id', $brandIds)
            ->where('orders.status_po', '!=', 'draft')
            ->sum('order_items.quantity');
        return $totalProduksi > 0 ? round(($totalRijek / $totalProduksi) * 100, 2) : 0;
    }

    private function getTargetProgress(array|string $brandIds): array
    {
        $brandIds = (array) $brandIds;
        if (empty($brandIds) || in_array('all', $brandIds)) {
            $brandIds = Brand::active()->pluck('id')->toArray();
        }
        $currentMonth = (int) now()->month;
        $currentYear  = (int) now()->year;

        $monthTarget = DB::table('brand_targets')
            ->whereIn('brand_id', $brandIds)
            ->where('year', $currentYear)
            ->where('month', $currentMonth)
            ->selectRaw('SUM(target_revenue) as revenue, SUM(target_pcs) as pcs')
            ->first();

        // Get actuals for the current month
        $monthActualRevenue = Order::query()
            ->whereIn('brand_id', $brandIds)
            ->whereYear('tanggal_masuk', $currentYear)
            ->whereMonth('tanggal_masuk', $currentMonth)
            ->where('status_po', '!=', 'draft')
            ->sum('total_tagihan');

        $monthActualPcs = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.brand_id', $brandIds)
            ->whereYear('orders.tanggal_masuk', $currentYear)
            ->whereMonth('orders.tanggal_masuk', $currentMonth)
            ->where('orders.status_po', '!=', 'draft')
            ->sum('order_items.quantity');

        $targetRevenue = (float) ($monthTarget?->revenue ?? 0);
        $targetPcs = (int) ($monthTarget?->pcs ?? 0);

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return [
            'month_name'         => $months[$currentMonth] ?? now()->format('F'),
            'target_revenue'     => $targetRevenue,
            'target_pcs'         => $targetPcs,
            'actual_revenue'     => (float) $monthActualRevenue,
            'actual_pcs'         => (int) $monthActualPcs,
            'revenue_percentage' => $targetRevenue > 0 ? (int) round(($monthActualRevenue / $targetRevenue) * 100) : 0,
            'pcs_percentage'     => $targetPcs > 0 ? (int) round(($monthActualPcs / $targetPcs) * 100) : 0,
        ];
    }
}
