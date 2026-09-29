<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmAttendance;
use App\Models\Hcm\HcmCompanyEvent;
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
            'id', 'employee_code', 'name', 'nickname', 'department', 'position', 'job_level',
            'birth_date', 'join_date', 'employment_status', 'is_active'
        )->get();

        $activeEmployees = $allEmployees->where('is_active', true);

        // === TOTAL DARI SELURUH MODUL KEPEGAWAIAN ===
        $moduleTotals = [
            // 1. Modul Karyawan & Magang
            'employees' => [
                'total_all' => $allEmployees->count(),
                'total_active' => $activeEmployees->count(),
                'total_inactive' => $allEmployees->where('is_active', false)->count(),
                'pkwtt' => $activeEmployees->where('employment_status', 'Tetap (PKWTT)')->count(),
                'pkwt' => $activeEmployees->where('employment_status', 'Kontrak (PKWT)')->count(),
                'magang' => $activeEmployees->where('employment_status', 'Magang')->count(),
                'probation' => $activeEmployees->where('employment_status', 'Probation')->count(),
            ],

            // 2. Modul Kontrak Kerja PKWT
            'contracts' => [
                'total_contracts' => HcmContract::count(),
                'active' => HcmContract::where('review_status', 'Aktif')->count(),
                'expiring_h30' => HcmContract::where('review_status', 'Aktif')
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

        // === 1. PERINGATAN PROBATION H-7 (Masa Percobaan 3 Bulan) ===
        $probationAlerts = $activeEmployees->filter(function ($emp) use ($today) {
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

        // === 2. PERINGATAN HABIS KONTRAK PKWT H-30 ===
        $contractAlerts = HcmContract::where('review_status', 'Aktif')
            ->whereDate('end_date', '>=', $todayStr)
            ->whereDate('end_date', '<=', $today->copy()->addDays(30)->toDateString())
            ->with('employee:id,employee_code,name,department,position')
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

        // === 3. REKAP IZIN & CUTI HARI INI (Scheduled Leave Alert) ===
        $activeLeavesToday = HcmLeaveRequest::where('status', 'APPROVED')
            ->whereDate('start_date', '<=', $todayStr)
            ->whereDate('end_date', '>=', $todayStr)
            ->with('employee:id,employee_code,name,department,position')
            ->get()
            ->map(function ($l) {
                return [
                    'id' => $l->id,
                    'employee_id' => $l->employee_id,
                    'employee_name' => $l->employee?->name,
                    'employee_code' => $l->employee?->employee_code,
                    'department' => $l->employee?->department,
                    'position' => $l->employee?->position,
                    'leave_type' => $l->leave_type,
                    'reason' => $l->reason,
                    'start_date' => $l->start_date ? Carbon::parse($l->start_date)->format('d M Y') : '-',
                    'end_date' => $l->end_date ? Carbon::parse($l->end_date)->format('d M Y') : '-',
                    'applied_at' => $l->created_at ? $l->created_at->format('d M Y') : '-',
                ];
            });

        // === 4. PERINGATAN ABSENSI (Unexcused Absence / Mangkir Hari Ini) ===
        $todayAttendances = HcmAttendance::whereDate('attendance_date', $todayStr)->get()->keyBy('employee_id');
        $activeLeavesEmpIds = HcmLeaveRequest::where('status', 'APPROVED')
            ->whereDate('start_date', '<=', $todayStr)
            ->whereDate('end_date', '>=', $todayStr)
            ->pluck('employee_id')
            ->all();

        $unexcusedAbsenceAlerts = $activeEmployees->filter(function ($emp) use ($todayAttendances, $activeLeavesEmpIds) {
            if (in_array($emp->id, $activeLeavesEmpIds)) {
                return false;
            }
            $att = $todayAttendances->get($emp->id);
            if (!$att) {
                return true; // Belum clock-in tanpa keterangan
            }
            return $att->attendance_category === 'Alpha/Mangkir';
        })->map(function ($emp) use ($todayAttendances) {
            $att = $todayAttendances->get($emp->id);
            return [
                'employee_id' => $emp->id,
                'name' => $emp->name,
                'nickname' => $emp->nickname,
                'employee_code' => $emp->employee_code,
                'department' => $emp->department,
                'position' => $emp->position,
                'status' => $att ? $att->attendance_category : 'Belum Absen',
            ];
        })->values();

        // === 5. PENGINGAT VALIDASI LEMBUR MINGGUAN (Jumat/Sabtu Alert) ===
        $isFriday = $today->isFriday();
        $isSaturday = $today->isSaturday();
        $draftOvertimeBatches = HcmOvertimeBatch::where('status', 'DRAFT')
            ->withCount('overtimes')
            ->orderBy('created_at', 'desc')
            ->get();

        $overtimeReminder = [
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

        // === 6. PENGINGAT CUT-OFF & REKAP ABSENSI BULANAN (H-3 Cut-Off Payroll) ===
        $cutoffDay = (int) SystemSetting::get('hcm_payroll', 'cutoff_day', 25);
        $currentCutoff = Carbon::create($today->year, $today->month, min($cutoffDay, 28));
        if ($today->day > $cutoffDay) {
            $currentCutoff = $currentCutoff->addMonth();
        }
        $daysToCutoff = (int) $today->diffInDays($currentCutoff, false);
        $draftMealBatches = HcmMealAllowanceBatch::where('status', 'DRAFT')
            ->orderBy('created_at', 'desc')
            ->get();

        $payrollCutoffReminder = [
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

        // === 7. PENDING APPROVALS (Cuti / Izin Menunggu HR) ===
        $pendingLeaves = HcmLeaveRequest::whereIn('status', ['PENDING', 'PENDING_REVIEW'])
            ->with('employee:id,employee_code,name,department,position')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($l) {
                return [
                    'id' => $l->id,
                    'employee_id' => $l->employee_id,
                    'employee_name' => $l->employee?->name,
                    'department' => $l->employee?->department,
                    'position' => $l->employee?->position,
                    'leave_type' => $l->leave_type,
                    'start_date' => $l->start_date ? Carbon::parse($l->start_date)->format('d M Y') : '-',
                    'end_date' => $l->end_date ? Carbon::parse($l->end_date)->format('d M Y') : '-',
                    'total_days' => $l->total_days,
                    'reason' => $l->reason,
                    'applied_at' => $l->created_at ? $l->created_at->format('d M Y') : '-',
                ];
            });

        // === 8. ULANG TAHUN H-3 DINAMIS ===
        $birthdayAlerts = $activeEmployees->filter(function ($emp) use ($today) {
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

        // === 9. WORK ANNIVERSARY H-7 DINAMIS ===
        $anniversaryAlerts = $activeEmployees->filter(function ($emp) use ($today) {
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

        // === 10. ACARA TERDEKAT DARI MODUL KALENDER ACARA ===
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
            'unexcusedAbsenceAlerts' => $unexcusedAbsenceAlerts,
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
}
