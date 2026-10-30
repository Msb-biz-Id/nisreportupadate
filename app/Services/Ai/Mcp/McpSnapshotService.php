<?php

namespace App\Services\Ai\Mcp;

use App\Models\Ai\AiSnapshotCache;
use App\Models\Brand;
use App\Models\Master\Customer;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\Progress;
use App\Services\CacheService;
use App\Services\DashboardService;
use App\Services\Reports\ReportMessageBuilder;
use App\Services\Reports\ReportRunner;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * McpSnapshotService — Pre-aggregated token-dense data engine for AI clients.
 * Optimized specifically for shared hosting (zero-N+1, low memory, fast caching).
 */
class McpSnapshotService
{
    private const CACHE_TTL_SHORT = 180;  // 3 minutes
    private const CACHE_TTL_MED = 600;    // 10 minutes

    /**
     * Compute apparel pieces (pcs) accurately accounting for jml_atasan
     */
    public static function countPcs(Order $order): int
    {
        $coreItems = $order->items->filter(fn($i) => empty($i->is_addon));
        $hasAnyJmlAtasan = $coreItems->contains(fn($i) => $i->jml_atasan !== null && $i->jml_atasan !== '');
        return (int) $coreItems->sum(function ($i) use ($hasAnyJmlAtasan) {
            if ($i->jml_atasan !== null && $i->jml_atasan !== '') {
                return (int) $i->jml_atasan;
            }
            return $hasAnyJmlAtasan ? 0 : (int) $i->quantity;
        });
    }

    /**
     * 1. Get Active Orders Summary (Token-Dense Format)
     */
    public static function getActiveOrders(?string $brandId = null, int $limit = 25): array
    {
        $scopeKey = ($brandId ?: 'all') . "_lim{$limit}";

        return CacheService::rememberOrder("mcp_active_orders_{$scopeKey}", function () use ($brandId, $limit) {
            $orders = Order::query()
                ->select(['id', 'no_po', 'nama_po', 'brand_id', 'pelanggan_id', 'status_po', 'deadline_customer', 'total_amount'])
                ->whereIn('status_po', ['published', 'on_progress', 'siap_dikirim'])
                ->when($brandId, fn($q) => $q->where('brand_id', $brandId))
                ->with([
                    'pelanggan:id,nama,nomor_hp',
                    'brand:id,kode',
                    'items:id,order_id,is_addon,quantity,jml_atasan',
                    'payments:id,order_id,amount,verified_at',
                ])
                ->orderBy('deadline_customer', 'asc')
                ->take($limit)
                ->get();

            $totalActive = Order::whereIn('status_po', ['published', 'on_progress', 'siap_dikirim'])
                ->when($brandId, fn($q) => $q->where('brand_id', $brandId))
                ->count();

            $items = $orders->map(function ($o) {
                $pcs = self::countPcs($o);
                $sisa = $o->sisaTagihan();
                return [
                    'po'      => $o->no_po,
                    'nama'    => $o->nama_po ?: '-',
                    'cust'    => $o->pelanggan?->nama ?: '-',
                    'phone'   => $o->pelanggan?->nomor_hp ?: '',
                    'brand'   => $o->brand?->kode ?: '',
                    'status'  => $o->status_po,
                    'pcs'     => $pcs,
                    'dl'      => $o->deadline_customer?->format('Y-m-d') ?: '-',
                    'sisa_rp' => $sisa,
                ];
            })->all();

            return [
                'total_active_orders' => $totalActive,
                'displayed_orders'    => count($items),
                'orders'              => $items,
            ];
        }, self::CACHE_TTL_SHORT);
    }

    /**
     * 2. Get Single Order Detail
     */
    public static function getOrderDetail(string $noPo): array
    {
        $order = Order::query()
            ->where('no_po', trim($noPo))
            ->with([
                'pelanggan',
                'brand:id,kode,nama_brand',
                'items.bahanKain',
                'items.bahanKainBawahan',
                'items.namesets',
                'payments.masterJenisPembayaran',
                'progressDetails.progress',
                'ekspedisi',
            ])
            ->first();

        if (!$order) {
            return ['error' => "Pesanan dengan No. PO '{$noPo}' tidak ditemukan."];
        }

        $pcs = self::countPcs($order);
        $totalTagihan = $order->totalTagihan();
        $totalPaid = $order->totalPaid();
        $sisaTagihan = $order->sisaTagihan();

        $itemsSummary = $order->items->map(function ($i) {
            return [
                'produk'     => $i->nama_produk,
                'is_addon'   => (bool) $i->is_addon,
                'qty'        => (int) ($i->jml_atasan ?: $i->quantity),
                'bahan'      => $i->bahan_formatted ?: ($i->bahanKain?->nama ?: '-'),
                'harga'      => (float) $i->harga_satuan,
                'subtotal'   => (float) $i->subtotal,
                'namesets'   => $i->namesets->map(fn($n) => [
                    'nama'   => $n->nama,
                    'nomor'  => $n->nomor,
                    'ukuran' => $n->ukuran,
                ])->all(),
            ];
        })->all();

        $timeline = $order->progressDetails->map(function ($p) {
            return [
                'pos'          => $p->progress?->nama_progress ?: '-',
                'status'       => $p->status,
                'catatan'      => $p->catatan,
                'updated_at'   => $p->updated_at?->format('d/m/Y H:i'),
            ];
        })->all();

        $payments = $order->payments->map(function ($pay) {
            return [
                'nominal' => (float) $pay->amount,
                'metode'  => $pay->masterJenisPembayaran?->nama ?: ($pay->payment_type ?: '-'),
                'status'  => $pay->verified_at ? 'Terverifikasi' : 'Menunggu Verifikasi',
                'tanggal' => $pay->payment_date?->format('d/m/Y') ?: $pay->created_at?->format('d/m/Y'),
            ];
        })->all();

        $shipping = [
            'ekspedisi'      => $order->nama_ekspedisi ?: ($order->ekspedisi?->nama ?: '-'),
            'no_resi'        => $order->no_resi ?: '-',
            'tipe'           => $order->tipe_pengiriman ?: '-',
            'ongkir'         => (float) $order->ongkir,
            'is_free_ongkir' => (bool) $order->is_free_ongkir,
        ];

        return [
            'no_po'          => $order->no_po,
            'nama_po'        => $order->nama_po,
            'brand'          => $order->brand?->nama_brand ?: $order->brand?->kode,
            'status'         => $order->status_po,
            'total_pcs'      => $pcs,
            'pelanggan'      => [
                'nama'    => $order->pelanggan?->nama ?: '-',
                'telepon' => $order->pelanggan?->nomor_hp ?: '-',
                'alamat'  => $order->pelanggan?->detail_alamat ?: '-',
            ],
            'jadwal'         => [
                'tgl_masuk' => $order->tanggal_masuk?->format('d/m/Y'),
                'deadline'  => $order->deadline_customer?->format('d/m/Y'),
            ],
            'keuangan'       => [
                'total_tagihan' => $totalTagihan,
                'total_dibayar' => $totalPaid,
                'sisa_tunggakan'=> $sisaTagihan,
                'is_lunas'      => $sisaTagihan <= 0.99,
                'riwayat_bayar' => $payments,
            ],
            'items'          => $itemsSummary,
            'progress_fisik' => $timeline,
            'pengiriman'     => $shipping,
        ];
    }

