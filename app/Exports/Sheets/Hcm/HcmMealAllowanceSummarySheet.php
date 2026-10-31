<?php

namespace App\Exports\Sheets\Hcm;

use App\Models\Hcm\HcmMealAllowanceBatch;
use App\Services\HcmPdfHelper;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class HcmMealAllowanceSummarySheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private int $metaStartRow = 7;
    private int $metaEndRow = 7;
    private int $presensiStartRow = 0;
    private int $presensiEndRow = 0;
    private int $deptStartRow = 0;
    private int $deptEndRow = 0;

    public function __construct(private HcmMealAllowanceBatch $batch, private ?string $userName = null) {}

    public function title(): string
    {
        return 'Ringkasan Uang Makan';
    }

    public function array(): array
    {
        $profile = HcmPdfHelper::getProfileData();
        $companyName = $profile['company_name'] ?? 'PT Nusantara Inti Solusindo';
        $divisionName = $profile['division_name'] ?? 'Divisi Human Capital Management';

        $items = $this->batch->items ?? collect();
        $totalEmployees = $items->count();
        $totalCair = $items->where('status', 'CAIR')->count() ?: $items->where('is_hold', false)->count();
        $totalHold = $items->where('status', 'HOLD')->count() ?: $items->where('is_hold', true)->count();

        $sumDisbursed = (float) ($this->batch->total_disbursed ?? $items->where('status', 'CAIR')->sum('final_amount'));
        if ($sumDisbursed == 0) {
            $sumDisbursed = (float) $items->where('is_hold', false)->sum('payable_amount');
        }
        $sumHeld = (float) ($this->batch->total_held ?? $items->where('status', 'HOLD')->sum('final_amount'));
        if ($sumHeld == 0) {
            $sumHeld = (float) $items->where('is_hold', true)->sum('payable_amount');
        }
        $totalBudget = $sumDisbursed + $sumHeld;

        $out = [];
        $out[] = [$companyName];
        $out[] = [$divisionName];
        $out[] = ['RINGKASAN EKSEKUTIF HAK UANG MAKAN BULANAN'];
        $out[] = [
            'Kode Batch: ' . $this->batch->batch_code,
            '',
            'Waktu Unduh: ' . now()->translatedFormat('d F Y, H:i') . ' WIB',
            '',
            'Petugas: ' . ($this->userName ?: 'Admin HCM'),
        ];
        $out[] = [''];

        // ===== 1. METADATA & REKAP KEUANGAN =====
        $out[] = ['INFORMASI BATCH & ANGGARAN UANG MAKAN', '', '', ''];
        $this->metaStartRow = count($out) + 1;
        $out[] = ['No', 'Komponen / Parameter', 'Keterangan / Nilai', 'Satuan / Detail'];
        $out[] = [1, 'Nomor / Kode Batch', $this->batch->batch_code, 'Dokumen Sah'];
        $out[] = [
            2, 'Periode Presensi Terhitung',
            $this->batch->period_start?->format('d/m/Y') . ' s.d. ' . $this->batch->period_end?->format('d/m/Y'),
            'Bulan Berjalan'
        ];
        $out[] = [3, 'Departemen / Unit Kerja', $this->batch->department ?: 'Semua Departemen', 'Lingkup'];
        $out[] = [4, 'Tarif Uang Makan Standar', 'Rp ' . number_format($this->batch->rate_monthly ?? 280000, 0, ',', '.'), 'Per Bulan Penuh'];
        $out[] = [5, 'Status Batch', $this->batch->status ?: 'COMPLETED', 'Otorisasi HCM'];
        $out[] = [6, 'Total Peserta Penerima Terdaftar', $totalEmployees, 'Orang Karyawan'];
        $out[] = [7, 'Total Peserta Lolos Verifikasi (Cair)', $totalCair, 'Orang (Hak Penuh/Pradana)'];
        $out[] = [8, 'Total Peserta Ditahan (Hold)', $totalHold, 'Orang (Pelanggaran/Alpha)'];
        $out[] = [9, 'Total Dana Dicairkan (Netto Cair)', 'Rp ' . number_format($sumDisbursed, 0, ',', '.'), 'Lolos Verifikasi'];
        $out[] = [10, 'Total Dana Ditahan / Hold', 'Rp ' . number_format($sumHeld, 0, ',', '.'), 'Ditahan Sistem'];
        $out[] = [11, 'Total Alokasi Anggaran Kotor', 'Rp ' . number_format($totalBudget, 0, ',', '.'), 'Total Anggaran'];
        $this->metaEndRow = count($out);
        $out[] = [''];

        // ===== 2. REKAPITULASI PRESENSI GLOBAL =====
        if ($items->isNotEmpty()) {
            $out[] = ['REKAPITULASI PRESENSI & TINGKAT KEHADIRAN', '', '', ''];
            $this->presensiStartRow = count($out) + 1;
            $out[] = ['No', 'Kategori Presensi', 'Total Akumulasi', 'Keterangan'];
            $out[] = [1, 'Hari Hadir Efektif', $items->sum('present_days') . ' Hari', 'Hadir Penuh di Tempat Kerja'];
            $out[] = [2, 'Terlambat Masuk (Late)', $items->sum('late_days') . ' Hari', 'Pemotongan Parsial'];
            $out[] = [3, 'Setengah Hari Kerja', $items->sum('half_days') . ' Hari', 'Pemotongan 50%'];
            $out[] = [4, 'Alpha / Mangkir Tanpa Kabar', $items->sum('alpha_days') . ' Hari', 'Potongan Hangus'];
            $out[] = [5, 'Cuti Resmi Disetujui', $items->sum('leave_days') . ' Hari', 'Hak Cuti Karyawan'];
            $out[] = [6, 'Izin / Sakit Resmi', $items->sum('permit_days') . ' Hari', 'Dengan Surat Izin Sah'];
            $this->presensiEndRow = count($out);
            $out[] = [''];
        }

        // ===== 3. REKAP PER DEPARTEMEN =====
        if ($items->isNotEmpty()) {
            $byDept = $items->groupBy(fn($i) => $i->department ?: ($i->employee?->department ?: 'Lainnya'));
            $out[] = ['REKAPITULASI DANA UANG MAKAN PER DEPARTEMEN', '', '', '', ''];
            $this->deptStartRow = count($out) + 1;
            $out[] = ['No', 'Departemen', 'Karyawan', 'Dana Cair (Rp)', 'Dana Hold (Rp)'];
            $dpNo = 1;
            foreach ($byDept as $deptName => $deptItems) {
                $dCair = $deptItems->where('status', 'CAIR')->sum('final_amount') ?: $deptItems->where('is_hold', false)->sum('payable_amount');
                $dHold = $deptItems->where('status', 'HOLD')->sum('final_amount') ?: $deptItems->where('is_hold', true)->sum('payable_amount');
                $out[] = [
                    $dpNo++,
                    $deptName,
                    $deptItems->count(),
                    'Rp ' . number_format($dCair, 0, ',', '.'),
                    'Rp ' . number_format($dHold, 0, ',', '.'),
                ];
            }
            $this->deptEndRow = count($out);
        }

        return $out;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('0F172A');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('A8001C');
                $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('1E293B');
                $sheet->getStyle('A4:E4')->getFont()->setSize(9)->getColor()->setRGB('64748B');

                // Metadata Section
                $metaTitleRow = $this->metaStartRow - 1;
                $sheet->getStyle("A{$metaTitleRow}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('0F172A');
                $sheet->getStyle("A{$this->metaStartRow}:D{$this->metaStartRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("B{$this->metaStartRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                if ($this->metaEndRow >= $this->metaStartRow) {
                    $sheet->getStyle("A{$this->metaStartRow}:D{$this->metaEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
                    for ($r = $this->metaStartRow + 1; $r <= $this->metaEndRow; $r++) {
                        $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle("C{$r}")->getFont()->setBold(true);
                        if ($r % 2 === 0) {
                            $sheet->getStyle("A{$r}:D{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
                        }
                    }
                }

                // Presensi Section
                if ($this->presensiStartRow > 0 && $this->presensiEndRow >= $this->presensiStartRow) {
                    $prTitleRow = $this->presensiStartRow - 1;
                    $sheet->getStyle("A{$prTitleRow}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('0F172A');
                    $sheet->getStyle("A{$this->presensiStartRow}:D{$this->presensiStartRow}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A8001C']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    $sheet->getStyle("B{$this->presensiStartRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("A{$this->presensiStartRow}:D{$this->presensiEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
                    for ($r = $this->presensiStartRow + 1; $r <= $this->presensiEndRow; $r++) {
                        $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle("C{$r}")->getFont()->setBold(true);
                    }
                }

                // Dept Section
                if ($this->deptStartRow > 0 && $this->deptEndRow >= $this->deptStartRow) {
                    $dpTitleRow = $this->deptStartRow - 1;
                    $sheet->getStyle("A{$dpTitleRow}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('0F172A');
                    $sheet->getStyle("A{$this->deptStartRow}:E{$this->deptStartRow}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    $sheet->getStyle("B{$this->deptStartRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("A{$this->deptStartRow}:E{$this->deptEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
                    for ($r = $this->deptStartRow + 1; $r <= $this->deptEndRow; $r++) {
                        $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("D{$r}:E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }
                }
            },
        ];
    }
}
