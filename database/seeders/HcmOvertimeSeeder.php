<?php

namespace Database\Seeders;

use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmOvertime;
use App\Models\Hcm\HcmOvertimeBatch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class HcmOvertimeSeeder extends Seeder
{
    /**
     * Run the database seeds for weekly overtime batches and daily overtime entries.
     */
    public function run(): void
    {
        $hcmUser = User::where('email', 'hcm@nisgroup.id')->first() ?? User::first();
        $financeUser = User::where('email', 'aini.finance@nisgroup.id')->first() ?? $hcmUser;

        // Karyawan bagian produksi & kreatif yang sering lembur
        $productionEmployees = HcmEmployee::whereIn('position', [
            'PIC Produksi',
            'Potong Bahan',
            'Jahit',
            'Setting Printing',
            'Press Sublime',
            'Quality Control',
            'Finishing (Packing)',
            'Designer',
        ])->get();

        if ($productionEmployees->isEmpty()) {
            return;
        }

        $batches = [
            // Batch 1: W37 (Paid & Completed)
            [
                'batch_code' => 'OT-2026-W37',
                'period_start' => '2026-09-05',
                'period_end' => '2026-09-11',
                'payout_date' => '2026-09-12',
                'status' => 'PAID_COMPLETED',
                'payment_method' => 'Kas Tunai',
                'coa_code' => '5-50100',
                'finance_notes' => 'Telah dibayarkan tunai melalui kas kecil pabrik pada hari Sabtu.',
                'hcm_signed' => true,
                'finance_signed' => true,
            ],
            // Batch 2: W38 (Paid & Completed)
            [
                'batch_code' => 'OT-2026-W38',
                'period_start' => '2026-09-12',
                'period_end' => '2026-09-18',
                'payout_date' => '2026-09-19',
                'status' => 'PAID_COMPLETED',
                'payment_method' => 'Transfer Bank',
                'coa_code' => '5-50100',
                'finance_notes' => 'Payroll transfer batch lembur ke rekening BRI masing-masing karyawan.',
                'hcm_signed' => true,
                'finance_signed' => true,
            ],
            // Batch 3: W39 (Approved by HCM, waiting finance)
            [
                'batch_code' => 'OT-2026-W39',
                'period_start' => '2026-09-19',
                'period_end' => '2026-09-25',
                'payout_date' => '2026-09-26',
                'status' => 'APPROVED_BY_HCM',
                'payment_method' => 'Transfer Bank',
                'coa_code' => '5-50100',
                'finance_notes' => null,
                'hcm_signed' => true,
                'finance_signed' => false,
            ],
            // Batch 4: W40 (Draft running this week)
            [
                'batch_code' => 'OT-2026-W40',
                'period_start' => '2026-09-26',
                'period_end' => '2026-10-02',
                'payout_date' => '2026-10-03',
                'status' => 'DRAFT',
                'payment_method' => null,
                'coa_code' => null,
                'finance_notes' => null,
                'hcm_signed' => false,
                'finance_signed' => false,
            ],
        ];

        $taskDescriptions = [
            'Kebut pesanan 250 pcs jersey turnamen futsal regional',
            'Potong bahan lot jersey dryfit milano 500 meter',
            'Press sublime kloter malam pesanan instansi dinas',
            'Jahit finishing rib leher dan kerah polo pesanan korporat',
            'Quality Control akhir dan packing ekspres sebelum jadwal pickup kargo',
            'Revisi layout pola jersey printing dan pemisahan file master cetak',
        ];

        foreach ($batches as $bData) {
            $batch = HcmOvertimeBatch::updateOrCreate(
                ['batch_code' => $bData['batch_code']],
                [
                    'period_start' => $bData['period_start'],
                    'period_end' => $bData['period_end'],
                    'payout_date' => $bData['payout_date'],
                    'status' => $bData['status'],
                    'payment_method' => $bData['payment_method'],
                    'coa_code' => $bData['coa_code'],
                    'finance_notes' => $bData['finance_notes'],
                    'hcm_signed_by' => $bData['hcm_signed'] ? $hcmUser?->id : null,
                    'hcm_signed_at' => $bData['hcm_signed'] ? Carbon::parse($bData['period_end'])->addDay() : null,
                    'finance_signed_by' => $bData['finance_signed'] ? $financeUser?->id : null,
                    'finance_signed_at' => $bData['finance_signed'] ? Carbon::parse($bData['payout_date']) : null,
                    'created_by' => $hcmUser?->id,
                ]
            );

            $batchHours = 0;
            $batchAmount = 0;

            $pStart = Carbon::parse($bData['period_start']);
            $pEnd = Carbon::parse($bData['period_end']);

            // Buat 4 - 8 baris lembur per batch
            $otDates = [
                $pStart->copy()->addDays(1)->format('Y-m-d'),
                $pStart->copy()->addDays(2)->format('Y-m-d'),
                $pStart->copy()->addDays(4)->format('Y-m-d'),
                $pStart->copy()->addDays(5)->format('Y-m-d'),
            ];

            foreach ($otDates as $i => $otDate) {
                if (Carbon::parse($otDate)->gt(Carbon::today())) {
                    continue;
                }

                $selectedEmps = $productionEmployees->take(3);
                foreach ($selectedEmps as $emp) {
                    $hours = ($i % 2 === 0) ? 2.0 : 2.5;
                    $hourlyRate = 15000;
                    $firstHalf = 7500;
                    $total = $hours * $hourlyRate;

                    HcmOvertime::create([
                        'batch_id' => $batch->id,
                        'overtime_date' => $otDate,
                        'employee_id' => $emp->id,
                        'position' => $emp->position,
                        'day_type' => 'Lembur Hari Kerja',
                        'duration_hours' => $hours,
                        'hourly_rate' => $hourlyRate,
                        'first_half_rate' => $firstHalf,
                        'total_amount' => $total,
                        'task_description' => $taskDescriptions[($emp->id + $i) % count($taskDescriptions)],
                        'created_by' => $hcmUser?->id,
                    ]);

                    $batchHours += $hours;
                    $batchAmount += $total;
                }
            }

            $batch->update([
                'total_hours' => $batchHours,
                'total_amount' => $batchAmount,
            ]);
        }
    }
}
