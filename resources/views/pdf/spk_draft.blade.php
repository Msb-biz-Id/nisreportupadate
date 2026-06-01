<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SPK DRAFT - {{ $brand->nama_brand }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #111; margin: 0; }
        @page { margin: 14mm; }

        .header { display: table; width: 100%; padding-bottom: 8px; border-bottom: 3px solid {{ $brand->warna_primary ?? '#1E40AF' }}; margin-bottom: 12px; }
        .header > div { display: table-cell; vertical-align: top; }
        .brand-name { font-size: 16pt; font-weight: 700; color: {{ $brand->warna_primary ?? '#1E40AF' }}; }
        .brand-tagline { font-size: 8pt; color: #555; }
        .doc-title { font-size: 14pt; font-weight: 700; text-align: right; }
        .draft-badge { display: inline-block; background: #FEF3C7; color: #92400E; border: 1px solid #F59E0B; font-size: 8pt; font-weight: 700; padding: 2px 8px; border-radius: 4px; margin-top: 4px; float: right; }

        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .info-table td { padding: 4px 6px; font-size: 8.5pt; vertical-align: top; }
        .info-table td.label { color: #555; width: 25%; }
        .info-table td.value { font-weight: 600; width: 25%; }

        h2 { font-size: 11pt; background: #F1F5F9; padding: 5px 8px; margin: 14px 0 6px 0; border-left: 4px solid {{ $brand->warna_primary ?? '#1E40AF' }}; }
        h3 { font-size: 9pt; background: #E2E8F0; padding: 4px 8px; margin: 10px 0 4px 0; }

        table.spec { width: 100%; border-collapse: collapse; margin-bottom: 6px; font-size: 8pt; }
        table.spec th { background: #1E293B; color: white; padding: 5px 6px; text-align: left; }
        table.spec td { border: 1px solid #CBD5E1; padding: 4px 6px; vertical-align: top; }
        table.spec td.lbl { background: #F8FAFC; font-weight: 600; width: 30%; }

        table.namesets { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 7pt; }
        table.namesets th { background: #475569; color: white; padding: 4px 5px; text-align: center; }
        table.namesets td { border: 1px solid #E2E8F0; padding: 3px 5px; text-align: center; }
        table.namesets td.left { text-align: left; }

        .size-recap { background: #F8FAFC; border: 1px solid #E2E8F0; padding: 6px 8px; margin-top: 6px; font-size: 8pt; }
        .size-recap span { display: inline-block; margin-right: 12px; }

        .footer { margin-top: 20px; display: table; width: 100%; }
        .footer > div { display: table-cell; width: 33%; text-align: center; }
        .footer .line { border-top: 1px solid #555; margin-top: 30px; padding-top: 4px; font-size: 8pt; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="brand-name">{{ $brand->nama_brand }}</div>
            <div class="brand-tagline">{{ $brand->tagline ?? '' }}</div>
            <div style="font-size: 7pt; color: #666; margin-top: 4px;">{{ $brand->alamat ?? '' }} · {{ $brand->no_hp ?? '' }}</div>
        </div>
        <div>
            <div class="doc-title">SURAT PERINTAH KERJA (SPK)</div>
            <div class="draft-badge">⚠ DRAFT — BELUM DISIMPAN</div>
        </div>
    </div>

    {{-- Info PO --}}
    <table class="info-table">
        <tr>
            <td class="label">Nama PO</td>
            <td class="value">{{ strtoupper($raw['nama_po'] ?? '-') }}</td>
            <td class="label">Tanggal Masuk</td>
            <td class="value">{{ $raw['tanggal_masuk'] ? \Carbon\Carbon::parse($raw['tanggal_masuk'])->translatedFormat('d M Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Pelanggan</td>
            <td class="value">{{ $pelanggan?->nama ?? '-' }}</td>
            <td class="label">Deadline</td>
            <td class="value">{{ $raw['deadline_customer'] ? \Carbon\Carbon::parse($raw['deadline_customer'])->translatedFormat('d M Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="label">No. HP</td>
            <td class="value">{{ $pelanggan?->nomor_hp ?? '-' }}</td>
            <td class="label">Kategori Order</td>
            <td class="value">{{ $kategori?->nama ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Wilayah</td>
            <td class="value">{{ trim(($pelanggan?->kabupaten_nama ?? '') . ' / ' . ($pelanggan?->provinsi_nama ?? ''), ' /') ?: '-' }}</td>
            <td class="label">Jenis Order</td>
            <td class="value">{{ $jenisOrder?->nama ?? '-' }}</td>
        </tr>
        @if($sumber)
        <tr>
            <td class="label">Sumber Order</td>
            <td class="value">{{ $sumber->nama }}</td>
            <td class="label">Dicetak</td>
            <td class="value">{{ now()->translatedFormat('d M Y, H:i') }}</td>
        </tr>
        @endif
        @if(!empty($raw['catatan']))
        <tr>
            <td class="label">Catatan PO</td>
            <td class="value" colspan="3">{{ $raw['catatan'] }}</td>
        </tr>
        @endif
    </table>

    {{-- Items --}}
    @foreach ($items as $idx => $item)
        <h2>PRODUK #{{ $idx + 1 }}: {{ strtoupper($item['nama_produk'] ?? '-') }}
            @if(!empty($item['varian_label'])) — {{ $item['varian_label'] }} @endif
        </h2>

        {{-- Spesifikasi Produk --}}
        <h3>Spesifikasi Produk</h3>
        <table class="spec">
            @php
                $jenisSetelanMap = ['stell'=>'Stell (Atasan + Bawahan)','non_stell'=>'Non-Stell','atasan_saja'=>'Atasan Saja','bawahan_saja'=>'Bawahan Saja'];
                $polaMap = ['standart'=>'Standart','perempuan'=>'Perempuan'];
            @endphp
            <tr>
                <td class="lbl">Jenis Setelan</td>
                <td>{{ $jenisSetelanMap[$item['jenis_setelan'] ?? ''] ?? ($item['jenis_setelan'] ?? '-') }}</td>
                <td class="lbl">Pola</td>
                <td>{{ $polaMap[$item['pola'] ?? ''] ?? ($item['pola'] ?? '-') }}</td>
            </tr>
            <tr>
                <td class="lbl">Bahan Kain</td>
                <td>{{ $item['_bahan_kain'] ?? '-' }}</td>
                <td class="lbl">Warna</td>
                <td>{{ strtoupper($item['warna'] ?? '-') }}</td>
            </tr>
            <tr>
                <td class="lbl">Jml Atasan</td>
                <td>{{ $item['jml_atasan'] ?: '-' }}</td>
                <td class="lbl">Jml Bawahan</td>
                <td>{{ $item['jml_bawahan'] ?: '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">Logo</td>
                <td>{{ $item['_logo'] ?? '-' }}</td>
                <td class="lbl">Jenis RIB</td>
                <td>{{ $item['jenis_rib'] ?: '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">Tutup Kerah</td>
                <td>{{ $item['tutup_kerah'] ?: '-' }}</td>
                <td class="lbl">List Kerah</td>
                <td>{{ $item['list_kerah'] ?: '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">List Lengan</td>
                <td>{{ $item['list_lengan'] ?: '-' }}</td>
                <td class="lbl">List Samping Celana</td>
                <td>{{ $item['list_samping_celana'] ?: '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">List Bawah Celana</td>
                <td colspan="3">{{ $item['list_bawah_celana'] ?: '-' }}</td>
            </tr>
        </table>

        {{-- Keterangan Jahitan & Resleting --}}
        <h3>Keterangan Jahitan & Resleting</h3>
        <table class="spec">
            <tr>
                <td class="lbl">Pola Jahitan</td>
                <td>{{ $item['_pola_jahitan'] ? $item['_pola_jahitan']->jenis_pola . ' — ' . $item['_pola_jahitan']->nama : '-' }}</td>
                <td class="lbl">Jahitan List Lengan</td>
                <td>{{ $item['jahitan_list_lengan'] ? ucfirst($item['jahitan_list_lengan']) : '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">Resleting</td>
                <td colspan="3">{{ $item['_resleting'] ?? '-' }}</td>
            </tr>
        </table>

        {{-- Referensi Desain & Kerah --}}
        @if(!empty($item['gambar_desain']) || !empty($item['gambar_kerah']) || !empty($item['ket_atasan']) || !empty($item['ket_bawahan']) || !empty($item['jenis_kerah']))
        <h3>Referensi Desain & Kerah</h3>
        <div style="display: table; width: 100%; margin-bottom: 8px;">
            <div style="display: table-cell; width: 55%; padding-right: 8px; vertical-align: top;">
                @if(!empty($item['ket_atasan']))
                    <div style="font-size: 8pt; margin-bottom: 3px;"><strong>Ket. Atasan:</strong> {{ $item['ket_atasan'] }}</div>
                @endif
                @if(!empty($item['ket_bawahan']))
                    <div style="font-size: 8pt; margin-bottom: 3px;"><strong>Ket. Bawahan:</strong> {{ $item['ket_bawahan'] }}</div>
                @endif
                @if(!empty($item['gambar_desain']))
                    @php $path = storage_path('app/public/' . $item['gambar_desain']); @endphp
                    @if(file_exists($path))
                        <img src="{{ $path }}" alt="Desain" style="max-width: 100%; max-height: 220px; border: 1px solid #CBD5E1; margin-top: 4px;">
                    @endif
                @endif
            </div>
            <div style="display: table-cell; width: 45%; vertical-align: top;">
                @if(!empty($item['jenis_kerah']))
                    <div style="font-size: 8pt; margin-bottom: 3px;"><strong>Jenis Kerah:</strong> {{ $item['jenis_kerah'] }}</div>
                @endif
                @if(!empty($item['gambar_kerah']))
                    @php $path = storage_path('app/public/' . $item['gambar_kerah']); @endphp
                    @if(file_exists($path))
                        <img src="{{ $path }}" alt="Kerah" style="max-width: 100%; max-height: 180px; border: 1px solid #CBD5E1; margin-top: 4px;">
                    @endif
                @endif
            </div>
        </div>
        @endif

        {{-- Nameset --}}
        @if(!empty($item['namesets']))
        <h3>Nameset & Ukuran ({{ count($item['namesets']) }} PCS)</h3>
        <table class="namesets">
            <thead>
                <tr>
                    <th width="20">#</th>
                    <th>Nama Punggung</th>
                    <th width="40">No. Punggung</th>
                    <th>Nama Dada</th>
                    <th width="35">No. Dada</th>
                    <th>Nama Lengan</th>
                    <th width="35">No. Lengan</th>
                    <th width="40">No. Punggung 2</th>
                    <th width="70">Size</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($item['namesets'] as $ni => $ns)
                <tr>
                    <td>{{ $ni + 1 }}</td>
                    <td class="left">{{ strtoupper($ns['nama_punggung'] ?? '') ?: '-' }}</td>
                    <td>{{ $ns['nomor_punggung'] ?? '-' }}</td>
                    <td class="left">{{ strtoupper($ns['nama_dada'] ?? '') ?: '-' }}</td>
                    <td>{{ $ns['nomor_dada'] ?? '-' }}</td>
                    <td class="left">{{ strtoupper($ns['nama_lengan'] ?? '') ?: '-' }}</td>
                    <td>{{ $ns['nomor_lengan'] ?? '-' }}</td>
                    <td>{{ $ns['nomor_punggung_2'] ?? '-' }}</td>
                    <td>{{ $ns['_size_label'] ?? '-' }}</td>
                    <td class="left">{{ $ns['keterangan'] ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @php
            $recap = collect($item['namesets'])->groupBy('_size_label')->map->count();
        @endphp
        <div class="size-recap">
            <strong>Rekap Ukuran:</strong>
            @foreach ($recap as $sz => $cnt)
                <span>{{ $sz }}: <strong>{{ $cnt }}</strong></span>
            @endforeach
            <span style="float:right">Total: <strong>{{ count($item['namesets']) }} pcs</strong></span>
        </div>
        @endif
    @endforeach

    <div class="footer">
        <div><div class="line">Pemesan / Customer</div></div>
        <div><div class="line">Admin Brand</div></div>
        <div><div class="line">Admin Produksi</div></div>
    </div>
</body>
</html>
