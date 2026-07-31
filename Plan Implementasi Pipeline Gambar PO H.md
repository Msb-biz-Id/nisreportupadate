# Plan Implementasi Pipeline Gambar PO HD dan Aman

## 1. Tujuan

Meningkatkan kualitas gambar pada preview PO web dan PDF Dompdf dengan target:

- Upload baru tidak lagi disimpan sebagai WebP.
- JPEG/PNG baru digunakan langsung oleh web dan PDF.
- WebP lama tetap dapat digunakan oleh PO lama.
- Konversi WebP tidak dilakukan berulang saat generate PDF.
- Resolusi crop cukup untuk output A4.
- Tidak ada upscale gambar kecil.
- Transparansi PNG tetap terjaga.
- Penghapusan gambar tidak memutus PO lain, preview web, atau PDF.
- DPI Dompdf tetap `96` sebagai baseline.
- Tidak terjadi regresi layout, ukuran PDF, atau performa.

## 2. Keputusan Arsitektur

### 2.1 Upload Baru

Format output:

| Kondisi | Format |
|---|---|
| Foto/desain tanpa transparansi | JPEG quality 90–92 |
| Logo/gambar dengan transparansi | PNG |
| Line-art atau teks kecil yang rusak jika JPEG | PNG |
| Input WebP | Diterima, tetapi output baru JPEG/PNG |

Alur:

```text
gambar asli
-> crop dan rotasi satu kali
-> encode JPEG/PNG satu kali
-> simpan ke storage
-> digunakan langsung oleh web dan PDF
```

Jangan hanya mengganti ekstensi `.webp` menjadi `.jpg`. Isi file harus benar-benar sesuai dengan MIME dan ekstensi.

### 2.2 WebP Legacy

- File WebP lama tidak dihapus.
- Path WebP lama di database tidak diubah.
- Preview web tetap menggunakan file WebP lama.
- PDF menggunakan resolver legacy.
- WebP lama dikonversi hanya jika diperlukan.
- Hasil konversi disimpan dalam cache PDF.
- Cache tidak boleh menggantikan atau memindahkan file sumber.

## 3. Target Resolusi

Target untuk gambar yang ditampilkan pada PO A4:

- Sisi panjang ideal minimal sekitar 1600 px.
- Sisi panjang maksimum 2400 px.
- Batas maksimum dapat diuji hingga 3000 px bila ada kebutuhan nyata.
- Gambar kecil tidak boleh di-upscale.
- Target minimum bukan alasan untuk menambahkan pixel buatan.

Contoh:

```text
4000 x 3000 -> 2400 x 1800
1200 x 900  -> 1200 x 900
2000 x 4000 -> 1200 x 2400
```

Resolusi efektif:

```text
PPI efektif = jumlah pixel / ukuran tampil dalam inch
```

Gambar 2400 px yang ditampilkan selebar 15 cm menghasilkan sekitar 406 PPI, sehingga DPI Dompdf tidak perlu dinaikkan untuk menambah ketajaman.

## 4. Perubahan Frontend

File utama:

```text
resources/js/Components/ImageUploader.jsx
```

### 4.1 Crop

- Gunakan source gambar asli, bukan thumbnail.
- Hitung dimensi crop dari area pixel sebenarnya.
- Batasi sisi panjang maksimal 2400 px.
- Pertahankan ukuran asli jika lebih kecil.
- Jangan melakukan upscale.

### 4.2 Rotasi dan Single Encoding

- Simpan rotasi sebagai state 0, 90, 180, atau 270.
- Jangan membuat base64 per tombol rotate.
- Terapkan rotasi dan crop pada canvas final.
- Jalankan `toBlob()` hanya satu kali saat upload final.
- Jangan melakukan re-encoding JPEG berkali-kali.

### 4.3 Format Output

- Deteksi alpha pada area crop.
- Jika terdapat pixel alpha kurang dari 255, hasilkan PNG.
- Jika seluruh pixel opaque, hasilkan JPEG quality 0.92.
- Nama file dan MIME harus sesuai isi aktual.
- Input tetap dapat menerima jpeg, png, dan webp.

