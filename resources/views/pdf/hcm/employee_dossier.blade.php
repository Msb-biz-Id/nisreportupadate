@php
    $isIntern  = $employee->is_intern;
    $docTitle  = $isIntern ? 'Profil Peserta Magang' : 'Profil Karyawan';
    $docSub    = $isIntern ? 'Biodata & Riwayat PKL' : 'Biodata & Riwayat Kepegawaian';
    $docPrefix = $isIntern ? 'PKL' : 'EMP';

    // ----- Palette (single source of truth for the whole document) -----
    $c = [
        'ink'         => '#0f172a',
        'ink2'        => '#1e293b',
        'muted'       => '#64748b',
        'soft'        => '#94a3b8',
        'line'        => '#e2e8f0',
        'line2'       => '#f1f5f9',
        'surface'     => '#f8fafc',
        'accent'      => '#2563eb',
        'accentDeep'  => '#1d4ed8',
        'accentSoft'  => '#eff6ff',
        'accentLine'  => '#dbeafe',
        'cyan'        => '#0ea5e9',
        'success'     => '#15803d',
        'successSoft' => '#dcfce7',
        'danger'      => '#b91c1c',
        'dangerSoft'  => '#fee2e2',
        'warn'        => '#b45309',
        'warnSoft'    => '#fef3c7',
    ];

    // ----- Company profile: fully dynamic from System Settings -----
    $s = fn($key, $default = null) => \App\Models\Settings\SystemSetting::get('hcm_profile', $key, $default);

    $companyName  = $employee->legal_entity ?: $s('company_name', config('app.name', 'NISGroup'));
    $companyTag   = $s('company_tagline', 'Human Capital Management & Operations');
    $companyAddr  = $s('company_address', 'Klaten, Jawa Tengah');
    $companyCity  = $s('company_city', 'Klaten');
    $companyPhone = $s('company_phone');
    $companyEmail = $s('company_email');
    $companyWeb   = $s('company_website');
    $companySoc   = $s('company_socials');
    $footerText   = $s('document_footer_text', 'Dokumen resmi diterbitkan oleh Sistem HCM.');
    $footerDisc   = $s('document_footer_disclaimer');
    $contacts     = array_values(array_filter([$companyPhone, $companyEmail, $companyWeb, $companySoc]));

    // ----- Logo -----
    $hcmLogo  = $s('logo');
    $logoPath = $hcmLogo && file_exists(storage_path('app/public/' . $hcmLogo))
        ? storage_path('app/public/' . $hcmLogo)
        : (file_exists(public_path('images/logo.png')) ? public_path('images/logo.png') : null);
    $logoBase64 = $logoPath
        ? 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($logoPath))
        : null;

    // ----- Derived values -----
    $joinDate     = $employee->join_date ? \Carbon\Carbon::parse($employee->join_date) : null;
    $masaKerja    = $joinDate ? $joinDate->diffForHumans(now(), true) : '-';
    $tanggalMasuk = $joinDate ? $joinDate->isoFormat('D MMMM Y') : '-';
    $birthObj     = $employee->birth_date ? \Carbon\Carbon::parse($employee->birth_date) : null;
    $birthDate    = $birthObj ? $birthObj->isoFormat('D MMM Y') : '-';
    $usia         = $birthObj ? $birthObj->age . ' tahun' : '-';
    $isActive     = $employee->is_active ?? true;
    $docCode      = $docPrefix . '/' . $employee->employee_code . '/' . now()->format('Y');
    $verifyCode   = strtoupper(substr(hash('sha256', $employee->employee_code . '|' . $employee->id), 0, 12));
    $sNum         = 1;

    // ----- Formatters -----
    $rp     = fn($n) => 'Rp ' . number_format((float) ($n ?? 0), 0, ',', '.');
    $fdate  = fn($d, $f = 'D MMM Y') => $d ? \Carbon\Carbon::parse($d)->isoFormat($f) : '-';
    $badge  = function ($status) {
        $st = strtolower((string) $status);
        if ($st === '') return 'tag-slate';
        if (str_contains($st, 'aktif') || str_contains($st, 'active')) return 'tag-green';
        if (str_contains($st, 'selesai') || str_contains($st, 'berakhir') || str_contains($st, 'expired') || str_contains($st, 'non')) return 'tag-red';
        if (str_contains($st, 'perpanjang') || str_contains($st, 'review') || str_contains($st, 'pending')) return 'tag-amber';
        return 'tag-slate';
    };

    // ----- Modern minimalist SVG icon set (base64 data-URIs, native DomPDF vector render) -----
    $svgIcon = function ($type, $color = '#64748b') {
        $wrap = fn($body) => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="' . $color . '" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">' . $body . '</svg>';
        $defs = [
            'user'       => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            'users'      => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            'id'         => '<rect x="2" y="5" width="20" height="14" rx="2"/><circle cx="8" cy="10.5" r="2"/><path d="M5.5 16a3 3 0 0 1 5 0"/><path d="M14 9h4"/><path d="M14 13h4"/>',
            'calendar'   => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
            'cake'       => '<path d="M20 21v-8a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8"/><path d="M4 16s.5-1 2-1 2.5 2 4 2 2.5-2 4-2 2.5 2 4 2 2-1 2-1"/><path d="M2 21h20"/><path d="M7 8v3M12 8v3M17 8v3"/><path d="M7 4h.01M12 4h.01M17 4h.01"/>',
            'phone'      => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
            'map'        => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
            'mail'       => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
            'heart'      => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>',
            'book'       => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
            'graduation' => '<path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>',
            'school'     => '<path d="m4 6 8-4 8 4"/><path d="m18 10 4 2v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6l4-2"/><path d="M14 22v-4a2 2 0 0 0-2-2a2 2 0 0 0-2 2v4"/>',
            'tag'        => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><circle cx="7" cy="7" r="1.4" fill="' . $color . '" stroke="none"/>',
            'briefcase'  => '<rect width="20" height="14" x="2" y="7" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
            'building'   => '<rect width="16" height="20" x="4" y="2" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01M16 6h.01M8 10h.01M16 10h.01M8 14h.01M16 14h.01"/>',
            'layers'     => '<path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/>',
            'file'       => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6"/>',
            'check'      => '<path d="M20 6 9 17l-5-5"/>',
            'bank'       => '<path d="M3 21h18"/><path d="M3 10h18"/><path d="m5 6 7-3 7 3"/><path d="M6 10v11M10 10v11M14 10v11M18 10v11"/>',
            'wallet'     => '<path d="M19 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5"/><path d="M16 12h.01"/>',
            'card'       => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
            'shield'     => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
            'pulse'      => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
            'trending'   => '<path d="M22 7 13.5 15.5 8.5 10.5 2 17"/><path d="M16 7h6v6"/>',
            'banknote'   => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/>',
            'globe'      => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
            'award'      => '<circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>',
            'clock'      => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
            'hash'       => '<path d="M4 9h16M4 15h16M10 3 8 21M16 3l-2 18"/>',
            'info'       => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
        ];
        return 'data:image/svg+xml;base64,' . base64_encode($wrap($defs[$type] ?? $defs['user']));
    };

    // ----- Reusable data-row renderer (keeps markup DRY & dynamic) -----
    $row = function ($icon, $label, $value, array $o = []) use ($svgIcon, $c) {
        $raw   = $o['raw'] ?? false;
        $val   = $raw ? $value : e(($value === null || $value === '') ? '-' : $value);
        $cls   = $o['class'] ?? '';
        $style = $o['style'] ?? '';
        $ic    = $o['iconColor'] ?? $c['accent'];
        return '<tr class="data-row">'
            . '<td class="cell-icon"><span class="icon-chip"><img src="' . $svgIcon($icon, $ic) . '" alt=""></span></td>'
            . '<td class="cell-label">' . e($label) . '</td>'
            . '<td class="cell-val ' . $cls . '" style="' . $style . '">' . $val . '</td>'
            . '</tr>';
    };

    // ----- Section header renderer (auto-numbered) -----
    $secHead = function ($title) use (&$sNum, $svgIcon, $c) {
        $num = str_pad($sNum++, 2, '0', STR_PAD_LEFT);
        return '<table class="sec-head"><tr>'
            . '<td style="width:22px;"><span class="sec-num">' . $num . '</span></td>'
            . '<td class="sec-title">' . e($title) . '</td>'
            . '<td class="sec-rule">&nbsp;</td>'
            . '</tr></table>';
    };

    // ----- Attendance aggregation -----
    $catMap = [
        'hadir' => ['PRESENT', 'Hadir', 'HADIR'],
        'telat' => ['LATE', 'Terlambat', 'TERLAMBAT'],
        'cepat' => ['Pulang Cepat', 'EARLY_LEAVE', 'PULANG_CEPAT'],
        'izin'  => ['PERMIT', 'Izin', 'IZIN', 'Dinas Luar', 'DINAS_LUAR'],
        'sakit' => ['SICK', 'Sakit', 'SAKIT'],
        'cuti'  => ['LEAVE', 'Cuti', 'CUTI'],
        'alpha' => ['ALPHA', 'Alpha/Mangkir', 'Alpha', 'MANGKIR'],
        'libur' => ['Libur/Cuti Bersama', 'Libur', 'HOLIDAY', 'LIBUR'],
    ];
    $byMonth = $employee->attendances->isNotEmpty()
        ? $employee->attendances->groupBy(fn($a) => \Carbon\Carbon::parse($a->attendance_date)->format('Y-m'))
        : collect();
    $attTotals = ['hadir' => 0, 'telat' => 0, 'cepat' => 0, 'izin' => 0, 'sakit' => 0, 'cuti' => 0, 'alpha' => 0, 'libur' => 0, 'total' => 0];
    $attRows = collect();
    foreach ($byMonth->take(6) as $month => $records) {
        $rowData = [];
        foreach ($catMap as $key => $values) {
            $rowData[$key] = $records->whereIn('attendance_category', $values)->count();
            $attTotals[$key] += $rowData[$key];
        }
        $rowData['total'] = $records->count();
        $attTotals['total'] += $rowData['total'];
        $attRows->put($month, $rowData);
    }
    $attPresent = $attTotals['hadir'] + $attTotals['telat'];
    $attRate    = $attTotals['total'] > 0 ? round($attPresent / $attTotals['total'] * 100) : 0;
    $attMonths  = $attRows->count();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>{{ $docTitle }} - {{ $employee->name }}</title>
