@php
    /**
     * @var string $mode
     * @var array $result
     * @var \App\Models\Brand|null $brand
     * @var int $year
     * @var array $years
     * @var \Carbon\Carbon $generated_at
     * @var \App\Models\User $user
     */
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Perbandingan & Kinerja - NISReport</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; color: #111; margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 8px; border-bottom: 2px solid #4F46E5; margin-bottom: 14px; }
        .brand { color: #4F46E5; font-weight: 700; font-size: 14pt; }
        .meta { font-size: 8pt; color: #666; text-align: right; float: right; }
        .title-area { float: left; }
        .clear { clear: both; }
        h1 { font-size: 13pt; margin: 0 0 4px 0; color: #111; }
        .filters { font-size: 8pt; color: #666; margin-bottom: 12px; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; background: #EEF2F6; color: #4F46E5; font-size: 7.5pt; font-weight: 600; margin-right: 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #4F46E5; color: white; text-align: center; padding: 6px 8px; font-size: 8pt; font-weight: 600; border: 1px solid #CBD5E1; }
        td { border: 1px solid #E2E8F0; padding: 5px 8px; font-size: 8pt; vertical-align: middle; }
        .month-col { font-weight: 600; background: #F8FAFC; border-right: 1px solid #CBD5E1; }
        .right { text-align: right; font-family: 'monospace'; }
        .total-row { background: #F1F5F9; font-weight: 700; }
        .total-row td { border-top: 2px solid #94A3B8; }
        .footer { position: fixed; bottom: 8px; left: 0; right: 0; text-align: center; font-size: 7pt; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title-area">
            <div class="brand">NISReport</div>
            <h1>Analisis Pertumbuhan & Kinerja Kuantitatif</h1>
            <div style="font-size: 8.5pt; color: #555;">
                @if ($mode === 'brands')
                    Perbandingan Kinerja Lintas Brand (Tahun {{ $year }})
                @else
                    Perbandingan Kinerja Multi-Tahun Brand: <strong>{{ $brand?->nama_brand }}</strong>
                @endif
            </div>
        </div>
        <div class="meta">
            <div><strong>Generated:</strong> {{ $generated_at->translatedFormat('d M Y, H:i') }}</div>
            <div><strong>Oleh:</strong> {{ $user?->name ?? '-' }}</div>
        </div>
        <div class="clear"></div>
    </div>

    <div class="filters">
        <strong>Filter Aktif:</strong>
        <span class="badge">Mode: {{ $mode === 'brands' ? 'Perbandingan Lintas Brand' : 'Perbandingan Multi-Tahun' }}</span>
        @if ($mode === 'brands')
            <span class="badge">Tahun: {{ $year }}</span>
            <span class="badge">Jumlah Brand: {{ count($result['data']) }}</span>
        @else
            <span class="badge">Brand: {{ $brand?->nama_brand }}</span>
            <span class="badge">Tahun Perbandingan: {{ implode(', ', $years) }}</span>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 10%;">Bulan</th>
                @if ($mode === 'brands')
                    @foreach ($result['data'] as $id => $b)
                        <th colspan="3" style="border-top: 3px solid {{ $b['warna'] ?: '#4F46E5' }};">
                            {{ $b['brand_name'] }} ({{ $b['kode'] }})
                        </th>
                    @endforeach
                @else
                    @foreach ($years as $y)
                        <th colspan="3">Tahun {{ $y }}</th>
                    @endforeach
                @endif
            </tr>
            <tr>
                @if ($mode === 'brands')
                    @foreach ($result['data'] as $id => $b)
                        <th>PO</th>
                        <th>Pcs</th>
                        <th>Omset</th>
                    @endforeach
                @else
                    @foreach ($years as $y)
                        <th>PO</th>
                        <th>Pcs</th>
                        <th>Omset</th>
                    @endforeach
                @endif
            </tr>
        </thead>
        <tbody>
            @php
                $monthsKeys = range(1, 12);
                $monthsNames = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                ];
            @endphp
            @foreach ($monthsNames as $num => $name)
                <tr>
                    <td class="month-col">{{ $name }}</td>
                    @if ($mode === 'brands')
                        @foreach ($result['data'] as $id => $b)
                            @php
                                $m = $b['months'][$num] ?? ['total_po' => 0, 'total_pcs' => 0, 'total_omset' => 0];
                            @endphp
                            <td class="right">{{ $m['total_po'] }}</td>
                            <td class="right">{{ $m['total_pcs'] }}</td>
                            <td class="right">Rp {{ number_format($m['total_omset'], 0, ',', '.') }}</td>
                        @endforeach
                    @else
                        @foreach ($years as $y)
                            @php
                                $m = $result['data'][$y]['months'][$num] ?? ['total_po' => 0, 'total_pcs' => 0, 'total_omset' => 0];
                            @endphp
                            <td class="right">{{ $m['total_po'] }}</td>
                            <td class="right">{{ $m['total_pcs'] }}</td>
                            <td class="right">Rp {{ number_format($m['total_omset'], 0, ',', '.') }}</td>
                        @endforeach
                    @endif
                </tr>
            @endforeach

            <!-- Total Row -->
            <tr class="total-row">
                <td>TOTAL</td>
                @if ($mode === 'brands')
                    @foreach ($result['data'] as $id => $b)
                        <td class="right">{{ $b['totals']['total_po'] }}</td>
                        <td class="right">{{ $b['totals']['total_pcs'] }}</td>
                        <td class="right">Rp {{ number_format($b['totals']['total_omset'], 0, ',', '.') }}</td>
                    @endforeach
                @else
                    @foreach ($years as $y)
                        @php
                            $totals = $result['data'][$y]['totals'] ?? ['total_po' => 0, 'total_pcs' => 0, 'total_omset' => 0];
                        @endphp
                        <td class="right">{{ $totals['total_po'] }}</td>
                        <td class="right">{{ $totals['total_pcs'] }}</td>
                        <td class="right">Rp {{ number_format($totals['total_omset'], 0, ',', '.') }}</td>
                    @endforeach
                @endif
            </tr>
        </tbody>
    </table>

    <div class="footer">NISReport · Laporan Pertumbuhan & Perbandingan Kinerja Tahunan</div>
</body>
</html>