## 5. Perubahan Upload Backend

File utama:

```text
app/Http/Controllers/UploadController.php
```

Checklist:

- Hapus pemaksaan nama file `.webp` untuk upload baru.
- Simpan JPEG sebagai `.jpg`.
- Simpan PNG sebagai `.png`.
- Validasi MIME aktual, bukan ekstensi saja.
- Tetap izinkan WebP sebagai input kompatibilitas bila diperlukan.
- Jangan mengubah file lama hanya karena memiliki ekstensi WebP.
- Pertahankan struktur path yang kompatibel dengan URL web saat ini.
- Catat dimensi, MIME, dan ukuran file jika diperlukan untuk audit kualitas.

## 6. Resolver PDF

File utama:

```text
app/Support/PdfHelper.php
```

Perilaku:

```text
JPEG/PNG:
    baca file langsung sebagai Data URI

WebP:
    cari cache PDF
    jika cache valid, gunakan cache
    jika belum ada, konversi satu kali
    simpan cache
    gunakan hasil cache
```

Ketentuan:

- JPEG/PNG tidak boleh melalui GD conversion.
- Cache menggunakan key berdasarkan path, ukuran, waktu modifikasi, atau hash sumber.
- Jika sumber berubah, cache lama tidak boleh digunakan.
- Penulisan cache harus atomic:
  - tulis ke file sementara;
  - validasi hasil;
  - rename ke nama final.
- Gunakan lock untuk mencegah dua request membuat cache bersamaan.
- Jangan menggunakan cache berukuran nol atau hasil konversi gagal.
- Satukan resolver logo dan gambar PO agar semua PDF memakai aturan yang sama.

## 7. Cache WebP Legacy

Contoh struktur:

```text
storage/app/public/orders/.../gambar.webp
storage/app/pdf-cache/{source-hash}/gambar.jpg
```

Aturan:

- Cache PDF boleh dihapus tanpa menghapus sumber.
- Cache tidak boleh dijadikan sumber utama web.
- Sumber WebP tidak boleh dihapus oleh proses pembuatan cache.
- Cache transparan disimpan sebagai PNG.
- WebP opaque dapat dikonversi ke JPEG quality 90–92.
- Sediakan command batch opsional dengan mode `--dry-run`.

## 8. Template PDF

Audit template:

```text
resources/views/pdf/fo.blade.php
resources/views/pdf/fo_draft.blade.php
resources/views/pdf/invoice.blade.php
resources/views/pdf/components/kop.blade.php
```

Aturan CSS:

```css
.pdf-image {
    display: block;
    max-width: 100%;
    height: auto;
}

.pdf-image-fixed {
    width: 150mm;
    max-width: 100%;
    height: auto;
}
```

Checklist:

- Jangan memaksa gambar kecil dengan `width: 100%` tanpa kebutuhan layout.
- Jangan menetapkan width dan height yang mendistorsi rasio.
- Gunakan mm, cm, atau pt untuk ukuran fisik yang memang harus konsisten.
- Jangan memakai CSS untuk mengatasi resolusi sumber yang kurang.
- Pastikan gambar tidak keluar dari area margin A4.

## 9. Konfigurasi Dompdf

File:

```text
config/dompdf.php
```

Keputusan:

```php
'dpi' => 96,
```

Jangan menaikkan DPI global menjadi 150 sebagai solusi gambar buram karena DPI:

- Tidak menambah pixel.
- Tidak mengembalikan detail yang hilang.
- Dapat mengubah ukuran fisik asset berbasis pixel.
- Dapat menggeser layout.
- Dapat meningkatkan penggunaan memory dan waktu render.

150 DPI hanya boleh diuji bila audit membuktikan terdapat masalah pemetaan ukuran fisik pada template. Pengujian harus mencakup seluruh template PDF dan regresi layout.

`enable_remote` hanya boleh dimatikan setelah seluruh resource eksternal diaudit dan dipastikan tidak diperlukan.

## 10. Audit Penghapusan Gambar

### 10.1 Prinsip

Menghapus referensi dari satu PO tidak otomatis berarti menghapus file fisik.

