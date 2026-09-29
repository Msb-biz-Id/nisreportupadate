@php
    $s = fn($key, $default = null) => \App\Models\Settings\SystemSetting::get('hcm_profile', $key, $default);
    $companyName = $s('company_name', config('app.name', 'NISGroup'));
    $companyAddr = $s('company_address', 'Klaten, Jawa Tengah');
    $companyCity = $s('company_city', 'Klaten');
    $footerText = $s('document_footer_text', 'Dokumen resmi diterbitkan oleh Sistem HCM.');

    $hcmLogo = $s('logo');
    $logoPath = $hcmLogo && file_exists(storage_path('app/public/' . $hcmLogo))
        ? storage_path('app/public/' . $hcmLogo)
        : (file_exists(public_path('images/logo.png')) ? public_path('images/logo.png') : null);
    $logoBase64 = $logoPath ? 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($logoPath)) : null;

    $age = $applicant->birth_date ? $applicant->birth_date->age : null;
    $fdate = fn($d, $f = 'D MMM Y') => $d ? \Carbon\Carbon::parse($d)->isoFormat($f) : '-';
    $row = function ($label, $value) {
        return '<tr><td class="lb">' . e($label) . '</td><td class="cl">:</td><td class="vl">' . e($value ?: '-') . '</td></tr>';
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Profil Pelamar - {{ $applicant->name }}</title>
<style>
  @font-face { font-family: 'Inter'; font-weight: 400; src: url('{{ public_path("fonts/Inter-Regular.ttf") }}') format('truetype'); }
  @font-face { font-family: 'Inter'; font-weight: 600; src: url('{{ public_path("fonts/Inter-Bold.ttf") }}') format('truetype'); }
  @font-face { font-family: 'Inter'; font-weight: 700; src: url('{{ public_path("fonts/Inter-Bold.ttf") }}') format('truetype'); }

  @page { size: A4 portrait; margin: 0 0 30px 0; }
  body, span, p, table, thead, tbody, tfoot, tr, th, td, img { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Inter', Helvetica, Arial, sans-serif; font-size: 9.5px; color: #0f172a; line-height: 1.4; }

  .header { background: #0f172a; border-bottom: 3px solid #2563eb; }
  .header td { padding: 14px 30px; vertical-align: middle; }
  .logo-box { width: 36px; height: 36px; background: #fff; border-radius: 7px; color: #2563eb; text-align: center; line-height: 36px; font-size: 15px; font-weight: 700; overflow: hidden; }
  .logo-box img { width: 100%; height: 100%; object-fit: contain; padding: 4px; }
  .brand { font-size: 13.5px; font-weight: 700; color: #fff; text-transform: uppercase; letter-spacing: .5px; }
  .brand-sub { font-size: 8px; color: #94a3b8; margin-top: 1px; }
  .doc-title { font-size: 12px; font-weight: 700; color: #fff; text-transform: uppercase; letter-spacing: 1px; text-align: right; }
  .doc-sub { font-size: 8px; color: #7dd3fc; text-align: right; margin-top: 2px; }

  .wrap { padding: 16px 30px 8px 30px; }
  .emp-name { font-size: 19px; font-weight: 700; }
  .emp-code { font-size: 9px; color: #2563eb; font-weight: 600; margin-top: 1px; }

  .section-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin: 14px 0 6px 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }

  table.kv { width: 100%; border-collapse: collapse; }
  table.kv td { padding: 3.5px 0; font-size: 9.5px; vertical-align: top; }
  table.kv td.lb { width: 34%; color: #64748b; }
  table.kv td.cl { width: 12px; color: #94a3b8; }
  table.kv td.vl { font-weight: 600; }

  table.data { width: 100%; border-collapse: collapse; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
  table.data thead th { background: #f1f5f9; color: #475569; font-size: 7px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; padding: 6px 7px; text-align: left; border-bottom: 1px solid #e2e8f0; }
  table.data tbody td { padding: 6px 7px; font-size: 8px; color: #1e293b; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
  table.data tbody tr:last-child td { border-bottom: none; }
  .c { text-align: center; }
  .muted { color: #64748b; }
  .box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px; font-size: 9px; color: #334155; }
  .tag { display: inline-block; font-size: 7.5px; font-weight: 700; padding: 2px 7px; border-radius: 10px; }
  .tag-green { background: #dcfce7; color: #15803d; }
  .tag-sky { background: #e0f2fe; color: #0369a1; }
  .tag-amber { background: #fef3c7; color: #b45309; }
  .tag-rose { background: #fee2e2; color: #b91c1c; }
  .tag-slate { background: #f1f5f9; color: #475569; }

  .footer { position: fixed; bottom: -30px; left: 0; right: 0; height: 26px; background: #0f172a; border-top: 2px solid #2563eb; }
  .footer td { padding: 7px 30px; font-size: 7px; color: #94a3b8; }
  .page::before { content: "HAL " counter(page); }
  .sign { margin-top: 24px; width: 100%; border-collapse: collapse; }
  .sign td { width: 50%; text-align: center; vertical-align: top; font-size: 8.5px; }
  .sign .line { width: 62%; margin: 42px auto 3px auto; border-top: 1px solid #0f172a; padding-top: 4px; font-weight: 700; }
</style>
</head>
<body>

<div class="footer">
  <table style="width:100%;border-collapse:collapse;">
    <tr>
      <td style="text-align:left;">{{ $footerText }} &bull; {{ strtoupper($applicant->name) }} &bull; {{ $applicant->applicant_code }}</td>
      <td style="text-align:right;"><span class="page"></span> &bull; {{ now()->isoFormat('D MMM Y, HH:mm') }} WIB</td>
    </tr>
  </table>
</div>

<table class="header" style="width:100%;border-collapse:collapse;">
  <tr>
    <td style="width:60%;">
      <table style="width:100%;border-collapse:collapse;">
        <tr>
          <td style="width:40px;vertical-align:middle;">
            <div class="logo-box">
              @if($logoBase64)<img src="{{ $logoBase64 }}" alt="Logo">@else {{ strtoupper(substr($companyName,0,1)) }} @endif
            </div>
          </td>
          <td style="vertical-align:middle;padding-left:8px;">
            <div class="brand">{{ strtoupper($companyName) }}</div>
            <div class="brand-sub">{{ $companyAddr }}</div>
          </td>
        </tr>
      </table>
    </td>
    <td style="width:40%;text-align:right;">
      <div class="doc-title">Profil Pelamar</div>
      <div class="doc-sub">Rekap Biodata &amp; Hasil Wawancara</div>
    </td>
  </tr>
</table>

<div class="wrap">
  <div class="emp-name">{{ $applicant->name }}</div>
  <div class="emp-code">{{ $applicant->applicant_code }} &bull; Melamar: {{ $applicant->jobPosting->title ?? '-' }} ({{ $applicant->jobPosting->job_code ?? '-' }})</div>
  <div style="margin-top:6px;">
    <span class="tag {{ $applicant->status === 'ACCEPTED' ? 'tag-green' : ($applicant->status === 'REJECTED' ? 'tag-rose' : 'tag-sky') }}">{{ $applicant->status }}</span>
    @if($applicant->invitation_status)<span class="tag tag-slate">{{ $applicant->invitation_status }}</span>@endif
    @if($applicant->is_blacklisted)<span class="tag tag-rose">BLACKLIST</span>@endif
  </div>

  <div class="section-title">Data Diri</div>
  <table class="kv">
    {!! $row('Nama Panggilan', $applicant->nickname) !!}
    {!! $row('Jenis Kelamin', $applicant->gender) !!}
    {!! $row('Tempat, Tanggal Lahir', ($applicant->birth_place ?: '-') . ', ' . $fdate($applicant->birth_date, 'D MMMM Y') . ($age ? " ({$age} tahun)" : '')) !!}
    {!! $row('Status Pernikahan', $applicant->marital_status) !!}
    {!! $row('Pendidikan Terakhir', trim(($applicant->education ?? '-') . ($applicant->major ? ' (' . $applicant->major . ')' : ''))) !!}
    {!! $row('No. HP / WhatsApp', $applicant->phone_number) !!}
    {!! $row('Email', $applicant->email) !!}
    {!! $row('Alamat Domisili', $applicant->address) !!}
    {!! $row('Ekspektasi Gaji', $applicant->expected_salary ? 'Rp ' . number_format((float) $applicant->expected_salary, 0, ',', '.') . ' / bulan' : '-') !!}
    {!! $row('Ketersediaan Mulai', $fdate($applicant->available_start_date)) !!}
    {!! $row('Tanggal Melamar', $fdate($applicant->apply_date, 'D MMMM Y')) !!}
  </table>

  @if($applicant->experience_summary)
    <div class="section-title">Pengalaman Kerja</div>
    <div class="box">{{ $applicant->experience_summary }}</div>
  @endif

  @if($applicant->skills)
    <div class="section-title">Keahlian &amp; Keterampilan</div>
    <div class="box">{{ $applicant->skills }}</div>
  @endif

  <div class="section-title">Rekap Hasil Wawancara</div>
  <table class="data">
    <thead>
      <tr>
        <th style="width:12%;">Ronde</th>
        <th style="width:18%;">Pewawancara</th>
        <th style="width:12%;">Tanggal</th>
        <th style="width:16%;">Hasil</th>
        <th style="width:16%;">Offering</th>
        <th>Catatan</th>
      </tr>
    </thead>
    <tbody>
      @forelse($applicant->interviews as $iv)
        <tr>
          <td>{{ $iv->interview_round ?: '-' }}</td>
          <td>{{ $iv->interviewer_name ?: '-' }}</td>
          <td class="c">{{ $iv->interview_date ? $iv->interview_date->isoFormat('D MMM Y') : '-' }}</td>
          <td>{{ $iv->interview_result ?: '-' }}</td>
          <td>{{ $iv->offering_status ?: '-' }}</td>
          <td class="muted">{{ $iv->offering_notes ?: '-' }}</td>
        </tr>
      @empty
        <tr><td colspan="6" class="c muted" style="padding:12px;">Belum ada sesi wawancara yang tercatat.</td></tr>
      @endforelse
    </tbody>
  </table>

  <table class="sign">
    <tr>
      <td>
        <div>Pewawancara / HRD,</div>
        <div class="line">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>
        <div class="muted">Admin HCM / Recruiter</div>
      </td>
      <td>
        <div>{{ $companyCity }}, {{ now()->isoFormat('D MMMM Y') }}</div>
        <div class="line">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>
        <div class="muted">HR Manager / Pimpinan</div>
      </td>
    </tr>
  </table>
</div>

</body>
</html>
