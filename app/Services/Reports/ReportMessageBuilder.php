<?php

namespace App\Services\Reports;

use App\Models\Brand;
use App\Models\Finance\Pemasukan;
use App\Models\Finance\Pengeluaran;
use App\Models\Order\Invoice;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\Refund;
use App\Models\Order\Rijek;
use App\Services\Ai\GeminiClient;
use Illuminate\Support\Facades\DB;

/**
 * ReportMessageBuilder — membangun pesan laporan per-role sesuai BRD 17.2.2.
 * Semua metode menggunakan query real-time, bukan cached stats.
 * Mendukung AI Insight opsional via Gemini (hanya jika dikonfigurasi).
 */
class ReportMessageBuilder
{
    private ?GeminiClient $ai;

    private function getAppName(): string
    {
        return \App\Models\Settings\SystemSetting::get('seo', 'site_name', config('app.name', 'ProTrack'));
    }

    public function __construct(?GeminiClient $ai = null)
    {
        $this->ai = $ai ?? GeminiClient::fromSettings();
    }

    /**
     * Generate AI insight singkat (2-3 kalimat) dari data ringkasan laporan.
     * Mengembalikan '' jika Gemini tidak dikonfigurasi atau gagal.
     */
    private function aiInsight(string $context): string
    {
        if (! $this->ai?->isConfigured()) {
            return '';
        }

        $prompt = <<<PROMPT
Kamu adalah analis bisnis untuk perusahaan apparel/jersey custom di Indonesia.
Berikan insight singkat (2-3 kalimat, bahasa Indonesia) berdasarkan data laporan berikut.
Fokus pada hal yang paling perlu diperhatikan atau ditindaklanjuti.
Jangan mengulang angka, cukup berikan interpretasi dan rekomendasi singkat.

DATA LAPORAN:
{$context}
PROMPT;

        $result = $this->ai->generate($prompt);
        return $result['success'] ? trim($result['text']) : '';
    }

