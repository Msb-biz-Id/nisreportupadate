<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmAttendance;
use App\Models\Hcm\HcmCompanyEvent;
use App\Models\Hcm\HcmCompensation;
use App\Models\Hcm\HcmContract;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmEmployeeReward;
use App\Models\Hcm\HcmLeaveRequest;
use App\Models\Hcm\HcmMasterOption;
use App\Models\Hcm\HcmMealAllowanceBatch;
use App\Models\Hcm\HcmOvertime;
use App\Models\Hcm\HcmOvertimeBatch;
use App\Models\Settings\SystemSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HcmDashboardController extends Controller
{
    /**
     * Tampilkan Command Center / Dashboard Kepegawaian (HCM) dengan total dari seluruh modul.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('hcm.view');

        $today = Carbon::today();
        $todayStr = $today->toDateString();
        $startOfMonth = $today->copy()->startOfMonth()->toDateString();
        $endOfMonth = $today->copy()->endOfMonth()->toDateString();
        $startOfYear = $today->copy()->startOfYear()->toDateString();

        // 1. Ambil seluruh data karyawan aktif untuk kalkulasi alert dinamis & total
        $allEmployees = HcmEmployee::select(
            'id', 'employee_code', 'name', 'nickname', 'department', 'division', 'job_level',
            'birth_date', 'join_date', 'employment_status', 'is_active'
        )->get();

        $activeEmployees = $allEmployees->where('is_active', true);

        // 2. Metrik modul dan alert
        $moduleTotals = $this->getModuleTotals($allEmployees, $activeEmployees, $today, $todayStr, $startOfMonth, $endOfMonth, $startOfYear);
        $probationAlerts = $this->getProbationAlerts($activeEmployees, $today);
        $contractAlerts = $this->getContractAlerts($today, $todayStr);
        $salaryEvaluationAlerts = $this->getSalaryEvaluationAlerts($today);
        $activeLeavesToday = $this->getActiveLeavesToday($todayStr);
        [$unexcusedAbsenceAlerts, $absenceAlertActive] = $this->getUnexcusedAbsenceAlerts($activeEmployees, $todayStr);
        $overtimeReminder = $this->getOvertimeReminder($today);
        [$payrollCutoffReminder, $daysToCutoff] = $this->getPayrollCutoffReminder($today);
        $pendingLeaves = $this->getPendingLeaves();
        $birthdayAlerts = $this->getBirthdayAlerts($activeEmployees, $today);
        $anniversaryAlerts = $this->getAnniversaryAlerts($activeEmployees, $today);

        $upcomingEvents = HcmCompanyEvent::whereDate('end_date', '>=', $todayStr)
            ->orderBy('start_date', 'asc')
            ->limit(5)
            ->get();

        $legalEntities = HcmMasterOption::getOptions('legal_entities');

        return Inertia::render('Hcm/Dashboard/Index', [
            'today' => $todayStr,
            'moduleTotals' => $moduleTotals,
            'legalEntities' => $legalEntities,
            'probationAlerts' => $probationAlerts,
            'contractAlerts' => $contractAlerts,
            'salaryEvaluationAlerts' => $salaryEvaluationAlerts,
            'unexcusedAbsenceAlerts' => $unexcusedAbsenceAlerts,
            'absenceAlertActive' => $absenceAlertActive,
            'overtimeReminder' => $overtimeReminder,
            'payrollCutoffReminder' => $payrollCutoffReminder,
            'pendingLeaves' => $pendingLeaves,
            'activeLeavesToday' => $activeLeavesToday,
            'birthdayAlerts' => $birthdayAlerts,
            'anniversaryAlerts' => $anniversaryAlerts,
            'upcomingEvents' => $upcomingEvents,
            'daysToCutoff' => $daysToCutoff,
        ]);
    }

    /**
     * Hitung total metrik dari seluruh modul kepegawaian.
     */
    private function getModuleTotals(
        Collection $allEmployees,
        Collection $activeEmployees,
        Carbon $today,
        string $todayStr,
        string $startOfMonth,
        string $endOfMonth,
        string $startOfYear
    ): array {
        return [
            // 1. Modul Karyawan & Magang
            'employees' => [
                'total_all' => $allEmployees->count(),
                'total_active' => $activeEmployees->count(),
                'total_inactive' => $allEmployees->where('is_active', false)->count(),
                'pkwtt' => $activeEmployees->filter(fn ($e) => str_contains($e->employment_status ?? '', 'Tetap'))->count(),
                'pkwt' => $activeEmployees->filter(fn ($e) => str_contains($e->employment_status ?? '', 'PKWT') || str_contains($e->employment_status ?? '', 'Kontrak'))->count(),
                'magang' => $activeEmployees->filter(fn ($e) => str_contains($e->employment_status ?? '', 'Magang') || $e->job_level === 'Magang' || $e->employee_category === 'Magang')->count(),
                'probation' => $activeEmployees->filter(fn ($e) => str_contains($e->employment_status ?? '', 'Probation') || str_contains($e->employment_status ?? '', 'Trainee') || $e->job_level === 'Trainee')->count(),
            ],

            // 2. Modul Kontrak Kerja PKWT
            'contracts' => [
                'total_contracts' => HcmContract::count(),
                'active' => HcmContract::where('review_status', 'like', '%Aktif%')->count(),
                'expiring_h30' => HcmContract::where('review_status', 'like', '%Aktif%')
                    ->whereDate('end_date', '>=', $todayStr)
                    ->whereDate('end_date', '<=', $today->copy()->addDays(30)->toDateString())
                    ->count(),
                'expired' => HcmContract::whereDate('end_date', '<', $todayStr)->count(),
            ],

            // 3. Modul Presensi Harian
            'attendance' => [
                'today_present' => HcmAttendance::whereDate('attendance_date', $todayStr)->where('attendance_category', 'Hadir')->count(),
                'today_late' => HcmAttendance::whereDate('attendance_date', $todayStr)->where('attendance_category', 'Terlambat')->count(),
                'today_half_day' => HcmAttendance::whereDate('attendance_date', $todayStr)->where('attendance_category', 'Pulang Cepat')->count(),
                'today_leave_permit' => HcmAttendance::whereDate('attendance_date', $todayStr)->whereIn('attendance_category', ['Cuti', 'Izin', 'Sakit', 'Dinas Luar'])->count(),
                'today_alpha' => HcmAttendance::whereDate('attendance_date', $todayStr)->where('attendance_category', 'Alpha/Mangkir')->count(),
                'month_total_logs' => HcmAttendance::whereBetween('attendance_date', [$startOfMonth, $endOfMonth])->count(),
            ],

            // 4. Modul Cuti & Perizinan
            'leaves' => [
                'total_year' => HcmLeaveRequest::whereDate('created_at', '>=', $startOfYear)->count(),
                'pending' => HcmLeaveRequest::whereIn('status', ['PENDING', 'PENDING_REVIEW'])->count(),
                'approved_month' => HcmLeaveRequest::where('status', 'APPROVED')->whereBetween('start_date', [$startOfMonth, $endOfMonth])->count(),
                'active_today' => HcmLeaveRequest::where('status', 'APPROVED')
                    ->whereDate('start_date', '<=', $todayStr)
                    ->whereDate('end_date', '>=', $todayStr)
                    ->count(),
            ],

            // 5. Modul Lembur Mingguan
            'overtime' => [
                'total_batches' => HcmOvertimeBatch::count(),
                'pending_batches' => HcmOvertimeBatch::whereIn('status', ['DRAFT', 'APPROVED_BY_HCM'])->count(),
                'month_hours' => (float) HcmOvertime::whereBetween('overtime_date', [$startOfMonth, $endOfMonth])->sum('duration_hours'),
                'month_cost' => (float) HcmOvertime::whereBetween('overtime_date', [$startOfMonth, $endOfMonth])->sum('total_amount'),
            ],

            // 6. Modul Uang Makan Bulanan
            'meal_allowance' => [
                'total_batches' => HcmMealAllowanceBatch::count(),
                'pending_batches' => HcmMealAllowanceBatch::whereIn('status', ['DRAFT', 'APPROVED_BY_HCM'])->count(),
                'total_disbursed' => (float) HcmMealAllowanceBatch::where('status', 'PAID_COMPLETED')->sum('total_amount'),
                'total_held' => (float) HcmMealAllowanceBatch::sum('total_held_amount'),
            ],

            // 7. Modul Reward & Apresiasi
            'rewards' => [
                'total_rewards' => HcmEmployeeReward::count(),
                'delivered' => HcmEmployeeReward::whereIn('distribution_status', ['Sudah Diterima (Serah Terima Langsung)', 'Sudah Ditransfer'])->count(),
                'pending' => HcmEmployeeReward::whereIn('distribution_status', ['Belum Diterima', 'Tertunda / Pending'])->count(),
                'total_budget' => (float) HcmEmployeeReward::sum('budget_amount'),
            ],

            // 8. Modul Agenda Acara Perusahaan
            'events' => [
                'total_year' => HcmCompanyEvent::whereYear('start_date', $today->year)->count(),
                'upcoming' => HcmCompanyEvent::whereDate('start_date', '>=', $todayStr)->count(),
                'holidays' => HcmCompanyEvent::where('event_type', 'Libur Nasional')->whereYear('start_date', $today->year)->count(),
            ],
        ];
    }

    /**
     * Peringatan Probation H-7 (Masa Percobaan 3 Bulan).
     */
    private function getProbationAlerts(Collection $activeEmployees, Carbon $today): Collection
    {
        return $activeEmployees->filter(function ($emp) use ($today) {
            if (!$emp->join_date) return false;
            if ($emp->employment_status === 'Probation' || $emp->job_level === 'Trainee') {
                $probEnd = Carbon::parse($emp->join_date)->addMonths(3);
                $diff = $today->diffInDays($probEnd, false);
                return $diff >= -7 && $diff <= 7;
            }
            return false;
        })->map(function ($emp) use ($today) {
            $probEnd = Carbon::parse($emp->join_date)->addMonths(3);
            $diff = (int) $today->diffInDays($probEnd, false);
            return [
                'employee_id' => $emp->id,
                'name' => $emp->name,
                'employee_code' => $emp->employee_code,
                'department' => $emp->department,
                'probation_end_date' => $probEnd->format('Y-m-d'),
                'days_remaining' => $diff,
                'urgency' => ($diff <= 3) ? 'critical' : 'warning',
            ];
        })->sortBy('days_remaining')->values();
    }

    /**
     * Peringatan Habis Kontrak PKWT H-30.
     */
    private function getContractAlerts(Carbon $today, string $todayStr): Collection
    {
        return HcmContract::where('review_status', 'Aktif')
            ->whereDate('end_date', '>=', $todayStr)
            ->whereDate('end_date', '<=', $today->copy()->addDays(30)->toDateString())
            ->with('employee:id,employee_code,name,department,division')
            ->orderBy('end_date', 'asc')
            ->get()
            ->map(function ($c) use ($today) {
                $endDate = Carbon::parse($c->end_date);
                $diff = (int) $today->diffInDays($endDate, false);
                return [
                    'contract_id' => $c->id,
                    'contract_number' => $c->contract_number,
                    'employee_id' => $c->employee?->id,
                    'name' => $c->employee?->name,
                    'employee_code' => $c->employee?->employee_code,
                    'department' => $c->employee?->department,
                    'end_date' => $c->end_date->format('Y-m-d'),
                    'days_remaining' => $diff,
                    'urgency' => ($diff <= 7) ? 'critical' : 'warning',
                ];
            });
    }

    /**
     * Rekap Cuti & Izin Aktif Hari Ini.
     */
    private function getActiveLeavesToday(string $todayStr): Collection
    {
        return HcmLeaveRequest::where('status', 'APPROVED')
            ->whereDate('start_date', '<=', $todayStr)
            ->whereDate('end_date', '>=', $todayStr)
            ->with('employee:id,employee_code,name,department,division')
            ->get()
            ->map(function ($l) {
                return [
                    'id' => $l->id,
                    'employee_id' => $l->employee_id,
                    'employee_name' => $l->employee?->name,
                    'employee_code' => $l->employee?->employee_code,
                    'department' => $l->employee?->department,
                    'division' => $l->employee?->division,
                    'leave_type' => $l->leave_type,
                    'reason' => $l->reason,
                    'start_date' => $l->start_date ? Carbon::parse($l->start_date)->format('d M Y') : '-',
                    'end_date' => $l->end_date ? Carbon::parse($l->end_date)->format('d M Y') : '-',
                    'applied_at' => $l->created_at ? $l->created_at->format('d M Y') : '-',
                ];
            });
    }

    /**
     * Peringatan Absensi (Unexcused Absence / Mangkir Hari Ini).
     */
    private function getUnexcusedAbsenceAlerts(Collection $activeEmployees, string $todayStr): array
    {
        $todayAttendances = HcmAttendance::whereDate('attendance_date', $todayStr)->get()->keyBy('employee_id');
        $activeLeavesEmpIds = HcmLeaveRequest::where('status', 'APPROVED')
            ->whereDate('start_date', '<=', $todayStr)
            ->whereDate('end_date', '>=', $todayStr)
            ->pluck('employee_id')
            ->all();

        $rejectedLeaveEmpIds = HcmLeaveRequest::where('status', 'REJECTED')
            ->whereDate('start_date', '<=', $todayStr)
            ->whereDate('end_date', '>=', $todayStr)
            ->pluck('employee_id')
            ->all();

        $absenceAlertActive = now()->greaterThanOrEqualTo(Carbon::parse($todayStr)->setTime(8, 30));

        $unexcusedAbsenceAlerts = $absenceAlertActive
            ? $activeEmployees->filter(function ($emp) use ($todayAttendances, $activeLeavesEmpIds) {
                if (in_array($emp->id, $activeLeavesEmpIds)) {
                    return false;
                }
                $att = $todayAttendances->get($emp->id);
                if (!$att) {
                    return true;
                }
                return $att->attendance_category === 'Alpha/Mangkir';
            })->map(function ($emp) use ($todayAttendances, $rejectedLeaveEmpIds) {
                $att = $todayAttendances->get($emp->id);
                $status = $att ? $att->attendance_category : 'Belum Absen';
                if (in_array($emp->id, $rejectedLeaveEmpIds)) {
                    $status = 'Mangkir (Izin Ditolak)';
                }
                return [
                    'employee_id' => $emp->id,
                    'name' => $emp->name,
                    'nickname' => $emp->nickname,
                    'employee_code' => $emp->employee_code,
                    'department' => $emp->department,
                    'division' => $emp->division,
                    'status' => $status,
                ];
            })->values()
            : collect();

        return [$unexcusedAbsenceAlerts, $absenceAlertActive];
    }

    /**
     * Pengingat Validasi Lembur Mingguan (Jumat/Sabtu Alert).
     */
    private function getOvertimeReminder(Carbon $today): array
    {
        $isFriday = $today->isFriday();
        $isSaturday = $today->isSaturday();
        $draftOvertimeBatches = HcmOvertimeBatch::where('status', 'DRAFT')
            ->withCount('overtimes')
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'is_weekend_cutoff' => ($isFriday || $isSaturday),
            'is_saturday' => $isSaturday,
            'draft_batches_count' => $draftOvertimeBatches->count(),
            'latest_batch' => $draftOvertimeBatches->first() ? [
                'id' => $draftOvertimeBatches->first()->id,
                'batch_code' => $draftOvertimeBatches->first()->batch_code,
                'period_start' => $draftOvertimeBatches->first()->period_start,
                'period_end' => $draftOvertimeBatches->first()->period_end,
                'total_hours' => $draftOvertimeBatches->first()->total_hours,
                'total_amount' => $draftOvertimeBatches->first()->total_amount,
                'items_count' => $draftOvertimeBatches->first()->overtimes_count,
            ] : null,
        ];
    }

    /**
     * Pengingat Cut-Off & Rekap Absensi Bulanan (H-3 Cut-Off Payroll).
     */
    private function getPayrollCutoffReminder(Carbon $today): array
    {
        $cutoffDay = (int) SystemSetting::get('hcm_payroll', 'cutoff_day', 25);
        $currentCutoff = Carbon::create($today->year, $today->month, min($cutoffDay, 28));
        if ($today->day > $cutoffDay) {
            $currentCutoff = $currentCutoff->addMonth();
        }
        $daysToCutoff = (int) $today->diffInDays($currentCutoff, false);
        $draftMealBatches = HcmMealAllowanceBatch::where('status', 'DRAFT')
            ->orderBy('created_at', 'desc')
            ->get();

        $reminder = [
            'days_to_cutoff' => $daysToCutoff,
            'cutoff_date' => $currentCutoff->format('d M Y'),
            'is_cutoff_window' => ($daysToCutoff <= 3 && $daysToCutoff >= 0),
            'is_cutoff_day' => ($daysToCutoff === 0),
            'draft_batches_count' => $draftMealBatches->count(),
            'latest_batch' => $draftMealBatches->first() ? [
                'id' => $draftMealBatches->first()->id,
                'batch_code' => $draftMealBatches->first()->batch_code,
                'period_month' => $draftMealBatches->first()->period_month,
                'period_year' => $draftMealBatches->first()->period_year,
                'total_amount' => $draftMealBatches->first()->total_amount,
            ] : null,
        ];

        return [$reminder, $daysToCutoff];
    }

    /**
     * Ambil 5 Pengajuan Cuti / Izin Menunggu Review HR.
     */
    private function getPendingLeaves(): Collection
    {
        return HcmLeaveRequest::whereIn('status', ['PENDING', 'PENDING_REVIEW'])
            ->with('employee:id,employee_code,name,department,division')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($l) {
                return [
                    'id' => $l->id,
                    'employee_id' => $l->employee_id,
                    'employee_name' => $l->employee?->name,
                    'department' => $l->employee?->department,
                    'division' => $l->employee?->division,
                    'position' => $l->employee?->division,
                    'leave_type' => $l->leave_type,
                    'start_date' => $l->start_date ? Carbon::parse($l->start_date)->format('d M Y') : '-',
                    'end_date' => $l->end_date ? Carbon::parse($l->end_date)->format('d M Y') : '-',
                    'total_days' => $l->total_days,
                    'reason' => $l->reason,
                    'applied_at' => $l->created_at ? $l->created_at->format('d M Y') : '-',
                ];
            });
    }

    /**
     * Alert Ulang Tahun H-3 Dinamis.
     */
    private function getBirthdayAlerts(Collection $activeEmployees, Carbon $today): Collection
    {
        return $activeEmployees->filter(function ($emp) use ($today) {
            if (!$emp->birth_date) return false;
            $bdate = Carbon::parse($emp->birth_date);
            $thisYearBday = Carbon::create($today->year, $bdate->month, $bdate->day);

            if ($thisYearBday->lt($today)) {
                $thisYearBday->addYear();
            }

            $diff = $today->diffInDays($thisYearBday, false);
            return $diff >= 0 && $diff <= 3;
        })->map(function ($emp) use ($today) {
            $bdate = Carbon::parse($emp->birth_date);
            $thisYearBday = Carbon::create($today->year, $bdate->month, $bdate->day);
            if ($thisYearBday->lt($today)) {
                $thisYearBday->addYear();
            }
            $diff = (int) $today->diffInDays($thisYearBday, false);
            return [
                'employee_id' => $emp->id,
                'name' => $emp->name,
                'nickname' => $emp->nickname,
                'department' => $emp->department,
                'birth_date' => $bdate->format('d M'),
                'is_today' => ($diff === 0),
                'days_remaining' => $diff,
            ];
        })->values();
    }

    /**
     * Alert Work Anniversary H-7 Dinamis.
     */
    private function getAnniversaryAlerts(Collection $activeEmployees, Carbon $today): Collection
    {
        return $activeEmployees->filter(function ($emp) use ($today) {
            if (!$emp->join_date) return false;
            $jdate = Carbon::parse($emp->join_date);
            if ($jdate->isSameYear($today)) return false;

            $thisYearAnniv = Carbon::create($today->year, $jdate->month, $jdate->day);
            if ($thisYearAnniv->lt($today)) {
                $thisYearAnniv->addYear();
            }

            $diff = $today->diffInDays($thisYearAnniv, false);
            return $diff >= 0 && $diff <= 7;
        })->map(function ($emp) use ($today) {
            $jdate = Carbon::parse($emp->join_date);
            $yearsOfService = $today->year - $jdate->year;
            $thisYearAnniv = Carbon::create($today->year, $jdate->month, $jdate->day);
            if ($thisYearAnniv->lt($today)) {
                $thisYearAnniv->addYear();
                $yearsOfService++;
            }
            $diff = (int) $today->diffInDays($thisYearAnniv, false);
            return [
                'employee_id' => $emp->id,
                'name' => $emp->name,
                'department' => $emp->department,
                'years_of_service' => $yearsOfService,
                'anniversary_date' => $jdate->format('d M'),
                'is_today' => ($diff === 0),
                'days_remaining' => $diff,
            ];
        })->values();
    }

    /**
     * Alert Jadwal Evaluasi Kenaikan Gaji Berkala (Milestone Siklus Kontrak / 6 Bulan).
     */
    private function getSalaryEvaluationAlerts(Carbon $today): Collection
    {
        return HcmCompensation::with([
            'employee:id,employee_code,name,nickname,department,division,join_date,original_join_date,is_active',
            'employee.activeContract',
        ])
            ->whereHas('employee', fn ($q) => $q->where('is_active', true))
            ->get()
            ->filter(function ($comp) use ($today) {
                // 1. Jika status sedang diajukan atau ditunda, selalu munculkan sebagai reminder aksi
                if (in_array($comp->decision_status, ['Sedang Diajukan', 'Ditunda'])) {
                    return true;
                }

                // 2. Jika ada custom milestone date (misal hasil keputusan tunda)
                if ($comp->custom_milestone_date) {
                    $customDate = Carbon::parse($comp->custom_milestone_date)->startOfDay();
                    $diff = $today->diffInDays($customDate, false);
                    return $diff <= 30 && $comp->decision_status !== 'Sudah Disetujui / ACC';
                }

                // 3. Hitung milestone siklus reguler (default 6 bulan)
                $emp = $comp->employee;
                if (!$emp) return false;

                $anchorDate = null;
                if ($emp->activeContract && $emp->activeContract->start_date) {
                    $anchorDate = Carbon::parse($emp->activeContract->start_date);
                } elseif ($emp->original_join_date) {
                    $anchorDate = Carbon::parse($emp->original_join_date);
                } elseif ($emp->join_date) {
                    $anchorDate = Carbon::parse($emp->join_date);
                }

                if (!$anchorDate) return false;

                $cycleMonths = (int) ($comp->evaluation_cycle_months ?: 6);
                if ($cycleMonths <= 0) $cycleMonths = 6;

                $monthsPassed = $anchorDate->diffInMonths($today);
                $cycleStep = max(1, (int) round($monthsPassed / $cycleMonths));
                $milestoneDate = $anchorDate->copy()->addMonths($cycleStep * $cycleMonths);

                $diffDays = (int) $today->diffInDays($milestoneDate, false);

                // Munculkan jika dalam window evaluasi (H-30 hari menuju milestone s.d. 30 hari sesudah milestone)
                // dan belum berstatus ACC pada periode ini
                if ($diffDays <= 30 && $diffDays >= -30) {
                    return $comp->decision_status !== 'Sudah Disetujui / ACC';
                }

                return false;
            })
            ->map(function ($comp) use ($today) {
                $emp = $comp->employee;
                $activeContract = $emp?->activeContract;

                $anchorDate = null;
                if ($activeContract && $activeContract->start_date) {
                    $anchorDate = Carbon::parse($activeContract->start_date);
                } elseif ($emp?->original_join_date) {
                    $anchorDate = Carbon::parse($emp->original_join_date);
                } elseif ($emp?->join_date) {
                    $anchorDate = Carbon::parse($emp->join_date);
                }

                $cycleMonths = (int) ($comp->evaluation_cycle_months ?: 6);
                $monthsPassed = $anchorDate ? $anchorDate->diffInMonths($today) : 0;
                $cycleStep = max(1, (int) round($monthsPassed / $cycleMonths));

                $targetDate = $comp->custom_milestone_date
                    ? Carbon::parse($comp->custom_milestone_date)
                    : ($anchorDate ? $anchorDate->copy()->addMonths($cycleStep * $cycleMonths) : $today);

                $diff = (int) $today->diffInDays($targetDate, false);

                return [
                    'compensation_id' => $comp->id,
                    'compensation_uuid' => $comp->uuid,
                    'employee_id' => $emp?->id,
                    'employee_code' => $emp?->employee_code,
                    'name' => $emp?->name,
                    'nickname' => $emp?->nickname,
                    'department' => $emp?->department,
                    'division' => $emp?->division,
                    'contract_number' => $comp->contract_number ?: $activeContract?->contract_number ?: '-',
                    'contract_sequence' => $activeContract?->contract_sequence ? "Kontrak ke-{$activeContract->contract_sequence}" : ($comp->duration_text ?: 'PKWT'),
                    'cycle_text' => "Bulan ke-" . ($cycleStep * $cycleMonths),
                    'current_salary' => (float) $comp->current_salary,
                    'planned_increment' => (float) ($comp->planned_increment ?? 0),
                    'decision_status' => $comp->decision_status ?: 'Jadwal Evaluasi Masuk',
                    'effective_date' => $comp->effective_date ? $comp->effective_date->format('Y-m-d') : null,
                    'target_date' => $targetDate->format('Y-m-d'),
                    'days_remaining' => $diff,
                    'urgency' => ($diff <= 7 || in_array($comp->decision_status, ['Sedang Diajukan', 'Ditunda'])) ? 'critical' : 'warning',
                ];
            })
            ->sortBy('days_remaining')
            ->values();
    }
}
