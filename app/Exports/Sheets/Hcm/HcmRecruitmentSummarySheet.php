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

class HcmRecruitmentSummarySheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private int $kpiStartRow = 7;
    private int $kpiEndRow = 7;
    private int $channelStartRow = 0;
    private int $channelEndRow = 0;
    private int $deptStartRow = 0;
    private int $deptEndRow = 0;

    public function __construct(
        private Collection $rows,
        private ?object $selectedJob = null,
        private Collection $applicants = new Collection(),
        private ?string $userName = null,
        private array $channels = []
    ) {}

    public function title(): string
    {
        return 'Ringkasan Rekrutmen';
    }

    public function array(): array
    {
        $profile = HcmPdfHelper::getProfileData();
        $companyName = $profile['company_name'] ?? 'PT Nusantara Inti Solusindo';
        $divisionName = $profile['division_name'] ?? 'Divisi Human Capital Management';

        $totalJobs = $this->rows->count();
        $totalQuota = $this->rows->sum('quota');
        $totalApplicants = $this->rows->sum('total_applicants');
        $totalScreening = $this->rows->sum('screening');
        $totalInterview = $this->rows->sum('interview');
        $totalAccepted = $this->rows->sum('accepted');
        $totalHired = $this->rows->sum('hired');
        $avgFulfillment = $totalQuota > 0 ? round(($totalHired / $totalQuota) * 100, 1) : 0;
        $conversionRate = $totalApplicants > 0 ? round(($totalHired / $totalApplicants) * 100, 1) : 0;

        $out = [];
        $out[] = [$companyName];
        $out[] = [$divisionName];
        $out[] = ['RINGKASAN EKSEKUTIF PERFORMA REKRUTMEN'];
        $out[] = [
            'Lingkup: ' . ($this->selectedJob ? $this->selectedJob->title . ' (' . $this->selectedJob->job_code . ')' : 'Seluruh Lowongan Kerja'),
            '',
            'Waktu Unduh: ' . now()->translatedFormat('d F Y, H:i') . ' WIB',
            '',
            'Petugas: ' . ($this->userName ?: 'Admin HCM'),
        ];
        $out[] = ['']; // separator

        // ===== 1. TABEL METRIK UTAMA (KPI) =====
        $out[] = ['INDIKATOR KINERJA UTAMA (KEY PERFORMANCE INDICATORS)', '', '', ''];
        $this->kpiStartRow = count($out) + 1;
        $out[] = ['No', 'Indikator Rekrutmen', 'Nilai / Realisasi', 'Satuan / Keterangan'];
        $out[] = [1, 'Total Lowongan Dibuka (Job Postings)', $totalJobs, 'Posisi Kerja'];
        $out[] = [2, 'Total Target / Kuota Kebutuhan', $totalQuota, 'Orang'];
        $out[] = [3, 'Total Berkas Pelamar Masuk', $totalApplicants, 'Berkas'];
        $out[] = [4, 'Tahap Screening / Seleksi Berkas', $totalScreening, 'Kandidat'];
        $out[] = [5, 'Tahap Wawancara / Interview', $totalInterview, 'Kandidat'];
        $out[] = [6, 'Kandidat Diterima / Offering', $totalAccepted, 'Kandidat'];
        $out[] = [7, 'Kandidat Masuk Kerja (Hired)', $totalHired, 'Karyawan Baru'];
        $out[] = [8, 'Rata-rata Pemenuhan Target (Fulfillment Rate)', $avgFulfillment . '%', 'Terhadap Kuota'];
        $out[] = [9, 'Rasio Konversi Pelamar ke Karyawan', $conversionRate . '%', 'Hired vs Pelamar Masuk'];
        $this->kpiEndRow = count($out);
        $out[] = ['']; // separator

        // ===== 2. PERFORMA SALURAN / CHANNEL REKRUTMEN =====
        if (!empty($this->channels)) {
            $out[] = ['REKAPITULASI SUMBER & SALURAN REKRUTMEN (CHANNELS)', '', '', ''];
            $this->channelStartRow = count($out) + 1;
            $out[] = ['No', 'Nama Saluran Rekrutmen', 'Jumlah Pelamar', 'Kandidat Diterima (Hired)', 'Kontribusi (%)'];
            $cNo = 1;
            foreach ($this->channels as $ch) {
                $chName = is_array($ch) ? ($ch['channel'] ?? $ch['name'] ?? '-') : (string) $ch;
                $chApplicants = is_array($ch) ? ($ch['applicants'] ?? 0) : 0;
                $chHired = is_array($ch) ? ($ch['hired'] ?? 0) : 0;
                $chShare = $totalApplicants > 0 ? round(($chApplicants / $totalApplicants) * 100, 1) : 0;
                $out[] = [$cNo++, $chName, $chApplicants, $chHired, $chShare . '%'];
            }
            $this->channelEndRow = count($out);
            $out[] = ['']; // separator
        }

        // ===== 3. REKAPITULASI PER DEPARTEMEN =====
        $byDept = $this->rows->groupBy('department');
        if ($byDept->isNotEmpty()) {
            $out[] = ['REKAPITULASI PER DEPARTEMEN / DIVISI', '', '', '', '', ''];
            $this->deptStartRow = count($out) + 1;
            $out[] = ['No', 'Departemen', 'Jml Loker', 'Target Kuota', 'Pelamar Masuk', 'Kandidat Hired', 'Pemenuhan (%)'];
            $dNo = 1;
            foreach ($byDept as $deptName => $deptJobs) {
                $dQuota = $deptJobs->sum('quota');
                $dApp = $deptJobs->sum('total_applicants');
                $dHired = $deptJobs->sum('hired');
                $dFulfill = $dQuota > 0 ? round(($dHired / $dQuota) * 100, 1) : 0;
                $out[] = [$dNo++, $deptName ?: 'Umum / Lainnya', $deptJobs->count(), $dQuota, $dApp, $dHired, $dFulfill . '%'];
            }
            $this->deptEndRow = count($out);
        }

        return $out;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Judul Perusahaan & Divisi
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0F172A');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('A8001C');
                $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('1E293B');
                $sheet->getStyle('A4:E4')->getFont()->setSize(9)->getColor()->setRGB('64748B');

                // Header KPI Section
                $kpiTitleRow = $this->kpiStartRow - 1;
                $sheet->getStyle("A{$kpiTitleRow}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('0F172A');
                $sheet->getStyle("A{$this->kpiStartRow}:D{$this->kpiStartRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("B{$this->kpiStartRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Data KPI
                if ($this->kpiEndRow >= $this->kpiStartRow) {
                    $sheet->getStyle("A{$this->kpiStartRow}:D{$this->kpiEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
                    for ($r = $this->kpiStartRow + 1; $r <= $this->kpiEndRow; $r++) {
                        $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle("C{$r}")->getFont()->setBold(true);
                        $sheet->getStyle("D{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        if ($r % 2 === 0) {
                            $sheet->getStyle("A{$r}:D{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
                        }
                    }
                }

                // Channel Section
                if ($this->channelStartRow > 0 && $this->channelEndRow >= $this->channelStartRow) {
                    $chTitleRow = $this->channelStartRow - 1;
                    $sheet->getStyle("A{$chTitleRow}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('0F172A');
                    $sheet->getStyle("A{$this->channelStartRow}:E{$this->channelStartRow}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A8001C']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    $sheet->getStyle("B{$this->channelStartRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("A{$this->channelStartRow}:E{$this->channelEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
                    for ($r = $this->channelStartRow + 1; $r <= $this->channelEndRow; $r++) {
                        $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("C{$r}:E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }
                }

                // Dept Section
                if ($this->deptStartRow > 0 && $this->deptEndRow >= $this->deptStartRow) {
                    $dpTitleRow = $this->deptStartRow - 1;
                    $sheet->getStyle("A{$dpTitleRow}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('0F172A');
                    $sheet->getStyle("A{$this->deptStartRow}:G{$this->deptStartRow}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    $sheet->getStyle("B{$this->deptStartRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("A{$this->deptStartRow}:G{$this->deptEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
                    for ($r = $this->deptStartRow + 1; $r <= $this->deptEndRow; $r++) {
                        $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("C{$r}:G{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }
                }
            },
        ];
    }
}
