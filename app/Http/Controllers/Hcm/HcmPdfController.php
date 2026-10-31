<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Hcm\HcmRecruitmentController;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmJobApplicant;
use App\Models\Hcm\HcmJobPosting;
use App\Models\Hcm\HcmMealAllowanceBatch;
use App\Models\Hcm\HcmMealAllowanceItem;
use App\Models\Hcm\HcmOvertimeBatch;
use App\Models\Hcm\HcmPayroll;
use App\Models\Hcm\HcmPayrollItem;
use App\Services\ActivityLogger;
use App\Services\HcmPdfHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class HcmPdfController extends Controller
{
    /**
     * PDF 1: Buku Profil Karyawan Lengkap (Employee Dossier)
     * Berisi: biodata lengkap, riwayat kontrak, kompensasi, presensi 3 bulan terakhir.
     */
    public function employeeDossier(Request $request, HcmEmployee $employee): Response
    {
        Gate::authorize('hcm.manage-employees');

        $employee->load([
            'contracts' => fn($q) => $q->orderByDesc('start_date')->limit(5),
            'compensation',
            'compensationHistories' => fn($q) => $q->orderByDesc('effective_date')->limit(10),
            'attendances' => fn($q) => $q->orderByDesc('attendance_date')->limit(93), // 3 bulan
            'intern',
            'onboarding',
            'offboarding',
        ]);

        $profile = HcmPdfHelper::getProfileData($employee);

        $pdf = Pdf::loadView('pdf.hcm.employee_dossier', compact('employee', 'profile'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
                'dpi' => 96,
            ]);

        $typePrefix = $employee->is_intern ? 'Buku-Riwayat-Magang-' : 'Buku-Riwayat-Karyawan-';
        $filename = $typePrefix . str($employee->name)->slug() . '-' . now()->format('Ymd') . '.pdf';

        ActivityLogger::log('export', 'hcm', $employee, "Download/Preview PDF Buku Riwayat Karyawan: {$employee->name} ({$employee->employee_code})");

        if ($request->query('action') === 'stream' || $request->boolean('preview')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * PDF 2: Voucher Pembayaran Lembur (Overtime Payment Voucher)
     * Berisi: detail batch lembur, rincian per karyawan, double sign-off.
     */
    public function overtimeVoucher(Request $request, HcmOvertimeBatch $batch): Response
    {
        Gate::authorize('hcm.manage-overtime');

        $batch->load([
            'overtimes.employee:id,name,department,division,employee_code',
            'hcmSigner:id,name',
            'financeSigner:id,name',
        ]);

        $profile = HcmPdfHelper::getProfileData();

        $pdf = Pdf::loadView('pdf.hcm.overtime_voucher', compact('batch', 'profile'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
                'dpi' => 96,
            ]);

        $filename = 'Voucher-Lembur-' . $batch->batch_code . '-' . now()->format('Ymd') . '.pdf';

        ActivityLogger::log('export', 'hcm', $batch, "Download/Preview PDF Voucher Lembur: {$batch->batch_code}");

        if ($request->query('action') === 'stream' || $request->boolean('preview')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * PDF 3: Rekapitulasi Uang Makan Bulanan (Meal Allowance Report)
     * Berisi: daftar penerima, nominal, potongan alpha, status hold/cair.
     */
    public function mealAllowanceReport(Request $request, HcmMealAllowanceBatch $batch): Response
    {
        Gate::authorize('hcm.manage-meal-allowance');

        $batch->load([
            'items.employee:id,name,department,division,employee_code',
            'hcmSigner:id,name',
            'financeSigner:id,name',
        ]);

        $profile = HcmPdfHelper::getProfileData();

        $pdf = Pdf::loadView('pdf.hcm.meal_allowance_report', compact('batch', 'profile'))
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
                'dpi' => 96,
            ]);

        $filename = 'Rekapitulasi-Uang-Makan-' . $batch->batch_code . '-' . now()->format('Ymd') . '.pdf';

        ActivityLogger::log('export', 'hcm', $batch, "Download/Preview PDF Rekapitulasi Uang Makan: {$batch->batch_code}");

        if ($request->query('action') === 'stream' || $request->boolean('preview')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * PDF 4: Surat Keterangan Pengalaman Kerja / Paklaring Resmi
     * Berisi: kop surat perusahaan & divisi HCM, detail karyawan, masa kerja, tanda tangan otomatis.
     */
    public function paklaring(Request $request, HcmEmployee $employee): Response
    {
        Gate::authorize('hcm.manage-employees');

        $employee->load(['contracts' => fn($q) => $q->orderBy('start_date')->limit(1)]);

        $profile = HcmPdfHelper::getProfileData($employee);

        $pdf = Pdf::loadView('pdf.hcm.paklaring', compact('employee', 'profile'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
                'dpi' => 96,
            ]);

        $filename = 'Paklaring-' . str($employee->name)->slug() . '-' . now()->format('Ymd') . '.pdf';

        ActivityLogger::log('export', 'hcm', $employee, "Download/Preview PDF Paklaring: {$employee->name} ({$employee->employee_code})");

        if ($request->query('action') === 'stream' || $request->boolean('preview')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * PDF 5: Laporan Performa Rekrutmen per Loker (Beserta Detail Pelamar).
     * Opsi ?job_id= untuk laporan satu loker saja.
     */
    public function recruitmentReport(Request $request): Response
    {
        Gate::authorize('hcm.manage-recruitment');

        $rows = HcmRecruitmentController::buildJobPerformance();

        $jobId = $request->query('job_id');
        $selectedJob = $jobId
            ? (is_numeric($jobId) ? HcmJobPosting::find($jobId) : HcmJobPosting::where('slug', $jobId)->first())
            : null;
        $applicants = $selectedJob
            ? HcmJobApplicant::with('interviews')->where('job_posting_id', $selectedJob->id)->orderByDesc('id')->get()
            : collect();

        $summary = [
            'total_jobs' => $rows->count(),
            'total_applicants' => $rows->sum('total_applicants'),
            'total_hired' => $rows->sum('hired'),
            'avg_fulfillment' => $rows->count() > 0 ? round($rows->avg('fulfillment_rate'), 1) : 0,
        ];

        $channels = HcmRecruitmentController::channelPerformance();

        $profile = HcmPdfHelper::getProfileData();

        $pdf = Pdf::loadView('pdf.hcm.recruitment_report', compact('rows', 'selectedJob', 'applicants', 'summary', 'channels', 'profile'))
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
                'dpi' => 96,
            ]);

        $scope = $selectedJob ? str($selectedJob->title)->slug() : 'Semua-Loker';
        $filename = 'Laporan-Rekrutmen-' . $scope . '-' . now()->format('Ymd') . '.pdf';

        ActivityLogger::log('export', 'hcm', $selectedJob, 'Cetak laporan performa rekrutmen' . ($selectedJob ? ": {$selectedJob->title}" : ' (semua loker)'));

        if ($request->query('action') === 'stream' || $request->boolean('preview')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * PDF 6: Profil & Hasil Wawancara Pelamar (Per Pelamar).
     */
    public function applicantReport(Request $request, HcmJobApplicant $applicant): Response
    {
        Gate::authorize('hcm.manage-recruitment');

        $applicant->load(['jobPosting', 'interviews.creator:id,name']);

        $profile = HcmPdfHelper::getProfileData();

        $pdf = Pdf::loadView('pdf.hcm.recruitment_applicant', compact('applicant', 'profile'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
                'dpi' => 96,
            ]);

        $filename = 'Profil-Pelamar-' . $applicant->applicant_code . '-' . now()->format('Ymd') . '.pdf';

        ActivityLogger::log('export', 'hcm', $applicant, "Cetak profil pelamar {$applicant->name} ({$applicant->applicant_code})");

        if ($request->query('action') === 'stream' || $request->boolean('preview')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * PDF 7: Slip Gaji Digital Transparan Karyawan (Official Payroll Slip).
     * Berisi: identitas, pendapatan itemized, pemotongan itemized, take-home pay, terbilang, QR verifikasi, double sign-off.
     */
    public function payrollSlip(Request $request, HcmPayrollItem $item): Response
    {
        Gate::authorize('hcm.manage-compensation');

        $item->load([
            'payroll.hcmSigner:id,name',
            'payroll.financeSigner:id,name',
            'employee:id,name,employee_code,department,division,employment_status,legal_entity,bank_name,bank_account_no,bank_account_name,join_date,original_join_date',
        ]);

        $payroll = $item->payroll;
        $employee = $item->employee;
        $profile = HcmPdfHelper::getProfileData($employee);
        $terbilang = HcmPdfHelper::terbilang($item->net_salary);

        $verificationUrl = route('hcm.payroll.verify-slip', $item->slip_token);
        $qrCodeSvg = QrCode::format('svg')
            ->size(110)
            ->margin(0)
            ->errorCorrection('M')
            ->generate($verificationUrl);
        $qrCodeBase64 = base64_encode($qrCodeSvg);

        $pdf = Pdf::loadView('pdf.hcm.payroll_slip', compact('item', 'payroll', 'employee', 'profile', 'terbilang', 'qrCodeBase64', 'verificationUrl'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
                'dpi' => 96,
            ]);

        $empSlug = str($employee?->name ?? 'karyawan')->slug();
        $filename = "Slip-Gaji-{$payroll->period_code}-{$empSlug}.pdf";

        ActivityLogger::log('export', 'hcm', $item, "Cetak slip gaji digital: {$employee?->name} ({$payroll->period_code})");

        if ($request->query('action') === 'stream' || $request->boolean('preview')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * Verifikasi Keabsahan Slip Gaji via QR Code / Token Publik.
     */
    public function verifySlipToken(string $token): Response
    {
        $item = HcmPayrollItem::with([
            'payroll.hcmSigner:id,name',
            'payroll.financeSigner:id,name',
            'employee:id,name,employee_code,department,division,employment_status,legal_entity,bank_name,bank_account_no,bank_account_name,join_date,original_join_date',
        ])->where('slip_token', $token)->firstOrFail();

        $payroll = $item->payroll;
        $employee = $item->employee;
        $profile = HcmPdfHelper::getProfileData($employee);
        $terbilang = HcmPdfHelper::terbilang($item->net_salary);

        $verificationUrl = route('hcm.payroll.verify-slip', $item->slip_token);
        $qrCodeSvg = QrCode::format('svg')
            ->size(110)
            ->margin(0)
            ->errorCorrection('M')
            ->generate($verificationUrl);
        $qrCodeBase64 = base64_encode($qrCodeSvg);

        $pdf = Pdf::loadView('pdf.hcm.payroll_slip', compact('item', 'payroll', 'employee', 'profile', 'terbilang', 'qrCodeBase64', 'verificationUrl'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
                'dpi' => 96,
            ]);

        $empSlug = str($employee?->name ?? 'karyawan')->slug();
        $filename = "Verifikasi-Slip-Gaji-{$payroll->period_code}-{$empSlug}.pdf";

        return $pdf->stream($filename);
    }

    /**
     * PDF 8: Ringkasan Eksekutif Batch Penggajian (Multi-Level Grouping Departemen -> Divisi).
     */
    public function payrollBatchSummaryPdf(Request $request, HcmPayroll $payroll): Response
    {
        Gate::authorize('hcm.manage-compensation');

        $payroll->load([
            'creator:id,name',
            'hcmSigner:id,name',
            'financeSigner:id,name',
            'items.employee:id,employee_code,name,nickname,department,division',
        ]);

        $profile = HcmPdfHelper::getProfileData();

        // Multi-level grouping (Departemen -> Divisi) Zero N+1
        $departmentGrouping = [];
        foreach ($payroll->items as $item) {
            $dept = $item->department ?: 'Departemen Lainnya';
            $div = $item->division ?: 'Umum';

            if (!isset($departmentGrouping[$dept])) {
                $departmentGrouping[$dept] = [
                    'department_name' => $dept,
                    'total_employees' => 0,
                    'total_base_salary' => 0,
                    'total_meal_allowance' => 0,
                    'total_overtime_pay' => 0,
                    'total_earnings' => 0,
                    'total_deductions' => 0,
                    'total_net_salary' => 0,
                    'divisions' => [],
                ];
            }

            if (!isset($departmentGrouping[$dept]['divisions'][$div])) {
                $departmentGrouping[$dept]['divisions'][$div] = [
                    'division_name' => $div,
                    'total_employees' => 0,
                    'total_net_salary' => 0,
                    'items' => [],
                ];
            }

            $departmentGrouping[$dept]['divisions'][$div]['total_employees']++;
            $departmentGrouping[$dept]['divisions'][$div]['total_net_salary'] += (float) $item->net_salary;
            $departmentGrouping[$dept]['divisions'][$div]['items'][] = $item;

            $departmentGrouping[$dept]['total_employees']++;
            $departmentGrouping[$dept]['total_base_salary'] += (float) $item->base_salary;
            $departmentGrouping[$dept]['total_meal_allowance'] += (float) $item->meal_allowance;
            $departmentGrouping[$dept]['total_overtime_pay'] += (float) $item->overtime_pay;
            $departmentGrouping[$dept]['total_earnings'] += (float) $item->total_earnings;
            $departmentGrouping[$dept]['total_deductions'] += (float) $item->total_deductions;
            $departmentGrouping[$dept]['total_net_salary'] += (float) $item->net_salary;
        }

        $pdf = Pdf::loadView('pdf.hcm.payroll_batch_summary', compact('payroll', 'departmentGrouping', 'profile'))
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
                'dpi' => 96,
            ]);

        $filename = "Rekap-Payroll-{$payroll->period_code}-" . now()->format('Ymd') . '.pdf';

        ActivityLogger::log('export', 'hcm', $payroll, "Cetak PDF ringkasan eksekutif batch payroll {$payroll->period_code}");

        if ($request->query('action') === 'stream' || $request->boolean('preview')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * PDF 9: Slip Uang Makan Digital Karyawan (Official Meal Allowance Slip).
     */
    public function mealAllowanceSlip(Request $request, HcmMealAllowanceItem $item): Response
    {
        Gate::authorize('hcm.manage-meal-allowance');

        $pdf = self::generateMealSlipPdf($item);
        $empSlug = str($item->employee?->name ?? 'karyawan')->slug();
        $filename = "Slip-Uang-Makan-{$item->batch?->batch_code}-{$empSlug}.pdf";

        ActivityLogger::log('export', 'hcm', $item, "Cetak slip uang makan digital: {$item->employee?->name} ({$item->batch?->batch_code})");

        if ($request->query('action') === 'stream' || $request->boolean('preview')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * Verifikasi Publik Slip Uang Makan via QR Code / Token UUID.
     */
    public function verifyMealSlip(string $uuid): Response
    {
        $item = HcmMealAllowanceItem::with([
            'batch.hcmSigner:id,name',
            'batch.financeSigner:id,name',
            'employee:id,name,employee_code,department,division,employment_status,bank_name,bank_account_no,bank_account_name',
        ])->where('uuid', $uuid)->firstOrFail();

        $pdf = self::generateMealSlipPdf($item);
        $empSlug = str($item->employee?->name ?? 'karyawan')->slug();
        $filename = "Verifikasi-Slip-Uang-Makan-{$item->batch?->batch_code}-{$empSlug}.pdf";

        return $pdf->stream($filename);
    }

    /**
     * PDF 10: Slip Upah Lembur Karyawan per Batch Mingguan.
     */
    public function overtimeSlip(Request $request, HcmOvertimeBatch $batch, HcmEmployee $employee): Response
    {
        Gate::authorize('hcm.manage-overtime');

        $pdf = self::generateOvertimeSlipPdf($batch, $employee);
        $empSlug = str($employee->name)->slug();
        $filename = "Slip-Lembur-{$batch->batch_code}-{$empSlug}.pdf";

        ActivityLogger::log('export', 'hcm', $batch, "Cetak slip upah lembur: {$employee->name} ({$batch->batch_code})");

        if ($request->query('action') === 'stream' || $request->boolean('preview')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * Verifikasi Publik Slip Lembur via QR Code / Token.
     */
    public function verifyOvertimeSlip(HcmOvertimeBatch $batch, HcmEmployee $employee): Response
    {
        $pdf = self::generateOvertimeSlipPdf($batch, $employee);
        $empSlug = str($employee->name)->slug();
        $filename = "Verifikasi-Slip-Lembur-{$batch->batch_code}-{$empSlug}.pdf";

        return $pdf->stream($filename);
    }

    /**
     * Helper Generator PDF Slip Uang Makan (Bisa dipakai Controller maupun Queue Job Email).
     */
    public static function generateMealSlipPdf(HcmMealAllowanceItem $item)
    {
        $item->loadMissing([
            'batch.hcmSigner:id,name',
            'batch.financeSigner:id,name',
            'employee:id,name,employee_code,department,division,employment_status,bank_name,bank_account_no,bank_account_name',
        ]);

        $batch = $item->batch;
        $employee = $item->employee;
        $profile = HcmPdfHelper::getProfileData($employee);
        $terbilang = HcmPdfHelper::terbilang((float) $item->payable_amount);

        $verificationUrl = route('hcm.meal-allowance.verify-slip', $item->uuid ?? $item->id);
        $qrCodeSvg = QrCode::format('svg')
            ->size(110)
            ->margin(0)
            ->errorCorrection('M')
            ->generate($verificationUrl);
        $qrCodeBase64 = base64_encode($qrCodeSvg);

        return Pdf::loadView('pdf.hcm.meal_allowance_slip', compact('item', 'batch', 'employee', 'profile', 'terbilang', 'qrCodeBase64', 'verificationUrl'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
                'dpi' => 96,
            ]);
    }

    /**
     * Helper Generator PDF Slip Lembur Karyawan (Bisa dipakai Controller maupun Queue Job Email).
     */
    public static function generateOvertimeSlipPdf(HcmOvertimeBatch $batch, HcmEmployee $employee)
    {
        $batch->loadMissing([
            'hcmSigner:id,name',
            'financeSigner:id,name',
        ]);

        $overtimes = $batch->overtimes()
            ->where('employee_id', $employee->id)
            ->orderBy('overtime_date')
            ->orderBy('id')
            ->get();

        $totalAmount = $overtimes->sum('total_amount');
        $profile = HcmPdfHelper::getProfileData($employee);
        $terbilang = HcmPdfHelper::terbilang((float) $totalAmount);

        $verificationUrl = route('hcm.overtime.verify-slip', ['batch' => $batch->batch_code, 'employee' => $employee->employee_code]);
        $qrCodeSvg = QrCode::format('svg')
            ->size(110)
            ->margin(0)
            ->errorCorrection('M')
            ->generate($verificationUrl);
        $qrCodeBase64 = base64_encode($qrCodeSvg);

        return Pdf::loadView('pdf.hcm.overtime_slip', compact('batch', 'employee', 'overtimes', 'profile', 'terbilang', 'qrCodeBase64', 'verificationUrl'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
                'dpi' => 96,
            ]);
    }

    /**
     * Helper Generator PDF Slip Gaji Karyawan (Bisa dipakai Controller maupun Queue Job Email).
     */
    public static function generatePayrollSlipPdf(HcmPayrollItem $item)
    {
        $item->loadMissing([
            'payroll.hcmSigner:id,name',
            'payroll.financeSigner:id,name',
            'employee:id,name,employee_code,department,division,employment_status,legal_entity,bank_name,bank_account_no,bank_account_name,join_date,original_join_date',
        ]);

        $payroll = $item->payroll;
        $employee = $item->employee;
        $profile = HcmPdfHelper::getProfileData($employee);
        $terbilang = HcmPdfHelper::terbilang((float) $item->net_salary);

        $verificationUrl = route('hcm.payroll.verify-slip', $item->slip_token);
        $qrCodeSvg = QrCode::format('svg')
            ->size(110)
            ->margin(0)
            ->errorCorrection('M')
            ->generate($verificationUrl);
        $qrCodeBase64 = base64_encode($qrCodeSvg);

        return Pdf::loadView('pdf.hcm.payroll_slip', compact('item', 'payroll', 'employee', 'profile', 'terbilang', 'qrCodeBase64', 'verificationUrl'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
                'dpi' => 96,
            ]);
    }
}
