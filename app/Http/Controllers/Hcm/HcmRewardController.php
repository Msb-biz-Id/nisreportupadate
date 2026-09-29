<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmEmployeeReward;
use App\Models\Hcm\HcmMasterOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HcmRewardController extends Controller
{
    /**
     * Tampilkan modul Rekapitulasi Penyaluran Reward & Penghargaan Karyawan.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('hcm.manage-rewards');

        $statusFilter = $request->query('status', 'all');
        $yearFilter = $request->query('year', 'all');
        $search = $request->query('search', '');

        $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search);

        $rewards = HcmEmployeeReward::with([
            'employee:id,employee_code,name,nickname,department,position',
            'creator:id,name',
        ])
            ->when($escapedSearch, function ($q, $term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('reward_name', 'like', "%{$term}%")
                        ->orWhereHas('employee', function ($eq) use ($term) {
                            $eq->where('name', 'like', "%{$term}%")
                               ->orWhere('employee_code', 'like', "%{$term}%");
                        });
                });
            })
            ->when($statusFilter !== 'all', fn ($q) => $q->where('distribution_status', $statusFilter))
            ->when($yearFilter !== 'all', fn ($q) => $q->where('reward_year', (int) $yearFilter))
            ->orderBy('reward_year', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(12)
            ->withQueryString();

        // Metrik Ringkasan Penyaluran Reward
        $metrics = [
            'total_rewards' => HcmEmployeeReward::count(),
            'delivered' => HcmEmployeeReward::where('distribution_status', 'Sudah Diterima (Serah Terima Langsung)')->count(),
            'transferred' => HcmEmployeeReward::where('distribution_status', 'Sudah Ditransfer')->count(),
            'pending' => HcmEmployeeReward::whereIn('distribution_status', ['Belum Diterima', 'Tertunda / Pending'])->count(),
            'total_budget' => HcmEmployeeReward::sum('budget_amount'),
        ];

        // Daftar Karyawan Aktif untuk SearchableSelect
        $employees = HcmEmployee::where('is_active', true)
            ->select('id', 'employee_code', 'name', 'department', 'position')
            ->orderBy('name')
            ->get();

        // Opsi Status Distribusi
        $distributionStatuses = [
            'Belum Diterima',
            'Sudah Diterima (Serah Terima Langsung)',
            'Sudah Ditransfer',
            'Tertunda / Pending',
            'Dibatalkan',
        ];

        return Inertia::render('Hcm/Rewards/Index', [
            'rewards' => $rewards,
            'filters' => [
                'status' => $statusFilter,
                'year' => $yearFilter,
                'search' => $search,
            ],
            'metrics' => $metrics,
            'employees' => $employees,
            'distributionStatuses' => $distributionStatuses,
        ]);
    }

    /**
     * Simpan data apresiasi / reward baru.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-rewards');

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:hcm_employees,id'],
            'reward_name' => ['required', 'string', 'max:150'],
            'reward_year' => ['required', 'integer', 'between:2020,2035'],
            'distribution_status' => ['required', 'string', 'max:50'],
            'received_date' => ['nullable', 'date'],
            'document_status' => ['nullable', 'string', 'max:50'],
            'budget_amount' => ['nullable', 'numeric', 'min:0'],
            'proof_url' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $employee = HcmEmployee::find($validated['employee_id']);

        HcmEmployeeReward::create([
            'employee_id' => $validated['employee_id'],
            'position' => $employee?->position,
            'reward_name' => $validated['reward_name'],
            'reward_year' => $validated['reward_year'],
            'distribution_status' => $validated['distribution_status'],
            'received_date' => $validated['received_date'] ?? null,
            'document_status' => $validated['document_status'] ?? 'Berita Acara Terlampir',
            'budget_amount' => $validated['budget_amount'] ?? 0,
            'proof_url' => $validated['proof_url'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', "Penghargaan '{$validated['reward_name']}' untuk {$employee->name} berhasil dicatat.");
    }

    /**
     * Perbarui data penghargaan.
     */
    public function update(Request $request, HcmEmployeeReward $reward): RedirectResponse
    {
        Gate::authorize('hcm.manage-rewards');

        $validated = $request->validate([
            'reward_name' => ['required', 'string', 'max:150'],
            'reward_year' => ['required', 'integer', 'between:2020,2035'],
            'distribution_status' => ['required', 'string', 'max:50'],
            'received_date' => ['nullable', 'date'],
            'document_status' => ['nullable', 'string', 'max:50'],
            'budget_amount' => ['nullable', 'numeric', 'min:0'],
            'proof_url' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $reward->update([
            'reward_name' => $validated['reward_name'],
            'reward_year' => $validated['reward_year'],
            'distribution_status' => $validated['distribution_status'],
            'received_date' => $validated['received_date'] ?? null,
            'document_status' => $validated['document_status'] ?? $reward->document_status,
            'budget_amount' => $validated['budget_amount'] ?? $reward->budget_amount,
            'proof_url' => $validated['proof_url'] ?? $reward->proof_url,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->back()->with('success', "Data penghargaan '{$reward->reward_name}' berhasil diperbarui.");
    }

    /**
     * Perbarui status penyaluran reward secara cepat.
     */
    public function updateStatus(Request $request, HcmEmployeeReward $reward): RedirectResponse
    {
        Gate::authorize('hcm.manage-rewards');

        $validated = $request->validate([
            'distribution_status' => ['required', 'string', 'max:50'],
        ]);

        $reward->update([
            'distribution_status' => $validated['distribution_status'],
            'received_date' => $validated['distribution_status'] === 'Belum Diterima' ? null : ($reward->received_date ?? now()->toDateString()),
        ]);

        return redirect()->back()->with('success', 'Status penyaluran reward berhasil diperbarui.');
    }

    /**
     * Hapus data penghargaan.
     */
    public function destroy(HcmEmployeeReward $reward): RedirectResponse
    {
        Gate::authorize('hcm.manage-rewards');

        $name = $reward->reward_name;
        $reward->delete();

        return redirect()->back()->with('success', "Data penghargaan '{$name}' berhasil dihapus.");
    }
}
