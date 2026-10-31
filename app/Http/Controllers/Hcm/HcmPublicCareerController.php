<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmJobApplicant;
use App\Models\Hcm\HcmJobPosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HcmPublicCareerController extends Controller
{
    /**
     * Tampilkan direktori portal karir terbuka (/karir).
     */
    public function index(): Response
    {
        $jobs = HcmJobPosting::query()
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();

        $departments = HcmJobPosting::whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->pluck('department');

        return Inertia::render('Career/Index', [
            'jobs' => $jobs,
            'departments' => $departments,
            'company' => $this->getCareerCompanyInfo(),
        ]);
    }

    /**
     * Tampilkan formulir pendaftaran online publik berdasarkan slug loker (/karir/{slug}).
     */
    public function show(string $slug): Response
    {
        $job = HcmJobPosting::where('slug', $slug)->firstOrFail();

        // Increment hit counter
        $job->increment('views_count');

        return Inertia::render('Career/Apply', [
            'job' => $job,
            'company' => $this->getCareerCompanyInfo(),
        ]);
    }

    /**
     * Helper resolusi profil divisi & logo HRIS untuk portal karir publik.
     */
    private function getCareerCompanyInfo(): array
    {
        $hcmProfile = \App\Models\Settings\SystemSetting::getGroup('hcm_profile');
        $divisionName = $hcmProfile['division_name'] ?? 'Divisi Human Capital Management';
        $companyName = $hcmProfile['company_name'] ?? 'PT Nusantara Inti Solusindo';

        // Warna tema resmi perusahaan (Default: #a8001c - Merah Maroon Khas Brand/NIS)
        $themeColor = \App\Models\Settings\SystemSetting::get('hcm_profile', 'theme_color', \App\Models\Settings\SystemSetting::get('system', 'theme_color', '#a8001c'));

        // Logo resolving: utamakan logo khusus HCM, lalu logo sistem
        $logoUrl = null;
        if (!empty($hcmProfile['logo'])) {
            $logoUrl = '/storage/' . $hcmProfile['logo'];
        } elseif (file_exists(storage_path('app/public/system/vHtjoYxbSXFbyBNfqOWKsiy3t310jJOhTlZhYcpD.png'))) {
            $logoUrl = '/storage/system/vHtjoYxbSXFbyBNfqOWKsiy3t310jJOhTlZhYcpD.png';
        } elseif (file_exists(storage_path('app/public/system/logo.svg'))) {
            $logoUrl = '/storage/system/logo.svg';
        }

        $portalTitle = $hcmProfile['career_portal_title'] ?? 'PORTAL KARIR & REKRUTMEN';

        return [
            'name' => $divisionName,
            'division_name' => $divisionName,
            'company_name' => $companyName,
            'portal_title' => $portalTitle,
            'theme_color' => $themeColor,
            'tagline' => $hcmProfile['company_tagline'] ?? $hcmProfile['kop_header_line1'] ?? 'People, Culture & Organizational Development',
            'logo_url' => $logoUrl,
            'logo_initial' => 'HCM',
            'footer_text' => $hcmProfile['document_footer_text'] ?? "© " . date('Y') . " {$companyName} — {$divisionName}. Seluruh Hak Cipta Dilindungi.",
        ];
    }

    /**
     * Simpan pengajuan lamaran kerja dari form publik.
     */
    public function apply(Request $request, string $slug): RedirectResponse
    {
        $job = HcmJobPosting::where('slug', $slug)->firstOrFail();

        if (!$job->is_active || $job->status !== 'Aktif') {
            return redirect()->back()->withErrors([
                'general' => 'Mohon maaf, pendaftaran untuk posisi ini telah ditutup atau kuota pemenuhan telah terpenuhi.'
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'nickname' => ['nullable', 'string', 'max:50'],
            'gender' => ['required', 'string', 'in:Laki Laki,Perempuan'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'phone_number' => ['required', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:100'],
            'education' => ['required', 'string', 'max:50'],
            'major' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'nik_ktp' => ['nullable', 'string', 'max:20'],
            'marital_status' => ['nullable', 'string', 'max:30'],
            'shirt_size' => ['nullable', 'string', 'max:10'],
            'expected_salary' => ['nullable', 'numeric', 'min:0'],
            'available_start_date' => ['nullable', 'date'],
            'experience_summary' => ['nullable', 'string'],
            'skills' => ['nullable', 'string'],
            'resume_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'], // Max 5MB
            'ktp_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'photo_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'], // Pasfoto resmi max 3MB
            'portfolio_url' => ['nullable', 'string', 'max:255'],
        ]);

        $resumeUrl = null;
        if ($request->hasFile('resume_file')) {
            $uploadedResume = \App\Services\GoogleDriveSyncService::uploadFile(
                $request->file('resume_file'),
                \App\Services\GoogleDriveSyncService::FOLDER_RECRUITMENT
            );
            $resumeUrl = $uploadedResume['url'];
        }

        $ktpUrl = null;
        if ($request->hasFile('ktp_file')) {
            $uploadedKtp = \App\Services\GoogleDriveSyncService::uploadFile(
                $request->file('ktp_file'),
                \App\Services\GoogleDriveSyncService::FOLDER_RECRUITMENT
            );
            $ktpUrl = $uploadedKtp['url'];
        }

        $photoPath = null;
        $photoUrl = null;
        if ($request->hasFile('photo_file')) {
            $photoPath = $request->file('photo_file')->store('recruitment/photos', 'public');
            $photoUrl = '/storage/' . $photoPath;
        }

        $applicant = HcmJobApplicant::create([
            'job_posting_id' => $job->id,
            'name' => $validated['name'],
            'nickname' => $validated['nickname'] ?? null,
            'gender' => $validated['gender'],
            'birth_place' => $validated['birth_place'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'phone_number' => $validated['phone_number'],
            'email' => $validated['email'] ?? null,
            'education' => $validated['education'],
            'major' => $validated['major'] ?? null,
            'address' => $validated['address'] ?? null,
            'nik_ktp' => $validated['nik_ktp'] ?? null,
            'marital_status' => $validated['marital_status'] ?? 'Belum Menikah',
            'shirt_size' => $validated['shirt_size'] ?? 'L',
            'expected_salary' => $validated['expected_salary'] ?? null,
            'available_start_date' => $validated['available_start_date'] ?? null,
            'experience_summary' => $validated['experience_summary'] ?? null,
            'skills' => $validated['skills'] ?? null,
            'resume_file_url' => $resumeUrl,
            'ktp_file_url' => $ktpUrl,
            'portfolio_file_url' => $validated['portfolio_url'] ?? null,
            'photo' => $photoPath,
            'photo_url' => $photoUrl,
            'status' => 'SUBMITTED',
        ]);

        return redirect()->back()->with('success', "Terima kasih, lamaran Anda telah berhasil dikirim dengan Kode Registrasi: {$applicant->applicant_code}. Tim HRD akan menghubungi Anda melalui WhatsApp / Email.");
    }
}
