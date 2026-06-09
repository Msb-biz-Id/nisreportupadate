<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SPK DRAFT - {{ $brand->nama_brand }}</title>
    <style>
        /* === CSS TEMPLATE DASAR PDF A4 (sesuai po/index.php) === */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        @page { margin: 60px 45px 60px 45px; }

        header { position: fixed; top: -45px; left: 0; right: 0; height: 35px;
                 text-align: center; font-size: 18pt; font-weight: bold;
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
        .info-table td { padding: 5px 8px; font-weight: normal; font-size: 11pt; }
        .info-table table td:first-child { font-weight: bold; }
        .colon { width: 12px; text-align: center; font-weight: bold; }

        /* Draft badge */
        .draft-badge { display: inline-block; background: #FEF3C7; color: #92400E;
                       border: 2px solid #F59E0B; font-size: 10pt; font-weight: bold;
                       padding: 2px 10px; border-radius: 3px; vertical-align: middle; margin-left: 10px; }

        /* Title boxes */
        .title-box        { font-weight: bold; font-size: 11pt; background: #d4d4d4; color: #000;
                            padding: 7px 10px; text-align: left;
                            border: 1px solid #000; border-bottom: none; text-transform: uppercase; }
        .title-box-center { font-weight: bold; font-size: 11pt; background: #d4d4d4; color: #000;
                            padding: 7px 10px; text-align: center;
                            border: 1px solid #000; border-bottom: none; text-transform: uppercase; }

        /* Product band */
        .product-band { background: #000; color: #fff; text-align: center; font-size: 13pt;
                        font-weight: bold; padding: 7px 10px; margin: 12px 0 0; letter-spacing: 0.5px; }

        /* Spec table */
        .spec-table { border: 1px solid #000; margin-bottom: 5px; }
        .spec-table th, .spec-table td { border: 1px solid #000; padding: 6px 8px; font-size: 11pt; text-align: center; font-weight: normal; }
        .spec-table th { background: #d4d4d4; }
        .spec-row-head { width: 30%; text-align: left !important; background: #fff; font-weight: bold; }
        .sub-header { background: #d4d4d4; text-align: center !important; padding: 7px 8px !important; font-weight: bold; letter-spacing: 1px; }

        /* Nameset table */
        .ns-table { border: 1px solid #000; margin-bottom: 5px; }
        .ns-table th, .ns-table td { border: 1px solid #000; padding: 7px 8px; font-size: 11pt; text-align: center; font-weight: normal; }
        .ns-table th { background: #d4d4d4; font-size: 11pt; font-weight: bold; }
        .t-left { text-align: left !important; padding-left: 10px !important; }

        /* Rekap size */
        .rekap-container { margin-top: 15px; font-size: 11pt; font-weight: bold; text-align: center; }
        .rekap-tabel { border: 2px solid #000; width: auto; margin: 8px auto 0; }
        .rekap-tabel td { border: 1px solid #000; padding: 7px 18px; text-align: center; font-size: 11pt; font-weight: normal; }
        .rekap-tabel th { border: 1px solid #000; padding: 7px 18px; text-align: center; font-size: 11pt; background: #d4d4d4; font-weight: bold; }

        /* Image wrapper */
        .img-wrapper { border: 2px solid #000; padding: 8px; margin: 0 0 10px; background: #fff; }
        .img-box { text-align: center; border: 1px solid #000; padding: 4px; margin-bottom: 5px; background: #fff; }
        .img-box img { max-width: 100%; object-fit: contain; }

        /* Misc */
        .page-break   { page-break-before: always; }
        .page-num:after { content: counter(page); }
        .lampiran-sub { font-weight: bold; font-size: 10pt; margin-bottom: 4px; }
    </style>
</head>
<body>

    <header>FORMAT ORDER {{ strtoupper($brand->nama_brand) }} <span class="draft-badge">⚠ DRAFT</span></header>

    <footer>
        <table style="width:100%; border:none; font-size:9pt; font-weight:bold;">
            <tr>
                <td style="text-align:left; width:40%;">HALAMAN <span class="page-num"></span></td>
                <td style="text-align:right; width:60%; color:#b91c1c;">NAMA ORDER: {{ strtoupper($raw['nama_po'] ?? '—') }}</td>
            </tr>
        </table>
    </footer>

    <main>

        {{-- ===== INFO BOX ===== --}}
        @php
            $printingNames = collect();
            if (!empty($raw['printing_ids'])) {
                $printingNames = \App\Models\Master\Printing::whereIn('id', $raw['printing_ids'])->pluck('nama');
            }
            $grandTotal = collect($items)->filter(fn($i) => empty($i['is_addon']))->sum(fn($i) => (int)($i['quantity'] ?? 0));
            $kategoriStr = collect($items)->filter(fn($i) => empty($i['is_addon']))->pluck('nama_produk')->filter()->map('strtoupper')->implode(', ');
        @endphp
        <table class="info-table">
            <tr>
                <td style="width:50%; border-right:2px solid #000;">
                    <table style="width:100%;">
                        <tr>
                            <td style="width:155px;">TANGGAL MASUK</td>
                            <td class="colon">:</td>
                            <td>{{ !empty($raw['tanggal_masuk']) ? \Carbon\Carbon::parse($raw['tanggal_masuk'])->format('d/m/Y') : '.......' }}</td>
                        </tr>
                        <tr>
                            <td>DATELINE</td>
                            <td class="colon">:</td>
                            <td>{{ !empty($raw['deadline_customer']) ? \Carbon\Carbon::parse($raw['deadline_customer'])->format('d/m/Y') : '.......' }}</td>
                        </tr>
                        <tr>
                            <td>NAMA ORDER</td>
                            <td class="colon">:</td>
                            <td style="color:#b91c1c;">{{ strtoupper($raw['nama_po'] ?? '') ?: '.......' }}</td>
                        </tr>
                        <tr>
                            <td>GRAND TOTAL</td>
                            <td class="colon">:</td>
                            <td style="color:#b91c1c;">{{ $grandTotal > 0 ? $grandTotal . ' PCS' : '.......' }}</td>
                        </tr>
                    </table>
                </td>
                <td style="width:50%; vertical-align:top; padding-left:15px;">
                    <table style="width:100%;">
                        <tr>
                            <td style="width:155px;">TIPE ORDER</td>
                            <td class="colon">:</td>
                            <td>{{ strtoupper($jenisOrder->nama ?? '') ?: '.......' }}</td>
                        </tr>
                        <tr>
                            <td>JENIS ORDER</td>
                            <td class="colon">:</td>
                            <td>{{ !empty($raw['is_special_order']) ? 'SPECIAL' : 'NORMAL' }}</td>
                        </tr>
                        <tr>
                            <td>KATEGORI ITEM</td>
                            <td class="colon">:</td>
                            <td>{{ $kategoriStr ?: '.......' }}</td>
                        </tr>
                        <tr>
                            <td>JENIS PRINTING</td>
                            <td class="colon">:</td>
                            <td>{{ $printingNames->isNotEmpty() ? strtoupper($printingNames->implode(', ')) : '.......' }}</td>
                        </tr>
                        <tr>
                            <td>NAMA BRAND</td>
                            <td class="colon">:</td>
                            <td>{{ strtoupper($brand->nama_brand) }}</td>
                        </tr>
                        @if(isset($paketOrder) && $paketOrder)
                        <tr>
                            <td>PAKET ORDER</td>
                            <td class="colon">:</td>
                            <td style="color: {{ $paketOrder->warna ?? '#000' }}; font-weight: 900;">
                                {{ strtoupper($paketOrder->nama) }}
                                @if($paketOrder->prioritas >= 2) ⚡⚡ @elseif($paketOrder->prioritas >= 1) ⚡ @endif
                            </td>
                        </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        @if(!empty($raw['catatan']))
        <div class="title-box" style="margin-top:0;">CATATAN ORDER</div>
        <div style="border:1px solid #000; padding:8px 10px; font-weight:bold; margin-bottom:15px;">{{ strtoupper($raw['catatan']) }}</div>
        @endif

        {{-- ===== PER ITEM ===== --}}
        @foreach ($items as $idx => $item)
            @if($idx > 0)<div class="page-break"></div>@endif

            @php
                $dv = fn($v) => trim((string)($v ?? '')) ?: '.......';
                $jenisSetelanMap = [
                    'stell'        => 'STELL (ATASAN + BAWAHAN)',
                    'non_stell'    => 'NON-STELL (ATASAN SAJA)',
                    'atasan_saja'  => 'ATASAN SAJA',
                    'bawahan_saja' => 'BAWAHAN SAJA',
                ];
            @endphp

            @if(!empty($item['is_addon']))
                {{-- ADD-ON --}}
                <div class="product-band">ADD-ON: {{ strtoupper($item['nama_produk'] ?? '') }}</div>
                <table class="spec-table" style="margin-top:0;">
                    <tr>
                        <td class="spec-row-head">HARGA SATUAN</td>
                        <td>RP {{ number_format($item['harga_satuan'] ?? 0, 0, ',', '.') }}</td>
                        <td class="spec-row-head">JUMLAH (QTY)</td>
                        <td>{{ $item['quantity'] ?? 0 }}</td>
                    </tr>
                    <tr>
                        <td class="spec-row-head">KETERANGAN</td>
                        <td>{{ $dv(strtoupper($item['catatan'] ?? '')) }}</td>
                        <td class="spec-row-head">JUMLAH TOTAL</td>
                        <td style="font-weight:900;">RP {{ number_format(($item['harga_satuan'] ?? 0) * ($item['quantity'] ?? 0), 0, ',', '.') }}</td>
                    </tr>
                </table>
            @else

            @php
                $logoDisplay = !empty($item['_logos']) ? strtoupper(implode(', ', $item['_logos'])) : strtoupper($item['_logo'] ?? '');
                $logoDisplay = $logoDisplay ?: '.......';
            @endphp

            <div class="product-band">
                PRODUK: {{ strtoupper($item['nama_produk'] ?? '') }}@if(!empty($item['varian_label'])) — {{ strtoupper($item['varian_label']) }}@endif
            </div>

            {{-- SPESIFIKASI --}}
            <table class="spec-table" style="margin-top:0;">
                <thead>
                    <tr>
                        <th class="sub-header" colspan="2" style="text-align: left !important; padding-left: 10px !important;">
                            SPESIFIKASI ({{ strtoupper($item['nama_produk'] ?? '') }})
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="spec-row-head">JENIS SETELAN</td>
                        <td>{{ $dv(strtoupper($item['_jenis_setelan'] ?? $item['jenis_setelan'] ?? '')) }}</td>
                    </tr>
                    <tr>
                        <td class="spec-row-head">POLA</td>
                        <td>{{ $dv(strtoupper($item['_pola_produksi'] ?? $item['pola'] ?? '')) }}</td>
                    </tr>
                    <tr>
                        <td class="spec-row-head">BAHAN ATASAN</td>
                        <td>{{ $dv(strtoupper($item['_bahan_kain_names'] ?? $item['_bahan_kain'] ?? '')) }}</td>
                    </tr>
                    @php $bahanBawahan = $item['_bahan_kain_bawahan_names'] ?? $item['_bahan_kain_bawahan'] ?? null; @endphp
                    @if(!empty($bahanBawahan))
                    <tr>
                        <td class="spec-row-head">BAHAN BAWAHAN</td>
                        <td>{{ strtoupper($bahanBawahan) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="spec-row-head">WARNA</td>
                        <td>{{ $dv(strtoupper($item['warna'] ?? '')) }}</td>
                    </tr>
                    <tr>
                        <td class="spec-row-head">JENIS LOGO</td>
                        <td>{{ $logoDisplay }}</td>
                    </tr>
                    <tr>
                        <td class="spec-row-head">JUMLAH ATASAN</td>
                        <td>{{ $item['jml_atasan'] ?: ($item['quantity'] ?? '.......') }}</td>
                    </tr>
                    <tr>
                        <td class="spec-row-head">JUMLAH BAWAHAN</td>
                        <td>{{ $item['jml_bawahan'] ?: '.......' }}</td>
                    </tr>
                    <tr>
                        <td class="spec-row-head">JENIS RIB</td>
                        <td>{{ $dv(strtoupper($item['jenis_rib'] ?? '')) }}</td>
                    </tr>
                    <tr>
                        <td class="spec-row-head">TUTUP KERAH</td>
                        <td>{{ $dv(strtoupper($item['tutup_kerah'] ?? '')) }}</td>
                    </tr>
                    <tr>
                        <td class="spec-row-head">LIST KERAH</td>
                        <td>{{ $dv(strtoupper($item['list_kerah'] ?? '')) }}</td>
                    </tr>
                    <tr>
                        <td class="spec-row-head">LIST LENGAN</td>
                        <td>{{ $dv(strtoupper($item['list_lengan'] ?? '')) }}</td>
                    </tr>
                    <tr>
                        <td class="spec-row-head">LIST SAMPING CELANA</td>
                        <td>{{ $dv(strtoupper($item['list_samping_celana'] ?? '')) }}</td>
                    </tr>
                    <tr>
                        <td class="spec-row-head">LIST BAWAH CELANA</td>
                        <td>{{ $dv(strtoupper($item['list_bawah_celana'] ?? '')) }}</td>
                    </tr>
                    <tr>
                        <th class="sub-header" colspan="2">KETERANGAN JAHITAN</th>
                    </tr>
                    @php
                        $polaJ = $item['_pola_jahitan'];
                        $polaJStr = $polaJ ? strtoupper($polaJ->jenis_pola . ' — ' . $polaJ->nama) : '.......';
                        $polaJLengan = $item['_pola_jahitan_lengan'] ?? null;
                        $lenganStr = $polaJLengan ? strtoupper($polaJLengan->nama) : $dv(strtoupper($item['jahitan_list_lengan'] ?? ''));
                    @endphp
                    <tr>
                        <td class="spec-row-head">POLA JAHITAN</td>
                        <td>{{ $polaJStr }}</td>
                    </tr>
                    <tr>
                        <td class="spec-row-head">JAHITAN LIST LENGAN</td>
                        <td>{{ $lenganStr }}</td>
                    </tr>
                    <tr>
                        <th class="sub-header" colspan="2">KETERANGAN RESLETING</th>
                    </tr>
                    <tr>
                        <td class="spec-row-head">JENIS RESLETING</td>
                        <td>{{ $dv(strtoupper($item['_resleting'] ?? '')) }}</td>
                    </tr>
                </tbody>
            </table>

            {{-- REFERENSI DESAIN & KERAH --}}
            @php
                $hasDesain = !empty($item['gambar_desain']) || !empty($item['ket_atasan']) || !empty($item['ket_bawahan'])
                          || !empty($item['jenis_kerah']) || !empty($item['gambar_kerah']) || !empty($item['gambar_ket_tambahan']);
            @endphp
            @if($hasDesain)
            <div class="page-break"></div>

            @if(!empty($item['gambar_desain']) || !empty($item['ket_atasan']) || !empty($item['ket_bawahan']))
            <div class="img-wrapper" style="padding:6px; margin-bottom:12px;">
                <div class="title-box-center" style="margin-top:0;">
                    REFERENSI DESAIN {{ strtoupper($item['nama_produk'] ?? '') }} — {{ strtoupper($item['varian_label'] ?? '') }}
                </div>

                @if(!empty($item['gambar_desain']))
                    @php $dPath = storage_path('app/public/' . $item['gambar_desain']); @endphp
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

                <table style="width:100%; border-collapse:collapse; border:1px solid #000; font-size:11pt; margin-bottom:0;">
                    <tr>
                        <td style="width:50%; vertical-align:top; padding:8px; border-right:1px solid #000;">
                            <div style="background:#d4d4d4; font-weight:900; padding:4px; text-align:center; margin-bottom:4px; border:1px solid #000;">KETERANGAN ATASAN</div>
                            <div style="padding:2px; text-align:center; font-weight:bold;">{{ $dv(strtoupper($item['ket_atasan'] ?? '')) }}</div>
                        </td>
                        <td style="width:50%; vertical-align:top; padding:8px;">
                            <div style="background:#d4d4d4; font-weight:900; padding:4px; text-align:center; margin-bottom:4px; border:1px solid #000;">KETERANGAN BAWAHAN</div>
                            <div style="padding:2px; text-align:center; font-weight:bold;">{{ $dv(strtoupper($item['ket_bawahan'] ?? '')) }}</div>
                        </td>
                    </tr>
                </table>
            </div>
            @endif

            @if(!empty($item['jenis_kerah']) || !empty($item['gambar_kerah']))
            <div class="img-wrapper" style="padding:6px; margin-bottom:12px;">
                <div class="title-box-center" style="margin-top:0;">
                    REFERENSI KERAH {{ strtoupper($item['nama_produk'] ?? '') }} — {{ strtoupper($item['varian_label'] ?? '') }}
                </div>
                <div style="border:1px solid #000; border-top:none; padding:10px; text-align:center; background:#fff;">
                    <div style="font-size:12pt; font-weight:bold; margin-bottom:10px;">
                        JENIS KERAH: <span style="font-weight:normal;">{{ strtoupper($item['jenis_kerah'] ?? '.......') }}</span>
                    </div>
                    @if(!empty($item['gambar_kerah']))
                        @php $kPath = storage_path('app/public/' . $item['gambar_kerah']); @endphp
                        @if(file_exists($kPath))
                        <div style="text-align:center; margin-top:8px;">
                            <img src="{{ $kPath }}" style="max-width:100%; max-height:350px; object-fit:contain;">
                        </div>
                        @else
                        <div style="color:#999; font-weight:bold; padding:10px;">[ GAMBAR KERAH TIDAK DITEMUKAN ]</div>
                        @endif
                    @else
                    <div style="color:#999; font-weight:bold; padding:10px;">[ GAMBAR KERAH BELUM DIUNGGAH ]</div>
                    @endif
                </div>
            </div>
            @endif

            @if(!empty($item['gambar_ket_tambahan']))
            <div class="img-wrapper" style="padding:6px; margin-bottom:0;">
                <div class="title-box-center" style="margin-top:0;">
                    KETERANGAN TAMBAHAN GAMBAR {{ strtoupper($item['nama_produk'] ?? '') }} — {{ strtoupper($item['varian_label'] ?? '') }}
                </div>
                <div style="border:1px solid #000; border-top:none; padding:10px; text-align:center; background:#fff;">
                    @php $ktPath = storage_path('app/public/' . $item['gambar_ket_tambahan']); @endphp
                    @if(file_exists($ktPath))
                    <div style="text-align:center;">
                        <img src="{{ $ktPath }}" style="max-width:100%; max-height:450px; object-fit:contain;">
                    </div>
                    @else
                    <div style="color:#999; font-weight:bold; padding:10px;">[ GAMBAR KETERANGAN TAMBAHAN TIDAK DITEMUKAN ]</div>
                    @endif
                </div>
            </div>
            @endif
            @endif

            {{-- NAMESET TABLE --}}
            @php
                $namesets = collect($item['namesets'] ?? []);
                $filled   = $namesets->filter(fn($ns) =>
                    !empty($ns['nama_punggung']) || !empty($ns['nomor_punggung']) ||
                    !empty($ns['nama_dada'])     || !empty($ns['nomor_dada'])     ||
                    !empty($ns['nama_lengan'])   || !empty($ns['nomor_lengan'])   ||
                    !empty($ns['nama_punggung_2']) || !empty($ns['nomor_punggung_2']) ||
                    !empty($ns['size_id'])       || !empty($ns['size_label'])     ||
                    !empty($ns['size_celana_id'])|| !empty($ns['size_celana_label']) ||
                    !empty($ns['keterangan'])
                );

                $hasNamaPunggung = $filled->contains(fn($ns) => !empty($ns['nama_punggung']) || !empty($ns['nomor_punggung']));
                $hasNamaDada     = $filled->contains(fn($ns) => !empty($ns['nama_dada'])     || !empty($ns['nomor_dada']));
                $hasNamaLengan   = $filled->contains(fn($ns) => !empty($ns['nama_lengan'])   || !empty($ns['nomor_lengan']));
                $hasNamaPunggung2 = $filled->contains(fn($ns) => !empty($ns['nama_punggung_2']) || !empty($ns['nomor_punggung_2']));
                $hasSizeAtasan   = $filled->contains(fn($ns) => !empty($ns['size_id'])       || !empty($ns['size_label']));
                $hasSizeBawahan  = $filled->contains(fn($ns) => !empty($ns['size_celana_id'])|| !empty($ns['size_celana_label']));
                $hasKeterangan   = $filled->contains(fn($ns) => !empty($ns['keterangan']));

                $standarSizes = ['XS ANAK','S ANAK','M ANAK','L ANAK','XL ANAK',
                                 'XS','S','M','L','XL','2XL','3XL','4XL','5XL','6XL','7XL','8XL','9XL','10XL'];
                $sizeAtasanRaw  = [];
                $sizeBawahanRaw = [];
                foreach ($filled as $ns) {
                    if (!empty($ns['size_id']) || !empty($ns['size_label'])) {
                        $parts = explode('-', $ns['_size_label'] ?? $ns['size_label'] ?? '');
                        $sz = trim(end($parts));
                        if ($sz) $sizeAtasanRaw[$sz] = ($sizeAtasanRaw[$sz] ?? 0) + 1;
                    }
                    if (!empty($ns['size_celana_id']) || !empty($ns['size_celana_label'])) {
                        $parts = explode('-', $ns['_size_celana_label'] ?? $ns['size_celana_label'] ?? '');
                        $sz = trim(end($parts));
                        if ($sz) $sizeBawahanRaw[$sz] = ($sizeBawahanRaw[$sz] ?? 0) + 1;
                    }
                }
                // Sort standard sizes first
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
                DATA PESANAN {{ strtoupper($item['nama_produk'] ?? '') }} @if(!empty($item['varian_label'])) — {{ strtoupper($item['varian_label']) }}@endif
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
                        <td class="t-left">{{ strtoupper($ns['nama_punggung'] ?? '') ?: '.......' }}</td>
                        <td>{{ $ns['nomor_punggung'] ?? '.......' }}</td>
                        @endif
                        @if($hasNamaDada)
                        <td class="t-left">{{ strtoupper($ns['nama_dada'] ?? '') ?: '.......' }}</td>
                        <td>{{ $ns['nomor_dada'] ?? '.......' }}</td>
                        @endif
                        @if($hasNamaLengan)
                        <td class="t-left">{{ strtoupper($ns['nama_lengan'] ?? '') ?: '.......' }}</td>
                        <td>{{ $ns['nomor_lengan'] ?? '.......' }}</td>
                        @endif
                        @if($hasNamaPunggung2)
                        <td class="t-left">{{ strtoupper($ns['nama_punggung_2'] ?? '') ?: '.......' }}</td>
                        <td>{{ $ns['nomor_punggung_2'] ?? '.......' }}</td>
                        @endif
                        @if($hasSizeAtasan)
                        @php
                            $parts = explode('-', $ns['_size_label'] ?? $ns['size_label'] ?? '');
                            $sv = trim(end($parts));
                        @endphp
                        <td>{{ $sv ?: '.......' }}</td>
                        @endif
                        @if($hasSizeBawahan)
                        @php
                            $parts = explode('-', $ns['_size_celana_label'] ?? $ns['size_celana_label'] ?? '');
                            $svc = trim(end($parts));
                        @endphp
                        <td>{{ $svc ?: '.......' }}</td>
                        @endif
                        @if($hasKeterangan)
                        <td class="t-left">{{ $ns['keterangan'] ?: '.......' }}</td>
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

        {{-- ===== LAMPIRAN ===== --}}
        <div class="page-break"></div>
        <div style="font-size:12pt; font-weight:900; text-decoration:underline; margin-bottom:10px; border-bottom:2px solid #000; padding-bottom:4px;">
            LAMPIRAN: DATA PESANAN
        </div>

        @foreach ($items as $item)
            @if(empty($item['is_addon']))
                @php
                    $lampFilled = collect($item['namesets'] ?? [])->filter(fn($ns) =>
                        !empty($ns['nama_punggung']) || !empty($ns['nomor_punggung']) ||
                        !empty($ns['nama_dada'])     || !empty($ns['nomor_dada'])     ||
                        !empty($ns['nama_lengan'])   || !empty($ns['nomor_lengan'])   ||
                        !empty($ns['nama_punggung_2']) || !empty($ns['nomor_punggung_2']) ||
                        !empty($ns['size_id'])       || !empty($ns['size_label'])     ||
                        !empty($ns['size_celana_id'])|| !empty($ns['size_celana_label']) ||
                        !empty($ns['keterangan'])
                    );

                    $hasLampNamaPunggung = $lampFilled->contains(fn($ns) => !empty($ns['nama_punggung']) || !empty($ns['nomor_punggung']));
                    $hasLampNamaDada     = $lampFilled->contains(fn($ns) => !empty($ns['nama_dada'])     || !empty($ns['nomor_dada']));
                    $hasLampNamaLengan   = $lampFilled->contains(fn($ns) => !empty($ns['nama_lengan'])   || !empty($ns['nomor_lengan']));
                    $hasLampNamaPunggung2 = $lampFilled->contains(fn($ns) => !empty($ns['nama_punggung_2']) || !empty($ns['nomor_punggung_2']));
                    $hasLampSizeAtasan   = $lampFilled->contains(fn($ns) => !empty($ns['size_id'])       || !empty($ns['size_label']));
                    $hasLampSizeBawahan  = $lampFilled->contains(fn($ns) => !empty($ns['size_celana_id'])|| !empty($ns['size_celana_label']));
                    $hasLampKeterangan   = $lampFilled->contains(fn($ns) => !empty($ns['keterangan']));
                @endphp
                @if($lampFilled->count())
                <div class="lampiran-sub">
                    DATA PESANAN: {{ strtoupper($item['nama_produk'] ?? '') }}@if(!empty($item['varian_label'])) — {{ strtoupper($item['varian_label']) }}@endif
                </div>
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
                            <td class="t-left">{{ strtoupper($ns['nama_punggung'] ?? '') ?: '.......' }}</td>
                            <td>{{ $ns['nomor_punggung'] ?? '.......' }}</td>
                            @endif
                            @if($hasLampNamaDada)
                            <td class="t-left">{{ strtoupper($ns['nama_dada'] ?? '') ?: '.......' }}</td>
                            <td>{{ $ns['nomor_dada'] ?? '.......' }}</td>
                            @endif
                            @if($hasLampNamaLengan)
                            <td class="t-left">{{ strtoupper($ns['nama_lengan'] ?? '') ?: '.......' }}</td>
                            <td>{{ $ns['nomor_lengan'] ?? '.......' }}</td>
                            @endif
                            @if($hasLampNamaPunggung2)
                            <td class="t-left">{{ strtoupper($ns['nama_punggung_2'] ?? '') ?: '.......' }}</td>
                            <td>{{ $ns['nomor_punggung_2'] ?? '.......' }}</td>
                            @endif
                            @if($hasLampSizeAtasan)
                            @php
                                $parts = explode('-', $ns['_size_label'] ?? $ns['size_label'] ?? '');
                                $sv = trim(end($parts));
                            @endphp
                            <td>{{ $sv ?: '.......' }}</td>
                            @endif
                            @if($hasLampSizeBawahan)
                            @php
                                $parts = explode('-', $ns['_size_celana_label'] ?? $ns['size_celana_label'] ?? '');
                                $svc = trim(end($parts));
                            @endphp
                            <td>{{ $svc ?: '.......' }}</td>
                            @endif
                            @if($hasLampKeterangan)
                            <td class="t-left">{{ $ns['keterangan'] ?: '.......' }}</td>
                            @endif
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
