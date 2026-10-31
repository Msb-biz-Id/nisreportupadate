<?php

namespace App\Services;

use App\Models\Hcm\HcmAttendance;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmLeaveRequest;
use Carbon\Carbon;

class HcmAttendanceWaSummaryService
{
    /**
     * Generate daily WhatsApp attendance summary text.
     */
    public function generateDailySummary(string $date, string $category = 'all', string $department = 'all'): array
    {
        $carbonDate = Carbon::parse($date);

        $empQuery = HcmEmployee::where('is_active', true)
            ->when($category === 'REGULAR', fn ($q) => $q->regular())
            ->when($category === 'INTERN', fn ($q) => $q->interns())
            ->when($department !== 'all', fn ($q) => $q->where('department', $department))
            ->orderBy('department')
            ->orderBy('name');

        $employees = $empQuery->get();
        $employeeIds = $employees->pluck('id');
        $totalPersonel = $employees->count();

        $attendances = HcmAttendance::where('attendance_date', $date)
            ->whereIn('employee_id', $employeeIds)
            ->get()
            ->keyBy('employee_id');

        $leaves = HcmLeaveRequest::where('status', 'APPROVED')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->whereIn('employee_id', $employeeIds)
            ->get()
            ->keyBy('employee_id');

        $cntHadir = 0;
        $cntTelat = 0;
        $cntIzin = 0;
        $cntSakit = 0;
        $cntCuti = 0;
        $cntAlpha = 0;
        $cntBelum = 0;

        $issues = [];

        foreach ($employees as $emp) {
            $att = $attendances->get($emp->id);
            $leave = $leaves->get($emp->id);

            $roleTag = $emp->employee_category === 'INTERN' ? ' [Magang]' : '';
            $deptTag = $emp->department ? " ({$emp->department}{$roleTag})" : "{$roleTag}";

            if ($att) {
                $rawCat = strtoupper(trim((string)$att->attendance_category));
                $catNorm = match (true) {
                    in_array($rawCat, ['PRESENT', 'HADIR']) => 'PRESENT',
                    in_array($rawCat, ['LATE', 'TERLAMBAT']) => 'LATE',
                    in_array($rawCat, ['PERMIT', 'IZIN', 'DINAS LUAR']) => 'PERMIT',
                    in_array($rawCat, ['SICK', 'SAKIT']) => 'SICK',
                    in_array($rawCat, ['LEAVE', 'CUTI']) || str_contains($rawCat, 'CUTI') => 'LEAVE',
                    in_array($rawCat, ['ALPHA', 'ALPHA/MANGKIR']) => 'ALPHA',
                    default => $rawCat,
                };

                if ($catNorm === 'PRESENT') {
                    $cntHadir++;
                } elseif ($catNorm === 'LATE') {
                    $cntTelat++;
                    $lateMin = (int) ($att->late_minutes ?? 0);
                    if ($lateMin === 0 && $att->clock_in) {
                        $cIn = Carbon::parse($att->clock_in);
                        $std = Carbon::parse('08:00:00');
                        if ($cIn->gt($std)) {
                            $lateMin = $std->diffInMinutes($cIn);
                        }
                    }
                    $issues[] = [
                        'type' => 'LATE',
                        'order' => 1,
                        'text' => "{$emp->name}{$deptTag} - Terlambat" . ($lateMin > 0 ? " ({$lateMin} mnt)" : ""),
                    ];
                } elseif ($catNorm === 'PERMIT') {
                    $cntIzin++;
                    $note = $att->notes ? " ({$att->notes})" : "";
                    $issues[] = [
                        'type' => 'PERMIT',
                        'order' => 3,
                        'text' => "{$emp->name}{$deptTag} - Izin{$note}",
                    ];
                } elseif ($catNorm === 'SICK') {
                    $cntSakit++;
                    $note = $att->notes ? " ({$att->notes})" : "";
                    $issues[] = [
                        'type' => 'SICK',
                        'order' => 2,
                        'text' => "{$emp->name}{$deptTag} - Sakit{$note}",
                    ];
                } elseif ($catNorm === 'LEAVE') {
                    $cntCuti++;
                    $note = $att->notes ? " ({$att->notes})" : "";
                    $issues[] = [
                        'type' => 'LEAVE',
                        'order' => 4,
                        'text' => "{$emp->name}{$deptTag} - Cuti{$note}",
                    ];
                } elseif ($catNorm === 'ALPHA') {
                    $cntAlpha++;
                    $issues[] = [
                        'type' => 'ALPHA',
                        'order' => 5,
                        'text' => "{$emp->name}{$deptTag} - Alpha (Tanpa Keterangan)",
                    ];
                }
            } elseif ($leave) {
                if ($leave->leave_type === 'SAKIT') {
                    $cntSakit++;
                    $reason = $leave->reason ? " ({$leave->reason})" : "";
                    $issues[] = [
                        'type' => 'SICK',
                        'order' => 2,
                        'text' => "{$emp->name}{$deptTag} - Sakit{$reason}",
                    ];
                } elseif ($leave->leave_type === 'IZIN') {
                    $cntIzin++;
                    $reason = $leave->reason ? " ({$leave->reason})" : "";
                    $issues[] = [
                        'type' => 'PERMIT',
                        'order' => 3,
                        'text' => "{$emp->name}{$deptTag} - Izin{$reason}",
                    ];
                } else {
                    $cntCuti++;
                    $reason = $leave->reason ? " ({$leave->reason})" : "";
                    $issues[] = [
                        'type' => 'LEAVE',
                        'order' => 4,
                        'text' => "{$emp->name}{$deptTag} - Cuti Disetujui{$reason}",
                    ];
                }
            } else {
                $cntBelum++;
            }
        }

        $recordedCount = $cntHadir + $cntTelat + $cntIzin + $cntSakit + $cntCuti + $cntAlpha;
        $presentTotal = $cntHadir + $cntTelat;
        $attendanceRate = $totalPersonel > 0 ? round(($presentTotal / $totalPersonel) * 100, 1) : 0;

        $catLabel = match ($category) {
            'REGULAR' => 'Karyawan Reguler (Managerial/Kontrak/Borongan)',
            'INTERN' => 'Peserta Magang SMK',
            default => 'Karyawan Reguler & Peserta Magang',
        };

        $deptLabel = $department !== 'all' ? $department : 'Seluruh Departemen';

        // Sort issues by order
        usort($issues, fn($a, $b) => $a['order'] <=> $b['order']);

        $lines = [];
        $lines[] = "*REKAP PRESENSI HARIAN - NISGROUP*";
        $lines[] = "Hari, Tanggal : " . $carbonDate->translatedFormat('l, d F Y');
        $lines[] = "Kategori      : {$catLabel}";
        if ($department !== 'all') {
            $lines[] = "Departemen    : {$deptLabel}";
        }
        $lines[] = "Waktu Laporan : " . now()->translatedFormat('H:i') . " WIB";
        $lines[] = "";
        $lines[] = "*RINGKASAN KEHADIRAN:*";
        $lines[] = "• Total Personel   : {$totalPersonel} Orang";
        $lines[] = "• Hadir Tepat Waktu: {$cntHadir} Orang";
        $lines[] = "• Terlambat        : {$cntTelat} Orang";
        $lines[] = "• Izin             : {$cntIzin} Orang";
        $lines[] = "• Sakit            : {$cntSakit} Orang";
        if ($cntCuti > 0) {
            $lines[] = "• Cuti             : {$cntCuti} Orang";
        }
        $lines[] = "• Tanpa Keterangan : {$cntAlpha} Orang";
        if ($cntBelum > 0) {
            $lines[] = "• Belum Diabsen    : {$cntBelum} Orang";
        }
        $lines[] = "• Tingkat Presensi : {$attendanceRate}%";
        $lines[] = "";

        $lines[] = "*RINCIAN KETERLAMBATAN & KETIDAKHADIRAN:*";
        if (count($issues) === 0) {
            $lines[] = "• Seluruh personel hadir lengkap dan tepat waktu.";
        } else {
            $num = 1;
            foreach ($issues as $item) {
                $lines[] = "{$num}. {$item['text']}";
                $num++;
            }
        }

        $lines[] = "";
        $lines[] = "_Catatan: Data tersinkronisasi otomatis dari Portal HCM NISGroup._";

        $fullText = implode("\n", $lines);

        return [
            'text' => $fullText,
            'stats' => [
                'total_personel' => $totalPersonel,
                'hadir' => $cntHadir,
                'terlambat' => $cntTelat,
                'izin' => $cntIzin,
                'sakit' => $cntSakit,
                'cuti' => $cntCuti,
                'alpha' => $cntAlpha,
                'belum_absen' => $cntBelum,
                'rate' => $attendanceRate,
            ],
        ];
    }

