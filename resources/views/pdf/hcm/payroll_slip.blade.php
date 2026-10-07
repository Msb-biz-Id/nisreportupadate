<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji – {{ $payroll->period_code }} – {{ $employee?->name ?? 'Karyawan' }}</title>
    <style>
        @font-face {
            font-family: 'Inter';
            font-weight: 400;
            src: url('{{ public_path("fonts/Inter-Regular.ttf") }}') format('truetype');
        }
        @font-face {
            font-family: 'Inter';
            font-weight: 700;
            src: url('{{ public_path("fonts/Inter-Bold.ttf") }}') format('truetype');
        }

        @page {
            size: A4 portrait;
            margin: 22px 28px 20px 28px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', Arial, sans-serif;
            font-size: 8.5pt;
            color: #1e293b;
            background: #ffffff;
            line-height: 1.4;
            width: 100%;
            position: relative;
        }

        /* WATERMARK CAP BACKGROUND */
        .watermark-stamp {
            position: absolute;
            top: 42%;
            left: 20%;
            width: 60%;
            text-align: center;
            transform: rotate(-24deg);
            z-index: 0;
            opacity: 0.08;
            pointer-events: none;
        }
        .watermark-stamp.paid {
            color: #059669;
            border: 8px solid #059669;
            padding: 14px 24px;
            border-radius: 16px;
            font-size: 54pt;
            font-weight: 900;
            letter-spacing: 6px;
            text-transform: uppercase;
        }
        .watermark-stamp.draft {
            color: #d97706;
            border: 8px solid #d97706;
            padding: 14px 24px;
            border-radius: 16px;
            font-size: 46pt;
            font-weight: 900;
            letter-spacing: 5px;
            text-transform: uppercase;
        }

        /* KARTU IDENTITAS KARYAWAN */
        .employee-info-card {
            width: 100%;
            border-collapse: collapse;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-top: 3px solid #a8001c;
            border-radius: 6px;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }
        .employee-info-card td {
            padding: 6px 10px;
            vertical-align: top;
            font-size: 8pt;
        }
        .info-label {
            color: #64748b;
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .info-value {
            font-size: 9pt;
            font-weight: 700;
            color: #0f172a;
        }
        .info-sub {
            font-size: 7.5pt;
            color: #475569;
            margin-top: 1px;
        }

        /* TABEL DUA KOLOM KOMPONEN GAJI */
        .components-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }
        .components-table > tbody > tr > td {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }

        .panel-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
            background: #ffffff;
        }
        .panel-header {
            padding: 6px 10px;
            font-size: 8pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #cbd5e1;
        }
        .panel-header.earnings {
            background: #f0fdf4;
            color: #166534;
            border-top: 2.5px solid #16a34a;
        }
        .panel-header.deductions {
            background: #fef2f2;
            color: #991b1b;
            border-top: 2.5px solid #dc2626;
        }

        .item-list-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }
        .item-list-table td {
            padding: 5.5px 10px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }
        .item-list-table tr:last-child td {
            border-bottom: none;
        }
        .item-list-table td.amount {
            text-align: right;
            font-family: 'Inter', monospace;
            font-weight: 600;
        }
        .item-subtext {
            font-size: 7pt;
            color: #64748b;
            display: block;
            margin-top: 1px;
        }

        .subtotal-row td {
            background: #f8fafc;
            font-weight: 700;
            border-top: 1.5px solid #cbd5e1;
            color: #0f172a;
            padding: 6px 10px;
        }
        .subtotal-row.earnings td {
            color: #166534;
        }
        .subtotal-row.deductions td {
            color: #991b1b;
        }

        /* KOTAK TAKE-HOME PAY (NET SALARY) */
        .take-home-card {
            width: 100%;
            border-collapse: collapse;
            background: #0f172a;
            color: #ffffff;
            border-radius: 6px;
            margin-bottom: 14px;
            position: relative;
            z-index: 1;
            overflow: hidden;
        }
        .take-home-card td {
            padding: 10px 14px;
            vertical-align: middle;
        }
        .th-label {
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #94a3b8;
            font-weight: 700;
        }
        .th-amount {
            font-size: 15pt;
            font-weight: 800;
            font-family: 'Inter', monospace;
            color: #38bdf8;
            letter-spacing: 0.5px;
            text-align: right;
        }
        .th-terbilang {
            font-size: 7.8pt;
            font-style: italic;
            color: #e2e8f0;
            margin-top: 2px;
            line-height: 1.3;
        }

        /* CATATAN & VERIFIKASI QR CODE */
        .verification-bar {
            width: 100%;
            border-collapse: collapse;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }
        .verification-bar td {
            padding: 8px 10px;
            vertical-align: middle;
        }
        .qr-box {
            width: 75px;
            text-align: center;
        }
        .qr-box img {
            width: 65px;
            height: 65px;
            display: block;
            margin: 0 auto;
        }
        .token-info {
            font-size: 7.5pt;
            color: #334155;
            line-height: 1.4;
        }
        .token-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 7pt;
            font-weight: 700;
            color: #0f172a;
            word-break: break-all;
        }

        /* BLOK DOUBLE SIGN-OFF (HCM & KEUANGAN) */
        .signature-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 12px 0;
            margin-top: 8px;
            position: relative;
            z-index: 1;
        }
        .signature-table > tbody > tr > td {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }
        .sign-card {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 12px;
            text-align: center;
            background: #ffffff;
        }
        .sign-title {
            font-size: 7.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #475569;
        }
        .sign-sub {
            font-size: 7pt;
            color: #64748b;
            margin-top: 1px;
        }
        .sign-holder {
            position: relative;
            height: 58px;
            margin: 4px auto;
            width: 180px;
        }
        .sign-img {
            max-height: 52px;
            max-width: 130px;
            object-fit: contain;
        }
        .stamp-img {
            position: absolute;
            left: 10px;
            top: 2px;
            width: 50px;
            height: 50px;
            opacity: 0.85;
        }
        .sign-name {
            font-size: 8.5pt;
            font-weight: 700;
            color: #0f172a;
            border-top: 1px solid #0f172a;
            display: inline-block;
            min-width: 140px;
            padding-top: 3px;
        }
        .sign-date {
            font-size: 7pt;
            color: #64748b;
            margin-top: 1px;
        }
    </style>
