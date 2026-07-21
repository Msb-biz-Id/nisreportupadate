@php
$primaryColor = $invoice->brand?->warna_primary
    ?? \App\Models\Settings\SystemSetting::get('system', 'theme_color', '#a8001c');
@endphp
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

        @font-face {
            font-family: 'Noto Sans Javanese';
            font-style: normal;
            font-weight: 400;
            src: url('{{ public_path("fonts/NotoSansJavanese-Regular.ttf") }}') format('truetype');
        }

        .cjk-font {
            font-family: 'Noto Sans JP', sans-serif !important;
            text-transform: none !important;
        }

        .javanese-font {
            font-family: 'Noto Sans Javanese', sans-serif !important;
            text-transform: none !important;
        }

        .arabic-font {
            font-family: 'Noto Sans Arabic', sans-serif !important;
            text-transform: none !important;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9.5pt;
            color: #1F2937;
            margin: 0;
        }

        @page {
            margin: 14mm;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 18px;
        }

        .header>div {
            display: table-cell;
            vertical-align: top;
        }

        .brand {
            color: #000000;
            font-weight: 900;
            font-size: 20pt;
            letter-spacing: -0.5px;
        }

        .brand-tagline {
            font-size: 9.5pt;
            color: #000000;
            font-weight: 700;
        }

        .invoice-title {
            font-size: 18pt;
            font-weight: 900;
            color: #000000;
            text-align: right;
            letter-spacing: -0.5px;
        }

        .invoice-no {
            font-family: monospace;
            text-align: right;
            font-size: 10pt;
            color: #111827;
            font-weight: 700;
            margin-top: -6px;
        }

        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 16px;
            table-layout: fixed;
        }

        .info-grid>div {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 12px;
            word-wrap: break-word;
            word-break: break-all;
        }

        .info-label {
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6B7280;
            margin-bottom: 4px;
        }

        .info-card {
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            padding: 10px;
            word-wrap: break-word;
            word-break: break-all;
        }

        .info-card strong {
            font-size: 10pt;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        table.items th {
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 9pt;
        }

        table.items td {
            border-bottom: 1px solid #E5E7EB;
            padding: 7px 8px;
        }

        table.items .right {
            text-align: right;
        }

        .text-green {
            color: #059669;
        }

        .text-red {
            color: #dc2626;
        }

        .totals {
            width: 280px;
            margin-left: auto;
            margin-top: 8px;
        }

        .totals table {
            width: 100%;
        }

        .totals td {
            padding: 4px 8px;
        }

        .totals tr.grand td {
            font-weight: 700;
            font-size: 12pt;
            border-top: 2px solid #1F2937;
            padding-top: 8px;
        }

        .totals .label {
            color: #6B7280;
        }

        .totals .value {
            text-align: right;
            font-family: monospace;
        }

        .qr-section {
            display: table;
            width: 100%;
            margin-top: 18px;
            padding: 12px;
            background: #F8FAFC;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
        }

        .qr-section>div {
            display: table-cell;
            vertical-align: middle;
        }

        .qr-image {
            width: 90px;
        }

        .footer {
            margin-top: 24px;
            padding-top: 12px;
            border-top: 1px solid #E5E7EB;
            font-size: 8pt;
            color: #6B7280;
        }

        .footer strong {
            color: #1F2937;
        }

        .faq {
            margin-top: 8px;
            font-size: 7.5pt;
        }
    </style>
    {!! '<' . 'style>table.items th { background-color: ' . $primaryColor . '; }</' . 'style>' !!}
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
        if (empty($email)) return '—' ;
        $parts=explode('@', $email);
        if (count($parts) !==2) return $email;
        $local=$parts[0];
        $domain=$parts[1];
        $len=strlen($local);
        if ($len <=2) return $local[0] . '***@' . $domain;
        return substr($local, 0, 2) . str_repeat('*', $len - 4) . substr($local, -2) . '@' . $domain;
        }
        }

        if (!function_exists('maskDetailAlamat')) {
        function maskDetailAlamat($address) {
        if (empty($address)) return '' ;
        $len=strlen($address);
        if ($len <=8) return str_repeat('*', $len);
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
                    @if($invoice->order?->is_free_ongkir)
                    <br><span style="font-size: 8pt; color: #059669; font-weight: bold;">Status: Free Ongkir</span>
                    @endif
                </div>
            </div>
        </div>

        @php $primaryColor = $invoice->brand?->warna_primary ?? \App\Models\Settings\SystemSetting::get('system', 'theme_color', '#a8001c'); @endphp
        <table class="items">
            <thead>
                <tr>
                    <th width="40">No</th>
                    <th>Produk</th>
                    <th class="right" width="80">Qty</th>
                    <th class="right" width="120">Harga Satuan</th>
                    <th class="right" width="140">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @php
                $primaryColor = $invoice->brand?->warna_primary
                    ?? \App\Models\Settings\SystemSetting::get('system', 'theme_color', '#a8001c');
                $mainItems = $invoice->items->where('is_addon', false);
                $addonItems = $invoice->items->where('is_addon', true);
                $mainSubtotal = $mainItems->sum('subtotal');
                $addonSubtotal = $addonItems->sum('subtotal');
                $rowNum = 1;
                @endphp

                @if($mainItems->isNotEmpty())
                @php
                $mainOrderItems = $invoice->order ? $invoice->order->items->where('is_addon', false)->values() : collect();
                @endphp
                <tr style="background-color: #f9fafb; font-weight: bold;">
                    <td colspan="5" style="padding: 4px 8px; font-size: 9pt;">PRODUK INTI</td>
                </tr>
                @foreach ($mainItems as $index => $item)
                @php
                $orderItem = $mainOrderItems->get($index);
                @endphp
                <tr>
                    <td>{{ $rowNum++ }}</td>
                    <td>
                        <div>{!! \App\Support\PdfHelper::formatText($item->produk) !!}</div>
                        @if($orderItem && $orderItem->bahan_formatted)
                        <div style="font-size: 8pt; color: #4B5563; margin-top: 2px;">
                            Bahan: {{ $orderItem->bahan_formatted }}
                        </div>
                        @endif
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
                @php
                $addonOrderItems = $invoice->order ? $invoice->order->items->where('is_addon', true)->values() : collect();
                @endphp
                <tr style="background-color: #f9fafb; font-weight: bold;">
                    <td colspan="5" style="padding: 4px 8px; font-size: 9pt;">ADD-ON</td>
                </tr>
                @foreach ($addonItems as $index => $item)
                @php
                $orderItem = $addonOrderItems->get($index);
                @endphp
                <tr>
                    <td>{{ $rowNum++ }}</td>
                    <td>
                        <div>{!! \App\Support\PdfHelper::formatText($item->produk) !!}</div>
                        @if($orderItem && $orderItem->bahan_formatted)
                        <div style="font-size: 8pt; color: #4B5563; margin-top: 2px;">
                            Bahan: {{ $orderItem->bahan_formatted }}
                        </div>
                        @endif
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

        @php
            $allPayments = $invoice->order?->payments ? $invoice->order->payments->whereNotNull('verified_at')->sortBy('payment_date') : collect();
        @endphp

        @if ($allPayments->isNotEmpty())
        <div style="margin-top: 15px; margin-bottom: 15px; page-break-inside: avoid;">
            <div style="font-size: 7.5pt; font-weight: bold; color: #6b7280; text-transform: uppercase; margin-bottom: 5px; letter-spacing: 0.05em;">Riwayat Pembayaran & Penyesuaian (Verified)</div>
            <table style="width: 100%; border-collapse: collapse; font-size: 8.5pt; border: 1px solid #e5e7eb;">
                <thead>
                    <tr style="background-color: #f3f4f6; border-bottom: 1px solid #e5e7eb;">
                        <th style="padding: 6px 8px; text-align: left; color: #4b5563; font-weight: bold; width: 15%;">Tanggal</th>
                        <th style="padding: 6px 8px; text-align: left; color: #4b5563; font-weight: bold; width: 45%;">Deskripsi / Jenis Transaksi</th>
                        <th style="padding: 6px 8px; text-align: left; color: #4b5563; font-weight: bold; width: 25%;">Tujuan Mutasi Bank</th>
                        <th style="padding: 6px 8px; text-align: right; color: #4b5563; font-weight: bold; width: 15%;">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($allPayments as $p)
                        @php
                            $isDebit = in_array($p->payment_type, ['dp', 'pelunasan', 'tambahan_produk', 'ongkir']);
                            $displayType = strtoupper($p->payment_type);
                            if ($p->payment_type === 'dp') {
                                $displayType = 'DP SEQUENCE (DP #' . ($p->dp_sequence ?? '1') . ')';
                            } elseif ($p->payment_type === 'tambahan_produk') {
                                $displayType = 'TAMBAHAN PRODUK';
                            } elseif (in_array($p->payment_type, ['return', 'refurn', 'refund'])) {
                                $displayType = 'REFUND';
                            }
                        @endphp
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 6px 8px; color: #4b5563; white-space: nowrap;">{{ $p->payment_date ? \Carbon\Carbon::parse($p->payment_date)->translatedFormat('d M Y') : '' }}</td>
                            <td style="padding: 6px 8px;">
                                <strong style="color: #1f2937;">{{ $displayType }}</strong>
                                @if ($p->notes)
                                    <span style="display: block; font-size: 7.5pt; color: #6b7280; margin-top: 2px;">{!! nl2br(e($p->notes)) !!}</span>
                                @endif
                            </td>
                            <td style="padding: 6px 8px; color: #4b5563;">
                                @if ($p->customer_bank_name || $p->customer_bank_account)
                                    <div style="font-weight: bold; color: #1f2937;">{{ $p->customer_bank_name ?? '—' }}</div>
                                    <div style="font-size: 7.5pt; color: #6b7280; font-family: monospace;">{{ $p->customer_bank_account ?? '—' }}</div>
                                @elseif ($p->bank)
                                    {{ $p->bank->bank }} — {{ $p->bank->nomor_rekening }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="{{ $isDebit ? 'text-green' : 'text-red' }}" style="padding: 6px 8px; text-align: right; font-family: monospace; font-weight: bold; white-space: nowrap;">
                                {{ $isDebit ? '+' : '-' }} Rp {{ number_format($p->amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif        @php
            $payments = $invoice->order && $invoice->order->payments ? $invoice->order->payments->whereNotNull('verified_at') : collect();

            $isDeductionPayment = function ($p) {
                if ($p->is_debit !== null) {
                    return !((bool) $p->is_debit);
                }
                return in_array($p->payment_type, ['cashback', 'return', 'refund']) ||
                    in_array($p->masterJenisPembayaran?->nama, ['Cashback', 'Refund', 'Return', 'Refurn']);
            };

            $isPenambahanPayment = function ($p) {
                if ($p->payment_type === 'ongkir') return false;
                if ($p->masterJenisPembayaran?->efek_tagihan === 'penambahan') return true;
                if ($p->master_jenis_pembayaran_id === null && $p->payment_type === 'tambahan_produk') return true;
                return false;
            };

            $cashbackSum = $payments
                ->filter(fn($p) => $p->payment_type === 'cashback' || $p->masterJenisPembayaran?->nama === 'Cashback')
                ->sum('amount');

            $returnSum = $payments
                ->filter(fn($p) => in_array($p->payment_type, ['return', 'refund']) || in_array($p->masterJenisPembayaran?->nama, ['Refund', 'Return', 'Refurn']))
                ->sum('amount');

            $additionPayments = $payments->filter(fn($p) => $isPenambahanPayment($p));
            $additionSum = $additionPayments->sum('amount');

            $totalReceived = $payments->filter(fn($p) => !$isDeductionPayment($p))->sum('amount');
            $totalRefunded = $returnSum + $cashbackSum;
        @endphp

        <div style="display: table; width: 100%; margin-top: 15px;">
            <!-- Column 1: Bank Transfer Details (Left side) -->
            <div style="display: table-cell; width: 50%; vertical-align: top; padding-right: 20px;">
                @if (!empty($invoice->bank) && $invoice->bank->bank === 'CASH')
                <div class="info-label" style="font-size: 7.5pt; color: #6B7280; text-transform: uppercase; margin-bottom: 5px;">Metode Pembayaran Resmi</div>
                <div style="border: 1px solid #E5E7EB; border-radius: 6px; padding: 12px; background: #F9FAFB;">
                    <strong style="font-size: 10pt; color: #111827;">TUNAI / CASH</strong><br>
                    <span style="font-size: 9pt; color: #374151;">Pembayaran secara tunai langsung ke kasir atau outlet resmi.</span>
                </div>
                @else
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
                @endif
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

                    $isSpecial = $invoice->order && (bool) $invoice->order->is_special_order;
                    $itemDiskonSum = (float) $invoice->items->sum('discount_amount');
                    $diskonValue = (float) $invoice->diskon_value;
                    $diskonNominal = $isSpecial
                        ? $grossSubtotal
                        : ($itemDiskonSum > 0
                            ? $itemDiskonSum
                            : ($invoice->diskon_type === 'persen'
                                ? ($grossSubtotal * $diskonValue / 100)
                                : $diskonValue));

                    $grossInvoiceTotal = (float) $invoice->total_tagihan;
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
                        <td style="padding: 4px 0; color: #6B7280;">Ongkir{{ $invoice->jasa_pengiriman ? ' (' . $invoice->jasa_pengiriman . ')' : '' }}</td>
                        <td style="padding: 4px 0; text-align: right; color: #059669; font-weight: bold;">Gratis Ongkir</td>
                    </tr>
                    @elseif ($invoice->biaya_pengiriman > 0)
                    <tr>
                        <td style="padding: 4px 0; color: #6B7280;">Ongkir{{ $invoice->jasa_pengiriman ? ' (' . $invoice->jasa_pengiriman . ')' : '' }}</td>
                        <td style="padding: 4px 0; text-align: right; font-family: monospace;">Rp {{ number_format((float) $invoice->biaya_pengiriman, 0, ',', '.') }}</td>
                    </tr>
                    @endif

                    <!-- Other Adjustments (Tambahan, Cashback, Return) -->
                    @if ($additionPayments->isNotEmpty())
                    <tr>
                        <td style="padding: 4px 0; color: #6B7280;">Tambahan Produk</td>
                        <td style="padding: 4px 0; text-align: right; font-family: monospace;">+ Rp {{ number_format($additionPayments->sum('amount'), 0, ',', '.') }}</td>
                    </tr>
                    @endif

                    <!-- Total yang Harus Dibayar -->
                    <tr style="border-top: 1px solid #E5E7EB; border-bottom: 2px solid #1F2937;">
                        <td style="padding: 6px 0; font-weight: bold; color: #1F2937;">Total yang Harus Dibayar</td>
                        <td style="padding: 6px 0; text-align: right; font-family: monospace; font-weight: bold; color: #1F2937; font-size: 10.5pt;">Rp {{ number_format($grossInvoiceTotal, 0, ',', '.') }}</td>
                    </tr>

                    @if ($totalReceived > 0)
                    <tr>
                        <td style="padding: 4px 0; color: #6B7280;">Total Terbayar</td>
                        <td style="padding: 4px 0; text-align: right; font-family: monospace; color: #1F2937; font-weight: bold;">Rp {{ number_format($totalReceived, 0, ',', '.') }}</td>
                    </tr>
                    @endif

                    @if ($returnSum > 0)
                    <tr>
                        <td style="padding: 4px 0; color: #6B7280;">Refund</td>
                        <td style="padding: 4px 0; text-align: right; font-family: monospace; color: #DC2626;">- Rp {{ number_format($returnSum, 0, ',', '.') }}</td>
                    </tr>
                    @endif

                    @if ($cashbackSum > 0)
                    <tr>
                        <td style="padding: 4px 0; color: #6B7280;">Cashback</td>
                        <td style="padding: 4px 0; text-align: right; font-family: monospace; color: #DC2626;">- Rp {{ number_format($cashbackSum, 0, ',', '.') }}</td>
                    </tr>
                    @endif

                    @if ($returnSum > 0 || $cashbackSum > 0)
                    <tr>
                        <td style="padding: 4px 0; font-weight: bold; color: #059669;">Neto Pembayaran</td>
                        <td style="padding: 4px 0; text-align: right; font-family: monospace; color: #059669; font-weight: bold;">Rp {{ number_format($totalReceived - $returnSum - $cashbackSum, 0, ',', '.') }}</td>
                    </tr>
                    @endif

                    <!-- Sisa -->
                    <tr style="border-top: 2px solid #1F2937;">
                        <td style="padding: 8px 0; font-weight: bold; font-size: 11pt; color: #111827;">SISA</td>
                        <td style="padding: 8px 0; text-align: right; font-family: monospace; font-weight: bold; font-size: 11.5pt; color: #DC2626;">Rp {{ number_format(max(0, $grossInvoiceTotal - ($totalReceived - $returnSum - $cashbackSum)), 0, ',', '.') }}</td>
                    </tr>
                </table>

                @if ($returnSum > 0)
                <div style="margin-top: 10px; border: 1px solid #FECACA; border-radius: 6px; padding: 8px 10px; background: #FEF2F2; font-size: 8.5pt;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 8.5pt; margin: 0;">
                        <tr>
                            <td style="font-weight: bold; color: #991B1B; padding: 0;">Informasi Refund</td>
                            <td style="text-align: right; font-family: monospace; font-weight: bold; color: #DC2626; font-size: 9.5pt; padding: 0;">Rp {{ number_format($returnSum, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>
                @endif

                @if ($cashbackSum > 0)
                <div style="margin-top: 10px; border: 1px solid #FDE68A; border-radius: 6px; padding: 8px 10px; background: #FEF3C7; font-size: 8.5pt;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 8.5pt; margin: 0;">
                        <tr>
                            <td style="font-weight: bold; color: #92400E; padding: 0;">Informasi Cashback</td>
                            <td style="text-align: right; font-family: monospace; font-weight: bold; color: #B45309; font-size: 9.5pt; padding: 0;">Rp {{ number_format($cashbackSum, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>
                @endif
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
                    @if (!empty($invoice->bank) && $invoice->bank->bank === 'CASH')
                    <strong>⚠️ Imbauan Keamanan Pembayaran:</strong> Demi keamanan transaksi, mohon lakukan pembayaran tunai secara langsung hanya melalui kasir atau sales resmi brand kami. Jangan melakukan transfer ke rekening perorangan/rekening lain yang tidak terdaftar secara resmi.
                    @else
                    <strong>⚠️ Imbauan Keamanan Pembayaran:</strong> Demi keamanan transaksi, mohon <strong>TIDAK MELAKUKAN</strong> transfer ke rekening mana pun selain rekening resmi atas nama <strong>{{ $invoice->bank ? $invoice->bank->atas_nama : ($headerBrand ? $headerBrand->nama_brand : ($invoice->brand ? $invoice->brand->getHeaderBrand()->nama_brand : 'brand kami')) }}</strong>.
                    @endif
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

        <div style="margin-top: 20px; padding-top: 10px; border-top: 1px solid #F3F4F6; font-size: 7.5pt; color: #374151; line-height: 1.4;">
            <div style="font-weight: bold; color: #1F2937; margin-bottom: 4px;">Ketentuan Lainnya:</div>
            <div style="margin-left: 5px; font-weight: bold; margin-bottom: 2px;">1. Bukti pembayaran wajib dikirimkan ke WhatsApp Admin untuk proses verifikasi.</div>
            <div style="margin-left: 5px; font-weight: bold; margin-bottom: 2px;">2. Mohon lakukan video unboxing saat paket diterima sebagai bukti apabila terjadi kendala pada produk.</div>
            <div style="margin-left: 5px; font-weight: bold;">3. Pengajuan komplain maksimal 3 × 24 jam sejak barang diterima dan wajib disertai bukti berupa video unboxing serta foto produk.</div>
        </div>
</body>

</html>