    /**
     * Generate monthly WhatsApp attendance summary text.
     */
    public function generateMonthlySummary(string $month, string $category = 'all', string $department = 'all'): array
    {
        $carbonMonth = Carbon::parse($month . '-01');
        $startOfMonth = $carbonMonth->copy()->startOfMonth();
        $endOfMonth = $carbonMonth->copy()->endOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;

        $empQuery = HcmEmployee::where('is_active', true)
            ->when($category === 'REGULAR', fn ($q) => $q->regular())
            ->when($category === 'INTERN', fn ($q) => $q->interns())
            ->when($department !== 'all', fn ($q) => $q->where('department', $department))
            ->orderBy('department')
            ->orderBy('name');

        $employees = $empQuery->get();
        $employeeIds = $employees->pluck('id');
        $totalEmployees = $employees->count();

        // Effective work days (exclude weekend)
        $effectiveWorkDays = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            if (!$startOfMonth->copy()->day($d)->isWeekend()) {
                $effectiveWorkDays++;
            }
        }
        $effectiveWorkDays = max(1, $effectiveWorkDays);

        $attendances = HcmAttendance::whereBetween('attendance_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->whereIn('employee_id', $employeeIds)
            ->get();

        $leaves = HcmLeaveRequest::where('status', 'APPROVED')
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('start_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                    ->orWhereBetween('end_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                    ->orWhere(function ($sub) use ($startOfMonth, $endOfMonth) {
                        $sub->where('start_date', '<=', $startOfMonth->toDateString())
                            ->where('end_date', '>=', $endOfMonth->toDateString());
                    });
            })
            ->whereIn('employee_id', $employeeIds)
            ->get();