    /**
     * 3. Get Unpaid Reminders (Identical with Widget & Reports)
     */
    public static function getUnpaidReminders(?string $brandId = null, int $limit = 15): array
    {
        $dashboardService = app(DashboardService::class);
        $data = $dashboardService->reminderSisaPo($brandId, $limit);

        // Enhance with WhatsApp click-to-chat links
        $itemsWithWa = array_map(function ($o) {
            $phone = preg_replace('/\D/', '', $o['pelanggan_hp'] ?? '');
            if (str_starts_with($phone, '0')) {
                $phone = '62' . substr($phone, 1);
            } elseif (str_starts_with($phone, '8')) {
                $phone = '62' . $phone;
            }

            $pelanggan = $o['pelanggan'] ?: 'Pelanggan';
            $noPo = $o['no_po'] ?: '';
            $namaPo = !empty($o['nama_po']) ? " ({$o['nama_po']})" : '';
            $pcsInfo = !empty($o['total_pcs']) ? " [{$o['total_pcs']} Pcs]" : '';
            $brandName = $o['nama_brand'] ?: ($o['brand'] ?: 'Kami');
            $tagihan = 'Rp ' . number_format($o['total_tagihan'] ?: 0, 0, ',', '.');
            $paid = 'Rp ' . number_format($o['total_paid'] ?: 0, 0, ',', '.');
            $sisa = 'Rp ' . number_format($o['sisa_tagihan'] ?: 0, 0, ',', '.');

            $msg = "Halo Kak *{$pelanggan}*,\n\nInformasi dari *{$brandName}* perihal status pesanan No. PO *{$noPo}*{$namaPo}{$pcsInfo}:\n\n*Rincian Pembayaran*:\n- Total Tagihan: {$tagihan}\n- Sudah Dibayar: {$paid}\n- *Sisa Tunggakan*: *{$sisa}*\n\nMohon konfirmasi atau pelunasan sisa tagihan tersebut agar proses dan pengiriman pesanan Kakak dapat berjalan lancar. Terima kasih banyak! 🙏";

            $waLink = !empty($phone) ? "https://wa.me/{$phone}?text=" . rawurlencode($msg) : null;

            return array_merge($o, ['wa_link' => $waLink]);
        }, $data['items']);

        return [
            'total_po_belum_lunas'  => $data['total_po'],
            'total_pcs_belum_lunas' => $data['total_pcs'],
            'total_tunggakan_rp'    => $data['total_tunggakan'],
            'items'                 => $itemsWithWa,
        ];
    }

    /**
     * 4. Get Cashflow Summary (Pemasukan, Pengeluaran, Laba)
     */
    public static function getCashflowSummary(string $periode = 'bulanan', ?string $brandId = null): array
    {
        $runner = app(ReportRunner::class);
        $pemasukan = $runner->run('pemasukan', $brandId, ['periode' => $periode]);
        $pengeluaran = $runner->run('pengeluaran', $brandId, ['periode' => $periode]);

        $totalIn = 0.0;
        foreach ($pemasukan['summary'] ?? [] as $s) {
            if ($s['label'] === 'Total Pemasukan') $totalIn = (float) $s['value'];
        }

        $totalOut = 0.0;
        foreach ($pengeluaran['summary'] ?? [] as $s) {
            if ($s['label'] === 'Total Pengeluaran') $totalOut = (float) $s['value'];
        }

        return [
            'periode'           => $periode,
            'total_pemasukan'   => $totalIn,
            'total_pengeluaran' => $totalOut,
            'laba_bersih'       => $totalIn - $totalOut,
            'rincian_pemasukan' => array_slice($pemasukan['rows'] ?? [], 0, 10),
            'rincian_pengeluaran'=> array_slice($pengeluaran['rows'] ?? [], 0, 10),
        ];
    }

    /**
     * 5. Get Production Queue Breakdown by Stage
     */
    public static function getProductionQueue(?string $brandId = null): array
    {
        $orders = Order::query()
            ->whereIn('status_po', ['published', 'on_progress'])
            ->when($brandId, fn($q) => $q->where('brand_id', $brandId))
            ->with(['items:id,order_id,is_addon,quantity,jml_atasan'])
            ->get();

        $totalPcs = 0;
        foreach ($orders as $o) {
            $totalPcs += self::countPcs($o);
        }

        // Count per status
        $byStatus = Order::query()
            ->select('status_po', DB::raw('count(*) as count'))
            ->whereNotIn('status_po', ['draft', 'selesai', 'batal'])
            ->when($brandId, fn($q) => $q->where('brand_id', $brandId))
            ->groupBy('status_po')
            ->pluck('count', 'status_po');

        return [
            'total_order_antrean' => $orders->count(),
            'total_pcs_antrean'   => $totalPcs,
            'breakdown_status'    => $byStatus->all(),
        ];
    }

    /**
     * 6. Get Approaching Deadlines (< 3 or 7 days)
     */
    public static function getApproachingDeadlines(int $days = 3, ?string $brandId = null): array
    {
        $deadlines = Order::query()
            ->whereIn('status_po', ['published', 'on_progress'])
            ->whereBetween('deadline_customer', [now()->startOfDay(), now()->addDays($days)->endOfDay()])
            ->when($brandId, fn($q) => $q->where('brand_id', $brandId))
            ->with(['pelanggan:id,nama', 'brand:id,kode', 'items:id,order_id,is_addon,quantity,jml_atasan'])
            ->orderBy('deadline_customer', 'asc')
            ->get();

        $rows = $deadlines->map(function ($o) {
            $diff = (int) now()->startOfDay()->diffInDays($o->deadline_customer, false);
            return [
                'no_po'     => $o->no_po,
                'nama_po'   => $o->nama_po ?: '-',
                'pelanggan' => $o->pelanggan?->nama ?: '-',
                'brand'     => $o->brand?->kode ?: '',
                'pcs'       => self::countPcs($o),
                'deadline'  => $o->deadline_customer?->format('Y-m-d'),
                'sisa_hari' => $diff <= 0 ? "HARI INI (H-0)" : "H-{$diff}",
                'urgensi'   => $diff <= 1 ? 'CRITICAL' : ($diff <= 3 ? 'HIGH' : 'MEDIUM'),
            ];
        })->all();

        return [
            'total_approaching' => count($rows),
            'orders'            => $rows,
        ];
    }

    /**
     * 7. Get Delayed / Overdue Orders
     */
    public static function getDelayedOrders(?string $brandId = null): array
    {
        $delayed = Order::query()
            ->whereNotIn('status_po', ['draft', 'sudah_dikirim', 'selesai', 'batal'])
            ->where('deadline_customer', '<', today()->toDateString())
            ->when($brandId, fn($q) => $q->where('brand_id', $brandId))
            ->with(['pelanggan:id,nama', 'brand:id,kode', 'items:id,order_id,is_addon,quantity,jml_atasan'])
            ->orderBy('deadline_customer', 'asc')
            ->take(20)
            ->get();

        $rows = $delayed->map(function ($o) {
            $terlambatHari = abs((int) now()->startOfDay()->diffInDays($o->deadline_customer, false));
            return [
                'no_po'          => $o->no_po,
                'nama_po'        => $o->nama_po ?: '-',
                'pelanggan'      => $o->pelanggan?->nama ?: '-',
                'brand'          => $o->brand?->kode ?: '',
                'pcs'            => self::countPcs($o),
                'status'         => $o->status_po,
                'deadline'       => $o->deadline_customer?->format('Y-m-d'),
                'terlambat_hari' => $terlambatHari,
            ];
        })->all();

        return [
            'total_delayed' => count($rows),
            'orders'        => $rows,
        ];
    }

    /**
     * 8. Get Rijek / Defect Rate (30 Days)
     */
    public static function getDefectStats(?string $brandId = null): array
    {
        $runner = app(ReportRunner::class);
        return $runner->run('rijek', $brandId, [
            'from' => now()->subDays(30)->toDateString(),
            'to'   => now()->toDateString(),
        ]);
    }

