<?php

namespace Database\Seeders;

use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmEmployeeReward;
use App\Models\Hcm\HcmMealAllowanceBatch;
use App\Models\Hcm\HcmMealAllowanceItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class HcmMealAllowanceAndRewardSeeder extends Seeder
{
    /**
     * Run the database seeds for monthly meal allowance and employee rewards.
     */
    public function run(): void
    {
        $hcmUser = User::where('email', 'hcm@nisgroup.id')->first() ?? User::first();
        $financeUser = User::where('email', 'aini.finance@nisgroup.id')->first() ?? $hcmUser;

        $employees = HcmEmployee::where('is_active', true)->get();
        if ($employees->isEmpty()) {
            return;
        }

        // 1. Batch Agustus 2026 (Paid & Completed)
        $batchAug = HcmMealAllowanceBatch::updateOrCreate(
            ['batch_code' => 'MA-2026-08'],
            [
                'period_month' => 8,
                'period_year' => 2026,
                'period_start' => '2026-08-01',
                'period_end' => '2026-08-31',
                'payout_date' => '2026-09-02',
                'total_employees' => $employees->count(),
                'status' => 'PAID_COMPLETED',
                'payment_method' => 'Transfer Bank',
                'coa_code' => '5-50110',
                'finance_notes' => 'Telah ditransfer serentak via payroll rekening bank BRI & BCA.',
                'hcm_signed_by' => $hcmUser?->id,
                'hcm_signed_at' => Carbon::create(2026, 9, 1, 15, 0),
                'finance_signed_by' => $financeUser?->id,
                'finance_signed_at' => Carbon::create(2026, 9, 2, 10, 30),
                'created_by' => $hcmUser?->id,
            ]
        );

        $totalAugAmount = 0;
        foreach ($employees as $emp) {
            $base = 280000;
            $present = 25;
            $late = 1;
            $deduction = 0;
            $payable = $base - $deduction;

            HcmMealAllowanceItem::updateOrCreate(
                ['batch_id' => $batchAug->id, 'employee_id' => $emp->id],
                [
                    'position' => $emp->position,
                    'department' => $emp->department,
                    'base_allowance' => $base,
                    'present_days' => $present,
                    'late_days' => $late,
                    'deduction_amount' => $deduction,
                    'payable_amount' => $payable,
                    'is_hold' => false,
                    'bonus_eligible' => true,
                    'notes' => 'Uang makan bulan Agustus lunas.',
                ]
            );
            $totalAugAmount += $payable;
        }
        $batchAug->update(['total_amount' => $totalAugAmount, 'total_held_amount' => 0]);

        // 2. Batch September 2026 (Approved by HCM)
        $batchSep = HcmMealAllowanceBatch::updateOrCreate(
            ['batch_code' => 'MA-2026-09'],
            [
                'period_month' => 9,
                'period_year' => 2026,
                'period_start' => '2026-09-01',
                'period_end' => '2026-09-30',
                'payout_date' => '2026-10-02',
                'total_employees' => $employees->count(),
                'status' => 'APPROVED_BY_HCM',
                'payment_method' => 'Transfer Bank',
                'coa_code' => '5-50110',
                'finance_notes' => null,
                'hcm_signed_by' => $hcmUser?->id,
                'hcm_signed_at' => Carbon::create(2026, 9, 30, 9, 0),
                'created_by' => $hcmUser?->id,
            ]
        );

        $totalSepAmount = 0;
        $totalSepHeld = 0;

        foreach ($employees as $index => $emp) {
            $base = 280000;
            $present = 24;
            $late = ($index === 3) ? 5 : (($index % 3 === 0) ? 1 : 0); // Emp 3 (Rahmat) terlambat 5x -> IS_HOLD!
            $half = ($index === 5) ? 1 : 0;
            $alpha = 0;
            $deduction = ($half > 0) ? 15000 : 0;
            $isHold = ($late >= 4);
            $payable = $isHold ? 0 : ($base - $deduction);

            HcmMealAllowanceItem::updateOrCreate(
                ['batch_id' => $batchSep->id, 'employee_id' => $emp->id],
                [
                    'position' => $emp->position,
                    'department' => $emp->department,
                    'base_allowance' => $base,
                    'present_days' => $present,
                    'late_days' => $late,
                    'half_days' => $half,
                    'alpha_days' => $alpha,
                    'deduction_amount' => $deduction,
                    'payable_amount' => $payable,
                    'is_hold' => $isHold,
                    'bonus_eligible' => !$isHold,
                    'notes' => $isHold ? 'Ditangguhkan karena akumulasi terlambat >= 4 kali bulan ini.' : 'Presensi memenuhi syarat.',
                ]
            );

            if ($isHold) {
                $totalSepHeld += $base;
            } else {
                $totalSepAmount += $payable;
            }
        }
        $batchSep->update(['total_amount' => $totalSepAmount, 'total_held_amount' => $totalSepHeld]);

        // 3. Rekap Reward & Apresiasi Karyawan (HcmEmployeeReward)
        $rewards = [
            [
                'employee_code' => 'EMP-2026-001', // Bambang
                'reward_name' => 'Karyawan Teladan Divisi Produksi Q2 2026',
                'reward_year' => 2026,
                'distribution_status' => 'Sudah Diterima (Serah Terima Langsung)',
                'received_date' => '2026-07-15',
                'document_status' => 'Lengkap (BA Serah Terima)',
                'budget_amount' => 1450000,
                'proof_url' => '/storage/hcm/rewards/ba_bambang.pdf',
                'notes' => 'Emas Antam 1 Gram diserahkan saat Townhall Meeting.',
            ],
            [
                'employee_code' => 'EMP-2026-005', // Dewi Sartika
                'reward_name' => 'Bonus Ketelitian Zero Defect QC Semester 1',
                'reward_year' => 2026,
                'distribution_status' => 'Sudah Ditransfer',
                'received_date' => '2026-07-02',
                'document_status' => 'Lengkap (Slip Transfer)',
                'budget_amount' => 1000000,
                'proof_url' => '/storage/hcm/rewards/transfer_dewi.pdf',
                'notes' => 'Ditransfer bersamaan dengan slip gaji Juli.',
            ],
            [
                'employee_code' => 'EMP-2026-002', // Puji Astuti
                'reward_name' => 'Apresiasi Rekor Pencapaian Target Order 10.000 Pcs',
                'reward_year' => 2026,
                'distribution_status' => 'Sudah Diterima (Serah Terima Langsung)',
                'received_date' => '2026-08-20',
                'document_status' => 'Lengkap',
                'budget_amount' => 2500000,
                'proof_url' => '/storage/hcm/rewards/mesin_cuci_puji.pdf',
                'notes' => 'Mesin Cuci Polytron 2 Tabung untuk apresiasi tim potong bahan.',
            ],
            [
                'employee_code' => 'EMP-2026-010', // Bintang Designer
                'reward_name' => 'Apresiasi Best Creative Design Sportswear',
                'reward_year' => 2026,
                'distribution_status' => 'Belum Diterima',
                'received_date' => null,
                'document_status' => 'Dalam Pengadaan',
                'budget_amount' => 2200000,
                'notes' => 'Penetapan reward Drawing Tablet Wacom untuk fasilitas kerja.',
            ],
            [
                'employee_code' => 'EMP-2026-004', // Rahmat
                'reward_name' => 'Paket Wisata Gathering Akhir Tahun Karimunjawa',
                'reward_year' => 2026,
                'distribution_status' => 'Tertunda / Pending',
                'received_date' => null,
                'document_status' => 'Pending Jadwal Trip',
                'budget_amount' => 3500000,
                'notes' => 'Akan disalurkan pada agenda gathering perusahaan November 2026.',
            ],
        ];

        foreach ($rewards as $r) {
            $emp = HcmEmployee::where('employee_code', $r['employee_code'])->first();
            if (!$emp) {
                continue;
            }

            unset($r['employee_code']);
            $r['employee_id'] = $emp->id;
            $r['position'] = $emp->position;
            $r['created_by'] = $hcmUser?->id;

            HcmEmployeeReward::create($r);
        }
    }
}
