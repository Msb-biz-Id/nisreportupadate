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

    $title = $selectedJob ? 'LAPORAN PERFORMA REKRUTMEN — ' . strtoupper($selectedJob->title) : 'LAPORAN PERFORMA REKRUTMEN';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>{{ $title }}</title>
<style>
  @font-face { font-family: 'Inter'; font-weight: 400; src: url('{{ public_path("fonts/Inter-Regular.ttf") }}') format('truetype'); }
  @font-face { font-family: 'Inter'; font-weight: 600; src: url('{{ public_path("fonts/Inter-Bold.ttf") }}') format('truetype'); }
  @font-face { font-family: 'Inter'; font-weight: 700; src: url('{{ public_path("fonts/Inter-Bold.ttf") }}') format('truetype'); }

  @page { size: A4 landscape; margin: 0 0 30px 0; }
  body, span, p, table, thead, tbody, tfoot, tr, th, td, img { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Inter', Helvetica, Arial, sans-serif; font-size: 9px; color: #0f172a; line-height: 1.35; }

  .header { background: #0f172a; border-bottom: 3px solid #2563eb; }
  .header td { padding: 12px 26px; vertical-align: middle; }
  .logo-box { width: 34px; height: 34px; background: #fff; border-radius: 7px; color: #2563eb; text-align: center; line-height: 34px; font-size: 15px; font-weight: 700; overflow: hidden; }
  .logo-box img { width: 100%; height: 100%; object-fit: contain; padding: 4px; }
  .brand { font-size: 13px; font-weight: 700; color: #fff; text-transform: uppercase; letter-spacing: .5px; }
  .brand-sub { font-size: 7.5px; color: #94a3b8; margin-top: 1px; }
  .doc-title { font-size: 11px; font-weight: 700; color: #fff; text-transform: uppercase; letter-spacing: 1px; }
  .doc-sub { font-size: 7.5px; color: #7dd3fc; text-align: right; margin-top: 2px; }

  .wrap { padding: 14px 26px 6px 26px; }

  .cards { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin-bottom: 12px; }
  .card { background: #f8fafc; border: 1px solid #e2e8f0; border-top: 3px solid #2563eb; border-radius: 8px; padding: 8px 10px; width: 25%; }
  .card .k { font-size: 7px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; }
  .card .v { font-size: 16px; font-weight: 700; color: #0f172a; margin-top: 2px; }

  table.data { width: 100%; border-collapse: collapse; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
  table.data thead th { background: #f1f5f9; color: #475569; font-size: 6.8px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; padding: 6px 6px; text-align: left; border-bottom: 1px solid #e2e8f0; }
  table.data tbody td { padding: 5px 6px; font-size: 7.6px; color: #1e293b; border-bottom: 1px solid #f1f5f9; }
  table.data tbody tr:last-child td { border-bottom: none; }
  .c { text-align: center; }
  .r { text-align: right; }
  .mono { font-family: 'Courier New', monospace; }
  .muted { color: #64748b; }
  .bold { font-weight: 700; }

  .tag { display: inline-block; font-size: 7px; font-weight: 700; padding: 2px 6px; border-radius: 10px; }
  .tag-green { background: #dcfce7; color: #15803d; }
  .tag-sky { background: #e0f2fe; color: #0369a1; }
  .tag-amber { background: #fef3c7; color: #b45309; }
  .tag-rose { background: #fee2e2; color: #b91c1c; }
  .tag-slate { background: #f1f5f9; color: #475569; }

  .section-title { font-size: 10px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 1px; margin: 14px 0 6px 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }

  .footer { position: fixed; bottom: -30px; left: 0; right: 0; height: 26px; background: #0f172a; border-top: 2px solid #2563eb; }
  .footer td { padding: 7px 26px; font-size: 7px; color: #94a3b8; }
  .page::before { content: "HAL " counter(page); }
  .sign { margin-top: 26px; width: 100%; border-collapse: collapse; }
  .sign td { width: 50%; text-align: center; vertical-align: top; font-size: 8px; }
  .sign .line { width: 60%; margin: 40px auto 3px auto; border-top: 1px solid #0f172a; padding-top: 4px; font-weight: 700; }
</style>
</head>
<body>

<div class="footer">
  <table style="width:100%;border-collapse:collapse;">
    <tr>
      <td style="text-align:left;">{{ $footerText }} &bull; {{ strtoupper($companyName) }}</td>
      <td style="text-align:right;"><span class="page"></span> &bull; {{ now()->isoFormat('D MMM Y, HH:mm') }} WIB</td>
    </tr>
  </table>
</div>

<table class="header" style="width:100%;border-collapse:collapse;">
  <tr>
    <td style="width:60%;">
      <table style="width:100%;border-collapse:collapse;">
        <tr>
          <td style="width:38px;vertical-align:middle;">
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
      <div class="doc-title">{{ $selectedJob ? 'Laporan Loker' : 'Laporan Rekrutmen' }}</div>
      <div class="doc-sub">{{ $title }}</div>
    </td>
  </tr>
</table>

<div class="wrap">
  <table class="cards">
    <tr>
      <td class="card"><div class="k">Total Loker</div><div class="v">{{ $summary['total_jobs'] }}</div></td>
      <td class="card"><div class="k">Total Pelamar</div><div class="v">{{ $summary['total_applicants'] }}</div></td>
      <td class="card"><div class="k">Total Diterima</div><div class="v">{{ $summary['total_hired'] }}</div></td>
      <td class="card"><div class="k">Rata-rata Pemenuhan</div><div class="v">{{ $summary['avg_fulfillment'] }}%</div></td>
    </tr>
  </table>

  <div class="section-title">Rekapitulasi Performa per Loker</div>
  <table class="data">
    <thead>
      <tr>
        <th style="width:11%;">Kode Loker</th>
        <th>Posisi / Judul</th>
        <th style="width:10%;">Divisi</th>
        <th class="c">Kuota</th>
        <th class="c">Pelamar</th>
        <th class="c">Screening</th>
        <th class="c">Interview</th>
        <th class="c">Diterima</th>
        <th class="c">Ditolak</th>
        <th class="c">Hired</th>
        <th class="c">Pemenuhan</th>
        <th class="c">Konversi</th>
        <th class="c">TT Hire</th>
        <th class="c">Status</th>
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $r)
        <tr>
          <td class="mono">{{ $r['job_code'] ?: '-' }}</td>
          <td class="bold">{{ $r['title'] }}</td>
          <td class="muted">{{ $r['department'] }}</td>
          <td class="c">{{ $r['quota'] }}</td>
          <td class="c">{{ $r['total_applicants'] }}</td>
          <td class="c">{{ $r['screening'] }}</td>
          <td class="c">{{ $r['interview'] }}</td>
          <td class="c">{{ $r['accepted'] }}</td>
          <td class="c">{{ $r['rejected'] }}</td>
          <td class="c bold">{{ $r['hired'] }}</td>
          <td class="c">{{ $r['fulfillment_rate'] }}%</td>
          <td class="c">{{ $r['conversion_rate'] }}%</td>
          <td class="c">{{ $r['avg_time_to_hire'] !== null ? $r['avg_time_to_hire'] . ' hr' : '-' }}</td>
          <td class="c">
            <span class="tag {{ $r['status'] === 'Terpenuhi' ? 'tag-green' : ($r['status'] === 'Ditutup' ? 'tag-slate' : 'tag-sky') }}">{{ $r['status'] }}</span>
          </td>
        </tr>
      @empty
        <tr><td colspan="14" class="c muted" style="padding:14px;">Belum ada data loker.</td></tr>
      @endforelse
    </tbody>
  </table>

  @if(isset($channels) && $channels->isNotEmpty())
    <div class="section-title">Performa per Saluran Rekrutmen</div>
    <table class="data">
      <thead>
        <tr>
          <th>Saluran / Sumber Kandidat</th>
          <th class="c">Jumlah Loker</th>
          <th class="c">Total Pelamar</th>
          <th class="c">Diterima</th>
          <th class="c">Konversi</th>
        </tr>
      </thead>
      <tbody>
        @foreach($channels as $ch)
          <tr>
            <td class="bold">{{ $ch['channel'] }}</td>
            <td class="c">{{ $ch['jobs'] }}</td>
            <td class="c">{{ $ch['applicants'] }}</td>
            <td class="c bold">{{ $ch['hired'] }}</td>
            <td class="c">{{ $ch['conversion_rate'] }}%</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  @if($selectedJob)
    <div class="section-title">Detail Pelamar — {{ $selectedJob->title }} ({{ $selectedJob->job_code }})</div>
    <table class="data">
      <thead>
        <tr>
          <th style="width:10%;">Kode</th>
          <th>Nama Pelamar</th>
          <th style="width:6%;">L/P</th>
          <th style="width:11%;">Pendidikan</th>
          <th style="width:9%;">Tgl Melamar</th>
          <th style="width:10%;">Tahap</th>
          <th style="width:13%;">Hasil Interview</th>
          <th style="width:12%;">Offering</th>
          <th style="width:8%;">Wawancara</th>
        </tr>
      </thead>
      <tbody>
        @forelse($applicants as $a)
          @php $latest = $a->interviews->first(); @endphp
          <tr>
            <td class="mono">{{ $a->applicant_code }}</td>
            <td class="bold">{{ $a->name }}</td>
            <td class="c">{{ strtoupper(substr($a->gender ?? '-', 0, 1)) }}</td>
            <td class="muted">{{ $a->education ?: '-' }}</td>
            <td class="c">{{ $a->apply_date ? $a->apply_date->isoFormat('D MMM Y') : '-' }}</td>
            <td class="c">{{ $a->status }}</td>
            <td>{{ $a->interview_result ?: ($latest->interview_result ?? '-') }}</td>
            <td>{{ $latest->offering_status ?? '-' }}</td>
            <td class="c">{{ $a->interviews->count() }}x</td>
          </tr>
        @empty
          <tr><td colspan="9" class="c muted" style="padding:14px;">Belum ada pelamar untuk loker ini.</td></tr>
        @endforelse
      </tbody>
    </table>
  @endif

  <table class="sign">
    <tr>
      <td>
        <div>Mengetahui,</div>
        <div class="line">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>
        <div class="muted">HR Manager / Admin HCM</div>
      </td>
      <td>
        <div>{{ $companyCity }}, {{ now()->isoFormat('D MMMM Y') }}</div>
        <div class="line">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>
        <div class="muted">Pimpinan / Penanggung Jawab Rekrutmen</div>
      </td>
    </tr>
  </table>
</div>

</body>
</html>
