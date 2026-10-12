<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Laporan Rekapitulasi Pengadaan' }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8.5pt;
            color: #1F2937;
            margin: 0;
            padding: 18px 24px;
        }
        .header {
            border-bottom: 2px solid #1E293B;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .title {
            font-size: 14pt;
            font-weight: bold;
            color: #0F172A;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .subtitle {
            font-size: 9pt;
            color: #64748B;
            margin-top: 3px;
        }
        .kpi-container {
            width: 100%;
            margin-bottom: 14px;
        }
        .kpi-box {
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            padding: 8px 12px;
            text-align: left;
        }
        .kpi-label {
            font-size: 7.5pt;
            color: #64748B;
            text-transform: uppercase;
            font-weight: bold;
        }
        .kpi-val {
            font-size: 11pt;
            color: #0F172A;
            font-weight: bold;
            margin-top: 2px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        table.data-table th {
            background-color: #1E293B;
            color: #FFFFFF;
            font-weight: bold;
            font-size: 7.5pt;
            padding: 6px 8px;
            text-align: left;
            text-transform: uppercase;
        }
        table.data-table td {
            border-bottom: 1px solid #E2E8F0;
            padding: 5px 8px;
            font-size: 8pt;
        }
        table.data-table tr:nth-child(even) {
            background-color: #F8FAFC;
        }
        .right {
            text-align: right;
        }
        .center {
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
        }
        .badge-opex { background-color: #EFF6FF; color: #1D4ED8; }
        .badge-capex { background-color: #FDF2F8; color: #BE185D; }
        .footer {
            margin-top: 20px;
            border-top: 1px solid #E2E8F0;
            padding-top: 10px;
            font-size: 7.5pt;
            color: #94A3B8;
        }
        .signatures {
            width: 100%;
            margin-top: 25px;
        }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="title">Laporan Rekapitulasi Pengadaan & Purchasing</div>
                    <div class="subtitle">NIS Group • Periode: {{ $period_label ?? 'Semua Waktu' }}</div>
                </td>
                <td style="text-align: right; vertical-align: bottom;">
                    <div style="font-size: 8pt; color: #64748B;">Tanggal Cetak: {{ date('d F Y H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Ringkasan Eksekutif KPI -->
    <table class="kpi-container">
        <tr>
            <td style="width: 25%; padding-right: 6px;">
                <div class="kpi-box">
                    <div class="kpi-label">Total Belanja Opex</div>
                    <div class="kpi-val" style="color: #2563EB;">Rp {{ number_format($summary['total_opex'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%; padding: 0 3px;">
                <div class="kpi-box">
                    <div class="kpi-label">Total Investasi Capex</div>
                    <div class="kpi-val" style="color: #DB2777;">Rp {{ number_format($summary['total_capex'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%; padding: 0 3px;">
                <div class="kpi-box">
                    <div class="kpi-label">Grand Total Pembelian</div>
                    <div class="kpi-val" style="color: #059669;">Rp {{ number_format($summary['total_spending'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%; padding-left: 6px;">
                <div class="kpi-box">
                    <div class="kpi-label">Total Transaksi PO</div>
                    <div class="kpi-val">{{ number_format($summary['total_orders'] ?? 0) }} Transaksi</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Rincian Transaksi -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;" class="center">No</th>
                <th style="width: 85px;">No. PO</th>
                <th style="width: 60px;">Tanggal</th>
                <th style="width: 45px;">Tipe</th>
                <th>Departemen & Divisi</th>
                <th>Pemohon</th>
                <th>Kategori / Item</th>
                <th class="right">Grand Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $idx => $order)
            <tr>
                <td class="center">{{ $idx + 1 }}</td>
                <td style="font-weight: bold; font-family: monospace;">{{ $order->po_number }}</td>
                <td>{{ $order->transaction_date?->format('d/m/Y') }}</td>
                <td>
                    <span class="badge {{ $order->order_type === 'CAPEX' ? 'badge-capex' : 'badge-opex' }}">
                        {{ $order->order_type }}
                    </span>
                </td>
                <td>
                    <strong>{{ $order->department }}</strong>
                    @if($order->division)<br><span style="color: #64748B; font-size: 7.5pt;">{{ $order->division }}</span>@endif
                </td>
                <td>{{ $order->requester_name ?: '-' }}</td>
                <td>{{ $order->item_category }}</td>
                <td class="right" style="font-weight: bold;">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="center" style="padding: 15px; color: #94A3B8;">Tidak ada data transaksi pengadaan pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #F1F5F9; font-weight: bold;">
                <td colspan="7" class="right" style="padding: 8px;">TOTAL KESELURUHAN PENGADAAN:</td>
                <td class="right" style="padding: 8px; color: #059669; font-size: 9pt;">Rp {{ number_format($summary['total_spending'] ?? 0, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- Tanda Tangan / Persetujuan Resmi -->
    <table class="signatures">
        <tr>
            <td style="width: 33%; text-align: center;">
                <div style="font-size: 8pt; color: #64748B; margin-bottom: 45px;">Dibuat Oleh,<br><strong>Staf Purchasing</strong></div>
                <div style="border-top: 1px solid #94A3B8; width: 65%; margin: 0 auto; padding-top: 3px;">(.......................................)</div>
            </td>
            <td style="width: 33%; text-align: center;">
                <div style="font-size: 8pt; color: #64748B; margin-bottom: 45px;">Diverifikasi Oleh,<br><strong>Head of Finance</strong></div>
                <div style="border-top: 1px solid #94A3B8; width: 65%; margin: 0 auto; padding-top: 3px;">(.......................................)</div>
            </td>
            <td style="width: 33%; text-align: center;">
                <div style="font-size: 8pt; color: #64748B; margin-bottom: 45px;">Disetujui Oleh,<br><strong>Direktur Operasional</strong></div>
                <div style="border-top: 1px solid #94A3B8; width: 65%; margin: 0 auto; padding-top: 3px;">(.......................................)</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Dokumen ini dihasilkan secara otomatis oleh ProTrack Purchasing & Asset Management System NIS Group. Dicetak pada {{ date('d/m/Y H:i:s') }}.
    </div>
</body>
</html>
