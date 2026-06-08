<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SPK {{ $order->no_po }}</title>
    <style>
        /* === CSS TEMPLATE DASAR PDF A4 (sesuai po/index.php) === */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        @page { margin: 60px 30px 60px 30px; }

        header { position: fixed; top: -45px; left: 0; right: 0; height: 35px;
                 text-align: center; font-size: 18pt; font-weight: 900;
                 text-decoration: underline; border-bottom: 4px solid #000; padding-bottom: 8px; }
        footer { position: fixed; bottom: -45px; left: 0; right: 0; height: 30px;
                 border-top: 2px solid #000; padding-top: 8px; }
        main   { margin-top: 15px; margin-bottom: 10px; }

        body { font-family: 'DejaVu Sans', 'Helvetica', sans-serif;
               font-size: 11pt; color: #000; line-height: 1.3; text-transform: uppercase; }

        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 5px; vertical-align: top; }

        /* Info header box */
        .info-table { margin-bottom: 15px; border: 2px solid #000; }
        .info-table td { padding: 7px 10px; font-weight: 900; font-size: 12pt; }
        .colon { width: 12px; text-align: center; }

        /* Title boxes */
        .title-box        { font-weight: 900; font-size: 11pt; background: #d4d4d4; color: #000;
                            padding: 7px 10px; text-align: left;
                            border: 1px solid #000; border-bottom: none; text-transform: uppercase; }
        .title-box-center { font-weight: 900; font-size: 11pt; background: #d4d4d4; color: #000;
                            padding: 7px 10px; text-align: center;
                            border: 1px solid #000; border-bottom: none; text-transform: uppercase; }

        /* Product band */
        .product-band { background: #000; color: #fff; text-align: center; font-size: 13pt;
                        font-weight: 900; padding: 7px 10px; margin: 12px 0 0; letter-spacing: 0.5px; }

        /* Spec table */
        .spec-table { border: 1px solid #000; margin-bottom: 5px; }
        .spec-table th, .spec-table td { border: 1px solid #000; padding: 6px 8px; font-size: 11pt; text-align: center; font-weight: bold; }
        .spec-table th { background: #d4d4d4; }
        .spec-row-head { width: 28%; text-align: left !important; background: #f9f9f9; font-weight: 900; }
        .sub-header { background: #d4d4d4; text-align: center !important; padding: 7px 8px !important; font-weight: 900; letter-spacing: 1px; }

        /* Nameset table */
        .ns-table { border: 1px solid #000; margin-bottom: 5px; }
        .ns-table th, .ns-table td { border: 1px solid #000; padding: 7px 8px; font-size: 11pt; text-align: center; font-weight: bold; }
        .ns-table th { background: #d4d4d4; font-size: 12pt; }
        .t-left { text-align: left !important; padding-left: 10px !important; }

        /* Rekap size */
        .rekap-container { margin-top: 15px; font-size: 12pt; font-weight: bold; text-align: center; }
        .rekap-tabel { border: 2px solid #000; width: auto; margin: 8px auto 0; }
        .rekap-tabel td { border: 1px solid #000; padding: 7px 18px; text-align: center; font-size: 12pt; font-weight: 900; }
        .rekap-tabel th { border: 1px solid #000; padding: 7px 18px; text-align: center; font-size: 12pt; background: #d4d4d4; font-weight: 900; }

        /* Image wrapper */
        .img-wrapper { border: 2px solid #000; padding: 8px; margin: 0 0 10px; background: #fff; }
        .img-box { text-align: center; border: 1px solid #000; padding: 4px; margin-bottom: 5px; background: #fff; }
        .img-box img { max-width: 100%; object-fit: contain; }

        /* Misc */
        .page-break   { page-break-before: always; }
        .page-num:after { content: counter(page); }
        .lampiran-sub { font-weight: 900; font-size: 10pt; margin-bottom: 4px; }
    </style>
</head>
<body>

    <header>FORMAT ORDER {{ strtoupper($order->brand->nama_brand ?? 'BRAND') }}</header>

    <footer>
        <table style="width:100%; border:none; font-size:9pt; font-weight:bold;">
            <tr>
                <td style="text-align:left; width:40%;">HALAMAN <span class="page-num"></span></td>
                <td style="text-align:right; width:60%; color:#b91c1c;">NAMA ORDER: {{ strtoupper($order->nama_po) }}</td>
            </tr>
        </table>
    </footer>

    <main>

        {{-- ===== INFO BOX ===== --}}
        @php
            $printingNames = collect();
            if (!empty($order->printing_ids)) {
                $printingNames = \App\Models\Master\Printing::whereIn('id', $order->printing_ids)->pluck('nama');
            }
        @endphp
        <table class="info-table">
            <tr>
                <td style="width:50%; border-right:2px solid #000;">
                    <table style="width:100%;">
                        <tr>
                            <td style="width:155px;">TANGGAL MASUK</td>
                            <td class="colon">:</td>
                            <td>{{ \Carbon\Carbon::parse($order->tanggal_masuk)->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td>DATELINE</td>
                            <td class="colon">:</td>
                            <td>{{ \Carbon\Carbon::parse($order->deadline_customer)->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td>NAMA ORDER</td>
                            <td class="colon">:</td>
                            <td style="color:#b91c1c;">{{ strtoupper($order->nama_po) }}</td>
                        </tr>
                        <tr>
                            <td>GRAND TOTAL</td>
                            <td class="colon">:</td>
                            <td style="color:#b91c1c;">{{ $order->items->sum('quantity') }} PCS</td>
                        </tr>
                    </table>
                </td>
                <td style="width:50%; vertical-align:top; padding-left:15px;">
                    <table style="width:100%;">
                        <tr>
                            <td style="width:155px;">TIPE ORDER</td>
                            <td class="colon">:</td>
                            <td>{{ strtoupper($order->jenisOrder->nama ?? '.......') }}</td>
                        </tr>
                        <tr>
                            <td>JENIS ORDER</td>
                            <td class="colon">:</td>
                            <td>{{ $order->is_special_order ? 'SPECIAL' : 'NORMAL' }}</td>
                        </tr>
                        <tr>
                            <td>KATEGORI ITEM</td>
                            <td class="colon">:</td>
                            <td>{{ strtoupper($order->items->pluck('nama_produk')->filter()->implode(', ')) ?: '.......' }}</td>
                        </tr>
                        <tr>
                            <td>JENIS PRINTING</td>
                            <td class="colon">:</td>
                            <td>{{ $printingNames->isNotEmpty() ? strtoupper($printingNames->implode(', ')) : '.......' }}</td>
                        </tr>
                        <tr>
                            <td>NAMA BRAND</td>
                            <td class="colon">:</td>
                            <td>{{ strtoupper($order->brand->nama_brand ?? '.......') }}</td>
                        </tr>
                        @if($order->paketOrder)
                        <tr>
                            <td>PAKET ORDER</td>
                            <td class="colon">:</td>
                            <td style="color: {{ $order->paketOrder->warna ?? '#000' }}; font-weight: 900;">
                                {{ strtoupper($order->paketOrder->nama) }}
                                @if($order->paketOrder->prioritas >= 2) ⚡⚡ @elseif($order->paketOrder->prioritas >= 1) ⚡ @endif
                            </td>
                        </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        @if($order->catatan)
        <div class="title-box" style="margin-top:0;">CATATAN ORDER</div>
        <div style="border:1px solid #000; padding:8px 10px; font-weight:bold; margin-bottom:15px;">{{ strtoupper($order->catatan) }}</div>
        @endif

        {{-- ===== PER ITEM ===== --}}
        @foreach ($order->items as $idx => $item)
            @if($idx > 0)<div class="page-break"></div>@endif

            @if(!empty($item->is_addon))
                {{-- ADD-ON --}}
                <div class="product-band">ADD-ON: {{ strtoupper($item->nama_produk) }}</div>
                <table class="spec-table" style="margin-top:0;">
                    <tr>
                        <td class="spec-row-head">HARGA SATUAN</td>
                        <td>RP {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                        <td class="spec-row-head">JUMLAH (QTY)</td>
                        <td>{{ $item->quantity }}</td>
                    </tr>
                    <tr>
                        <td class="spec-row-head">KETERANGAN</td>
                        <td>{{ strtoupper($item->catatan ?? '') ?: '.......' }}</td>
                        <td class="spec-row-head">JUMLAH TOTAL</td>
                        <td style="font-weight:900;">RP {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                </table>
            @else

            @php
                $jenisSetelanMap = [
                    'stell'        => 'STELL (ATASAN + BAWAHAN)',
                    'non_stell'    => 'NON-STELL (ATASAN SAJA)',
                    'atasan_saja'  => 'ATASAN SAJA',
                    'bawahan_saja' => 'BAWAHAN SAJA',
                ];
                // Logo: dari logo_ids (multi) atau logo_id (single, fallback)
                $itemLogos = collect();
                if (!empty($item->logo_ids)) {
                    $itemLogos = \App\Models\Master\Logo::whereIn('id', $item->logo_ids)->pluck('nama');
                } elseif ($item->logo_id) {
                    $itemLogos = collect([$item->logo?->nama])->filter();
                }
                $logoStr = $itemLogos->isNotEmpty()
                    ? strtoupper($itemLogos->implode(', '))
                    : '.......';
            @endphp

            <div class="product-band">
                PRODUK: {{ strtoupper($item->nama_produk) }}@if($item->varian_label) — {{ strtoupper($item->varian_label) }}@endif
            </div>

            {{-- SPESIFIKASI --}}
            <div class="title-box" style="margin-top:0;">SPESIFIKASI ({{ strtoupper($item->nama_produk) }})</div>
            <table class="spec-table" style="margin-top:0;">
                <tr>
                    <td class="spec-row-head">JENIS PESANAN</td>
                    <td>{{ strtoupper($item->varian_label ?? '') ?: '.......' }}</td>
                    <td class="spec-row-head">JENIS SETELAN</td>
                    <td>{{ $item->jenisSetelan ? strtoupper($item->jenisSetelan->nama) : strtoupper($item->jenis_setelan ?? '.......') }}</td>
                </tr>
                <tr>
                    <td class="spec-row-head">POLA</td>
                    <td>{{ $item->polaProduksi ? strtoupper($item->polaProduksi->nama) : (strtoupper($item->pola ?? '') ?: '.......') }}</td>
                    <td class="spec-row-head">BAHAN ATASAN</td>
                    @php
                        $bahanAtasanStr = !empty($item->bahan_kain_ids)
                            ? strtoupper(\App\Models\Master\BahanKain::whereIn('id', $item->bahan_kain_ids)->pluck('nama')->implode(', '))
                            : strtoupper($item->bahanKain->nama ?? '.......');
                    @endphp
                    <td>{{ $bahanAtasanStr }}</td>
                </tr>
                @php
                    $bahanBawahanStr = !empty($item->bahan_kain_bawahan_ids)
                        ? strtoupper(\App\Models\Master\BahanKain::whereIn('id', $item->bahan_kain_bawahan_ids)->pluck('nama')->implode(', '))
                        : strtoupper($item->bahanKainBawahan?->nama ?? '');
                @endphp
                @if(!empty($bahanBawahanStr))
                <tr>
                    <td class="spec-row-head">BAHAN BAWAHAN</td>
                    <td colspan="3">{{ $bahanBawahanStr }}</td>
                </tr>
                @endif
                <tr>
                    <td class="spec-row-head">WARNA</td>
                    <td>{{ strtoupper($item->warna ?? '') ?: '.......' }}</td>
                    <td class="spec-row-head">JENIS LOGO</td>
                    <td>{{ $logoStr }}</td>
                </tr>
                <tr>
                    <td class="spec-row-head">JUMLAH ATASAN</td>
                    <td>{{ $item->jml_atasan ?: $item->quantity }}</td>
                    <td class="spec-row-head">JUMLAH BAWAHAN</td>
                    <td>{{ $item->jml_bawahan ?: '.......' }}</td>
                </tr>
                <tr>
                    <td class="spec-row-head">JENIS RIB</td>
                    <td>{{ strtoupper($item->jenis_rib ?? '') ?: '.......' }}</td>
                    <td class="spec-row-head">TUTUP KERAH</td>
                    <td>{{ strtoupper($item->tutup_kerah ?? '') ?: '.......' }}</td>
                </tr>
                <tr>
                    <td class="spec-row-head">LIST KERAH</td>
                    <td>{{ strtoupper($item->list_kerah ?? '') ?: '.......' }}</td>
                    <td class="spec-row-head">LIST LENGAN</td>
                    <td>{{ strtoupper($item->list_lengan ?? '') ?: '.......' }}</td>
                </tr>
                <tr>
                    <td class="spec-row-head">LIST SAMPING CELANA</td>
                    <td>{{ strtoupper($item->list_samping_celana ?? '') ?: '.......' }}</td>
                    <td class="spec-row-head">LIST BAWAH CELANA</td>
                    <td>{{ strtoupper($item->list_bawah_celana ?? '') ?: '.......' }}</td>
                </tr>
                <tr><td class="sub-header" colspan="4">KETERANGAN JAHITAN</td></tr>
                <tr>
                    <td class="spec-row-head">POLA JAHITAN</td>
                    <td>{{ $item->polaJahitan ? strtoupper($item->polaJahitan->jenis_pola . ' — ' . $item->polaJahitan->nama) : '.......' }}</td>
                    <td class="spec-row-head">JAHITAN LIST LENGAN</td>
                    <td>{{ $item->polaJahitanLengan ? strtoupper($item->polaJahitanLengan->nama) : (strtoupper($item->jahitan_list_lengan ?? '') ?: '.......') }}</td>
                </tr>
                <tr><td class="sub-header" colspan="4">KETERANGAN RESLETING</td></tr>
                <tr>
                    <td class="spec-row-head">JENIS RESLETING</td>
                    <td colspan="3">{{ strtoupper($item->resleting->nama ?? '.......') }}</td>
                </tr>
            </table>

            {{-- REFERENSI DESAIN & KERAH --}}
            @if($item->gambar_desain || $item->ket_atasan || $item->ket_bawahan || $item->jenis_kerah || $item->gambar_kerah || $item->gambar_ket_tambahan)
            <div class="page-break"></div>

            <div class="img-wrapper" style="padding:6px; margin-bottom:0;">
                <div class="title-box-center" style="margin-top:0;">
                    REFERENSI DESAIN {{ strtoupper($item->nama_produk) }} — {{ strtoupper($item->varian_label ?? '') }}
                </div>

                @if($item->gambar_desain)
                    @php $dPath = storage_path('app/public/' . $item->gambar_desain); @endphp
                    @if(file_exists($dPath))
                    <div class="img-box" style="border-top:none; padding:2px; margin-bottom:6px;">
                        <img src="{{ $dPath }}" style="max-width:100%; max-height:850px; object-fit:contain;">
                    </div>
                    @else
                    <div class="img-box" style="border-top:none; height:300px; line-height:300px; color:#999; font-weight:bold;">[ GAMBAR DESAIN TIDAK DITEMUKAN ]</div>
                    @endif
                @else
                <div class="img-box" style="border-top:none; height:250px; line-height:250px; color:#999; font-weight:bold;">[ GAMBAR DESAIN BELUM DIUNGGAH ]</div>
                @endif

                <table style="width:100%; border-collapse:collapse; border:1px solid #000; font-size:11pt; margin-bottom:8px;">
                    <tr>
                        <td style="width:50%; vertical-align:top; padding:8px; border-right:1px solid #000;">
                            <div style="background:#d4d4d4; font-weight:900; padding:4px; text-align:center; margin-bottom:4px; border:1px solid #000;">KETERANGAN ATASAN</div>
                            <div style="padding:2px; text-align:center; font-weight:bold;">{{ strtoupper($item->ket_atasan ?? '') ?: '.......' }}</div>
                        </td>
                        <td style="width:50%; vertical-align:top; padding:8px;">
                            <div style="background:#d4d4d4; font-weight:900; padding:4px; text-align:center; margin-bottom:4px; border:1px solid #000;">KETERANGAN BAWAHAN</div>
                            <div style="padding:2px; text-align:center; font-weight:bold;">{{ strtoupper($item->ket_bawahan ?? '') ?: '.......' }}</div>
                        </td>
                    </tr>
                </table>

                <div class="title-box-center">
                    REFERENSI KERAH {{ strtoupper($item->nama_produk) }} — {{ strtoupper($item->varian_label ?? '') }}
                </div>
                <table style="width:100%; border-collapse:collapse; border:1px solid #000; border-top:none; font-size:11pt;">
                    <tr>
                        <td style="width:45%; vertical-align:middle; padding:10px; border-right:1px solid #000;">
                            <div style="background:#d4d4d4; font-weight:900; padding:6px; text-align:center; margin-bottom:8px; border:1px solid #000;">JENIS KERAH</div>
                            <div style="padding:4px; text-align:center; font-weight:900; font-size:13pt;">{{ strtoupper($item->jenis_kerah ?? '') ?: '.......' }}</div>
                        </td>
                        <td style="width:55%; vertical-align:middle; padding:4px; text-align:center;">
                            @if($item->gambar_kerah)
                                @php $kPath = storage_path('app/public/' . $item->gambar_kerah); @endphp
                                @if(file_exists($kPath))
                                <img src="{{ $kPath }}" style="max-width:95%; max-height:150px; object-fit:contain;">
                                @endif
                            @endif
                            @if($item->gambar_ket_tambahan)
                                @php $ktPath = storage_path('app/public/' . $item->gambar_ket_tambahan); @endphp
                                @if(file_exists($ktPath))
                                <img src="{{ $ktPath }}" style="max-width:95%; max-height:150px; object-fit:contain; margin-top:4px;">
                                @endif
                            @endif
                            @if(!$item->gambar_kerah && !$item->gambar_ket_tambahan)
                            <div style="height:100px; line-height:100px; color:#999; font-weight:bold;">[ GAMBAR KERAH BELUM DIUNGGAH ]</div>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
            @endif

            {{-- NAMESET TABLE --}}
            @php
                $filled = $item->namesets->filter(fn($ns) =>
                    !empty($ns->nama_punggung) || !empty($ns->nomor_punggung) ||
                    !empty($ns->nama_dada)     || !empty($ns->nomor_dada)     ||
                    !empty($ns->size_id)       || !empty($ns->size_label)     || !empty($ns->keterangan)
                );

                $hasNamaPunggung = $filled->contains(fn($ns) => !empty($ns->nama_punggung) || !empty($ns->nomor_punggung));
                $hasNamaDada     = $filled->contains(fn($ns) => !empty($ns->nama_dada)     || !empty($ns->nomor_dada));
                $hasSizeAtasan   = $filled->contains(fn($ns) => !empty($ns->size_id)       || !empty($ns->size_label));
                $hasSizeBawahan  = $filled->contains(fn($ns) => !empty($ns->size_celana_id)|| !empty($ns->size_celana_label));
                $hasKeterangan   = $filled->contains(fn($ns) => !empty($ns->keterangan));

                $standarSizes = ['XS ANAK','S ANAK','M ANAK','L ANAK','XL ANAK',
                                 'XS','S','M','L','XL','2XL','3XL','4XL','5XL','6XL','7XL','8XL','9XL','10XL'];
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
                // Sort by standard order
                $sizeAtasanRecap = [];
                foreach ($standarSizes as $s) { if (isset($sizeAtasanRaw[$s])) $sizeAtasanRecap[$s] = $sizeAtasanRaw[$s]; }
                foreach ($sizeAtasanRaw as $s => $c) { if (!in_array($s, $standarSizes)) $sizeAtasanRecap[$s] = $c; }

                $sizeBawahanRecap = [];
                foreach ($standarSizes as $s) { if (isset($sizeBawahanRaw[$s])) $sizeBawahanRecap[$s] = $sizeBawahanRaw[$s]; }
                foreach ($sizeBawahanRaw as $s => $c) { if (!in_array($s, $standarSizes)) $sizeBawahanRecap[$s] = $c; }
            @endphp

            @if($filled->count())
            <div class="page-break"></div>

            <div class="title-box" style="text-align:center; font-size:13pt;">
                DATA PESANAN {{ strtoupper($item->nama_produk) }} — {{ strtoupper($item->varian_label ?? '') }}
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
                        <td style="font-weight:900;">{{ $ns->nomor_punggung ?: '.......' }}</td>
                        @endif
                        @if($hasNamaDada)
                        <td class="t-left">{{ strtoupper($ns->nama_dada ?? '') ?: '.......' }}</td>
                        <td style="font-weight:900;">{{ $ns->nomor_dada ?: '.......' }}</td>
                        @endif
                        @if($hasSizeAtasan)
                        @php $sv = $ns->size ? $ns->size->ukuran : trim(last(explode('-', $ns->size_label ?? ''))); @endphp
                        <td style="font-weight:900;">{{ $sv ?: '.......' }}</td>
                        @endif
                        @if($hasSizeBawahan)
                        @php $svc = $ns->sizeCelana ? $ns->sizeCelana->ukuran : trim(last(explode('-', $ns->size_celana_label ?? ''))); @endphp
                        <td style="font-weight:900;">{{ $svc ?: '.......' }}</td>
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
                    @if($hasSizeBawahan)<div style="font-size:9pt; margin:5px 0 3px;">REKAP SIZE ATASAN</div>@endif
                    @foreach(array_chunk($sizeAtasanRecap, 10, true) as $chunk)
                    <table class="rekap-tabel">
                        <thead><tr>@foreach($chunk as $sz => $c)<th>{{ $sz }}</th>@endforeach</tr></thead>
                        <tbody><tr>@foreach($chunk as $c)<td>{{ $c }}</td>@endforeach</tr></tbody>
                    </table>
                    @endforeach
                @endif

                @if(count($sizeBawahanRecap))
                    <div style="font-size:9pt; margin:10px 0 3px;">REKAP SIZE BAWAHAN</div>
                    @foreach(array_chunk($sizeBawahanRecap, 10, true) as $chunk)
                    <table class="rekap-tabel">
                        <thead><tr>@foreach($chunk as $sz => $c)<th>{{ $sz }}</th>@endforeach</tr></thead>
                        <tbody><tr>@foreach($chunk as $c)<td>{{ $c }}</td>@endforeach</tr></tbody>
                    </table>
                    @endforeach
                @endif
            </div>
            @endif

            @endif {{-- end non-addon --}}
        @endforeach

        {{-- ===== CHECKLIST PRODUKSI ===== --}}
        @php
            $progresses   = \App\Models\Master\Progress::active()->ordered()->get();
            $produkItems  = $order->items->filter(fn($i) => empty($i->is_addon))->values();
        @endphp
        @if($progresses->count() && $produkItems->count())
        <div class="page-break"></div>

        <div style="font-size:13pt; font-weight:900; text-decoration:underline; margin-bottom:10px; border-bottom:2px solid #000; padding-bottom:4px; text-align:center;">
            CHECKLIST PRODUKSI — {{ strtoupper($order->nama_po) }}
        </div>

        <table style="width:100%; border-collapse:collapse; font-size:10pt;">
            <thead>
                <tr style="background:#000; color:#fff; font-weight:900;">
                    <th style="border:1.5px solid #000; padding:6px 8px; text-align:center; width:35px;">NO</th>
                    <th style="border:1.5px solid #000; padding:6px 8px; text-align:left; width:180px;">PROSES</th>
                    @foreach($produkItems as $pi)
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
                    @foreach($produkItems as $pi)
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

        @foreach ($order->items as $item)
            @if(empty($item->is_addon))
                @php
                    $lampFilled = $item->namesets->filter(fn($ns) =>
                        !empty($ns->nama_punggung) || !empty($ns->nomor_punggung) ||
                        !empty($ns->size_id)       || !empty($ns->size_label)
                    );
                @endphp
                @if($lampFilled->count())
                <div class="lampiran-sub">DATA PESANAN: {{ strtoupper($item->nama_produk) }} — {{ strtoupper($item->varian_label ?? '') }}</div>
                <table class="ns-table" style="margin-bottom:12px;">
                    <thead>
                        <tr>
                            <th width="40">NO.</th>
                            <th class="t-left">NAMA PUNGGUNG</th>
                            <th width="90">NO. PUNGGUNG</th>
                            <th width="75">SIZE</th>
                            <th class="t-left">KETERANGAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lampFilled as $i => $ns)
                        <tr>
                            <td>{{ $i + 1 }}.</td>
                            <td class="t-left">{{ strtoupper($ns->nama_punggung ?? '') ?: '.......' }}</td>
                            <td style="font-weight:900;">{{ $ns->nomor_punggung ?: '.......' }}</td>
                            @php $sv = $ns->size ? $ns->size->ukuran : trim(last(explode('-', $ns->size_label ?? ''))); @endphp
                            <td style="font-weight:900;">{{ $sv ?: '.......' }}</td>
                            <td class="t-left">{{ $ns->keterangan ?: '.......' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            @endif
        @endforeach

    </main>
</body>
</html>