File hanya boleh dihapus jika tidak lagi direferensikan oleh:

- PO aktif.
- PO lama.
- Draft PO.
- Item order.
- Produk.
- Brand.
- Invoice.
- Dokumen lain.
- Path JSON atau metadata lain.

### 10.2 Lokasi yang Wajib Diaudit

Cari seluruh penggunaan:

- `Storage::delete`
- `Storage::disk()->delete`
- `unlink`
- `File::delete`
- method `destroy`
- method `removeImage`
- method `deleteImage`
- form edit yang mengganti atau mengosongkan gambar
- observer model
- job cleanup
- scheduled command
- komponen frontend yang mengirim request delete

### 10.3 Reference-Safe Delete

Sebelum menghapus file:

- Normalisasi path.
- Pastikan path berada di root storage yang diizinkan.
- Tolak path kosong, root folder, atau path traversal.
- Cari semua referensi pada tabel yang relevan.
- Periksa variasi:
  - path relatif;
  - prefix `storage/`;
  - URL `/storage/...`;
  - path pada JSON;
  - nama/path lama.
- Jika masih ada referensi lain, hapus hanya referensi dari record saat ini.
- Jika tidak ada referensi, hapus file sumber.
- Hapus cache PDF terkait setelah sumber berhasil dihapus.
- Catat hasil operasi pada audit log.

### 10.4 Penggantian Gambar

Urutan wajib:

```text
simpan gambar baru
-> verifikasi file baru valid
-> update database dalam transaction
-> verifikasi web/PDF dapat membaca path baru
-> tandai file lama untuk cleanup
-> hapus file lama hanya jika tidak lagi direferensikan
```

Jika upload atau update database gagal:

- Referensi lama tetap dipertahankan.
- File lama tidak boleh dihapus.
- File baru yang tidak terpakai dibersihkan melalui proses terkontrol.

### 10.5 PO Lama

- Jangan mengubah path WebP lama otomatis.
- Jangan memindahkan atau me-rename sumber WebP.
- Jangan menganggap semua WebP sebagai file yatim.
- Cache PDF tidak boleh mengubah sumber WebP.
- Cleanup legacy harus memiliki mode `--dry-run`.
- Penghapusan massal wajib memerlukan verifikasi dan backup.

## 11. Pengujian Wajib

### Upload

- JPEG landscape dan portrait.
- PNG transparan.
- PNG opaque.
- WebP opaque dan transparan.
- Gambar kecil.
- Gambar resolusi tinggi.
- EXIF orientation.
- Rotasi 90, 180, dan 270 derajat.
- Crop dengan berbagai rasio.

### PDF

- PO baru dengan JPEG.
- PO baru dengan PNG transparan.
- PO lama dengan WebP.
- PDF dengan satu dan banyak gambar.
- Logo WebP legacy.
- Cache pertama dan cache berikutnya.
- Cache invalid setelah sumber berubah.
- Penghapusan cache tanpa penghapusan sumber.

### Penghapusan

- Gambar hanya dipakai satu PO.
- Gambar dipakai beberapa PO.
- Penggantian gambar.
- Pembatalan upload.
- Kegagalan generate PDF.
- Dua request delete bersamaan.
- Path dengan variasi prefix.
- File yatim melalui command `--dry-run`.

### Performa

Bandingkan sebelum dan sesudah:

- Waktu upload.
- Waktu generate PDF.
- Peak memory PHP.
- Ukuran file gambar.
- Ukuran PDF.
- PO dengan 1, 5, dan banyak gambar.

## 12. Kriteria Penerimaan

Implementasi berhasil apabila:

- Upload baru menghasilkan JPEG/PNG, bukan WebP.
- JPEG/PNG digunakan langsung oleh Dompdf.
- WebP lama tetap tampil di web dan PDF.
- WebP legacy dikonversi maksimal sekali per versi sumber.
- Crop besar tidak melebihi 2400 px.
- Gambar kecil tidak di-upscale.
- Transparansi PNG tetap terjaga.
- Gambar tidak lebih buram pada ukuran tampil yang sama.
- Ukuran PDF tetap wajar.
- Waktu dan memory generate PDF tidak memburuk.
- Tidak ada perubahan layout yang tidak diinginkan.
- Menghapus gambar dari satu PO tidak menghilangkan gambar dari PO lain.
- Penggantian gambar tidak menghapus gambar lama sebelum gambar baru siap.
- File sumber tidak terhapus ketika PDF gagal dibuat.
- Cache PDF tidak pernah menghapus sumber.
- DPI tetap 96.
- Semua operasi penghapusan tercatat dan dapat diaudit.

