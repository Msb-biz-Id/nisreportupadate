# Plan: Refactoring Invoice PDF (1-Page Guarantee Architecture)

> Dokumen: `plan/invoice.md`  
> Status: Siap Dieksekusi  
> Masalah Utama: Invoice PDF di DomPDF seringkali tumpah menjadi **2 halaman** (halaman kedua hanya memuat QR Code, Imbauan Keamanan, atau Catatan Komplain kosong).  
> Sasaran: **Garansi 1 Halaman Penuh (Single-Page Fit)** untuk seluruh invoice reguler (1 s.d. 8 baris item produk + riwayat DP) dengan tata letak padat, profesional, dan elegan.

---

## 1. Audit Penyebab: Mengapa Invoice Menjadi 2 Halaman?

DomPDF menggunakan mesin rendering berbasis ukuran kertas absolut. Pada kertas **A4 Potrait**, tinggi total adalah **297 mm = 841,89 pt** (~1123 px pada 96 DPI).

### 1.1. Rincian Tinggi Vertikal Sebelum Perbaikan

| Komponen Invoice | Lokasi Kode | Tinggi Rata-rata | Akar Masalah Pemborosan Ruang |
| :--- | :--- | :--- | :--- |
| **Margin Kertas Halaman** | `invoice.blade.php:97` | **~80 pt** (28 mm) | `@page { margin: 14mm; }` memakan 14mm atas + 14mm bawah. Ini memangkas area cetak kertas sebesar hampir 10%. |
| **Kop Brand + Logo + Kontak** | `pdf/components/kop.blade.php` | **~130 pt** | Logo `max-height: 58px` + margin-bottom 8px + teks brand 16pt + alamat multi-baris + 4 icon SVG medsos vertikal. |
| **Header Invoice & Tanggal** | `invoice.blade.php:316-328` | **~75 pt** | Font title 18pt tebal, nomor invoice monospace, tanggal terbit & jatuh tempo dengan margin bawah 18px. |
| **Info Grid (Pelanggan & PO)** | `invoice.blade.php:330-369` | **~95 pt** | Dua kotak kartu border dengan padding 10px, line-height 1.4, banyak pemisah `<br>` antarbaris detail. |
| **Tabel Item Produk** | `invoice.blade.php:372-459` | **~120–160 pt** | Padding cell `7px 8px`, header tebal, baris pemisah kategori "PRODUK INTI" dan "ADD-ON", baris diskon & bahan produk. |
| **Tabel Riwayat Pembayaran** | `invoice.blade.php:465-515` | **~85–120 pt** | Tabel riwayat verifikasi DP/Pelunasan dengan padding `6px 8px`, margin atas-bawah 15px. |
| **Rekening Bank & Total Finansial** | `invoice.blade.php:549-716` | **~180 pt** | Kotak rekening bank (kiri) + tabel rincian keuangan 12 baris (kanan: total harga, diskon, voucher, ongkir, DP, refund, sisa tagihan). |
| **Footer Imbauan & QR Code** | `invoice.blade.php:718-748` | **~120 pt** | Kotak imbauan keamanan kuning dengan border dashed + gambar QR Code 90px + margin atas 25px. |
| **Ketentuan Komplain & Unboxing** | `invoice.blade.php:750-755` | **~60 pt** | Tiga butir klausul komplain 3x24 jam & video unboxing di bagian paling bawah. |
| **TOTAL TINGGI DOKUMEN** | — | **~865 – 940 pt** | **MELEBIHI AREA CETAK MAKSIMAL A4 (762 pt)!** |

DomPDF secara otomatis memecah halaman (*page break*) tepat sebelum elemen footer atau tabel pembayaran, menyebabkan **halaman kedua tampak "yatim" (hanya memuat QR Code atau ketentuan komplain saja)**.

---

## 2. Arsitektur Solusi: "1-Page Guarantee Budget"

Untuk memastikan invoice selalu pas dalam 1 halaman, kita menetapkan **Budget Tinggi Vertikal Maksimal $\le 540\text{ pt}$** (menyisakan ruang aman $\sim 220\text{ pt}$ untuk variasi jumlah item):

