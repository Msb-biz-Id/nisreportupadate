<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReportDetailSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private int $detailHeaderRow = 1;
    private int $detailTotalRow = 0;

    public function __construct(
        private string $title,
        private array $columns,
        private array $rows,
        private string $primaryColor = '1E40AF',
        private array $filters = [],
        private ?string $brandName = null,
        private ?string $userName = null,
        private string $sheetTitle = 'Detail Data'
    ) {}

    public function array(): array
    {
        $output = [];

        // 1. Header Metadata Dokumen
        $siteName = \App\Models\Settings\SystemSetting::get('seo', 'site_name', config('app.name', 'ProTrack'));

        $from = !empty($this->filters['from'])
            ? \Carbon\Carbon::parse($this->filters['from'])->translatedFormat('d M Y')
            : null;
        $to = !empty($this->filters['to'])
            ? \Carbon\Carbon::parse($this->filters['to'])->translatedFormat('d M Y')
            : null;

        if ($from && $to) {
            $rangeTanggal = "$from s/d $to";
        } elseif ($from) {
            $rangeTanggal = "Mulai $from";
        } elseif ($to) {
            $rangeTanggal = "Sampai $to";
        } else {
            $rangeTanggal = "Semua Tanggal (Keseluruhan Data)";
        }

        $filterParts = [];
        $filterParts[] = "Brand: " . ($this->brandName ?: 'Semua Brand');
        if (!empty($this->filters['jenis_po']) && $this->filters['jenis_po'] !== '__all__') {
            $filterParts[] = "Jenis PO: " . ucfirst(str_replace('_', ' ', $this->filters['jenis_po']));
        }
        if (!empty($this->filters['status']) && $this->filters['status'] !== '__all__') {
            $filterParts[] = "Status: " . ucfirst($this->filters['status']);
        }
        $metaFilterRow = implode('  |  ', $filterParts) . '  |  Waktu Cetak: ' . now()->translatedFormat('d M Y, H:i') . ($this->userName ? " ({$this->userName})" : '');

        $output[] = [$siteName];
        $output[] = ['LAPORAN ' . strtoupper($this->title) . ' (DETAIL DATA TRANSAKSI)'];
        $output[] = ['Periode Tanggal: ' . $rangeTanggal];
        $output[] = [$metaFilterRow];
        $output[] = ['']; // Baris 5 spasi pemisah

        // 2. Header Kolom Tabel Detail
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
                    $val = $statusLabels[$val] ?? str_replace('_', ' ', (string) $val);
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

        // 4. Baris Footer Total Tabel Detail
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
        return mb_substr($this->sheetTitle, 0, 31);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestCol = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();

                // 1. Format Header Metadata (Baris 1 - 4)
                $sheet->getStyle("A1:{$highestCol}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => $this->primaryColor]],
                ]);
                $sheet->getStyle("A2:{$highestCol}2")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1E293B']],
                ]);
                $sheet->getStyle("A3:{$highestCol}3")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '0F172A']],
                ]);
                $sheet->getStyle("A4:{$highestCol}4")->applyFromArray([
                    'font' => ['italic' => true, 'size' => 8.5, 'color' => ['rgb' => '64748B']],
                ]);

                // 2. Format Header Tabel Detail
                $dRow = $this->detailHeaderRow;
                $headerRange = "A{$dRow}:{$highestCol}{$dRow}";
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->primaryColor]],
                    'alignment' => ['vertical' => 'center', 'horizontal' => 'left'],
                ]);
                $sheet->getRowDimension($dRow)->setRowHeight(26);

                // 3. Border Data Detail
                $detailDataStartRow = $dRow + 1;
                $sheet->getStyle("A{$dRow}:{$highestCol}{$highestRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
                ]);

                // 4. Alignment Right untuk kolom number & currency di Tabel Detail
                foreach ($this->columns as $cIdx => $col) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cIdx + 1);
                    $format = $col['format'] ?? null;
                    if (in_array($format, ['number', 'currency']) || in_array($col['key'], ['pcs', 'total_tagihan', 'total_qty', 'total_order', 'nominal', 'amount'])) {
                        $sheet->getStyle("{$colLetter}{$detailDataStartRow}:{$colLetter}{$highestRow}")->applyFromArray([
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                        ]);
                    }
                }

                // 5. Group Header & Group Total Styling jika ada
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

                // 6. Format Total Keseluruhan Detail (Footer)
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
