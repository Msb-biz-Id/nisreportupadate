<?php

namespace App\Exports\Sheets\Hcm;

use App\Models\Hcm\HcmOvertimeBatch;
use App\Services\HcmPdfHelper;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class HcmOvertimeDetailSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private int $headerRow = 6;
    private int $endRow = 0;

    public function __construct(private HcmOvertimeBatch $batch) {}

    public function title(): string
    {
        return 'Rincian Lembur Karyawan';
    }

    public function array(): array
    {
        $profile = HcmPdfHelper::getProfileData();
        $companyName = $profile['company_name'] ?? 'PT Nusantara Inti Solusindo';

        $out = [];
        $out[] = [$companyName . ' — SISTEM HCM'];
        $out[] = ['RINCIAN KOMPENSASI LEMBUR KARYAWAN'];
        $out[] = [
            'Batch: ' . $this->batch->batch_code
            . '  |  Periode: ' . $this->batch->period_start?->format('d/m/Y') . ' s.d. ' . $this->batch->period_end?->format('d/m/Y')
            . '  |  Waktu Unduh: ' . now()->translatedFormat('d F Y, H:i') . ' WIB',
        ];
        $out[] = [''];

        // Header Kolom Tabel
        $out[] = [
            'No', 'NIP / NIK', 'Nama Karyawan', 'Departemen', 'Posisi / Jabatan',
            'Tanggal Lembur', 'Jenis Hari', 'Jam Mulai', 'Jam Selesai',
            'Durasi (Jam)', 'Tarif / Jam (Rp)', 'Total Upah Lembur (Rp)',
        ];
        $this->headerRow = count($out);

        $no = 1;
        $items = $this->batch->items ?? collect();
        foreach ($items as $ot) {
            $out[] = [
                $no++,
                $ot->employee?->employee_code ?: '-',
                $ot->employee?->name ?: ($ot->employee_name ?: '-'),
                $ot->employee?->department ?: ($ot->department ?: '-'),
                $ot->position ?: ($ot->employee?->position ?: '-'),
                $ot->overtime_date ? \Carbon\Carbon::parse($ot->overtime_date)->format('d/m/Y') : '-',
                $ot->day_type ?: 'Hari Kerja',
                substr((string) ($ot->start_time ?? ''), 0, 5) ?: '-',
                substr((string) ($ot->end_time ?? ''), 0, 5) ?: '-',
                (float) ($ot->duration_hours ?? 0),
                (float) ($ot->hourly_rate ?? 0),
                (float) ($ot->total_pay ?? ($ot->total_amount ?? 0)),
            ];
        }
        $this->endRow = count($out);

        // Baris Total
        $totalHours = (float) ($this->batch->total_hours ?? $items->sum('duration_hours'));
        $totalPay = (float) ($this->batch->total_amount ?? $items->sum('total_pay'));

        $out[] = [
            'TOTAL PENGELUARAN', '', '', '', '', '', '', '', '',
            $totalHours, '', $totalPay,
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
                $sheet->getStyle("A{$this->headerRow}:L{$this->headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // Body Data
                if ($this->endRow >= $this->headerRow) {
                    $sheet->getStyle("A{$this->headerRow}:L{$this->endRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

                    for ($r = $this->headerRow + 1; $r <= $this->endRow; $r++) {
                        $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("F{$r}:I{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("J{$r}:L{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                        // Format Currency
                        $sheet->getStyle("K{$r}:L{$r}")->getNumberFormat()->setFormatCode('#,##0');

                        if ($r % 2 === 0) {
                            $sheet->getStyle("A{$r}:L{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
                        }
                    }
                }

                // Total Row
                $totalRow = $this->endRow + 1;
                $sheet->mergeCells("A{$totalRow}:I{$totalRow}");
                $sheet->getStyle("A{$totalRow}:L{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 9.5, 'color' => ['rgb' => '0F172A']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                    'borders' => [
                        'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '94A3B8']],
                        'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '94A3B8']],
                    ],
                ]);
                $sheet->getStyle("A{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("J{$totalRow}:L{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("L{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');
            },
        ];
    }
}
