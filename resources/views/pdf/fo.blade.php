@php
/** @var \App\Models\Order\Order $order */
/** @var \App\Models\Brand|null $headerBrand */
/** @var string|null $logoData */
/** @var \Illuminate\Database\Eloquent\Collection<\App\Models\Master\Progress> $progresses */
    @endphp
    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="utf-8">
        <title>FO {{ $order->no_po }}</title>
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

            /* === CSS TEMPLATE DASAR PDF A4 (Polished) === */
            * {
                box-sizing: border-box;
            }

            @page {
                margin: 15mm 12mm 15mm 12mm;
            }

            header {
                position: fixed;
                top: -10mm;
                left: 0;
                right: 0;
                height: 8mm;
                text-align: center;
                font-size: 11pt;
                font-weight: bold;
                border-bottom: 1px solid #777;
                padding-bottom: 5px;
                text-transform: uppercase;
            }

            footer {
                position: fixed;
                bottom: -10mm;
                left: 0;
                right: 0;
                height: 8mm;
                border-top: 1px solid #777;
                padding-top: 5px;
            }

            main {
                margin-top: 5mm;
                margin-bottom: 5mm;
            }

            body {
                font-family: 'DejaVu Sans', 'Helvetica', sans-serif;
                font-size: 10pt;
                color: #000;
                line-height: 1.35;
                text-transform: uppercase;
                margin: 0;
                padding: 0;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            td,
            th {
                padding: 5px;
                vertical-align: top;
            }

            /* Title boxes */
            .title-box {
                font-weight: bold;
                font-size: 10.5pt;
                background: #d4d4d4;
                color: #000;
                padding: 6px 10px;
                text-align: left;
                border: 1px solid #000;
                border-bottom: none;
                text-transform: uppercase;
            }

            .title-box-center {
                font-weight: bold;
                font-size: 10.5pt;
                background: #d4d4d4;
                color: #000;
                padding: 6px 10px;
                text-align: center;
                border: 1px solid #000;
                border-bottom: none;
                text-transform: uppercase;
            }

            /* Product band */
            .product-band {
                background: #000;
                color: #fff;
                text-align: center;
                font-size: 11pt;
                font-weight: bold;
                padding: 6px 10px;
                margin: 10px 0 0;
                letter-spacing: 0.5px;
            }

            /* Spec table */
            .spec-table {
                border: 1px solid #000;
                margin-bottom: 5px;
                width: 100%;
                page-break-inside: avoid;
            }

            .spec-table th,
            .spec-table td {
                border: 1px solid #000;
                padding: 3px 5px;
                font-size: 8pt;
                text-align: center;
                font-weight: normal;
            }

            .spec-table th {
                background: #d4d4d4;
                font-weight: bold;
            }

            .spec-row-head {
                width: 25%;
                text-align: left !important;
                background: #f2f2f2;
                font-weight: bold;
            }

            /* Nameset table */
            .ns-table {
                border: 1px solid #000;
                margin-bottom: 5px;
            }

            .ns-table th,
            .ns-table td {
                border: 1px solid #000;
                padding: 5px 6px;
                font-size: 9.5pt;
                text-align: center;
                font-weight: normal;
                word-wrap: break-word;
                word-break: break-all;
                white-space: normal;
            }

            .ns-table-dense th,
            .ns-table-dense td {
                font-size: 7.5pt !important;
                padding: 3px 4px !important;
            }

            .ns-table th {
                background: #d4d4d4;
                font-size: 9.5pt;
                font-weight: bold;
            }

            .t-left {
                text-align: left !important;
                padding-left: 6px !important;
            }

            /* Rekap size */
            .rekap-container {
                margin-top: 12px;
                font-size: 10pt;
                font-weight: bold;
                text-align: center;
            }

            .rekap-tabel {
                border: 2px solid #000;
                width: auto;
                margin: 6px auto 0;
            }

            .rekap-tabel td {
                border: 1px solid #000;
                padding: 5px 12px;
                text-align: center;
                font-size: 9.5pt;
                font-weight: normal;
            }

            .rekap-tabel th {
                border: 1px solid #000;
                padding: 5px 12px;
                text-align: center;
                font-size: 9.5pt;
                background: #d4d4d4;
                font-weight: bold;
            }

            /* Image wrapper */
            .img-wrapper {
                border: 2px solid #000;
                padding: 6px;
                margin: 0 0 10px;
                background: #fff;
            }

            .img-box {
                text-align: center;
                border: 1px solid #000;
                padding: 4px;
                margin-bottom: 5px;
                background: #fff;
            }

            .img-box img {
                max-width: 100%;
                object-fit: contain;
            }

            /* Misc */
            .page-break {
                page-break-before: always;
            }

            .page-num:after {
                content: counter(page);
            }

            .lampiran-sub {
                font-weight: bold;
                font-size: 9.5pt;
                margin-bottom: 4px;
            }

            .ns-name {
                text-transform: none !important;
            }
        </style>
    </head>

    <body>

        <header>
            <table style="width: 100%; border-collapse: collapse; border: none; margin: 0; padding: 0;">
                <tr>
                    <td style="width: 25%; text-align: left; font-size: 7.5pt; font-weight: normal; color: #444; vertical-align: bottom; padding: 0 0 5px 0; border: none; text-decoration: none; text-transform: uppercase;">
                        MESIN PRINT: ....................
                    </td>
                    <td style="width: 50%; text-align: center; font-size: 11pt; font-weight: bold; vertical-align: bottom; padding: 0 0 5px 0; border: none; text-decoration: underline; text-transform: uppercase;">
                        FORMAT ORDER
                    </td>
                    <td style="width: 25%; text-align: right; font-size: 7.5pt; font-weight: normal; color: #444; vertical-align: bottom; padding: 0 0 5px 0; border: none; text-decoration: none; text-transform: uppercase;">
                        MESIN PRES: ....................
                    </td>
                </tr>
            </table>
        </header>
        <footer>
            <table style="width:100%; border:none; font-size:7pt; font-weight:bold;">
                <tr>
                    <td style="text-align:left; width:40%;">HALAMAN <span class="page-num"></span></td>
                    <td style="text-align:right; width:60%; color:#b91c1c;">{{ $order->no_po }}</td>
                </tr>
            </table>
        </footer>

        <main>

            {{-- ===== TOP DETAILS SECTION ===== --}}
            @php
            $nonAddonItems = $nonAddonItems ?? $order->items->filter(fn($i) => empty($i->is_addon))->values();
            $addonItems = $addonItems ?? $order->items->filter(fn($i) => !empty($i->is_addon))->values();
            $grandTotal = $nonAddonItems->sum('quantity');
            $totalAtasan = $nonAddonItems->sum(fn($i) => (int)($i->jml_atasan ?? 0));
            $totalBawahan = $nonAddonItems->sum(fn($i) => (int)($i->jml_bawahan ?? 0));
            $printingNames = collect();
            if (!empty($order->printing_ids)) {
            $printingNames = \App\Models\Master\Printing::whereIn('id', $order->printing_ids)->pluck('nama');
            }
            $commaSep = ', ';
            $printingStr = $printingNames->isNotEmpty() ? $printingNames->implode($commaSep) : '';
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
                                <td style="font-size: 10.5pt; padding: 3px 0; font-weight: bold;">{!! !empty($order->nama_po) ? \App\Support\PdfHelper::formatText($order->nama_po) : '' !!}</td>
                            </tr>
                            @if($order->is_repeat_order)
                            <tr style="color: #b45309;">
                                <td style="font-weight: bold; font-size: 10.5pt; padding: 3px 0; color: #b45309;">REPEAT ORDER</td>
                                <td style="font-weight: bold; padding: 3px 0; color: #b45309;">:</td>
                                <td style="font-size: 10.5pt; padding: 3px 0; font-weight: bold; color: #b45309;">YA {{ $order->repeatFrom ? '(PO ASAL: ' . $order->repeatFrom->no_po . ')' : '' }}</td>
                            </tr>
                            @endif
                            @if($order->resellerDisplayBrand || ($order->brand && $order->brand->isReseller()))
                            <tr>
                                <td style="font-weight: bold; font-size: 10.5pt; padding: 3px 0;">RESELLER</td>
                                <td style="font-weight: bold; padding: 3px 0;">:</td>
                                <td style="font-size: 10.5pt; padding: 3px 0; font-weight: bold;">{{ strtoupper($order->resellerDisplayBrand ? $order->resellerDisplayBrand->nama_brand : $order->brand->nama_brand) }}</td>
                            </tr>
                            @endif

                            <tr>
                                <td style="font-weight: bold; font-size: 10.5pt; padding: 3px 0;">TOTAL ATASAN</td>
                                <td style="font-weight: bold; padding: 3px 0;">:</td>
                                <td style="font-size: 10.5pt; padding: 3px 0; font-weight: bold;">{{ $totalAtasan }} PCS</td>
                            </tr>
                            <tr>
                                <td style="font-weight: bold; font-size: 10.5pt; padding: 3px 0;">TOTAL BAWAHAN</td>
                                <td style="font-weight: bold; padding: 3px 0;">:</td>
                                <td style="font-size: 10.5pt; padding: 3px 0; font-weight: bold;">{{ $totalBawahan ?: '0' }} PCS</td>
                            </tr>
                        </table>
                    </td>
                    <!-- Right Side: Printing Box -->
                    <td style="width: 40%; padding: 0 0 0 15px; vertical-align: top;">
                        <div style="border: 2px solid #000; padding: 12px 10px; background: #fff; text-align: center; min-height: 75px;">
                              @if($order->resellerDisplayBrand)
                              <div style="font-size: 10.5pt; font-weight: 900; color: #000; margin-bottom: 6px; padding-bottom: 4px;">
                                  RESELLER:<br>
                                  <span>{{ strtoupper($order->resellerDisplayBrand->nama_brand) }}</span>
                              </div>
                              @elseif($order->brand && $order->brand->isReseller())
                              <div style="font-size: 10.5pt; font-weight: 900; color: #000; margin-bottom: 6px; padding-bottom: 4px;">
                                  RESELLER:<br>
                                  <span>{{ strtoupper($order->brand->nama_brand) }}</span>
                              </div>
                              @else
                              <div style="font-size: 13pt; font-weight: 900; color: #000; margin-bottom: 6px; padding-bottom: 4px; line-height: 1.2;">
                                  {{ strtoupper($order->brand->nama_brand ?? 'BRAND') }}
                              </div>
                              @endif
                             <div style="font-size: 10pt; font-weight: bold; line-height: 1.2;">
                                 JENIS PRINTING:<br>
                                 <span style="font-size: 12pt; font-weight: 900; color: #000;">{{ strtoupper($printingStr) }}</span>
                             </div>
                             @if($order->paketOrder)
                             <div style="font-size: 10pt; font-weight: bold; margin-top: 10px; padding-top: 5px; line-height: 1.2;">
                                 PAKET ORDER:<br>
                                 <span style="font-size: 12pt; font-weight: 900; color: #000;">{{ strtoupper($order->paketOrder->nama) }}</span>
                             </div>
                             @endif
                             @if($order->is_reseller_price)
                             <div style="font-size: 10pt; font-weight: bold; margin-top: 10px; padding-top: 5px; line-height: 1.2;">
                                 <span style="font-size: 11pt; font-weight: 900; color: #dc2626;">HARGA RESELLER</span>
                             </div>
                             @endif
                             @if($order->is_repeat_order)
                             <div style="font-size: 10pt; font-weight: bold; margin-top: 10px; padding-top: 5px; border-top: 1px dashed #000; line-height: 1.2;">
                                 <span style="font-size: 11pt; font-weight: 900; color: #b45309;">REPEAT ORDER {{ $order->repeatFrom ? '(' . $order->repeatFrom->no_po . ')' : '' }}</span>
                             </div>
                             @endif
                        </div>
                    </td>
                </tr>
            </table>

            @if($order->catatan)
            <div class="title-box" style="margin-top:0;">CATATAN ORDER</div>
            <div style="border:1px solid #000; padding:6px 10px; font-weight:bold; margin-bottom:15px; font-size: 9.5pt;">
                {!! \App\Support\PdfHelper::formatText($order->catatan) !!}
            </div>
            @endif

            {{-- ===== KETERANGAN MATERIAL & JAHITAN (CONSOLIDATED ON ONE PAGE) ===== --}}
            @if($nonAddonItems->isNotEmpty())
            <div style="page-break-inside: avoid;">
                {{-- ===== KETERANGAN MATERIAL (SIDE-BY-SIDE SPEC TABLE) ===== --}}
                <div style="color: #000; font-weight: bold; font-size: 11pt; margin-bottom: 5px; text-transform: uppercase;">KETERANGAN MATERIAL</div>
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
                            <td class="spec-row-head">JENIS SETELAN</td>
                            @foreach($nonAddonItems as $item)
                            <td>{{ strtoupper($item->jenisSetelan->nama ?? $item->jenis_setelan ?? '') ?: '' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="spec-row-head">POLA</td>
                            @foreach($nonAddonItems as $item)
                            <td>{{ strtoupper($item->polaProduksi->nama ?? $item->pola ?? '') ?: '' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="spec-row-head">BAHAN ATASAN</td>
                            @foreach($nonAddonItems as $item)
                            @php
                            $bahanAtasanStr = $item->bahan_kains_names ?: ($item->bahanKain->nama ?? '');
                            @endphp
                            <td>{{ strtoupper($bahanAtasanStr) }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="spec-row-head">BAHAN BAWAHAN</td>
                            @foreach($nonAddonItems as $item)
                            @php
                            $bahanBawahanStr = $item->bahan_kain_bawahan_names ?: ($item->bahanKainBawahan->nama ?? '');
                            @endphp
                            <td>{{ strtoupper($bahanBawahanStr) }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="spec-row-head">JUMLAH ATASAN</td>
                            @foreach($nonAddonItems as $item)
                            <td>{{ ($item->jml_atasan !== null && $item->jml_atasan !== '') ? $item->jml_atasan : '' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="spec-row-head">JUMLAH BAWAHAN</td>
                            @foreach($nonAddonItems as $item)
                            <td>{{ $item->jml_bawahan ?: '' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="spec-row-head">WARNA</td>
                            @foreach($nonAddonItems as $item)
                            <td>{{ strtoupper($item->warna ?? '') ?: '' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="spec-row-head">JENIS LOGO</td>
                            @foreach($nonAddonItems as $item)
                            @php
                            $logoStr = !empty($item->logo_names) ? implode(', ', $item->logo_names) : ($item->logo?->nama ?? '');
                            @endphp
                            <td>{{ strtoupper($logoStr) }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="spec-row-head">JENIS RIB</td>
                            @foreach($nonAddonItems as $item)
                            <td>{{ strtoupper($item->jenis_rib ?? '') ?: '' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="spec-row-head">LIST KERAH</td>
                            @foreach($nonAddonItems as $item)
                            <td>{{ strtoupper($item->list_kerah ?? '') ?: '' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="spec-row-head">LIST LENGAN</td>
                            @foreach($nonAddonItems as $item)
                            <td>{{ strtoupper($item->list_lengan ?? '') ?: '' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="spec-row-head">LIST SAMPING CELANA</td>
                            @foreach($nonAddonItems as $item)
                            <td>{{ strtoupper($item->list_samping_celana ?? '') ?: '' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="spec-row-head">LIST BAWAH CELANA</td>
                            @foreach($nonAddonItems as $item)
                            <td>{{ strtoupper($item->list_bawah_celana ?? '') ?: '' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="spec-row-head">TUTUP KERAH</td>
                            @foreach($nonAddonItems as $item)
                            <td>{{ strtoupper($item->tutup_kerah ?? '') ?: '' }}</td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>

                {{-- ===== KETERANGAN JAHITAN ===== --}}
                <div style="color: #000; font-weight: bold; font-size: 11pt; margin-top: 15px; margin-bottom: 5px; text-transform: uppercase;">KETERANGAN JAHITAN</div>
                <table class="spec-table" style="margin-bottom:15px;">
                    <thead>
                        <tr>
                            <th style="text-align:left; width:25%;">JAHITAN / DETAIL</th>
                            @foreach($nonAddonItems as $item)
                            <th>
                                {{ strtoupper($item->varian_label ?: $item->nama_produk) }}
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="spec-row-head">POLA JAHITAN</td>
                            @foreach($nonAddonItems as $item)
                            <td>
                                {{ $item->polaJahitan ? strtoupper($item->polaJahitan->nama) : '' }}
                            </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="spec-row-head">JAHITAN LIST LENGAN</td>
                            @foreach($nonAddonItems as $item)
                            <td>
                                {{ $item->polaJahitanLengan ? strtoupper($item->polaJahitanLengan->nama) : strtoupper($item->jahitan_list_lengan ?? '') }}
                            </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="spec-row-head">JENIS RESLETING</td>
                            @foreach($nonAddonItems as $item)
                            <td>
                                {{ strtoupper($item->resleting->nama ?? '') }}
                            </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
            @endif

            @php
            $standarSizes = ['XS ANAK','S ANAK','M ANAK','L ANAK','XL ANAK',
            'XS','S','M','L','XL','2XL','3XL','4XL','5XL','6XL','7XL','8XL','9XL','10XL'];
            $renderedDesigns = [];
            @endphp

            {{-- ===== DETAIL PER ITEM (DESAIN & NAMESET) ===== --}}
            @foreach ($nonAddonItems as $item)
            @php
            /** @var \App\Models\Order\OrderItem $item */
            $hasDesain = $item->gambar_desain || $item->ket_atasan || $item->ket_bawahan || $item->jenis_kerah || $item->gambar_kerah || $item->gambar_ket_tambahan;

            $designKey = implode('|', [
                $item->gambar_desain ?? '',
                $item->gambar_kerah ?? '',
                $item->gambar_ket_tambahan ?? '',
                $item->ket_atasan ?? '',
                $item->ket_bawahan ?? '',
            ]);

            $skipDesain = false;
            if (in_array($designKey, $renderedDesigns)) {
                $skipDesain = true;
            }

            if ($hasDesain && !$skipDesain) {
                if (!in_array($designKey, $renderedDesigns)) {
                    $renderedDesigns[] = $designKey;
                }
            }

            $hasVal = fn($val) => strlen(trim((string)($val ?? ''))) > 0;

            $filled = $item->namesets->filter(fn($ns) =>
                $hasVal($ns->nama_punggung ?? '') || $hasVal($ns->nomor_punggung ?? '') ||
                $hasVal($ns->nama_dada ?? '') || $hasVal($ns->nomor_dada ?? '') ||
                $hasVal($ns->nama_lengan ?? '') || $hasVal($ns->nomor_lengan ?? '') ||
                $hasVal($ns->nama_punggung_2 ?? '') || $hasVal($ns->nomor_punggung_2 ?? '') ||
                $hasVal($ns->size_id ?? '') || $hasVal($ns->size_label ?? '') ||
                $hasVal($ns->size_celana_id ?? '') || $hasVal($ns->size_celana_label ?? '') ||
                $hasVal($ns->keterangan ?? '')
            );
            @endphp

            @if($hasDesain && !$skipDesain)
            <div class="page-break"></div>

            <div class="img-wrapper" style="padding:6px; margin-bottom:10px;">
                <div class="title-box-center" style="margin-top:0;">
                    REFERENSI DESAIN {{ strtoupper($item->nama_produk) }} @if($item->varian_label) — {{ strtoupper($item->varian_label) }}@endif
                </div>

                @if($item->gambar_desain)
                @php
                $dUrl = asset('storage/' . $item->gambar_desain);
                $dPath = (isset($isWebPreview) && $isWebPreview) ? $dUrl : \App\Support\PdfHelper::resolveImageForPdf($item->gambar_desain);
                @endphp
                @if(!empty($dPath))
                <div class="img-box" style="border-top:none; padding:2px; margin-bottom:6px;">
                    <img src="{{ $dPath }}" style="max-width: 100%; max-height: 520px; display: block; margin: 0 auto;">
                </div>
                @else
                <div class="img-box" style="border-top:none; height:60px; line-height:60px; color:#64748b; font-style:italic; font-size:9.5pt;">Gambar desain tidak ditemukan</div>
                @endif
                @else
                <div class="img-box" style="border-top:none; height:60px; line-height:60px; color:#64748b; font-style:italic; font-size:9.5pt;">Gambar desain belum diunggah</div>
                @endif

                <table style="width:100%; border-collapse:collapse; border:1px solid #000; font-size:10pt; margin-bottom:0;">
                    <tr>
                        <td style="width:50%; vertical-align:top; padding:6px; border-right:1px solid #000;">
                            <div style="background:#d4d4d4; font-weight:900; padding:4px; text-align:center; margin-bottom:4px; border:1px solid #000;">KETERANGAN ATASAN</div>
                            <div style="padding:2px; text-align:center; font-weight:bold;">{!! \App\Support\PdfHelper::formatText($item->ket_atasan) !!}</div>
                        </td>
                        <td style="width:50%; vertical-align:top; padding:6px;">
                            <div style="background:#d4d4d4; font-weight:900; padding:4px; text-align:center; margin-bottom:4px; border:1px solid #000;">KETERANGAN BAWAHAN</div>
                            <div style="padding:2px; text-align:center; font-weight:bold;">{!! \App\Support\PdfHelper::formatText($item->ket_bawahan) !!}</div>
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
                    @if($hasTambahan)
                    <td style="width: 50%; padding-right: 5px; border: none; vertical-align: top;">
                    @else
                    <td style="width: 100%; padding-right: 0; border: none; vertical-align: top;">
                    @endif
                        <div class="img-wrapper" style="padding:6px; margin-bottom:0;">
                            <div class="title-box-center" style="margin-top:0; font-size: 9pt;">
                                JENIS KERAH: {{ strtoupper($item->jenis_kerah ?? '') }}
                            </div>
                            <div style="border:1px solid #000; border-top:none; padding:6px; text-align:center; background:#fff;">
                                @if($item->gambar_kerah)
                                @php
                                $kUrl = asset('storage/' . $item->gambar_kerah);
                                $kPath = (isset($isWebPreview) && $isWebPreview) ? $kUrl : \App\Support\PdfHelper::resolveImageForPdf($item->gambar_kerah);
                                @endphp
                                @if(!empty($kPath))
                                <div style="text-align:center;">
                                    <img src="{{ $kPath }}" style="max-width: 100%; max-height: 160px; display: block; margin: 0 auto;">
                                </div>
                                @else
                                <div style="color:#64748b; font-style:italic; font-size: 8.5pt; padding:15px 0;">Gambar kerah tidak ditemukan</div>
                                @endif
                                @else
                                <div style="color:#64748b; font-style:italic; font-size: 8.5pt; padding:15px 0;">Gambar kerah belum diunggah</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    @endif

                    @if($hasTambahan)
                    @if($hasKerah)
                    <td style="width: 50%; padding-left: 5px; border: none; vertical-align: top;">
                    @else
                    <td style="width: 100%; padding-left: 0; border: none; vertical-align: top;">
                    @endif
                        <div class="img-wrapper" style="padding:6px; margin-bottom:0;">
                            <div class="title-box-center" style="margin-top:0; font-size: 9pt;">
                                KETERANGAN TAMBAHAN
                            </div>
                            <div style="border:1px solid #000; border-top:none; padding:6px; text-align:center; background:#fff;">
                                @php
                                $ktUrl = asset('storage/' . $item->gambar_ket_tambahan);
                                $ktPath = (isset($isWebPreview) && $isWebPreview) ? $ktUrl : \App\Support\PdfHelper::resolveImageForPdf($item->gambar_ket_tambahan);
                                @endphp
                                @if(!empty($ktPath))
                                <div style="text-align:center;">
                                    <img src="{{ $ktPath }}" style="max-width: 100%; max-height: 160px; display: block; margin: 0 auto;">
                                </div>
                                @else
                                <div style="color:#64748b; font-style:italic; font-size: 8.5pt; padding:15px 0;">Gambar tambahan tidak ditemukan</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    @endif
                </tr>
            </table>
            @endif
            @endif

            @if($filled->isNotEmpty())
            <div class="page-break"></div>

            @php
            $hasCustomization = $filled->contains(fn($ns) =>
                $hasVal($ns->nama_punggung ?? '') || $hasVal($ns->nomor_punggung ?? '') ||
                $hasVal($ns->nama_dada ?? '') || $hasVal($ns->nomor_dada ?? '') ||
                $hasVal($ns->nama_lengan ?? '') || $hasVal($ns->nomor_lengan ?? '') ||
                $hasVal($ns->nama_punggung_2 ?? '') || $hasVal($ns->nomor_punggung_2 ?? '') ||
                $hasVal($ns->keterangan ?? '')
            );

            $hasVal = fn($val) => strlen(trim((string)($val ?? ''))) > 0;

            $hasNamaPunggung = $filled->contains(fn($ns) => $hasVal($ns->nama_punggung ?? ''));
            $hasNoPunggung = $filled->contains(fn($ns) => $hasVal($ns->nomor_punggung ?? ''));
            $hasNamaDada = $filled->contains(fn($ns) => $hasVal($ns->nama_dada ?? ''));
            $hasNoDada = $filled->contains(fn($ns) => $hasVal($ns->nomor_dada ?? ''));
            $hasNamaLengan = $filled->contains(fn($ns) => $hasVal($ns->nama_lengan ?? ''));
            $hasNoLengan = $filled->contains(fn($ns) => $hasVal($ns->nomor_lengan ?? ''));
            $hasNamaPunggung2 = $filled->contains(fn($ns) => $hasVal($ns->nama_punggung_2 ?? ''));
            $hasNoPunggung2 = $filled->contains(fn($ns) => $hasVal($ns->nomor_punggung_2 ?? ''));
            $hasSizeAtasan = $filled->contains(fn($ns) => $hasVal($ns->size_id ?? '') || $hasVal($ns->size_label ?? ''));
            $hasSizeBawahan = $filled->contains(fn($ns) => $hasVal($ns->size_celana_id ?? '') || $hasVal($ns->size_celana_label ?? ''));
            $hasKeterangan = $filled->contains(fn($ns) => $hasVal($ns->keterangan ?? ''));

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

            $cols = [];
            $cols[] = ['type' => 'no', 'label' => 'NO.', 'weight' => 6];
            if ($hasNamaPunggung) {
                $cols[] = ['type' => 'nama_punggung', 'label' => 'NAMA PUNGGUNG', 'weight' => 22, 'align' => 't-left'];
            }
            if ($hasNoPunggung) {
                $cols[] = ['type' => 'no_punggung', 'label' => 'NO. PUNGGUNG', 'weight' => 12];
            }
            if ($hasNamaDada) {
                $cols[] = ['type' => 'nama_dada', 'label' => 'NAMA DADA', 'weight' => 18, 'align' => 't-left'];
            }
            if ($hasNoDada) {
                $cols[] = ['type' => 'no_dada', 'label' => 'NO. DADA', 'weight' => 12];
            }
            if ($hasNamaLengan) {
                $cols[] = ['type' => 'nama_lengan', 'label' => 'NAMA LENGAN', 'weight' => 18, 'align' => 't-left'];
            }
            if ($hasNoLengan) {
                $cols[] = ['type' => 'no_lengan', 'label' => 'NO. LENGAN', 'weight' => 12];
            }
            if ($hasNoPunggung2) {
                $cols[] = ['type' => 'no_punggung_2', 'label' => 'NO. PUNGGUNG 2', 'weight' => 12];
            }
            if ($hasNamaPunggung2) {
                $cols[] = ['type' => 'nama_punggung_2', 'label' => 'NAMA PUNGGUNG 2', 'weight' => 22, 'align' => 't-left'];
            }
            if ($hasSizeAtasan) {
                $cols[] = ['type' => 'size', 'label' => 'SIZE', 'weight' => 10];
            }
            if ($hasSizeBawahan) {
                $cols[] = ['type' => 'size_celana', 'label' => 'SIZE CELANA', 'weight' => 12];
            }
            if ($hasKeterangan) {
                $cols[] = ['type' => 'keterangan', 'label' => 'KETERANGAN', 'weight' => 18, 'align' => 't-left'];
            }

            $weightKey = 'weight';
            $totalWeight = collect($cols)->sum($weightKey);
            foreach ($cols as &$col) {
                $col['pct'] = round(($col['weight'] / $totalWeight) * 100, 1);
            }
            unset($col);

            $tableClass = count($cols) > 7 ? 'ns-table ns-table-dense' : 'ns-table';
            @endphp

            @if(!$hasCustomization)
            <div class="title-box-center" style="font-size:11.5pt; border-bottom: 1px solid #000;">
            @else
            <div class="title-box-center" style="font-size:11.5pt;">
            @endif
                DATA PESANAN {{ strtoupper($item->nama_produk) }} @if($item->varian_label) — {{ strtoupper($item->varian_label) }}@endif
            </div>
            @if($hasCustomization)
            <table class="{{ $tableClass }}" style="border-top:none; table-layout: fixed; width: 100%;">
                <colgroup>
                    @foreach($cols as $col)
                    <col width="{{ $col['pct'] }}%">
                    @endforeach
                </colgroup>
                <thead>
                    <tr>
                        @foreach($cols as $col)
                        <th class="{{ $col['align'] ?? '' }}" width="{{ $col['pct'] }}%">{{ $col['label'] }}</th>
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
                        <td class="t-left ns-name">
                            {!! \App\Support\PdfHelper::formatText($ns->nama_punggung) !!}
                            
                        </td>
                        @elseif($col['type'] === 'no_punggung')
                        <td>{!! \App\Support\PdfHelper::formatText($ns->nomor_punggung) !!}</td>
                        @elseif($col['type'] === 'nama_dada')
                        <td class="t-left ns-name">{!! \App\Support\PdfHelper::formatText($ns->nama_dada) !!}</td>
                        @elseif($col['type'] === 'no_dada')
                        <td>{!! \App\Support\PdfHelper::formatText($ns->nomor_dada) !!}</td>
                        @elseif($col['type'] === 'nama_lengan')
                        <td class="t-left ns-name">{!! \App\Support\PdfHelper::formatText($ns->nama_lengan) !!}</td>
                        @elseif($col['type'] === 'no_lengan')
                        <td>{!! \App\Support\PdfHelper::formatText($ns->nomor_lengan) !!}</td>
                        @elseif($col['type'] === 'nama_punggung_2')
                        <td class="t-left ns-name">{!! \App\Support\PdfHelper::formatText($ns->nama_punggung_2) !!}</td>
                        @elseif($col['type'] === 'no_punggung_2')
                        <td>{!! \App\Support\PdfHelper::formatText($ns->nomor_punggung_2) !!}</td>
                        @elseif($col['type'] === 'size')
                        @php $sv = $ns->size ? $ns->size->ukuran : trim(last(explode('-', $ns->size_label ?? ''))); @endphp
                        <td>{!! \App\Support\PdfHelper::formatText($sv) !!}</td>
                        @elseif($col['type'] === 'size_celana')
                        @php $svc = $ns->sizeCelana ? $ns->sizeCelana->ukuran : trim(last(explode('-', $ns->size_celana_label ?? ''))); @endphp
                        <td>{!! \App\Support\PdfHelper::formatText($svc) !!}</td>
                        @elseif($col['type'] === 'keterangan')
                        <td class="t-left ns-name">{!! \App\Support\PdfHelper::formatText($ns->keterangan) !!}</td>
                        @endif
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif

            {{-- REKAP SIZE --}}
            <div class="rekap-container">
                <div style="margin-bottom:5px; text-decoration:underline;">JUMLAH KESELURUHAN: {{ $filled->count() }} PCS</div>

                @if(count($sizeAtasanRecap))
                @if($hasSizeBawahan)<div style="font-size:9pt; margin:4px 0 2px;">REKAP SIZE ATASAN</div>@endif
                @foreach(array_chunk($sizeAtasanRecap, 10, true) as $chunk)
                <table class="rekap-tabel">
                    <thead>
                        <tr>@foreach($chunk as $sz => $c)<th>{{ $sz }}</th>@endforeach</tr>
                    </thead>
                    <tbody>
                        <tr>@foreach($chunk as $c)<td>{{ $c }}</td>@endforeach</tr>
                    </tbody>
                </table>
                @endforeach
                @endif

                @if(count($sizeBawahanRecap))
                <div style="font-size:9pt; margin:8px 0 2px;">REKAP SIZE BAWAHAN</div>
                @foreach(array_chunk($sizeBawahanRecap, 10, true) as $chunk)
                <table class="rekap-tabel">
                    <thead>
                        <tr>@foreach($chunk as $sz => $c)<th>{{ $sz }}</th>@endforeach</tr>
                    </thead>
                    <tbody>
                        <tr>@foreach($chunk as $c)<td>{{ $c }}</td>@endforeach</tr>
                    </tbody>
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
                CHECKLIST PRODUKSI — {!! \App\Support\PdfHelper::formatText($order->nama_po) !!}
            </div>

            <table style="width:100%; border-collapse:collapse; font-size:10pt;">
                <thead>
                    <tr style="background:#d4d4d4; color:#000; font-weight:bold;">
                        <th style="border:1.5px solid #000; padding:6px 8px; text-align:center; width:35px;">NO</th>
                        <th style="border:1.5px solid #000; padding:6px 8px; text-align:left; width:180px;">PROSES</th>
                        <th style="border:1.5px solid #000; padding:6px 8px; text-align:center; font-size:9pt;">NAMA 1</th>
                        <th style="border:1.5px solid #000; padding:6px 8px; text-align:center; font-size:9pt;">NAMA 2</th>
                        <th style="border:1.5px solid #000; padding:6px 8px; text-align:center; font-size:9pt;">NAMA 3</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Manual static rows --}}
                    @php $manualRows = ['ADMIN', 'DESAIN', 'FORMAT ORDER']; @endphp
                    @foreach($manualRows as $mi => $label)
                    <tr @if($mi % 2===0) style="background:#f9f9f9;" @else style="background:#fff;" @endif>
                        <td style="border:1px solid #000; padding:6px 8px; text-align:center; font-weight:bold;">{{ $mi + 1 }}</td>
                        <td style="border:1px solid #000; padding:6px 8px; font-weight:900; font-size:10pt;">{{ $label }}</td>
                        <td style="border:1px solid #000; padding:6px 8px; text-align:center; min-width:80px;">&nbsp;</td>
                        <td style="border:1px solid #000; padding:6px 8px; text-align:center; min-width:80px;">&nbsp;</td>
                        <td style="border:1px solid #000; padding:6px 8px; text-align:center; min-width:80px;">&nbsp;</td>
                    </tr>
                    @endforeach
                    {{-- Dynamic progress rows from DB (SETTING, etc.) --}}
                    @foreach($progresses as $idx => $prog)
                    @php $rowNum = count($manualRows) + $idx + 1; $totalIdx = count($manualRows) + $idx; @endphp
                    <tr @if($totalIdx % 2===0) style="background:#f9f9f9;" @else style="background:#fff;" @endif>
                        <td style="border:1px solid #000; padding:6px 8px; text-align:center; font-weight:bold;">{{ $rowNum }}</td>
                        <td style="border:1px solid #000; padding:6px 8px; font-weight:900; font-size:10pt;">
                            {{ strtoupper($prog->nama_progress) }}
                        </td>
                        <td style="border:1px solid #000; padding:6px 8px; text-align:center; min-width:80px;">&nbsp;</td>
                        <td style="border:1px solid #000; padding:6px 8px; text-align:center; min-width:80px;">&nbsp;</td>
                        <td style="border:1px solid #000; padding:6px 8px; text-align:center; min-width:80px;">&nbsp;</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="margin-top:8px; font-size:8.5pt; color:#555; text-align:right;">
                Dicetak: {{ now()->translatedFormat('d M Y, H:i') }} · {{ strtoupper($order->resellerDisplayBrand ? $order->resellerDisplayBrand->nama_brand : ($order->brand->nama_brand ?? '')) }}
            </div>
            @endif

            {{-- ===== LAMPIRAN: DATA PESANAN SEMUA PRODUK ===== --}}
            @php
            $hasAnyLampFilled = false;
            foreach ($nonAddonItems as $item) {
                $lampFilled = $item->namesets->filter(fn($ns) =>
                    $hasVal($ns->nama_punggung ?? '') || $hasVal($ns->nomor_punggung ?? '') ||
                    $hasVal($ns->nama_dada ?? '') || $hasVal($ns->nomor_dada ?? '') ||
                    $hasVal($ns->nama_lengan ?? '') || $hasVal($ns->nomor_lengan ?? '') ||
                    $hasVal($ns->nama_punggung_2 ?? '') || $hasVal($ns->nomor_punggung_2 ?? '') ||
                    $hasVal($ns->size_id ?? '') || $hasVal($ns->size_label ?? '') ||
                    $hasVal($ns->size_celana_id ?? '') || $hasVal($ns->size_celana_label ?? '') ||
                    $hasVal($ns->keterangan ?? '')
                );
                if ($lampFilled->isNotEmpty()) {
                    $hasAnyLampFilled = true;
                    break;
                }
            }
            @endphp

            @if($hasAnyLampFilled)
            <div class="page-break"></div>
            <div style="font-size:12pt; font-weight:900; text-decoration:underline; margin-bottom:10px; border-bottom:2px solid #000; padding-bottom:4px;">
                LAMPIRAN: DATA PESANAN
            </div>
            @php
            $getSizeSortWeight = function($sizeStr) {
                $sizeStr = strtolower(trim($sizeStr));
                $weights = [
                    'kids' => -100,
                    'xxs'  => -2,
                    'xs'   => -1,
                    's'    => 1,
                    'm'    => 2,
                    'l'    => 3,
                    'xl'   => 4,
                    '2xl'  => 5,
                    'xxl'  => 5,
                    '3xl'  => 6,
                    'xxxl' => 6,
                    '4xl'  => 7,
                    '5xl'  => 8,
                    '6xl'  => 9,
                    '7xl'  => 10,
                ];
                if (isset($weights[$sizeStr])) {
                    return $weights[$sizeStr];
                }
                if (is_numeric($sizeStr)) {
                    return 100 + (float)$sizeStr;
                }
                if (preg_match('/^(\d+)xl$/', $sizeStr, $matches)) {
                    return 4 + (int)$matches[1];
                }
                return 999;
            };

            $globalGroupIdx = 1;
            @endphp

            @foreach ($nonAddonItems as $item)
            @php
            $lampFilled = $item->namesets->filter(fn($ns) =>
                $hasVal($ns->nama_punggung ?? '') || $hasVal($ns->nomor_punggung ?? '') ||
                $hasVal($ns->nama_dada ?? '') || $hasVal($ns->nomor_dada ?? '') ||
                $hasVal($ns->nama_lengan ?? '') || $hasVal($ns->nomor_lengan ?? '') ||
                $hasVal($ns->nama_punggung_2 ?? '') || $hasVal($ns->nomor_punggung_2 ?? '') ||
                $hasVal($ns->size_id ?? '') || $hasVal($ns->size_label ?? '') ||
                $hasVal($ns->size_celana_id ?? '') || $hasVal($ns->size_celana_label ?? '') ||
                $hasVal($ns->keterangan ?? '')
            );
            @endphp
            @if($lampFilled->isNotEmpty())
            @php
            $currentGroupIndex = $globalGroupIdx++;

            $hasLampNamaPunggung = $lampFilled->contains(fn($ns) => $hasVal($ns->nama_punggung ?? ''));
            $hasLampNoPunggung = $lampFilled->contains(fn($ns) => $hasVal($ns->nomor_punggung ?? ''));
            $hasLampNamaDada = $lampFilled->contains(fn($ns) => $hasVal($ns->nama_dada ?? ''));
            $hasLampNoDada = $lampFilled->contains(fn($ns) => $hasVal($ns->nomor_dada ?? ''));
            $hasLampNamaLengan = $lampFilled->contains(fn($ns) => $hasVal($ns->nama_lengan ?? ''));
            $hasLampNoLengan = $lampFilled->contains(fn($ns) => $hasVal($ns->nomor_lengan ?? ''));
            $hasLampNamaPunggung2 = $lampFilled->contains(fn($ns) => $hasVal($ns->nama_punggung_2 ?? ''));
            $hasLampNoPunggung2 = $lampFilled->contains(fn($ns) => $hasVal($ns->nomor_punggung_2 ?? ''));
            $hasLampSizeAtasan = $lampFilled->contains(fn($ns) => $hasVal($ns->size_id ?? '') || $hasVal($ns->size_label ?? ''));
            $hasLampSizeBawahan = $lampFilled->contains(fn($ns) => $hasVal($ns->size_celana_id ?? '') || $hasVal($ns->size_celana_label ?? ''));
            $hasLampKeterangan = $lampFilled->contains(fn($ns) => $hasVal($ns->keterangan ?? ''));

            $cols = [];
            $cols[] = ['type' => 'no', 'label' => 'NO.', 'weight' => 6];
            if ($hasLampNamaPunggung) {
                $cols[] = ['type' => 'nama_punggung', 'label' => 'NAMA PUNGGUNG', 'weight' => 22, 'align' => 't-left'];
            }
            if ($hasLampNoPunggung) {
                $cols[] = ['type' => 'no_punggung', 'label' => 'NOPUNG', 'weight' => 12];
            }
            if ($hasLampNamaDada) {
                $cols[] = ['type' => 'nama_dada', 'label' => 'NAMA DADA', 'weight' => 18, 'align' => 't-left'];
            }
            if ($hasLampNoDada) {
                $cols[] = ['type' => 'no_dada', 'label' => 'NO. DADA', 'weight' => 12];
            }
            if ($hasLampNamaLengan) {
                $cols[] = ['type' => 'nama_lengan', 'label' => 'NAMA LENGAN', 'weight' => 18, 'align' => 't-left'];
            }
            if ($hasLampNoLengan) {
                $cols[] = ['type' => 'no_lengan', 'label' => 'NO. LENGAN', 'weight' => 12];
            }
            if ($hasLampNoPunggung2) {
                $cols[] = ['type' => 'no_punggung_2', 'label' => 'NO. PUNGGUNG 2', 'weight' => 12];
            }
            if ($hasLampNamaPunggung2) {
                $cols[] = ['type' => 'nama_punggung_2', 'label' => 'NAMA PUNGGUNG 2', 'weight' => 22, 'align' => 't-left'];
            }
            if ($hasLampSizeAtasan) {
                $cols[] = ['type' => 'size', 'label' => 'SIZE', 'weight' => 10];
            }
            if ($hasLampSizeBawahan) {
                $cols[] = ['type' => 'size_celana', 'label' => 'SIZE CELANA', 'weight' => 12];
            }
            if ($hasLampKeterangan) {
                $cols[] = ['type' => 'keterangan', 'label' => 'KETERANGAN', 'weight' => 18, 'align' => 't-left'];
            }

            $weightKey = 'weight';
            $totalWeight = collect($cols)->sum($weightKey);
            foreach ($cols as &$col) {
                $col['pct'] = round(($col['weight'] / $totalWeight) * 100, 1);
            }
            unset($col);

            $tableClass = count($cols) > 7 ? 'ns-table ns-table-dense' : 'ns-table';

            // Group by size
            $groupedBySize = $lampFilled->groupBy(function($ns) use ($hasLampSizeAtasan, $hasLampSizeBawahan) {
                if ($hasLampSizeAtasan) {
                    $sv = $ns->size ? $ns->size->ukuran : trim(last(explode('-', $ns->size_label ?? '')));
                    if (!empty($sv)) return $sv;
                }
                if ($hasLampSizeBawahan) {
                    $svc = $ns->sizeCelana ? $ns->sizeCelana->ukuran : trim(last(explode('-', $ns->size_celana_label ?? '')));
                    if (!empty($svc)) return $svc;
                }
                return '-';
            });

            // Sort size keys
            $sortedSizeKeys = $groupedBySize->keys()->sortBy(function($sizeName) use ($getSizeSortWeight) {
                return $getSizeSortWeight($sizeName);
            });

            // Construct descriptive title
            $varDetails = [];
            if ($item->nama_produk) {
                $varDetails[] = strtoupper($item->nama_produk);
            }
            if ($item->varian_label) {
                $varDetails[] = strtoupper($item->varian_label);
            }
            $groupSubtitle = implode(' — ', $varDetails);
            @endphp

            <div style="font-size: 11pt; font-weight: bold; text-align: center; margin-top: 15px; margin-bottom: 8px; text-transform: uppercase;">
                DATA PESANAN: {{ $groupSubtitle }}
            </div>

            @foreach($sortedSizeKeys as $sizeKey)
                @php
                $namesetsInSize = $groupedBySize->get($sizeKey);
                @endphp
                <table class="{{ $tableClass }}" style="margin-bottom:12px; table-layout: fixed; width: 100%;">
                    <colgroup>
                        @foreach($cols as $col)
                        <col width="{{ $col['pct'] }}%">
                        @endforeach
                    </colgroup>
                    <thead>
                        <tr>
                            @foreach($cols as $col)
                            <th class="{{ $col['align'] ?? '' }}" width="{{ $col['pct'] }}%">{{ $col['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($namesetsInSize->values() as $i => $ns)
                        <tr>
                            @foreach($cols as $col)
                            @if($col['type'] === 'no')
                            <td>{{ $i + 1 }}.</td>
                            @elseif($col['type'] === 'nama_punggung')
                            <td class="t-left ns-name">
                                {!! \App\Support\PdfHelper::formatText($ns->nama_punggung) !!}
                            </td>
                            @elseif($col['type'] === 'no_punggung')
                            <td>{!! \App\Support\PdfHelper::formatText($ns->nomor_punggung) !!}</td>
                            @elseif($col['type'] === 'nama_dada')
                            <td class="t-left ns-name">{!! \App\Support\PdfHelper::formatText($ns->nama_dada) !!}</td>
                            @elseif($col['type'] === 'no_dada')
                            <td>{!! \App\Support\PdfHelper::formatText($ns->nomor_dada) !!}</td>
                            @elseif($col['type'] === 'nama_lengan')
                            <td class="t-left ns-name">{!! \App\Support\PdfHelper::formatText($ns->nama_lengan) !!}</td>
                            @elseif($col['type'] === 'no_lengan')
                            <td>{!! \App\Support\PdfHelper::formatText($ns->nomor_lengan) !!}</td>
                            @elseif($col['type'] === 'nama_punggung_2')
                            <td class="t-left ns-name">{!! \App\Support\PdfHelper::formatText($ns->nama_punggung_2) !!}</td>
                            @elseif($col['type'] === 'no_punggung_2')
                            <td>{!! \App\Support\PdfHelper::formatText($ns->nomor_punggung_2) !!}</td>
                            @elseif($col['type'] === 'size')
                            @php $sv = $ns->size ? $ns->size->ukuran : trim(last(explode('-', $ns->size_label ?? ''))); @endphp
                            <td>{!! \App\Support\PdfHelper::formatText($sv) !!}</td>
                            @elseif($col['type'] === 'size_celana')
                            @php $svc = $ns->sizeCelana ? $ns->sizeCelana->ukuran : trim(last(explode('-', $ns->size_celana_label ?? ''))); @endphp
                            <td>{!! \App\Support\PdfHelper::formatText($svc) !!}</td>
                            @elseif($col['type'] === 'keterangan')
                            <td class="t-left ns-name">{!! \App\Support\PdfHelper::formatText($ns->keterangan) !!}</td>
                            @endif
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endforeach
            @endif
            @endforeach
            @endif

        </main>

    </body>

    </html>