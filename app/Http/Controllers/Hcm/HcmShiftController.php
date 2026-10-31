<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmMasterOption;
use App\Models\Hcm\HcmShiftRoster;
use App\Models\Hcm\HcmWorkShift;
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

class HcmShiftController extends Controller
{
    /**
     * Tampilkan Halaman Manajemen Shift Kerja & Roster Jadwal Karyawan.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('hcm.manage-attendance');

        $selectedMonth = $request->query('month', date('Y-m'));
        $departmentFilter = $request->query('department', 'all');
        $divisionFilter = $request->query('division', 'all');
        $search = $request->query('search', '');
        $activeTab = $request->query('tab', 'roster'); // 'roster', 'shifts'

        $startOfMonth = Carbon::parse($selectedMonth . '-01')->startOfMonth();
        $endOfMonth = (clone $startOfMonth)->endOfMonth();

        // 1. Ambil Seluruh Master Shift
        $shifts = HcmWorkShift::orderBy('is_active', 'desc')
            ->orderBy('is_off_day', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        // 2. Karyawan Aktif
        $employeesQuery = HcmEmployee::with('defaultShift')
            ->where('is_active', true);

        if ($search) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search);
            $employeesQuery->where(function ($q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                  ->orWhere('nickname', 'like', "%{$escaped}%")
                  ->orWhere('employee_code', 'like', "%{$escaped}%");
            });
        }

        if ($departmentFilter !== 'all') {
            $employeesQuery->where('department', $departmentFilter);
        }

        if ($divisionFilter !== 'all') {
            $employeesQuery->where(function ($q) use ($divisionFilter) {
                $q->where('division', $divisionFilter)
                  ->orWhere('position', $divisionFilter);
            });
        }

        $employees = $employeesQuery->orderBy('department')
            ->orderBy('name')
            ->get();

        // 3. Ambil data Roster untuk bulan terpilih
        $employeeIds = $employees->pluck('id')->toArray();
        $rosters = HcmShiftRoster::with('shift')
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('roster_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->get()
            ->groupBy(fn ($item) => $item->employee_id . '_' . $item->roster_date->format('Y-m-d'));

        // 4. Buat list hari dalam bulan
        $daysList = [];
        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);
        foreach ($period as $dt) {
            $daysList[] = [
                'date' => $dt->format('Y-m-d'),
                'day' => (int) $dt->format('j'),
                'day_name' => $dt->locale('id')->isoFormat('dd'),
                'is_sunday' => $dt->isSunday(),
                'is_saturday' => $dt->isSaturday(),
                'is_weekend' => $dt->isWeekend(),
            ];
        }

        // Shift Default Reguler sebagai fallback global
        $defaultGlobalShift = $shifts->firstWhere('shift_code', 'REG') ?: $shifts->firstWhere('is_off_day', false);

        // 5. Susun Matriks Roster Karyawan
        $rosterMatrix = $employees->map(function ($emp) use ($daysList, $rosters, $defaultGlobalShift) {
            $empDefaultShift = $emp->defaultShift ?: $defaultGlobalShift;
            $rosterDays = [];
            $shiftCountSummary = [];

            foreach ($daysList as $day) {
                $key = $emp->id . '_' . $day['date'];
                $assignedRoster = $rosters->get($key)?->first();

                if ($assignedRoster && $assignedRoster->shift) {
                    $resolvedShift = $assignedRoster->shift;
                    $isExplicitRoster = true;
                    $rosterNotes = $assignedRoster->notes;
                } else {
                    // Fallback ke default shift karyawan
                    $resolvedShift = $day['is_sunday']
                        ? null // Hari Minggu tanpa roster diindikasikan libur/off jika tidak ada roster
                        : $empDefaultShift;
                    $isExplicitRoster = false;
                    $rosterNotes = null;
                }

                $shiftCode = $resolvedShift ? $resolvedShift->shift_code : 'OFF';
                $shiftCountSummary[$shiftCode] = ($shiftCountSummary[$shiftCode] ?? 0) + 1;

                $rosterDays[$day['day']] = [
                    'date' => $day['date'],
                    'shift_id' => $resolvedShift?->id,
                    'shift_code' => $shiftCode,
                    'shift_name' => $resolvedShift?->name ?? 'Libur Rutin',
                    'color_code' => $resolvedShift?->color_code ?? '#64748b',
                    'start_time' => $resolvedShift?->start_time ? substr($resolvedShift->start_time, 0, 5) : null,
                    'end_time' => $resolvedShift?->end_time ? substr($resolvedShift->end_time, 0, 5) : null,
                    'is_off_day' => $resolvedShift ? $resolvedShift->is_off_day : true,
                    'is_explicit' => $isExplicitRoster,
                    'notes' => $rosterNotes,
                ];
            }

            return [
                'employee_id' => $emp->id,
                'employee_code' => $emp->employee_code,
                'name' => $emp->name,
                'nickname' => $emp->nickname,
                'department' => $emp->department,
                'division' => $emp->division ?: $emp->position,
                'default_shift_id' => $emp->default_shift_id,
                'default_shift_name' => $emp->defaultShift?->name ?? ($defaultGlobalShift?->name ?? 'Shift Reguler'),
                'default_shift_code' => $emp->defaultShift?->shift_code ?? ($defaultGlobalShift?->shift_code ?? 'REG'),
                'photo_url' => $emp->photo_url,
                'days' => $rosterDays,
                'summary' => $shiftCountSummary,
            ];
        });

        // 6. Master data dropdown
        $departments = HcmMasterOption::getOptions('departemen') ?: HcmMasterOption::getOptions('departments');
        $divisions = HcmMasterOption::getOptions('divisi');

        // 7. Metrik Ringkasan
        $todayStr = date('Y-m-d');
        $todayRosterCounts = HcmShiftRoster::whereDate('roster_date', $todayStr)
            ->join('hcm_work_shifts', 'hcm_shift_rosters.shift_id', '=', 'hcm_work_shifts.id')
            ->select('hcm_work_shifts.shift_code', 'hcm_work_shifts.name', 'hcm_work_shifts.color_code', DB::raw('count(*) as total'))
            ->groupBy('hcm_work_shifts.shift_code', 'hcm_work_shifts.name', 'hcm_work_shifts.color_code')
            ->get();

        $metrics = [
            'total_shifts' => $shifts->count(),
            'active_shifts' => $shifts->where('is_active', true)->count(),
            'total_employees' => $employees->count(),
            'rostered_this_month' => $rosters->count(),
            'today_distribution' => $todayRosterCounts,
        ];

        return Inertia::render('Hcm/Shifts/Index', [
            'activeTab' => $activeTab,
            'selectedMonth' => $selectedMonth,
            'shifts' => $shifts,
            'rosterMatrix' => $rosterMatrix,
            'daysList' => $daysList,
            'departments' => $departments,
            'divisions' => $divisions,
            'employees' => $employees->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'employee_code' => $e->employee_code,
                'department' => $e->department,
                'division' => $e->division ?: $e->position,
                'default_shift_id' => $e->default_shift_id,
            ]),
            'metrics' => $metrics,
            'filters' => [
                'month' => $selectedMonth,
                'department' => $departmentFilter,
                'division' => $divisionFilter,
                'search' => $search,
                'tab' => $activeTab,
            ],
        ]);
    }

    /**
     * Tambah Master Shift Kerja Baru.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-attendance');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'shift_code' => ['required', 'string', 'max:20', 'unique:hcm_work_shifts,shift_code'],
            'start_time' => ['nullable', 'string'],
            'end_time' => ['nullable', 'string'],
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'color_code' => ['required', 'string', 'max:30'],
            'is_overnight' => ['nullable', 'boolean'],
            'is_off_day' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $isOffDay = (bool) ($validated['is_off_day'] ?? false);

        HcmWorkShift::create([
            'name' => $validated['name'],
            'shift_code' => strtoupper(trim($validated['shift_code'])),
            'start_time' => $isOffDay ? null : (!empty($validated['start_time']) ? Carbon::parse($validated['start_time'])->format('H:i:s') : null),
            'end_time' => $isOffDay ? null : (!empty($validated['end_time']) ? Carbon::parse($validated['end_time'])->format('H:i:s') : null),
            'late_tolerance_minutes' => $validated['late_tolerance_minutes'],
            'color_code' => $validated['color_code'],
            'is_overnight' => (bool) ($validated['is_overnight'] ?? false),
            'is_off_day' => $isOffDay,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', "Master shift '{$validated['name']}' ({$validated['shift_code']}) berhasil ditambahkan.");
    }

    /**
     * Perbarui Master Shift Kerja.
     */
    public function update(Request $request, HcmWorkShift $shift): RedirectResponse
    {
        Gate::authorize('hcm.manage-attendance');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'shift_code' => ['required', 'string', 'max:20', 'unique:hcm_work_shifts,shift_code,' . $shift->id],
            'start_time' => ['nullable', 'string'],
            'end_time' => ['nullable', 'string'],
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'color_code' => ['required', 'string', 'max:30'],
            'is_overnight' => ['nullable', 'boolean'],
            'is_off_day' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $isOffDay = (bool) ($validated['is_off_day'] ?? false);

        $shift->update([
            'name' => $validated['name'],
            'shift_code' => strtoupper(trim($validated['shift_code'])),
            'start_time' => $isOffDay ? null : (!empty($validated['start_time']) ? Carbon::parse($validated['start_time'])->format('H:i:s') : null),
            'end_time' => $isOffDay ? null : (!empty($validated['end_time']) ? Carbon::parse($validated['end_time'])->format('H:i:s') : null),
            'late_tolerance_minutes' => $validated['late_tolerance_minutes'],
            'color_code' => $validated['color_code'],
            'is_overnight' => (bool) ($validated['is_overnight'] ?? false),
            'is_off_day' => $isOffDay,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->back()->with('success', "Shift '{$shift->name}' berhasil diperbarui.");
    }

    /**
     * Hapus Master Shift Kerja.
     */
    public function destroy(HcmWorkShift $shift): RedirectResponse
    {
        Gate::authorize('hcm.manage-attendance');

        $rosterCount = $shift->rosters()->count();
        $attendanceCount = $shift->attendances()->count();

        if ($rosterCount > 0 || $attendanceCount > 0) {
            return redirect()->back()->with('error', "Shift '{$shift->name}' tidak dapat dihapus karena sudah memiliki riwayat {$rosterCount} jadwal roster dan {$attendanceCount} data presensi. Anda dapat menonaktifkannya.");
        }

        $shiftName = $shift->name;
        $shift->delete();

        return redirect()->back()->with('success', "Shift '{$shiftName}' berhasil dihapus.");
    }

    /**
     * Toggle status aktif / nonaktif master shift.
     */
    public function toggleActive(HcmWorkShift $shift): RedirectResponse
    {
        Gate::authorize('hcm.manage-attendance');

        $shift->is_active = !$shift->is_active;
        $shift->save();

        $statusText = $shift->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Shift '{$shift->name}' berhasil {$statusText}.");
    }

    /**
     * Penugasan Roster Massal (Bulk Assign Roster).
     */
    public function assignBulkRoster(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-attendance');

        $validated = $request->validate([
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:hcm_employees,id'],
            'department' => ['nullable', 'string'],
            'division' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'shift_id' => ['required', 'exists:hcm_work_shifts,id'],
            'days_of_week' => ['nullable', 'array'], // [0=Minggu, 1=Senin, ..., 6=Sabtu]
            'override_existing' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $shiftId = (int) $validated['shift_id'];
        $daysOfWeek = $validated['days_of_week'] ?? null; // jika null, assign semua hari
        $override = (bool) ($validated['override_existing'] ?? true);
        $notes = $validated['notes'] ?? null;
        $userId = Auth::id();

        // Tentukan daftar karyawan yang akan dijadwalkan
        $employeeIds = $validated['employee_ids'] ?? [];
        if (empty($employeeIds)) {
            $empQuery = HcmEmployee::where('is_active', true);
            if (!empty($validated['department']) && $validated['department'] !== 'all') {
                $empQuery->where('department', $validated['department']);
            }
            if (!empty($validated['division']) && $validated['division'] !== 'all') {
                $empQuery->where(function ($q) use ($validated) {
                    $q->where('division', $validated['division'])
                      ->orWhere('position', $validated['division']);
                });
            }
            $employeeIds = $empQuery->pluck('id')->toArray();
        }

        if (empty($employeeIds)) {
            return redirect()->back()->with('error', 'Tidak ada karyawan yang memenuhi kriteria penugasan roster.');
        }

        $period = CarbonPeriod::create($startDate, $endDate);
        $totalAssigned = 0;

        DB::transaction(function () use ($period, $employeeIds, $shiftId, $daysOfWeek, $override, $notes, $userId, &$totalAssigned) {
            foreach ($period as $dt) {
                // Cek filter hari dalam minggu jika ada
                if ($daysOfWeek !== null && !empty($daysOfWeek)) {
                    $dayOfWeek = $dt->dayOfWeek; // 0=Sunday, 6=Saturday
                    if (!in_array($dayOfWeek, $daysOfWeek) && !in_array((string) $dayOfWeek, $daysOfWeek)) {
                        continue;
                    }
                }

                $dateStr = $dt->format('Y-m-d');

                foreach ($employeeIds as $empId) {
                    if (!$override) {
                        $exists = HcmShiftRoster::where('employee_id', $empId)
                            ->whereDate('roster_date', $dateStr)
                            ->exists();
                        if ($exists) {
                            continue;
                        }
                    }

                    HcmShiftRoster::updateOrCreate(
                        [
                            'employee_id' => $empId,
                            'roster_date' => $dateStr,
                        ],
                        [
                            'shift_id' => $shiftId,
                            'notes' => $notes,
                            'created_by' => $userId,
                        ]
                    );

                    $totalAssigned++;
                }
            }
        });

        $shift = HcmWorkShift::find($shiftId);
        return redirect()->back()->with('success', "Berhasil menugaskan {$totalAssigned} jadwal roster untuk shift '{$shift?->name}' ({$startDate->format('d M')} - {$endDate->format('d M Y')}).");
    }

    /**
     * Penugasan Roster Satuan / Single Cell Click.
     */
    public function assignSingleRoster(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-attendance');

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:hcm_employees,id'],
            'roster_date' => ['required', 'date'],
            'shift_id' => ['nullable', 'exists:hcm_work_shifts,id'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $employeeId = (int) $validated['employee_id'];
        $rosterDate = $validated['roster_date'];
        $shiftId = $validated['shift_id'] ?? null;
        $notes = $validated['notes'] ?? null;

        if (empty($shiftId)) {
            // Hapus assignment roster khusus (kembali ke default)
            HcmShiftRoster::where('employee_id', $employeeId)
                ->whereDate('roster_date', $rosterDate)
                ->delete();

            return redirect()->back()->with('success', 'Jadwal shift tanggal tersebut dikembalikan ke shift default karyawan.');
        }

        HcmShiftRoster::updateOrCreate(
            [
                'employee_id' => $employeeId,
                'roster_date' => $rosterDate,
            ],
            [
                'shift_id' => $shiftId,
                'notes' => $notes,
                'created_by' => Auth::id(),
            ]
        );

        $shift = HcmWorkShift::find($shiftId);
        return redirect()->back()->with('success', "Shift untuk tanggal {$rosterDate} diubah menjadi '{$shift?->name}'.");
    }

    /**
     * Hapus / Bersihkan Jadwal Roster Berdasarkan Rentang Tanggal.
     */
    public function clearRoster(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-attendance');

        $validated = $request->validate([
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:hcm_employees,id'],
            'department' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $query = HcmShiftRoster::whereBetween('roster_date', [$validated['start_date'], $validated['end_date']]);

        if (!empty($validated['employee_ids'])) {
            $query->whereIn('employee_id', $validated['employee_ids']);
        } elseif (!empty($validated['department']) && $validated['department'] !== 'all') {
            $query->whereHas('employee', fn ($q) => $q->where('department', $validated['department']));
        }

        $deletedCount = $query->delete();

        return redirect()->back()->with('success', "Sebanyak {$deletedCount} penugasan roster berhasil dibersihkan.");
    }

    /**
     * Set Default Shift untuk Karyawan atau Departemen.
     */
    public function setDefaultShift(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-attendance');

        $validated = $request->validate([
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:hcm_employees,id'],
            'department' => ['nullable', 'string'],
            'shift_id' => ['required', 'exists:hcm_work_shifts,id'],
        ]);

        $shiftId = (int) $validated['shift_id'];
        $empQuery = HcmEmployee::query();

        if (!empty($validated['employee_ids'])) {
            $empQuery->whereIn('id', $validated['employee_ids']);
        } elseif (!empty($validated['department']) && $validated['department'] !== 'all') {
            $empQuery->where('department', $validated['department']);
        } else {
            return redirect()->back()->with('error', 'Pilih karyawan atau departemen yang akan diatur default shift-nya.');
        }

        $count = $empQuery->update(['default_shift_id' => $shiftId]);
        $shift = HcmWorkShift::find($shiftId);

        return redirect()->back()->with('success', "Default shift untuk {$count} karyawan berhasil diset menjadi '{$shift?->name}'.");
    }
}