</head>
<body>

{{-- WATERMARK STATUS --}}
@if($item->is_paid || $payroll->status === 'PAID_COMPLETED')
    <div class="watermark-stamp paid">LUNAS / PAID</div>
@else
    <div class="watermark-stamp draft">DRAFT / PROSES</div>
@endif

{{-- 1. KOP SURAT RESMI HCM --}}
@include('pdf.hcm.partials.kop', [
    'profile' => $profile,
    'badgeText' => 'SLIP GAJI RESMI',
    'badgeSub' => $payroll->period_code
])

{{-- 2. KARTU IDENTITAS KARYAWAN & PERIODE PENGGAJIAN --}}
<table class="employee-info-card">
    <tr>
        <td style="width: 28%;">
            <div class="info-label">Nama Karyawan</div>
            <div class="info-value">{{ $employee?->name ?? $item->bank_account_name ?? 'Karyawan' }}</div>
            <div class="info-sub">NIP: {{ $employee?->employee_code ?? '-' }}</div>
        </td>
        <td style="width: 26%;">
            <div class="info-label">Struktur Organisasi</div>
            <div class="info-value">{{ $item->department ?: ($employee?->department ?? '-') }}</div>
            <div class="info-sub">{{ $item->division ?: ($employee?->division ?? 'Umum') }}</div>
        </td>
        <td style="width: 24%;">
            <div class="info-label">Rekening Bank BRI</div>
            <div class="info-value">{{ $item->bank_account_no ?: ($employee?->bank_account_no ?? 'Belum diisi') }}</div>
            <div class="info-sub">a.n {{ $item->bank_account_name ?: ($employee?->name ?? '-') }}</div>
        </td>
        <td style="width: 22%; text-align: right;">
            <div class="info-label">Periode Gaji</div>
            <div class="info-value">{{ $payroll->period_code }}</div>
            <div class="info-sub">Kinerja: {{ $payroll->work_period_month }}<br>Bayar: {{ $payroll->payout_period_month }}</div>
        </td>
    </tr>