    /**
     * 9. Search Customer
     */
    public static function searchCustomer(string $query): array
    {
        $q = trim($query);

        // Cek apakah query merujuk pada nama atau kode Brand tertentu
        $matchedBrand = Brand::where('kode', 'LIKE', $q)
            ->orWhere('nama_brand', 'LIKE', "%{$q}%")
            ->first();

        $customers = Customer::query()
            ->where(function ($w) use ($q, $matchedBrand) {
                $w->where('nama', 'LIKE', "%{$q}%")
                  ->orWhere('nomor_hp', 'LIKE', "%{$q}%")
                  ->orWhere('email', 'LIKE', "%{$q}%")
                  ->orWhere('kode', 'LIKE', "%{$q}%");

                if ($matchedBrand) {
                    $w->orWhere('brand_id', $matchedBrand->id)
                      ->orWhereHas('orders', fn($o) => $o->where('brand_id', $matchedBrand->id));
                } else {
                    $w->orWhereHas('brand', function ($b) use ($q) {
                        $b->where('nama_brand', 'LIKE', "%{$q}%")
                          ->orWhere('kode', 'LIKE', "%{$q}%");
                    });
                }
            })
            ->with(['brand:id,kode,nama_brand'])
            ->withCount('orders')
            ->orderByDesc('orders_count')
            ->take(20)
            ->get();

        return [
            'total_found' => $customers->count(),
            'brand_matched' => $matchedBrand?->nama_brand,
            'customers'   => $customers->map(fn($c) => [
                'id'              => $c->id,
                'kode'            => $c->kode,
                'nama'            => $c->nama,
                'nomor_hp'        => $c->nomor_hp,
                'brand'           => $c->brand?->nama_brand ?: '-',
                'email'           => $c->email,
                'alamat'          => $c->detail_alamat ?: '-',
                'total_order'     => $c->orders_count ?? $c->total_order,
                'total_transaksi' => (float) ($c->total_transaksi ?? 0),
            ])->all(),
        ];
    }

    /**
     * 10. Get Top Customers Ranking by Orders, Pcs, or Revenue
     */
    public static function getTopCustomers(?string $brand = null, string $by = 'orders', int $limit = 10): array
    {
        $brandModel = null;
        if (!empty($brand) && strtolower($brand) !== 'all') {
            $brandModel = Brand::where('id', $brand)
                ->orWhere('kode', 'LIKE', $brand)
                ->orWhere('nama_brand', 'LIKE', "%{$brand}%")
                ->first();
        }

        $brandId = $brandModel?->id;

        $customersQuery = Customer::query()
            ->when($brandId, function ($q) use ($brandId) {
                $q->where(function ($sub) use ($brandId) {
                    $sub->where('brand_id', $brandId)
                        ->orWhereHas('orders', fn($o) => $o->where('brand_id', $brandId));
                });
            })
            ->with(['brand:id,kode,nama_brand'])
            ->withCount(['orders' => function ($q) use ($brandId) {
                $q->when($brandId, fn($sq) => $sq->where('brand_id', $brandId))
                  ->where('status_po', '!=', 'draft');
            }]);

        $customers = $customersQuery->get();

        $ranked = $customers->map(function ($c) use ($brandId) {
            $ordersQuery = Order::query()
                ->where('pelanggan_id', $c->id)
                ->when($brandId, fn($oq) => $oq->where('brand_id', $brandId))
                ->where('status_po', '!=', 'draft')
                ->with(['items:id,order_id,is_addon,quantity,jml_atasan']);

            $orders = $ordersQuery->get();
            $totalPo = $orders->count();
            $totalNilai = (float) $orders->sum(fn($o) => $o->totalTagihan());
            $totalPcs = 0;
            foreach ($orders as $o) {
                $totalPcs += self::countPcs($o);
            }

            return [
                'id'              => $c->id,
                'kode'            => $c->kode,
                'nama'            => $c->nama,
                'nomor_hp'        => $c->nomor_hp ?: '-',
                'brand'           => $c->brand?->nama_brand ?: ($orders->first()?->brand?->nama_brand ?: '-'),
                'total_po'        => $totalPo,
                'total_pcs'       => $totalPcs,
                'total_transaksi' => $totalNilai,
                'formatted_nilai' => 'Rp ' . number_format($totalNilai, 0, ',', '.'),
            ];
        });

        // Filter pelanggan yang minimal memiliki transaksi jika ada
        $filtered = $ranked->filter(fn($c) => $c['total_po'] > 0 || $c['total_pcs'] > 0 || $c['total_transaksi'] > 0);
        if ($filtered->isEmpty()) {
            $filtered = $ranked;
        }

        // Urutkan sesuai kriteria
        $sorted = match ($by) {
            'pcs'      => $filtered->sortByDesc('total_pcs'),
            'revenue'  => $filtered->sortByDesc('total_transaksi'),
            default    => $filtered->sortByDesc('total_po')->sortByDesc('total_pcs'),
        };

        $topList = $sorted->take($limit)->values();

        return [
            'brand_filter' => $brandModel ? $brandModel->nama_brand : ($brand ?: 'Semua Brand'),
            'sorted_by'    => $by,
            'total_found'  => $topList->count(),
            'top_customers'=> $topList->all(),
        ];
    }

    /**
     * 11. Get Customer History & LTV
     */
    public static function getCustomerHistory(string $query): array
    {
        $q = trim($query);

        // Jika query adalah nama Brand, redirect ke getTopCustomers
        $brandMatch = Brand::where('kode', 'LIKE', $q)
            ->orWhere('nama_brand', 'LIKE', "%{$q}%")
            ->first();

        $customer = Customer::query()
            ->where('id', $q)
            ->orWhere('nomor_hp', $q)
            ->orWhere('kode', $q)
            ->orWhere('nama', 'LIKE', "%{$q}%")
            ->first();

        if (!$customer && $brandMatch) {
            return self::getTopCustomers($brandMatch->nama_brand, 'orders', 10);
        }

        if (!$customer) {
            return ['error' => "Pelanggan '{$query}' tidak ditemukan."];
        }

        $orders = Order::query()
            ->where('pelanggan_id', $customer->id)
            ->with(['brand:id,kode', 'items:id,order_id,is_addon,quantity,jml_atasan'])
            ->orderByDesc('tanggal_masuk')
            ->take(20)
            ->get();

        $totalSpent = $orders->sum(fn($o) => $o->totalTagihan());
        $totalPcs = 0;
        foreach ($orders as $o) {
            $totalPcs += self::countPcs($o);
        }

        return [
            'customer' => [
                'id'       => $customer->id,
                'kode'     => $customer->kode,
                'nama'     => $customer->nama,
                'nomor_hp' => $customer->nomor_hp,
                'email'    => $customer->email,
                'alamat'   => $customer->detail_alamat ?: '-',
            ],
            'statistik' => [
                'total_po'    => $orders->count(),
                'total_pcs'   => $totalPcs,
                'total_omzet' => (float) $totalSpent,
            ],
            'riwayat_order' => $orders->map(fn($o) => [
                'no_po'    => $o->no_po,
                'nama_po'  => $o->nama_po,
                'brand'    => $o->brand?->kode,
                'status'   => $o->status_po,
                'pcs'      => self::countPcs($o),
                'nilai_rp' => (float) $o->totalTagihan(),
                'tgl'      => $o->tanggal_masuk?->format('d/m/Y'),
            ])->all(),
        ];
    }

