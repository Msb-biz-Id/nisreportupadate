<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Settings\SystemSetting;
use App\Services\Ai\GeminiClient;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Reports\ReportMessageBuilder;
use Illuminate\Console\Command;

class SendScheduledReport extends Command
{
    protected $signature = 'reports:send
        {periode=harian : harian|mingguan|bulanan}
        {--brand= : UUID brand spesifik}
        {--force : Kirim meski enable_auto_report = false}';

    protected $description = 'Kirim laporan otomatis per-role ke WhatsApp/Telegram (BRD 17.2.2)';

    public function handle(NotificationDispatcher $dispatcher): int
    {
        $periode = $this->argument('periode');
        $force   = $this->option('force');

        // Guard: cek apakah laporan otomatis diaktifkan
        $enabled = (bool) SystemSetting::get('reports', 'enable_auto_report', false);
        if (! $enabled && ! $force) {
            $this->warn('Laporan otomatis tidak aktif. Gunakan --force untuk memaksa pengiriman.');
            return self::SUCCESS;
        }

        // Builder dengan GeminiClient — AI insight opsional (skip jika key belum dikonfigurasi)
        $ai      = GeminiClient::fromSettings();
        $builder = new ReportMessageBuilder($ai);
        if ($ai->isConfigured()) {
            $this->info('Gemini terkonfigurasi — AI Insight akan disertakan dalam laporan.');
        }

        // Jenis laporan yang diaktifkan
        $typesRaw = SystemSetting::get('reports', 'report_types', 'brand,produksi');
        $types    = array_filter(array_map('trim', explode(',', $typesRaw)));

        // Semua brand aktif diproses — hub, branch, dan regular diperlakukan sama.
        $brandId = $this->option('brand');
        $brands  = $brandId
            ? Brand::where('id', $brandId)->get()
            : Brand::active()->get();

        $totalSent = 0;

        // ── Superadmin report (satu pesan global, bukan per-brand)
        if (in_array('superadmin', $types)) {
            $recipients = $this->parseRecipients('superadmin_recipients');
            if (! empty($recipients['whatsapp']) || ! empty($recipients['telegram'])) {
                $message = $builder->superadmin($periode);
                $results = $dispatcher->send($message, $recipients);
                $sent    = collect($results)->where('success', true)->count();
                $totalSent += $sent;
                $this->info("[SUPERADMIN] Terkirim: {$sent}/" . count($results));
            } else {
                $this->warn('[SUPERADMIN] Tidak ada recipients terkonfigurasi.');
            }
        }

        // ── Per-brand reports
        foreach ($brands as $brand) {
            $this->info("── Brand: {$brand->kode} ──");

            if (in_array('produksi', $types)) {
                $r = $this->parseRecipients('produksi_recipients');
                if (! empty($r['whatsapp']) || ! empty($r['telegram'])) {
                    $msg     = $builder->adminProduksi($brand, $periode);
                    $results = $dispatcher->send($msg, $r);
                    $sent    = collect($results)->where('success', true)->count();
                    $totalSent += $sent;
                    $this->info("  [PRODUKSI] {$brand->kode}: {$sent}/" . count($results));
                }
            }

            if (in_array('brand', $types)) {
                $r = $this->parseRecipients('brand_recipients');
                if (! empty($r['whatsapp']) || ! empty($r['telegram'])) {
                    $msg     = $builder->adminBrand($brand, $periode);
                    $results = $dispatcher->send($msg, $r);
                    $sent    = collect($results)->where('success', true)->count();
                    $totalSent += $sent;
                    $this->info("  [BRAND] {$brand->kode}: {$sent}/" . count($results));
                }
            }

            if (in_array('owner', $types)) {
                $r = $this->parseRecipients('owner_recipients');
                if (! empty($r['whatsapp']) || ! empty($r['telegram'])) {
                    $msg     = $builder->owner($brand, $periode);
                    $results = $dispatcher->send($msg, $r);
                    $sent    = collect($results)->where('success', true)->count();
                    $totalSent += $sent;
                    $this->info("  [OWNER] {$brand->kode}: {$sent}/" . count($results));
                }
            }

            if (in_array('keuangan', $types)) {
                $r = $this->parseRecipients('keuangan_recipients');
                if (! empty($r['whatsapp']) || ! empty($r['telegram'])) {
                    $msg     = $builder->keuangan($brand, $periode);
                    $results = $dispatcher->send($msg, $r);
                    $sent    = collect($results)->where('success', true)->count();
                    $totalSent += $sent;
                    $this->info("  [KEUANGAN] {$brand->kode}: {$sent}/" . count($results));
                }
            }
        }

        $this->info("Selesai. Total terkirim: {$totalSent}");
        return self::SUCCESS;
    }

    /**
     * Parse recipients dari settings ke format dispatcher.
     * Fallback ke default_recipient / default_chat_id jika belum dikonfigurasi per-role.
     */
    private function parseRecipients(string $settingKey): array
    {
        $raw = SystemSetting::get('reports', $settingKey, '');
        $wa  = array_filter(array_map('trim', explode(',', $raw ?? '')));

        if (empty($wa)) {
            // Fallback ke default global
            $defaultWa = SystemSetting::get('whatsapp', 'default_recipient');
            if ($defaultWa) $wa = [$defaultWa];
        }

        $defaultTg = SystemSetting::get('telegram', 'default_chat_id');
        $tg = $defaultTg ? [$defaultTg] : [];

        return ['whatsapp' => $wa, 'telegram' => $tg];
    }
}