    /** Format AI insight untuk disisipkan di pesan WA */
    private function appendAiInsight(array &$lines, string $context): void
    {
        $insight = $this->aiInsight($context);
        if ($insight !== '') {
            $lines[] = '';
            $lines[] = '🤖 *AI INSIGHT:*';
            $lines[] = $insight;
        }
    }
    /** ===== Template A: Superadmin (semua brand) ===== */
    public function superadmin(string $periode): string
    {
        $totalBrand = Brand::active()->count();

        $statusRows = Order::query()
            ->select('status_po', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status_po')
            ->pluck('cnt', 'status_po');

        $masuk   = (int)(($statusRows['published'] ?? 0) + ($statusRows['on_progress'] ?? 0));
        $proses  = (int)($statusRows['on_progress'] ?? 0);
        $selesai = (int)(($statusRows['selesai_produksi'] ?? 0) + ($statusRows['siap_dikirim'] ?? 0) + ($statusRows['sudah_dikirim'] ?? 0) + ($statusRows['selesai'] ?? 0));
        $delay   = (int)($statusRows['delay'] ?? 0);

        $terlambat = Order::query()
            ->whereNotIn('status_po', ['draft', 'sudah_dikirim', 'selesai'])
            ->where('deadline_customer', '<', today())
            ->with(['pelanggan:id,nama', 'brand:id,kode'])
            ->orderBy('deadline_customer')->limit(5)->get();

        $deadlines = Order::query()
            ->whereIn('status_po', ['published', 'on_progress'])
            ->whereBetween('deadline_customer', [now(), now()->addDays(3)])
            ->with(['pelanggan:id,nama', 'brand:id,kode'])
            ->orderBy('deadline_customer')->limit(5)->get();

        $revToday = Order::where('status_po', '!=', 'draft')->where('tanggal_masuk', today()->toDateString())->sum('total_tagihan');
        $revWeek  = Order::where('status_po', '!=', 'draft')->whereBetween('tanggal_masuk', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])->sum('total_tagihan');
        $revMonth = Order::where('status_po', '!=', 'draft')->whereBetween('tanggal_masuk', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->sum('total_tagihan');

        $topBrands = Order::query()
            ->where('status_po', '!=', 'draft')
            ->whereBetween('tanggal_masuk', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->select('brand_id', DB::raw('COUNT(*) as total'), DB::raw('SUM(total_tagihan) as revenue'))
            ->groupBy('brand_id')
            ->with('brand:id,nama_brand,kode')
            ->orderByDesc('revenue')->limit(3)->get();

        $lines = [
            "📊 *LAPORAN SUPERADMIN - " . strtoupper($periode) . "*",
            "📅 " . now()->translatedFormat('d M Y, H:i'),
            "",
            "🏢 *TOTAL BRAND:* {$totalBrand} brand aktif",
            "",
            "📦 *ORDER KESELURUHAN:*",
            "• Masuk: {$masuk} order",
            "• Proses: {$proses} order",
            "• Selesai: {$selesai} order",
            "• Delay: {$delay} order",
        ];

        if ($terlambat->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "⚠️ *PO TERLAMBAT:*";
            foreach ($terlambat as $po) {
                $hari = abs((int) now()->startOfDay()->diffInDays($po->deadline_customer, false));
                $lines[] = "• [{$po->brand?->kode}] {$po->no_po} - {$po->pelanggan?->nama} ({$hari} hari)";
            }
        }

        if ($deadlines->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "⏰ *DEADLINE < 3 HARI:*";
            foreach ($deadlines as $po) {
                $h = (int) now()->startOfDay()->diffInDays($po->deadline_customer, false);
                $lines[] = "• [{$po->brand?->kode}] {$po->no_po} - {$po->pelanggan?->nama} (H-{$h})";
            }
        }

        $lines[] = "";
        $lines[] = "💰 *REVENUE TOTAL:*";
        $lines[] = "• Hari ini: Rp " . number_format($revToday, 0, ',', '.');
        $lines[] = "• Minggu ini: Rp " . number_format($revWeek, 0, ',', '.');
        $lines[] = "• Bulan ini: Rp " . number_format($revMonth, 0, ',', '.');

        if ($topBrands->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "🏆 *TOP BRAND BULAN INI:*";
            $medals = ["🥇", "🥈", "🥉"];
            foreach ($topBrands as $i => $b) {
                $m = $medals[$i] ?? "•";
                $lines[] = "{$m} {$b->brand?->nama_brand}: {$b->total} order / Rp " . number_format($b->revenue, 0, ',', '.');
            }
        }

        // AI Insight
        $aiCtx = "Brand aktif: {$totalBrand}. Order masuk: {$masuk}, proses: {$proses}, selesai: {$selesai}, delay: {$delay}. "
            . "PO terlambat: {$terlambat->count()}. Deadline < 3 hari: {$deadlines->count()}. "
            . "Revenue hari ini: Rp " . number_format($revToday, 0) . ", bulan ini: Rp " . number_format($revMonth, 0) . ".";
        $this->appendAiInsight($lines, $aiCtx);

        $lines[] = "";
        $lines[] = "_" . $this->getAppName() . " · " . strtoupper($periode) . " · " . now()->format('d/m/Y H:i') . "_";
        return implode("\n", $lines);
    }

    /** ===== Template B: Admin Produksi ===== */
    public function adminProduksi(Brand $brand, string $periode): string
    {
        $bid = $brand->id;

        $dalamProses    = Order::where('brand_id', $bid)->where('status_po', 'on_progress')->count();
        $masukHariIni   = Order::where('brand_id', $bid)->where('status_po', '!=', 'draft')->where('tanggal_masuk', today()->toDateString())->count();
        $selesaiHariIni = Order::where('brand_id', $bid)->where('status_po', 'selesai_produksi')->whereBetween('updated_at', [today()->startOfDay(), today()->endOfDay()])->count();

        $poMasuk = Order::where('brand_id', $bid)->where('status_po', '!=', 'draft')
            ->where('tanggal_masuk', today()->toDateString())
            ->with(['pelanggan:id,nama', 'items:order_id,nama_produk,quantity'])
            ->orderByDesc('tanggal_masuk')->limit(5)->get();

        $deadlines = Order::where('brand_id', $bid)
            ->whereIn('status_po', ['published', 'on_progress'])
            ->whereBetween('deadline_customer', [now(), now()->addDays(7)])
            ->with('pelanggan:id,nama')->orderBy('deadline_customer')->limit(5)->get();

        $terlambat = Order::where('brand_id', $bid)
            ->whereNotIn('status_po', ['draft', 'sudah_dikirim', 'selesai'])
            ->where('deadline_customer', '<', today())
            ->with('pelanggan:id,nama')->orderBy('deadline_customer')->limit(5)->get();

        $totalRijek = Rijek::query()
            ->join('orders', 'orders.id', '=', 'rijeks.order_id')
            ->where('orders.brand_id', $bid)
            ->where('rijeks.created_at', '>=', now()->subDays(30))
            ->sum('rijeks.jumlah');
        $totalPcs = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.brand_id', $bid)->where('orders.status_po', '!=', 'draft')
            ->where('orders.tanggal_masuk', '>=', now()->subDays(30))
            ->sum('order_items.quantity');
        $rijekRate = $totalPcs > 0 ? round(($totalRijek / $totalPcs) * 100, 1) : 0;

        $lines = [
            "📊 *LAPORAN PRODUKSI - {$brand->nama_brand}*",
            "📅 " . now()->translatedFormat('d M Y, H:i'),
            "",
            "📦 *STATUS ORDER:*",
            "• Dalam Proses: {$dalamProses} order",
            "• Masuk Hari Ini: {$masukHariIni} order",
            "• Selesai Hari Ini: {$selesaiHariIni} order",
        ];

        if ($poMasuk->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "📋 *DETAIL PO MASUK HARI INI:*";
            foreach ($poMasuk as $i => $po) {
                $produk = $po->items->first()?->nama_produk ?? '-';
                $qty    = $po->items->sum('quantity');
                $lines[] = ($i + 1) . ". {$po->no_po} - {$po->pelanggan?->nama} - {$produk} x{$qty}";
            }
        }

        if ($deadlines->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "⏰ *DEADLINE < 7 HARI:*";
            foreach ($deadlines as $po) {
                $h = (int) now()->startOfDay()->diffInDays($po->deadline_customer, false);
                $lines[] = "• {$po->no_po} - {$po->pelanggan?->nama} - H-{$h}";
            }
        }

        if ($terlambat->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "⚠️ *PO TERLAMBAT:*";
            foreach ($terlambat as $po) {
                $hari = abs((int) now()->startOfDay()->diffInDays($po->deadline_customer, false));
                $lines[] = "• {$po->no_po} - {$po->pelanggan?->nama} ({$hari} hari)";
            }
        }

        $lines[] = "";
        $lines[] = "⚠️ *RIJEK (30 HARI):*";
        $lines[] = "• Rate: {$rijekRate}% | Total: {$totalRijek} pcs dari {$totalPcs} pcs produksi";

        // AI Insight
        $aiCtx = "Brand {$brand->nama_brand}. Dalam proses: {$dalamProses}, masuk hari ini: {$masukHariIni}, selesai hari ini: {$selesaiHariIni}. "
            . "PO terlambat: {$terlambat->count()}, deadline < 7 hari: {$deadlines->count()}. Rijek rate 30 hari: {$rijekRate}% ({$totalRijek} pcs).";
        $this->appendAiInsight($lines, $aiCtx);

        $lines[] = "";
        $lines[] = "_" . $this->getAppName() . " · {$brand->kode} · " . strtoupper($periode) . " · " . now()->format('d/m/Y H:i') . "_";
        return implode("\n", $lines);
    }

    /** ===== Template C: Admin Brand ===== */
    public function adminBrand(Brand $brand, string $periode): string
    {
        $bid = $brand->id;

        $statusRows = Order::where('brand_id', $bid)
            ->select('status_po', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status_po')->pluck('cnt', 'status_po');

        $masuk   = (int)(($statusRows['published'] ?? 0) + ($statusRows['on_progress'] ?? 0));
        $proses  = (int)($statusRows['on_progress'] ?? 0);
        $selesai = (int)(($statusRows['selesai_produksi'] ?? 0) + ($statusRows['siap_dikirim'] ?? 0) + ($statusRows['sudah_dikirim'] ?? 0) + ($statusRows['selesai'] ?? 0));
        $delay   = (int)($statusRows['delay'] ?? 0);

        $poTerbaru = Order::where('brand_id', $bid)->where('status_po', '!=', 'draft')
            ->with(['pelanggan:id,nama', 'items:order_id,nama_produk,quantity'])
            ->orderByDesc('tanggal_masuk')->limit(3)->get();

        $deadlines = Order::where('brand_id', $bid)->whereIn('status_po', ['published', 'on_progress'])
            ->whereBetween('deadline_customer', [now(), now()->addDays(7)])
            ->with('pelanggan:id,nama')->orderBy('deadline_customer')->limit(3)->get();

        $terlambat = Order::where('brand_id', $bid)->whereNotIn('status_po', ['draft', 'sudah_dikirim', 'selesai'])
            ->where('deadline_customer', '<', today())
            ->with('pelanggan:id,nama')->orderBy('deadline_customer')->limit(3)->get();

        $revToday = Order::where('brand_id', $bid)->where('status_po', '!=', 'draft')->where('tanggal_masuk', today()->toDateString())->sum('total_tagihan');
        $revWeek  = Order::where('brand_id', $bid)->where('status_po', '!=', 'draft')->whereBetween('tanggal_masuk', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])->sum('total_tagihan');
        $revMonth = Order::where('brand_id', $bid)->where('status_po', '!=', 'draft')->whereBetween('tanggal_masuk', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->sum('total_tagihan');

        $newPelanggan = Order::where('brand_id', $bid)->where('status_po', '!=', 'draft')
            ->whereBetween('tanggal_masuk', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->distinct('pelanggan_id')->count('pelanggan_id');

        $topProduk = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.brand_id', $bid)->where('orders.status_po', '!=', 'draft')
            ->whereBetween('orders.tanggal_masuk', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->select('order_items.nama_produk', DB::raw('SUM(order_items.quantity) as total_qty'))
            ->groupBy('order_items.nama_produk')->orderByDesc('total_qty')->first();

        $lines = [
            "📊 *LAPORAN HARIAN - {$brand->nama_brand}*",
            "📅 " . now()->translatedFormat('d M Y, H:i'),
            "",
            "📦 *ORDER:*",
            "• Masuk: {$masuk} order",
            "• Proses: {$proses} order",
            "• Selesai: {$selesai} order",
            "• Delay: {$delay} order",
        ];

        if ($poTerbaru->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "📋 *DETAIL PO TERBARU:*";
            foreach ($poTerbaru as $i => $po) {
                $produk = $po->items->first()?->nama_produk ?? '-';
                $qty    = $po->items->sum('quantity');
                $lines[] = ($i + 1) . ". {$po->no_po} - {$po->pelanggan?->nama} - {$produk} x{$qty}";
            }
        }

        if ($deadlines->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "⏰ *DEADLINE MENDEKAT:*";
            foreach ($deadlines as $po) {
                $h = (int) now()->startOfDay()->diffInDays($po->deadline_customer, false);
                $lines[] = "• {$po->no_po} - {$po->pelanggan?->nama} (H-{$h})";
            }
        }

        if ($terlambat->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "⚠️ *PO TERLAMBAT:*";
            foreach ($terlambat as $po) {
                $hari = abs((int) now()->startOfDay()->diffInDays($po->deadline_customer, false));
                $lines[] = "• {$po->no_po} - {$po->pelanggan?->nama} ({$hari} hari)";
            }
        }

        $lines[] = "";
        $lines[] = "💰 *REVENUE:*";
        $lines[] = "• Hari ini: Rp " . number_format($revToday, 0, ',', '.');
        $lines[] = "• Minggu ini: Rp " . number_format($revWeek, 0, ',', '.');
        $lines[] = "• Bulan ini: Rp " . number_format($revMonth, 0, ',', '.');

        $lines[] = "";
        $lines[] = "📊 *TOPS BULAN INI:*";
        $lines[] = "• Produk Terlaris: " . ($topProduk?->nama_produk ?? '-') . " (" . ($topProduk?->total_qty ?? 0) . " pcs)";
        $lines[] = "• Pelanggan Baru: {$newPelanggan} customer";

        // AI Insight
        $aiCtx = "Brand {$brand->nama_brand}. Order masuk: {$masuk}, proses: {$proses}, selesai: {$selesai}, delay: {$delay}. "
            . "Revenue hari ini: Rp " . number_format($revToday, 0) . ", bulan ini: Rp " . number_format($revMonth, 0) . ". "
            . "Produk terlaris bulan ini: " . ($topProduk?->nama_produk ?? '-') . " ({$topProduk?->total_qty} pcs). "
            . "PO terlambat: {$terlambat->count()}, deadline mendekat: {$deadlines->count()}.";
        $this->appendAiInsight($lines, $aiCtx);

        $lines[] = "";
        $lines[] = "_" . $this->getAppName() . " · {$brand->kode} · " . strtoupper($periode) . " · " . now()->format('d/m/Y H:i') . "_";
        return implode("\n", $lines);
    }

    /** ===== Template D: Owner (per brand) ===== */
    public function owner(Brand $brand, string $periode): string
    {
        $bid = $brand->id;

        $statusRows = Order::where('brand_id', $bid)
            ->select('status_po', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status_po')->pluck('cnt', 'status_po');

        $masuk   = (int)(($statusRows['published'] ?? 0) + ($statusRows['on_progress'] ?? 0));
        $proses  = (int)($statusRows['on_progress'] ?? 0);
        $selesai = (int)(($statusRows['selesai_produksi'] ?? 0) + ($statusRows['siap_dikirim'] ?? 0) + ($statusRows['sudah_dikirim'] ?? 0) + ($statusRows['selesai'] ?? 0));
        $delay   = (int)($statusRows['delay'] ?? 0);

        $poHariIni = Order::where('brand_id', $bid)->where('status_po', '!=', 'draft')
            ->where('tanggal_masuk', today()->toDateString())
            ->with(['pelanggan:id,nama', 'items:order_id,quantity'])->limit(3)->get();

        $poProduksi = Order::where('brand_id', $bid)->where('status_po', 'on_progress')
            ->with('pelanggan:id,nama')->orderBy('deadline_customer')->limit(3)->get();

        $poSelesai = Order::where('brand_id', $bid)->where('status_po', 'selesai_produksi')
            ->whereBetween('updated_at', [today()->startOfDay(), today()->endOfDay()])
            ->with(['pelanggan:id,nama', 'items:order_id,quantity'])->limit(3)->get();

        $deadlines = Order::where('brand_id', $bid)->whereIn('status_po', ['published', 'on_progress'])
            ->whereBetween('deadline_customer', [now(), now()->addDays(3)])
            ->with('pelanggan:id,nama')->orderBy('deadline_customer')->limit(3)->get();

        $terlambat = Order::where('brand_id', $bid)->whereNotIn('status_po', ['draft', 'sudah_dikirim', 'selesai'])
            ->where('deadline_customer', '<', today())
            ->with('pelanggan:id,nama')->orderBy('deadline_customer')->limit(3)->get();

        $revToday     = Order::where('brand_id', $bid)->where('status_po', '!=', 'draft')->where('tanggal_masuk', today()->toDateString())->sum('total_tagihan');
        $revWeek      = Order::where('brand_id', $bid)->where('status_po', '!=', 'draft')->whereBetween('tanggal_masuk', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])->sum('total_tagihan');
        $revMonth     = Order::where('brand_id', $bid)->where('status_po', '!=', 'draft')->whereBetween('tanggal_masuk', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->sum('total_tagihan');
        $revLastMonth = Order::where('brand_id', $bid)->where('status_po', '!=', 'draft')->whereBetween('tanggal_masuk', [now()->subMonth()->startOfMonth()->toDateString(), now()->subMonth()->endOfMonth()->toDateString()])->sum('total_tagihan');

        $growth    = $revLastMonth > 0 ? round((($revMonth - $revLastMonth) / $revLastMonth) * 100, 1) : 0;
        $growthStr = $growth >= 0 ? "📈 +{$growth}% vs bulan lalu" : "📉 {$growth}% vs bulan lalu";

        $topProduk = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.brand_id', $bid)->where('orders.status_po', '!=', 'draft')
            ->whereBetween('orders.tanggal_masuk', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->select('order_items.nama_produk', DB::raw('SUM(order_items.quantity) as total_qty'))
            ->groupBy('order_items.nama_produk')->orderByDesc('total_qty')->first();

        $topCustomer = Order::where('brand_id', $bid)->where('status_po', '!=', 'draft')
            ->whereBetween('tanggal_masuk', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->select('pelanggan_id', DB::raw('COUNT(*) as total'))
            ->groupBy('pelanggan_id')->with('pelanggan:id,nama')->orderByDesc('total')->first();

        $lines = [
            "📊 *LAPORAN OWNER - {$brand->nama_brand}*",
            "📅 " . now()->translatedFormat('d M Y, H:i'),
            "",
            "🏢 BRAND: *{$brand->nama_brand}* ({$brand->kode})",
            "",
            "📦 *STATUS ORDER:*",
            "• Masuk: {$masuk} | Proses: {$proses} | Selesai: {$selesai} | Delay: {$delay}",
        ];

        if ($poHariIni->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "📋 *PO BARU HARI INI:*";
            foreach ($poHariIni as $i => $po) {
                $lines[] = ($i + 1) . ". {$po->no_po} - {$po->pelanggan?->nama} - " . $po->items->sum('quantity') . " pcs";
            }
        }

        if ($poProduksi->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "📋 *SEDANG PRODUKSI:*";
            foreach ($poProduksi as $i => $po) {
                $lines[] = ($i + 1) . ". {$po->no_po} - {$po->pelanggan?->nama}";
            }
        }

        if ($poSelesai->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "✅ *SELESAI HARI INI:*";
            foreach ($poSelesai as $i => $po) {
                $lines[] = ($i + 1) . ". {$po->no_po} - {$po->pelanggan?->nama} - " . $po->items->sum('quantity') . " pcs";
            }
        }

        if ($deadlines->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "⏰ *DEADLINE < 3 HARI:*";
            foreach ($deadlines as $po) {
                $h = (int) now()->startOfDay()->diffInDays($po->deadline_customer, false);
                $lines[] = "• {$po->no_po} - {$po->pelanggan?->nama} (H-{$h})";
            }
        }

        if ($terlambat->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "⚠️ *PO TERLAMBAT:*";
            foreach ($terlambat as $po) {
                $hari = abs((int) now()->startOfDay()->diffInDays($po->deadline_customer, false));
                $lines[] = "• {$po->no_po} - {$po->pelanggan?->nama} ({$hari} hari)";
            }
        }

        $lines[] = "";
        $lines[] = "💰 *REVENUE:*";
        $lines[] = "• Hari ini: Rp " . number_format($revToday, 0, ',', '.');
        $lines[] = "• Minggu ini: Rp " . number_format($revWeek, 0, ',', '.');
        $lines[] = "• Bulan ini: Rp " . number_format($revMonth, 0, ',', '.');
        $lines[] = "• {$growthStr}";

        $lines[] = "";
        $lines[] = "👥 *PELANGGAN & TOPS:*";
        $lines[] = "• Produk Terlaris: " . ($topProduk?->nama_produk ?? '-') . " (" . ($topProduk?->total_qty ?? 0) . " pcs)";
        $lines[] = "• Customer Terbesar: " . ($topCustomer?->pelanggan?->nama ?? '-');

        // AI Insight
        $aiCtx = "Brand {$brand->nama_brand}. Order masuk: {$masuk}, proses: {$proses}, selesai: {$selesai}, delay: {$delay}. "
            . "Revenue bulan ini: Rp " . number_format($revMonth, 0) . ". Pertumbuhan vs bulan lalu: {$growth}%. "
            . "PO baru hari ini: {$poHariIni->count()}, sedang produksi: {$poProduksi->count()}, selesai hari ini: {$poSelesai->count()}. "
            . "PO terlambat: {$terlambat->count()}, deadline < 3 hari: {$deadlines->count()}.";
        $this->appendAiInsight($lines, $aiCtx);

        $lines[] = "";
        $lines[] = "_" . $this->getAppName() . " · {$brand->kode} · " . strtoupper($periode) . " · " . now()->format('d/m/Y H:i') . "_";
        return implode("\n", $lines);
    }

    /** ===== Template E: Admin Keuangan ===== */
    public function keuangan(Brand $brand, string $periode): string
    {
        $bid = $brand->id;

        $pemasukanToday   = Pemasukan::where('brand_id', $bid)->where('tanggal', today()->toDateString())->sum('nominal');
        $pengeluaranToday = Pengeluaran::where('brand_id', $bid)->where('tanggal', today()->toDateString())->sum('nominal');
        $labaToday        = $pemasukanToday - $pengeluaranToday;

        $pemasukanMonth   = Pemasukan::where('brand_id', $bid)->whereBetween('tanggal', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->sum('nominal');
        $pengeluaranMonth = Pengeluaran::where('brand_id', $bid)->whereBetween('tanggal', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->sum('nominal');
        $labaMonth        = $pemasukanMonth - $pengeluaranMonth;

        $refundPending = Refund::where('brand_id', $bid)->where('status', 'pending_review')
            ->with('order:id,no_po')->orderByDesc('created_at')->limit(3)->get();

        $invoicePendingCount  = Invoice::where('brand_id', $bid)->whereIn('status', ['draft', 'validated'])->count();
        $invoicePendingAmount = Invoice::where('brand_id', $bid)->whereIn('status', ['draft', 'validated'])->sum('sisa_pembayaran');

        $invoiceJatuhTempo = Invoice::where('brand_id', $bid)
            ->where('status', 'published')
            ->whereNotNull('jatuh_tempo')
            ->where('jatuh_tempo', '<=', now()->addDays(3))
            ->with(['order:id,no_po,pelanggan_id', 'order.pelanggan:id,nama'])
            ->orderBy('jatuh_tempo')->limit(3)->get();

        $lines = [
            "📊 *LAPORAN KEUANGAN - {$brand->nama_brand}*",
            "📅 " . now()->translatedFormat('d M Y, H:i'),
            "",
            "💰 *RINGKASAN HARI INI:*",
            "• Pemasukan: Rp " . number_format($pemasukanToday, 0, ',', '.'),
            "• Pengeluaran: Rp " . number_format($pengeluaranToday, 0, ',', '.'),
            "• Laba Bersih: Rp " . number_format($labaToday, 0, ',', '.'),
            "",
            "📈 *RINGKASAN BULAN INI:*",
            "• Pemasukan: Rp " . number_format($pemasukanMonth, 0, ',', '.'),
            "• Pengeluaran: Rp " . number_format($pengeluaranMonth, 0, ',', '.'),
            "• Laba Bersih: Rp " . number_format($labaMonth, 0, ',', '.'),
        ];

        if ($refundPending->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "📋 *REFUND BARU (PENDING):*";
            foreach ($refundPending as $i => $r) {
                $lines[] = ($i + 1) . ". {$r->order?->no_po} - Rp " . number_format($r->nominal_refund, 0, ',', '.');
            }
        }

        $lines[] = "";
        $lines[] = "🧾 *INVOICE MENUNGGU:*";
        $lines[] = "• {$invoicePendingCount} Invoice (Total: Rp " . number_format($invoicePendingAmount, 0, ',', '.') . ")";

        if ($invoiceJatuhTempo->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "⚠️ *INVOICE JATUH TEMPO (< 3 HARI):*";
            foreach ($invoiceJatuhTempo as $i => $inv) {
                $lines[] = ($i + 1) . ". {$inv->order?->no_po} - {$inv->order?->pelanggan?->nama} - Rp " . number_format($inv->sisa_pembayaran, 0, ',', '.');
            }
        }

        // AI Insight
        $aiCtx = "Brand {$brand->nama_brand}. Keuangan hari ini — pemasukan: Rp " . number_format($pemasukanToday, 0)
            . ", pengeluaran: Rp " . number_format($pengeluaranToday, 0) . ", laba bersih: Rp " . number_format($labaToday, 0) . ". "
            . "Bulan ini — pemasukan: Rp " . number_format($pemasukanMonth, 0) . ", pengeluaran: Rp " . number_format($pengeluaranMonth, 0)
            . ", laba bersih: Rp " . number_format($labaMonth, 0) . ". "
            . "Refund pending: {$refundPending->count()}. Invoice menunggu: {$invoicePendingCount} (Rp " . number_format($invoicePendingAmount, 0) . "). "
            . "Invoice jatuh tempo < 3 hari: {$invoiceJatuhTempo->count()}.";
        $this->appendAiInsight($lines, $aiCtx);

        $lines[] = "";
        $lines[] = "_" . $this->getAppName() . " · {$brand->kode} · " . strtoupper($periode) . " · " . now()->format('d/m/Y H:i') . "_";
        return implode("\n", $lines);
    }
}