## 13. Urutan Implementasi

1. Audit dan catat baseline.
2. Audit seluruh penggunaan gambar dan seluruh jalur delete.
3. Implementasikan frontend crop, rotasi, resolusi, dan format.
4. Ubah backend agar tidak memaksa WebP.
5. Implementasikan resolver JPEG/PNG langsung.
6. Implementasikan cache WebP legacy.
7. Sentralisasi resolver logo dan gambar PDF.
8. Perbaiki CSS template PDF.
9. Tambahkan reference-safe delete.
10. Jalankan unit, feature, smoke test, dan pengujian performa.
11. Deploy bertahap.
12. Pantau error PDF, missing image, cleanup, ukuran PDF, dan waktu generate.
13. Jangan hapus WebP legacy sampai seluruh referensi dan hasil migrasi tervalidasi.

## 14. Retensi dan Lifecycle Cache Gambar PDF

### 14.1 Perilaku Perintah Laravel

- `php artisan optimize` hanya mengoptimalkan cache aplikasi Laravel seperti config, route, event, dan view.
- `php artisan optimize` tidak boleh menghapus cache gambar PDF.
- Cache gambar PDF wajib disimpan sebagai file pada folder storage khusus, misalnya:
  `storage/app/pdf-image-cache/`
- Cache gambar tidak boleh disimpan di `bootstrap/cache/`.
- Cache gambar tidak boleh menjadi satu-satunya sumber gambar.
- Sumber utama tetap file JPEG, PNG, atau WebP legacy.

### 14.2 Perintah Pembersihan Cache

- `php artisan optimize:clear` dan `php artisan cache:clear` harus diuji agar tidak menghapus cache gambar secara tidak sengaja.
- Jika cache gambar menggunakan Laravel Cache, gunakan store atau namespace terpisah dari cache aplikasi umum.
- Penghapusan cache gambar hanya boleh dilakukan oleh command khusus.
- Command cleanup wajib mendukung:
  - `--dry-run`
  - laporan jumlah file dan ukuran yang akan dihapus
  - filter umur cache
  - konfirmasi sebelum penghapusan aktual
- Penghapusan cache tidak boleh menghapus file sumber JPEG, PNG, atau WebP.
- Cache yang dihapus harus dapat dibuat ulang otomatis dari file sumber WebP legacy.

### 14.3 Deployment dan Storage

- Proses deployment tidak boleh menghapus folder sumber gambar.
- Folder cache gambar harus berada pada storage yang persistent.
- Jika deployment menggunakan container atau proses build baru, storage gambar dan cache harus dipastikan tetap ter-mount atau dipersistenkan.
- Jangan menjalankan cleanup massal pada seluruh folder `storage` sebagai bagian dari deployment tanpa pengecualian untuk:
  - gambar upload
  - WebP legacy
  - cache gambar PDF
- Sebelum migrasi atau cleanup, lakukan backup atau validasi daftar file yang masih direferensikan.

### 14.4 Recovery

Jika cache gambar hilang:

```text
cache tersedia -> gunakan cache
cache tidak tersedia + sumber tersedia -> buat ulang cache dari sumber
cache dan sumber tidak tersedia -> gunakan fallback aman dan catat warning
```

- Hilangnya cache tidak boleh membuat gambar sumber ikut hilang.
- PDF harus tetap dapat dibuat ulang selama sumber WebP legacy masih tersedia.
- Kegagalan membuat cache tidak boleh menghentikan seluruh proses generate PDF.
- Sistem wajib mencatat cache hit, cache miss, cache rebuild, dan kegagalan resolver tanpa mencatat isi gambar atau data sensitif.
