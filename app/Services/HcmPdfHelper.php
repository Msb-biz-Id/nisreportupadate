<?php

namespace App\Services;

use App\Models\Hcm\HcmEmployee;
use App\Models\Settings\SystemSetting;
use Illuminate\Support\Facades\Storage;

class HcmPdfHelper
{
    /**
     * Mengambil konfigurasi profil instansi, divisi, kop surat resmi, pejabat penandatangan,
     * serta Base64 URI untuk logo, TTD digital, dan stempel untuk rendering DomPDF.
     *
     * @param HcmEmployee|null $employee
     * @param array $overrides
     * @return array
     */
    public static function getProfileData(?HcmEmployee $employee = null, array $overrides = []): array
    {
        $s = fn(string $key, mixed $default = null) => SystemSetting::get('hcm_profile', $key, $default);

        // Identitas Perusahaan & Divisi
        $companySetting = $s('company_name');
        $companyName = !empty($employee?->legal_entity)
            ? $employee->legal_entity
            : (!empty($companySetting) && $companySetting !== 'PROTRACK' ? $companySetting : 'PT Nusantara Inti Solusindo');

        $divisionName = $s('division_name', 'Divisi Human Capital Management');
        $tagline = $s('company_tagline', 'People, Culture & Organizational Development');
        $address = $s('company_address', SystemSetting::get('company', 'address', 'Klaten, Jawa Tengah'));
        $city = $s('company_city', 'Klaten');
        $phone = $s('company_phone', '0812-3456-7890');
        $email = $s('company_email', 'hrd@nisgroup.co.id');
        $website = $s('company_website', 'https://nisgroup.co.id');
        $socials = $s('company_socials', '@nisgroup.apparel');

        // Header & Footer
        $kopHeader1 = $s('kop_header_line1', 'DIVISI HUMAN CAPITAL & MANAJEMEN OPERASIONAL');
        $kopHeader2 = $s('kop_header_line2', 'No. Izin KBLI 14111 / 14120 - Manajemen SDM Terpadu');
        $footerText = $s('document_footer_text', 'Dokumen resmi diterbitkan otomatis oleh Sistem Kepegawaian terintegrasi.');
        $footerDisclaimer = $s('document_footer_disclaimer', 'Keabsahan dokumen dapat diverifikasi langsung melalui portal HCM atau QR code tertera.');

        // Pejabat Penandatangan Resmi (Automatic Signer)
        $signerName = $s('signer_name', 'Ahmad Fauzi, S.Psi., CHRP');
        $signerRole = $s('signer_role', 'Head of Human Capital Management');
        $signerNik = $s('signer_nik', 'HCM-2021-001');
        $showSignature = (bool) $s('show_signature_on_pdf', true);
        $showStamp = (bool) $s('show_stamp_on_pdf', true);

        // Warna Tema Utama Resmi Perusahaan (Default: #a8001c - Merah Maroon Khas Brand/NIS)
        $themeColor = $s('theme_color', SystemSetting::get('system', 'theme_color', '#a8001c'));

        // Convert Media to Base64 (Bulletproof rendering for DomPDF)
        $defaultLogoFallback = file_exists(storage_path('app/public/system/vHtjoYxbSXFbyBNfqOWKsiy3t310jJOhTlZhYcpD.png'))
            ? storage_path('app/public/system/vHtjoYxbSXFbyBNfqOWKsiy3t310jJOhTlZhYcpD.png')
            : (file_exists(storage_path('app/public/system/logo.svg')) ? storage_path('app/public/system/logo.svg') : public_path('images/logo.png'));

        $logoBase64 = self::fileToBase64($s('logo'), $defaultLogoFallback);
        $signatureBase64 = self::fileToBase64($s('signature'));
        $stampBase64 = self::fileToBase64($s('stamp'));

        // Kontak terkompilasi
        $contacts = array_values(array_filter([$phone, $email, $website, $socials]));

        $data = [
            'theme_color' => $themeColor,
            'company_name' => $companyName,
            'division_name' => $divisionName,
            'company_tagline' => $tagline,
            'company_address' => $address,
            'company_city' => $city,
            'company_phone' => $phone,
            'company_email' => $email,
            'company_website' => $website,
            'company_socials' => $socials,
            'contacts' => $contacts,

            'kop_header_line1' => $kopHeader1,
            'kop_header_line2' => $kopHeader2,
            'document_footer_text' => $footerText,
            'document_footer_disclaimer' => $footerDisclaimer,

            'signer_name' => $signerName,
            'signer_role' => $signerRole,
            'signer_nik' => $signerNik,
            'show_signature_on_pdf' => $showSignature,
            'show_stamp_on_pdf' => $showStamp,

            'logo_base64' => $logoBase64,
            'signature_base64' => $signatureBase64,
            'stamp_base64' => $stampBase64,

            'current_date_formatted' => now()->isoFormat('D MMMM Y'),
            'current_datetime_formatted' => now()->isoFormat('D MMMM Y, HH:mm') . ' WIB',
        ];

        return array_merge($data, $overrides);
    }

