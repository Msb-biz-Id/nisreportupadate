<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmMealAllowanceBatch;
use App\Models\Hcm\HcmOvertimeBatch;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class HcmPdfController extends Controller
{
    /**
     * PDF 1: Buku Profil Karyawan Lengkap (Employee Dossier)
     * Berisi: biodata lengkap, riwayat kontrak, kompensasi, presensi 3 bulan terakhir.
     */
    public function employeeDossier(HcmEmployee $employee): Response
    {
        Gate::authorize('hcm.manage-employees');

        $employee->load([
            'contracts' => fn($q) => $q->orderByDesc('start_date')->limit(5),
            'compensation',
            'compensationHistories' => fn($q) => $q->orderByDesc('effective_date')->limit(10),
            'attendances' => fn($q) => $q->orderByDesc('attendance_date')->limit(93), // 3 bulan
            'intern',
        ]);

        $pdf = Pdf::loadView('pdf.hcm.employee_dossier', compact('employee'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
                'dpi' => 96,
            ]);

        $filename = 'Profil-Karyawan-' . str($employee->name)->slug() . '-' . now()->format('Ymd') . '.pdf';

        ActivityLogger::log('export', 'hcm', $employee, "Download PDF Dossier Profil Karyawan: {$employee->name} ({$employee->employee_code})");

        return $pdf->download($filename);
    }

    /**
     * PDF 2: Voucher Pembayaran Lembur (Overtime Payment Voucher)
     * Berisi: detail batch lembur, rincian per karyawan, double sign-off.
     */
    public function overtimeVoucher(HcmOvertimeBatch $batch): Response
    {
        Gate::authorize('hcm.manage-overtime');

        $batch->load([
            'overtimes.employee:id,name,department,position,employee_code',
            'hcmSigner:id,name',
            'financeSigner:id,name',
        ]);

        $pdf = Pdf::loadView('pdf.hcm.overtime_voucher', compact('batch'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
                'dpi' => 96,
            ]);

        $filename = 'Voucher-Lembur-' . $batch->batch_code . '-' . now()->format('Ymd') . '.pdf';

        ActivityLogger::log('export', 'hcm', $batch, "Download PDF Voucher Lembur: {$batch->batch_code}");

        return $pdf->download($filename);
    }

    /**
     * PDF 3: Rekapitulasi Uang Makan Bulanan (Meal Allowance Report)
     * Berisi: daftar penerima, nominal, potongan alpha, status hold/cair.
     */
    public function mealAllowanceReport(HcmMealAllowanceBatch $batch): Response
    {
        Gate::authorize('hcm.manage-meal-allowance');

        $batch->load([
            'items.employee:id,name,department,position,employee_code',
            'hcmSigner:id,name',
            'financeSigner:id,name',
        ]);

        $pdf = Pdf::loadView('pdf.hcm.meal_allowance_report', compact('batch'))
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
                'dpi' => 96,
            ]);

        $filename = 'Rekapitulasi-Uang-Makan-' . $batch->batch_code . '-' . now()->format('Ymd') . '.pdf';

        ActivityLogger::log('export', 'hcm', $batch, "Download PDF Rekapitulasi Uang Makan: {$batch->batch_code}");

        return $pdf->download($filename);
    }

    /**
     * PDF 4: Surat Keterangan Pengalaman Kerja / Paklaring Resmi
     * Berisi: kop surat perusahaan, detail karyawan, masa kerja, tanda tangan.
     */
    public function paklaring(HcmEmployee $employee): Response
    {
        Gate::authorize('hcm.manage-employees');

        $employee->load(['contracts' => fn($q) => $q->orderBy('start_date')->limit(1)]);

        // Paklaring hanya untuk karyawan yang sudah tidak aktif atau atas permintaan
        $pdf = Pdf::loadView('pdf.hcm.paklaring', compact('employee'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
                'dpi' => 96,
            ]);

        $filename = 'Paklaring-' . str($employee->name)->slug() . '-' . now()->format('Ymd') . '.pdf';

        ActivityLogger::log('export', 'hcm', $employee, "Download PDF Paklaring: {$employee->name} ({$employee->employee_code})");

        return $pdf->download($filename);
    }
}