    /**
     * 11. Get Executive Report Text
     */
    public static function getExecutiveReport(string $role, string $periode = 'harian', ?string $brandId = null): string
    {
        $builder = app(ReportMessageBuilder::class);
        $role = strtolower($role);

        if ($role === 'superadmin') {
            return $builder->superadmin($periode);
        }

        $brand = $brandId ? Brand::find($brandId) : Brand::active()->first();
        if (!$brand) {
            return "Error: Brand tidak ditemukan.";
        }

        return match ($role) {
            'owner'          => $builder->owner($brand, $periode),
            'admin_brand'    => $builder->adminBrand($brand, $periode),
            'admin_produksi' => $builder->adminProduksi($brand, $periode),
            'keuangan'       => $builder->keuangan($brand, $periode),
            default          => $builder->superadmin($periode),
        };
    }

    /**
     * 12. Get Brand Overview
     */
    public static function getBrandOverview(): array
    {
        $brands = Brand::active()->get();
        return $brands->map(function ($b) {
            $activeOrders = Order::where('brand_id', $b->id)->whereIn('status_po', ['published', 'on_progress'])->count();
            return [
                'id'             => $b->id,
                'kode'           => $b->kode,
                'nama'           => $b->nama_brand,
                'order_aktif'    => $activeOrders,
            ];
        })->all();
    }

    /**
     * 13. Get Comprehensive Finance KPI Summary
     */
    public static function getFinanceKpi(?string $brandId = null): array
    {
        $dashboardService = app(DashboardService::class);
        $finance = $dashboardService->financeStats($brandId);

        return [
            'cards' => $finance['cards'] ?? [],
            'piutang_outstanding' => [
                'total_tunggakan' => $finance['reminder_sisa_po']['total_tunggakan'] ?? 0,
                'total_po' => $finance['reminder_sisa_po']['total_po'] ?? 0,
                'total_pcs' => $finance['reminder_sisa_po']['total_pcs'] ?? 0,
            ],
            'brand_financial_reports' => $finance['brand_financial_reports'] ?? [],
            'payment_status_distribution' => $finance['payment_status'] ?? [],
            'current_brand_id' => $finance['current_brand_id'] ?? 'all',
        ];
    }

    /**
     * 14. Get Production KPI & Workstation Antrean
     */
    public static function getProductionKpi(?string $brandId = null): array
    {
        $dashboardService = app(DashboardService::class);
        $prod = $dashboardService->adminProduksiStats($brandId);

        return [
            'cards' => $prod['cards'] ?? [],
            'tahapan_progress' => $prod['progress_distribution'] ?? [],
            'deadline_mendekat' => $prod['deadline_mendekat'] ?? [],
            'po_terlambat' => $prod['po_terlambat'] ?? [],
            'po_siap_dikirim' => $prod['po_siap_dikirim'] ?? [],
        ];
    }

    /**
     * 15. Get Brand Monthly Sales Targets & Achievement
     */
    public static function getBrandTargets(?string $brandId = null): array
    {
        $targetMonth = (int) now()->month;
        $targetYear = (int) now()->year;

        $brands = Brand::active()
            ->when($brandId && $brandId !== 'all', fn($q) => $q->where('id', $brandId))
            ->get();

        $startOfMonth = now()->startOfMonth()->toDateTimeString();
        $endOfMonth = now()->endOfMonth()->toDateTimeString();

        $results = [];
        $totalTargetRevenue = 0;
        $totalActualRevenue = 0;

        foreach ($brands as $b) {
            $target = DB::table('brand_targets')
                ->where('brand_id', $b->id)
                ->where('year', $targetYear)
                ->where('month', $targetMonth)
                ->first();

            $actualRevenue = (float) Order::where('brand_id', $b->id)
                ->whereBetween('tanggal_masuk', [$startOfMonth, $endOfMonth])
                ->where('status_po', '!=', 'draft')
                ->sum('total_tagihan');

            $actualPcs = (int) OrderItem::query()
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('order_items.is_addon', false)
                ->where('orders.brand_id', $b->id)
                ->whereBetween('orders.tanggal_masuk', [$startOfMonth, $endOfMonth])
                ->where('orders.status_po', '!=', 'draft')
                ->sum(DB::raw("CASE WHEN order_items.jml_atasan IS NOT NULL AND order_items.jml_atasan != '' THEN CAST(order_items.jml_atasan AS UNSIGNED) ELSE order_items.quantity END"));

            $revTarget = (float) ($target?->target_revenue ?? 0);
            $pcsTarget = (int) ($target?->target_pcs ?? 0);
            $achievement = $revTarget > 0 ? round(($actualRevenue / $revTarget) * 100, 1) : 0;

            $totalTargetRevenue += $revTarget;
            $totalActualRevenue += $actualRevenue;

            $results[] = [
                'brand_id'           => $b->id,
                'kode'               => $b->kode,
                'nama_brand'         => $b->nama_brand,
                'target_omzet'       => $revTarget,
                'aktual_omzet'       => $actualRevenue,
                'target_pcs'         => $pcsTarget,
                'aktual_pcs'         => $actualPcs,
                'persentase_capai'   => "{$achievement}%",
                'sisa_kejar_target'  => max(0, $revTarget - $actualRevenue),
            ];
        }

        $overallPct = $totalTargetRevenue > 0 ? round(($totalActualRevenue / $totalTargetRevenue) * 100, 1) : 0;

        return [
            'bulan'                  => now()->translatedFormat('F Y'),
            'total_target_agregat'   => $totalTargetRevenue,
            'total_aktual_agregat'   => $totalActualRevenue,
            'persentase_total'       => "{$overallPct}%",
            'detail_per_brand'       => $results,
        ];
    }

    /**
     * 16. Get Bank Reconciliation & Cash Inflow
     */
    public static function getBankReconciliation(?string $brandId = null): array
    {
        $dashboardService = app(DashboardService::class);
        $banks = $dashboardService->financeStats($brandId)['bank_accounts_summary'] ?? collect();

        return [
            'total_rekening' => count($banks),
            'rekening_list'  => $banks,
        ];
    }

    /**
     * 17. Get Invoices with Status Filter
     */
    public static function getInvoices(?string $brandId = null, ?string $status = null, int $limit = 20): array
    {
        $query = \App\Models\Order\Invoice::query()
            ->when($brandId && $brandId !== 'all', fn($q) => $q->where('brand_id', $brandId))
            ->when($status && $status !== 'all', fn($q) => $q->where('status', $status))
            ->with([
                'order:id,no_po,nama_po,pelanggan_id',
                'order.pelanggan:id,nama,nomor_hp',
                'brand:id,kode,nama_brand',
            ])
            ->orderByDesc('tanggal_terbit')
            ->take($limit);

        $invoices = $query->get();

        return [
            'filter_status' => $status ?: 'all',
            'total_count'   => $invoices->count(),
            'invoices'      => $invoices->map(fn($iv) => [
                'no_invoice'       => $iv->invoice_number,
                'no_po'            => $iv->order?->no_po ?: '-',
                'nama_po'          => $iv->order?->nama_po ?: '-',
                'pelanggan'        => $iv->order?->pelanggan?->nama ?: '-',
                'telepon'          => $iv->order?->pelanggan?->nomor_hp ?: '-',
                'brand'            => $iv->brand?->kode ?: '-',
                'status'           => $iv->status,
                'total_tagihan'    => (float) $iv->total_tagihan,
                'total_bayar'      => (float) $iv->total_bayar,
                'sisa_pembayaran'  => (float) $iv->sisa_pembayaran,
                'tanggal_terbit'   => $iv->tanggal_terbit?->format('d/m/Y'),
                'jatuh_tempo'      => $iv->jatuh_tempo?->format('d/m/Y'),
            ])->all(),
        ];
    }

