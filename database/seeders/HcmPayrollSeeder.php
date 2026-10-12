<?php

namespace Database\Seeders;

use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmPayroll;
use App\Models\Hcm\HcmPayrollItem;
use App\Models\Hcm\HcmSalaryDeduction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HcmPayrollSeeder extends Seeder
{
    /**
     * Run database seeds for Unified Payroll and employee payroll items.
     */
    public function run(): void
    {
        $adminUser   = User::where('email', 'hcm@nisgroup.id')->first() ?? User::first();
        $financeUser = User::where('email', 'finance.nisgroup@gmail.com')->first() ?? $adminUser;
        $employees   = HcmEmployee::with('compensation')->where('is_active', true)->get();

        if ($employees->isEmpty()) {
            return;
        }

        // ==========================================
        // 1. BATCH SEPTEMBER 2026 (STATUS: PAID_COMPLETED)
        // ==========================================
        $payrollSep = HcmPayroll::updateOrCreate(
            ['period_code' => 'PAY-2026-09'],
            [
                'uuid' => (string) Str::uuid(),
                'work_period_month' => '2026-09',
                'payout_period_month' => '2026-10',
                'payout_date' => '2026-10-01',
                'status' => 'PAID_COMPLETED',
                'hcm_signed_by' => $adminUser?->id,
                'hcm_signed_at' => '2026-09-28 14:30:00',
                'finance_signed_by' => $financeUser?->id,
                'finance_signed_at' => '2026-09-30 10:15:00',
                'payment_method' => 'Transfer Bank',
                'notes' => 'Penggajian periode kinerja September 2026 telah dicairkan dan ditransfer tuntas.',
                'created_by' => $adminUser?->id,
            ]
        );

        foreach ($employees as $idx => $emp) {
            $baseSalary = (float) ($emp->compensation?->base_salary ?? 2850000.00);
            $mealAllowance = (float) ($emp->compensation?->meal_allowance ?? 450000.00);
            $overtimePay = ($idx % 2 === 0) ? 225000.00 : 0.00;

            // Cek apakah ada potongan gaji di bulan 2026-09
            $deduction = HcmSalaryDeduction::where('employee_id', $emp->id)
                ->where('effective_payroll_month', '2026-09')
                ->first();

            $penaltyDeduction = $deduction ? (float) $deduction->deduction_amount : 0.00;
            $leaveDeduction = 0.00;
            $tieredDeduction = 0.00;

            $item = HcmPayrollItem::updateOrCreate(
                [
                    'payroll_id' => $payrollSep->id,
                    'employee_id' => $emp->id,
                ],
                [
                    'uuid' => (string) Str::uuid(),
                    'department' => $emp->department,
                    'division' => $emp->division,
                    'employment_status' => $emp->employment_status,
                    'legal_entity' => $emp->legal_entity ?? 'CV Bawang Merah',
                    'bank_name' => $emp->bank_name ?? 'Bank BRI',
                    'bank_account_no' => $emp->bank_account_no ?? ('0011-01-' . rand(100000, 999999) . '-50-1'),
                    'bank_account_name' => $emp->name,
                    'base_salary' => $baseSalary,
                    'meal_allowance' => $mealAllowance,
                    'overtime_pay' => $overtimePay,
                    'increment_adjustment' => 0.00,
                    'penalty_deduction' => $penaltyDeduction,
                    'leave_deduction' => $leaveDeduction,
                    'tiered_deduction' => $tieredDeduction,
                    'slip_token' => Str::random(40),
                    'is_paid' => true,
                    'notes' => 'Gaji September 2026 ditransfer via payroll BRI',
                ]
            );

            $item->computeNet();
            $item->save();
        }

        $payrollSep->recalculateTotals();

        // ==========================================
        // 2. BATCH OKTOBER 2026 (STATUS: APPROVED_BY_HCM)
        // ==========================================
        $payrollOct = HcmPayroll::updateOrCreate(
            ['period_code' => 'PAY-2026-10'],
            [
                'uuid' => (string) Str::uuid(),
                'work_period_month' => '2026-10',
                'payout_period_month' => '2026-11',
                'payout_date' => '2026-11-01',
                'status' => 'APPROVED_BY_HCM',
                'hcm_signed_by' => $adminUser?->id,
                'hcm_signed_at' => now(),
                'finance_signed_by' => null,
                'finance_signed_at' => null,
                'payment_method' => 'Transfer Bank',
                'notes' => 'Penggajian periode Oktober 2026 telah diverifikasi HCM dan diajukan ke Keuangan.',
                'created_by' => $adminUser?->id,
            ]
        );

        foreach ($employees as $idx => $emp) {
            $baseSalary = (float) ($emp->compensation?->base_salary ?? 2850000.00);
            $mealAllowance = (float) ($emp->compensation?->meal_allowance ?? 450000.00);
            $overtimePay = ($idx % 3 === 0) ? 175000.00 : 0.00;

            $deduction = HcmSalaryDeduction::where('employee_id', $emp->id)
                ->where('effective_payroll_month', '2026-10')
                ->first();

            $penaltyDeduction = $deduction ? (float) $deduction->deduction_amount : 0.00;

            $item = HcmPayrollItem::updateOrCreate(
                [
                    'payroll_id' => $payrollOct->id,
                    'employee_id' => $emp->id,
                ],
                [
                    'uuid' => (string) Str::uuid(),
                    'department' => $emp->department,
                    'division' => $emp->division,
                    'employment_status' => $emp->employment_status,
                    'legal_entity' => $emp->legal_entity ?? 'CV Bawang Merah',
                    'bank_name' => $emp->bank_name ?? 'Bank BRI',
                    'bank_account_no' => $emp->bank_account_no ?? ('0011-01-' . rand(100000, 999999) . '-50-1'),
                    'bank_account_name' => $emp->name,
                    'base_salary' => $baseSalary,
                    'meal_allowance' => $mealAllowance,
                    'overtime_pay' => $overtimePay,
                    'increment_adjustment' => 0.00,
                    'penalty_deduction' => $penaltyDeduction,
                    'leave_deduction' => 0.00,
                    'tiered_deduction' => 0.00,
                    'slip_token' => Str::random(40),
                    'is_paid' => false,
                    'notes' => 'Draft penggajian Oktober 2026 siap verifikasi Finance',
                ]
            );

            $item->computeNet();
            $item->save();
        }

        $payrollOct->recalculateTotals();
    }
}
