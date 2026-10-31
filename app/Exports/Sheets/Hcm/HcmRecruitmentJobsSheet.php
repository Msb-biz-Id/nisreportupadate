<?php

namespace App\Exports\Sheets\Hcm;

use App\Services\HcmPdfHelper;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class HcmRecruitmentJobsSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private int $headerRow = 6;
    private int $endRow = 0;

    public function __construct(private Collection $rows) {}

    public function title(): string
    {
        return 'Data Lowongan Kerja';
    }

    public function array(): array
    {
        $profile = HcmPdfHelper::getProfileData();
        $companyName = $profile['company_name'] ?? 'PT Nusantara Inti Solusindo';

        $out = [];
        $out[] = [$companyName . ' — SISTEM HCM'];
        $out[] = ['DAFTAR LOWONGAN KERJA & REKAPITULASI PEMENUHAN (JOB POSTINGS)'];
        $out[] = ['Tanggal Unduh: ' . now()->translatedFormat('d F Y, H:i') . ' WIB  |  Total Posisi Lowongan: ' . $this->rows->count()];
        $out[] = [''];

        // Header Tabel
        $out[] = [
            'No', 'Kode Loker', 'Posisi / Judul Lowongan', 'Departemen', 'Entitas (CV)',
            'Target Kuota', 'Total Pelamar', 'Screening', 'Interview', 'Diterima',
            'Ditolak', 'Hired', 'Pemenuhan (%)', 'Konversi (%)', 'TT Hire (hari)', 'Status',
        ];
        $this->headerRow = count($out);

        $no = 1;
        foreach ($this->rows as $r) {
            $out[] = [
                $no++,
                $r['job_code'] ?: '-',
                $r['title'],
                $r['department'] ?: '-',
                $r['legal_entity'] ?: '-',
                (int) ($r['quota'] ?? 0),
                (int) ($r['total_applicants'] ?? 0),
                (int) ($r['screening'] ?? 0),
                (int) ($r['interview'] ?? 0),
                (int) ($r['accepted'] ?? 0),
                (int) ($r['rejected'] ?? 0),
                (int) ($r['hired'] ?? 0),
                (float) ($r['fulfillment_rate'] ?? 0),
                (float) ($r['conversion_rate'] ?? 0),
                $r['avg_time_to_hire'] !== null ? (float) $r['avg_time_to_hire'] : '-',
                $r['status'] ?? 'Aktif',
            ];
        }
        $this->endRow = count($out);

        // Baris Total
        $totalQuota = $this->rows->sum('quota');
        $totalApps = $this->rows->sum('total_applicants');
        $totalScreen = $this->rows->sum('screening');
        $totalIv = $this->rows->sum('interview');
        $totalAcc = $this->rows->sum('accepted');
        $totalRej = $this->rows->sum('rejected');
        $totalHired = $this->rows->sum('hired');
        $avgFulfill = $totalQuota > 0 ? round(($totalHired / $totalQuota) * 100, 1) : 0;
        $avgConv = $totalApps > 0 ? round(($totalHired / $totalApps) * 100, 1) : 0;

        $out[] = [
            'TOTAL', '', '', '', '',
            $totalQuota, $totalApps, $totalScreen, $totalIv, $totalAcc,
            $totalRej, $totalHired, $avgFulfill, $avgConv, '-', '',
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
                $sheet->getStyle("A{$this->headerRow}:P{$this->headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle("C{$this->headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Body Data
                if ($this->endRow >= $this->headerRow) {
                    $sheet->getStyle("A{$this->headerRow}:P{$this->endRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

                    for ($r = $this->headerRow + 1; $r <= $this->endRow; $r++) {
                        $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("F{$r}:O{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle("P{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                        if ($r % 2 === 0) {
                            $sheet->getStyle("A{$r}:P{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
                        }
                    }
                }

                // Total Row
                $totalRow = $this->endRow + 1;
                $sheet->mergeCells("A{$totalRow}:E{$totalRow}");
                $sheet->getStyle("A{$totalRow}:P{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 9.5, 'color' => ['rgb' => '0F172A']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                    'borders' => [
                        'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '94A3B8']],
                        'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '94A3B8']],
                    ],
                ]);
                $sheet->getStyle("A{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("F{$totalRow}:O{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }
}
