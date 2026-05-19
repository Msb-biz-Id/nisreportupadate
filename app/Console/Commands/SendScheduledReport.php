<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Services\DashboardService;
use App\Services\Notifications\NotificationDispatcher;
use App\Models\Settings\SystemSetting;
use Illuminate\Console\Command;

class SendScheduledReport extends Command
{
    protected $signature = 'reports:send {periode=harian : harian|mingguan|bulanan} {--brand= : UUID brand spesifik}';

    protected $description = 'Kirim laporan otomatis ke WhatsApp/Telegram recipients';

    public function handle(DashboardService $dashboard, NotificationDispatcher $dispatcher): int
    {
        $periode = $this->argument('periode');
        $brandId = $this->option('brand');

        $brands = $brandId
            ? Brand::where('id', $brandId)->get()
            : Brand::active()->get();

        foreach ($brands as $brand) {
            $this->info("Generating $periode report for {$brand->kode}...");

            $stats = $dashboard->adminBrandStats($brand->id);
            $message = $this->formatMessage($brand, $periode, $stats);

            $recipients = $this->getRecipients();
            if (empty($recipients['whatsapp']) && empty($recipients['telegram'])) {
                $this->warn("  Tidak ada recipient terkonfigurasi.");
                continue;
            }

            $results = $dispatcher->send($message, $recipients);
            $sent = collect($results)->where('success', true)->count();
            $this->info("  Sent: {$sent}/" . count($results));
        }

        return self::SUCCESS;
    }

    private function formatMessage(Brand $brand, string $periode, array $stats): string
    {
        $lines = [
            "📊 *LAPORAN " . strtoupper($periode) . " — {$brand->nama_brand}*",
            "📅 " . now()->translatedFormat('d M Y, H:i'),
            "",
            "*RINGKASAN ORDER:*",
        ];

        foreach (($stats['cards'] ?? []) as $card) {
            $label = $card['label'];
            $value = ! empty($card['currency'])
                ? 'Rp ' . number_format((float) $card['value'], 0, ',', '.')
                : $card['value'];
            $lines[] = "• {$label}: *{$value}*";
        }

        $lines[] = "";
        $lines[] = "*STATUS PO:*";
        foreach (($stats['status_breakdown'] ?? []) as $st) {
            if ($st['count'] > 0) $lines[] = "• {$st['label']}: {$st['count']}";
        }

        $deadline = $stats['deadline_mendekat'] ?? [];
        if (count($deadline)) {
            $lines[] = "";
            $lines[] = "*⏰ DEADLINE MENDEKAT:*";
            foreach (array_slice($deadline, 0, 5) as $po) {
                $lines[] = "• {$po['no_po']} — {$po['pelanggan']} (H-{$po['days_remaining']})";
            }
        }

        $terlambat = $stats['po_terlambat'] ?? [];
        if (count($terlambat)) {
            $lines[] = "";
            $lines[] = "*⚠️ PO TERLAMBAT:*";
            foreach (array_slice($terlambat, 0, 5) as $po) {
                $lines[] = "• {$po['no_po']} — {$po['pelanggan']} ({$po['days_late']} hari)";
            }
        }

        $lines[] = "";
        $lines[] = "_NISReport — Multi-Brand Order Management_";

        return implode("\n", $lines);
    }

    private function getRecipients(): array
    {
        $waRaw = SystemSetting::get('whatsapp', 'recipients');
        $tgRaw = SystemSetting::get('telegram', 'chat_ids');
        $defaultWa = SystemSetting::get('whatsapp', 'default_recipient');
        $defaultTg = SystemSetting::get('telegram', 'default_chat_id');

        $wa = array_filter(array_map('trim', explode(',', $waRaw ?? '')));
        $tg = array_filter(array_map('trim', explode(',', $tgRaw ?? '')));

        if (empty($wa) && $defaultWa) $wa = [$defaultWa];
        if (empty($tg) && $defaultTg) $tg = [$defaultTg];

        return ['whatsapp' => $wa, 'telegram' => $tg];
    }
}
