<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmCompensation;
use App\Models\Hcm\HcmCompensationHistory;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmMasterOption;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HcmCompensationController extends Controller
{
    /**
     * Modul 4: Kompensasi & Riwayat Honor/Gaji (Sesuai Blueprint Excel Sheet Database Baris 21-26).
     */
    public function index(Request $request): Response
    {
        Gate::authorize('hcm.manage-compensation');

        $search = $request->query('search', '');
        $statusFilter = $request->query('status', 'all');
        $employmentStatusFilter = $request->query('employment_status', 'all');
        $entityFilter = $request->query('entity', 'all');

        $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search);

        $query = HcmCompensation::with([
            'employee:id,employee_code,name,nickname,department,division,legal_entity,employment_status,is_active,photo_url,join_date,original_join_date',
            'histories' => fn($q) => $q->orderBy('effective_date', 'desc')->take(5),
        ])
            ->when($escapedSearch, function ($q, $term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('contract_number', 'like', "%{$term}%")
                        ->orWhere('duration_text', 'like', "%{$term}%")
                        ->orWhereHas('employee', function ($eq) use ($term) {
                            $eq->where('name', 'like', "%{$term}%")
                                ->orWhere('nickname', 'like', "%{$term}%")
                                ->orWhere('employee_code', 'like', "%{$term}%")
                                ->orWhere('department', 'like', "%{$term}%")
                                ->orWhere('division', 'like', "%{$term}%");
                        });
                });
            })
            ->when($statusFilter !== 'all', function ($q) use ($statusFilter) {
                $q->where('salary_status', $statusFilter);
            })
            ->when($employmentStatusFilter !== 'all', function ($q) use ($employmentStatusFilter) {
                if ($employmentStatusFilter === 'Tetap' || str_contains($employmentStatusFilter, 'Tetap')) {
                    $q->where('employment_status', 'like', '%Tetap%');
                } elseif ($employmentStatusFilter === 'PKWT') {
                    $q->where('employment_status', 'like', '%PKWT%');
                } else {
                    $q->where('employment_status', $employmentStatusFilter);
                }
            })
            ->when($entityFilter !== 'all', fn($q) => $q->where('legal_entity', $entityFilter))
            ->orderBy('id', 'desc');

        $compensations = $query->paginate(15)->withQueryString();

        $all = HcmCompensation::query();

        $metrics = [
            'total_employees' => (clone $all)->count(),
            'total_initial' => (float) (clone $all)->sum('initial_salary'),
            'total_current' => (float) (clone $all)->sum('current_salary'),
            'total_increment' => (float) ((clone $all)->sum('current_salary') - (clone $all)->sum('initial_salary')),
            'avg_current' => (float) round((clone $all)->avg('current_salary') ?? 0, 2),
            'status_telah_berlaku' => (clone $all)->where('salary_status', 'like', '%Telah berlaku%')->orWhere('salary_status', 'like', '%Aktif%')->count(),
            'status_sedang_diajukan' => (clone $all)->where(function ($q) {
                $q->where('salary_status', 'like', '%Sedang Diajukan%')
                  ->orWhere('decision_status', 'Sedang Diajukan');
            })->count(),
            'evaluations_pending' => (clone $all)->where('decision_status', 'Sedang Diajukan')->count(),
            'evaluations_approved' => (clone $all)->where('decision_status', 'Sudah Disetujui / ACC')->count(),
        ];

        // Ambil potongan gaji bulan aktif berjalan
        $currentMonth = \Carbon\Carbon::now()->format('Y-m');
        $currentMonthDeductions = \App\Models\Hcm\HcmSalaryDeduction::with(['employee:id,name,employee_code,department,division'])
            ->where('effective_payroll_month', $currentMonth)
            ->get();

        $dropdowns = [
            'employees' => HcmEmployee::where('is_active', true)
                ->select('id', 'name', 'nickname', 'employee_code', 'department', 'division', 'legal_entity', 'employment_status')
                ->orderBy('name')
                ->get(),
            'employment_statuses' => HcmMasterOption::getOptions('status_ketenagakerjaan'),
            'legal_entities' => HcmMasterOption::getOptions('entitas_cv'),
            'salary_statuses' => HcmMasterOption::getOptions('status_pengajuan_honor'),
            'deduction_categories' => HcmMasterOption::getOptions('kategori_potongan_gaji'),
            'decision_statuses' => [
                'Sedang Diajukan',
                'Sudah Disetujui / ACC',
                'Ditunda',
                'Tidak Naik',
            ],
        ];

        return Inertia::render('Hcm/Compensations/Index', [
            'compensations' => $compensations,
            'currentMonthDeductions' => $currentMonthDeductions,
            'currentMonth' => $currentMonth,
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
                'employment_status' => $employmentStatusFilter,
                'entity' => $entityFilter,
            ],
            'metrics' => $metrics,
            'dropdowns' => $dropdowns,
            'entities' => HcmCompensation::whereNotNull('legal_entity')->distinct()->orderBy('legal_entity')->pluck('legal_entity'),
            'statuses' => HcmCompensation::whereNotNull('salary_status')->distinct()->orderBy('salary_status')->pluck('salary_status'),
        ]);
    }

    /**
     * Simpan data kompensasi baru (CRUD Tambah Kompensasi).
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-compensation');

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:hcm_employees,id', 'unique:hcm_compensations,employee_id'],
            'employment_status' => ['required', 'string', 'max:50'],
            'legal_entity' => ['required', 'string', 'max:100'],
            'contract_number' => ['nullable', 'string', 'max:100'],
            'duration_text' => ['nullable', 'string', 'max:50'],
            'trainee_duration_months' => ['nullable', 'integer', 'min:0'],
            'evaluation_cycle_months' => ['required', 'integer', 'min:1'],
            'initial_salary' => ['required', 'numeric', 'min:0'],
            'current_salary' => ['required', 'numeric', 'min:0'],
            'salary_status' => ['required', 'string', 'max:100'],
            'salary_increment_count' => ['nullable', 'integer', 'min:0'],
            'increment_1_amount' => ['nullable', 'numeric', 'min:0'],
            'increment_2_amount' => ['nullable', 'numeric', 'min:0'],
            'increment_3_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $compensation = HcmCompensation::create($validated);

        $employee = HcmEmployee::find($validated['employee_id']);
        ActivityLogger::log('create', 'hcm', $compensation, "Mencatat kompensasi & honor karyawan {$employee?->name} ({$employee?->employee_code})");

        return redirect()->back()->with('success', "Data kompensasi untuk {$employee?->name} berhasil ditambahkan.");
    }

    /**
     * Perbarui data kompensasi & honor.
     */
    public function update(Request $request, HcmCompensation $compensation): RedirectResponse
    {
        Gate::authorize('hcm.manage-compensation');

        $validated = $request->validate([
            'employment_status' => ['sometimes', 'required', 'string', 'max:50'],
            'legal_entity' => ['sometimes', 'required', 'string', 'max:100'],
            'contract_number' => ['nullable', 'string', 'max:100'],
            'duration_text' => ['nullable', 'string', 'max:50'],
            'trainee_duration_months' => ['nullable', 'integer', 'min:0'],
            'evaluation_cycle_months' => ['sometimes', 'required', 'integer', 'min:1'],
            'initial_salary' => ['sometimes', 'required', 'numeric', 'min:0'],
            'current_salary' => ['sometimes', 'required', 'numeric', 'min:0'],
            'salary_status' => ['sometimes', 'required', 'string', 'max:100'],
            'salary_increment_count' => ['nullable', 'integer', 'min:0'],
            'increment_1_amount' => ['nullable', 'numeric', 'min:0'],
            'increment_2_amount' => ['nullable', 'numeric', 'min:0'],
            'increment_3_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $compensation->update($validated);

        ActivityLogger::log('update', 'hcm', $compensation, "Memperbarui data kompensasi #{$compensation->id} {$compensation->employee?->name}");

        return redirect()->back()->with('success', "Data kompensasi berhasil diperbarui.");
    }

    /**
     * Catat kenaikan honor / penyesuaian gaji berkala (Increment Audit Trail).
     */
    public function storeIncrement(Request $request, HcmCompensation $compensation): RedirectResponse
    {
        Gate::authorize('hcm.manage-compensation');

        $validated = $request->validate([
            'new_salary' => ['required', 'numeric', 'min:0'],
            'effective_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $previousSalary = (float) $compensation->current_salary;
        $newSalary = (float) $validated['new_salary'];
        $incrementAmount = $newSalary - $previousSalary;

        DB::transaction(function () use ($compensation, $previousSalary, $newSalary, $incrementAmount, $validated) {
            $newCount = ($compensation->salary_increment_count ?? 0) + 1;

            $updateData = [
                'current_salary' => $newSalary,
                'salary_increment_count' => $newCount,
                'salary_status' => 'Telah berlaku',
            ];

            if ($newCount === 1) {
                $updateData['increment_1_amount'] = $incrementAmount;
            } elseif ($newCount === 2) {
                $updateData['increment_2_amount'] = $incrementAmount;
            } else {
                $updateData['increment_3_amount'] = $incrementAmount;
            }

            $compensation->update($updateData);

            HcmCompensationHistory::create([
                'compensation_id' => $compensation->id,
                'employee_id' => $compensation->employee_id,
                'previous_salary' => $previousSalary,
                'new_salary' => $newSalary,
                'increment_amount' => $incrementAmount,
                'effective_date' => $validated['effective_date'],
                'reason' => $validated['reason'] ?? 'Kenaikan Berkala / Evaluasi Performa',
                'approved_by' => Auth::id(),
            ]);
        });

        ActivityLogger::log(
            'update',
            'hcm',
            $compensation,
            "Kenaikan gaji {$compensation->employee?->name}: Rp " . number_format($previousSalary, 0, ',', '.') . " -> Rp " . number_format($newSalary, 0, ',', '.')
        );

        return redirect()->back()->with('success', "Kenaikan honor untuk {$compensation->employee?->name} berhasil dicatat.");
    }

    /**
     * Keputusan Evaluasi Gaji / Siklus Kenaikan (ACC, Ditunda, Tidak Naik, Sedang Diajukan).
     */
    public function updateDecision(Request $request, HcmCompensation $compensation): RedirectResponse
    {
        Gate::authorize('hcm.manage-compensation');

        $validated = $request->validate([
            'decision_status' => ['required', 'string', 'in:Sedang Diajukan,Sudah Disetujui / ACC,Ditunda,Tidak Naik'],
            'planned_increment' => ['nullable', 'numeric', 'min:0'],
            'effective_date' => ['nullable', 'date'],
            'custom_milestone_date' => ['nullable', 'date'],
            'decision_notes' => ['nullable', 'string', 'max:500'],
            'apply_immediately' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($compensation, $validated) {
            $updateData = [
                'decision_status' => $validated['decision_status'],
                'planned_increment' => $validated['planned_increment'] ?? 0,
                'effective_date' => $validated['effective_date'] ?? null,
                'custom_milestone_date' => $validated['custom_milestone_date'] ?? null,
                'decision_notes' => $validated['decision_notes'] ?? null,
            ];

            // Jika status ACC dan dicentang langsung terapkan ke gaji berjalan
            if ($validated['decision_status'] === 'Sudah Disetujui / ACC' && !empty($validated['apply_immediately']) && !empty($validated['planned_increment']) && (float) $validated['planned_increment'] > 0) {
                $previousSalary = (float) $compensation->current_salary;
                $incrementAmount = (float) $validated['planned_increment'];
                $newSalary = $previousSalary + $incrementAmount;
                $newCount = ($compensation->salary_increment_count ?? 0) + 1;

                $updateData['current_salary'] = $newSalary;
                $updateData['salary_increment_count'] = $newCount;
                $updateData['salary_status'] = 'Telah berlaku';

                if ($newCount === 1) {
                    $updateData['increment_1_amount'] = $incrementAmount;
                } elseif ($newCount === 2) {
                    $updateData['increment_2_amount'] = $incrementAmount;
                } else {
                    $updateData['increment_3_amount'] = $incrementAmount;
                }

                HcmCompensationHistory::create([
                    'compensation_id' => $compensation->id,
                    'employee_id' => $compensation->employee_id,
                    'previous_salary' => $previousSalary,
                    'new_salary' => $newSalary,
                    'increment_amount' => $incrementAmount,
                    'effective_date' => $validated['effective_date'] ?? now()->toDateString(),
                    'reason' => $validated['decision_notes'] ?? 'Kenaikan Gaji dari Keputusan Evaluasi Siklus Berkala',
                    'approved_by' => Auth::id(),
                ]);
            }

            $compensation->update($updateData);
        });

        $empName = $compensation->employee?->name ?? 'Karyawan';
        ActivityLogger::log(
            'update',
            'hcm',
            $compensation,
            "Memperbarui keputusan evaluasi gaji {$empName}: Status [{$validated['decision_status']}]"
        );

        return redirect()->back()->with('success', "Keputusan evaluasi kenaikan honor {$empName} berhasil disimpan.");
    }

    /**
     * Hapus data kompensasi karyawan.
     */
    public function destroy(HcmCompensation $compensation): RedirectResponse
    {
        Gate::authorize('hcm.manage-compensation');

        $empName = $compensation->employee?->name ?? 'Karyawan';
        $compensation->histories()->delete();
        $compensation->delete();

        ActivityLogger::log('delete', 'hcm', null, "Menghapus data kompensasi {$empName}");

        return redirect()->back()->with('success', "Data kompensasi {$empName} berhasil dihapus.");
    }
}
