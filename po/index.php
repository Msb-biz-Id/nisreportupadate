<?php
// ==========================================
// BACKEND: PROSES GENERATE PDF DENGAN DOMPDF
// ==========================================
// Pastikan Anda sudah menjalankan: composer require dompdf/dompdf
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Daftar Kategori Produk yang tersedia
$katOrderList = [
    'jersey' => 'Jersey',
    'jersey_running' => 'Jersey Running',
    'jersey_running_lekbong' => 'Jersey Running Lekbong',
    'jersey_padel_tenis' => 'Jersey Padel/Tenis',
    'jersey_basket' => 'Jersey Basket',
    'jersey_tanpa_lengan' => 'Jersey Tanpa Lengan / Lekbong',
    'jaket' => 'Jaket',
    'celana_panjang' => 'Celana Panjang',
    'celana_pendek' => 'Celana Pendek',
    'celana_cewek' => 'Celana Cewek',
    'celana_rok_hoki' => 'Celana Rok Hoki',
    'celana_rok_padel' => 'Celana Rok Padel/Tenis',
    'celana_running' => 'Celana Running',
    'rok_hoki' => 'Rok Hoki',
    'rok_padel' => 'Rok Padel/Tenis'
];

// Fungsi Helper untuk merubah data kosong menjadi "......."
function displayVal($val)
{
    $val = trim((string)$val);
    return ($val === '' || $val === '-' || $val === null) ? '.......' : nl2br(htmlspecialchars($val));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_pdf') {

    $kategoriOrderArr = $_POST['kategoriOrder'] ?? [];
    $activeProducts = [];
    foreach ($kategoriOrderArr as $catKey) {
        if (isset($katOrderList[$catKey])) {
            $activeProducts[$catKey] = $katOrderList[$catKey];
        }
    }
    $kategoriOrderStr = implode(', ', $activeProducts);

    $info = [
        'namaBrand' => $_POST['namaBrand'] ?? '',
        'namaOrder' => $_POST['namaOrder'] ?? '',
        'tipeOrder' => $_POST['tipeOrder'] ?? '',
        'jenisOrder' => $_POST['jenisOrder'] ?? '',
        'kategoriOrder' => $kategoriOrderStr,
        'jenisPrinting' => $_POST['jenisPrinting'] ?? '',
        'tanggalMasuk' => $_POST['tanggalMasuk'] ?? '',
        'dateline' => $_POST['dateline'] ?? '',
        'detailing' => $_POST['detailingPelanggan'] ?? ''
    ];

    // Hitung Grand Total Keseluruhan
    $grandTotal = 0;
    $productCounts = [];

    foreach ($activeProducts as $prodId => $prodLabel) {
        if (isset($_POST['subcat'][$prodId]) && is_array($_POST['subcat'][$prodId])) {
            foreach ($_POST['subcat'][$prodId] as $subId => $val) {
                if ($val === '1') {
                    $namas = $_POST['nama'][$prodId][$subId] ?? [];
                    $nomors = $_POST['nomor'][$prodId][$subId] ?? [];
                    $catCount = 0;
                    for ($i = 0; $i < count($namas); $i++) {
                        if (trim($namas[$i]) !== '' || trim($nomors[$i]) !== '') $catCount++;
                    }
                    $productCounts[$prodId][$subId] = $catCount;
                    $grandTotal += $catCount;
                }
            }
        }
    }

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('dpi', 150);
    $dompdf = new Dompdf($options);

    // =====================================
    // CSS & TEMPLATE DASAR PDF (KERTAS A4)
    // =====================================
    $html = '
    <html>
    <head>
        <style>
            @page { margin: 60px 30px 60px 30px; } 
            header { position: fixed; top: -45px; left: 0px; right: 0px; height: 35px; text-align: center; font-size: 26px; font-weight: 900; text-decoration: underline; border-bottom: 4px solid #000; padding-bottom: 8px; }
            footer { position: fixed; bottom: -45px; left: 0px; right: 0px; height: 30px; border-top: 2px solid #000; padding-top: 8px; }
            main { margin-top: 15px; margin-bottom: 10px; }
            
            body { font-family: "Helvetica", "Arial", sans-serif; font-size: 14px; color: #000; line-height: 1.3; text-transform: uppercase; }
            
            table { width: 100%; border-collapse: collapse; }
            td, th { padding: 6px; vertical-align: top; }
            
            .info-table { margin-bottom: 15px; border: 2px solid #000; }
            .info-table td { padding: 8px 10px; font-weight: 900; font-size: 16px; }
            .colon { width: 10px; text-align: center; }
            
            .title-box { font-weight: bold; font-size: 16px; background: #d4d4d4; color: #000; padding: 8px 10px; text-align: left; border: 1px solid #000; border-bottom: none; margin-top: 15px; text-transform: uppercase; }
            .title-box-center { font-weight: bold; font-size: 16px; background: #d4d4d4; color: #000; padding: 8px 10px; text-align: center; border: 1px solid #000; border-bottom: none; text-transform: uppercase; }
            
            .spec-table { border: 1px solid #000; margin-bottom: 5px; }
            .spec-table th, .spec-table td { border: 1px solid #000; padding: 7px 8px; font-size: 14px; text-align: center; font-weight: bold; }
            .spec-table th { background-color: #d4d4d4; font-size: 14px; }
            .spec-row-head { width: 28%; text-align: left !important; background-color: #f9f9f9; }
            .sub-header { background-color: #d4d4d4; text-align: center !important; padding: 8px 8px !important; font-size: 14px; letter-spacing: 1px; }

            .nameset-table { border: 1px solid #000; margin-bottom: 5px; }
            .nameset-table th, .nameset-table td { border: 1px solid #000; padding: 8px; text-align: center; font-size: 14px; font-weight: bold; }
            .nameset-table th { background-color: #d4d4d4; font-size: 15px; }
            .t-left { text-align: left !important; padding-left: 10px !important; }

            .page-break { page-break-before: always; }
            
            .img-wrapper { border: 2px solid #000; padding: 10px; margin: 0 0 10px 0; box-sizing: border-box; background: #fff; }
            .img-box { text-align: center; border: 1px solid #000; padding: 4px; margin-bottom: 5px; background: #fff; }
            .img-box img { max-width: 100%; object-fit: contain; }

            .rekap-container { margin-top: 15px; font-size: 16px; font-weight: bold; text-align: center; }
            .rekap-tabel { border: 2px solid #000 !important; width: auto !important; margin: 8px auto 0 auto; }
            .rekap-tabel td { border: 1px solid #000; padding: 8px 20px; text-align: center; font-size: 16px; font-weight: 900; }
            .rekap-tabel th { border: 1px solid #000; padding: 8px 20px; text-align: center; font-size: 16px; background: #d4d4d4; font-weight: 900; }
            
            .page-number:after { content: counter(page); }
        </style>
    </head>
    <body>
        
        <header>
            FORMAT ORDER INDOWAREHOUSE
        </header>

        <footer>
            <table style="width: 100%; border: none; padding: 0; margin: 0; font-size: 14px; font-weight: bold;">
                <tr>
                    <td style="text-align: left; width: 50%;">HALAMAN <span class="page-number"></span></td>
                    <td style="text-align: right; width: 50%; color: #b91c1c;">NAMA ORDER: ' . displayVal($info['namaOrder']) . '</td>
                </tr>
            </table>
        </footer>
        
        <main>
            <table class="info-table">
                <tr>
                    <td width="50%" style="border-right: 2px solid #000;">
                        <table style="width: 100%;">
                            <tr><td width="160">TANGGAL MASUK</td><td class="colon">:</td><td>' . displayVal($info['tanggalMasuk']) . '</td></tr>
                            <tr><td>DATELINE</td><td class="colon">:</td><td>' . displayVal($info['dateline']) . '</td></tr>
                            <tr><td>NAMA ORDER</td><td class="colon">:</td><td style="color: #b91c1c;">' . displayVal($info['namaOrder']) . '</td></tr>
                            <tr><td>GRAND TOTAL</td><td class="colon">:</td><td style="color: #b91c1c;">' . ($grandTotal > 0 ? $grandTotal : '.......') . ' PCS</td></tr>
                        </table>
                    </td>
                    <td width="50%" style="vertical-align: top; padding-left: 15px;">
                        <table style="width: 100%;">
                            <tr><td width="150">TIPE ORDER</td><td class="colon">:</td><td>' . displayVal($info['tipeOrder']) . '</td></tr>
                            <tr><td>JENIS ORDER</td><td class="colon">:</td><td>' . displayVal($info['jenisOrder']) . '</td></tr>
                            <tr><td>KATEGORI ITEM</td><td class="colon">:</td><td>' . displayVal($info['kategoriOrder']) . '</td></tr>
                            <tr><td>JENIS PRINTING</td><td class="colon">:</td><td>' . displayVal($info['jenisPrinting']) . '</td></tr>
                            <tr><td>NAMA BRAND</td><td class="colon">:</td><td>' . displayVal($info['namaBrand']) . '</td></tr>
                        </table>
                    </td>
                </tr>
            </table>';

    if (!empty($info['detailing'])) {
        $html .= '
                <div class="title-box" style="margin-top: 0;">DETAILING PELANGGAN</div>
                <div style="border: 2px solid #000; border-top: none; padding: 12px; font-weight: 900; font-size: 16px; margin-bottom: 15px;">' . nl2br(htmlspecialchars($info['detailing'])) . '</div>';
    }

    // =========================================================
    // LOOPING PER PRODUK (JERSEY, JAKET, DLL)
    // =========================================================
    $productIndex = 0;
    $appendixHtml = ''; // Variabel untuk menyimpan data pesanan (lampiran)

    foreach ($activeProducts as $prodId => $prodLabel) {

        $activeSubcats = [];
        if (isset($_POST['subcat'][$prodId]) && is_array($_POST['subcat'][$prodId])) {
            foreach ($_POST['subcat'][$prodId] as $subId => $val) {
                if ($val === '1') {
                    $activeSubcats[$subId] = $_POST['subLabel'][$prodId][$subId] ?? strtoupper(str_replace('_', ' ', $subId));
                }
            }
        }

        if (count($activeSubcats) === 0) continue;

        // PISAH HALAMAN ANTAR PRODUK 
        if ($productIndex > 0) {
            $html .= '<div class="page-break"></div>';
        }
        $productIndex++;

        $html .= '<div style="border: 2px solid #000; padding: 10px; background: #000; color: #fff; text-align: center; font-size: 20px; font-weight: bold; margin-top: 10px; margin-bottom: 15px;">PRODUK: ' . strtoupper($prodLabel) . '</div>';

        // --- TABEL SPESIFIKASI PRODUK ---
        $html .= '<div class="title-box" style="margin-top:0;">SPESIFIKASI (' . strtoupper($prodLabel) . ')</div>';
        $html .= '<table class="spec-table" style="margin-top: 0;">
                    <thead>
                        <tr>
                            <th class="spec-row-head" style="background: #d4d4d4;">JENIS PESANAN</th>';
        foreach ($activeSubcats as $subLabel) {
            $html .= '<th>' . strtoupper($subLabel) . '</th>';
        }
        $html .= '      </tr>
                    </thead>
                    <tbody>';

        $specFields = [
            'jenisSetelan' => 'JENIS SETELAN',
            'jenisPola' => 'POLA',
            'bahan' => 'BAHAN',
            'jumlahAtasan' => 'JUMLAH ATASAN',
            'jumlahBawahan' => 'JUMLAH BAWAHAN',
            'warna' => 'WARNA',
            'jenisLogo' => 'JENIS LOGO',
            'jenisRib' => 'JENIS RIB',
            'listKerah' => 'LIST KERAH',
            'listLengan' => 'LIST LENGAN',
            'listSamping' => 'LIST SAMPING CELANA',
            'listBawahCelana' => 'LIST BAWAH CELANA',
            'tutupKerah' => 'TUTUP KERAH'
        ];

        foreach ($specFields as $fieldKey => $fieldLabel) {
            $html .= '<tr><td class="spec-row-head">' . $fieldLabel . '</td>';
            foreach ($activeSubcats as $subId => $subLabel) {
                if ($fieldKey == 'jumlahAtasan' || $fieldKey == 'jumlahBawahan') {
                    $val = $_POST['spec'][$prodId][$subId][$fieldKey] ?? '';
                    if (trim($val) === '') {
                        $val = $productCounts[$prodId][$subId] ?? 0;
                        if ($val == 0) $val = '';
                    }
                } else {
                    $val = $_POST['spec'][$prodId][$subId][$fieldKey] ?? '';
                }

                $display = displayVal($val);
                $html .= '<td>' . $display . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '<tr><td colspan="' . (count($activeSubcats) + 1) . '" class="sub-header">KETERANGAN JAHITAN</td></tr>';

        $jahitanFields = [
            'polaJahitanKerah' => 'POLA JAHITAN ',
            'jahitanListLengan' => 'JENIS JAHITAN LIST LENGAN',
            'jahitanBawah' => 'JENIS JAHITAN BAWAH',
            'jahitanPundak' => 'JENIS JAHITAN PUNDAK'
        ];

        foreach ($jahitanFields as $fieldKey => $fieldLabel) {
            $html .= '<tr><td class="spec-row-head">' . $fieldLabel . '</td>';
            foreach ($activeSubcats as $subId => $subLabel) {
                $val = $_POST['spec'][$prodId][$subId][$fieldKey] ?? '';
                $html .= '<td>' . displayVal($val) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '<tr><td colspan="' . (count($activeSubcats) + 1) . '" class="sub-header">KETERANGAN RESLETING</td></tr>';

        $html .= '<tr><td class="spec-row-head">JENIS RESLETING</td>';
        foreach ($activeSubcats as $subId => $subLabel) {
            $val = $_POST['spec'][$prodId][$subId]['jenisResleting'] ?? '';
            $html .= '<td>' . displayVal($val) . '</td>';
        }
        $html .= '</tr>';

        $html .= '</tbody></table>';

        foreach ($activeSubcats as $subId => $subLabel) {

            // --- PEMISAH HALAMAN: REFERENSI DESAIN HARUS FULL 1 HALAMAN SENDIRI ---
            $html .= '<div class="page-break"></div>';

            $specData = $_POST['spec'][$prodId][$subId] ?? [];
            // Mengambil Base64 langsung dari input hidden hasil Cropper.js
            $imgDesainBase64 = $_POST["img_base64_{$prodId}_{$subId}_desain"] ?? null;
            $imgKerahBase64 = $_POST["img_base64_{$prodId}_{$subId}_kerah"] ?? null;

            $ketAtasan = $specData['ketAtasan'] ?? '';
            $ketBawahan = $specData['ketBawahan'] ?? '';
            $jenisKerahVal = $specData['jenisKerah'] ?? '';

            $html .= '<div class="img-wrapper" style="padding: 6px; margin-bottom: 0;">';

            // --- BLOK DESAIN (MAKSIMAL 1 HALAMAN) ---
            $html .= '<div class="title-box-center" style="margin-top: 0; padding: 6px; font-size: 16px;">REFERENSI DESAIN ' . strtoupper($prodLabel . ' - ' . $subLabel) . '</div>';
            if ($imgDesainBase64 && strpos($imgDesainBase64, 'data:image') === 0) {
                // Gambar Utama diperbesar maksimal agar memenuhi halaman full dengan kerah
                $html .= '<div class="img-box" style="border-top: none; padding: 2px; margin-bottom: 6px;"><img src="' . $imgDesainBase64 . '" style="height: 1200px; width: auto; object-fit: contain;"></div>';
            } else {
                $html .= '<div class="img-box" style="border-top: none; padding: 2px; margin-bottom: 6px; height: 1200px; line-height: 900px; color: #999; font-size: 16px; font-weight: bold;">[ GAMBAR DESAIN BELUM DIUNGGAH ]</div>';
            }

            $html .= '<table style="width: 100%; border-collapse: collapse; border: 1px solid #000; font-size: 13px; margin-bottom: 8px;">
                        <tr>
                            <td style="width: 50%; vertical-align: top; padding: 8px; border-right: 1px solid #000;">
                                <div style="background: #d4d4d4; font-weight: 900; padding: 4px; text-align: center; margin-bottom: 4px; border: 1px solid #000;">KETERANGAN ATASAN</div>
                                <div style="padding: 2px; text-align: center; font-weight: bold;">' . displayVal($ketAtasan) . '</div>
                            </td>
                            <td style="width: 50%; vertical-align: top; padding: 8px;">
                                <div style="background: #d4d4d4; font-weight: 900; padding: 4px; text-align: center; margin-bottom: 4px; border: 1px solid #000;">KETERANGAN BAWAHAN</div>
                                <div style="padding: 2px; text-align: center; font-weight: bold;">' . displayVal($ketBawahan) . '</div>
                            </td>
                        </tr>
                        </table>';

            // --- BLOK KERAH (2 KOLOM: JENIS KIRI, GAMBAR KANAN) ---
            $html .= '<div class="title-box-center" style="margin-top: 0; padding: 6px; font-size: 16px;">REFERENSI KERAH ' . strtoupper($prodLabel . ' - ' . $subLabel) . '</div>';

            $html .= '<table style="width: 100%; border-collapse: collapse; border: 1px solid #000; border-top: none; background: #fff; font-size: 13px;">
                        <tr>
                            <td style="width: 45%; vertical-align: middle; padding: 10px; border-right: 1px solid #000;">
                                <div style="background: #d4d4d4; font-weight: 900; padding: 6px; text-align: center; margin-bottom: 8px; border: 1px solid #000;">JENIS KERAH</div>
                                <div style="padding: 4px; text-align: center; font-weight: 900; font-size: 16px;">' . displayVal($jenisKerahVal) . '</div>
                            </td>
                            <td style="width: 55%; vertical-align: middle; padding: 4px; text-align: center;">';
            if ($imgKerahBase64 && strpos($imgKerahBase64, 'data:image') === 0) {
                // Gambar Kerah diperkecil agar halaman full dengan desain besar
                $html .= '<img src="' . $imgKerahBase64 . '" style="height: 100px; width: auto; object-fit: contain;">';
            } else {
                $html .= '<div style="height: 100px; line-height: 100px; color: #999; font-size: 13px; font-weight: bold;">[ GAMBAR KERAH BELUM DIUNGGAH ]</div>';
            }
            $html .= '      </td>
                        </tr>
                        </table>';

            $html .= '</div>'; // End wrapper visual

            // --- HALAMAN NAMESET (UNTUK DOKUMEN UTAMA) ---
            $namesetMainHtml = '<div class="page-break"></div>';
            $namesetMainHtml .= '<div class="title-box" style="text-align: center; font-size: 18px; margin-bottom: 0;">DATA PESANAN ' . strtoupper($prodLabel . ' - ' . $subLabel) . '</div>';
            
            // Tabel Dasar (Akan digunakan di Utama & Lampiran)
            $tableHeader = '<table class="nameset-table" style="border-top: none;">
                <thead>
                    <tr>
                        <th width="8%">NO.</th>
                        <th class="t-left" width="35%">NAMA PUNGGUNG</th>
                        <th width="15%">NO. PUNGGUNG</th>
                        <th width="12%">SIZE</th>
                        <th class="t-left" width="30%">KETERANGAN</th>
                    </tr>
                </thead>
                <tbody>';
            
            $tableBody = '';
            $namas = $_POST['nama'][$prodId][$subId] ?? [];
            $nomors = $_POST['nomor'][$prodId][$subId] ?? [];
            $sizes = $_POST['size'][$prodId][$subId] ?? [];
            $customSizes = $_POST['size_custom'][$prodId][$subId] ?? [];
            $kets = $_POST['ket'][$prodId][$subId] ?? [];

            $sizeCount = [];
            $totalCount = 0;

            if (count($namas) > 0) {
                for ($i = 0; $i < count($namas); $i++) {
                    if (trim($namas[$i]) === '' && trim($nomors[$i]) === '') continue;

                    $sDisplay = displayVal($sizes[$i]);
                    $sRaw = strtoupper(trim($sizes[$i]));
                    if ($sRaw === 'CUSTOM' && !empty($customSizes[$i])) {
                        $sDisplay = '<strong>' . displayVal($customSizes[$i]) . '</strong>';
                        $sRaw = strtoupper(trim($customSizes[$i]));
                    }

                    $tableBody .= '<tr>
                        <td>' . ($totalCount + 1) . '.</td>
                        <td class="t-left">' . displayVal($namas[$i]) . '</td>
                        <td><strong style="font-size: 15px;">' . displayVal($nomors[$i]) . '</strong></td>
                        <td><strong style="font-size: 15px;">' . $sDisplay . '</strong></td>
                        <td class="t-left">' . displayVal($kets[$i]) . '</td>
                    </tr>';

                    if (!isset($sizeCount[$sRaw])) $sizeCount[$sRaw] = 0;
                    $sizeCount[$sRaw]++;
                    $totalCount++;
                }
            }

            if ($totalCount === 0) {
                $tableBody .= '<tr><td colspan="5" style="padding: 10px;">.......</td></tr>';
            }
            $tableBody .= '</tbody></table>';

            $namesetMainHtml .= $tableHeader . $tableBody;

            // REKAP SIZE (Hanya untuk dokumen utama)
            if ($totalCount > 0) {
                $namesetMainHtml .= '<div class="rekap-container" style="text-align: center; margin-bottom: 20px;">';
                $namesetMainHtml .= '<div style="margin-bottom: 5px; text-decoration: underline; font-size: 18px;">JUMLAH KESELURUHAN: ' . $totalCount . ' PCS</div>';

                $standarSizes = [
                    'XS ANAK', 'S ANAK', 'M ANAK', 'L ANAK', 'XL ANAK',
                    'XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL', '6XL', '7XL', '8XL', '9XL', '10XL'
                ];
                $orderedCount = [];
                foreach ($standarSizes as $sz) {
                    if (isset($sizeCount[$sz]) && $sizeCount[$sz] > 0) {
                        $orderedCount[$sz] = $sizeCount[$sz];
                    }
                }
                foreach ($sizeCount as $sz => $c) {
                    if (!in_array($sz, $standarSizes) && $c > 0) $orderedCount[$sz] = $c;
                }

                $chunkedCount = array_chunk($orderedCount, 10, true);
                foreach ($chunkedCount as $chunk) {
                    $namesetMainHtml .= '<table class="rekap-tabel" style="margin: 8px auto 0 auto;"><tr>';
                    foreach ($chunk as $sz => $c) $namesetMainHtml .= '<th>' . $sz . '</th>';
                    $namesetMainHtml .= '</tr><tr>';
                    foreach ($chunk as $sz => $c) $namesetMainHtml .= '<td>' . $c . '</td>';
                    $namesetMainHtml .= '</tr></table>';
                }
                $namesetMainHtml .= '</div>';
            }
            
            $html .= $namesetMainHtml;

            // --- HTML UNTUK LAMPIRAN (Tanpa Rekap, Tanpa Page Break, Judul Simple) ---
            $appendixHtml .= '<div style="margin-bottom: 10px;">';
            $appendixHtml .= '<div style="font-weight: bold; font-size: 12px; margin-bottom: 5px; text-transform: uppercase;">DATA PESANAN: ' . $prodLabel . ' - ' . $subLabel . '</div>';
            $appendixHtml .= $tableHeader . $tableBody;
            $appendixHtml .= '</div>';

        } // End loop Subcats
    } // End loop Products

    // TAMBAHKAN LAMPIRAN DI AKHIR PDF
    if (!empty($appendixHtml)) {
        $html .= '<div class="page-break"></div>';
        $html .= '<div style="font-size: 14px; font-weight: bold; text-decoration: underline; margin-bottom: 10px;">LAMPIRAN: DATA PESANAN</div>';
        $html .= $appendixHtml;
    }

    $html .= '</main></body></html>';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("Order_" . str_replace(' ', '_', $info['namaOrder']) . ".pdf", ["Attachment" => true]);
    exit;
}

// ==========================================
// DATA KOSONG (Form Blank)
// ==========================================
$dataLoaded = [
    'orderInfo' => [
        'namaBrand' => '',
        'namaOrder' => '',
        'tipeOrder' => '',
        'jenisOrder' => '',
        'kategoriOrder' => [],
        'jenisPrinting' => '',
        'tanggalMasuk' => date('Y-m-d'),
        'dateline' => '',
        'detailingPelanggan' => ''
    ]
];

// Load dynamic data for dropdowns
$dynamicData = [];
$files = glob('data/*.json');
foreach ($files as $file) {
    $key = pathinfo($file, PATHINFO_FILENAME);
    if ($key === 'kerah') continue; // Hapus dropdown kerah
    $dynamicData[$key] = json_decode(file_get_contents($file), true);
}

// Pass to JavaScript
?>
<script>
    window.dynamicData = <?= json_encode($dynamicData) ?>;
</script>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Order Apparel</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
    <!-- jQuery + Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <link href="assets/css/select2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Cropper.js -->
    <link href="assets/css/cropper.min.css" rel="stylesheet">
    <script src="assets/js/cropper.min.js"></script>
    <style>
        ::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        input,
        select,
        textarea {
            text-transform: uppercase;
        }

        .module-enter {
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .collapse-content {
            transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out;
            overflow: hidden;
        }

        .is-collapsed {
            max-height: 0 !important;
            opacity: 0 !important;
            margin: 0 !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            border: none !important;
        }

        .rotate-180 {
            transform: rotate(180deg);
        }

        /* Select2 Overrides */
        .select2-container {
            width: 100% !important;
        }

        .select2-container .select2-selection--single {
            height: 32px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            padding: 2px 4px;
        }

        .select2-container .select2-selection--single .select2-selection__rendered {
            line-height: 28px;
            color: #1e293b;
            padding-left: 6px;
        }

        .select2-container .select2-selection--single .select2-selection__arrow {
            height: 30px;
        }

        .select2-dropdown {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            z-index: 9999;
        }

        .select2-search--dropdown .select2-search__field {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 8px;
            font-size: 12px;
            text-transform: uppercase;
        }

        .select2-results__option {
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 500;
            padding: 6px 10px;
        }

        .select2-results__option--highlighted {
            background: #3b82f6 !important;
        }

        .select2-results__option--selected {
            background: #dbeafe !important;
            color: #1e40af !important;
        }

        .select2-results__option[role="group"]>.select2-results__options {
            padding-left: 0;
        }

        .select2-results__group {
            font-size: 10px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 6px 10px;
            background: #f1f5f9;
        }

        .s2-blue .select2-selection--single {
            background: #eff6ff;
            border-color: #93c5fd;
        }

        .s2-blue .select2-selection--single .select2-selection__rendered {
            color: #1e40af;
            font-weight: 700;
        }
    </style>
</head>

<body class="bg-gray-100 p-4 md:p-8 font-sans text-gray-800 relative">

    <!-- MODAL CROPPER -->
    <div id="cropperModal" class="fixed inset-0 bg-slate-900 bg-opacity-90 z-[100] hidden flex-col items-center justify-center p-4 md:p-10 transition-opacity">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl flex flex-col overflow-hidden" style="height: 85vh;">
            <div class="p-4 bg-slate-800 text-white flex justify-between items-center">
                <h3 class="font-bold text-lg uppercase tracking-wide">Potong Gambar (Crop)</h3>
                <button type="button" onclick="closeCropper()" class="text-slate-300 hover:text-white transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="flex-grow relative bg-slate-100 overflow-hidden flex items-center justify-center p-2">
                <!-- Tempat gambar Cropper ditampilkan -->
                <img id="cropperImage" class="max-w-full max-h-full block">
            </div>
            <div class="p-4 border-t border-slate-200 bg-gray-50 flex justify-end gap-3">
                <button type="button" onclick="closeCropper()" class="px-5 py-2.5 bg-slate-300 text-slate-800 rounded-lg font-bold hover:bg-slate-400 transition shadow-sm uppercase text-sm">Batal</button>
                <button type="button" onclick="applyCrop()" class="px-5 py-2.5 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 transition shadow-lg uppercase text-sm">Terapkan & Simpan</button>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto bg-white rounded-2xl shadow-xl overflow-hidden">
        <form method="POST" id="apparelForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="generate_pdf">

            <div class="bg-slate-900 text-white p-6 flex flex-col md:flex-row justify-between items-center border-b-4 border-red-600">
                <div class="flex-1">
                    <h1 class="text-2xl font-black tracking-wider uppercase text-center md:text-left">FORMAT ORDER <span class="text-red-500">INDOWAREHOUSE</span></h1>
                </div>
                <div class="mt-4 md:mt-0 flex gap-3">
                    <button type="submit" class="flex items-center gap-2 bg-red-600 text-white hover:bg-red-500 px-5 py-2.5 rounded-lg text-sm font-black transition shadow-lg shadow-red-600/30 uppercase tracking-wide">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="7 10 12 15 17 10" />
                            <line x1="12" x2="12" y1="15" y2="3" />
                        </svg>
                        Download SPK PDF
                    </button>
                </div>
            </div>

            <div class="p-6 md:p-8 space-y-8">

                <section class="flex flex-col lg:flex-row gap-6">
                    <div class="flex-grow bg-slate-50 p-6 rounded-xl border border-slate-200">
                        <h2 class="text-lg font-black text-slate-800 border-b-2 border-slate-200 pb-2 mb-4 uppercase tracking-wide">Informasi Pesanan</h2>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="flex flex-col">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Tanggal Masuk</label>
                                <input type="date" name="tanggalMasuk" value="<?= htmlspecialchars($dataLoaded['orderInfo']['tanggalMasuk']) ?>" class="border border-slate-300 rounded-md p-2 text-sm outline-none bg-white focus:ring-2 focus:ring-blue-400 transition-all">
                            </div>
                            <div class="flex flex-col">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Dateline</label>
                                <input type="date" name="dateline" value="<?= htmlspecialchars($dataLoaded['orderInfo']['dateline']) ?>" class="border border-slate-300 rounded-md p-2 text-sm outline-none bg-white focus:ring-2 focus:ring-blue-400 transition-all">
                            </div>
                            <div class="flex flex-col col-span-2">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nama Order (Tim / PO)</label>
                                <input type="text" name="namaOrder" value="<?= htmlspecialchars($dataLoaded['orderInfo']['namaOrder']) ?>" class="border border-slate-300 rounded-md p-2 text-sm outline-none bg-white focus:ring-2 focus:ring-blue-400 transition-all font-bold" required>
                            </div>

                            <div class="flex flex-col col-span-2">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nama Brand</label>
                                <?php $brandVal = $dataLoaded['orderInfo']['namaBrand'];
                                $brandMatched = false; ?>
                                <select name="namaBrand" class="s2-top">
                                    <option value=""></option>
                                    <?php foreach ($dynamicData['brand'] as $brand): ?>
                                        <?php $isSel = (strcasecmp($brandVal, $brand) === 0);
                                        if ($isSel) $brandMatched = true; ?>
                                        <option value="<?= htmlspecialchars($brand) ?>" <?= $isSel ? 'selected' : '' ?>><?= htmlspecialchars($brand) ?></option>
                                    <?php endforeach; ?>
                                    <?php if (!$brandMatched && $brandVal): ?><option value="<?= htmlspecialchars($brandVal) ?>" selected><?= htmlspecialchars($brandVal) ?></option><?php endif; ?>
                                </select>
                            </div>
                            <div class="flex flex-col col-span-1">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Tipe Order</label>
                                <?php $tipeVal = $dataLoaded['orderInfo']['tipeOrder'];
                                $tipeMatched = false; ?>
                                <select name="tipeOrder" class="s2-top">
                                    <option value=""></option>
                                    <?php foreach ($dynamicData['tipe_order'] as $tipe): ?>
                                        <?php $isSel = (strcasecmp($tipeVal, $tipe) === 0);
                                        if ($isSel) $tipeMatched = true; ?>
                                        <option value="<?= htmlspecialchars($tipe) ?>" <?= $isSel ? 'selected' : '' ?>><?= htmlspecialchars($tipe) ?></option>
                                    <?php endforeach; ?>
                                    <?php if (!$tipeMatched && $tipeVal): ?><option value="<?= htmlspecialchars($tipeVal) ?>" selected><?= htmlspecialchars($tipeVal) ?></option><?php endif; ?>
                                </select>
                            </div>
                            <div class="flex flex-col col-span-1">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Jenis Order</label>
                                <?php $jenisVal = $dataLoaded['orderInfo']['jenisOrder'];
                                $jenisMatched = false; ?>
                                <select name="jenisOrder" class="s2-top">
                                    <option value=""></option>
                                    <?php foreach ($dynamicData['jenis_order'] as $jenis): ?>
                                        <?php $isSel = (strcasecmp($jenisVal, $jenis) === 0);
                                        if ($isSel) $jenisMatched = true; ?>
                                        <option value="<?= htmlspecialchars($jenis) ?>" <?= $isSel ? 'selected' : '' ?>><?= htmlspecialchars($jenis) ?></option>
                                    <?php endforeach; ?>
                                    <?php if (!$jenisMatched && $jenisVal): ?><option value="<?= htmlspecialchars($jenisVal) ?>" selected><?= htmlspecialchars($jenisVal) ?></option><?php endif; ?>
                                </select>
                            </div>
                            <div class="flex flex-col col-span-4">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Jenis Printing</label>
                                <?php $printVal = $dataLoaded['orderInfo']['jenisPrinting'] ?? '';
                                $printMatched = false; ?>
                                <select name="jenisPrinting" class="s2-top">
                                    <option value=""></option>
                                    <?php foreach (($dynamicData['jenis_printing'] ?? []) as $jp): ?>
                                        <?php $isSel = (strcasecmp($printVal, $jp) === 0);
                                        if ($isSel) $printMatched = true; ?>
                                        <option value="<?= htmlspecialchars($jp) ?>" <?= $isSel ? 'selected' : '' ?>><?= htmlspecialchars($jp) ?></option>
                                    <?php endforeach; ?>
                                    <?php if (!$printMatched && $printVal): ?><option value="<?= htmlspecialchars($printVal) ?>" selected><?= htmlspecialchars($printVal) ?></option><?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-col">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Detailing Pelanggan</label>
                            <textarea name="detailingPelanggan" rows="2" class="border border-slate-300 rounded-md p-2 text-sm outline-none bg-white focus:ring-2 focus:ring-blue-400 transition-all resize-y" placeholder="Catatan khusus pelanggan..."><?= htmlspecialchars($dataLoaded['orderInfo']['detailingPelanggan']) ?></textarea>
                        </div>

                        <div class="mt-6 border-t border-slate-200 pt-5 bg-blue-50/50 p-4 rounded-xl border border-blue-100">
                            <label class="text-xs font-black text-blue-900 uppercase tracking-wide mb-3 block">Kategori Order / Produk (Pilih Untuk Membuka Modul)</label>
                            <div class="flex flex-wrap gap-2">
                                <?php
                                $selectedKatOrder = $dataLoaded['orderInfo']['kategoriOrder'] ?? [];
                                foreach ($katOrderList as $id => $label):
                                    $checked = in_array($id, $selectedKatOrder) ? 'checked' : '';
                                ?>
                                    <label class="flex items-center gap-1.5 cursor-pointer bg-white border border-slate-300 px-3 py-2 rounded-lg text-[11px] font-bold text-slate-600 hover:bg-blue-50 hover:border-blue-400 transition-all shadow-sm has-[:checked]:bg-blue-600 has-[:checked]:border-blue-700 has-[:checked]:text-white">
                                        <input type="checkbox" name="kategoriOrder[]" value="<?= $id ?>" id="chk_prod_<?= $id ?>" class="w-4 h-4 rounded" onchange="toggleProductModule('<?= $id ?>', '<?= $label ?>')" <?= $checked ?>>
                                        <?= strtoupper($label) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="lg:w-64 flex flex-col gap-4">
                        <h2 class="text-lg font-black text-slate-800 border-b-2 border-slate-200 pb-2 uppercase tracking-wide">Ringkasan Total</h2>

                        <div class="bg-slate-900 text-white p-5 rounded-xl shadow-lg text-center border-b-4 border-red-600">
                            <p class="text-xs font-bold text-slate-300 uppercase tracking-widest">Total Keseluruhan</p>
                            <p class="text-5xl font-black mt-2"><span id="summary_total">0</span> <span class="text-sm font-bold">PCS</span></p>
                        </div>

                        <div id="summary_list" class="space-y-3">
                            <!-- Summary items dinamis muncul di sini -->
                        </div>
                    </div>
                </section>

                <div id="dynamic_modules_container" class="space-y-10">
                    <!-- Modul Produk dinamis muncul di sini -->
                </div>

            </div>
        </form>
    </div>

    <script>
        // =====================================
        // LOGIKA CROPPER.JS UNTUK GAMBAR
        // =====================================
        let cropper = null;
        let currentTargetId = '';

        function openCropper(input, targetId) {
            if (input.files && input.files[0]) {
                currentTargetId = targetId;
                const file = input.files[0];

                // Cek ukuran file (max 5MB untuk menghindari masalah memori)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File terlalu besar. Maksimal 5MB.');
                    input.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        const cropperImg = document.getElementById('cropperImage');
                        if (!cropperImg) {
                            alert('Elemen cropper tidak ditemukan');
                            return;
                        }
                        cropperImg.src = e.target.result;

                        // Tampilkan Modal
                        const modal = document.getElementById('cropperModal');
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');

                        // Inisialisasi Cropper
                        if (cropper) {
                            cropper.destroy();
                        }
                        if (typeof Cropper === 'undefined') {
                            alert('Cropper.js tidak dimuat. Pastikan koneksi internet stabil.');
                            closeCropper();
                            return;
                        }
                        cropper = new Cropper(cropperImg, {
                            viewMode: 1, // Membatasi crop box agar tidak melebihi kanvas gambar
                            autoCropArea: 1, // Area crop full secara default
                            background: false, // Menghilangkan grid background bawaan
                            zoomable: true,
                            scalable: true,
                            responsive: true,
                            restore: true,
                            checkCrossOrigin: false,
                            checkOrientation: false,
                        });
                    } catch (e) {
                        console.error('Error in openCropper:', e);
                        alert('Error saat membuka cropper: ' + e.message);
                        closeCropper();
                    }
                };
                reader.onerror = function() {
                    alert('Gagal membaca file');
                };
                reader.readAsDataURL(file);
                // Bersihkan input file agar pengguna bisa memilih file yang sama lagi jika batal
                input.value = '';
            }
        }

        function closeCropper() {
            const modal = document.getElementById('cropperModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            currentTargetId = '';
        }

        function applyCrop() {
            if (!cropper) {
                alert('Cropper tidak tersedia');
                return;
            }

            try {
                // Ambil hasil crop menjadi base64 dengan limit ukuran agar tidak crash saat diolah PHP/PDF
                const canvas = cropper.getCroppedCanvas({
                    maxWidth: 800,
                    maxHeight: 800
                });

                if (!canvas) {
                    alert('Gagal membuat canvas dari crop');
                    return;
                }

                const base64Url = canvas.toDataURL('image/jpeg', 0.85); // Kualitas 85% untuk efisiensi

                if (!base64Url || base64Url.length < 100) {
                    alert('Gagal mengkonversi ke base64');
                    return;
                }

                // Pasang hasil crop ke kotak preview gambar
                const imgEl = document.getElementById('preview_' + currentTargetId);
                if (!imgEl) {
                    alert('Elemen preview tidak ditemukan');
                    return;
                }
                imgEl.src = base64Url;
                imgEl.classList.remove('hidden');

                // Masukkan Base64 ke input type hidden agar bisa dikirim POST ke PHP
                const base64Input = document.getElementById('base64_' + currentTargetId);
                if (!base64Input) {
                    alert('Input hidden base64 tidak ditemukan');
                    return;
                }
                base64Input.value = base64Url;

                // Tampilkan tombol tong sampah (hapus)
                const btnHapus = document.getElementById('btn_hapus_' + currentTargetId);
                if (btnHapus) btnHapus.classList.remove('hidden');

                closeCropper();
                alert('Gambar berhasil di-apply dan disimpan');
            } catch (e) {
                console.error('Error in applyCrop:', e);
                alert('Terjadi error saat apply crop: ' + e.message);
            }
        }

        function removeImage(targetId) {
            if (confirm('Yakin ingin menghapus gambar ini?')) {
                // Kosongkan src dan sembunyikan gambar
                const imgEl = document.getElementById('preview_' + targetId);
                imgEl.src = '';
                imgEl.classList.add('hidden');

                // Kosongkan input hidden base64
                document.getElementById('base64_' + targetId).value = '';

                // Sembunyikan kembali tombol hapusnya
                document.getElementById('btn_hapus_' + targetId).classList.add('hidden');
            }
        }

        // =====================================
        // LOGIKA UI LAINNYA
        // =====================================

        // Fungsi Toggle Collapse UI
        function toggleCollapse(btn, targetId) {
            const target = document.getElementById(targetId);
            const icon = btn.querySelector('.chevron-icon');
            if (target.classList.contains('is-collapsed')) {
                target.classList.remove('is-collapsed');
                target.style.maxHeight = target.scrollHeight + 1000 + "px"; // Expand
                if (icon) icon.classList.remove('rotate-180');
            } else {
                target.style.maxHeight = target.scrollHeight + "px";
                setTimeout(() => {
                    target.classList.add('is-collapsed');
                }, 10); // Shrink
                if (icon) icon.classList.add('rotate-180');
            }
        }

        // Ukuran dari data dinamis
        const sizesOpt = window.dynamicData && window.dynamicData.size ? window.dynamicData.size : [];

        // Helper: generate <option> HTML from dynamicData key (with optgroup for kerah categories)
        function s2Opts(key, subKey = null) {
            let arr = window.dynamicData && window.dynamicData[key] ? window.dynamicData[key] : [];
            if (subKey && typeof arr === 'object' && !Array.isArray(arr)) {
                arr = arr[subKey] || [];
            }
            let html = '<option value=""></option>';
            let inGroup = false;
            arr.forEach(v => {
                if (v.startsWith('- ')) {
                    if (inGroup) html += '</optgroup>';
                    const label = v.replace(/^-\s*/, '').replace(/\s*-\s*$/, '').trim();
                    html += `<optgroup label="${label}">`;
                    inGroup = true;
                } else {
                    html += `<option value="${v}">${v}</option>`;
                }
            });
            if (inGroup) html += '</optgroup>';
            return html;
        }

        // Init Select2 on all .s2 selects inside a container
        function initSelect2(container) {
            $(container).find('select.s2').each(function() {
                $(this).select2({
                    placeholder: 'Pilih...',
                    allowClear: true,
                    tags: true,
                    width: '100%',
                    dropdownParent: $(this).parent()
                });
            });
        }

        // State Menyimpan Subkategori Aktif tiap Produk
        const activeSubcats = {};

        // Fungsi Helper Pembersih Input Paste Size
        function sanitizeSize(s) {
            if (!s) return sizesOpt.length > 0 ? sizesOpt[0] : 'L';
            let val = String(s).toUpperCase().replace(/\s+/g, ''); // Hapus spasi

            // Buat map dari sizesOpt untuk pencarian cepat
            const map = {};
            sizesOpt.forEach(size => {
                const key = size.replace(/\s+/g, '').toUpperCase();
                map[key] = size;
            });

            return map[val] || String(s).trim().toUpperCase();
        }

        function generateSizeOptions(selected) {
            return sizesOpt.map(s => `<option value="${s}" ${s === selected ? 'selected' : ''}>${s}</option>`).join('');
        }

        function renderProductModule(prodId, prodLabel) {
            activeSubcats[prodId] = []; // Reset subkategori menjadi kosong secara default

            let html = `
        <section id="module_${prodId}" class="module-enter bg-white border-2 border-slate-300 rounded-2xl overflow-hidden shadow-xl mb-10">
            
            <div class="bg-slate-800 p-4 flex justify-between items-center cursor-pointer select-none" onclick="toggleCollapse(this, 'module_content_${prodId}')">
                <h2 class="text-xl font-black text-white uppercase tracking-widest pl-2 flex items-center gap-2">
                    <svg class="chevron-icon w-6 h-6 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                    MODUL: ${prodLabel}
                </h2>
                <button type="button" onclick="event.stopPropagation(); addVariasiPemain('${prodId}')" class="flex items-center gap-1 bg-red-600 text-white hover:bg-red-500 px-3 py-1.5 rounded text-xs font-black transition uppercase shadow-sm">
                    + Tambah Variasi Sub/Pemain
                </button>
            </div>
            
            <div id="module_content_${prodId}" class="collapse-content" style="max-height: 50000px;">
                <div class="p-6 space-y-8 bg-slate-50">
                    <!-- DIKUNCI MAKSIMAL 2 KOLOM (md:grid-cols-2) AGAR LEBAR -->
                    <div id="spec_container_${prodId}" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Spesifikasi Pemain muncul di sini -->
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-black text-slate-800 border-b-2 border-slate-300 pb-2 mb-5 uppercase tracking-wide">Data Nameset & Ukuran</h3>
                        <div id="table_container_${prodId}">
                            <!-- Tabel Pemain muncul di sini -->
                        </div>
                    </div>
                </div>
            </div>
        </section>
        `;
            return html;
        }

        const defaultColors = ['red', 'blue', 'emerald', 'amber', 'purple', 'pink', 'teal'];

        function addVariasiPemain(prodId, subIdStr = null, subLabelStr = null) {
            if (!activeSubcats[prodId]) activeSubcats[prodId] = [];

            let subIndex = activeSubcats[prodId].length + 1;
            let subId = subIdStr || `pemain_${subIndex}`;
            let subLabel = subLabelStr || `Pemain ${subIndex}`;
            let color = defaultColors[(subIndex - 1) % defaultColors.length];

            activeSubcats[prodId].push(subId);

            // 1. Tambah Blok Spesifikasi
            const specHtml = `
        <div id="spec_${prodId}_${subId}" class="bg-white border shadow-md rounded-xl overflow-hidden flex flex-col border-t-4 border-t-${color}-500 transition-all">
            <div class="bg-${color}-50 p-3.5 border-b border-${color}-100 flex justify-between items-center cursor-pointer select-none" onclick="toggleCollapse(this, 'spec_content_${prodId}_${subId}')">
                <input type="text" value="${subLabel}" class="font-black text-sm text-${color}-800 tracking-wider bg-transparent border-none outline-none w-3/4 uppercase" onchange="updateSubLabel(this, '${prodId}', '${subId}')" onclick="event.stopPropagation();">
                <div class="flex items-center gap-2">
                    <input type="hidden" name="subcat[${prodId}][${subId}]" value="1" id="chk_sub_${prodId}_${subId}">
                    <input type="hidden" name="subLabel[${prodId}][${subId}]" value="${subLabel}" id="subLabel_${prodId}_${subId}">
                    <button type="button" onclick="event.stopPropagation(); removeVariasi('${prodId}', '${subId}')" class="text-red-500 hover:text-red-700 bg-white rounded p-1" title="Hapus Variasi Ini"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                    <svg class="chevron-icon w-5 h-5 text-${color}-600 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
            
            <div id="spec_content_${prodId}_${subId}" class="collapse-content" style="max-height: 2000px;">
                <div class="p-5 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-b border-gray-200 pb-3 mb-3">
                        <div class="flex flex-col w-full s2-blue">
                            <label class="text-[10px] font-bold text-gray-800 uppercase mb-1">Jenis Setelan (Stell/Non-Stell)</label>
                            <select name="spec[${prodId}][${subId}][jenisSetelan]" class="s2">${s2Opts('jenis_setelan')}</select>
                        </div>
                        <div class="flex flex-col w-full s2-blue">
                            <label class="text-[10px] font-bold text-gray-800 uppercase mb-1">Pola (Standart/Perempuan)</label>
                            <select name="spec[${prodId}][${subId}][jenisPola]" class="s2">${s2Opts('pola')}</select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="flex flex-col"><label class="text-[9px] font-bold text-gray-500 uppercase">Bahan</label><select name="spec[${prodId}][${subId}][bahan]" class="s2">${s2Opts('bahan_kain')}</select></div>
                        <div class="flex flex-col"><label class="text-[9px] font-bold text-gray-500 uppercase">Warna</label><textarea name="spec[${prodId}][${subId}][warna]" rows="2" class="border border-gray-300 rounded p-1.5 text-xs font-medium resize-y"></textarea></div>
                        <div class="flex flex-col"><label class="text-[9px] font-bold text-gray-500 uppercase">Jml Atasan (Manual)</label><input type="text" name="spec[${prodId}][${subId}][jumlahAtasan]" class="border border-gray-300 rounded p-1.5 text-xs font-medium" placeholder="Auto"></div>
                        <div class="flex flex-col"><label class="text-[9px] font-bold text-gray-500 uppercase">Jml Bawahan (Manual)</label><input type="text" name="spec[${prodId}][${subId}][jumlahBawahan]" class="border border-gray-300 rounded p-1.5 text-xs font-medium" placeholder="Auto"></div>
                        <div class="flex flex-col"><label class="text-[9px] font-bold text-gray-500 uppercase">Logo</label><select name="spec[${prodId}][${subId}][jenisLogo]" class="s2">${s2Opts('logo')}</select></div>
                        <div class="flex flex-col"><label class="text-[9px] font-bold text-gray-500 uppercase">Jenis Rib</label><input type="text" name="spec[${prodId}][${subId}][jenisRib]" class="border border-gray-300 rounded p-1.5 text-xs font-medium"></div>
                        <div class="flex flex-col"><label class="text-[9px] font-bold text-gray-500 uppercase">Tutup Kerah</label><input type="text" name="spec[${prodId}][${subId}][tutupKerah]" class="border border-gray-300 rounded p-1.5 text-xs font-medium"></div>
                        <div class="flex flex-col"><label class="text-[9px] font-bold text-gray-500 uppercase">List Kerah</label><input type="text" name="spec[${prodId}][${subId}][listKerah]" class="border border-gray-300 rounded p-1.5 text-xs font-medium"></div>
                        <div class="flex flex-col"><label class="text-[9px] font-bold text-gray-500 uppercase">List Lengan</label><textarea name="spec[${prodId}][${subId}][listLengan]" rows="2" class="border border-gray-300 rounded p-1.5 text-xs font-medium resize-y"></textarea></div>
                        <div class="flex flex-col"><label class="text-[9px] font-bold text-gray-500 uppercase">List Samping Celana</label><textarea name="spec[${prodId}][${subId}][listSamping]" rows="2" class="border border-gray-300 rounded p-1.5 text-xs font-medium resize-y"></textarea></div>
                        <div class="flex flex-col col-span-2"><label class="text-[9px] font-bold text-gray-500 uppercase">List Bawah Celana</label><input type="text" name="spec[${prodId}][${subId}][listBawahCelana]" class="border border-gray-300 rounded p-1.5 text-xs font-medium"></div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-2 border-t border-gray-200 pt-3">
                        <div class="flex flex-col col-span-2"><label class="text-[10px] font-bold text-gray-800 uppercase bg-gray-100 p-1 text-center rounded">Keterangan Jahitan</label></div>
                        <div class="flex flex-col"><label class="text-[9px] font-bold text-gray-500 uppercase">Pola Jahitan Kerah</label><select name="spec[${prodId}][${subId}][polaJahitanKerah]" class="s2">${s2Opts('pola_jahitan', 'kerah')}</select></div>
                        <div class="flex flex-col"><label class="text-[9px] font-bold text-gray-500 uppercase">Jahitan List Lengan</label><select name="spec[${prodId}][${subId}][jahitanListLengan]" class="s2">${s2Opts('pola_jahitan', 'list_lengan')}</select></div>
                        <div class="flex flex-col"><label class="text-[9px] font-bold text-gray-500 uppercase">Jahitan Bawah</label><select name="spec[${prodId}][${subId}][jahitanBawah]" class="s2">${s2Opts('pola_jahitan', 'bawah')}</select></div>
                        <div class="flex flex-col"><label class="text-[9px] font-bold text-gray-500 uppercase">Jahitan Pundak</label><select name="spec[${prodId}][${subId}][jahitanPundak]" class="s2">${s2Opts('pola_jahitan', 'pundak')}</select></div>
                    </div>

                    <div class="grid grid-cols-1 gap-2 border-t border-gray-200 pt-3">
                        <div class="flex flex-col"><label class="text-[10px] font-bold text-gray-800 uppercase bg-gray-100 p-1 text-center rounded mb-2">Keterangan Resleting</label></div>
                        <div class="flex flex-col"><label class="text-[9px] font-bold text-gray-500 uppercase">Jenis Resleting</label><select name="spec[${prodId}][${subId}][jenisResleting]" class="s2">${s2Opts('resleting')}</select></div>
                    </div>

                    <div class="border-t border-gray-200 pt-4">
                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 mb-4">
                            <label class="text-[10px] font-bold text-gray-800 uppercase text-center block mb-2 tracking-wide">1. Referensi Desain</label>
                            <div class="relative h-28 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center hover:bg-gray-100 overflow-hidden bg-white mb-3 group">
                                <input type="hidden" name="img_base64_${prodId}_${subId}_desain" id="base64_${prodId}_${subId}_desain">
                                <input type="file" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="openCropper(this, '${prodId}_${subId}_desain')">
                                <div class="text-center text-gray-400 absolute z-0"><svg class="mx-auto h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg><span class="text-[10px]">Upload Desain</span></div>
                                <img id="preview_${prodId}_${subId}_desain" class="w-full h-full object-contain absolute inset-0 z-20 hidden bg-white pointer-events-none">
                                <button type="button" id="btn_hapus_${prodId}_${subId}_desain" onclick="removeImage('${prodId}_${subId}_desain')" class="hidden absolute top-1 right-1 bg-red-500 text-white rounded p-1 z-30 shadow hover:bg-red-600 transition" title="Hapus Gambar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="flex flex-col"><label class="text-[9px] font-bold text-gray-500 uppercase">Ket. Atasan</label><textarea name="spec[${prodId}][${subId}][ketAtasan]" rows="2" class="border border-gray-300 rounded p-1.5 text-xs font-medium resize-y"></textarea></div>
                                <div class="flex flex-col"><label class="text-[9px] font-bold text-gray-500 uppercase">Ket. Bawahan</label><textarea name="spec[${prodId}][${subId}][ketBawahan]" rows="2" class="border border-gray-300 rounded p-1.5 text-xs font-medium resize-y"></textarea></div>
                            </div>
                        </div>

                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                            <label class="text-[10px] font-bold text-gray-800 uppercase text-center block mb-2 tracking-wide">2. Referensi Kerah</label>
                            <div class="relative h-28 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center hover:bg-gray-100 overflow-hidden bg-white mb-3 group">
                                <input type="hidden" name="img_base64_${prodId}_${subId}_kerah" id="base64_${prodId}_${subId}_kerah">
                                <input type="file" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="openCropper(this, '${prodId}_${subId}_kerah')">
                                <div class="text-center text-gray-400 absolute z-0"><svg class="mx-auto h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg><span class="text-[10px]">Upload Kerah</span></div>
                                <img id="preview_${prodId}_${subId}_kerah" class="w-full h-full object-contain absolute inset-0 z-20 hidden bg-white pointer-events-none">
                                <button type="button" id="btn_hapus_${prodId}_${subId}_kerah" onclick="removeImage('${prodId}_${subId}_kerah')" class="hidden absolute top-1 right-1 bg-red-500 text-white rounded p-1 z-30 shadow hover:bg-red-600 transition" title="Hapus Gambar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                            <div class="grid grid-cols-1 gap-2">
                                <div class="flex flex-col"><label class="text-[9px] font-bold text-gray-500 uppercase">Jenis Kerah</label><input type="text" name="spec[${prodId}][${subId}][jenisKerah]" class="border border-gray-300 rounded p-1.5 text-xs font-medium"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        `;
            document.getElementById(`spec_container_${prodId}`).insertAdjacentHTML('beforeend', specHtml);
            initSelect2(document.getElementById(`spec_${prodId}_${subId}`));

            // 2. Tambah Blok Tabel Nameset
            const tableHtml = `
        <div id="table_box_${prodId}_${subId}" class="mb-8 bg-white border border-gray-300 rounded-xl overflow-hidden shadow-md transition-all">
            <div class="bg-${color}-50 border-b border-${color}-200 p-3.5 flex flex-col sm:flex-row gap-3 justify-between items-center cursor-pointer select-none" onclick="toggleCollapse(this, 'table_content_${prodId}_${subId}')">
                <div class="flex items-center gap-2">
                    <svg class="chevron-icon w-5 h-5 text-${color}-600 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                    <h3 class="font-black text-sm text-${color}-800 uppercase tracking-widest label-table-${prodId}-${subId}">TABEL ${subLabel} <span id="count_${prodId}_${subId}" class="bg-${color}-200 text-${color}-900 px-2 py-0.5 rounded ml-2 text-xs">0 PCS</span></h3>
                </div>
                <div class="flex flex-wrap gap-2 justify-end" onclick="event.stopPropagation();">
                    <button type="button" onclick="clearTableData('${prodId}', '${subId}')" class="flex items-center gap-1 bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 px-3 py-1.5 rounded-md text-xs font-bold transition shadow-sm" title="Hapus Semua">Kosongkan</button>
                    <button type="button" onclick="handlePasteData('${prodId}', '${subId}')" class="flex items-center gap-1 bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200 px-3 py-1.5 rounded-md text-xs font-bold transition shadow-sm" title="Paste Data (Ctrl+V)">Paste Data</button>
                    <input type="file" id="file_${prodId}_${subId}" accept=".xlsx, .xls, .csv" class="hidden" onchange="handleExcelImport(event, '${prodId}', '${subId}')">
                    <button type="button" onclick="document.getElementById('file_${prodId}_${subId}').click()" class="flex items-center gap-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 px-3 py-1.5 rounded-md text-xs font-bold transition shadow-sm">Import File</button>
                    <button type="button" onclick="addRow('${prodId}', '${subId}')" class="flex items-center gap-1 bg-slate-800 text-white hover:bg-slate-700 px-3 py-1.5 rounded-md text-xs font-bold transition shadow-sm">+ Tambah Baris</button>
                </div>
            </div>
            
            <div id="table_content_${prodId}_${subId}" class="collapse-content" style="max-height: 5000px;">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-100 text-gray-600 text-xs uppercase tracking-wider">
                                <th class="p-3 border-b font-bold w-12 text-center">No</th>
                                <th class="p-3 border-b font-bold">Nama Punggung</th>
                                <th class="p-3 border-b font-bold w-32 text-center">No. Punggung</th>
                                <th class="p-3 border-b font-bold w-32 text-center">Size</th>
                                <th class="p-3 border-b font-bold">Keterangan</th>
                                <th class="p-3 border-b font-bold w-12 text-center">X</th>
                            </tr>
                        </thead>
                        <tbody id="tbody_${prodId}_${subId}"></tbody>
                    </table>
                </div>
                <div id="rekap_${prodId}_${subId}" class="bg-slate-50 p-3 border-t border-gray-200 flex flex-wrap gap-2 justify-center items-center text-sm font-medium"></div>
            </div>
        </div>`;
            document.getElementById(`table_container_${prodId}`).insertAdjacentHTML('beforeend', tableHtml);

            updateGlobalSummary();
        }

        function removeVariasi(prodId, subId) {
            if (confirm('Yakin ingin menghapus variasi ini berserta datanya?')) {
                document.getElementById(`spec_${prodId}_${subId}`).remove();
                document.getElementById(`table_box_${prodId}_${subId}`).remove();
                activeSubcats[prodId] = activeSubcats[prodId].filter(id => id !== subId);
                updateGlobalSummary();
            }
        }

        function updateSubLabel(input, prodId, subId) {
            const val = input.value.toUpperCase();
            const lbls = document.querySelectorAll(`.label-table-${prodId}-${subId}`);
            lbls.forEach(el => {
                el.innerHTML = `TABEL ${val} <span id="count_${prodId}_${subId}" class="${el.querySelector('span').className}">${el.querySelector('span').innerText}</span>`;
            });
            const hiddenLabel = document.getElementById(`subLabel_${prodId}_${subId}`);
            if (hiddenLabel) hiddenLabel.value = val;
        }

        function toggleProductModule(prodId, prodLabel) {
            const chk = document.getElementById(`chk_prod_${prodId}`);
            const container = document.getElementById('dynamic_modules_container');
            let moduleEl = document.getElementById(`module_${prodId}`);

            if (chk.checked) {
                if (!moduleEl) {
                    container.insertAdjacentHTML('beforeend', renderProductModule(prodId, prodLabel));
                } else {
                    moduleEl.classList.remove('hidden');
                }
            } else {
                if (moduleEl) moduleEl.classList.add('hidden');
            }
            updateGlobalSummary();
        }

        function handleSizeChange(select, prodId, subId) {
            const tr = select.closest('tr');
            const customInput = tr.querySelector('.size-custom-input');
            if (select.value === 'CUSTOM') {
                customInput.classList.remove('hidden');
            } else {
                customInput.classList.add('hidden');
            }
            updateNumbersAndCounts(prodId, subId);
        }

        function addRow(prodId, subId, nama = '', nomor = '', size = 'L', ket = '', customSize = '') {
            const tbody = document.getElementById(`tbody_${prodId}_${subId}`);
            if (!tbody) return;
            const tr = document.createElement('tr');
            tr.className = "border-b border-slate-50 hover:bg-slate-50 transition";
            
            // Cek apakah size yang dimasukkan ada di sizesOpt
            let selectedSize = size;
            let finalCustomSize = customSize;
            if (size && !sizesOpt.includes(size)) {
                selectedSize = 'CUSTOM';
                finalCustomSize = size;
            }

            tr.innerHTML = `
            <td class="p-2 text-center text-slate-500 font-bold row-number"></td>
            <td class="p-1.5"><input type="text" name="nama[${prodId}][${subId}][]" value="${nama}" class="w-full border border-slate-300 rounded p-2 text-sm uppercase focus:ring-1 focus:ring-blue-500 outline-none"></td>
            <td class="p-1.5"><input type="text" name="nomor[${prodId}][${subId}][]" value="${nomor}" class="w-full border border-slate-300 rounded p-2 text-sm text-center font-bold focus:ring-1 focus:ring-blue-500 outline-none"></td>
            <td class="p-1.5">
                <select name="size[${prodId}][${subId}][]" onchange="handleSizeChange(this, '${prodId}', '${subId}')" class="size-select w-full border border-slate-300 rounded p-2 text-sm text-center font-bold bg-white focus:ring-1 focus:ring-blue-500 outline-none">
                    ${generateSizeOptions(selectedSize)}
                </select>
                <input type="text" name="size_custom[${prodId}][${subId}][]" value="${finalCustomSize}" oninput="updateNumbersAndCounts('${prodId}', '${subId}')" class="size-custom-input mt-1 w-full border border-amber-300 rounded p-1 text-[10px] text-center font-bold focus:ring-1 focus:ring-amber-500 outline-none ${selectedSize === 'CUSTOM' ? '' : 'hidden'}" placeholder="Input Ukuran...">
            </td>
            <td class="p-1.5"><input type="text" name="ket[${prodId}][${subId}][]" value="${ket}" class="w-full border border-slate-300 rounded p-2 text-sm focus:ring-1 focus:ring-blue-500 outline-none"></td>
            <td class="p-1.5 text-center"><button type="button" onclick="removeRow(this, '${prodId}', '${subId}')" class="text-red-400 hover:text-red-600 bg-red-50 hover:bg-red-100 p-2 rounded transition">X</button></td>
        `;
            tbody.appendChild(tr);
            updateNumbersAndCounts(prodId, subId);
        }

        function removeRow(btn, prodId, subId) {
            btn.closest('tr').remove();
            updateNumbersAndCounts(prodId, subId);
        }

        function clearTableData(prodId, subId) {
            const tbody = document.getElementById(`tbody_${prodId}_${subId}`);
            if (!tbody) return;
            if (tbody.children.length === 0) {
                alert(`Tabel sudah kosong.`);
                return;
            }
            if (confirm(`Apakah Anda yakin ingin menghapus SELURUH data di tabel ini?`)) {
                tbody.innerHTML = '';
                updateNumbersAndCounts(prodId, subId);
            }
        }

        function updateNumbersAndCounts(prodId, subId) {
            const tbody = document.getElementById(`tbody_${prodId}_${subId}`);
            if (!tbody) return;
            const rows = tbody.querySelectorAll('tr');
            let count = 0;
            let sizesCount = {};
            sizesOpt.forEach(s => sizesCount[s] = 0);

            rows.forEach((row, index) => {
                row.querySelector('.row-number').innerText = index + 1;
                count++;
                let sel = row.querySelector('.size-select');
                let customInput = row.querySelector('.size-custom-input');
                let sz = sel.value;
                if (sz === 'CUSTOM' && customInput && customInput.value.trim() !== '') {
                    sz = customInput.value.trim().toUpperCase();
                }

                if (!sizesCount[sz]) sizesCount[sz] = 0;
                sizesCount[sz]++;
            });

            const countLabel = document.getElementById(`count_${prodId}_${subId}`);
            if (countLabel) countLabel.innerText = `${count} PCS`;

            const rekapDiv = document.getElementById(`rekap_${prodId}_${subId}`);
            if (rekapDiv) {
                let rekapHtml = '<span class="text-xs font-bold text-gray-500 mr-2 uppercase">Rekap Size:</span>';
                
                // Urutan standar dulu
                sizesOpt.forEach(s => {
                    if (sizesCount[s] > 0) {
                        rekapHtml += `<div class="px-2 py-0.5 bg-white border border-slate-800 text-slate-800 rounded text-xs font-bold shadow-sm">${s} : ${sizesCount[s]}</div>`;
                    }
                });
                // Baru custom yang tidak ada di standar
                Object.keys(sizesCount).forEach(s => {
                    if (!sizesOpt.includes(s) && sizesCount[s] > 0) {
                        rekapHtml += `<div class="px-2 py-0.5 bg-white border border-amber-600 text-amber-900 rounded text-xs font-bold shadow-sm">${s} : ${sizesCount[s]}</div>`;
                    }
                });

                if (count === 0) rekapHtml += '<span class="text-xs text-gray-400">Belum ada data...</span>';
                rekapDiv.innerHTML = rekapHtml;
            }

            updateGlobalSummary();
        }

        function updateGlobalSummary() {
            let totalKeseluruhan = 0;
            let summaryHtml = '';

            const activeProds = document.querySelectorAll('input[name="kategoriOrder[]"]:checked');

            activeProds.forEach(chk => {
                const prodId = chk.value;
                const prodLabel = chk.nextSibling.textContent.trim();
                let prodTotal = 0;

                if (activeSubcats[prodId]) {
                    activeSubcats[prodId].forEach(subId => {
                        const tbody = document.getElementById(`tbody_${prodId}_${subId}`);
                        if (tbody) prodTotal += tbody.querySelectorAll('tr').length;
                    });
                }

                summaryHtml += `
            <div class="flex justify-between items-center bg-slate-50 p-3 rounded-lg border border-slate-200 shadow-sm">
                <span class="font-bold text-xs text-slate-700 uppercase">${prodLabel}</span>
                <span class="font-black text-lg text-slate-900">${prodTotal}</span>
            </div>`;

                totalKeseluruhan += prodTotal;
            });

            document.getElementById('summary_list').innerHTML = summaryHtml;
            document.getElementById('summary_total').innerText = totalKeseluruhan;
        }

        async function handlePasteData(prodId, subId) {
            try {
                const text = await navigator.clipboard.readText();
                if (!text) {
                    alert("Clipboard kosong!");
                    return;
                }
                processPastedText(text, prodId, subId);
            } catch (err) {
                const pastedText = prompt("Browser memblokir akses clipboard otomatis.\nSilakan PASTE (Ctrl+V) data Anda di kotak ini, lalu klik OK:");
                if (pastedText) processPastedText(pastedText, prodId, subId);
            }
        }

        function processPastedText(text, prodId, subId) {
            const rows = text.split(/\r?\n/);
            let importedCount = 0;

            for (let i = 0; i < rows.length; i++) {
                const rowStr = rows[i].trim();
                if (!rowStr) continue;

                let cols = [];
                if (rowStr.includes('\t')) cols = rowStr.split('\t');
                else if (rowStr.includes(',')) cols = rowStr.split(',');
                else cols = rowStr.split(/\s{2,}/);

                cols = cols.map(c => c.trim());
                let name = '',
                    nomor = '',
                    size = 'L',
                    ket = '';
                let startIndex = (!isNaN(cols[0]) && cols[0] !== '') ? 1 : 0;

                if (cols.length > startIndex) {
                    name = cols[startIndex] ? String(cols[startIndex]).trim() : '';
                    nomor = cols[startIndex + 1] ? String(cols[startIndex + 1]).trim() : '';
                    size = cols[startIndex + 2] ? sanitizeSize(cols[startIndex + 2]) : 'L';
                    ket = cols[startIndex + 3] ? String(cols[startIndex + 3]).trim() : '';
                    if (cols.length > startIndex + 4) ket += " " + cols.slice(startIndex + 4).join(" ");
                }

                if (!sizesOpt.includes(size)) {
                    const possibleSize = sizesOpt.find(s => s === sanitizeSize(ket));
                    if (possibleSize) {
                        size = possibleSize;
                        ket = '';
                    } else if (sizesOpt.includes(sanitizeSize(nomor))) {
                        ket = size;
                        size = sanitizeSize(nomor);
                        nomor = '';
                    } else {
                        size = 'L';
                    }
                }

                if ((name.toLowerCase().includes('nama') || name.toLowerCase().includes('punggung')) &&
                    (nomor.toLowerCase().includes('nomor') || isNaN(nomor) && nomor !== '')) continue;

                if (name !== '' || nomor !== '') {
                    addRow(prodId, subId, name, nomor, size, ket);
                    importedCount++;
                }
            }
            if (importedCount > 0) alert('Sukses! Berhasil paste ' + importedCount + ' baris.');
            else alert('Gagal mendeteksi data yang valid.');
        }

        function handleExcelImport(event, prodId, subId) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, {
                    type: 'array'
                });
                const json = XLSX.utils.sheet_to_json(workbook.Sheets[workbook.SheetNames[0]], {
                    header: 1
                });

                if (json.length > 0) {
                    let importedCount = 0;
                    for (let i = 1; i < json.length; i++) {
                        const row = json[i];
                        if (!row || row.length === 0) continue;
                        let nama = row[1] ? String(row[1]).trim() : '';
                        let nomor = row[2] ? String(row[2]).trim() : '';
                        let size = row[3] ? sanitizeSize(row[3]) : 'L';
                        let ket = row[4] ? String(row[4]).trim() : '';

                        if (row[4] && sizesOpt.includes(sanitizeSize(row[4]))) {
                            nomor = row[3] ? String(row[3]).trim() : '';
                            size = sanitizeSize(row[4]);
                            ket = row[5] ? String(row[5]).trim() : '';
                        }
                        if (!sizesOpt.includes(size)) size = 'L';
                        if (nama !== '' || nomor !== '') {
                            addRow(prodId, subId, nama, nomor, size, ket);
                            importedCount++;
                        }
                    }
                    if (importedCount > 0) alert('Sukses mengimpor ' + importedCount + ' baris.');
                }
                event.target.value = '';
            };
            reader.readAsArrayBuffer(file);
        }


        window.onload = function() {
            // Init Select2 on top-level order info fields
            $('.s2-top').select2({
                placeholder: 'Pilih...',
                allowClear: true,
                tags: true,
                width: '100%'
            });
        }
    </script>

</body>

</html>