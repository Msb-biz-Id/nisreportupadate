<?php

namespace App\Services\Purchasing;

use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmMasterOption;

class PurchasingHrisService
{
    /**
     * Dapatkan daftar departemen resmi dari HRIS beserta kodenya (Read-Only Single Source of Truth).
     *
     * @return array<int, array{name: string, code: string}>
     */
    public function getDepartmentsWithCodes(): array
    {
        return HcmMasterOption::getDepartmentsWithCodes();
    }

    /**
     * Dapatkan nama-nama departemen saja sebagai array string datar.
     *
     * @return array<int, string>
     */
    public function getDepartments(): array
    {
        $deps = $this->getDepartmentsWithCodes();
        return !empty($deps) ? array_column($deps, 'name') : ['Umum', 'Produksi', 'Finance', 'Marketing', 'HCM', 'IT'];
    }

    /**
     * Dapatkan peta hirarki Divisi dan Posisi Fungsional terikat berdasarkan lembar kerja Struktur Fungsi Kerja.
     *
     * @return array<string, array<string>>
     */
    public function getDivisionPositionMap(): array
    {
        return HcmMasterOption::getDepartmentDivisionsMap();
    }

    /**
     * Dapatkan daftar karyawan aktif untuk autocomplete form pemohon & pemegang aset.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveEmployees()
    {
        return HcmEmployee::where('is_active', true)
            ->select([
                'id',
                'uuid',
                'employee_code',
                'name',
                'department',
                'division',
                'position',
            ])
            ->orderBy('name')
            ->get();
    }

    /**
     * Dapatkan semua referensi HRIS yang dibutuhkan untuk modul Purchasing.
     *
     * @return array
     */
    public function getHrisReferences(): array
    {
        $hcmDropdowns = HcmMasterOption::getAllDropdowns();
        $deptDivMap = HcmMasterOption::getDepartmentDivisionsMap();

        return [
            'departments' => $hcmDropdowns['departments'] ?? [],
            'departments_with_codes' => $this->getDepartmentsWithCodes(),
            'divisions' => $hcmDropdowns['divisions'] ?? [],
            'department_division_map' => $deptDivMap,
            'division_position_map' => $deptDivMap,
            'all_positions' => HcmMasterOption::getOptions('posisi'),
        ];
    }
}