    /**
     * 18. Get Marketing & Leads Acquisition KPI (Sumber Order)
     */
    public static function getMarketingLeadsKpi(?string $brandId = null): array
    {
        $orders = Order::query()
            ->when($brandId && $brandId !== 'all', fn($q) => $q->where('brand_id', $brandId))
            ->where('status_po', '!=', 'draft')
            ->with(['sumberOrder:id,nama'])
            ->get();

        $grouped = $orders->groupBy(fn($o) => $o->sumberOrder?->nama ?: 'Tanpa Sumber / Direct');

        $channels = [];
        $totalOrders = $orders->count();
        $totalRevenue = 0;

        foreach ($grouped as $channelName => $channelOrders) {
            $rev = (float) $channelOrders->sum(fn($o) => $o->totalTagihan());
            $pcs = 0;
            foreach ($channelOrders as $o) {
                $pcs += self::countPcs($o);
            }
            $totalRevenue += $rev;

            $channels[] = [
                'channel'        => $channelName,
                'total_order'    => $channelOrders->count(),
                'persentase_po'  => $totalOrders > 0 ? round(($channelOrders->count() / $totalOrders) * 100, 1) . '%' : '0%',
                'total_pcs'      => $pcs,
                'total_omzet'    => $rev,
                'formatted_omzet'=> 'Rp ' . number_format($rev, 0, ',', '.'),
            ];
        }

        usort($channels, fn($a, $b) => $b['total_omzet'] <=> $a['total_omzet']);

        return [
            'total_order_tervalidasi' => $totalOrders,
            'total_omzet_agregat'     => $totalRevenue,
            'formatted_omzet_agregat' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
            'channels'                => $channels,
        ];
    }

    /**
     * 19. Get Expense & Operational Cost Summary
     */
    public static function getExpenseSummary(?string $brandId = null, ?string $month = null, ?string $year = null): array
    {
        $m = $month ? (int)$month : (int)now()->month;
        $y = $year ? (int)$year : (int)now()->year;

        $query = \App\Models\Finance\Pengeluaran::query()
            ->when($brandId && $brandId !== 'all', fn($q) => $q->where('brand_id', $brandId))
            ->whereMonth('tanggal', $m)
            ->whereYear('tanggal', $y)
            ->with(['kategori:id,nama', 'brand:id,kode']);

        $expenses = $query->get();
        $totalNominal = (float) $expenses->sum('nominal');

        $byCategory = $expenses->groupBy(fn($e) => $e->kategori?->nama ?: 'Lain-lain')
            ->map(function ($items, $cat) use ($totalNominal) {
                $sub = (float) $items->sum('nominal');
                return [
                    'kategori'       => $cat,
                    'total_transaksi'=> $items->count(),
                    'total_nominal'  => $sub,
                    'formatted_rp'   => 'Rp ' . number_format($sub, 0, ',', '.'),
                    'persentase'     => $totalNominal > 0 ? round(($sub / $totalNominal) * 100, 1) . '%' : '0%',
                ];
            })->values()->sortByDesc('total_nominal')->all();

        return [
            'periode'            => date('F Y', mktime(0, 0, 0, $m, 1, $y)),
            'total_pengeluaran'  => $totalNominal,
            'formatted_total'    => 'Rp ' . number_format($totalNominal, 0, ',', '.'),
            'kategori_breakdown' => $byCategory,
        ];
    }

    /**
     * 20. Get Top Products & Fabric Popularity
     */
    public static function getTopProductsAndFabrics(?string $brandId = null, int $limit = 10): array
    {
        // 1. Top Fabrics (Bahan Kain)
        $fabrics = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('bahan_kains', 'bahan_kains.id', '=', 'order_items.bahan_kain_id')
            ->when($brandId && $brandId !== 'all', fn($q) => $q->where('orders.brand_id', $brandId))
            ->where('orders.status_po', '!=', 'draft')
            ->where('order_items.is_addon', false)
            ->select(
                'bahan_kains.nama as nama_bahan',
                DB::raw('COUNT(DISTINCT orders.id) as total_po'),
                DB::raw('SUM(order_items.quantity) as total_pcs')
            )
            ->groupBy('bahan_kains.nama')
            ->orderByDesc('total_pcs')
            ->take($limit)
            ->get();

        // 2. Top Kategori Order
        $categories = DB::table('orders')
            ->leftJoin('kategori_orders', 'kategori_orders.id', '=', 'orders.kategori_order_id')
            ->when($brandId && $brandId !== 'all', fn($q) => $q->where('orders.brand_id', $brandId))
            ->where('orders.status_po', '!=', 'draft')
            ->select(
                DB::raw("COALESCE(kategori_orders.nama, 'Reguler') as kategori"),
                DB::raw('COUNT(orders.id) as total_po'),
                DB::raw('SUM(orders.total_tagihan) as total_omzet')
            )
            ->groupBy('kategori_orders.nama')
            ->orderByDesc('total_po')
            ->take($limit)
            ->get();

        return [
            'top_bahan_kain' => $fabrics->map(fn($f) => [
                'bahan'     => $f->nama_bahan,
                'total_po'  => (int)$f->total_po,
                'total_pcs' => (int)$f->total_pcs,
            ])->all(),
            'top_kategori'   => $categories->map(fn($c) => [
                'kategori'   => $c->kategori,
                'total_po'   => (int)$c->total_po,
                'total_omzet'=> (float)$c->total_omzet,
                'formatted_omzet' => 'Rp ' . number_format((float)$c->total_omzet, 0, ',', '.'),
            ])->all(),
        ];
    }

    /**
     * 21. Get Quality Issues, Defect (Rijek) & Refund KPI
     */
    public static function getComplaintAndRefundKpi(?string $brandId = null): array
    {
        // 1. Rijek Produksi
        $rijeks = \App\Models\Order\Rijek::query()
            ->when($brandId && $brandId !== 'all', function ($q) use ($brandId) {
                $q->whereHas('order', fn($o) => $o->where('brand_id', $brandId));
            })
            ->with(['order:id,no_po,nama_po'])
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        $totalPcsRijek = (int) $rijeks->sum('jumlah');

        // 2. Refunds
        $refunds = \App\Models\Order\Refund::query()
            ->when($brandId && $brandId !== 'all', function ($q) use ($brandId) {
                $q->whereHas('order', fn($o) => $o->where('brand_id', $brandId));
            })
            ->with(['order:id,no_po,nama_po'])
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        $totalRefundRp = (float) $refunds->sum('nominal');

        return [
            'rijek_summary' => [
                'total_insiden' => $rijeks->count(),
                'total_pcs_rijek' => $totalPcsRijek,
                'insiden_terbaru' => $rijeks->map(fn($r) => [
                    'no_po'    => $r->order?->no_po ?: '-',
                    'nama_po'  => $r->order?->nama_po ?: '-',
                    'stasiun'  => $r->tahapan ?: '-',
                    'jumlah'   => (int)$r->jumlah,
                    'alasan'   => $r->alasan ?: '-',
                    'tanggal'  => $r->created_at?->format('d/m/Y'),
                ])->all(),
            ],
            'refund_summary' => [
                'total_kasus'    => $refunds->count(),
                'total_nominal'  => $totalRefundRp,
                'formatted_rp'   => 'Rp ' . number_format($totalRefundRp, 0, ',', '.'),
                'kasus_terbaru'  => $refunds->map(fn($rf) => [
                    'no_po'      => $rf->order?->no_po ?: '-',
                    'nominal'    => (float)$rf->nominal,
                    'status'     => $rf->status ?: '-',
                    'alasan'     => $rf->alasan ?: '-',
                    'tanggal'    => $rf->created_at?->format('d/m/Y'),
                ])->all(),
            ]
        ];
    }

