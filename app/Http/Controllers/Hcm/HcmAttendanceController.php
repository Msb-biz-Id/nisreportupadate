<?php

namespace App\Http\Controllers\Hcm;

use App\Exports\HcmAttendanceDailyExport;
use App\Exports\HcmAttendanceMonthlyMatrixExport;
use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmAttendance;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmLeaveRequest;
use App\Models\Hcm\HcmMasterOption;
use App\Services\HcmAttendanceWaSummaryService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        // 1. Ambil seluruh karyawan aktif
        $employeesQuery = HcmEmployee::where('is_active', true)
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
            ->orderBy('name');

        $employees = $employeesQuery->get();
        $employeeIds = $employees->pluck('id');

        // 2. Data untuk Matriks Kalender Bulanan (1 - 31 Hari)
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
        $effectiveWorkDays = max(1, $effectiveWorkDays);

        // Ambil data presensi bulan terpilih
        $monthlyAttendances = HcmAttendance::whereBetween('attendance_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->whereIn('employee_id', $employeeIds)
            ->get();

        // Ambil cuti/izin bulan terpilih
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

        // Index presensi per employee_id dan tanggal day
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
            ];
        }

        // Susun matriks kalender bulanan per karyawan
        $matrixEmployees = $employees->map(function ($emp) use ($daysList, $attKeyed, $monthlyLeaves, $effectiveWorkDays) {
            $daysData = [];
            $hadirCount = 0;
            $lateCount = 0;
            $izinCount = 0;
            $sakitCount = 0;
            $cutiCount = 0;
            $alphaCount = 0;

            $empLeaves = $monthlyLeaves->where('employee_id', $emp->id);

            foreach ($daysList as $dayInfo) {
                $dayNum = $dayInfo['day'];
                $dateStr = $dayInfo['date'];

                if (isset($attKeyed[$emp->id][$dayNum])) {
                    $record = $attKeyed[$emp->id][$dayNum];
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
                        ];
                    } else {
                        $record = null;
                    }
                }

                $daysData[$dayNum] = $record;

                if ($record) {
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
            $attendanceRate = round(($totalAttended / $effectiveWorkDays) * 100, 1);

            return [
                'employee_id' => $emp->id,
                'employee_code' => $emp->employee_code,
                'name' => $emp->name,
                'nickname' => $emp->nickname,
                'department' => $emp->department,
                'position' => $emp->position,
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

        // 3. Data Rekap Multi-Bulan (Dossier Tracker Seluruh Bulan Sepanjang Tahun)
        // Menampilkan histori presensi multi-bulan sinkron dengan format Buku Profil Dossier PDF
        $yearStart = Carbon::create($selectedYear, 1, 1)->startOfYear();
        $yearEnd = Carbon::create($selectedYear, 12, 31)->endOfYear();

        // Ambil agregasi per bulan dalam tahun tersebut
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

        // Susun daftar 12 bulan nama Indonesia
        $monthNamesIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $currentYearMonth = date('Y-m');
        $allMonthsInYear = [];
        for ($m = 1; $m <= 12; $m++) {
            $ym = sprintf('%04d-%02d', $selectedYear, $m);
            // Hitung hari kerja standar bulan tersebut
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

        // Susun data rekap dossier per karyawan
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
                'position' => $emp->position,
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

        // 4. Data untuk Tab Entri Harian (Tanggal terpilih)
        $dailyAttendances = HcmAttendance::whereDate('attendance_date', $selectedDate)
            ->whereIn('employee_id', $employeeIds)
            ->get()
            ->keyBy('employee_id');

        $dailyLeaves = HcmLeaveRequest::where('status', 'APPROVED')
            ->whereDate('start_date', '<=', $selectedDate)
            ->whereDate('end_date', '>=', $selectedDate)
            ->whereIn('employee_id', $employeeIds)
            ->get()
            ->keyBy('employee_id');

        $dailyMatrixData = $employees->map(function ($emp) use ($dailyAttendances, $dailyLeaves) {
            $att = $dailyAttendances->get($emp->id);
            $leave = $dailyLeaves->get($emp->id);

            return [
                'employee_id' => $emp->id,
                'employee_code' => $emp->employee_code,
                'name' => $emp->name,
                'nickname' => $emp->nickname,
                'employee_category' => $emp->employee_category,
                'department' => $emp->department,
                'position' => $emp->position,
                'job_level' => $emp->job_level,
                'attendance_id' => $att?->id,
                'attendance_category' => $att?->attendance_category ?? ($leave ? $leave->leave_type : null),
                'clock_in' => $att?->clock_in ? substr($att->clock_in, 0, 5) : ($leave ? null : '08:00'),
                'clock_out' => $att?->clock_out ? substr($att->clock_out, 0, 5) : ($leave ? null : '17:00'),
                'notes' => $att?->notes ?? ($leave ? "Cuti/Izin Disetujui: {$leave->reason}" : ''),
                'attachment_status' => $att?->attachment_status ?? ($leave?->attachment_url ? 'Terlampir' : 'Tidak Terlampir'),
                'attachment_url' => $att?->attachment_url ?? $leave?->attachment_url,
                'has_active_leave' => (bool) $leave,
            ];
        });

        // 5. Metrik Global
        $totalHadirBulan = $matrixEmployees->sum(fn ($e) => $e['summary']['hadir']);
        $totalTerlambatBulan = $matrixEmployees->sum(fn ($e) => $e['summary']['terlambat']);
        $totalIzinSakitCutiBulan = $matrixEmployees->sum(fn ($e) => $e['summary']['izin'] + $e['summary']['sakit'] + $e['summary']['cuti']);
        $totalAlphaBulan = $matrixEmployees->sum(fn ($e) => $e['summary']['alpha']);

        $metrics = [
            'total_active' => $employees->count(),
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
                'belum_terabsen' => max(0, $employees->count() - $dailyAttendances->count()),
            ],
        ];

        // 6. Master data dropdowns
        $departments = HcmMasterOption::getOptions('departments');
        $positions = HcmMasterOption::getOptions('positions');

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
            'departments' => $departments,
            'positions' => $positions,
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
     * Tombol Pintas 1-Klik: Set Seluruh Karyawan Hadir (08:00 - 17:00).
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

        DB::transaction(function () use ($date, $department, $userId) {
            // Dapatkan karyawan aktif
            $employees = HcmEmployee::where('is_active', true)
                ->when($department !== 'all', fn ($q) => $q->where('department', $department))
                ->get();

            // Cek apakah ada cuti/izin yang aktif pada tanggal ini
            $activeLeaveIds = HcmLeaveRequest::where('status', 'APPROVED')
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->pluck('employee_id')
                ->toArray();

            foreach ($employees as $emp) {
                // Jika sedang cuti/izin, lewati agar tidak tertimpa Hadir
                if (in_array($emp->id, $activeLeaveIds)) {
                    continue;
                }

                HcmAttendance::updateOrCreate(
                    [
                        'attendance_date' => $date,
                        'employee_id' => $emp->id,
                    ],
                    [
                        'position' => $emp->position,
                        'attendance_category' => 'Hadir',
                        'clock_in' => '08:00:00',
                        'clock_out' => '17:00:00',
                        'notes' => null,
                        'attachment_status' => 'Tidak Terlampir',
                        'created_by' => $userId,
                    ]
                );
            }
        });

        return redirect()->back()->with('success', "Seluruh karyawan aktif pada tanggal {$date} berhasil diset HADIR (08:00 - 17:00).");
    }

    /**
     * Set Presensi Massal Fleksibel berdasarkan Divisi, Posisi/Jabatan, dan Shift Kerja.
     */
    public function setBulkAttendance(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-attendance');

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'department' => ['nullable', 'string'],
            'position' => ['nullable', 'string'],
            'attendance_category' => ['required', 'string', 'in:Hadir,Terlambat,Izin,Sakit,Alpha/Mangkir,Dinas Luar'],
            'clock_in' => ['nullable', 'string'],
            'clock_out' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:255'],
            'override_existing' => ['nullable', 'boolean'],
        ]);

        $date = $validated['date'];
        $department = $validated['department'] ?? 'all';
        $position = $validated['position'] ?? 'all';
        $category = $validated['attendance_category'];
        $clockIn = !empty($validated['clock_in']) ? Carbon::parse($validated['clock_in'])->format('H:i:s') : ($category === 'Hadir' ? '08:00:00' : null);
        $clockOut = !empty($validated['clock_out']) ? Carbon::parse($validated['clock_out'])->format('H:i:s') : ($category === 'Hadir' ? '17:00:00' : null);
        $notes = $validated['notes'] ?? null;
        $override = (bool) ($validated['override_existing'] ?? false);
        $userId = Auth::id();

        $affectedCount = 0;

        DB::transaction(function () use ($date, $department, $position, $category, $clockIn, $clockOut, $notes, $override, $userId, &$affectedCount) {
            $employees = HcmEmployee::where('is_active', true)
                ->when($department && $department !== 'all', fn ($q) => $q->where('department', $department))
                ->when($position && $position !== 'all', fn ($q) => $q->where('position', $position))
                ->get();

            // Cek cuti/izin aktif
            $activeLeaveIds = HcmLeaveRequest::where('status', 'APPROVED')
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->pluck('employee_id')
                ->toArray();

            // Record yang sudah ada
            $existingAttendanceIds = HcmAttendance::whereDate('attendance_date', $date)
                ->whereIn('employee_id', $employees->pluck('id'))
                ->pluck('employee_id')
                ->toArray();

            foreach ($employees as $emp) {
                // Jangan timpa jika sedang cuti/izin yang telah disetujui resmi
                if (in_array($emp->id, $activeLeaveIds)) {
                    continue;
                }

                // Jika tidak override dan sudah terisi presensinya, lewati
                if (!$override && in_array($emp->id, $existingAttendanceIds)) {
                    continue;
                }

                HcmAttendance::updateOrCreate(
                    [
                        'attendance_date' => $date,
                        'employee_id' => $emp->id,
                    ],
                    [
                        'position' => $emp->position,
                        'attendance_category' => $category,
                        'clock_in' => in_array($category, ['Hadir', 'Terlambat']) ? $clockIn : null,
                        'clock_out' => in_array($category, ['Hadir', 'Terlambat']) ? $clockOut : null,
                        'notes' => $notes,
                        'attachment_status' => 'Tidak Terlampir',
                        'created_by' => $userId,
                    ]
                );
                $affectedCount++;
            }
        });

        $targetDesc = ($department !== 'all' ? "Divisi {$department}" : "Semua Divisi") . ($position !== 'all' ? " / {$position}" : "");
        return redirect()->back()->with('success', "Presensi massal ({$affectedCount} karyawan) untuk {$targetDesc} pada {$date} berhasil diset ({$category}).");
    }

    /**
     * Tampilan Kalender / Matriks Bulanan Presensi Seluruh Karyawan (1 - 31 Hari).
     */
    public function monthlyCalendar(Request $request): Response
    {
        Gate::authorize('hcm.manage-attendance');

        $selectedMonth = $request->query('month', date('Y-m'));
        $categoryFilter = $request->query('category', 'all');
        $departmentFilter = $request->query('department', 'all');
        $search = $request->query('search', '');

        $startOfMonth = Carbon::parse($selectedMonth . '-01')->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;

        // Susun daftar hari dalam bulan
        $daysList = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $currentDate = $startOfMonth->copy()->day($d);
            $daysList[] = [
                'day' => $d,
                'date' => $currentDate->toDateString(),
                'day_name' => $currentDate->isoFormat('ddd'),
                'is_weekend' => $currentDate->isWeekend(),
            ];
        }

        // Ambil karyawan aktif
        $employees = HcmEmployee::where('is_active', true)
            ->when($categoryFilter === 'REGULAR', fn ($q) => $q->regular())
            ->when($categoryFilter === 'INTERN', fn ($q) => $q->interns())
            ->when($departmentFilter !== 'all', fn ($q) => $q->where('department', $departmentFilter))
            ->when($search, fn ($q) => $q->where(fn ($sub) => $sub->where('name', 'like', "%{$search}%")->orWhere('employee_code', 'like', "%{$search}%")))
            ->orderBy('department')
            ->orderBy('name')
            ->get();

        $employeeIds = $employees->pluck('id');

        // Ambil seluruh record presensi bulan ini
        $attendances = HcmAttendance::whereBetween('attendance_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->whereIn('employee_id', $employeeIds)
            ->get();

        // Ambil cuti/izin yang disetujui dalam bulan ini
        $approvedLeaves = HcmLeaveRequest::where('status', 'APPROVED')
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

        // Index presensi per employee_id dan tanggal
        $attKeyed = [];
        foreach ($attendances as $att) {
            $dayNum = (int) Carbon::parse($att->attendance_date)->format('j');
            $attKeyed[$att->employee_id][$dayNum] = [
                'id' => $att->id,
                'category' => $att->attendance_category,
                'clock_in' => $att->clock_in ? substr($att->clock_in, 0, 5) : null,
                'clock_out' => $att->clock_out ? substr($att->clock_out, 0, 5) : null,
                'notes' => $att->notes,
            ];
        }

        // Hitung hari kerja efektif bulan ini (tidak termasuk akhir pekan)
        $effectiveWorkDays = 0;
        foreach ($daysList as $d) {
            if (!$d['is_weekend']) {
                $effectiveWorkDays++;
            }
        }
        $effectiveWorkDays = max(1, $effectiveWorkDays);

        // Agregasi bulanan per karyawan
        $matrixEmployees = $employees->map(function ($emp) use ($daysList, $attKeyed, $approvedLeaves, $effectiveWorkDays) {
            $daysData = [];
            $hadirCount = 0;
            $lateCount = 0;
            $izinCount = 0;
            $sakitCount = 0;
            $cutiCount = 0;
            $alphaCount = 0;

            // Cek leaves untuk karyawan ini
            $empLeaves = $approvedLeaves->where('employee_id', $emp->id);

            foreach ($daysList as $dayInfo) {
                $dayNum = $dayInfo['day'];
                $dateStr = $dayInfo['date'];

                if (isset($attKeyed[$emp->id][$dayNum])) {
                    $record = $attKeyed[$emp->id][$dayNum];
                } else {
                    // Cek apakah ada cuti/izin aktif di tanggal ini
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
                        ];
                    } else {
                        $record = null;
                    }
                }

                $daysData[$dayNum] = $record;

                if ($record) {
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
            $attendanceRate = round(($totalAttended / $effectiveWorkDays) * 100, 1);

            return [
                'employee_id' => $emp->id,
                'employee_code' => $emp->employee_code,
                'name' => $emp->name,
                'employee_category' => $emp->employee_category,
                'department' => $emp->department,
                'position' => $emp->position,
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

        // Master departments
        $departments = HcmMasterOption::select('hcm_master_options.name')
            ->join('hcm_master_categories', 'hcm_master_options.category_id', '=', 'hcm_master_categories.id')
            ->where('hcm_master_categories.code', 'department')
            ->where('hcm_master_options.is_active', true)
            ->orderBy('hcm_master_options.order_index')
            ->pluck('name');

        // Master positions
        $positions = HcmMasterOption::select('hcm_master_options.name')
            ->join('hcm_master_categories', 'hcm_master_options.category_id', '=', 'hcm_master_categories.id')
            ->where('hcm_master_categories.code', 'position')
            ->where('hcm_master_options.is_active', true)
            ->orderBy('hcm_master_options.order_index')
            ->pluck('name');

        // Metrik global bulan terpilih
        $totalHadir = $matrixEmployees->sum(fn ($e) => $e['summary']['hadir']);
        $totalTerlambat = $matrixEmployees->sum(fn ($e) => $e['summary']['terlambat']);
        $totalIzinSakitCuti = $matrixEmployees->sum(fn ($e) => $e['summary']['izin'] + $e['summary']['sakit'] + $e['summary']['cuti']);
        $totalAlpha = $matrixEmployees->sum(fn ($e) => $e['summary']['alpha']);

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
            'positions' => $positions,
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
            'attendance_category' => [
                'required',
                'string',
                'in:Hadir,Terlambat,Pulang Cepat,Cuti,Izin,Sakit,Dinas Luar,Alpha/Mangkir,Libur/Cuti Bersama',
            ],
            'clock_in' => ['nullable', 'string'],
            'clock_out' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $emp = HcmEmployee::findOrFail($validated['employee_id']);
        $clockIn = !empty($validated['clock_in']) ? Carbon::parse($validated['clock_in'])->format('H:i:s') : null;
        $clockOut = !empty($validated['clock_out']) ? Carbon::parse($validated['clock_out'])->format('H:i:s') : null;

        HcmAttendance::updateOrCreate(
            [
                'attendance_date' => $validated['date'],
                'employee_id' => $emp->id,
            ],
            [
                'position' => $emp->position,
                'attendance_category' => $validated['attendance_category'],
                'clock_in' => in_array($validated['attendance_category'], ['Hadir', 'Terlambat']) ? ($clockIn ?: '08:00:00') : null,
                'clock_out' => in_array($validated['attendance_category'], ['Hadir', 'Terlambat']) ? ($clockOut ?: '17:00:00') : null,
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

