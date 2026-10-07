<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Hcm\HcmRecruitmentController;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmJobApplicant;
use App\Models\Hcm\HcmJobPosting;
use App\Models\Hcm\HcmMealAllowanceBatch;
use App\Models\Hcm\HcmOvertimeBatch;
use App\Services\ActivityLogger;
use App\Services\HcmPdfHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

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
            'overtimes.employee:id,name,department,position,employee_code',
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
            'items.employee:id,name,department,position,employee_code',
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
}
