<?php

namespace App\Exports;

use App\Exports\Sheets\Hcm\HcmOvertimeDetailSheet;
use App\Exports\Sheets\Hcm\HcmOvertimeSummarySheet;
use App\Models\Hcm\HcmOvertimeBatch;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class HcmOvertimeBatchExport implements WithMultipleSheets
{
    public function __construct(private HcmOvertimeBatch $batch, private ?string $userName = null) {}

    public function sheets(): array
    {
        return [
            // Sheet 1: Ringkasan Voucher Lembur & Agregasi
            new HcmOvertimeSummarySheet($this->batch, $this->userName),

            // Sheet 2: Rincian Lengkap Kompensasi Lembur Karyawan
            new HcmOvertimeDetailSheet($this->batch),
        ];
    }
}
