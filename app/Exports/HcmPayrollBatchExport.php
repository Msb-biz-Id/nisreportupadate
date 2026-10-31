<?php

namespace App\Exports;

use App\Exports\Sheets\Hcm\HcmPayrollBriTransferSheet;
use App\Exports\Sheets\Hcm\HcmPayrollDetailSheet;
use App\Exports\Sheets\Hcm\HcmPayrollSummarySheet;
use App\Models\Hcm\HcmPayroll;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class HcmPayrollBatchExport implements WithMultipleSheets
{
    public function __construct(private HcmPayroll $payroll, private ?string $userName = null) {}

    public function sheets(): array
    {
        return [
            // Sheet 1: Rekapitulasi Penggajian per Departemen & Divisi (Laporan Eksekutif untuk Owner)
            new HcmPayrollSummarySheet($this->payroll, $this->userName),

            // Sheet 2: Format Data Transfer Massal Bank BRI
            new HcmPayrollBriTransferSheet($this->payroll),

            // Sheet 3: Rincian Lengkap Seluruh Komponen Gaji per Karyawan
            new HcmPayrollDetailSheet($this->payroll),
        ];
    }
}