```
┌────────────────────────────────────────────────────────────────────────┐
│               ARSITEKTUR ANGGARAN TINGGI VERTIKAL (A4)                 │
├────────────────────────────────────────────────────────────────────────┤
│ Margin Kertas Atas & Bawah (8mm + 7mm)                       =  42 pt  │
│ Kop Brand Ramping (Logo 36px + Kontak Inline) + Header Judul =  80 pt  │
│ Info Grid Dua Kolom (Tagihan & Referensi PO Padat)           =  60 pt  │
│ Tabel Item Produk & Add-On (Padding 3.5px, Font 8pt)         =  80 pt  │
│ Riwayat Pembayaran Verified (Dense Single-Line, Font 7.5pt)  =  45 pt  │
│ Rekening Bank + QR 65px (Kiri) & Rekap Finansial (Kanan)     = 130 pt  │
│ Footer Kompak (Imbauan Keamanan + Ketentuan Unboxing)        =  65 pt  │
├────────────────────────────────────────────────────────────────────────┤
│ TOTAL AKUMULASI TINGGI DOKUMEN                               = 502 pt  │
│ KAPASITAS BERSIH KERTAS A4                                   = 762 pt  │
│ SISA BUFFER RUANG AMAN                                       = 260 pt! │
└────────────────────────────────────────────────────────────────────────┘
```

Dengan sisa ruang aman **~260 pt**, invoice dengan **hingga 8 baris item produk + 3 tahapan pembayaran DP** dijamin **tetap bertahan di 1 halaman tunggal**.

---

## 3. Spesifikasi Refactoring Rinci

### 3.1. Penyesuaian Margin Kertas `@page`
**File**: `resources/views/pdf/invoice.blade.php` (Line 96–98)
```css
@page {
    /* Margin diperkecil dari 14mm menjadi 8mm atas, 10mm samping, 7mm bawah */
    margin: 8mm 10mm 7mm 10mm;
}
```

### 3.2. Perampingan Kop Brand
**File**: `resources/views/pdf/components/kop.blade.php`
1. **Logo**: Skala `max-height` diturunkan dari `58px` menjadi `36px` (`max-width: 140px`).
2. **Nama Brand**: Font size disesuaikan dari `16pt` ke `14pt` dengan margin bawah diperkecil (`2px`).
3. **Kontak & Alamat**: Dibuat mengalir secara horizontal (*inline with bullets*), bukan baris baru terpisah:
   ```html
   <div style="font-size: 7.5pt; color: #4B5563; margin-top: 2px; line-height: 1.25;">
       @if ($brand?->alamat) <span>{{ $brand->alamat }}</span> · @endif
       @if ($brand?->no_hp) <span>WA: {{ $brand->no_hp }}</span> · @endif
       @if ($brand?->email) <span>Email: {{ $brand->email }}</span> @endif
   </div>
   ```
4. **Ikon Media Sosial**: Ditampilkan mini secara horizontal inline (`height: 9px; width: 9px; vertical-align: middle;`).

### 3.3. Penataan Info Grid (Pelanggan & PO)
**File**: `resources/views/pdf/invoice.blade.php` (Line 330–369)
1. Padding kartu `.info-card` dikurangi dari `10px` menjadi `5px 8px`.
2. Margin bawah `.info-grid` diturunkan dari `16px` ke `8px`.
3. Alamat pelanggan diringkas: satu baris alamat lengkap dengan font `7.5pt`.
4. Baris status promo / ekspedisi / resi dibuat inline atau dipadatkan dengan font `7.5pt`.

### 3.4. Tabel Produk & Add-On Ultra-Dense
**File**: `resources/views/pdf/invoice.blade.php` (Line 372–459)
1. Padding sel tabel (`th`, `td`):
   - `th`: Diturunkan dari `8px` ke `4px 6px`, font `8pt`.
   - `td`: Diturunkan dari `7px 8px` ke `3.5px 6px`, font `8pt`.
2. Baris subheader "PRODUK INTI" dan "ADD-ON": Padding `2px 6px`, font `7.5pt`.
3. Keterangan Bahan & Diskon: Dibuat inline kecil di bawah nama produk (font `7pt`, margin-top `1px`).
4. Margin bawah tabel items diturunkan dari `12px` ke `6px`.

### 3.5. Tabel Riwayat Pembayaran (Verified Payments)
**File**: `resources/views/pdf/invoice.blade.php` (Line 465–515)
1. Margin atas & bawah dikurangi dari `15px` menjadi `6px`.
2. Padding sel tabel diturunkan menjadi `2.5px 5px`.
3. Font diturunkan menjadi `7.5pt`.
4. Kolom deskripsi dan nomor rekening bank asal digabung ringkas dalam 1 baris.
5. Tambahkan properti CSS: `page-break-inside: avoid;`.

