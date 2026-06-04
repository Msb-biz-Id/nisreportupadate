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
            <td class="label">Jenis Order</td>
            <td class="value">{{ $jenisOrder?->nama ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Wilayah</td>
            <td class="value">{{ trim(($pelanggan?->kabupaten_nama ?? '') . ' / ' . ($pelanggan?->provinsi_nama ?? ''), ' /') ?: '-' }}</td>
            <td class="label"></td>
            <td class="value"></td>
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
        @if(!empty($item['is_addon']))
            <h2>ADD-ON #{{ $idx + 1 }}: {{ strtoupper($item['nama_produk'] ?? '-') }}
                @if(!empty($item['varian_label'])) — {{ $item['varian_label'] }} @endif
            </h2>
            <table class="spec">
                <tr>
                    <td class="lbl" width="150">Harga Satuan</td>
                    <td>Rp {{ number_format($item['harga_satuan'] ?? 0, 0, ',', '.') }}</td>
                    <td class="lbl" width="150">Jumlah (Qty)</td>
                    <td>{{ $item['quantity'] ?? 0 }}</td>
                </tr>
                <tr>
                    <td class="lbl">Keterangan</td>
                    <td>{{ $item['catatan'] ?? '-' }}</td>
                    <td class="lbl">Jumlah Total</td>
                    <td><strong>Rp {{ number_format(($item['harga_satuan'] ?? 0) * ($item['quantity'] ?? 0), 0, ',', '.') }}</strong></td>
                </tr>
            </table>
        @else
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
                <td class="lbl">Bahan Atasan</td>
                <td>{{ $item['_bahan_kain'] ?? '-' }}</td>
                <td class="lbl">Bahan Bawahan</td>
                <td>{{ $item['_bahan_kain_bawahan'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">Warna</td>
                <td>{{ strtoupper($item['warna'] ?? '-') }}</td>
                <td class="lbl">Jml Atasan</td>
                <td>{{ $item['jml_atasan'] ?: '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">Jml Bawahan</td>
                <td>{{ $item['jml_bawahan'] ?: '-' }}</td>
                <td class="lbl">Logo</td>
                <td>
                    @php
                        $itemLogos = !empty($item['_logos']) ? $item['_logos'] : [];
                    @endphp
                    {{ !empty($itemLogos) ? implode(', ', $itemLogos) : ($item['_logo'] ?? '-') }}
                </td>
            </tr>
            <tr>
                <td class="lbl">Jenis RIB</td>
                <td>{{ $item['jenis_rib'] ?: '-' }}</td>
                <td class="lbl">Tutup Kerah</td>
                <td>{{ $item['tutup_kerah'] ?: '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">List Kerah</td>
                <td>{{ $item['list_kerah'] ?: '-' }}</td>
                <td class="lbl">List Lengan</td>
                <td>{{ $item['list_lengan'] ?: '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">List Samping Celana</td>
                <td>{{ $item['list_samping_celana'] ?: '-' }}</td>
                <td class="lbl">List Bawah Celana</td>
                <td>{{ $item['list_bawah_celana'] ?: '-' }}</td>
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
        @if(!empty($item['gambar_desain']) || !empty($item['gambar_kerah']) || !empty($item['gambar_ket_tambahan']) || !empty($item['ket_atasan']) || !empty($item['ket_bawahan']) || !empty($item['jenis_kerah']))
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
                        <img src="{{ $path }}" alt="Kerah" style="max-width: 100%; max-height: 180px; border: 1px solid #CBD5E1; margin-top: 4px; display: block; margin-bottom: 8px;">
                    @endif
                @endif
                @if(!empty($item['gambar_ket_tambahan']))
                    @php $path = storage_path('app/public/' . $item['gambar_ket_tambahan']); @endphp
                    @if(file_exists($path))
                        <div style="font-size: 8pt; margin-bottom: 3px; margin-top: 6px;"><strong>Keterangan Tambahan Gambar:</strong></div>
                        <img src="{{ $path }}" alt="Ket. Tambahan" style="max-width: 100%; max-height: 180px; border: 1px solid #CBD5E1;">
                    @endif
                @endif
            </div>
        </div>
        @endif

        {{-- Nameset --}}
        @php
            $filledNamesets = collect($item['namesets'] ?? [])->filter(function ($ns) {
                return !empty($ns['nama_punggung']) || 
                       !empty($ns['nomor_punggung']) || 
                       !empty($ns['nama_dada']) || 
                       !empty($ns['nomor_dada']) || 
                       !empty($ns['nama_lengan']) || 
                       !empty($ns['nomor_lengan']) || 
                       !empty($ns['nama_punggung_2']) || 
                       !empty($ns['size_id']) || 
                       !empty($ns['size_celana_id']) || 
                       !empty($ns['size_label']) || 
                       !empty($ns['size_celana_label']) || 
                       !empty($ns['keterangan']);
            });
        @endphp

        @if($filledNamesets->count())
        @php
            $hasNamaPunggung = $filledNamesets->contains(fn($ns) => !empty($ns['nama_punggung']) || !empty($ns['nomor_punggung']));
            $hasNamaDada = $filledNamesets->contains(fn($ns) => !empty($ns['nama_dada']) || !empty($ns['nomor_dada']));
            $hasNamaLengan = $filledNamesets->contains(fn($ns) => !empty($ns['nama_lengan']) || !empty($ns['nomor_lengan']));
            $hasNamaPunggung2 = $filledNamesets->contains(fn($ns) => !empty($ns['nama_punggung_2']));
            $hasSizeAtasan = $filledNamesets->contains(fn($ns) => !empty($ns['size_id']) || !empty($ns['size_label']));
            $hasSizeBawahan = $filledNamesets->contains(fn($ns) => !empty($ns['size_celana_id']) || !empty($ns['size_celana_label']));
            $hasKeterangan = $filledNamesets->contains(fn($ns) => !empty($ns['keterangan']));

            // Calculate size recap for Atasan
            $sizeAtasanRecap = [];
            foreach ($filledNamesets as $ns) {
                if (!empty($ns['size_id']) || !empty($ns['size_label'])) {
                    $parts = explode('-', $ns['_size_label'] ?? $ns['size_label'] ?? '');
                    $sz = trim(end($parts));
                    if ($sz !== '') {
                        $sizeAtasanRecap[$sz] = ($sizeAtasanRecap[$sz] ?? 0) + 1;
                    }
                }
            }

            // Calculate size recap for Bawahan
            $sizeBawahanRecap = [];
            foreach ($filledNamesets as $ns) {
                if (!empty($ns['size_celana_id']) || !empty($ns['size_celana_label'])) {
                    $parts = explode('-', $ns['_size_celana_label'] ?? $ns['size_celana_label'] ?? '');
                    $sz = trim(end($parts));
                    if ($sz !== '') {
                        $sizeBawahanRecap[$sz] = ($sizeBawahanRecap[$sz] ?? 0) + 1;
                    }
                }
            }
        @endphp

        <table class="namesets" style="border: 2px solid #000; border-collapse: collapse; font-family: sans-serif; font-size: 8pt; width: 100%; margin-top: 10px;">
            <thead>
                <tr style="background-color: #E2E8F0; text-align: center;">
                    <th colspan="{{ 1 + ($hasNamaPunggung ? 2 : 0) + ($hasNamaDada ? 2 : 0) + ($hasNamaLengan ? 2 : 0) + ($hasNamaPunggung2 ? 1 : 0) + ($hasSizeAtasan ? 1 : 0) + ($hasSizeBawahan ? 1 : 0) + ($hasKeterangan ? 1 : 0) }}" style="text-align: center; font-weight: bold; padding: 6px; border: 1px solid #000; font-size: 10pt; color: #000; letter-spacing: 0.5px;">
                        DATA PESANAN {{ strtoupper($item['nama_produk']) }} @if(!empty($item['varian_label']))— {{ strtoupper($item['varian_label']) }}@endif
                    </th>
                </tr>
                <tr style="background-color: #E2E8F0; text-align: center; font-weight: bold; color: #000;">
                    <th width="30" style="border: 1px solid #000; padding: 5px; text-align: center;">NO.</th>
                    @if($hasNamaPunggung)
                        <th style="border: 1px solid #000; padding: 5px; text-align: left;">NAMA PUNGGUNG</th>
                        <th width="80" style="border: 1px solid #000; padding: 5px; text-align: center;">NO. PUNGGUNG</th>
                    @endif
                    @if($hasNamaDada)
                        <th style="border: 1px solid #000; padding: 5px; text-align: left;">NAMA DADA</th>
                        <th width="80" style="border: 1px solid #000; padding: 5px; text-align: center;">NO. DADA</th>
                    @endif
                    @if($hasNamaLengan)
                        <th style="border: 1px solid #000; padding: 5px; text-align: left;">NAMA LENGAN</th>
                        <th width="80" style="border: 1px solid #000; padding: 5px; text-align: center;">NO. LENGAN</th>
                    @endif
                    @if($hasNamaPunggung2)
                        <th style="border: 1px solid #000; padding: 5px; text-align: left;">NAMA PUNGGUNG (NAPUNG 2)</th>
                    @endif
                    @if($hasSizeAtasan)
                        <th width="70" style="border: 1px solid #000; padding: 5px; text-align: center;">SIZE</th>
                    @endif
                    @if($hasSizeBawahan)
                        <th width="70" style="border: 1px solid #000; padding: 5px; text-align: center;">SIZE CELANA</th>
                    @endif
                    @if($hasKeterangan)
                        <th style="border: 1px solid #000; padding: 5px; text-align: left;">KETERANGAN</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($filledNamesets as $i => $ns)
                <tr style="text-align: center; color: #000;">
                    <td style="border: 1px solid #000; padding: 5px; text-align: center;">{{ $i + 1 }}.</td>
                    @if($hasNamaPunggung)
                        <td class="left" style="border: 1px solid #000; padding: 5px; text-align: left;">{{ strtoupper($ns['nama_punggung'] ?? '') ?: '-' }}</td>
                        <td style="border: 1px solid #000; padding: 5px; text-align: center; font-weight: bold;">{{ $ns['nomor_punggung'] ?? '-' }}</td>
                    @endif
                    @if($hasNamaDada)
                        <td class="left" style="border: 1px solid #000; padding: 5px; text-align: left;">{{ strtoupper($ns['nama_dada'] ?? '') ?: '-' }}</td>
                        <td style="border: 1px solid #000; padding: 5px; text-align: center; font-weight: bold;">{{ $ns['nomor_dada'] ?? '-' }}</td>
                    @endif
                    @if($hasNamaLengan)
                        <td class="left" style="border: 1px solid #000; padding: 5px; text-align: left;">{{ strtoupper($ns['nama_lengan'] ?? '') ?: '-' }}</td>
                        <td style="border: 1px solid #000; padding: 5px; text-align: center; font-weight: bold;">{{ $ns['nomor_lengan'] ?? '-' }}</td>
                    @endif
                    @if($hasNamaPunggung2)
                        <td class="left" style="border: 1px solid #000; padding: 5px; text-align: left;">{{ strtoupper($ns['nama_punggung_2'] ?? '') ?: '-' }}</td>
                    @endif
                    @if($hasSizeAtasan)
                        @php
                            $parts = explode('-', $ns['_size_label'] ?? $ns['size_label'] ?? '');
                            $szVal = trim(end($parts));
                        @endphp
                        <td style="border: 1px solid #000; padding: 5px; text-align: center; font-weight: bold;">{{ $szVal ?: '-' }}</td>
                    @endif
                    @if($hasSizeBawahan)
                        @php
                            $parts = explode('-', $ns['_size_celana_label'] ?? $ns['size_celana_label'] ?? '');
                            $szValC = trim(end($parts));
                        @endphp
                        <td style="border: 1px solid #000; padding: 5px; text-align: center; font-weight: bold;">{{ $szValC ?: '-' }}</td>
                    @endif
                    @if($hasKeterangan)
                        <td class="left" style="border: 1px solid #000; padding: 5px; text-align: left;">{{ $ns['keterangan'] ?? '-' }}</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 15px; margin-bottom: 25px; text-align: center; font-family: sans-serif;">
            <p style="font-weight: bold; text-decoration: underline; margin-bottom: 8px; font-size: 10pt; color: #000;">
                JUMLAH KESELURUHAN: {{ $filledNamesets->count() }} PCS
            </p>

            @if(count($sizeAtasanRecap))
                <div style="margin-bottom: 12px; display: inline-block;">
                    @if($hasSizeBawahan)
                        <div style="font-size: 8pt; font-weight: bold; margin-bottom: 3px; color: #000;">REKAP SIZE ATASAN</div>
                    @endif
                    <table style="margin: 0 auto; border-collapse: collapse; font-size: 9pt; border: 1.5px solid #000;">
                        <thead>
                            <tr style="background-color: #E2E8F0; text-align: center; color: #000;">
                                @foreach ($sizeAtasanRecap as $size => $cnt)
                                    <th style="border: 1px solid #000; padding: 5px 12px; font-weight: bold;">{{ $size }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="text-align: center; color: #000;">
                                @foreach ($sizeAtasanRecap as $size => $cnt)
                                    <td style="border: 1px solid #000; padding: 5px 12px; font-weight: bold;">{{ $cnt }}</td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif

            @if(count($sizeBawahanRecap))
                <div style="margin-top: 5px; margin-bottom: 5px; display: inline-block;">
                    @if($hasSizeAtasan)
                        <div style="font-size: 8pt; font-weight: bold; margin-bottom: 3px; color: #000;">REKAP SIZE BAWAHAN</div>
                    @endif
                    <table style="margin: 0 auto; border-collapse: collapse; font-size: 9pt; border: 1.5px solid #000;">
                        <thead>
                            <tr style="background-color: #E2E8F0; text-align: center; color: #000;">
                                @foreach ($sizeBawahanRecap as $size => $cnt)
                                    <th style="border: 1px solid #000; padding: 5px 12px; font-weight: bold;">{{ $size }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="text-align: center; color: #000;">
                                @foreach ($sizeBawahanRecap as $size => $cnt)
                                    <td style="border: 1px solid #000; padding: 5px 12px; font-weight: bold;">{{ $cnt }}</td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        @endif
        @endif
    @endforeach

    <div class="footer">
        <div><div class="line">Pemesan / Customer</div></div>
        <div><div class="line">Admin Brand</div></div>
        <div><div class="line">Admin Produksi</div></div>
    </div>
</body>
</html>
