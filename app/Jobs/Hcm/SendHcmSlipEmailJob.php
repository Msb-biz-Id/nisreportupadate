<?php

namespace App\Jobs\Hcm;

use App\Http\Controllers\Hcm\HcmPdfController;
use App\Mail\HcmSlipEmail;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmMealAllowanceItem;
use App\Models\Hcm\HcmOvertimeBatch;
use App\Models\Hcm\HcmPayrollItem;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendHcmSlipEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Jumlah percobaan eksekusi jika terjadi timeout/koneksi.
     */
    public int $tries = 3;

    /**
     * Timeout job per eksekusi.
     */
    public int $timeout = 120;

    public function __construct(
        public string $type,          // 'salary', 'meal_allowance', 'overtime'
        public int $referenceId,      // ID item atau batch
        public ?int $employeeId = null // Khusus overtime (karena grouped per employee)
    ) {}

    public function handle(): void
    {
        try {
            match ($this->type) {
                'salary' => $this->handleSalarySlip(),
                'meal_allowance' => $this->handleMealAllowanceSlip(),
                'overtime' => $this->handleOvertimeSlip(),
                default => Log::warning("Tipe slip tidak dikenal: {$this->type}"),
            };
        } catch (\Throwable $e) {
            Log::error("Gagal mengirim email slip ({$this->type} #{$this->referenceId}): " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Kirim Slip Gaji Bulanan.
     */
    protected function handleSalarySlip(): void
    {
        $item = HcmPayrollItem::with([
            'payroll.hcmSigner',
            'payroll.financeSigner',
            'employee',
        ])->find($this->referenceId);

        if (!$item || !$item->employee) {
            Log::info("Payroll item #{$this->referenceId} tidak ditemukan atau tanpa relasi employee.");
            return;
        }

        $employee = $item->employee;
        $email = trim((string) $employee->email);

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::info("Karyawan {$employee->name} ({$employee->employee_code}) tidak memiliki alamat email valid. Pengiriman slip dilewati.");
            return;
        }

        $pdf = HcmPdfController::generatePayrollSlipPdf($item);
        $pdfContent = $pdf->output();

        $empSlug = str($employee->name)->slug();
        $filename = "Slip-Gaji-{$item->payroll->period_code}-{$empSlug}.pdf";
        $periodTitle = $item->payroll->period_name ?: $item->payroll->period_code;
        $bankInfo = ($employee->bank_name ?: 'Bank BRI') . ' - No Rek: ' . ($employee->bank_account_no ?: '-') . ' a.n. ' . ($employee->bank_account_name ?: $employee->name);

        Mail::to($email)->send(new HcmSlipEmail(
            employee: $employee,
            slipType: 'salary',
            slipTitle: 'Slip Gaji Bulanan',
            periodTitle: $periodTitle,
            netAmount: (float) $item->net_salary,
            pdfContent: $pdfContent,
            pdfFilename: $filename,
            bankInfo: $bankInfo,
        ));

        ActivityLogger::log('email', 'hcm', $item, "Slip gaji {$item->payroll->period_code} berhasil dikirim ke email karyawan {$employee->name} ({$email})");
    }

    /**
     * Kirim Slip Uang Makan Bulanan.
     */
    protected function handleMealAllowanceSlip(): void
    {
        $item = HcmMealAllowanceItem::with([
            'batch.hcmSigner',
            'batch.financeSigner',
            'employee',
        ])->find($this->referenceId);

        if (!$item || !$item->employee) {
            Log::info("Meal allowance item #{$this->referenceId} tidak ditemukan.");
            return;
        }

        $employee = $item->employee;
        $email = trim((string) $employee->email);

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::info("Karyawan {$employee->name} ({$employee->employee_code}) tidak memiliki alamat email valid untuk uang makan.");
            return;
        }

        $pdf = HcmPdfController::generateMealSlipPdf($item);
        $pdfContent = $pdf->output();

        $empSlug = str($employee->name)->slug();
        $filename = "Slip-Uang-Makan-{$item->batch->batch_code}-{$empSlug}.pdf";
        $periodTitle = Carbon::createFromDate($item->batch->period_year, $item->batch->period_month, 1)->translatedFormat('F Y');
        $bankInfo = ($employee->bank_name ?: 'Bank BRI') . ' - No Rek: ' . ($employee->bank_account_no ?: '-') . ' a.n. ' . ($employee->bank_account_name ?: $employee->name);

        Mail::to($email)->send(new HcmSlipEmail(
            employee: $employee,
            slipType: 'meal_allowance',
            slipTitle: 'Slip Uang Makan Bulanan',
            periodTitle: $periodTitle,
            netAmount: (float) $item->payable_amount,
            pdfContent: $pdfContent,
            pdfFilename: $filename,
            bankInfo: $bankInfo,
            notes: $item->notes
        ));

        ActivityLogger::log('email', 'hcm', $item, "Slip uang makan {$item->batch->batch_code} berhasil dikirim ke email {$employee->name} ({$email})");
    }

    /**
     * Kirim Slip Lembur Karyawan per Batch.
     */
    protected function handleOvertimeSlip(): void
    {
        if (!$this->employeeId) {
            return;
        }

        $batch = HcmOvertimeBatch::with(['hcmSigner', 'financeSigner'])->find($this->referenceId);
        $employee = HcmEmployee::find($this->employeeId);

        if (!$batch || !$employee) {
            Log::info("Batch lembur #{$this->referenceId} atau employee #{$this->employeeId} tidak ditemukan.");
            return;
        }

        $email = trim((string) $employee->email);
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::info("Karyawan {$employee->name} ({$employee->employee_code}) tidak memiliki alamat email valid untuk lembur.");
            return;
        }

        $overtimes = $batch->overtimes()->where('employee_id', $employee->id)->get();
        if ($overtimes->isEmpty()) {
            return;
        }

        $pdf = HcmPdfController::generateOvertimeSlipPdf($batch, $employee);
        $pdfContent = $pdf->output();

        $empSlug = str($employee->name)->slug();
        $filename = "Slip-Lembur-{$batch->batch_code}-{$empSlug}.pdf";
        $periodTitle = Carbon::parse($batch->period_start)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($batch->period_end)->translatedFormat('d M Y');
        $totalAmount = (float) $overtimes->sum('total_amount');
        $bankInfo = ($employee->bank_name ?: 'Bank BRI') . ' - No Rek: ' . ($employee->bank_account_no ?: '-') . ' a.n. ' . ($employee->bank_account_name ?: $employee->name);

        Mail::to($email)->send(new HcmSlipEmail(
            employee: $employee,
            slipType: 'overtime',
            slipTitle: 'Slip Upah Lembur',
            periodTitle: $periodTitle,
            netAmount: $totalAmount,
            pdfContent: $pdfContent,
            pdfFilename: $filename,
            bankInfo: $bankInfo
        ));

        ActivityLogger::log('email', 'hcm', $batch, "Slip lembur {$batch->batch_code} berhasil dikirim ke email {$employee->name} ({$email})");
    }
}
