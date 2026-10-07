<?php

namespace App\Exports;

use App\Exports\Sheets\Hcm\HcmMealAllowanceDetailSheet;
use App\Exports\Sheets\Hcm\HcmMealAllowanceSummarySheet;
use App\Models\Hcm\HcmMealAllowanceBatch;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class HcmMealAllowanceBatchExport implements WithMultipleSheets
{
    public function __construct(private HcmMealAllowanceBatch $batch, private ?string $userName = null) {}

    public function sheets(): array
    {
        return [
            // Sheet 1: Ringkasan Eksekutif Hak Uang Makan
            new HcmMealAllowanceSummarySheet($this->batch, $this->userName),

            // Sheet 2: Rincian Lengkap per Karyawan & Presensi
            new HcmMealAllowanceDetailSheet($this->batch),
        ];
    }
}
