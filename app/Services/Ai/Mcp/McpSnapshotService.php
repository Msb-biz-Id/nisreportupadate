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
                    'pelanggan:id,nama,telepon',
                    'brand:id,kode',
                    'items:id,order_id,is_addon,quantity,jml_atasan',
                    'payments:id,order_id,nominal,status',
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
                    'phone'   => $o->pelanggan?->telepon ?: '',
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
                'items.masterBahan',
                'items.namesets',
                'payments.masterJenisPembayaran',
                'progressDetails.progress',
                'shippings',
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
                'bahan'      => $i->masterBahan?->nama ?: '-',
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
                'nominal' => (float) $pay->nominal,
                'metode'  => $pay->masterJenisPembayaran?->nama ?: '-',
                'status'  => $pay->status,
                'tanggal' => $pay->created_at?->format('d/m/Y'),
            ];
        })->all();

        $shipping = $order->shippings->map(function ($s) {
            return [
                'ekspedisi' => $s->ekspedisi,
                'no_resi'   => $s->no_resi,
                'alamat'    => $s->alamat_tujuan,
                'tgl_kirim' => $s->tanggal_kirim?->format('d/m/Y'),
            ];
        })->all();

        return [
            'no_po'          => $order->no_po,
            'nama_po'        => $order->nama_po,
            'brand'          => $order->brand?->nama_brand ?: $order->brand?->kode,
            'status'         => $order->status_po,
            'total_pcs'      => $pcs,
            'pelanggan'      => [
                'nama'    => $order->pelanggan?->nama,
                'telepon' => $order->pelanggan?->telepon,
                'alamat'  => $order->pelanggan?->alamat,
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
        $customers = Customer::query()
            ->where(function ($w) use ($q) {
                $w->where('nama', 'LIKE', "%{$q}%")
                  ->orWhere('telepon', 'LIKE', "%{$q}%")
                  ->orWhere('email', 'LIKE', "%{$q}%")
                  ->orWhere('instansi', 'LIKE', "%{$q}%");
            })
            ->withCount('orders')
            ->take(10)
            ->get();

        return [
            'total_found' => $customers->count(),
            'customers'   => $customers->map(fn($c) => [
                'id'          => $c->id,
                'nama'        => $c->nama,
                'telepon'     => $c->telepon,
                'email'       => $c->email,
                'instansi'    => $c->instansi ?: '-',
                'total_order' => $c->orders_count,
            ])->all(),
        ];
    }

    /**
     * 10. Get Customer History & LTV
     */
    public static function getCustomerHistory(string $query): array
    {
        $q = trim($query);
        $customer = Customer::query()
            ->where('id', $q)
            ->orWhere('telepon', $q)
            ->orWhere('nama', 'LIKE', "%{$q}%")
            ->first();

        if (!$customer) {
            return ['error' => "Pelanggan '{$query}' tidak ditemukan."];
        }

        $orders = Order::query()
            ->where('pelanggan_id', $customer->id)
            ->with(['brand:id,kode', 'items:id,order_id,is_addon,quantity,jml_atasan'])
            ->orderByDesc('tanggal_masuk')
            ->take(20)
            ->get();

        $totalSpent = $orders->sum('total_amount');
        $totalPcs = 0;
        foreach ($orders as $o) {
            $totalPcs += self::countPcs($o);
        }

        return [
            'customer' => [
                'nama'     => $customer->nama,
                'telepon'  => $customer->telepon,
                'instansi' => $customer->instansi,
                'alamat'   => $customer->alamat,
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
                'nilai_rp' => (float) $o->total_amount,
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
     * Flush all AI-related cache snapshots
     */
    public static function flushAllAiCache(): void
    {
        CacheService::flushDomain('order');
        CacheService::flushDomain('finance');
        CacheService::flushDomain('production');
        try {
            AiSnapshotCache::truncate();
        } catch (\Throwable $e) {
            // ignore if table doesn't support truncate in transaction
        }
    }
}
