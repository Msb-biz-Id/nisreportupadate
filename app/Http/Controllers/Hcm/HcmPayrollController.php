<?php

namespace App\Http\Controllers\Hcm;

use App\Exports\HcmPayrollBatchExport;
use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmMealAllowanceBatch;
use App\Models\Hcm\HcmMealAllowanceItem;
use App\Models\Hcm\HcmOvertime;
use App\Models\Hcm\HcmPayroll;
use App\Models\Hcm\HcmPayrollItem;
use App\Models\Hcm\HcmSalaryDeduction;
use App\Services\ActivityLogger;
use App\Services\GoogleDriveSyncService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Jobs\Hcm\SendHcmSlipEmailJob;
use App\Models\Settings\SystemSetting;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class HcmPayrollController extends Controller
{
    /**
     * Tampilkan daftar batch periode penggajian (Unified Payroll Engine).
     */
    public function index(Request $request): Response
    {
        Gate::authorize('hcm.manage-compensation');

        $search = $request->input('search', '');
        $statusFilter = $request->input('status', 'all');

        $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search);

        $query = HcmPayroll::with([
            'creator:id,name',
            'hcmSigner:id,name',
            'financeSigner:id,name',
        ])
            ->when($escapedSearch, function ($q, $term) {
                $q->where('period_code', 'like', "%{$term}%")
                    ->orWhere('work_period_month', 'like', "%{$term}%")
                    ->orWhere('payout_period_month', 'like', "%{$term}%")
                    ->orWhere('notes', 'like', "%{$term}%");
            })
            ->when($statusFilter !== 'all', fn($q) => $q->where('status', $statusFilter))
            ->orderBy('id', 'desc');

        $payrolls = $query->paginate(15)->withQueryString();

        $allPayrolls = HcmPayroll::query();
        $metrics = [
            'total_payroll_expenditure' => (float) (clone $allPayrolls)->where('status', 'PAID_COMPLETED')->sum('total_net_payout'),
            'total_batches_count' => (clone $allPayrolls)->count(),
            'waiting_hcm_approval' => (clone $allPayrolls)->where('status', 'DRAFT_HCM')->count(),
            'waiting_finance_payment' => (clone $allPayrolls)->where('status', 'APPROVED_BY_HCM')->count(),
            'paid_completed' => (clone $allPayrolls)->where('status', 'PAID_COMPLETED')->count(),
        ];

        // Usulan bulan periode berikutnya
        $now = Carbon::now();
        $suggestedWorkMonth = $now->copy()->subMonth()->format('Y-m'); // Bulan kinerja lalu
        $suggestedPayoutMonth = $now->format('Y-m'); // Bulan pencairan ini

        return Inertia::render('Hcm/Payroll/Index', [
            'payrolls' => $payrolls,
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
            ],
            'metrics' => $metrics,
            'suggestions' => [
                'work_period_month' => $suggestedWorkMonth,
                'payout_period_month' => $suggestedPayoutMonth,
                'payout_date' => $now->format('Y-m-25'),
            ],
        ]);
    }

    /**
     * Generate batch penggajian terpadu (Unified Aggregation Engine).
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-compensation');

        $validated = $request->validate([
            'work_period_month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'payout_period_month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'payout_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $periodCode = 'PAY-' . $validated['work_period_month'];

        // Cek apakah batch sudah ada dan sudah final
        $existing = HcmPayroll::where('period_code', $periodCode)->first();
        if ($existing && $existing->status === 'PAID_COMPLETED') {
            return redirect()->back()->withErrors([
                'work_period_month' => "Batch penggajian untuk periode {$periodCode} sudah berstatus PAID_COMPLETED (Terkunci permanen).",
            ]);
        }

        DB::transaction(function () use ($validated, $periodCode, $existing) {
            $payroll = $existing ?? new HcmPayroll();
            $payroll->period_code = $periodCode;
            $payroll->work_period_month = $validated['work_period_month'];
            $payroll->payout_period_month = $validated['payout_period_month'];
            $payroll->payout_date = $validated['payout_date'] ?? null;
            $payroll->notes = $validated['notes'] ?? null;
            $payroll->status = 'DRAFT_HCM';
            if (!$payroll->created_by) {
                $payroll->created_by = Auth::id();
            }
            $payroll->save();

            // Jika generate ulang, hapus item-item draf sebelumnya
            $payroll->items()->delete();

            // 1. Ambil seluruh karyawan aktif beserta kompensasi pokok
            $employees = HcmEmployee::where('is_active', true)
                ->with(['compensation'])
                ->get();

            // 2. Agregasi Uang Makan Bulan Kinerja (HcmMealAllowanceBatch)
            $workYear = (int) substr($validated['work_period_month'], 0, 4);
            $workMonth = (int) substr($validated['work_period_month'], 5, 2);

            $mealBatch = HcmMealAllowanceBatch::where('period_year', $workYear)
                ->where('period_month', $workMonth)
                ->first();

            $mealAllowances = [];
            if ($mealBatch) {
                $mealAllowances = HcmMealAllowanceItem::where('batch_id', $mealBatch->id)
                    ->pluck('payable_amount', 'employee_id')
                    ->toArray();
            }

            // 3. Agregasi Upah Lembur Bulan Kinerja (HcmOvertime)
            $startOfMonth = Carbon::parse($validated['work_period_month'] . '-01')->startOfMonth()->toDateString();
            $endOfMonth = Carbon::parse($validated['work_period_month'] . '-01')->endOfMonth()->toDateString();

            $overtimes = HcmOvertime::whereBetween('overtime_date', [$startOfMonth, $endOfMonth])
                ->groupBy('employee_id')
                ->selectRaw('employee_id, SUM(total_amount) as total_ot')
                ->pluck('total_ot', 'employee_id')
                ->toArray();

            // 4. Agregasi Pemotongan Gaji Bulan Kinerja (HcmSalaryDeduction)
            $deductions = HcmSalaryDeduction::where('effective_payroll_month', $validated['work_period_month'])
                ->whereIn('status', ['APPROVED', 'APPLIED'])
                ->get();

            $deductionsGrouped = [];
            foreach ($deductions as $d) {
                $empId = $d->employee_id;
                if (!isset($deductionsGrouped[$empId])) {
                    $deductionsGrouped[$empId] = [
                        'penalty' => 0,
                        'leave' => 0,
                        'tiered' => 0,
                    ];
                }
                $cat = strtolower($d->deduction_category);
                if (str_contains($cat, 'pelanggaran') || str_contains($cat, 'disciplinary') || str_contains($cat, 'sanksi')) {
                    $deductionsGrouped[$empId]['penalty'] += (float) $d->deduction_amount;
                } elseif (str_contains($cat, 'berjenjang') || str_contains($cat, 'maternity')) {
                    $deductionsGrouped[$empId]['tiered'] += (float) $d->deduction_amount;
                } else {
                    $deductionsGrouped[$empId]['leave'] += (float) $d->deduction_amount;
                }
            }

            // 5. Generate Item Penggajian untuk Setiap Karyawan
            foreach ($employees as $employee) {
                $baseSalary = (float) ($employee->compensation?->current_salary ?? 0);
                $mealAllowance = (float) ($mealAllowances[$employee->id] ?? 0);
                $overtimePay = (float) ($overtimes[$employee->id] ?? 0);

                // Penyesuaian kenaikan gaji jika ada
                $incrementAdj = 0;
                if ($employee->compensation?->decision_status === 'Sudah Disetujui / ACC' && (float) $employee->compensation?->planned_increment > 0) {
                    // Jika belum terrefleksikan ke current_salary
                    // Opsional: $incrementAdj = (float) $employee->compensation->planned_increment;
                }

                $penaltyDed = (float) ($deductionsGrouped[$employee->id]['penalty'] ?? 0);
                $leaveDed = (float) ($deductionsGrouped[$employee->id]['leave'] ?? 0);
                $tieredDed = (float) ($deductionsGrouped[$employee->id]['tiered'] ?? 0);

                $item = new HcmPayrollItem([
                    'payroll_id' => $payroll->id,
                    'employee_id' => $employee->id,
                    'department' => $employee->department,
                    'division' => $employee->division,
                    'employment_status' => $employee->employment_status,
                    'legal_entity' => $employee->legal_entity,
                    'bank_name' => $employee->bank_name ?? 'Bank BRI',
                    'bank_account_no' => $employee->bank_account_no,
                    'bank_account_name' => $employee->bank_account_name ?? $employee->name,
                    'base_salary' => $baseSalary,
                    'meal_allowance' => $mealAllowance,
                    'overtime_pay' => $overtimePay,
                    'increment_adjustment' => $incrementAdj,
                    'penalty_deduction' => $penaltyDed,
                    'leave_deduction' => $leaveDed,
                    'tiered_deduction' => $tieredDed,
                    'is_paid' => false,
                ]);

                $item->computeNet();
                $item->save();
            }

            // 6. Rekalkulasi Total Header
            $payroll->recalculateTotals();
        });

        ActivityLogger::log('create', 'hcm', null, "Generate batch penggajian terpadu periode {$periodCode}");

        return redirect()->route('hcm.payroll.show', $periodCode)->with('success', "Batch penggajian periode {$periodCode} berhasil digenerate.");
    }

    /**
     * Tampilkan lembar rekapitulasi penggajian bertingkat (Multi-Level Grouping: Departemen -> Divisi).
     */
    public function show(HcmPayroll $payroll): Response
    {
        Gate::authorize('hcm.manage-compensation');

        $payroll->load([
            'creator:id,name',
            'hcmSigner:id,name',
            'financeSigner:id,name',
            'items' => function ($q) {
                $q->with(['employee:id,employee_code,name,nickname,department,division,photo_url'])
                    ->orderBy('department')
                    ->orderBy('division');
            },
        ]);

        // Pengelompokan Multi-Level (Departemen -> Divisi -> Karyawan)
        $departmentGrouping = [];
        foreach ($payroll->items as $item) {
            $dept = $item->department ?: 'Departemen Lainnya';
            $div = $item->division ?: 'Umum';

            if (!isset($departmentGrouping[$dept])) {
                $departmentGrouping[$dept] = [
                    'department_name' => $dept,
                    'total_employees' => 0,
                    'total_base_salary' => 0,
                    'total_meal_allowance' => 0,
                    'total_overtime_pay' => 0,
                    'total_earnings' => 0,
                    'total_deductions' => 0,
                    'total_net_salary' => 0,
                    'divisions' => [],
                ];
            }

            if (!isset($departmentGrouping[$dept]['divisions'][$div])) {
                $departmentGrouping[$dept]['divisions'][$div] = [
                    'division_name' => $div,
                    'total_employees' => 0,
                    'total_net_salary' => 0,
                    'items' => [],
                ];
            }

            // Tambahkan ke divisi
            $departmentGrouping[$dept]['divisions'][$div]['total_employees']++;
            $departmentGrouping[$dept]['divisions'][$div]['total_net_salary'] += (float) $item->net_salary;
            $departmentGrouping[$dept]['divisions'][$div]['items'][] = $item;

            // Tambahkan ke departemen
            $departmentGrouping[$dept]['total_employees']++;
            $departmentGrouping[$dept]['total_base_salary'] += (float) $item->base_salary;
            $departmentGrouping[$dept]['total_meal_allowance'] += (float) $item->meal_allowance;
            $departmentGrouping[$dept]['total_overtime_pay'] += (float) $item->overtime_pay;
            $departmentGrouping[$dept]['total_earnings'] += (float) $item->total_earnings;
            $departmentGrouping[$dept]['total_deductions'] += (float) $item->total_deductions;
            $departmentGrouping[$dept]['total_net_salary'] += (float) $item->net_salary;
        }

        // Ubah associative array menjadi list array untuk kenyamanan rendering di frontend
        $groupedData = [];
        foreach ($departmentGrouping as $deptKey => $deptVal) {
            $divisionsList = [];
            foreach ($deptVal['divisions'] as $divKey => $divVal) {
                $divisionsList[] = $divVal;
            }
            $deptVal['divisions'] = $divisionsList;
            $groupedData[] = $deptVal;
        }

        return Inertia::render('Hcm/Payroll/Show', [
            'payroll' => $payroll,
            'groupedData' => $groupedData,
        ]);
    }

    /**
     * Otorisasi Level 1: Persetujuan HCM (Double Sign-Off HCM).
     */
    public function signHcm(HcmPayroll $payroll): RedirectResponse
    {
        Gate::authorize('hcm.manage-compensation');

        if ($payroll->status !== 'DRAFT_HCM') {
            return redirect()->back()->withErrors(['status' => 'Batch ini tidak dapat disetujui HCM karena bukan draf aktif.']);
        }

        $payroll->update([
            'status' => 'APPROVED_BY_HCM',
            'hcm_signed_by' => Auth::id(),
            'hcm_signed_at' => Carbon::now(),
        ]);

        ActivityLogger::log('update', 'hcm', $payroll, "Persetujuan Double Sign-Off HCM batch payroll {$payroll->period_code}");

        return redirect()->back()->with('success', "Batch {$payroll->period_code} berhasil disetujui oleh HCM dan diteruskan ke Keuangan.");
    }

    /**
     * Otorisasi Level 2: Verifikasi & Pencairan Keuangan (Double Sign-Off Keuangan).
     */
    public function signFinance(Request $request, HcmPayroll $payroll): RedirectResponse
    {
        Gate::authorize('hcm.manage-compensation');

        if ($payroll->status !== 'APPROVED_BY_HCM') {
            return redirect()->back()->withErrors(['status' => 'Batch harus disetujui HCM terlebih dahulu sebelum diproses Keuangan.']);
        }

        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'max:50'],
            'payment_proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'finance_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $proofUrl = null;
        if ($request->hasFile('payment_proof')) {
            $uploaded = GoogleDriveSyncService::uploadFile(
                $request->file('payment_proof'),
                'HCM_PAYROLL_PROOFS'
            );
            $proofUrl = $uploaded['url'];
        }

        DB::transaction(function () use ($payroll, $validated, $proofUrl) {
            $payroll->update([
                'status' => 'PAID_COMPLETED',
                'finance_signed_by' => Auth::id(),
                'finance_signed_at' => Carbon::now(),
                'payment_method' => $validated['payment_method'],
                'payment_proof_url' => $proofUrl ?? $payroll->payment_proof_url,
                'notes' => $validated['finance_notes'] ?? $payroll->notes,
            ]);

            // Kunci seluruh item menjadi berstatus lunas / paid
            $payroll->items()->update(['is_paid' => true]);
        });

        ActivityLogger::log('update', 'hcm', $payroll, "Pencairan final & Double Sign-Off Keuangan batch payroll {$payroll->period_code}");

        // Distribusi Slip Gaji Otomatis ke Email Karyawan (dengan Jeda Waktu Setor BRI)
        $autoSend = in_array(SystemSetting::get('hcm_payroll', 'auto_send_slip_email', '0'), ['1', 1, true, 'true'], true);
        $sendSalary = in_array(SystemSetting::get('hcm_payroll', 'send_salary_slip_email', '1'), ['1', 1, true, 'true'], true);
        $delayMinutes = (int) SystemSetting::get('hcm_payroll', 'slip_email_delay_minutes', 60);

        if ($autoSend && $sendSalary) {
            $payroll->load(['items.employee']);
            $dispatchedCount = 0;
            foreach ($payroll->items as $item) {
                if (!empty($item->employee?->email)) {
                    SendHcmSlipEmailJob::dispatch('salary', $item->id)->delay(now()->addMinutes($delayMinutes));
                    $dispatchedCount++;
                }
            }
            if ($dispatchedCount > 0) {
                ActivityLogger::log('email', 'hcm', $payroll, "Menjadwalkan pengiriman {$dispatchedCount} slip gaji ke email karyawan dengan jeda {$delayMinutes} menit.");
            }
        }

        return redirect()->back()->with('success', "Batch {$payroll->period_code} berhasil dicairkan dan berstatus PAID_COMPLETED.");
    }

    /**
     * Hapus batch draf penggajian.
     */
    public function destroy(HcmPayroll $payroll): RedirectResponse
    {
        Gate::authorize('hcm.manage-compensation');

        if ($payroll->status === 'PAID_COMPLETED') {
            return redirect()->back()->withErrors(['status' => 'Batch yang telah dicairkan (PAID_COMPLETED) tidak dapat dihapus.']);
        }

        $code = $payroll->period_code;
        $payroll->items()->delete();
        $payroll->delete();

        ActivityLogger::log('delete', 'hcm', null, "Menghapus batch penggajian {$code}");

        return redirect()->route('hcm.payroll.index')->with('success', "Batch penggajian {$code} berhasil dihapus.");
    }

    /**
     * Ekspor lembar rekapitulasi penggajian multi-sheet Excel (Rekap Eksekutif & Transfer BRI).
     */
    public function exportBatchExcel(HcmPayroll $payroll)
    {
        Gate::authorize('hcm.manage-compensation');

        $payroll->load([
            'creator:id,name',
            'hcmSigner:id,name',
            'financeSigner:id,name',
            'items.employee:id,employee_code,name,nickname,department,division,employment_status,bank_name,bank_account_no,bank_account_name',
        ]);

        $fileName = 'Rekap-Payroll-' . $payroll->period_code . '-' . now()->format('Ymd') . '.xlsx';

        ActivityLogger::log('export', 'hcm', $payroll, "Ekspor Excel batch payroll {$payroll->period_code}");

        return Excel::download(
            new HcmPayrollBatchExport($payroll, Auth::user()?->name),
            $fileName
        );
    }
}
