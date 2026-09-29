<?php

namespace App\Exports;

use App\Models\Hcm\HcmMealAllowanceBatch;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class HcmMealAllowanceBatchExport implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private int $headerRow = 0;
    private int $endRow = 0;

    public function __construct(private HcmMealAllowanceBatch $batch, private ?string $userName = null) {}

    public function array(): array
    {
        $out = [];
        $out[] = ['NISGROUP — SISTEM HCM'];
        $out[] = ['REKAPITULASI UANG MAKAN BULANAN'];
        $out[] = ['Kode Batch: ' . $this->batch->batch_code . '  |  Periode: ' . $this->batch->period_start?->format('d/m/Y') . ' s.d. ' . $this->batch->period_end?->format('d/m/Y')];
        $out[] = ['Status: ' . $this->batch->status . '  |  Total Karyawan: ' . $this->batch->total_employees . '  |  Waktu Unduh: ' . now()->translatedFormat('d M Y H:i') . ($this->userName ? '  |  Petugas: ' . $this->userName : '')];
        $out[] = [''];

        $out[] = ['No', 'NIK', 'Nama Karyawan', 'Departemen', 'Hadir', 'Telat', 'Setengah', 'Alpha', 'Cuti', 'Izin', 'Hold', 'Standar (Rp)', 'Potongan (Rp)', 'Dibayar (Rp)'];
        $this->headerRow = count($out);

        $no = 1;
        foreach ($this->batch->items as $it) {
            $out[] = [
                $no++,
                $it->employee?->employee_code ?? '-',
                $it->employee?->name ?? '-',
                $it->department ?? '-',
                (int) $it->present_days,
                (int) $it->late_days,
                (int) $it->half_days,
                (int) $it->alpha_days,
                (int) $it->leave_days,
                (int) $it->permit_days,
                $it->is_hold ? 'Ya' : 'Tidak',
                (float) $it->base_allowance,
                (float) $it->deduction_amount,
                (float) $it->payable_amount,
            ];
        }
        $this->endRow = count($out);

        $out[] = ['TOTAL', '', '', '', '', '', '', '', '', '', '', (float) $this->batch->total_amount, (float) $this->batch->total_held_amount, (float) $this->batch->total_amount];

        return $out;
    }

    public function title(): string
    {
        return 'Uang Makan ' . $this->batch->batch_code;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('1E40AF');
                $sheet->getStyle("A{$this->headerRow}:N{$this->headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                ]);
                if ($this->endRow >= $this->headerRow) {
                    $sheet->getStyle("A{$this->headerRow}:N{$this->endRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                }
                $totalRow = $this->endRow + 1;
                $sheet->getStyle("A{$totalRow}:N{$totalRow}")->getFont()->setBold(true);
            },
        ];
    }
}
