<?php

namespace App\Exports;

use App\Models\Hcm\HcmOvertimeBatch;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class HcmOvertimeBatchExport implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private int $headerRow = 0;
    private int $endRow = 0;

    public function __construct(private HcmOvertimeBatch $batch, private ?string $userName = null) {}

    public function array(): array
    {
        $out = [];
        $out[] = ['NISGROUP — SISTEM HCM'];
        $out[] = ['REKAP LEMBUR MINGGUAN (VOUCHER PEMBAYARAN)'];
        $out[] = ['Kode Batch: ' . $this->batch->batch_code . '  |  Periode: ' . $this->batch->period_start?->format('d/m/Y') . ' s.d. ' . $this->batch->period_end?->format('d/m/Y') . '  |  Bayar: ' . $this->batch->payout_date?->format('d/m/Y')];
        $out[] = ['Status: ' . $this->batch->status . '  |  Metode: ' . ($this->batch->payment_method ?: '-') . '  |  Waktu Unduh: ' . now()->translatedFormat('d M Y H:i') . ($this->userName ? '  |  Petugas: ' . $this->userName : '')];
        $out[] = [''];

        $out[] = ['No', 'NIK', 'Nama Karyawan', 'Departemen', 'Posisi', 'Tanggal', 'Jenis Hari', 'Jam', 'Tarif/Jam', 'Total (Rp)'];
        $this->headerRow = count($out);

        $no = 1;
        foreach ($this->batch->overtimes as $ot) {
            $out[] = [
                $no++,
                $ot->employee?->employee_code ?? '-',
                $ot->employee?->name ?? '-',
                $ot->employee?->department ?? '-',
                $ot->position ?? '-',
                $ot->overtime_date?->format('d/m/Y') ?? '-',
                $ot->day_type,
                (float) $ot->duration_hours,
                (float) $ot->hourly_rate,
                (float) $ot->total_amount,
            ];
        }
        $this->endRow = count($out);

        $out[] = ['TOTAL', '', '', '', '', '', '', (float) $this->batch->total_hours, '', (float) $this->batch->total_amount];

        return $out;
    }

    public function title(): string
    {
        return 'Lembur ' . $this->batch->batch_code;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('1E40AF');
                $sheet->getStyle("A{$this->headerRow}:J{$this->headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                ]);
                if ($this->endRow >= $this->headerRow) {
                    $sheet->getStyle("A{$this->headerRow}:J{$this->endRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                }
                $totalRow = $this->endRow + 1;
                $sheet->getStyle("A{$totalRow}:J{$totalRow}")->getFont()->setBold(true);
            },
        ];
    }
}