        $attKeyed = [];
        foreach ($attendances as $att) {
            $dayNum = (int) Carbon::parse($att->attendance_date)->format('j');
            $attKeyed[$att->employee_id][$dayNum] = [
                'category' => $att->attendance_category,
                'overtime' => (float) ($att->overtime_hours ?? 0),
            ];
        }

        $leaveKeyed = [];
        foreach ($leaves as $lv) {
            $lvStart = Carbon::parse($lv->start_date);
            $lvEnd = Carbon::parse($lv->end_date);
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $checkDate = $startOfMonth->copy()->day($d);
                if ($checkDate->betweenIncluded($lvStart, $lvEnd)) {
                    $leaveCode = match ($lv->leave_type) {
                        'SAKIT' => 'S',
                        'IZIN' => 'I',
                        default => 'C',
                    };
                    $leaveKeyed[$lv->employee_id][$d] = $leaveCode;
                }
            }
        }

        $totalLateCount = 0;
        $totalOvertimeHours = 0.0;
        $totalIzinDays = 0;
        $totalSakitDays = 0;
        $totalAlphaDays = 0;
        $employeeRates = [];
        $perfectAttendees = [];

        foreach ($employees as $emp) {
            $cntPresent = 0;
            $cntLate = 0;

            for ($d = 1; $d <= $daysInMonth; $d++) {
                if (isset($attKeyed[$emp->id][$d])) {
                    $cat = $attKeyed[$emp->id][$d]['category'];
                    $ot = $attKeyed[$emp->id][$d]['overtime'];
                    $totalOvertimeHours += $ot;

                    if ($cat === 'PRESENT') {
                        $cntPresent++;
                    } elseif ($cat === 'LATE') {
                        $cntLate++;
                        $totalLateCount++;
                    } elseif ($cat === 'PERMIT') {
                        $totalIzinDays++;
                    } elseif ($cat === 'SICK') {
                        $totalSakitDays++;
                    } elseif ($cat === 'ALPHA') {
                        $totalAlphaDays++;
                    }
                } elseif (isset($leaveKeyed[$emp->id][$d])) {
                    $code = $leaveKeyed[$emp->id][$d];
                    if ($code === 'S') $totalSakitDays++;
                    elseif ($code === 'I') $totalIzinDays++;
                }
            }

            $actualDays = $cntPresent + $cntLate;
            $rate = round(($actualDays / $effectiveWorkDays) * 100, 1);
            if ($rate > 100) $rate = 100.0;
            $employeeRates[] = $rate;

            if ($cntPresent >= $effectiveWorkDays && $cntLate === 0) {
                $deptTag = $emp->department ? " ({$emp->department})" : "";
                $roleTag = $emp->employee_category === 'INTERN' ? " [Magang]" : "";
                $perfectAttendees[] = "{$emp->name}{$deptTag}{$roleTag}";
            }
        }

        $avgRate = count($employeeRates) > 0 ? round(array_sum($employeeRates) / count($employeeRates), 1) : 0;

        $catLabel = match ($category) {
            'REGULAR' => 'Karyawan Reguler',
            'INTERN' => 'Peserta Magang SMK',
            default => 'Karyawan Reguler & Peserta Magang',
        };

        $lines = [];
        $lines[] = "*REKAPITULASI PRESENSI BULANAN - NISGROUP*";
        $lines[] = "Periode       : " . $carbonMonth->translatedFormat('F Y');
        $lines[] = "Kategori      : {$catLabel}";
        if ($department !== 'all') {
            $lines[] = "Departemen    : {$department}";
        }
        $lines[] = "Hari Efektif  : {$effectiveWorkDays} Hari Kerja";
        $lines[] = "";
        $lines[] = "*STATISTIK KINERJA KEHADIRAN:*";
        $lines[] = "• Total Personel Aktif : {$totalEmployees} Orang";
        $lines[] = "• Rata-rata Kehadiran : {$avgRate}%";
        $lines[] = "• Akumulasi Lembur    : " . round($totalOvertimeHours, 1) . " Jam";
        $lines[] = "• Total Keterlambatan : {$totalLateCount} Kali";
        $lines[] = "• Total Izin / Sakit  : " . ($totalIzinDays + $totalSakitDays) . " Hari";
        $lines[] = "• Total Alpha         : {$totalAlphaDays} Hari";
        $lines[] = "";

        $lines[] = "*DISIPLIN TERBAIK (KEHADIRAN 100% TANPA TELAT):*";
        if (count($perfectAttendees) === 0) {
            $lines[] = "• Tidak ada personel dengan kehadiran 100% murni bulan ini.";
        } else {
            $slice = array_slice($perfectAttendees, 0, 10);
            foreach ($slice as $attendee) {
                $lines[] = "• {$attendee}";
            }
            if (count($perfectAttendees) > 10) {
                $remaining = count($perfectAttendees) - 10;
                $lines[] = "• ... dan {$remaining} personel lainnya.";
            }
        }

        $lines[] = "";
        $lines[] = "_Rekap lengkap matriks Excel dapat diunduh langsung di Portal HCM NISGroup._";

        $fullText = implode("\n", $lines);

        return [
            'text' => $fullText,
            'stats' => [
                'total_personel' => $totalEmployees,
                'avg_rate' => $avgRate,
                'total_lembur' => round($totalOvertimeHours, 1),
                'total_telat' => $totalLateCount,
                'total_izin_sakit' => $totalIzinDays + $totalSakitDays,
                'total_alpha' => $totalAlphaDays,
                'perfect_count' => count($perfectAttendees),
            ],
        ];
    }
}
