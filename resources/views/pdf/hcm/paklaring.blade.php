<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Paklaring – {{ $employee->name }}</title>
    <style>
        @font-face { font-family: 'Inter'; font-weight: 400; src: url('{{ public_path("fonts/Inter-Regular.ttf") }}') format('truetype'); }
        @font-face { font-family: 'Inter'; font-weight: 700; src: url('{{ public_path("fonts/Inter-Bold.ttf") }}') format('truetype'); }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', Arial, sans-serif; font-size: 10pt; color: #1a1a1a; background: #fff; padding: 0; }

        /* KOP SURAT */
        .kop { display: table; width: 100%; border-bottom: 4px solid #1e3a5f; padding-bottom: 12px; margin-bottom: 20px; }
        .kop-logo { display: table-cell; width: 90px; vertical-align: middle; }
        .kop-logo img { width: 80px; height: 80px; object-fit: contain; }
        .kop-info { display: table-cell; vertical-align: middle; padding-left: 14px; }
        .kop-info .company { font-size: 16pt; font-weight: 700; color: #1e3a5f; }
        .kop-info .sub { font-size: 9pt; color: #4a5568; margin-top: 3px; }
        .kop-info .sub2 { font-size: 8.5pt; color: #718096; margin-top: 2px; }

        /* JUDUL */
        .doc-title { text-align: center; margin: 20px 0 6px 0; }
        .doc-title h1 { font-size: 16pt; font-weight: 700; color: #1e3a5f; text-transform: uppercase; letter-spacing: 2px; }
        .doc-title .underline { width: 200px; height: 3px; background: #1e3a5f; margin: 6px auto 0 auto; border-radius: 2px; }
        .doc-number { text-align: center; font-size: 9pt; color: #718096; margin-bottom: 24px; }

        /* BODY SURAT */
        .letter-body { line-height: 1.9; font-size: 10.5pt; text-align: justify; color: #1a1a1a; }
        .letter-body p { margin-bottom: 14px; }
        .letter-body .opening { margin-bottom: 20px; }

        /* TABEL DATA RINGKAS */
        table.data { width: 100%; border-collapse: collapse; margin: 16px 0; }
        table.data tr { border-bottom: 1px solid #e2e8f0; }
        table.data td { padding: 8px 10px; font-size: 10.5pt; }
        table.data td.label { width: 180px; color: #4a5568; font-weight: 700; }
        table.data td.colon { width: 12px; color: #4a5568; }
        table.data td.value { color: #1a1a1a; font-weight: 600; }

        /* TTD */
        .ttd-section { margin-top: 40px; text-align: right; }
        .ttd-place { font-size: 10.5pt; margin-bottom: 60px; }
        .ttd-line { width: 200px; border-top: 1px solid #1a1a1a; margin: 0 0 6px auto; }
        .ttd-name { font-size: 11pt; font-weight: 700; }
        .ttd-role { font-size: 9.5pt; color: #4a5568; }

        /* STEMPEL WATERMARK AREA */
        .stamp-area { display: inline-block; width: 120px; height: 120px; border: 2px dashed #e2e8f0; border-radius: 50%; text-align: center; line-height: 120px; font-size: 7.5pt; color: #cbd5e0; position: absolute; margin-top: -80px; margin-left: 20px; }

        .footer-note { margin-top: 30px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 8pt; color: #a0aec0; text-align: center; }
    </style>
</head>
<body>

<!-- KOP SURAT -->
<div class="kop">
    <div class="kop-logo">
        @php
            $hcmLogo = \App\Models\Settings\SystemSetting::get('hcm_profile', 'logo');
            $logoPath = $hcmLogo && file_exists(storage_path('app/public/' . $hcmLogo)) 
                ? storage_path('app/public/' . $hcmLogo) 
                : (file_exists(public_path('images/logo.png')) ? public_path('images/logo.png') : null);
            $logoBase64 = $logoPath ? 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($logoPath)) : null;
            $companyName = $employee->legal_entity ?: \App\Models\Settings\SystemSetting::get('hcm_profile', 'company_name', config('app.name', 'NISGroup'));
            $companyAddress = \App\Models\Settings\SystemSetting::get('hcm_profile', 'company_address', \App\Models\Settings\SystemSetting::get('company', 'address', 'Klaten, Jawa Tengah'));
            $kopLine1 = \App\Models\Settings\SystemSetting::get('hcm_profile', 'kop_header_line1', 'Divisi Human Capital & Manajemen Sumber Daya Manusia');
            $contacts = array_filter([
                \App\Models\Settings\SystemSetting::get('hcm_profile', 'company_phone'),
                \App\Models\Settings\SystemSetting::get('hcm_profile', 'company_email'),
                \App\Models\Settings\SystemSetting::get('hcm_profile', 'company_socials'),
            ]);
        @endphp
        @if($logoBase64)
            <img src="{{ $logoBase64 }}" alt="Logo" style="max-height:75px; max-width:110px; object-fit:contain;">
        @else
            <div style="width:75px;height:75px;background:#1e3a5f;border-radius:10px;text-align:center;line-height:75px;">
                <span style="color:#fff;font-size:20pt;font-weight:700;">{{ substr($companyName, 0, 1) }}</span>
            </div>
        @endif
    </div>
    <div class="kop-info">
        <div class="company">{{ strtoupper($companyName) }}</div>
        <div class="sub">{{ $companyAddress }}</div>
        <div class="sub2">{{ $kopLine1 }}@if(count($contacts) > 0) &bull; {{ implode(' | ', $contacts) }}@endif</div>
    </div>
</div>

<!-- JUDUL DOKUMEN -->
<div class="doc-title">
    <h1>Surat Keterangan Pengalaman Kerja</h1>
    <div class="underline"></div>
</div>
<div class="doc-number">
    No: {{ sprintf('SKP/%s/%s/%d', str_pad($employee->id, 3, '0', STR_PAD_LEFT), now()->format('m'), now()->year) }}
</div>

<!-- BADAN SURAT -->
<div class="letter-body">
    <p class="opening">Yang bertanda tangan di bawah ini, kami selaku Manajemen <strong>{{ $employee->legal_entity ?? config('app.name', 'NISGroup') }}</strong>, dengan ini menerangkan bahwa:</p>

    <table class="data">
        <tr>
            <td class="label">Nama Lengkap</td>
            <td class="colon">:</td>
            <td class="value">{{ strtoupper($employee->name) }}</td>
        </tr>
        <tr>
            <td class="label">No. KTP (NIK)</td>
            <td class="colon">:</td>
            <td class="value">{{ $employee->nik ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tempat / Tgl. Lahir</td>
            <td class="colon">:</td>
            <td class="value">{{ $employee->birth_place ?? '-' }}, {{ $employee->birth_date ? \Carbon\Carbon::parse($employee->birth_date)->isoFormat('D MMMM Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Alamat Terakhir</td>
            <td class="colon">:</td>
            <td class="value">{{ $employee->address ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Jabatan Terakhir</td>
            <td class="colon">:</td>
            <td class="value">{{ $employee->position ?? '-' }} – {{ $employee->department ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Masuk Kerja</td>
            <td class="colon">:</td>
            <td class="value">{{ $employee->join_date ? \Carbon\Carbon::parse($employee->join_date)->isoFormat('D MMMM Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Berakhir Kerja</td>
            <td class="colon">:</td>
            <td class="value">{{ ($employee->resign_date ?? null) ? \Carbon\Carbon::parse($employee->resign_date)->isoFormat('D MMMM Y') : now()->isoFormat('D MMMM Y') }}</td>
        </tr>
        @php
            $masaKerja = \Carbon\Carbon::parse($employee->join_date)->diffForHumans(
                \Carbon\Carbon::parse($employee->resign_date ?? now()), true, false, 2
            );
        @endphp
        <tr>
            <td class="label">Lama Bekerja</td>
            <td class="colon">:</td>
            <td class="value"><strong>{{ $masaKerja }}</strong></td>
        </tr>
    </table>

    <p>
        Bahwa yang bersangkutan <strong>telah bekerja di perusahaan kami</strong> terhitung sejak
        <strong>{{ $employee->join_date ? \Carbon\Carbon::parse($employee->join_date)->isoFormat('D MMMM Y') : '-' }}</strong>
        dan mengakhiri hubungan kerja pada
        <strong>{{ ($employee->resign_date ?? null) ? \Carbon\Carbon::parse($employee->resign_date)->isoFormat('D MMMM Y') : now()->isoFormat('D MMMM Y') }}</strong>
        dengan status kepegawaian terakhir sebagai
        <strong>{{ $employee->employment_status ?? 'Karyawan' }}</strong>.
    </p>

    <p>
        Selama bekerja di perusahaan kami, yang bersangkutan telah menunjukkan <strong>dedikasi, integritas kerja, dan tanggung jawab yang baik</strong> dalam mengemban tugas-tugasnya. Surat keterangan ini kami buat dengan sebenar-benarnya untuk dapat dipergunakan sebagaimana mestinya.
    </p>
</div>

<!-- TTD -->
<div class="ttd-section">
    <div class="ttd-place">{{ \App\Models\Settings\SystemSetting::get('hcm_profile', 'company_city', 'Klaten') }}, {{ now()->isoFormat('D MMMM Y') }}</div>
    <div class="ttd-line"></div>
    <div class="ttd-name">Pimpinan / HR Manager</div>
    <div class="ttd-role">{{ $companyName }}</div>
</div>

<div class="footer-note">
    {{ \App\Models\Settings\SystemSetting::get('hcm_profile', 'document_footer_text', 'Dokumen resmi diterbitkan otomatis oleh Sistem Kepegawaian terintegrasi.') }}<br>
    {{ \App\Models\Settings\SystemSetting::get('hcm_profile', 'document_footer_disclaimer', 'Surat Keterangan ini sah tanpa tanda tangan basah apabila dilengkapi stempel resmi atau verifikasi sistem HCM.') }}
</div>

</body>
</html>
