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

class HcmMealAllowanceDetailSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private int $headerRow = 6;
    private int $endRow = 0;

    public function __construct(private HcmMealAllowanceBatch $batch) {}

    public function title(): string
    {
        return 'Rincian Uang Makan Karyawan';
    }

    public function array(): array
    {
        $profile = HcmPdfHelper::getProfileData();
        $companyName = $profile['company_name'] ?? 'PT Nusantara Inti Solusindo';

        $out = [];
        $out[] = [$companyName . ' — SISTEM HCM'];
        $out[] = ['DAFTAR REKAPITULASI HAK UANG MAKAN BULANAN KARYAWAN'];
        $out[] = [
            'Batch: ' . $this->batch->batch_code
            . '  |  Periode: ' . $this->batch->period_start?->format('d/m/Y') . ' s.d. ' . $this->batch->period_end?->format('d/m/Y')
            . '  |  Waktu Unduh: ' . now()->translatedFormat('d F Y, H:i') . ' WIB',
        ];
        $out[] = [''];

        // Header Kolom Tabel
        $out[] = [
            'No', 'NIP / NIK', 'Nama Karyawan', 'Departemen / Posisi',
            'Hadir (Hari)', 'Telat', 'Setengah Hari', 'Alpha / Mangkir', 'Cuti', 'Izin',
            'Status Cair', 'Alasan / Catatan', 'Hak Dasar (Rp)', 'Potongan (Rp)', 'Hak Bersih Diterima (Rp)',
        ];
        $this->headerRow = count($out);

        $no = 1;
        $items = $this->batch->items ?? collect();
        foreach ($items as $it) {
            $isHold = $it->status === 'HOLD' || $it->is_hold;
            $out[] = [
                $no++,
                $it->employee?->employee_code ?: '-',
                $it->employee?->name ?: ($it->employee_name ?: '-'),
                $it->department ?: ($it->employee?->department ?: '-'),
                (int) $it->present_days,
                (int) $it->late_days,
                (int) $it->half_days,
                (int) $it->alpha_days,
                (int) $it->leave_days,
                (int) $it->permit_days,
                $isHold ? 'DITAHAN (HOLD)' : 'CAIR',
                $it->hold_reason ?: ($isHold ? 'Terkena sanksi/alpha' : 'Lolos presensi'),
                (float) ($it->base_amount ?? ($it->base_allowance ?? 280000)),
                (float) ($it->deduction_amount ?? 0),
                (float) ($it->final_amount ?? ($it->payable_amount ?? 0)),
            ];
        }
        $this->endRow = count($out);

        // Baris Total
        $totalBase = (float) $items->sum(fn($i) => $i->base_amount ?? ($i->base_allowance ?? 280000));
        $totalDeduct = (float) $items->sum('deduction_amount');
        $totalPayable = (float) ($this->batch->total_amount ?? $items->sum(fn($i) => $i->final_amount ?? ($i->payable_amount ?? 0)));

        $out[] = [
            'TOTAL KESELURUHAN', '', '', '',
            (int) $items->sum('present_days'),
            (int) $items->sum('late_days'),
            (int) $items->sum('half_days'),
            (int) $items->sum('alpha_days'),
            (int) $items->sum('leave_days'),
            (int) $items->sum('permit_days'),
            '', '',
            $totalBase, $totalDeduct, $totalPayable,
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
                $sheet->getStyle("A{$this->headerRow}:O{$this->headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // Body Data
                if ($this->endRow >= $this->headerRow) {
                    $sheet->getStyle("A{$this->headerRow}:O{$this->endRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

                    for ($r = $this->headerRow + 1; $r <= $this->endRow; $r++) {
                        $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("E{$r}:J{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("K{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("M{$r}:O{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                        // Format Currency
                        $sheet->getStyle("M{$r}:O{$r}")->getNumberFormat()->setFormatCode('#,##0');

                        // Status Color
                        $statusVal = $sheet->getCell("K{$r}")->getValue();
                        if (str_contains($statusVal, 'HOLD')) {
                            $sheet->getStyle("K{$r}")->getFont()->setBold(true)->getColor()->setRGB('B91C1C');
                        } else {
                            $sheet->getStyle("K{$r}")->getFont()->setBold(true)->getColor()->setRGB('15803D');
                        }

                        if ($r % 2 === 0) {
                            $sheet->getStyle("A{$r}:O{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
                        }
                    }
                }

                // Total Row
                $totalRow = $this->endRow + 1;
                $sheet->mergeCells("A{$totalRow}:D{$totalRow}");
                $sheet->getStyle("A{$totalRow}:O{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 9.5, 'color' => ['rgb' => '0F172A']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                    'borders' => [
                        'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '94A3B8']],
                        'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '94A3B8']],
                    ],
                ]);
                $sheet->getStyle("A{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E{$totalRow}:J{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("M{$totalRow}:O{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("M{$totalRow}:O{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');
            },
        ];
    }
}
