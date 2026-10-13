<?php

namespace App\Http\Controllers\Hcm;

use App\Exports\HcmAttendanceDailyExport;
use App\Exports\HcmAttendanceMonthlyMatrixExport;
use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmAttendance;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmLeaveRequest;
use App\Models\Hcm\HcmMasterOption;
use App\Models\Hcm\HcmOfficeExitPermit;
use App\Models\Hcm\HcmShiftRoster;
use App\Models\Hcm\HcmWorkShift;
use App\Services\HcmAttendanceWaSummaryService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HcmAttendanceController extends Controller
{
    /**
     * Tampilkan Dashboard & Matriks Presensi HCM Terpadu (Kalender Bulanan, Rekap Dossier Multi-Bulan, & Entri Harian).
     */
    public function index(Request $request): Response
    {
        Gate::authorize('hcm.manage-attendance');

        $selectedMonth = $request->query('month', date('Y-m'));
        $selectedYear = (int) $request->query('year', Carbon::parse($selectedMonth . '-01')->format('Y'));
        $selectedDate = $request->query('date', date('Y-m-d'));
        $categoryFilter = $request->query('category', 'all'); // 'all', 'REGULAR', 'INTERN'
        $departmentFilter = $request->query('department', 'all');
        $search = $request->query('search', '');
        $activeTab = $request->query('tab', 'matrix'); // 'matrix', 'dossier', 'daily'

        $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search);

        // 1. Ambil karyawan aktif sesuai kriteria
        $employees = $this->getActiveEmployees($escapedSearch, $categoryFilter, $departmentFilter);

        // 2. Data Matriks Kalender Bulanan (1 - 31 Hari)
        [$daysList, $effectiveWorkDays, $startOfMonth, $endOfMonth] = $this->generateDaysList($selectedMonth);
        $matrixEmployees = $this->getMonthlyMatrixEmployees($employees, $daysList, $startOfMonth, $endOfMonth, $effectiveWorkDays);

        // 3. Data Rekap Multi-Bulan (Dossier Tracker Seluruh Bulan Sepanjang Tahun)
        [$allMonthsInYear, $dossierEmployees] = $this->getDossierData($employees, $selectedYear);

        // 4. Data Entri Harian (Tanggal terpilih)
        [$dailyAttendances, $dailyMatrixData] = $this->getDailyMatrixData($employees, $selectedDate);

        // 5. Data Izin Keluar Kantor (Gate Pass)
        [$exitPermits, $gatepassMetrics] = $this->getGatePassData($selectedDate, $escapedSearch, $departmentFilter);

        // 6. Metrik Global
        $metrics = $this->buildIndexMetrics($employees->count(), $matrixEmployees, $effectiveWorkDays, $dailyAttendances, $gatepassMetrics);

        // 7. Master data dropdowns
        $departments = HcmMasterOption::getOptions('departemen') ?: HcmMasterOption::getOptions('departments');
        if (empty($departments)) {
            $departments = HcmEmployee::where('is_active', true)->whereNotNull('department')->distinct()->pluck('department')->values()->toArray();
        }
        $divisions = HcmMasterOption::getOptions('divisi');
        if (empty($divisions)) {
            $divisions = HcmEmployee::where('is_active', true)->whereNotNull('division')->distinct()->pluck('division')->values()->toArray();
        }
        $attachmentStatuses = HcmMasterOption::getOptions('status_lampiran') ?: ['Terlampir', 'Tidak Terlampir'];

        $activeEmployeesForGatePass = $this->formatGatePassEmployees($employees);
        $activeEmployeesForBulk = $employees->map(fn ($e) => [
            'id' => $e->id,
            'department' => $e->department,
            'division' => $e->division,
        ])->values();
        $shifts = HcmWorkShift::where('is_active', true)->orderBy('shift_code')->get();

        return Inertia::render('Hcm/Attendance/Index', [
            'activeTab' => $activeTab,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'selectedDate' => $selectedDate,
            'daysList' => $daysList,
            'matrixEmployees' => $matrixEmployees,
            'dossierEmployees' => $dossierEmployees,
            'allMonthsInYear' => $allMonthsInYear,
            'dailyMatrixData' => $dailyMatrixData,
            'exitPermits' => $exitPermits,
            'departments' => $departments,
            'divisions' => $divisions,
            'shifts' => $shifts,
            'attachmentStatuses' => $attachmentStatuses,
            'activeEmployeesForGatePass' => $activeEmployeesForGatePass,
            'activeEmployeesForBulk' => $activeEmployeesForBulk,
            'metrics' => $metrics,
            'filters' => [
                'tab' => $activeTab,
                'month' => $selectedMonth,
                'year' => $selectedYear,
                'date' => $selectedDate,
                'category' => $categoryFilter,
                'department' => $departmentFilter,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Hitung agregasi metrik untuk tampilan index dashboard presensi.
     */
    private function buildIndexMetrics(
        int $totalActive,
        Collection $matrixEmployees,
        int $effectiveWorkDays,
        Collection $dailyAttendances,
        array $gatepassMetrics
    ): array {
        $totalHadirBulan = (int) $matrixEmployees->sum(fn ($e) => $e['summary']['hadir']);
        $totalTerlambatBulan = (int) $matrixEmployees->sum(fn ($e) => $e['summary']['terlambat']);
        $totalIzinSakitCutiBulan = (int) $matrixEmployees->sum(fn ($e) => $e['summary']['izin'] + $e['summary']['sakit'] + $e['summary']['cuti']);
        $totalAlphaBulan = (int) $matrixEmployees->sum(fn ($e) => $e['summary']['alpha']);

        return [
            'total_active' => $totalActive,
            'monthly' => [
                'total_hadir' => $totalHadirBulan,
                'total_terlambat' => $totalTerlambatBulan,
                'total_izin_sakit_cuti' => $totalIzinSakitCutiBulan,
                'total_alpha' => $totalAlphaBulan,
                'effective_work_days' => $effectiveWorkDays,
            ],
            'daily' => [
                'hadir' => $dailyAttendances->where('attendance_category', 'Hadir')->count(),
                'terlambat' => $dailyAttendances->where('attendance_category', 'Terlambat')->count(),
                'cuti_izin_sakit' => $dailyAttendances->whereIn('attendance_category', ['Cuti', 'Izin', 'Sakit', 'Dinas Luar'])->count(),
                'alpha' => $dailyAttendances->where('attendance_category', 'Alpha/Mangkir')->count(),
                'belum_terabsen' => max(0, $totalActive - $dailyAttendances->count()),
            ],
            'gatepass' => $gatepassMetrics,
        ];
    }

    /**
     * Format list karyawan aktif untuk dropdown form Gate Pass.
     */
    private function formatGatePassEmployees(Collection $employees): array
    {
        return $employees->map(fn ($e) => [
            'value' => $e->id,
            'label' => "{$e->employee_code} - {$e->name} (" . ($e->division ? "{$e->division} • " : '') . "{$e->department})",
            'name' => $e->name,
            'department' => $e->department,
            'division' => $e->division,
        ])->values()->toArray();
    }

    /**
     * Dapatkan daftar karyawan aktif berdasarkan filter.
     */
    private function getActiveEmployees(string $escapedSearch, string $categoryFilter, string $departmentFilter): Collection
    {
        return HcmEmployee::with('defaultShift')
            ->where('is_active', true)
            ->when($escapedSearch, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                      ->orWhere('nickname', 'like', "%{$term}%")
                      ->orWhere('employee_code', 'like', "%{$term}%");
                });
            })
            ->when($categoryFilter === 'REGULAR', fn ($q) => $q->regular())
            ->when($categoryFilter === 'INTERN', fn ($q) => $q->interns())
            ->when($departmentFilter !== 'all', fn ($q) => $q->where('department', $departmentFilter))
            ->orderBy('department')
            ->orderBy('name')
            ->get();
    }

    /**
     * Susun daftar hari dan hari kerja efektif dalam bulan terpilih.
     */
    private function generateDaysList(string $selectedMonth): array
    {
        $startOfMonth = Carbon::parse($selectedMonth . '-01')->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;

        $daysList = [];
        $effectiveWorkDays = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $currentDate = $startOfMonth->copy()->day($d);
            $isWeekend = $currentDate->isWeekend();
            if (!$isWeekend) {
                $effectiveWorkDays++;
            }
            $daysList[] = [
                'day' => $d,
                'date' => $currentDate->toDateString(),
                'day_name' => $currentDate->isoFormat('ddd'),
                'is_weekend' => $isWeekend,
            ];
        }

        return [$daysList, max(1, $effectiveWorkDays), $startOfMonth, $endOfMonth];
    }

    /**
     * Susun data matriks kalender bulanan (1-31 hari) per karyawan.
     */
    private function getMonthlyMatrixEmployees(Collection $employees, array $daysList, Carbon $startOfMonth, Carbon $endOfMonth, int $effectiveWorkDays): Collection
    {
        $employeeIds = $employees->pluck('id');

        $monthlyAttendances = HcmAttendance::with('shift')
            ->whereBetween('attendance_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->whereIn('employee_id', $employeeIds)
            ->get();

        $monthlyLeaves = HcmLeaveRequest::where('status', 'APPROVED')
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

        $monthlyRosters = HcmShiftRoster::with('shift')
            ->whereBetween('roster_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->whereIn('employee_id', $employeeIds)
            ->get()
            ->groupBy(fn ($item) => $item->employee_id . '_' . (int) $item->roster_date->format('j'));

        $defaultGlobalShift = HcmWorkShift::where('shift_code', 'REG')->first()
            ?: HcmWorkShift::where('is_off_day', false)->first();

        $attKeyed = [];
        foreach ($monthlyAttendances as $att) {
            $dayNum = (int) Carbon::parse($att->attendance_date)->format('j');
            $attKeyed[$att->employee_id][$dayNum] = [
                'id' => $att->id,
                'category' => $att->attendance_category,
                'clock_in' => $att->clock_in ? substr($att->clock_in, 0, 5) : null,
                'clock_out' => $att->clock_out ? substr($att->clock_out, 0, 5) : null,
                'notes' => $att->notes,
                'attachment_url' => $att->attachment_url,
                'shift_id' => $att->shift_id,
                'shift_code' => $att->shift?->shift_code,
                'shift_color' => $att->shift?->color_code,
                'shift_name' => $att->shift?->name,
                'late_minutes' => $att->late_minutes,
            ];
        }

        return $employees->map(function ($emp) use ($daysList, $attKeyed, $monthlyLeaves, $monthlyRosters, $defaultGlobalShift, $effectiveWorkDays) {
            $daysData = [];
            $hadirCount = 0;
            $lateCount = 0;
            $izinCount = 0;
            $sakitCount = 0;
            $cutiCount = 0;
            $alphaCount = 0;

            $empDefaultShift = $emp->defaultShift ?: $defaultGlobalShift;
            $empLeaves = $monthlyLeaves->where('employee_id', $emp->id);

            foreach ($daysList as $dayInfo) {
                $dayNum = $dayInfo['day'];
                $dateStr = $dayInfo['date'];
                $rosterItem = $monthlyRosters->get($emp->id . '_' . $dayNum)?->first();
                $dayShift = $rosterItem?->shift ?: ($dayInfo['is_weekend'] && !$rosterItem ? null : $empDefaultShift);

                if (isset($attKeyed[$emp->id][$dayNum])) {
                    $record = $attKeyed[$emp->id][$dayNum];
                    $record['shift_code'] = $record['shift_code'] ?? ($dayShift?->shift_code ?? 'REG');
                    $record['shift_color'] = $record['shift_color'] ?? ($dayShift?->color_code ?? '#2563eb');
                    $record['shift_name'] = $record['shift_name'] ?? ($dayShift?->name ?? 'Shift Reguler');
                } else {
                    $matchingLeave = $empLeaves->first(function ($l) use ($dateStr) {
                        return $l->start_date <= $dateStr && $l->end_date >= $dateStr;
                    });

                    if ($matchingLeave) {
                        $record = [
                            'id' => null,
                            'category' => $matchingLeave->leave_type,
                            'clock_in' => null,
                            'clock_out' => null,
                            'notes' => "Cuti/Izin: {$matchingLeave->reason}",
                            'attachment_url' => $matchingLeave->attachment_url,
                            'shift_code' => $dayShift?->shift_code ?? 'REG',
                            'shift_color' => $dayShift?->color_code ?? '#2563eb',
                            'shift_name' => $dayShift?->name ?? 'Shift Reguler',
                            'late_minutes' => 0,
                        ];
                    } else {
                        $record = [
                            'id' => null,
                            'category' => null,
                            'clock_in' => null,
                            'clock_out' => null,
                            'notes' => null,
                            'attachment_url' => null,
                            'shift_code' => $dayShift ? $dayShift->shift_code : 'OFF',
                            'shift_color' => $dayShift ? $dayShift->color_code : '#64748b',
                            'shift_name' => $dayShift ? $dayShift->name : 'Libur Rutin',
                            'is_off_day' => $dayShift ? $dayShift->is_off_day : true,
                            'late_minutes' => 0,
                        ];
                    }
                }

                $daysData[$dayNum] = $record;

                if ($record && !empty($record['category'])) {
                    $cat = $record['category'];
                    if ($cat === 'Hadir') $hadirCount++;
                    elseif ($cat === 'Terlambat') $lateCount++;
                    elseif ($cat === 'Izin' || $cat === 'Dinas Luar') $izinCount++;
                    elseif ($cat === 'Sakit') $sakitCount++;
                    elseif (str_contains($cat, 'Cuti')) $cutiCount++;
                    elseif ($cat === 'Alpha/Mangkir') $alphaCount++;
                }
            }

            $totalAttended = $hadirCount + $lateCount;
            $attendanceRate = $effectiveWorkDays > 0 ? round(($totalAttended / $effectiveWorkDays) * 100, 1) : 0;

            return [
                'employee_id' => $emp->id,
                'employee_code' => $emp->employee_code,
                'name' => $emp->name,
                'nickname' => $emp->nickname,
                'employee_category' => $emp->employee_category,
                'department' => $emp->department,
                'division' => $emp->division,
                'position' => $emp->division,
                'job_level' => $emp->job_level,
                'days' => $daysData,
                'summary' => [
                    'hadir' => $hadirCount,
                    'terlambat' => $lateCount,
                    'izin' => $izinCount,
                    'sakit' => $sakitCount,
                    'cuti' => $cutiCount,
                    'alpha' => $alphaCount,
                    'total_absen' => $totalAttended,
                    'rate' => min(100, $attendanceRate),
                ],
            ];
        });
    }

    /**
     * Susun histori presensi multi-bulan dossier sepanjang tahun.
     */
    private function getDossierData(Collection $employees, int $selectedYear): array
    {
        $employeeIds = $employees->pluck('id');
        $yearStart = Carbon::create($selectedYear, 1, 1)->startOfYear();
        $yearEnd = Carbon::create($selectedYear, 12, 31)->endOfYear();

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $monthFormatExpr = $isSqlite ? "strftime('%Y-%m', attendance_date)" : "DATE_FORMAT(attendance_date, '%Y-%m')";

        $annualAttendances = HcmAttendance::select(
            'employee_id',
            DB::raw("{$monthFormatExpr} as year_month"),
            DB::raw("SUM(CASE WHEN attendance_category = 'Hadir' THEN 1 ELSE 0 END) as count_hadir"),
            DB::raw("SUM(CASE WHEN attendance_category = 'Terlambat' THEN 1 ELSE 0 END) as count_terlambat"),
            DB::raw("SUM(CASE WHEN attendance_category IN ('Izin', 'Dinas Luar') THEN 1 ELSE 0 END) as count_izin"),
            DB::raw("SUM(CASE WHEN attendance_category = 'Sakit' THEN 1 ELSE 0 END) as count_sakit"),
            DB::raw("SUM(CASE WHEN attendance_category LIKE '%Cuti%' THEN 1 ELSE 0 END) as count_cuti"),
            DB::raw("SUM(CASE WHEN attendance_category = 'Alpha/Mangkir' THEN 1 ELSE 0 END) as count_alpha"),
            DB::raw("COUNT(*) as count_total")
        )
        ->whereBetween('attendance_date', [$yearStart->toDateString(), $yearEnd->toDateString()])
        ->whereIn('employee_id', $employeeIds)
        ->groupBy('employee_id', DB::raw($monthFormatExpr))
        ->get()
        ->groupBy('employee_id');

        $monthNamesIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $currentYearMonth = date('Y-m');
        $allMonthsInYear = [];
        for ($m = 1; $m <= 12; $m++) {
            $ym = sprintf('%04d-%02d', $selectedYear, $m);
            $mStart = Carbon::parse($ym . '-01');
            $wDays = 0;
            for ($day = 1; $day <= $mStart->daysInMonth; $day++) {
                if (!$mStart->copy()->day($day)->isWeekend()) {
                    $wDays++;
                }
            }
            $allMonthsInYear[] = [
                'year_month' => $ym,
                'month_num' => $m,
                'month_name' => $monthNamesIndo[$m],
                'full_label' => "{$monthNamesIndo[$m]} {$selectedYear}",
                'work_days' => max(1, $wDays),
                'is_current' => $ym === $currentYearMonth,
            ];
        }

        $dossierEmployees = $employees->map(function ($emp) use ($annualAttendances, $allMonthsInYear) {
            $empMonthly = $annualAttendances->get($emp->id, collect())->keyBy('year_month');

            $monthsSummary = [];
            $totHadir = 0;
            $totTerlambat = 0;
            $totIzin = 0;
            $totSakit = 0;
            $totCuti = 0;
            $totAlpha = 0;
            $totDays = 0;

            foreach ($allMonthsInYear as $mInfo) {
                $ym = $mInfo['year_month'];
                $rec = $empMonthly->get($ym);

                $h = (int) ($rec->count_hadir ?? 0);
                $t = (int) ($rec->count_terlambat ?? 0);
                $i = (int) ($rec->count_izin ?? 0);
                $s = (int) ($rec->count_sakit ?? 0);
                $c = (int) ($rec->count_cuti ?? 0);
                $a = (int) ($rec->count_alpha ?? 0);
                $total = (int) ($rec->count_total ?? 0);

                $totHadir += $h;
                $totTerlambat += $t;
                $totIzin += $i;
                $totSakit += $s;
                $totCuti += $c;
                $totAlpha += $a;
                $totDays += $total;

                $rate = $mInfo['work_days'] > 0 ? round((($h + $t) / $mInfo['work_days']) * 100, 1) : 0;

                $monthsSummary[$ym] = [
                    'year_month' => $ym,
                    'month_name' => $mInfo['month_name'],
                    'full_label' => $mInfo['full_label'],
                    'hadir' => $h,
                    'terlambat' => $t,
                    'izin' => $i,
                    'sakit' => $s,
                    'cuti' => $c,
                    'alpha' => $a,
                    'total' => $total,
                    'work_days' => $mInfo['work_days'],
                    'rate' => min(100, $rate),
                    'has_data' => $total > 0,
                ];
            }

            return [
                'employee_id' => $emp->id,
                'employee_code' => $emp->employee_code,
                'name' => $emp->name,
                'nickname' => $emp->nickname,
                'department' => $emp->department,
                'division' => $emp->division,
                'position' => $emp->division,
                'months' => $monthsSummary,
                'annual_totals' => [
                    'hadir' => $totHadir,
                    'terlambat' => $totTerlambat,
                    'izin' => $totIzin,
                    'sakit' => $totSakit,
                    'cuti' => $totCuti,
                    'alpha' => $totAlpha,
                    'total' => $totDays,
                ],
            ];
        });

        return [$allMonthsInYear, $dossierEmployees];
    }

    /**
     * Susun baris entri harian untuk tanggal yang dipilih.
     */
    private function getDailyMatrixData(Collection $employees, string $selectedDate): array
    {
        $employeeIds = $employees->pluck('id');

        $dailyAttendances = HcmAttendance::with('shift')
            ->whereDate('attendance_date', $selectedDate)
            ->whereIn('employee_id', $employeeIds)
            ->get()
            ->keyBy('employee_id');

        $dailyLeaves = HcmLeaveRequest::where('status', 'APPROVED')
            ->whereDate('start_date', '<=', $selectedDate)
            ->whereDate('end_date', '>=', $selectedDate)
            ->whereIn('employee_id', $employeeIds)
            ->get()
            ->keyBy('employee_id');

        $dailyRosters = HcmShiftRoster::with('shift')
            ->whereDate('roster_date', $selectedDate)
            ->whereIn('employee_id', $employeeIds)
            ->get()
            ->keyBy('employee_id');

        $defaultGlobalShift = HcmWorkShift::where('shift_code', 'REG')->first()
            ?: HcmWorkShift::where('is_off_day', false)->first();

        $dailyMatrixData = $employees->map(function ($emp) use ($dailyAttendances, $dailyLeaves, $dailyRosters, $defaultGlobalShift) {
            $att = $dailyAttendances->get($emp->id);
            $leave = $dailyLeaves->get($emp->id);
            $roster = $dailyRosters->get($emp->id);

            $assignedShift = $att?->shift ?: ($roster?->shift ?: ($emp->defaultShift ?: $defaultGlobalShift));
            $shiftStartTime = $assignedShift && $assignedShift->start_time ? substr($assignedShift->start_time, 0, 5) : '08:00';
            $shiftEndTime = $assignedShift && $assignedShift->end_time ? substr($assignedShift->end_time, 0, 5) : '17:00';

            return [
                'employee_id' => $emp->id,
                'employee_code' => $emp->employee_code,
                'name' => $emp->name,
                'nickname' => $emp->nickname,
                'employee_category' => $emp->employee_category,
                'department' => $emp->department,
                'division' => $emp->division,
                'position' => $emp->division,
                'job_level' => $emp->job_level,
                'attendance_id' => $att?->id,
                'attendance_category' => $att?->attendance_category ?? ($leave ? $leave->leave_type : null),
                'shift_id' => $assignedShift?->id,
                'shift_code' => $assignedShift?->shift_code ?? 'REG',
                'shift_name' => $assignedShift?->name ?? 'Shift Reguler',
                'shift_color' => $assignedShift?->color_code ?? '#2563eb',
                'is_off_day' => $assignedShift?->is_off_day ?? false,
                'clock_in' => $att?->clock_in ? substr($att->clock_in, 0, 5) : ($leave ? null : $shiftStartTime),
                'clock_out' => $att?->clock_out ? substr($att->clock_out, 0, 5) : ($leave ? null : $shiftEndTime),
                'late_minutes' => $att?->late_minutes ?? 0,
                'notes' => $att?->notes ?? ($leave ? "Cuti/Izin Disetujui: {$leave->reason}" : ''),
                'attachment_status' => $att?->attachment_status ?? ($leave?->attachment_url ? 'Terlampir' : 'Tidak Terlampir'),
                'attachment_url' => $att?->attachment_url ?? $leave?->attachment_url,
                'has_active_leave' => (bool) $leave,
            ];
        });

        return [$dailyAttendances, $dailyMatrixData];
    }

    /**
     * Dapatkan data perizinan keluar kantor (gate pass) untuk tanggal terpilih.
     */
    private function getGatePassData(string $selectedDate, string $escapedSearch, string $departmentFilter): array
    {
        $exitPermitsQuery = HcmOfficeExitPermit::with(['employee:id,employee_code,name,nickname,department,division,photo_url', 'creator:id,name'])
            ->whereDate('permit_date', $selectedDate)
            ->when($escapedSearch, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('purpose', 'like', "%{$term}%")
                      ->orWhere('notes', 'like', "%{$term}%")
                      ->orWhereHas('employee', function ($eq) use ($term) {
                          $eq->where('name', 'like', "%{$term}%")
                             ->orWhere('nickname', 'like', "%{$term}%")
                             ->orWhere('employee_code', 'like', "%{$term}%");
                      });
                });
            })
            ->when($departmentFilter !== 'all', function ($query) use ($departmentFilter) {
                $query->whereHas('employee', fn ($q) => $q->where('department', $departmentFilter));
            })
            ->orderBy('exit_time', 'desc');

        $exitPermits = $exitPermitsQuery->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'uuid' => $p->uuid,
                'employee_id' => $p->employee_id,
                'employee' => $p->employee ? [
                    'id' => $p->employee->id,
                    'employee_code' => $p->employee->employee_code,
                    'name' => $p->employee->name,
                    'nickname' => $p->employee->nickname,
                    'department' => $p->employee->department,
                    'division' => $p->employee->division,
                    'photo_url' => $p->employee->photo_url,
                ] : null,
                'permit_date' => $p->permit_date->format('Y-m-d'),
                'exit_time' => $p->exit_time,
                'return_time' => $p->return_time,
                'purpose' => $p->purpose,
                'notes' => $p->notes,
                'attachment_status' => $p->attachment_status,
                'attachment_url' => $p->attachment_url,
                'status' => $p->status,
                'duration_text' => $p->duration_text,
                'created_by_name' => $p->creator?->name,
                'created_at' => $p->created_at->format('Y-m-d H:i'),
            ];
        });

        $gatepassMetrics = [
            'total' => $exitPermits->count(),
            'outside' => $exitPermits->where('status', 'Masih di Luar')->count(),
            'returned' => $exitPermits->where('status', 'Kembali')->count(),
            'with_attachment' => $exitPermits->where('attachment_status', 'Terlampir')->count(),
        ];

        return [$exitPermits, $gatepassMetrics];
    }

    /**
     * Simpan pembaruan presensi massal (Bulk Save Matrix).
     */
    public function batchStore(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-attendance');

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'items' => ['required', 'array'],
            'items.*.employee_id' => ['required', 'exists:hcm_employees,id'],
            'items.*.position' => ['nullable', 'string', 'max:100'],
            'items.*.shift_id' => ['nullable', 'exists:hcm_work_shifts,id'],
            'items.*.attendance_category' => [
                'required',
                'string',
                'in:Hadir,Terlambat,Pulang Cepat,Cuti,Izin,Sakit,Dinas Luar,Alpha/Mangkir,Libur/Cuti Bersama',
            ],
            'items.*.clock_in' => ['nullable', 'string', 'max:8'],
            'items.*.clock_out' => ['nullable', 'string', 'max:8'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            'items.*.attachment_status' => ['nullable', 'string', 'in:Terlampir,Tidak Terlampir'],
            'items.*.attachment_url' => ['nullable', 'string', 'max:255'],
        ]);

        $date = $validated['date'];
        $userId = Auth::id();

        DB::transaction(function () use ($date, $validated, $userId) {
            foreach ($validated['items'] as $item) {
                // Formatting time values to H:i:s or null
                $clockIn = !empty($item['clock_in']) ? Carbon::parse($item['clock_in'])->format('H:i:s') : null;
                $clockOut = !empty($item['clock_out']) ? Carbon::parse($item['clock_out'])->format('H:i:s') : null;

                HcmAttendance::updateOrCreate(
                    [
                        'attendance_date' => $date,
                        'employee_id' => $item['employee_id'],
                    ],
                    [
                        'position' => $item['position'] ?? null,
                        'shift_id' => $item['shift_id'] ?? null,
                        'attendance_category' => $item['attendance_category'],
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                        'notes' => $item['notes'] ?? null,
                        'attachment_status' => $item['attachment_status'] ?? 'Tidak Terlampir',
                        'attachment_url' => $item['attachment_url'] ?? null,
                        'created_by' => $userId,
                    ]
                );
            }
        });

        $totalSaved = count($validated['items']);
        return redirect()->back()->with('success', "Presensi massal ({$totalSaved} karyawan) untuk tanggal {$date} berhasil disimpan.");
    }

    /**
     * Tombol Pintas 1-Klik: Set Seluruh Karyawan Hadir Sesuai Jadwal Shift / Default.
     */
    public function setAllPresent(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-attendance');

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'department' => ['nullable', 'string'],
        ]);

        $date = $validated['date'];
        $department = $validated['department'] ?? 'all';
        $userId = Auth::id();

        $affectedCount = 0;

        DB::transaction(function () use ($date, $department, $userId, &$affectedCount) {
            // Dapatkan karyawan aktif
            $employees = HcmEmployee::with('defaultShift')
                ->where('is_active', true)
                ->when($department !== 'all', fn ($q) => $q->where('department', $department))
                ->get();

            // Cek apakah ada cuti/izin yang aktif pada tanggal ini
            $activeLeaveIds = HcmLeaveRequest::where('status', 'APPROVED')
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->pluck('employee_id')
                ->toArray();

            $dailyRosters = HcmShiftRoster::with('shift')
                ->whereDate('roster_date', $date)
                ->whereIn('employee_id', $employees->pluck('id'))
                ->get()
                ->keyBy('employee_id');

            $defaultGlobalShift = HcmWorkShift::where('shift_code', 'REG')->first()
                ?: HcmWorkShift::where('is_off_day', false)->first();

            foreach ($employees as $emp) {
                // Jika sedang cuti/izin, lewati agar tidak tertimpa Hadir
                if (in_array($emp->id, $activeLeaveIds)) {
                    continue;
                }

                $roster = $dailyRosters->get($emp->id);
                $shift = $roster?->shift ?: ($emp->defaultShift ?: $defaultGlobalShift);

                // Jika shift adalah hari libur (OFF), lewati
                if ($shift && $shift->is_off_day) {
                    continue;
                }

                $clockIn = $shift && $shift->start_time ? $shift->start_time : '08:00:00';
                $clockOut = $shift && $shift->end_time ? $shift->end_time : '17:00:00';

                HcmAttendance::updateOrCreate(
                    [
                        'attendance_date' => $date,
                        'employee_id' => $emp->id,
                    ],
                    [
                        'position' => $emp->division ?: $emp->position,
                        'shift_id' => $shift?->id,
                        'attendance_category' => 'Hadir',
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                        'notes' => null,
                        'attachment_status' => 'Tidak Terlampir',
                        'created_by' => $userId,
                    ]
                );

                $affectedCount++;
            }
        });

        return redirect()->back()->with('success', "Sebanyak {$affectedCount} karyawan aktif pada tanggal {$date} berhasil diset HADIR sesuai jadwal shift masing-masing.");
    }

    /**
     * Set Presensi Massal Fleksibel berdasarkan Departemen, Divisi, dan Jam Shift Kerja Resmi.
     */
    public function setBulkAttendance(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-attendance');

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'department' => ['nullable', 'string'],
            'division' => ['nullable', 'string'],
            'position' => ['nullable', 'string'],
            'shift_id' => ['nullable', 'exists:hcm_work_shifts,id'],
            'attendance_category' => ['required', 'string', 'in:Hadir,Terlambat,Pulang Cepat,Izin,Sakit,Alpha/Mangkir,Dinas Luar,Libur/Cuti Bersama'],
            'clock_in' => ['nullable', 'string'],
            'clock_out' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:255'],
            'override_existing' => ['nullable', 'boolean'],
        ]);

        $date = $validated['date'];
        $department = $validated['department'] ?? 'all';
        $division = $validated['division'] ?? ($validated['position'] ?? 'all');
        $shiftId = !empty($validated['shift_id']) ? (int) $validated['shift_id'] : null;
        $category = $validated['attendance_category'];
        $notes = $validated['notes'] ?? null;
        $override = (bool) ($validated['override_existing'] ?? false);
        $userId = Auth::id();

        // 1. Ambil data shift kerja jika dipilih
        $selectedShift = $shiftId ? HcmWorkShift::find($shiftId) : null;

        // 2. Tentukan jam masuk dan keluar secara cerdas berdasarkan shift / input
        if ($selectedShift && $selectedShift->is_off_day) {
            $clockIn = null;
            $clockOut = null;
            if ($category === 'Hadir') {
                $category = 'Libur/Cuti Bersama';
            }
        } else {
            $shiftStartTime = $selectedShift && $selectedShift->start_time ? substr($selectedShift->start_time, 0, 5) : '08:00';
            $shiftEndTime = $selectedShift && $selectedShift->end_time ? substr($selectedShift->end_time, 0, 5) : '17:00';

            $rawClockIn = !empty($validated['clock_in']) ? $validated['clock_in'] : $shiftStartTime;
            $rawClockOut = !empty($validated['clock_out']) ? $validated['clock_out'] : $shiftEndTime;

            $clockIn = in_array($category, ['Hadir', 'Terlambat', 'Pulang Cepat'])
                ? Carbon::parse($rawClockIn)->format('H:i:s')
                : null;
            $clockOut = in_array($category, ['Hadir', 'Terlambat', 'Pulang Cepat'])
                ? Carbon::parse($rawClockOut)->format('H:i:s')
                : null;
        }

        if (empty($notes) && $selectedShift) {
            $notes = "Shift: {$selectedShift->name}";
        }

        $affectedCount = 0;

        DB::transaction(function () use (
            $date, $department, $division, $selectedShift, $category,
            $clockIn, $clockOut, $notes, $override, $userId, &$affectedCount
        ) {
            $employees = HcmEmployee::with('defaultShift')
                ->where('is_active', true)
                ->when($department && $department !== 'all', fn ($q) => $q->where('department', $department))
                ->when($division && $division !== 'all', fn ($q) => $q->where(function ($sub) use ($division) {
                    $sub->where('division', $division)->orWhere('position', $division);
                }))
                ->get();

            // Cek cuti/izin aktif pada tanggal bersangkutan
            $activeLeaveIds = HcmLeaveRequest::where('status', 'APPROVED')
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->pluck('employee_id')
                ->toArray();

            // Record yang sudah terisi presensinya
            $existingAttendanceIds = HcmAttendance::whereDate('attendance_date', $date)
                ->whereIn('employee_id', $employees->pluck('id'))
                ->pluck('employee_id')
                ->toArray();

            foreach ($employees as $emp) {
                // Jangan timpa jika sedang cuti/izin resmi yang telah disetujui
                if (in_array($emp->id, $activeLeaveIds)) {
                    continue;
                }

                // Jika tidak override dan sudah terisi presensinya, lewati
                if (!$override && in_array($emp->id, $existingAttendanceIds)) {
                    continue;
                }

                $assignedShiftId = $selectedShift?->id ?: ($emp->default_shift_id ?? null);

                HcmAttendance::updateOrCreate(
                    [
                        'attendance_date' => $date,
                        'employee_id' => $emp->id,
                    ],
                    [
                        'position' => $emp->division ?: $emp->position,
                        'shift_id' => $assignedShiftId,
                        'attendance_category' => $category,
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                        'notes' => $notes,
                        'attachment_status' => 'Tidak Terlampir',
                        'created_by' => $userId,
                    ]
                );
                $affectedCount++;
            }
        });

        $shiftInfo = $selectedShift ? " [Shift {$selectedShift->name}]" : "";
        $targetDesc = ($department !== 'all' ? "Departemen {$department}" : "Semua Departemen")
            . ($division !== 'all' ? " / Divisi {$division}" : "");

        return redirect()->back()->with(
            'success',
            "Presensi massal ({$affectedCount} karyawan) untuk {$targetDesc}{$shiftInfo} pada {$date} berhasil diset ({$category})."
        );
    }

    /**
     * Tampilan Kalender / Matriks Bulanan Presensi Seluruh Karyawan (1 - 31 Hari).
     */
    public function monthlyCalendar(Request $request): Response
    {
        Gate::authorize('hcm.manage-attendance');

        $selectedMonth = (string) $request->query('month', date('Y-m'));
        $categoryFilter = (string) $request->query('category', 'all');
        $departmentFilter = (string) $request->query('department', 'all');
        $search = (string) $request->query('search', '');
        $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search);

        [$daysList, $effectiveWorkDays, $startOfMonth, $endOfMonth] = $this->generateDaysList($selectedMonth);
        $employees = $this->getActiveEmployees($escapedSearch, $categoryFilter, $departmentFilter);
        $matrixEmployees = $this->getMonthlyMatrixEmployees($employees, $daysList, $startOfMonth, $endOfMonth, $effectiveWorkDays);

        $departments = HcmMasterOption::getOptions('departemen') ?: HcmMasterOption::getOptions('departments');
        $divisions = HcmMasterOption::getOptions('divisi');

        $totalHadir = (int) $matrixEmployees->sum(fn ($e) => $e['summary']['hadir']);
        $totalTerlambat = (int) $matrixEmployees->sum(fn ($e) => $e['summary']['terlambat']);
        $totalIzinSakitCuti = (int) $matrixEmployees->sum(fn ($e) => $e['summary']['izin'] + $e['summary']['sakit'] + $e['summary']['cuti']);
        $totalAlpha = (int) $matrixEmployees->sum(fn ($e) => $e['summary']['alpha']);

        $metrics = [
            'total_karyawan' => $employees->count(),
            'total_hadir' => $totalHadir,
            'total_terlambat' => $totalTerlambat,
            'total_izin_sakit_cuti' => $totalIzinSakitCuti,
            'total_alpha' => $totalAlpha,
            'effective_work_days' => $effectiveWorkDays,
        ];

        return Inertia::render('Hcm/Attendance/MonthlyCalendar', [
            'month' => $selectedMonth,
            'daysList' => $daysList,
            'employees' => $matrixEmployees,
            'departments' => $departments,
            'divisions' => $divisions,
            'positions' => $divisions, // Backward compatibility
            'filters' => [
                'month' => $selectedMonth,
                'category' => $categoryFilter,
                'department' => $departmentFilter,
                'search' => $search,
            ],
            'metrics' => $metrics,
        ]);
    }

    /**
     * Update cepat 1 record presensi per karyawan per tanggal (Quick Edit dari Sel Kalender).
     */
    public function singleUpdate(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-attendance');

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:hcm_employees,id'],
            'date' => ['required', 'date'],
            'shift_id' => ['nullable', 'exists:hcm_work_shifts,id'],
            'attendance_category' => [
                'required',
                'string',
                'in:Hadir,Terlambat,Pulang Cepat,Cuti,Izin,Sakit,Dinas Luar,Alpha/Mangkir,Libur/Cuti Bersama',
            ],
            'clock_in' => ['nullable', 'string'],
            'clock_out' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $emp = HcmEmployee::with('defaultShift')->findOrFail($validated['employee_id']);
        $shiftId = $validated['shift_id'] ?? null;
        if (!$shiftId) {
            $roster = HcmShiftRoster::where('employee_id', $emp->id)->whereDate('roster_date', $validated['date'])->first();
            $shiftId = $roster?->shift_id ?? ($emp->default_shift_id ?? HcmWorkShift::where('shift_code', 'REG')->value('id'));
        }
        $shift = $shiftId ? HcmWorkShift::find($shiftId) : null;
        $defaultIn = $shift && $shift->start_time ? $shift->start_time : '08:00:00';
        $defaultOut = $shift && $shift->end_time ? $shift->end_time : '17:00:00';

        $clockIn = !empty($validated['clock_in']) ? Carbon::parse($validated['clock_in'])->format('H:i:s') : null;
        $clockOut = !empty($validated['clock_out']) ? Carbon::parse($validated['clock_out'])->format('H:i:s') : null;

        HcmAttendance::updateOrCreate(
            [
                'attendance_date' => $validated['date'],
                'employee_id' => $emp->id,
            ],
            [
                'position' => $emp->division ?: $emp->position,
                'shift_id' => $shiftId,
                'attendance_category' => $validated['attendance_category'],
                'clock_in' => in_array($validated['attendance_category'], ['Hadir', 'Terlambat']) ? ($clockIn ?: $defaultIn) : null,
                'clock_out' => in_array($validated['attendance_category'], ['Hadir', 'Terlambat']) ? ($clockOut ?: $defaultOut) : null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]
        );

        return redirect()->back()->with('success', "Presensi {$emp->name} tanggal {$validated['date']} berhasil diperbarui ({$validated['attendance_category']}).");
    }

    /**
     * Unduh Rekap Matriks Presensi Bulanan dalam format Excel (.xlsx).
     */
    public function exportMonthly(Request $request): BinaryFileResponse
    {
        Gate::authorize('hcm.manage-attendance');

        $month = $request->query('month', date('Y-m'));
        $category = $request->query('category', 'all');
        $department = $request->query('department', 'all');
        $userName = Auth::user()?->name;

        $catSlug = match ($category) {
            'REGULAR' => 'Reguler',
            'INTERN' => 'Magang',
            default => 'Semua',
        };

        $filename = 'Rekap_Matriks_Presensi_' . Carbon::parse($month . '-01')->format('Y_m') . "_{$catSlug}.xlsx";

        return Excel::download(
            new HcmAttendanceMonthlyMatrixExport($month, $category, $department, $userName),
            $filename
        );
    }

    /**
     * Unduh Daftar Presensi Harian dalam format Excel (.xlsx).
     */
    public function exportDaily(Request $request): BinaryFileResponse
    {
        Gate::authorize('hcm.manage-attendance');

        $date = $request->query('date', date('Y-m-d'));
        $category = $request->query('category', 'all');
        $department = $request->query('department', 'all');
        $userName = Auth::user()?->name;

        $catSlug = match ($category) {
            'REGULAR' => 'Reguler',
            'INTERN' => 'Magang',
            default => 'Semua',
        };

        $filename = 'Presensi_Harian_' . Carbon::parse($date)->format('Y_m_d') . "_{$catSlug}.xlsx";

        return Excel::download(
            new HcmAttendanceDailyExport($date, $category, $department, $userName),
            $filename
        );
    }

    /**
     * Dapatkan teks ringkasan presensi siap kirim ke WhatsApp.
     */
    public function waSummary(Request $request, HcmAttendanceWaSummaryService $waService): JsonResponse
    {
        Gate::authorize('hcm.manage-attendance');

        $type = $request->query('type', 'daily');
        $category = $request->query('category', 'all');
        $department = $request->query('department', 'all');

        if ($type === 'monthly') {
            $month = $request->query('month', date('Y-m'));
            $data = $waService->generateMonthlySummary($month, $category, $department);
        } else {
            $date = $request->query('date', date('Y-m-d'));
            $data = $waService->generateDailySummary($date, $category, $department);
        }

        return response()->json([
            'success' => true,
            'text' => $data['text'],
            'stats' => $data['stats'],
        ]);
    }
}

