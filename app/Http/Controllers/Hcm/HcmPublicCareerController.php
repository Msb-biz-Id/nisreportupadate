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
     * Tampilkan formulir pendaftaran online publik berdasarkan slug loker (/karir/{slug}).
     */
    public function show(string $slug): Response
    {
        $job = HcmJobPosting::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // Increment hit counter
        $job->increment('views_count');

        $hcmProfile = \App\Models\Settings\SystemSetting::getGroup('hcm_profile');
        $companyName = $hcmProfile['company_name'] ?? config('app.name', 'PT Nusantara Inti Solusindo');
        $company = [
            'name' => $companyName,
            'tagline' => $hcmProfile['kop_header_line1'] ?? 'Human Capital Management System',
            'logo_url' => !empty($hcmProfile['logo']) ? '/storage/' . $hcmProfile['logo'] : null,
            'footer_text' => $hcmProfile['document_footer_text'] ?? "© " . date('Y') . " {$companyName} - All Rights Reserved. Human Capital Management System.",
        ];

        return Inertia::render('Career/Apply', [
            'job' => $job,
            'company' => $company,
        ]);
    }

    /**
     * Simpan pengajuan lamaran kerja dari form publik.
     */
    public function apply(Request $request, string $slug): RedirectResponse
    {
        $job = HcmJobPosting::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

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
            $resumeUrl = $request->file('resume_file')->store('recruitment/resumes', 'public');
            $resumeUrl = '/storage/' . $resumeUrl;
        }

        $ktpUrl = null;
        if ($request->hasFile('ktp_file')) {
            $ktpUrl = $request->file('ktp_file')->store('recruitment/ktp', 'public');
            $ktpUrl = '/storage/' . $ktpUrl;
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
