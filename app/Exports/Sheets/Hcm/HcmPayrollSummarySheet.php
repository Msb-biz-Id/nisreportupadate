<?php

namespace App\Exports\Sheets\Hcm;

use App\Models\Hcm\HcmPayroll;
use App\Services\HcmPdfHelper;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class HcmPayrollSummarySheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private int $metaStartRow = 7;
    private int $metaEndRow = 7;
    private int $deptStartRow = 0;
    private int $deptEndRow = 0;

    public function __construct(private HcmPayroll $payroll, private ?string $userName = null) {}

    public function title(): string
    {
        return 'Ringkasan Eksekutif';
    }

    public function array(): array
    {
        $profile = HcmPdfHelper::getProfileData();
        $companyName = $profile['company_name'] ?? 'PT Nusantara Inti Solusindo';
        $divisionName = $profile['division_name'] ?? 'Divisi Human Capital Management';

        $items = $this->payroll->items ?? collect();
        $totalEmployees = (int) ($this->payroll->total_employees ?: $items->count());
        $totalNet = (float) ($this->payroll->total_net_payout ?: $items->sum('net_salary'));
        $avgNet = $totalEmployees > 0 ? round($totalNet / $totalEmployees, 0) : 0;

        $out = [];
        $out[] = [$companyName];
        $out[] = [$divisionName];
        $out[] = ['RINGKASAN EKSEKUTIF PENGGAJIAN TERPADU (UNIFIED PAYROLL)'];
        $out[] = [
            'Kode Periode: ' . $this->payroll->period_code,
            '',
            'Waktu Unduh: ' . now()->translatedFormat('d F Y, H:i') . ' WIB',
            '',
            'Petugas: ' . ($this->userName ?: 'Admin HCM'),
        ];
        $out[] = [''];

        // ===== 1. METADATA & REKAPITULASI BATCH =====
        $out[] = ['INFORMASI BATCH & AGREGASI KEUANGAN PAYROLL', '', '', ''];
        $this->metaStartRow = count($out) + 1;
        $out[] = ['No', 'Parameter / Komponen', 'Keterangan / Nilai', 'Satuan / Detail'];
        $out[] = [1, 'Nomor / Kode Periode', $this->payroll->period_code, 'Dokumen Sah'];
        $out[] = [2, 'Bulan Kinerja (Work Period)', $this->payroll->work_period_month, 'Presensi & Lembur'];
        $out[] = [3, 'Bulan Realisasi (Payout Period)', $this->payroll->payout_period_month, 'Pencairan Kas/Bank'];
        $payoutDateStr = $this->payroll->payout_date ? $this->payroll->payout_date->format('d/m/Y') : 'Sesuai Jadwal';
        $out[] = [4, 'Tanggal Transfer Gaji', $payoutDateStr, 'Realisasi'];
        $out[] = [5, 'Metode Pembayaran', $this->payroll->payment_method ?: 'Transfer Massal Bank BRI', 'Kas / Bank'];
        $out[] = [6, 'Status Otorisasi Batch', $this->payroll->status ?: 'DRAFT_HCM', 'Governance'];
        $out[] = [7, 'Pengesah HCM (Level 1)', $this->payroll->hcmSigner?->name ?: 'Menunggu Otorisasi', 'Audit Data'];
        $out[] = [8, 'Pengesah Keuangan (Level 2)', $this->payroll->financeSigner?->name ?: 'Menunggu Pencairan', 'Audit Kas'];
        $out[] = [9, 'Total Tenaga Kerja Aktif', $totalEmployees, 'Orang'];
        $out[] = [10, 'Total Gaji Pokok / Honor', 'Rp ' . number_format($this->payroll->total_base_salary, 0, ',', '.'), 'Rupiah Bruto'];
        $out[] = [11, 'Total Tunjangan Uang Makan', 'Rp ' . number_format($this->payroll->total_meal_allowance, 0, ',', '.'), 'Rupiah Bruto'];
        $out[] = [12, 'Total Upah Lembur Riil', 'Rp ' . number_format($this->payroll->total_overtime_pay, 0, ',', '.'), 'Rupiah Bruto'];
        $out[] = [13, 'Total Penyesuaian Kenaikan', 'Rp ' . number_format($this->payroll->total_adjustments, 0, ',', '.'), 'Rupiah Bruto'];
        $out[] = [14, 'Total Seluruh Pemotongan Gaji', 'Rp ' . number_format($this->payroll->total_deductions, 0, ',', '.'), 'Potongan Bersih'];
        $out[] = [15, 'GRAND TOTAL TAKE-HOME PAY (NET)', 'Rp ' . number_format($totalNet, 0, ',', '.'), 'Rupiah Bersih Cair'];
        $out[] = [16, 'Rata-rata Gaji Bersih per Orang', 'Rp ' . number_format($avgNet, 0, ',', '.'), 'Rupiah / Orang'];
        $this->metaEndRow = count($out);
        $out[] = [''];

        // ===== 2. REKAPITULASI MULTI-LEVEL (DEPARTEMEN -> DIVISI) =====
        if ($items->isNotEmpty()) {
            $out[] = ['REKAPITULASI PENGGAJIAN PER DEPARTEMEN & DIVISI (MULTI-LEVEL GROUPING)', '', '', '', '', '', '', '', '', ''];
            $this->deptStartRow = count($out) + 1;
            $out[] = [
                'No', 'Departemen Induk', 'Divisi Sub-Unit', 'Jumlah Pegawai',
                'Gaji Pokok (Rp)', 'Uang Makan (Rp)', 'Lembur (Rp)', 'Total Bruto (Rp)',
                'Total Potongan (Rp)', 'Take-Home Pay (Rp)',
            ];

            // Grouping: Departemen -> Divisi (Zero N+1)
            $grouped = $items->groupBy(fn($i) => $i->department ?: 'Departemen Lainnya');
            $rowNo = 1;

            foreach ($grouped as $deptName => $deptItems) {
                $divGrouped = $deptItems->groupBy(fn($i) => $i->division ?: 'Umum');
                foreach ($divGrouped as $divName => $divItems) {
                    $out[] = [
                        $rowNo++,
                        $deptName,
                        $divName,
                        $divItems->count(),
                        (float) $divItems->sum('base_salary'),
                        (float) $divItems->sum('meal_allowance'),
                        (float) $divItems->sum('overtime_pay'),
                        (float) $divItems->sum('total_earnings'),
                        (float) $divItems->sum('total_deductions'),
                        (float) $divItems->sum('net_salary'),
                    ];
                }
            }

            // Total Baris Departemen Grouping
            $out[] = [
                'TOTAL REKAPITULASI', '', '',
                $totalEmployees,
                (float) $this->payroll->total_base_salary,
                (float) $this->payroll->total_meal_allowance,
                (float) $this->payroll->total_overtime_pay,
                (float) ($this->payroll->total_base_salary + $this->payroll->total_meal_allowance + $this->payroll->total_overtime_pay + $this->payroll->total_adjustments),
                (float) $this->payroll->total_deductions,
                (float) $totalNet,
            ];

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
                $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('1E293B');
                $sheet->getStyle('A4:E4')->getFont()->setSize(9)->getColor()->setRGB('64748B');

                // Metadata Section Header
                $metaHeaderRow = $this->metaStartRow - 1;
                $sheet->mergeCells("A{$metaHeaderRow}:D{$metaHeaderRow}");
                $sheet->getStyle("A{$metaHeaderRow}:D{$metaHeaderRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // Metadata Table Header
                $sheet->getStyle("A{$this->metaStartRow}:D{$this->metaStartRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '0F172A'], 'size' => 9],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Metadata Table Borders & Rows
                $sheet->getStyle("A{$this->metaStartRow}:D{$this->metaEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

                for ($r = $this->metaStartRow + 1; $r <= $this->metaEndRow; $r++) {
                    $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("B{$r}")->getFont()->setBold(true);
                    if ($r % 2 === 0) {
                        $sheet->getStyle("A{$r}:D{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
                    }
                }

                // Highlight Row 15: Grand Total Net
                $highlightRow = $this->metaStartRow + 15;
                if ($highlightRow <= $this->metaEndRow) {
                    $sheet->getStyle("A{$highlightRow}:D{$highlightRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '166534']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCFCE7']],
                    ]);
                }

                // Departemen & Divisi Grouping Section
                if ($this->deptStartRow > 0) {
                    $secHeader = $this->deptStartRow - 1;
                    $sheet->mergeCells("A{$secHeader}:J{$secHeader}");
                    $sheet->getStyle("A{$secHeader}:J{$secHeader}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A8001C']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                    ]);

                    $sheet->getStyle("A{$this->deptStartRow}:J{$this->deptStartRow}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);

                    $sheet->getStyle("A{$this->deptStartRow}:J{$this->deptEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

                    for ($r = $this->deptStartRow + 1; $r < $this->deptEndRow; $r++) {
                        $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("D{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("E{$r}:J{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle("E{$r}:J{$r}")->getNumberFormat()->setFormatCode('#,##0');

                        if ($r % 2 === 0) {
                            $sheet->getStyle("A{$r}:J{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
                        }
                    }

                    // Total Baris Departemen
                    $sheet->mergeCells("A{$this->deptEndRow}:C{$this->deptEndRow}");
                    $sheet->getStyle("A{$this->deptEndRow}:J{$this->deptEndRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 9.5, 'color' => ['rgb' => '0F172A']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                    ]);
                    $sheet->getStyle("E{$this->deptEndRow}:J{$this->deptEndRow}")->getNumberFormat()->setFormatCode('#,##0');
                }
            },
        ];
    }
}
