<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SPK {{ $order->no_po }}</title>
    <style>
        /* === CSS TEMPLATE DASAR PDF A4 (Polished) === */
        * { box-sizing: border-box; }
        @page { margin: 25mm 15mm 20mm 15mm; }

        header { position: fixed; top: -18mm; left: 0; right: 0; height: 12mm;
                 text-align: center; font-size: 14pt; font-weight: bold;
                 text-decoration: underline; border-bottom: 3px solid #000; padding-bottom: 5px; text-transform: uppercase; }
        footer { position: fixed; bottom: -15mm; left: 0; right: 0; height: 10mm;
                 border-top: 2px solid #000; padding-top: 5px; }
        main   { margin-top: 5mm; margin-bottom: 5mm; }

        body { font-family: 'DejaVu Sans', 'Helvetica', sans-serif;
               font-size: 10pt; color: #000; line-height: 1.35; text-transform: uppercase; margin: 0; padding: 0; }

        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 5px; vertical-align: top; }

        /* Title boxes */
        .title-box        { font-weight: bold; font-size: 10.5pt; background: #d4d4d4; color: #000;
                            padding: 6px 10px; text-align: left;
                            border: 1px solid #000; border-bottom: none; text-transform: uppercase; }
        .title-box-center { font-weight: bold; font-size: 10.5pt; background: #d4d4d4; color: #000;
                            padding: 6px 10px; text-align: center;
                            border: 1px solid #000; border-bottom: none; text-transform: uppercase; }

        /* Product band */
        .product-band { background: #000; color: #fff; text-align: center; font-size: 11pt;
                        font-weight: bold; padding: 6px 10px; margin: 10px 0 0; letter-spacing: 0.5px; }

        /* Spec table */
        .spec-table { border: 1px solid #000; margin-bottom: 5px; width: 100%; }
        .spec-table th, .spec-table td { border: 1px solid #000; padding: 5px 6px; font-size: 9.5pt; text-align: center; font-weight: normal; }
        .spec-table th { background: #d4d4d4; font-weight: bold; }
        .spec-row-head { width: 25%; text-align: left !important; background: #f2f2f2; font-weight: bold; }

        /* Nameset table */
        .ns-table { border: 1px solid #000; margin-bottom: 5px; }
        .ns-table th, .ns-table td { border: 1px solid #000; padding: 5px 6px; font-size: 9.5pt; text-align: center; font-weight: normal; word-wrap: break-word; word-break: break-all; white-space: normal; }
        .ns-table-dense th, .ns-table-dense td { font-size: 7.5pt !important; padding: 3px 4px !important; }
        .ns-table th { background: #d4d4d4; font-size: 9.5pt; font-weight: bold; }
        .t-left { text-align: left !important; padding-left: 6px !important; }

        /* Rekap size */
        .rekap-container { margin-top: 12px; font-size: 10pt; font-weight: bold; text-align: center; }
        .rekap-tabel { border: 2px solid #000; width: auto; margin: 6px auto 0; }
        .rekap-tabel td { border: 1px solid #000; padding: 5px 12px; text-align: center; font-size: 9.5pt; font-weight: normal; }
        .rekap-tabel th { border: 1px solid #000; padding: 5px 12px; text-align: center; font-size: 9.5pt; background: #d4d4d4; font-weight: bold; }

        /* Image wrapper */
        .img-wrapper { border: 2px solid #000; padding: 6px; margin: 0 0 10px; background: #fff; }
        .img-box { text-align: center; border: 1px solid #000; padding: 4px; margin-bottom: 5px; background: #fff; }
        .img-box img { max-width: 100%; object-fit: contain; }

        /* Misc */
        .page-break   { page-break-before: always; }
        .page-num:after { content: counter(page); }
        .lampiran-sub { font-weight: bold; font-size: 9.5pt; margin-bottom: 4px; }

        @media screen {
            body {
                background: #f3f4f6;
                padding: 80px 15px 40px;
                font-family: 'Inter', system-ui, -apple-system, sans-serif;
                margin: 0;
            }
            .web-toolbar {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: 60px;
                background: #1f2937;
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0 24px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                z-index: 9999;
                font-family: system-ui, -apple-system, sans-serif;
            }
            .toolbar-title {
                font-weight: 600;
                font-size: 15px;
                letter-spacing: 0.5px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .toolbar-actions {
                display: flex;
                gap: 12px;
            }
            .btn {
                padding: 8px 16px;
                border-radius: 6px;
                font-size: 13px;
                font-weight: 500;
                cursor: pointer;
                border: none;
                transition: all 0.2s;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }
            .btn-primary {
                background: #3b82f6;
                color: #fff;
            }
            .btn-primary:hover {
                background: #2563eb;
            }
            .btn-secondary {
                background: #4b5563;
                color: #fff;
            }
            .btn-secondary:hover {
                background: #374151;
            }
            .btn-danger {
                background: #ef4444;
                color: #fff;
            }
            .btn-danger:hover {
                background: #dc2626;
            }
            .paper-container {
                background: #fff;
                max-width: 210mm;
                min-height: 297mm;
                margin: 0 auto;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                padding: 20mm 15mm;
                border: 1px solid #e5e7eb;
                position: relative;
            }
            header {
                position: absolute;
                top: 5mm;
                left: 15mm;
                right: 15mm;
                border-bottom: 3px solid #000;
            }
            footer {
                position: absolute;
                bottom: 5mm;
                left: 15mm;
                right: 15mm;
                border-top: 2px solid #000;
            }
            main {
                margin-top: 5mm;
                margin-bottom: 5mm;
            }
            .page-break {
                margin-top: 40px;
                border-top: 2px dashed #9ca3af;
                padding-top: 40px;
                position: relative;
            }
            .page-break::before {
                content: "BATAS HALAMAN CETAK";
                position: absolute;
                top: -12px;
                left: 50%;
                transform: translateX(-50%);
                background: #fff;
                padding: 0 10px;
                font-size: 10px;
                color: #9ca3af;
                font-weight: 600;
            }
        }
        @media print {
            .web-toolbar {
                display: none !important;
            }
            body {
                background: #fff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .paper-container {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                min-height: 0 !important;
            }
        }
    </style>
</head>
<body>
    @if(isset($isWebPreview) && $isWebPreview)
        <div class="web-toolbar">
            <div class="toolbar-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                PREVIEW SPK: {{ $order->no_po }}
            </div>
            <div class="toolbar-actions">
                <button onclick="window.print()" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                    Cetak / Simpan PDF
                </button>
                <a href="{{ route('orders.spk.pdf', $order->id) }}" class="btn btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                    Unduh PDF
                </a>
                <button onclick="window.close()" class="btn btn-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><path d="m15 9-6 6M9 9l6 6"/></svg>
                    Tutup
                </button>
            </div>
        </div>
        <div class="paper-container">
    @endif

    <header>FORMAT ORDER {{ strtoupper($order->brand->nama_brand ?? 'BRAND') }}</header>

    <footer>
        <table style="width:100%; border:none; font-size:8.5pt; font-weight:bold;">
            <tr>
                <td style="text-align:left; width:40%;">HALAMAN <span class="page-num"></span></td>
                <td style="text-align:right; width:60%; color:#b91c1c;">NAMA ORDER: {{ strtoupper($order->nama_po) }} · {{ $order->no_po }}</td>
            </tr>
        </table>
    </footer>

    <main>

        {{-- ===== TOP DETAILS SECTION ===== --}}
        @php
            $nonAddonItems = $order->items->filter(fn($i) => empty($i->is_addon))->values();
            $addonItems = $order->items->filter(fn($i) => !empty($i->is_addon))->values();
            $grandTotal = $nonAddonItems->sum('quantity');
            $printingNames = collect();
            if (!empty($order->printing_ids)) {
                $printingNames = \App\Models\Master\Printing::whereIn('id', $order->printing_ids)->pluck('nama');
            }
            $printingStr = $printingNames->isNotEmpty() ? $printingNames->implode(', ') : '.......';
        @endphp

        <table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
            <tr>
                <!-- Left Side: Order Info -->
                <td style="width: 60%; padding: 0; vertical-align: top;">
                    <table style="width: 100%; border: none;">
                        <tr>
                            <td style="width: 160px; font-weight: bold; font-size: 10.5pt; padding: 3px 0;">TANGGAL MASUK</td>
                            <td style="width: 15px; font-weight: bold; padding: 3px 0;">:</td>
                            <td style="font-size: 10.5pt; padding: 3px 0; font-weight: bold;">{{ strtoupper(\Carbon\Carbon::parse($order->tanggal_masuk)->translatedFormat('d F Y')) }}</td>
                        </tr>
                        <tr style="color: red;">
                            <td style="font-weight: bold; font-size: 10.5pt; padding: 3px 0; color: red;">DATELINE</td>
                            <td style="font-weight: bold; padding: 3px 0; color: red;">:</td>
                            <td style="font-size: 10.5pt; padding: 3px 0; font-weight: bold; color: red;">{{ strtoupper(\Carbon\Carbon::parse($order->deadline_customer)->translatedFormat('d F Y')) }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; font-size: 10.5pt; padding: 3px 0;">NAMA ORDER</td>
                            <td style="font-weight: bold; padding: 3px 0;">:</td>
                            <td style="font-size: 10.5pt; padding: 3px 0; font-weight: bold;">{{ strtoupper($order->nama_po) }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; font-size: 10.5pt; padding: 3px 0;">TOTAL ATASAN</td>
                            <td style="font-weight: bold; padding: 3px 0;">:</td>
                            <td style="font-size: 10.5pt; padding: 3px 0; font-weight: bold;">{{ $order->items->sum('jml_atasan') ?: $grandTotal }} PCS</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; font-size: 10.5pt; padding: 3px 0;">TOTAL BAWAHAN</td>
                            <td style="font-weight: bold; padding: 3px 0;">:</td>
                            <td style="font-size: 10.5pt; padding: 3px 0; font-weight: bold;">{{ $order->items->sum('jml_bawahan') ?: '.......' }} PCS</td>
                        </tr>
                    </table>
                </td>
                <!-- Right Side: Brand & Paket Box -->                 <td style="width: 40%; padding: 0 0 0 15px; vertical-align: top; text-align: center;">
                    <div style="border: 2px solid #000; padding: 12px 10px; min-height: 110px; background: #fff; text-align: center;">
                        <div style="font-size: 15pt; font-weight: 900; line-height: 1.2;">
                            {{ strtoupper($order->brand->nama_brand ?? 'BRAND') }}
                        </div>
                        <div style="font-size: 12pt; font-weight: 700; color: red; margin-top: 3px;">
                            ({{ $order->is_special_order ? 'SPECIAL' : 'NORMAL' }})
                        </div>
                        <div style="font-size: 11pt; font-weight: bold; margin-top: 10px; border-top: 1px solid #000; padding-top: 5px; line-height: 1.2;">
                            JENIS PRINTING:<br>
                            <span style="font-size: 12pt; font-weight: 900; color: red;">{{ strtoupper($printingStr) }}</span>
                        </div>
                        @if($order->paketOrder)
                            <div style="font-size: 11pt; font-weight: bold; margin-top: 10px; border-top: 1px solid #000; padding-top: 5px; line-height: 1.2;">
                                PAKET:<br>
                                <span style="font-size: 12.5pt; font-weight: 900;">{{ strtoupper($order->paketOrder->nama) }}</span>
                            </div>
                        @endif
                    </div>
                </td>>
            </tr>
        </table>

        @if($order->catatan)
        <div class="title-box" style="margin-top:0;">CATATAN ORDER</div>
        <div style="border:1px solid #000; padding:6px 10px; font-weight:bold; margin-bottom:15px; font-size: 9.5pt;">
            {{ strtoupper($order->catatan) }}
        </div>
        @endif

        {{-- ===== KETERANGAN MATERIAL (SIDE-BY-SIDE SPEC TABLE) ===== --}}
        @if($nonAddonItems->isNotEmpty())
        <div style="color: red; font-weight: bold; font-size: 11pt; margin-bottom: 5px; text-transform: uppercase;">KETERANGAN MATERIAL</div>
        <table class="spec-table" style="margin-top:0;">
            <thead>
                <tr>
                    <th style="width: 25%; text-align: left;">JENIS PESANAN</th>
                    @foreach($nonAddonItems as $item)
                    <th>{{ strtoupper($item->varian_label ?: $item->nama_produk) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="spec-row-head">BAHAN</td>
                    @foreach($nonAddonItems as $item)
                    @php
                        $bahanAtasanStr = !empty($item->bahan_kain_ids)
                            ? \App\Models\Master\BahanKain::whereIn('id', $item->bahan_kain_ids)->pluck('nama')->implode(', ')
                            : ($item->bahanKain->nama ?? '.......');
                    @endphp
                    <td>{{ strtoupper($bahanAtasanStr) }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td class="spec-row-head">JUMLAH ATASAN</td>
                    @foreach($nonAddonItems as $item)
                    <td>{{ $item->jml_atasan ?: $item->quantity }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td class="spec-row-head">JUMLAH BAWAHAN</td>
                    @foreach($nonAddonItems as $item)
                    <td>{{ $item->jml_bawahan ?: '.......' }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td class="spec-row-head">WARNA</td>
                    @foreach($nonAddonItems as $item)
                    <td>{{ strtoupper($item->warna ?? '') ?: '.......' }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td class="spec-row-head">JENIS LOGO</td>
                    @foreach($nonAddonItems as $item)
                    @php
                        $itemLogos = collect();
                        if (!empty($item->logo_ids)) {
                            $itemLogos = \App\Models\Master\Logo::whereIn('id', $item->logo_ids)->pluck('nama');
                        } elseif ($item->logo_id) {
                            $itemLogos = collect([$item->logo?->nama])->filter();
                        }
                        $logoStr = $itemLogos->isNotEmpty() ? $itemLogos->implode(', ') : '.......';
                    @endphp
                    <td>{{ strtoupper($logoStr) }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td class="spec-row-head">JENIS RIB</td>
                    @foreach($nonAddonItems as $item)
                    <td>{{ strtoupper($item->jenis_rib ?? '') ?: '.......' }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td class="spec-row-head">LIST KERAH</td>
                    @foreach($nonAddonItems as $item)
                    <td>{{ strtoupper($item->list_kerah ?? '') ?: '.......' }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td class="spec-row-head">LIST LENGAN</td>
                    @foreach($nonAddonItems as $item)
                    <td>{{ strtoupper($item->list_lengan ?? '') ?: '.......' }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td class="spec-row-head">LIST SAMPING CELANA</td>
                    @foreach($nonAddonItems as $item)
                    <td>{{ strtoupper($item->list_samping_celana ?? '') ?: '.......' }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td class="spec-row-head">LIST BAWAH CELANA</td>
                    @foreach($nonAddonItems as $item)
                    <td>{{ strtoupper($item->list_bawah_celana ?? '') ?: '.......' }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td class="spec-row-head">TUTUP KERAH</td>
                    @foreach($nonAddonItems as $item)
                    <td>{{ strtoupper($item->tutup_kerah ?? '') ?: '.......' }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
        @endif

        {{-- ===== KETERANGAN JAHITAN ===== --}}
        @if($nonAddonItems->isNotEmpty())
        <div style="color: #b91c1c; font-weight: bold; font-size: 11pt; margin-top: 15px; margin-bottom: 5px; text-transform: uppercase;">KETERANGAN JAHITAN</div>
        <table style="width:100%; border:1px solid #000; border-collapse:collapse; font-size:9.5pt; margin-bottom:15px;">
            <thead>
                <tr style="background:#d4d4d4; font-weight:bold;">
                    <th style="border:1px solid #000; padding:5px 6px; text-align:left; width:250px;">JAHITAN / DETAIL</th>
                    @foreach($nonAddonItems as $item)
                    <th style="border:1px solid #000; padding:5px 6px; text-align:center;">
                        {{ strtoupper($item->varian_label ?: $item->nama_produk) }}
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="border:1px solid #000; padding:5px 6px; font-weight:bold; text-align:left;">POLA JAHITAN</td>
                    @foreach($nonAddonItems as $item)
                    <td style="border:1px solid #000; padding:5px 6px; text-align:center;">
                        {{ $item->polaJahitan ? strtoupper($item->polaJahitan->nama) : '.......' }}
                    </td>
                    @endforeach
                </tr>
                <tr>
                    <td style="border:1px solid #000; padding:5px 6px; font-weight:bold; text-align:left;">JAHITAN LIST LENGAN</td>
                    @foreach($nonAddonItems as $item)
                    <td style="border:1px solid #000; padding:5px 6px; text-align:center;">
                        {{ $item->polaJahitanLengan ? strtoupper($item->polaJahitanLengan->nama) : (strtoupper($item->jahitan_list_lengan ?? '') ?: '.......') }}
                    </td>
                    @endforeach
                </tr>
                <tr>
                    <td style="border:1px solid #000; padding:5px 6px; font-weight:bold; text-align:left;">JENIS RESLETING</td>
                    @foreach($nonAddonItems as $item)
                    <td style="border:1px solid #000; padding:5px 6px; text-align:center;">
                        {{ strtoupper($item->resleting->nama ?? '.......') }}
                    </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
        @endif

        {{-- ===== REFERENSI DESAIN & GAMBAR (PER ITEM) ===== --}}
        @foreach ($nonAddonItems as $item)
            @if($item->gambar_desain || $item->ket_atasan || $item->ket_bawahan || $item->jenis_kerah || $item->gambar_kerah || $item->gambar_ket_tambahan)
            <div class="page-break"></div>

            <div class="img-wrapper" style="padding:6px; margin-bottom:10px;">
                <div class="title-box-center" style="margin-top:0;">
                    REFERENSI DESAIN {{ strtoupper($item->nama_produk) }} @if($item->varian_label) — {{ strtoupper($item->varian_label) }}@endif
                </div>

                @if($item->gambar_desain)
                    @php
                        $dPath = storage_path('app/public/' . $item->gambar_desain);
                        $dUrl = asset('storage/' . $item->gambar_desain);
                    @endphp
                    @if(file_exists($dPath))
                    <div class="img-box" style="border-top:none; padding:2px; margin-bottom:6px;">
                        <img src="{{ (isset($isWebPreview) && $isWebPreview) ? $dUrl : $dPath }}" style="max-width: 100%; max-height: 520px; display: block; margin: 0 auto;">
                    </div>
                    @else
                    <div class="img-box" style="border-top:none; height:120px; line-height:120px; color:#999; font-weight:bold;">[ GAMBAR DESAIN TIDAK DITEMUKAN ]</div>
                    @endif
                @else
                <div class="img-box" style="border-top:none; height:120px; line-height:120px; color:#999; font-weight:bold;">[ GAMBAR DESAIN BELUM DIUNGGAH ]</div>
                @endif

                <table style="width:100%; border-collapse:collapse; border:1px solid #000; font-size:10pt; margin-bottom:0;">
                    <tr>
                        <td style="width:50%; vertical-align:top; padding:6px; border-right:1px solid #000;">
                            <div style="background:#d4d4d4; font-weight:900; padding:4px; text-align:center; margin-bottom:4px; border:1px solid #000;">KETERANGAN ATASAN</div>
                            <div style="padding:2px; text-align:center; font-weight:bold;">{{ strtoupper($item->ket_atasan ?? '') ?: '.......' }}</div>
                        </td>
                        <td style="width:50%; vertical-align:top; padding:6px;">
                            <div style="background:#d4d4d4; font-weight:900; padding:4px; text-align:center; margin-bottom:4px; border:1px solid #000;">KETERANGAN BAWAHAN</div>
                            <div style="padding:2px; text-align:center; font-weight:bold;">{{ strtoupper($item->ket_bawahan ?? '') ?: '.......' }}</div>
                        </td>
                    </tr>
                </table>
            </div>

            @php
                $hasKerah = !empty($item->jenis_kerah) || !empty($item->gambar_kerah);
                $hasTambahan = !empty($item->gambar_ket_tambahan);
            @endphp

            @if($hasKerah || $hasTambahan)
            <table style="width: 100%; border-collapse: collapse; border: none; margin-top: 5px;">
                <tr>
                    @if($hasKerah)
                    <td style="width: {{ $hasTambahan ? '50%' : '100%' }}; padding-right: {{ $hasTambahan ? '5px' : '0' }}; border: none; vertical-align: top;">
                        <div class="img-wrapper" style="padding:6px; margin-bottom:0;">
                            <div class="title-box-center" style="margin-top:0; font-size: 9pt;">
                                JENIS KERAH: {{ strtoupper($item->jenis_kerah ?? '.......') }}
                            </div>
                            <div style="border:1px solid #000; border-top:none; padding:6px; text-align:center; background:#fff;">
                                @if($item->gambar_kerah)
                                    @php
                                        $kPath = storage_path('app/public/' . $item->gambar_kerah);
                                        $kUrl = asset('storage/' . $item->gambar_kerah);
                                    @endphp
                                    @if(file_exists($kPath))
                                    <div style="text-align:center;">
                                        <img src="{{ (isset($isWebPreview) && $isWebPreview) ? $kUrl : $kPath }}" style="max-width: 100%; max-height: 160px; display: block; margin: 0 auto;">
                                    </div>
                                    @else
                                    <div style="color:#999; font-weight:bold; font-size: 8.5pt; padding:20px 0;">[ GAMBAR KERAH TIDAK DITEMUKAN ]</div>
                                    @endif
                                @else
                                <div style="color:#999; font-weight:bold; font-size: 8.5pt; padding:20px 0;">[ GAMBAR KERAH BELUM DIUNGGAH ]</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    @endif

                    @if($hasTambahan)
                    <td style="width: {{ $hasKerah ? '50%' : '100%' }}; padding-left: {{ $hasKerah ? '5px' : '0' }}; border: none; vertical-align: top;">
                        <div class="img-wrapper" style="padding:6px; margin-bottom:0;">
                            <div class="title-box-center" style="margin-top:0; font-size: 9pt;">
                                KETERANGAN TAMBAHAN
                            </div>
                            <div style="border:1px solid #000; border-top:none; padding:6px; text-align:center; background:#fff;">
                                @php
                                    $ktPath = storage_path('app/public/' . $item->gambar_ket_tambahan);
                                    $ktUrl = asset('storage/' . $item->gambar_ket_tambahan);
                                @endphp
                                @if(file_exists($ktPath))
                                <div style="text-align:center;">
                                    <img src="{{ (isset($isWebPreview) && $isWebPreview) ? $ktUrl : $ktPath }}" style="max-width: 100%; max-height: 160px; display: block; margin: 0 auto;">
                                </div>
                                @else
                                <div style="color:#999; font-weight:bold; font-size: 8.5pt; padding:20px 0;">[ GAMBAR TIDAK DITEMUKAN ]</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    @endif
                </tr>
            </table>
            @endif
            @endif
        @endforeach

        {{-- ===== NAMESETS DATA (PER ITEM) ===== --}}
        @php
            $standarSizes = ['XS ANAK','S ANAK','M ANAK','L ANAK','XL ANAK',
                             'XS','S','M','L','XL','2XL','3XL','4XL','5XL','6XL','7XL','8XL','9XL','10XL'];
        @endphp

        @foreach ($nonAddonItems as $item)
            @php
                $filled = $item->namesets->filter(fn($ns) =>
                    !empty($ns->nama_punggung) || !empty($ns->nomor_punggung) ||
                    !empty($ns->nama_dada)     || !empty($ns->nomor_dada)     ||
                    !empty($ns->nama_lengan)   || !empty($ns->nomor_lengan)   ||
                    !empty($ns->nama_punggung_2) || !empty($ns->nomor_punggung_2) ||
                    !empty($ns->size_id)       || !empty($ns->size_label)     ||
                    !empty($ns->size_celana_id)|| !empty($ns->size_celana_label) ||
                    !empty($ns->keterangan)
                );

                $hasNamaPunggung = $filled->contains(fn($ns) => !empty($ns->nama_punggung) || !empty($ns->nomor_punggung));
                $hasNamaDada     = $filled->contains(fn($ns) => !empty($ns->nama_dada)     || !empty($ns->nomor_dada));
                $hasNamaLengan   = $filled->contains(fn($ns) => !empty($ns->nama_lengan)   || !empty($ns->nomor_lengan));
                $hasNamaPunggung2 = $filled->contains(fn($ns) => !empty($ns->nama_punggung_2) || !empty($ns->nomor_punggung_2));
                $hasSizeAtasan   = $filled->contains(fn($ns) => !empty($ns->size_id)       || !empty($ns->size_label));
                $hasSizeBawahan  = $filled->contains(fn($ns) => !empty($ns->size_celana_id)|| !empty($ns->size_celana_label));
                $hasKeterangan   = $filled->contains(fn($ns) => !empty($ns->keterangan));

                $sizeAtasanRaw = [];
                $sizeBawahanRaw = [];
                foreach ($filled as $ns) {
                    if (!empty($ns->size_id) || !empty($ns->size_label)) {
                        $sz = $ns->size ? $ns->size->ukuran : trim(last(explode('-', $ns->size_label ?? '')));
                        if ($sz) $sizeAtasanRaw[$sz] = ($sizeAtasanRaw[$sz] ?? 0) + 1;
                    }
                    if (!empty($ns->size_celana_id) || !empty($ns->size_celana_label)) {
                        $sz = $ns->sizeCelana ? $ns->sizeCelana->ukuran : trim(last(explode('-', $ns->size_celana_label ?? '')));
                        if ($sz) $sizeBawahanRaw[$sz] = ($sizeBawahanRaw[$sz] ?? 0) + 1;
                    }
                }
                
                $sizeAtasanRecap = [];
                foreach ($standarSizes as $s) { if (isset($sizeAtasanRaw[$s])) $sizeAtasanRecap[$s] = $sizeAtasanRaw[$s]; }
                foreach ($sizeAtasanRaw as $s => $c) { if (!in_array($s, $standarSizes)) $sizeAtasanRecap[$s] = $c; }

                $sizeBawahanRecap = [];
                foreach ($standarSizes as $s) { if (isset($sizeBawahanRaw[$s])) $sizeBawahanRecap[$s] = $sizeBawahanRaw[$s]; }
                foreach ($sizeBawahanRaw as $s => $c) { if (!in_array($s, $standarSizes)) $sizeBawahanRecap[$s] = $c; }
            @endphp

            @if($filled->isNotEmpty())
            <div class="page-break"></div>

            @php
                $cols = [];
                $cols[] = ['type' => 'no', 'label' => 'NO.', 'weight' => 5];
                if ($hasNamaPunggung) {
                    $cols[] = ['type' => 'nama_punggung', 'label' => 'NAMA PUNGGUNG', 'weight' => 22, 'align' => 't-left'];
                    $cols[] = ['type' => 'no_punggung', 'label' => 'NO. PUNGGUNG', 'weight' => 8];
                }
                if ($hasNamaDada) {
                    $cols[] = ['type' => 'nama_dada', 'label' => 'NAMA DADA', 'weight' => 16, 'align' => 't-left'];
                    $cols[] = ['type' => 'no_dada', 'label' => 'NO. DADA', 'weight' => 8];
                }
                if ($hasNamaLengan) {
                    $cols[] = ['type' => 'nama_lengan', 'label' => 'NAMA LENGAN', 'weight' => 16, 'align' => 't-left'];
                    $cols[] = ['type' => 'no_lengan', 'label' => 'NO. LENGAN', 'weight' => 8];
                }
                if ($hasNamaPunggung2) {
                    $cols[] = ['type' => 'nama_punggung_2', 'label' => 'NAMA PUNGGUNG 2', 'weight' => 22, 'align' => 't-left'];
                    $cols[] = ['type' => 'no_punggung_2', 'label' => 'NO. PUNGGUNG 2', 'weight' => 8];
                }
                if ($hasSizeAtasan) {
                    $cols[] = ['type' => 'size', 'label' => 'SIZE', 'weight' => 8];
                }
                if ($hasSizeBawahan) {
                    $cols[] = ['type' => 'size_celana', 'label' => 'SIZE CELANA', 'weight' => 9];
                }
                if ($hasKeterangan) {
                    $cols[] = ['type' => 'keterangan', 'label' => 'KETERANGAN', 'weight' => 20, 'align' => 't-left'];
                }

                $totalWeight = collect($cols)->sum('weight');
                foreach ($cols as &$col) {
                    $col['pct'] = round(($col['weight'] / $totalWeight) * 100, 1);
                }
                unset($col);
                
                $tableClass = count($cols) > 7 ? 'ns-table ns-table-dense' : 'ns-table';
            @endphp

            <div class="title-box-center" style="font-size:11.5pt;">
                DATA PESANAN {{ strtoupper($item->nama_produk) }} @if($item->varian_label) — {{ strtoupper($item->varian_label) }}@endif
            </div>
            <table class="{{ $tableClass }}" style="border-top:none; table-layout: fixed; width: 100%;">
                <colgroup>
                    @foreach($cols as $col)
                        <col style="width: {{ $col['pct'] }}%;">
                    @endforeach
                </colgroup>
                <thead>
                    <tr>
                        @foreach($cols as $col)
                            <th class="{{ $col['align'] ?? '' }}">{{ $col['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($filled as $i => $ns)
                    <tr>
                        @foreach($cols as $col)
                            @if($col['type'] === 'no')
                                <td>{{ $i + 1 }}.</td>
                            @elseif($col['type'] === 'nama_punggung')
                                <td class="t-left">{{ strtoupper($ns->nama_punggung ?? '') ?: '.......' }}</td>
                            @elseif($col['type'] === 'no_punggung')
                                <td>{{ $ns->nomor_punggung ?: '.......' }}</td>
                            @elseif($col['type'] === 'nama_dada')
                                <td class="t-left">{{ strtoupper($ns->nama_dada ?? '') ?: '.......' }}</td>
                            @elseif($col['type'] === 'no_dada')
                                <td>{{ $ns->nomor_dada ?: '.......' }}</td>
                            @elseif($col['type'] === 'nama_lengan')
                                <td class="t-left">{{ strtoupper($ns->nama_lengan ?? '') ?: '.......' }}</td>
                            @elseif($col['type'] === 'no_lengan')
                                <td>{{ $ns->nomor_lengan ?: '.......' }}</td>
                            @elseif($col['type'] === 'nama_punggung_2')
                                <td class="t-left">{{ strtoupper($ns->nama_punggung_2 ?? '') ?: '.......' }}</td>
                            @elseif($col['type'] === 'no_punggung_2')
                                <td>{{ $ns->nomor_punggung_2 ?: '.......' }}</td>
                            @elseif($col['type'] === 'size')
                                @php $sv = $ns->size ? $ns->size->ukuran : trim(last(explode('-', $ns->size_label ?? ''))); @endphp
                                <td>{{ $sv ?: '.......' }}</td>
                            @elseif($col['type'] === 'size_celana')
                                @php $svc = $ns->sizeCelana ? $ns->sizeCelana->ukuran : trim(last(explode('-', $ns->size_celana_label ?? ''))); @endphp
                                <td>{{ $svc ?: '.......' }}</td>
                            @elseif($col['type'] === 'keterangan')
                                <td class="t-left">{{ $ns->keterangan ?: '.......' }}</td>
                            @endif
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- REKAP SIZE --}}
            <div class="rekap-container">
                <div style="margin-bottom:5px; text-decoration:underline;">JUMLAH KESELURUHAN: {{ $filled->count() }} PCS</div>

                @if(count($sizeAtasanRecap))
                    @if($hasSizeBawahan)<div style="font-size:9pt; margin:4px 0 2px;">REKAP SIZE ATASAN</div>@endif
                    @foreach(array_chunk($sizeAtasanRecap, 10, true) as $chunk)
                    <table class="rekap-tabel">
                        <thead><tr>@foreach($chunk as $sz => $c)<th>{{ $sz }}</th>@endforeach</tr></thead>
                        <tbody><tr>@foreach($chunk as $c)<td>{{ $c }}</td>@endforeach</tr></tbody>
                    </table>
                    @endforeach
                @endif

                @if(count($sizeBawahanRecap))
                    <div style="font-size:9pt; margin:8px 0 2px;">REKAP SIZE BAWAHAN</div>
                    @foreach(array_chunk($sizeBawahanRecap, 10, true) as $chunk)
                    <table class="rekap-tabel">
                        <thead><tr>@foreach($chunk as $sz => $c)<th>{{ $sz }}</th>@endforeach</tr></thead>
                        <tbody><tr>@foreach($chunk as $c)<td>{{ $c }}</td>@endforeach</tr></tbody>
                    </table>
                    @endforeach
                @endif
            </div>
            @endif
        @endforeach

        {{-- ===== CHECKLIST PRODUKSI ===== --}}
        @php
            $progresses = \App\Models\Master\Progress::active()->ordered()->get();
        @endphp
        @if($progresses->count() && $nonAddonItems->count())
        <div class="page-break"></div>

        <div style="font-size:12pt; font-weight:900; text-decoration:underline; margin-bottom:10px; border-bottom:2px solid #000; padding-bottom:4px; text-align:center;">
            CHECKLIST PRODUKSI — {{ strtoupper($order->nama_po) }}
        </div>

        <table style="width:100%; border-collapse:collapse; font-size:10pt;">
            <thead>
                <tr style="background:#000; color:#fff; font-weight:900;">
                    <th style="border:1.5px solid #000; padding:6px 8px; text-align:center; width:35px;">NO</th>
                    <th style="border:1.5px solid #000; padding:6px 8px; text-align:left; width:180px;">PROSES</th>
                    @foreach($nonAddonItems as $pi)
                    <th style="border:1.5px solid #000; padding:6px 8px; text-align:center; font-size:9pt;">
                        {{ strtoupper($pi->varian_label ?: $pi->nama_produk) }}
                        @if($pi->varian_label && $pi->varian_label !== $pi->nama_produk)
                        <div style="font-size:7.5pt; font-weight:400; opacity:0.8;">{{ strtoupper($pi->nama_produk) }}</div>
                        @endif
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($progresses as $idx => $prog)
                <tr style="{{ $idx % 2 === 0 ? 'background:#f9f9f9;' : 'background:#fff;' }}">
                    <td style="border:1px solid #000; padding:6px 8px; text-align:center; font-weight:bold;">{{ $idx + 1 }}</td>
                    <td style="border:1px solid #000; padding:6px 8px; font-weight:900; font-size:10pt;">
                        <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:{{ $prog->warna ?? '#6B7280' }}; margin-right:5px; vertical-align:middle;"></span>
                        {{ strtoupper($prog->nama_progress) }}
                    </td>
                    @foreach($nonAddonItems as $pi)
                    <td style="border:1px solid #000; padding:6px 8px; text-align:center; min-width:80px;">&nbsp;</td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top:8px; font-size:8.5pt; color:#555; text-align:right;">
            Dicetak: {{ now()->translatedFormat('d M Y, H:i') }} · {{ strtoupper($order->brand->nama_brand ?? '') }}
        </div>
        @endif

        {{-- ===== LAMPIRAN: DATA PESANAN SEMUA PRODUK ===== --}}
        <div class="page-break"></div>
        <div style="font-size:12pt; font-weight:900; text-decoration:underline; margin-bottom:10px; border-bottom:2px solid #000; padding-bottom:4px;">
            LAMPIRAN: DATA PESANAN
        </div>

        @foreach ($nonAddonItems as $item)
            @php
                $lampFilled = $item->namesets->filter(fn($ns) =>
                    !empty($ns->nama_punggung) || !empty($ns->nomor_punggung) ||
                    !empty($ns->nama_dada)     || !empty($ns->nomor_dada)     ||
                    !empty($ns->nama_lengan)   || !empty($ns->nomor_lengan)   ||
                    !empty($ns->nama_punggung_2) || !empty($ns->nomor_punggung_2) ||
                    !empty($ns->size_id)       || !empty($ns->size_label)     ||
                    !empty($ns->size_celana_id)|| !empty($ns->size_celana_label) ||
                    !empty($ns->keterangan)
                );

                $hasLampNamaPunggung = $lampFilled->contains(fn($ns) => !empty($ns->nama_punggung) || !empty($ns->nomor_punggung));
                $hasLampNamaDada     = $lampFilled->contains(fn($ns) => !empty($ns->nama_dada)     || !empty($ns->nomor_dada));
                $hasLampNamaLengan   = $lampFilled->contains(fn($ns) => !empty($ns->nama_lengan)   || !empty($ns->nomor_lengan));
                $hasLampNamaPunggung2 = $lampFilled->contains(fn($ns) => !empty($ns->nama_punggung_2) || !empty($ns->nomor_punggung_2));
                $hasLampSizeAtasan   = $lampFilled->contains(fn($ns) => !empty($ns->size_id)       || !empty($ns->size_label));
                $hasLampSizeBawahan  = $lampFilled->contains(fn($ns) => !empty($ns->size_celana_id)|| !empty($ns->size_celana_label));
                $hasLampKeterangan   = $lampFilled->contains(fn($ns) => !empty($ns->keterangan));
            @endphp
            @if($lampFilled->isNotEmpty())
            @php
                $cols = [];
                $cols[] = ['type' => 'no', 'label' => 'NO.', 'weight' => 5];
                if ($hasLampNamaPunggung) {
                    $cols[] = ['type' => 'nama_punggung', 'label' => 'NAMA PUNGGUNG', 'weight' => 22, 'align' => 't-left'];
                    $cols[] = ['type' => 'no_punggung', 'label' => 'NO. PUNGGUNG', 'weight' => 8];
                }
                if ($hasLampNamaDada) {
                    $cols[] = ['type' => 'nama_dada', 'label' => 'NAMA DADA', 'weight' => 16, 'align' => 't-left'];
                    $cols[] = ['type' => 'no_dada', 'label' => 'NO. DADA', 'weight' => 8];
                }
                if ($hasLampNamaLengan) {
                    $cols[] = ['type' => 'nama_lengan', 'label' => 'NAMA LENGAN', 'weight' => 16, 'align' => 't-left'];
                    $cols[] = ['type' => 'no_lengan', 'label' => 'NO. LENGAN', 'weight' => 8];
                }
                if ($hasLampNamaPunggung2) {
                    $cols[] = ['type' => 'nama_punggung_2', 'label' => 'NAMA PUNGGUNG 2', 'weight' => 22, 'align' => 't-left'];
                    $cols[] = ['type' => 'no_punggung_2', 'label' => 'NO. PUNGGUNG 2', 'weight' => 8];
                }
                if ($hasLampSizeAtasan) {
                    $cols[] = ['type' => 'size', 'label' => 'SIZE', 'weight' => 8];
                }
                if ($hasLampSizeBawahan) {
                    $cols[] = ['type' => 'size_celana', 'label' => 'SIZE CELANA', 'weight' => 9];
                }
                if ($hasLampKeterangan) {
                    $cols[] = ['type' => 'keterangan', 'label' => 'KETERANGAN', 'weight' => 20, 'align' => 't-left'];
                }

                $totalWeight = collect($cols)->sum('weight');
                foreach ($cols as &$col) {
                    $col['pct'] = round(($col['weight'] / $totalWeight) * 100, 1);
                }
                unset($col);
                
                $tableClass = count($cols) > 7 ? 'ns-table ns-table-dense' : 'ns-table';
            @endphp
            <div class="lampiran-sub">DATA PESANAN: {{ strtoupper($item->nama_produk) }} @if($item->varian_label) — {{ strtoupper($item->varian_label) }}@endif</div>
            <table class="{{ $tableClass }}" style="margin-bottom:12px; table-layout: fixed; width: 100%;">
                <colgroup>
                    @foreach($cols as $col)
                        <col style="width: {{ $col['pct'] }}%;">
                    @endforeach
                </colgroup>
                <thead>
                    <tr>
                        @foreach($cols as $col)
                            <th class="{{ $col['align'] ?? '' }}">{{ $col['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($lampFilled as $i => $ns)
                    <tr>
                        @foreach($cols as $col)
                            @if($col['type'] === 'no')
                                <td>{{ $i + 1 }}.</td>
                            @elseif($col['type'] === 'nama_punggung')
                                <td class="t-left">{{ strtoupper($ns->nama_punggung ?? '') ?: '.......' }}</td>
                            @elseif($col['type'] === 'no_punggung')
                                <td>{{ $ns->nomor_punggung ?: '.......' }}</td>
                            @elseif($col['type'] === 'nama_dada')
                                <td class="t-left">{{ strtoupper($ns->nama_dada ?? '') ?: '.......' }}</td>
                            @elseif($col['type'] === 'no_dada')
                                <td>{{ $ns->nomor_dada ?: '.......' }}</td>
                            @elseif($col['type'] === 'nama_lengan')
                                <td class="t-left">{{ strtoupper($ns->nama_lengan ?? '') ?: '.......' }}</td>
                            @elseif($col['type'] === 'no_lengan')
                                <td>{{ $ns->nomor_lengan ?: '.......' }}</td>
                            @elseif($col['type'] === 'nama_punggung_2')
                                <td class="t-left">{{ strtoupper($ns->nama_punggung_2 ?? '') ?: '.......' }}</td>
                            @elseif($col['type'] === 'no_punggung_2')
                                <td>{{ $ns->nomor_punggung_2 ?: '.......' }}</td>
                            @elseif($col['type'] === 'size')
                                @php $sv = $ns->size ? $ns->size->ukuran : trim(last(explode('-', $ns->size_label ?? ''))); @endphp
                                <td>{{ $sv ?: '.......' }}</td>
                            @elseif($col['type'] === 'size_celana')
                                @php $svc = $ns->sizeCelana ? $ns->sizeCelana->ukuran : trim(last(explode('-', $ns->size_celana_label ?? ''))); @endphp
                                <td>{{ $svc ?: '.......' }}</td>
                            @elseif($col['type'] === 'keterangan')
                                <td class="t-left">{{ $ns->keterangan ?: '.......' }}</td>
                            @endif
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        @endforeach

    </main>
    @if(isset($isWebPreview) && $isWebPreview)
        </div>
    @endif
</body>
</html>
