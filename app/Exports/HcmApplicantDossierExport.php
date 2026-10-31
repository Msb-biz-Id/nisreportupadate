<?php

namespace App\Exports;

use App\Models\Hcm\HcmJobApplicant;
use App\Services\HcmPdfHelper;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class HcmApplicantDossierExport implements WithMultipleSheets
{
    public function __construct(private HcmJobApplicant $applicant) {}

    public function sheets(): array
    {
        return [
            new class($this->applicant) implements FromArray, WithTitle, ShouldAutoSize, WithEvents {
                private int $bioEndRow = 0;

                public function __construct(private HcmJobApplicant $applicant) {}

                public function title(): string
                {
                    return 'Ringkasan Profil';
                }

                public function array(): array
                {
                    $profile = HcmPdfHelper::getProfileData();
                    $companyName = $profile['company_name'] ?? 'PT Nusantara Inti Solusindo';
                    $divisionName = $profile['division_name'] ?? 'Divisi Human Capital Management';
                    $age = $this->applicant->birth_date ? $this->applicant->birth_date->age . ' tahun' : '-';

                    $out = [];
                    $out[] = [$companyName];
                    $out[] = [$divisionName];
                    $out[] = ['BERKAS PROFIL & BIODATA PELAMAR KERJA'];
                    $out[] = [
                        'Kode Pelamar: ' . $this->applicant->applicant_code,
                        '',
                        'Status Saat Ini: ' . strtoupper($this->applicant->status ?? 'REVIEW'),
                    ];
                    $out[] = [''];

                    $out[] = ['DATA PRIBADI & KUALIFIKASI KANDIDAT', '', '', ''];
                    $out[] = ['Parameter / Biodata', 'Data / Informasi Pelamar', 'Kategori', 'Keterangan'];
                    $out[] = ['Nama Lengkap', $this->applicant->name, 'Identitas', ''];
                    $out[] = ['Kode Pelamar', $this->applicant->applicant_code, 'Identitas', 'Sistem Rekrutmen'];
                    $out[] = ['Posisi yang Dilamar', $this->applicant->jobPosting?->title ?? 'Umum', 'Lowongan', $this->applicant->jobPosting?->department ?? '-'];
                    $out[] = ['Status Seleksi', strtoupper($this->applicant->status ?? '-'), 'Status', ''];
                    $out[] = ['Jenis Kelamin', $this->applicant->gender ?: '-', 'Identitas', ''];
                    $out[] = [
                        'Tempat, Tanggal Lahir',
                        ($this->applicant->birth_place ?: '-') . ', ' . ($this->applicant->birth_date ? $this->applicant->birth_date->isoFormat('D MMMM Y') : '-'),
                        'Identitas',
                        "Usia: {$age}"
                    ];
                    $out[] = ['Pendidikan Terakhir', $this->applicant->education ?: '-', 'Kualifikasi', ''];
                    $out[] = ['Institusi / Kampus / Sekolah', $this->applicant->institution ?: '-', 'Kualifikasi', ''];
                    $out[] = ['Jurusan / Bidang Keahlian', $this->applicant->major ?: '-', 'Kualifikasi', ''];
                    $out[] = ['Nomor WhatsApp / HP', $this->applicant->phone ?: '-', 'Kontak', ''];
                    $out[] = ['Alamat Email Aktif', $this->applicant->email ?: '-', 'Kontak', ''];
                    $out[] = ['Alamat Domisili', $this->applicant->address ?: '-', 'Domisili', ''];
                    $out[] = ['Tanggal Melamar', $this->applicant->apply_date ? $this->applicant->apply_date->isoFormat('D MMMM Y') : '-', 'Riwayat', ''];
                    $out[] = ['Sumber Informasi Loker', $this->applicant->source ?: 'Website Karir', 'Akuisisi', ''];
                    $this->bioEndRow = count($out);

                    return $out;
                }

                public function registerEvents(): array
                {
                    return [
                        AfterSheet::class => function (AfterSheet $event) {
                            $sheet = $event->sheet->getDelegate();
                            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('0F172A');
                            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('A8001C');
                            $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('1E293B');
                            $sheet->getStyle('A4:D4')->getFont()->setSize(9)->getColor()->setRGB('64748B');

                            $sheet->getStyle('A6:D6')->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                            ]);

                            if ($this->bioEndRow >= 7) {
                                $sheet->getStyle("A7:D{$this->bioEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
                                for ($r = 7; $r <= $this->bioEndRow; $r++) {
                                    $sheet->getStyle("A{$r}")->getFont()->setBold(true)->getColor()->setRGB('475569');
                                    $sheet->getStyle("B{$r}")->getFont()->setBold(true)->getColor()->setRGB('0F172A');
                                    if ($r % 2 === 0) {
                                        $sheet->getStyle("A{$r}:D{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
                                    }
                                }
                            }
                        },
                    ];
                }
            },

            new class($this->applicant) implements FromArray, WithTitle, ShouldAutoSize, WithEvents {
                private int $ivEndRow = 0;

                public function __construct(private HcmJobApplicant $applicant) {}

                public function title(): string
                {
                    return 'Riwayat Wawancara';
                }

                public function array(): array
                {
                    $profile = HcmPdfHelper::getProfileData();
                    $companyName = $profile['company_name'] ?? 'PT Nusantara Inti Solusindo';

                    $out = [];
                    $out[] = [$companyName . ' — REKRUTMEN'];
                    $out[] = ['RIWAYAT EVALUASI & CATATAN WAWANCARA KANDIDAT'];
                    $out[] = ['Kandidat: ' . $this->applicant->name . ' (' . $this->applicant->applicant_code . ')'];
                    $out[] = [''];

                    $out[] = ['No', 'Tahap / Babak', 'Pewawancara / Tim Penilai', 'Tanggal Sesi', 'Hasil Wawancara', 'Offering Status', 'Catatan & Masukan Wawancara'];

                    $no = 1;
                    $interviews = $this->applicant->interviews ?? collect();
                    foreach ($interviews as $iv) {
                        $out[] = [
                            $no++,
                            $iv->interview_round ?: 'Tahap 1',
                            $iv->interviewer_name ?: ($iv->creator?->name ?? '-'),
                            $iv->interview_date ? $iv->interview_date->format('d/m/Y') : '-',
                            $iv->interview_result ?: '-',
                            $iv->offering_status ?: '-',
                            $iv->offering_notes ?: ($iv->notes ?: '-'),
                        ];
                    }
                    if ($interviews->isEmpty()) {
                        $out[] = ['-', '-', 'Belum ada sesi wawancara tercatat untuk pelamar ini.', '-', '-', '-', '-'];
                    }
                    $this->ivEndRow = count($out);

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

                            $sheet->getStyle('A5:G5')->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                            ]);

                            if ($this->ivEndRow >= 6) {
                                $sheet->getStyle("A6:G{$this->ivEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
                                for ($r = 6; $r <= $this->ivEndRow; $r++) {
                                    $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                    $sheet->getStyle("D{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                    $sheet->getStyle("E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                    if ($r % 2 === 0) {
                                        $sheet->getStyle("A{$r}:G{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
                                    }
                                }
                            }
                        },
                    ];
                }
            },
        ];
    }
}