<style>
  @font-face { font-family: 'Inter'; font-weight: 400; src: url('{{ public_path("fonts/Inter-Regular.ttf") }}') format('truetype'); }
  @font-face { font-family: 'Inter'; font-weight: 600; src: url('{{ public_path("fonts/Inter-Bold.ttf") }}') format('truetype'); }
  @font-face { font-family: 'Inter'; font-weight: 700; src: url('{{ public_path("fonts/Inter-Bold.ttf") }}') format('truetype'); }

  @page {
    size: A4 portrait;
    margin: 24px 0 34px 0;
  }
  @page :first {
    margin-top: 0;
  }
  body, span, p, a, table, thead, tbody, tfoot, tr, th, td, img { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: 'Inter', 'Helvetica Neue', Helvetica, Arial, sans-serif;
    background: #ffffff;
    color: {{ $c['ink'] }};
    font-size: 10px;
    line-height: 1.35;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  table { border-collapse: collapse; }

  /* ================= HEADER ================= */
  .header-strip { height: 5px; background-color: {{ $c['accent'] }}; }
  .page-header { background-color: {{ $c['ink'] }}; border-bottom: 3px solid {{ $c['accent'] }}; }
  .ph-table { width: 100%; border-collapse: collapse; }
  .ph-table > tbody > tr > td { padding: 15px 34px; vertical-align: middle; }
  .brand-logo-box {
    width: 42px; height: 42px; background-color: #ffffff; color: {{ $c['accent'] }};
    font-size: 18px; font-weight: 700; text-align: center; line-height: 42px;
    border-radius: 9px; overflow: hidden;
  }
  .brand-logo-box img { width: 100%; height: 100%; object-fit: contain; padding: 5px; }
  .brand-title { font-size: 14.5px; font-weight: 700; color: #ffffff; letter-spacing: .5px; text-transform: uppercase; }
  .brand-tagline { font-size: 8.5px; color: {{ $c['accentSoft'] }}; margin-top: 2px; }
  .brand-contact { font-size: 8px; color: {{ $c['soft'] }}; margin-top: 6px; }
  .doc-kicker { font-size: 8px; color: #7dd3fc; letter-spacing: 2px; text-transform: uppercase; text-align: right; }
  .doc-title { font-size: 13px; font-weight: 700; color: #ffffff; letter-spacing: 1.5px; text-transform: uppercase; text-align: right; margin-top: 1px; }
  .doc-chip {
    display: inline-block; background-color: #1e293b; border: 1px solid #38bdf8;
    color: #7dd3fc; font-family: 'Courier New', monospace; font-size: 8px; font-weight: 700;
    padding: 3px 10px; border-radius: 12px; margin-top: 6px; letter-spacing: .5px;
  }

  /* ================= LAYOUT ================= */
  .container { padding: 20px 34px 8px 34px; }

  /* ================= PROFILE HERO ================= */
  .profile { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
  .profile > tbody > tr > td { vertical-align: top; }
  .photo-frame {
    width: 122px; height: 156px; background-color: {{ $c['surface'] }}; border: 1px solid {{ $c['line'] }};
    border-radius: 10px; overflow: hidden; text-align: center;
  }
  .photo-frame img.user-photo { width: 120px; height: 154px; object-fit: cover; border-radius: 9px; display: block; }
  .photo-empty { color: {{ $c['soft'] }}; font-size: 8.5px; font-weight: 700; letter-spacing: 1.5px; }
  .emp-name { font-size: 23px; font-weight: 700; color: {{ $c['ink'] }}; line-height: 1.1; letter-spacing: -.4px; }
  .emp-nick { font-size: 10px; color: {{ $c['muted'] }}; margin-top: 2px; }
  .emp-role { font-size: 11px; font-weight: 600; color: {{ $c['accent'] }}; margin-top: 4px; }

  /* BADGES */
  .tags { margin: 9px 0 11px 0; }
  .tag {
    display: inline-block; font-size: 8.5px; font-weight: 600; padding: 3px 9px;
    border-radius: 12px; margin-right: 5px; letter-spacing: .3px;
  }
  .tag-blue  { background-color: {{ $c['accentSoft'] }}; color: {{ $c['accentDeep'] }}; }
  .tag-slate { background-color: {{ $c['line2'] }}; color: #334155; }
  .tag-green { background-color: {{ $c['successSoft'] }}; color: {{ $c['success'] }}; }
  .tag-red   { background-color: {{ $c['dangerSoft'] }}; color: {{ $c['danger'] }}; }
  .tag-amber { background-color: {{ $c['warnSoft'] }}; color: {{ $c['warn'] }}; }

  /* QUICK INFO PANEL */
  .quick-panel { background-color: {{ $c['surface'] }}; border: 1px solid {{ $c['line'] }}; border-radius: 10px; padding: 9px 12px; }
  .quick { width: 100%; border-collapse: collapse; }
  .quick td { padding: 3.5px 0; vertical-align: middle; font-size: 9.5px; }
  .q-icon { display: inline-block; width: 20px; height: 20px; background: #ffffff; border: 1px solid {{ $c['line'] }}; border-radius: 6px; margin-right: 6px; position: relative; }
  .q-icon img { width: 12px; height: 12px; position: absolute; top: 4px; left: 4px; }
  .q-label { color: {{ $c['muted'] }}; }
  .q-value { font-weight: 600; color: {{ $c['ink'] }}; }

  /* ================= SECTIONS ================= */
  .section { margin-bottom: 15px; page-break-inside: avoid; }
  .sec-head { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
  .sec-num {
    display: inline-block; width: 20px; height: 20px; background-color: {{ $c['accent'] }}; color: #ffffff;
    font-size: 9px; font-weight: 700; text-align: center; line-height: 20px; border-radius: 6px;
  }
  .sec-title { font-size: 10.5px; font-weight: 700; color: {{ $c['ink'] }}; letter-spacing: 1.2px; text-transform: uppercase; padding-left: 9px; white-space: nowrap; }
  .sec-rule { border-bottom: 1px solid {{ $c['line'] }}; width: 100%; }

  /* DATA GRID (2 columns) */
  .grid { width: 100%; border-collapse: collapse; }
  .grid > tbody > tr > td.col { width: 50%; vertical-align: top; padding-right: 15px; }
  .grid > tbody > tr > td.col:last-child { padding-right: 0; padding-left: 15px; }

  .rows { width: 100%; border-collapse: collapse; }
  .data-row td { padding: 4.5px 0; vertical-align: middle; font-size: 9.5px; border-bottom: 1px solid {{ $c['line2'] }}; }
  .data-row:last-child td { border-bottom: none; }
  .cell-icon { width: 27px; }
  .icon-chip {
    display: inline-block; width: 20px; height: 20px; background-color: {{ $c['accentSoft'] }};
    border: 1px solid {{ $c['accentLine'] }}; border-radius: 6px; position: relative;
  }
  .icon-chip img { width: 12px; height: 12px; position: absolute; top: 4px; left: 4px; }
  .cell-label { width: 96px; color: {{ $c['muted'] }}; font-weight: 400; }
  .cell-val { color: {{ $c['ink'] }}; font-weight: 600; }
  .mono { font-family: 'Courier New', monospace; font-size: 9px; letter-spacing: -.2px; }
  .soft { color: {{ $c['muted'] }}; font-weight: 400; }

  /* ================= STAT CARDS ================= */
  .stat-table { width: 100%; border-collapse: separate; border-spacing: 10px 0; margin-top: 2px; }
  .stat-card { background-color: #ffffff; border: 1px solid {{ $c['line'] }}; border-top: 3px solid {{ $c['accent'] }}; border-radius: 10px; padding: 10px 12px; width: 50%; }
  .stat-card.ok { border-top-color: {{ $c['success'] }}; }
  .stat-card.warn { border-top-color: {{ $c['danger'] }}; }
  .stat-card.amber { border-top-color: {{ $c['warn'] }}; }
  .stat-card .k { font-size: 7.5px; color: {{ $c['muted'] }}; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
  .stat-card .v { font-size: 15px; font-weight: 700; color: {{ $c['ink'] }}; letter-spacing: -.3px; }
  .stat-card .v small { font-size: 10px; font-weight: 400; color: {{ $c['muted'] }}; margin-right: 2px; }

  /* ================= TABLES ================= */
  .tbl-wrap { border: 1px solid {{ $c['line'] }}; border-radius: 10px; overflow: hidden; margin-top: 4px; }
  .tbl { width: 100%; border-collapse: collapse; font-size: 9px; }
  .tbl thead { display: table-header-group; }
  .tbl thead th {
    background-color: {{ $c['surface'] }}; color: #475569; font-size: 7.5px; font-weight: 700;
    letter-spacing: .6px; text-transform: uppercase; padding: 7px 9px; text-align: left;
    border-bottom: 1px solid {{ $c['line'] }};
  }
  .tbl tbody td { padding: 6.5px 9px; color: {{ $c['ink2'] }}; border-bottom: 1px solid {{ $c['line2'] }}; }
  .tbl tbody tr:last-child td { border-bottom: none; }
  .tbl tfoot td { background-color: {{ $c['surface'] }}; font-weight: 700; color: {{ $c['ink'] }}; padding: 7px 9px; border-top: 1px solid {{ $c['line'] }}; }
  .tbl .c { text-align: center; }
  .tbl .r { text-align: right; }

  /* NOTES */
  .notes-box { background-color: {{ $c['surface'] }}; border: 1px solid {{ $c['line'] }}; border-left: 4px solid {{ $c['accent'] }}; border-radius: 8px; padding: 9px 12px; font-size: 9.5px; color: {{ $c['ink2'] }}; }

  /* ================= SIGNATURE ================= */
  .sign-table { width: 100%; border-collapse: collapse; margin-top: 26px; page-break-inside: avoid; }
  .sign-table td { width: 50%; text-align: center; vertical-align: top; padding: 0 18px; }
  .sign-title { font-size: 9px; color: #475569; }
  .sign-space { height: 52px; }
  .sign-name { width: 74%; margin: 0 auto; border-top: 1.5px solid {{ $c['ink'] }}; padding-top: 5px; font-size: 9.5px; font-weight: 700; color: {{ $c['ink'] }}; }
  .sign-role { font-size: 8px; color: {{ $c['soft'] }}; margin-top: 2px; }

  /* ================= RUNNING FOOTER ================= */
  .footer { position: fixed; bottom: -34px; left: 0; right: 0; height: 30px; background-color: {{ $c['ink'] }}; border-top: 2px solid {{ $c['accent'] }}; }
  .footer-table { width: 100%; border-collapse: collapse; }
  .footer-table td { padding: 8px 34px; font-size: 7.5px; color: {{ $c['soft'] }}; vertical-align: middle; }
  .fuel { color: #7dd3fc; }
  .page-no::before { content: "HAL " counter(page); }
</style>
</head>
<body>

  {{-- ===== RUNNING FOOTER ===== --}}
  <div class="footer">
    <table class="footer-table">
      <tr>
        <td style="text-align:left;">
          {{ $footerText }} &bull; {{ strtoupper($employee->name) }} &bull; {{ $employee->employee_code }}
        </td>
        <td style="text-align:center;">
          <span class="fuel">Ref: {{ $verifyCode }}</span>
        </td>
        <td style="text-align:right;">
          <span class="page-no"></span> &bull; {{ now()->isoFormat('D MMM Y, HH:mm') }} WIB
        </td>
      </tr>
    </table>
  </div>

  <div class="header-strip"></div>

  {{-- ===== HEADER ===== --}}
  <div class="page-header">
    <table class="ph-table">
      <tr>
        <td style="width: 58%;">
          <table style="width:100%; border-collapse:collapse;">
            <tr>
              <td style="width: 44px; vertical-align: middle;">
                <div class="brand-logo-box">
                  @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Logo">
                  @else
                    {{ strtoupper(substr($companyName, 0, 1)) }}
                  @endif
                </div>
              </td>
              <td style="vertical-align: middle; padding-left: 10px;">
                <div class="brand-title">{{ strtoupper($companyName) }}</div>
                <div class="brand-tagline">{{ $companyTag }}</div>
              </td>
            </tr>
          </table>
          @if(count($contacts))
            <div class="brand-contact">{{ $companyAddr }}@if(count($contacts)) &bull; {{ implode(' • ', $contacts) }}@endif</div>
          @endif
        </td>
        <td style="width: 42%; text-align: right;">
          <div class="doc-kicker">{{ $docSub }}</div>
          <div class="doc-title">{{ $docTitle }}</div>
          <span class="doc-chip">{{ $docCode }}</span>
        </td>
      </tr>
    </table>
  </div>

  {{-- ===== MAIN CONTENT ===== --}}
  <div class="container">

    {{-- PROFILE HERO --}}
    <table class="profile">
      <tr>
        <td style="width: 138px;">
          <div class="photo-frame">
            @if($employee->photo_base64)
              <img src="{{ $employee->photo_base64 }}" alt="{{ $employee->name }}" class="user-photo">
            @else
              <table style="width:100%; height:100%;">
                <tr>
                  <td style="text-align:center; vertical-align:middle;">
                    <img src="{{ $svgIcon('user', $c['soft']) }}" style="width:34px; height:34px; opacity:.45;"><br>
                    <span class="photo-empty">PASFOTO</span>
                  </td>
                </tr>
              </table>
            @endif
          </div>
        </td>
        <td style="padding-left: 20px;">
          <div class="emp-name">{{ $employee->name }}</div>
          @if($employee->nickname)
            <div class="emp-nick">Nama panggilan: {{ $employee->nickname }}</div>
          @endif
          <div class="emp-role">
            {{ $employee->position ?? ($isIntern ? 'Peserta Magang' : 'Karyawan') }}
            @if($employee->department) &mdash; {{ $employee->department }}@endif
          </div>

          <div class="tags">
            @if($employee->employment_status)
              <span class="tag tag-blue">{{ $employee->employment_status }}</span>
            @endif
            @if($employee->job_level)
              <span class="tag tag-slate">{{ $employee->job_level }}</span>
            @endif
            <span class="tag tag-slate">{{ $employee->employee_code }}</span>
            <span class="tag {{ $isActive ? 'tag-green' : 'tag-red' }}">{{ $isActive ? 'AKTIF' : 'NON-AKTIF' }}</span>
          </div>

          <div class="quick-panel">
            <table class="quick">
              <tr>
                <td style="width:50%;">
                  <span class="q-icon"><img src="{{ $svgIcon('calendar', $c['accent']) }}"></span>
                  <span class="q-label">Masuk:</span> <span class="q-value">{{ $tanggalMasuk }}</span>
                </td>
                <td style="width:50%;">
                  <span class="q-icon"><img src="{{ $svgIcon('clock', $c['accent']) }}"></span>
                  <span class="q-label">Masa Kerja:</span> <span class="q-value">{{ $masaKerja }}</span>
                </td>
              </tr>
              <tr>
                <td>
                  <span class="q-icon"><img src="{{ $svgIcon('phone', $c['accent']) }}"></span>
                  <span class="q-label">Kontak:</span> <span class="q-value">{{ $employee->phone_number ?: '-' }}</span>
                </td>
                <td>
                  <span class="q-icon"><img src="{{ $svgIcon('map', $c['accent']) }}"></span>
                  <span class="q-label">Domisili:</span> <span class="q-value">{{ \Illuminate\Support\Str::limit($employee->address ?: ($employee->birth_place ?: '-'), 34) }}</span>
                </td>
              </tr>
            </table>
          </div>
        </td>
      </tr>
    </table>

    {{-- ============ 01 DATA PRIBADI ============ --}}
    <div class="section">
      {!! $secHead('Data Pribadi') !!}
      <table class="grid">
        <tr>
          <td class="col">
            <table class="rows">
              {!! $row('users', 'Jenis Kelamin', $employee->gender) !!}
              {!! $row('cake', 'Tempat, Tgl Lahir', ($employee->birth_place ?: '-') . ', ' . $birthDate) !!}
              {!! $row('calendar', 'Usia', $usia) !!}
              {!! $row('heart', 'Status Pernikahan', $employee->marital_status) !!}
              {!! $row('book', 'Agama', $employee->religion) !!}
            </table>
          </td>
          <td class="col">
            <table class="rows">
              {!! $row('id', 'No. KTP / NIK', $employee->nik_ktp, ['class' => 'mono']) !!}
              {!! $row('phone', 'No. Telepon', $employee->phone_number, ['class' => 'mono']) !!}
              {!! $row('mail', 'Email', $employee->email) !!}
              {!! $row('graduation', 'Pendidikan', $employee->education) !!}
              {!! $row('tag', 'Ukuran Seragam', $employee->shirt_size) !!}
            </table>
          </td>
        </tr>
      </table>
      <table class="rows" style="margin-top:2px;">
        {!! $row('map', 'Alamat Domisili', $employee->address, ['style' => 'font-weight:400;']) !!}
      </table>
    </div>

    {{-- ============ 02 STATUS KEPEGAWAIAN ============ --}}
    <div class="section">
      {!! $secHead('Status Kepegawaian') !!}
      <table class="grid">
        <tr>
          <td class="col">
            <table class="rows">
              {!! $row('building', 'Departemen', $employee->department) !!}
              {!! $row('briefcase', 'Jabatan', $employee->position) !!}
              {!! $row('layers', 'Jenjang / Level', $employee->job_level) !!}
              {!! $row('building', 'Entitas Legal', $employee->legal_entity ?: $companyName) !!}
            </table>
          </td>
          <td class="col">
            <table class="rows">
              {!! $row('file', 'Ikatan Kerja', $employee->employment_status ?: ($isIntern ? 'Magang' : 'PKWT'), ['class' => 'cell-strong']) !!}
              {!! $row('calendar', 'Tanggal Masuk', $tanggalMasuk) !!}
              {!! $row('clock', 'Masa Kerja', $masaKerja) !!}
              {!! $row('info', 'Kategori', ucfirst(strtolower($employee->employee_category ?? 'REGULAR')), ['class' => 'cell-strong']) !!}
              {!! $row('check', 'Status Keaktifan', '<span class="tag ' . ($isActive ? 'tag-green' : 'tag-red') . '">' . ($isActive ? 'AKTIF' : 'NON-AKTIF') . '</span>', ['raw' => true]) !!}
            </table>
          </td>
        </tr>
      </table>
    </div>

    {{-- ============ REKENING & JAMINAN SOSIAL ============ --}}
    <div class="section">
      {!! $secHead('Rekening & Jaminan Sosial') !!}
      <table class="grid">
        <tr>
          <td class="col">
            <table class="rows">
              {!! $row('bank', 'Bank', $employee->bank_name) !!}
              {!! $row('card', 'No. Rekening', $employee->bank_account_no, ['class' => 'mono']) !!}
            </table>
          </td>
          <td class="col">
            <table class="rows">
              {!! $row('pulse', 'BPJS Kesehatan', $employee->bpjs_kesehatan_no, ['class' => 'mono']) !!}
              {!! $row('shield', 'BPJS Ketenagakerjaan', $employee->bpjs_ketenagakerjaan_no, ['class' => 'mono']) !!}
            </table>
          </td>
        </tr>
      </table>
    </div>

    {{-- ============ DATA MAGANG (KONDISIONAL) ============ --}}
    @if($isIntern && $employee->intern)
    <div class="section">
      {!! $secHead('Institusi & Pembimbing Magang') !!}
      <table class="grid">
        <tr>
          <td class="col">
            <table class="rows">
              {!! $row('school', 'Asal Sekolah / Kampus', $employee->intern->school_name) !!}
              {!! $row('graduation', 'Kelas & Jurusan', trim(($employee->intern->class ?? '-') . ($employee->intern->major ? ' - ' . $employee->intern->major : ''))) !!}
              {!! $row('id', 'NIS / NIM', $employee->intern->nis, ['class' => 'mono']) !!}
              {!! $row('calendar', 'Periode PKL', $fdate($employee->intern->start_date) . ' s/d ' . $fdate($employee->intern->end_date)) !!}
            </table>
          </td>
          <td class="col">
            <table class="rows">
              {!! $row('clock', 'Durasi PKL', $employee->intern->duration_text) !!}
              {!! $row('user', 'Pembimbing Sekolah', $employee->intern->mentor_teacher_name) !!}
              {!! $row('phone', 'Kontak Pembimbing', $employee->intern->mentor_teacher_phone, ['class' => 'mono']) !!}
            </table>
          </td>
        </tr>
      </table>
    </div>
    @endif

    {{-- ============ RIWAYAT KONTRAK ============ --}}
    @if($employee->contracts->isNotEmpty())
    <div class="section">
      {!! $secHead('Riwayat Kontrak Kerja') !!}
      <div class="tbl-wrap">
        <table class="tbl">
          <thead>
            <tr>
              <th style="width:26%;">No. Kontrak</th>
              <th>Mulai</th>
              <th>Berakhir</th>
              <th>Durasi</th>
              <th>Status</th>
              <th style="width:24%;">Catatan</th>
            </tr>
          </thead>
          <tbody>
            @foreach($employee->contracts as $contract)
              @php
                $st = $contract->review_status ?: ($contract->employment_status ?: '-');
                $dur = $contract->duration_text
                    ?: ($contract->start_date && $contract->end_date
                        ? \Carbon\Carbon::parse($contract->start_date)->diffForHumans(\Carbon\Carbon::parse($contract->end_date), true)
                        : '-');
              @endphp
              <tr>
                <td class="mono" style="font-weight:700;">{{ $contract->contract_number ?: '-' }}</td>
                <td>{{ $fdate($contract->start_date) }}</td>
                <td>{{ $contract->end_date ? $fdate($contract->end_date) : 'Tanpa Batas' }}</td>
                <td>{{ $dur }}</td>
                <td><span class="tag {{ $badge($st) }}">{{ strtoupper($st) }}</span></td>
                <td class="soft">{{ $contract->notes ?: '-' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @endif

    {{-- ============ KOMPENSASI ============ --}}
    @if($employee->compensation)
    @php
      $comp = $employee->compensation;
      $initial = (float) ($comp->initial_salary ?? 0);
      $current = (float) ($comp->current_salary ?? 0);
      $delta   = $current - $initial;
      $pct     = $initial > 0 ? round($delta / $initial * 100, 1) : 0;
      $increments = array_values(array_filter([$comp->increment_1_amount, $comp->increment_2_amount, $comp->increment_3_amount], fn($v) => $v !== null));
    @endphp
    <div class="section">
      {!! $secHead('Kompensasi & Pengupahan') !!}
      <table class="stat-table">
        <tr>
          <td class="stat-card">
            <div class="k">Gaji Awal Masuk</div>
            <div class="v"><small>Rp</small>{{ number_format($initial, 0, ',', '.') }}</div>
          </td>
          <td class="stat-card ok">
            <div class="k">Gaji Berjalan</div>
            <div class="v"><small>Rp</small>{{ number_format($current, 0, ',', '.') }}</div>
          </td>
          <td class="stat-card {{ $delta > 0 ? 'amber' : '' }}">
            <div class="k">Total Kenaikan</div>
            <div class="v">{{ $delta >= 0 ? '+' : '-' }}Rp {{ number_format(abs($delta), 0, ',', '.') }} <small>({{ $pct }}%)</small></div>
          </td>
        </tr>
      </table>

      <table class="rows" style="margin-top:10px;">
        {!! $row('banknote', 'Status Pengupahan', $comp->salary_status) !!}
        {!! $row('trending', 'Jumlah Kenaikan', ($comp->salary_increment_count ?? count($increments)) . ' kali' . ($comp->evaluation_cycle_months ? ' • siklus evaluasi ' . $comp->evaluation_cycle_months . ' bulan' : '')) !!}
        @if(count($increments))
          {!! $row('award', 'Riwayat Nominal Kenaikan', collect($increments)->map(fn($v) => $rp($v))->implode('  •  ')) !!}
        @endif
      </table>

      @if($employee->compensationHistories->isNotEmpty())
      <div class="tbl-wrap" style="margin-top:10px;">
        <table class="tbl">
          <thead>
            <tr>
              <th>Tanggal Efektif</th>
              <th class="r">Gaji Sebelum</th>
              <th class="r">Gaji Baru</th>
              <th class="r">Selisih</th>
              <th style="width:28%;">Keterangan</th>
            </tr>
          </thead>
          <tbody>
            @foreach($employee->compensationHistories as $hist)
              @php
                $prev = (float) ($hist->previous_salary ?? 0);
                $new  = (float) ($hist->new_salary ?? 0);
                $inc  = $hist->increment_amount !== null ? (float) $hist->increment_amount : ($new - $prev);
              @endphp
              <tr>
                <td>{{ $fdate($hist->effective_date) }}</td>
                <td class="r soft">{{ $rp($prev) }}</td>
                <td class="r" style="font-weight:700;">{{ $rp($new) }}</td>
                <td class="r" style="color:{{ $inc >= 0 ? $c['success'] : $c['danger'] }}; font-weight:700;">
                  {{ $inc >= 0 ? '+' : '-' }}{{ $rp(abs($inc)) }}
                </td>
                <td class="soft">{{ $hist->reason ?: '-' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @endif
    </div>
    @endif

    {{-- ============ REKAP PRESENSI ============ --}}
    @if($attRows->isNotEmpty())
    <div class="section">
      {!! $secHead('Rekap Presensi ' . $attMonths . ' Bulan Terakhir') !!}

      <table class="stat-table" style="margin-bottom:10px;">
        <tr>
          <td class="stat-card ok">
            <div class="k">Tingkat Kehadiran</div>
            <div class="v">{{ $attRate }}<small>%</small></div>
          </td>
          <td class="stat-card">
            <div class="k">Hari Tercatat</div>
            <div class="v">{{ $attTotals['total'] }} <small>hari</small></div>
          </td>
          <td class="stat-card warn">
            <div class="k">Total Alpha</div>
            <div class="v">{{ $attTotals['alpha'] }} <small>hari</small></div>
          </td>
        </tr>
      </table>

      <div class="tbl-wrap">
        <table class="tbl">
          <thead>
            <tr>
              <th style="width:17%;">Bulan</th>
              <th class="c">Hadir</th>
              <th class="c">Telat</th>
              <th class="c">P. Cepat</th>
              <th class="c">Izin</th>
              <th class="c">Sakit</th>
              <th class="c">Cuti</th>
              <th class="c">Alpha</th>
              <th class="c">Libur</th>
              <th class="c">Total</th>
            </tr>
          </thead>
          <tbody>
            @foreach($attRows as $month => $d)
              <tr>
                <td style="font-weight:700;">{{ \Carbon\Carbon::parse($month . '-01')->isoFormat('MMMM Y') }}</td>
                <td class="c">{{ $d['hadir'] }}</td>
                <td class="c">{{ $d['telat'] }}</td>
                <td class="c">{{ $d['cepat'] }}</td>
                <td class="c">{{ $d['izin'] }}</td>
                <td class="c">{{ $d['sakit'] }}</td>
                <td class="c">{{ $d['cuti'] }}</td>
                <td class="c" style="color:{{ $c['danger'] }}; font-weight:600;">{{ $d['alpha'] }}</td>
                <td class="c">{{ $d['libur'] }}</td>
                <td class="c" style="font-weight:700;">{{ $d['hadir'] + $d['telat'] }}<span class="soft"> / {{ $d['total'] }}</span></td>
              </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr>
              <td>Total {{ $attMonths }} Bulan</td>
              <td class="c">{{ $attTotals['hadir'] }}</td>
              <td class="c">{{ $attTotals['telat'] }}</td>
              <td class="c">{{ $attTotals['cepat'] }}</td>
              <td class="c">{{ $attTotals['izin'] }}</td>
              <td class="c">{{ $attTotals['sakit'] }}</td>
              <td class="c">{{ $attTotals['cuti'] }}</td>
              <td class="c">{{ $attTotals['alpha'] }}</td>
              <td class="c">{{ $attTotals['libur'] }}</td>
              <td class="c">{{ $attPresent }} / {{ $attTotals['total'] }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
    @endif

    {{-- ============ REKAP ONBOARDING KARYAWAN BARU ============ --}}
    @if($employee->onboarding)
    @php
      $ob = $employee->onboarding;
      $obCheck = $ob->status_checklist ?? [];
      $obComplete = $ob->isComplete();
    @endphp
    <div class="section">
      {!! $secHead('Rekap Onboarding Karyawan Baru') !!}
      <table class="grid">
        <tr>
          <td class="col">
            <table class="rows">
              {!! $row('briefcase', 'Posisi Saat Masuk', $ob->position ?: $employee->position) !!}
              {!! $row('building', 'Departemen', $ob->department ?: $employee->department) !!}
            </table>
          </td>
          <td class="col">
            <table class="rows">
              {!! $row('calendar', 'Tanggal Masuk', $fdate($ob->join_date, 'D MMMM Y')) !!}
              {!! $row('check', 'Tanggal Approval', $ob->approved_date ? $fdate($ob->approved_date, 'D MMMM Y') : 'Belum di-approve') !!}
            </table>
          </td>
        </tr>
      </table>

      <div class="tbl-wrap" style="margin-top: 10px;">
        <table class="tbl">
          <thead>
            <tr>
              <th style="width:7%;">No</th>
              <th>Item Kelengkapan Berkas</th>
              <th style="width:24%;">Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach(\App\Models\Hcm\HcmOnboarding::CHECKLIST as $key => $label)
            @php $ok = !empty($obCheck[$key]); @endphp
            <tr>
              <td class="c soft">{{ $loop->iteration }}</td>
              <td>{{ $label }}</td>
              <td><span class="tag {{ $ok ? 'tag-green' : 'tag-amber' }}">{{ $ok ? 'LENGKAP' : 'BELUM LENGKAP' }}</span></td>
            </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr>
              <td colspan="2">Status Onboarding</td>
              <td><span class="tag {{ $obComplete ? 'tag-green' : 'tag-amber' }}">{{ $obComplete ? 'SELESAI' : 'DALAM PROSES' }}</span></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
    @endif

    {{-- ============ REKAP OFFBOARDING KARYAWAN KELUAR ============ --}}
    @php $ofb = $employee->offboarding; @endphp
    <div class="section">
      {!! $secHead('Rekap Offboarding Karyawan Keluar') !!}
      @if($ofb)
      <div class="tbl-wrap">
        <table class="tbl">
          <thead>
            <tr>
              <th style="width:36%;">Item Offboarding</th>
              <th>Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="soft">Posisi Terakhir</td>
              <td style="font-weight:600;">{{ $ofb->position ?: ($employee->position ?: '-') }}</td>
            </tr>
            <tr>
              <td class="soft">Tanggal Keluar</td>
              <td style="font-weight:600;">{{ $ofb->exit_date ? $fdate($ofb->exit_date, 'D MMMM Y') : '-' }}</td>
            </tr>
            <tr>
              <td class="soft">Alasan Keluar</td>
              <td>{{ $ofb->exit_reason ?: '-' }}</td>
            </tr>
            <tr>
              <td class="soft">Kepatuhan Notice Period</td>
              <td>{{ $ofb->notice_compliance ?: '-' }}</td>
            </tr>
            <tr>
              <td class="soft">Hak Sisa Karyawan</td>
              <td>{{ $ofb->rights_status ?: '-' }}</td>
            </tr>
            <tr>
              <td class="soft">Pengembalian Aset &amp; Paklaring</td>
              <td>{{ $ofb->asset_clearance ?: '-' }}</td>
            </tr>
            <tr>
              <td class="soft">Status Clearance Sheet</td>
              <td>
                @php $clear = strtolower((string) $ofb->clearance_status); @endphp
                <span class="tag {{ (str_contains($clear, 'selesai') || str_contains($clear, 'clear')) ? 'tag-green' : 'tag-amber' }}">
                  {{ $ofb->clearance_status ? strtoupper($ofb->clearance_status) : 'PENDING' }}
                </span>
              </td>
            </tr>
            @if($ofb->offboarding_notes)
            <tr>
              <td class="soft">Catatan</td>
              <td class="soft">{{ $ofb->offboarding_notes }}</td>
            </tr>
            @endif
          </tbody>
        </table>
      </div>
      @else
      <div class="notes-box" style="border-left-color: {{ $c['success'] }};">
        Karyawan berstatus <strong>AKTIF</strong> — belum ada proses offboarding yang tercatat.
      </div>
      @endif
    </div>

    {{-- ============ CATATAN ============ --}}
    @if($employee->notes)
    <div class="section">
      {!! $secHead('Catatan Tambahan') !!}
      <div class="notes-box">{{ $employee->notes }}</div>
    </div>
    @endif

    {{-- ============ TANDA TANGAN ============ --}}
    <table class="sign-table">
      <tr>
        <td>
          <div class="sign-title">Mengetahui, Kepala Divisi {{ $employee->department ?: '' }}</div>
          <div class="sign-space"></div>
          <div class="sign-name">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>
          <div class="sign-role">Kepala Divisi / Manajer Departemen</div>
        </td>
        <td>
          <div class="sign-title">{{ $companyCity }}, {{ now()->isoFormat('D MMMM Y') }}</div>
          <div class="sign-space"></div>
          <div class="sign-name">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>
          <div class="sign-role">HR Manager / Admin HCM</div>
        </td>
      </tr>
    </table>

    @if($footerDisc)
      <div style="margin-top:14px; text-align:center; font-size:7.5px; color:{{ $c['soft'] }};">
        {{ $footerDisc }}
      </div>
    @endif

  </div>

</body>
</html>
