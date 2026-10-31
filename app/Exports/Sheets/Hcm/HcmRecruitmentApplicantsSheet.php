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

class HcmRecruitmentApplicantsSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private int $headerRow = 6;
    private int $endRow = 0;

    public function __construct(private Collection $applicants, private ?object $selectedJob = null) {}

    public function title(): string
    {
        return 'Data Pelamar & Seleksi';
    }

    public function array(): array
    {
        $profile = HcmPdfHelper::getProfileData();
        $companyName = $profile['company_name'] ?? 'PT Nusantara Inti Solusindo';

        $out = [];
        $out[] = [$companyName . ' — SISTEM HCM'];
        $out[] = ['DATABASE REKAPITULASI PELAMAR & STATUS SELEKSI'];
        $out[] = [
            'Filter: ' . ($this->selectedJob ? $this->selectedJob->title . ' (' . $this->selectedJob->job_code . ')' : 'Semua Pelamar Masuk')
            . '  |  Total Pelamar: ' . $this->applicants->count()
            . '  |  Waktu Unduh: ' . now()->translatedFormat('d F Y, H:i') . ' WIB',
        ];
        $out[] = [''];

        // Header Kolom Tabel
        $out[] = [
            'No', 'Kode Pelamar', 'Posisi Dilamar', 'Nama Lengkap', 'L/P',
            'Pendidikan', 'Institusi / Sekolah', 'Jurusan', 'No HP / WhatsApp', 'Email',
            'Alamat Domisili', 'Tanggal Melamar', 'Sumber Info', 'Status Seleksi',
            'Tahap Interview', 'Hasil Evaluasi', 'Status Offering', 'Catatan / Feedback',
        ];
        $this->headerRow = count($out);

        $no = 1;
        foreach ($this->applicants as $a) {
            $latest = $a->interviews ? $a->interviews->first() : null;
            $out[] = [
                $no++,
                $a->applicant_code ?: '-',
                $a->jobPosting?->title ?? ($this->selectedJob?->title ?? '-'),
                $a->name,
                strtoupper(substr((string) ($a->gender ?? '-'), 0, 1)) ?: '-',
                $a->education ?: '-',
                $a->institution ?: '-',
                $a->major ?: '-',
                $a->phone ?: '-',
                $a->email ?: '-',
                $a->address ?: '-',
                $a->apply_date ? $a->apply_date->format('d/m/Y') : '-',
                $a->source ?: 'Website Karir',
                $a->status ?: 'Review',
                $latest?->interview_round ?: ($a->interviews && $a->interviews->count() > 0 ? 'Tahap ' . $a->interviews->count() : '-'),
                $a->interview_result ?: ($latest?->interview_result ?? '-'),
                $latest?->offering_status ?? '-',
                $latest?->notes ?: ($a->notes ?: '-'),
            ];
        }
        $this->endRow = count($out);

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
                $sheet->getStyle("A{$this->headerRow}:R{$this->headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // Body Data
                if ($this->endRow >= $this->headerRow) {
                    $sheet->getStyle("A{$this->headerRow}:R{$this->endRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

                    for ($r = $this->headerRow + 1; $r <= $this->endRow; $r++) {
                        $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("L{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("N{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                        if ($r % 2 === 0) {
                            $sheet->getStyle("A{$r}:R{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
                        }
                    }
                }
            },
        ];
    }
}
