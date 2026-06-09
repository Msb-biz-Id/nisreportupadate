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
        .ns-table th, .ns-table td { border: 1px solid #000; padding: 5px 6px; font-size: 9.5pt; text-align: center; font-weight: normal; }
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
                <!-- Right Side: Brand & Paket Box -->
                <td style="width: 40%; padding: 0 0 0 15px; vertical-align: top; text-align: center;">
                    <div style="border: 2px solid #000; padding: 12px 10px; min-height: 110px; background: #fff; text-align: center;">
                        <div style="font-size: 15pt; font-weight: 900; line-height: 1.2;">
                            {{ strtoupper($order->brand->nama_brand ?? 'BRAND') }}
                        </div>
                        <div style="font-size: 12pt; font-weight: 700; color: red; margin-top: 3px;">
                            ({{ $order->is_special_order ? 'SPECIAL' : 'NORMAL' }})
                        </div>
                        @if($order->paketOrder)
                            <div style="font-size: 11pt; font-weight: bold; margin-top: 10px; border-top: 1px solid #000; padding-top: 5px; line-height: 1.2;">
                                PAKET:<br>
                                <span style="font-size: 12.5pt; font-weight: 900;">{{ strtoupper($order->paketOrder->nama) }}</span>
                            </div>
                        @endif
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
                        {{ $item->polaJahitan ? strtoupper($item->polaJahitan->jenis_pola . ' — ' . $item->polaJahitan->nama) : '.......' }}
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

        {{-- ===== ADD-ONS TABLE ===== --}}
        @if($addonItems->isNotEmpty())
        <div style="color: #b91c1c; font-weight: bold; font-size: 11pt; margin-top: 15px; margin-bottom: 5px; text-transform: uppercase;">ADD-ONS & BIAYA TAMBAHAN</div>
        <table class="spec-table" style="margin-top:0;">
            <thead>
                <tr>
                    <th style="border:1px solid #000; padding:5px 6px; text-align:left;">NAMA ADD-ON</th>
                    <th style="border:1px solid #000; padding:5px 6px; text-align:center; width:100px;">QTY</th>
                    <th style="border:1px solid #000; padding:5px 6px; text-align:right; width:150px;">HARGA SATUAN</th>
                    <th style="border:1px solid #000; padding:5px 6px; text-align:right; width:160px;">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($addonItems as $item)
                <tr>
                    <td style="border:1px solid #000; padding:5px 6px; text-align:left; font-weight:bold;">{{ strtoupper($item->nama_produk) }}</td>
                    <td style="border:1px solid #000; padding:5px 6px; text-align:center;">{{ $item->quantity }}</td>
                    <td style="border:1px solid #000; padding:5px 6px; text-align:right;">RP {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                    <td style="border:1px solid #000; padding:5px 6px; text-align:right; font-weight:bold;">RP {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @if($item->catatan)
                <tr>
                    <td colspan="4" style="border:1px solid #000; padding:4px 6px; font-size:8.5pt; color:#555; background:#fafafa; text-align:left;">
                        KETERANGAN: {{ strtoupper($item->catatan) }}
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- ===== REFERENSI DESAIN & GAMBAR (PER ITEM) ===== --}}
        @foreach ($nonAddonItems as $item)
            @if($item->gambar_desain || $item->ket_atasan || $item->ket_bawahan || $item->jenis_kerah || $item->gambar_kerah || $item->gambar_ket_tambahan)
            <div class="page-break"></div>

            <div class="img-wrapper" style="padding:6px; margin-bottom:12px;">
                <div class="title-box-center" style="margin-top:0;">
                    REFERENSI DESAIN {{ strtoupper($item->nama_produk) }} @if($item->varian_label) — {{ strtoupper($item->varian_label) }}@endif
                </div>

                @if($item->gambar_desain)
                    @php $dPath = storage_path('app/public/' . $item->gambar_desain); @endphp
                    @if(file_exists($dPath))
                    <div class="img-box" style="border-top:none; padding:2px; margin-bottom:6px;">
                        <img src="{{ $dPath }}" style="max-width: 100%; max-height: 380px; display: block; margin: 0 auto;">
                    </div>
                    @else
                    <div class="img-box" style="border-top:none; height:150px; line-height:150px; color:#999; font-weight:bold;">[ GAMBAR DESAIN TIDAK DITEMUKAN ]</div>
                    @endif
                @else
                <div class="img-box" style="border-top:none; height:150px; line-height:150px; color:#999; font-weight:bold;">[ GAMBAR DESAIN BELUM DIUNGGAH ]</div>
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

            @if($item->jenis_kerah || $item->gambar_kerah)
            <div class="img-wrapper" style="padding:6px; margin-bottom:12px;">
                <div style="border:1px solid #000; padding:10px; text-align:center; background:#fff;">
                    <div style="font-size:11pt; font-weight:bold; margin-bottom:6px;">
                        JENIS KERAH: <span style="font-weight:normal;">{{ strtoupper($item->jenis_kerah ?? '.......') }}</span>
                    </div>
                    @if($item->gambar_kerah)
                        @php $kPath = storage_path('app/public/' . $item->gambar_kerah); @endphp
                        @if(file_exists($kPath))
                        <div style="text-align:center; margin-top:6px;">
                            <img src="{{ $kPath }}" style="max-width: 100%; max-height: 220px; display: block; margin: 0 auto;">
                        </div>
                        @else
                        <div style="color:#999; font-weight:bold; padding:8px;">[ GAMBAR KERAH TIDAK DITEMUKAN ]</div>
                        @endif
                    @endif
                </div>
            </div>
            @endif

            @if($item->gambar_ket_tambahan)
            <div class="img-wrapper" style="padding:6px; margin-bottom:0;">
                <div class="title-box-center" style="margin-top:0;">
                    KETERANGAN TAMBAHAN GAMBAR
                </div>
                <div style="border:1px solid #000; border-top:none; padding:10px; text-align:center; background:#fff;">
                    @php $ktPath = storage_path('app/public/' . $item->gambar_ket_tambahan); @endphp
                    @if(file_exists($ktPath))
                    <div style="text-align:center;">
                        <img src="{{ $ktPath }}" style="max-width: 100%; max-height: 250px; display: block; margin: 0 auto;">
                    </div>
                    @else
                    <div style="color:#999; font-weight:bold; padding:8px;">[ GAMBAR KETERANGAN TAMBAHAN TIDAK DITEMUKAN ]</div>
                    @endif
                </div>
            </div>
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

            <div class="title-box-center" style="font-size:11.5pt;">
                DATA PESANAN {{ strtoupper($item->nama_produk) }} @if($item->varian_label) — {{ strtoupper($item->varian_label) }}@endif
            </div>
            <table class="ns-table" style="border-top:none;">
                <thead>
                    <tr>
                        <th width="40">NO.</th>
                        @if($hasNamaPunggung)
                        <th class="t-left">NAMA PUNGGUNG</th>
                        <th width="90">NO. PUNGGUNG</th>
                        @endif
                        @if($hasNamaDada)
                        <th class="t-left">NAMA DADA</th>
                        <th width="90">NO. DADA</th>
                        @endif
                        @if($hasNamaLengan)
                        <th class="t-left">NAMA LENGAN</th>
                        <th width="80">NO. LENGAN</th>
                        @endif
                        @if($hasNamaPunggung2)
                        <th class="t-left">NAMA PUNGGUNG 2</th>
                        <th width="90">NO. PUNGGUNG 2</th>
                        @endif
                        @if($hasSizeAtasan)<th width="75">SIZE</th>@endif
                        @if($hasSizeBawahan)<th width="85">SIZE CELANA</th>@endif
                        @if($hasKeterangan)<th class="t-left">KETERANGAN</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($filled as $i => $ns)
                    <tr>
                        <td>{{ $i + 1 }}.</td>
                        @if($hasNamaPunggung)
                        <td class="t-left">{{ strtoupper($ns->nama_punggung ?? '') ?: '.......' }}</td>
                        <td>{{ $ns->nomor_punggung ?: '.......' }}</td>
                        @endif
                        @if($hasNamaDada)
                        <td class="t-left">{{ strtoupper($ns->nama_dada ?? '') ?: '.......' }}</td>
                        <td>{{ $ns->nomor_dada ?: '.......' }}</td>
                        @endif
                        @if($hasNamaLengan)
                        <td class="t-left">{{ strtoupper($ns->nama_lengan ?? '') ?: '.......' }}</td>
                        <td>{{ $ns->nomor_lengan ?: '.......' }}</td>
                        @endif
                        @if($hasNamaPunggung2)
                        <td class="t-left">{{ strtoupper($ns->nama_punggung_2 ?? '') ?: '.......' }}</td>
                        <td>{{ $ns->nomor_punggung_2 ?: '.......' }}</td>
                        @endif
                        @if($hasSizeAtasan)
                        @php $sv = $ns->size ? $ns->size->ukuran : trim(last(explode('-', $ns->size_label ?? ''))); @endphp
                        <td>{{ $sv ?: '.......' }}</td>
                        @endif
                        @if($hasSizeBawahan)
                        @php $svc = $ns->sizeCelana ? $ns->sizeCelana->ukuran : trim(last(explode('-', $ns->size_celana_label ?? ''))); @endphp
                        <td>{{ $svc ?: '.......' }}</td>
                        @endif
                        @if($hasKeterangan)
                        <td class="t-left">{{ $ns->keterangan ?: '.......' }}</td>
                        @endif
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
            <div class="lampiran-sub">DATA PESANAN: {{ strtoupper($item->nama_produk) }} @if($item->varian_label) — {{ strtoupper($item->varian_label) }}@endif</div>
            <table class="ns-table" style="margin-bottom:12px;">
                <thead>
                    <tr>
                        <th width="40">NO.</th>
                        @if($hasLampNamaPunggung)
                        <th class="t-left">NAMA PUNGGUNG</th>
                        <th width="90">NO. PUNGGUNG</th>
                        @endif
                        @if($hasLampNamaDada)
                        <th class="t-left">NAMA DADA</th>
                        <th width="90">NO. DADA</th>
                        @endif
                        @if($hasLampNamaLengan)
                        <th class="t-left">NAMA LENGAN</th>
                        <th width="90">NO. LENGAN</th>
                        @endif
                        @if($hasLampNamaPunggung2)
                        <th class="t-left">NAMA PUNGGUNG 2</th>
                        <th width="90">NO. PUNGGUNG 2</th>
                        @endif
                        @if($hasLampSizeAtasan)<th width="75">SIZE</th>@endif
                        @if($hasLampSizeBawahan)<th width="85">SIZE CELANA</th>@endif
                        @if($hasLampKeterangan)<th class="t-left">KETERANGAN</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($lampFilled as $i => $ns)
                    <tr>
                        <td>{{ $i + 1 }}.</td>
                        @if($hasLampNamaPunggung)
                        <td class="t-left">{{ strtoupper($ns->nama_punggung ?? '') ?: '.......' }}</td>
                        <td>{{ $ns->nomor_punggung ?: '.......' }}</td>
                        @endif
                        @if($hasLampNamaDada)
                        <td class="t-left">{{ strtoupper($ns->nama_dada ?? '') ?: '.......' }}</td>
                        <td>{{ $ns->nomor_dada ?: '.......' }}</td>
                        @endif
                        @if($hasLampNamaLengan)
                        <td class="t-left">{{ strtoupper($ns->nama_lengan ?? '') ?: '.......' }}</td>
                        <td>{{ $ns->nomor_lengan ?: '.......' }}</td>
                        @endif
                        @if($hasLampNamaPunggung2)
                        <td class="t-left">{{ strtoupper($ns->nama_punggung_2 ?? '') ?: '.......' }}</td>
                        <td>{{ $ns->nomor_punggung_2 ?: '.......' }}</td>
                        @endif
                        @if($hasLampSizeAtasan)
                        @php $sv = $ns->size ? $ns->size->ukuran : trim(last(explode('-', $ns->size_label ?? ''))); @endphp
                        <td>{{ $sv ?: '.......' }}</td>
                        @endif
                        @if($hasLampSizeBawahan)
                        @php $svc = $ns->sizeCelana ? $ns->sizeCelana->ukuran : trim(last(explode('-', $ns->size_celana_label ?? ''))); @endphp
                        <td>{{ $svc ?: '.......' }}</td>
                        @endif
                        @if($hasLampKeterangan)
                        <td class="t-left">{{ $ns->keterangan ?: '.......' }}</td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        @endforeach

    </main>
</body>
</html>
