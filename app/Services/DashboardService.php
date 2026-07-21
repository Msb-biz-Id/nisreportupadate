<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Master\Customer;
use App\Models\Order\DesignDeposit;
use App\Models\Order\Invoice;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\OrderPayment;
use App\Models\Order\Refund;
use App\Models\Order\Rijek;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

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
     * CACHED: Hasil disimpan 15 menit
     */
    private function expandToOperationalIds(array $brandIds): array
    {
        if (empty($brandIds)) {
            return [];
        }

        $key = 'brand_ops:' . implode(',', array_unique($brandIds));

        return Cache::remember($key, 900, function () use ($brandIds) {
            // Single query: get hub IDs and branch IDs in one go
            $result = Brand::query()
                ->select('id')
                ->where(function ($q) use ($brandIds) {
                    // Include original brand IDs
                    $q->whereIn('id', $brandIds)
                      // OR include branches whose parent is a hub in brandIds
                      ->orWhereHas('parentBrand', function ($pq) use ($brandIds) {
                          $pq->whereIn('id', $brandIds)
                            ->where('brand_type', Brand::TYPE_RESELLER_HUB);
                      });
                })
                ->pluck('id')
                ->toArray();

            return array_unique($result);
        });
    }

    // ----- public stats -----

    public function adminBrandStats(string|array|null $brandId, array $filters = []): array
    {
        return CacheService::rememberDashboard(
            'adminBrandStats',
            $brandId,
            function () use ($brandId, $filters) {
                $base = Order::query()->when($brandId, $this->bf($brandId));

                $today = (clone $base)->where('tanggal_masuk', today()->toDateString())->count();
                $week  = (clone $base)->whereBetween('tanggal_masuk', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])->count();
                $month = (clone $base)->whereBetween('tanggal_masuk', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->count();

                $totalProdukDiOrder = OrderItem::query()
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->when($brandId, $this->obf($brandId))
                    ->distinct('order_items.product_id')->count('order_items.product_id');

                $totalPelanggan = Customer::query()->when($brandId, $this->bf($brandId))->count();

                $totalWilayah = Customer::query()
                    ->when($brandId, $this->bf($brandId))
                    ->whereNotNull('kabupaten_code')->distinct('kabupaten_code')->count('kabupaten_code');

                $dpPendingCount = DesignDeposit::query()
                    ->when($brandId, $this->bf($brandId))
                    ->where('status', 'pending')
                    ->count();

                $refundPendingCount = Refund::query()
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
                    'po_type_distribution'          => $this->poTypeDistribution($brandId, $filters),
                    'status_breakdown'              => $this->statusBreakdown($brandId),
                    'progress_distribution'         => $this->progressDistribution($brandId),
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
                    'po_siap_dikirim'               => $this->poSiapDikirim($brandId, 10),
                    'dp_pending_list' => DesignDeposit::query()
                        ->when($brandId, $this->bf($brandId))
                        ->where('status', 'pending')
                        ->with(['customer:id,nama', 'brand:id,nama_brand,kode'])
                        ->orderByDesc('created_at')
                        ->limit(10)
                        ->get(),
                    'refund_pending_list' => Refund::query()
                        ->when($brandId, $this->bf($brandId))
                        ->where('status', 'pending_review')
                        ->with(['order:id,no_po', 'creator:id,name'])
                        ->orderByDesc('created_at')
                        ->limit(10)
                        ->get(),
                    'ringkasan_keuangan' => (function () use ($brandId) {
                        $bankAccounts = \App\Models\Master\BankAccount::query()
                            ->when($brandId, function ($q) use ($brandId) {
                                return is_array($brandId)
                                    ? $q->whereIn('brand_id', $brandId)
                                    : $q->where('brand_id', $brandId);
                            })
                            ->active()
                            ->get();

                        $bankIds = $bankAccounts->pluck('id')->toArray();
                        $verifiedSums = [];
                        $pendingSums = [];
                        $thisWeekPayments = collect();

                        if (!empty($bankIds)) {
                            $verifiedSums = OrderPayment::query()
                                ->whereIn('bank_id', $bankIds)
                                ->whereNotNull('verified_at')
                                ->select('bank_id', DB::raw('SUM(amount) as total'))
                                ->groupBy('bank_id')
                                ->pluck('total', 'bank_id')
                                ->toArray();

                            $pendingSums = OrderPayment::query()
                                ->whereIn('bank_id', $bankIds)
                                ->whereNull('verified_at')
                                ->select('bank_id', DB::raw('SUM(amount) as total'))
                                ->groupBy('bank_id')
                                ->pluck('total', 'bank_id')
                                ->toArray();

                            $startOfWeek = now()->startOfWeek();

                            $thisWeekPayments = OrderPayment::query()
                                ->whereIn('bank_id', $bankIds)
                                ->where('payment_date', '>=', $startOfWeek->toDateString())
                                ->select('bank_id',
                                    DB::raw('SUM(CASE WHEN verified_at IS NOT NULL THEN amount ELSE 0 END) as verified_week'),
                                    DB::raw('SUM(CASE WHEN verified_at IS NULL THEN amount ELSE 0 END) as pending_week')
                                )
                                ->groupBy('bank_id')
                                ->get()
                                ->keyBy('bank_id');
                        }

                        return $bankAccounts->map(function ($bank) use ($verifiedSums, $pendingSums, $thisWeekPayments) {
                            $verified = (float) ($verifiedSums[$bank->id] ?? 0.0);
                            $pending = (float) ($pendingSums[$bank->id] ?? 0.0);
                            $weekData = $thisWeekPayments->get($bank->id);
                            $verifiedWeek = $weekData ? (float) $weekData->verified_week : 0.0;
                            $pendingWeek = $weekData ? (float) $weekData->pending_week : 0.0;

                            return [
                                'id' => $bank->id,
                                'bank' => $bank->bank,
                                'atas_nama' => $bank->atas_nama,
                                'nomor_rekening' => $bank->nomor_rekening,
                                'total_verified' => $verified,
                                'total_pending' => $pending,
                                'total_all' => $verified + $pending,
                                'week_verified' => $verifiedWeek,
                                'week_pending' => $pendingWeek,
                                'week_total' => $verifiedWeek + $pendingWeek,
                            ];
                        })->values()->toArray();
                    })(),
                    'recent_payments' => (function () use ($brandId) {
                        $bankAccounts = \App\Models\Master\BankAccount::query()
                            ->when($brandId, function ($q) use ($brandId) {
                                return is_array($brandId)
                                    ? $q->whereIn('brand_id', $brandId)
                                    : $q->where('brand_id', $brandId);
                            })
                            ->active()
                            ->pluck('id')
                            ->toArray();

                        if (empty($bankAccounts)) {
                            return [];
                        }

                        return OrderPayment::query()
                            ->whereIn('bank_id', $bankAccounts)
                            ->with(['order:id,no_po,nama_po,pelanggan_id', 'order.pelanggan:id,nama', 'bank:id,bank,nomor_rekening'])
                            ->orderByDesc('payment_date')
                            ->orderByDesc('created_at')
                            ->limit(10)
                            ->get()
                            ->map(fn ($p) => [
                                'id' => $p->id,
                                'no_po' => $p->order?->no_po,
                                'nama_po' => $p->order?->nama_po,
                                'pelanggan' => $p->order?->pelanggan?->nama ?? '-',
                                'amount' => (float) $p->amount,
                                'payment_date' => $p->payment_date?->toDateString(),
                                'bank' => $p->bank?->bank,
                                'nomor_rekening' => $p->bank?->nomor_rekening,
                                'verified' => !empty($p->verified_at),
                                'notes' => $p->notes,
                            ])
                            ->all();
                    })(),
                ];
            },
            CacheService::TTL_SHORT,
            $filters
        );
    }

    public function adminProduksiStats(string|array|null $brandId): array
    {
        return CacheService::rememberDashboard(
            'adminProduksiStats',
            $brandId,
            function () use ($brandId) {
                $base = Order::query()->when($brandId, $this->bf($brandId));

                $dalamProses  = (clone $base)->where('status_po', 'on_progress')->count();
                $selesaiToday = (clone $base)->where('status_po', 'selesai_produksi')
                    ->whereBetween('updated_at', [today()->startOfDay(), today()->endOfDay()])->count();
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
                    ->where('order_items.is_addon', false)
                    ->where('orders.status_po', '!=', 'draft')
                    ->sum(DB::raw("CASE WHEN order_items.jml_atasan IS NOT NULL AND order_items.jml_atasan != '' THEN CAST(order_items.jml_atasan AS UNSIGNED) ELSE order_items.quantity END"));
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
            },
            CacheService::TTL_SHORT
        );
    }

    public function superadminStats(string|array|null $brandId = null): array
    {
        return CacheService::rememberDashboard(
            'superadminStats',
            $brandId,
            function () use ($brandId) {
                $totalBrandAktif = Brand::active()->count();
                $totalUser       = User::count();
                
                $baseOrder = Order::query()->when($brandId && $brandId !== 'all', $this->bf($brandId));
                $totalOrder      = (clone $baseOrder)->count();
                $totalRevenue    = (clone $baseOrder)->sum('total_tagihan');

                $ordersSummary = Order::query()
                    ->when($brandId && $brandId !== 'all', $this->bf($brandId))
                    ->select('brand_id', DB::raw('COUNT(id) as total'), DB::raw('SUM(total_tagihan) as revenue'))
                    ->groupBy('brand_id')
                    ->with('brand:id,nama_brand,kode,warna_primary')
                    ->get();

                $pcsByBrand = OrderItem::query()
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->where('order_items.is_addon', false)
                    ->when($brandId && $brandId !== 'all', $this->obf($brandId))
                    ->select('orders.brand_id', DB::raw('SUM(COALESCE(NULLIF(order_items.jml_atasan, ""), order_items.quantity)) as total_pcs'))
                    ->groupBy('orders.brand_id')
                    ->pluck('total_pcs', 'orders.brand_id')
                    ->toArray();

                $perBrand = $ordersSummary->map(fn ($r) => [
                    'brand'     => $r->brand?->nama_brand ?? '-',
                    'kode'      => $r->brand?->kode,
                    'warna'     => $r->brand?->warna_primary,
                    'total'     => (int) $r->total,
                    'revenue'   => (float) $r->revenue,
                    'total_pcs' => (int) ($pcsByBrand[$r->brand_id] ?? 0),
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
                    'progress_distribution' => $this->progressDistribution($brandId),
                    'trend_harian'      => $this->trendHarian($brandId, 14),
                    'po_terbaru'        => $this->poTerbaru($brandId, 10),
                    'trend_bulanan'     => $this->trendBulanan($brandId),
                    'target_progress'   => $this->getTargetProgress($brandId ?: Brand::active()->pluck('id')->toArray()),
                    'po_siap_dikirim'   => $this->poSiapDikirim($brandId, 10),
                ];
            },
            CacheService::TTL_SHORT
        );
    }

    public function ownerStats(User $user, ?string $filterBrand): array
    {
        return CacheService::rememberDashboard(
            'ownerStats',
            $filterBrand,
            function () use ($user, $filterBrand) {
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
                )->whereNotNull('verified_at')->sum('amount');
                $rejectRate = $this->calculateRejectRate($opBrandIds);

                return [
                    'cards' => [
                        ['label' => 'Total PO',             'value' => $totalPo,                  'icon' => 'Package',       'accent' => 'blue'],
                        ['label' => 'Total Revenue',         'value' => $totalRevenue, 'currency' => true, 'icon' => 'TrendingUp',    'accent' => 'emerald'],
                        ['label' => 'Outstanding Payment',   'value' => max(0, $outstanding), 'currency' => true, 'icon' => 'CreditCard',    'accent' => 'orange'],
                        ['label' => 'Rijek Rate',            'value' => $rejectRate.'%',           'icon' => 'AlertTriangle', 'accent' => 'red'],
                    ],
                    'owned_brands' => Brand::whereIn('id', $ownedBrandIds)->get(['id', 'nama_brand', 'kode', 'warna_primary']),
            'brand_performance' => (function() use ($opBrandIds) {
                $ordersSummary = Order::query()
                    ->whereIn('orders.brand_id', $opBrandIds)
                    ->select('orders.brand_id', DB::raw('COUNT(orders.id) as total'), DB::raw('SUM(orders.total_tagihan) as revenue'))
                    ->groupBy('orders.brand_id')
                    ->with('brand:id,nama_brand,kode,warna_primary')
                    ->get();

                $pcsByBrand = OrderItem::query()
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->where('order_items.is_addon', false)
                    ->whereIn('orders.brand_id', $opBrandIds)
                    ->select('orders.brand_id', DB::raw('SUM(COALESCE(NULLIF(order_items.jml_atasan, ""), order_items.quantity)) as total_pcs'))
                    ->groupBy('orders.brand_id')
                    ->pluck('total_pcs', 'orders.brand_id')
                    ->toArray();

                return $ordersSummary->map(fn ($r) => [
                    'brand'     => $r->brand?->nama_brand,
                    'kode'      => $r->brand?->kode,
                    'warna'     => $r->brand?->warna_primary,
                    'total'     => (int) $r->total,
                    'revenue'   => (float) $r->revenue,
                    'total_pcs' => (int) ($pcsByBrand[$r->brand_id] ?? 0),
                ]);
            })(),
            'status_breakdown'              => $this->statusBreakdown($opBrandIds),
            'progress_distribution'         => $this->progressDistribution($opBrandIds),
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
            'po_siap_dikirim'               => $this->poSiapDikirim($opBrandIds, 10),
            'current_brand_id'              => $filterBrand ?: 'all',
        ];
            },
            CacheService::TTL_SHORT
        );
    }

    public function financeStats(string|array|null $brandId): array
    {
        return CacheService::rememberDashboard(
            'financeStats',
            $brandId,
            function () use ($brandId) {
                $invoices = Invoice::query()->when($brandId && $brandId !== 'all', $this->bf($brandId));
                $refunds  = Refund::query()->when($brandId && $brandId !== 'all', $this->bf($brandId));

                $invoicePending      = (clone $invoices)->whereIn('status', ['draft', 'validated'])->count();
                $invoiceToday        = (clone $invoices)->where('tanggal_terbit', today()->toDateString())->count();
                $totalTagihanPending = (clone $invoices)->whereIn('status', ['draft', 'validated', 'published'])->sum('sisa_pembayaran');

                $paidToday = OrderPayment::query()
                    ->where('payment_date', today()->toDateString())
                    ->when($brandId && $brandId !== 'all', fn ($q) => $q->whereHas('order', $this->bf($brandId)))
                    ->sum('amount');

        $refundPending        = (clone $refunds)->where('status', 'pending_review')->count();
        $refundPublished      = (clone $refunds)->where('status', 'published');
        $refundPublishedCount  = (clone $refundPublished)->count();
        $refundPublishedAmount = (clone $refundPublished)->sum('nominal_refund');

        // Total payments verified (debit payments only)
        $totalPayments = OrderPayment::query()
            ->when($brandId && $brandId !== 'all', fn ($q) => $q->whereHas('order', $this->bf($brandId)))
            ->whereNotNull('verified_at')
            ->where('is_debit', true)
            ->sum('amount');

        // Total refunds/credits verified (credit payments only)
        $totalRefunds = OrderPayment::query()
            ->when($brandId && $brandId !== 'all', fn ($q) => $q->whereHas('order', $this->bf($brandId)))
            ->whereNotNull('verified_at')
            ->where('is_debit', false)
            ->sum('amount');

        $outstandingTotal = Order::query()
            ->when($brandId && $brandId !== 'all', $this->bf($brandId))
            ->where('status_po', '!=', 'draft')
            ->sum('total_tagihan')
            - ($totalPayments - $totalRefunds);

        $dpPendingCount = DesignDeposit::query()
            ->when($brandId && $brandId !== 'all', $this->bf($brandId))
            ->where('status', 'pending')
            ->count();

        $dpVerifiedAmount = DesignDeposit::query()
            ->when($brandId && $brandId !== 'all', $this->bf($brandId))
            ->where('status', 'verified')
            ->sum('amount');

        $bankAccountsSummary = \App\Models\Master\BankAccount::query()
            ->when($brandId && $brandId !== 'all', $this->bf($brandId))
            ->with('brand:id,nama_brand,kode')
            ->get();

        $bankIds = $bankAccountsSummary->pluck('id')->toArray();

        // 1. Ambil total received per bank account via single query GROUP BY
        $verifiedPaymentsByBank = [];
        if (!empty($bankIds)) {
            $verifiedPaymentsByBank = OrderPayment::query()
                ->whereIn('bank_id', $bankIds)
                ->whereNotNull('verified_at')
                ->when($brandId && $brandId !== 'all', fn ($q) => $q->whereHas('order', $this->bf($brandId)))
                ->select('bank_id', DB::raw('SUM(amount) as total'))
                ->groupBy('bank_id')
                ->pluck('total', 'bank_id')
                ->toArray();
        }

        // 2. Ambil recent transactions untuk bank account yang bersangkutan via single query
        $recentPayments = collect();
        if (!empty($bankIds)) {
            $recentPayments = OrderPayment::query()
                ->whereIn('bank_id', $bankIds)
                ->when($brandId && $brandId !== 'all', fn ($q) => $q->whereHas('order', $this->bf($brandId)))
                ->with(['order:id,no_po,nama_po,pelanggan_id', 'order.pelanggan:id,nama', 'masterJenisPembayaran:id,nama'])
                ->orderByDesc('payment_date')
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('bank_id');
        }

        $bankAccountsSummary = $bankAccountsSummary->map(function ($bank) use ($verifiedPaymentsByBank, $recentPayments) {
            $totalReceived = $verifiedPaymentsByBank[$bank->id] ?? 0.0;
            
            $recentTransactions = collect($recentPayments->get($bank->id) ?? [])
                ->take(15)
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

        $brandsForReport = Brand::active()
            ->when($brandId && $brandId !== 'all', fn ($q) => is_array($brandId) ? $q->whereIn('id', $brandId) : $q->where('id', $brandId))
            ->get();

        $brandIdsForReport = $brandsForReport->pluck('id')->toArray();

        // 1. Total revenue per brand (only published POs)
        $brandRevenues = [];
        if (!empty($brandIdsForReport)) {
            $brandRevenues = Order::whereIn('brand_id', $brandIdsForReport)
                ->where('status_po', '!=', 'draft')
                ->select('brand_id', DB::raw('SUM(total_tagihan) as total'))
                ->groupBy('brand_id')
                ->pluck('total', 'brand_id')
                ->toArray();
        }

        // 2. Total received payments per brand (verified only)
        $brandPayments = [];
        if (!empty($brandIdsForReport)) {
            $brandPayments = OrderPayment::query()
                ->whereNotNull('order_payments.verified_at')
                ->join('orders', 'orders.id', '=', 'order_payments.order_id')
                ->whereIn('orders.brand_id', $brandIdsForReport)
                ->select('orders.brand_id', DB::raw('SUM(CASE WHEN order_payments.is_debit = 1 THEN order_payments.amount ELSE 0 END) as total'))
                ->groupBy('orders.brand_id')
                ->pluck('total', 'orders.brand_id')
                ->toArray();
        }

        // 3. Total refunds per brand (published only)
        $brandRefunds = [];
        if (!empty($brandIdsForReport)) {
            $brandRefunds = OrderPayment::query()
                ->whereNotNull('order_payments.verified_at')
                ->join('orders', 'orders.id', '=', 'order_payments.order_id')
                ->whereIn('orders.brand_id', $brandIdsForReport)
                ->select('orders.brand_id', DB::raw('SUM(CASE WHEN order_payments.is_debit = 0 THEN order_payments.amount ELSE 0 END) as total'))
                ->groupBy('orders.brand_id')
                ->pluck('total', 'orders.brand_id')
                ->toArray();
        }

        // 4. Payments by payment types for each brand
        $brandPaymentTypesRaw = collect();
        if (!empty($brandIdsForReport)) {
            $brandPaymentTypesRaw = DB::table('order_payments')
                ->join('orders', 'orders.id', '=', 'order_payments.order_id')
                ->leftJoin('master_jenis_pembayarans', 'master_jenis_pembayarans.id', '=', 'order_payments.master_jenis_pembayaran_id')
                ->whereIn('orders.brand_id', $brandIdsForReport)
                ->select('orders.brand_id', 'master_jenis_pembayarans.nama', DB::raw('SUM(order_payments.amount) as total'))
                ->groupBy('orders.brand_id', 'master_jenis_pembayarans.nama')
                ->get()
                ->groupBy('brand_id');
        }

        $brandFinancialReports = $brandsForReport
            ->map(function ($brand) use ($brandRevenues, $brandPayments, $brandRefunds, $brandPaymentTypesRaw) {
                $totalRevenue = $brandRevenues[$brand->id] ?? 0.0;
                $totalPayments = $brandPayments[$brand->id] ?? 0.0;
                $totalRefunds = $brandRefunds[$brand->id] ?? 0.0;

                // Net payments received
                $netPayments = $totalPayments - $totalRefunds;

                // Total outstanding
                $outstanding = max(0, $totalRevenue - $netPayments);

                // Payments by payment types for this brand
                $rawTypes = $brandPaymentTypesRaw->get($brand->id, collect());
                $paymentTypeBreakdown = $rawTypes->map(fn ($r) => [
                    'nama' => $r->nama ?? 'Lainnya',
                    'total' => (float) $r->total,
                ])->values()->all();

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

        $allActiveBrands = Brand::active()->get(['id', 'nama_brand', 'kode']);

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
            'dp_pending_list' => DesignDeposit::query()
                ->when($brandId && $brandId !== 'all', $this->bf($brandId))
                ->where('status', 'pending')
                ->with(['customer:id,nama', 'brand:id,nama_brand,kode'])
                ->orderByDesc('created_at')->limit(10)->get(),
            'payment_status' => $this->paymentStatusBreakdown($brandId),
            'bank_accounts_summary' => $bankAccountsSummary,
            'brand_financial_reports' => $brandFinancialReports,
            'brands' => $allActiveBrands,
            'progress_distribution' => $this->progressDistribution($brandId),
            'current_brand_id' => $brandId ?: 'all',
        ];
            },
            CacheService::TTL_SHORT
        );
    }

    // ----- private helpers -----

    private function statusBreakdown(string|array|null $brandId): array
    {
        return CacheService::rememberDashboard(
            'statusBreakdown',
            $brandId,
            function () use ($brandId) {
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
            },
            CacheService::TTL_SHORT
        );
    }

    private function trendHarian(string|array|null $brandId, int $days): array
    {
        return CacheService::rememberDashboard(
            'trendHarian',
            $brandId,
            function () use ($brandId, $days) {
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
            },
            CacheService::TTL_SHORT
        );
    }

    private function produkTerpopuler(string|array|null $brandId, int $limit): array
    {
        return CacheService::rememberDashboard(
            'produkTerpopuler',
            $brandId,
            function () use ($brandId, $limit) {
                return OrderItem::query()
                    ->when($brandId, fn ($q) => $q->whereHas('order', $this->bf($brandId)))
                    ->where('order_items.is_addon', false)
                    ->select('nama_produk', DB::raw("SUM(CASE WHEN order_items.jml_atasan IS NOT NULL AND order_items.jml_atasan != '' THEN CAST(order_items.jml_atasan AS UNSIGNED) ELSE order_items.quantity END) as total_qty"), DB::raw('COUNT(DISTINCT order_id) as total_order'))
                    ->groupBy('nama_produk')
                    ->orderByDesc('total_qty')
                    ->limit($limit)
                    ->get()
                    ->map(fn ($r) => ['nama' => $r->nama_produk, 'total_qty' => (int) $r->total_qty, 'total_order' => (int) $r->total_order])
                    ->all();
            },
            CacheService::TTL_MEDIUM
        );
    }

    private function kategoriDistribusi(string|array|null $brandId): array
    {
        return CacheService::rememberDashboard(
            'kategoriDistribusi',
            $brandId,
            function () use ($brandId) {
                return Order::query()
                    ->when($brandId, $this->bf($brandId))
                    ->whereNotNull('kategori_order_id')
                    ->with('kategoriOrder:id,nama')
                    ->select('kategori_order_id', DB::raw('COUNT(*) as cnt'))
                    ->groupBy('kategori_order_id')
                    ->get()
                    ->map(fn ($r) => ['label' => $r->kategoriOrder?->nama ?? '-', 'count' => (int) $r->cnt])
                    ->all();
            },
            CacheService::TTL_MEDIUM
        );
    }

    private function sumberDistribusi(string|array|null $brandId): array
    {
        return CacheService::rememberDashboard(
            'sumberDistribusi',
            $brandId,
            function () use ($brandId) {
                return Order::query()
                    ->when($brandId, $this->bf($brandId))
                    ->whereNotNull('sumber_order_id')
                    ->with('sumberOrder.parent')
                    ->select('sumber_order_id', DB::raw('COUNT(*) as cnt'))
                    ->groupBy('sumber_order_id')
                    ->get()
                    ->map(function ($r) {
                        $sumber = $r->sumberOrder;
                        $label = '-';
                        if ($sumber) {
                            $label = $sumber->parent ? "{$sumber->parent->nama} — {$sumber->nama}" : $sumber->nama;
                        }
                        return ['label' => $label, 'count' => (int) $r->cnt];
                    })
                    ->all();
            },
            CacheService::TTL_MEDIUM
        );
    }

    private function kategoriPelangganDistribusi(string|array|null $brandId): array
    {
        return CacheService::rememberDashboard(
            'kategoriPelangganDistribusi',
            $brandId,
            function () use ($brandId) {
                return Order::query()
                    ->when($brandId, $this->obf($brandId))
                    ->join('customers', 'customers.id', '=', 'orders.pelanggan_id')
                    ->leftJoin('customer_types', 'customer_types.id', '=', 'customers.type_pelanggan_id')
                    ->select(DB::raw('COALESCE(customer_types.nama, "Tanpa Kategori") as label'), DB::raw('COUNT(*) as cnt'))
                    ->groupBy('label')
                    ->get()
                    ->map(fn ($r) => ['label' => $r->label, 'count' => (int) $r->cnt])
                    ->all();
            },
            CacheService::TTL_MEDIUM
        );
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
            ->whereBetween('tanggal_masuk', ["{$year}-01-01 00:00:00", "{$year}-12-31 23:59:59"])
            ->where('status_po', '!=', 'draft')
            ->select(
                DB::raw("$monthExpr as bulan"),
                DB::raw('COUNT(*) as total_po'),
                DB::raw('SUM(total_tagihan) as total_omset')
            )
            ->groupBy('bulan')
            ->get()->keyBy('bulan');

        $items = OrderItem::query()
            ->where('order_items.is_addon', false)
            ->whereHas('order', function ($q) use ($brandId, $year) {
                $q->when($brandId, $this->bf($brandId))
                  ->whereBetween('tanggal_masuk', ["{$year}-01-01 00:00:00", "{$year}-12-31 23:59:59"])
                  ->where('status_po', '!=', 'draft');
            })
            ->select(
                DB::raw("$orderMonthExpr as bulan"),
                DB::raw("SUM(CASE WHEN order_items.jml_atasan IS NOT NULL AND order_items.jml_atasan != '' THEN CAST(order_items.jml_atasan AS UNSIGNED) ELSE order_items.quantity END) as total_pcs")
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
            ->where('order_items.is_addon', false)
            ->whereIn('orders.brand_id', $brandIds)
            ->where('orders.status_po', '!=', 'draft')
            ->sum(DB::raw("CASE WHEN order_items.jml_atasan IS NOT NULL AND order_items.jml_atasan != '' THEN CAST(order_items.jml_atasan AS UNSIGNED) ELSE order_items.quantity END"));
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

        $startOfMonth = now()->startOfMonth()->toDateTimeString();
        $endOfMonth = now()->endOfMonth()->toDateTimeString();

        // Get actuals for the current month
        $monthActualRevenue = Order::query()
            ->whereIn('brand_id', $brandIds)
            ->whereBetween('tanggal_masuk', [$startOfMonth, $endOfMonth])
            ->where('status_po', '!=', 'draft')
            ->sum('total_tagihan');

        $monthActualPcs = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.is_addon', false)
            ->whereIn('orders.brand_id', $brandIds)
            ->whereBetween('orders.tanggal_masuk', [$startOfMonth, $endOfMonth])
            ->where('orders.status_po', '!=', 'draft')
            ->sum(DB::raw("CASE WHEN order_items.jml_atasan IS NOT NULL AND order_items.jml_atasan != '' THEN CAST(order_items.jml_atasan AS UNSIGNED) ELSE order_items.quantity END"));

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

    private function poSiapDikirim(string|array|null $brandId, int $limit): array
    {
        return Order::query()
            ->when($brandId, $this->bf($brandId))
            ->where('status_po', 'siap_dikirim')
            ->with(['pelanggan:id,nama,nomor_hp', 'brand:id,nama_brand,kode'])
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get()
            ->map(function (Order $o) {
                $totalTagihan = $o->totalTagihan();
                $totalPaid = $o->totalPaid();
                $sisa = max(0, $totalTagihan - $totalPaid);
                $isLunas = $sisa <= 0 && $totalTagihan > 0;
                
                return [
                    'id'            => $o->id,
                    'no_po'         => $o->no_po,
                    'nama_po'       => $o->nama_po,
                    'pelanggan'     => $o->pelanggan?->nama,
                    'pelanggan_hp'  => $o->pelanggan?->nomor_hp,
                    'brand'         => $o->brand?->kode,
                    'total_tagihan' => $totalTagihan,
                    'total_paid'    => $totalPaid,
                    'sisa_tagihan'  => $sisa,
                    'is_lunas'      => $isLunas,
                    'status'        => $o->status_po,
                ];
            })
            ->all();
    }

    public function poTypeDistribution(string|array|null $brandId, array $filters): array
    {
        $filterType = $filters['date_filter'] ?? 'bulanan';
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        [$startDate, $endDate] = $this->resolveDateRange($filterType, $from, $to);

        $stats = Order::query()
            ->when($brandId, $this->bf($brandId))
            ->whereBetween('tanggal_masuk', [$startDate, $endDate])
            ->where('status_po', '!=', 'draft')
            ->select(
                DB::raw('COUNT(CASE WHEN is_special_order = 0 AND is_reseller_price = 0 THEN 1 END) as normal_count'),
                DB::raw('COUNT(CASE WHEN is_special_order = 1 THEN 1 END) as special_count'),
                DB::raw('COUNT(CASE WHEN is_reseller_price = 1 THEN 1 END) as reseller_count'),
                DB::raw('COALESCE(SUM(CASE WHEN is_special_order = 0 AND is_reseller_price = 0 THEN total_tagihan ELSE 0 END), 0) as normal_value'),
                DB::raw('COALESCE(SUM(CASE WHEN is_special_order = 1 THEN total_tagihan ELSE 0 END), 0) as special_value'),
                DB::raw('COALESCE(SUM(CASE WHEN is_reseller_price = 1 THEN total_tagihan ELSE 0 END), 0) as reseller_value')
            )
            ->first();

        return [
            'normal' => [
                'count' => (int) ($stats->normal_count ?? 0),
                'value' => (float) ($stats->normal_value ?? 0),
            ],
            'special' => [
                'count' => (int) ($stats->special_count ?? 0),
                'value' => (float) ($stats->special_value ?? 0),
            ],
            'reseller' => [
                'count' => (int) ($stats->reseller_count ?? 0),
                'value' => (float) ($stats->reseller_value ?? 0),
            ],
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ]
        ];
    }

    private function resolveDateRange(string $filterType, ?string $from = null, ?string $to = null): array
    {
        $now = now();
        switch ($filterType) {
            case 'harian':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;
            case 'mingguan':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfWeek();
                break;
            case 'bulanan':
            default:
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                break;
            case 'custom':
                $startDate = $from ? \Illuminate\Support\Carbon::parse($from)->startOfDay() : $now->copy()->startOfMonth();
                $endDate = $to ? \Illuminate\Support\Carbon::parse($to)->endOfDay() : $now->copy()->endOfDay();
                break;
        }
        return [$startDate, $endDate];
    }
}
