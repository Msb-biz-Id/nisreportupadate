<?php

namespace App\Exports\Sheets\Hcm;

use App\Models\Hcm\HcmPayroll;
use App\Services\HcmPdfHelper;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class HcmPayrollBriTransferSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private int $headerRow = 6;
    private int $endRow = 0;

    public function __construct(private HcmPayroll $payroll) {}

    public function title(): string
    {
        return 'Transfer Bank BRI';
    }

    public function array(): array
    {
        $profile = HcmPdfHelper::getProfileData();
        $companyName = $profile['company_name'] ?? 'PT Nusantara Inti Solusindo';

        $out = [];
        $out[] = [$companyName . ' — CORPORATE PAYROLL DISBURSEMENT'];
        $out[] = ['FORMAT DATA TRANSFER MASSAL PENGGAJIAN BANK BRI'];
        $out[] = [
            'Periode: ' . $this->payroll->period_code
            . '  |  Bulan Kinerja: ' . $this->payroll->work_period_month
            . '  |  Realisasi: ' . $this->payroll->payout_period_month
            . '  |  Waktu Unduh: ' . now()->translatedFormat('d F Y, H:i') . ' WIB',
        ];
        $out[] = [''];

        // Header Kolom Tabel Sesuai Format Mass Transfer
        $out[] = [
            'No',
            'Nomor Rekening BRI',
            'Nama Pemilik Rekening',
            'Nominal Transfer (Rp)',
            'Keterangan / Berita Transfer',
            'Departemen',
            'Divisi',
            'NIP Pegawai',
            'Status Pembayaran',
        ];
        $this->headerRow = count($out);

        $no = 1;
        $items = $this->payroll->items ?? collect();
        foreach ($items as $it) {
            $emp = $it->employee;
            $accNo = $it->bank_account_no ?: ($emp?->bank_account_no ?: '-');
            $accName = $it->bank_account_name ?: ($emp?->name ?: '-');
            $empCode = $emp?->employee_code ?: '-';
            $netAmount = (float) $it->net_salary;
            $remark = "Gaji {$this->payroll->period_code} {$accName}";

            $statusText = ($it->is_paid || $this->payroll->status === 'PAID_COMPLETED')
                ? 'LUNAS (PAID)'
                : 'MENUNGGU';

            $out[] = [
                $no++,
                $accNo,
                $accName,
                $netAmount,
                $remark,
                $it->department ?: ($emp?->department ?: '-'),
                $it->division ?: ($emp?->division ?: 'Umum'),
                $empCode,
                $statusText,
            ];
        }
        $this->endRow = count($out);

        // Baris Total Transfer
        $totalTransfer = (float) ($this->payroll->total_net_payout ?: $items->sum('net_salary'));
        $out[] = [
            'TOTAL TRANSFER MASSAL',
            '',
            '',
            $totalTransfer,
            "Total {$items->count()} Penerima Transfer",
            '',
            '',
            '',
            '',
        ];

        return $out;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('0F172A');
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('00529C'); // Warna Biru Khas BRI
                $sheet->getStyle('A3')->getFont()->setSize(9)->getColor()->setRGB('64748B');

                // Header Baris Tabel
                $sheet->getStyle("A{$this->headerRow}:I{$this->headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00529C']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // Body Data
                if ($this->endRow >= $this->headerRow) {
                    $sheet->getStyle("A{$this->headerRow}:I{$this->endRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

                    for ($r = $this->headerRow + 1; $r <= $this->endRow; $r++) {
                        $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("B{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("B{$r}")->getNumberFormat()->setFormatCode('@'); // Text string format untuk No Rekening
                        $sheet->getStyle("D{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle("D{$r}")->getNumberFormat()->setFormatCode('#,##0');
                        $sheet->getStyle("H{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("I{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                        // Status Color
                        $statusVal = (string) $sheet->getCell("I{$r}")->getValue();
                        if (str_contains($statusVal, 'PAID')) {
                            $sheet->getStyle("I{$r}")->getFont()->setBold(true)->getColor()->setRGB('15803D');
                        } else {
                            $sheet->getStyle("I{$r}")->getFont()->setBold(true)->getColor()->setRGB('D97706');
                        }

                        if ($r % 2 === 0) {
                            $sheet->getStyle("A{$r}:I{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
                        }
                    }
                }

                // Total Row
                $totalRow = $this->endRow + 1;
                $sheet->mergeCells("A{$totalRow}:C{$totalRow}");
                $sheet->getStyle("A{$totalRow}:I{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '0F172A']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0F2FE']],
                ]);
                $sheet->getStyle("D{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("D{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');
            },
        ];
    }
}
