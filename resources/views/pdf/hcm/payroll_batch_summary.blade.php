<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi Penggajian Eksekutif – {{ $payroll->period_code }}</title>
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
            size: A4 landscape;
            margin: 20px 24px 18px 24px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', Arial, sans-serif;
            font-size: 7.8pt;
            color: #1e293b;
            background: #ffffff;
            line-height: 1.35;
            width: 100%;
        }

        .meta-card {
            width: 100%;
            border-collapse: collapse;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-top: 2.5px solid #a8001c;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        .meta-card td {
            padding: 6px 10px;
            vertical-align: top;
        }
        .m-label {
            font-size: 6.8pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-weight: 700;
        }
        .m-val {
            font-size: 9pt;
            font-weight: 700;
            color: #0f172a;
            margin-top: 1px;
        }
        .m-sub {
            font-size: 7pt;
            color: #64748b;
        }

        .dept-banner {
            background: #0f172a;
            color: #ffffff;
            padding: 4px 8px;
            font-size: 8pt;
            font-weight: 700;
            margin-top: 8px;
            margin-bottom: 3px;
            border-radius: 4px;
        }
        .div-banner {
            background: #f1f5f9;
            color: #334155;
            padding: 3px 6px;
            font-size: 7.2pt;
            font-weight: 700;
            border-left: 3px solid #6366f1;
            margin: 4px 0 2px 0;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.2pt;
            margin-bottom: 6px;
            border: 1px solid #cbd5e1;
        }
        table.data-table th {
            background: #f8fafc;
            color: #1e293b;
            padding: 4px 5px;
            text-align: left;
            font-size: 6.8pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 1.5px solid #cbd5e1;
            font-weight: 700;
        }
        table.data-table td {
            padding: 3.5px 5px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }
        table.data-table tr:nth-child(even) td {
            background: #fafafa;
        }
        table.data-table td.right {
            text-align: right;
            font-family: 'Inter', monospace;
        }
        table.data-table td.center {
            text-align: center;
        }
        table.data-table tr.dept-total-row td {
            background: #f1f5f9;
            font-weight: 700;
            border-top: 1.5px solid #94a3b8;
            border-bottom: 1.5px solid #94a3b8;
            color: #0f172a;
        }
        table.data-table tr.grand-total-row td {
            background: #0f172a;
            font-weight: 800;
            color: #38bdf8;
            font-size: 8pt;
            padding: 6px 5px;
        }

        .status-pill {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 6.8pt;
            font-weight: 700;
        }
        .status-paid { background: #dcfce7; color: #15803d; }
        .status-hcm { background: #dbeafe; color: #1d4ed8; }
        .status-draft { background: #fef3c7; color: #b45309; }

        .double-sign-wrap {
            width: 100%;
            border-collapse: separate;
            border-spacing: 12px 0;
            margin-top: 10px;
        }
        .sign-card {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 10px;
            text-align: center;
            background: #ffffff;
        }
        .sign-holder-box {
            position: relative;
            height: 48px;
            margin: 2px auto;
            width: 140px;
        }
    </style>
</head>
<body>

{{-- KOP SURAT --}}
@include('pdf.hcm.partials.kop', [
    'profile' => $profile,
    'badgeText' => 'REKAPITULASI PENGGAJIAN TERPADU',
    'badgeSub' => $payroll->period_code
])

{{-- METADATA BATCH --}}
<table class="meta-card">
    <tr>
        <td style="width: 25%;">
            <div class="m-label">Periode Penggajian</div>
            <div class="m-val">{{ $payroll->period_code }}</div>
            <div class="m-sub">Kinerja: {{ $payroll->work_period_month }} &bull; Cair: {{ $payroll->payout_period_month }}</div>
        </td>
        <td style="width: 25%;">
            <div class="m-label">Tanggal Transfer &amp; Metode</div>
            <div class="m-val">{{ $payroll->payout_date ? \Carbon\Carbon::parse($payroll->payout_date)->isoFormat('D MMMM Y') : 'Sesuai Jadwal' }}</div>
            <div class="m-sub">{{ $payroll->payment_method ?: 'Transfer Bank / Kas' }}</div>
        </td>
        <td style="width: 25%;">
            <div class="m-label">Total Tenaga Kerja</div>
            <div class="m-val">{{ $payroll->total_employees }} Orang</div>
            <div class="m-sub">Lintas Semua Departemen &amp; Divisi</div>
        </td>
        <td style="width: 25%; text-align: right;">
            <div class="m-label">Total Realisasi Kas Payroll</div>
            <div class="m-val" style="color: #a8001c; font-size: 11pt;">
                Rp {{ number_format($payroll->total_net_payout, 0, ',', '.') }}
            </div>
            <div style="margin-top: 2px;">
                @if($payroll->status === 'PAID_COMPLETED')
                    <span class="status-pill status-paid">&#10003; LUNAS TERBAYAR (PAID)</span>
                @elseif($payroll->status === 'APPROVED_BY_HCM')
                    <span class="status-pill status-hcm">&#9679; DISETUJUI HCM (PROSES KEUANGAN)</span>
                @else
                    <span class="status-pill status-draft">&#9675; DRAFT HCM</span>
                @endif
            </div>
        </td>
    </tr>
</table>

{{-- TABEL MULTI-LEVEL GROUPING DEPARTEMEN -> DIVISI --}}
@foreach($departmentGrouping as $dept)
    <div class="dept-banner">
        DEPARTEMEN: {{ strtoupper($dept['department_name']) }} &bull; Total {{ $dept['total_employees'] }} Karyawan &bull; Subtotal Kas: Rp {{ number_format($dept['total_net_salary'], 0, ',', '.') }}
    </div>

    @foreach($dept['divisions'] as $div)
        <div class="div-banner">
            &bull; Divisi: {{ $div['division_name'] }} ({{ $div['total_employees'] }} Karyawan — Subtotal: Rp {{ number_format($div['total_net_salary'], 0, ',', '.') }})
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 20px; text-align: center;">No</th>
                    <th style="width: 65px;">NIP</th>
                    <th>Nama Karyawan</th>
                    <th style="width: 100px;">No. Rekening</th>
                    <th style="width: 65px; text-align: right;">Gaji Pokok</th>
                    <th style="width: 55px; text-align: right;">Uang Makan</th>
                    <th style="width: 55px; text-align: right;">Upah Lembur</th>
                    <th style="width: 65px; text-align: right;">Total Bruto</th>
                    <th style="width: 50px; text-align: right; color: #b91c1c;">Sanksi</th>
                    <th style="width: 50px; text-align: right; color: #b91c1c;">Cuti</th>
                    <th style="width: 50px; text-align: right; color: #b91c1c;">Maternity</th>
                    <th style="width: 65px; text-align: right; font-weight: 700; color: #047857;">Take-Home Pay</th>
                </tr>
            </thead>
            <tbody>
                @foreach($div['items'] as $idx => $it)
                <tr>
                    <td class="center">{{ $idx + 1 }}</td>
                    <td><strong>{{ $it->employee?->employee_code ?? '-' }}</strong></td>
                    <td><strong>{{ $it->employee?->name ?? $it->bank_account_name }}</strong></td>
                    <td>{{ $it->bank_account_no ?: '-' }}</td>
                    <td class="right">Rp {{ number_format($it->base_salary, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($it->meal_allowance, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($it->overtime_pay, 0, ',', '.') }}</td>
                    <td class="right" style="font-weight: 600;">Rp {{ number_format($it->total_earnings, 0, ',', '.') }}</td>
                    <td class="right" style="color: #b91c1c;">{{ $it->penalty_deduction > 0 ? '-' . number_format($it->penalty_deduction, 0, ',', '.') : '-' }}</td>
                    <td class="right" style="color: #b91c1c;">{{ $it->leave_deduction > 0 ? '-' . number_format($it->leave_deduction, 0, ',', '.') : '-' }}</td>
                    <td class="right" style="color: #b91c1c;">{{ $it->tiered_deduction > 0 ? '-' . number_format($it->tiered_deduction, 0, ',', '.') : '-' }}</td>
                    <td class="right" style="font-weight: 700; color: #047857;">Rp {{ number_format($it->net_salary, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
@endforeach

{{-- GRAND TOTAL KESELURUHAN --}}
<table class="data-table" style="margin-top: 8px;">
    <tbody>
        <tr class="grand-total-row">
            <td colspan="4" style="text-align: left; padding-left: 8px;">
                GRAND TOTAL PENGELUARAN PAYROLL ({{ $payroll->total_employees }} ORANG KARYAWAN)
            </td>
            <td class="right" style="width: 65px; color: #ffffff;">Rp {{ number_format($payroll->total_base_salary, 0, ',', '.') }}</td>
            <td class="right" style="width: 55px; color: #ffffff;">Rp {{ number_format($payroll->total_meal_allowance, 0, ',', '.') }}</td>
            <td class="right" style="width: 55px; color: #ffffff;">Rp {{ number_format($payroll->total_overtime_pay, 0, ',', '.') }}</td>
            <td class="right" style="width: 65px; color: #ffffff;">Rp {{ number_format($payroll->total_base_salary + $payroll->total_meal_allowance + $payroll->total_overtime_pay, 0, ',', '.') }}</td>
            <td colspan="3" class="right" style="color: #fca5a5;">- Rp {{ number_format($payroll->total_deductions, 0, ',', '.') }}</td>
            <td class="right" style="width: 65px; color: #38bdf8; font-size: 8.5pt;">Rp {{ number_format($payroll->total_net_payout, 0, ',', '.') }}</td>
        </tr>
    </tbody>
</table>

{{-- DOUBLE SIGN-OFF OTORISASI --}}
<table class="double-sign-wrap">
    <tr>
        <td style="width: 50%;">
            <div class="sign-card">
                <div style="font-size: 7.2pt; font-weight: 700; color: #475569; text-transform: uppercase;">Pengesahan HCM (Level 1)</div>
                <div class="sign-holder-box">
                    @if($payroll->hcm_signed_at && !empty($profile['signature_base64']))
                        <img src="{{ $profile['signature_base64'] }}" style="max-height: 44px; max-width: 110px; object-fit: contain;">
                    @endif
                </div>
                <div style="font-weight: 700; font-size: 8pt; border-top: 1px solid #0f172a; display: inline-block; min-width: 120px;">
                    {{ $payroll->hcmSigner?->name ?? $profile['signer_name'] }}
                </div>
                <div style="font-size: 6.8pt; color: #64748b;">
                    {{ $payroll->hcm_signed_at ? \Carbon\Carbon::parse($payroll->hcm_signed_at)->isoFormat('D MMMM Y, HH:mm') . ' WIB' : 'Menunggu Otorisasi HCM' }}
                </div>
            </div>
        </td>
        <td style="width: 50%;">
            <div class="sign-card">
                <div style="font-size: 7.2pt; font-weight: 700; color: #475569; text-transform: uppercase;">Pencairan Kas Keuangan (Level 2)</div>
                <div class="sign-holder-box">
                    @if($payroll->finance_signed_at && !empty($profile['signature_base64']))
                        <img src="{{ $profile['signature_base64'] }}" style="max-height: 44px; max-width: 110px; object-fit: contain;">
                    @endif
                </div>
                <div style="font-weight: 700; font-size: 8pt; border-top: 1px solid #0f172a; display: inline-block; min-width: 120px;">
                    {{ $payroll->financeSigner?->name ?? ('Tim Keuangan ' . ($profile['company_name'] ?? '')) }}
                </div>
                <div style="font-size: 6.8pt; color: #64748b;">
                    {{ $payroll->finance_signed_at ? \Carbon\Carbon::parse($payroll->finance_signed_at)->isoFormat('D MMMM Y, HH:mm') . ' WIB' : 'Menunggu Pencairan Keuangan' }}
                </div>
            </div>
        </td>
    </tr>
</table>

@include('pdf.hcm.partials.footer', ['profile' => $profile])

</body>
</html>
