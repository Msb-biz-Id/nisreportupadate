<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SPK {{ $order->no_po }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #000; }
        @page { margin: 12mm 14mm 18mm 14mm; }

        /* Title */
        .main-title { text-align: center; font-size: 14pt; font-weight: 700; text-decoration: underline; padding: 8px 0 10px; color: #C00000; }

        /* Header info box */
        .header-box { border: 1.5px solid #000; padding: 10px 12px; margin-bottom: 14px; }
        .header-row { display: table; width: 100%; margin-bottom: 2px; }
        .header-row > div { display: table-cell; vertical-align: top; }
        .hdr-left { width: 50%; }
        .hdr-right { width: 50%; }
        .hdr-item { font-size: 8.5pt; line-height: 1.8; }
        .hdr-label { font-weight: 700; display: inline-block; width: 130px; text-transform: uppercase; }
        .hdr-sep { display: inline-block; width: 12px; text-align: center; font-weight: 700; }
        .hdr-val { font-weight: 400; }

        /* Product section band */
        .product-band { background: #333; color: #fff; text-align: center; font-size: 11pt; font-weight: 700; padding: 7px 10px; margin: 14px 0 8px; letter-spacing: 0.5px; }

        /* Spec table */
        .spec-box { border: 1px solid #000; margin-bottom: 6px; }
        .spec-header { background: #E8E8E8; font-weight: 700; font-size: 8.5pt; padding: 5px 8px; border-bottom: 1px solid #000; }
        .spec-row { display: table; width: 100%; border-bottom: 1px solid #ccc; }
        .spec-row:last-child { border-bottom: none; }
        .spec-lbl { display: table-cell; width: 35%; font-weight: 700; font-size: 8pt; padding: 4px 8px; text-transform: uppercase; vertical-align: middle; }
        .spec-val { display: table-cell; width: 65%; font-size: 8.5pt; padding: 4px 8px; text-align: center; font-weight: 600; vertical-align: middle; }

        /* Section divider */
        .section-divider { text-align: center; font-weight: 700; font-size: 9pt; padding: 6px 0; letter-spacing: 1px; }

        /* Desain & Kerah sections */
        .ref-section { border: 1px solid #000; margin-bottom: 8px; }
        .ref-header { background: #333; color: #fff; font-weight: 700; font-size: 8.5pt; padding: 5px 8px; text-align: center; }
        .ref-body { padding: 6px; }
        .ref-img { max-width: 100%; max-height: 350px; display: block; margin: 4px auto; }
        .ket-box { display: table; width: 100%; border: 1px solid #000; margin-bottom: 8px; }
        .ket-cell { display: table-cell; width: 50%; border: 1px solid #000; text-align: center; vertical-align: top; }
        .ket-cell-head { background: #333; color: #fff; font-weight: 700; font-size: 8pt; padding: 4px 6px; }
        .ket-cell-val { font-size: 8.5pt; padding: 5px 6px; font-weight: 600; }
        .kerah-section { border: 1px solid #000; margin-bottom: 8px; }
        .kerah-title { text-align: center; font-weight: 700; font-size: 9pt; padding: 5px 0; }
        .kerah-body { display: table; width: 100%; }
        .kerah-left { display: table-cell; width: 50%; border: 1px solid #000; vertical-align: top; }
        .kerah-right { display: table-cell; width: 50%; border: 1px solid #000; vertical-align: top; text-align: center; padding: 4px; }
        .kerah-lbl-head { background: #333; color: #fff; font-weight: 700; font-size: 8pt; padding: 4px 8px; }
        .kerah-lbl-val { font-size: 8.5pt; padding: 5px 8px; font-weight: 600; text-align: center; }

        /* Nameset table */
        .ns-table { width: 100%; border-collapse: collapse; border: 1.5px solid #000; margin-top: 8px; font-size: 8pt; }
        .ns-table .ns-title { background: #E8E8E8; text-align: center; font-weight: 700; font-size: 9.5pt; padding: 6px; border: 1px solid #000; color: #000; letter-spacing: 0.5px; }
        .ns-table th { background: #E8E8E8; border: 1px solid #000; padding: 5px 6px; font-weight: 700; text-align: center; color: #000; }
        .ns-table th.left { text-align: left; }
        .ns-table td { border: 1px solid #000; padding: 4px 6px; text-align: center; }
        .ns-table td.left { text-align: left; }

        /* Size recap */
        .recap-title { font-weight: 700; text-decoration: underline; text-align: center; font-size: 10pt; margin: 12px 0 8px; }
        .recap-table { margin: 0 auto; border-collapse: collapse; font-size: 9pt; border: 1.5px solid #000; }
        .recap-table th { background: #E8E8E8; border: 1px solid #000; padding: 5px 12px; font-weight: 700; }
        .recap-table td { border: 1px solid #000; padding: 5px 12px; font-weight: 700; text-align: center; }
        .recap-label { font-size: 8pt; font-weight: 700; margin-bottom: 3px; text-align: center; }

        /* Footer */
        .page-footer { position: fixed; bottom: 0; left: 0; right: 0; height: 20px; border-top: 2px solid #C00000; padding: 4px 14mm 0; font-size: 7.5pt; }
        .page-footer .left { float: left; }
        .page-footer .right { float: right; color: #C00000; font-weight: 700; }

        /* Lampiran */
        .lampiran-header { background: #333; color: #fff; font-weight: 700; font-size: 9pt; padding: 5px 8px; margin-bottom: 4px; }
        .lampiran-sub { font-weight: 700; font-size: 8pt; padding: 3px 0; }

        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="page-footer">
        <span class="right">NAMA ORDER: {{ strtoupper($order->nama_po) }}</span>
    </div>

    {{-- ==================== PAGE 1: TITLE + HEADER + FIRST ITEM SPEC ==================== --}}
    <div class="main-title">FORMAT ORDER {{ strtoupper($order->brand->nama_brand ?? 'INDOWAREHOUSE') }}</div>

    <div class="header-box">
        <div class="header-row">
            <div class="hdr-left">
                <div class="hdr-item"><span class="hdr-label">Tanggal Masuk</span><span class="hdr-sep">:</span><span class="hdr-val">{{ \Carbon\Carbon::parse($order->tanggal_masuk)->format('Y-m-d') }}</span></div>
                <div class="hdr-item"><span class="hdr-label">Dateline</span><span class="hdr-sep">:</span><span class="hdr-val">{{ \Carbon\Carbon::parse($order->deadline_customer)->format('Y-m-d') }}</span></div>
                <div class="hdr-item"><span class="hdr-label">Nama Order</span><span class="hdr-sep">:</span><span class="hdr-val">{{ strtoupper($order->nama_po) }}</span></div>
                <div class="hdr-item"><span class="hdr-label">Grand Total</span><span class="hdr-sep">:</span><span class="hdr-val">{{ $order->items->sum('quantity') }} PCS</span></div>
            </div>
            <div class="hdr-right">
                <div class="hdr-item"><span class="hdr-label">Tipe Order</span><span class="hdr-sep">:</span><span class="hdr-val">{{ strtoupper($order->jenisOrder->nama ?? '-') }}</span></div>
                <div class="hdr-item"><span class="hdr-label">Jenis Order</span><span class="hdr-sep">:</span><span class="hdr-val">{{ $order->is_special_order ? 'SPECIAL' : 'NORMAL' }}</span></div>
                <div class="hdr-item"><span class="hdr-label">Kategori Item</span><span class="hdr-sep">:</span><span class="hdr-val">{{ strtoupper($order->items->pluck('nama_produk')->implode(', ')) }}</span></div>
                @php
                    $printingNames = collect();
                    if (!empty($order->printing_ids)) {
                        $printingNames = \App\Models\Master\Printing::whereIn('id', $order->printing_ids)->pluck('nama');
                    }
                @endphp
                <div class="hdr-item"><span class="hdr-label">Jenis Printing</span><span class="hdr-sep">:</span><span class="hdr-val">{{ $printingNames->isNotEmpty() ? strtoupper($printingNames->implode(', ')) : '-' }}</span></div>
                <div class="hdr-item"><span class="hdr-label">Nama Brand</span><span class="hdr-sep">:</span><span class="hdr-val">{{ strtoupper($order->brand->nama_brand ?? '-') }}</span></div>
            </div>
        </div>
    </div>

    @foreach ($order->items as $idx => $item)
        @if($idx > 0)<div class="page-break"></div>@endif

        @if(!empty($item->is_addon))
            {{-- Addon band --}}
            <div class="product-band">ADD-ON: {{ strtoupper($item->nama_produk) }}</div>
            <div class="spec-box">
                <div class="spec-header">DETAIL ADD-ON</div>
                <div class="spec-row">
                    <div class="spec-lbl" style="width: 25%;">Harga Satuan</div>
                    <div class="spec-val" style="width: 25%;">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</div>
                    <div class="spec-lbl" style="width: 25%;">Jumlah (Qty)</div>
                    <div class="spec-val" style="width: 25%;">{{ $item->quantity }}</div>
                </div>
                <div class="spec-row">
                    <div class="spec-lbl" style="width: 25%;">Keterangan</div>
                    <div class="spec-val" style="width: 25%;">{{ $item->catatan ?? '-' }}</div>
                    <div class="spec-lbl" style="width: 25%;">Jumlah Total</div>
                    <div class="spec-val" style="width: 25%;"><strong>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</strong></div>
                </div>
            </div>
        @else

        {{-- Product band --}}
        <div class="product-band">PRODUK: {{ strtoupper($item->nama_produk) }}</div>

        @php
            $jenisSetelanMap = ['stell'=>'STELL (ATASAN + BAWAHAN)','non_stell'=>'NON-STELL (ATASAN SAJA)','atasan_saja'=>'ATASAN SAJA','bawahan_saja'=>'BAWAHAN SAJA'];
            $itemLogos = collect();
            if (!empty($item->logo_ids)) {
                $itemLogos = \App\Models\Master\Logo::whereIn('id', $item->logo_ids)->pluck('nama');
            }
        @endphp

        {{-- Spesifikasi --}}
        <div class="spec-box">
            <div class="spec-header">SPESIFIKASI ({{ strtoupper($item->nama_produk) }})</div>
            <div class="spec-row"><div class="spec-lbl">Jenis Pesanan</div><div class="spec-val">{{ strtoupper($item->varian_label ?? '-') }}</div></div>
            <div class="spec-row"><div class="spec-lbl">Jenis Setelan</div><div class="spec-val">{{ $jenisSetelanMap[$item->jenis_setelan ?? ''] ?? strtoupper($item->jenis_setelan ?? '-') }}</div></div>
            <div class="spec-row"><div class="spec-lbl">Pola</div><div class="spec-val">{{ strtoupper($item->pola ?? '-') }}</div></div>
            <div class="spec-row"><div class="spec-lbl">Bahan</div><div class="spec-val">{{ strtoupper($item->bahanKain->nama ?? '-') }}</div></div>
            @if($item->bahanKainBawahan)
            <div class="spec-row"><div class="spec-lbl">Bahan Bawahan</div><div class="spec-val">{{ strtoupper($item->bahanKainBawahan->nama) }}</div></div>
            @endif
            <div class="spec-row"><div class="spec-lbl">Jumlah Atasan</div><div class="spec-val">{{ $item->jml_atasan ?: $item->quantity }}</div></div>
            <div class="spec-row"><div class="spec-lbl">Jumlah Bawahan</div><div class="spec-val">{{ $item->jml_bawahan ?: '-' }}</div></div>
            <div class="spec-row"><div class="spec-lbl">Warna</div><div class="spec-val">{{ strtoupper($item->warna ?? '-') }}</div></div>
            <div class="spec-row"><div class="spec-lbl">Jenis Logo</div><div class="spec-val">{{ $itemLogos->isNotEmpty() ? strtoupper($itemLogos->implode(', ')) : strtoupper($item->logo->nama ?? '-') }}</div></div>
            <div class="spec-row"><div class="spec-lbl">Jenis RIB</div><div class="spec-val">{{ strtoupper($item->jenis_rib ?? '-') }}</div></div>
            <div class="spec-row"><div class="spec-lbl">List Kerah</div><div class="spec-val">{{ strtoupper($item->list_kerah ?? '-') }}</div></div>
            <div class="spec-row"><div class="spec-lbl">List Lengan</div><div class="spec-val">{{ strtoupper($item->list_lengan ?? '-') }}</div></div>
            <div class="spec-row"><div class="spec-lbl">List Samping Celana</div><div class="spec-val">{{ strtoupper($item->list_samping_celana ?? '-') }}</div></div>
            <div class="spec-row"><div class="spec-lbl">List Bawah Celana</div><div class="spec-val">{{ strtoupper($item->list_bawah_celana ?? '-') }}</div></div>
            <div class="spec-row"><div class="spec-lbl">Tutup Kerah</div><div class="spec-val">{{ strtoupper($item->tutup_kerah ?? '-') }}</div></div>

            {{-- Keterangan Jahitan section --}}
            <div class="section-divider">KETERANGAN JAHITAN</div>
            <div class="spec-row"><div class="spec-lbl">Pola Jahitan</div><div class="spec-val">{{ $item->polaJahitan ? strtoupper($item->polaJahitan->nama) : '-' }}</div></div>
            <div class="spec-row"><div class="spec-lbl">Jenis Jahitan List Lengan</div><div class="spec-val">{{ strtoupper($item->jahitan_list_lengan ?? '-') }}</div></div>
            @if($item->pola_jahitan_bawah_id)
            <div class="spec-row"><div class="spec-lbl">Jenis Jahitan Bawah</div><div class="spec-val">{{ strtoupper(optional(\App\Models\Master\PolaJahitan::find($item->pola_jahitan_bawah_id))->nama ?? '-') }}</div></div>
            @endif
            @if($item->pola_jahitan_pundak_id)
            <div class="spec-row"><div class="spec-lbl">Jenis Jahitan Pundak</div><div class="spec-val">{{ strtoupper(optional(\App\Models\Master\PolaJahitan::find($item->pola_jahitan_pundak_id))->nama ?? '-') }}</div></div>
            @endif

            {{-- Keterangan Resleting section --}}
            <div class="section-divider">KETERANGAN RESLETING</div>
            <div class="spec-row"><div class="spec-lbl">Jenis Resleting</div><div class="spec-val">{{ strtoupper($item->resleting->nama ?? '-') }}</div></div>
        </div>

        {{-- Referensi Desain --}}
        @if($item->gambar_desain)
        <div class="page-break"></div>
        <div class="ref-section">
            <div class="ref-header">REFERENSI DESAIN {{ strtoupper($item->nama_produk) }} - {{ strtoupper($item->varian_label ?? '') }}</div>
            <div class="ref-body">
                @php $desainPath = storage_path('app/public/' . $item->gambar_desain); @endphp
                @if(file_exists($desainPath))
                    <img src="{{ $desainPath }}" class="ref-img" alt="Desain">
                @endif
            </div>
        </div>
        @endif

        {{-- Keterangan Atasan/Bawahan --}}
        @if($item->ket_atasan || $item->ket_bawahan)
        <div class="ket-box">
            <div class="ket-cell">
                <div class="ket-cell-head">KETERANGAN ATASAN</div>
                <div class="ket-cell-val">{{ strtoupper($item->ket_atasan ?? '-') }}</div>
            </div>
            <div class="ket-cell">
                <div class="ket-cell-head">KETERANGAN BAWAHAN</div>
                <div class="ket-cell-val">{{ strtoupper($item->ket_bawahan ?? '-') }}</div>
            </div>
        </div>
        @endif

        {{-- Referensi Kerah --}}
        @if($item->jenis_kerah || $item->gambar_kerah || $item->gambar_ket_tambahan)
        <div class="kerah-title">REFERENSI KERAH {{ strtoupper($item->nama_produk) }} - {{ strtoupper($item->varian_label ?? '') }}</div>
        <div class="kerah-section">
            <div class="kerah-body">
                <div class="kerah-left">
                    <div class="kerah-lbl-head">JENIS KERAH</div>
                    <div class="kerah-lbl-val">{{ strtoupper($item->jenis_kerah ?? '-') }}</div>
                </div>
                <div class="kerah-right">
                    @if($item->gambar_kerah)
                        @php $kerahPath = storage_path('app/public/' . $item->gambar_kerah); @endphp
                        @if(file_exists($kerahPath))
                            <img src="{{ $kerahPath }}" style="max-width: 95%; max-height: 140px;" alt="Kerah">
                        @endif
                    @endif
                    @if($item->gambar_ket_tambahan)
                        @php $ktPath = storage_path('app/public/' . $item->gambar_ket_tambahan); @endphp
                        @if(file_exists($ktPath))
                            <img src="{{ $ktPath }}" style="max-width: 95%; max-height: 140px; margin-top: 4px;" alt="Ket Tambahan">
                        @endif
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Nameset Table --}}
        @php
            $filledNamesets = $item->namesets->filter(function ($ns) {
                return !empty($ns->nama_punggung) || !empty($ns->nomor_punggung) ||
                       !empty($ns->nama_dada) || !empty($ns->nomor_dada) ||
                       !empty($ns->size_id) || !empty($ns->size_label) || !empty($ns->keterangan);
            });
        @endphp

        @if($filledNamesets->count())
        @php
            $hasNamaPunggung = $filledNamesets->contains(fn($ns) => !empty($ns->nama_punggung) || !empty($ns->nomor_punggung));
            $hasSizeAtasan = $filledNamesets->contains(fn($ns) => !empty($ns->size_id) || !empty($ns->size_label));
            $hasSizeBawahan = $filledNamesets->contains(fn($ns) => !empty($ns->size_celana_id) || !empty($ns->size_celana_label));
            $hasKeterangan = $filledNamesets->contains(fn($ns) => !empty($ns->keterangan));

            $sizeAtasanRecap = [];
            $sizeBawahanRecap = [];
            foreach ($filledNamesets as $ns) {
                if (!empty($ns->size_id) || !empty($ns->size_label)) {
                    $sz = $ns->size ? $ns->size->ukuran : trim(last(explode('-', $ns->size_label ?? '')));
                    if ($sz !== '') $sizeAtasanRecap[$sz] = ($sizeAtasanRecap[$sz] ?? 0) + 1;
                }
                if (!empty($ns->size_celana_id) || !empty($ns->size_celana_label)) {
                    $sz = $ns->sizeCelana ? $ns->sizeCelana->ukuran : trim(last(explode('-', $ns->size_celana_label ?? '')));
                    if ($sz !== '') $sizeBawahanRecap[$sz] = ($sizeBawahanRecap[$sz] ?? 0) + 1;
                }
            }
        @endphp

        <div class="page-break"></div>

        <table class="ns-table">
            <thead>
                <tr><td class="ns-title" colspan="{{ 2 + ($hasNamaPunggung ? 1 : 0) + ($hasSizeAtasan ? 1 : 0) + ($hasSizeBawahan ? 1 : 0) + ($hasKeterangan ? 1 : 0) }}">DATA PESANAN {{ strtoupper($item->nama_produk) }} - {{ strtoupper($item->varian_label ?? '') }}</td></tr>
                <tr>
                    <th width="35">NO.</th>
                    @if($hasNamaPunggung)<th class="left">NAMA PUNGGUNG</th><th width="90">NO. PUNGGUNG</th>@endif
                    @if($hasSizeAtasan)<th width="70">SIZE</th>@endif
                    @if($hasSizeBawahan)<th width="80">SIZE CELANA</th>@endif
                    @if($hasKeterangan)<th class="left">KETERANGAN</th>@endif
                </tr>
            </thead>
            <tbody>
                @foreach ($filledNamesets as $i => $ns)
                <tr>
                    <td>{{ $i + 1 }}.</td>
                    @if($hasNamaPunggung)
                        <td class="left">{{ strtoupper($ns->nama_punggung ?? '') ?: '-' }}</td>
                        <td style="font-weight:700">{{ $ns->nomor_punggung ?: '-' }}</td>
                    @endif
                    @if($hasSizeAtasan)
                        @php $szVal = $ns->size ? $ns->size->ukuran : trim(last(explode('-', $ns->size_label ?? ''))); @endphp
                        <td style="font-weight:700">{{ $szVal ?: '-' }}</td>
                    @endif
                    @if($hasSizeBawahan)
                        @php $szValC = $ns->sizeCelana ? $ns->sizeCelana->ukuran : trim(last(explode('-', $ns->size_celana_label ?? ''))); @endphp
                        <td style="font-weight:700">{{ $szValC ?: '-' }}</td>
                    @endif
                    @if($hasKeterangan)
                        <td class="left">{{ $ns->keterangan ?: '.......' }}</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="recap-title">JUMLAH KESELURUHAN: {{ $filledNamesets->count() }} PCS</div>

        @if(count($sizeAtasanRecap))
            @if($hasSizeBawahan)<div class="recap-label">REKAP SIZE ATASAN</div>@endif
            <table class="recap-table">
                <thead><tr>@foreach($sizeAtasanRecap as $size => $cnt)<th>{{ $size }}</th>@endforeach</tr></thead>
                <tbody><tr>@foreach($sizeAtasanRecap as $cnt)<td>{{ $cnt }}</td>@endforeach</tr></tbody>
            </table>
        @endif

        @if(count($sizeBawahanRecap))
            <div class="recap-label" style="margin-top:8px">REKAP SIZE BAWAHAN</div>
            <table class="recap-table">
                <thead><tr>@foreach($sizeBawahanRecap as $size => $cnt)<th>{{ $size }}</th>@endforeach</tr></thead>
                <tbody><tr>@foreach($sizeBawahanRecap as $cnt)<td>{{ $cnt }}</td>@endforeach</tr></tbody>
            </table>
        @endif
        @endif
        @endif

    @endforeach

    {{-- ==================== LAST PAGE: LAMPIRAN DATA PESANAN (all items combined) ==================== --}}
    <div class="page-break"></div>
    <div class="lampiran-header">LAMPIRAN: DATA PESANAN</div>

    @foreach ($order->items as $item)
        @if(empty($item->is_addon))
            @php
                $filledNamesets = $item->namesets->filter(function ($ns) {
                    return !empty($ns->nama_punggung) || !empty($ns->nomor_punggung) ||
                           !empty($ns->size_id) || !empty($ns->size_label);
                });
            @endphp
            @if($filledNamesets->count())
            <div class="lampiran-sub">DATA PESANAN: {{ strtoupper($item->nama_produk) }} - {{ strtoupper($item->varian_label ?? '') }}</div>
            <table class="ns-table" style="margin-bottom: 12px;">
                <thead>
                    <tr>
                        <th width="35">NO.</th>
                        <th class="left">NAMA PUNGGUNG</th>
                        <th width="90">NO. PUNGGUNG</th>
                        <th width="70">SIZE</th>
                        <th class="left">KETERANGAN</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($filledNamesets as $i => $ns)
                    <tr>
                        <td>{{ $i + 1 }}.</td>
                        <td class="left">{{ strtoupper($ns->nama_punggung ?? '') ?: '-' }}</td>
                        <td style="font-weight:700">{{ $ns->nomor_punggung ?: '-' }}</td>
                        @php $szVal = $ns->size ? $ns->size->ukuran : trim(last(explode('-', $ns->size_label ?? ''))); @endphp
                        <td style="font-weight:700">{{ $szVal ?: '-' }}</td>
                        <td class="left">{{ $ns->keterangan ?: '.......' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        @endif
    @endforeach

</body>
</html>
