<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $config['label'] }} - {{ \App\Models\Settings\SystemSetting::get('seo', 'site_name', config('app.name', 'ProTrack')) }}</title>
    <style>
        @font-face {
            font-family: 'Inter';
            font-style: normal;
            font-weight: 400;
            src: url('{{ public_path("fonts/Inter-Regular.ttf") }}') format('truetype');
        }
        @font-face {
            font-family: 'Inter';
            font-style: normal;
            font-weight: 700;
            src: url('{{ public_path("fonts/Inter-Bold.ttf") }}') format('truetype');
        }
        @font-face {
            font-family: 'Noto Sans JP';
            font-style: normal;
            font-weight: 400;
            src: url('{{ public_path("fonts/NotoSansJP-Regular.ttf") }}') format('truetype');
        }
        @font-face {
            font-family: 'Noto Sans SC';
            font-style: normal;
            font-weight: 400;
            src: url('{{ public_path("fonts/NotoSansSC-Regular.ttf") }}') format('truetype');
        }
        @font-face {
            font-family: 'Noto Sans Arabic';
            font-style: normal;
            font-weight: 400;
            src: url('{{ public_path("fonts/NotoSansArabic-Regular.ttf") }}') format('truetype');
        }
        @font-face {
            font-family: 'Noto Sans Javanese';
            font-style: normal;
            font-weight: 400;
            src: url('{{ public_path("fonts/NotoSansJavanese-Regular.ttf") }}') format('truetype');
        }
        @font-face {
            font-family: 'Sarabun';
            font-style: normal;
            font-weight: 400;
            src: url('{{ public_path("fonts/Sarabun-Regular.ttf") }}') format('truetype');
        }
        .cjk-font {
            font-family: 'Noto Sans SC', 'Noto Sans JP', sans-serif !important;
            text-transform: none !important;
        }
        .javanese-font {
            font-family: 'Noto Sans Javanese', sans-serif !important;
            text-transform: none !important;
        }
        .arabic-font {
            font-family: 'Noto Sans Arabic', sans-serif !important;
            text-transform: none !important;
            line-height: 1.4 !important;
            display: inline-block;
        }
        .thai-font {
            font-family: 'Sarabun', sans-serif !important;
            text-transform: none !important;
        }
        @page { size: A4 landscape; margin: 8mm 10mm 10mm 10mm; }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', 'Helvetica', 'Arial', 'DejaVu Sans', sans-serif; font-size: 8pt; color: #111; margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 6px; border-bottom: 2px solid #1E40AF; margin-bottom: 10px; }
        .brand { color: #1E40AF; font-weight: 700; font-size: 13pt; }
        .meta { font-size: 7.5pt; color: #666; text-align: right; }
        h1 { font-size: 12pt; margin: 0 0 3px 0; color: #111; }
        .filters { font-size: 7.5pt; color: #666; margin-bottom: 10px; }
        .summary { margin-bottom: 10px; }
        .summary-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px; }
        .summary-card { border: 1px solid #E2E8F0; border-radius: 4px; padding: 5px 7px; background: #F8FAFC; }
        .summary-card .label { font-size: 6.5pt; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em; }
        .summary-card .value { font-size: 9.5pt; font-weight: 700; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 7.5pt; table-layout: auto; }
        th { background: #1E40AF; color: white; text-align: left; padding: 4px 5px; font-size: 7.5pt; font-weight: 600; white-space: nowrap; }
        td { border-bottom: 1px solid #E2E8F0; padding: 3px 5px; font-size: 7.5pt; vertical-align: top; }
        tr:nth-child(even) td { background: #F8FAFC; }
        .right { text-align: right; }
        .footer { position: fixed; bottom: 6px; left: 0; right: 0; text-align: center; font-size: 6.5pt; color: #999; }
        .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; background: #F1F5F9; color: #1E40AF; font-size: 6.5pt; font-weight: 600; }
    </style>
    {!! '<' . 'style>' .
        '.header { border-bottom-color: ' . $primaryColor . '; }' .
        '.brand { color: ' . $primaryColor . '; }' .
        '.badge { color: ' . $primaryColor . '; }' .
        'th { background-color: ' . $primaryColor . '; }' .
        '</' . 'style>' !!}
</head>
<body>
    <div class="header">
        <div>
            <div class="brand">{{ \App\Models\Settings\SystemSetting::get('seo', 'site_name', config('app.name', 'ProTrack')) }}</div>
            <h1>{{ $config['label'] }}</h1>
            <div style="font-size: 8pt; color: #666;">{{ $config['description'] ?? '' }}</div>
        </div>
        <div class="meta">
            <div><strong>Dibuat:</strong> {{ $generated_at->translatedFormat('d M Y, H:i') }}</div>
            <div><strong>Oleh:</strong> {{ $user->name ?? '-' }}</div>
        </div>
    </div>

    <div class="filters">
        <strong>Filter:</strong>
        @foreach ($filters as $key => $value)
            @if ($value !== '' && $value !== null)
                <span class="badge">{{ $key }}: {{ $value }}</span>
            @endif
        @endforeach
    </div>

    @if (!empty($summary))
        <div class="summary">
            <div class="summary-grid">
                @foreach ($summary as $s)
                    <div class="summary-card">
                        <div class="label">{{ $s['label'] }}</div>
                        <div class="value">
                            @if (($s['format'] ?? null) === 'currency')
                                Rp {{ number_format((float) $s['value'], 0, ',', '.') }}
                            @else
                                {{ is_numeric($s['value']) ? number_format($s['value'], 0, ',', '.') : $s['value'] }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <table>
        <thead>
            <tr>
                @foreach ($config['columns'] as $col)
                    <th>{{ $col['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @if (!empty($row['is_group_header']))
                    <tr style="background-color: #F1F5F9;">
                        <td colspan="{{ count($config['columns']) }}" {!! 'style="font-weight: bold; border-bottom: 2px solid ' . $primaryColor . '; color: ' . $primaryColor . '; font-size: 9.5pt; padding: 6px 8px;"' !!}>
                            {{ ($config['slug'] ?? '') === 'monitoring-deadline' ? 'Deadline Produksi' : 'Deadline' }}: {{ \Carbon\Carbon::parse($row['deadline_produksi'] ?? $row['deadline'])->translatedFormat('d M Y') }}
                        </td>
                    </tr>
                @elseif (!empty($row['is_group_total']))
                    <tr style="background-color: #F8FAFC; font-weight: bold;">
                        @foreach ($config['columns'] as $col)
                            @php
                                $val = $row[$col['key']] ?? null;
                                $fmt = $col['format'] ?? null;
                            @endphp
                            <td class="{{ in_array($fmt, ['currency', 'number']) ? 'right' : '' }}" style="border-top: 1px solid #CBD5E1; border-bottom: 2px double #94A3B8; padding: 6px 8px; font-weight: bold;">
                                @if ($col['key'] === 'pelanggan')
                                    <strong>TOTAL PCS</strong>
                                @elseif ($col['key'] === 'pcs')
                                    <strong>{{ number_format((float) $val, 0, ',', '.') }}</strong>
                                @elseif ($col['key'] === 'deadline')
                                    &nbsp;
                                @else
                                    &nbsp;
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @else
                    <tr>
                        @foreach ($config['columns'] as $col)
                            @php
                                $val = $row[$col['key']] ?? null;
                                $fmt = $col['format'] ?? null;
                            @endphp
                            <td class="{{ in_array($fmt, ['currency', 'number']) ? 'right' : '' }}">
                                @if ($val === null || $val === '')
                                    -
                                @elseif ($fmt === 'currency')
                                    Rp {{ number_format((float) $val, 0, ',', '.') }}
                                @elseif ($fmt === 'number')
                                    {{ number_format((float) $val, 0, ',', '.') }}
                                @elseif ($fmt === 'date')
                                    {{ \Carbon\Carbon::parse($val)->translatedFormat('d M Y') }}
                                @elseif ($fmt === 'days_indicator')
                                    @if ((int) $val < 0)
                                        <span style="color:#DC2626;font-weight:600">{{ abs((int) $val) }} hari telat</span>
                                    @else
                                        H-{{ (int) $val }}
                                    @endif
                                @else
                                    @if ($col['key'] === 'status')
                                        @php
                                            $statusLabels = [
                                                'draft' => 'Draft',
                                                'validated' => 'Validasi',
                                                'published' => 'Baru Masuk',
                                                'on_progress' => 'Sedang Produksi',
                                                'selesai_produksi' => 'Selesai Produksi',
                                                'siap_dikirim' => 'Siap Dikirim',
                                                'sudah_dikirim' => 'Sudah Dikirim',
                                                'selesai' => 'Selesai',
                                                'delay' => 'Tertunda (Delay)',
                                                'hold' => 'Ditahan (Hold)',
                                                'cancel' => 'Dibatalkan',
                                                'paid' => 'Lunas',
                                                'overdue' => 'Jatuh Tempo',
                                                'sent' => 'Dikirim',
                                            ];
                                            echo $statusLabels[$val] ?? str_replace('_', ' ', $val);
                                        @endphp
                                    @else
                                        {!! \App\Support\PdfHelper::formatText($val) !!}
                                    @endif
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endif
            @empty
                <tr><td colspan="{{ count($config['columns']) }}" style="text-align:center;color:#999;padding:20px">Tidak ada data</td></tr>
            @endforelse
        </tbody>
        @php
            $dataRows = array_filter($rows, fn($r) => empty($r['is_group_header']) && empty($r['is_group_total']));
            $keysToSum = ['pcs', 'total_qty', 'total_order', 'total_tagihan', 'total_value', 'jumlah', 'nominal', 'nominal_refund', 'amount', 'total_pcs'];
        @endphp
        @if (!empty($dataRows))
            <tfoot>
                <tr {!! 'style="background-color: #F1F5F9; font-weight: bold; border-top: 2px solid ' . $primaryColor . '; border-bottom: 2px double ' . $primaryColor . ';"' !!}>
                    @foreach ($config['columns'] as $idx => $col)
                        @php
                            $fmt = $col['format'] ?? null;
                            $isSummable = in_array($col['key'], $keysToSum, true);
                            $sumVal = $isSummable ? array_sum(array_column($dataRows, $col['key'])) : null;
                        @endphp
                        <td class="{{ in_array($fmt, ['currency', 'number']) ? 'right' : '' }}" style="padding: 6px; font-size: 8.5pt; font-weight: bold;">
                            @if ($idx === 0)
                                TOTAL KESELURUHAN ({{ count($dataRows) }} Data)
                            @elseif ($isSummable)
                                @if ($fmt === 'currency')
                                    Rp {{ number_format((float) $sumVal, 0, ',', '.') }}
                                @else
                                    {{ number_format((float) $sumVal, 0, ',', '.') }}
                                @endif
                            @else
                                &nbsp;
                            @endif
                        </td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="footer">{{ \App\Models\Settings\SystemSetting::get('seo', 'site_name', config('app.name', 'ProTrack')) }} · Multi-Brand Order Management · halaman <span class="pagenum"></span></div>
</body>
</html>
