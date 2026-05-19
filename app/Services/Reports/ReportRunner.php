<?php

namespace App\Services\Reports;

use App\Models\Finance\Pemasukan;
use App\Models\Finance\Pengeluaran;
use App\Models\Master\Customer;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\Refund;
use App\Models\Order\Rijek;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportRunner
{
    public function run(string $slug, ?string $brandId, array $filters): array
    {
        return match ($slug) {
            'penjualan-produk' => $this->penjualanProduk($brandId, $filters),
            'pelanggan' => $this->pelanggan($brandId, $filters),
            'wilayah' => $this->wilayah($brandId, $filters),
            'kategori' => $this->kategori($brandId, $filters),
            'status-po' => $this->statusPo($brandId, $filters),
            'monitoring-deadline' => $this->monitoringDeadline($brandId, $filters),
            'rijek' => $this->rijek($brandId, $filters),
            'refund' => $this->refund($brandId, $filters),
            'pemasukan' => $this->pemasukan($brandId, $filters),
            'pengeluaran' => $this->pengeluaran($brandId, $filters),
            'laba-rugi' => $this->labaRugi($brandId, $filters),
            'peak-hours' => $this->peakHours($brandId, $filters),
            default => ['rows' => [], 'summary' => []],
        };
    }

    private function dateRange(array $filters): array
    {
        $from = ! empty($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : Carbon::now()->subMonth()->startOfDay();
        $to = ! empty($filters['to']) ? Carbon::parse($filters['to'])->endOfDay() : Carbon::now()->endOfDay();
        return [$from, $to];
    }

    private function penjualanProduk(?string $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $rows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->when($brandId, fn ($q) => $q->where('orders.brand_id', $brandId))
            ->whereBetween('orders.tanggal_masuk', [$from, $to])
            ->where('orders.status_po', '!=', 'draft')
            ->select(
                'order_items.nama_produk',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('COUNT(DISTINCT order_items.order_id) as total_order'),
                DB::raw('AVG(order_items.quantity) as avg_qty'),
                DB::raw('SUM(order_items.subtotal) as total_value'),
            )
            ->groupBy('order_items.nama_produk')
            ->orderByDesc('total_qty')
            ->get()
            ->map(fn ($r) => [
                'nama_produk' => $r->nama_produk,
                'total_qty' => (int) $r->total_qty,
                'total_order' => (int) $r->total_order,
                'avg_qty' => round((float) $r->avg_qty, 1),
                'total_value' => (float) $r->total_value,
            ])->all();

        return [
            'rows' => $rows,
            'summary' => [
                ['label' => 'Total Produk Berbeda', 'value' => count($rows)],
                ['label' => 'Total Qty Terjual', 'value' => array_sum(array_column($rows, 'total_qty'))],
                ['label' => 'Total Order', 'value' => array_sum(array_column($rows, 'total_order'))],
                ['label' => 'Total Nilai', 'value' => array_sum(array_column($rows, 'total_value')), 'format' => 'currency'],
            ],
        ];
    }

    private function pelanggan(?string $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $rows = DB::table('customers')
            ->leftJoin('orders', function ($j) use ($from, $to) {
                $j->on('orders.pelanggan_id', '=', 'customers.id')
                  ->whereBetween('orders.tanggal_masuk', [$from, $to])
                  ->where('orders.status_po', '!=', 'draft');
            })
            ->when($brandId, fn ($q) => $q->where('customers.brand_id', $brandId))
            ->select(
                'customers.kode', 'customers.nama', 'customers.nomor_hp',
                DB::raw('COUNT(orders.id) as total_order'),
                DB::raw('COALESCE(SUM(orders.total_tagihan), 0) as total_value'),
                DB::raw('MAX(orders.tanggal_masuk) as last_order'),
            )
            ->groupBy('customers.id', 'customers.kode', 'customers.nama', 'customers.nomor_hp')
            ->havingRaw('COUNT(orders.id) > 0')
            ->orderByDesc('total_value')
            ->get()
            ->map(fn ($r) => [
                'kode' => $r->kode,
                'nama' => $r->nama,
                'nomor_hp' => $r->nomor_hp,
                'total_order' => (int) $r->total_order,
                'total_value' => (float) $r->total_value,
                'last_order' => $r->last_order,
            ])->all();

        return [
            'rows' => $rows,
            'summary' => [
                ['label' => 'Total Pelanggan Aktif', 'value' => count($rows)],
                ['label' => 'Total Order', 'value' => array_sum(array_column($rows, 'total_order'))],
                ['label' => 'Total Transaksi', 'value' => array_sum(array_column($rows, 'total_value')), 'format' => 'currency'],
            ],
        ];
    }

    private function wilayah(?string $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $rows = DB::table('orders')
            ->join('customers', 'customers.id', '=', 'orders.pelanggan_id')
            ->when($brandId, fn ($q) => $q->where('orders.brand_id', $brandId))
            ->whereBetween('orders.tanggal_masuk', [$from, $to])
            ->where('orders.status_po', '!=', 'draft')
            ->whereNotNull('customers.kabupaten_nama')
            ->select(
                'customers.provinsi_nama as provinsi',
                'customers.kabupaten_nama as kabupaten',
                DB::raw('COUNT(DISTINCT customers.id) as total_pelanggan'),
                DB::raw('COUNT(orders.id) as total_order'),
                DB::raw('SUM(orders.total_tagihan) as total_value'),
            )
            ->groupBy('customers.provinsi_nama', 'customers.kabupaten_nama')
            ->orderByDesc('total_order')
            ->get()
            ->map(fn ($r) => [
                'provinsi' => $r->provinsi,
                'kabupaten' => $r->kabupaten,
                'total_pelanggan' => (int) $r->total_pelanggan,
                'total_order' => (int) $r->total_order,
                'total_value' => (float) $r->total_value,
            ])->all();

        return ['rows' => $rows, 'summary' => [
            ['label' => 'Total Wilayah', 'value' => count($rows)],
            ['label' => 'Total Order', 'value' => array_sum(array_column($rows, 'total_order'))],
            ['label' => 'Total Nilai', 'value' => array_sum(array_column($rows, 'total_value')), 'format' => 'currency'],
        ]];
    }

    private function kategori(?string $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $rows = DB::table('orders')
            ->leftJoin('kategori_orders', 'kategori_orders.id', '=', 'orders.kategori_order_id')
            ->leftJoin('order_items', 'order_items.order_id', '=', 'orders.id')
            ->when($brandId, fn ($q) => $q->where('orders.brand_id', $brandId))
            ->whereBetween('orders.tanggal_masuk', [$from, $to])
            ->where('orders.status_po', '!=', 'draft')
            ->select(
                DB::raw("COALESCE(kategori_orders.nama, '— Tanpa Kategori —') as kategori"),
                DB::raw('COUNT(DISTINCT orders.id) as total_order'),
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as total_qty'),
                DB::raw('SUM(DISTINCT orders.total_tagihan) as total_value'),
            )
            ->groupBy('kategori')
            ->orderByDesc('total_order')
            ->get()
            ->map(fn ($r) => [
                'kategori' => $r->kategori,
                'total_order' => (int) $r->total_order,
                'total_qty' => (int) $r->total_qty,
                'total_value' => (float) $r->total_value,
            ])->all();

        return ['rows' => $rows, 'summary' => [
            ['label' => 'Total Kategori Terpakai', 'value' => count($rows)],
            ['label' => 'Total Order', 'value' => array_sum(array_column($rows, 'total_order'))],
        ]];
    }

    private function statusPo(?string $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $q = Order::query()
            ->when($brandId, fn ($x) => $x->where('brand_id', $brandId))
            ->whereBetween('tanggal_masuk', [$from, $to])
            ->with(['pelanggan:id,nama']);

        if (! empty($filters['status'])) {
            $q->where('status_po', $filters['status']);
        }

        $orders = $q->orderByDesc('tanggal_masuk')->get();

        $rows = $orders->map(fn ($o) => [
            'no_po' => $o->no_po,
            'nama_po' => $o->nama_po,
            'pelanggan' => $o->pelanggan?->nama,
            'tanggal_masuk' => $o->tanggal_masuk?->toDateString(),
            'deadline' => $o->deadline_customer?->toDateString(),
            'status' => $o->status_po,
            'total' => (float) $o->total_tagihan,
        ])->all();

        $breakdown = $orders->groupBy('status_po')->map->count();

        return ['rows' => $rows, 'summary' => $breakdown->map(fn ($cnt, $st) => ['label' => $st, 'value' => $cnt])->values()->all()];
    }

    private function monitoringDeadline(?string $brandId, array $filters): array
    {
        $threshold = (int) ($filters['threshold'] ?? 7);

        $orders = Order::query()
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
            ->whereNotIn('status_po', ['draft', 'sudah_dikirim'])
            ->where('deadline_customer', '<=', Carbon::now()->addDays($threshold))
            ->with(['pelanggan:id,nama'])
            ->orderBy('deadline_customer')
            ->get();

        $rows = $orders->map(function ($o) {
            $days = now()->startOfDay()->diffInDays($o->deadline_customer, false);
            return [
                'no_po' => $o->no_po,
                'pelanggan' => $o->pelanggan?->nama,
                'deadline' => $o->deadline_customer?->toDateString(),
                'days' => (int) $days,
                'status' => $o->status_po,
            ];
        })->all();

        $terlambat = collect($rows)->where('days', '<', 0)->count();
        $mendekati = collect($rows)->whereBetween('days', [0, 2])->count();

        return ['rows' => $rows, 'summary' => [
            ['label' => 'Total Monitoring', 'value' => count($rows)],
            ['label' => 'Terlambat', 'value' => $terlambat],
            ['label' => 'Mendekati Deadline (≤2 hari)', 'value' => $mendekati],
        ]];
    }

    private function rijek(?string $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $rows = Rijek::query()
            ->when($brandId, fn ($q) => $q->whereHas('order', fn ($x) => $x->where('brand_id', $brandId)))
            ->whereBetween('created_at', [$from, $to])
            ->with(['order:id,no_po', 'progress:id,nama_progress'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($r) => [
                'no_po' => $r->order?->no_po,
                'tahapan' => $r->progress?->nama_progress ?? '-',
                'jenis' => $r->jenis,
                'tingkat' => $r->tingkat,
                'jumlah' => (int) $r->jumlah,
                'biaya_ganti' => (float) $r->biaya_ganti,
                'kendala' => $r->kendala,
                'tanggal' => $r->created_at?->toDateString(),
            ])->all();

        return ['rows' => $rows, 'summary' => [
            ['label' => 'Total Insiden Rijek', 'value' => count($rows)],
            ['label' => 'Total Item Rijek', 'value' => array_sum(array_column($rows, 'jumlah'))],
            ['label' => 'Total Biaya Ganti', 'value' => array_sum(array_column($rows, 'biaya_ganti')), 'format' => 'currency'],
        ]];
    }

    private function refund(?string $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $q = Refund::query()
            ->when($brandId, fn ($x) => $x->where('brand_id', $brandId))
            ->whereBetween('created_at', [$from, $to])
            ->with('order:id,no_po');

        if (! empty($filters['refund_status'])) {
            $q->where('status', $filters['refund_status']);
        }

        $rows = $q->orderByDesc('created_at')->get()->map(fn ($r) => [
            'refund_number' => $r->refund_number,
            'no_po' => $r->order?->no_po,
            'jenis_masalah' => $r->jenis_masalah,
            'jumlah_item' => (int) $r->jumlah_item,
            'nominal_refund' => (float) $r->nominal_refund,
            'status' => $r->status,
            'tanggal' => $r->created_at?->toDateString(),
        ])->all();

        return ['rows' => $rows, 'summary' => [
            ['label' => 'Total Refund', 'value' => count($rows)],
            ['label' => 'Total Nominal', 'value' => array_sum(array_column($rows, 'nominal_refund')), 'format' => 'currency'],
        ]];
    }

    private function pemasukan(?string $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $q = Pemasukan::query()
            ->when($brandId, fn ($x) => $x->where('brand_id', $brandId))
            ->whereBetween('tanggal', [$from, $to])
            ->with('kategori:id,nama_kategori');

        if (isset($filters['is_auto']) && $filters['is_auto'] !== '') {
            $q->where('is_auto', (bool) $filters['is_auto']);
        }

        $rows = $q->orderByDesc('tanggal')->get()->map(fn ($p) => [
            'tanggal' => $p->tanggal?->toDateString(),
            'kategori' => $p->kategori?->nama_kategori,
            'keterangan' => $p->keterangan,
            'nominal' => (float) $p->nominal,
            'sumber' => $p->is_auto ? 'Otomatis' : 'Manual',
        ])->all();

        return ['rows' => $rows, 'summary' => [
            ['label' => 'Total Transaksi', 'value' => count($rows)],
            ['label' => 'Total Pemasukan', 'value' => array_sum(array_column($rows, 'nominal')), 'format' => 'currency'],
        ]];
    }

    private function pengeluaran(?string $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $q = Pengeluaran::query()
            ->when($brandId, fn ($x) => $x->where('brand_id', $brandId))
            ->whereBetween('tanggal', [$from, $to])
            ->with('kategori:id,nama_kategori');

        if (isset($filters['is_auto']) && $filters['is_auto'] !== '') {
            $q->where('is_auto', (bool) $filters['is_auto']);
        }

        $rows = $q->orderByDesc('tanggal')->get()->map(fn ($p) => [
            'tanggal' => $p->tanggal?->toDateString(),
            'kategori' => $p->kategori?->nama_kategori,
            'keterangan' => $p->keterangan,
            'nominal' => (float) $p->nominal,
            'sumber' => $p->is_auto ? 'Otomatis' : 'Manual',
        ])->all();

        return ['rows' => $rows, 'summary' => [
            ['label' => 'Total Transaksi', 'value' => count($rows)],
            ['label' => 'Total Pengeluaran', 'value' => array_sum(array_column($rows, 'nominal')), 'format' => 'currency'],
        ]];
    }

    private function labaRugi(?string $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $pemasukan = Pemasukan::query()
            ->when($brandId, fn ($x) => $x->where('brand_id', $brandId))
            ->whereBetween('tanggal', [$from, $to])
            ->select(DB::raw('DATE_FORMAT(tanggal, "%Y-%m") as bulan'), DB::raw('SUM(nominal) as total'))
            ->groupBy('bulan')
            ->pluck('total', 'bulan')
            ->toArray();

        $pengeluaran = Pengeluaran::query()
            ->when($brandId, fn ($x) => $x->where('brand_id', $brandId))
            ->whereBetween('tanggal', [$from, $to])
            ->select(DB::raw('DATE_FORMAT(tanggal, "%Y-%m") as bulan'), DB::raw('SUM(nominal) as total'))
            ->groupBy('bulan')
            ->pluck('total', 'bulan')
            ->toArray();

        $months = collect(array_keys($pemasukan + $pengeluaran))->unique()->sort()->values();

        $rows = $months->map(function ($month) use ($pemasukan, $pengeluaran) {
            $in = (float) ($pemasukan[$month] ?? 0);
            $out = (float) ($pengeluaran[$month] ?? 0);
            return [
                'bulan' => Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y'),
                'pemasukan' => $in,
                'pengeluaran' => $out,
                'laba_rugi' => $in - $out,
            ];
        })->all();

        $totalIn = collect($rows)->sum('pemasukan');
        $totalOut = collect($rows)->sum('pengeluaran');

        return ['rows' => $rows, 'summary' => [
            ['label' => 'Total Pemasukan', 'value' => $totalIn, 'format' => 'currency'],
            ['label' => 'Total Pengeluaran', 'value' => $totalOut, 'format' => 'currency'],
            ['label' => 'Laba/Rugi Bersih', 'value' => $totalIn - $totalOut, 'format' => 'currency'],
        ]];
    }

    private function peakHours(?string $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $hariMap = [1 => 'Minggu', 2 => 'Senin', 3 => 'Selasa', 4 => 'Rabu', 5 => 'Kamis', 6 => 'Jumat', 7 => 'Sabtu'];

        $raw = Order::query()
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
            ->where('status_po', '!=', 'draft')
            ->whereBetween('created_at', [$from, $to])
            ->select(
                DB::raw('DAYOFWEEK(created_at) as dow'),
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as total'),
            )
            ->groupBy(DB::raw('DAYOFWEEK(created_at)'), DB::raw('HOUR(created_at)'))
            ->get();

        // Flat rows untuk tabel & export
        $rows = $raw->map(fn ($r) => [
            'hari'  => $hariMap[$r->dow] ?? "Hari {$r->dow}",
            'jam'   => sprintf('%02d:00', $r->hour),
            'total' => (int) $r->total,
        ])->sortBy(['hari', 'jam'])->values()->all();

        // Heatmap series: 1 series per hari (urutan Senin–Minggu), 24 data points per jam
        $orderedDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        $matrix = collect($orderedDays)->map(function (string $day) use ($raw, $hariMap) {
            $dowKey = array_search($day, $hariMap);
            $data = collect(range(0, 23))->map(function (int $h) use ($raw, $dowKey) {
                $found = $raw->first(fn ($r) => (int) $r->dow === $dowKey && (int) $r->hour === $h);
                return ['x' => sprintf('%02d:00', $h), 'y' => $found ? (int) $found->total : 0];
            })->values()->all();
            return ['name' => $day, 'data' => $data];
        })->values()->all();

        $totalOrder = $raw->sum('total');
        $peakRow = $raw->sortByDesc('total')->first();
        $peakLabel = $peakRow
            ? "{$hariMap[$peakRow->dow]} jam " . sprintf('%02d:00', $peakRow->hour) . " ({$peakRow->total} order)"
            : '-';

        return [
            'rows'        => $rows,
            'heatmapSeries' => $matrix,
            'summary'     => [
                ['label' => 'Total Order dalam Periode', 'value' => $totalOrder, 'format' => 'number'],
                ['label' => 'Jam Tersibuk', 'value' => $peakLabel, 'format' => 'text'],
            ],
        ];
    }
}
