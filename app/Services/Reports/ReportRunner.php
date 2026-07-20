<?php

namespace App\Services\Reports;

use App\Models\Finance\Pemasukan;
use App\Models\Finance\Pengeluaran;
use App\Models\Master\Customer;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\OrderPayment;
use App\Models\Order\Refund;
use App\Models\Order\Rijek;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportRunner
{
    private function bf(string|array|null $brandId): \Closure
    {
        return fn ($q) => is_array($brandId)
            ? $q->whereIn('brand_id', $brandId)
            : $q->where('brand_id', $brandId);
    }

    private function obf(string|array|null $brandId): \Closure
    {
        return fn ($q) => is_array($brandId)
            ? $q->whereIn('orders.brand_id', $brandId)
            : $q->where('orders.brand_id', $brandId);
    }

    public function run(string $slug, string|array|null $brandId, array $filters): array
    {
        return match ($slug) {
            'penjualan-produk' => $this->penjualanProduk($brandId, $filters),
            'pelanggan' => $this->pelanggan($brandId, $filters),
            'wilayah' => $this->wilayah($brandId, $filters),
            'jenis-po' => $this->jenisPo($brandId, $filters),
            'status-po' => $this->statusPo($brandId, $filters),
            'monitoring-deadline' => $this->monitoringDeadline($brandId, $filters),
            'rijek' => $this->rijek($brandId, $filters),
            'refund' => $this->refund($brandId, $filters),
            'pemasukan' => $this->pemasukan($brandId, $filters),
            'pengeluaran' => $this->pengeluaran($brandId, $filters),
            'arus-kas-bank' => $this->arusKasBank($brandId, $filters),
            'analisis-marketing' => $this->analisisMarketing($brandId, $filters),
            'crm-churn' => $this->crmChurn($brandId, $filters),
            'crm-seasonal' => $this->crmSeasonal($brandId, $filters),
            'kinerja-produksi' => $this->kinerjaProduksi($brandId, $filters),
            default => ['rows' => [], 'summary' => []],
        };
    }

    private function dateRange(array $filters): array
    {
        $from = ! empty($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : Carbon::now()->subMonth()->startOfDay();
        $to = ! empty($filters['to']) ? Carbon::parse($filters['to'])->endOfDay() : Carbon::now()->endOfDay();
        return [$from, $to];
    }

    private function penjualanProduk(string|array|null $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $rows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->when($brandId, $this->obf($brandId))
            ->where('order_items.is_addon', false)
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

    private function pelanggan(string|array|null $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $rows = DB::table('customers')
            ->leftJoin('orders', function ($j) use ($from, $to) {
                $j->on('orders.pelanggan_id', '=', 'customers.id')
                  ->whereBetween('orders.tanggal_masuk', [$from, $to])
                  ->where('orders.status_po', '!=', 'draft');
            })
            ->leftJoin(DB::raw('(SELECT order_id, SUM(quantity) as qty FROM order_items WHERE is_addon = 0 GROUP BY order_id) as items_sum'), 'items_sum.order_id', '=', 'orders.id')
            // Array = reseller hub context → filter via orders.brand_id (customers live at hub, orders at branch)
            ->when(is_array($brandId), fn ($q) => $q->whereIn('orders.brand_id', $brandId))
            ->when(! is_array($brandId) && $brandId, fn ($q) => $q->where('customers.brand_id', $brandId))
            ->select(
                'customers.kode', 'customers.nama', 'customers.nomor_hp',
                DB::raw('COUNT(DISTINCT orders.id) as total_order'),
                DB::raw('COALESCE(SUM(orders.total_tagihan), 0) as total_value'),
                DB::raw('COALESCE(SUM(items_sum.qty), 0) as total_qty'),
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
                'total_qty' => (int) $r->total_qty,
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

    private function wilayah(string|array|null $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);
        $level = $filters['level_wilayah'] ?? 'kabupaten';

        $query = DB::table('orders')
            ->join('customers', 'customers.id', '=', 'orders.pelanggan_id')
            ->when($brandId, $this->obf($brandId))
            ->whereBetween('orders.tanggal_masuk', [$from, $to])
            ->where('orders.status_po', '!=', 'draft');

        $selects = [
            DB::raw('COUNT(DISTINCT customers.id) as total_pelanggan'),
            DB::raw('COUNT(orders.id) as total_order'),
            DB::raw('SUM(orders.total_tagihan) as total_value'),
        ];
        
        $groups = [];

        if ($level === 'provinsi') {
            $query->whereNotNull('customers.provinsi_nama');
            $selects[] = 'customers.provinsi_nama as provinsi';
            $groups[] = 'customers.provinsi_nama';
        } elseif ($level === 'kabupaten') {
            $query->whereNotNull('customers.kabupaten_nama');
            $selects[] = 'customers.provinsi_nama as provinsi';
            $selects[] = 'customers.kabupaten_nama as kabupaten';
            $groups[] = 'customers.provinsi_nama';
            $groups[] = 'customers.kabupaten_nama';
        } elseif ($level === 'kecamatan') {
            $query->whereNotNull('customers.kecamatan_nama');
            $selects[] = 'customers.provinsi_nama as provinsi';
            $selects[] = 'customers.kabupaten_nama as kabupaten';
            $selects[] = 'customers.kecamatan_nama as kecamatan';
            $groups[] = 'customers.provinsi_nama';
            $groups[] = 'customers.kabupaten_nama';
            $groups[] = 'customers.kecamatan_nama';
        } elseif ($level === 'desa') {
            $query->whereNotNull('customers.desa_nama');
            $selects[] = 'customers.provinsi_nama as provinsi';
            $selects[] = 'customers.kabupaten_nama as kabupaten';
            $selects[] = 'customers.kecamatan_nama as kecamatan';
            $selects[] = 'customers.desa_nama as desa';
            $groups[] = 'customers.provinsi_nama';
            $groups[] = 'customers.kabupaten_nama';
            $groups[] = 'customers.kecamatan_nama';
            $groups[] = 'customers.desa_nama';
        }

        $rows = $query->select($selects)
            ->groupBy($groups)
            ->orderByDesc('total_order')
            ->get()
            ->map(fn ($r) => [
                'provinsi' => $r->provinsi ?? null,
                'kabupaten' => $r->kabupaten ?? null,
                'kecamatan' => $r->kecamatan ?? null,
                'desa' => $r->desa ?? null,
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


    private function statusPo(string|array|null $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $q = Order::query()
            ->when($brandId, $this->bf($brandId))
            ->whereBetween('tanggal_masuk', [$from, $to])
            ->with(['pelanggan:id,nama'])
            ->withSum(['items' => fn($query) => $query->where('is_addon', false)], 'quantity');

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
            'pcs' => (int) ($o->items_sum_quantity ?? 0),
            'status' => $o->status_po,
            'total' => (float) $o->total_tagihan,
        ])->all();

        $breakdown = $orders->groupBy('status_po')->map->count();

        return ['rows' => $rows, 'summary' => $breakdown->map(fn ($cnt, $st) => ['label' => $st, 'value' => $cnt])->values()->all()];
    }

    private function monitoringDeadline(string|array|null $brandId, array $filters): array
    {
        $threshold = (int) ($filters['threshold'] ?? 7);

        $orders = Order::query()
            ->when($brandId, $this->bf($brandId))
            ->whereNotIn('status_po', ['draft', 'sudah_dikirim', 'selesai'])
            ->whereRaw('COALESCE(end_production_date, deadline_customer) <= ?', [Carbon::now()->addDays($threshold)->toDateString()])
            ->with(['pelanggan:id,nama', 'brand:id,nama_brand'])
            ->withSum(['items' => fn($query) => $query->where('is_addon', false)], 'quantity')
            ->orderByRaw('COALESCE(end_production_date, deadline_customer) ASC')
            ->get();

        $printingNames = \App\Models\Master\Printing::pluck('nama', 'id')->all();

        $mappedOrders = $orders->map(function ($o) use ($printingNames) {
            $prodDeadline = $o->end_production_date ?? $o->deadline_customer;
            $days = $prodDeadline ? (int) now()->startOfDay()->diffInDays($prodDeadline, false) : 0;
            $daysCustomer = $o->deadline_customer ? (int) now()->startOfDay()->diffInDays($o->deadline_customer, false) : null;
            
            $orderPrintings = [];
            foreach ($o->printing_ids ?? [] as $pid) {
                if (isset($printingNames[$pid])) {
                    $orderPrintings[] = $printingNames[$pid];
                }
            }

            return [
                'deadline_produksi' => $prodDeadline?->toDateString(),
                'days' => $days,
                'deadline_customer' => $o->deadline_customer?->toDateString(),
                'days_customer' => $daysCustomer,
                'no_po' => $o->no_po,
                'nama_po' => $o->nama_po,
                'brand_nama' => $o->brand?->nama_brand ?? '-',
                'pelanggan' => $o->pelanggan?->nama ?? '-',
                'pcs' => (int) ($o->items_sum_quantity ?? 0),
                'jenis_printing' => implode(', ', $orderPrintings) ?: '-',
                'status' => $o->status_po,
            ];
        });

        $terlambat = $mappedOrders->where('days', '<', 0)->count();
        $mendekati = $mappedOrders->whereBetween('days', [0, 2])->count();
        $totalMonitoring = $mappedOrders->count();

        $grouped = $mappedOrders->groupBy('deadline_produksi');

        $rows = [];
        foreach ($grouped as $date => $groupOrders) {
            // Group Header Row
            $rows[] = [
                'deadline_produksi' => $date,
                'deadline' => $date,
                'is_group_header' => true,
                'days' => null,
                'deadline_customer' => null,
                'days_customer' => null,
                'no_po' => null,
                'nama_po' => null,
                'brand_nama' => null,
                'pelanggan' => null,
                'pcs' => null,
                'jenis_printing' => null,
                'status' => null,
            ];

            // Order Rows
            foreach ($groupOrders as $o) {
                $rows[] = array_merge($o, [
                    'deadline' => $o['deadline_produksi'],
                    'is_group_header' => false,
                    'is_group_total' => false,
                ]);
            }

            // Group Total Row
            $rows[] = [
                'deadline_produksi' => $date,
                'deadline' => $date,
                'is_group_total' => true,
                'days' => null,
                'deadline_customer' => null,
                'days_customer' => null,
                'no_po' => null,
                'nama_po' => null,
                'brand_nama' => null,
                'pelanggan' => 'TOTAL PCS',
                'pcs' => $groupOrders->sum('pcs'),
                'jenis_printing' => null,
                'status' => null,
                'is_group_header' => false,
            ];
        }

        return ['rows' => $rows, 'summary' => [
            ['label' => 'Total Monitoring', 'value' => $totalMonitoring],
            ['label' => 'Terlambat (Produksi)', 'value' => $terlambat],
            ['label' => 'Mendekati Deadline Produksi (≤2 hari)', 'value' => $mendekati],
        ]];
    }

    private function rijek(string|array|null $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $rows = Rijek::query()
            ->when($brandId, fn ($q) => $q->whereHas('order', $this->bf($brandId)))
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
                'kendala' => $r->kendala,
                'tanggal' => $r->created_at?->toDateString(),
            ])->all();

        // PO terbanyak rijek
        $poTerbanyak = Rijek::query()
            ->when($brandId, fn ($q) => $q->whereHas('order', $this->bf($brandId)))
            ->whereBetween('created_at', [$from, $to])
            ->select('order_id', DB::raw('SUM(jumlah) as total'))
            ->groupBy('order_id')
            ->orderByDesc('total')
            ->with('order:id,no_po')
            ->first();
        $poTerbanyakLabel = $poTerbanyak && $poTerbanyak->order 
            ? "{$poTerbanyak->order->no_po} ({$poTerbanyak->total} pcs)" 
            : '—';

        // Tahapan terbanyak rijek
        $tahapanTerbanyak = Rijek::query()
            ->when($brandId, fn ($q) => $q->whereHas('order', $this->bf($brandId)))
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('progress_id')
            ->select('progress_id', DB::raw('SUM(jumlah) as total'))
            ->groupBy('progress_id')
            ->orderByDesc('total')
            ->with('progress:id,nama_progress')
            ->first();
        $tahapanTerbanyakLabel = $tahapanTerbanyak && $tahapanTerbanyak->progress 
            ? "{$tahapanTerbanyak->progress->nama_progress} ({$tahapanTerbanyak->total} pcs)" 
            : '—';

        return ['rows' => $rows, 'summary' => [
            ['label' => 'Total Insiden Rijek', 'value' => count($rows)],
            ['label' => 'Total Item Rijek', 'value' => array_sum(array_column($rows, 'jumlah'))],
            ['label' => 'PO Terbanyak Rijek', 'value' => $poTerbanyakLabel],
            ['label' => 'Tahapan Terbanyak Rijek', 'value' => $tahapanTerbanyakLabel],
        ]];
    }

    private function refund(string|array|null $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $q = Refund::query()
            ->when($brandId, $this->bf($brandId))
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

    private function pemasukan(string|array|null $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        if (isset($filters['is_auto']) && $filters['is_auto'] !== '') {
            $isAuto = (bool) $filters['is_auto'];
            if (!$isAuto) {
                return ['rows' => [], 'summary' => [
                    ['label' => 'Total Transaksi', 'value' => 0],
                    ['label' => 'Total Pemasukan', 'value' => 0, 'format' => 'currency'],
                ]];
            }
        }

        // Fetch verified positive (debit) payments
        $payments = OrderPayment::query()
            ->join('orders', 'orders.id', '=', 'order_payments.order_id')
            ->whereNotNull('order_payments.verified_at')
            ->where('order_payments.is_debit', true)
            ->whereBetween('order_payments.payment_date', [$from, $to])
            ->when($brandId, function ($q) use ($brandId) {
                return is_array($brandId)
                    ? $q->whereIn('orders.brand_id', $brandId)
                    : $q->where('orders.brand_id', $brandId);
            })
            ->select([
                'order_payments.payment_date as tanggal',
                'order_payments.payment_type',
                'order_payments.dp_sequence',
                'order_payments.amount as nominal',
                'orders.no_po',
                'orders.nama_po',
            ])
            ->orderByDesc('tanggal')
            ->get();

        $rows = $payments->map(function ($p) {
            $label = match ($p->payment_type) {
                'dp'               => 'DP ' . ($p->dp_sequence ?? 1),
                'pelunasan'        => 'Pelunasan',
                'ongkir'           => 'Ongkir',
                'tambahan_produk'  => 'Tambahan Produk',
                default            => 'Pembayaran Lainnya',
            };

            return [
                'tanggal' => $p->tanggal ? Carbon::parse($p->tanggal)->toDateString() : null,
                'kategori' => 'Pembayaran PO',
                'keterangan' => "{$label} PO {$p->no_po} — {$p->nama_po}",
                'nominal' => (float) $p->nominal,
                'sumber' => 'Otomatis',
            ];
        })->all();

        return ['rows' => $rows, 'summary' => [
            ['label' => 'Total Transaksi', 'value' => count($rows)],
            ['label' => 'Total Pemasukan', 'value' => array_sum(array_column($rows, 'nominal')), 'format' => 'currency'],
        ]];
    }

    private function pengeluaran(string|array|null $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        if (isset($filters['is_auto']) && $filters['is_auto'] !== '') {
            $isAuto = (bool) $filters['is_auto'];
            if (!$isAuto) {
                return ['rows' => [], 'summary' => [
                    ['label' => 'Total Transaksi', 'value' => 0],
                    ['label' => 'Total Pengeluaran', 'value' => 0, 'format' => 'currency'],
                ]];
            }
        }

        // 1. Fetch published refunds
        $refunds = Refund::query()
            ->join('orders', 'orders.id', '=', 'refunds.order_id')
            ->where('refunds.status', 'published')
            ->whereBetween('refunds.published_at', [$from, $to])
            ->when($brandId, function ($q) use ($brandId) {
                return is_array($brandId)
                    ? $q->whereIn('refunds.brand_id', $brandId)
                    : $q->where('refunds.brand_id', $brandId);
            })
            ->select([
                'refunds.published_at as tanggal',
                'refunds.refund_number',
                'refunds.nominal_refund as nominal',
                'refunds.alasan',
                'orders.no_po',
            ])
            ->get()
            ->map(fn ($r) => [
                'tanggal' => Carbon::parse($r->tanggal)->toDateString(),
                'kategori' => 'Refund PO',
                'keterangan' => "Refund {$r->refund_number} PO {$r->no_po} — {$r->alasan}",
                'nominal' => (float) $r->nominal,
                'sumber' => 'Otomatis',
            ]);

        // 2. Fetch negative verified payments (cashback/return) excluding auto-refunds to avoid double-counting
        $negativePayments = OrderPayment::query()
            ->join('orders', 'orders.id', '=', 'order_payments.order_id')
            ->whereNotNull('order_payments.verified_at')
            ->where('order_payments.is_debit', false)
            ->where(function ($q) {
                $q->whereNull('order_payments.notes')
                  ->orWhere('order_payments.notes', 'not like', '%Refund otomatis%');
            })
            ->whereBetween('order_payments.payment_date', [$from, $to])
            ->when($brandId, function ($q) use ($brandId) {
                return is_array($brandId)
                    ? $q->whereIn('orders.brand_id', $brandId)
                    : $q->where('orders.brand_id', $brandId);
            })
            ->select([
                'order_payments.payment_date as tanggal',
                'order_payments.payment_type',
                'order_payments.amount as nominal',
                'orders.no_po',
                'orders.nama_po',
            ])
            ->get()
            ->map(function ($p) {
                $label = match ($p->payment_type) {
                    'cashback' => 'Cashback',
                    'return'   => 'Refund',
                    default    => 'Pengeluaran Lainnya',
                };
                $noDoc = $p->no_po;
                if ($p->payment_type === 'cashback') {
                    $cbNumber = str_replace('PO-', 'CB-', $p->no_po);
                    $noDoc = "{$p->no_po} / {$cbNumber}";
                }
                return [
                    'tanggal' => $p->tanggal ? Carbon::parse($p->tanggal)->toDateString() : null,
                    'kategori' => $p->payment_type === 'cashback' ? 'Cashback PO' : 'Refund PO',
                    'keterangan' => "{$label} PO {$noDoc} — {$p->nama_po}",
                    'nominal' => (float) $p->nominal,
                    'sumber' => 'Otomatis',
                ];
            });

        // Merge and sort by date descending
        $rows = $refunds->concat($negativePayments)
            ->sortByDesc('tanggal')
            ->values()
            ->all();

        return ['rows' => $rows, 'summary' => [
            ['label' => 'Total Transaksi', 'value' => count($rows)],
            ['label' => 'Total Pengeluaran', 'value' => array_sum(array_column($rows, 'nominal')), 'format' => 'currency'],
        ]];
    }

    private function analisisMarketing(string|array|null $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $targetBrandId = !empty($filters['brand_id']) ? $filters['brand_id'] : $brandId;

        $rawRows = DB::table('orders')
            ->join('customers', 'customers.id', '=', 'orders.pelanggan_id')
            ->leftJoin('customer_types', 'customer_types.id', '=', 'customers.type_pelanggan_id')
            ->leftJoin('sumber_orders', 'sumber_orders.id', '=', 'orders.sumber_order_id')
            ->leftJoin('sumber_orders as parent_sumber', 'parent_sumber.id', '=', 'sumber_orders.parent_id')
            ->leftJoin(DB::raw('(SELECT order_id, SUM(quantity) as qty FROM order_items WHERE is_addon = 0 ' .
                (!empty($filters['product_id']) ? 'AND product_id = ' . DB::connection()->getPdo()->quote($filters['product_id']) : '') .
                ' GROUP BY order_id) as items_sum'), 'items_sum.order_id', '=', 'orders.id')
            ->whereBetween('orders.tanggal_masuk', [$from, $to])
            ->where('orders.status_po', '!=', 'draft')
            ->when($targetBrandId && $targetBrandId !== 'all', $this->obf($targetBrandId))
            ->when(! empty($filters['customer_type_id']), fn ($x) => $x->where('customers.type_pelanggan_id', $filters['customer_type_id']))
            ->when(! empty($filters['sumber_order_id']), function ($x) use ($filters) {
                $sumberId = $filters['sumber_order_id'];
                $childIds = DB::table('sumber_orders')->where('parent_id', $sumberId)->pluck('id')->toArray();
                if (!empty($childIds)) {
                    $x->whereIn('orders.sumber_order_id', array_merge([$sumberId], $childIds));
                } else {
                    $x->where('orders.sumber_order_id', $sumberId);
                }
            })
            ->when(! empty($filters['region']), function ($q) use ($filters) {
                $term = "%{$filters['region']}%";
                $q->where(function ($w) use ($term) {
                    $w->where('customers.provinsi_nama', 'like', $term)
                      ->orWhere('customers.kabupaten_nama', 'like', $term)
                      ->orWhere('customers.kecamatan_nama', 'like', $term)
                      ->orWhere('customers.desa_nama', 'like', $term);
                });
            })
            ->when(! empty($filters['product_id']), function ($q) use ($filters) {
                $q->whereExists(function ($w) use ($filters) {
                    $w->select(DB::raw(1))
                      ->from('order_items')
                      ->whereColumn('order_items.order_id', 'orders.id')
                      ->where('order_items.product_id', $filters['product_id']);
                });
            })
            ->select(
                DB::raw('COALESCE(CASE WHEN parent_sumber.nama IS NOT NULL THEN CONCAT(parent_sumber.nama, " — ", sumber_orders.nama) ELSE sumber_orders.nama END, "— Tanpa Sumber —") as sumber_order'),
                DB::raw('COALESCE(customer_types.nama, "— Tanpa Kategori —") as kategori_pelanggan'),
                DB::raw('COUNT(DISTINCT orders.id) as total_order'),
                DB::raw('COALESCE(SUM(items_sum.qty), 0) as total_qty'),
                DB::raw('SUM(orders.total_tagihan) as total_value')
            )
            ->groupBy('sumber_order', 'kategori_pelanggan')
            ->orderByDesc('total_value')
            ->get();

        $totalOmset = $rawRows->sum('total_value');

        $rows = $rawRows->map(fn ($r) => [
            'sumber_order' => $r->sumber_order,
            'kategori_pelanggan' => $r->kategori_pelanggan,
            'total_order' => (int) $r->total_order,
            'total_qty' => (int) $r->total_qty,
            'total_value' => (float) $r->total_value,
            'percentage' => $totalOmset > 0 ? round(($r->total_value / $totalOmset) * 100, 1) : 0,
        ])->all();

        return [
            'rows' => $rows,
            'summary' => [
                ['label' => 'Total Kombinasi', 'value' => count($rows)],
                ['label' => 'Total Qty Terjual', 'value' => array_sum(array_column($rows, 'total_qty'))],
                ['label' => 'Total Order', 'value' => array_sum(array_column($rows, 'total_order'))],
                ['label' => 'Total Omset', 'value' => $totalOmset, 'format' => 'currency'],
            ],
        ];
    }

    private function crmChurn(string|array|null $brandId, array $filters): array
    {
        $customers = DB::table('customers')
            ->join('orders', 'orders.pelanggan_id', '=', 'customers.id')
            ->where('orders.status_po', '!=', 'draft')
            ->when($brandId && $brandId !== 'all', function ($q) use ($brandId) {
                if (is_array($brandId)) {
                    $q->whereIn('orders.brand_id', $brandId);
                } else {
                    $q->where('customers.brand_id', $brandId);
                }
            })
            ->select('customers.id', 'customers.kode', 'customers.nama', 'customers.nomor_hp', 'orders.tanggal_masuk', 'orders.total_tagihan')
            ->orderBy('customers.id')
            ->orderBy('orders.tanggal_masuk')
            ->get();

        $grouped = $customers->groupBy('id');
        $rows = [];
        $warningCount = 0;
        $highRiskCount = 0;
        $totalLoss = 0;

        foreach ($grouped as $customerId => $customerOrders) {
            $first = $customerOrders->first();
            $totalOrder = $customerOrders->count();
            $totalValue = $customerOrders->sum('total_tagihan');
            $aov = $totalOrder > 0 ? ($totalValue / $totalOrder) : 0;
            
            // Extract dates
            $dates = $customerOrders->pluck('tanggal_masuk')->map(fn($d) => Carbon::parse($d))->all();
            
            // Calculate intervals
            $intervals = [];
            for ($i = 1; $i < count($dates); $i++) {
                $intervals[] = $dates[$i-1]->diffInDays($dates[$i]);
            }
            
            $aoi = count($intervals) > 0 ? (array_sum($intervals) / count($intervals)) : 30; // fallback 30 hari
            $aoi = max(1, $aoi);
            
            $lastOrderDate = end($dates);
            $recency = $lastOrderDate->diffInDays(Carbon::now());
            
            // Churn Risk Assessment
            if ($recency <= $aoi * 1.5) {
                $riskLevel = 'Safe';
            } elseif ($recency <= $aoi * 2.5) {
                $riskLevel = 'Warning';
                $warningCount++;
            } else {
                $riskLevel = 'High Risk';
                $highRiskCount++;
            }
            
            $monetaryLoss = ($riskLevel === 'Safe') ? 0 : $aov;
            if ($riskLevel !== 'Safe') {
                $totalLoss += $monetaryLoss;
            }
            
            $nextOrderPred = $lastOrderDate->copy()->addDays((int) round($aoi));
            
            $rows[] = [
                'kode' => $first->kode,
                'nama' => $first->nama,
                'nomor_hp' => $first->nomor_hp,
                'total_order' => $totalOrder,
                'avg_interval' => $aoi,
                'avg_interval_text' => round($aoi) . ' hari',
                'recency_days' => $recency,
                'next_order_pred' => $nextOrderPred->toDateString(),
                'risk_level' => $riskLevel,
                'monetary_loss' => $monetaryLoss,
                'whatsapp_action' => [
                    'nama' => $first->nama,
                    'nomor_hp' => $first->nomor_hp,
                    'recency' => $recency,
                    'aoi' => (int) round($aoi),
                ]
            ];
        }

        // Sort rows by risk severity: High Risk first, then Warning, then Safe
        usort($rows, function ($a, $b) {
            $riskScore = ['High Risk' => 3, 'Warning' => 2, 'Safe' => 1];
            $scoreA = $riskScore[$a['risk_level']] ?? 0;
            $scoreB = $riskScore[$b['risk_level']] ?? 0;
            if ($scoreA === $scoreB) {
                return $b['recency_days'] <=> $a['recency_days']; // show longer elapsed first
            }
            return $scoreB <=> $scoreA;
        });

        return [
            'rows' => $rows,
            'summary' => [
                ['label' => 'Total Pelanggan Teranalisa', 'value' => count($rows)],
                ['label' => 'Pelanggan Waspada (Mulai Berisiko)', 'value' => $warningCount],
                ['label' => 'Pelanggan Risiko Tinggi (Hampir Pasti Churn)', 'value' => $highRiskCount],
                ['label' => 'Total Potensi Omset Hilang', 'value' => $totalLoss, 'format' => 'currency'],
            ],
        ];
    }

    private function crmSeasonal(string|array|null $brandId, array $filters): array
    {
        $targetMonth = Carbon::now()->month;

        $results = DB::table('customers')
            ->join('orders', 'orders.pelanggan_id', '=', 'customers.id')
            ->where('orders.status_po', '!=', 'draft')
            ->where(function ($q) use ($targetMonth) {
                $currentYear = Carbon::now()->year;
                // Generate sargable ranges for the past years (e.g. back to 2018)
                for ($y = $currentYear - 1; $y >= 2018; $y--) {
                    $start = Carbon::create($y, $targetMonth, 1)->startOfDay()->toDateTimeString();
                    $end = Carbon::create($y, $targetMonth, 1)->endOfMonth()->toDateTimeString();
                    $q->orWhereBetween('orders.tanggal_masuk', [$start, $end]);
                }
            })
            ->when($brandId && $brandId !== 'all', function ($q) use ($brandId) {
                if (is_array($brandId)) {
                    $q->whereIn('orders.brand_id', $brandId);
                } else {
                    $q->where('customers.brand_id', $brandId);
                }
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('orders as active_orders')
                    ->whereColumn('active_orders.pelanggan_id', 'customers.id')
                    ->where('active_orders.status_po', '!=', 'draft')
                    ->where('active_orders.tanggal_masuk', '>=', Carbon::now()->subDays(60)->toDateString());
            })
            ->select(
                'customers.id',
                'customers.kode',
                'customers.nama',
                'customers.nomor_hp',
                'orders.no_po',
                'orders.tanggal_masuk',
                'orders.total_tagihan'
            )
            ->orderByDesc('orders.tanggal_masuk')
            ->get();

        $grouped = $results->groupBy('id');
        $rows = [];
        $totalLoss = 0;

        foreach ($grouped as $customerId => $customerOrders) {
            $latestPastOrder = $customerOrders->first();
            $totalLoss += (float) $latestPastOrder->total_tagihan;

            $rows[] = [
                'kode' => $latestPastOrder->kode,
                'nama' => $latestPastOrder->nama,
                'order_tahun_lalu' => $latestPastOrder->no_po,
                'tanggal_order_lalu' => $latestPastOrder->tanggal_masuk,
                'nilai_order_lalu' => (float) $latestPastOrder->total_tagihan,
                'whatsapp_action' => [
                    'type' => 'seasonal',
                    'nama' => $latestPastOrder->nama,
                    'nomor_hp' => $latestPastOrder->nomor_hp,
                    'order_tahun_lalu' => $latestPastOrder->no_po,
                    'tanggal_order_lalu' => $latestPastOrder->tanggal_masuk,
                ]
            ];
        }

        // Sort by value descending
        usort($rows, function ($a, $b) {
            return $b['nilai_order_lalu'] <=> $a['nilai_order_lalu'];
        });

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $targetMonthName = $monthNames[$targetMonth] ?? Carbon::now()->format('F');

        return [
            'rows' => $rows,
            'summary' => [
                ['label' => 'Bulan Target', 'value' => $targetMonthName],
                ['label' => 'Pelanggan Terdeteksi', 'value' => count($rows)],
                ['label' => 'Total Potensi Omset Repeat Order', 'value' => $totalLoss, 'format' => 'currency'],
            ],
        ];
    }

    private function arusKasBank(string|array|null $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $bankIds = $filters['bank_ids'] ?? [];
        if (empty($bankIds)) {
            // Find all active bank accounts for these brand(s)
            $bankIds = \App\Models\Master\BankAccount::query()
                ->active()
                ->when($brandId, function($q) use ($brandId) {
                    return is_array($brandId)
                        ? $q->whereIn('brand_id', $brandId)
                        : $q->where('brand_id', $brandId);
                })
                ->pluck('id')
                ->toArray();
        }

        if (empty($bankIds)) {
            return [
                'rows' => [],
                'summary' => [
                    ['label' => 'Total Transaksi', 'value' => 0],
                    ['label' => 'Total Debit (Masuk)', 'value' => 0, 'format' => 'currency'],
                    ['label' => 'Total Kredit (Keluar)', 'value' => 0, 'format' => 'currency'],
                    ['label' => 'Saldo Bersih', 'value' => 0, 'format' => 'currency'],
                ],
            ];
        }

        $rawPayments = OrderPayment::query()
            ->whereIn('bank_id', $bankIds)
            ->whereBetween('payment_date', [$from, $to])
            ->with(['order:id,no_po,nama_po,pelanggan_id', 'order.pelanggan:id,nama', 'bank:id,brand_id,bank,nomor_rekening,atas_nama', 'bank.brand:id,nama_brand', 'masterJenisPembayaran:id,nama'])
            ->get();

        $sortedGroups = $this->sortAndGroupPayments($rawPayments);

        $payments = collect();
        foreach ($sortedGroups as $groupData) {
            foreach ($groupData['payments'] as $p) {
                $payments->push($p);
            }
        }

        $rows = $payments->map(fn ($p) => $this->formatArusKasPayment($p))->all();

        $totalDebit = array_sum(array_column($rows, 'debit'));
        $totalKredit = array_sum(array_column($rows, 'kredit'));
        $saldoBersih = $totalDebit - $totalKredit;

        return [
            'rows' => $rows,
            'summary' => [
                ['label' => 'Total Transaksi', 'value' => count($rows)],
                ['label' => 'Total Debit (Masuk)', 'value' => $totalDebit, 'format' => 'currency'],
                ['label' => 'Total Kredit (Keluar)', 'value' => $totalKredit, 'format' => 'currency'],
                ['label' => 'Saldo Bersih', 'value' => $saldoBersih, 'format' => 'currency'],
            ],
        ];
    }

    private function formatArusKasPayment(OrderPayment $p): array
    {
        $debit = $p->is_debit ? (float) $p->amount : 0.0;
        $kredit = !$p->is_debit ? (float) $p->amount : 0.0;

        $statusText = $p->verified_at ? 'verified' : 'pending';

        $tipeLabel = $p->masterJenisPembayaran?->nama ?? $p->payment_type;
        $tipeLabelClean = match(strtolower($tipeLabel)) {
            'dp' => 'DP ' . ($p->dp_sequence ?? 1),
            'pelunasan' => 'Pelunasan',
            'ongkir' => 'Ongkir',
            'tambahan_produk' => 'Tambahan Produk',
            'cashback' => 'Cashback',
            'return' => 'Refund',
            'refurn' => 'Refund',
            'refund' => 'Refund',
            default => ucfirst($tipeLabel)
        };

        $docNumber = $p->order?->no_po ?? '-';
        if ($p->payment_type === 'return') {
            $refund = Refund::where('order_id', $p->order_id)
                ->where('nominal_refund', $p->amount)
                ->first();
            if ($refund) {
                $docNumber = "{$p->order?->no_po} / {$refund->refund_number}";
            } else if ($p->notes && preg_match('/(REF-[A-Z0-9-]+)/i', $p->notes, $matches)) {
                $docNumber = "{$p->order?->no_po} / {$matches[1]}";
            }
        } elseif ($p->payment_type === 'cashback') {
            if ($p->order?->no_po) {
                $cbNumber = str_replace('PO-', 'CB-', $p->order->no_po);
                $docNumber = "{$p->order->no_po} / {$cbNumber}";
            }
        }

        return [
            'tanggal' => $p->payment_date ? Carbon::parse($p->payment_date)->toDateString() : null,
            'bank_info' => $p->bank ? "{$p->bank->bank} - {$p->bank->nomor_rekening} (A.N. {$p->bank->atas_nama})" : 'CASH',
            'brand_name' => $p->bank?->brand?->nama_brand ?? 'General',
            'no_po' => $docNumber,
            'pelanggan' => $p->order?->pelanggan?->nama ?? '-',
            'tipe' => $tipeLabelClean,
            'debit' => $debit,
            'kredit' => $kredit,
            'status' => $statusText,
            'notes' => $p->notes ?? '-',
        ];
    }

    private function sortAndGroupPayments(\Illuminate\Support\Collection $rawPayments): \Illuminate\Support\Collection
    {
        $grouped = $rawPayments->groupBy(function ($p) {
            return $p->order_id ?? ('no_order_' . $p->id);
        });

        $sortedGroups = $grouped->map(function ($group) {
            $sortedPayments = $group->sortBy(function ($p) {
                $datePart = $p->payment_date ? Carbon::parse($p->payment_date)->toDateString() : '0000-00-00';
                $timePart = $p->created_at ? $p->created_at->toDateTimeString() : '00:00:00';
                return $datePart . '_' . $timePart;
            })->values();

            $latestPayment = $group->sortByDesc(function ($p) {
                $datePart = $p->payment_date ? Carbon::parse($p->payment_date)->toDateString() : '0000-00-00';
                $timePart = $p->created_at ? $p->created_at->toDateTimeString() : '00:00:00';
                return $datePart . '_' . $timePart;
            })->first();

            $sortKey = ($latestPayment->payment_date ? Carbon::parse($latestPayment->payment_date)->toDateString() : '0000-00-00') . '_' . 
                       ($latestPayment->created_at ? $latestPayment->created_at->toDateTimeString() : '0000-00-00 00:00:00');

            return [
                'payments' => $sortedPayments,
                'sort_key' => $sortKey,
            ];
        });

        return $sortedGroups->sortByDesc('sort_key')->values();
    }

    private function formatDuration(mixed $started, mixed $completed, bool $isActive = false): string
    {
        if (!$started) {
            return '-';
        }
        $start = Carbon::parse($started);
        $end = $completed ? Carbon::parse($completed) : Carbon::now();
        
        $diffInHours = $start->diffInHours($end);
        
        if ($diffInHours < 1) {
            return '< 1 jam' . ($isActive ? ' (Aktif)' : '');
        } elseif ($diffInHours < 24) {
            return $diffInHours . ' jam' . ($isActive ? ' (Aktif)' : '');
        } else {
            $days = round($diffInHours / 24, 1);
            return $days . ' hari' . ($isActive ? ' (Aktif)' : '');
        }
    }

    private function kinerjaProduksi(string|array|null $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $orders = Order::query()
            ->when($brandId, $this->bf($brandId))
            ->whereBetween('tanggal_masuk', [$from, $to])
            ->where('status_po', '!=', 'draft')
            ->with(['brand:id,nama_brand', 'pelanggan:id,nama', 'progressDetails.progress'])
            ->withSum(['items' => fn($query) => $query->where('is_addon', false)], 'quantity')
            ->orderByDesc('tanggal_masuk')
            ->get();

        $progresses = \App\Models\Master\Progress::active()->ordered()->get();

        $rows = $orders->map(function ($o) use ($progresses) {
            // 1. Calculate overall lateness
            $lateness = '-';
            $isCompleted = in_array($o->status_po, ['selesai_produksi', 'siap_dikirim', 'sudah_dikirim']);
            
            if ($o->deadline_customer) {
                $deadline = Carbon::parse($o->deadline_customer)->startOfDay();
                
                $completionDate = null;
                if ($isCompleted) {
                    if ($o->end_production_date) {
                        $completionDate = Carbon::parse($o->end_production_date)->startOfDay();
                    } else {
                        $lastCompleted = $o->progressDetails
                            ->where('status', 'selesai')
                            ->sortByDesc('completed_at')
                            ->first();
                        $completionDate = $lastCompleted && $lastCompleted->completed_at 
                            ? Carbon::parse($lastCompleted->completed_at)->startOfDay()
                            : Carbon::now()->startOfDay();
                    }
                } else {
                    $completionDate = Carbon::now()->startOfDay();
                }

                $diffDays = $deadline->diffInDays($completionDate, false);
                
                if ($diffDays > 0) {
                    $lateness = 'Telat ' . $diffDays . ' hari';
                } else {
                    if ($isCompleted) {
                        $lateness = 'Tepat Waktu';
                    } else {
                        $lateness = 'Sisa ' . abs($diffDays) . ' hari';
                    }
                }
            }

            // 2. Calculate total production duration
            $durasiTotal = '-';
            $firstStage = $o->progressDetails->sortBy(fn($d) => $d->progress?->urutan ?? 0)->first();
            $productionStart = $o->start_production_date 
                ? Carbon::parse($o->start_production_date) 
                : ($firstStage && $firstStage->started_at ? Carbon::parse($firstStage->started_at) : null);

            if ($productionStart) {
                $productionEnd = null;
                if ($isCompleted) {
                    $productionEnd = $o->end_production_date 
                        ? Carbon::parse($o->end_production_date) 
                        : ($o->progressDetails->where('status', 'selesai')->sortByDesc('completed_at')->first()?->completed_at 
                            ? Carbon::parse($o->progressDetails->where('status', 'selesai')->sortByDesc('completed_at')->first()->completed_at)
                            : Carbon::now());
                } else {
                    $productionEnd = Carbon::now();
                }
                
                $diffInHours = $productionStart->diffInHours($productionEnd);
                if ($diffInHours < 24) {
                    $durasiTotal = $diffInHours . ' jam';
                } else {
                    $durasiTotal = round($diffInHours / 24, 1) . ' hari';
                }
            }

            $row = [
                'no_po' => $o->no_po,
                'nama_po' => $o->nama_po,
                'brand_nama' => $o->brand?->nama_brand ?? '-',
                'pelanggan' => $o->pelanggan?->nama ?? '-',
                'tanggal_masuk' => $o->tanggal_masuk?->toDateString(),
                'deadline' => $o->deadline_customer?->toDateString(),
                'pcs' => (int) ($o->items_sum_quantity ?? 0),
                'status' => $o->status_po,
                'keterlambatan' => $lateness,
                'durasi_total' => $durasiTotal,
            ];

            foreach ($progresses as $index => $p) {
                $detail = $o->progressDetails->firstWhere('progress_id', $p->id);
                $key = 'progress_' . strtolower(str_replace(' ', '_', $p->nama_progress));
                
                if ($detail) {
                    // Estimate started_at if null to prevent displaying '-' for completed/active stages
                    $estimatedStart = $detail->started_at;
                    if (!$estimatedStart && in_array($detail->status, ['selesai', 'on_progress'], true)) {
                        // Search backward to find completion of previous stage
                        for ($i = $index - 1; $i >= 0; $i--) {
                            $prevProgress = $progresses[$i];
                            $prevDetail = $o->progressDetails->firstWhere('progress_id', $prevProgress->id);
                            if ($prevDetail) {
                                if ($prevDetail->status === 'selesai' && $prevDetail->completed_at) {
                                    $estimatedStart = $prevDetail->completed_at;
                                    break;
                                }
                                if ($prevDetail->status === 'skipped' && $prevDetail->updated_at) {
                                    $estimatedStart = $prevDetail->updated_at;
                                    break;
                                }
                            }
                        }
                        if (!$estimatedStart) {
                            $estimatedStart = $o->published_at ?? $o->tanggal_masuk;
                        }
                    }

                    if ($detail->status === 'selesai') {
                        $row[$key] = $this->formatDuration($estimatedStart, $detail->completed_at);
                    } elseif ($detail->status === 'skipped') {
                        $row[$key] = 'Dilewati';
                    } elseif ($detail->status === 'on_progress') {
                        $row[$key] = $this->formatDuration($estimatedStart, null, true);
                    } else {
                        $row[$key] = '-';
                    }
                } else {
                    $row[$key] = '-';
                }
            }

            return $row;
        })->all();

        // Summary Statistics
        $totalPo = count($rows);
        $totalPcs = array_sum(array_column($rows, 'pcs'));
        
        $latePoCount = 0;
        $totalLateDays = 0;
        foreach ($rows as $r) {
            if (str_starts_with($r['keterlambatan'], 'Telat')) {
                $latePoCount++;
                $parts = explode(' ', $r['keterlambatan']);
                if (isset($parts[1])) {
                    $totalLateDays += (int)$parts[1];
                }
            }
        }
        $avgLateness = $latePoCount > 0 ? round($totalLateDays / $latePoCount, 1) . ' hari' : '0 hari';

        $completedWithDuration = collect($rows)->filter(function($r) {
            $isCompleted = in_array($r['status'], ['selesai_produksi', 'siap_dikirim', 'sudah_dikirim']);
            return $isCompleted && str_contains($r['durasi_total'], 'hari');
        });
        
        $totalCompletedDays = 0;
        foreach ($completedWithDuration as $r) {
            $parts = explode(' ', $r['durasi_total']);
            if (isset($parts[0])) {
                $totalCompletedDays += (float)$parts[0];
            }
        }
        $avgDuration = $completedWithDuration->count() > 0 
            ? round($totalCompletedDays / $completedWithDuration->count(), 1) . ' hari' 
            : '0 hari';

        return [
            'rows' => $rows,
            'summary' => [
                ['label' => 'Total PO', 'value' => $totalPo],
                ['label' => 'Total PCS', 'value' => $totalPcs, 'format' => 'number'],
                ['label' => 'Total PO Telat', 'value' => $latePoCount],
                ['label' => 'Rata-rata Keterlambatan', 'value' => $avgLateness],
                ['label' => 'Rata-rata Waktu Produksi', 'value' => $avgDuration],
            ],
        ];
    }

    private function jenisPo(string|array|null $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $statusFilter = $filters['status'] ?? null;
        $jenisPoFilter = $filters['jenis_po'] ?? null;

        $q = Order::query()
            ->when($brandId, $this->bf($brandId))
            ->whereBetween('tanggal_masuk', [$from, $to])
            ->where('status_po', '!=', 'draft')
            ->when($statusFilter, fn($query) => $query->where('status_po', $statusFilter))
            ->when($jenisPoFilter, function($query) use ($jenisPoFilter) {
                if ($jenisPoFilter === 'normal') {
                    $query->where('is_special_order', false)->where('is_reseller_price', false);
                } elseif ($jenisPoFilter === 'special_order') {
                    $query->where('is_special_order', true);
                } elseif ($jenisPoFilter === 'reseller_price') {
                    $query->where('is_reseller_price', true);
                } elseif ($jenisPoFilter === 'repeat_order') {
                    $query->where('is_repeat_order', true);
                }
            })
            ->with(['brand:id,nama_brand', 'pelanggan:id,nama'])
            ->withSum(['items' => fn($query) => $query->where('is_addon', false)], 'quantity');

        $orders = $q->orderByDesc('tanggal_masuk')->get();

        $rows = $orders->map(function ($o) {
            $jenisPo = 'Normal';
            if ($o->is_special_order) {
                $jenisPo = 'Special Order';
            } elseif ($o->is_reseller_price) {
                $jenisPo = 'Reseller Price';
            }

            return [
                'no_po' => $o->no_po,
                'nama_po' => $o->nama_po,
                'brand_nama' => $o->brand?->nama_brand ?? '-',
                'pelanggan' => $o->pelanggan?->nama ?? '-',
                'tanggal_masuk' => $o->tanggal_masuk?->toDateString(),
                'jenis_po' => $jenisPo,
                'pcs' => (int) ($o->items_sum_quantity ?? 0),
                'total_tagihan' => (float) $o->total_tagihan,
                'status' => $o->status_po,
            ];
        })->all();

        $normalCount = 0;
        $specialCount = 0;
        $resellerCount = 0;
        $totalVal = 0;

        foreach ($rows as $r) {
            if ($r['jenis_po'] === 'Special Order') {
                $specialCount++;
            } elseif ($r['jenis_po'] === 'Reseller Price') {
                $resellerCount++;
            } else {
                $normalCount++;
            }
            $totalVal += $r['total_tagihan'];
        }

        return [
            'rows' => $rows,
            'summary' => [
                ['label' => 'Total PO Normal', 'value' => $normalCount],
                ['label' => 'Total PO Special Order', 'value' => $specialCount],
                ['label' => 'Total PO Harga Reseller', 'value' => $resellerCount],
                ['label' => 'Total Nilai PO', 'value' => $totalVal, 'format' => 'currency'],
            ],
        ];
    }
}
