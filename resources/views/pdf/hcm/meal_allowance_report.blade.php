<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi Uang Makan – {{ $batch->batch_code }}</title>
    <style>
        @font-face { font-family: 'Inter'; font-weight: 400; src: url('{{ public_path("fonts/Inter-Regular.ttf") }}') format('truetype'); }
        @font-face { font-family: 'Inter'; font-weight: 700; src: url('{{ public_path("fonts/Inter-Bold.ttf") }}') format('truetype'); }

        @page { size: A4 landscape; margin: 24px 28px 22px 28px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', Arial, sans-serif; font-size: 8pt; color: #1e293b; background: #fff; line-height: 1.35; width: 100%; }

        .meta-summary-bar { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin-bottom: 10px; }
        .msb-cell { background: #f8fafc; border: 1px solid #e2e8f0; border-top: 2.5px solid #a8001c; border-radius: 6px; padding: 6px 10px; width: 25%; text-align: center; }
        .msb-label { font-size: 6.8pt; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .msb-val { font-size: 11pt; font-weight: 700; color: #0f172a; margin-top: 1px; }
        .msb-sub { font-size: 6.8pt; color: #64748b; }

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

        table.detail-table { width: 100%; border-collapse: collapse; font-size: 7.5pt; margin-bottom: 8px; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; }
        table.detail-table th { background: #f1f5f9; color: #1e293b; padding: 5px 6px; text-align: left; font-size: 7pt; text-transform: uppercase; letter-spacing: 0.35px; border-bottom: 1.5px solid #cbd5e1; font-weight: 700; }
        table.detail-table td { padding: 4.5px 6px; border-bottom: 1px solid #f1f5f9; color: #1e293b; }
        table.detail-table tr:nth-child(even) td { background: #fbfcfe; }
        table.detail-table td.right { text-align: right; }
        table.detail-table td.center { text-align: center; }
        table.detail-table tr.total-row td { background: #f1f5f9; font-weight: 700; font-size: 8pt; border-top: 1.5px solid #94a3b8; border-bottom: 1.5px solid #94a3b8; color: #0f172a; }

        .badge-status { display: inline-block; padding: 2px 6px; border-radius: 10px; font-size: 6.5pt; font-weight: 700; }
        .badge-cair { background: #dcfce7; color: #15803d; }
        .badge-hold { background: #fee2e2; color: #b91c1c; }

        .sign-tri-wrap { width: 100%; margin-top: 10px; border-collapse: collapse; }
        .sign-tri-cell { width: 33.3%; text-align: center; padding: 0 8px; vertical-align: top; }
        .sign-tri-card { border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 8px; background: #fff; }
        .sign-tri-title { font-size: 7.2pt; font-weight: 700; color: #475569; text-transform: uppercase; }
        .sign-tri-holder { position: relative; height: 58px; margin: 2px auto; width: 140px; }
        .sign-tri-img { max-height: 52px; max-width: 120px; object-fit: contain; }
        .sign-tri-stamp { position: absolute; left: 0px; top: 0px; width: 50px; height: 50px; opacity: 0.85; }
        .sign-tri-name { font-size: 8pt; font-weight: 700; color: #0f172a; border-top: 1px solid #0f172a; padding-top: 2px; display: inline-block; min-width: 120px; }
        .sign-tri-role { font-size: 6.8pt; color: #64748b; margin-top: 1px; }
        .text-alpha-bold { color: #b91c1c; font-weight: 700; }
    </style>
</head>
<body>

{{-- 1. KOP SURAT BAKU RESMI INDONESIA --}}
@include('pdf.hcm.partials.kop', [
    'profile' => $profile,
    'badgeText' => 'Rekap Uang Makan',
    'badgeSub' => $batch->batch_code
])

{{-- 2. SUMMARY METRICS --}}
<table class="meta-summary-bar">
    <tr>
        <td class="msb-cell">
            <div class="msb-label">Periode Rekapitulasi</div>
            <div class="msb-val">
                {{ \Carbon\Carbon::parse($batch->period_start)->isoFormat('D MMM') }} –
                {{ \Carbon\Carbon::parse($batch->period_end)->isoFormat('D MMM Y') }}
            </div>
            <div class="msb-sub">Departemen: {{ $batch->department ?? 'Semua Departemen' }}</div>
        </td>
        <td class="msb-cell">
            <div class="msb-label">Total Peserta Penerima</div>
            <div class="msb-val">{{ $batch->items->count() }} Orang</div>
            <div class="msb-sub">Tarif Penuh: Rp {{ number_format($batch->rate_monthly ?? 280000, 0, ',', '.') }}/bln</div>
        </td>
        <td class="msb-cell">
            <div class="msb-label">Total Dana Dicairkan</div>
            <div class="msb-val" style="color: #15803d;">
                Rp {{ number_format($batch->total_disbursed ?? $batch->items->where('status', 'CAIR')->sum('final_amount'), 0, ',', '.') }}
            </div>
            <div class="msb-sub">Status: Lolos Verifikasi Presensi</div>
        </td>
        <td class="msb-cell">
            <div class="msb-label">Total Dana Ditahan (Hold)</div>
            <div class="msb-val" style="color: #b91c1c;">
                Rp {{ number_format($batch->total_held ?? $batch->items->where('status', 'HOLD')->sum('final_amount'), 0, ',', '.') }}
            </div>
            <div class="msb-sub">Alpha / Pelanggaran Aturan</div>
        </td>
    </tr>
</table>

{{-- 3. TABEL DETAIL DAFTAR KARYAWAN --}}
<div class="section-heading">Daftar Rekapitulasi Hak Uang Makan Bulanan Karyawan</div>
<table class="detail-table">
    <thead>
        <tr>
            <th style="width: 24px; text-align: center;">No</th>
            <th style="width: 75px;">NIP</th>
            <th>Nama Lengkap</th>
            <th>Departemen & Jabatan</th>
            <th style="width: 50px; text-align: center;">Hadir</th>
            <th style="width: 45px; text-align: center;">Alpha</th>
            <th style="width: 45px; text-align: center;">1/2 Hari</th>
            <th style="width: 70px; text-align: right;">Hak Dasar</th>
            <th style="width: 70px; text-align: right;">Potongan</th>
            <th style="width: 80px; text-align: right;">Uang Makan Bersih</th>
            <th style="width: 60px; text-align: center;">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($batch->items as $idx => $item)
        <tr>
            <td class="center">{{ $idx + 1 }}</td>
            <td><strong>{{ $item->employee?->employee_code ?? '-' }}</strong></td>
            <td><strong>{{ $item->employee?->name ?? $item->employee_name }}</strong></td>
            <td>{{ $item->employee?->department ?? '-' }} &bull; {{ $item->employee?->position ?? '-' }}</td>
            <td class="center">{{ $item->attendance_days ?? '-' }} hr</td>
            <td class="center {{ ($item->alpha_count ?? 0) > 0 ? 'text-alpha-bold' : '' }}">
                {{ $item->alpha_count ?? 0 }}
            </td>
            <td class="center">{{ $item->half_day_count ?? 0 }}</td>
            <td class="right">Rp {{ number_format($item->base_amount ?? 280000, 0, ',', '.') }}</td>
            <td class="right" style="color: #b91c1c;">- Rp {{ number_format($item->deduction_amount ?? 0, 0, ',', '.') }}</td>
            <td class="right" style="font-weight: 700; color: #0f172a;">Rp {{ number_format($item->final_amount ?? 0, 0, ',', '.') }}</td>
            <td class="center">
                @if(($item->status ?? 'CAIR') === 'CAIR')
                    <span class="badge-status badge-cair">CAIR</span>
                @else
                    <span class="badge-status badge-hold">HOLD</span>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="11" class="center" style="padding: 12px; color: #64748b;">Tidak ada baris data rekapitulasi pada batch ini.</td>
        </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="7" style="text-align: right; padding-right: 10px;">TOTAL KESELURUHAN HAK UANG MAKAN:</td>
            <td class="right">Rp {{ number_format($batch->items->sum('base_amount'), 0, ',', '.') }}</td>
            <td class="right" style="color: #b91c1c;">- Rp {{ number_format($batch->items->sum('deduction_amount'), 0, ',', '.') }}</td>
            <td class="right" style="color: #a8001c; font-size: 8.5pt;">Rp {{ number_format($batch->items->sum('final_amount'), 0, ',', '.') }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

{{-- 4. TIGA BLOK TANDA TANGAN RESMI (PEMBUAT, KEPALA HCM, KEUANGAN) --}}
@php
    $makerName = $batch->creator?->name ?? 'Admin Presensi HCM';
    $hcmName = $batch->hcmSigner?->name ?? $profile['signer_name'];
    $hcmRole = $profile['signer_role'];
    $hcmNik  = $profile['signer_nik'];
    $showHcmSig = $profile['show_signature_on_pdf'] && !empty($profile['signature_base64']);
    $showStamp  = $profile['show_stamp_on_pdf'] && !empty($profile['stamp_base64']);
    $finName = $batch->financeSigner?->name ?? 'Admin Keuangan';
@endphp

<table class="sign-tri-wrap">
    <tr>
        {{-- KOLOM 1: PEMBUAT --}}
        <td class="sign-tri-cell">
            <div class="sign-tri-card">
                <div class="sign-tri-title">Dibuat Oleh</div>
                <div style="font-size: 6.8pt; color: #64748b;">Staf Divisi HCM</div>
                <div class="sign-tri-holder"></div>
                <div class="sign-tri-name">{{ $makerName }}</div>
                <div class="sign-tri-role">Admin Rekapitulasi Presensi & Lembur</div>
            </div>
        </td>

        {{-- KOLOM 2: DIVERIFIKASI OLEH HCM --}}
        <td class="sign-tri-cell">
            <div class="sign-tri-card">
                <div class="sign-tri-title">Diverifikasi Oleh (HCM)</div>
                <div style="font-size: 6.8pt; color: #64748b;">{{ $profile['division_name'] }}</div>
                
                <div class="sign-tri-holder">
                    @if($showStamp)
                    <img src="{{ $profile['stamp_base64'] }}" class="sign-tri-stamp" alt="Stempel">
                    @endif
                    @if($showHcmSig)
                    <img src="{{ $profile['signature_base64'] }}" class="sign-tri-img" alt="TTD HCM">
                    @endif
                </div>

                <div class="sign-tri-name">{{ $hcmName ?: '( ______________________ )' }}</div>
                <div class="sign-tri-role">{{ $hcmRole }} @if($hcmNik) &bull; NIK: {{ $hcmNik }} @endif</div>
            </div>
        </td>

        {{-- KOLOM 3: DISETUJUI & DIBAYAR KEUANGAN --}}
        <td class="sign-tri-cell">
            <div class="sign-tri-card">
                <div class="sign-tri-title">Disetujui & Dibayar (Keuangan)</div>
                <div style="font-size: 6.8pt; color: #64748b;">Finance & Treasury Operations</div>
                
                <div class="sign-tri-holder">
                    @if($batch->status === 'PAID_COMPLETED')
                    <div style="padding-top: 15px; font-weight: 700; color: #15803d; font-size: 8.5pt;">
                        &#10003; DIVERIFIKASI & CAIR
                    </div>
                    @endif
                </div>

                <div class="sign-tri-name">{{ $finName ?: '( ______________________ )' }}</div>
                <div class="sign-tri-role">Bagian Administrasi Keuangan & Kasir</div>
            </div>
        </td>
    </tr>
</table>

{{-- 5. CATATAN KAKI DOKUMEN --}}
@include('pdf.hcm.partials.footer', ['profile' => $profile])

</body>
</html>
