<?php

namespace App\Console\Commands\Purchasing;

use App\Models\Purchasing\PurchasingPayment;
use App\Services\Notifications\IdealNotificationService;
use App\Services\Purchasing\PurchasingPaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckDuePaymentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purchasing:check-due-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periksa jatuh tempo cicilan termin pembelian (TOP), update status DUE_TODAY/OVERDUE, dan kirimkan notifikasi alert';

    /**
     * Execute the console command.
     */
    public function handle(PurchasingPaymentService $paymentService): int
    {
        $this->info('Memulai pengecekan jatuh tempo termin pembelian (TOP)...');

        $result = $paymentService->checkAndUpdateDueStatuses();

        $dueTodayList = PurchasingPayment::with('order.vendor')
            ->where('status', 'DUE_TODAY')
            ->get();

        $overdueList = PurchasingPayment::with('order.vendor')
            ->where('status', 'OVERDUE')
            ->get();

        $this->line("Tagihan Jatuh Tempo Hari Ini: {$dueTodayList->count()}");
        $this->line("Tagihan Lewat Tempo (Overdue): {$overdueList->count()}");

        // Kirim notifikasi jika ada tagihan jatuh tempo hari ini
        foreach ($dueTodayList as $payment) {
            try {
                IdealNotificationService::dispatch('purchasing_payment_due_today', [
                    'no_po' => $payment->order?->po_number ?? 'PO',
                    'term_name' => $payment->term_name,
                    'amount' => $payment->amount,
                    'due_date' => $payment->due_date?->format('Y-m-d'),
                    'vendor_name' => $payment->order?->vendor?->name ?? 'Vendor',
                    'status' => 'DUE_TODAY',
                    'message' => "Tagihan {$payment->term_name} untuk PO {$payment->order?->po_number} sebesar Rp " . number_format($payment->amount, 0, ',', '.') . " JATUH TEMPO HARI INI.",
                ]);
            } catch (\Throwable $e) {
                Log::warning("Gagal mengirim notifikasi jatuh tempo: " . $e->getMessage());
            }
        }

        $this->info('Pengecekan termin pembelian selesai.');

        return Command::SUCCESS;
    }
}
