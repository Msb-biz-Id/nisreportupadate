<?php

namespace App\Console\Commands;

use App\Models\Order\Invoice;
use App\Services\Notifications\InvoiceWhatsappService;
use App\Services\Notifications\SidobeClient;
use Illuminate\Console\Command;

class SendInvoiceReminders extends Command
{
    protected $signature = 'invoices:send-reminders
        {--days=3 : Kirim reminder N hari sebelum jatuh tempo}
        {--dry-run : Preview tanpa mengirim}';

    protected $description = 'Kirim reminder WA untuk invoice mendekati / melewati jatuh tempo (BRD 13.5.3)';

    public function handle(): int
    {
        $service  = new InvoiceWhatsappService(SidobeClient::fromSettings());
        $days     = (int) $this->option('days');
        $dryRun   = $this->option('dry-run');

        // Mendekati jatuh tempo
        $reminders = Invoice::whereIn('status', ['published', 'sent'])
            ->whereNotNull('jatuh_tempo')
            ->whereBetween('jatuh_tempo', [today(), today()->addDays($days)])
            ->with(['order.pelanggan', 'brand'])
            ->get();

        // Sudah melewati jatuh tempo
        $overdues = Invoice::whereIn('status', ['published', 'sent'])
            ->whereNotNull('jatuh_tempo')
            ->where('jatuh_tempo', '<', today())
            ->with(['order.pelanggan', 'brand'])
            ->get();

        $this->info("Reminder (≤{$days} hari): {$reminders->count()} | Overdue: {$overdues->count()}");

        if ($dryRun) {
            $reminders->each(fn ($inv) => $this->line("  [DRY] REMINDER {$inv->invoice_number} — {$inv->order?->pelanggan?->nama} — {$inv->jatuh_tempo?->format('d/m/Y')}"));
            $overdues->each(fn ($inv) => $this->line("  [DRY] OVERDUE  {$inv->invoice_number} — {$inv->order?->pelanggan?->nama} — {$inv->jatuh_tempo?->format('d/m/Y')}"));
            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($reminders as $inv) {
            $result = $service->send($inv, 'reminder');
            if ($result['success']) {
                $inv->update(['sent_via' => 'whatsapp', 'sent_at' => now()]);
                $sent++;
                $this->info("  ✓ REMINDER {$inv->invoice_number}");
            } else {
                $this->warn("  ✗ REMINDER gagal {$inv->invoice_number}: " . ($result['error'] ?? '-'));
            }
        }

        foreach ($overdues as $inv) {
            $result = $service->send($inv, 'overdue');
            if ($result['success']) {
                $inv->update(['status' => 'overdue', 'sent_via' => 'whatsapp', 'sent_at' => now()]);
                $sent++;
                $this->info("  ✓ OVERDUE {$inv->invoice_number}");
            } else {
                $this->warn("  ✗ OVERDUE gagal {$inv->invoice_number}: " . ($result['error'] ?? '-'));
            }
        }

        $this->info("Selesai. Total terkirim: {$sent}");
        return self::SUCCESS;
    }
}
