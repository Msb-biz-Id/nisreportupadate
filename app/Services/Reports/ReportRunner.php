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
            'kategori' => $this->kategori($brandId, $filters),
            'status-po' => $this->statusPo($brandId, $filters),
            'monitoring-deadline' => $this->monitoringDeadline($brandId, $filters),
            'rijek' => $this->rijek($brandId, $filters),
            'refund' => $this->refund($brandId, $filters),
            'pemasukan' => $this->pemasukan($brandId, $filters),
            'pengeluaran' => $this->pengeluaran($brandId, $filters),
            'analisis-marketing' => $this->analisisMarketing($brandId, $filters),
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
            ->leftJoin(DB::raw('(SELECT order_id, SUM(quantity) as qty FROM order_items GROUP BY order_id) as items_sum'), 'items_sum.order_id', '=', 'orders.id')
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

    private function kategori(string|array|null $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $rows = DB::table('orders')
            ->leftJoin('kategori_orders', 'kategori_orders.id', '=', 'orders.kategori_order_id')
            ->leftJoin(DB::raw('(SELECT order_id, SUM(quantity) as qty FROM order_items GROUP BY order_id) as items_sum'), 'items_sum.order_id', '=', 'orders.id')
            ->when($brandId, $this->obf($brandId))
            ->whereBetween('orders.tanggal_masuk', [$from, $to])
            ->where('orders.status_po', '!=', 'draft')
            ->select(
                DB::raw("COALESCE(kategori_orders.nama, '— Tanpa Kategori —') as kategori"),
                DB::raw('COUNT(DISTINCT orders.id) as total_order'),
                DB::raw('COALESCE(SUM(items_sum.qty), 0) as total_qty'),
                DB::raw('SUM(orders.total_tagihan) as total_value'),
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

    private function statusPo(string|array|null $brandId, array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $q = Order::query()
            ->when($brandId, $this->bf($brandId))
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

    private function monitoringDeadline(string|array|null $brandId, array $filters): array
    {
        $threshold = (int) ($filters['threshold'] ?? 7);

        $orders = Order::query()
            ->when($brandId, $this->bf($brandId))
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
            ->select('order_id', \Illuminate\Support\Facades\DB::raw('SUM(jumlah) as total'))
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
            ->select('progress_id', \Illuminate\Support\Facades\DB::raw('SUM(jumlah) as total'))
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
                'tanggal' => $p->tanggal?->toDateString(),
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

        // 2. Fetch negative verified payments (cashback/return)
        $negativePayments = OrderPayment::query()
            ->join('orders', 'orders.id', '=', 'order_payments.order_id')
            ->whereNotNull('order_payments.verified_at')
            ->where('order_payments.is_debit', false)
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
                    'return'   => 'Refund/Return',
                    default    => 'Pengeluaran Lainnya',
                };
                return [
                    'tanggal' => $p->tanggal?->toDateString(),
                    'kategori' => $p->payment_type === 'cashback' ? 'Cashback PO' : 'Refund/Return PO',
                    'keterangan' => "{$label} PO {$p->no_po} — {$p->nama_po}",
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
            ->leftJoin(DB::raw('(SELECT order_id, SUM(quantity) as qty FROM order_items ' .
                (!empty($filters['product_id']) ? 'WHERE product_id = ' . DB::connection()->getPdo()->quote($filters['product_id']) : '') .
                ' GROUP BY order_id) as items_sum'), 'items_sum.order_id', '=', 'orders.id')
            ->whereBetween('orders.tanggal_masuk', [$from, $to])
            ->where('orders.status_po', '!=', 'draft')
            ->when($targetBrandId && $targetBrandId !== 'all', $this->obf($targetBrandId))
            ->when(! empty($filters['customer_type_id']), fn ($x) => $x->where('customers.type_pelanggan_id', $filters['customer_type_id']))
            ->when(! empty($filters['sumber_order_id']), fn ($x) => $x->where('orders.sumber_order_id', $filters['sumber_order_id']))
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
                DB::raw('COALESCE(sumber_orders.nama, "— Tanpa Sumber —") as sumber_order'),
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
}
