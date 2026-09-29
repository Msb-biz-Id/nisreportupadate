<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Voucher Lembur – {{ $batch->batch_code }}</title>
    <style>
        @font-face { font-family: 'Inter'; font-weight: 400; src: url('{{ public_path("fonts/Inter-Regular.ttf") }}') format('truetype'); }
        @font-face { font-family: 'Inter'; font-weight: 700; src: url('{{ public_path("fonts/Inter-Bold.ttf") }}') format('truetype'); }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', Arial, sans-serif; font-size: 9pt; color: #1a1a2e; background: #fff; }

        .kop { display: table; width: 100%; border-bottom: 3px solid #1e3a5f; padding-bottom: 10px; margin-bottom: 12px; }
        .kop-logo { display: table-cell; width: 80px; vertical-align: middle; }
        .kop-logo img { width: 65px; height: 65px; object-fit: contain; }
        .kop-info { display: table-cell; vertical-align: middle; padding-left: 12px; }
        .kop-info .company { font-size: 14pt; font-weight: 700; color: #1e3a5f; }
        .kop-info .sub { font-size: 7.5pt; color: #4a5568; margin-top: 2px; }
        .kop-badge { display: table-cell; width: 180px; vertical-align: middle; text-align: right; }
        .kop-badge .doc-type { background: #744210; color: #fff; font-size: 8pt; font-weight: 700; padding: 5px 10px; border-radius: 4px; text-transform: uppercase; letter-spacing: 1px; }
        .kop-badge .doc-num { font-size: 7.5pt; color: #718096; margin-top: 4px; }

        .voucher-header { background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; margin-bottom: 14px; display: table; width: 100%; }
        .vh-cell { display: table-cell; width: 33%; }
        .vh-label { font-size: 7pt; color: #718096; text-transform: uppercase; letter-spacing: 0.5px; }
        .vh-value { font-size: 10pt; font-weight: 700; color: #1e3a5f; margin-top: 2px; }
        .vh-sub { font-size: 7.5pt; color: #4a5568; }

        .section-title { background: #744210; color: #fff; font-size: 8.5pt; font-weight: 700; padding: 5px 10px; margin: 12px 0 8px 0; border-radius: 3px; text-transform: uppercase; letter-spacing: 0.5px; }

        table.detail { width: 100%; border-collapse: collapse; font-size: 8pt; margin-bottom: 10px; }
        table.detail th { background: #2d3748; color: #fff; padding: 5px 7px; text-align: left; font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.5px; }
        table.detail td { padding: 4.5px 7px; border-bottom: 1px solid #e2e8f0; color: #2d3748; }
        table.detail tr:nth-child(even) td { background: #f7fafc; }
        table.detail td.right { text-align: right; }
        table.detail td.center { text-align: center; }
        table.detail tr.total-row td { background: #e2e8f0; font-weight: 700; font-size: 8.5pt; border-top: 2px solid #2d3748; }

        .sign-section { margin-top: 24px; display: table; width: 100%; }
        .sign-status { display: table-cell; width: 40%; vertical-align: top; }
        .sign-box-wrap { display: table-cell; width: 60%; vertical-align: top; }
        .sign-boxes { display: table; width: 100%; }
        .sign-box { display: table-cell; width: 50%; text-align: center; padding: 0 10px; }
        .sign-line { margin: 45px auto 5px auto; width: 140px; border-top: 1px solid #2d3748; }
        .sign-name { font-size: 8.5pt; font-weight: 700; }
        .sign-role { font-size: 7.5pt; color: #718096; }
        .sign-date { font-size: 7pt; color: #4a5568; margin-top: 2px; }

        .status-pill { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 8pt; font-weight: 700; }
        .status-paid { background: #c6f6d5; color: #22543d; }
        .status-approved { background: #bee3f8; color: #2a4365; }
        .status-draft { background: #e2e8f0; color: #4a5568; }

        .footer { position: fixed; bottom: 0; left: 0; right: 0; border-top: 1px solid #e2e8f0; padding: 5px 20px; font-size: 6.5pt; color: #a0aec0; display: table; width: 100%; }
        .footer-left { display: table-cell; }
        .footer-right { display: table-cell; text-align: right; }
    </style>
</head>
<body>

<div class="footer">
    <div class="footer-left">VOUCHER PEMBAYARAN LEMBUR – {{ $batch->batch_code }} – DOKUMEN RESMI INTERNAL</div>
    <div class="footer-right">Dicetak: {{ now()->isoFormat('D MMMM Y, HH:mm') }} WIB</div>
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
            $kopLine1 = \App\Models\Settings\SystemSetting::get('hcm_profile', 'kop_header_line1', 'Divisi Human Capital & Manajemen Sumber Daya Manusia');
            $contacts = array_filter([
                \App\Models\Settings\SystemSetting::get('hcm_profile', 'company_phone'),
                \App\Models\Settings\SystemSetting::get('hcm_profile', 'company_email'),
                \App\Models\Settings\SystemSetting::get('hcm_profile', 'company_socials'),
            ]);
        @endphp
        @if($logoBase64)
            <img src="{{ $logoBase64 }}" alt="Logo" style="max-height:65px; max-width:100px; object-fit:contain;">
        @else
            <div style="width:65px;height:65px;background:#1e3a5f;border-radius:8px;text-align:center;line-height:65px;">
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
        <div class="doc-type">Voucher Lembur</div>
        <div class="doc-num">{{ $batch->batch_code }}</div>
    </div>
</div>

<!-- HEADER VOUCHER -->
<div class="voucher-header">
    <div class="vh-cell">
        <div class="vh-label">Periode Lembur</div>
        <div class="vh-value">
            {{ \Carbon\Carbon::parse($batch->period_start)->isoFormat('D MMM') }} –
            {{ \Carbon\Carbon::parse($batch->period_end)->isoFormat('D MMM Y') }}
        </div>
    </div>
    <div class="vh-cell">
        <div class="vh-label">Departemen / Divisi</div>
        <div class="vh-value">{{ $batch->department ?? 'Semua Divisi' }}</div>
        <div class="vh-sub">Total Peserta: {{ $batch->items->count() }} karyawan</div>
    </div>
    <div class="vh-cell" style="text-align:right;">
        <div class="vh-label">Status Dokumen</div>
        <div class="vh-value" style="margin-top:4px;">
            @if($batch->status === 'PAID_COMPLETED')
                <span class="status-pill status-paid">✓ LUNAS TERBAYAR</span>
            @elseif($batch->status === 'PENDING_FINANCE_SIGN')
                <span class="status-pill status-approved">● VERIFIKASI KAS KEUANGAN</span>
            @elseif($batch->status === 'APPROVED_BY_HCM')
                <span class="status-pill status-approved">● DISETUJUI HCM</span>
            @else
                <span class="status-pill status-draft">○ DRAFT</span>
            @endif
        </div>
    </div>
</div>

<!-- TABEL DETAIL LEMBUR -->
<div class="section-title">Rincian Pembayaran Lembur Per Karyawan</div>
<table class="detail">
    <thead>
        <tr>
            <th style="width:28px;">No</th>
            <th>NIK</th>
            <th>Nama Karyawan</th>
            <th>Departemen</th>
            <th class="center">Tgl Lembur</th>
            <th class="center">Jenis Hari</th>
            <th class="center">Durasi (Mnt)</th>
            <th class="center">Jam</th>
            <th class="right">Nominal (Rp)</th>
        </tr>
    </thead>
    <tbody>
        @php $grandTotal = 0; $totalHours = 0; @endphp
        @foreach(($batch->items ?? $batch->overtimes) as $i => $item)
        @php
            $hrs = (float) ($item->duration_hours ?? (($item->duration_minutes ?? 0) / 60));
            $amt = (float) ($item->total_amount ?? $item->total_pay ?? 0);
            $grandTotal += $amt;
            $totalHours += $hrs;
        @endphp
        <tr>
            <td class="center">{{ $i + 1 }}</td>
            <td>{{ $item->employee->employee_code ?? '-' }}</td>
            <td><strong>{{ $item->employee->name ?? '-' }}</strong></td>
            <td>{{ $item->employee->department ?? '-' }}</td>
            <td class="center">{{ \Carbon\Carbon::parse($item->overtime_date)->isoFormat('D MMM Y') }}</td>
            <td class="center">
                <span class="status-pill {{ str_contains($item->day_type, 'Libur') ? 'status-approved' : 'status-draft' }}">
                    {{ $item->day_type ?? '-' }}
                </span>
            </td>
            <td class="center">{{ round($hrs * 60) }} mnt</td>
            <td class="center">{{ number_format($hrs, 1) }} Jam</td>
            <td class="right">{{ number_format($amt, 0, ',', '.') }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="6" class="right">TOTAL KESELURUHAN</td>
            <td class="center">{{ round($totalHours * 60) }} mnt</td>
            <td class="center">{{ number_format($totalHours, 1) }} Jam</td>
            <td class="right">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
        </tr>
    </tbody>
</table>

<!-- INFORMASI PEMBAYARAN -->
@if($batch->payment_method)
<div style="background:#f0fff4;border:1px solid #9ae6b4;border-radius:5px;padding:8px 12px;margin-bottom:12px;font-size:8pt;">
    <strong style="color:#22543d;">Metode Pembayaran:</strong>
    <span style="color:#2f855a;">{{ $batch->payment_method }}</span>
    @if($batch->finance_signed_at || $batch->paid_at)
    &nbsp;&nbsp;|&nbsp;&nbsp;
    <strong style="color:#22543d;">Tanggal Bayar:</strong>
    <span style="color:#2f855a;">{{ \Carbon\Carbon::parse($batch->finance_signed_at ?? $batch->paid_at)->isoFormat('D MMMM Y') }}</span>
    @endif
    <br>
    <strong style="color:#22543d;">Bukti Bayar / Transfer:</strong>
    @if($batch->payout_proof_url)
        <span style="color:#2f855a;word-break:break-all;">{{ $batch->payout_proof_url }}</span>
    @else
        <span style="color:#718096;">Tidak dilampirkan (cukup catatan voucher).</span>
    @endif
</div>
@endif

<!-- TTD DOUBLE SIGN-OFF -->
<div class="sign-section">
    <div class="sign-status">
        <div style="font-size:8pt;font-weight:700;color:#2d3748;margin-bottom:8px;">Catatan / Keterangan:</div>
        <div style="border:1px solid #e2e8f0;border-radius:4px;padding:8px;min-height:60px;font-size:8pt;color:#4a5568;">
            {{ $batch->finance_notes ?? $batch->notes ?? 'Tidak ada catatan tambahan.' }}
        </div>
    </div>
    <div class="sign-box-wrap">
        <div class="sign-boxes">
            <div class="sign-box">
                <div style="font-size:8pt;color:#718096;">Disetujui oleh,</div>
                <div style="font-size:7.5pt;color:#718096;">Admin Human Capital</div>
                <div class="sign-line"></div>
                <div class="sign-name">{{ $batch->hcmSigner?->name ?? $batch->signedByHcm?->name ?? '( _________________ )' }}</div>
                <div class="sign-role">Admin Human Capital / HCM</div>
                @if($batch->hcm_signed_at ?? $batch->signed_hcm_at)
                <div class="sign-date">{{ \Carbon\Carbon::parse($batch->hcm_signed_at ?? $batch->signed_hcm_at)->isoFormat('D MMM Y, HH:mm') }} WIB</div>
                @endif
            </div>
            <div class="sign-box">
                <div style="font-size:8pt;color:#718096;">Diperiksa & Dibayar,</div>
                <div style="font-size:7.5pt;color:#718096;">Admin Keuangan</div>
                <div class="sign-line"></div>
                <div class="sign-name">{{ $batch->financeSigner?->name ?? $batch->signedByFinance?->name ?? '( _________________ )' }}</div>
                <div class="sign-role">Admin Keuangan / Finance</div>
                @if($batch->finance_signed_at ?? $batch->signed_finance_at)
                <div class="sign-date">{{ \Carbon\Carbon::parse($batch->finance_signed_at ?? $batch->signed_finance_at)->isoFormat('D MMM Y, HH:mm') }} WIB</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div style="margin-top:20px; padding-top:10px; border-top:1px solid #e2e8f0; font-size:7.5pt; color:#a0aec0; text-align:center;">
    {{ \App\Models\Settings\SystemSetting::get('hcm_profile', 'document_footer_text', 'Dokumen resmi diterbitkan otomatis oleh Sistem Kepegawaian terintegrasi.') }} &bull; {{ \App\Models\Settings\SystemSetting::get('hcm_profile', 'document_footer_disclaimer', 'Keabsahan dokumen dan double sign-off tercatat di sistem audit.') }}
</div>

</body>
</html>
