<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Lembur – {{ $batch->batch_code }} – {{ $employee?->name ?? 'Karyawan' }}</title>
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
            font-size: 52pt;
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
            border-top: 3px solid #ea580c;
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

        /* TABEL RINCIAN LEMBUR */
        .overtime-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }
        .overtime-table th {
            background: #f8fafc;
            color: #334155;
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            padding: 7px 10px;
            border-bottom: 1.5px solid #cbd5e1;
            border-right: 1px solid #e2e8f0;
            text-align: left;
        }
        .overtime-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #f1f5f9;
            border-right: 1px solid #e2e8f0;
            font-size: 8pt;
            color: #1e293b;
            vertical-align: middle;
        }
        .overtime-table tr:last-child td {
            border-bottom: none;
        }
        .overtime-table tr td:last-child,
        .overtime-table tr th:last-child {
            border-right: none;
        }
        .amount {
            text-align: right;
            font-family: 'Inter', monospace;
            font-weight: 600;
        }
        .total-row td {
            background: #f8fafc;
            font-weight: 700;
            border-top: 1.5px solid #cbd5e1;
            color: #0f172a;
            padding: 7px 10px;
        }
        .day-badge {
            font-size: 7.2pt;
            padding: 1px 5px;
            border-radius: 3px;
            font-weight: 700;
        }
        .day-badge-weekend {
            background: #fef3c7;
            color: #b45309;
        }
        .day-badge-weekday {
            background: #e0f2fe;
            color: #0369a1;
        }

        /* TAKE-HOME CARD */
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
            color: #fb923c;
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

        /* FOOTER SIGNATURES */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            position: relative;
            z-index: 1;
        }
        .signature-table td {
            width: 33.33%;
            vertical-align: top;
            text-align: center;
            padding: 4px 8px;
        }
        .sig-role {
            font-size: 7.5pt;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 2px;
        }
        .sig-box {
            height: 52px;
            position: relative;
            margin: 2px 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .sig-img {
            max-height: 48px;
            max-width: 130px;
        }
        .stamp-overlay {
            position: absolute;
            left: 8px;
            top: -2px;
            width: 48px;
            height: 48px;
            opacity: 0.75;
        }
        .sig-name {
            font-size: 8.5pt;
            font-weight: 700;
            color: #0f172a;
            border-top: 1px solid #94a3b8;
            display: inline-block;
            min-width: 130px;
            padding-top: 2px;
        }
        .sig-date {
            font-size: 6.8pt;
            color: #64748b;
            margin-top: 1px;
        }
    </style>
</head>
<body>

    {{-- WATERMARK STATUS --}}
    <div class="watermark-stamp {{ $batch->status === 'PAID_COMPLETED' ? 'paid' : 'draft' }}">
        {{ $batch->status === 'PAID_COMPLETED' ? 'LUNAS / DIBAYARKAN' : 'DRAFT / PROSES' }}
    </div>

    {{-- KOP SURAT RESMI HCM --}}
    @include('pdf.hcm.partials.kop', [
        'profile' => $profile,
        'badgeText' => 'SLIP UPAH LEMBUR',
        'badgeSub' => $batch->batch_code
    ])

    {{-- HEADER DOKUMEN --}}
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px; border-bottom: 1.5px solid #0f172a; padding-bottom: 6px;">
        <tr>
            <td style="vertical-align: bottom;">
                <div style="font-size: 13pt; font-weight: 800; color: #0f172a; letter-spacing: -0.3px; text-transform: uppercase;">
                    SLIP UPAH LEMBUR KARYAWAN
                </div>
                <div style="font-size: 7.5pt; color: #64748b; margin-top: 1px;">
                    Rincian Jam Kerja Tambahan (Overtime) & Hak Kompensasi Upah Lembur
                </div>
            </td>
            <td style="vertical-align: bottom; text-align: right;">
                <div style="font-size: 8pt; font-weight: 700; color: #0f172a;">
                    Periode: <span style="color: #ea580c;">{{ \Carbon\Carbon::parse($batch->period_start)->translatedFormat('d M Y') }} s/d {{ \Carbon\Carbon::parse($batch->period_end)->translatedFormat('d M Y') }}</span>
                </div>
                <div style="font-size: 7.2pt; color: #64748b; margin-top: 1px;">
                    Kode Batch: <strong style="font-family: monospace; color: #0f172a;">{{ $batch->batch_code }}</strong>
                </div>
            </td>
        </tr>
    </table>

    {{-- KARTU IDENTITAS KARYAWAN --}}
    <table class="employee-info-card">
        <tr>
            <td style="width: 28%;">
                <div class="info-label">Nama Karyawan</div>
                <div class="info-value">{{ $employee?->name ?? '-' }}</div>
                <div class="info-sub">NIP: {{ $employee?->employee_code ?? '-' }} {{ $employee?->nickname ? '(' . $employee->nickname . ')' : '' }}</div>
            </td>
            <td style="width: 24%;">
                <div class="info-label">Status Ketenagakerjaan</div>
                <div class="info-value">{{ $employee?->employment_status ?? 'Karyawan' }}</div>
                <div class="info-sub">{{ $employee?->job_level ?? 'Staff' }}</div>
            </td>
            <td style="width: 24%;">
                <div class="info-label">Struktur Organisasi</div>
                <div class="info-value">{{ $employee?->department ?? '-' }}</div>
                <div class="info-sub">{{ $employee?->division ?: 'Divisi Umum' }}</div>
            </td>
            <td style="width: 24%;">
                <div class="info-label">Penyaluran Rekening / Bank</div>
                <div class="info-value" style="color: #0369a1;">
                    {{ $employee?->bank_name ?: 'Bank / Kas' }}
                </div>
                <div class="info-sub" style="font-family: monospace; font-weight: 600;">
                    {{ $employee?->bank_account_no ?: '-' }} ({{ $employee?->bank_account_name ?: $employee?->name }})
                </div>
            </td>
        </tr>
    </table>

    {{-- RINCIAN JADWAL & DURASI LEMBUR --}}
    <div style="font-size: 7.8pt; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.4px;">
        Rincian Pelaksanaan Lembur & Kompensasi
    </div>
    <table class="overtime-table">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">No</th>
                <th style="width: 15%;">Tanggal</th>
                <th style="width: 14%;">Jenis Hari</th>
                <th style="width: 12%; text-align: center;">Durasi (Jam)</th>
                <th style="width: 16%; text-align: right;">Tarif Satuan</th>
                <th style="width: 18%; text-align: right;">Subtotal Upah</th>
                <th style="width: 20%;">Uraian Pekerjaan / Task</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $totalHours = 0;
                $totalAmount = 0;
            @endphp
            @forelse($overtimes as $index => $ot)
                @php
                    $totalHours += (float) $ot->duration_hours;
                    $totalAmount += (float) $ot->total_amount;
                    $isWeekend = in_array(strtolower($ot->day_type), ['weekend', 'libur', 'akhir pekan']);
                @endphp
                <tr>
                    <td style="text-align: center; color: #64748b;">{{ $index + 1 }}</td>
                    <td style="font-weight: 600;">{{ \Carbon\Carbon::parse($ot->overtime_date)->translatedFormat('d M Y') }}</td>
                    <td>
                        <span class="day-badge {{ $isWeekend ? 'day-badge-weekend' : 'day-badge-weekday' }}">
                            {{ $isWeekend ? 'Akhir Pekan' : 'Hari Kerja' }}
                        </span>
                    </td>
                    <td style="text-align: center; font-weight: 700; font-family: monospace;">{{ number_format((float) $ot->duration_hours, 1) }} jam</td>
                    <td class="amount">Rp {{ number_format((float) $ot->hourly_rate, 0, ',', '.') }}/jam</td>
                    <td class="amount" style="color: #ea580c; font-weight: 700;">Rp {{ number_format((float) $ot->total_amount, 0, ',', '.') }}</td>
                    <td style="font-size: 7.2pt; color: #475569;">{{ $ot->task_description ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 12px; color: #94a3b8;">
                        Tidak ada catatan lembur tercatat dalam batch ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">TOTAL KUMULATIF:</td>
                <td style="text-align: center; font-family: monospace;">{{ number_format($totalHours, 1) }} jam</td>
                <td></td>
                <td class="amount" style="color: #c2410c; font-size: 9.5pt;">Rp {{ number_format($totalAmount, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    {{-- KOTAK TOTAL DITERIMA (TAKE-HOME) --}}
    <table class="take-home-card">
        <tr>
            <td style="width: 55%;">
                <div class="th-label">Total Upah Lembur Bersih Diterima</div>
                <div class="th-terbilang">Terbilang: # {{ $terbilang }} rupiah #</div>
            </td>
            <td style="width: 45%; text-align: right;">
                <div class="th-amount">Rp {{ number_format($totalAmount, 0, ',', '.') }}</div>
                <div style="font-size: 7.2pt; color: #94a3b8; margin-top: 2px;">
                    Metode: {{ $batch->payment_method ?: ('Transfer Rekening ' . ($employee?->bank_name ?: 'Bank')) }}
                </div>
            </td>
        </tr>
    </table>

    {{-- VERIFIKASI QR CODE & CATATAN --}}
    <table class="verification-bar">
        <tr>
            <td style="width: 70px; text-align: center;">
                @if(!empty($qrCodeBase64))
                    <img src="data:image/svg+xml;base64,{{ $qrCodeBase64 }}" style="width: 56px; height: 56px;" alt="QR Token">
                @else
                    <div style="width: 56px; height: 56px; border: 1px dashed #cbd5e1; line-height: 56px; font-size: 7pt; color: #94a3b8;">QR Code</div>
                @endif
            </td>
            <td style="padding-left: 8px;">
                <div style="font-size: 7.2pt; font-weight: 700; color: #0f172a; text-transform: uppercase;">
                    Verifikasi Dokumen Upah Lembur Digital
                </div>
                <div style="font-size: 6.8pt; color: #475569; line-height: 1.35; margin-top: 1px;">
                    Slip upah lembur resmi ini diterbitkan otomatis oleh Sistem {{ $profile['division_name'] ?? 'HRIS / HCM' }} {{ $profile['company_name'] ?? '' }} dan telah disinkronkan dengan rekap presensi pabrik.
                    Pindai QR code untuk memverifikasi keabsahan data pencairan ini.
                </div>
                <div style="font-size: 6.5pt; font-family: monospace; color: #64748b; margin-top: 2px;">
                    Kode Batch: {{ $batch->batch_code }} | NIK: {{ $employee?->employee_code }}
                </div>
            </td>
            <td style="width: 25%; text-align: right; border-left: 1px dashed #cbd5e1; padding-left: 8px;">
                <div style="font-size: 7pt; color: #64748b;">Tanggal Cetak:</div>
                <div style="font-size: 7.5pt; font-weight: 700; color: #0f172a;">{{ now()->translatedFormat('d F Y H:i') }} WIB</div>
            </td>
        </tr>
    </table>

    {{-- TANDA TANGAN DOUBLE SIGN-OFF RESMI --}}
    <table class="signature-table">
        <tr>
            {{-- Disahkan HCM --}}
            <td>
                <div class="sig-role">Disetujui & Diverifikasi HCM</div>
                <div class="sig-box">
                    @if(!empty($profile['show_stamp_on_pdf']) && !empty($profile['stamp_path']) && file_exists($profile['stamp_path']))
                        <img src="{{ $profile['stamp_path'] }}" class="stamp-overlay" alt="Stempel">
                    @endif
                    @if(!empty($profile['show_signature_on_pdf']) && !empty($profile['signature_path']) && file_exists($profile['signature_path']))
                        <img src="{{ $profile['signature_path'] }}" class="sig-img" alt="TTD HCM">
                    @endif
                </div>
                <div class="sig-name">{{ $batch->hcmSigner?->name ?? ($profile['signer_name'] ?? 'Head of HCM') }}</div>
                <div class="sig-date">{{ $batch->hcm_signed_at ? \Carbon\Carbon::parse($batch->hcm_signed_at)->translatedFormat('d M Y H:i') : 'Divisi HCM' }}</div>
            </td>

            {{-- Dibayarkan Keuangan --}}
            <td>
                <div class="sig-role">Dibayarkan & Disetor Keuangan</div>
                <div class="sig-box">
                    @if($batch->status === 'PAID_COMPLETED')
                        <div style="display: inline-block; border: 1.5px solid #059669; color: #059669; padding: 3px 8px; border-radius: 4px; font-weight: 800; font-size: 7.5pt; transform: rotate(-5deg); margin-top: 10px;">
                            PAID / LUNAS TRANSFER
                        </div>
                    @else
                        <div style="font-size: 7pt; color: #94a3b8; font-style: italic; line-height: 48px;">
                            Menunggu Realisasi Keuangan
                        </div>
                    @endif
                </div>
                <div class="sig-name">{{ $batch->financeSigner?->name ?? ('Tim Keuangan ' . ($profile['company_name'] ?? '')) }}</div>
                <div class="sig-date">{{ $batch->finance_signed_at ? \Carbon\Carbon::parse($batch->finance_signed_at)->translatedFormat('d M Y H:i') : 'Divisi Keuangan' }}</div>
            </td>

            {{-- Penerima Karyawan --}}
            <td>
                <div class="sig-role">Diterima oleh Karyawan</div>
                <div class="sig-box">
                    <div style="font-size: 7pt; color: #94a3b8; font-style: italic; line-height: 48px;">
                        Setoran Rekening Bank
                    </div>
                </div>
                <div class="sig-name">{{ $employee?->name ?? 'Karyawan Bersangkutan' }}</div>
                <div class="sig-date">Karyawan / Pelaksana Lembur</div>
            </td>
        </tr>
    </table>

    {{-- CATATAN KAKI DOKUMEN --}}
    <div style="position: absolute; bottom: 0; left: 0; width: 100%; border-top: 1px solid #e2e8f0; padding-top: 4px; font-size: 6.8pt; color: #94a3b8; text-align: center;">
        {{ $profile['document_footer_text'] ?? 'Dokumen resmi diterbitkan otomatis oleh Sistem Kepegawaian terintegrasi.' }} — {{ $profile['document_footer_disclaimer'] ?? 'Keabsahan dokumen dapat diverifikasi langsung melalui portal HCM atau QR code tertera.' }}
    </div>

</body>
</html>