    /**
     * 22. Get Shipping & Logistics Performance
     */
    public static function getShippingPerformance(?string $brandId = null): array
    {
        $orders = Order::query()
            ->when($brandId && $brandId !== 'all', fn($q) => $q->where('brand_id', $brandId))
            ->whereIn('status_po', ['siap_dikirim', 'sudah_dikirim', 'selesai'])
            ->with(['ekspedisi:id,nama'])
            ->get();

        $totalShipped = $orders->count();
        $freeOngkirCount = $orders->filter(fn($o) => $o->isFreeOngkir())->count();
        $regularOngkirCount = $totalShipped - $freeOngkirCount;
        $totalOngkirTalangan = (float) $orders->filter(fn($o) => $o->isFreeOngkir())->sum('ongkir');

        $byEkspedisi = $orders->groupBy(fn($o) => $o->ekspedisi?->nama ?: ($o->nama_ekspedisi ?: 'Belum Ditentukan'))
            ->map(fn($group, $name) => [
                'ekspedisi'    => $name,
                'total_kirim'  => $group->count(),
                'persentase'   => $totalShipped > 0 ? round(($group->count() / $totalShipped) * 100, 1) . '%' : '0%',
            ])->values()->sortByDesc('total_kirim')->all();

        return [
            'total_order_terkirim' => $totalShipped,
            'rasio_pengiriman' => [
                'free_ongkir_count'     => $freeOngkirCount,
                'regular_ongkir_count'  => $regularOngkirCount,
                'total_ongkir_subsidi'  => $totalOngkirTalangan,
                'formatted_subsidi'     => 'Rp ' . number_format($totalOngkirTalangan, 0, ',', '.'),
            ],
            'kurir_terpopuler' => $byEkspedisi,
        ];
    }

    /**
     * 23. Safe Ad-Hoc Analytics Query Builder
     * Memungkinkan AI melakukan query analitik agregasi terproteksi tanpa batas.
     */
    public static function runAnalyticsQuery(
        string $dataset,
        array $filters = [],
        string $metric = 'count',
        ?string $field = null,
        ?string $groupBy = null,
        int $limit = 20
    ): array {
        $allowedDatasets = [
            'orders'     => Order::class,
            'customers'  => Customer::class,
            'invoices'   => \App\Models\Order\Invoice::class,
            'payments'   => \App\Models\Order\OrderPayment::class,
            'expenses'   => \App\Models\Finance\Pengeluaran::class,
            'rijeks'     => \App\Models\Order\Rijek::class,
            'refunds'    => \App\Models\Order\Refund::class,
        ];

        $datasetKey = strtolower(trim($dataset));
        if (!isset($allowedDatasets[$datasetKey])) {
            return [
                'error' => "Dataset '{$dataset}' tidak diizinkan. Pilihan yang valid: " . implode(', ', array_keys($allowedDatasets))
            ];
        }

        $modelClass = $allowedDatasets[$datasetKey];
        $query = $modelClass::query();

        // 1. Terapkan filter tanggal (start_date, end_date)
        if (!empty($filters['start_date'])) {
            $dateCol = $datasetKey === 'orders' ? 'tanggal_masuk' : ($datasetKey === 'expenses' ? 'tanggal' : 'created_at');
            $query->whereDate($dateCol, '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $dateCol = $datasetKey === 'orders' ? 'tanggal_masuk' : ($datasetKey === 'expenses' ? 'tanggal' : 'created_at');
            $query->whereDate($dateCol, '<=', $filters['end_date']);
        }

        // 2. Terapkan filter brand
        if (!empty($filters['brand_id']) && $filters['brand_id'] !== 'all') {
            if ($datasetKey === 'orders' || $datasetKey === 'invoices' || $datasetKey === 'expenses' || $datasetKey === 'customers') {
                $query->where('brand_id', $filters['brand_id']);
            }
        }

        // 3. Terapkan filter status
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $statusCol = $datasetKey === 'orders' ? 'status_po' : 'status';
            $query->where($statusCol, $filters['status']);
        }

        // 4. Agregasi
        $metric = strtolower($metric);
        $field = $field ?: ($metric === 'count' ? '*' : 'id');

        if ($groupBy) {
            $allowedGroupBy = ['status', 'brand_id', 'month', 'year'];
            if (!in_array($groupBy, $allowedGroupBy, true)) {
                $groupBy = 'status';
            }

            if ($groupBy === 'month') {
                $dateCol = $datasetKey === 'orders' ? 'tanggal_masuk' : 'created_at';
                $isSqlite = DB::connection()->getDriverName() === 'sqlite';
                $expr = $isSqlite ? "strftime('%Y-%m', {$dateCol})" : "DATE_FORMAT({$dateCol}, '%Y-%m')";
                $query->selectRaw("{$expr} as group_key");
            } else {
                $query->selectRaw("{$groupBy} as group_key");
            }

            $aggExpr = match ($metric) {
                'sum' => "SUM({$field}) as metric_value",
                'avg' => "AVG({$field}) as metric_value",
                'max' => "MAX({$field}) as metric_value",
                'min' => "MIN({$field}) as metric_value",
                default => "COUNT(*) as metric_value",
            };

            $query->selectRaw($aggExpr)->groupBy('group_key')->orderByDesc('metric_value')->take($limit);
            $results = $query->get()->map(fn($row) => [
                'group' => (string)$row->group_key,
                'value' => (float)$row->metric_value,
            ])->all();

            return [
                'dataset'      => $datasetKey,
                'metric'       => $metric,
                'field'        => $field,
                'group_by'     => $groupBy,
                'total_groups' => count($results),
                'results'      => $results,
            ];
        }

        // Single Scalar Aggregate
        $val = match ($metric) {
            'sum' => (float) $query->sum($field),
            'avg' => (float) $query->avg($field),
            'max' => (float) $query->max($field),
            'min' => (float) $query->min($field),
            default => (int) $query->count(),
        };

        return [
            'dataset' => $datasetKey,
            'metric'  => $metric,
            'field'   => $field,
            'result'  => $val,
        ];
    }

