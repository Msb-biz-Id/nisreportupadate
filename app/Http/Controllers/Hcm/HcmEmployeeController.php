<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmCompensation;
use App\Models\Hcm\HcmCompensationHistory;
use App\Models\Hcm\HcmContract;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmIntern;
use App\Models\Hcm\HcmMasterOption;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HcmEmployeeController extends Controller
{
    /**
     * Tampilkan daftar seluruh karyawan & peserta magang.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('hcm.manage-employees');

        $search = $request->query('search', '');
        $departmentFilter = $request->query('department', 'all');
        $jobLevelFilter = $request->query('job_level', 'all');
        $statusFilter = $request->query('status', 'all');

        $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search);

        $employeesQuery = HcmEmployee::with([
            'intern',
            'activeContract',
            'compensation',
        ])
            ->when($escapedSearch, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                      ->orWhere('nickname', 'like', "%{$term}%")
                      ->orWhere('employee_code', 'like', "%{$term}%")
                      ->orWhere('nik_ktp', 'like', "%{$term}%")
                      ->orWhere('phone_number', 'like', "%{$term}%");
                });
            })
            ->when($departmentFilter !== 'all', fn ($q) => $q->where('department', $departmentFilter))
            ->when($jobLevelFilter !== 'all', fn ($q) => $q->where('job_level', $jobLevelFilter))
            ->when($statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('id', 'desc');

        $employees = $employeesQuery->paginate(15)->withQueryString();

        // Dropdown dinamis dari master data
        $dropdowns = HcmMasterOption::getAllDropdowns();

        // Ringkasan metrik statistik
        $metrics = [
            'total_active' => HcmEmployee::where('is_active', true)->count(),
            'total_interns' => HcmEmployee::where('job_level', 'Magang')->where('is_active', true)->count(),
            'total_contract' => HcmEmployee::whereIn('employment_status', ['PKWT', 'PKWT Lanjutan', 'Kontrak (PKWT)'])->where('is_active', true)->count(),
            'total_permanent' => HcmEmployee::whereIn('employment_status', ['Karyawan Tetap', 'Tetap (PKWTT)'])->where('is_active', true)->count(),
        ];

        return Inertia::render('Hcm/Employees/Index', [
            'employees' => $employees,
            'filters' => [
                'search' => $search,
                'department' => $departmentFilter,
                'job_level' => $jobLevelFilter,
                'status' => $statusFilter,
            ],
            'metrics' => $metrics,
            'dropdowns' => $dropdowns,
        ]);
    }

    /**
     * Simpan data karyawan baru beserta kontrak/magang/kompensasi awal.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-employees');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'nickname' => ['required', 'string', 'max:50'],
            'department' => ['required', 'string', 'max:100'],
            'position' => ['required', 'string', 'max:100'],
            'job_level' => ['required', 'string', 'max:50'],
            'employment_status' => ['required', 'string', 'max:50'],
            'legal_entity' => ['nullable', 'string', 'max:100'],
            'phone_number' => ['required', 'string', 'max:25'],
            'gender' => ['required', 'string', 'in:Laki Laki,Perempuan'],
            'religion' => ['nullable', 'string', 'max:30'],
            'education' => ['nullable', 'string', 'max:50'],
            'marital_status' => ['nullable', 'string', 'max:30'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'nik_ktp' => ['required', 'string', 'digits:16', 'unique:hcm_employees,nik_ktp'],
            'bpjs_kesehatan_no' => ['nullable', 'string', 'max:50'],
            'bpjs_ketenagakerjaan_no' => ['nullable', 'string', 'max:50'],
            'shirt_size' => ['required', 'string', 'max:10'],
            'address' => ['nullable', 'string'],
            'bank_account_no' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'join_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],

            // Data Magang (Opsional jika job_level Magang)
            'intern_school_name' => ['nullable', 'string', 'max:150'],
            'intern_class' => ['nullable', 'string', 'max:20'],
            'intern_major' => ['nullable', 'string', 'max:100'],
            'intern_nis' => ['nullable', 'string', 'max:50'],
            'intern_start_date' => ['nullable', 'date'],
            'intern_end_date' => ['nullable', 'date'],
            'intern_duration_text' => ['nullable', 'string', 'max:50'],
            'intern_mentor_teacher' => ['nullable', 'string', 'max:100'],
            'intern_mentor_phone' => ['nullable', 'string', 'max:25'],

            // Data Kontrak Awal (Opsional)
            'contract_number' => ['nullable', 'string', 'max:100'],
            'contract_duration_text' => ['nullable', 'string', 'max:50'],
            'contract_start_date' => ['nullable', 'date'],
            'contract_end_date' => ['nullable', 'date'],

            // Data Kompensasi Awal (Opsional)
            'initial_salary' => ['nullable', 'numeric', 'min:0'],

            // Foto Karyawan
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $employee = null;
        DB::transaction(function () use ($request, $validated, &$employee) {
            // Generate auto employee_code
            $isIntern = ($validated['job_level'] === 'Magang' || $validated['employment_status'] === 'Magang');
            $prefix = $isIntern ? 'INT-' : 'EMP-';
            $year = date('Y');
            $count = HcmEmployee::whereYear('created_at', $year)
                ->where('employee_code', 'like', "{$prefix}{$year}-%")
                ->count() + 1;
            $code = sprintf("%s%s-%03d", $prefix, $year, $count);

            $photoPath = null;
            $photoUrl = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('hcm/employees/photos', 'public');
                $photoUrl = '/storage/' . $photoPath;
            }

            $employee = HcmEmployee::create([
                'employee_code' => $code,
                'name' => $validated['name'],
                'nickname' => $validated['nickname'],
                'department' => $validated['department'],
                'position' => $validated['position'],
                'job_level' => $validated['job_level'],
                'employment_status' => $validated['employment_status'],
                'legal_entity' => $validated['legal_entity'] ?? null,
                'phone_number' => $validated['phone_number'],
                'gender' => $validated['gender'],
                'religion' => $validated['religion'] ?? 'Islam',
                'education' => $validated['education'] ?? 'SMA Sederajat',
                'marital_status' => $validated['marital_status'] ?? 'Belum Menikah',
                'birth_place' => $validated['birth_place'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'nik_ktp' => $validated['nik_ktp'],
                'bpjs_kesehatan_no' => $validated['bpjs_kesehatan_no'] ?? null,
                'bpjs_ketenagakerjaan_no' => $validated['bpjs_ketenagakerjaan_no'] ?? null,
                'shirt_size' => $validated['shirt_size'],
                'address' => $validated['address'] ?? null,
                'bank_account_no' => $validated['bank_account_no'] ?? null,
                'bank_name' => $validated['bank_name'] ?? 'Bank BRI',
                'email' => $validated['email'] ?? null,
                'join_date' => $validated['join_date'] ?? now()->toDateString(),
                'notes' => $validated['notes'] ?? null,
                'photo' => $photoPath,
                'photo_url' => $photoUrl,
                'is_active' => true,
            ]);

            // Jika peserta magang, simpan data hcm_interns
            if ($isIntern && !empty($validated['intern_school_name'])) {
                HcmIntern::create([
                    'employee_id' => $employee->id,
                    'school_name' => $validated['intern_school_name'],
                    'class' => $validated['intern_class'] ?? 'XII',
                    'major' => $validated['intern_major'] ?? null,
                    'nis' => $validated['intern_nis'] ?? null,
                    'start_date' => $validated['intern_start_date'] ?? $validated['join_date'] ?? null,
                    'end_date' => $validated['intern_end_date'] ?? null,
                    'duration_text' => $validated['intern_duration_text'] ?? null,
                    'mentor_teacher_name' => $validated['intern_mentor_teacher'] ?? null,
                    'mentor_teacher_phone' => $validated['intern_mentor_phone'] ?? null,
                ]);
            }

            $defaultLegalEntity = HcmMasterOption::getOptions('legal_entities')[0] ?? null;

            // Jika ada kontrak awal
            if (!empty($validated['contract_number'])) {
                HcmContract::create([
                    'employee_id' => $employee->id,
                    'contract_number' => $validated['contract_number'],
                    'contract_sequence' => 1,
                    'employment_status' => $validated['employment_status'],
                    'position' => $validated['position'],
                    'legal_entity' => $validated['legal_entity'] ?? $defaultLegalEntity,
                    'duration_text' => $validated['contract_duration_text'] ?? '1 Tahun',
                    'start_year' => date('Y'),
                    'start_date' => $validated['contract_start_date'] ?? now()->toDateString(),
                    'end_date' => $validated['contract_end_date'] ?? null,
                    'review_status' => 'Aktif',
                ]);
            }

            // Jika ada gaji awal
            if (isset($validated['initial_salary']) && $validated['initial_salary'] > 0) {
                HcmCompensation::create([
                    'employee_id' => $employee->id,
                    'employment_status' => $validated['employment_status'],
                    'legal_entity' => $validated['legal_entity'] ?? $defaultLegalEntity,
                    'contract_number' => $validated['contract_number'] ?? null,
                    'duration_text' => $validated['contract_duration_text'] ?? null,
                    'initial_salary' => $validated['initial_salary'],
                    'current_salary' => $validated['initial_salary'],
                    'evaluation_cycle_months' => 6,
                    'salary_increment_count' => 0,
                    'salary_status' => 'Telah Berlaku',
                ]);
            }
        });

        if ($employee) {
            ActivityLogger::log('create', 'hcm', $employee, "Menambahkan karyawan baru: {$employee->name} ({$employee->employee_code})");
        }

        return redirect()->route('hcm.employees.index')->with('success', 'Data karyawan baru berhasil ditambahkan.');
    }

    /**
     * Tampilkan Buku Induk Profil Karyawan 360° (6 Tab Interaktif).
     */
    public function show(HcmEmployee $employee): Response
    {
        Gate::authorize('hcm.manage-employees');

        $employee->load([
            'intern',
            'contracts',
            'compensation.histories.approver',
            'attendances' => fn ($q) => $q->orderBy('attendance_date', 'desc')->limit(31),
            'leaveRequests' => fn ($q) => $q->orderBy('start_date', 'desc')->limit(10),
            'overtimes.batch' => fn ($q) => $q->orderBy('overtime_date', 'desc')->limit(20),
        ]);

        return Inertia::render('Hcm/Employees/Show', [
            'employee' => $employee,
            'dropdowns' => HcmMasterOption::getAllDropdowns(),
        ]);
    }

    /**
     * Perbarui data profil karyawan.
     */
    public function update(Request $request, HcmEmployee $employee): RedirectResponse
    {
        Gate::authorize('hcm.manage-employees');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'nickname' => ['required', 'string', 'max:50'],
            'department' => ['required', 'string', 'max:100'],
            'position' => ['required', 'string', 'max:100'],
            'job_level' => ['required', 'string', 'max:50'],
            'employment_status' => ['required', 'string', 'max:50'],
            'legal_entity' => ['nullable', 'string', 'max:100'],
            'phone_number' => ['required', 'string', 'max:25'],
            'gender' => ['required', 'string', 'in:Laki Laki,Laki-Laki,Perempuan'],
            'religion' => ['nullable', 'string', 'max:30'],
            'education' => ['nullable', 'string', 'max:50'],
            'marital_status' => ['nullable', 'string', 'max:30'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'nik_ktp' => ['required', 'string', 'digits:16', 'unique:hcm_employees,nik_ktp,' . $employee->id],
            'bpjs_kesehatan_no' => ['nullable', 'string', 'max:50'],
            'bpjs_ketenagakerjaan_no' => ['nullable', 'string', 'max:50'],
            'shirt_size' => ['required', 'string', 'max:10'],
            'address' => ['nullable', 'string'],
            'bank_account_no' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'join_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],

            // Data Magang (jika ada)
            'intern_school_name' => ['nullable', 'string', 'max:150'],
            'intern_class' => ['nullable', 'string', 'max:20'],
            'intern_major' => ['nullable', 'string', 'max:100'],
            'intern_nis' => ['nullable', 'string', 'max:50'],
            'intern_start_date' => ['nullable', 'date'],
            'intern_end_date' => ['nullable', 'date'],
            'intern_duration_text' => ['nullable', 'string', 'max:50'],
            'intern_mentor_teacher' => ['nullable', 'string', 'max:100'],
            'intern_mentor_phone' => ['nullable', 'string', 'max:25'],

            // Foto Profil Karyawan
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $validated, $employee) {
            $photoPath = $employee->photo;
            $photoUrl = $employee->photo_url;

            if ($request->boolean('remove_photo')) {
                $oldDiskPath = $employee->photo ?: str_replace('/storage/', '', (string) $employee->photo_url);
                if ($oldDiskPath && Storage::disk('public')->exists($oldDiskPath)) {
                    Storage::disk('public')->delete($oldDiskPath);
                }
                $photoPath = null;
                $photoUrl = null;
            } elseif ($request->hasFile('photo')) {
                $oldDiskPath = $employee->photo ?: str_replace('/storage/', '', (string) $employee->photo_url);
                if ($oldDiskPath && Storage::disk('public')->exists($oldDiskPath)) {
                    Storage::disk('public')->delete($oldDiskPath);
                }
                $photoPath = $request->file('photo')->store('hcm/employees/photos', 'public');
                $photoUrl = '/storage/' . $photoPath;
            }

            $employee->update([
                'photo' => $photoPath,
                'photo_url' => $photoUrl,
                'name' => $validated['name'],
                'nickname' => $validated['nickname'],
                'department' => $validated['department'],
                'position' => $validated['position'],
                'job_level' => $validated['job_level'],
                'employment_status' => $validated['employment_status'],
                'legal_entity' => $validated['legal_entity'] ?? null,
                'phone_number' => $validated['phone_number'],
                'gender' => $validated['gender'],
                'religion' => $validated['religion'] ?? 'Islam',
                'education' => $validated['education'] ?? 'SMA Sederajat',
                'marital_status' => $validated['marital_status'] ?? 'Belum Menikah',
                'birth_place' => $validated['birth_place'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'nik_ktp' => $validated['nik_ktp'],
                'bpjs_kesehatan_no' => $validated['bpjs_kesehatan_no'] ?? null,
                'bpjs_ketenagakerjaan_no' => $validated['bpjs_ketenagakerjaan_no'] ?? null,
                'shirt_size' => $validated['shirt_size'],
                'address' => $validated['address'] ?? null,
                'bank_account_no' => $validated['bank_account_no'] ?? null,
                'bank_name' => $validated['bank_name'] ?? 'Bank BRI',
                'email' => $validated['email'] ?? null,
                'join_date' => $validated['join_date'] ?? $employee->join_date,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Update atau create intern record jika status/job_level Magang
            if (!empty($validated['intern_school_name'])) {
                HcmIntern::updateOrCreate(
                    ['employee_id' => $employee->id],
                    [
                        'school_name' => $validated['intern_school_name'],
                        'class' => $validated['intern_class'] ?? 'XII',
                        'major' => $validated['intern_major'] ?? null,
                        'nis' => $validated['intern_nis'] ?? null,
                        'start_date' => $validated['intern_start_date'] ?? null,
                        'end_date' => $validated['intern_end_date'] ?? null,
                        'duration_text' => $validated['intern_duration_text'] ?? null,
                        'mentor_teacher_name' => $validated['intern_mentor_teacher'] ?? null,
                        'mentor_teacher_phone' => $validated['intern_mentor_phone'] ?? null,
                    ]
                );
            }
        });

        ActivityLogger::log('update', 'hcm', $employee, "Memperbarui data profil karyawan: {$employee->name} ({$employee->employee_code})");

        return redirect()->back()->with('success', 'Data profil karyawan berhasil diperbarui.');
    }

    /**
     * Tambah naskah perpanjangan kontrak kerja baru pada profil karyawan.
     */
    public function storeContract(Request $request, HcmEmployee $employee): RedirectResponse
    {
        Gate::authorize('hcm.manage-contracts');

        $validated = $request->validate([
            'contract_number' => ['required', 'string', 'max:100', 'unique:hcm_contracts,contract_number'],
            'employment_status' => ['required', 'string', 'max:50'],
            'position' => ['required', 'string', 'max:100'],
            'legal_entity' => ['required', 'string', 'max:100'],
            'duration_text' => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'review_status' => ['required', 'string', 'max:100'],
            'file_contract' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'file_contract_url' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $fileUrl = $validated['file_contract_url'] ?? null;
        if ($request->hasFile('file_contract')) {
            $uploaded = \App\Services\GoogleDriveSyncService::uploadFile(
                $request->file('file_contract'),
                \App\Services\GoogleDriveSyncService::FOLDER_CONTRACTS
            );
            $fileUrl = $uploaded['url'];
        }

        $nextSequence = ($employee->contracts()->max('contract_sequence') ?? 0) + 1;

        $employee->contracts()->create([
            'contract_number' => $validated['contract_number'],
            'contract_sequence' => $nextSequence,
            'employment_status' => $validated['employment_status'],
            'position' => $validated['position'],
            'legal_entity' => $validated['legal_entity'],
            'duration_text' => $validated['duration_text'],
            'start_year' => date('Y', strtotime($validated['start_date'])),
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'review_status' => $validated['review_status'],
            'file_contract_url' => $fileUrl,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Perbarui juga employment_status & position di master jika berubah
        $employee->update([
            'employment_status' => $validated['employment_status'],
            'position' => $validated['position'],
            'legal_entity' => $validated['legal_entity'],
        ]);

        ActivityLogger::log('create', 'hcm', $employee, "Menambahkan naskah kontrak kerja baru #{$validated['contract_number']} untuk {$employee->name}");

        return redirect()->back()->with('success', "Kontrak baru No. {$validated['contract_number']} berhasil ditambahkan.");
    }

    /**
     * Tambah riwayat kenaikan gaji / kompensasi karyawan.
     */
    public function storeCompensationHistory(Request $request, HcmEmployee $employee): RedirectResponse
    {
        Gate::authorize('hcm.manage-compensation');

        $validated = $request->validate([
            'new_salary' => ['required', 'numeric', 'min:0'],
            'increment_amount' => ['required', 'numeric', 'min:0'],
            'effective_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $defaultLegalEntity = HcmMasterOption::getOptions('legal_entities')[0] ?? null;

        $compensation = $employee->compensation()->firstOrCreate(
            ['employee_id' => $employee->id],
            [
                'employment_status' => $employee->employment_status,
                'legal_entity' => $employee->legal_entity ?? $defaultLegalEntity,
                'initial_salary' => $validated['new_salary'] - $validated['increment_amount'],
                'current_salary' => $validated['new_salary'],
                'evaluation_cycle_months' => 6,
                'salary_status' => 'Telah Berlaku',
            ]
        );

        $previousSalary = $compensation->current_salary;

        DB::transaction(function () use ($compensation, $employee, $validated, $previousSalary) {
            $incrementCount = $compensation->salary_increment_count + 1;

            $updatePayload = [
                'current_salary' => $validated['new_salary'],
                'salary_increment_count' => $incrementCount,
            ];

            if ($incrementCount === 1) {
                $updatePayload['increment_1_amount'] = $validated['increment_amount'];
            } elseif ($incrementCount === 2) {
                $updatePayload['increment_2_amount'] = $validated['increment_amount'];
            } elseif ($incrementCount === 3) {
                $updatePayload['increment_3_amount'] = $validated['increment_amount'];
            }

            $compensation->update($updatePayload);

            HcmCompensationHistory::create([
                'compensation_id' => $compensation->id,
                'employee_id' => $employee->id,
                'previous_salary' => $previousSalary,
                'new_salary' => $validated['new_salary'],
                'increment_amount' => $validated['increment_amount'],
                'effective_date' => $validated['effective_date'],
                'reason' => $validated['reason'],
                'approved_by' => Auth::id(),
            ]);
        });

        ActivityLogger::log('update', 'hcm', $employee, "Kenaikan honor/gaji karyawan {$employee->name} menjadi Rp " . number_format($validated['new_salary'], 0, ',', '.'));

        return redirect()->back()->with('success', 'Riwayat kenaikan honor/gaji berhasil dicatat.');
    }

    /**
     * Toggle status aktif / keluar karyawan.
     */
    public function toggle(HcmEmployee $employee): RedirectResponse
    {
        Gate::authorize('hcm.manage-employees');

        $employee->update([
            'is_active' => !$employee->is_active,
        ]);

        $status = $employee->is_active ? 'diaktifkan kembali' : 'dinonaktifkan (offboarding)';
        ActivityLogger::log('toggle', 'hcm', $employee, "Status karyawan {$employee->name} {$status}");

        return redirect()->back()->with('success', "Karyawan '{$employee->name}' berhasil {$status}.");
    }

    /**
     * Hapus data karyawan secara permanen.
     */
    public function destroy(HcmEmployee $employee): RedirectResponse
    {
        Gate::authorize('hcm.manage-employees');

        $name = $employee->name;
        $code = $employee->employee_code;
        $employee->delete();
        ActivityLogger::log('delete', 'hcm', null, "Menghapus data karyawan: {$name} ({$code})");

        return redirect()->route('hcm.employees.index')->with('success', "Data karyawan '{$name}' berhasil dihapus.");
    }

    /**
     * Upload / Ganti / Hapus Foto Profil Karyawan (Direct Action).
     */
    public function updatePhoto(Request $request, HcmEmployee $employee): RedirectResponse
    {
        Gate::authorize('hcm.manage-employees');

        $request->validate([
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('remove_photo')) {
            $oldDiskPath = $employee->photo ?: str_replace('/storage/', '', (string) $employee->photo_url);
            if ($oldDiskPath && Storage::disk('public')->exists($oldDiskPath)) {
                Storage::disk('public')->delete($oldDiskPath);
            }
            $employee->update([
                'photo' => null,
                'photo_url' => null,
            ]);
            ActivityLogger::log('update', 'hcm', $employee, "Menghapus foto profil karyawan {$employee->name}");

            return redirect()->back()->with('success', "Foto profil karyawan berhasil dihapus.");
        }

        if ($request->hasFile('photo')) {
            $oldDiskPath = $employee->photo ?: str_replace('/storage/', '', (string) $employee->photo_url);
            if ($oldDiskPath && Storage::disk('public')->exists($oldDiskPath)) {
                Storage::disk('public')->delete($oldDiskPath);
            }
            $path = $request->file('photo')->store('hcm/employees/photos', 'public');
            $employee->update([
                'photo' => $path,
                'photo_url' => '/storage/' . $path,
            ]);
            ActivityLogger::log('update', 'hcm', $employee, "Memperbarui foto profil karyawan {$employee->name}");

            return redirect()->back()->with('success', "Foto profil karyawan berhasil diperbarui.");
        }

        return redirect()->back();
    }
}
