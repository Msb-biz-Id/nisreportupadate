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
            $out = [];
            foreach ($this->columns as $col) {
                $out[] = $r[$col['key']] ?? null;
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

                $dataRange = 'A1:' . $sheet->getHighestColumn() . $sheet->getHighestRow();
                $sheet->getStyle($dataRange)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
                ]);
            },
        ];
    }
}