    /**
     * 24. Get Accounts Receivable Aging Schedule (Untuk Skill CFO / Keuangan)
     * Mengelompokkan piutang berdasarkan umur keterlambatan: 0-30 hari, 31-60 hari, 61-90 hari, >90 hari.
     */
    public static function getReceivableAgingSchedule(?string $brandId = null): array
    {
        $orders = Order::query()
            ->when($brandId && $brandId !== 'all', fn($q) => $q->where('brand_id', $brandId))
            ->where('is_lunas', false)
            ->where('status_po', '!=', 'draft')
            ->where('is_special_order', false)
            ->with([
                'pelanggan:id,nama,nomor_hp',
                'brand:id,kode,nama_brand',
                'payments' => fn($q) => $q->whereNotNull('verified_at')
            ])
            ->get();

        $buckets = [
            'current'    => ['label' => 'Jatuh Tempo <= 30 Hari', 'count' => 0, 'total_rp' => 0, 'items' => []],
            'overdue_30' => ['label' => 'Terlambat 31 - 60 Hari', 'count' => 0, 'total_rp' => 0, 'items' => []],
            'overdue_60' => ['label' => 'Terlambat 61 - 90 Hari', 'count' => 0, 'total_rp' => 0, 'items' => []],
            'bad_debt'   => ['label' => 'Macet (> 90 Hari)',     'count' => 0, 'total_rp' => 0, 'items' => []],
        ];

        $totalReceivable = 0;
        $now = now();

        foreach ($orders as $order) {
            $tagihan = $order->totalTagihan();
            $terbayar = (float) $order->payments->sum('amount');
            $sisa = max(0, $tagihan - $terbayar);

            if ($sisa <= 0) {
                continue;
            }

            $totalReceivable += $sisa;
            $tglMasuk = $order->tanggal_masuk ?: $order->created_at;
            $daysOld = $tglMasuk ? (int)$tglMasuk->diffInDays($now) : 0;

            $itemData = [
                'no_po'      => $order->no_po,
                'nama_po'    => $order->nama_po,
                'pelanggan'  => $order->pelanggan?->nama ?: '-',
                'telepon'    => $order->pelanggan?->nomor_hp ?: '-',
                'brand'      => $order->brand?->kode ?: '-',
                'umur_hari'  => $daysOld,
                'sisa_rp'    => $sisa,
                'formatted'  => 'Rp ' . number_format($sisa, 0, ',', '.'),
            ];

            if ($daysOld <= 30) {
                $buckets['current']['count']++;
                $buckets['current']['total_rp'] += $sisa;
                $buckets['current']['items'][] = $itemData;
            } elseif ($daysOld <= 60) {
                $buckets['overdue_30']['count']++;
                $buckets['overdue_30']['total_rp'] += $sisa;
                $buckets['overdue_30']['items'][] = $itemData;
            } elseif ($daysOld <= 90) {
                $buckets['overdue_60']['count']++;
                $buckets['overdue_60']['total_rp'] += $sisa;
                $buckets['overdue_60']['items'][] = $itemData;
            } else {
                $buckets['bad_debt']['count']++;
                $buckets['bad_debt']['total_rp'] += $sisa;
                $buckets['bad_debt']['items'][] = $itemData;
            }
        }

        foreach ($buckets as $k => $b) {
            $buckets[$k]['formatted_total'] = 'Rp ' . number_format($b['total_rp'], 0, ',', '.');
            $buckets[$k]['persentase'] = $totalReceivable > 0 ? round(($b['total_rp'] / $totalReceivable) * 100, 1) . '%' : '0%';
            // batasi sample list untuk token efficiency
            $buckets[$k]['items'] = array_slice($b['items'], 0, 10);
        }

        return [
            'total_piutang_outstanding' => $totalReceivable,
            'formatted_total_piutang'   => 'Rp ' . number_format($totalReceivable, 0, ',', '.'),
            'aging_schedule'            => $buckets,
        ];
    }

    /**
     * 25. Audit Financial & PO Compliance (Untuk Skill Auditor Internal & Risk Controller)
     * Mendeteksi transaksi anomali, bypass DP tanpa izin, order selesai belum lunas, dan voucher mencurigakan.
     */
    public static function auditFinancialAndPoCompliance(?string $brandId = null): array
    {
        // 1. Audit PO yang di-Bypass DP
        $bypassedOrders = Order::query()
            ->when($brandId && $brandId !== 'all', fn($q) => $q->where('brand_id', $brandId))
            ->where('is_dp_bypassed', true)
            ->with(['dpBypasser:id,name', 'brand:id,kode', 'pelanggan:id,nama'])
            ->orderByDesc('dp_bypassed_at')
            ->take(15)
            ->get();

        // 2. Audit PO Selesai / Terkirim tapi Belum Lunas (High Risk Piutang)
        $unpaidDelivered = Order::query()
            ->when($brandId && $brandId !== 'all', fn($q) => $q->where('brand_id', $brandId))
            ->whereIn('status_po', ['sudah_dikirim', 'selesai'])
            ->where('is_lunas', false)
            ->where('is_special_order', false)
            ->with(['pelanggan:id,nama,nomor_hp', 'brand:id,kode'])
            ->take(15)
            ->get();

        // 3. Audit PO dengan Diskon Voucher / Nilai Abnormal
        $highDiscountOrders = Order::query()
            ->when($brandId && $brandId !== 'all', fn($q) => $q->where('brand_id', $brandId))
            ->where('voucher_discount_amount', '>', 0)
            ->with(['creator:id,name', 'brand:id,kode', 'pelanggan:id,nama'])
            ->orderByDesc('voucher_discount_amount')
            ->take(15)
            ->get();

        return [
            'audit_summary' => [
                'total_dp_bypassed'         => $bypassedOrders->count(),
                'total_unpaid_delivered'    => $unpaidDelivered->count(),
                'total_voucher_discounts'   => $highDiscountOrders->count(),
                'status_kepatuhan_pabrik'   => $unpaidDelivered->count() > 5 ? 'PERHATIAN TINGGI (RISK WARNING)' : 'TERKENDALI (LOW RISK)',
            ],
            'temuan_anomali' => [
                'po_bypass_dp' => $bypassedOrders->map(fn($o) => [
                    'no_po'        => $o->no_po,
                    'pelanggan'    => $o->pelanggan?->nama ?: '-',
                    'di_bypass_oleh'=> $o->dpBypasser?->name ?: 'System / Admin',
                    'tanggal'      => $o->dp_bypassed_at?->format('d/m/Y H:i'),
                ])->all(),
                'po_terkirim_belum_lunas' => $unpaidDelivered->map(fn($o) => [
                    'no_po'        => $o->no_po,
                    'pelanggan'    => $o->pelanggan?->nama ?: '-',
                    'telepon'      => $o->pelanggan?->nomor_hp ?: '-',
                    'sisa_tagihan' => (float)$o->totalTagihan(),
                    'formatted'    => 'Rp ' . number_format((float)$o->totalTagihan(), 0, ',', '.'),
                    'status'       => $o->status_po,
                ])->all(),
                'voucher_diskon_terbesar' => $highDiscountOrders->map(fn($o) => [
                    'no_po'        => $o->no_po,
                    'pelanggan'    => $o->pelanggan?->nama ?: '-',
                    'diskon_rp'    => (float)$o->voucher_discount_amount,
                    'formatted'    => 'Rp ' . number_format((float)$o->voucher_discount_amount, 0, ',', '.'),
                    'dibuat_oleh'  => $o->creator?->name ?: 'Admin',
                ])->all(),
            ]
        ];
    }

