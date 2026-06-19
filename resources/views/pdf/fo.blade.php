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
        /* === CSS TEMPLATE DASAR PDF A4 (Polished) === */
        * { box-sizing: border-box; }
        @page { margin: 15mm 12mm 15mm 12mm; }

        header { position: fixed; top: -10mm; left: 0; right: 0; height: 8mm;
                 text-align: center; font-size: 11pt; font-weight: bold;
                 border-bottom: 1px solid #777; padding-bottom: 5px; text-transform: uppercase; }
        footer { position: fixed; bottom: -10mm; left: 0; right: 0; height: 8mm;
                 border-top: 1px solid #777; padding-top: 5px; }
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
                    FORMAT ORDER {{ strtoupper(($headerBrand ?? ($order->brand ? $order->brand->getHeaderBrand() : null))?->nama_brand ?? 'BRAND') }}
                </td>
                <td style="width: 25%; text-align: right; font-size: 7.5pt; font-weight: normal; color: #444; vertical-align: bottom; padding: 0 0 5px 0; border: none; text-decoration: none; text-transform: uppercase;">
                    MESIN PRES: ....................
                </td>
            </tr>
        </table>
    </header>
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

        <!-- Standardized KOP Header -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px; border-bottom: 2px solid #000; padding-bottom: 8px;">
            <tr>
                <td style="width: 65%; padding: 0; vertical-align: top;">
                    @include('pdf.components.kop', ['brand' => $headerBrand ?? ($order->brand ? $order->brand->getHeaderBrand() : null), 'logoData' => $logoData ?? null])
                </td>
                <td style="width: 35%; padding: 0; vertical-align: top; text-align: right;">
                    <div style="font-size: 16pt; font-weight: 900; color: #000; letter-spacing: -0.5px;">FORMAT ORDER</div>
                    <div style="font-family: monospace; font-size: 11pt; font-weight: bold; margin-top: 2px;">{{ $order->no_po }}</div>
                    @if($order->paketOrder)
                        <div style="font-size: 8.5pt; font-weight: bold; margin-top: 6px; color: #374151;">
                            PAKET: <span style="font-size: 9.5pt; font-weight: 900; color: #000;">{{ strtoupper($order->paketOrder->nama) }}</span>
                        </div>
                    @endif
                </td>
            </tr>
        </table>

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
                        @if($order->brand && ($order->brand->isResellerHub() || $order->brand->isResellerBranch()))
                        <tr>
                            <td style="font-weight: bold; font-size: 10.5pt; padding: 3px 0;">RESELLER</td>
                            <td style="font-weight: bold; padding: 3px 0;">:</td>
                            <td style="font-size: 10.5pt; padding: 3px 0; font-weight: bold;">{{ strtoupper($order->brand->nama_brand) }}</td>
                        </tr>
                        @endif
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
                <!-- Right Side: Printing Box -->
                <td style="width: 40%; padding: 0 0 0 15px; vertical-align: top;">
                    <div style="border: 2px solid #000; padding: 12px 10px; background: #fff; text-align: center; min-height: 75px;">
                        <div style="font-size: 10pt; font-weight: bold; line-height: 1.2;">
                            JENIS PRINTING:<br>
                            <span style="font-size: 12pt; font-weight: 900; color: red;">{{ strtoupper($printingStr) }}</span>
                        </div>
                    </div>
                </td>
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
                    <td class="spec-row-head">BAHAN</td>
                    @foreach($nonAddonItems as $item)
                    @php
                        $bahanAtasanStr = $item->bahan_kains_names ?: ($item->bahanKain->nama ?? '.......');
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
                        $logoStr = !empty($item->logo_names) ? implode(', ', $item->logo_names) : ($item->logo?->nama ?? '.......');
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
        <div style="color: #000; font-weight: bold; font-size: 11pt; margin-top: 15px; margin-bottom: 5px; text-transform: uppercase;">KETERANGAN JAHITAN</div>
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
            @php /** @var \App\Models\Order\OrderItem $item */ @endphp
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
                /** @var \App\Models\Order\OrderItem $item */
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
                                <td class="t-left">{{ strtoupper($ns->nama_punggung ?? '') ?: '' }}</td>
                            @elseif($col['type'] === 'no_punggung')
                                <td>{{ $ns->nomor_punggung ?: '' }}</td>
                            @elseif($col['type'] === 'nama_dada')
                                <td class="t-left">{{ strtoupper($ns->nama_dada ?? '') ?: '' }}</td>
                            @elseif($col['type'] === 'no_dada')
                                <td>{{ $ns->nomor_dada ?: '' }}</td>
                            @elseif($col['type'] === 'nama_lengan')
                                <td class="t-left">{{ strtoupper($ns->nama_lengan ?? '') ?: '' }}</td>
                            @elseif($col['type'] === 'no_lengan')
                                <td>{{ $ns->nomor_lengan ?: '' }}</td>
                            @elseif($col['type'] === 'nama_punggung_2')
                                <td class="t-left">{{ strtoupper($ns->nama_punggung_2 ?? '') ?: '' }}</td>
                            @elseif($col['type'] === 'no_punggung_2')
                                <td>{{ $ns->nomor_punggung_2 ?: '' }}</td>
                            @elseif($col['type'] === 'size')
                                @php $sv = $ns->size ? $ns->size->ukuran : trim(last(explode('-', $ns->size_label ?? ''))); @endphp
                                <td>{{ $sv ?: '' }}</td>
                            @elseif($col['type'] === 'size_celana')
                                @php $svc = $ns->sizeCelana ? $ns->sizeCelana->ukuran : trim(last(explode('-', $ns->size_celana_label ?? ''))); @endphp
                                <td>{{ $svc ?: '' }}</td>
                            @elseif($col['type'] === 'keterangan')
                                <td class="t-left">{{ $ns->keterangan ?: '' }}</td>
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
                <tr style="{{ $mi % 2 === 0 ? 'background:#f9f9f9;' : 'background:#fff;' }}">
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
                <tr style="{{ $totalIdx % 2 === 0 ? 'background:#f9f9f9;' : 'background:#fff;' }}">
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
                /** @var \App\Models\Order\OrderItem $item */
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
                                <td class="t-left">{{ strtoupper($ns->nama_dada ?? '') ?: '' }}</td>
                            @elseif($col['type'] === 'no_dada')
                                <td>{{ $ns->nomor_dada ?: '' }}</td>
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

</body>
</html>
