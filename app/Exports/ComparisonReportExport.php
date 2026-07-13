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

class ComparisonReportExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{
    public function __construct(
        private string $title,
        private string $mode,
        private array $headings,
        private array $rows,
        private string $primaryColor = '4F46E5'
    ) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return mb_substr($this->title, 0, 31);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Merge A1:A2 (Bulan)
                $sheet->mergeCells('A1:A2');
                
                // Merge brands/years headings dynamically
                $highestCol = $sheet->getHighestColumn();
                $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
                
                $numGroups = ($highestColIndex - 1) / 3;
                for ($i = 0; $i < $numGroups; $i++) {
                    $startCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + $i * 3);
                    $endCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(4 + $i * 3);
                    $sheet->mergeCells("{$startCol}1:{$endCol}1");
                }

                // Apply header styling (Row 1 and Row 2)
                $headerRange = 'A1:' . $highestCol . '2';
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->primaryColor]], // Dynamic theme
                    'alignment' => ['vertical' => 'center', 'horizontal' => 'center'],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(26);
                $sheet->getRowDimension(2)->setRowHeight(20);

                // Format data rows
                $highestRow = $sheet->getHighestRow();
                for ($col = 2; $col <= $highestColIndex; $col++) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $metricType = ($col - 2) % 3; // 0 = PO, 1 = Pcs, 2 = Omset

                    for ($row = 3; $row <= $highestRow; $row++) {
                        $cell = $sheet->getCell("{$colLetter}{$row}");
                        $val = $cell->getValue();
                        
                        // Cast numeric values to float/int
                        if (is_numeric($val)) {
                            $cell->setValue((float)$val);
                        }

                        // Align numbers to the right
                        $sheet->getStyle("{$colLetter}{$row}")->getAlignment()
                            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

                        // Set formatting code
                        if ($metricType === 2) {
                            $sheet->getStyle("{$colLetter}{$row}")->getNumberFormat()
                                ->setFormatCode('Rp #,##0');
                        } else {
                            $sheet->getStyle("{$colLetter}{$row}")->getNumberFormat()
                                ->setFormatCode('#,##0');
                        }
                    }
                }

                // Apply borders and alignments across the entire table range
                $dataRange = 'A1:' . $highestCol . $highestRow;
                $sheet->getStyle($dataRange)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                    'alignment' => ['vertical' => 'center'],
                ]);

                // Align "Bulan" / row label column to the left
                for ($row = 3; $row <= $highestRow; $row++) {
                    $sheet->getStyle("A{$row}")->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                }

                // Bold total row
                $sheet->getStyle("A{$highestRow}:" . $highestCol . $highestRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                ]);
            },
        ];
    }
}
