<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Master\Customer;
use App\Models\Master\Product;
use App\Models\Order\Invoice;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\OrderPayment;
use App\Models\Order\Refund;
use App\Models\Order\Rijek;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function adminBrandStats(?string $brandId): array
    {
        $base = Order::query()->when($brandId, fn ($q) => $q->where('brand_id', $brandId));

        $today = (clone $base)->whereDate('tanggal_masuk', today())->count();
        $week = (clone $base)->whereBetween('tanggal_masuk', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $month = (clone $base)->whereMonth('tanggal_masuk', now()->month)->whereYear('tanggal_masuk', now()->year)->count();

        $totalProdukDiOrder = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->when($brandId, fn ($q) => $q->where('orders.brand_id', $brandId))
            ->distinct('order_items.product_id')->count('order_items.product_id');

        $totalPelanggan = Customer::query()->when($brandId, fn ($q) => $q->where('brand_id', $brandId))->count();

        $totalWilayah = Customer::query()
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
            ->whereNotNull('kabupaten_code')->distinct('kabupaten_code')->count('kabupaten_code');

        return [
            'cards' => [
                ['label' => 'Order Hari Ini', 'value' => $today, 'icon' => 'Calendar', 'accent' => 'blue'],
                ['label' => 'Order Minggu Ini', 'value' => $week, 'icon' => 'CalendarRange', 'accent' => 'emerald'],
                ['label' => 'Order Bulan Ini', 'value' => $month, 'icon' => 'CalendarDays', 'accent' => 'violet'],
                ['label' => 'Produk Di-order', 'value' => $totalProdukDiOrder, 'icon' => 'Package', 'accent' => 'amber'],
                ['label' => 'Total Pelanggan', 'value' => $totalPelanggan, 'icon' => 'Users', 'accent' => 'pink'],
                ['label' => 'Wilayah Tercakup', 'value' => $totalWilayah, 'icon' => 'MapPin', 'accent' => 'cyan'],
            ],
            'status_breakdown' => $this->statusBreakdown($brandId),
            'trend_harian' => $this->trendHarian($brandId, 14),
            'produk_terpopuler' => $this->produkTerpopuler($brandId, 10),
            'kategori_distribusi' => $this->kategoriDistribusi($brandId),
            'sumber_distribusi' => $this->sumberDistribusi($brandId),
            'wilayah_top' => $this->wilayahTop($brandId, 8),
            'top_pelanggan' => $this->topPelanggan($brandId, 5),
            'po_terbaru' => $this->poTerbaru($brandId, 10),
            'deadline_mendekat' => $this->deadlineMendekat($brandId, 5),
            'po_terlambat' => $this->poTerlambat($brandId, 5),
        ];
    }

    public function adminProduksiStats(?string $brandId): array
    {
        $base = Order::query()->when($brandId, fn ($q) => $q->where('brand_id', $brandId));

        $dalamProses = (clone $base)->where('status_po', 'on_progress')->count();
        $selesaiToday = (clone $base)->where('status_po', 'selesai_produksi')
            ->whereDate('updated_at', today())->count();
        $deadlineDekat = (clone $base)
            ->whereIn('status_po', ['published', 'on_progress'])
            ->whereBetween('deadline_customer', [now(), now()->addDays(7)])
            ->count();
        $totalRijek = Rijek::query()
            ->join('orders', 'orders.id', '=', 'rijeks.order_id')
            ->when($brandId, fn ($q) => $q->where('orders.brand_id', $brandId))
            ->sum('rijeks.jumlah');
        $totalProduksi = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->when($brandId, fn ($q) => $q->where('orders.brand_id', $brandId))
            ->where('orders.status_po', '!=', 'draft')
            ->sum('order_items.quantity');
        $rijekRate = $totalProduksi > 0 ? round(($totalRijek / $totalProduksi) * 100, 2) : 0;

        return [
            'cards' => [
                ['label' => 'Order Dalam Proses', 'value' => $dalamProses, 'icon' => 'Settings2', 'accent' => 'amber'],
                ['label' => 'Selesai Hari Ini', 'value' => $selesaiToday, 'icon' => 'CheckCircle2', 'accent' => 'emerald'],
                ['label' => 'Deadline ≤ 7 Hari', 'value' => $deadlineDekat, 'icon' => 'AlarmClock', 'accent' => 'orange'],
                ['label' => 'Rijek Rate', 'value' => $rijekRate . '%', 'icon' => 'AlertTriangle', 'accent' => 'red'],
            ],
            'status_breakdown' => $this->statusBreakdown($brandId),
            'rijek_by_jenis' => $this->rijekByJenis($brandId),
            'rijek_by_tingkat' => $this->rijekByTingkat($brandId),
            'progress_distribution' => $this->progressDistribution($brandId),
            'deadline_mendekat' => $this->deadlineMendekat($brandId, 10),
            'po_terlambat' => $this->poTerlambat($brandId, 10),
        ];
    }

    public function superadminStats(): array
    {
        $totalBrand = Brand::count();
        $totalBrandAktif = Brand::active()->count();
        $totalUser = User::count();
        $totalOrder = Order::count();
        $totalRevenue = Order::sum('total_tagihan');

        $perBrand = Order::query()
            ->select('brand_id', DB::raw('COUNT(*) as total'), DB::raw('SUM(total_tagihan) as revenue'))
            ->groupBy('brand_id')
            ->with('brand:id,nama_brand,kode,warna_primary')
            ->get()
            ->map(fn ($r) => [
                'brand' => $r->brand?->nama_brand ?? '-',
                'kode' => $r->brand?->kode,
                'warna' => $r->brand?->warna_primary,
                'total' => (int) $r->total,
                'revenue' => (float) $r->revenue,
            ]);

        return [
            'cards' => [
                ['label' => 'Total Brand Aktif', 'value' => $totalBrandAktif, 'icon' => 'Building2', 'accent' => 'blue'],
                ['label' => 'Total User', 'value' => $totalUser, 'icon' => 'Users', 'accent' => 'emerald'],
                ['label' => 'Total Order (Semua)', 'value' => $totalOrder, 'icon' => 'Package', 'accent' => 'violet'],
                ['label' => 'Total Revenue', 'value' => $totalRevenue, 'currency' => true, 'icon' => 'TrendingUp', 'accent' => 'amber'],
            ],
            'brand_performance' => $perBrand,
            'status_breakdown' => $this->statusBreakdown(null),
            'trend_harian' => $this->trendHarian(null, 14),
            'po_terbaru' => $this->poTerbaru(null, 10),
        ];
    }

    public function ownerStats(User $user, ?string $filterBrand): array
    {
        $ownedBrandIds = $user->brands()->pluck('brands.id');
        $brandIds = $filterBrand ? [$filterBrand] : $ownedBrandIds->all();

        $base = Order::query()->whereIn('brand_id', $brandIds);

        $totalRevenue = (clone $base)->sum('total_tagihan');
        $totalPo = (clone $base)->count();
        $outstanding = (clone $base)->sum('total_tagihan') - OrderPayment::whereHas('order', fn ($q) => $q->whereIn('brand_id', $brandIds))->sum('amount');
        $rejectRate = $this->calculateRejectRate($brandIds);

        return [
            'cards' => [
                ['label' => 'Total PO', 'value' => $totalPo, 'icon' => 'Package', 'accent' => 'blue'],
                ['label' => 'Total Revenue', 'value' => $totalRevenue, 'currency' => true, 'icon' => 'TrendingUp', 'accent' => 'emerald'],
                ['label' => 'Outstanding Payment', 'value' => max(0, $outstanding), 'currency' => true, 'icon' => 'CreditCard', 'accent' => 'orange'],
                ['label' => 'Rijek Rate', 'value' => $rejectRate . '%', 'icon' => 'AlertTriangle', 'accent' => 'red'],
            ],
            'owned_brands' => Brand::whereIn('id', $ownedBrandIds)->get(['id', 'nama_brand', 'kode', 'warna_primary']),
            'brand_performance' => Order::query()
                ->whereIn('brand_id', $ownedBrandIds)
                ->select('brand_id', DB::raw('COUNT(*) as total'), DB::raw('SUM(total_tagihan) as revenue'))
                ->groupBy('brand_id')
                ->with('brand:id,nama_brand,kode,warna_primary')
                ->get()
                ->map(fn ($r) => [
                    'brand' => $r->brand?->nama_brand,
                    'kode' => $r->brand?->kode,
                    'warna' => $r->brand?->warna_primary,
                    'total' => (int) $r->total,
                    'revenue' => (float) $r->revenue,
                ]),
            'status_breakdown' => $this->statusBreakdownForBrands($brandIds),
            'trend_harian' => $this->trendHarianForBrands($brandIds, 14),
        ];
    }

    public function financeStats(?string $brandId): array
    {
        $invoices = Invoice::query()->when($brandId, fn ($q) => $q->where('brand_id', $brandId));
        $refunds = Refund::query()->when($brandId, fn ($q) => $q->where('brand_id', $brandId));

        $invoicePending = (clone $invoices)->whereIn('status', ['draft', 'validated'])->count();
        $invoiceToday = (clone $invoices)->whereDate('tanggal_terbit', today())->count();
        $totalTagihanPending = (clone $invoices)->whereIn('status', ['draft', 'validated', 'published'])->sum('sisa_pembayaran');
        $paidToday = OrderPayment::query()
            ->whereDate('payment_date', today())
            ->when($brandId, fn ($q) => $q->whereHas('order', fn ($x) => $x->where('brand_id', $brandId)))
            ->sum('amount');

        $refundPending = (clone $refunds)->where('status', 'pending_review')->count();
        $refundPublished = (clone $refunds)->where('status', 'published');
        $refundPublishedCount = (clone $refundPublished)->count();
        $refundPublishedAmount = (clone $refundPublished)->sum('nominal_refund');

        $outstandingTotal = Order::query()
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
            ->where('status_po', '!=', 'draft')
            ->sum('total_tagihan')
            - OrderPayment::query()
                ->when($brandId, fn ($q) => $q->whereHas('order', fn ($x) => $x->where('brand_id', $brandId)))
                ->sum('amount');

        return [
            'cards' => [
                ['label' => 'Invoice Pending', 'value' => $invoicePending, 'icon' => 'FileText', 'accent' => 'amber'],
                ['label' => 'Invoice Hari Ini', 'value' => $invoiceToday, 'icon' => 'FileCheck', 'accent' => 'emerald'],
                ['label' => 'Tagihan Pending', 'value' => $totalTagihanPending, 'currency' => true, 'icon' => 'CreditCard', 'accent' => 'blue'],
                ['label' => 'Pembayaran Hari Ini', 'value' => $paidToday, 'currency' => true, 'icon' => 'TrendingUp', 'accent' => 'emerald'],
                ['label' => 'Outstanding Total', 'value' => max(0, $outstandingTotal), 'currency' => true, 'icon' => 'AlertCircle', 'accent' => 'red'],
                ['label' => 'Refund Pending', 'value' => $refundPending, 'icon' => 'RotateCcw', 'accent' => 'orange'],
                ['label' => 'Refund Diterbitkan', 'value' => $refundPublishedCount, 'icon' => 'CheckCircle2', 'accent' => 'violet'],
                ['label' => 'Total Refund', 'value' => $refundPublishedAmount, 'currency' => true, 'icon' => 'TrendingDown', 'accent' => 'pink'],
            ],
            'invoice_pending_list' => (clone $invoices)
                ->whereIn('status', ['draft', 'validated'])
                ->with(['order:id,no_po,pelanggan_id', 'order.pelanggan:id,nama'])
                ->orderByDesc('created_at')->limit(10)->get(),
            'refund_pending_list' => (clone $refunds)
                ->where('status', 'pending_review')
                ->with(['order:id,no_po', 'creator:id,name'])
                ->orderByDesc('created_at')->limit(10)->get(),
            'payment_status' => $this->paymentStatusBreakdown($brandId),
        ];
    }

    // ---- Shared helpers ----

    private function statusBreakdown(?string $brandId): array
    {
        $rows = Order::query()
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
            ->select('status_po', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status_po')
            ->pluck('cnt', 'status_po')
            ->toArray();

        $statuses = [
            'draft' => ['label' => 'Draft', 'color' => '#9CA3AF'],
            'published' => ['label' => 'PO Masuk', 'color' => '#3B82F6'],
            'on_progress' => ['label' => 'On Progress', 'color' => '#F59E0B'],
            'selesai_produksi' => ['label' => 'Selesai Produksi', 'color' => '#22C55E'],
            'siap_dikirim' => ['label' => 'Siap Dikirim', 'color' => '#06B6D4'],
            'sudah_dikirim' => ['label' => 'Sudah Dikirim', 'color' => '#8B5CF6'],
            'delay' => ['label' => 'Delay', 'color' => '#EF4444'],
            'hold' => ['label' => 'Hold', 'color' => '#F97316'],
        ];

        $out = [];
        foreach ($statuses as $key => $meta) {
            $out[] = [
                'key' => $key,
                'label' => $meta['label'],
                'color' => $meta['color'],
                'count' => (int) ($rows[$key] ?? 0),
            ];
        }
        return $out;
    }

    private function statusBreakdownForBrands(array $brandIds): array
    {
        $rows = Order::query()
            ->whereIn('brand_id', $brandIds)
            ->select('status_po', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status_po')->pluck('cnt', 'status_po')->toArray();

        return collect($this->statusBreakdown(null))->map(fn ($s) => [
            ...$s,
            'count' => (int) ($rows[$s['key']] ?? 0),
        ])->all();
    }

    private function trendHarian(?string $brandId, int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $rows = Order::query()
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
            ->where('tanggal_masuk', '>=', $from->toDateString())
            ->select(DB::raw('DATE(tanggal_masuk) as d'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('d')->pluck('cnt', 'd')->toArray();

        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $d = $from->copy()->addDays($i)->toDateString();
            $out[] = ['date' => $d, 'count' => (int) ($rows[$d] ?? 0)];
        }
        return $out;
    }

    private function trendHarianForBrands(array $brandIds, int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $rows = Order::query()
            ->whereIn('brand_id', $brandIds)
            ->where('tanggal_masuk', '>=', $from->toDateString())
            ->select(DB::raw('DATE(tanggal_masuk) as d'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('d')->pluck('cnt', 'd')->toArray();

        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $d = $from->copy()->addDays($i)->toDateString();
            $out[] = ['date' => $d, 'count' => (int) ($rows[$d] ?? 0)];
        }
        return $out;
    }

    private function produkTerpopuler(?string $brandId, int $limit): array
    {
        return OrderItem::query()
            ->when($brandId, fn ($q) => $q->whereHas('order', fn ($x) => $x->where('brand_id', $brandId)))
            ->select('nama_produk', DB::raw('SUM(quantity) as total_qty'), DB::raw('COUNT(DISTINCT order_id) as total_order'))
            ->groupBy('nama_produk')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'nama' => $r->nama_produk,
                'total_qty' => (int) $r->total_qty,
                'total_order' => (int) $r->total_order,
            ])
            ->all();
    }

    private function kategoriDistribusi(?string $brandId): array
    {
        return Order::query()
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
            ->whereNotNull('kategori_order_id')
            ->with('kategoriOrder:id,nama')
            ->select('kategori_order_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('kategori_order_id')
            ->get()
            ->map(fn ($r) => ['label' => $r->kategoriOrder?->nama ?? '-', 'count' => (int) $r->cnt])
            ->all();
    }

    private function sumberDistribusi(?string $brandId): array
    {
        return Order::query()
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
            ->whereNotNull('sumber_order_id')
            ->with('sumberOrder:id,nama')
            ->select('sumber_order_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('sumber_order_id')
            ->get()
            ->map(fn ($r) => ['label' => $r->sumberOrder?->nama ?? '-', 'count' => (int) $r->cnt])
            ->all();
    }

    private function wilayahTop(?string $brandId, int $limit): array
    {
        return Order::query()
            ->when($brandId, fn ($q) => $q->where('orders.brand_id', $brandId))
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

    private function topPelanggan(?string $brandId, int $limit): array
    {
        return Order::query()
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
            ->select('pelanggan_id', DB::raw('COUNT(*) as total_order'), DB::raw('SUM(total_tagihan) as total_value'))
            ->groupBy('pelanggan_id')
            ->with('pelanggan:id,nama,kode,nomor_hp')
            ->orderByDesc('total_value')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'nama' => $r->pelanggan?->nama,
                'kode' => $r->pelanggan?->kode,
                'total_order' => (int) $r->total_order,
                'total_value' => (float) $r->total_value,
            ])
            ->all();
    }

    private function poTerbaru(?string $brandId, int $limit): array
    {
        return Order::query()
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
            ->with(['pelanggan:id,nama', 'brand:id,nama_brand,kode'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'no_po' => $o->no_po,
                'nama_po' => $o->nama_po,
                'pelanggan' => $o->pelanggan?->nama,
                'brand' => $o->brand?->kode,
                'status' => $o->status_po,
                'total_tagihan' => (float) $o->total_tagihan,
                'tanggal_masuk' => $o->tanggal_masuk?->toDateString(),
            ])
            ->all();
    }

    private function deadlineMendekat(?string $brandId, int $limit): array
    {
        return Order::query()
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
            ->whereIn('status_po', ['published', 'on_progress'])
            ->whereBetween('deadline_customer', [now(), now()->addDays(7)])
            ->with(['pelanggan:id,nama'])
            ->orderBy('deadline_customer')
            ->limit($limit)
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'no_po' => $o->no_po,
                'nama_po' => $o->nama_po,
                'pelanggan' => $o->pelanggan?->nama,
                'deadline' => $o->deadline_customer?->toDateString(),
                'days_remaining' => now()->startOfDay()->diffInDays($o->deadline_customer, false),
                'status' => $o->status_po,
            ])
            ->all();
    }

    private function poTerlambat(?string $brandId, int $limit): array
    {
        return Order::query()
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
            ->whereNotIn('status_po', ['draft', 'sudah_dikirim'])
            ->where('deadline_customer', '<', today())
            ->with(['pelanggan:id,nama'])
            ->orderBy('deadline_customer')
            ->limit($limit)
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'no_po' => $o->no_po,
                'nama_po' => $o->nama_po,
                'pelanggan' => $o->pelanggan?->nama,
                'deadline' => $o->deadline_customer?->toDateString(),
                'days_late' => abs(now()->startOfDay()->diffInDays($o->deadline_customer, false)),
                'status' => $o->status_po,
            ])
            ->all();
    }

    private function rijekByJenis(?string $brandId): array
    {
        return Rijek::query()
            ->when($brandId, fn ($q) => $q->whereHas('order', fn ($x) => $x->where('brand_id', $brandId)))
            ->select('jenis', DB::raw('SUM(jumlah) as total'))
            ->groupBy('jenis')
            ->get()
            ->map(fn ($r) => ['label' => $r->jenis, 'count' => (int) $r->total])
            ->all();
    }

    private function rijekByTingkat(?string $brandId): array
    {
        return Rijek::query()
            ->when($brandId, fn ($q) => $q->whereHas('order', fn ($x) => $x->where('brand_id', $brandId)))
            ->select('tingkat', DB::raw('SUM(jumlah) as total'))
            ->groupBy('tingkat')
            ->get()
            ->map(fn ($r) => ['label' => $r->tingkat, 'count' => (int) $r->total])
            ->all();
    }

    private function progressDistribution(?string $brandId): array
    {
        return DB::table('order_progress_details')
            ->join('progress', 'progress.id', '=', 'order_progress_details.progress_id')
            ->join('orders', 'orders.id', '=', 'order_progress_details.order_id')
            ->when($brandId, fn ($q) => $q->where('orders.brand_id', $brandId))
            ->where('order_progress_details.status', 'on_progress')
            ->select('progress.nama_progress as label', DB::raw('COUNT(*) as count'))
            ->groupBy('progress.nama_progress', 'progress.urutan')
            ->orderBy('progress.urutan')
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'count' => (int) $r->count])
            ->all();
    }

    private function paymentStatusBreakdown(?string $brandId): array
    {
        $sub = DB::table('orders')
            ->leftJoin('order_payments', 'order_payments.order_id', '=', 'orders.id')
            ->when($brandId, fn ($q) => $q->where('orders.brand_id', $brandId))
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
            ['label' => 'Lunas', 'value' => (int) ($row->lunas ?? 0)],
            ['label' => 'Partial', 'value' => (int) ($row->partial ?? 0)],
            ['label' => 'Belum Bayar', 'value' => (int) ($row->belum_bayar ?? 0)],
        ];
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
}
