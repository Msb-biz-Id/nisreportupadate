<?php

namespace App\Exports\Sheets\Hcm;

use App\Models\Hcm\HcmPayroll;
use App\Services\HcmPdfHelper;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class HcmPayrollDetailSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private int $headerRow = 6;
    private int $endRow = 0;

    public function __construct(private HcmPayroll $payroll) {}

    public function title(): string
    {
        return 'Rincian Komponen Gaji';
    }

    public function array(): array
    {
        $profile = HcmPdfHelper::getProfileData();
        $companyName = $profile['company_name'] ?? 'PT Nusantara Inti Solusindo';

        $out = [];
        $out[] = [$companyName . ' — SISTEM HCM NIS GROUP'];
        $out[] = ['DAFTAR RINCIAN ITEM GAJI INDIVIDUAL KARYAWAN'];
        $out[] = [
            'Batch: ' . $this->payroll->period_code
            . '  |  Bulan Kinerja: ' . $this->payroll->work_period_month
            . '  |  Pencairan: ' . $this->payroll->payout_period_month
            . '  |  Waktu Unduh: ' . now()->translatedFormat('d F Y, H:i') . ' WIB',
        ];
        $out[] = [''];

        // Header Kolom Tabel Itemized
        $out[] = [
            'No',
            'NIP Pegawai',
            'Nama Lengkap',
            'Departemen',
            'Divisi',
            'Status Kerja',
            'Bank',
            'Nomor Rekening',
            'Atas Nama Rekening',
            'Gaji Pokok (Rp)',
            'Uang Makan (Rp)',
            'Upah Lembur (Rp)',
            'Penyesuaian (Rp)',
            'Total Bruto (Rp)',
            'Potongan Sanksi (Rp)',
            'Potongan Cuti (Rp)',
            'Potongan Maternity (Rp)',
            'Total Potongan (Rp)',
            'Take-Home Pay (Rp)',
            'Status Cair',
        ];
        $this->headerRow = count($out);

        $no = 1;
        $items = $this->payroll->items ?? collect();
        foreach ($items as $it) {
            $emp = $it->employee;
            $out[] = [
                $no++,
                $emp?->employee_code ?: '-',
                $emp?->name ?: ($it->bank_account_name ?: '-'),
                $it->department ?: ($emp?->department ?: '-'),
                $it->division ?: ($emp?->division ?: 'Umum'),
                $it->employment_status ?: ($emp?->employment_status ?: '-'),
                $it->bank_name ?: ($emp?->bank_name ?: 'Bank BRI'),
                $it->bank_account_no ?: ($emp?->bank_account_no ?: '-'),
                $it->bank_account_name ?: ($emp?->name ?: '-'),
                (float) $it->base_salary,
                (float) $it->meal_allowance,
                (float) $it->overtime_pay,
                (float) $it->increment_adjustment,
                (float) $it->total_earnings,
                (float) $it->penalty_deduction,
                (float) $it->leave_deduction,
                (float) $it->tiered_deduction,
                (float) $it->total_deductions,
                (float) $it->net_salary,
                ($it->is_paid || $this->payroll->status === 'PAID_COMPLETED') ? 'LUNAS (PAID)' : 'MENUNGGU',
            ];
        }
        $this->endRow = count($out);

        // Baris Grand Total
        $out[] = [
            'GRAND TOTAL KESELURUHAN',
            '', '', '', '', '', '', '', '',
            (float) $this->payroll->total_base_salary,
            (float) $this->payroll->total_meal_allowance,
            (float) $this->payroll->total_overtime_pay,
            (float) $this->payroll->total_adjustments,
            (float) ($this->payroll->total_base_salary + $this->payroll->total_meal_allowance + $this->payroll->total_overtime_pay + $this->payroll->total_adjustments),
            (float) $items->sum('penalty_deduction'),
            (float) $items->sum('leave_deduction'),
            (float) $items->sum('tiered_deduction'),
            (float) $this->payroll->total_deductions,
            (float) $this->payroll->total_net_payout,
            '',
        ];

        return $out;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('0F172A');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('A8001C');
                $sheet->getStyle('A3')->getFont()->setSize(9)->getColor()->setRGB('64748B');

                // Header Baris Tabel
                $sheet->getStyle("A{$this->headerRow}:T{$this->headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 8.5],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // Body Data
                if ($this->endRow >= $this->headerRow) {
                    $sheet->getStyle("A{$this->headerRow}:T{$this->endRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

                    for ($r = $this->headerRow + 1; $r <= $this->endRow; $r++) {
                        $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("F{$r}:H{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("H{$r}")->getNumberFormat()->setFormatCode('@');
                        $sheet->getStyle("J{$r}:S{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle("J{$r}:S{$r}")->getNumberFormat()->setFormatCode('#,##0');
                        $sheet->getStyle("T{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                        // Potongan Font Red
                        $sheet->getStyle("O{$r}:R{$r}")->getFont()->getColor()->setRGB('B91C1C');

                        // Take-Home Pay Green Bold
                        $sheet->getStyle("S{$r}")->getFont()->setBold(true)->getColor()->setRGB('15803D');

                        if ($r % 2 === 0) {
                            $sheet->getStyle("A{$r}:T{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
                        }
                    }
                }

                // Total Row
                $totalRow = $this->endRow + 1;
                $sheet->mergeCells("A{$totalRow}:I{$totalRow}");
                $sheet->getStyle("A{$totalRow}:T{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 9.5, 'color' => ['rgb' => '0F172A']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                ]);
                $sheet->getStyle("J{$totalRow}:S{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("J{$totalRow}:S{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');
            },
        ];
    }
}
