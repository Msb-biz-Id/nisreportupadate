@php
    $p = $profile ?? \App\Services\HcmPdfHelper::getProfileData();
    $title = $selectedJob ? 'Laporan Performa Rekrutmen — ' . strtoupper($selectedJob->title) : 'Laporan Performa Rekrutmen Terpadu';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>{{ $title }}</title>
<style>
  @font-face { font-family: 'Inter'; font-weight: 400; src: url('{{ public_path("fonts/Inter-Regular.ttf") }}') format('truetype'); }
  @font-face { font-family: 'Inter'; font-weight: 700; src: url('{{ public_path("fonts/Inter-Bold.ttf") }}') format('truetype'); }

  @page { size: A4 landscape; margin: 24px 28px 22px 28px; }
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Inter', Helvetica, Arial, sans-serif; font-size: 8pt; color: #1e293b; line-height: 1.35; background: #fff; width: 100%; }

  .meta-cards-table { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin-bottom: 10px; }
  .mct-card { background: #f8fafc; border: 1px solid #e2e8f0; border-top: 2.5px solid #a8001c; border-radius: 6px; padding: 6px 10px; width: 25%; text-align: center; }
  .mct-card .k { font-size: 6.8pt; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
  .mct-card .v { font-size: 13pt; font-weight: 700; color: #0f172a; margin-top: 1px; }

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
    margin: 10px 0 6px 0; 
    border-radius: 0 4px 4px 0; 
    text-transform: uppercase; 
    letter-spacing: 0.6px; 
  }

  table.data-table { width: 100%; border-collapse: collapse; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; font-size: 7.5pt; margin-bottom: 8px; }
  table.data-table thead th { background: #f1f5f9; color: #1e293b; font-size: 7pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.35px; padding: 5px 6px; text-align: left; border-bottom: 1.5px solid #cbd5e1; }
  table.data-table tbody td { padding: 4.5px 6px; color: #1e293b; border-bottom: 1px solid #f1f5f9; }
  table.data-table tbody tr:last-child td { border-bottom: none; }
  table.data-table tbody tr:nth-child(even) td { background: #fbfcfe; }

  .c { text-align: center; }
  .r { text-align: right; }
  .bold { font-weight: 700; }

  .sign-duo-table { width: 100%; margin-top: 12px; border-collapse: collapse; }
  .sign-duo-cell { width: 50%; text-align: center; vertical-align: top; padding: 0 16px; }
  .sign-duo-card { border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; background: #fff; }
  .sign-duo-holder { position: relative; height: 56px; margin: 2px auto; width: 150px; }
  .sign-duo-img { max-height: 52px; max-width: 130px; object-fit: contain; }
  .sign-duo-stamp { position: absolute; left: 0px; top: 0px; width: 52px; height: 52px; opacity: 0.85; }
  .sign-duo-name { font-size: 8.5pt; font-weight: 700; color: #0f172a; border-top: 1px solid #0f172a; padding-top: 2px; display: inline-block; min-width: 140px; }
</style>
</head>
<body>

{{-- 1. KOP SURAT BAKU RESMI INDONESIA --}}
@include('pdf.hcm.partials.kop', [
    'profile' => $p,
    'badgeText' => 'Laporan Rekrutmen',
    'badgeSub' => now()->isoFormat('D MMMM Y')
])

{{-- 2. METRIC SUMMARY --}}
<table class="meta-cards-table">
  <tr>
    <td class="mct-card">
      <div class="k">Total Posisi / Loker Dibuka</div>
      <div class="v">{{ $summary['total_jobs'] }} Loker</div>
    </td>
    <td class="mct-card">
      <div class="k">Total Berkas Pelamar Masuk</div>
      <div class="v" style="color: #a8001c;">{{ $summary['total_applicants'] }} Berkas</div>
    </td>
    <td class="mct-card">
      <div class="k">Total Kandidat Diterima (Hired)</div>
      <div class="v" style="color: #15803d;">{{ $summary['total_hired'] }} Orang</div>
    </td>
    <td class="mct-card">
      <div class="k">Rata-rata Pemenuhan Target</div>
      <div class="v" style="color: #b45309;">{{ $summary['avg_fulfillment'] }}%</div>
    </td>
  </tr>
</table>

{{-- 3. TABEL PERFORMA LOKER --}}
<div class="section-heading">Tabel Rekapitulasi Pemenuhan Lowongan Kerja (Job Postings)</div>
<table class="data-table">
  <thead>
    <tr>
      <th style="width: 25px; text-align: center;">No</th>
      <th>Judul Posisi Lowongan</th>
      <th>Departemen</th>
      <th class="c" style="width: 50px;">Target</th>
      <th class="c" style="width: 55px;">Pelamar</th>
      <th class="c" style="width: 55px;">Review</th>
      <th class="c" style="width: 65px;">Interview</th>
      <th class="c" style="width: 50px;">Hired</th>
      <th class="c" style="width: 60px;">Fulfillment</th>
      <th class="c" style="width: 65px;">Status</th>
    </tr>
  </thead>
  <tbody>
    @forelse($rows as $idx => $r)
      <tr>
        <td class="c">{{ $idx + 1 }}</td>
        <td class="bold">{{ $r['title'] ?? '-' }}</td>
        <td>{{ $r['department'] ?? '-' }}</td>
        <td class="c">{{ $r['quota'] ?? $r['target_hires'] ?? 0 }}</td>
        <td class="c bold" style="color: #a8001c;">{{ $r['total_applicants'] ?? 0 }}</td>
        <td class="c">{{ $r['screening'] ?? $r['reviewing'] ?? 0 }}</td>
        <td class="c">{{ $r['interview'] ?? 0 }}</td>
        <td class="c bold" style="color: #15803d;">{{ $r['hired'] ?? 0 }}</td>
        <td class="c bold">{{ $r['fulfillment_rate'] ?? 0 }}%</td>
        <td class="c">{{ $r['status'] ?? 'Aktif' }}</td>
      </tr>
    @empty
      <tr><td colspan="10" class="c" style="padding:10px; color:#64748b;">Belum ada data lowongan kerja.</td></tr>
    @endforelse
  </tbody>
</table>

{{-- 4. JIKA ADA DETAIL PELAMAR SPESIFIK --}}
@if($selectedJob && $applicants->isNotEmpty())
<div class="section-heading">Daftar Kandidat Pelamar: {{ $selectedJob->title }}</div>
<table class="data-table">
  <thead>
    <tr>
      <th style="width: 75px;">Kode Pelamar</th>
      <th>Nama Lengkap</th>
      <th class="c" style="width: 45px;">L/P</th>
      <th>Pendidikan</th>
      <th class="c" style="width: 75px;">Tgl Melamar</th>
      <th class="c" style="width: 75px;">Status Akhir</th>
      <th>Hasil Wawancara</th>
      <th>Offering Status</th>
    </tr>
  </thead>
  <tbody>
    @foreach($applicants as $a)
      @php $latest = $a->interviews->first(); @endphp
      <tr>
        <td style="font-family: monospace;">{{ $a->applicant_code }}</td>
        <td class="bold">{{ $a->name }}</td>
        <td class="c">{{ strtoupper(substr($a->gender ?? '-', 0, 1)) }}</td>
        <td>{{ $a->education ?: '-' }}</td>
        <td class="c">{{ $a->apply_date ? $a->apply_date->isoFormat('D MMM Y') : '-' }}</td>
        <td class="c">{{ $a->status }}</td>
        <td>{{ $a->interview_result ?: ($latest->interview_result ?? '-') }}</td>
        <td>{{ $latest->offering_status ?? '-' }}</td>
      </tr>
    @endforeach
  </tbody>
</table>
@endif

{{-- 5. BLOK TANDA TANGAN (REKRUTER & KEPALA HCM) --}}
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
        <div style="font-size: 7.2pt; font-weight: 700; color: #475569; text-transform: uppercase;">Disusun Oleh</div>
        <div style="font-size: 6.8pt; color: #64748b;">Divisi Talent Acquisition & Recruitment</div>
        <div class="sign-duo-holder"></div>
        <div class="sign-duo-name">( ______________________ )</div>
        <div style="font-size: 6.8pt; color: #64748b; margin-top: 1px;">Admin Rekrutmen & Seleksi</div>
      </div>
    </td>
    <td class="sign-duo-cell">
      <div class="sign-duo-card">
        <div style="font-size: 7.2pt; font-weight: 700; color: #475569; text-transform: uppercase;">Mengetahui & Mengesahkan</div>
        <div style="font-size: 6.8pt; color: #64748b;">{{ $p['division_name'] }}</div>
        
        <div class="sign-duo-holder">
          @if($showStamp)
          <img src="{{ $p['stamp_base64'] }}" class="sign-duo-stamp" alt="Stempel">
          @endif
          @if($showHcmSig)
          <img src="{{ $p['signature_base64'] }}" class="sign-duo-img" alt="TTD HCM">
          @endif
        </div>

        <div class="sign-duo-name">{{ $hcmName ?: '( ______________________ )' }}</div>
        <div style="font-size: 6.8pt; color: #64748b; margin-top: 1px;">{{ $hcmRole }} @if($hcmNik) &bull; NIK: {{ $hcmNik }} @endif</div>
      </div>
    </td>
  </tr>
</table>

{{-- 6. CATATAN KAKI DOKUMEN --}}
@include('pdf.hcm.partials.footer', ['profile' => $p])

</body>
</html>
