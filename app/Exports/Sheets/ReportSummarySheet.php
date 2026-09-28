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

class ReportSummarySheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private int $st1HeaderRow = 0;
    private int $st1TotalRow = 0;
    private int $st2HeaderRow = 0;
    private int $st2TotalRow = 0;

    public function __construct(
        private string $title,
        private string $primaryColor = '1E40AF',
        private ?array $summaryTable = null,
        private ?array $brandSummaryTable = null,
        private array $filters = [],
        private ?string $brandName = null,
        private ?string $userName = null
    ) {}

    public function array(): array
    {
        $output = [];

        // 1. Header Metadata Dokumen (Bagian Atas Excel)
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
        $output[] = ['LAPORAN ' . strtoupper($this->title) . ' (RINGKASAN DATA)'];
        $output[] = ['Periode Tanggal: ' . $rangeTanggal];
        $output[] = [$metaFilterRow];
        $output[] = ['']; // Baris 5 spasi pemisah

        // 2. Render Tabel 1: Ringkasan Kategori Harga jika tersedia
        if (!empty($this->summaryTable) && !empty($this->summaryTable['rows'])) {
            $st1Title = $this->summaryTable['title'] ?? 'Ringkasan Berdasarkan Kategori Harga';
            $st1Cols = $this->summaryTable['columns'] ?? [
                ['key' => 'kategori', 'label' => 'Kategori'],
                ['key' => 'total_po', 'label' => 'Jumlah PO'],
                ['key' => 'total_pcs', 'label' => 'Jumlah PCS'],
            ];

            // Judul Tabel 1
            $output[] = [$st1Title];

            // Header Tabel 1
            $this->st1HeaderRow = count($output) + 1;
            $st1Header = [];
            foreach ($st1Cols as $c) {
                $st1Header[] = $c['label'];
            }
            $output[] = $st1Header;

            // Baris Data Tabel 1
            foreach ($this->summaryTable['rows'] as $row) {
                $rArr = [];
                foreach ($st1Cols as $c) {
                    $rArr[] = $row[$c['key']] ?? '';
                }
                $output[] = $rArr;
            }

            // Baris Total Tabel 1
            if (!empty($this->summaryTable['total'])) {
                $rTot = [];
                foreach ($st1Cols as $c) {
                    $rTot[] = $this->summaryTable['total'][$c['key']] ?? '';
                }
                $output[] = $rTot;
                $this->st1TotalRow = count($output);
            }

            // Spasi Pemisah
            $output[] = [''];
            $output[] = [''];
        }

        // 3. Render Tabel 2: Ringkasan Per Brand Secara Jumlah jika tersedia
        if (!empty($this->brandSummaryTable) && !empty($this->brandSummaryTable['rows'])) {
            $st2Title = $this->brandSummaryTable['title'] ?? 'Ringkasan Per Brand (Kuantitas PCS & PO)';
            $st2Cols = $this->brandSummaryTable['columns'] ?? [
                ['key' => 'brand', 'label' => 'Nama Brand'],
                ['key' => 'pcs_normal', 'label' => 'PCS Normal'],
                ['key' => 'pcs_reseller', 'label' => 'PCS Reseller'],
                ['key' => 'pcs_support', 'label' => 'PCS PO Support'],
                ['key' => 'total_pcs', 'label' => 'Total PCS'],
                ['key' => 'total_po', 'label' => 'Total PO'],
            ];

            // Judul Tabel 2
            $output[] = [$st2Title];

            // Header Tabel 2
            $this->st2HeaderRow = count($output) + 1;
            $st2Header = [];
            foreach ($st2Cols as $c) {
                $st2Header[] = $c['label'];
            }
            $output[] = $st2Header;

            // Baris Data Tabel 2
            foreach ($this->brandSummaryTable['rows'] as $bRow) {
                $rArr = [];
                foreach ($st2Cols as $c) {
                    $rArr[] = $bRow[$c['key']] ?? 0;
                }
                $output[] = $rArr;
            }

            // Baris Total Tabel 2
            if (!empty($this->brandSummaryTable['total'])) {
                $rTot = [];
                foreach ($st2Cols as $c) {
                    $rTot[] = $this->brandSummaryTable['total'][$c['key']] ?? 0;
                }
                $output[] = $rTot;
                $this->st2TotalRow = count($output);
            }

            $output[] = [''];
        }

        return $output;
    }

    public function title(): string
    {
        return 'Ringkasan';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestCol = $sheet->getHighestColumn();

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

                // 2. Format Tabel 1 (Ringkasan Kategori)
                if ($this->st1HeaderRow > 0) {
                    $st1ColsCount = count($this->summaryTable['columns'] ?? [1, 2, 3]);
                    $st1EndColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($st1ColsCount);
                    $st1TitleRow = $this->st1HeaderRow - 1;
                    $st1HRow = $this->st1HeaderRow;

                    // Judul Tabel 1
                    $sheet->getStyle("A{$st1TitleRow}:{$st1EndColLetter}{$st1TitleRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 10.5, 'color' => ['rgb' => $this->primaryColor]],
                    ]);

                    // Header Kolom Tabel 1
                    $sheet->getStyle("A{$st1HRow}:{$st1EndColLetter}{$st1HRow}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->primaryColor]],
                        'alignment' => ['vertical' => 'center'],
                    ]);
                    $sheet->getRowDimension($st1HRow)->setRowHeight(25);

                    // Border Data Tabel 1
                    $st1EndRow = $this->st1TotalRow ?: ($st1HRow + count($this->summaryTable['rows']));
                    $sheet->getStyle("A{$st1HRow}:{$st1EndColLetter}{$st1EndRow}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
                    ]);

                    // Alignment Right untuk kolom angka di Tabel 1 (B sampai selesai)
                    $sheet->getStyle("B{$st1HRow}:{$st1EndColLetter}{$st1EndRow}")->applyFromArray([
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    ]);

                    // Total Row Tabel 1
                    if ($this->st1TotalRow > 0) {
                        $sheet->getStyle("A{$this->st1TotalRow}:{$st1EndColLetter}{$this->st1TotalRow}")->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                            'borders' => [
                                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']],
                                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '475569']],
                            ],
                        ]);
                    }
                }

                // 3. Format Tabel 2 (Ringkasan Per Brand Secara Jumlah)
                if ($this->st2HeaderRow > 0) {
                    $st2ColsCount = count($this->brandSummaryTable['columns'] ?? [1, 2, 3, 4, 5, 6]);
                    $st2EndColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($st2ColsCount);
                    $st2TitleRow = $this->st2HeaderRow - 1;
                    $st2HRow = $this->st2HeaderRow;

                    // Judul Tabel 2
                    $sheet->getStyle("A{$st2TitleRow}:{$st2EndColLetter}{$st2TitleRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 10.5, 'color' => ['rgb' => $this->primaryColor]],
                    ]);

                    // Header Kolom Tabel 2
                    $sheet->getStyle("A{$st2HRow}:{$st2EndColLetter}{$st2HRow}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->primaryColor]],
                        'alignment' => ['vertical' => 'center'],
                    ]);
                    $sheet->getRowDimension($st2HRow)->setRowHeight(25);

                    // Border Data Tabel 2
                    $st2EndRow = $this->st2TotalRow ?: ($st2HRow + count($this->brandSummaryTable['rows']));
                    $sheet->getStyle("A{$st2HRow}:{$st2EndColLetter}{$st2EndRow}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
                    ]);

                    // Alignment Right untuk kolom kuantitas di Tabel 2 (B sampai selesai)
                    $sheet->getStyle("B{$st2HRow}:{$st2EndColLetter}{$st2EndRow}")->applyFromArray([
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    ]);

                    // Total Row Tabel 2
                    if ($this->st2TotalRow > 0) {
                        $sheet->getStyle("A{$this->st2TotalRow}:{$st2EndColLetter}{$this->st2TotalRow}")->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                            'borders' => [
                                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']],
                                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '475569']],
                            ],
                        ]);
                    }
                }
            },
        ];
    }
}
