<?php

namespace App\Http\Controllers\Hcm;

use App\Exports\HcmRecruitmentReportExport;
use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmApplicantInterview;
use App\Models\Hcm\HcmJobApplicant;
use App\Models\Hcm\HcmJobPosting;
use App\Models\Hcm\HcmMasterOption;
use App\Models\Hcm\HcmOnboarding;
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
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
            'legal_entity' => ['nullable', 'string', 'max:100'],
            'salary_range' => ['nullable', 'string', 'max:100'],
            'salary_range_min' => ['nullable', 'numeric', 'min:0'],
            'salary_range_max' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'recruitment_channel' => ['nullable', 'string', 'max:100'],
            'recruiter_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'string', 'max:50'],
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
            'status' => $validated['status'] ?? 'Aktif',
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
            'legal_entity' => ['nullable', 'string', 'max:100'],
            'salary_range' => ['nullable', 'string', 'max:100'],
            'salary_range_min' => ['nullable', 'numeric', 'min:0'],
            'salary_range_max' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'recruitment_channel' => ['nullable', 'string', 'max:100'],
            'recruiter_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'string', 'max:50'],
            'description' => ['required', 'string'],
            'requirements' => ['required', 'string'],
            'benefits' => ['nullable', 'string'],
            'deadline' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ]);

        $validated['status'] = $validated['status'] ?? $job->status ?? 'Aktif';

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
            'interviews',
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
            'invitation_status' => ['nullable', 'string', 'max:50'],
            'interview_result' => ['nullable', 'string', 'max:50'],
            'onboarding_attendance' => ['nullable', 'string', 'max:50'],
            'is_blacklisted' => ['nullable', 'boolean'],
            'hcm_notes' => ['nullable', 'string'],
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

            // Tambah counter kuota terpenuhi & tutup loker bila kuota terpenuhi.
            if ($posting = $applicant->jobPosting) {
                $posting->increment('fulfilled_count');
                $posting->refresh();
                if ($posting->fulfilled_count >= $posting->quota) {
                    $posting->update(['status' => 'Terpenuhi', 'is_active' => false]);
                }
            }

            // Modul 12: buat rekap onboarding karyawan baru.
            HcmOnboarding::syncFor($employee);

            return $employee;
        });

        ActivityLogger::log('create', 'hcm', $newEmployee, "Konversi pelamar {$applicant->name} menjadi karyawan ({$newEmployee->employee_code})");

        return redirect()->route('hcm.employees.show', $newEmployee->id)
            ->with('success', "Pelamar {$applicant->name} berhasil dikonversi menjadi Karyawan Baru dengan NIK {$newEmployee->employee_code}.");
    }

    /**
     * Simpan satu sesi rekap hasil wawancara kandidat (Modul 11.4).
     */
    public function storeInterview(Request $request, HcmJobApplicant $applicant): RedirectResponse
    {
        Gate::authorize('hcm.manage-recruitment');

        $validated = $this->validateInterview($request);

        // Auto-isi profil dari data pelamar bila belum diisi pewawancara.
        $validated['applicant_id'] = $applicant->id;
        $validated['age'] = $validated['age'] ?? ($applicant->birth_date ? $applicant->birth_date->age : null);
        $validated['marital_status'] = $validated['marital_status'] ?? $applicant->marital_status;
        $validated['education'] = $validated['education'] ?? $applicant->education;
        $validated['last_experience'] = $validated['last_experience'] ?? $applicant->experience_summary;
        $validated['core_skills'] = $validated['core_skills'] ?? $applicant->skills;
        $validated['salary_expectation'] = $validated['salary_expectation'] ?? $applicant->expected_salary;
        $validated['created_by'] = Auth::id();

        $interview = HcmApplicantInterview::create($validated);

        $this->syncApplicantFromInterviews($applicant);

        ActivityLogger::log('create', 'hcm', $interview, "Menambah rekap wawancara {$applicant->name} ({$applicant->applicant_code})");

        return redirect()->back()->with('success', 'Rekap wawancara berhasil disimpan.');
    }

    /**
     * Perbarui sesi wawancara.
     */
    public function updateInterview(Request $request, HcmApplicantInterview $interview): RedirectResponse
    {
        Gate::authorize('hcm.manage-recruitment');

        $interview->update($this->validateInterview($request));

        $this->syncApplicantFromInterviews($interview->applicant);

        ActivityLogger::log('update', 'hcm', $interview, "Memperbarui rekap wawancara #{$interview->id}");

        return redirect()->back()->with('success', 'Rekap wawancara berhasil diperbarui.');
    }

    /**
     * Hapus sesi wawancara.
     */
    public function destroyInterview(HcmApplicantInterview $interview): RedirectResponse
    {
        Gate::authorize('hcm.manage-recruitment');

        $applicant = $interview->applicant;
        $interview->delete();

        if ($applicant) {
            $this->syncApplicantFromInterviews($applicant);
        }

        return redirect()->back()->with('success', 'Rekap wawancara berhasil dihapus.');
    }

    /**
     * Validasi payload wawancara (dipakai store & update).
     */
    private function validateInterview(Request $request): array
    {
        return $request->validate([
            'interview_round' => ['nullable', 'string', 'max:50'],
            'interviewer_name' => ['nullable', 'string', 'max:100'],
            'interview_date' => ['nullable', 'date'],
            'interview_result' => ['nullable', 'string', 'max:50'],
            'age' => ['nullable', 'integer', 'min:15', 'max:80'],
            'marital_status' => ['nullable', 'string', 'max:30'],
            'education' => ['nullable', 'string', 'max:50'],
            'last_experience' => ['nullable', 'string'],
            'daily_activity' => ['nullable', 'string', 'max:100'],
            'core_skills' => ['nullable', 'string'],
            'salary_expectation' => ['nullable', 'numeric', 'min:0'],
            'offering_status' => ['nullable', 'string', 'max:50'],
            'interview_decision' => ['nullable', 'string', 'max:50'],
            'offering_notes' => ['nullable', 'string'],
        ]);
    }

    /**
     * Selaraskan ringkasan hasil wawancara terbaru ke data pelamar.
     */
    private function syncApplicantFromInterviews(HcmJobApplicant $applicant): void
    {
        $latest = $applicant->interviews()->first();
        if (!$latest) {
            return;
        }

        $applicant->update([
            'interview_result' => $latest->interview_result ?? $applicant->interview_result,
        ]);
    }

    /**
     * Halaman Laporan Performa Rekrutmen per Loker (Beserta Detail).
     */
    public function reports(Request $request): Response
    {
        Gate::authorize('hcm.manage-recruitment');

        $selectedJobId = $request->query('job_id');

        $rows = self::buildJobPerformance();

        $selectedJob = null;
        $selectedApplicants = collect();

        if ($selectedJobId) {
            $selectedJob = HcmJobPosting::find($selectedJobId);
            if ($selectedJob) {
                $selectedApplicants = HcmJobApplicant::with(['interviews'])
                    ->where('job_posting_id', $selectedJob->id)
                    ->orderByDesc('id')
                    ->get();
            }
        }

        $summary = [
            'total_jobs' => $rows->count(),
            'total_applicants' => $rows->sum('total_applicants'),
            'total_hired' => $rows->sum('hired'),
            'avg_fulfillment' => $rows->count() > 0 ? round($rows->avg('fulfillment_rate'), 1) : 0,
            'avg_time_to_hire' => $rows->whereNotNull('avg_time_to_hire')->count() > 0
                ? round($rows->whereNotNull('avg_time_to_hire')->avg('avg_time_to_hire'), 1)
                : null,
        ];

        return Inertia::render('Hcm/Recruitment/Reports', [
            'rows' => $rows->values(),
            'summary' => $summary,
            'channels' => self::channelPerformance(),
            'selectedJob' => $selectedJob,
            'selectedApplicants' => $selectedApplicants,
            'filters' => ['job_id' => $selectedJobId],
        ]);
    }

    /**
     * Export Excel laporan performa rekrutmen (semua loker atau satu loker + detail pelamar).
     */
    public function exportReport(Request $request): BinaryFileResponse
    {
        Gate::authorize('hcm.manage-recruitment');

        $rows = self::buildJobPerformance();

        $jobId = $request->query('job_id');
        $selectedJob = $jobId ? HcmJobPosting::find($jobId) : null;
        $applicants = $selectedJob
            ? HcmJobApplicant::with('interviews')->where('job_posting_id', $selectedJob->id)->orderByDesc('id')->get()
            : collect();

        $scope = $selectedJob ? Str::slug($selectedJob->title) : 'Semua-Loker';
        $filename = 'Laporan_Rekrutmen_' . $scope . '_' . now()->format('Ymd') . '.xlsx';

        ActivityLogger::log('export', 'hcm', $selectedJob, 'Export Excel laporan rekrutmen' . ($selectedJob ? ": {$selectedJob->title}" : ' (semua loker)'));

        return Excel::download(
            new HcmRecruitmentReportExport($rows, $selectedJob, $applicants, Auth::user()?->name),
            $filename
        );
    }

    /**
     * Bangun data performa rekrutmen agregat per loker.
     */
    public static function buildJobPerformance(): \Illuminate\Support\Collection
    {
        $jobs = HcmJobPosting::withCount('applicants')->orderByDesc('id')->get();

        return $jobs->map(function (HcmJobPosting $job) {
            $applicants = HcmJobApplicant::where('job_posting_id', $job->id)->get();

            $byStage = fn ($status) => $applicants->where('status', $status)->count();
            $hired = $applicants->whereNotNull('converted_employee_id')->count();

            // Time-to-hire: rata-rata hari dari apply_date ke converted_at.
            $tth = $applicants
                ->filter(fn ($a) => $a->apply_date && $a->converted_at)
                ->map(fn ($a) => $a->apply_date->startOfDay()->diffInDays($a->converted_at->startOfDay()));

            $total = $applicants->count();
            $quota = (int) $job->quota;

            return [
                'id' => $job->id,
                'job_code' => $job->job_code,
                'title' => $job->title,
                'department' => $job->department,
                'legal_entity' => $job->legal_entity,
                'status' => $job->status ?? ($job->is_active ? 'Aktif' : 'Ditutup'),
                'quota' => $quota,
                'fulfilled_count' => $hired,
                'total_applicants' => $total,
                'submitted' => $byStage('SUBMITTED'),
                'screening' => $byStage('SCREENING'),
                'interview' => $byStage('INTERVIEW'),
                'accepted' => $byStage('ACCEPTED'),
                'rejected' => $byStage('REJECTED'),
                'hired' => $hired,
                'fulfillment_rate' => $quota > 0 ? round(min(100, $hired / $quota * 100), 1) : 0,
                'conversion_rate' => $total > 0 ? round($hired / $total * 100, 1) : 0,
                'avg_time_to_hire' => $tth->count() > 0 ? round($tth->avg(), 1) : null,
                'interviewed' => HcmApplicantInterview::whereHas('applicant', fn ($q) => $q->where('job_posting_id', $job->id))->count(),
            ];
        });
    }

    /**
     * Performa per saluran rekrutmen (ROI / efektivitas sumber kandidat).
     */
    public static function channelPerformance(): \Illuminate\Support\Collection
    {
        return HcmJobPosting::query()
            ->get()
            ->groupBy(fn ($j) => $j->recruitment_channel ?: 'Tidak Disebutkan')
            ->map(function ($jobs, $channel) {
                $jobIds = $jobs->pluck('id');
                $total = HcmJobApplicant::whereIn('job_posting_id', $jobIds)->count();
                $hired = HcmJobApplicant::whereIn('job_posting_id', $jobIds)->whereNotNull('converted_employee_id')->count();

                return [
                    'channel' => $channel,
                    'jobs' => $jobs->count(),
                    'applicants' => $total,
                    'hired' => $hired,
                    'conversion_rate' => $total > 0 ? round($hired / $total * 100, 1) : 0,
                ];
            })
            ->sortByDesc('applicants')
            ->values();
    }
}
