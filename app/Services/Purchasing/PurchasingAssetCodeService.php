<?php

namespace App\Services\Purchasing;

use App\Models\Hcm\HcmMasterOption;
use App\Models\Purchasing\PurchasingAsset;
use App\Models\Purchasing\PurchasingMasterOption;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchasingAssetCodeService
{
    /**
     * Format tahun 3 digit sesuai aturan NIS:
     * 2026 -> 026, 2025 -> 025.
     */
    public function formatYearCode(?int $year = null): string
    {
        $year = $year ?: (int) date('Y');
        $twoDigit = substr((string) $year, -2);
        return sprintf('%03d', (int) $twoDigit);
    }

    /**
     * Dapatkan kode kategori valid dari master option atau uppercase string.
     */
    public function sanitizeCategoryCode(string $categoryCode): string
    {
        $code = strtoupper(trim($categoryCode));
        if (empty($code)) {
            throw new InvalidArgumentException('Kode kategori aset tidak boleh kosong.');
        }
        return $code;
    }

    /**
     * Dapatkan kode departemen resmi dari nama atau kode.
     */
    public function resolveDepartmentCode(string $department): string
    {
        // Cek jika sudah berupa kode singkatan 2-5 karakter (misal HCM, FIN, BRM, PRD, SCP, MIN, MEX)
        $clean = strtoupper(trim($department));
        $departmentsWithCodes = HcmMasterOption::getDepartmentsWithCodes();
        
        foreach ($departmentsWithCodes as $dep) {
            if (strtoupper($dep['code']) === $clean || strtoupper($dep['name']) === $clean) {
                return strtoupper($dep['code']);
            }
        }

        // Coba via getDepartmentCodeByName
        $deptCode = HcmMasterOption::getDepartmentCodeByName($department);
        if ($deptCode) {
            return strtoupper($deptCode);
        }

        // Fallback: jika clean panjangnya <= 5 karakter alfabetik gunakan langsung, jika panjang buat akronim
        if (strlen($clean) <= 5 && ctype_alnum($clean)) {
            return $clean;
        }

        // Buat kode dari huruf awal kata
        $words = preg_split('/\s+/', $department);
        $acronym = '';
        foreach ($words as $w) {
            if (!empty($w)) {
                $acronym .= strtoupper($w[0]);
            }
        }

        return !empty($acronym) ? substr($acronym, 0, 4) : 'GEN';
    }

    /**
     * Generate Kode Aset Otomatis: [Kategori].[Dept].[Tahun3Digit].[Urut3Digit]
     * Menggunakan locking DB untuk anti-race-condition.
     *
     * @return array{
     *     asset_code: string,
     *     category_code: string,
     *     department_code: string,
     *     year_code: string,
     *     sequence_number: int
     * }
     */
    public function generateAssetCode(string $categoryCode, string $departmentCode, ?int $year = null): array
    {
        $categoryCode = $this->sanitizeCategoryCode($categoryCode);
        $departmentCode = $this->resolveDepartmentCode($departmentCode);
        $yearCode = $this->formatYearCode($year);

        // Ambil sequence berikutnya dengan lockForUpdate
        $nextSequence = DB::transaction(function () use ($categoryCode, $departmentCode, $yearCode) {
            $lastSequence = PurchasingAsset::where('category_code', $categoryCode)
                ->where('department_code', $departmentCode)
                ->where('year_code', $yearCode)
                ->lockForUpdate()
                ->max('sequence_number');

            return ((int) $lastSequence) + 1;
        });

        $sequenceCode = sprintf('%03d', $nextSequence);
        $assetCode = "{$categoryCode}.{$departmentCode}.{$yearCode}.{$sequenceCode}";

        return [
            'asset_code' => $assetCode,
            'category_code' => $categoryCode,
            'department_code' => $departmentCode,
            'year_code' => $yearCode,
            'sequence_number' => $nextSequence,
        ];
    }

    /**
     * Hitung kode aset baru saat mutasi departemen.
     * Kategori dan tahun perolehan dipertahankan, sequence di-generate untuk departemen tujuan baru.
     *
     * @return array{
     *     asset_code: string,
     *     category_code: string,
     *     department_code: string,
     *     year_code: string,
     *     sequence_number: int
     * }
     */
    public function generateMutatedAssetCode(PurchasingAsset $asset, string $toDepartmentCode): array
    {
        $newDeptCode = $this->resolveDepartmentCode($toDepartmentCode);

        return $this->generateAssetCode(
            $asset->category_code,
            $newDeptCode,
            $asset->purchase_date ? (int) $asset->purchase_date->format('Y') : (int) ('20' . substr($asset->year_code, 1))
        );
    }
}
