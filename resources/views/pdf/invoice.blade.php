<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5pt; color: #1F2937; margin: 0; }
        @page { margin: 14mm; }
        .header { display: table; width: 100%; margin-bottom: 18px; }
        .header > div { display: table-cell; vertical-align: top; }
        .brand { color: {{ $invoice->brand->warna_primary ?? '#1E40AF' }}; font-weight: 700; font-size: 18pt; }
        .brand-tagline { font-size: 8.5pt; color: #6B7280; }
        .invoice-title { font-size: 28pt; font-weight: 800; color: {{ $invoice->brand->warna_primary ?? '#1E40AF' }}; text-align: right; letter-spacing: -1px; }
        .invoice-no { font-family: monospace; text-align: right; font-size: 10pt; color: #6B7280; margin-top: -6px; }

        .info-grid { display: table; width: 100%; margin-bottom: 16px; }
        .info-grid > div { display: table-cell; width: 50%; vertical-align: top; padding-right: 12px; }
        .info-label { font-size: 7pt; text-transform: uppercase; letter-spacing: 0.05em; color: #6B7280; margin-bottom: 4px; }
        .info-card { border: 1px solid #E5E7EB; border-radius: 6px; padding: 10px; }
        .info-card strong { font-size: 10pt; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.items th { background: {{ $invoice->brand->warna_primary ?? '#1E40AF' }}; color: white; padding: 8px; text-align: left; font-size: 9pt; }
        table.items td { border-bottom: 1px solid #E5E7EB; padding: 7px 8px; }
        table.items .right { text-align: right; }

        .totals { width: 280px; margin-left: auto; margin-top: 8px; }
        .totals table { width: 100%; }
        .totals td { padding: 4px 8px; }
        .totals tr.grand td { font-weight: 700; font-size: 12pt; border-top: 2px solid #1F2937; padding-top: 8px; }
        .totals .label { color: #6B7280; }
        .totals .value { text-align: right; font-family: monospace; }

        .qr-section { display: table; width: 100%; margin-top: 18px; padding: 12px; background: #F8FAFC; border: 1px solid #E5E7EB; border-radius: 6px; }
        .qr-section > div { display: table-cell; vertical-align: middle; }
        .qr-image { width: 90px; }

        .footer { margin-top: 24px; padding-top: 12px; border-top: 1px solid #E5E7EB; font-size: 8pt; color: #6B7280; }
        .footer strong { color: #1F2937; }
        .faq { margin-top: 8px; font-size: 7.5pt; }
        .faq li { margin-bottom: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="brand">{{ $invoice->brand->nama_brand }}</div>
            <div class="brand-tagline">{{ $invoice->brand->tagline ?? '' }}</div>
            <div style="font-size: 7.5pt; color: #6B7280; margin-top: 4px;">
                {{ $invoice->brand->alamat ?? '' }}<br>
                {{ $invoice->brand->no_hp ?? '' }} · {{ $invoice->brand->email ?? '' }}
            </div>
        </div>
        <div>
            <div class="invoice-title">INVOICE</div>
            <div class="invoice-no">{{ $invoice->invoice_number }}</div>
            <div style="text-align: right; font-size: 8.5pt; color: #6B7280; margin-top: 8px;">
                <div>Tgl Terbit: <strong>{{ \Carbon\Carbon::parse($invoice->tanggal_terbit)->translatedFormat('d M Y') }}</strong></div>
                @if ($invoice->jatuh_tempo)
                <div>Jatuh Tempo: <strong>{{ \Carbon\Carbon::parse($invoice->jatuh_tempo)->translatedFormat('d M Y') }}</strong></div>
                @endif
            </div>
        </div>
    </div>

    <div class="info-grid">
        <div>
            <div class="info-label">Tagihan kepada</div>
            <div class="info-card">
                <strong>{{ $invoice->order->pelanggan->nama ?? '-' }}</strong><br>
                {{ $invoice->order->pelanggan->nomor_hp ?? '' }}<br>
                @if ($invoice->order->pelanggan->email) {{ $invoice->order->pelanggan->email }}<br> @endif
                <span style="color:#6B7280; font-size: 8pt;">
                    {{ trim(implode(', ', array_filter([
                        $invoice->order->pelanggan->detail_alamat,
                        $invoice->order->pelanggan->kabupaten_nama,
                        $invoice->order->pelanggan->provinsi_nama,
                        $invoice->order->pelanggan->kodepos,
                    ])), ', ') }}
                </span>
            </div>
        </div>
        <div>
            <div class="info-label">Referensi PO</div>
            <div class="info-card">
                <strong style="font-family: monospace;">{{ $invoice->order->no_po }}</strong><br>
                {{ $invoice->order->nama_po }}<br>
                <span style="color:#6B7280; font-size: 8pt;">Tgl Order: {{ \Carbon\Carbon::parse($invoice->order->tanggal_masuk)->translatedFormat('d M Y') }}</span>
            </div>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th width="40">#</th>
                <th>Produk</th>
                <th class="right" width="80">Qty</th>
                <th class="right" width="120">Harga Satuan</th>
                <th class="right" width="140">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->produk }}</td>
                    <td class="right">{{ number_format($item->jumlah, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr><td class="label">Subtotal</td><td class="value">Rp {{ number_format($invoice->total_tagihan, 0, ',', '.') }}</td></tr>
            @if ($invoice->diskon_value > 0)
            <tr><td class="label">Diskon @if($invoice->diskon_type === 'persen') ({{ $invoice->diskon_value }}%) @endif</td>
                <td class="value">- Rp {{ number_format($invoice->diskon_type === 'persen' ? ($invoice->total_tagihan * $invoice->diskon_value / 100) : $invoice->diskon_value, 0, ',', '.') }}</td>
            </tr>
            @endif
            @if ($invoice->biaya_pengiriman > 0)
            <tr><td class="label">Ongkir ({{ $invoice->jasa_pengiriman ?? '' }})</td><td class="value">Rp {{ number_format($invoice->biaya_pengiriman, 0, ',', '.') }}</td></tr>
            @endif
            @if ($invoice->dp_amount > 0)
            <tr><td class="label">DP Diterima</td><td class="value">- Rp {{ number_format($invoice->dp_amount, 0, ',', '.') }}</td></tr>
            @endif
            <tr class="grand"><td>SISA TAGIHAN</td><td class="value">Rp {{ number_format($invoice->sisa_pembayaran, 0, ',', '.') }}</td></tr>
        </table>
    </div>

    @if (!empty($invoice->bank))
    <div class="qr-section">
        <div>
            <div class="info-label">Transfer ke</div>
            <strong>{{ $invoice->bank->bank ?? '' }}</strong><br>
            {{ $invoice->bank->atas_nama ?? '' }}<br>
            <span style="font-family: monospace; font-size: 11pt;">{{ $invoice->bank->nomor_rekening ?? '' }}</span>
        </div>
        @if (!empty($qrCodeData))
        <div style="text-align: right;">
            <img class="qr-image" src="{{ $qrCodeData }}" alt="QR Code">
            <div style="font-size: 7pt; color: #6B7280; margin-top: 4px;">Scan untuk tracking</div>
        </div>
        @endif
    </div>
    @endif

    <div class="footer">
        <strong>Terima kasih atas kepercayaan Anda!</strong>
        @if ($invoice->peraturan)
            <div style="margin-top: 6px;">{{ $invoice->peraturan }}</div>
        @endif
        <ol class="faq">
            <li><strong>Cara tracking pesanan?</strong> Scan QR code di atas atau kunjungi /track/{{ $invoice->order->no_po }}.</li>
            <li><strong>Pembayaran:</strong> Transfer via rekening di atas, kirim bukti ke WA admin.</li>
            <li><strong>Komplain:</strong> Hubungi admin melalui WhatsApp resmi dalam 3x24 jam setelah barang diterima.</li>
        </ol>
    </div>
</body>
</html>
