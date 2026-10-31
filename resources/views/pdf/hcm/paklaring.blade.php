<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan Kerja – {{ $employee->name }}</title>
    <style>
        @font-face { font-family: 'Inter'; font-weight: 400; src: url('{{ public_path("fonts/Inter-Regular.ttf") }}') format('truetype'); }
        @font-face { font-family: 'Inter'; font-weight: 700; src: url('{{ public_path("fonts/Inter-Bold.ttf") }}') format('truetype'); }

        @page { size: A4 portrait; margin: 28px 34px 26px 34px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', Arial, Helvetica, sans-serif; font-size: 10pt; color: #1e293b; line-height: 1.5; background: #fff; width: 100%; }

        /* JUDUL DOKUMEN */
        .doc-header { text-align: center; margin: 18px 0 20px 0; }
        .doc-title { font-size: 14pt; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 1.2px; }
        .doc-subtitle { font-size: 9pt; font-weight: 600; color: #64748b; letter-spacing: 0.5px; margin-top: 1px; }
        .doc-underline { width: 180px; height: 2px; background: #a8001c; margin: 5px auto 6px auto; border-radius: 1px; }
        .doc-number { font-size: 9.5pt; font-weight: 600; color: #334155; }

        /* BADAN SURAT */
        .letter-content { font-size: 10pt; line-height: 1.75; text-align: justify; color: #1e293b; }
        .letter-content p { margin-bottom: 12px; }

        /* TABEL DATA KARYAWAN */
        table.employee-table { width: 100%; border-collapse: collapse; margin: 12px 0 16px 0; }
        table.employee-table td { padding: 4.5px 6px; font-size: 10pt; vertical-align: top; }
        table.employee-table td.label { width: 190px; color: #475569; font-weight: 600; }
        table.employee-table td.colon { width: 14px; color: #64748b; text-align: center; }
        table.employee-table td.val { color: #0f172a; font-weight: 600; }

        /* SIGN SECTION */
        .sign-wrapper { width: 100%; margin-top: 28px; }
    </style>
</head>
<body>

{{-- 1. KOP SURAT BAKU RESMI INDONESIA --}}
@include('pdf.hcm.partials.kop', ['profile' => $profile])

{{-- 2. JUDUL DAN NOMOR SURAT --}}
<div class="doc-header">
    <div class="doc-title">Surat Keterangan Pengalaman Kerja</div>
    <div class="doc-subtitle">CERTIFICATE OF EMPLOYMENT</div>
    <div class="doc-underline"></div>
    <div class="doc-number">
        Nomor: {{ sprintf('SKP/%s/%s/%d', str_pad((string)$employee->id, 3, '0', STR_PAD_LEFT), now()->format('m'), now()->year) }}
    </div>
</div>

{{-- 3. BADAN SURAT --}}
<div class="letter-content">
    <p>
        Yang bertanda tangan di bawah ini, Manajemen <strong>{{ $profile['division_name'] }} – {{ $profile['company_name'] }}</strong>, dengan ini menerangkan dengan sebenarnya bahwa:
    </p>

    <table class="employee-table">
        <tr>
            <td class="label">Nama Lengkap</td>
            <td class="colon">:</td>
            <td class="val"><strong>{{ strtoupper($employee->name) }}</strong></td>
        </tr>
        <tr>
            <td class="label">Nomor Induk Kependudukan (NIK)</td>
            <td class="colon">:</td>
            <td class="val">{{ $employee->nik ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Nomor Induk Karyawan (NIP)</td>
            <td class="colon">:</td>
            <td class="val">{{ $employee->employee_code ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tempat / Tanggal Lahir</td>
            <td class="colon">:</td>
            <td class="val">{{ $employee->birth_place ?? '-' }}, {{ $employee->birth_date ? \Carbon\Carbon::parse($employee->birth_date)->isoFormat('D MMMM Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Alamat Tinggal Terakhir</td>
            <td class="colon">:</td>
            <td class="val">{{ $employee->address ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Departemen / Divisi Terakhir</td>
            <td class="colon">:</td>
            <td class="val">{{ $employee->department ? ($employee->department . ($employee->division ? ' — ' . $employee->division : '')) : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Jabatan Terakhir</td>
            <td class="colon">:</td>
            <td class="val">{{ $employee->position ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Masa Bekerja</td>
            <td class="colon">:</td>
            <td class="val">
                <strong>{{ $employee->join_date ? \Carbon\Carbon::parse($employee->join_date)->isoFormat('D MMMM Y') : '-' }}</strong>
                &nbsp;s.d.&nbsp;
                <strong>{{ ($employee->resign_date ?? null) ? \Carbon\Carbon::parse($employee->resign_date)->isoFormat('D MMMM Y') : now()->isoFormat('D MMMM Y') }}</strong>
                @php
                    $masa = \Carbon\Carbon::parse($employee->join_date)->diffForHumans(
                        \Carbon\Carbon::parse($employee->resign_date ?? now()), true, false, 2
                    );
                @endphp
                ({{ $masa }})
            </td>
        </tr>
    </table>

    <p>
        Menerangkan bahwa yang bersangkutan benar-benar pernah bekerja pada perusahaan kami dalam kurun waktu tersebut dengan status kepegawaian terakhir sebagai <strong>{{ $employee->employment_status ?? 'Karyawan' }}</strong>.
    </p>

    <p>
        Selama masa kerja yang bersangkutan, telah menunjukkan dedikasi, integritas, loyalitas, dan tanggung jawab yang baik terhadap seluruh tugas yang diamanahkan. Kami menyampaikan terima kasih dan apresiasi yang setinggi-tingginya atas sumbangsih dan kerja sama yang telah diberikan selama masa kerja.
    </p>

    <p>
        Demikian Surat Keterangan Pengalaman Kerja ini diterbitkan secara sah dan benar untuk dapat dipergunakan sebagaimana mestinya.
    </p>
</div>

{{-- 4. BLOK TANDA TANGAN RESMI HCM --}}
<table class="sign-wrapper" style="border-collapse: collapse;">
    <tr>
        <td style="width: 50%; vertical-align: bottom; font-size: 8pt; color: #64748b;">
            <div style="padding-bottom: 8px;">
                Kode Verifikasi Berkas: <strong>{{ strtoupper(substr(hash('sha256', $employee->id . '|' . $employee->employee_code), 0, 10)) }}</strong>
            </div>
        </td>
        <td style="width: 50%; text-align: right; vertical-align: top;">
            @include('pdf.hcm.partials.ttd', ['profile' => $profile])
        </td>
    </tr>
</table>

{{-- 5. CATATAN KAKI DOKUMEN --}}
@include('pdf.hcm.partials.footer', ['profile' => $profile])

</body>
</html>
