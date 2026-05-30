<?php

namespace App\Services\Reports;

use App\Models\Brand;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\Refund;
use App\Models\Order\Rijek;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ComparisonRunner
{
    /**
     * Bandingkan beberapa brand head-to-head pada periode yang sama.
     *
     * @param  string[]  $brandIds
     * @return array  [brands => [...metrics per brand], summary => [...]]
     */
    public function run(array $brandIds, ?string $from, ?string $to): array
    {
        $from = Carbon::parse($from ?? now()->subMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($to ?? now()->toDateString())->endOfDay();

        $brands = Brand::whereIn('id', $brandIds)->get();
        if ($brands->isEmpty()) {
            return ['brands' => [], 'summary' => [], 'periode' => $this->periodeLabel($from, $to)];
        }

        $result = [];
        foreach ($brands as $brand) {
            $result[] = $this->metricsForBrand($brand, $from, $to);
        }

        // Tentukan winner per metric (highest revenue, highest po_count, lowest rijek_rate)
        $maxRevenue = max(array_column($result, 'revenue')) ?: 0;
        $maxPo = max(array_column($result, 'po_count')) ?: 0;
        $minRijek = count($result) ? min(array_filter(array_column($result, 'rijek_rate'), fn ($x) => $x !== null) ?: [0]) : 0;

        foreach ($result as $i => $r) {
            $result[$i]['is_winner_revenue'] = $maxRevenue > 0 && $r['revenue'] == $maxRevenue;
            $result[$i]['is_winner_po'] = $maxPo > 0 && $r['po_count'] == $maxPo;
            $result[$i]['is_winner_rijek'] = $r['rijek_rate'] !== null && $r['rijek_rate'] == $minRijek;
        }

        return [
            'brands' => $result,
            'summary' => [
                'total_revenue' => array_sum(array_column($result, 'revenue')),
                'total_po' => array_sum(array_column($result, 'po_count')),
                'total_customers' => array_sum(array_column($result, 'customer_count')),
                'avg_rijek_rate' => $this->avg(array_filter(array_column($result, 'rijek_rate'), fn ($x) => $x !== null)),
            ],
            'periode' => $this->periodeLabel($from, $to),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ];
    }

    private function metricsForBrand(Brand $brand, Carbon $from, Carbon $to): array
    {
        $baseQ = Order::query()
            ->where('brand_id', $brand->id)
            ->whereBetween('tanggal_masuk', [$from, $to]);

        $poCount = (clone $baseQ)->where('status_po', '!=', 'draft')->count();
        $revenue = (float) (clone $baseQ)->where('status_po', '!=', 'draft')->sum('total_tagihan');

        $customers = (clone $baseQ)->where('status_po', '!=', 'draft')
            ->distinct('pelanggan_id')->count('pelanggan_id');

        $totalQty = OrderItem::query()
            ->whereHas('order', fn ($q) => $q->where('brand_id', $brand->id)
                ->whereBetween('tanggal_masuk', [$from, $to])
                ->where('status_po', '!=', 'draft'))
            ->sum('quantity');

        $totalRijek = Rijek::query()
            ->whereHas('order', fn ($q) => $q->where('brand_id', $brand->id))
            ->whereBetween('created_at', [$from, $to])
            ->sum('jumlah');

        $rijekRate = $totalQty > 0 ? round(($totalRijek / $totalQty) * 100, 2) : null;

        $totalRefund = (float) Refund::query()
            ->where('brand_id', $brand->id)
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'published')
            ->sum('nominal_refund');

        $topProduct = OrderItem::query()
            ->whereHas('order', fn ($q) => $q->where('brand_id', $brand->id)
                ->whereBetween('tanggal_masuk', [$from, $to]))
            ->select('nama_produk', DB::raw('SUM(quantity) as qty'))
            ->groupBy('nama_produk')
            ->orderByDesc('qty')
            ->first();

        $statusBreakdown = (clone $baseQ)
            ->select('status_po', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status_po')
            ->pluck('cnt', 'status_po')
            ->toArray();

        return [
            'brand_id' => $brand->id,
            'brand_name' => $brand->nama_brand,
            'kode' => $brand->kode,
            'warna' => $brand->warna_primary,
            'po_count' => (int) $poCount,
            'revenue' => $revenue,
            'avg_po_value' => $poCount > 0 ? round($revenue / $poCount, 0) : 0,
            'customer_count' => (int) $customers,
            'total_qty' => (int) $totalQty,
            'rijek_count' => (int) $totalRijek,
            'rijek_rate' => $rijekRate,
            'refund_amount' => $totalRefund,
            'top_product' => $topProduct ? ['nama' => $topProduct->nama_produk, 'qty' => (int) $topProduct->qty] : null,
            'status_breakdown' => $statusBreakdown,
        ];
    }

    private function avg(array $values): float
    {
        if (empty($values)) return 0;
        return round(array_sum($values) / count($values), 2);
    }

    private function periodeLabel(Carbon $from, Carbon $to): string
    {
        return $from->translatedFormat('d M Y') . ' — ' . $to->translatedFormat('d M Y');
    }

    public function runAdvanced(string $mode, array $brandIds, array $years, ?string $singleBrandId, ?int $singleYear): array
    {
        if ($mode === 'years') {
            $brand = Brand::find($singleBrandId);
            $data = [];
            foreach ($years as $year) {
                $metrics = $this->getMonthlyMetricsForBrandAndYear($singleBrandId, $year);
                $data[$year] = $metrics;
            }
            return [
                'mode' => 'years',
                'brand' => $brand,
                'years' => $years,
                'data' => $data,
            ];
        }

        // Default to 'brands' mode
        $brands = Brand::whereIn('id', $brandIds)->get();
        $year = $singleYear ?: (int) now()->year;
        $data = [];
        foreach ($brands as $brand) {
            $metrics = $this->getMonthlyMetricsForBrandAndYear($brand->id, $year);
            $data[$brand->id] = [
                'brand_name' => $brand->nama_brand,
                'kode' => $brand->kode,
                'warna' => $brand->warna_primary,
                'months' => $metrics['months'],
                'totals' => $metrics['totals'],
            ];
        }

        return [
            'mode' => 'brands',
            'year' => $year,
            'brandIds' => $brandIds,
            'data' => $data,
        ];
    }

    public function getMonthlyMetricsForBrandAndYear(string $brandId, int $year): array
    {
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $monthExpr = $isSqlite ? 'CAST(strftime("%m", tanggal_masuk) AS INTEGER)' : 'MONTH(tanggal_masuk)';
        $orderMonthExpr = $isSqlite ? 'CAST(strftime("%m", orders.tanggal_masuk) AS INTEGER)' : 'MONTH(orders.tanggal_masuk)';

        $orders = Order::query()
            ->where('brand_id', $brandId)
            ->whereYear('tanggal_masuk', $year)
            ->where('status_po', '!=', 'draft')
            ->select(
                DB::raw("$monthExpr as bulan"),
                DB::raw('COUNT(*) as total_po'),
                DB::raw('SUM(total_tagihan) as total_omset')
            )
            ->groupBy('bulan')
            ->get()
            ->keyBy('bulan');

        $items = OrderItem::query()
            ->whereHas('order', function ($q) use ($brandId, $year) {
                $q->where('brand_id', $brandId)
                  ->whereYear('tanggal_masuk', $year)
                  ->where('status_po', '!=', 'draft');
            })
            ->select(
                DB::raw("$orderMonthExpr as idx"),
                DB::raw('SUM(order_items.quantity) as total_pcs')
            )
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->groupBy('idx')
            ->get()
            ->keyBy('idx');

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $out = [];
        $totalPoYear = 0;
        $totalOmsetYear = 0;
        $totalPcsYear = 0;

        foreach ($months as $num => $name) {
            $o = $orders->get($num);
            $item = $items->get($num);
            
            $poVal = (int) ($o ? $o->total_po : 0);
            $omsetVal = (float) ($o ? $o->total_omset : 0);
            $pcsVal = (int) ($item ? $item->total_pcs : 0);

            $totalPoYear += $poVal;
            $totalOmsetYear += $omsetVal;
            $totalPcsYear += $pcsVal;

            $out[$num] = [
                'bulan' => $name,
                'total_po' => $poVal,
                'total_omset' => $omsetVal,
                'total_pcs' => $pcsVal,
            ];
        }

        return [
            'months' => $out,
            'totals' => [
                'total_po' => $totalPoYear,
                'total_omset' => $totalOmsetYear,
                'total_pcs' => $totalPcsYear,
            ]
        ];
    }
}