    /**
     * 26. Get Brand Portfolio Matrix (Untuk Skill Konsultan Bisnis & Analis Data)
     * Evaluasi matriks performa brand (Omzet, Pcs, Tingkat Pelunasan, dan Kontribusi Laba).
     */
    public static function getBrandPortfolioMatrix(): array
    {
        $brands = Brand::active()->get();
        $totalPabrikOmzet = (float) Order::where('status_po', '!=', 'draft')->sum('total_tagihan');
        $matrix = [];

        foreach ($brands as $b) {
            $orders = Order::where('brand_id', $b->id)->where('status_po', '!=', 'draft')->with(['items', 'payments'])->get();
            $omzet = (float) $orders->sum(fn($o) => $o->totalTagihan());
            $pcs = 0;
            foreach ($orders as $o) {
                $pcs += self::countPcs($o);
            }
            $terbayar = (float) $orders->sum(fn($o) => $o->payments->whereNotNull('verified_at')->sum('amount'));
            $unpaid = max(0, $omzet - $terbayar);
            $repaymentRate = $omzet > 0 ? round(($terbayar / $omzet) * 100, 1) : 100;
            $marketShare = $totalPabrikOmzet > 0 ? round(($omzet / $totalPabrikOmzet) * 100, 1) : 0;

            // Klasifikasi Strategis
            $strategicRole = 'Standard Brand';
            if ($marketShare >= 30 && $repaymentRate >= 80) {
                $strategicRole = 'CASH COW (Pilar Utama Omzet & Kas)';
            } elseif ($marketShare >= 20 && $repaymentRate < 70) {
                $strategicRole = 'HIGH VOLUME / HIGH RISK (Omzet Besar tapi Piutang Macet)';
            } elseif ($marketShare < 10 && $repaymentRate >= 90) {
                $strategicRole = 'HEALTHY NICHE (Kecil tapi Arus Kas Sehat)';
            } elseif ($marketShare < 5) {
                $strategicRole = 'UNDERPERFORMING (Perlu Evaluasi Pemasaran)';
            }

            $matrix[] = [
                'brand_id'           => $b->id,
                'kode'               => $b->kode,
                'nama_brand'         => $b->nama_brand,
                'total_order'        => $orders->count(),
                'total_pcs'          => $pcs,
                'total_omzet'        => $omzet,
                'formatted_omzet'    => 'Rp ' . number_format($omzet, 0, ',', '.'),
                'piutang_tertahan'   => $unpaid,
                'tingkat_pelunasan'  => "{$repaymentRate}%",
                'kontribusi_omzet'   => "{$marketShare}%",
                'klasifikasi_bisnis' => $strategicRole,
            ];
        }

        usort($matrix, fn($a, $b) => $b['total_omzet'] <=> $a['total_omzet']);

        return [
            'total_omzet_keseluruhan' => $totalPabrikOmzet,
            'formatted_keseluruhan'   => 'Rp ' . number_format($totalPabrikOmzet, 0, ',', '.'),
            'portofolio_brands'       => $matrix,
        ];
    }

    /**
     * 27. Master AI Multi-Role Skills Injection Package
     * Menginjeksi 4 Persona Spesialis: CFO Keuangan, Analis Bisnis, Konsultan Strategis, & Auditor Internal.
     */
    public static function getFactoryAiSkills(?string $role = null): array
    {
        $allSkills = [
            'finance_expert' => [
                'title' => 'Direktur Keuangan & Arus Kas (Chief Financial Officer / CFO)',
                'perspective' => 'Fokus pada likuiditas, solvabilitas kas, perlindungan modal kerja, dan penagihan piutang agresif namun santun.',
                'capabilities' => [
                    'Aging Schedule Analysis' => 'Memantau piutang berdasarkan umur jatuh tempo (<30 hari, 31-60 hari, >90 hari).',
                    'Dunning Strategy' => 'Menyusun naskah pesan penagihan WhatsApp berjenjang (Reminder Halus -> Penegasan Jatuh Tempo -> Somasi Penyelesaian).',
                    'Cashflow Projection' => 'Menghitung rasio realisasi pembayaran masuk terhadap proyeksi pengeluaran operasional.',
                ],
                'recommended_tools' => ['get_finance_kpi', 'get_receivable_aging_schedule', 'get_unpaid_reminders', 'get_bank_reconciliation', 'get_expense_summary']
            ],
            'business_analyst' => [
                'title' => 'Senior Business & Data Analyst',
                'perspective' => 'Fokus pada tren data, pola musiman, perilaku pembelian pelanggan, dan elastisitas produk terlaris.',
                'capabilities' => [
                    'Customer Cohort & LTV' => 'Menganalisis frekuensi order pelanggan setia dan kontribusi nilai seumur hidup.',
                    'Product Affinity & Fabric Trend' => 'Mengevaluasi kombinasi bahan kain dan kategori produk dengan perputaran paling cepat.',
                    'Channel ROI' => 'Mengidentifikasi sumber leads (TikTok, IG, Referral) yang menghasilkan konversi omzet tertinggi.',
                ],
                'recommended_tools' => ['get_top_customers', 'get_marketing_leads_kpi', 'get_top_products_and_fabrics', 'get_brand_targets', 'run_analytics_query']
            ],
            'strategy_consultant' => [
                'title' => 'Konsultan Bisnis & Manajemen Strategis (McKinsey / BCG Style)',
                'perspective' => 'Fokus pada pertumbuhan jangka panjang, skalabilitas pabrik, optimasi kapasitas produksi, dan profitabilitas antar brand.',
                'capabilities' => [
                    'Brand Portfolio Matrix' => 'Mengklasifikasikan brand ke dalam kuadran Cash Cow, Star, Question Mark, atau Underperforming.',
                    'Theory of Constraints (TOC)' => 'Menganalisis stasiun kerja pabrik yang menjadi leher botol (bottleneck) dan menghambat throughput.',
                    'Pricing & Margin Optimization' => 'Menilai kebijakan harga grosir/reseller terhadap margin laba bersih pabrik.',
                ],
                'recommended_tools' => ['get_brand_portfolio_matrix', 'get_production_kpi', 'get_production_queue', 'get_executive_report', 'get_shipping_performance']
            ],
            'internal_auditor' => [
                'title' => 'Auditor Internal, Kepatuhan & Pengendali Risiko (Audit & Risk Controller)',
                'perspective' => 'Fokus pada integritas transaksi, deteksi anomali kecurangan (fraud), kepatuhan SOP pabrik, dan mitigasi kerugian bahan baku.',
                'capabilities' => [
                    'Transaction Anomaly Detection' => 'Audit PO bypass DP tanpa otorisasi pimpinan atau diskon voucher abnormal.',
                    'Delivery Risk Audit' => 'Audit order yang barangnya sudah dikirim/selesai tapi pembayaran belum lunas.',
                    'Quality & Scrap Loss Audit' => 'Audit persentase cacat rijek produksi QC untuk mencegah pemborosan kain dan biaya penjahit.',
                ],
                'recommended_tools' => ['audit_financial_and_po_compliance', 'get_complaint_and_refund_kpi', 'get_defect_stats', 'get_delayed_orders']
            ]
        ];

        if ($role && isset($allSkills[$role])) {
            return [
                'role_selected' => $role,
                'skill_package' => $allSkills[$role]
            ];
        }

        return [
            'framework' => 'Garment Operating System (GOS) Multi-Persona Executive Intelligence',
            'version' => '3.0.0-PROD',
            'available_roles' => $allSkills,
            'core_business_rules' => [
                'Kalkulasi Kuantiti (Pcs)' => 'Pcs pakaian dihitung dari order_items non-addon dengan prioritas jml_atasan jika tersedia. Addon sablon/bordir tidak boleh dihitung ganda sebagai pcs baju.',
                'Perhitungan Piutang' => 'Piutang dihitung dari Total Tagihan dikurangi Pembayaran yang telah berstatus verified_at.',
                'Deadline Urgensi' => 'CRITICAL jika sisa deadline <= 1 hari; HIGH jika <= 3 hari; NORMAL jika <= 7 hari.',
                'Format Komunikasi WhatsApp' => 'Saat menyajikan data tunggakan/piutang, sertakan tautan resmi WhatsApp (https://wa.me/62...(?text=...)) dengan kalimat penagihan sopan, mengapresiasi kerja sama pelanggan.',
                'Gaya Presentasi' => 'Gunakan tabel Markdown ringkas untuk daftar perbandingan, bullet points untuk action items, dan badge status tebal (LUNAS, DP, PROSES, TERLAMBAT).',
            ]
        ];
    }


    /**
     * Flush all AI-related cache snapshots
     */
    public static function flushAllAiCache(): void
    {
        CacheService::forgetDashboard();
        CacheService::forgetProductionViews();
        try {
            AiSnapshotCache::truncate();
        } catch (\Throwable $e) {
            // ignore if table doesn't support truncate in transaction
        }
    }
}