### 3.6. Konsolidasi Dua Kolom: Rekening Bank, QR Code, & Total Keuangan
**File**: `resources/views/pdf/invoice.blade.php` (Line 549–716)
Struktur diubah menjadi 2 kolom simetris yang sangat padat:
* **Kolom Kiri (Lebar 45%)**:
  - Kotak Rekening Pembayaran Resmi (padding `6px 8px`, border radius `4px`, font bank `9pt`, font rekening monospace `10pt`).
  - Posisi QR Code disatukan di kolom kiri ini tepat di bawah info rekening (ukuran QR disesuaikan dari `90px` ke **`65px`**).
* **Kolom Kanan (Lebar 55%)**:
  - Tabel rincian keuangan: padding vertikal `2px 0` per baris.
  - Font label & nilai: `8pt` (Sisa tagihan `10.5pt` merah tebal).
  - Informasi refund / cashback (jika ada): disederhanakan menjadi baris biasa di dalam tabel, bukan kotak besar terpisah.

### 3.7. Footer Terpadu (Single-Block Security & Terms Footer)
**File**: `resources/views/pdf/invoice.blade.php` (Line 718–755)
1. Satukan pesan terima kasih, imbauan keamanan pembayaran, link pelacakan, dan 3 butir ketentuan komplain ke dalam 1 blok footer bergaris tipis di bagian bawah.
2. Ukuran font: `6.8pt`, `line-height: 1.25`, warna teks `#4B5563`.
3. Bungkus dengan container pelindung:
   ```css
   .footer-unified {
       margin-top: 6px;
       padding-top: 4px;
       border-top: 1px solid #E5E7EB;
       page-break-inside: avoid;
   }
   ```

---

## 4. Fallback Multi-Halaman Dinamis (Untuk Pesanan > 8 Item)

Bagaimana jika suatu saat ada pesanan perusahaan dengan 15 atau 20 baris item produk yang secara fisik memang tidak mungkin muat di 1 halaman?

Sistem menerapkan **Clean Multi-Page Fallback**:
1. `table.items` diberi style CSS:
   ```css
   table.items {
       page-break-inside: auto;
   }
   table.items tr {
       page-break-inside: avoid;
       page-break-after: auto;
   }
   table.items thead {
       display: table-header-group; /* Otomatis ulang header tabel di halaman 2 */
   }
   ```
2. Blok rincian pembayaran, rekening bank, total tagihan, dan footer dikunci bersama dengan `page-break-inside: avoid;` sehingga jika berpindah ke halaman kedua, seluruh komponen keuangan dan tanda tangan berpindah secara utuh dan tetap rapi.

---

## 5. File yang Dimodifikasi

| No | File Sasaran | Aksi Perubahan |
| :--- | :--- | :--- |
| 1 | `resources/views/pdf/invoice.blade.php` | Refactoring CSS `@page`, penataan padding rapat, perampingan tabel items & pembayaran, penyatuan kolom bank + QR code, dan penyederhanaan footer terpadu. |
| 2 | `resources/views/pdf/components/kop.blade.php` | Pengecilan batas logo (`max-height: 36px`), format kontak & alamat inline, dan perampingan ikon media sosial. |

---

## 6. Rencana Pengujian & Verifikasi

1. **Uji Kasus 1 (Order Minimal - 1 Item Tanpa DP)**:
   - Verifikasi total tinggi berkisar antara ~450–480 pt.
   - Hasil harus murni **1 halaman A4**.
2. **Uji Kasus 2 (Order Standar - 3 Item Inti + 2 Add-On + DP 1 & DP 2)**:
   - Verifikasi tabel riwayat pembayaran muncul dengan rapi.
   - Hasil harus murni **1 halaman A4**.
3. **Uji Kasus 3 (Order Ekstrem - 12 Item)**:
   - Verifikasi *Clean Multi-Page Fallback*.
   - Halaman 1 memuat item 1–8.
   - Halaman 2 memuat sisa item + header tabel berulang + rekap bank & keuangan utuh.
4. **Verifikasi Output PDF**:
   - Jalankan uji via browser dan `Pdf::loadView('pdf.invoice', ...)->output()`.
   - Pastikan teks tajam, barcode/QR terbaca jelas oleh smartphone, dan tidak ada elemen terpotong.
