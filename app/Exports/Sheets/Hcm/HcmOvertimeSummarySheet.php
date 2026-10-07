<?php

namespace App\Exports\Sheets\Hcm;

use App\Models\Hcm\HcmOvertimeBatch;
use App\Services\HcmPdfHelper;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class HcmOvertimeSummarySheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private int $metaStartRow = 7;
    private int $metaEndRow = 7;
    private int $dayTypeStartRow = 0;
    private int $dayTypeEndRow = 0;
    private int $deptStartRow = 0;
    private int $deptEndRow = 0;

    public function __construct(private HcmOvertimeBatch $batch, private ?string $userName = null) {}

    public function title(): string
    {
        return 'Ringkasan Lembur';
    }

    public function array(): array
    {
        $profile = HcmPdfHelper::getProfileData();
        $companyName = $profile['company_name'] ?? 'PT Nusantara Inti Solusindo';
        $divisionName = $profile['division_name'] ?? 'Divisi Human Capital Management';

        $totalEmployees = $this->batch->items ? $this->batch->items->pluck('employee_id')->unique()->count() : 0;
        $totalHours = (float) ($this->batch->total_hours ?? 0);
        $totalAmount = (float) ($this->batch->total_amount ?? 0);
        $avgHours = $totalEmployees > 0 ? round($totalHours / $totalEmployees, 1) : 0;
        $avgPay = $totalEmployees > 0 ? round($totalAmount / $totalEmployees, 0) : 0;

        $out = [];
        $out[] = [$companyName];
        $out[] = [$divisionName];
        $out[] = ['RINGKASAN EKSEKUTIF VOUCHER LEMBUR KARYAWAN'];
        $out[] = [
            'Kode Voucher: ' . $this->batch->batch_code,
            '',
            'Waktu Unduh: ' . now()->translatedFormat('d F Y, H:i') . ' WIB',
            '',
            'Petugas: ' . ($this->userName ?: 'Admin HCM'),
        ];
        $out[] = [''];

        // ===== 1. METADATA & REKAP KEUANGAN BATCH =====
        $out[] = ['INFORMASI VOUCHER & REKAPITULASI BIAYA', '', '', ''];
        $this->metaStartRow = count($out) + 1;
        $out[] = ['No', 'Komponen / Parameter', 'Keterangan / Nilai', 'Satuan / Detail'];
        $out[] = [1, 'Nomor / Kode Batch', $this->batch->batch_code, 'Dokumen Sah'];
        $out[] = [
            2, 'Periode Pelaksanaan Lembur',
            $this->batch->period_start?->format('d/m/Y') . ' s.d. ' . $this->batch->period_end?->format('d/m/Y'),
            'Tanggal Kerja'
        ];
        $out[] = [3, 'Tanggal Pencairan / Bayar', $this->batch->payout_date?->format('d/m/Y') ?: '-', 'Payroll'];
        $out[] = [4, 'Departemen / Unit Kerja', $this->batch->department ?: 'Semua Departemen', 'Lingkup'];
        $out[] = [5, 'Kode COA Akuntansi', $this->batch->coa_code ?: '5-50100 (Beban Lembur)', 'Buku Besar'];
        $out[] = [6, 'Metode Pembayaran', $this->batch->payment_method ?: 'Transfer Payroll', 'Kas / Bank'];
        $out[] = [7, 'Status Voucher', $this->batch->status ?: 'APPROVED_BY_HCM', 'Otorisasi'];
        $out[] = [8, 'Total Karyawan Lembur', $totalEmployees, 'Orang'];
        $out[] = [9, 'Total Jam Lembur Disetujui', $totalHours, 'Jam Kerja'];
        $out[] = [10, 'Rata-rata Durasi Lembur per Orang', $avgHours, 'Jam / Orang'];
        $out[] = [11, 'Total Beban Biaya Lembur', 'Rp ' . number_format($totalAmount, 0, ',', '.'), 'Rupiah Bersih'];
        $out[] = [12, 'Rata-rata Upah Lembur per Orang', 'Rp ' . number_format($avgPay, 0, ',', '.'), 'Rupiah / Orang'];
        $this->metaEndRow = count($out);
        $out[] = [''];

        // ===== 2. REKAP PER JENIS HARI =====
        $items = $this->batch->items ?? collect();
        if ($items->isNotEmpty()) {
            $byDay = $items->groupBy('day_type');
            $out[] = ['REKAPITULASI BERDASARKAN JENIS HARI KERJA', '', '', ''];
            $this->dayTypeStartRow = count($out) + 1;
            $out[] = ['No', 'Jenis Hari', 'Total Jam', 'Total Biaya (Rp)'];
            $dNo = 1;
            foreach ($byDay as $dayType => $dayItems) {
                $out[] = [
                    $dNo++,
                    $dayType ?: 'Hari Kerja Biasa',
                    (float) $dayItems->sum('duration_hours'),
                    'Rp ' . number_format($dayItems->sum('total_amount'), 0, ',', '.'),
                ];
            }
            $this->dayTypeEndRow = count($out);
            $out[] = [''];
        }

        // ===== 3. REKAP PER DEPARTEMEN =====
        if ($items->isNotEmpty()) {
            $byDept = $items->groupBy(fn($i) => $i->employee?->department ?? 'Lainnya');
            $out[] = ['REKAPITULASI LEMBUR PER DEPARTEMEN', '', '', '', ''];
            $this->deptStartRow = count($out) + 1;
            $out[] = ['No', 'Departemen', 'Jml Karyawan', 'Total Jam', 'Total Upah (Rp)'];
            $dpNo = 1;
            foreach ($byDept as $deptName => $deptItems) {
                $out[] = [
                    $dpNo++,
                    $deptName,
                    $deptItems->pluck('employee_id')->unique()->count(),
                    (float) $deptItems->sum('duration_hours'),
                    'Rp ' . number_format($deptItems->sum('total_amount'), 0, ',', '.'),
                ];
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

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('0F172A');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('A8001C');
                $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('1E293B');
                $sheet->getStyle('A4:E4')->getFont()->setSize(9)->getColor()->setRGB('64748B');

                // Metadata Table
                $metaTitleRow = $this->metaStartRow - 1;
                $sheet->getStyle("A{$metaTitleRow}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('0F172A');
                $sheet->getStyle("A{$this->metaStartRow}:D{$this->metaStartRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("B{$this->metaStartRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                if ($this->metaEndRow >= $this->metaStartRow) {
                    $sheet->getStyle("A{$this->metaStartRow}:D{$this->metaEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
                    for ($r = $this->metaStartRow + 1; $r <= $this->metaEndRow; $r++) {
                        $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle("C{$r}")->getFont()->setBold(true);
                        if ($r % 2 === 0) {
                            $sheet->getStyle("A{$r}:D{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
                        }
                    }
                }

                // Day Type Table
                if ($this->dayTypeStartRow > 0 && $this->dayTypeEndRow >= $this->dayTypeStartRow) {
                    $dtTitleRow = $this->dayTypeStartRow - 1;
                    $sheet->getStyle("A{$dtTitleRow}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('0F172A');
                    $sheet->getStyle("A{$this->dayTypeStartRow}:D{$this->dayTypeStartRow}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A8001C']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    $sheet->getStyle("B{$this->dayTypeStartRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("A{$this->dayTypeStartRow}:D{$this->dayTypeEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
                    for ($r = $this->dayTypeStartRow + 1; $r <= $this->dayTypeEndRow; $r++) {
                        $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("C{$r}:D{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }
                }

                // Dept Table
                if ($this->deptStartRow > 0 && $this->deptEndRow >= $this->deptStartRow) {
                    $dpTitleRow = $this->deptStartRow - 1;
                    $sheet->getStyle("A{$dpTitleRow}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('0F172A');
                    $sheet->getStyle("A{$this->deptStartRow}:E{$this->deptStartRow}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    $sheet->getStyle("B{$this->deptStartRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("A{$this->deptStartRow}:E{$this->deptEndRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
                    for ($r = $this->deptStartRow + 1; $r <= $this->deptEndRow; $r++) {
                        $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("C{$r}:E{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }
                }
            },
        ];
    }
}
