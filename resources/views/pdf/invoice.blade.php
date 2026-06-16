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
        .brand { color: #000000; font-weight: 900; font-size: 20pt; letter-spacing: -0.5px; }
        .brand-tagline { font-size: 9.5pt; color: #000000; font-weight: 700; }
        .invoice-title { font-size: 28pt; font-weight: 900; color: #000000; text-align: right; letter-spacing: -1px; }
        .invoice-no { font-family: monospace; text-align: right; font-size: 10pt; color: #111827; font-weight: 700; margin-top: -6px; }

        .info-grid { display: table; width: 100%; margin-bottom: 16px; }
        .info-grid > div { display: table-cell; width: 50%; vertical-align: top; padding-right: 12px; }
        .info-label { font-size: 7pt; text-transform: uppercase; letter-spacing: 0.05em; color: #6B7280; margin-bottom: 4px; }
        .info-card { border: 1px solid #E5E7EB; border-radius: 6px; padding: 10px; }
        .info-card strong { font-size: 10pt; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.items th { background: {{ $invoice->brand?->warna_primary ?? '#1E40AF' }}; color: white; padding: 8px; text-align: left; font-size: 9pt; }
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
    @php
        if (!function_exists('maskPhoneNumber')) {
            function maskPhoneNumber($phone) {
                if (empty($phone)) return '—';
                $clean = trim($phone);
                $len = strlen($clean);
                if ($len < 8) return str_repeat('*', $len);
                return substr($clean, 0, 4) . str_repeat('*', $len - 8) . substr($clean, -4);
            }
        }

        if (!function_exists('maskEmailAddress')) {
            function maskEmailAddress($email) {
                if (empty($email)) return '—';
                $parts = explode('@', $email);
                if (count($parts) !== 2) return $email;
                $local = $parts[0];
                $domain = $parts[1];
                $len = strlen($local);
                if ($len <= 2) return $local[0] . '***@' . $domain;
                return substr($local, 0, 2) . str_repeat('*', $len - 4) . substr($local, -2) . '@' . $domain;
            }
        }

        if (!function_exists('maskDetailAlamat')) {
            function maskDetailAlamat($address) {
                if (empty($address)) return '';
                $len = strlen($address);
                if ($len <= 8) return str_repeat('*', $len);
                return substr($address, 0, 5) . str_repeat('*', $len - 8) . substr($address, -3);
            }
        }
    @endphp

    <div class="header">
        <div>
            @if (!empty($logoData))
                <div style="margin-bottom: 8px;">
                    <img src="{{ $logoData }}" alt="{{ $invoice->brand?->nama_brand ?? 'Brand Logo' }}" style="max-height: 58px; max-width: 180px; object-fit: contain;">
                </div>
            @elseif ($invoice->brand?->logo)
                <div style="margin-bottom: 8px;">
                    <img src="{{ public_path('storage/' . $invoice->brand->logo) }}" alt="{{ $invoice->brand?->nama_brand ?? 'Brand Logo' }}" style="max-height: 58px; max-width: 180px; object-fit: contain;">
                </div>
            @else
                <div class="brand">{{ $invoice->brand?->nama_brand ?? 'Circle Sportwear' }}</div>
            @endif

            @if ($invoice->brand?->tagline)
                <div class="brand-tagline">{{ $invoice->brand->tagline }}</div>
            @endif

            @if ($invoice->brand?->deskripsi)
                <div style="font-size: 8pt; color: #000000; font-weight: 600; margin-top: 3px;">{{ $invoice->brand->deskripsi }}</div>
            @endif
 
            <div style="font-size: 8pt; color: #4B5563; font-weight: 500; margin-top: 4px; line-height: 1.35;">
                @if ($invoice->brand?->alamat)
                    <div><span style="font-weight: 700; color: #6B7280;">Alamat:</span> {{ $invoice->brand->alamat }}</div>
                @endif
                <div style="margin-top: 1.5px;">
                    @if ($invoice->brand?->no_hp)
                        <span style="font-weight: 700; color: #6B7280;">WA/Telp:</span> {{ $invoice->brand->no_hp }}
                    @endif
                    @if ($invoice->brand?->no_hp && $invoice->brand?->email)
                         · 
                    @endif
                    @if ($invoice->brand?->email)
                        <span style="font-weight: 700; color: #6B7280;">Email:</span> {{ $invoice->brand->email }}
                    @endif
                </div>
            </div>
 
            @php
                $svgGlobe = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#374151"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>';
                $svgInstagram = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#9D174D"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.051.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>';
                $svgFacebook = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#1D4ED8"><path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/></svg>';
                $svgTiktok = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FFFFFF"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.02 1.59 4.23.02.02.04.04.06.06.01.83.02 1.66.02 2.5-1.04-.37-1.99-1.01-2.73-1.84-.04-.04-.08-.09-.13-.13-.01 2.92-.01 5.84-.02 8.76-.08 1.63-.73 3.23-1.86 4.39-1.42 1.48-3.55 2.19-5.59 1.84-2.22-.35-4.14-1.99-4.73-4.18-.72-2.59.18-5.51 2.27-7.06.94-.71 2.09-1.09 3.28-1.12.01 1.48.01 2.97.02 4.45-.63.07-1.25.35-1.68.83-.56.61-.75 1.48-.5 2.27.27.86.99 1.51 1.87 1.69.96.22 2.04-.15 2.55-.99.27-.45.37-.99.35-1.52-.01-4.72-.01-9.44-.02-14.16z"/></svg>';

                $globeDataUri = 'data:image/svg+xml;base64,' . base64_encode($svgGlobe);
                $instagramDataUri = 'data:image/svg+xml;base64,' . base64_encode($svgInstagram);
                $facebookDataUri = 'data:image/svg+xml;base64,' . base64_encode($svgFacebook);
                $tiktokDataUri = 'data:image/svg+xml;base64,' . base64_encode($svgTiktok);
            @endphp

            <div style="margin-top: 6px;">
                @if ($invoice->brand?->website)
                    <a href="{{ str_starts_with($invoice->brand->website, 'http') ? $invoice->brand->website : 'https://' . $invoice->brand->website }}" target="_blank" style="display: inline-block; text-decoration: none; background: #F3F4F6; border: 1px solid #E5E7EB; border-radius: 4px; padding: 2px 6px; font-size: 7.5pt; font-weight: bold; color: #374151; margin-right: 5px; margin-bottom: 4px;">
                        <img src="{{ $globeDataUri }}" style="width: 9px; height: 9px; vertical-align: middle; margin-right: 2px; margin-top: 1.5px;"><span style="vertical-align: middle;">{{ $invoice->brand->website }}</span>
                    </a>
                @endif
                @if ($invoice->brand?->instagram)
                    <a href="https://instagram.com/{{ ltrim($invoice->brand->instagram, '@') }}" target="_blank" style="display: inline-block; text-decoration: none; background: #FDF2F8; border: 1px solid #FCE7F3; border-radius: 4px; padding: 2px 6px; font-size: 7.5pt; font-weight: bold; color: #9D174D; margin-right: 5px; margin-bottom: 4px;">
                        <img src="{{ $instagramDataUri }}" style="width: 9px; height: 9px; vertical-align: middle; margin-right: 2px; margin-top: 1.5px;"><span style="vertical-align: middle;"><span>@</span>{{ ltrim($invoice->brand->instagram, '@') }}</span>
                    </a>
                @endif
                @if ($invoice->brand?->facebook)
                    <a href="https://facebook.com/{{ $invoice->brand->facebook }}" target="_blank" style="display: inline-block; text-decoration: none; background: #EFF6FF; border: 1px solid #DBEAFE; border-radius: 4px; padding: 2px 6px; font-size: 7.5pt; font-weight: bold; color: #1D4ED8; margin-right: 5px; margin-bottom: 4px;">
                        <img src="{{ $facebookDataUri }}" style="width: 9px; height: 9px; vertical-align: middle; margin-right: 2px; margin-top: 1.5px;"><span style="vertical-align: middle;">{{ $invoice->brand->facebook }}</span>
                    </a>
                @endif
                @if ($invoice->brand?->tiktok)
                    <a href="https://tiktok.com/@{{ ltrim($invoice->brand->tiktok, '@') }}" target="_blank" style="display: inline-block; text-decoration: none; background: #111827; border: 1px solid #111827; border-radius: 4px; padding: 2px 6px; font-size: 7.5pt; font-weight: bold; color: #FFFFFF; margin-bottom: 4px;">
                        <img src="{{ $tiktokDataUri }}" style="width: 9px; height: 9px; vertical-align: middle; margin-right: 2px; margin-top: 1.5px;"><span style="vertical-align: middle;">TikTok: <span>@</span>{{ ltrim($invoice->brand->tiktok, '@') }}</span>
                    </a>
                @endif
            </div>
        </div>
        <div>
            <div class="invoice-title">INVOICE</div>
            <div class="invoice-no">{{ $invoice->invoice_number }}</div>
            <div style="text-align: right; font-size: 8.5pt; color: #000000; font-weight: 600; margin-top: 8px;">
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
                <strong>{{ $invoice->order?->pelanggan?->nama ?? '-' }}</strong><br>
                {{ maskPhoneNumber($invoice->order?->pelanggan?->nomor_hp ?? '') }}<br>
                @if ($invoice->order?->pelanggan?->email ?? '')
                    {{ maskEmailAddress($invoice->order->pelanggan->email) }}<br>
                @endif
                <span style="color:#6B7280; font-size: 8pt;">
                    {{ trim(implode(', ', array_filter([
                        maskDetailAlamat($invoice->order?->pelanggan?->detail_alamat ?? ''),
                        $invoice->order?->pelanggan?->kabupaten_nama ?? '',
                        $invoice->order?->pelanggan?->provinsi_nama ?? '',
                        $invoice->order?->pelanggan?->kodepos ?? '',
                    ])), ', ') }}
                </span>
            </div>
        </div>
        <div>
            <div class="info-label">Referensi PO</div>
            <div class="info-card">
                <strong style="font-family: monospace;">{{ $invoice->order?->no_po ?? '—' }}</strong><br>
                {{ $invoice->order?->nama_po ?? '—' }}<br>
                <span style="color:#6B7280; font-size: 8pt;">Tgl Order: {{ $invoice->order?->tanggal_masuk ? \Carbon\Carbon::parse($invoice->order->tanggal_masuk)->translatedFormat('d M Y') : '—' }}</span>
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
            @php
                $mainItems = $invoice->items->where('is_addon', false);
                $addonItems = $invoice->items->where('is_addon', true);
                $mainSubtotal = $mainItems->sum('subtotal');
                $addonSubtotal = $addonItems->sum('subtotal');
                $rowNum = 1;
            @endphp

            @if($mainItems->isNotEmpty())
                <tr style="background-color: #f9fafb; font-weight: bold;"><td colspan="5" style="padding: 4px 8px; font-size: 9pt;">PRODUK INTI</td></tr>
                @foreach ($mainItems as $item)
                    <tr>
                        <td>{{ $rowNum++ }}</td>
                        <td>
                            <div>{{ $item->produk }}</div>
                            @if (!empty($item->discount_amount) && $item->discount_amount > 0)
                                <div style="font-size: 8pt; color: #DC2626; font-weight: bold; margin-top: 2px;">
                                    Diskon: {{ $item->discount_type === 'persen' ? (number_format($item->discount_value, 0) . '%') : ('Rp ' . number_format($item->discount_value, 0, ',', '.')) }} (-Rp {{ number_format($item->discount_amount, 0, ',', '.') }})
                                </div>
                            @endif
                        </td>
                        <td class="right">{{ number_format($item->jumlah, 0, ',', '.') }}</td>
                        <td class="right">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                        <td class="right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @endif

            @if($addonItems->isNotEmpty())
                <tr style="background-color: #f9fafb; font-weight: bold;"><td colspan="5" style="padding: 4px 8px; font-size: 9pt;">ADD-ON</td></tr>
                @foreach ($addonItems as $item)
                    <tr>
                        <td>{{ $rowNum++ }}</td>
                        <td>
                            <div>{{ $item->produk }}</div>
                            @if (!empty($item->discount_amount) && $item->discount_amount > 0)
                                <div style="font-size: 8pt; color: #DC2626; font-weight: bold; margin-top: 2px;">
                                    Diskon: {{ $item->discount_type === 'persen' ? (number_format($item->discount_value, 0) . '%') : ('Rp ' . number_format($item->discount_value, 0, ',', '.')) }} (-Rp {{ number_format($item->discount_amount, 0, ',', '.') }})
                                </div>
                            @endif
                        </td>
                        <td class="right">{{ number_format($item->jumlah, 0, ',', '.') }}</td>
                        <td class="right">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                        <td class="right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    <div class="totals">
        <table>
            @if($addonItems->isNotEmpty())
                <tr><td class="label">Subtotal Produk Inti</td><td class="value">Rp {{ number_format($mainSubtotal, 0, ',', '.') }}</td></tr>
                <tr><td class="label">Subtotal Add-ons</td><td class="value">Rp {{ number_format($addonSubtotal, 0, ',', '.') }}</td></tr>
            @endif
            <tr><td class="label">Total Tagihan</td><td class="value">Rp {{ number_format($invoice->total_tagihan, 0, ',', '.') }}</td></tr>
            @if ($invoice->diskon_value > 0)
                <tr>
                    <td class="label">Diskon @if($invoice->diskon_type === 'persen') ({{ $invoice->diskon_value }}%) @endif</td>
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

    <div class="qr-section">
        <div>
            @if (!empty($invoice->bank))
                <div class="info-label">Transfer ke</div>
                <strong>{{ $invoice->bank->bank ?? '' }}</strong><br>
                {{ $invoice->bank->atas_nama ?? '' }}<br>
                <span style="font-family: monospace; font-size: 11pt;">{{ $invoice->bank->nomor_rekening ?? '' }}</span>
            @else
                <div class="info-label">Transfer ke</div>
                <strong style="color: #DC2626;">Hubungi Admin Resmi untuk Rekening Pembayaran</strong>
            @endif
        </div>
        @if (!empty($qrCodeData))
            <div style="text-align: right;">
                <img class="qr-image" src="{{ $qrCodeData }}" alt="QR Code">
                <div style="font-size: 7pt; color: #6B7280; margin-top: 4px;">Scan untuk tracking</div>
            </div>
        @endif
    </div>
    <div style="margin-top: 8px; padding: 8px; border: 1px dashed #D97706; background-color: #FEF3C7; border-radius: 4px; color: #92400E; font-size: 8pt; line-height: 1.3;">
        <strong>⚠️ Himbauan Keamanan Pembayaran:</strong> Demi keamanan transaksi, mohon <strong>TIDAK MELAKUKAN</strong> scan barcode/QR atau melakukan transfer ke rekening mana pun selain rekening resmi atas nama <strong>{{ $invoice->bank ? $invoice->bank->atas_nama : ($invoice->brand ? $invoice->brand->nama_brand : 'brand kami') }}</strong>. Jangan pernah mengirimkan dana ke rekening perorangan/sales/rekening lain di luar informasi resmi yang tertera. Selalu konfirmasi transaksi melalui kontak resmi brand kami.
    </div>

    <div class="footer">
        <strong>Terima kasih atas kepercayaan Anda!</strong>
        @if ($invoice->peraturan)
            <div style="margin-top: 6px;">{{ $invoice->peraturan }}</div>
        @endif
        <ol class="faq">
            <li><strong>Cara tracking pesanan?</strong> Scan QR code di atas atau kunjungi /track/{{ $invoice->order?->no_po ?? '' }}.</li>
            <li><strong>Pembayaran:</strong> Transfer via rekening di atas, kirim bukti ke WA admin.</li>
            <li><strong>Komplain:</strong> Hubungi admin melalui WhatsApp resmi dalam 3x24 jam setelah barang diterima.</li>
        </ol>
    </div>
</body>
</html>
