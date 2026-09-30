<?php

namespace Database\Seeders;

use App\Models\Hcm\HcmAttendance;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmLeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class HcmAttendanceAndLeaveSeeder extends Seeder
{
    /**
     * Run the database seeds for daily attendances and leave requests.
     */
    public function run(): void
    {
        $adminUser = User::where('email', 'hcm@nisgroup.id')->first() ?? User::first();
        $employees = HcmEmployee::where('is_active', true)->get();

        if ($employees->isEmpty()) {
            return;
        }

        // 1. Generate Daily Attendance Logs for September 2026 (1 Sep - 30 Sep 2026)
        $startDate = Carbon::create(2026, 9, 1);
        $endDate = Carbon::create(2026, 9, 30); // local time is 30 Sep 2026

        // Specific attendance profiles to create natural variability
        // Employee 0: Bambang (Very punctual)
        // Employee 1: Puji (Punctual, once sick)
        // Employee 2: Danang (Hard worker, sometimes late 2x)
        // Employee 3: Rahmat (Frequently late, triggers meal allowance hold: >= 4x late!)
        // Employee 4: Dewi (Punctual supervisor)

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            // Minggu libur
            if ($date->isSunday()) {
                continue;
            }

            // Maulid Nabi Muhammad SAW (16 September 2026 - Libur Nasional)
            if ($date->format('Y-m-d') === '2026-09-16') {
                foreach ($employees as $emp) {
                    HcmAttendance::updateOrCreate(
                        ['attendance_date' => $date->format('Y-m-d'), 'employee_id' => $emp->id],
                        [
                            'position' => $emp->position,
                            'attendance_category' => 'Libur/Cuti Bersama',
                            'clock_in' => null,
                            'clock_out' => null,
                            'notes' => 'Hari Libur Nasional Maulid Nabi',
                            'attachment_status' => 'Tidak Terlampir',
                            'created_by' => $adminUser?->id,
                        ]
                    );
                }
                continue;
            }

            $dayNum = $date->day;

            foreach ($employees as $index => $emp) {
                $category = 'Hadir';
                $clockIn = '07:' . str_pad((string) (45 + (($emp->id + $dayNum) % 12)), 2, '0', STR_PAD_LEFT) . ':00';
                $clockOut = '16:' . str_pad((string) (30 + (($emp->id + $dayNum) % 30)), 2, '0', STR_PAD_LEFT) . ':00';
                $notes = null;

                // Hari ini (30 September 2026)
                if ($date->format('Y-m-d') === '2026-09-30') {
                    if ($index === 2) {
                        // Danang izin sakit hari ini
                        $category = 'Sakit';
                        $clockIn = null;
                        $clockOut = null;
                        $notes = 'Sakit demam & flu (Surat Dokter Klinik Sehat)';
                    } elseif ($index === 3) {
                        // Rahmat terlambat hari ini
                        $category = 'Terlambat';
                        $clockIn = '08:24:00';
                        $clockOut = null; // belum pulang
                        $notes = 'Ban motor bocor di Jl. Jaksa Agung';
                    } elseif ($index === 5) {
                        // Agus pulang cepat
                        $category = 'Pulang Cepat';
                        $clockIn = '07:55:00';
                        $clockOut = '12:15:00';
                        $notes = 'Izin keluarga mendesak jam 12:00';
                    } else {
                        // Yang lain hadir normal
                        $category = 'Hadir';
                        $clockIn = '07:50:00';
                        $clockOut = null; // hari ini jam kerja masih berlangsung
                        $notes = 'Presensi pagi normal';
                    }
                } else {
                    // Hari-hari sebelumnya di bulan September
                    // Rahmat Hidayat (index 3) kita buat sering terlambat (>= 4x) untuk memicu audit uang makan hold
                    if ($index === 3 && in_array($dayNum, [4, 11, 18, 25])) {
                        $category = 'Terlambat';
                        $clockIn = '08:20:00';
                        $notes = 'Terlambat masuk kerja';
                    }
                    // Puji (index 1) izin sakit tgl 8 Sep
                    elseif ($index === 1 && $dayNum === 8) {
                        $category = 'Sakit';
                        $clockIn = null;
                        $clockOut = null;
                        $notes = 'Surat Keterangan Sakit Puskesmas Lamongan';
                    }
                    // Danang (index 2) izin keperluan keluarga tgl 15 Sep
                    elseif ($index === 2 && $dayNum === 15) {
                        $category = 'Izin';
                        $clockIn = null;
                        $clockOut = null;
                        $notes = 'Izin menghadiri acara hajatan adik';
                    }
                    // Bintang Designer (index 9) dinas luar tgl 22 Sep
                    elseif ($index === 9 && $dayNum === 22) {
                        $category = 'Dinas Luar';
                        $clockIn = '08:00:00';
                        $clockOut = '17:00:00';
                        $notes = 'Meeting koordinasi katalog jersey dengan vendor kain di Surabaya';
                    }
                }

                HcmAttendance::updateOrCreate(
                    [
                        'attendance_date' => $date->format('Y-m-d'),
                        'employee_id' => $emp->id,
                    ],
                    [
                        'position' => $emp->position,
                        'attendance_category' => $category,
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                        'notes' => $notes,
                        'attachment_status' => in_array($category, ['Sakit', 'Dinas Luar']) ? 'Terlampir' : 'Tidak Terlampir',
                        'attachment_url' => in_array($category, ['Sakit', 'Dinas Luar']) ? '/storage/hcm/attachments/surat_keterangan.pdf' : null,
                        'created_by' => $adminUser?->id,
                    ]
                );
            }
        }

        // 2. Pengajuan Cuti & Perizinan (Leave Requests)
        $leaveRequests = [
            [
                'employee_code' => 'EMP-2026-003', // Danang
                'leave_type' => 'Sakit',
                'start_date' => '2026-09-30',
                'end_date' => '2026-10-01',
                'total_days' => 2,
                'reason' => 'Sakit demam berdarah ringan butuh istirahat rawat jalan',
                'status' => 'APPROVED',
                'reviewed_by' => $adminUser?->id,
                'reviewed_at' => Carbon::create(2026, 9, 30, 8, 30),
            ],
            [
                'employee_code' => 'EMP-2026-005', // Dewi Sartika
                'leave_type' => 'Cuti Tahunan',
                'start_date' => '2026-10-08',
                'end_date' => '2026-10-10',
                'total_days' => 3,
                'reason' => 'Acara keluarga aqiqah anak di kampung halaman',
                'status' => 'APPROVED',
                'reviewed_by' => $adminUser?->id,
                'reviewed_at' => Carbon::create(2026, 9, 28, 14, 00),
            ],
            [
                'employee_code' => 'EMP-2026-008', // Faisal Akbar
                'leave_type' => 'Cuti Tahunan',
                'start_date' => '2026-10-15',
                'end_date' => '2026-10-17',
                'total_days' => 3,
                'reason' => 'Keperluan pengurusan berkas wisuda lanjutan',
                'status' => 'PENDING_REVIEW', // Menunggu review
            ],
            [
                'employee_code' => 'EMP-2026-010', // Bintang
                'leave_type' => 'Izin',
                'start_date' => '2026-10-03',
                'end_date' => '2026-10-03',
                'total_days' => 1,
                'reason' => 'Perpanjangan SIM di Satlantas Polres Lamongan',
                'status' => 'PENDING_REVIEW',
            ],
            [
                'employee_code' => 'EMP-2026-006', // Agus Setiawan
                'leave_type' => 'Izin',
                'start_date' => '2026-09-20',
                'end_date' => '2026-09-22',
                'total_days' => 3,
                'reason' => 'Izin liburan mendadak tanpa konfirmasi H-3',
                'status' => 'REJECTED',
                'reviewed_by' => $adminUser?->id,
                'reviewed_at' => Carbon::create(2026, 9, 19, 10, 00),
                'rejection_reason' => 'Pengajuan mendadak di masa deadline pesanan tinggi (Peak Season)',
            ],
        ];

        foreach ($leaveRequests as $lr) {
            $emp = HcmEmployee::where('employee_code', $lr['employee_code'])->first();
            if (!$emp) {
                continue;
            }

            unset($lr['employee_code']);
            $lr['employee_id'] = $emp->id;
            $lr['created_by'] = $adminUser?->id;

            HcmLeaveRequest::create($lr);
        }
    }
}
