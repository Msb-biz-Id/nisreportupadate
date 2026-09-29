<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class HcmRecruitmentReportExport implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private int $lokerHeaderRow = 6;
    private int $lokerEndRow = 0;
    private int $detailHeaderRow = 0;
    private int $detailEndRow = 0;
    private int $lokerCols = 16;
    private int $detailCols = 11;

    public function __construct(
        private Collection $rows,
        private ?object $selectedJob = null,
        private Collection $applicants = new Collection(),
        private ?string $userName = null
    ) {}

    public function array(): array
    {
        $out = [];

        $out[] = ['NISGROUP — SISTEM HCM'];
        $out[] = ['LAPORAN PERFORMA REKRUTMEN'];
        $out[] = ['Lingkup: ' . ($this->selectedJob ? $this->selectedJob->title . ' (' . $this->selectedJob->job_code . ')' : 'Semua Loker')];
        $out[] = [
            'Waktu Unduh: ' . now()->translatedFormat('d M Y, H:i') . ' WIB'
            . ($this->userName ? '  |  Petugas: ' . $this->userName : '')
            . '  |  Total Loker: ' . $this->rows->count(),
        ];
        $out[] = [''];

        // ===== Bagian 1: Rekap per loker =====
        $out[] = [
            'No', 'Kode Loker', 'Posisi / Judul', 'Divisi', 'Entitas (CV)', 'Kuota',
            'Pelamar', 'Screening', 'Interview', 'Diterima', 'Ditolak', 'Hired',
            'Pemenuhan (%)', 'Konversi (%)', 'TT Hire (hari)', 'Status',
        ];
        $this->lokerHeaderRow = count($out);

        $no = 1;
        foreach ($this->rows as $r) {
            $out[] = [
                $no++,
                $r['job_code'] ?: '-',
                $r['title'],
                $r['department'] ?: '-',
                $r['legal_entity'] ?: '-',
                $r['quota'],
                $r['total_applicants'],
                $r['screening'],
                $r['interview'],
                $r['accepted'],
                $r['rejected'],
                $r['hired'],
                $r['fulfillment_rate'],
                $r['conversion_rate'],
                $r['avg_time_to_hire'] !== null ? $r['avg_time_to_hire'] : '-',
                $r['status'],
            ];
        }
        $this->lokerEndRow = count($out);

        // ===== Bagian 2: Detail pelamar (opsional) =====
        if ($this->selectedJob) {
            $out[] = [''];
            $out[] = ['DETAIL PELAMAR — ' . $this->selectedJob->title . ' (' . $this->selectedJob->job_code . ')'];
            $out[] = [
                'No', 'Kode Pelamar', 'Nama', 'L/P', 'Pendidikan', 'Tgl Melamar',
                'Tahap', 'Hasil Interview', 'Offering', 'Jml Wawancara', 'Status Kehadiran Kerja',
            ];
            $this->detailHeaderRow = count($out);

            $no = 1;
            foreach ($this->applicants as $a) {
                $latest = $a->interviews->first();
                $out[] = [
                    $no++,
                    $a->applicant_code,
                    $a->name,
                    strtoupper(substr((string) $a->gender, 0, 1)) ?: '-',
                    $a->education ?: '-',
                    $a->apply_date ? $a->apply_date->format('d-m-Y') : '-',
                    $a->status,
                    $a->interview_result ?: ($latest->interview_result ?? '-'),
                    $latest->offering_status ?? '-',
                    $a->interviews->count(),
                    $a->onboarding_attendance ?: '-',
                ];
            }
            $this->detailEndRow = count($out);
        }

        return $out;
    }

    public function title(): string
    {
        return 'Laporan Rekrutmen';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Judul
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0F172A');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('1E40AF');
                $sheet->getStyle('A3:A4')->getFont()->setSize(10)->getColor()->setRGB('334155');

                $this->styleHeader($sheet, $this->lokerHeaderRow, $this->lokerCols);
                $this->styleHeader($sheet, $this->detailHeaderRow, $this->detailCols);

                if ($this->lokerEndRow > $this->lokerHeaderRow) {
                    $sheet->getStyle("A{$this->lokerHeaderRow}:P{$this->lokerEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                }
                if ($this->detailEndRow > $this->detailHeaderRow && $this->detailHeaderRow > 0) {
                    $sheet->getStyle("A{$this->detailHeaderRow}:K{$this->detailEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $sheet->mergeCells("A{$this->detailHeaderRow}:A{$this->detailHeaderRow}");
                }
            },
        ];
    }

    private function styleHeader($sheet, int $row, int $cols): void
    {
        if ($row <= 0) {
            return;
        }
        $sheet->getStyle("A{$row}:" . $this->colLetter($cols) . "{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
    }

    private function colLetter(int $index): string
    {
        $letters = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $index = intdiv($index - 1, 26);
        }
        return $letters;
    }
}