    /**
     * Konversi file di storage public atau path absolut lokal ke data URI Base64.
     */
    protected static function fileToBase64(?string $storageRelativePath, ?string $fallbackLocalPath = null): ?string
    {
        if ($storageRelativePath) {
            $fullPath = storage_path('app/public/' . $storageRelativePath);
            if (file_exists($fullPath) && is_readable($fullPath)) {
                $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                $mime = match ($ext) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    'svg' => 'image/svg+xml',
                    default => 'image/png',
                };
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
            }
        }

        if ($fallbackLocalPath && file_exists($fallbackLocalPath) && is_readable($fallbackLocalPath)) {
            $ext = strtolower(pathinfo($fallbackLocalPath, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
                default => 'image/png',
            };
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fallbackLocalPath));
        }

        return null;
    }

    /**
     * Konversi nominal angka rupiah menjadi teks terbilang bahasa Indonesia.
     */
    public static function terbilang(float|int $angka): string
    {
        $angka = abs((int) floor($angka));
        if ($angka === 0) {
            return 'Nol Rupiah';
        }

        $bilangan = [
            '', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima',
            'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas',
        ];

        $terbilangDasar = function ($n) use (&$terbilangDasar, $bilangan): string {
            $n = (int) $n;
            if ($n < 12) {
                return $bilangan[$n];
            } elseif ($n < 20) {
                return $terbilangDasar($n - 10) . ' Belas';
            } elseif ($n < 100) {
                return $terbilangDasar((int) ($n / 10)) . ' Puluh ' . $terbilangDasar($n % 10);
            } elseif ($n < 200) {
                return 'Seratus ' . $terbilangDasar($n - 100);
            } elseif ($n < 1000) {
                return $terbilangDasar((int) ($n / 100)) . ' Ratus ' . $terbilangDasar($n % 100);
            } elseif ($n < 2000) {
                return 'Seribu ' . $terbilangDasar($n - 1000);
            } elseif ($n < 1000000) {
                return $terbilangDasar((int) ($n / 1000)) . ' Ribu ' . $terbilangDasar($n % 1000);
            } elseif ($n < 1000000000) {
                return $terbilangDasar((int) ($n / 1000000)) . ' Juta ' . $terbilangDasar($n % 1000000);
            } elseif ($n < 1000000000000) {
                return $terbilangDasar((int) ($n / 1000000000)) . ' Miliar ' . $terbilangDasar($n % 1000000000);
            } elseif ($n < 1000000000000000) {
                return $terbilangDasar((int) ($n / 1000000000000)) . ' Triliun ' . $terbilangDasar($n % 1000000000000);
            }
            return '';
        };

        $teks = trim(preg_replace('/\s+/', ' ', $terbilangDasar($angka)));
        return $teks ? $teks . ' Rupiah' : 'Nol Rupiah';
    }
}
