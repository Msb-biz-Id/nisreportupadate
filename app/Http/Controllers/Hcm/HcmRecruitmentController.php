<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmJobApplicant;
use App\Models\Hcm\HcmJobPosting;
use App\Models\Hcm\HcmMasterOption;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class HcmRecruitmentController extends Controller
{
    /**
     * Tampilkan Modul Master Lowongan Kerja (Job Postings).
     */
    public function jobs(Request $request): Response
    {
        Gate::authorize('hcm.manage-recruitment');

        $search = $request->query('search', '');
        $statusFilter = $request->query('status', 'all');
        $deptFilter = $request->query('department', 'all');

        $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search);

        $jobs = HcmJobPosting::withCount(['applicants'])
            ->with('creator:id,name')
            ->when($escapedSearch, fn ($q, $t) => $q->where(fn ($sub) =>
                $sub->where('title', 'like', "%{$t}%")
                    ->orWhere('department', 'like', "%{$t}%")
                    ->orWhere('position', 'like', "%{$t}%")
            ))
            ->when($statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($deptFilter !== 'all', fn ($q) => $q->where('department', $deptFilter))
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        $metrics = [
            'total_jobs' => HcmJobPosting::count(),
            'active_jobs' => HcmJobPosting::where('is_active', true)->count(),
            'total_applicants' => HcmJobApplicant::count(),
            'hired_applicants' => HcmJobApplicant::where('status', 'ACCEPTED')->count(),
        ];

        // Ambil opsi master departemen & posisi
        $departments = HcmMasterOption::whereHas('category', fn ($c) => $c->where('code', 'divisi'))
            ->where('is_active', true)
            ->orderBy('order_index')
            ->pluck('name');

        $positions = HcmMasterOption::whereHas('category', fn ($c) => $c->where('code', 'posisi'))
            ->where('is_active', true)
            ->orderBy('order_index')
            ->pluck('name');

        return Inertia::render('Hcm/Recruitment/Jobs', [
            'jobs' => $jobs,
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
                'department' => $deptFilter,
            ],
            'metrics' => $metrics,
            'departments' => $departments,
            'positions' => $positions,
        ]);
    }

    /**
     * Simpan Master Lowongan Kerja baru.
     */
    public function storeJob(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-recruitment');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'department' => ['required', 'string', 'max:100'],
            'position' => ['required', 'string', 'max:100'],
            'job_type' => ['required', 'string', 'max:50'],
            'location' => ['required', 'string', 'max:100'],
            'quota' => ['required', 'integer', 'min:1'],
            'min_education' => ['required', 'string', 'max:50'],
            'min_experience_years' => ['required', 'integer', 'min:0'],
            'salary_range' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'requirements' => ['required', 'string'],
            'benefits' => ['nullable', 'string'],
            'deadline' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ]);

        $slug = Str::slug($validated['title']) . '-' . Str::random(5);

        $job = HcmJobPosting::create([
            ...$validated,
            'slug' => $slug,
            'created_by' => Auth::id(),
        ]);

        ActivityLogger::log('create', 'hcm', $job, "Membuat lowongan kerja baru: {$job->title} ({$job->department})");

        return redirect()->back()->with('success', "Lowongan kerja '{$validated['title']}' berhasil dibuat. Link publik siap dibagikan.");
    }

    /**
     * Perbarui data Lowongan Kerja.
     */
    public function updateJob(Request $request, HcmJobPosting $job): RedirectResponse
    {
        Gate::authorize('hcm.manage-recruitment');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'department' => ['required', 'string', 'max:100'],
            'position' => ['required', 'string', 'max:100'],
            'job_type' => ['required', 'string', 'max:50'],
            'location' => ['required', 'string', 'max:100'],
            'quota' => ['required', 'integer', 'min:1'],
            'min_education' => ['required', 'string', 'max:50'],
            'min_experience_years' => ['required', 'integer', 'min:0'],
            'salary_range' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'requirements' => ['required', 'string'],
            'benefits' => ['nullable', 'string'],
            'deadline' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ]);

        $job->update($validated);

        ActivityLogger::log('update', 'hcm', $job, "Memperbarui lowongan kerja: {$job->title}");

        return redirect()->back()->with('success', "Lowongan kerja '{$job->title}' berhasil diperbarui.");
    }

    /**
     * Hapus Lowongan Kerja.
     */
    public function destroyJob(HcmJobPosting $job): RedirectResponse
    {
        Gate::authorize('hcm.manage-recruitment');

        $title = $job->title;
        $job->delete();

        ActivityLogger::log('delete', 'hcm', null, "Menghapus lowongan kerja: {$title}");

        return redirect()->back()->with('success', "Lowongan kerja '{$title}' berhasil dihapus.");
    }

    /**
     * Generate QR Code SVG untuk link internal loker (via simple-qrcode).
     * Disajikan sebagai endpoint GET yang dapat ditampilkan sebagai <img src=...>.
     */
    public function jobQrCode(HcmJobPosting $job): HttpResponse
    {
        Gate::authorize('hcm.manage-recruitment');

        // Link internal yang di-encode ke QR — diarahkan ke halaman pipeline pelamar
        $url = route('hcm.recruitment.applicants.index', ['job_id' => $job->id]);

        $svg = QrCode::format('svg')
            ->size(300)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($url);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    /**
     * Tampilkan Pipeline Pelamar Masuk (Recruitment Funnel).
     */
    public function applicants(Request $request): Response
    {
        Gate::authorize('hcm.manage-recruitment');

        $search = $request->query('search', '');
        $statusFilter = $request->query('status', 'all');
        $jobFilter = $request->query('job_id', 'all');

        $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search);

        $applicants = HcmJobApplicant::with([
            'jobPosting:id,title,department,position',
            'convertedEmployee:id,employee_code,name',
        ])
            ->when($escapedSearch, fn ($q, $t) => $q->where(fn ($sub) =>
                $sub->where('name', 'like', "%{$t}%")
                    ->orWhere('applicant_code', 'like', "%{$t}%")
                    ->orWhere('phone_number', 'like', "%{$t}%")
                    ->orWhere('email', 'like', "%{$t}%")
            ))
            ->when($statusFilter !== 'all', fn ($q) => $q->where('status', $statusFilter))
            ->when($jobFilter !== 'all', fn ($q) => $q->where('job_posting_id', (int) $jobFilter))
            ->orderBy('id', 'desc')
            ->paginate(12)
            ->withQueryString();

        $metrics = [
            'total_applicants' => HcmJobApplicant::count(),
            'submitted' => HcmJobApplicant::where('status', 'SUBMITTED')->count(),
            'interview' => HcmJobApplicant::where('status', 'INTERVIEW')->count(),
            'accepted' => HcmJobApplicant::where('status', 'ACCEPTED')->count(),
            'converted' => HcmJobApplicant::whereNotNull('converted_employee_id')->count(),
        ];

        $jobOptions = HcmJobPosting::select('id', 'title', 'department')
            ->orderBy('title')
            ->get();

        return Inertia::render('Hcm/Recruitment/Applicants', [
            'applicants' => $applicants,
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
                'job_id' => $jobFilter,
            ],
            'metrics' => $metrics,
            'jobOptions' => $jobOptions,
        ]);
    }

    /**
     * Perbarui Status Tahap Seleksi Pelamar & Jadwal Interview.
     */
    public function updateApplicantStatus(Request $request, HcmJobApplicant $applicant): RedirectResponse
    {
        Gate::authorize('hcm.manage-recruitment');

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:SUBMITTED,SCREENING,INTERVIEW,ACCEPTED,REJECTED'],
            'interview_date' => ['nullable', 'date'],
            'interview_location' => ['nullable', 'string', 'max:200'],
            'interviewer_notes' => ['nullable', 'string'],
            'rejection_reason' => ['nullable', 'string'],
        ]);

        $applicant->update($validated);

        ActivityLogger::log('update', 'hcm', $applicant, "Memperbarui status pelamar {$applicant->name} menjadi {$validated['status']}");

        return redirect()->back()->with('success', "Status pelamar {$applicant->name} berhasil diperbarui menjadi {$validated['status']}.");
    }

    /**
     * 1-KLIK KONVERSI PELAMAR MENJADI KARYAWAN BARU (Instant Onboarding).
     */
    public function convertApplicant(Request $request, HcmJobApplicant $applicant): RedirectResponse
    {
        Gate::authorize('hcm.manage-recruitment');

        if ($applicant->converted_employee_id) {
            return redirect()->back()->with('error', "Pelamar ini sudah pernah dikonversi menjadi karyawan sebelumnya.");
        }

        $validated = $request->validate([
            'employment_status' => ['required', 'string', 'max:50'], // Probation, Kontrak (PKWT), Tetap (PKWTT), Magang
            'job_level' => ['required', 'string', 'max:50'],
            'department' => ['required', 'string', 'max:100'],
            'position' => ['required', 'string', 'max:100'],
            'join_date' => ['required', 'date'],
        ]);

        $newEmployee = DB::transaction(function () use ($applicant, $validated) {
            // Generate Kode Karyawan Unik
            $year = date('Y');
            $lastEmp = HcmEmployee::max('id') ?? 0;
            $code = 'EMP-' . $year . '-' . str_pad($lastEmp + 1, 3, '0', STR_PAD_LEFT);

            // Buat Record Karyawan Baru
            $employee = HcmEmployee::create([
                'employee_code' => $code,
                'name' => $applicant->name,
                'nickname' => $applicant->nickname ?? Str::before($applicant->name, ' '),
                'gender' => $applicant->gender,
                'birth_place' => $applicant->birth_place,
                'birth_date' => $applicant->birth_date,
                'phone_number' => $applicant->phone_number,
                'email' => $applicant->email,
                'education' => $applicant->education,
                'address' => $applicant->address,
                'nik_ktp' => $applicant->nik_ktp ?? ('AUTO-' . Str::random(10)),
                'marital_status' => $applicant->marital_status ?? 'Belum Menikah',
                'shirt_size' => $applicant->shirt_size ?? 'L',
                'department' => $validated['department'],
                'position' => $validated['position'],
                'job_level' => $validated['job_level'],
                'employment_status' => $validated['employment_status'],
                'join_date' => $validated['join_date'],
                'is_active' => true,
                'photo' => $applicant->photo,
                'photo_url' => $applicant->photo_url,
                'notes' => "Dikonversi otomatis dari rekrutmen pelamar #{$applicant->applicant_code}. Posisi lamaran: {$applicant->jobPosting?->title}",
            ]);

            // Tandai pelamar telah dikonversi
            $applicant->update([
                'status' => 'ACCEPTED',
                'converted_employee_id' => $employee->id,
                'converted_at' => now(),
            ]);

            return $employee;
        });

        ActivityLogger::log('create', 'hcm', $newEmployee, "Konversi pelamar {$applicant->name} menjadi karyawan ({$newEmployee->employee_code})");

        return redirect()->route('hcm.employees.show', $newEmployee->id)
            ->with('success', "Pelamar {$applicant->name} berhasil dikonversi menjadi Karyawan Baru dengan NIK {$newEmployee->employee_code}.");
    }
}
