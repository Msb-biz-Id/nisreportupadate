<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SPK {{ $order->no_po }} - {{ $order->brand->nama_brand }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #111; margin: 0; }
        @page { margin: 14mm; }
        .header { display: table; width: 100%; padding-bottom: 8px; border-bottom: 3px solid {{ $order->brand->warna_primary ?? '#1E40AF' }}; margin-bottom: 12px; }
        .header > div { display: table-cell; vertical-align: top; }
        .brand-name { font-size: 16pt; font-weight: 700; color: {{ $order->brand->warna_primary ?? '#1E40AF' }}; }
        .brand-tagline { font-size: 8pt; color: #555; }
        .doc-title { font-size: 14pt; font-weight: 700; text-align: right; }
        .doc-no { font-family: monospace; font-size: 10pt; text-align: right; color: #555; }

        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .info-table td { padding: 4px 6px; font-size: 8.5pt; vertical-align: top; }
        .info-table td.label { color: #555; width: 28%; }
        .info-table td.value { font-weight: 600; }

        h2 { font-size: 11pt; background: #F1F5F9; padding: 5px 8px; margin: 14px 0 6px 0; border-left: 4px solid {{ $order->brand->warna_primary ?? '#1E40AF' }}; }

        table.spec { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 8pt; }
        table.spec th { background: #1E293B; color: white; padding: 5px 6px; text-align: left; }
        table.spec td { border: 1px solid #CBD5E1; padding: 4px 6px; vertical-align: top; }
        table.spec td.lbl { background: #F8FAFC; font-weight: 600; width: 28%; }

        table.namesets { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 7.5pt; }
        table.namesets th { background: #475569; color: white; padding: 4px 6px; }
        table.namesets td { border: 1px solid #E2E8F0; padding: 3px 6px; }

        .footer { margin-top: 20px; display: table; width: 100%; }
        .footer > div { display: table-cell; width: 33%; text-align: center; padding-top: 30px; font-size: 8pt; }
        .footer .line { border-top: 1px solid #555; margin-top: 30px; padding-top: 4px; }

        .size-recap { background: #F8FAFC; border: 1px solid #E2E8F0; padding: 6px 8px; margin-top: 6px; font-size: 8pt; }
        .size-recap span { display: inline-block; margin-right: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="brand-name">{{ $order->brand->nama_brand }}</div>
            <div class="brand-tagline">{{ $order->brand->tagline ?? '' }}</div>
            <div style="font-size: 7pt; color: #666; margin-top: 4px;">
                {{ $order->brand->alamat ?? '' }} · {{ $order->brand->no_hp ?? '' }} · {{ $order->brand->email ?? '' }}
            </div>
        </div>
        <div>
            <div class="doc-title">SURAT PERINTAH KERJA (SPK)</div>
            <div class="doc-no">{{ $order->no_po }}</div>
        </div>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Nama PO</td><td class="value">{{ $order->nama_po }}</td>
            <td class="label">Tanggal Masuk</td><td class="value">{{ \Carbon\Carbon::parse($order->tanggal_masuk)->translatedFormat('d M Y') }}</td>
        </tr>
        <tr>
            <td class="label">Pelanggan</td><td class="value">{{ $order->pelanggan->nama ?? '-' }}</td>
            <td class="label">Deadline</td><td class="value">{{ \Carbon\Carbon::parse($order->deadline_customer)->translatedFormat('d M Y') }}</td>
        </tr>
        <tr>
            <td class="label">No. HP</td><td class="value">{{ $order->pelanggan->nomor_hp ?? '-' }}</td>
            <td class="label">Kategori</td><td class="value">{{ $order->kategoriOrder->nama ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Wilayah</td>
            <td class="value">{{ trim(($order->pelanggan->kabupaten_nama ?? '') . ' / ' . ($order->pelanggan->provinsi_nama ?? ''), ' /') ?: '-' }}</td>
            <td class="label">Sumber Order</td><td class="value">{{ $order->sumberOrder->nama ?? '-' }}</td>
        </tr>
    </table>

    @foreach ($order->items as $idx => $item)
        <h2>PRODUK #{{ $idx + 1 }}: {{ strtoupper($item->nama_produk) }} @if($item->varian_label) — {{ $item->varian_label }} @endif</h2>

        <table class="spec">
            <tr>
                <td class="lbl">Jumlah Pesanan</td>
                <td>{{ $item->quantity }} pcs @ Rp {{ number_format($item->harga_satuan, 0, ',', '.') }} = <strong>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td class="lbl">Jenis Setelan</td><td>{{ $item->jenis_setelan ? str_replace('_', ' ', strtoupper($item->jenis_setelan)) : '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">Bahan Kain</td><td>{{ $item->bahanKain->nama ?? '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">Logo</td><td>{{ $item->logo->nama ?? '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">Jenis Printing</td><td>{{ $item->printing->nama ?? '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">Resleting</td><td>{{ $item->resleting->nama ?? '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">Warna</td><td>{{ $item->warna ?? '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">Jenis Kerah</td><td>{{ $item->jenis_kerah ?? '-' }}</td>
            </tr>
            @if($item->catatan)
            <tr><td class="lbl">Catatan</td><td>{{ $item->catatan }}</td></tr>
            @endif
        </table>

        @if ($item->gambar_desain || $item->gambar_kerah)
            <div style="margin: 10px 0; display: table; width: 100%;">
                @if ($item->gambar_desain)
                    @php $designPath = storage_path('app/public/' . $item->gambar_desain); @endphp
                    <div style="display: table-cell; width: 60%; padding-right: 8px; vertical-align: top;">
                        <div style="font-size: 8pt; font-weight: 600; margin-bottom: 4px;">REFERENSI DESAIN</div>
                        @if (file_exists($designPath))
                            <img src="{{ $designPath }}" alt="Desain" style="max-width: 100%; max-height: 280px; border: 1px solid #CBD5E1;">
                        @else
                            <div style="border: 1px dashed #CBD5E1; padding: 40px; text-align: center; color: #999; font-size: 7pt;">[Gambar tidak tersedia]</div>
                        @endif
                    </div>
                @endif
                @if ($item->gambar_kerah)
                    @php $collarPath = storage_path('app/public/' . $item->gambar_kerah); @endphp
                    <div style="display: table-cell; width: 40%; vertical-align: top;">
                        <div style="font-size: 8pt; font-weight: 600; margin-bottom: 4px;">REFERENSI KERAH</div>
                        @if (file_exists($collarPath))
                            <img src="{{ $collarPath }}" alt="Kerah" style="max-width: 100%; max-height: 200px; border: 1px solid #CBD5E1;">
                        @endif
                    </div>
                @endif
            </div>
        @endif

        @if($item->namesets->count())
            <strong style="font-size: 9pt;">Nameset & Nomor Punggung</strong>
            <table class="namesets">
                <thead>
                    <tr>
                        <th width="35">#</th>
                        <th>Nama Punggung</th>
                        <th width="60">No. Punggung</th>
                        <th width="100">Ukuran</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($item->namesets as $i => $ns)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $ns->nama_punggung ?: '-' }}</td>
                            <td>{{ $ns->nomor_punggung ?: '-' }}</td>
                            <td>{{ $ns->size->kategori_size ?? '' }} - {{ $ns->size->ukuran ?? $ns->size_label ?? '-' }}</td>
                            <td>{{ $ns->keterangan ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @php
                $sizeRecap = $item->namesets->groupBy(fn ($n) => ($n->size->kategori_size ?? '') . '-' . ($n->size->ukuran ?? $n->size_label ?? '-'))->map->count();
            @endphp
            <div class="size-recap">
                <strong>Rekap Ukuran:</strong>
                @foreach ($sizeRecap as $key => $cnt)
                    <span>{{ $key }}: <strong>{{ $cnt }}</strong></span>
                @endforeach
                <span style="float:right">Total: <strong>{{ $item->namesets->count() }} pcs</strong></span>
            </div>
        @endif
    @endforeach

    @if ($order->catatan)
        <h2>CATATAN PO</h2>
        <div style="padding: 8px; background: #FEF3C7; border-left: 4px solid #F59E0B; font-size: 8.5pt;">{{ $order->catatan }}</div>
    @endif

    <div class="footer">
        <div><div class="line">Pemesan / Customer</div></div>
        <div><div class="line">Admin Brand</div></div>
        <div><div class="line">Admin Produksi</div></div>
    </div>
</body>
</html>
