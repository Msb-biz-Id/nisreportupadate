<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmContract;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmMasterOption;
use App\Services\ActivityLogger;
use App\Services\GoogleDriveSyncService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HcmContractController extends Controller
{
    /**
     * Tampilkan halaman utama manajemen Kontrak & PKWT.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('hcm.manage-contracts');

        $search = $request->input('search');
        $legalEntity = $request->input('legal_entity');
        $reviewStatus = $request->input('review_status');
        $employmentStatus = $request->input('employment_status');

        $query = HcmContract::with(['employee:id,name,nickname,employee_code,department,position,legal_entity,is_active,photo_url'])
            ->orderByDesc('start_date');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('contract_number', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($eq) use ($search) {
                        $eq->where('name', 'like', "%{$search}%")
                            ->orWhere('nickname', 'like', "%{$search}%")
                            ->orWhere('employee_code', 'like', "%{$search}%")
                            ->orWhere('nik_ktp', 'like', "%{$search}%");
                    });
            });
        }

        if ($legalEntity && $legalEntity !== 'all') {
            $query->where('legal_entity', $legalEntity);
        }

        if ($employmentStatus && $employmentStatus !== 'all') {
            if ($employmentStatus === 'Tetap (PKWTT)' || str_contains($employmentStatus, 'Tetap')) {
                $query->where(function ($q) {
                    $q->where('employment_status', 'like', '%Tetap%')
                      ->orWhereNull('end_date');
                });
            } else {
                $query->where('employment_status', $employmentStatus);
            }
        }

        if ($reviewStatus && $reviewStatus !== 'all') {
            if ($reviewStatus === 'H-14' || str_contains($reviewStatus, 'H-14') || str_contains($reviewStatus, 'Kritis')) {
                $query->where(function ($q) {
                    $q->where('review_status', 'like', '%H-14%')
                      ->orWhere(function ($dq) {
                          $dq->whereNotNull('end_date')
                             ->whereBetween('end_date', [Carbon::today(), Carbon::today()->addDays(14)]);
                      });
                });
            } elseif ($reviewStatus === 'H-30' || str_contains($reviewStatus, 'H-30') || str_contains($reviewStatus, 'Review')) {
                $query->where(function ($q) {
                    $q->where('review_status', 'like', '%H-30%')
                      ->orWhere(function ($dq) {
                          $dq->whereNotNull('end_date')
                             ->whereBetween('end_date', [Carbon::today(), Carbon::today()->addDays(30)]);
                      });
                });
            } elseif ($reviewStatus === 'H-60' || str_contains($reviewStatus, 'H-60') || str_contains($reviewStatus, 'Evaluasi')) {
                $query->where('review_status', 'like', '%H-60%');
            } elseif ($reviewStatus === 'Aktif' || str_contains($reviewStatus, 'Aktif')) {
                $query->where('review_status', 'like', '%Aktif%');
            } elseif ($reviewStatus === 'Tetap (PKWTT)' || str_contains($reviewStatus, 'Tetap')) {
                $query->where(function ($q) {
                    $q->where('employment_status', 'like', '%Tetap%')
                      ->orWhereNull('end_date');
                });
            } else {
                $query->where('review_status', 'like', "%{$reviewStatus}%");
            }
        }

        $contracts = $query->paginate(15)->withQueryString();

        // Metrik Ringkasan Kontrak
        $today = Carbon::today();
        $in30Days = Carbon::today()->addDays(30);
        $in14Days = Carbon::today()->addDays(14);

        $metrics = [
            'total_contracts' => HcmContract::count(),
            'active_contracts' => HcmContract::where('review_status', 'like', '%Aktif%')->count(),
            'expiring_30_days' => HcmContract::whereNotNull('end_date')
                ->whereBetween('end_date', [$today, $in30Days])
                ->count(),
            'expiring_14_days' => HcmContract::whereNotNull('end_date')
                ->whereBetween('end_date', [$today, $in14Days])
                ->count(),
            'expired' => HcmContract::whereNotNull('end_date')
                ->where('end_date', '<', $today)
                ->count(),
            'permanent' => HcmContract::whereNull('end_date')
                ->orWhere('employment_status', 'like', '%Tetap%')
                ->count(),
        ];

        $dropdowns = [
            'legal_entities' => HcmMasterOption::getOptions('legal_entities'),
            'employment_statuses' => HcmMasterOption::getOptions('employment_statuses'),
            'positions' => HcmMasterOption::getOptions('positions'),
            'review_statuses' => [
                'Aktif',
                'Mendekati H-60',
                'Wajib Review H-30',
                'Masa Tenggang H-14',
                'Perpanjang',
                'Selesai Kontrak',
                'Diputus',
            ],
            'employees' => HcmEmployee::where('is_active', true)
                ->select('id', 'name', 'nickname', 'employee_code', 'department', 'position', 'legal_entity')
                ->orderBy('name')
                ->get(),
        ];

        return Inertia::render('Hcm/Contracts/Index', [
            'contracts' => $contracts,
            'filters' => [
                'search' => $search,
                'legal_entity' => $legalEntity,
                'review_status' => $reviewStatus,
                'employment_status' => $employmentStatus,
            ],
            'metrics' => $metrics,
            'dropdowns' => $dropdowns,
        ]);
    }

    /**
     * Simpan data kontrak baru / perpanjangan.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-contracts');

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:hcm_employees,id'],
            'contract_number' => ['required', 'string', 'max:100', 'unique:hcm_contracts,contract_number'],
            'contract_sequence' => ['required', 'integer', 'min:1'],
            'employment_status' => ['required', 'string', 'max:50'],
            'position' => ['required', 'string', 'max:100'],
            'legal_entity' => ['required', 'string', 'max:100'],
            'duration_text' => ['required', 'string', 'max:50'],
            'trainee_start_month' => ['nullable', 'string', 'max:50'],
            'trainee_end_month' => ['nullable', 'string', 'max:50'],
            'contract_month' => ['nullable', 'string', 'max:50'],
            'start_year' => ['nullable', 'integer'],
            'end_year' => ['nullable', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'review_status' => ['required', 'string', 'max:100'],
            'file_contract' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'], // Max 10MB
            'notes' => ['nullable', 'string'],
        ]);

        $fileUrl = null;
        if ($request->hasFile('file_contract')) {
            $uploaded = GoogleDriveSyncService::uploadFile(
                $request->file('file_contract'),
                GoogleDriveSyncService::FOLDER_CONTRACTS
            );
            $fileUrl = $uploaded['url'];
        }

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = !empty($validated['end_date']) ? Carbon::parse($validated['end_date']) : null;

        $contract = HcmContract::create([
            'employee_id' => $validated['employee_id'],
            'contract_number' => $validated['contract_number'],
            'contract_sequence' => $validated['contract_sequence'],
            'employment_status' => $validated['employment_status'],
            'position' => $validated['position'],
            'legal_entity' => $validated['legal_entity'],
            'duration_text' => $validated['duration_text'],
            'trainee_start_month' => $validated['trainee_start_month'] ?? null,
            'trainee_end_month' => $validated['trainee_end_month'] ?? null,
            'start_year' => $validated['start_year'] ?? $startDate->year,
            'end_year' => $validated['end_year'] ?? ($endDate ? $endDate->year : null),
            'contract_month' => $validated['contract_month'] ?? $startDate->locale('id')->isoFormat('MMMM'),
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'review_status' => $validated['review_status'],
            'file_contract_url' => $fileUrl,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Perbarui employment_status & legal_entity di master employee jika merupakan kontrak terbaru
        $employee = HcmEmployee::find($validated['employee_id']);
        if ($employee) {
            $employee->update([
                'employment_status' => $validated['employment_status'],
                'legal_entity' => $validated['legal_entity'],
                'position' => $validated['position'],
            ]);
        }

        ActivityLogger::log('create', 'hcm', $contract, "Penerbitan kontrak PKWT baru: {$contract->contract_number} untuk {$employee?->name}");

        return redirect()->back()->with('success', "Kontrak nomor '{$contract->contract_number}' berhasil diterbitkan.");
    }

    /**
     * Perbarui data kontrak.
     */
    public function update(Request $request, HcmContract $contract): RedirectResponse
    {
        Gate::authorize('hcm.manage-contracts');

        $validated = $request->validate([
            'contract_number' => ['required', 'string', 'max:100', 'unique:hcm_contracts,contract_number,' . $contract->id],
            'contract_sequence' => ['required', 'integer', 'min:1'],
            'employment_status' => ['required', 'string', 'max:50'],
            'position' => ['required', 'string', 'max:100'],
            'legal_entity' => ['required', 'string', 'max:100'],
            'duration_text' => ['required', 'string', 'max:50'],
            'trainee_start_month' => ['nullable', 'string', 'max:50'],
            'trainee_end_month' => ['nullable', 'string', 'max:50'],
            'contract_month' => ['nullable', 'string', 'max:50'],
            'start_year' => ['nullable', 'integer'],
            'end_year' => ['nullable', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'review_status' => ['required', 'string', 'max:100'],
            'file_contract' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'notes' => ['nullable', 'string'],
        ]);

        $fileUrl = $contract->file_contract_url;
        if ($request->hasFile('file_contract')) {
            if ($fileUrl) {
                GoogleDriveSyncService::deleteFile($fileUrl);
            }
            $uploaded = GoogleDriveSyncService::uploadFile(
                $request->file('file_contract'),
                GoogleDriveSyncService::FOLDER_CONTRACTS
            );
            $fileUrl = $uploaded['url'];
        }

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = !empty($validated['end_date']) ? Carbon::parse($validated['end_date']) : null;

        $contract->update([
            'contract_number' => $validated['contract_number'],
            'contract_sequence' => $validated['contract_sequence'],
            'employment_status' => $validated['employment_status'],
            'position' => $validated['position'],
            'legal_entity' => $validated['legal_entity'],
            'duration_text' => $validated['duration_text'],
            'trainee_start_month' => $validated['trainee_start_month'] ?? null,
            'trainee_end_month' => $validated['trainee_end_month'] ?? null,
            'start_year' => $validated['start_year'] ?? $startDate->year,
            'end_year' => $validated['end_year'] ?? ($endDate ? $endDate->year : null),
            'contract_month' => $validated['contract_month'] ?? $startDate->locale('id')->isoFormat('MMMM'),
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'review_status' => $validated['review_status'],
            'file_contract_url' => $fileUrl,
            'notes' => $validated['notes'] ?? null,
        ]);

        ActivityLogger::log('update', 'hcm', $contract, "Memperbarui naskah kontrak PKWT: {$contract->contract_number}");

        return redirect()->back()->with('success', "Data kontrak '{$contract->contract_number}' berhasil diperbarui.");
    }

    /**
     * Upload berkas digital naskah kontrak (Quick Upload).
     */
    public function uploadFile(Request $request, HcmContract $contract): RedirectResponse
    {
        Gate::authorize('hcm.manage-contracts');

        $request->validate([
            'file_contract' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);

        if ($contract->file_contract_url) {
            GoogleDriveSyncService::deleteFile($contract->file_contract_url);
        }

        $uploaded = GoogleDriveSyncService::uploadFile(
            $request->file('file_contract'),
            GoogleDriveSyncService::FOLDER_CONTRACTS
        );

        $contract->update([
            'file_contract_url' => $uploaded['url'],
        ]);

        ActivityLogger::log('update', 'hcm', $contract, "Upload berkas digital kontrak: {$contract->contract_number}");

        return redirect()->back()->with('success', "Berkas naskah kontrak berhasil diunggah.");
    }

    /**
     * Hapus record kontrak.
     */
    public function destroy(HcmContract $contract): RedirectResponse
    {
        Gate::authorize('hcm.manage-contracts');

        $num = $contract->contract_number;
        if ($contract->file_contract_url) {
            GoogleDriveSyncService::deleteFile($contract->file_contract_url);
        }

        $contract->delete();
        ActivityLogger::log('delete', 'hcm', null, "Menghapus naskah kontrak PKWT: {$num}");

        return redirect()->back()->with('success', "Data kontrak '{$num}' berhasil dihapus.");
    }
}
