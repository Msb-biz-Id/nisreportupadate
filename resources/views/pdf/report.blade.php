<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $config['label'] }} - {{ \App\Models\Settings\SystemSetting::get('seo', 'site_name', config('app.name', 'ProTrack')) }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #111; margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 8px; border-bottom: 2px solid #1E40AF; margin-bottom: 14px; }
        .brand { color: #1E40AF; font-weight: 700; font-size: 14pt; }
        .meta { font-size: 8pt; color: #666; text-align: right; }
        h1 { font-size: 13pt; margin: 0 0 4px 0; color: #111; }
        .filters { font-size: 8pt; color: #666; margin-bottom: 12px; }
        .summary { margin-bottom: 12px; }
        .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; }
        .summary-card { border: 1px solid #E2E8F0; border-radius: 4px; padding: 6px 8px; background: #F8FAFC; }
        .summary-card .label { font-size: 7pt; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em; }
        .summary-card .value { font-size: 10pt; font-weight: 700; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #1E40AF; color: white; text-align: left; padding: 5px 6px; font-size: 8.5pt; font-weight: 600; }
        td { border-bottom: 1px solid #E2E8F0; padding: 4px 6px; font-size: 8pt; vertical-align: top; }
        tr:nth-child(even) td { background: #F8FAFC; }
        .right { text-align: right; }
        .footer { position: fixed; bottom: 8px; left: 0; right: 0; text-align: center; font-size: 7pt; color: #999; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; background: #E0E7FF; color: #3730A3; font-size: 7pt; font-weight: 600; }
    </style>
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
                    <tr style="background-color: #EFF6FF;">
                        <td colspan="{{ count($config['columns']) }}" style="font-weight: bold; border-bottom: 2px solid #3B82F6; color: #1D4ED8; font-size: 9.5pt; padding: 6px 8px;">
                            Deadline: {{ \Carbon\Carbon::parse($row['deadline'])->translatedFormat('d M Y') }}
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
                                        {{ $val }}
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
    </table>

    <div class="footer">{{ \App\Models\Settings\SystemSetting::get('seo', 'site_name', config('app.name', 'ProTrack')) }} · Multi-Brand Order Management · halaman <span class="pagenum"></span></div>
</body>
</html>
