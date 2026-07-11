<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class GenericReportExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{
    public function __construct(
        private string $title,
        private array $columns,
        private array $rows,
    ) {}

    public function array(): array
    {
        return collect($this->rows)->map(function ($r) {
            if (!empty($r['is_group_header'])) {
                $deadlineVal = !empty($r['deadline']) 
                    ? \Carbon\Carbon::parse($r['deadline'])->translatedFormat('d M Y') 
                    : '-';
                $out = ['Deadline: ' . $deadlineVal];
                for ($i = 1; $i < count($this->columns); $i++) {
                    $out[] = '';
                }
                return $out;
            }
            if (!empty($r['is_group_total'])) {
                $out = [];
                foreach ($this->columns as $col) {
                    if ($col['key'] === 'pelanggan') {
                        $out[] = 'TOTAL PCS';
                    } elseif ($col['key'] === 'pcs') {
                        $out[] = $r['pcs'] ?? 0;
                    } else {
                        $out[] = '';
                    }
                }
                return $out;
            }
            $out = [];
            foreach ($this->columns as $col) {
                $val = $r[$col['key']] ?? null;
                if ($col['key'] === 'status' || $col['key'] === 'status_po') {
                    $statusLabels = [
                        'draft' => 'Draft',
                        'validated' => 'Validasi',
                        'published' => 'Baru Masuk',
                        'on_progress' => 'Sedang Produksi',
                        'selesai_produksi' => 'Selesai Produksi',
                        'siap_dikirim' => 'Siap Dikirim',
                        'sudah_dikirim' => 'Sudah Dikirim',
                        'delay' => 'Tertunda (Delay)',
                        'hold' => 'Ditahan (Hold)',
                        'cancel' => 'Dibatalkan',
                        'paid' => 'Lunas',
                        'overdue' => 'Jatuh Tempo',
                        'sent' => 'Dikirim',
                    ];
                    $val = $statusLabels[$val] ?? str_replace('_', ' ', $val);
                } elseif (($col['format'] ?? null) === 'days_indicator') {
                    if ((int) $val < 0) {
                        $val = abs((int) $val) . ' hari telat';
                    } else {
                        $val = 'H-' . (int) $val;
                    }
                }
                $out[] = $val;
            }
            return $out;
        })->all();
    }

    public function headings(): array
    {
        return array_column($this->columns, 'label');
    }

    public function title(): string
    {
        return mb_substr($this->title, 0, 31); // sheet name max 31 chars
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $headerRange = 'A1:' . $sheet->getHighestColumn() . '1';
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
                    'alignment' => ['vertical' => 'center', 'horizontal' => 'left'],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(28);

                $rowIndex = 2;
                foreach ($this->rows as $row) {
                    if (!empty($row['is_group_header'])) {
                        $endCol = $sheet->getHighestColumn();
                        $sheet->mergeCells("A{$rowIndex}:{$endCol}{$rowIndex}");
                        $sheet->getStyle("A{$rowIndex}:{$endCol}{$rowIndex}")->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => '1E40AF']],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']],
                        ]);
                    } elseif (!empty($row['is_group_total'])) {
                        $endCol = $sheet->getHighestColumn();
                        $sheet->getStyle("A{$rowIndex}:{$endCol}{$rowIndex}")->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
                        ]);
                    }
                    $rowIndex++;
                }

                $dataRange = 'A1:' . $sheet->getHighestColumn() . $sheet->getHighestRow();
                $sheet->getStyle($dataRange)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
                ]);
            },
        ];
    }
}
