<?php

namespace Database\Seeders;

use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmSalaryDeduction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HcmSalaryDeductionSeeder extends Seeder
{
    /**
     * Run database seeds for employee salary deductions.
     */
    public function run(): void
    {
        $adminUser = User::where('email', 'hcm@nisgroup.id')->first() ?? User::first();
        $employees = HcmEmployee::where('is_active', true)->get();

        if ($employees->isEmpty()) {
            return;
        }

        $empRahmat  = $employees->firstWhere('nickname', 'Rahmat') ?? $employees->get(3);
        $empDanang  = $employees->firstWhere('nickname', 'Danang') ?? $employees->get(2);
        $empBambang = $employees->firstWhere('nickname', 'Bambang') ?? $employees->get(0);

        $deductions = [
            // 1. Potongan Keterlambatan Berulang Rahmat (September 2026)
            [
                'employee_id' => $empRahmat?->id ?? $empBambang->id,
                'effective_payroll_month' => '2026-09',
                'deduction_category' => 'Pelanggaran (Disciplinary Penalty)',
                'calculation_type' => 'fixed',
                'percentage_rate' => null,
                'deduction_amount' => 150000.00,
                'base_salary_snapshot' => 2850000.00,
                'net_salary_snapshot' => 2700000.00,
                'notes' => 'Sanksi keterlambatan hadir kerja lebih dari 4 kali dalam periode September 2026.',
                'status' => 'APPLIED',
            ],
            // 2. Kelebihan Cuti Danang (September 2026)
            [
                'employee_id' => $empDanang?->id ?? $empBambang->id,
                'effective_payroll_month' => '2026-09',
                'deduction_category' => 'Kelebihan Pengambilan Cuti (Leave Exceed)',
                'calculation_type' => 'fixed',
                'percentage_rate' => null,
                'deduction_amount' => 100000.00,
                'base_salary_snapshot' => 3100000.00,
                'net_salary_snapshot' => 3000000.00,
                'notes' => 'Cuti melebihi jatah hak cuti tahunan tanpa kompensasi penggantian shift.',
                'status' => 'APPLIED',
            ],
            // 3. Cicilan Kasbon Perlengkapan Kerja / Pelanggaran (Oktober 2026)
            [
                'employee_id' => $empRahmat?->id ?? $empBambang->id,
                'effective_payroll_month' => '2026-10',
                'deduction_category' => 'Pelanggaran (Disciplinary Penalty)',
                'calculation_type' => 'fixed',
                'percentage_rate' => null,
                'deduction_amount' => 75000.00,
                'base_salary_snapshot' => 2850000.00,
                'net_salary_snapshot' => 2775000.00,
                'notes' => 'Keterlambatan masuk shift kerja pagi tanggal 05 dan 08 Oktober 2026.',
                'status' => 'APPROVED',
            ],
        ];

        foreach ($deductions as $item) {
            HcmSalaryDeduction::firstOrCreate(
                [
                    'employee_id' => $item['employee_id'],
                    'effective_payroll_month' => $item['effective_payroll_month'],
                    'deduction_category' => $item['deduction_category'],
                ],
                array_merge($item, [
                    'uuid' => (string) Str::uuid(),
                    'created_by' => $adminUser?->id,
                ])
            );
        }
    }
}
