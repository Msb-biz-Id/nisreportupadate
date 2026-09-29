<?php

namespace App\Exports;

use App\Models\Hcm\HcmEmployeeReward;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class HcmRewardExport implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private int $headerRow = 0;
    private int $endRow = 0;

    public function __construct(
        private Collection $rewards,
        private ?string $userName = null,
        private array $filters = []
    ) {}

    public function array(): array
    {
        $out = [];
        $out[] = ['NISGROUP — SISTEM HCM'];
        $out[] = ['REKAPITULASI REWARD & PENGHARGAAN KARYAWAN'];
        $out[] = ['Filter: Status=' . ($this->filters['status'] ?? 'all') . '  |  Tahun=' . ($this->filters['year'] ?? 'all') . '  |  Cari=' . (($this->filters['search'] ?? '') ?: '-')];
        $out[] = ['Waktu Unduh: ' . now()->translatedFormat('d M Y H:i') . ($this->userName ? '  |  Petugas: ' . $this->userName : '') . '  |  Total: ' . $this->rewards->count()];
        $out[] = [''];

        $out[] = ['No', 'NIK', 'Nama Karyawan', 'Departemen', 'Reward', 'Tahun', 'Status Penyaluran', 'Tanggal Diterima', 'Anggaran (Rp)'];
        $this->headerRow = count($out);

        $no = 1;
        $total = 0;
        foreach ($this->rewards as $rw) {
            $total += (float) $rw->budget_amount;
            $out[] = [
                $no++,
                $rw->employee?->employee_code ?? '-',
                $rw->employee?->name ?? '-',
                $rw->employee?->department ?? '-',
                $rw->reward_name,
                (int) $rw->reward_year,
                $rw->distribution_status,
                $rw->received_date ?: '-',
                (float) $rw->budget_amount,
            ];
        }
        $this->endRow = count($out);

        $out[] = ['TOTAL', '', '', '', '', '', '', '', $total];

        return $out;
    }

    public function title(): string
    {
        return 'Reward Karyawan';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('1E40AF');
                $sheet->getStyle("A{$this->headerRow}:I{$this->headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                ]);
                if ($this->endRow >= $this->headerRow) {
                    $sheet->getStyle("A{$this->headerRow}:I{$this->endRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                }
                $totalRow = $this->endRow + 1;
                $sheet->getStyle("A{$totalRow}:I{$totalRow}")->getFont()->setBold(true);
            },
        ];
    }
}
