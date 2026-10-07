@php
    $p = $profile ?? \App\Services\HcmPdfHelper::getProfileData();
    $age = $applicant->birth_date ? $applicant->birth_date->age : null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Profil Pelamar – {{ $applicant->name }}</title>
<style>
  @font-face { font-family: 'Inter'; font-weight: 400; src: url('{{ public_path("fonts/Inter-Regular.ttf") }}') format('truetype'); }
  @font-face { font-family: 'Inter'; font-weight: 700; src: url('{{ public_path("fonts/Inter-Bold.ttf") }}') format('truetype'); }

  @page { size: A4 portrait; margin: 24px 30px 22px 30px; }
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Inter', Helvetica, Arial, sans-serif; font-size: 8.5pt; color: #1e293b; line-height: 1.4; background: #fff; width: 100%; }

  .applicant-hero { background: #ffffff; border: 1px solid #e2e8f0; border-top: 2.5px solid #a8001c; border-radius: 6px; padding: 10px 14px; margin-bottom: 12px; }
  .ah-name { font-size: 13pt; font-weight: 700; color: #0f172a; }
  .ah-code { font-size: 8pt; color: #a8001c; font-weight: 700; margin-top: 1px; }

  .section-heading { 
    background: #f8fafc; 
    border-left: 3.5px solid #a8001c; 
    border-top: 1px solid #e2e8f0;
    border-right: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
    color: #0f172a; 
    font-size: 7.8pt; 
    font-weight: 700; 
    padding: 4.5px 10px; 
    margin: 12px 0 6px 0; 
    border-radius: 0 4px 4px 0; 
    text-transform: uppercase; 
    letter-spacing: 0.6px; 
  }

  table.kv-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
  table.kv-table td { padding: 3px 4px; font-size: 8.5pt; vertical-align: top; }
  table.kv-table td.lb { width: 32%; color: #64748b; font-weight: 600; }
  table.kv-table td.cl { width: 12px; color: #94a3b8; }
  table.kv-table td.vl { font-weight: 700; color: #0f172a; }

  table.data-table { width: 100%; border-collapse: collapse; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; margin-top: 4px; }
  table.data-table thead th { background: #f1f5f9; color: #1e293b; font-size: 7pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; padding: 5px 6px; text-align: left; border-bottom: 1.5px solid #cbd5e1; }
  table.data-table tbody td { padding: 4.5px 6px; font-size: 7.8pt; color: #1e293b; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
  table.data-table tbody tr:last-child td { border-bottom: none; }

  .sign-duo-table { width: 100%; margin-top: 22px; border-collapse: collapse; }
  .sign-duo-cell { width: 50%; text-align: center; vertical-align: top; padding: 0 10px; }
  .sign-duo-card { border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 10px; background: #fff; }
  .sign-duo-holder { position: relative; height: 60px; margin: 3px auto; width: 150px; }
  .sign-duo-img { max-height: 55px; max-width: 130px; object-fit: contain; }
  .sign-duo-stamp { position: absolute; left: 0px; top: 0px; width: 55px; height: 55px; opacity: 0.85; }
  .sign-duo-name { font-size: 8.5pt; font-weight: 700; color: #0f172a; border-top: 1px solid #0f172a; padding-top: 3px; display: inline-block; min-width: 130px; }
</style>
</head>
<body>

{{-- 1. KOP SURAT BAKU RESMI INDONESIA --}}
@include('pdf.hcm.partials.kop', [
    'profile' => $p,
    'badgeText' => 'Profil Pelamar',
    'badgeSub' => $applicant->applicant_code
])

{{-- 2. HERO IDENTITAS PELAMAR --}}
<div class="applicant-hero">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td>
                <div class="ah-name">{{ strtoupper($applicant->name) }}</div>
                <div class="ah-code">{{ $applicant->applicant_code }} &bull; Posisi: <strong>{{ $applicant->jobPosting?->title ?? 'Umum' }}</strong></div>
            </td>
            <td style="text-align: right; vertical-align: middle;">
                <span style="display: inline-block; padding: 3px 9px; border-radius: 12px; font-size: 7.5pt; font-weight: 700; letter-spacing: 0.5px; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                    {{ strtoupper($applicant->status) }}
                </span>
            </td>
        </tr>
    </table>
</div>

{{-- 3. BIODATA PRIBADI & KONTAK --}}
<div class="section-heading">Data Pribadi & Kualifikasi Pelamar</div>
<table style="width: 100%; border-collapse: collapse;">
    <tr>
        <td style="width: 50%; vertical-align: top; padding-right: 8px;">
            <table class="kv-table">
                <tr><td class="lb">Nama Lengkap</td><td class="cl">:</td><td class="vl">{{ $applicant->name }}</td></tr>
                <tr><td class="lb">Jenis Kelamin</td><td class="cl">:</td><td class="vl">{{ $applicant->gender ?: '-' }}</td></tr>
                <tr><td class="lb">Tempat, Tgl Lahir</td><td class="cl">:</td><td class="vl">{{ $applicant->birth_place ?: '-' }}, {{ $applicant->birth_date ? $applicant->birth_date->isoFormat('D MMM Y') : '-' }} @if($age) ({{ $age }} thn) @endif</td></tr>
                <tr><td class="lb">Pendidikan Terakhir</td><td class="cl">:</td><td class="vl">{{ $applicant->education ?: '-' }}</td></tr>
                <tr><td class="lb">Institusi / Jurusan</td><td class="cl">:</td><td class="vl">{{ $applicant->institution ?: '-' }} ({{ $applicant->major ?: '-' }})</td></tr>
            </table>
        </td>
        <td style="width: 50%; vertical-align: top; padding-left: 8px;">
            <table class="kv-table">
                <tr><td class="lb">Nomor Telepon / WA</td><td class="cl">:</td><td class="vl">{{ $applicant->phone ?: '-' }}</td></tr>
                <tr><td class="lb">Alamat Email</td><td class="cl">:</td><td class="vl">{{ $applicant->email ?: '-' }}</td></tr>
                <tr><td class="lb">Alamat Domisili</td><td class="cl">:</td><td class="vl">{{ $applicant->address ?: '-' }}</td></tr>
                <tr><td class="lb">Tanggal Melamar</td><td class="cl">:</td><td class="vl">{{ $applicant->apply_date ? $applicant->apply_date->isoFormat('D MMMM Y') : '-' }}</td></tr>
                <tr><td class="lb">Sumber Informasi</td><td class="cl">:</td><td class="vl">{{ $applicant->source ?: 'Website Karir' }}</td></tr>
            </table>
        </td>
    </tr>
</table>

{{-- 4. RIWAYAT SESI WAWANCARA & PENILAIAN --}}
<div class="section-heading">Riwayat Evaluasi & Catatan Wawancara</div>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 75px;">Tahap / Babak</th>
            <th style="width: 110px;">Pewawancara</th>
            <th style="width: 75px; text-align: center;">Tanggal</th>
            <th style="width: 80px;">Hasil</th>
            <th style="width: 80px;">Offering</th>
            <th>Catatan & Masukan Wawancara</th>
        </tr>
    </thead>
    <tbody>
        @forelse($applicant->interviews as $iv)
        <tr>
            <td><strong>{{ $iv->interview_round ?: 'Tahap 1' }}</strong></td>
            <td>{{ $iv->interviewer_name ?: ($iv->creator?->name ?? '-') }}</td>
            <td style="text-align: center;">{{ $iv->interview_date ? $iv->interview_date->isoFormat('D MMM Y') : '-' }}</td>
            <td><strong>{{ $iv->interview_result ?: '-' }}</strong></td>
            <td>{{ $iv->offering_status ?: '-' }}</td>
            <td style="color: #475569;">{{ $iv->offering_notes ?: ($iv->notes ?: '-') }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="text-align: center; color: #64748b; padding: 10px;">Belum ada sesi wawancara tercatat untuk pelamar ini.</td>
        </tr>
        @endforelse
    </tbody>
</table>

{{-- 5. BLOK TANDA TANGAN (PEWAWANCARA & HR MANAGER) --}}
@php
    $hcmName = $p['signer_name'];
    $hcmRole = $p['signer_role'];
    $hcmNik  = $p['signer_nik'];
    $showHcmSig = $p['show_signature_on_pdf'] && !empty($p['signature_base64']);
    $showStamp  = $p['show_stamp_on_pdf'] && !empty($p['stamp_base64']);
@endphp

<table class="sign-duo-table">
    <tr>
        <td class="sign-duo-cell">
            <div class="sign-duo-card">
                <div style="font-size: 7.5pt; font-weight: 700; color: #475569; text-transform: uppercase;">Pewawancara / Tim Penilai</div>
                <div style="font-size: 7pt; color: #64748b;">Recruitment & Talent Acquisition</div>
                <div class="sign-duo-holder"></div>
                <div class="sign-duo-name">( ______________________ )</div>
                <div style="font-size: 7pt; color: #64748b; margin-top: 1px;">Admin Rekrutmen / Interviewer</div>
            </div>
        </td>
        <td class="sign-duo-cell">
            <div class="sign-duo-card">
                <div style="font-size: 7.5pt; font-weight: 700; color: #475569; text-transform: uppercase;">Mengetahui & Menyetujui</div>
                <div style="font-size: 7pt; color: #64748b;">{{ $p['division_name'] }}</div>
                
                <div class="sign-duo-holder">
                    @if($showStamp)
                    <img src="{{ $p['stamp_base64'] }}" class="sign-duo-stamp" alt="Stempel">
                    @endif
                    @if($showHcmSig)
                    <img src="{{ $p['signature_base64'] }}" class="sign-duo-img" alt="TTD HCM">
                    @endif
                </div>

                <div class="sign-duo-name">{{ $hcmName ?: '( ______________________ )' }}</div>
                <div style="font-size: 7pt; color: #64748b; margin-top: 1px;">{{ $hcmRole }} @if($hcmNik) &bull; NIK: {{ $hcmNik }} @endif</div>
            </div>
        </td>
    </tr>
</table>

{{-- 6. CATATAN KAKI DOKUMEN --}}
@include('pdf.hcm.partials.footer', ['profile' => $p])

</body>
</html>
