<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Uang Makan – {{ $batch->batch_code }}</title>
    <style>
        @font-face { font-family: 'Inter'; font-weight: 400; src: url('{{ public_path("fonts/Inter-Regular.ttf") }}') format('truetype'); }
        @font-face { font-family: 'Inter'; font-weight: 700; src: url('{{ public_path("fonts/Inter-Bold.ttf") }}') format('truetype'); }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', Arial, sans-serif; font-size: 8.5pt; color: #1a1a2e; background: #fff; }

        .kop { display: table; width: 100%; border-bottom: 3px solid #2d6a4f; padding-bottom: 10px; margin-bottom: 12px; }
        .kop-logo { display: table-cell; width: 80px; vertical-align: middle; }
        .kop-logo img { width: 65px; height: 65px; object-fit: contain; }
        .kop-info { display: table-cell; vertical-align: middle; padding-left: 12px; }
        .kop-info .company { font-size: 14pt; font-weight: 700; color: #2d6a4f; }
        .kop-info .sub { font-size: 7.5pt; color: #4a5568; margin-top: 2px; }
        .kop-badge { display: table-cell; width: 170px; vertical-align: middle; text-align: right; }
        .kop-badge .doc-type { background: #2d6a4f; color: #fff; font-size: 8pt; font-weight: 700; padding: 5px 10px; border-radius: 4px; text-transform: uppercase; letter-spacing: 1px; }
        .kop-badge .doc-num { font-size: 7.5pt; color: #718096; margin-top: 4px; }

        .summary-bar { display: table; width: 100%; background: #f0fff4; border: 1px solid #9ae6b4; border-radius: 6px; padding: 10px 14px; margin-bottom: 14px; }
        .sb-cell { display: table-cell; width: 25%; text-align: center; border-right: 1px solid #9ae6b4; }
        .sb-cell:last-child { border-right: none; }
        .sb-label { font-size: 7pt; color: #276749; text-transform: uppercase; letter-spacing: 0.5px; }
        .sb-value { font-size: 12pt; font-weight: 700; color: #22543d; }
        .sb-sub { font-size: 7pt; color: #48bb78; }

        .section-title { background: #2d6a4f; color: #fff; font-size: 8.5pt; font-weight: 700; padding: 5px 10px; margin: 12px 0 8px 0; border-radius: 3px; text-transform: uppercase; letter-spacing: 0.5px; }

        table.detail { width: 100%; border-collapse: collapse; font-size: 7.5pt; }
        table.detail th { background: #2d3748; color: #fff; padding: 5px 6px; text-align: left; font-size: 7pt; text-transform: uppercase; letter-spacing: 0.5px; }
        table.detail td { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; color: #2d3748; }
        table.detail tr:nth-child(even) td { background: #f0fff4; }
        table.detail td.right { text-align: right; }
        table.detail td.center { text-align: center; }
        table.detail tr.total-row td { background: #c6f6d5; font-weight: 700; font-size: 8pt; border-top: 2px solid #2d6a4f; }
        table.detail tr.hold-row td { background: #fff5f5 !important; color: #c53030; }

        .badge { display: inline-block; padding: 2px 7px; border-radius: 10px; font-size: 6.5pt; font-weight: 700; }
        .badge-cair { background: #c6f6d5; color: #22543d; }
        .badge-hold { background: #fed7d7; color: #742a2a; }
        .badge-draft { background: #e2e8f0; color: #4a5568; }

        .sign-section { margin-top: 24px; display: table; width: 100%; }
        .sign-box { display: table-cell; width: 33%; text-align: center; padding: 0 8px; }
        .sign-line { margin: 45px auto 5px auto; width: 140px; border-top: 1px solid #2d3748; }
        .sign-name { font-size: 8pt; font-weight: 700; }
        .sign-role { font-size: 7pt; color: #718096; }
        .sign-date { font-size: 6.5pt; color: #4a5568; margin-top: 2px; }

        .footer { position: fixed; bottom: 0; left: 0; right: 0; border-top: 1px solid #e2e8f0; padding: 5px 20px; font-size: 6pt; color: #a0aec0; display: table; width: 100%; }
        .footer-left { display: table-cell; }
        .footer-right { display: table-cell; text-align: right; }
        .text-danger-bold { color: #c53030; font-weight: 700; }
    </style>
</head>
<body>

<div class="footer">
    <div class="footer-left">REKAPITULASI UANG MAKAN BULANAN – {{ $batch->batch_code }} – DOKUMEN RESMI INTERNAL</div>
    <div class="footer-right">Dicetak: {{ now()->isoFormat('D MMMM Y, HH:mm') }} WIB &nbsp;|&nbsp; Halaman <span class="pagenum"></span></div>
</div>

<!-- KOP SURAT -->
<div class="kop">
    <div class="kop-logo">
        @php
            $hcmLogo = \App\Models\Settings\SystemSetting::get('hcm_profile', 'logo');
            $logoPath = $hcmLogo && file_exists(storage_path('app/public/' . $hcmLogo)) 
                ? storage_path('app/public/' . $hcmLogo) 
                : (file_exists(public_path('images/logo.png')) ? public_path('images/logo.png') : null);
            $logoBase64 = $logoPath ? 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($logoPath)) : null;
            $companyName = \App\Models\Settings\SystemSetting::get('hcm_profile', 'company_name', config('app.name', 'NISGroup'));
            $companyAddress = \App\Models\Settings\SystemSetting::get('hcm_profile', 'company_address', \App\Models\Settings\SystemSetting::get('company', 'address', 'Klaten, Jawa Tengah'));
            $kopLine1 = \App\Models\Settings\SystemSetting::get('hcm_profile', 'kop_header_line1', 'Divisi Human Capital – Rekapitulasi Uang Makan Bulanan');
            $contacts = array_filter([
                \App\Models\Settings\SystemSetting::get('hcm_profile', 'company_phone'),
                \App\Models\Settings\SystemSetting::get('hcm_profile', 'company_email'),
                \App\Models\Settings\SystemSetting::get('hcm_profile', 'company_socials'),
            ]);
        @endphp
        @if($logoBase64)
            <img src="{{ $logoBase64 }}" alt="Logo" style="max-height:65px; max-width:100px; object-fit:contain;">
        @else
            <div style="width:65px;height:65px;background:#2d6a4f;border-radius:8px;text-align:center;line-height:65px;">
                <span style="color:#fff;font-size:18pt;font-weight:700;">{{ substr($companyName, 0, 1) }}</span>
            </div>
        @endif
    </div>
    <div class="kop-info">
        <div class="company">{{ strtoupper($companyName) }}</div>
        <div class="sub">{{ $kopLine1 }}</div>
        <div class="sub">{{ $companyAddress }}@if(count($contacts) > 0) &bull; {{ implode(' | ', $contacts) }}@endif</div>
    </div>
    <div class="kop-badge">
        <div class="doc-type">Rekap Uang Makan</div>
        <div class="doc-num">{{ $batch->batch_code }}</div>
    </div>
</div>

<!-- SUMMARY BAR -->
@php
    $totalAmount = $batch->items->sum('final_amount');
    $holdCount = $batch->items->where('is_held', true)->count();
    $holdAmount = $batch->items->where('is_held', true)->sum('final_amount');
    $cairCount = $batch->items->where('is_held', false)->count();
    $cairAmount = $batch->items->where('is_held', false)->sum('final_amount');
@endphp
<div class="summary-bar">
    <div class="sb-cell">
        <div class="sb-label">Total Karyawan</div>
        <div class="sb-value">{{ $batch->items->count() }}</div>
        <div class="sb-sub">peserta bulan ini</div>
    </div>
    <div class="sb-cell">
        <div class="sb-label">Total Dana Cair</div>
        <div class="sb-value">Rp {{ number_format($cairAmount, 0, ',', '.') }}</div>
        <div class="sb-sub">{{ $cairCount }} karyawan</div>
    </div>
    <div class="sb-cell" style="color:#c53030;">
        <div class="sb-label" style="color:#c53030;">Total Dana Ditahan</div>
        <div class="sb-value" style="color:#c53030;">Rp {{ number_format($holdAmount, 0, ',', '.') }}</div>
        <div class="sb-sub" style="color:#fc8181;">{{ $holdCount }} karyawan (hold)</div>
    </div>
    <div class="sb-cell">
        <div class="sb-label">Grand Total</div>
        <div class="sb-value">Rp {{ number_format($totalAmount, 0, ',', '.') }}</div>
        <div class="sb-sub">Periode: {{ $batch->period_month }}/{{ $batch->period_year }}</div>
    </div>
</div>

<!-- TABEL DETAIL -->
<div class="section-title">Rincian Uang Makan Per Karyawan – {{ $batch->period_month }}/{{ $batch->period_year }}</div>
<table class="detail">
    <thead>
        <tr>
            <th style="width:24px;">No</th>
            <th>NIK</th>
            <th>Nama Karyawan</th>
            <th>Departemen</th>
            <th class="center">Hadir</th>
            <th class="center">Terlambat</th>
            <th class="center">½ Hari</th>
            <th class="center">Alpha</th>
            <th class="right">Uang Makan</th>
            <th class="right">Potongan</th>
            <th class="right">Hold Lalu</th>
            <th class="right">Diterima (Rp)</th>
            <th class="center">Status</th>
        </tr>
    </thead>
    <tbody>
        @php $grandTotal = 0; @endphp
        @foreach($batch->items as $i => $item)
        @php
            $isHeld = $item->is_hold ?? false;
            $payable = (float) ($item->payable_amount ?? 0);
            if (!$isHeld) {
                $grandTotal += $payable;
            }
        @endphp
        <tr class="{{ $isHeld ? 'hold-row' : '' }}">
            <td class="center">{{ $i + 1 }}</td>
            <td>{{ $item->employee->employee_code ?? '-' }}</td>
            <td><strong>{{ $item->employee->name ?? '-' }}</strong></td>
            <td>{{ $item->employee->department ?? '-' }}</td>
            <td class="center">{{ $item->present_days ?? 0 }}</td>
            <td class="center {{ ($item->late_days ?? 0) >= 4 ? 'text-danger-bold' : '' }}">{{ $item->late_days ?? 0 }}x</td>
            <td class="center">{{ $item->half_days ?? 0 }}</td>
            <td class="center {{ ($item->alpha_days ?? 0) > 0 ? 'text-danger-bold' : '' }}">{{ $item->alpha_days ?? 0 }}</td>
            <td class="right">Rp {{ number_format($item->base_allowance ?? 280000, 0, ',', '.') }}</td>
            <td class="right" style="color:#c53030;">{{ ($item->deduction_amount ?? 0) > 0 ? '-Rp '.number_format($item->deduction_amount, 0, ',', '.') : '-' }}</td>
            <td class="right" style="color:#2b6cb0;">{{ ($item->previous_hold_amount ?? 0) > 0 ? '+Rp '.number_format($item->previous_hold_amount, 0, ',', '.') : '-' }}</td>
            <td class="right"><strong>Rp {{ number_format($payable, 0, ',', '.') }}</strong></td>
            <td class="center">
                @if($isHeld)
                    <span class="badge badge-hold">DITAHAN</span>
                @else
                    <span class="badge badge-cair">CAIR</span>
                @endif
            </td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="11" class="right">GRAND TOTAL PEMBAYARAN CAIR</td>
            <td class="right">Rp {{ number_format($batch->total_amount ?? $grandTotal, 0, ',', '.') }}</td>
            <td class="center" style="color:#22543d;">CAIR</td>
        </tr>
    </tbody>
</table>

<!-- TTD -->
<div class="sign-section">
    <div class="sign-box">
        <div style="font-size:7.5pt;color:#718096;">Dibuat oleh,</div>
        <div class="sign-line"></div>
        <div class="sign-name">{{ $batch->creator?->name ?? 'Admin HCM' }}</div>
        <div class="sign-role">Pembuat Rekapitulasi</div>
    </div>
    <div class="sign-box">
        <div style="font-size:7.5pt;color:#718096;">Diverifikasi oleh,</div>
        <div class="sign-line"></div>
        <div class="sign-name">{{ $batch->hcmSigner?->name ?? $batch->signedByHcm?->name ?? '( ______________________ )' }}</div>
        <div class="sign-role">HR Manager / Admin HC</div>
        @if($batch->hcm_signed_at ?? $batch->signed_hcm_at)
        <div class="sign-date">{{ \Carbon\Carbon::parse($batch->hcm_signed_at ?? $batch->signed_hcm_at)->isoFormat('D MMM Y, HH:mm') }} WIB</div>
        @endif
    </div>
    <div class="sign-box">
        <div style="font-size:7.5pt;color:#718096;">Diverifikasi & Dibayar,</div>
        <div class="sign-line"></div>
        <div class="sign-name">{{ $batch->financeSigner?->name ?? $batch->signedByFinance?->name ?? '( ______________________ )' }}</div>
        <div class="sign-role">Admin Keuangan / Finance</div>
        @if($batch->finance_signed_at ?? $batch->signed_finance_at)
        <div class="sign-date">{{ \Carbon\Carbon::parse($batch->finance_signed_at ?? $batch->signed_finance_at)->isoFormat('D MMM Y, HH:mm') }} WIB</div>
        @endif
    </div>
</div>

<div style="margin-top:15px; padding-top:8px; border-top:1px solid #e2e8f0; font-size:7.5pt; color:#a0aec0; text-align:center;">
    {{ \App\Models\Settings\SystemSetting::get('hcm_profile', 'document_footer_text', 'Dokumen resmi diterbitkan otomatis oleh Sistem Kepegawaian terintegrasi.') }} &bull; {{ \App\Models\Settings\SystemSetting::get('hcm_profile', 'document_footer_disclaimer', 'Keabsahan dokumen dan double sign-off tercatat di sistem audit.') }}
</div>

</body>
</html>
