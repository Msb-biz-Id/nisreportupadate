<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Models\Settings\SystemSetting;
use App\Services\ActivityLogger;
use App\Support\UrlHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HcmSettingController extends Controller
{
    /**
     * Memastikan user memiliki izin akses pengaturan HCM.
     */
    protected function authorizeAccess(): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $allowed = $user?->is_superadmin 
            || \Illuminate\Support\Facades\Gate::allows('hcm.manage-settings') 
            || ($user && method_exists($user, 'hasRole') && $user->hasRole(['superadmin', 'admin_hcm']));

        if (! $allowed) {
            abort(403, 'Akses ditolak: Anda tidak memiliki otoritas untuk mengakses Pengaturan Kepegawaian (HCM).');
        }
    }

    /**
     * Halaman Utama Pengaturan HCM (Profil Instansi, Kop Surat, Medsos, Lembur Dinamis, & Uang Makan).
     */
    public function index(Request $request): Response
    {
        $this->authorizeAccess();

        /** @var \Illuminate\Filesystem\FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');
        $logo = SystemSetting::get('hcm_profile', 'logo');
        $logoUrl = null;
        if ($logo) {
            $logoUrl = UrlHelper::clean($publicDisk->url($logo), $request);
        }

        return Inertia::render('Hcm/Settings/Index', [
            'profile' => [
                'company_name' => SystemSetting::get('hcm_profile', 'company_name', config('app.name', 'NISGroup')),
                'company_tagline' => SystemSetting::get('hcm_profile', 'company_tagline', 'Human Capital Management & Operations'),
                'company_address' => SystemSetting::get('hcm_profile', 'company_address', 'Klaten, Jawa Tengah'),
                'company_city' => SystemSetting::get('hcm_profile', 'company_city', 'Klaten'),
                'company_email' => SystemSetting::get('hcm_profile', 'company_email', 'hrd@nisgroup.co.id'),
                'company_phone' => SystemSetting::get('hcm_profile', 'company_phone', '0812-3456-7890'),
                'company_website' => SystemSetting::get('hcm_profile', 'company_website', 'https://nisgroup.co.id'),
                'company_socials' => SystemSetting::get('hcm_profile', 'company_socials', '@nisgroup.apparel'),
                'logo' => $logo,
                'logo_url' => $logoUrl,
                'kop_header_line1' => SystemSetting::get('hcm_profile', 'kop_header_line1', 'DIVISI HUMAN CAPITAL & MANAJEMEN OPERASIONAL'),
                'kop_header_line2' => SystemSetting::get('hcm_profile', 'kop_header_line2', 'No. Izin KBLI 14111 / 14120 - Manajemen SDM Terpadu'),
                'document_footer_text' => SystemSetting::get('hcm_profile', 'document_footer_text', 'Dokumen resmi diterbitkan otomatis oleh Sistem Kepegawaian terintegrasi.'),
                'document_footer_disclaimer' => SystemSetting::get('hcm_profile', 'document_footer_disclaimer', 'Keabsahan dokumen dapat diverifikasi langsung melalui portal HCM atau QR code tertera.'),
            ],
            'overtime' => [
                'weekday_hourly_rate' => (float) SystemSetting::get('hcm_overtime', 'weekday_hourly_rate', 10000),
                'weekday_first_half_rate' => (float) SystemSetting::get('hcm_overtime', 'weekday_first_half_rate', 5000),
                'weekend_hourly_rate' => (float) SystemSetting::get('hcm_overtime', 'weekend_hourly_rate', 15000),
                'weekend_first_half_rate' => (float) SystemSetting::get('hcm_overtime', 'weekend_first_half_rate', 10000),
                'coa_code' => (string) SystemSetting::get('hcm_overtime', 'coa_code', '5-50100'),
                'coa_name' => (string) SystemSetting::get('hcm_overtime', 'coa_name', 'Beban Upah Lembur Karyawan Pabrik'),
            ],
            'meal_allowance' => [
                'monthly_rate' => (float) SystemSetting::get('hcm_meal_allowance', 'monthly_rate', 250000),
                'alpha_deduction_rate' => (float) SystemSetting::get('hcm_meal_allowance', 'alpha_deduction_rate', 25000),
                'half_day_deduction_rate' => (float) SystemSetting::get('hcm_meal_allowance', 'half_day_deduction_rate', 12500),
                'max_late_tolerance' => (int) SystemSetting::get('hcm_meal_allowance', 'max_late_tolerance', 3),
                'max_permit_bonus_limit' => (int) SystemSetting::get('hcm_meal_allowance', 'max_permit_bonus_limit', 2),
                'coa_code' => (string) SystemSetting::get('hcm_meal_allowance', 'coa_code', '5-50200'),
                'coa_name' => (string) SystemSetting::get('hcm_meal_allowance', 'coa_name', 'Beban Uang Makan Karyawan Pabrik'),
            ],
            'payroll' => [
                'cutoff_day' => (int) SystemSetting::get('hcm_payroll', 'cutoff_day', 25),
            ],
        ]);
    }

    /**
     * Perbarui Profil Instansi, Kop Surat, Medsos, Logo & Footer Dokumen HCM.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'company_tagline' => ['nullable', 'string', 'max:255'],
            'company_address' => ['required', 'string', 'max:500'],
            'company_city' => ['nullable', 'string', 'max:100'],
            'company_email' => ['nullable', 'email', 'max:150'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_website' => ['nullable', 'string', 'max:200'],
            'company_socials' => ['nullable', 'string', 'max:200'],
            'kop_header_line1' => ['nullable', 'string', 'max:255'],
            'kop_header_line2' => ['nullable', 'string', 'max:255'],
            'document_footer_text' => ['nullable', 'string', 'max:500'],
            'document_footer_disclaimer' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:3072'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        SystemSetting::set('hcm_profile', 'company_name', $validated['company_name'], false, 'Nama resmi instansi/perusahaan pada kop dokumen HCM');
        SystemSetting::set('hcm_profile', 'company_tagline', $validated['company_tagline'] ?? '', false, 'Tagline atau sub-identitas instansi');
        SystemSetting::set('hcm_profile', 'company_address', $validated['company_address'], false, 'Alamat domisili instansi/pabrik pada kop dokumen');
        SystemSetting::set('hcm_profile', 'company_city', $validated['company_city'] ?? '', false, 'Kota domisili kantor HCM');
        SystemSetting::set('hcm_profile', 'company_email', $validated['company_email'] ?? '', false, 'Alamat email korespondensi resmi HCM');
        SystemSetting::set('hcm_profile', 'company_phone', $validated['company_phone'] ?? '', false, 'Nomor telepon atau hotline resmi HCM');
        SystemSetting::set('hcm_profile', 'company_website', $validated['company_website'] ?? '', false, 'Situs web resmi perusahaan');
        SystemSetting::set('hcm_profile', 'company_socials', $validated['company_socials'] ?? '', false, 'Akun media sosial resmi HCM');
        SystemSetting::set('hcm_profile', 'kop_header_line1', $validated['kop_header_line1'] ?? '', false, 'Teks baris 1 header kop surat');
        SystemSetting::set('hcm_profile', 'kop_header_line2', $validated['kop_header_line2'] ?? '', false, 'Teks baris 2 header kop surat (Izin/KBLI)');
        SystemSetting::set('hcm_profile', 'document_footer_text', $validated['document_footer_text'] ?? '', false, 'Catatan kaki / footer dokumen resmi HCM');
        SystemSetting::set('hcm_profile', 'document_footer_disclaimer', $validated['document_footer_disclaimer'] ?? '', false, 'Teks klausul disclaimer keabsahan dokumen HCM');

        // Sinkronkan juga ke general company address agar modul lain tetap konsisten
        SystemSetting::set('company', 'address', $validated['company_address'], false, 'Alamat perusahaan terpadu');

        if ($request->boolean('remove_logo')) {
            $oldLogo = SystemSetting::get('hcm_profile', 'logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
            SystemSetting::set('hcm_profile', 'logo', null, false, 'Logo resmi kop dokumen HCM');
        } elseif ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('hcm/logos', 'public');
            SystemSetting::set('hcm_profile', 'logo', $path, false, 'Logo resmi kop dokumen HCM');
        }

        ActivityLogger::log('update', 'hcm', null, 'Memperbarui profil instansi, kop surat, dan footer dokumen HCM.');

        return back()->with('success', 'Profil instansi, kop surat, kontak medsos, dan footer dokumen HCM berhasil disimpan.');
    }

    /**
     * Perbarui Konfigurasi Tarif Lembur Dinamis & Akun COA Akuntansi.
     */
    public function updateOvertime(Request $request): RedirectResponse
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'weekday_hourly_rate' => ['required', 'numeric', 'min:0'],
            'weekday_first_half_rate' => ['required', 'numeric', 'min:0'],
            'weekend_hourly_rate' => ['required', 'numeric', 'min:0'],
            'weekend_first_half_rate' => ['required', 'numeric', 'min:0'],
            'coa_code' => ['required', 'string', 'max:50'],
            'coa_name' => ['nullable', 'string', 'max:150'],
        ]);

        SystemSetting::set('hcm_overtime', 'weekday_hourly_rate', (string) $validated['weekday_hourly_rate'], false, 'Tarif dasar lembur hari kerja per jam');
        SystemSetting::set('hcm_overtime', 'weekday_first_half_rate', (string) $validated['weekday_first_half_rate'], false, 'Tarif 30 menit pertama lembur hari kerja');
        SystemSetting::set('hcm_overtime', 'weekend_hourly_rate', (string) $validated['weekend_hourly_rate'], false, 'Tarif dasar lembur hari libur/weekend per jam');
        SystemSetting::set('hcm_overtime', 'weekend_first_half_rate', (string) $validated['weekend_first_half_rate'], false, 'Tarif 30 menit pertama lembur hari libur/weekend');
        SystemSetting::set('hcm_overtime', 'coa_code', (string) $validated['coa_code'], false, 'Kode Akun Akuntansi / COA Beban Lembur');
        SystemSetting::set('hcm_overtime', 'coa_name', (string) ($validated['coa_name'] ?? 'Beban Upah Lembur Karyawan Pabrik'), false, 'Nama Akun COA Beban Lembur');

        ActivityLogger::log('update', 'hcm', null, 'Memperbarui konfigurasi tarif lembur dinamis dari modul Pengaturan HCM.');

        return back()->with('success', 'Pengaturan tarif lembur dinamis dan akun COA akuntansi berhasil diperbarui.');
    }

    /**
     * Perbarui Konfigurasi Uang Makan Bulanan & Batas Cut-Off Penggajian.
     */
    public function updateMealAllowance(Request $request): RedirectResponse
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'monthly_rate' => ['required', 'numeric', 'min:0'],
            'alpha_deduction_rate' => ['required', 'numeric', 'min:0'],
            'half_day_deduction_rate' => ['required', 'numeric', 'min:0'],
            'max_late_tolerance' => ['required', 'integer', 'min:0'],
            'max_permit_bonus_limit' => ['required', 'integer', 'min:0'],
            'coa_code' => ['required', 'string', 'max:50'],
            'coa_name' => ['nullable', 'string', 'max:150'],
            'cutoff_day' => ['nullable', 'integer', 'between:1,31'],
        ]);

        SystemSetting::set('hcm_meal_allowance', 'monthly_rate', (string) $validated['monthly_rate'], false, 'Tarif standar uang makan per bulan');
        SystemSetting::set('hcm_meal_allowance', 'alpha_deduction_rate', (string) $validated['alpha_deduction_rate'], false, 'Nominal potongan per hari alpha/mangkir');
        SystemSetting::set('hcm_meal_allowance', 'half_day_deduction_rate', (string) $validated['half_day_deduction_rate'], false, 'Nominal potongan per hari setengah hari');
        SystemSetting::set('hcm_meal_allowance', 'max_late_tolerance', (string) $validated['max_late_tolerance'], false, 'Batas toleransi keterlambatan sebelum status hold');
        SystemSetting::set('hcm_meal_allowance', 'max_permit_bonus_limit', (string) $validated['max_permit_bonus_limit'], false, 'Batas izin pribadi sebelum pembatalan bonus');
        SystemSetting::set('hcm_meal_allowance', 'coa_code', (string) $validated['coa_code'], false, 'Kode Akun Akuntansi / COA Beban Uang Makan');
        SystemSetting::set('hcm_meal_allowance', 'coa_name', (string) ($validated['coa_name'] ?? 'Beban Uang Makan Karyawan Pabrik'), false, 'Nama Akun COA Uang Makan');

        if (!empty($validated['cutoff_day'])) {
            SystemSetting::set('hcm_payroll', 'cutoff_day', (string) $validated['cutoff_day'], false, 'Tanggal batas cut-off payroll bulanan');
        }

        ActivityLogger::log('update', 'hcm', null, 'Memperbarui konfigurasi uang makan dan cut-off payroll dari modul Pengaturan HCM.');

        return back()->with('success', 'Pengaturan uang makan dan batas cut-off penggajian berhasil diperbarui.');
    }
}