</table>

{{-- 3. RINCIAN PENDAPATAN VS PEMOTONGAN --}}
<table class="components-table">
    <tr>
        {{-- KOLOM PENDAPATAN (EARNINGS) --}}
        <td>
            <div class="panel-box">
                <div class="panel-header earnings">
                    I. Penghasilan Bruto (Earnings)
                </div>
                <table class="item-list-table">
                    <tr>
                        <td>
                            <strong>Gaji Pokok / Honor</strong>
                            <span class="item-subtext">Sesuai kontrak kerja aktif</span>
                        </td>
                        <td class="amount">Rp {{ number_format($item->base_salary, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>
                            <strong>Tunjangan Uang Makan</strong>
                            <span class="item-subtext">Berdasarkan audit kehadiran presensi</span>
                        </td>
                        <td class="amount">Rp {{ number_format($item->meal_allowance, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>
                            <strong>Upah Lembur Riil</strong>
                            <span class="item-subtext">Akumulasi jam lembur mingguan</span>
                        </td>
                        <td class="amount">Rp {{ number_format($item->overtime_pay, 0, ',', '.') }}</td>
                    </tr>
                    @if($item->increment_adjustment > 0)
                    <tr>
                        <td>
                            <strong>Penyesuaian Kenaikan Upah</strong>
                            <span class="item-subtext">Evaluasi berkala disetujui</span>
                        </td>
                        <td class="amount">Rp {{ number_format($item->increment_adjustment, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="subtotal-row earnings">
                        <td><strong>TOTAL PENGHASILAN KOTOR</strong></td>
                        <td class="amount">Rp {{ number_format($item->total_earnings, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </td>

        {{-- KOLOM PEMOTONGAN (DEDUCTIONS) --}}
        <td>
            <div class="panel-box">
                <div class="panel-header deductions">
                    II. Pemotongan Gaji (Deductions)
                </div>
                <table class="item-list-table">
                    <tr>
                        <td>
                            <strong>Pelanggaran Disiplin / Sanksi</strong>
                            <span class="item-subtext">Sanksi SOP operasional</span>
                        </td>
                        <td class="amount" style="color: #b91c1c;">
                            {{ $item->penalty_deduction > 0 ? '- Rp ' . number_format($item->penalty_deduction, 0, ',', '.') : 'Rp 0' }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>Kelebihan Pengambilan Cuti</strong>
                            <span class="item-subtext">Potongan cuti tidak berbayar</span>
                        </td>
                        <td class="amount" style="color: #b91c1c;">
                            {{ $item->leave_deduction > 0 ? '- Rp ' . number_format($item->leave_deduction, 0, ',', '.') : 'Rp 0' }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>Cuti Khusus Berjenjang</strong>
                            <span class="item-subtext">Maternity Leave (25% / 50%)</span>
                        </td>
                        <td class="amount" style="color: #b91c1c;">
                            {{ $item->tiered_deduction > 0 ? '- Rp ' . number_format($item->tiered_deduction, 0, ',', '.') : 'Rp 0' }}
                        </td>
                    </tr>
                    <tr class="subtotal-row deductions">
                        <td><strong>TOTAL PEMOTONGAN GAJI</strong></td>
                        <td class="amount" style="color: #b91c1c;">
                            - Rp {{ number_format($item->total_deductions, 0, ',', '.') }}
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>

{{-- 4. KOTAK TAKE-HOME PAY (NET SALARY) RESMI --}}
<table class="take-home-card">
    <tr>
        <td style="width: 55%;">
            <div class="th-label">PENGHASILAN BERSIH YANG DITERIMA (TAKE-HOME PAY)</div>
            <div class="th-terbilang">" {{ $terbilang }} "</div>
        </td>
        <td style="width: 45%;">
            <div class="th-amount">Rp {{ number_format($item->net_salary, 0, ',', '.') }}</div>
        </td>
    </tr>
</table>

{{-- 5. KOTAK VERIFIKASI DIGITAL & TOKEN KEABSAHAN --}}
<table class="verification-bar">
    <tr>
        <td class="qr-box">
            @if(!empty($qrCodeBase64))
                <img src="data:image/svg+xml;base64,{{ $qrCodeBase64 }}" alt="QR Verifikasi">
            @endif
        </td>
        <td>
            <div class="token-info">
                <strong>Verifikasi Digital &amp; Keabsahan Dokumen</strong><br>
                Dokumen slip gaji ini diterbitkan resmi secara otomatis oleh Unified Payroll Engine {{ $profile['division_name'] ?? 'HCM' }} {{ $profile['company_name'] ?? '' }}.<br>
                Token Autentikasi: <span class="token-code">{{ $item->slip_token }}</span><br>
                Verifikasi Online: <span style="color: #2563eb;">{{ $verificationUrl }}</span>
            </div>
        </td>
    </tr>
</table>

{{-- 6. DOUBLE SIGN-OFF OTORISASI (HCM & KEUANGAN) --}}
<table class="signature-table">
    <tr>
        {{-- SIGN LEVEL 1: HCM GATEKEEPER DATA --}}
        <td>
            <div class="sign-card">
                <div class="sign-title">Otorisasi Level 1: Human Capital Management</div>
                <div class="sign-sub">Audit Absensi, Lembur &amp; Potongan</div>
                <div class="sign-holder">
                    @if($payroll->hcm_signed_at && !empty($profile['show_signature_on_pdf']) && !empty($profile['signature_base64']))
                        <img src="{{ $profile['signature_base64'] }}" class="sign-img" alt="TTD HCM">
                    @endif
                    @if($payroll->hcm_signed_at && !empty($profile['show_stamp_on_pdf']) && !empty($profile['stamp_base64']))
                        <img src="{{ $profile['stamp_base64'] }}" class="stamp-img" alt="Stempel HCM">
                    @endif
                </div>
                <div class="sign-name">{{ $payroll->hcmSigner?->name ?? $profile['signer_name'] }}</div>
                <div class="sign-date">
                    {{ $payroll->hcm_signed_at ? \Carbon\Carbon::parse($payroll->hcm_signed_at)->isoFormat('D MMMM Y, HH:mm') . ' WIB' : 'Menunggu Otorisasi HCM' }}
                </div>
            </div>
        </td>

        {{-- SIGN LEVEL 2: KEUANGAN GATEKEEPER DANA --}}
        <td>
            <div class="sign-card">
                <div class="sign-title">Otorisasi Level 2: Departemen Keuangan</div>
                <div class="sign-sub">Verifikasi Kas / Bank &amp; Pencairan Payroll</div>
                <div class="sign-holder">
                    @if($payroll->finance_signed_at && !empty($profile['show_signature_on_pdf']) && !empty($profile['signature_base64']))
                        <img src="{{ $profile['signature_base64'] }}" class="sign-img" alt="TTD Keuangan">
                    @endif
                </div>
                <div class="sign-name">{{ $payroll->financeSigner?->name ?? ('Tim Keuangan ' . ($profile['company_name'] ?? '')) }}</div>
                <div class="sign-date">
                    {{ $payroll->finance_signed_at ? \Carbon\Carbon::parse($payroll->finance_signed_at)->isoFormat('D MMMM Y, HH:mm') . ' WIB' : 'Menunggu Pencairan Keuangan' }}
                </div>
            </div>
        </td>
    </tr>
</table>

{{-- 7. FOOTER DOKUMEN RESMI --}}
@include('pdf.hcm.partials.footer', ['profile' => $profile])

</body>
</html>
