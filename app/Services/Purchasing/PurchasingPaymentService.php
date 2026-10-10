<?php

namespace App\Services\Purchasing;

use App\Models\Purchasing\PurchasingOrder;
use App\Models\Purchasing\PurchasingPayment;
use App\Models\User;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PurchasingPaymentService
{
    /**
     * Generate or update payment term installments for a PO.
     * Enforces strict mathematical equality between sum of installments and PO Grand Total.
     */
    public function generateTerms(PurchasingOrder $order, array $terms, User $user): Collection
    {
        return DB::transaction(function () use ($order, $terms, $user) {
            $grandTotal = (float) $order->grand_total;
            $accumulatedAmount = 0.0;

            foreach ($terms as $t) {
                $accumulatedAmount += round((float) ($t['amount'] ?? 0), 2);
            }
            $accumulatedAmount = round($accumulatedAmount, 2);

            // Validasi mutlak selisih Rp 0
            if (abs($accumulatedAmount - $grandTotal) > 0.01) {
                throw new \InvalidArgumentException(
                    "Total akumulasi nominal termin (Rp " . number_format($accumulatedAmount, 0, ',', '.') .
                    ") harus tepat sama dengan Grand Total PO (Rp " . number_format($grandTotal, 0, ',', '.') . ")."
                );
            }

            // Hapus termin non-PAID yang ada sebelumnya untuk PO ini
            $order->payments()->where('status', '!=', 'PAID')->delete();

            $today = Carbon::today()->toDateString();
            $createdTerms = collect([]);

            foreach ($terms as $index => $t) {
                $dueDate = Carbon::parse($t['due_date'])->toDateString();
                $amount = round((float) $t['amount'], 2);
                $percentage = (float) ($t['term_percentage'] ?? round(($amount / max($grandTotal, 1)) * 100, 2));

                $status = 'PENDING';
                if ($dueDate < $today) {
                    $status = 'OVERDUE';
                } elseif ($dueDate === $today) {
                    $status = 'DUE_TODAY';
                }

                $payment = PurchasingPayment::create([
                    'order_id' => $order->id,
                    'invoice_number' => $order->invoice_number ?? $order->po_number,
                    'term_step' => (int) ($t['term_step'] ?? ($index + 1)),
                    'term_name' => $t['term_name'] ?? ('Termin ' . ($index + 1)),
                    'term_percentage' => $percentage,
                    'amount' => $amount,
                    'due_date' => $dueDate,
                    'status' => $status,
                    'notes' => $t['notes'] ?? null,
                ]);

                $createdTerms->push($payment);
            }

            ActivityLogger::log('update', 'purchasing', $order, "Membuat jadwal " . count($createdTerms) . " termin cicilan untuk PO {$order->po_number}");

            return $createdTerms;
        });
    }

    /**
     * Record payment clearance & upload transfer receipt by Finance.
     */
    public function recordPayment(PurchasingPayment $payment, array $data, ?UploadedFile $receiptFile, User $user): PurchasingPayment
    {
        return DB::transaction(function () use ($payment, $data, $receiptFile, $user) {
            $receiptFilePath = $payment->receipt_file_path;
            if ($receiptFile && $receiptFile->isValid()) {
                if ($payment->receipt_file_path && Storage::disk('public')->exists($payment->receipt_file_path)) {
                    Storage::disk('public')->delete($payment->receipt_file_path);
                }
                $receiptFilePath = $this->storeReceiptFile($receiptFile, $payment->order?->po_number ?? 'PO');
            }

            $payment->update([
                'status' => 'PAID',
                'paid_at' => $data['paid_at'] ?? Carbon::now()->toDateString(),
                'payment_method' => $data['payment_method'] ?? 'Transfer Bank',
                'receipt_file_path' => $receiptFilePath,
                'verified_by' => $user->id,
                'notes' => $data['notes'] ?? $payment->notes,
            ]);

            $order = $payment->order;
            if ($order) {
                // Cek apakah seluruh termin pada order ini sudah berstatus PAID
                $unpaidCount = $order->payments()->where('status', '!=', 'PAID')->count();
                if ($unpaidCount === 0) {
                    $order->update(['status' => 'PAID_COMPLETED']);
                }
            }

            ActivityLogger::log('approve', 'purchasing', $payment, "Pencairan dana {$payment->term_name} sebesar Rp " . number_format($payment->amount, 0, ',', '.') . " untuk PO {$payment->order?->po_number} diverifikasi oleh {$user->name}");

            return $payment;
        });
    }

    /**
     * Check and synchronize due date statuses for all pending payments.
     */
    public function checkAndUpdateDueStatuses(): array
    {
        $today = Carbon::today()->toDateString();

        $dueTodayUpdated = PurchasingPayment::where('status', '!=', 'PAID')
            ->whereDate('due_date', '=', $today)
            ->where('status', '!=', 'DUE_TODAY')
            ->update(['status' => 'DUE_TODAY']);

        $overdueUpdated = PurchasingPayment::where('status', '!=', 'PAID')
            ->whereDate('due_date', '<', $today)
            ->where('status', '!=', 'OVERDUE')
            ->update(['status' => 'OVERDUE']);

        $pendingRestored = PurchasingPayment::where('status', '!=', 'PAID')
            ->whereDate('due_date', '>', $today)
            ->where('status', '!=', 'PENDING')
            ->update(['status' => 'PENDING']);

        return [
            'due_today_count' => $dueTodayUpdated,
            'overdue_count' => $overdueUpdated,
            'pending_restored' => $pendingRestored,
        ];
    }

    /**
     * Store transfer receipt safely
     */
    protected function storeReceiptFile(UploadedFile $file, string $poNumber): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $sanitizedPo = Str::slug($poNumber);
        $fileName = "receipt_{$sanitizedPo}_" . time() . '_' . Str::random(8) . '.' . $ext;

        return $file->storeAs('purchasing/receipts', $fileName, 'public');
    }
}
