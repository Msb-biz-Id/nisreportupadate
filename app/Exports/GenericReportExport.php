<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class GenericReportExport implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private int $detailHeaderRow = 1;
    private int $summaryTotalRow = 0;
    private int $detailTotalRow = 0;

    public function __construct(
        private string $title,
        private array $columns,
        private array $rows,
        private string $primaryColor = '1E40AF',
        private ?array $summaryTable = null
    ) {}

    public function array(): array
    {
        $output = [];

        // 1. Render Summary Table jika tersedia
        if (!empty($this->summaryTable) && !empty($this->summaryTable['rows'])) {
            $stTitle = $this->summaryTable['title'] ?? 'RINGKASAN DATA';
            $stCols = $this->summaryTable['columns'] ?? [
                ['key' => 'kategori', 'label' => 'Kategori'],
                ['key' => 'total_po', 'label' => 'Jumlah PO'],
                ['key' => 'total_pcs', 'label' => 'Jumlah PCS'],
            ];

            // Baris 1: Judul Tabel Ringkasan
            $output[] = [$stTitle];

            // Baris 2: Header Tabel Ringkasan
            $stHeaderRow = [];
            foreach ($stCols as $c) {
                $stHeaderRow[] = $c['label'];
            }
            $output[] = $stHeaderRow;

            // Baris 3..N: Data Tabel Ringkasan
            foreach ($this->summaryTable['rows'] as $stRow) {
                $rowArr = [];
                foreach ($stCols as $c) {
                    $rowArr[] = $stRow[$c['key']] ?? '';
                }
                $output[] = $rowArr;
            }

            // Baris Total Tabel Ringkasan
            if (!empty($this->summaryTable['total'])) {
                $stTotRow = [];
                foreach ($stCols as $c) {
                    $stTotRow[] = $this->summaryTable['total'][$c['key']] ?? '';
                }
                $output[] = $stTotRow;
                $this->summaryTotalRow = count($output);
            }

            // Spasi pemisah 1 baris
            $output[] = [''];
        }

        // 2. Render Header Tabel Detail
        $this->detailHeaderRow = count($output) + 1;
        $output[] = array_column($this->columns, 'label');

        // 3. Render Data Tabel Detail
        foreach ($this->rows as $r) {
            if (!empty($r['is_group_header'])) {
                $rawDate = $r['deadline_produksi'] ?? $r['deadline'] ?? null;
                $deadlineVal = !empty($rawDate) 
                    ? \Carbon\Carbon::parse($rawDate)->translatedFormat('d M Y') 
                    : '-';
                $prefix = !empty($r['deadline_produksi']) ? 'Deadline Produksi: ' : 'Deadline: ';
                $out = [$prefix . $deadlineVal];
                for ($i = 1; $i < count($this->columns); $i++) {
                    $out[] = '';
                }
                $output[] = $out;
                continue;
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
                $output[] = $out;
                continue;
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
                        'selesai' => 'Selesai',
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
            $output[] = $out;
        }

        // 4. Auto Grand Total Row untuk Tabel Detail
        $dataRows = array_filter($this->rows, fn($r) => empty($r['is_group_header']) && empty($r['is_group_total']));
        if (!empty($dataRows)) {
            $totalRow = [];
            $keysToSum = ['pcs', 'total_qty', 'total_order', 'total_tagihan', 'total_value', 'jumlah', 'nominal', 'nominal_refund', 'amount', 'total_pcs'];
            
            foreach ($this->columns as $idx => $col) {
                if ($idx === 0) {
                    $totalRow[] = 'TOTAL KESELURUHAN (' . count($dataRows) . ' Data)';
                } elseif (in_array($col['key'], $keysToSum, true)) {
                    $sum = array_sum(array_column($dataRows, $col['key']));
                    $totalRow[] = $sum;
                } else {
                    $totalRow[] = '';
                }
            }
            $output[] = $totalRow;
            $this->detailTotalRow = count($output);
        }

        return $output;
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
                $highestCol = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();

                // 1. Format Tabel Ringkasan jika ada
                if (!empty($this->summaryTable) && !empty($this->summaryTable['rows'])) {
                    $stColsCount = count($this->summaryTable['columns'] ?? [1, 2, 3]);
                    $stEndColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($stColsCount);

                    // Row 1: Judul Ringkasan
                    $sheet->getStyle("A1:{$stEndColLetter}1")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $this->primaryColor]],
                    ]);

                    // Row 2: Header Ringkasan
                    $sheet->getStyle("A2:{$stEndColLetter}2")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->primaryColor]],
                        'alignment' => ['vertical' => 'center'],
                    ]);
                    $sheet->getRowDimension(2)->setRowHeight(24);

                    // Row Border Data Ringkasan
                    $stEndRow = $this->summaryTotalRow ?: (2 + count($this->summaryTable['rows']));
                    $sheet->getStyle("A2:{$stEndColLetter}{$stEndRow}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
                    ]);

                    // Row Total Ringkasan
                    if ($this->summaryTotalRow > 0) {
                        $sheet->getStyle("A{$this->summaryTotalRow}:{$stEndColLetter}{$this->summaryTotalRow}")->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                            'borders' => [
                                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']],
                                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '475569']],
                            ],
                        ]);
                    }
                }

                // 2. Format Header Tabel Detail
                $dRow = $this->detailHeaderRow;
                $headerRange = "A{$dRow}:{$highestCol}{$dRow}";
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->primaryColor]],
                    'alignment' => ['vertical' => 'center', 'horizontal' => 'left'],
                ]);
                $sheet->getRowDimension($dRow)->setRowHeight(28);

                // 3. Format Data Detail
                $detailDataStartRow = $dRow + 1;
                $sheet->getStyle("A{$dRow}:{$highestCol}{$highestRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
                ]);

                // Group header dan total styling jika ada
                $curRow = $detailDataStartRow;
                foreach ($this->rows as $row) {
                    if (!empty($row['is_group_header'])) {
                        $sheet->mergeCells("A{$curRow}:{$highestCol}{$curRow}");
                        $sheet->getStyle("A{$curRow}:{$highestCol}{$curRow}")->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => $this->primaryColor]],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                        ]);
                    } elseif (!empty($row['is_group_total'])) {
                        $sheet->getStyle("A{$curRow}:{$highestCol}{$curRow}")->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
                        ]);
                    }
                    $curRow++;
                }

                // 4. Format Total Keseluruhan Detail (Footer)
                if ($this->detailTotalRow > 0) {
                    $sheet->getStyle("A{$this->detailTotalRow}:{$highestCol}{$this->detailTotalRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                        'borders' => [
                            'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']],
                            'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '475569']],
                        ],
                    ]);
                }
            },
        ];
    }
}
