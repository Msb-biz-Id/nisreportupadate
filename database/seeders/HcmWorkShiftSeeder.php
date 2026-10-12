<?php

namespace Database\Seeders;

use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmShiftRoster;
use App\Models\Hcm\HcmWorkShift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HcmWorkShiftSeeder extends Seeder
{
    /**
     * Seed master shift kerja default NIS Group dan penugasan roster karyawan.
     */
    public function run(): void
    {
        $adminUser = User::where('email', 'hcm@nisgroup.id')->first() ?? User::first();

        $shifts = [
            [
                'name' => 'Shift Reguler (Office)',
                'shift_code' => 'REG',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'late_tolerance_minutes' => 15,
                'color_code' => '#2563eb', // Blue
                'is_overnight' => false,
                'is_off_day' => false,
                'description' => 'Shift standar operasional kantor & staf non-shift (08:00 - 17:00)',
                'is_active' => true,
            ],
            [
                'name' => 'Shift 1 (Pagi Produksi)',
                'shift_code' => 'S1',
                'start_time' => '07:00:00',
                'end_time' => '15:00:00',
                'late_tolerance_minutes' => 15,
                'color_code' => '#10b981', // Emerald green
                'is_overnight' => false,
                'is_off_day' => false,
                'description' => 'Shift pagi lini operasional / pabrik konveksi (07:00 - 15:00)',
                'is_active' => true,
            ],
            [
                'name' => 'Shift 2 (Siang Produksi)',
                'shift_code' => 'S2',
                'start_time' => '15:00:00',
                'end_time' => '23:00:00',
                'late_tolerance_minutes' => 15,
                'color_code' => '#f59e0b', // Amber/Orange
                'is_overnight' => false,
                'is_off_day' => false,
                'description' => 'Shift siang lini operasional / produksi (15:00 - 23:00)',
                'is_active' => true,
            ],
            [
                'name' => 'Shift 3 (Malam / Lembur)',
                'shift_code' => 'S3',
                'start_time' => '23:00:00',
                'end_time' => '07:00:00',
                'late_tolerance_minutes' => 15,
                'color_code' => '#8b5cf6', // Violet
                'is_overnight' => true,
                'is_off_day' => false,
                'description' => 'Shift malam lintas hari (23:00 - 07:00)',
                'is_active' => true,
            ],
            [
                'name' => 'Libur Mingguan / Off Roster',
                'shift_code' => 'OFF',
                'start_time' => null,
                'end_time' => null,
                'late_tolerance_minutes' => 0,
                'color_code' => '#64748b', // Slate gray
                'is_overnight' => false,
                'is_off_day' => true,
                'description' => 'Jadwal hari libur / istirahat berkala karyawan roster',
                'is_active' => true,
            ],
        ];

        $shiftModels = [];
        foreach ($shifts as $data) {
            $shiftModels[$data['shift_code']] = HcmWorkShift::firstOrCreate(
                ['shift_code' => $data['shift_code']],
                array_merge($data, ['uuid' => (string) Str::uuid()])
            );
        }

        // 2. Hubungkan default shift ke data karyawan aktif
        $regShift = $shiftModels['REG'] ?? null;
        $s1Shift  = $shiftModels['S1'] ?? null;
        $s2Shift  = $shiftModels['S2'] ?? null;
        $offShift = $shiftModels['OFF'] ?? null;

        $employees = HcmEmployee::where('is_active', true)->get();
        foreach ($employees as $emp) {
            if ($emp->department === 'Produksi') {
                $emp->update(['default_shift_id' => $s1Shift?->id]);
            } else {
                $emp->update(['default_shift_id' => $regShift?->id]);
            }
        }

        // 3. Generate Jadwal Roster Shift untuk Bulan Oktober 2026 (1 Okt - 31 Okt 2026)
        if ($employees->isNotEmpty() && $s1Shift && $s2Shift && $regShift && $offShift) {
            $startDate = Carbon::create(2026, 10, 1);
            $endDate   = Carbon::create(2026, 10, 31);

            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $dateStr = $date->format('Y-m-d');
                $isSunday = $date->isSunday();

                foreach ($employees as $idx => $emp) {
                    if ($emp->department === 'Produksi') {
                        // Karyawan produksi bergantian Shift 1 dan Shift 2, hari Minggu OFF
                        if ($isSunday) {
                            $assignedShift = $offShift;
                            $notes = 'Libur akhir pekan';
                        } else {
                            $assignedShift = ($idx % 2 === 0) ? $s1Shift : $s2Shift;
                            $notes = 'Jadwal reguler lini produksi';
                        }
                    } else {
                        // Staff kantor & manajemen: Senin-Sabtu REG, Minggu OFF
                        if ($isSunday) {
                            $assignedShift = $offShift;
                            $notes = 'Hari libur mingguan';
                        } else {
                            $assignedShift = $regShift;
                            $notes = 'Jam operasional kantor';
                        }
                    }

                    HcmShiftRoster::firstOrCreate(
                        [
                            'employee_id' => $emp->id,
                            'roster_date' => $dateStr,
                        ],
                        [
                            'uuid' => (string) Str::uuid(),
                            'shift_id' => $assignedShift->id,
                            'notes' => $notes,
                            'created_by' => $adminUser?->id,
                        ]
                    );
                }
            }
        }
    }
}
