<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Karyawan – {{ $employee->name }}</title>
    <style>
        @page {
            margin: 12mm 15mm 15mm 15mm;
            size: a4 portrait;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            line-height: 1.4;
            color: #0f172a;
            background: #ffffff;
        }

        /* ── KOP SURAT MINIMALIST ── */
        .kop {
            display: table;
            width: 100%;
            border-bottom: 1.5px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .kop-logo {
            display: table-cell;
            width: 65px;
            vertical-align: middle;
        }
        .kop-logo img {
            max-width: 58px;
            max-height: 58px;
            object-fit: contain;
        }
        .kop-info {
            display: table-cell;
            vertical-align: middle;
            padding-left: 12px;
        }
        .kop-info .company {
            font-size: 13pt;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .kop-info .tagline {
            font-size: 7.5pt;
            color: #475569;
            margin-top: 1px;
        }
        .kop-info .contacts {
            font-size: 7pt;
            color: #64748b;
            margin-top: 1px;
        }
        .kop-badge {
            display: table-cell;
            width: 160px;
            vertical-align: middle;
            text-align: right;
        }
        .kop-badge .doc-type {
            display: inline-block;
            background: #f1f5f9;
            color: #1e293b;
            font-size: 7.5pt;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 0.5px solid #cbd5e1;
        }
        .kop-badge .doc-date {
            font-size: 7pt;
            color: #64748b;
            margin-top: 3px;
        }

        /* ── JUDUL SEKSI MINIMALIST ── */
        .section-header {
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
            margin: 11px 0 6px 0;
        }
        .section-title {
            font-size: 8.5pt;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .section-num {
            display: inline-block;
            background: #0f172a;
            color: #ffffff;
            font-size: 7pt;
            font-weight: 700;
            width: 14px;
            height: 14px;
            line-height: 14px;
            text-align: center;
            border-radius: 50%;
            margin-right: 4px;
        }

        /* ── TABEL DATA BIODATA ── */
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        table.data td {
            padding: 3.5px 4px;
            font-size: 8pt;
            vertical-align: top;
            border-bottom: 0.5px solid #f8fafc;
        }
        table.data td.label {
            width: 120px;
            color: #64748b;
            font-weight: 600;
        }
        table.data td.colon {
            width: 8px;
            color: #94a3b8;
            text-align: center;
        }
        table.data td.value {
            color: #0f172a;
        }

        /* ── BADGE STATUS CLEAN ── */
        .badge {
            display: inline-block;
            padding: 1.5px 7px;
            border-radius: 12px;
            font-size: 7pt;
            font-weight: 600;
        }
        .badge-aktif {
            background: #ecfdf5;
            color: #065f46;
            border: 0.5px solid #a7f3d0;
        }
        .badge-nonaktif {
            background: #fef2f2;
            color: #991b1b;
            border: 0.5px solid #fecaca;
        }
        .badge-probation {
            background: #fffbeb;
            color: #92400e;
            border: 0.5px solid #fde68a;
        }
        .badge-kontrak {
            background: #eff6ff;
            color: #1e40af;
            border: 0.5px solid #bfdbfe;
        }

        /* ── TABEL RINCIAN ── */
        table.detail {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
            margin-top: 3px;
            margin-bottom: 8px;
        }
        table.detail th {
            background: #f8fafc;
            color: #475569;
            padding: 4px 6px;
            text-align: left;
            font-size: 7pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #cbd5e1;
        }
        table.detail td {
            padding: 4px 6px;
            border-bottom: 0.5px solid #e2e8f0;
            color: #1e293b;
        }
        table.detail tr:nth-child(even) td {
            background: #fafafa;
        }
        table.detail td.right {
            text-align: right;
        }
        table.detail td.center {
            text-align: center;
        }

        /* ── PASFOTO 3X4 MINIMALIST ── */
        .photo-cell {
            width: 95px;
            vertical-align: top;
            text-align: center;
            padding-left: 8px;
        }
        .photo-frame {
            width: 90px;
            height: 120px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            overflow: hidden;
            background: #f8fafc;
            margin: 0 auto;
        }
        .photo-frame img {
            width: 90px;
            height: 120px;
            object-fit: cover;
        }
        .photo-placeholder {
            width: 90px;
            height: 120px;
            line-height: 120px;
            font-size: 7pt;
            color: #94a3b8;
            text-align: center;
            border: 1px dashed #cbd5e1;
            background: #f8fafc;
            border-radius: 4px;
        }
        .photo-caption {
            font-size: 6.5pt;
            font-weight: 600;
            color: #64748b;
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        /* ── TANDA TANGAN CLEAN ── */
        .ttd-section {
            margin-top: 22px;
            display: table;
            width: 100%;
        }
        .ttd-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            font-size: 8pt;
        }
        .ttd-line {
            margin: 45px auto 4px auto;
            width: 140px;
            border-top: 1px solid #475569;
        }
        .ttd-name {
            font-size: 8pt;
            font-weight: 700;
            color: #0f172a;
        }
        .ttd-role {
            font-size: 7pt;
            color: #64748b;
        }

        /* ── FOOTER HALAMAN ── */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 0.5px solid #e2e8f0;
            padding-top: 4px;
            font-size: 6.5pt;
            color: #94a3b8;
            display: table;
            width: 100%;
        }
        .footer-left {
            display: table-cell;
        }
        .footer-right {
            display: table-cell;
            text-align: right;
        }

        .text-danger-bold {
            color: #dc2626;
            font-weight: 700;
        }
    </style>
</head>
<body>

<div class="footer">
    <div class="footer-left">DOKUMEN RAHASIA &bull; BUKU PROFIL KARYAWAN &bull; {{ strtoupper($employee->name) }} ({{ $employee->employee_code }})</div>
    <div class="footer-right">Dicetak: {{ now()->isoFormat('D MMMM Y, HH:mm') }} WIB</div>
</div>

<!-- KOP SURAT RESMI MINIMALIS -->
<div class="kop">
    <div class="kop-logo">
        @php
            $hcmLogo = \App\Models\Settings\SystemSetting::get('hcm_profile', 'logo');
            $logoPath = $hcmLogo && file_exists(storage_path('app/public/' . $hcmLogo)) 
                ? storage_path('app/public/' . $hcmLogo) 
                : (file_exists(public_path('images/logo.png')) ? public_path('images/logo.png') : null);
            $logoBase64 = $logoPath ? 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($logoPath)) : null;
            $companyName = $employee->legal_entity ?: \App\Models\Settings\SystemSetting::get('hcm_profile', 'company_name', config('app.name', 'NISGroup'));
            $companyAddress = \App\Models\Settings\SystemSetting::get('hcm_profile', 'company_address', \App\Models\Settings\SystemSetting::get('company', 'address', 'Klaten, Jawa Tengah'));
            $kopLine1 = \App\Models\Settings\SystemSetting::get('hcm_profile', 'kop_header_line1', 'Divisi Human Capital & Manajemen Sumber Daya Manusia');
            $contacts = array_filter([
                \App\Models\Settings\SystemSetting::get('hcm_profile', 'company_phone'),
                \App\Models\Settings\SystemSetting::get('hcm_profile', 'company_email'),
            ]);
        @endphp
        @if($logoBase64)
            <img src="{{ $logoBase64 }}" alt="Logo">
        @else
            <div style="width:52px;height:52px;background:#0f172a;border-radius:4px;text-align:center;line-height:52px;">
                <span style="color:#fff;font-size:14pt;font-weight:700;">{{ substr($companyName, 0, 1) }}</span>
            </div>
        @endif
    </div>
    <div class="kop-info">
        <div class="company">{{ strtoupper($companyName) }}</div>
        <div class="tagline">{{ $kopLine1 }}</div>
        <div class="contacts">{{ $companyAddress }}@if(count($contacts) > 0) &bull; {{ implode(' &bull; ', $contacts) }}@endif</div>
    </div>
    <div class="kop-badge">
        <div class="doc-type">Buku Profil Karyawan</div>
        <div class="doc-date">DOSSIER/{{ $employee->employee_code }}/{{ now()->format('Y') }}</div>
    </div>
</div>

<!-- SEKSI 1: IDENTITAS DIRI & PASFOTO -->
<div class="section-header">
    <div class="section-title"><span class="section-num">1</span> Identitas Diri & Pasfoto Resmi</div>
</div>

<table style="width: 100%; border-collapse: collapse; margin-bottom: 6px;">
    <tr>
        <td style="vertical-align: top; width: 78%; padding-right: 6px;">
            <table class="data">
                <tr>
                    <td class="label">Nama Lengkap</td>
                    <td class="colon">:</td>
                    <td class="value"><strong>{{ strtoupper($employee->name) }}</strong></td>
                    <td class="label">NIK / No. KTP</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $employee->nik_ktp ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Nama Panggilan</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $employee->nickname ?? '-' }}</td>
                    <td class="label">No. Induk Karyawan</td>
                    <td class="colon">:</td>
                    <td class="value"><strong>{{ $employee->employee_code ?? '-' }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Jenis Kelamin</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $employee->gender ?? '-' }}</td>
                    <td class="label">Agama</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $employee->religion ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Tempat Lahir</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $employee->birth_place ?? '-' }}</td>
                    <td class="label">Tanggal Lahir</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $employee->birth_date ? \Carbon\Carbon::parse($employee->birth_date)->isoFormat('D MMMM Y') : '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Pendidikan Terakhir</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $employee->education ?? '-' }}</td>
                    <td class="label">Status Menikah</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $employee->marital_status ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">No. HP / WhatsApp</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $employee->phone_number ?? '-' }}</td>
                    <td class="label">Email Pribadi</td>
                    <td class="colon">:</td>
                    <td class="value">{{ $employee->email ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Alamat Lengkap</td>
                    <td class="colon">:</td>
                    <td class="value" colspan="3">{{ $employee->address ?? '-' }}</td>
                </tr>
            </table>
        </td>
        <td class="photo-cell">
            @if($employee->photo_base64)
                <div class="photo-frame">
                    <img src="{{ $employee->photo_base64 }}" alt="Pasfoto {{ $employee->name }}">
                </div>
                <div class="photo-caption">Pasfoto Resmi</div>
            @else
                <div class="photo-placeholder">
                    Pasfoto 3x4
                </div>
                <div class="photo-caption">Belum Ada Foto</div>
            @endif
        </td>
    </tr>
</table>

<!-- SEKSI 2: POSISI & STATUS KEPEGAWAIAN -->
<div class="section-header">
    <div class="section-title"><span class="section-num">2</span> Posisi & Status Kepegawaian</div>
</div>
<table class="data">
    <tr>
        <td class="label">Departemen / Divisi</td>
        <td class="colon">:</td>
        <td class="value">{{ $employee->department ?? '-' }}</td>
        <td class="label">Jabatan / Posisi</td>
        <td class="colon">:</td>
        <td class="value"><strong>{{ $employee->position ?? '-' }}</strong></td>
    </tr>
    <tr>
        <td class="label">Jenjang / Level</td>
        <td class="colon">:</td>
        <td class="value">{{ $employee->job_level ?? '-' }}</td>
        <td class="label">Status Ketenagakerjaan</td>
        <td class="colon">:</td>
        <td class="value">
            @php
                $statusMap = [
                    'Karyawan Tetap (PKWTT)' => 'badge-aktif',
                    'Karyawan Kontrak (PKWT)' => 'badge-kontrak',
                    'Probation' => 'badge-probation',
                    'Magang' => 'badge-kontrak',
                    'Borongan' => 'badge-kontrak',
                    'Harian Lepas' => 'badge-kontrak'
                ];
                $badgeClass = $statusMap[$employee->employment_status] ?? 'badge-kontrak';
            @endphp
            <span class="badge {{ $badgeClass }}">{{ $employee->employment_status ?? '-' }}</span>
        </td>
    </tr>
    <tr>
        <td class="label">Entitas Legalitas</td>
        <td class="colon">:</td>
        <td class="value">{{ $employee->legal_entity ?? '-' }}</td>
        <td class="label">Ukuran Seragam</td>
        <td class="colon">:</td>
        <td class="value">{{ $employee->shirt_size ?? '-' }}</td>
    </tr>
    <tr>
        <td class="label">Tanggal Bergabung</td>
        <td class="colon">:</td>
        <td class="value">{{ $employee->join_date ? \Carbon\Carbon::parse($employee->join_date)->isoFormat('D MMMM Y') : '-' }}</td>
        <td class="label">Masa Kerja</td>
        <td class="colon">:</td>
        <td class="value">{{ $employee->join_date ? \Carbon\Carbon::parse($employee->join_date)->diffForHumans(now(), true) : '-' }}</td>
    </tr>
</table>

<!-- SEKSI 3: REKENING & BPJS -->
<div class="section-header">
    <div class="section-title"><span class="section-num">3</span> Rekening Bank & Jaminan Sosial</div>
</div>
<table class="data">
    <tr>
        <td class="label">Bank Pembayaran</td>
        <td class="colon">:</td>
        <td class="value">{{ $employee->bank_name ?? 'Bank BRI' }}</td>
        <td class="label">Nomor Rekening</td>
        <td class="colon">:</td>
        <td class="value"><strong>{{ $employee->bank_account_no ?? '-' }}</strong></td>
    </tr>
    <tr>
        <td class="label">No. BPJS Kesehatan</td>
        <td class="colon">:</td>
        <td class="value">{{ $employee->bpjs_kesehatan_no ?? '-' }}</td>
        <td class="label">No. BPJS Ketenagakerjaan</td>
        <td class="colon">:</td>
        <td class="value">{{ $employee->bpjs_ketenagakerjaan_no ?? '-' }}</td>
    </tr>
</table>

<!-- SEKSI KHUSUS MAGANG (JIKA ADA) -->
@if($employee->intern)
<div class="section-header">
    <div class="section-title"><span class="section-num">4</span> Data Institusi Magang / PKL</div>
</div>
<table class="data">
    <tr>
        <td class="label">Asal Sekolah / Kampus</td>
        <td class="colon">:</td>
        <td class="value"><strong>{{ $employee->intern->school_name ?? '-' }}</strong></td>
        <td class="label">Kelas / Jurusan</td>
        <td class="colon">:</td>
        <td class="value">{{ $employee->intern->class ?? '-' }} {{ $employee->intern->major ? ' - ' . $employee->intern->major : '' }}</td>
    </tr>
    <tr>
        <td class="label">No. Induk Siswa (NIS)</td>
        <td class="colon">:</td>
        <td class="value">{{ $employee->intern->nis ?? '-' }}</td>
        <td class="label">Durasi Magang</td>
        <td class="colon">:</td>
        <td class="value">{{ $employee->intern->duration_text ?? '-' }}</td>
    </tr>
    <tr>
        <td class="label">Guru / Dosen Pembimbing</td>
        <td class="colon">:</td>
        <td class="value">{{ $employee->intern->mentor_teacher_name ?? '-' }}</td>
        <td class="label">Kontak Pembimbing</td>
        <td class="colon">:</td>
        <td class="value">{{ $employee->intern->mentor_teacher_phone ?? '-' }}</td>
    </tr>
</table>
@endif

<!-- SEKSI RIWAYAT KONTRAK -->
@if($employee->contracts->isNotEmpty())
<div class="section-header">
    <div class="section-title"><span class="section-num">{{ $employee->intern ? '5' : '4' }}</span> Riwayat Kontrak Kerja & PKWT</div>
</div>
<table class="detail">
    <thead>
        <tr>
            <th>No. Kontrak Resmi</th>
            <th>Mulai Efektif</th>
            <th>Berakhir</th>
            <th class="center">Durasi</th>
            <th class="center">Status</th>
            <th>Catatan</th>
        </tr>
    </thead>
    <tbody>
        @foreach($employee->contracts as $contract)
        <tr>
            <td><strong>{{ $contract->contract_number ?? '-' }}</strong></td>
            <td>{{ \Carbon\Carbon::parse($contract->start_date)->isoFormat('D MMM Y') }}</td>
            <td>{{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->isoFormat('D MMM Y') : 'Tetap' }}</td>
            <td class="center">{{ $contract->duration_text ?? ($contract->duration_months ? $contract->duration_months . ' bln' : '-') }}</td>
            <td class="center">
                <span class="badge {{ ($contract->status === 'Aktif' || $contract->review_status === 'Aktif') ? 'badge-aktif' : 'badge-nonaktif' }}">
                    {{ $contract->review_status ?? $contract->status ?? '-' }}
                </span>
            </td>
            <td>{{ $contract->notes ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<!-- SEKSI KOMPENSASI & KENAIKAN GAJI -->
@if($employee->compensation)
<div class="section-header">
    <div class="section-title"><span class="section-num">{{ $employee->intern ? ($employee->contracts->isNotEmpty() ? '6' : '5') : ($employee->contracts->isNotEmpty() ? '5' : '4') }}</span> Kompensasi Upah & Riwayat Kenaikan</div>
</div>
<table class="data">
    <tr>
        <td class="label">Gaji Pokok Awal</td>
        <td class="colon">:</td>
        <td class="value">Rp {{ number_format($employee->compensation->initial_salary ?? 0, 0, ',', '.') }}</td>
        <td class="label">Gaji Pokok Saat Ini</td>
        <td class="colon">:</td>
        <td class="value"><strong>Rp {{ number_format($employee->compensation->current_salary ?? 0, 0, ',', '.') }}</strong></td>
    </tr>
</table>
@if($employee->compensationHistories->isNotEmpty())
<table class="detail">
    <thead>
        <tr>
            <th>Tanggal Efektif</th>
            <th class="right">Gaji Sebelumnya</th>
            <th class="right">Gaji Baru</th>
            <th class="right">Kenaikan</th>
            <th>Alasan / Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @foreach($employee->compensationHistories as $hist)
        <tr>
            <td>{{ \Carbon\Carbon::parse($hist->effective_date)->isoFormat('D MMM Y') }}</td>
            <td class="right">Rp {{ number_format($hist->old_salary ?? 0, 0, ',', '.') }}</td>
            <td class="right"><strong>Rp {{ number_format($hist->new_salary ?? 0, 0, ',', '.') }}</strong></td>
            <td class="right" style="color:#059669;font-weight:700;">+Rp {{ number_format(($hist->new_salary ?? 0) - ($hist->old_salary ?? 0), 0, ',', '.') }}</td>
            <td>{{ $hist->reason ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
@endif

<!-- SEKSI REKAP PRESENSI 3 BULAN -->
@if($employee->attendances->isNotEmpty())
<div class="section-header">
    <div class="section-title"><span class="section-num">&bull;</span> Rekap Presensi Kerja 3 Bulan Terakhir</div>
</div>
@php
    $byMonth = $employee->attendances->groupBy(fn($a) => \Carbon\Carbon::parse($a->attendance_date ?? $a->date)->format('Y-m'));
@endphp
<table class="detail">
    <thead>
        <tr>
            <th>Periode Bulan</th>
            <th class="center">Hadir</th>
            <th class="center">Terlambat</th>
            <th class="center">Izin</th>
            <th class="center">Sakit</th>
            <th class="center">Cuti</th>
            <th class="center">Alpha</th>
            <th class="center">Total Catatan</th>
        </tr>
    </thead>
    <tbody>
        @foreach($byMonth->take(3) as $month => $records)
        @php $counts = $records->groupBy('status')->map->count(); @endphp
        <tr>
            <td><strong>{{ \Carbon\Carbon::parse($month . '-01')->isoFormat('MMMM Y') }}</strong></td>
            <td class="center">{{ $counts->get('H', 0) }}</td>
            <td class="center {{ ($counts->get('T', 0) > 3) ? 'text-danger-bold' : '' }}">{{ $counts->get('T', 0) }}</td>
            <td class="center">{{ $counts->get('I', 0) }}</td>
            <td class="center">{{ $counts->get('S', 0) }}</td>
            <td class="center">{{ $counts->get('C', 0) }}</td>
            <td class="center {{ ($counts->get('A', 0) > 0) ? 'text-danger-bold' : '' }}">{{ $counts->get('A', 0) }}</td>
            <td class="center"><strong>{{ $records->count() }}</strong></td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<!-- SEKSI TANDA TANGAN -->
<div class="ttd-section">
    <div class="ttd-box">
        <div>Mengetahui,</div>
        <div style="font-size:7.5pt;color:#64748b;">Manajer Departemen / Kepala Divisi</div>
        <div class="ttd-line"></div>
        <div class="ttd-name">( _________________________ )</div>
        <div class="ttd-role">Kepala Divisi {{ $employee->department ?? '' }}</div>
    </div>
    <div class="ttd-box">
        <div>{{ \App\Models\Settings\SystemSetting::get('hcm_profile', 'company_city', 'Klaten') }}, {{ now()->isoFormat('D MMMM Y') }}</div>
        <div style="font-size:7.5pt;color:#64748b;">Human Capital Management</div>
        <div class="ttd-line"></div>
        <div class="ttd-name">( _________________________ )</div>
        <div class="ttd-role">HR Manager / Admin HCM</div>
    </div>
</div>

<div style="margin-top:16px; padding-top:8px; border-top:0.5px solid #e2e8f0; font-size:6.5pt; color:#94a3b8; text-align:center;">
    {{ \App\Models\Settings\SystemSetting::get('hcm_profile', 'document_footer_text', 'Dokumen resmi diterbitkan otomatis oleh Sistem Human Capital Management.') }} &bull; {{ \App\Models\Settings\SystemSetting::get('hcm_profile', 'document_footer_disclaimer', 'Keabsahan dokumen terverifikasi.') }}
</div>

</body>
</html>
