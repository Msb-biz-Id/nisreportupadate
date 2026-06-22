<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @font-face {
            font-family: 'Noto Sans JP';
            font-style: normal;
            font-weight: 400;
            src: url('{{ public_path("fonts/NotoSansJP-Regular.ttf") }}') format('truetype');
        }
        @font-face {
            font-family: 'Noto Sans Arabic';
            font-style: normal;
            font-weight: 400;
            src: url('{{ public_path("fonts/NotoSansArabic-Regular.ttf") }}') format('truetype');
        }
        
        .cjk-font {
            font-family: 'Noto Sans JP', sans-serif !important;
            text-transform: none !important;
        }
        .arabic-font {
            font-family: 'Noto Sans Arabic', sans-serif !important;
            text-transform: none !important;
            /* ArPHP::utf8Glyphs() sudah reshape — gunakan ltr agar tidak di-reverse ulang */
            direction: ltr;
            unicode-bidi: bidi-override;
        }

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
        table.items th { color: white; padding: 8px; text-align: left; font-size: 9pt; }
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
    </style>
</head>
<body>
    @php
        /** @var \App\Models\Order\Invoice $invoice */
        /** @var \App\Models\Brand|null $headerBrand */
        /** @var string|null $logoData */
        /** @var string|null $qrCodeData */

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
        @include('pdf.components.kop', ['brand' => $headerBrand ?? ($invoice->brand ? $invoice->brand->getHeaderBrand() : null), 'logoData' => $logoData ?? null])
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
                <strong>{!! \App\Support\PdfHelper::formatText($invoice->order?->pelanggan?->nama ?? '-') !!}</strong><br>
                {{ maskPhoneNumber($invoice->order?->pelanggan?->nomor_hp ?? '') }}<br>
                @if ($invoice->order?->pelanggan?->email ?? '')
                    {{ maskEmailAddress($invoice->order?->pelanggan?->email) }}<br>
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
                {!! \App\Support\PdfHelper::formatText($invoice->order?->nama_po ?? '—') !!}<br>
                <span style="color:#6B7280; font-size: 8pt;">Tgl Order: {{ $invoice->order?->tanggal_masuk ? \Carbon\Carbon::parse($invoice->order?->tanggal_masuk)->translatedFormat('d M Y') : '—' }}</span>
                @if($invoice->order?->iklan)
                    <br><span style="font-size: 8pt; color: #047857; font-weight: bold;">Promo: {{ $invoice->order?->iklan?->nama }}{{ $invoice->order?->iklan?->platform ? ' (' . $invoice->order?->iklan?->platform . ')' : '' }}</span>
                @endif
            </div>
        </div>
    </div>

    @php
        $primaryColor = $invoice->brand?->warna_primary ?? '#1E40AF';
    @endphp
    <table class="items">
        <thead>
            <tr>
                <th style="background-color: {{ $primaryColor }};" width="40">#</th>
                <th style="background-color: {{ $primaryColor }};">Produk</th>
                <th style="background-color: {{ $primaryColor }};" class="right" width="80">Qty</th>
                <th style="background-color: {{ $primaryColor }};" class="right" width="120">Harga Satuan</th>
                <th style="background-color: {{ $primaryColor }};" class="right" width="140">Subtotal</th>
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
                            <div>{!! \App\Support\PdfHelper::formatText($item->produk) !!}</div>
                            @if (!empty($item->discount_amount) && $item->discount_amount > 0)
                                <div style="font-size: 8pt; color: #DC2626; font-weight: bold; margin-top: 2px;">
                                    Diskon: {{ $item->discount_type === 'persen' ? (number_format($item->discount_value, 0) . '%') : ('Rp ' . number_format($item->discount_value, 0, ',', '.') . '/pcs') }} (-Rp {{ number_format($item->discount_amount, 0, ',', '.') }})
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
                            <div>{!! \App\Support\PdfHelper::formatText($item->produk) !!}</div>
                            @if (!empty($item->discount_amount) && $item->discount_amount > 0)
                                <div style="font-size: 8pt; color: #DC2626; font-weight: bold; margin-top: 2px;">
                                    Diskon: {{ $item->discount_type === 'persen' ? (number_format($item->discount_value, 0) . '%') : ('Rp ' . number_format($item->discount_value, 0, ',', '.') . '/pcs') }} (-Rp {{ number_format($item->discount_amount, 0, ',', '.') }})
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

    <div style="display: table; width: 100%; margin-top: 15px;">
        <!-- Column 1: Bank Transfer Details (Left side) -->
        <div style="display: table-cell; width: 50%; vertical-align: top; padding-right: 20px;">
            <div class="info-label" style="font-size: 7.5pt; color: #6B7280; text-transform: uppercase; margin-bottom: 5px;">Rekening Pembayaran Resmi</div>
            <div style="border: 1px solid #E5E7EB; border-radius: 6px; padding: 12px; background: #F9FAFB;">
                @if (!empty($invoice->bank))
                    <strong style="font-size: 10pt; color: #111827;">{{ $invoice->bank->bank ?? '' }}</strong><br>
                    <span style="font-size: 9pt; color: #374151;">Atas Nama: {{ $invoice->bank->atas_nama ?? '' }}</span><br>
                    <span style="font-family: monospace; font-size: 11pt; font-weight: bold; color: #1E40AF; display: inline-block; margin-top: 4px;">{{ $invoice->bank->nomor_rekening ?? '' }}</span>
                @else
                    <strong style="color: #DC2626;">Hubungi Admin Resmi untuk Rekening Pembayaran</strong>
                @endif
            </div>
        </div>

        <!-- Column 2: Financial Summary (Right side) -->
        <div style="display: table-cell; width: 50%; vertical-align: top;">
            <table style="width: 100%; border-collapse: collapse; font-size: 9pt;">
                @php
                    $mainItems = $invoice->items->where('is_addon', false);
                    $addonItems = $invoice->items->where('is_addon', true);
                    
                    $mainSubtotalGross = $mainItems->sum(function($item) {
                        return $item->jumlah * $item->harga_satuan;
                    });
                    $addonSubtotalGross = $addonItems->sum(function($item) {
                        return $item->jumlah * $item->harga_satuan;
                    });
                    $grossSubtotal = $mainSubtotalGross + $addonSubtotalGross;

                    $itemDiskonSum = (float) $invoice->items->sum('discount_amount');
                    $diskonValue = (float) $invoice->diskon_value;
                    $diskonNominal = $itemDiskonSum > 0
                        ? $itemDiskonSum
                        : ($invoice->diskon_type === 'persen'
                            ? ($grossSubtotal * $diskonValue / 100)
                            : $diskonValue);

                    $additionPayments = $invoice->order?->payments ? $invoice->order->payments->whereNotNull('verified_at')->where('payment_type', 'tambahan_produk') : collect();
                    $cashbackPayments = $invoice->order?->payments ? $invoice->order->payments->whereNotNull('verified_at')->where('payment_type', 'cashback') : collect();
                    $returnPayments = $invoice->order?->payments ? $invoice->order->payments->whereNotNull('verified_at')->where('payment_type', 'return') : collect();
                @endphp

                <!-- Total Harga -->
                <tr>
                    <td style="padding: 4px 0; color: #6B7280;">Total Harga</td>
                    <td style="padding: 4px 0; text-align: right; font-family: monospace;">Rp {{ number_format($grossSubtotal, 0, ',', '.') }}</td>
                </tr>

                <!-- Total Diskon -->
                <tr>
                    <td style="padding: 4px 0; color: #6B7280;">Total Diskon</td>
                    <td style="padding: 4px 0; text-align: right; font-family: monospace; color: #DC2626;">
                        @if ($diskonNominal > 0)
                            - Rp {{ number_format($diskonNominal, 0, ',', '.') }}
                        @else
                            Rp 0
                        @endif
                    </td>
                </tr>

                <!-- Ongkir -->
                @if ($invoice->order?->is_free_ongkir)
                    <tr>
                        <td style="padding: 4px 0; color: #6B7280;">Ongkir ({{ $invoice->jasa_pengiriman ?? 'Bebas Ongkir' }})</td>
                        <td style="padding: 4px 0; text-align: right; color: #059669; font-weight: bold;">Gratis Ongkir</td>
                    </tr>
                @elseif ($invoice->biaya_pengiriman > 0)
                    <tr>
                        <td style="padding: 4px 0; color: #6B7280;">Ongkir ({{ $invoice->jasa_pengiriman ?? '' }})</td>
                        <td style="padding: 4px 0; text-align: right; font-family: monospace;">Rp {{ number_format($invoice->biaya_pengiriman, 0, ',', '.') }}</td>
                    </tr>
                @endif

                <!-- Other Adjustments (Tambahan, Cashback, Return) -->
                @if ($additionPayments->isNotEmpty())
                    <tr>
                        <td style="padding: 4px 0; color: #6B7280;">Tambahan Produk</td>
                        <td style="padding: 4px 0; text-align: right; font-family: monospace;">+ Rp {{ number_format($additionPayments->sum('amount'), 0, ',', '.') }}</td>
                    </tr>
                @endif

                @if ($cashbackPayments->isNotEmpty())
                    <tr>
                        <td style="padding: 4px 0; color: #6B7280;">Cashback</td>
                        <td style="padding: 4px 0; text-align: right; font-family: monospace; color: #DC2626;">- Rp {{ number_format($cashbackPayments->sum('amount'), 0, ',', '.') }}</td>
                    </tr>
                @endif

                @if ($returnPayments->isNotEmpty())
                    <tr>
                        <td style="padding: 4px 0; color: #6B7280;">Returns / Refunds</td>
                        <td style="padding: 4px 0; text-align: right; font-family: monospace; color: #DC2626;">- Rp {{ number_format($returnPayments->sum('amount'), 0, ',', '.') }}</td>
                    </tr>
                @endif

                <!-- Total yang Harus Dibayar -->
                <tr style="border-top: 1px solid #E5E7EB; border-bottom: 2px solid #1F2937;">
                    <td style="padding: 6px 0; font-weight: bold; color: #1F2937;">Total yang Harus Dibayar</td>
                    <td style="padding: 6px 0; text-align: right; font-family: monospace; font-weight: bold; color: #1F2937; font-size: 10.5pt;">Rp {{ number_format($invoice->total_tagihan, 0, ',', '.') }}</td>
                </tr>

                <!-- DP -->
                @php
                    $totalDPAndBayar = $invoice->total_bayar > 0 ? $invoice->total_bayar : $invoice->dp_amount;
                @endphp
                <tr>
                    <td style="padding: 4px 0; color: #6B7280;">DP</td>
                    <td style="padding: 4px 0; text-align: right; font-family: monospace; color: #059669; font-weight: bold;">- Rp {{ number_format($totalDPAndBayar, 0, ',', '.') }}</td>
                </tr>

                <!-- Sisa -->
                <tr style="border-top: 2px solid #1F2937;">
                    <td style="padding: 8px 0; font-weight: bold; font-size: 11pt; color: #111827;">SISA</td>
                    <td style="padding: 8px 0; text-align: right; font-family: monospace; font-weight: bold; font-size: 11.5pt; color: #DC2626;">Rp {{ number_format($invoice->sisa_pembayaran, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div style="display: table; width: 100%; margin-top: 25px; border-top: 1px solid #E5E7EB; padding-top: 15px;">
        <!-- Left side: Thank you note + Security advice -->
        <div style="display: table-cell; vertical-align: top; padding-right: 20px;">
            <div style="font-weight: bold; font-size: 10.5pt; color: #111827; margin-bottom: 4px;">Terima kasih atas pembayaran Anda!</div>
            @if ($invoice->peraturan)
                <div style="font-size: 8pt; color: #4B5563; line-height: 1.4; margin-bottom: 8px;">{!! \App\Support\PdfHelper::formatText($invoice->peraturan) !!}</div>
            @endif

            <div style="padding: 8px 10px; border: 1px dashed #D97706; background-color: #FEF3C7; border-radius: 6px; color: #92400E; font-size: 7.5pt; line-height: 1.3; margin-bottom: 8px;">
                <strong>⚠️ Imbauan Keamanan Pembayaran:</strong> Demi keamanan transaksi, mohon <strong>TIDAK MELAKUKAN</strong> transfer ke rekening mana pun selain rekening resmi atas nama <strong>{{ $invoice->bank ? $invoice->bank->atas_nama : ($headerBrand ? $headerBrand->nama_brand : ($invoice->brand ? $invoice->brand->getHeaderBrand()->nama_brand : 'brand kami')) }}</strong>.
            </div>

            <div style="font-size: 7.5pt; color: #6B7280; line-height: 1.3;">
                <strong>Cara Cek Pesanan:</strong> Kunjungi link <strong>/track/{{ $invoice->order?->no_po ?? '' }}</strong> atau scan QR code di samping untuk memantau status pesanan secara langsung.
            </div>
        </div>

        <!-- Right side: QR Code -->
        @if (!empty($qrCodeData))
            <div style="display: table-cell; width: 110px; text-align: center; vertical-align: top;">
                <img src="{{ $qrCodeData }}" alt="QR Code" style="width: 90px; height: 90px; display: block; margin: 0 auto;">
                <div style="font-size: 7.5pt; color: #4B5563; margin-top: 6px; font-weight: bold; line-height: 1.2;">
                    Scan QR untuk cek pesanan
                </div>
            </div>
        @endif
    </div>

    <div style="margin-top: 20px; padding-top: 10px; border-top: 1px solid #F3F4F6; font-size: 7.5pt; color: #9CA3AF;">
        <span style="font-weight: bold; color: #4B5563;">Ketentuan Lainnya:</span>
        <span style="margin-left: 10px;">• Bukti pembayaran wajib dikirimkan ke WhatsApp admin.</span>
        <span style="margin-left: 10px;">• Batas waktu komplain produk maksimal 3x24 jam sejak barang diterima.</span>
    </div>
</body>
</html>
