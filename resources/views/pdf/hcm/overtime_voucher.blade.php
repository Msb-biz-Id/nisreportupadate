<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Voucher Lembur – {{ $batch->batch_code }}</title>
    <style>
        @font-face { font-family: 'Inter'; font-weight: 400; src: url('{{ public_path("fonts/Inter-Regular.ttf") }}') format('truetype'); }
        @font-face { font-family: 'Inter'; font-weight: 700; src: url('{{ public_path("fonts/Inter-Bold.ttf") }}') format('truetype'); }

        @page { size: A4 portrait; margin: 24px 30px 22px 30px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', Arial, sans-serif; font-size: 8.5pt; color: #1e293b; background: #fff; line-height: 1.4; width: 100%; }

        .voucher-meta-card { background: #f8fafc; border: 1px solid #e2e8f0; border-top: 2.5px solid #a8001c; border-radius: 6px; padding: 10px 14px; margin-bottom: 12px; width: 100%; }
        .vm-col { vertical-align: top; }
        .vm-label { font-size: 7pt; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; }
        .vm-val { font-size: 9.5pt; font-weight: 700; color: #0f172a; margin-top: 2px; }
        .vm-sub { font-size: 7.5pt; color: #64748b; }

        .section-heading { 
            background: #f8fafc; 
            border-left: 3.5px solid #a8001c; 
            border-top: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            color: #0f172a; 
            font-size: 8pt; 
            font-weight: 700; 
            padding: 4.5px 10px; 
            margin: 12px 0 6px 0; 
            border-radius: 0 4px 4px 0; 
            text-transform: uppercase; 
            letter-spacing: 0.6px; 
        }

        table.detail-table { width: 100%; border-collapse: collapse; font-size: 8pt; margin-bottom: 8px; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; }
        table.detail-table th { background: #f1f5f9; color: #1e293b; padding: 5px 6px; text-align: left; font-size: 7.2pt; text-transform: uppercase; letter-spacing: 0.35px; border-bottom: 1.5px solid #cbd5e1; font-weight: 700; }
        table.detail-table td { padding: 4.5px 6px; border-bottom: 1px solid #f1f5f9; color: #1e293b; }
        table.detail-table tr:nth-child(even) td { background: #fbfcfe; }
        table.detail-table td.right { text-align: right; }
        table.detail-table td.center { text-align: center; }
        table.detail-table tr.total-row td { background: #f1f5f9; font-weight: 700; font-size: 8.5pt; border-top: 1.5px solid #94a3b8; border-bottom: 1.5px solid #94a3b8; color: #0f172a; }

        .status-pill { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 7.5pt; font-weight: 700; }
        .status-paid { background: #dcfce7; color: #15803d; }
        .status-approved { background: #dbeafe; color: #1d4ed8; }
        .status-draft { background: #f1f5f9; color: #475569; }

        .double-sign-wrap { width: 100%; margin-top: 14px; border-collapse: collapse; }
        .sign-card { border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; text-align: center; background: #fff; }
        .sign-header-role { font-size: 7.5pt; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; }
        .sign-title-sub { font-size: 7pt; color: #64748b; margin-top: 1px; }
        .sign-holder-box { position: relative; height: 65px; margin: 3px auto; width: 160px; }
        .sign-image { max-height: 58px; max-width: 140px; object-fit: contain; }
        .stamp-overlay { position: absolute; left: -10px; top: 0px; width: 55px; height: 55px; opacity: 0.85; }
        .sign-name-underline { font-size: 8.5pt; font-weight: 700; color: #0f172a; border-top: 1px solid #0f172a; padding-top: 3px; display: inline-block; min-width: 140px; }
        .sign-nik-text { font-size: 7pt; color: #64748b; margin-top: 1px; }
    </style>
</head>
<body>

{{-- 1. KOP SURAT BAKU RESMI INDONESIA --}}
@include('pdf.hcm.partials.kop', [
    'profile' => $profile,
    'badgeText' => 'Voucher Lembur',
    'badgeSub' => $batch->batch_code
])

{{-- 2. METADATA VOUCHER --}}
<table class="voucher-meta-card" style="border-collapse: collapse;">
    <tr>
        <td class="vm-col" style="width: 32%;">
            <div class="vm-label">Periode Lembur</div>
            <div class="vm-val">
                {{ \Carbon\Carbon::parse($batch->period_start)->isoFormat('D MMM') }} –
                {{ \Carbon\Carbon::parse($batch->period_end)->isoFormat('D MMM Y') }}
            </div>
            <div class="vm-sub">Total: {{ $batch->items->count() }} orang pekerja</div>
        </td>
        <td class="vm-col" style="width: 34%;">
            <div class="vm-label">Departemen / Divisi</div>
            <div class="vm-val">{{ $batch->department ?? 'Semua Departemen' }}</div>
            <div class="vm-sub">COA Akuntansi: {{ $batch->coa_code ?? '5-50100' }}</div>
        </td>
        <td class="vm-col" style="width: 34%; text-align: right;">
            <div class="vm-label">Total Pengeluaran Lembur</div>
            <div class="vm-val" style="color: #a8001c; font-size: 11pt;">
                Rp {{ number_format($batch->total_amount, 0, ',', '.') }}
            </div>
            <div style="margin-top: 3px;">
                @if($batch->status === 'PAID_COMPLETED')
                    <span class="status-pill status-paid">&#10003; LUNAS TERBAYAR</span>
                @elseif($batch->status === 'PENDING_FINANCE_SIGN')
                    <span class="status-pill status-approved">&#9679; VERIFIKASI KEUANGAN</span>
                @elseif($batch->status === 'APPROVED_BY_HCM')
                    <span class="status-pill status-approved">&#9679; DISETUJUI HCM</span>
                @else
                    <span class="status-pill status-draft">&#9675; DRAFT</span>
                @endif
            </div>
        </td>
    </tr>
</table>

{{-- 3. TABEL DETAIL LEMBUR --}}
<div class="section-heading">Rincian Kompensasi Lembur Karyawan</div>
<table class="detail-table">
    <thead>
        <tr>
            <th style="width: 25px; text-align: center;">No</th>
            <th style="width: 80px;">NIP</th>
            <th>Nama Lengkap</th>
            <th>Divisi / Jabatan</th>
            <th style="width: 70px; text-align: center;">Tgl Lembur</th>
            <th style="width: 55px; text-align: center;">Jam Kerja</th>
            <th style="width: 50px; text-align: center;">Durasi</th>
            <th style="width: 75px; text-align: right;">Tarif / Jam</th>
            <th style="width: 85px; text-align: right;">Total Upah</th>
        </tr>
    </thead>
    <tbody>
        @forelse($batch->items as $idx => $item)
        <tr>
            <td class="center">{{ $idx + 1 }}</td>
            <td><strong>{{ $item->employee?->employee_code ?? '-' }}</strong></td>
            <td><strong>{{ $item->employee?->name ?? $item->employee_name }}</strong></td>
            <td>{{ $item->employee?->department ?? '-' }} &bull; {{ $item->employee?->position ?? '-' }}</td>
            <td class="center">{{ \Carbon\Carbon::parse($item->overtime_date)->isoFormat('D MMM Y') }}</td>
            <td class="center">{{ substr($item->start_time ?? '', 0, 5) }} - {{ substr($item->end_time ?? '', 0, 5) }}</td>
            <td class="center">{{ number_format($item->duration_hours, 1) }} jam</td>
            <td class="right">Rp {{ number_format($item->hourly_rate, 0, ',', '.') }}</td>
            <td class="right" style="font-weight: 700; color: #0f172a;">Rp {{ number_format($item->total_pay, 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="9" class="center" style="padding: 12px; color: #64748b;">Tidak ada rincian data lembur pada batch ini.</td>
        </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="6" style="text-align: right; padding-right: 10px;">TOTAL BIAYA UPAH LEMBUR:</td>
            <td class="center">{{ number_format($batch->items->sum('duration_hours'), 1) }} jam</td>
            <td></td>
            <td class="right" style="font-size: 9.5pt; color: #a8001c;">Rp {{ number_format($batch->total_amount, 0, ',', '.') }}</td>
        </tr>
    </tfoot>
</table>

{{-- 4. DOUBLE SIGN-OFF (HCM & KEUANGAN) --}}
@php
    $hcmName = $batch->hcmSigner?->name ?? $profile['signer_name'];
    $hcmRole = $profile['signer_role'];
    $hcmNik  = $profile['signer_nik'];
    $showHcmSig = $profile['show_signature_on_pdf'] && !empty($profile['signature_base64']);
    $showStamp  = $profile['show_stamp_on_pdf'] && !empty($profile['stamp_base64']);

    $finName = $batch->financeSigner?->name ?? 'Admin Keuangan';
@endphp

<table class="double-sign-wrap">
    <tr>
        <td style="width: 48%; padding-right: 8px; vertical-align: top;">
            <div class="sign-card">
                <div class="sign-header-role">Disetujui Oleh (HCM)</div>
                <div class="sign-title-sub">{{ $profile['division_name'] }}</div>
                
                <div class="sign-holder-box">
                    @if($showStamp)
                    <img src="{{ $profile['stamp_base64'] }}" class="stamp-overlay" alt="Stempel">
                    @endif
                    @if($showHcmSig)
                    <img src="{{ $profile['signature_base64'] }}" class="sign-image" alt="TTD HCM">
                    @endif
                </div>

                <div class="sign-name-underline">{{ $hcmName ?: '( ______________________ )' }}</div>
                <div class="sign-nik-text">{{ $hcmRole }} @if($hcmNik) &bull; NIK: {{ $hcmNik }} @endif</div>
                @if($batch->hcm_signed_at)
                <div style="font-size: 6.8pt; color: #15803d; margin-top: 2px;">
                    Diverifikasi: {{ \Carbon\Carbon::parse($batch->hcm_signed_at)->isoFormat('D MMM Y, HH:mm') }} WIB
                </div>
                @endif
            </div>
        </td>
        <td style="width: 4%; text-align: center;"></td>
        <td style="width: 48%; padding-left: 8px; vertical-align: top;">
            <div class="sign-card">
                <div class="sign-header-role">Diperiksa & Dibayar (Keuangan)</div>
                <div class="sign-title-sub">Finance & Treasury Operations</div>

                <div class="sign-holder-box">
                    @if($batch->status === 'PAID_COMPLETED')
                    <div style="padding-top: 15px; font-weight: 700; color: #15803d; font-size: 9pt;">
                        &#10003; TERBAYAR LUNAS
                    </div>
                    @endif
                </div>

                <div class="sign-name-underline">{{ $finName ?: '( ______________________ )' }}</div>
                <div class="sign-nik-text">Bagian Administrasi Keuangan & Kasir</div>
                @if($batch->finance_signed_at)
                <div style="font-size: 6.8pt; color: #15803d; margin-top: 2px;">
                    Dibayar: {{ \Carbon\Carbon::parse($batch->finance_signed_at)->isoFormat('D MMM Y, HH:mm') }} WIB
                </div>
                @endif
            </div>
        </td>
    </tr>
</table>

{{-- 5. CATATAN KAKI DOKUMEN --}}
@include('pdf.hcm.partials.footer', ['profile' => $profile])

</body>
</html>
