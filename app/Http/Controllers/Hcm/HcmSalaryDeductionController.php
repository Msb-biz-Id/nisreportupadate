<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmCompensation;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmMasterOption;
use App\Models\Hcm\HcmSalaryDeduction;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HcmSalaryDeductionController extends Controller
{
    /**
     * Tampilkan data potongan dan penyesuaian gaji bulanan.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('hcm.manage-compensation');

        $selectedMonth = $request->input('month', Carbon::now()->format('Y-m'));
        $search = $request->input('search', '');
        $category = $request->input('category', 'all');

        $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search);

        $query = HcmSalaryDeduction::with([
            'employee:id,employee_code,name,nickname,department,division,legal_entity,photo_url',
            'creator:id,name',
        ])
            ->where('effective_payroll_month', $selectedMonth)
            ->when($escapedSearch, function ($q, $term) {
                $q->whereHas('employee', function ($eq) use ($term) {
                    $eq->where('name', 'like', "%{$term}%")
                        ->orWhere('employee_code', 'like', "%{$term}%")
                        ->orWhere('department', 'like', "%{$term}%")
                        ->orWhere('division', 'like', "%{$term}%");
                })->orWhere('notes', 'like', "%{$term}%");
            })
            ->when($category !== 'all', fn($q) => $q->where('deduction_category', $category))
            ->orderBy('id', 'desc');

        $deductions = $query->paginate(20)->withQueryString();

        // Rekapitulasi metrik bulan berjalan
        $monthQuery = HcmSalaryDeduction::where('effective_payroll_month', $selectedMonth);
        $metrics = [
            'total_deductions_count' => (clone $monthQuery)->count(),
            'total_deductions_amount' => (float) (clone $monthQuery)->sum('deduction_amount'),
            'total_affected_employees' => (clone $monthQuery)->distinct('employee_id')->count('employee_id'),
            'selected_month' => $selectedMonth,
        ];

        // Dropdown dinamis dari database (Zero Hardcode)
        $categories = HcmMasterOption::getOptions('kategori_potongan_gaji') ?: [
            'Pelanggaran (Disciplinary Penalty)',
            'Kelebihan Pengambilan Cuti (Leave Exceed)',
            'Cuti Khusus Berjenjang (Maternity Leave)',
        ];

        $employees = HcmEmployee::where('is_active', true)
            ->with(['compensation:id,employee_id,current_salary'])
            ->select('id', 'employee_code', 'name', 'nickname', 'department', 'division', 'legal_entity')
            ->orderBy('name')
            ->get();

        return Inertia::render('Hcm/SalaryDeductions/Index', [
            'deductions' => $deductions,
            'filters' => [
                'month' => $selectedMonth,
                'search' => $search,
                'category' => $category,
            ],
            'metrics' => $metrics,
            'categories' => $categories,
            'employees' => $employees,
        ]);
    }

    /**
     * Catat potongan gaji baru untuk karyawan.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-compensation');

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:hcm_employees,id'],
            'effective_payroll_month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'deduction_category' => ['required', 'string', 'max:100'],
            'calculation_type' => ['required', 'in:fixed,percent'],
            'percentage_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'deduction_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'in:APPROVED,APPLIED,PENDING,CANCELLED'],
        ]);

        $compensation = HcmCompensation::where('employee_id', $validated['employee_id'])->first();
        $baseSalary = (float) ($compensation?->current_salary ?? 0);

        if ($validated['calculation_type'] === 'percent') {
            $percentage = (float) ($validated['percentage_rate'] ?? 0);
            $deductionAmount = round(($baseSalary * $percentage) / 100, 2);
        } else {
            $percentage = null;
            $deductionAmount = (float) ($validated['deduction_amount'] ?? 0);
        }

        $netSalary = max(0, $baseSalary - $deductionAmount);

        $deduction = HcmSalaryDeduction::create([
            'employee_id' => $validated['employee_id'],
            'effective_payroll_month' => $validated['effective_payroll_month'],
            'deduction_category' => $validated['deduction_category'],
            'calculation_type' => $validated['calculation_type'],
            'percentage_rate' => $percentage,
            'deduction_amount' => $deductionAmount,
            'base_salary_snapshot' => $baseSalary,
            'net_salary_snapshot' => $netSalary,
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'] ?? 'APPROVED',
            'created_by' => Auth::id(),
        ]);

        $employee = HcmEmployee::find($validated['employee_id']);
        ActivityLogger::log(
            'create',
            'hcm',
            $deduction,
            "Mencatat potongan gaji Rp " . number_format($deductionAmount, 0, ',', '.') . " ({$validated['deduction_category']}) untuk {$employee?->name} bulan {$validated['effective_payroll_month']}"
        );

        return redirect()->back()->with('success', "Pemotongan gaji untuk {$employee?->name} berhasil dicatat.");
    }

    /**
     * Perbarui data potongan gaji.
     */
    public function update(Request $request, HcmSalaryDeduction $salaryDeduction): RedirectResponse
    {
        Gate::authorize('hcm.manage-compensation');

        $validated = $request->validate([
            'effective_payroll_month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'deduction_category' => ['required', 'string', 'max:100'],
            'calculation_type' => ['required', 'in:fixed,percent'],
            'percentage_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'deduction_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:APPROVED,APPLIED,PENDING,CANCELLED'],
        ]);

        $compensation = HcmCompensation::where('employee_id', $salaryDeduction->employee_id)->first();
        $baseSalary = (float) ($compensation?->current_salary ?? $salaryDeduction->base_salary_snapshot);

        if ($validated['calculation_type'] === 'percent') {
            $percentage = (float) ($validated['percentage_rate'] ?? 0);
            $deductionAmount = round(($baseSalary * $percentage) / 100, 2);
        } else {
            $percentage = null;
            $deductionAmount = (float) ($validated['deduction_amount'] ?? 0);
        }

        $netSalary = max(0, $baseSalary - $deductionAmount);

        $salaryDeduction->update([
            'effective_payroll_month' => $validated['effective_payroll_month'],
            'deduction_category' => $validated['deduction_category'],
            'calculation_type' => $validated['calculation_type'],
            'percentage_rate' => $percentage,
            'deduction_amount' => $deductionAmount,
            'base_salary_snapshot' => $baseSalary,
            'net_salary_snapshot' => $netSalary,
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'],
        ]);

        ActivityLogger::log(
            'update',
            'hcm',
            $salaryDeduction,
            "Memperbarui potongan gaji {$salaryDeduction->employee?->name} bulan {$validated['effective_payroll_month']}"
        );

        return redirect()->back()->with('success', "Pemotongan gaji berhasil diperbarui.");
    }

    /**
     * Hapus record potongan gaji.
     */
    public function destroy(HcmSalaryDeduction $salaryDeduction): RedirectResponse
    {
        Gate::authorize('hcm.manage-compensation');

        $empName = $salaryDeduction->employee?->name ?? 'Karyawan';
        $month = $salaryDeduction->effective_payroll_month;
        $amount = number_format($salaryDeduction->deduction_amount, 0, ',', '.');

        $salaryDeduction->delete();

        ActivityLogger::log('delete', 'hcm', null, "Menghapus potongan gaji {$empName} senilai Rp {$amount} bulan {$month}");

        return redirect()->back()->with('success', "Potongan gaji {$empName} berhasil dihapus.");
    }
}
