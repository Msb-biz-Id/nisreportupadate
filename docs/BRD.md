# Business Requirements Document (BRD)

## Sistem Manajemen Order Multi-Brand (NISReport)

**Versi:** 2.1  
**Tanggal:** 13 Mei 2026  
**Penulis:** Tim Development  
**Status:** Final Review - FULL BRD  

---

# 1. Pendahuluan

## 1.1 Latar Belakang

Sistem ini dirancang untuk mengelola orderan masuk dan laporan multi-data yang robust, fast, dan clean untuk bisnis apparel/jersey dengan dukungan multi-brand. Sistem beroperasi secara terisolasi berdasarkan brand.id dimana semua master data bersifat spesifik per brand. Sistem ini tertutup (tidak ada register publik) dengan mekanisme akses user yang terkontrol.

## 1.2 Tujuan Sistem

1. Mengelola orderan masuk dari berbagai brand secara terisolasi
2. Menyediakan tracking publik untuk pelanggan berdasarkan Nomor PO
3. Melindungi data order yang sudah masuk produksi (read-only)
4. Menyediakan laporan komprehensif meliputi Rijek, Profit, dan Performance
5. Menyediakan tools AI untuk otomatisasi tugas admin
6. Menyediakan notifikasi otomatis via Email/WhatsApp
7. Audit trail lengkap untuk keamanan

## 1.3 Ruang Lingkup

- Multi-brand management dengan brand switching
- Public order tracking
- Production management dengan progress tracking

- AI-powered admin tools
- Comprehensive reporting dengan export Excel/PDF
- Notification system
- Complete audit trail

---

# 2. User Roles & Permissions

## 2.1 Daftar Role User

| Role | Deskripsi | Akses Multi-Brand | Komentar |
|------|-----------|------------------|----------|
| **Superadmin** | Akses penuh ke semua brand + fitur comparison | Ya (Semua Brand) | Hanya untuk owner utama |
| **Owner** | Owner spesifik per brand yang ditugaskan | Tidak (1 Brand) | Bisa memiliki multiple brand assignment |
| **Admin Brand** | Mengelola master data & input order | Tidak (1 Brand) | Tidak bisa hapus order produksi |
| **Reseller** | Mengelola order & pelanggan via master data global reseller | Tidak (1 Brand) | Menggunakan master data global untuk semua reseller |
| **Admin Produksi** | Mengelola progress & laporan produksi | Tidak (1 Brand) | Tidak bisa edit order produksi |
| **Admin Keuangan** | Mengelola seluruh keuangan perusahaan | Tidak (1 Brand) | Validasi invoice, refund, pemasukan & pengeluaran |

## 2.2 Rincian Permissions per Role

### 2.2.1 Superadmin

| Fitur | Create | Read | Update | Delete |
|-------|--------|------|--------|--------|
| Brand Management | ✓ | ✓ | ✓ | ✓ |
| User Management (Semua Brand) | ✓ | ✓ | ✓ | ✓ |
| Comparison Brand Report | ✓ | ✓ | - | - |
| View All Reports (Semua Brand) | ✓ | ✓ | - | - |
| System Settings | ✓ | ✓ | ✓ | - |
| AI Configuration (Global) | ✓ | ✓ | ✓ | - |
| Export Semua Data | ✓ | ✓ | - | - |
| View Audit Trail (Semua) | ✓ | ✓ | - | - |
| Inventory Global View | ✓ | ✓ | - | - |
| Finance Global View | ✓ | ✓ | - | - |

### 2.2.2 Owner

| Fitur | Create | Read | Update | Delete |
|-------|--------|------|--------|--------|
| Brand Settings | ✓ | ✓ | ✓ | ✓ |
| User Management (Brand Sendiri) | ✓ | ✓ | ✓ | ✓ |
| Master Data (Brand Sendiri) | ✓ | ✓ | ✓ | ✓ |
| Order Management | ✓ | ✓ | ✓ | ✓ |
| Reports (Brand Sendiri) | ✓ | ✓ | - | - |
| Consolidated Reports (All Owned Brands) | - | ✓ | - | - |
| Comparison (Owned Brands Only) | - | ✓ | - | - |
| Export Data | ✓ | ✓ | - | - |
| Inventory (Brand Sendiri) | ✓ | ✓ | ✓ | ✓ |
| Finance (Brand Sendiri) | ✓ | ✓ | - | - |
| View Audit Trail (Brand) | ✓ | ✓ | - | - |

### 2.2.3 Admin Brand

| Fitur | Create | Read | Update | Delete |
|-------|--------|------|--------|--------|
| Master Data Produk | ✓ | ✓ | ✓ | ✓ |
| Master Data Pelanggan | ✓ | ✓ | ✓ | ✓ |
| Master Data Promo | ✓ | ✓ | ✓ | ✓ |
| Master Data Kategori Order | ✓ | ✓ | ✓ | ✓ |
| Master Data Sumber Order | ✓ | ✓ | ✓ | ✓ |
| Input Order Baru | ✓ | ✓ | ✓ | ✓* |
| Laporan Penjualan | ✓ | ✓ | - | - |
| Laporan Promo | ✓ | ✓ | - | - |
| Laporan Pelanggan | ✓ | ✓ | - | - |
| Laporan Wilayah | ✓ | ✓ | - | - |
| Export Laporan | ✓ | ✓ | - | - |
| Inventory View | ✓ | ✓ | - | - |
| Notification Settings | ✓ | ✓ | ✓ | - |

*Catatan: Setelah order masuk produksi, tidak bisa edit/hapus

### 2.2.4 Reseller

| Fitur | Create | Read | Update | Delete |
|-------|--------|------|--------|--------|
| Master Data Produk Global Reseller | - | ✓ | - | - |
| Master Data Pelanggan Global Reseller | - | ✓ | - | - |
| Master Data Promo Global Reseller | - | ✓ | - | - |
| Master Data Kategori Order Global Reseller | - | ✓ | - | - |
| Master Data Sumber Order Global Reseller | - | ✓ | - | - |
| Input Order Baru (Draft & Terbitkan) | ✓ | ✓ | ✓ | ✓* |
| Repeat Order | ✓ | ✓ | - | - |
| Refund Management | ✓ | ✓ | ✓ | - |
| Laporan Penjualan | ✓ | ✓ | - | - |
| Laporan Pelanggan | ✓ | ✓ | - | - |
| Export Laporan | ✓ | ✓ | - | - |
| Notification Settings | ✓ | ✓ | ✓ | - |

*Catatan: Setelah order diterbitkan & masuk produksi, tidak bisa edit/hapus. Reseller menggunakan master data global reseller yang dipakai bersama oleh semua reseller.

### 2.2.5 Admin Produksi

| Fitur | Create | Read | Update | Delete |
|-------|--------|------|--------|--------|
| View Orders (Production) | - | ✓ | - | - |
| Update Progress PO | - | - | ✓ | - |
| Master Data Progress | ✓ | ✓ | ✓ | ✓ |
| Input Rijek | ✓ | ✓ | ✓ | ✓ |
| Kanban/Timeline View | - | ✓ | - | - |
| Gantt Chart View | - | ✓ | - | - |
| Laporan Produksi | ✓ | ✓ | - | - |
| Laporan Rijek | ✓ | ✓ | - | - |
| Export Laporan | ✓ | ✓ | - | - |
| Dashboard Stats | ✓ | ✓ | - | - |
| Inventory View | - | ✓ | - | - |

### 2.2.6 Admin Keuangan

| Fitur | Create | Read | Update | Delete |
|-------|--------|------|--------|--------|
| Invoice Validation & Publishing | ✓ | ✓ | ✓ | - |
| Refund Validation & Publishing | ✓ | ✓ | ✓ | - |
| Master Kategori Pemasukan | ✓ | ✓ | ✓ | ✓ |
| Master Kategori Pengeluaran | ✓ | ✓ | ✓ | ✓ |
| Input Pemasukan Manual | ✓ | ✓ | ✓ | ✓ |
| Input Pengeluaran Manual | ✓ | ✓ | ✓ | ✓ |
| Laporan Keuangan Lengkap | ✓ | ✓ | - | - |
| Laporan Refund | ✓ | ✓ | - | - |
| Laporan Laba Rugi | ✓ | ✓ | - | - |
| Export Laporan Keuangan | ✓ | ✓ | - | - |
| Cash Flow Management | ✓ | ✓ | ✓ | - |
| Bank Data Management | ✓ | ✓ | ✓ | - |

---

# 3. Fitur Utama Sistem

## 3.1 Autentikasi & Authorization

### 3.1.1 Login System (Laravel Built-in)

- **Method**: Email + Password
- **Session**: Laravel Session (HttpOnly, Secure, SameSite)
- **Timeout**: 24 jam (configurable)
- **Max Login Attempt**: 5x salah password, then blokir 15 menit
- **Two-Factor Authentication**: Opsional (Google Authenticator)
- **Password**: bcrypt dengan cost 12
- **Rate Limiting**: Built-in Laravel throttle

### 3.1.2 Brand Switching

- Fasilitas switch antar brand untuk user dengan multiple brand access
- Switch via dropdown di header/sidebar
- Session per brand terpisah
- Last active brand tersimpan di session
- Brand context tersimpan di session (bukan JWT)

### 3.1.3 Lupa Password

- Input email registrasi
- Kirim link reset ke email (expire 1 jam)
- Template email forgot password dalam bahasa Indonesia
- Token random 64 karakter
- Setelah reset, password lama invalid
- Notifikasi ke email得知 password changed

### 3.1.4 RBAC (Role-Based Access Control)

Struktur permissions:

```
- Permission: read, create, update, delete, export, view
- Role: kumpulan permissions
- User: ditugaskan ke role(s)
- Middleware: role-based checking
```

## 3.2 Halaman Publik

### 3.2.1 Tracking Order

- **URL**: `/track/{po_number}`
- **Input**: Nomor PO
- **Output**:
  - Nama pemesan
  - Detail order (produk, jumlah, ukuran)
  - Progress terkini dengan visual timeline
  - Estimasi selesai
  - Status terkini
- **Bypass**: Tidak butuh login
- **Keamanan**:
  - Akses cukup menggunakan Nomor PO
  - Rate limit wajib diterapkan untuk mencegah enumerasi PO
  - Data sensitif seperti nomor HP, email, alamat lengkap, dan nominal internal dimasking atau tidak ditampilkan
  - Akses tracking dicatat ringan untuk audit dan troubleshooting

---

# 4. Dashboard & Widget ( Lengkap )

## Catatan Penting: Dashboard & Analisa Data

**Tujuan Dashboard:** Mengetahui JUMLAH master data yang di-order untuk analisa keseluruhan

**Fokus Analytics:**

- Berapa banyak produk yang di-order (identifikasi produk terlaris)
- Berapa banyak pelanggan yang order (pelanggan aktif)
- Berapa banyak per wilayah (provinsi/kabupaten)
- Berapa banyak yang menggunakan promo
- Berapa banyak per kategori order
- Berapa banyak dari sumber order tertentu

**Dashboard bukan hanya revenue, tapi ANALISA DATA MASTER secara menyeluruh**

### ⚠️ PRINSIP DASHBOARD DETAILING (Berlaku Semua Dashboard)

**Dashboard BUKAN hanya tentang angka.** Setiap widget angka/statistik **WAJIB** dilengkapi:

1. **List 10 item terbaru** — Menampilkan 10 data terbaru/teratas yang relevan dengan widget
2. **Tombol "Lihat Selengkapnya"** — Mengarah ke **laporan spesifik** di divisi masing-masing
3. **Filter selaras** — Data yang tampil di dashboard harus selaras dengan filter di halaman laporan tujuan

**Contoh Implementasi:**

| Widget Dashboard | List 10 | Link "Lihat Selengkapnya" |
|-----------------|---------|---------------------------|
| Total Order Bulan Ini: 150 | 10 order terbaru (nama PO, pelanggan, status) | → `/laporan/penjualan?periode=bulanan` |
| PO Terlambat: 5 | 5 PO terlambat (nama PO, deadline, hari lambat) | → `/laporan/monitoring-deadline?status=terlambat` |
| Produk Terpopuler | Top 10 produk | → `/laporan/produk?sort=terlaris` |
| PO Siap Dikirim: 12 | 10 PO siap kirim terbaru | → `/laporan/status-po?status=siap_dikirim` |
| PO Lunas: 80 | 10 PO lunas terbaru | → `/laporan/finance?status=lunas` |

**Aturan:** Setiap widget di semua dashboard (Admin Brand, Admin Produksi, Superadmin, Finance) HARUS mengikuti prinsip ini.

## 4.1 Dashboard Admin Brand - Widget Komprehensif

### 4.1.1 Summary Cards (Top Row) - Fokus Master Data

| Widget | Tipe | Keterangan |
|--------|------|-------------|
| Total Order Hari Ini | Number Card | Jumlah order masuk hari ini |
| Total Order Minggu Ini | Number Card | Jumlah order minggu ini |
| Total Order Bulan Ini | Number Card | Jumlah order bulan ini |
| Total Produk Di-Order | Number Card | Jumlah produk berbeda yang di-order |
| Total Pelanggan Order | Number Card | Jumlah pelanggan yang order |
| Total Wilayah Tercakup | Number Card | Jumlah wilayah terdata |

### 4.1.2 Analisa Master Data (ApexCharts)

| Widget | Tipe | Keterangan |
|--------|------|-------------|
| Produk Terpopuler | Horizontal Bar | Top 10 produk di-order |
| Pelanggan Aktif | Bar Chart | Pelanggan berdasarkan jumlah order |
| Order per Wilayah | Donut/Map | Distribusi by provinsi/kabupaten |
| Promo Usage | Bar Chart | Promo yang paling banyak digunakan |
| Kategori Favorit | Donut Chart | Kategori order paling banyak |
| Sumber Order | Bar Chart | Dari mana order masuk |
| Trend Harian | Line Chart | Tren order per hari |

### 4.1.3 Diamond & Gold (Best/Worst)

| Widget | Tipe | Keterangan |
|--------|------|-------------|
| 🏆 Diamond Produk | List | Produk dengan penjualan tertinggi |
| 🌟 Gold Pelanggan | List | Top 5 pelanggan berdasarkan total order |
| 💎 Diamond Promo | List | Promo dengan conversion tertinggi |
| 📊 Kategori Favorit | List | Kategori order paling banyak |
| ❄️ Worst Performer | List | Produk dengan reject rate tinggi |

### 4.1.4 Recent Activities

| Widget | Tipe | Keterangan |
|--------|------|-------------|
| Order Terbaru | Table | 10 order terbaru |
| Riwayat Login User | Table | 10 login terakhir |
| Notifikasi Terbaru | List | Notifikasi 7 hari terakhir |

### 4.1.5 Quick Actions

- Input Order Baru
- Tambah Pelanggan
- Buat Laporan
- Kirim WhatsApp

### 4.1.6 Status PO (Extended - Selaras dengan Pengaturan Indikator Selesai)

| Status | Warna | Keterangan | List 10 + Link |
|--------|-------|-------------|----------------|
| PO Masuk | #3B82F6 (Blue) | Order baru masuk, belum diproses | → `/laporan/status-po?status=masuk` |
| On Progress | #F59E0B (Amber) | Order sedang dalam proses produksi | → `/laporan/status-po?status=on_progress` |
| Selesai Produksi | #22C55E (Green) | Order telah selesai diproduksi | → `/laporan/status-po?status=selesai` |
| Siap Dikirim | #06B6D4 (Cyan) | Sudah packing + syarat terpenuhi | → `/laporan/status-po?status=siap_dikirim` |
| Sudah Dikirim | #8B5CF6 (Purple) | Barang sudah dikirim ke customer | → `/laporan/status-po?status=sudah_dikirim` |
| Delay | #EF4444 (Red) | Order melewati estimasi selesai | → `/laporan/status-po?status=delay` |
| Hold | #F97316 (Orange) | Ditahan (belum lunas/rijek aktif) | → `/laporan/status-po?status=hold` |

### 4.1.7 Distribusi Kerja Harian

| Widget | Tipe | Keterangan |
|--------|------|-------------|
| Jumlah PO Perhari | Bar Chart | Jumlah PO masuk per hari |
| Beban Kerja Per-tanggal | Area Chart | Distribusi workload berdasarkan tanggal |
| Trend PO Mingguan | Line Chart | Tren PO per minggu |

### 4.1.8 Monitoring Deadline

| Widget | Tipe | Keterangan |
|--------|------|-------------|
| PO Mendekati Deadline | List | PO dengan deadline < 3 hari |
| PO Terlambat | List | PO yang sudah melewati deadline |
| PO Express | List | PO dengan status prioritas tinggi |

### 4.1.9 Breakdown Produksi

| Widget | Tipe | Keterangan |
|--------|------|-------------|
| Qty Hari Ini | Number Card | Total produksi hari ini |
| Qty Minggu Ini | Number Card | Total produksi minggu ini |
| Qty Bulan Ini | Number Card | Total produksi bulan ini |
| Qty Jenis Produk | Donut Chart | Distribusi berdasarkan jenis produk |
| Qty Detail Produk | Table | Detail produk yang diproses |

### 4.1.10 Analisis Bisnis

| Widget | Tipe | Keterangan |
|--------|------|-------------|
| Produk Paling Sering Dipesan | Horizontal Bar | Top produk berdasarkan order |
| Detail Produk Sering Dipakai | Table | Detail attributes yang sering digunakan |

## 4.2 Dashboard Admin Produksi - Widget Komprehensif

### 4.2.1 Summary Cards

| Widget | Tipe | Keterangan |
|--------|------|-------------|
| Order Dalam Proses | Number |order belum selesai |
| Order Selesai Hari Ini | Number | order selesai today |
| Deadline Mendekat (7hr) | Number | order with deadline < 7 days |
| Rijek Rate (%) | Percentage | rejected/total ratio |
| Average Processing Time | Time | rata-rata waktu produksi |
| On-Time Delivery Rate | Percentage | tepat waktu delivery |

### 4.2.2 Kanban Board

| Kolom | Warna (ApexChart) | Keterangan |
|------|------------------|-------------|
| Baru Masuk | #3B82F6 (Blue) | Order baru masuk |
| Sedang Produksi | #F59E0B (Amber) | Produksi berjalan |
| Quality Control | #10B981 (Green) | QC sedang berlangsung |
| Selesai | #22C55E (Green) | Siap kirim |

#### **Kanban Card Indicators (Visual Alerts)**

Setiap kartu PO di Kanban Board **WAJIB** memiliki indikator visual berikut:

| Indikator | Visual | Keterangan |
|-----------|--------|-------------|
| **Terlambat** | 🔴 Red Glow / Badge `Terlambat` | Sudah melewati deadline customer |
| **Mendekati Deadline** | 🟡 Yellow Border / Badge `Urgent` | Deadline < 48 jam |
| **Express/Prioritas** | ⭐ Star Icon / Badge `Express` | PO dengan kategori express/prioritas |
| **Hold / Blocked** | ⏸️ Grey Overlay / Badge `Hold` | PO ditahan (masalah pembayaran/rijek berat) |
| **Has Reject** | ❌ Red X Icon | Ada rijek aktif di salah satu tahapan |
| **Locked** | 🔒 Lock Icon | PO sudah masuk pengerjaan (tidak bisa edit detail) |

**Interaksi:**

- **Hover**: Menampilkan summary kendala terakhir jika ada.
- **Click**: Membuka **Preview PO** (Section 5.15).
- **Drag & Drop**: Mengubah status progress (update `order_progress_details`).

### 4.2.3 Gantt/Timeline Chart (ApexCharts)

- Visualisasi timeline per order
- Start date vs estimated finish
- Progress percentage
- Dependency view
- Critical path highlight
- Drag & drop reschedule

### 4.2.4 Production Analytics

| Widget | Tipe | Keterangan |
|--------|------|-------------|
| Produksi per Operator | Bar Chart | Performance by user |
| Rijek by Jenis | Pie Chart | Breakdown reject types |
| Lead Time Analysis | Line Chart | Waktu rata-rata per stage |
| Capacity Utilization | Area Chart | Penggunaan kapasitas |
| Deadline Compliance | Gauge Chart | Kepatuhan deadline |

### 4.2.5 Diamond & Gold Produksi

| Widget | Keterangan |
|--------|-------------|
| 🔥 Best Operator | Operator dengan output tertinggi |
| ⚡ Fastest Turnaround | Produksi tercepat |
| 🎯 Highest Quality | Operator dengan reject terendah |
| ❄️ Bottleneck Stage | stage dengan delay tertinggi |

### 4.2.6 Status PO (Extended - Selaras dengan Pengaturan Indikator Selesai)

| Status | Warna | Keterangan | List 10 + Link |
|--------|-------|-------------|----------------|
| PO Masuk | #3B82F6 (Blue) | Order baru masuk, belum diproses | → `/laporan/status-po?status=masuk` |
| On Progress | #F59E0B (Amber) | Order sedang dalam proses produksi | → `/laporan/status-po?status=on_progress` |
| Selesai Produksi | #22C55E (Green) | Order telah selesai diproduksi | → `/laporan/status-po?status=selesai` |
| Siap Dikirim | #06B6D4 (Cyan) | Sudah packing + syarat terpenuhi | → `/laporan/status-po?status=siap_dikirim` |
| Sudah Dikirim | #8B5CF6 (Purple) | Barang sudah dikirim ke customer | → `/laporan/status-po?status=sudah_dikirim` |
| Delay | #EF4444 (Red) | Order melewati estimasi selesai | → `/laporan/status-po?status=delay` |
| Hold | #F97316 (Orange) | Ditahan (belum lunas/rijek aktif) | → `/laporan/status-po?status=hold` |

### 4.2.7 Distribusi Kerja Harian

| Widget | Tipe | Keterangan |
|--------|------|-------------|
| Jumlah PO Perhari | Bar Chart | Jumlah PO masuk per hari |
| Beban Kerja Per-tanggal | Area Chart | Distribusi workload berdasarkan tanggal |
| Trend PO Mingguan | Line Chart | Tren PO per minggu |
| Peak Hours | Heatmap | Jam tersibuk dalam sehari |

### 4.2.8 Monitoring Deadline

| Widget | Tipe | Keterangan |
|--------|------|-------------|
| PO Mendekati Deadline | List | PO dengan deadline < 3 hari |
| PO Terlambat | List | PO yang sudah melewati deadline |
| PO Express | List | PO dengan status prioritas tinggi |
| Warning Alert | Gauge | Tingkat kepatuhan deadline |

### 4.2.9 Breakdown Produksi

| Widget | Tipe | Keterangan |
|--------|------|-------------|
| Qty Hari Ini | Number Card | Total produksi hari ini |
| Qty Minggu Ini | Number Card | Total produksi minggu ini |
| Qty Bulan Ini | Number Card | Total produksi bulan ini |
| Qty Jenis Produk | Donut Chart | Distribusi berdasarkan jenis produk |
| Qty Tiap Brand | Bar Chart | Produksi per brand |
| Qty Tiap Detail Produk | Table | Detail produk yang diproses |

### 4.2.10 Analisis Bisnis

| Widget | Tipe | Keterangan |
|--------|------|-------------|
| Brand Paling Banyak Order | List | Brand dengan jumlah order tertinggi |
| Brand Paling Besar Volume | List | Brand dengan total quantity tertinggi |
| Produk Paling Sering Dipesan | Horizontal Bar | Top produk berdasarkan order |
| Detail Produk Sering Dipakai | Table | Detail attributes yang sering digunakan |

## 4.3 Dashboard Superadmin - Widget Komprehensif

### 4.3.1 Summary Cards

| Widget | Tipe | Keterangan |
|--------|------|-------------|
| Total Brand Aktif | Number | Semua brand |
| Total User Aktif | Number | User aktif |
| Total Order (All) | Number | Semua brand |
| Total Pendapatan | Currency | All brands combined |
| Brand Growth | Percentage | Growth per brand |

### 4.3.2 Comparison Charts (ApexCharts)

| Widget | Tipe | Keterangan |
|--------|------|-------------|
| Brand Performance | Multi Bar | Side-by-side comparison |
| Revenue by Brand | Donut Chart | Distribution |
| Order Volume Trend | Line Chart | Per brand over time |
| User Activity | Heatmap | Active users per brand |
| Performance Matrix | Scatter Plot | Brand comparison matrix |

### 4.3.3 Global Analytics

| Widget | Tipe | Keterangan |
|--------|------|-------------|
| Cross-Brand Best Sellers | horizontal Bar | Best seller across brands |
| Customer Overlap | Venn Diagram | Pelanggan sama antar brand |
| Resource Usage | Stacked Bar | CPU, DB, Storage |

### 4.3.4 Ranking Brands

| Rank | Widget | Keterangan |
|------|--------|-------------|
| 1 | 🏆 Top Brand | Brand dengan pendapatan tertinggi |
| 2 | 📈 Fastest Growing | Brand dengan growth highest |
| 3 | 👥 Most Users | Brand dengan user paling banyak |
| 4 | 💰 Highest ARPU | Brand dengan average revenue highest |

### 4.3.5 Status PO (Semua Brand)

| Status | Warna | Keterangan |
|--------|-------|-------------|
| PO Masuk | #3B82F6 (Blue) | Total PO masuk semua brand |
| On Progress | #F59E0B (Amber) | PO sedang diproses |
| Selesai Produksi | #22C55E (Green) | PO selesai produksi |
| Siap Dikirim | #06B6D4 (Cyan) | PO siap dikirim |
| Sudah Dikirim | #8B5CF6 (Purple) | PO sudah dikirim |
| Delay | #EF4444 (Red) | PO terlambat semua brand |
| Hold | #F97316 (Orange) | PO ditahan (pembayaran/rijek) |

### 4.3.6 Distribusi Kerja Harian (Global)

| Widget | Tipe | Keterangan |
|--------|------|-------------|
| Jumlah PO Perhari (All) | Bar Chart | PO masuk per hari semua brand |
| Beban Kerja Per-tanggal | Area Chart | Workload distribution |
| Trend PO Mingguan | Line Chart | Tren mingguan semua brand |

### 4.3.7 Monitoring Deadline (Global)

| Widget | Tipe | Keterangan |
|--------|------|-------------|
| PO Mendekati Deadline | List | Semua PO deadline < 3 hari |
| PO Terlambat | List | Semua PO terlambat |
| PO Express (All Brand) | List | PO prioritas semua brand |

### 4.3.8 Breakdown Produksi (Global)

| Widget | Tipe | Keterangan |
|--------|------|-------------|
| Qty Hari Ini (All) | Number Card | Total produksi hari ini |
| Qty Minggu Ini (All) | Number Card | Total produksi minggu ini |
| Qty Bulan Ini (All) | Number Card | Total produksi bulan ini |
| Qty Jenis Produk (All) | Donut Chart | Semua brand |
| Qty Tiap Brand | Multi Bar Chart | Perbandingan brand |
| Qty Detail Produk | Table | Semua brand |

### 4.3.9 Analisis Bisnis (Global)

| Widget | Tipe | Keterangan |
|--------|------|-------------|
| Brand Paling Banyak Order | List | Cross-brand analysis |
| Brand Paling Besar Volume | List | Berdasarkan quantity |
| Produk Paling Sering Dipesan | Horizontal Bar | Semua brand |
| Detail Produk Sering Dipakai | Table | Attribut populer |

## 4.4 Dashboard Owner (Multi-Brand & Consolidated)

### 4.4.1 Filosofi Dashboard Owner

Berbeda dengan Admin Brand yang fokus pada operasional satu brand, Dashboard Owner dirancang untuk **pengambilan keputusan strategis** lintas brand dan reseller yang mereka miliki.

### 4.4.2 Global Filter Control (Drill-down Capability)

Setiap widget di dashboard ini wajib terikat pada filter global yang terletak di bagian atas halaman:

| Filter | Opsi | Deskripsi |
|--------|------|-------------|
| **Brand Selector** | Semua Brand / [Nama Brand A] / [Nama Brand B] | Menampilkan data agregat atau spesifik per brand milik owner |
| **Reseller Selector** | Semua Reseller / [Nama Reseller] | Menampilkan kontribusi spesifik reseller (pilihan menyesuaikan brand yang dipilih) |
| **Periode** | Hari ini / Minggu ini / Bulan ini / Custom | Rentang waktu data |

### 4.4.3 Summary Cards (Aggregated View)

Widget yang menampilkan totalitas performa berdasarkan filter yang dipilih:

- **Total Revenue**: Gabungan pendapatan dari brand/reseller terpilih.
- **Total Volume PO**: Jumlah PO masuk.
- **Outstanding Payment**: Total piutang yang belum tertagih (lintas brand).
- **Reject Rate Average**: Rata-rata kualitas produksi.

### 4.4.4 Strategic Charts (ApexCharts)

- **Brand Performance Comparison**: Bar chart perbandingan Sales/Volume antar brand (hanya muncul jika pilih "Semua Brand").
- **Reseller Contribution**: Donut chart kontribusi masing-masing reseller terhadap total order.
- **Top Products per Brand**: Horizontal bar produk terlaris.
- **Monthly Revenue Growth**: Line chart tren pertumbuhan pendapatan gabungan.

### 4.4.5 Financial Health Monitoring

- **Cash Flow Overview**: Ringkasan uang masuk vs piutang.
- **Payment Aging Global**: Daftar invoice yang mendekati atau melewati jatuh tempo dari semua brand.

---

# 5. Master Data Global (PO Management)

## 5.1 Master Jenis Bahan Kain

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| nama | VARCHAR(100) | ✓ | Nama bahan kain |
| deskripsi | TEXT | - | Deskripsi bahan |
| is_active | BOOLEAN | ✓ | Default: true |

## 5.2 Master Kategori Order

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| nama | VARCHAR(100) | ✓ | Kategori order |
| deskripsi | TEXT | - | Deskripsi kategori |
| is_active | BOOLEAN | ✓ | Default: true |

## 5.3 Master Size

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| kategori_size | VARCHAR(50) | ✓ | Kategori ukuran (anak, perempuan, laki-laki) |
| ukuran | VARCHAR(20) | ✓ | Ukuran spesifik |
| urutan | INT | - | Urutan tampilan |
| is_active | BOOLEAN | ✓ | Default: true |

## 5.4 Master Logo

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| jenis_logo | VARCHAR(100) | ✓ | Jenis logo |
| deskripsi | TEXT | - | Deskripsi logo |
| is_active | BOOLEAN | ✓ | Default: true |

## 5.6 Master Resleting

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| jenis_resleting | VARCHAR(100) | ✓ | Jenis resleting |
| deskripsi | TEXT | - | Deskripsi resleting |
| is_active | BOOLEAN | ✓ | Default: true |

## 5.7 Master Jenis Printing

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| jenis_printing | VARCHAR(100) | ✓ | Jenis printing |
| deskripsi | TEXT | - | Deskripsi printing |
| is_active | BOOLEAN | ✓ | Default: true |

## 5.8 Master Data Pola Jahitan

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| jenis_pola | VARCHAR(100) | ✓ | Jenis pola |
| nama | VARCHAR(100) | ✓ | Nama pola jahitan |
| is_active | BOOLEAN | ✓ | Default: true |

**Catatan:** Data ini sinkron secara dinamis dengan **Seam Specifications** di Modul Order Management.

## 5.9 Master Paket Order

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| paket_order | VARCHAR(100) | ✓ | Paket order |
| deskripsi | TEXT | - | Deskripsi paket |
| is_active | BOOLEAN | ✓ | Default: true |

## 5.10 Master Tipe Order

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| tipe_order | VARCHAR(100) | ✓ | Tipe order |
| deskripsi | TEXT | - | Deskripsi tipe |
| is_active | BOOLEAN | ✓ | Default: true |

## 5.11 Master Data Bank

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| bank | VARCHAR(100) | ✓ | Nama bank |
| atas_nama | VARCHAR(255) | ✓ | Atas nama rekening |
| nomor_rekening | VARCHAR(50) | ✓ | Nomor rekening |
| is_active | BOOLEAN | ✓ | Default: true |

## 5.12 Master Progress

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| nama_progress | VARCHAR(100) | ✓ | Nama progress (Default list di bawah) |
| warna | VARCHAR(20) | - | Hex color untuk UI |
| urutan | INT | ✓ | Urutan progress (1 - 12) |
| is_skippable | BOOLEAN | ✓ | Default: true |
| is_active | BOOLEAN | ✓ | Default: true |

**Daftar Default Progress (Urutan Produksi):**

1. **SETTING** (Desain ke pola/siap cetak)
2. **PRINTING**
3. **POTONG SUBLIME**
4. **PRESS SUBLIME**
5. **POTONG BAHAN**
6. **JAHIT**
7. **QC JAHIT & BUANG BENANG**
8. **TALI CELANA**
9. **PRINT PRESS POLYFLEX**
10. **STEAM**
11. **PACKING** (Indikator Siap Kirim)
12. **SENDING** (Indikator Sudah Dikirim)

**Catatan:** Field `is_completed` dihapus dari master. Penentuan "progress mana yang dianggap selesai" diatur di **Pengaturan Superadmin** (lihat Section 12.6).

## 5.13 Order Progress Details (Per PO Per Tahapan)

Tabel ini menyimpan status, catatan, dan kendala di **setiap tahapan progress** untuk **setiap PO**. Ini adalah inti dari tracking produksi.

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| order_id | UUID | ✓ | FK ke orders |
| progress_id | UUID | ✓ | FK ke master progress |
| status | ENUM | ✓ | `pending`, `on_progress`, `selesai`, `skipped` |
| catatan | TEXT | - | Catatan umum untuk tahapan ini |
| kendala | TEXT | - | Kendala/masalah yang ditemukan di tahapan ini |
| has_reject | BOOLEAN | ✓ | Default: false. Apakah ada rijek di tahapan ini |
| started_at | TIMESTAMP | - | Kapan tahapan dimulai (on_progress) |
| completed_at | TIMESTAMP | - | Kapan tahapan selesai |
| skipped_reason | TEXT | - | Alasan jika tahapan di-skip |
| updated_by | UUID | ✓ | FK ke user (Admin Produksi yang update) |
| created_at | TIMESTAMP | ✓ | |
| updated_at | TIMESTAMP | ✓ | |

**Aturan Bisnis:**

- Setiap tahapan progress memiliki status independen: `pending` → `on_progress` → `selesai`
- Tahapan boleh **di-skip** (loncat) jika `is_skippable = true` di master, status menjadi `skipped` dengan wajib isi `skipped_reason`
- Setiap perubahan status **wajib** mengisi `catatan` untuk dokumentasi
- Field `kendala` diisi jika ada masalah/hambatan di tahapan tersebut
- Admin Produksi bisa **membaca semua kendala** di setiap progress via fitur Preview PO
- Jika `has_reject = true`, maka ada data rijek terkait di tabel `rijeks` yang ter-link ke tahapan ini
- **Otomatisasi Status PO (Sinkronisasi Section 12.6):**
  - Tahapan **PACKING** Selesai → Status PO otomatis berubah menjadi **Siap Dikirim** (jika syarat lain terpenuhi).
  - Tahapan **SENDING** Selesai → Status PO otomatis berubah menjadi **Sudah Dikirim**.

## 5.14 PO Lock & Change Record

Tabel untuk mencatat setiap kali PO di-unlock dan diubah setelah masuk pengerjaan.

### 5.14.1 PO Lock Status

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| order_id | UUID | ✓ | FK ke orders (unique) |
| is_locked | BOOLEAN | ✓ | Default: true. Auto-lock saat masuk pengerjaan |
| locked_at | TIMESTAMP | ✓ | Kapan PO di-lock |
| locked_by | UUID | ✓ | FK ke user yang memulai pengerjaan |

**Aturan Bisnis:**

- PO **otomatis ter-lock** ketika progress pertama diubah ke `on_progress` (masuk pengerjaan)
- Untuk mengubah detailing PO (deadline, komponen, dll) harus **unlock** terlebih dahulu
- Setelah selesai edit, PO otomatis **re-lock**

### 5.14.2 PO Change Log (Record Perubahan)

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| order_id | UUID | ✓ | FK ke orders |
| changed_by | UUID | ✓ | FK ke user (siapa yang mengubah) |
| change_reason | TEXT | ✓ | Alasan perubahan (wajib diisi) |
| field_changed | VARCHAR(100) | ✓ | Field yang diubah (contoh: deadline_customer, bahan_kain) |
| old_value | TEXT | - | Nilai sebelum perubahan |
| new_value | TEXT | - | Nilai setelah perubahan |
| approved_by | UUID | - | FK ke user yang menyetujui (jika perlu approval) |
| created_at | TIMESTAMP | ✓ | Waktu perubahan |

**Aturan Bisnis:**

- Setiap perubahan pada PO yang sudah locked **wajib** mencatat: siapa, apa yang diubah, alasan
- Record ini bisa dilihat di **Preview PO** dan di **Laporan Perubahan PO**
- Data ini selaras dengan audit trail dan laporan

## 5.15 PO Preview (Fitur Preview Detail PO)

Fitur preview menampilkan **semua informasi** PO dalam satu halaman komprehensif:

| Section | Konten | Keterangan |
|---------|--------|-------------|
| Info PO | Nama PO, pelanggan, deadline, status | Header informasi dasar |
| Status PO | Draft / Published / On Progress / dll | Badge warna sesuai status |
| Progress Timeline | Semua tahapan + status masing-masing | Visual timeline dengan warna status |
| Catatan per Progress | Catatan di setiap tahapan | Kronologis dari awal sampai akhir |
| Kendala per Progress | Daftar kendala di setiap tahapan | Highlight merah untuk kendala aktif |
| Rijek History | Semua rijek yang terjadi di PO ini | Link ke detail rijek per tahapan |
| **Refund History** | **Semua refund yang diajukan untuk PO ini** | **Nomor refund, alasan, nominal, status (pending/diterbitkan/ditolak), total refund** |
| Change Log | Record semua perubahan PO | Siapa, kapan, apa, alasan |
| Invoice Status | Status pembayaran | Lunas/Belum Lunas/Partial |
| **Repeat Order History** | **Daftar PO yang dibuat dari PO ini (jika ada)** | **Link ke PO turunan, atau link ke PO asal jika PO ini adalah repeat order** |

**Aturan Visual:** Layout Web Preview menggunakan **Dashboard-style (Cards & Modals)** yang interaktif, berbeda dengan layout PDF yang bersifat formal untuk cetak.

**Catatan Refund di Preview:** Section Refund History **WAJIB** menampilkan total nominal refund yang sudah diterbitkan sebagai informasi pengurangan nilai pada PO tersebut. Ini memudahkan pelaporan dan rekonsiliasi keuangan.

---

# 6. Master Data: Brand (Terisolasi) vs Reseller (Terisolasi Tersendiri)

## 6.0 Prinsip Data Isolation

### **Brand Accounts (Terisolasi per Brand)**

- Setiap brand memiliki master data sendiri (produk, kategori, sumber order, pelanggan)
- Data tidak bisa diakses brand lain
- Memungkinkan customisasi per brand
- PO dan transaksi terisolasi per brand

### **Reseller Accounts (Master Data Global, Transaksi Terisolasi)**

- Semua reseller menggunakan **master data global reseller** yang sama
- Master data global reseller meliputi produk, pelanggan, kategori order, sumber order, promo, dan type pelanggan
- Master data global reseller dikelola oleh role yang diberi permission master data reseller/global
- PO, laporan, refund, dashboard, dan aktivitas tetap terisolasi per reseller
- Reseller memiliki dashboard, fitur, dan output yang serupa dengan Admin Brand tetapi hanya pada scope transaksi reseller tersebut

### **Perbedaan Admin Brand vs Reseller**

| Aspek | Admin Brand | Reseller |
|-------|-------------|----------|
| Master Data | Terisolasi per brand | Global untuk semua reseller |
| PO Management | CRUD PO brand | CRUD PO reseller |
| Dashboard | Dashboard brand | Dashboard reseller (fitur & output serupa) |
| Refund | Bisa mengajukan refund | Bisa mengajukan refund |
| Repeat Order | ✓ | ✓ |
| Draft & Terbitkan | ✓ | ✓ |
| Laporan | Laporan brand | Laporan reseller |

---

## 6.1 Master Produk (Brand Isolated, Reseller Global)

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| brand_id | UUID | - | FK ke brand; NULL untuk master data global reseller |
| nama | VARCHAR(255) | ✓ | Nama produk |
| harga | DECIMAL(12,2) | ✓ | Harga dasar |
| deskripsi | TEXT | - | Deskripsi produk |
| gambar | VARCHAR(255) | - | Path gambar produk |
| is_active | BOOLEAN | ✓ | Default: true |
| is_featured | BOOLEAN | - | Featured product |
| created_at | TIMESTAMP | ✓ | |
| updated_at | TIMESTAMP | ✓ | |

**Catatan Data Isolation:**

- **Brand**: Produk terisolasi per brand, hanya bisa diakses brand tersebut
- **Reseller**: Menggunakan produk global, semua reseller dapat akses semua produk

## 6.2 Master Kategori Order (Brand Isolated, Reseller Global)

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| brand_id | UUID | - | FK ke brand; NULL untuk master data global reseller |
| nama | VARCHAR(100) | ✓ | Kategori order |
| deskripsi | TEXT | - | Deskripsi kategori |
| is_active | BOOLEAN | ✓ | Default: true |

**Catatan Data Isolation:**

- **Brand**: Kategori terisolasi per brand
- **Reseller**: Menggunakan kategori global

## 6.3 Master Sumber Order (Brand Isolated, Reseller Global)

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| brand_id | UUID | - | FK ke brand; NULL untuk master data global reseller |
| nama | VARCHAR(100) | ✓ | Sumber order |
| deskripsi | TEXT | - | Deskripsi sumber |
| is_active | BOOLEAN | ✓ | Default: true |

**Catatan Data Isolation:**

- **Brand**: Sumber order terisolasi per brand
- **Reseller**: Menggunakan sumber order global

## 6.4 Master Pelanggan (Brand Isolated, Reseller Global)

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| brand_id | UUID | - | FK ke brand; NULL untuk master data global reseller |
| kode | VARCHAR(50) | ✓ | Kode unik pelanggan |
| nama | VARCHAR(255) | ✓ | Nama pelanggan |
| nomor_hp | VARCHAR(20) | ✓ | No. HP |
| email | VARCHAR(255) | - | Email |
| type_pelanggan_id | UUID | - | FK ke type pelanggan |
| sumber_daftar | UUID | - | FK ke sumber order |
| provinsi | VARCHAR(100) | ✓ | Dari API Indonesia |
| kabupaten | VARCHAR(100) | ✓ | Dari API Indonesia |
| kecamatan | VARCHAR(100) | ✓ | Dari API Indonesia |
| desa | VARCHAR(100) | ✓ | Dari API Indonesia |
| detail_alamat | TEXT | - | Detail alamat lain |
| kodepos | VARCHAR(10) | - | Kode pos |
| notes | TEXT | - | Catatan internal |
| total_order | INT | - | Total histori order |
| total_transaksi | DECIMAL(15,2) | - | Total transaksi |
| is_active | BOOLEAN | ✓ | Default: true |
| created_at | TIMESTAMP | ✓ | |
| updated_at | TIMESTAMP | ✓ | |

**Catatan Data Isolation:**

- **Brand**: Pelanggan terisolasi per brand, hanya brand tersebut yang bisa akses
- **Reseller**: Menggunakan database pelanggan global, semua reseller dapat akses semua pelanggan

### 6.4.1 Type Pelanggan (Brand Isolated, Reseller Global)

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| brand_id | UUID | - | FK ke brand; NULL untuk master data global reseller |
| nama | VARCHAR(100) | ✓ | Nama jenis pelanggan |
| diskon_default | DECIMAL(5,2) | - | Diskon default untuk tipe ini |
| is_active | BOOLEAN | ✓ | Default: true |

**Catatan Data Isolation:**

- **Brand**: Type pelanggan terisolasi per brand
- **Reseller**: Menggunakan type pelanggan global

---

# 7. Update User Roles & Permissions

## 7.1 Daftar Role User (Updated)

| Role | Deskripsi | Akses Multi-Brand | Komentar |
|------|-----------|------------------|----------|
| **Superadmin** | Akses penuh ke semua brand + fitur comparison | Ya (Semua Brand) | Hanya untuk owner utama |
| **Owner** | Owner spesifik per brand yang ditugaskan | Tidak (1 Brand) | Bisa memiliki multiple brand assignment |
| **Admin Brand** | Mengelola master data & input order brand | Tidak (1 Brand) | Master data terisolasi per brand |
| **Reseller** | Mengelola order & pelanggan via master data reseller sendiri | Tidak (1 Brand) | Master data khusus reseller, terpisah dari Admin Brand |
| **Admin Produksi** | Mengelola progress & laporan produksi | Tidak (1 Brand) | Tidak bisa edit order produksi |
| **Admin Keuangan** | Mengelola seluruh keuangan perusahaan secara komprehensif | Tidak (1 Brand) | Validasi invoice, refund, pemasukan, pengeluaran & laporan keuangan |

## 7.2 Account Types

### 7.2.1 Brand Account

- Mengelola produk dan pelanggan sendiri
- Master data terisolasi per brand
- Dashboard khusus brand
- PO dengan fitur Draft & Terbitkan
- Fitur Repeat Order & Refund

### 7.2.2 Reseller Account

- **Master Data Global**: Reseller menggunakan master data global reseller (produk, pelanggan, kategori, sumber order, promo) yang dipakai bersama oleh semua reseller
- **Dashboard Context**: Menampilkan nama reseller aktif di header
- **PO Integration**: Setiap PO otomatis terhubung dengan reseller, dengan fitur Draft & Terbitkan
- **Repeat Order**: Fitur repeat order untuk mempermudah pemesanan ulang tanpa menulis produk lagi
- **Refund Management**: Fitur pengajuan refund untuk PO yang bermasalah
- **Reporting**: Laporan per reseller dengan data penjualan, pelanggan, dan refund

## 7.3 Rincian Permissions per Role (Updated)

### 7.3.1 Reseller

| Fitur | Create | Read | Update | Delete |
|-------|--------|------|--------|--------|
| Master Data Produk Global Reseller | - | ✓ | - | - |
| Master Data Pelanggan Global Reseller | - | ✓ | - | - |
| Master Data Promo Global Reseller | - | ✓ | - | - |
| Master Data Kategori Order Global Reseller | - | ✓ | - | - |
| Master Data Sumber Order Global Reseller | - | ✓ | - | - |
| Input Order (Draft & Terbitkan) | ✓ | ✓ | ✓ | ✓* |
| Repeat Order | ✓ | ✓ | - | - |
| Refund Management | ✓ | ✓ | ✓ | - |
| Laporan Penjualan (Reseller) | ✓ | ✓ | - | - |
| Laporan Pelanggan (Reseller) | ✓ | ✓ | - | - |
| Laporan Refund (Reseller) | - | ✓ | - | - |
| Export Laporan | ✓ | ✓ | - | - |
| View Invoice Draft | - | ✓ | - | - |

*Catatan: Setelah PO diterbitkan & masuk produksi, tidak bisa edit/hapus. Reseller menggunakan master data global reseller; transaksi dan laporan tetap terisolasi per reseller.

### 7.3.2 Admin Keuangan

| Fitur | Create | Read | Update | Delete |
|-------|--------|------|--------|--------|
| Invoice Validation & Publishing | ✓ | ✓ | ✓ | - |
| Refund Validation & Publishing | ✓ | ✓ | ✓ | - |
| Master Kategori Pemasukan | ✓ | ✓ | ✓ | ✓ |
| Master Kategori Pengeluaran | ✓ | ✓ | ✓ | ✓ |
| Input Pemasukan Manual | ✓ | ✓ | ✓ | ✓ |
| Input Pengeluaran Manual | ✓ | ✓ | ✓ | ✓ |
| Laporan Keuangan Lengkap | ✓ | ✓ | - | - |
| Laporan Laba Rugi | ✓ | ✓ | - | - |
| Laporan Refund | ✓ | ✓ | - | - |
| Laporan Pemasukan & Pengeluaran | ✓ | ✓ | - | - |
| Export Laporan Keuangan | ✓ | ✓ | - | - |
| Cash Flow Management | ✓ | ✓ | ✓ | - |
| Bank Data Management | ✓ | ✓ | ✓ | - |
| Payment Verification | ✓ | ✓ | ✓ | - |

**Catatan:** PO yang diterbitkan otomatis tercatat sebagai pemasukan. Refund yang diterbitkan otomatis tercatat sebagai pengurangan pemasukan.

---

# 8. Modul Order Management (4 Section)

## 8.0 Fitur Draft & Terbitkan PO

### 8.0.1 Konsep PO Lifecycle

Setiap PO yang dibuat oleh Admin Brand atau Reseller memiliki lifecycle Draft → Terbitkan:

```
Admin Brand/Reseller → Buat PO (Draft)
    ↓
PO disimpan sebagai DRAFT (bisa diedit, dihapus, dilengkapi)
    ↓
Admin Brand/Reseller → Klik "Terbitkan"
    ↓
Setelah DITERBITKAN (otomatis):
  ✅ PO masuk ke dashboard Admin Produksi (siap diproses)
  ✅ Status PO berubah dari 'draft' ke 'published'
  ✅ PO tidak bisa dihapus lagi (hanya bisa diubah via mekanisme unlock)
  ✅ Invoice TIDAK otomatis dibuat; invoice dibuat manual oleh Admin Keuangan jika ada DP atau PO ditandai pesanan khusus
```

### 8.0.2 Status PO

| Status | Keterangan |
|--------|-------------|
| `draft` | PO baru dibuat, belum diterbitkan. Bisa diedit/dihapus bebas. |
| `published` | PO sudah diterbitkan dan masuk ke Admin Produksi. Invoice dibuat manual jika ada DP atau pesanan khusus. |
| `on_progress` | PO sedang dalam proses produksi. |
| `selesai_produksi` | Produksi selesai. |
| `siap_dikirim` | Sudah packing + syarat terpenuhi. |
| `sudah_dikirim` | Barang sudah dikirim ke customer. |
| `delay` | Melewati deadline. |
| `hold` | Ditahan (belum lunas/rijek aktif). |

### 8.0.3 Aturan Bisnis Draft & Terbitkan

- **Draft**: PO bisa diedit, dihapus, dilengkapi data kapan saja
- **Terbitkan**: Tombol "Terbitkan" hanya aktif jika semua field wajib sudah terisi
- **Setelah Terbitkan**: PO tidak bisa dihapus, perubahan hanya via mekanisme unlock
- **Otomatis**: Saat terbitkan, PO muncul di dashboard Admin Produksi untuk mulai diproses
- **Tidak Otomatis**: Invoice tidak dibuat otomatis saat terbitkan
- **Conditional Invoice**: Admin Keuangan membuat invoice manual hanya jika DP sudah tercatat di PO payment section atau PO diberi flag `is_special_order`

## 8.0.4 Fitur Repeat Order

Fitur Repeat Order memungkinkan Admin Brand dan Reseller membuat PO baru berdasarkan PO yang sudah ada, tanpa perlu menulis ulang detail produk.

**Cara Kerja:**

1. Di halaman detail PO atau daftar PO, terdapat tombol **"Repeat Order"**
2. Klik tombol → sistem menyalin seluruh data PO (produk, ukuran, nameset, spesifikasi) ke PO baru
3. PO baru berstatus **Draft** — admin bisa mengedit sebelum menerbitkan
4. Field yang disalin: produk, varian, ukuran, nameset, spesifikasi jahitan, bahan, logo, dll
5. Field yang **TIDAK** disalin: tanggal masuk (otomatis hari ini), deadline, pelanggan (bisa diganti)
6. **Repeat Order Record**: Setiap PO yang dibuat dari repeat order tercatat dengan referensi `repeat_from_po_id`

**Struktur Data Repeat Order:**

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| repeat_from_po_id | UUID | - | FK ke PO asal jika dibuat dari repeat order |
| is_repeat_order | BOOLEAN | ✓ | Default: false. True jika PO ini hasil repeat order |

**Tampilan di List PO:**

- PO yang merupakan repeat order ditandai dengan badge **"Repeat"**
- Klik badge → menampilkan informasi PO asal
- Di detail PO asal, terdapat section **"Repeat Order History"** yang menampilkan semua PO yang dibuat dari PO tersebut

## 8.1 Section 1: Informasi Detail PO

### 8.1.1 Struktur Data

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| nama_po | VARCHAR(255) | ✓ | Nama Purchase Order |
| status_po | ENUM | ✓ | `draft`, `published`, `on_progress`, `selesai_produksi`, `siap_dikirim`, `sudah_dikirim`, `delay`, `hold` — Default: `draft` |
| is_special_order | BOOLEAN | ✓ | Default: false. Jika true, PO bisa dibuatkan invoice meskipun belum ada DP |
| tanggal_masuk | DATE | ✓ | Tanggal order masuk |
| deadline_customer | DATE | ✓ | Deadline dari customer |
| start_production_date | DATE | - | Tanggal mulai produksi (diisi Admin Produksi) |
| end_production_date | DATE | - | Estimasi tanggal selesai produksi |
| kategori_order_id | UUID | ✓ | FK ke master kategori order |
| sumber_order_id | UUID | ✓ | FK ke master sumber order |
| pelanggan_id | UUID | ✓ | FK ke master pelanggan |
| repeat_from_po_id | UUID | - | FK ke PO asal jika dibuat dari repeat order |
| is_repeat_order | BOOLEAN | ✓ | Default: false |
| published_at | TIMESTAMP | - | Tanggal/waktu PO diterbitkan |
| published_by | UUID | - | FK ke user yang menerbitkan PO |
| catatan | TEXT | - | Catatan tambahan |

**PO Payment Section (Sebelum Invoice):**

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| po_id | UUID | ✓ | FK ke PO |
| payment_type | ENUM | ✓ | `dp`, `pelunasan`, `lainnya` |
| amount | DECIMAL(12,2) | ✓ | Nominal pembayaran |
| payment_date | DATE | ✓ | Tanggal pembayaran |
| bank_id | UUID | - | FK ke master bank |
| proof_file | VARCHAR(255) | - | Bukti pembayaran |
| notes | TEXT | - | Catatan pembayaran |
| recorded_by | UUID | ✓ | User yang mencatat |
| verified_by | UUID | - | Admin Keuangan yang memverifikasi |
| verified_at | TIMESTAMP | - | Waktu verifikasi |

**Catatan CRUD PO:**

- Daftar PO (List View) **WAJIB** menampilkan: Nama PO, Pelanggan, **Status PO (Draft/Published/dll)**, **Detail Progress Terkini**, **Deadline Customer**, serta **Start/End Production Deadline**.
- PO Draft ditandai badge kuning "Draft", PO Published ditandai badge hijau "Published"
- PO yang sudah masuk tahap pengerjaan akan ter-lock otomatis.
- Tombol **"Repeat Order"** tersedia di setiap baris PO yang sudah pernah diterbitkan.
- Kolom filter tambahan: Status PO (Draft/Published/All)

## 8.2 Section 2: Detail PO (Dinamis dari Master)

### 8.2.1 Komponen Utama (Sesuai @po online/)

#### A. **Dynamic Product Modules**

- **Category-Based Activation**: Checkbox untuk memilih kategori produk (Jersey, Jaket, Celana, dll)
- **Modular UI**: Setiap kategori yang dipilih akan membuka modul terpisah
- **Collapsible Sections**: Header dengan chevron icon untuk expand/collapse
- **Color Coding**: Setiap varian produk menggunakan warna berbeda untuk visual distinction

#### B. **Product Variant Management**

- **Subcategory Creation**: Tombol "+" untuk menambah variasi pemain/sub-kategori
- **Dynamic Naming**: Input field untuk custom naming setiap variasi
- **Delete Functionality**: Tombol hapus untuk menghilangkan variasi yang tidak diperlukan
- **State Persistence**: Menyimpan status aktif/non-aktif setiap variasi

#### C. **Image Upload System**

- **Dual Image Types**: Desain utama dan gambar kerah terpisah
- **Cropper.js Integration**: Modal cropper dengan zoom, rotate, dan aspect ratio control
- **Base64 Storage**: Hasil crop disimpan sebagai base64 untuk PDF generation
- **Preview System**: Real-time preview gambar yang sudah di-crop
- **File Validation**: Cek ukuran file (max 5MB) dan format gambar

#### D. **Comprehensive Specification Forms**

- **Setelan Type**: Stell/Non-Stell dengan atasan/bawahan options
- **Material Selection**: Dropdown bahan kain dari master data
- **Color Management**: Input warna dengan bullet point display
- **Logo Integration**: Dropdown jenis logo dari master data
- **Seam Specifications**: Detail jahitan (sinkron dinamis dengan Master Pola Jahitan), lengan, bawah, pundak
- **Zipper Selection**: Jenis resleting dari master data
- **Size Compatibility**: Validasi ukuran berdasarkan kategori (anak, perempuan, laki-laki)

### 8.2.2 Fitur Utama (Mirroring @po online/)

#### A. **Advanced Size Management**

- **33 Size Options**: XS ANAK hingga 10XL serta **Ukuran Custom** dengan kategori terpisah
- **Size Sanitization**: Auto-correction paste input (contoh: "xsp" → "XS PEREMPUAN")
- **Size Validation**: Warning untuk ukuran tidak valid
- **Bulk Size Input**: Support paste dari Excel/spreadsheet
- **Size Summary**: Rekap otomatis jumlah per ukuran

#### B. **Nameset & Number Management**

- **Dynamic Row Addition**: Auto-add rows saat input
- **Name Validation**: Required field dengan trim whitespace
- **Number Formatting**: Input khusus untuk nomor punggung
- **Size Assignment**: Dropdown dengan 33+ opsi ukuran
- **Notes Field**: Kolom keterangan tambahan per entry
- **Duplicate Prevention**: Warning untuk nama/nomor duplikat

#### C. **PDF Generation Engine (SPK Format)**

- **Multi-Page Layout**: Otomatis page break antar produk
- **Header Branding**: Logo, tagline, nama brand di header
- **Footer Pagination**: Halaman counter dengan nama order
- **Visual Elements**: Full-page design images, collar images, tables
- **Specification Tables**: Detail spesifikasi dalam format tabel
- **Summary Calculations**: Total quantity, breakdown per size
- **A4 Portrait**: Optimized untuk printing dan digital view

#### D. **Data Processing & Validation**

- **Real-time Calculation**: Total quantity update otomatis
- **Form Validation**: Required fields dengan visual indicators
- **Data Sanitization**: Auto-uppercase, trim, format validation
- **State Management**: Preserve form state saat navigasi
- **Error Handling**: User-friendly error messages

### 8.2.3 PDF Output Structure (Identik @po online/)

#### A. **Page 1: Header & Summary**

```
FORMAT ORDER INDOWAREHOUSE
[Header Branding]

Info Table (2 kolom):
- Kiri: Tanggal Masuk, Dateline, Nama Order, Grand Total
- Kanan: Tipe Order, Jenis Order, Kategori Item, Paket Produksi, Nama Brand

Detailing Pelanggan (optional section)
```

#### B. **Page 2+: Product Specifications**

```
[Header: PRODUK: [NAMA PRODUK]]

Tabel Spesifikasi:
- Kolom: Jenis Pesanan | [Variant 1] | [Variant 2] | ...
- Baris: Jenis Setelan, Pola, Bahan, Jumlah Atasan/Bawahan, Warna, dll
- Sub-header: Keterangan Jahitan, Keterangan Resleting

[Page Break]

Referensi Desain (Full Page):
- Gambar desain utama (scaled to fit)
- Keterangan Atasan & Bawahan (2 kolom table)

Referensi Kerah (Bottom Section):
- Jenis Kerah (text)
- Gambar kerah (small, right side)

[Page Break]

Data Pesanan Table:
- No | Nama Punggung | No Punggung | Size | Keterangan

Rekap Size (Center):
- Tabel breakdown per ukuran
- Total keseluruhan
```

#### C. **Technical Specifications**

- **Paper Size**: A4 Portrait
- **Margins**: 60px all sides
- **Font**: Helvetica/Arial, 14px base
- **Image Quality**: 150 DPI, max 800x800px per image
- **Table Styling**: 1px borders, striped rows
- **Page Breaks**: Automatic per product section

### 8.2.4 Web View vs PDF Differences

#### A. **Web Interface Features (PO Preview & Input)**

- **Dashboard-Style Layout**: Tampilan menggunakan cards, tabs, dan accordion untuk navigasi informasi yang padat.
- **Interactive Forms**: Real-time validation dan calculation.
- **Image Preview**: Thumbnail preview sebelum crop dengan zoom functionality.
- **Collapsible Sections**: Expand/collapse untuk navigasi mudah di layar desktop/mobile.
- **Progress Indicators**: Visual progress bar dan timeline yang interaktif.
- **Responsive Design**: Layout yang menyesuaikan layar (bukan fix A4).

#### B. **PDF Output Optimizations (SPK Format)**

- **Formal SPK Layout**: Tampilan fix A4 Portrait, kaku, dan formal untuk kebutuhan arsip & produksi.
- **Print-Friendly**: Background putih, teks hitam tajam, no interactive elements.
- **Page Optimization**: Strategic page breaks (auto-split antar produk) untuk keterbacaan di kertas.
- **Fixed Table Formatting**: Kolom dengan lebar tetap (fixed-width) agar tidak terpotong saat diprint.
- **Summary Sections**: Penempatan rekap di posisi strategis untuk referensi cepat operator produksi.

**Penting:** Meskipun data yang ditampilkan 100% sama, layout Web Preview fokus pada **kecepatan monitoring & interaksi**, sedangkan layout PDF fokus pada **standarisasi cetak & panduan produksi**.

## 8.3 Section 3: Invoice Draft

### 8.3.1 Struktur Invoice

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| po_id | UUID | ✓ | FK ke PO |
| status | ENUM | ✓ | draft, validated, published |
| total_tagihan | DECIMAL(12,2) | ✓ | Total sebelum diskon |
| diskon_type | ENUM | - | persen, nominal |
| diskon_value | DECIMAL(12,2) | - | Nilai diskon |
| biaya_pengiriman | DECIMAL(12,2) | - | Biaya ongkir |
| jasa_pengiriman | VARCHAR(100) | - | Detail jasa (JNE, TIKI, dll) |
| bank_id | UUID | ✓ | FK ke master bank |
| dp_amount | DECIMAL(12,2) | - | Down payment |
| sisa_pembayaran | DECIMAL(12,2) | - | Sisa yang harus dibayar |
| created_by | UUID | ✓ | FK ke user |

### 8.3.2 Fitur Invoice (Draft → Validated → Published)

#### A. **Draft Creation (Manual & Conditional)**

- **Trigger**: Invoice dibuat manual oleh Admin Keuangan dari PO yang sudah `published`
- **Syarat Pembuatan Invoice**:
  - PO sudah memiliki DP yang tercatat di PO payment section, atau
  - PO diberi flag `is_special_order = true`
- **Data Source**: Mengambil dari PO details dan PO payment section
- **Calculation**:
  - Total tagihan dari semua produk
  - DP amount dari pembayaran DP yang sudah tercatat
  - Sisa pembayaran otomatis (total - DP - diskon)
  - Biaya pengiriman berdasarkan jasa yang dipilih
- **Discount System**: Persen (contoh: 10%) atau nominal (Rp 50,000)
- **Shipping Options**: Free/Paid dengan detail jasa pengiriman (JNE, TIKI, dll)
- **Bank Selection**: Dropdown dari master data bank

#### B. **Finance Validation Process**

- **Invoice Review Dashboard**: Table view semua invoice draft
- **Detail Validation**:
  - Cek kesesuaian PO details dengan produk yang dipesan
  - Verifikasi nominal tagihan vs harga produk
  - Validasi bank tujuan pembayaran
  - Cek diskon dan biaya pengiriman
- **Approval Workflow**: Approve/Reject dengan alasan jika ditolak
- **Payment Verification**: Cek nominal transfer sesuai rekening bank
- **Audit Trail**: Log semua validasi dan perubahan

#### C. **Publishing Process (After Approval)**

- **Status Update**: Draft → Published
- **WhatsApp Auto-Send**:
  - Template pesan customizable
  - Include invoice link dan QR code
  - Auto-send ke nomor pelanggan dari master data
- **Public Invoice URL**: `/invoice/{invoice_number}` (no login required)
- **QR Code Generation**: Link tracking ke halaman invoice

#### D. **Invoice Features**

- **Partial Payment Tracking**: History pembayaran bertahap
- **Payment Reminders**: Otomatis sebelum/sesetelah jatuh tempo
- **Aging Reports**: Invoice berdasarkan umur (overdue tracking)
- **PDF Export**: Clean, professional layout untuk printing
- **Multi-language Support**: Default FAQ dalam bahasa Indonesia

#### E. **Invoice Status Flow**

```
Draft → Finance Review → Approved/Rejected
    ↓ (if Approved)
Published → WhatsApp Sent → Payment Tracking → Completed/Overdue
    ↓ (if Rejected)
Return to Brand → Revision → Resubmit
```

### 8.3.3 Invoice PDF Template

#### A. **Header Section**

- Brand logo dan tagline
- Invoice number (custom format)
- Tanggal terbit dan jatuh tempo

#### B. **Customer Information**

- Nama, HP, email dari master pelanggan
- Alamat lengkap (provinsi sampai desa + kodepos)

#### C. **Order Details**

- No PO dan tanggal order
- Tabel produk dengan jumlah, harga, subtotal
- Total sebelum diskon
- Diskon applied
- Biaya pengiriman dengan detail jasa
- DP amount dan sisa pembayaran

#### D. **Payment Information**

- Bank details (nama bank, atas nama, no rekening)
- QR code untuk tracking
- Progress timeline order

#### E. **Terms & FAQ**

- Syarat & ketentuan (customizable per brand)
- FAQ default dalam bahasa Indonesia
- Contact information

#### F. **Footer**

- Brand contact details
- Social media links (jika ada)

## 8.4 Flow Order Management (Updated: Draft & Terbitkan)

```
Admin Brand/Reseller → Buat PO (Draft) → Simpan sebagai Draft
    ↓
PO tersimpan sebagai DRAFT (bisa diedit/dihapus kapan saja)
    ↓
Admin Brand/Reseller → Review & Lengkapi → Klik "Terbitkan"
    ↓
OTOMATIS setelah Terbitkan:
  ✅ PO masuk ke Dashboard Admin Produksi (siap diproses)
  ✅ PO dapat langsung dikerjakan oleh Admin Produksi
  ✅ Invoice belum dibuat otomatis
    ↓
Jika DP sudah tercatat atau PO adalah pesanan khusus:
Admin Keuangan → Buat Invoice dari PO → Verifikasi → Publish
    ↓
Jika Published → Invoice dikirim ke customer via WhatsApp/manual
    ↓
Jika tidak ada DP dan bukan pesanan khusus:
PO tetap berjalan di produksi tanpa invoice
```

## 8.5 Section 4: Refund Management

### 8.5.1 Deskripsi

Fitur Refund digunakan oleh Admin Brand dan Reseller untuk mencatat biaya khusus yang timbul akibat kesalahan produk pada PO tertentu. Refund ini bukan pengembalian dana langsung, melainkan **pencatatan pengurangan nilai** yang mempengaruhi laporan keuangan.

### 8.5.2 Alur Refund

```
Admin Brand/Reseller → Tambah Data Refund
    ↓
Memilih PO yang bermasalah (dari daftar PO yang sudah diterbitkan)
    ↓
Mengisi detail refund:
  - Alasan refund (produk cacat, ukuran salah, warna tidak sesuai, dll)
  - Jumlah item yang bermasalah
  - Nominal refund
  - Bukti foto/dokumen (opsional)
    ↓
Simpan → Data Refund masuk ke sistem Admin Keuangan
    ↓
Admin Keuangan → Review & Edit detail refund
    ↓
Admin Keuangan → Pilih: Terbitkan / Tolak
    ↓
Jika DITERBITKAN:
  ✅ Refund tercatat di laporan refund tersendiri
  ✅ Nominal refund otomatis menjadi pengurangan pemasukan
  ✅ Data muncul di detail PO terkait (section Refund History)
  ✅ Laporan keuangan terupdate otomatis
    ↓
Jika DITOLAK:
  ❌ Return ke Admin Brand/Reseller dengan alasan penolakan
  ❌ Admin Brand/Reseller bisa revisi & submit ulang
```

### 8.5.3 Struktur Data Refund

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| brand_id | UUID | ✓ | FK ke brand |
| order_id | UUID | ✓ | FK ke PO yang bermasalah |
| refund_number | VARCHAR(50) | ✓ | Nomor refund unik (auto-generated) |
| alasan | TEXT | ✓ | Alasan/deskripsi refund |
| jenis_masalah | ENUM | ✓ | `produk_cacat`, `ukuran_salah`, `warna_tidak_sesuai`, `bahan_salah`, `printing_error`, `jahitan_rusak`, `lainnya` |
| jumlah_item | INT | ✓ | Jumlah item yang bermasalah |
| nominal_refund | DECIMAL(12,2) | ✓ | Nominal nilai refund |
| bukti | JSON | - | Array path foto/dokumen bukti |
| catatan | TEXT | - | Catatan tambahan |

**PO Payment Section (Sebelum Invoice):**

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| po_id | UUID | ✓ | FK ke PO |
| payment_type | ENUM | ✓ | `dp`, `pelunasan`, `lainnya` |
| amount | DECIMAL(12,2) | ✓ | Nominal pembayaran |
| payment_date | DATE | ✓ | Tanggal pembayaran |
| bank_id | UUID | - | FK ke master bank |
| proof_file | VARCHAR(255) | - | Bukti pembayaran |
| notes | TEXT | - | Catatan pembayaran |
| recorded_by | UUID | ✓ | User yang mencatat |
| verified_by | UUID | - | Admin Keuangan yang memverifikasi |
| verified_at | TIMESTAMP | - | Waktu verifikasi |
| status | ENUM | ✓ | `draft`, `pending_review`, `approved`, `published`, `rejected` — Default: `pending_review` |
| rejected_reason | TEXT | - | Alasan penolakan (jika ditolak) |
| reviewed_by | UUID | - | FK ke user Admin Keuangan yang mereview |
| reviewed_at | TIMESTAMP | - | Tanggal/waktu review |
| published_by | UUID | - | FK ke user Admin Keuangan yang menerbitkan |
| published_at | TIMESTAMP | - | Tanggal/waktu diterbitkan |
| created_by | UUID | ✓ | FK ke user yang membuat (Admin Brand/Reseller) |
| created_at | TIMESTAMP | ✓ | |
| updated_at | TIMESTAMP | ✓ | |

### 8.5.4 Aturan Bisnis Refund

- **Siapa yang bisa membuat refund**: Admin Brand dan Reseller
- **PO yang bisa di-refund**: Hanya PO dengan status `published` atau setelahnya (bukan draft)
- **Multiple refund**: Satu PO bisa memiliki lebih dari satu refund
- **Nominal**: Tidak boleh melebihi total tagihan PO
- **Verifikasi**: Admin Keuangan wajib memverifikasi sebelum refund diterbitkan
- **Penerbitan**: Setelah diterbitkan, refund tidak bisa dihapus (hanya bisa dibuat refund baru sebagai koreksi)

### 8.5.5 Refund di Detail PO (Preview)

Setiap PO yang memiliki refund **WAJIB** menampilkan section **Refund History** di halaman Preview PO:

```
┌─────────────────────────────────────────────┐
│ 🔄 REFUND HISTORY                            │
├─────────────────────────────────────────────┤
│ 1. REF-SHU-20260420-001                     │
│    Alasan: Produk cacat - jahitan rusak     │
│    Jumlah: 5 pcs                            │
│    Nominal: Rp 250.000                      │
│    Status: ✅ Diterbitkan (20 Apr 2026)     │
│    Diverifikasi oleh: Admin Keuangan A      │
├─────────────────────────────────────────────┤
│ 2. REF-SHU-20260425-002                     │
│    Alasan: Warna tidak sesuai               │
│    Jumlah: 3 pcs                            │
│    Nominal: Rp 150.000                      │
│    Status: ⏳ Pending Review                │
├─────────────────────────────────────────────┤
│ 📊 TOTAL REFUND: Rp 400.000                 │
│    Diterbitkan: Rp 250.000                  │
│    Pending: Rp 150.000                      │
└─────────────────────────────────────────────┘
```

### 8.5.6 Format Nomor Refund

Format: `REF-{BRAND_CODE}-{YYYYMMDD}-{SEQUENCE}`
Contoh: `REF-SHU-20260420-001`

---

# 9. Dashboard Admin Keuangan & Sistem Keuangan (Comprehensive)

## 9.1 Summary Cards

| Widget | Tipe | Keterangan |
|--------|------|-------------|
| Invoice Pending Validation | Number | Invoice menunggu validasi |
| Invoice Validated Today | Number | Invoice divalidasi hari ini |
| Total Revenue Pending | Currency | Total tagihan pending |
| Payment Verified Today | Currency | Pembayaran diverifikasi hari ini |
| **Total PO Lunas** | **Number + Currency** | **Jumlah PO yang sudah lunas + total nominal** |
| **Total PO Belum Lunas** | **Number + Currency** | **Jumlah PO belum lunas + total sisa tagihan** |
| **Total PO Partial Payment** | **Number + Currency** | **PO yang sudah bayar sebagian** |
| **Outstanding Amount** | **Currency** | **Total piutang yang belum terbayar** |
| **Total Refund Pending** | **Number + Currency** | **Refund menunggu verifikasi + total nominal** |
| **Total Refund Diterbitkan** | **Number + Currency** | **Refund yang sudah diterbitkan (pengurangan)** |
| **Total Pemasukan (Bulan Ini)** | **Currency** | **Total pemasukan bulan berjalan (otomatis + manual)** |
| **Total Pengeluaran (Bulan Ini)** | **Currency** | **Total pengeluaran bulan berjalan** |
| **Saldo Bersih (Bulan Ini)** | **Currency** | **Pemasukan - Pengeluaran - Refund** |

## 9.2 Detailing PO Pembayaran

### 9.2.1 PO Lunas (List + Lihat Selengkapnya)

| Kolom | Tipe | Keterangan |
|-------|------|-------------|
| No PO | String | Nomor PO |
| Pelanggan | String | Nama pelanggan |
| Total Tagihan | Currency | Total tagihan |
| Tanggal Lunas | Date | Tanggal pelunasan |
| Metode Bayar | String | Transfer/E-Wallet |

**Tampilkan 10 terbaru** → Tombol **"Lihat Selengkapnya"** → mengarah ke `/laporan/finance?status=lunas`

### 9.2.2 PO Belum Lunas (List + Lihat Selengkapnya)

| Kolom | Tipe | Keterangan |
|-------|------|-------------|
| No PO | String | Nomor PO |
| Pelanggan | String | Nama pelanggan |
| Total Tagihan | Currency | Total tagihan |
| Sudah Bayar | Currency | Total yang sudah dibayar (DP + cicilan) |
| Sisa Tagihan | Currency | Total yang belum dibayar |
| Jatuh Tempo | Date | Tanggal jatuh tempo |
| Status | Badge | Belum Bayar / Partial / Overdue |

**Tampilkan 10 terbaru** → Tombol **"Lihat Selengkapnya"** → mengarah ke `/laporan/finance?status=belum_lunas`

### 9.2.3 PO Overdue (List + Lihat Selengkapnya)

| Kolom | Tipe | Keterangan |
|-------|------|-------------|
| No PO | String | Nomor PO |
| Pelanggan | String | Nama pelanggan |
| Sisa Tagihan | Currency | Sisa yang belum dibayar |
| Jatuh Tempo | Date | Tanggal jatuh tempo |
| Hari Terlambat | Number | Berapa hari sudah lewat jatuh tempo |

**Tampilkan 10 terbaru** → Tombol **"Lihat Selengkapnya"** → mengarah ke `/laporan/finance?status=overdue`

## 9.3 Invoice Management Table

| Kolom | Tipe | Keterangan |
|-------|------|-------------|
| No Invoice | String | Nomor invoice |
| PO Name | String | Nama PO |
| Customer | String | Nama pelanggan |
| Total Amount | Currency | Total tagihan |
| Sudah Bayar | Currency | Total yang sudah dibayar |
| Sisa | Currency | Sisa tagihan |
| Payment Status | Badge | Lunas / Belum Lunas / Partial / Overdue |
| Bank | String | Bank tujuan |
| Action | Button | Validate/Publish |

## 9.4 Refund Management Dashboard

### 9.4.1 Refund Pending Review (List + Lihat Selengkapnya)

| Kolom | Tipe | Keterangan |
|-------|------|-------------|
| No Refund | String | Nomor refund |
| No PO | String | Nomor PO terkait |
| Pelanggan | String | Nama pelanggan |
| Jenis Masalah | Badge | Produk cacat / Ukuran salah / dll |
| Nominal Refund | Currency | Nominal refund |
| Diajukan Oleh | String | Admin Brand / Reseller |
| Tanggal Pengajuan | Date | Tanggal pengajuan |
| Action | Button | Review / Terbitkan / Tolak |

**Tampilkan 10 terbaru** → Tombol **"Lihat Selengkapnya"** → mengarah ke `/laporan/refund?status=pending`

### 9.4.2 Refund Diterbitkan (List + Lihat Selengkapnya)

| Kolom | Tipe | Keterangan |
|-------|------|-------------|
| No Refund | String | Nomor refund |
| No PO | String | Nomor PO terkait |
| Jenis Masalah | Badge | Kategori masalah |
| Nominal Refund | Currency | Nominal refund |
| Diterbitkan Oleh | String | Admin Keuangan |
| Tanggal Terbit | Date | Tanggal penerbitan |

**Tampilkan 10 terbaru** → Tombol **"Lihat Selengkapnya"** → mengarah ke `/laporan/refund?status=published`

### 9.4.3 Workflow Refund di Admin Keuangan

```
Refund masuk dari Admin Brand/Reseller (status: pending_review)
    ↓
Admin Keuangan → Lihat detail refund:
  - Detail PO terkait
  - Alasan refund
  - Jumlah item & nominal
  - Bukti foto/dokumen
    ↓
Admin Keuangan bisa:
  ✏️ Edit nominal refund (jika perlu koreksi)
  ✏️ Tambah catatan keuangan
    ↓
Admin Keuangan → Pilih:
  ✅ Terbitkan → Refund resmi & masuk laporan pengurangan
  ❌ Tolak → Return ke pengaju dengan alasan
```

## 9.5 Master Data Keuangan

### 9.5.1 Master Kategori Pemasukan

Master data untuk mengkategorikan semua jenis pemasukan perusahaan.

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| brand_id | UUID | ✓ | FK ke brand |
| nama_kategori | VARCHAR(100) | ✓ | Nama kategori pemasukan |
| deskripsi | TEXT | - | Deskripsi kategori |
| is_system | BOOLEAN | ✓ | Default: false. True untuk kategori otomatis (PO Published) |
| is_active | BOOLEAN | ✓ | Default: true |
| created_at | TIMESTAMP | ✓ | |
| updated_at | TIMESTAMP | ✓ | |

**Kategori Default (is_system = true):**

| Kategori | Keterangan | Otomatis |
|----------|-------------|---------|
| PO Published | Pemasukan dari PO yang diterbitkan | ✅ Otomatis tercatat saat PO diterbitkan |
| Pembayaran Invoice | Pemasukan dari pembayaran invoice | ✅ Otomatis tercatat saat pembayaran diverifikasi |

**Kategori Custom (contoh):**

- Investasi
- Pinjaman
- Pendapatan Lain-lain
- Penjualan Aset
- Hibah/Sponsor
- (Admin Keuangan bisa menambah kategori sesuai kebutuhan)

### 9.5.2 Master Kategori Pengeluaran

Master data untuk mengkategorikan semua jenis pengeluaran perusahaan. **Sub-kategori bersifat dinamis** — Admin Keuangan dapat menambah kategori pengeluaran baru kapan saja sesuai kebutuhan operasional.

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| brand_id | UUID | ✓ | FK ke brand |
| parent_id | UUID | - | FK ke parent kategori (untuk sub-kategori, nullable) |
| nama_kategori | VARCHAR(100) | ✓ | Nama kategori pengeluaran |
| deskripsi | TEXT | - | Deskripsi kategori |
| is_system | BOOLEAN | ✓ | Default: false. True untuk kategori otomatis (Refund) |
| is_active | BOOLEAN | ✓ | Default: true |
| created_at | TIMESTAMP | ✓ | |
| updated_at | TIMESTAMP | ✓ | |

**Kategori Default (is_system = true):**

| Kategori | Keterangan | Otomatis |
|----------|-------------|---------|
| Refund PO | Pengeluaran/pengurangan dari refund PO | ✅ Otomatis tercatat saat refund diterbitkan |

**Kategori Custom (contoh, bisa ditambah dinamis):**

- **Operasional**
  - Gaji Karyawan
  - Listrik & Air
  - Sewa Gedung
  - Internet & Telepon
  - Transportasi
- **Produksi**
  - Pembelian Bahan Baku
  - Pembelian Tinta/Cat
  - Maintenance Mesin
  - Spare Part
- **Marketing**
  - Iklan Online
  - Biaya Endorsement
  - Cetak Brosur/Banner
- **Administrasi**
  - ATK (Alat Tulis Kantor)
  - Pajak
  - Asuransi
  - Biaya Bank
- **Lain-lain**
  - Donasi
  - Biaya Tak Terduga
  - (Dan sub-kategori lainnya yang bisa ditambah dinamis)

**Catatan Penting:** Struktur kategori pengeluaran mendukung **hierarki parent-child** sehingga sub-kategori bisa sangat banyak dan dinamis. Admin Keuangan bebas menambah, mengedit, atau menonaktifkan kategori sesuai kebutuhan bisnis.

### 9.5.3 Data Pemasukan

Tabel untuk mencatat semua pemasukan perusahaan, baik otomatis (dari PO/Invoice) maupun manual.

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| brand_id | UUID | ✓ | FK ke brand |
| kategori_pemasukan_id | UUID | ✓ | FK ke master kategori pemasukan |
| order_id | UUID | - | FK ke PO (jika pemasukan otomatis dari PO) |
| invoice_id | UUID | - | FK ke invoice (jika pemasukan dari pembayaran invoice) |
| tanggal | DATE | ✓ | Tanggal pemasukan |
| nominal | DECIMAL(15,2) | ✓ | Jumlah pemasukan |
| keterangan | TEXT | ✓ | Deskripsi/keterangan pemasukan |
| bukti | JSON | - | Array path bukti transfer/dokumen |
| is_auto | BOOLEAN | ✓ | Default: false. True jika otomatis dari PO/Invoice |
| created_by | UUID | ✓ | FK ke user |
| created_at | TIMESTAMP | ✓ | |
| updated_at | TIMESTAMP | ✓ | |

**Aturan Bisnis Pemasukan:**

- **PO Diterbitkan** → Otomatis tercatat sebagai pemasukan dengan kategori "PO Published"
- **Pembayaran Invoice** → Otomatis tercatat sebagai pemasukan dengan kategori "Pembayaran Invoice"
- **Manual** → Admin Keuangan bisa menambah pemasukan manual (investasi, pendapatan lain, dll)
- **Record otomatis** (`is_auto = true`) tidak bisa diedit/dihapus oleh Admin Keuangan

### 9.5.4 Data Pengeluaran

Tabel untuk mencatat semua pengeluaran perusahaan, baik otomatis (dari Refund) maupun manual.

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| brand_id | UUID | ✓ | FK ke brand |
| kategori_pengeluaran_id | UUID | ✓ | FK ke master kategori pengeluaran |
| refund_id | UUID | - | FK ke refund (jika pengeluaran otomatis dari refund) |
| tanggal | DATE | ✓ | Tanggal pengeluaran |
| nominal | DECIMAL(15,2) | ✓ | Jumlah pengeluaran |
| keterangan | TEXT | ✓ | Deskripsi/keterangan pengeluaran |
| bukti | JSON | - | Array path bukti pembayaran/dokumen |
| is_auto | BOOLEAN | ✓ | Default: false. True jika otomatis dari Refund |
| created_by | UUID | ✓ | FK ke user |
| created_at | TIMESTAMP | ✓ | |
| updated_at | TIMESTAMP | ✓ | |

**Aturan Bisnis Pengeluaran:**

- **Refund Diterbitkan** → Otomatis tercatat sebagai pengeluaran dengan kategori "Refund PO"
- **Manual** → Admin Keuangan bisa menambah pengeluaran manual (gaji, listrik, bahan baku, dll)
- **Record otomatis** (`is_auto = true`) tidak bisa diedit/dihapus oleh Admin Keuangan
- **Sub-kategori dinamis** → Bisa ditambah sesuai kebutuhan tanpa batas

## 9.6 Input Keuangan oleh Admin Keuangan

### 9.6.1 Input Pemasukan Manual

Form untuk Admin Keuangan menginput pemasukan perusahaan:

| Field | Tipe | UI | Keterangan |
|-------|------|-----|-------------|
| Kategori | Dropdown | Select2 | Pilih dari master kategori pemasukan |
| Tanggal | Date Picker | Calendar | Tanggal pemasukan |
| Nominal | Currency Input | Rp format | Jumlah pemasukan |
| Keterangan | Textarea | Rich text | Deskripsi detail pemasukan |
| Bukti | File Upload | Multiple | Upload bukti transfer/dokumen |

### 9.6.2 Input Pengeluaran Manual

Form untuk Admin Keuangan menginput pengeluaran perusahaan:

| Field | Tipe | UI | Keterangan |
|-------|------|-----|-------------|
| Kategori | Dropdown | Select2 dengan parent-child | Pilih kategori (bisa nested sub-kategori) |
| Tanggal | Date Picker | Calendar | Tanggal pengeluaran |
| Nominal | Currency Input | Rp format | Jumlah pengeluaran |
| Keterangan | Textarea | Rich text | Deskripsi detail pengeluaran |
| Bukti | File Upload | Multiple | Upload bukti pembayaran/dokumen |

### 9.6.3 CRUD Pemasukan & Pengeluaran

- **Create**: Form input dengan validasi field wajib
- **Read**: Tabel data dengan filter (tanggal, kategori, range nominal)
- **Update**: Edit data yang sudah diinput (hanya data manual, bukan otomatis)
- **Delete**: Soft delete dengan alasan penghapusan (hanya data manual)
- **Export**: Excel/PDF per periode

## 9.7 Financial Analytics (Charts)

| Widget | Tipe | Keterangan |
|--------|------|-------------|
| Revenue Trend | Line Chart | Tren pendapatan harian/mingguan/bulanan |
| Payment Status Distribution | Donut Chart | Distribusi Lunas vs Belum Lunas vs Partial |
| Outstanding Aging | Bar Chart | Piutang berdasarkan usia (30/60/90 hari) |
| Cash Flow | Area Chart | Arus kas masuk vs keluar per periode |
| **Pemasukan vs Pengeluaran** | **Stacked Bar Chart** | **Perbandingan pemasukan dan pengeluaran per bulan** |
| **Refund Trend** | **Line Chart** | **Tren refund per bulan** |
| **Pengeluaran per Kategori** | **Donut Chart** | **Distribusi pengeluaran berdasarkan kategori** |
| **Pemasukan per Kategori** | **Donut Chart** | **Distribusi pemasukan berdasarkan kategori** |
| **Laba Rugi Bulanan** | **Bar Chart** | **Profit/Loss per bulan (Pemasukan - Pengeluaran - Refund)** |

## 9.8 Financial Reports (Comprehensive)

### 9.8.1 Laporan Invoice

- Invoice aging report
- Payment reconciliation report
- Revenue by brand/reseller
- Outstanding payments
- Cash flow projection
- **Detailing PO Lunas** (selaras dengan dashboard)
- **Detailing PO Belum Lunas** (selaras dengan dashboard)

### 9.8.2 Laporan Refund

- **Total refund per periode** (harian/mingguan/bulanan)
- **Refund per jenis masalah** (produk cacat, ukuran salah, dll)
- **Refund per PO** (detail per PO yang di-refund)
- **Refund per Admin Brand/Reseller** (siapa yang paling banyak mengajukan)
- **Tren refund** (apakah meningkat/menurun)
- **Total nominal refund sebagai data pengurangan nilai**
- Export Excel/PDF

### 9.8.3 Laporan Pemasukan

- **Total pemasukan per periode** (harian/mingguan/bulanan/tahunan)
- **Pemasukan per kategori** (PO, Invoice, manual, dll)
- **Pemasukan otomatis vs manual**
- **Tren pemasukan**
- **Detail per transaksi**
- Export Excel/PDF

### 9.8.4 Laporan Pengeluaran

- **Total pengeluaran per periode** (harian/mingguan/bulanan/tahunan)
- **Pengeluaran per kategori** (operasional, produksi, marketing, dll)
- **Pengeluaran per sub-kategori** (detail breakdown)
- **Tren pengeluaran**
- **Pengeluaran otomatis (refund) vs manual**
- **Detail per transaksi**
- Export Excel/PDF

### 9.8.5 Laporan Laba Rugi

- **Total Pemasukan** - Total semua pemasukan (otomatis + manual)
- **Total Pengeluaran** - Total semua pengeluaran (otomatis + manual)
- **Total Refund** - Total semua refund yang diterbitkan
- **Laba/Rugi Bersih** = Pemasukan - Pengeluaran - Refund
- **Per Periode** (harian/mingguan/bulanan/tahunan)
- **Perbandingan antar periode**
- **Visualisasi trend laba/rugi**
- Export Excel/PDF

### 9.8.6 Laporan Arus Kas (Cash Flow)

- **Arus Kas Masuk**: Pemasukan dari semua sumber
- **Arus Kas Keluar**: Pengeluaran dari semua sumber + Refund
- **Saldo Awal & Saldo Akhir** per periode
- **Net Cash Flow** per periode
- **Proyeksi arus kas** (berdasarkan invoice outstanding & pengeluaran rutin)
- Export Excel/PDF

---

# 10. Kalender PO (All Roles Except Finance)

## 10.1 Arsitektur Kalender Global

### 10.1.1 Akses Universal

- **All Roles Access**: Superadmin, Owner, Admin Brand, Admin Produksi, Reseller
- **Role-Based Filtering**: Data PO difilter berdasarkan brand/reseller assignment
- **Real-time Synchronization**: Update otomatis saat ada perubahan PO/progress
- **Multi-Device Support**: Responsive design untuk desktop, tablet, mobile

### 10.1.2 View Modes

- **Monthly View**: Kalender bulanan dengan PO indicators
- **Weekly View**: Focus pada 1 minggu dengan detail timeline
- **List View**: Table format untuk bulk operations
- **Timeline View**: Gantt chart style untuk production tracking

### 10.1.3 Grouping & Detailing

- **Master Data Grouping**: Kalender **WAJIB** menampilkan detail spesifik berdasarkan **Grup Master Data Jenis Order** (Contoh: Jersey, Jaket, Celana, dll).
- **Color Coding per Group**: Selain status, user dapat memilih view berdasarkan warna kategori produk.

## 10.2 Color Coding & Status Indicators

### 10.2.1 Status-Based Colors

| Status | Color | Icon | Description |
|--------|-------|------|-------------|
| PO Masuk | 🔵 Blue (#3B82F6) | 📦 | Order baru masuk, belum diproses |
| On Progress | 🟡 Amber (#F59E0B) | ⚙️ | Sedang dalam proses produksi |
| QC Check | 🟢 Emerald (#10B981) | ✅ | Quality Control sedang berlangsung |
| Selesai Produksi | 🟢 Green (#22C55E) | 🎉 | Produksi selesai, siap packing |
| Siap Dikirim | 🔵 Cyan (#06B6D4) | 🚚 | Sudah packing & siap dikirim |
| Sudah Dikirim | 🟣 Purple (#8B5CF6) | 🏁 | Barang telah dikirim ke pelanggan |
| Delay | 🔴 Red (#EF4444) | ⚠️ | Melewati estimasi selesai |
| Hold | 🟠 Orange (#F97316) | ⏸️ | Ditahan (masalah pembayaran/rijek aktif) |
| Cancel | ⚫ Gray (#6B7280) | ❌ | Order dibatalkan |

### 10.2.2 Priority Indicators

- **⭐ High Priority**: Diamond customers atau express orders
- **🔥 Urgent**: Deadline < 24 jam
- **🔔 Reminder**: Deadline < 3 hari
- **💰 High Value**: Order dengan nilai > threshold

## 10.3 Role-Based Calendar Functionality

### 10.3.1 **Superadmin Calendar View**

```
Dashboard: /calendar/superadmin

Features:
✅ View ALL brands/resellers PO dalam satu calendar
✅ Cross-brand comparison dengan filter toggle
✅ Performance metrics overlay (revenue, delay rate)
✅ Bulk operations across brands
✅ Export global calendar reports

Color Theme: Dark blue header with multi-brand badges
Access Level: Full system visibility
```

**Use Cases:**

- Monitor overall system capacity
- Identify bottleneck brands
- Compare performance across resellers
- Generate executive reports

### 10.3.2 **Owner Calendar View**

```
Dashboard: /calendar/owner/{brand_id}

Features:
✅ All PO dalam brand yang dimiliki
✅ Revenue tracking per PO
✅ Customer analysis per date
✅ Deadline compliance dashboard
✅ Export brand-specific reports

Color Theme: Brand primary colors
Access Level: Single/multiple brands owned
```

**Use Cases:**

- Track brand performance
- Customer relationship management
- Revenue forecasting
- Capacity planning

### 10.3.3 **Admin Brand Calendar View**

```
Dashboard: /calendar/brand

Features:
✅ PO yang dibuat/dikelola dalam brand
✅ Customer deadline tracking
✅ Production progress visibility
✅ Invoice status integration
✅ Quick PO creation from calendar

Color Theme: Light blue with brand accent
Access Level: Single brand only
```

**Use Cases:**

- Daily PO monitoring
- Customer communication
- Production coordination
- Invoice follow-up

### 10.3.4 **Admin Produksi Calendar View**

```
Dashboard: /calendar/production

Features:
✅ Production capacity per date
✅ Progress update directly from calendar
✅ Deadline vs actual completion tracking
✅ Operator workload distribution
✅ Bottleneck identification

Color Theme: Orange/amber theme
Access Level: Production management scope
```

**Use Cases:**

- Production scheduling
- Capacity management
- Quality control monitoring
- Delay prevention

### 10.3.5 **Reseller Calendar View**

```
Dashboard: /calendar/reseller

Features:
✅ PO yang dibuat/dikelola oleh reseller tersebut
✅ Customer deadline tracking (data customer reseller)
✅ Production progress visibility
✅ Invoice status integration
✅ Quick PO creation from calendar

Color Theme: Green with reseller accent
Access Level: Single reseller data only (terisolasi)
```

**Use Cases:**

- Daily PO monitoring
- Customer communication
- Production coordination
- Invoice follow-up

## 10.4 Interactive Calendar Features

### 10.4.1 Click Date Functionality

Ketika user klik tanggal di calendar:

**Pop-up Modal Content:**

```
📅 {Tanggal} - {Jumlah PO} Orders

📋 PO List:
1. 🎯 {PO Name} - {Customer}
   Status: {Current Status}
   Deadline: {Customer Deadline}
   Progress: {Production Progress}%
   Value: Rp {Total Value}
   [View Details] [Update Progress] [Contact Customer]

2. 🎯 {PO Name} - {Customer}
   ...

📊 Daily Summary:
• Total Orders: {X}
• Completed Today: {Y}
• Delayed: {Z}
• Revenue: Rp {Total}
```

### 10.4.2 Drag & Drop Operations

- **PO Rescheduling**: Drag PO ke tanggal lain (role permissions apply)
- **Progress Updates**: Drag status indicators untuk quick updates
- **Bulk Selection**: Select multiple PO untuk batch operations

### 10.4.3 Quick Actions dari Calendar

- **Create New PO**: Quick add button pada tanggal kosong
- **Update Progress**: Inline status updates
- **Send Notifications**: WhatsApp reminders langsung dari calendar
- **Export Date Range**: PDF/Excel export untuk selected dates

## 10.5 Advanced Filtering & Search

### 10.5.1 Filter Options

- **Date Range**: Custom start/end dates
- **Status Filter**: Multiple status selection
- **Brand/Reseller**: Dropdown filter
- **Customer**: Search by customer name
- **Value Range**: Filter by order value
- **Product Category**: Jersey, Jaket, etc.
- **Priority Level**: High, Normal, Low

### 10.5.2 Search Functionality

- **PO Number Search**: Exact match or partial
- **Customer Search**: Fuzzy search dengan autocomplete
- **Advanced Filters**: Combine multiple criteria
- **Saved Filters**: User dapat save custom filter sets

## 10.6 Calendar Integration Points

### 10.6.1 Dashboard Widgets

- **Calendar Preview**: Mini calendar di dashboard utama
- **Today's Highlights**: Critical items untuk hari ini
- **Upcoming Deadlines**: Countdown untuk deadline mendekat
- **Capacity Alerts**: Warning untuk over-capacity dates

### 10.6.2 Notification Integration

- **Deadline Reminders**: Auto-notify via WhatsApp/Telegram
- **Status Change Alerts**: Real-time updates saat progress berubah
- **Capacity Warnings**: Alert saat production overbooked
- **Customer Updates**: Notify customer progress changes

### 10.6.3 Reporting Integration

- **Calendar Exports**: PDF/Excel dengan visual indicators
- **Performance Reports**: Calendar-based analytics
- **Trend Analysis**: Historical calendar data
- **Forecasting**: Future capacity planning

## 10.7 Calendar Permissions Matrix

| Feature | Superadmin | Owner | Admin Brand | Admin Produksi | Reseller |
|---------|------------|-------|-------------|----------------|----------|
| View All PO | ✅ | ❌ (own brands) | ❌ (own brand) | ❌ (production) | ❌ (own reseller only) |
| Update Progress | ✅ | ❌ | ❌ | ✅ | ❌ |
| Create PO | ✅ | ✅ | ✅ | ❌ | ✅ |
| Reschedule PO | ✅ | ⚠️ (approval) | ⚠️ (approval) | ✅ | ⚠️ (approval) |
| Export Data | ✅ | ✅ | ✅ | ✅ | ✅ |
| Bulk Operations | ✅ | ✅ | ✅ | ✅ | ✅ |
| Cross-Brand View | ✅ | ❌ | ❌ | ❌ | ❌ |

## 10.8 Mobile Calendar Experience

### 10.8.1 Touch Optimizations

- **Swipe Gestures**: Swipe untuk navigasi bulan
- **Tap Actions**: Double-tap untuk quick view
- **Pinch Zoom**: Zoom in/out untuk detail
- **Pull Refresh**: Sync latest data

### 10.8.2 Mobile-Specific Features

- **Today Button**: Quick jump ke hari ini
- **Voice Commands**: "Show delayed orders" etc.
- **Push Notifications**: Mobile push untuk critical alerts
- **Offline Mode**: Cache calendar data untuk offline viewing

## 10.9 Calendar Analytics & Insights

### 10.9.1 Built-in Analytics

- **Capacity Utilization**: % kapasitas terpakai per tanggal
- **Delay Patterns**: Trend delay berdasarkan hari dalam minggu
- **Revenue Tracking**: Revenue per calendar date
- **Customer Concentration**: Customer dengan multiple PO

### 10.9.2 AI-Powered Insights

- **Predictive Alerts**: Prediksi bottleneck berdasarkan patterns
- **Optimization Suggestions**: Saran redistribusi workload
- **Trend Forecasting**: Prediksi busy periods
- **Performance Scoring**: Calendar-based performance metrics

---

# 11. Production Flow & Scheduling

## 11.1 Production Status Transition (Updated)

```
Invoice Published → Admin Produksi dapat Process PO
    ↓
Admin Produksi → Set Production Deadline & Start Date
    ↓
🔒 PO AUTO-LOCK (tidak bisa edit detailing tanpa unlock)
    ↓
Progress Update Per Tahapan:
  Tahapan 1 → on_progress (wajib catatan) → selesai / skip (wajib alasan)
    ↓
  Tahapan 2 → on_progress → selesai / skip
    ↓
  ... (bisa loncat tahapan jika is_skippable = true)
    ↓
  Tahapan N → selesai
    ↓
Indikator Selesai (berdasarkan pengaturan Superadmin)
    ↓
Packing → PO Siap Dikirim
    ↓
Dikirim → PO Benar-benar Selesai
```

## 11.2 Production Scheduling

### 11.2.1 Deadline Management

- **Customer Deadline**: Deadline dari customer (read-only setelah PO locked, perlu unlock + alasan untuk ubah)
- **Production Deadline**: Deadline produksi yang ditentukan Admin Produksi
- **Start Date**: Tanggal mulai produksi yang dijadwalkan
- **End Date**: Tanggal target selesai produksi
- **Buffer Time**: Selisih antara customer deadline dan production deadline

### 11.2.2 Progress Update by Admin Produksi (Enhanced)

Setiap tahapan progress memiliki **workflow independen**:

| Aksi | Deskripsi | Wajib Isi |
|------|-----------|-----------|
| **Set On Progress** | Memulai pengerjaan tahapan | Catatan (apa yang akan dikerjakan) |
| **Set Selesai** | Menyelesaikan tahapan | Catatan (hasil/output) |
| **Skip Tahapan** | Loncat ke tahapan berikutnya | Alasan skip (wajib) + Catatan |
| **Tambah Kendala** | Melaporkan masalah di tahapan | Deskripsi kendala |
| **Tambah Rijek** | Input rijek di tahapan ini | Detail rijek (lihat Section 6.3) |

**Aturan Skip Tahapan:**

- Tahapan boleh di-skip jika `is_skippable = true` di master progress
- Alasan skip **wajib diisi** dan tercatat di `order_progress_details`
- Tahapan yang di-skip tetap terlihat di timeline dengan status `skipped`
- Admin Produksi bisa melanjutkan ke tahapan berikutnya tanpa menunggu tahapan sebelumnya selesai (jika sudah ada catatan)

**Catatan & Kendala per Progress:**

- Setiap perubahan status **wajib** ada catatan
- Kendala bisa ditambahkan kapan saja selama tahapan masih `on_progress`
- Admin Produksi bisa **membaca semua kendala** di masing-masing progress
- Kendala tercatat dengan timestamp dan user

### 11.2.3 PO Lock Mechanism (NEW)

**Auto-Lock:**

- PO **otomatis ter-lock** ketika tahapan pertama diubah ke `on_progress`
- Setelah locked, **tidak bisa** mengubah detailing PO (deadline, komponen, bahan, dll)

**Unlock Process:**

1. User dengan permission (Owner/Admin Brand) request unlock
2. Wajib isi **alasan perubahan** (change_reason)
3. PO di-unlock sementara untuk editing
4. Setiap field yang diubah tercatat: field, old_value, new_value, changed_by
5. Setelah selesai edit, PO otomatis **re-lock**
6. Semua perubahan masuk ke **PO Change Log** (Section 5.14.2)

**Yang bisa diubah saat unlock:**

- Deadline customer
- Deadline produksi
- Komponen PO (bahan, warna, logo, dll)
- Catatan/keterangan

**Yang TIDAK bisa diubah:**

- Nomor PO
- Pelanggan
- Tanggal masuk

### 11.2.4 Protection Rules (Updated)

- PO hanya bisa diproses setelah invoice published
- PO **auto-lock** saat masuk pengerjaan produksi
- Perubahan pada locked PO wajib melalui mekanisme unlock dengan record alasan
- Admin Brand tidak bisa edit deadline produksi
- Admin Produksi dapat menyesuaikan scheduling berdasarkan kapasitas
- Progress history lengkap untuk audit trail
- Semua perubahan tercatat di PO Change Log

## 11.3 Preview PO (Production View)

Admin Produksi memiliki akses **Preview PO** yang menampilkan semua informasi secara komprehensif:

```
┌─────────────────────────────────────────────┐
│ PREVIEW PO: PO-SHU-20260415-001            │
├─────────────────────────────────────────────┤
│ 📋 INFO PO                                  │
│   Pelanggan: PT ABC | Deadline: 20 Apr 2026│
│   Status: On Progress | Lock: 🔒 Locked     │
├─────────────────────────────────────────────┤
│ 📊 PROGRESS TIMELINE                        │
│   ✅ Cutting    → Selesai (15 Apr)          │
│      Catatan: Cutting selesai 50 pcs        │
│   🔄 Jahit      → On Progress (16 Apr)     │
│      Catatan: Mulai jahit batch 1           │
│      ⚠️ Kendala: Mesin jahit #3 rusak       │
│   ⏭️ Sablon     → Skipped                   │
│      Alasan: Tidak ada sablon di PO ini     │
│   ⏳ QC         → Pending                   │
│   ⏳ Packing    → Pending                   │
├─────────────────────────────────────────────┤
│ ❌ RIJEK HISTORY                             │
│   - 16 Apr: 5 pcs jahit gagal (ringan)     │
├─────────────────────────────────────────────┤
│ 📝 CHANGE LOG                               │
│   - 16 Apr: Deadline diubah oleh Admin A   │
│     Alasan: Customer minta extend           │
│     20 Apr → 25 Apr                         │
├─────────────────────────────────────────────┤
│ 💰 STATUS PEMBAYARAN                        │
│   DP: Rp 500.000 | Sisa: Rp 1.500.000      │
│   Status: Belum Lunas                        │
└─────────────────────────────────────────────┘
```

## 11.4 Calendar Integration & Synchronization

### 11.4.1 Calendar Features (All Roles Except Finance)

- **Monthly View**: Kalender bulanan dengan PO indicators
- **Color Coding System**:
  - 🔵 Blue: PO baru masuk (belum diproses)
  - 🟡 Amber: Sedang produksi (on progress)
  - 🟢 Green: Selesai (completed)
  - 🔴 Red: Terlambat (delay/customer deadline passed)
- **Deadline Indicators**: Warning badges untuk PO mendekati deadline
- **Click Interaction**: Klik tanggal → popup list PO pada tanggal tersebut

### 11.4.2 PO Detail Pop-up

```
Ketika klik tanggal menampilkan:
- List PO dengan info: Nama PO, Customer, Deadline Customer
- Status progress saat ini
- Production deadline (jika sudah diset Admin Produksi)
- Link ke Preview PO (detail catatan, kendala, rijek, change log)
- Quick actions: View details, Update progress (role-based)
```

### 11.4.3 Brand vs Production Synchronization

- **Customer Deadline**: Deadline dari customer (read-only untuk semua)
- **Production Deadline**: Deadline produksi yang ditentukan Admin Produksi
- **Buffer Management**: Sistem tracking selisih antara kedua deadline
- **Communication Tool**: Calendar sebagai medium koordinasi antar role

### 11.4.4 Advanced Calendar Features

- **Multi-Role View**: Filter berdasarkan role (Brand/Production view)
- **Deadline Alerts**: Notification untuk PO mendekati deadline
- **Capacity Planning**: Visual indikator beban kerja per tanggal
- **Historical Tracking**: Archive view untuk PO selesai
- **Export Calendar**: PDF/Excel export untuk reporting

## 11.5 Rijek Management (Updated)

### 11.5.1 Input Rijek

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| order_id | UUID | ✓ | FK ke order |
| progress_id | UUID | ✓ | FK ke master progress (tahapan mana rijek terjadi) |
| detail_order_id | UUID | - | FK ke detail order |
| jumlah | INT | ✓ | Jumlah rijek |
| jenis | ENUM | ✓ | sablon, printing, jahit, ukuran, lain |
| tingkat | ENUM | ✓ | ringan, sedang, berat |
| kendala | TEXT | ✓ | Deskripsi kendala |
| penanganan | TEXT | - | Cara menangani |
| biaya_ganti | DECIMAL(12,2) | - | Biaya ganti komponen |
| status | ENUM | ✓ | pending, proses, selesai |
| verified_by | UUID | - | Admin Produksi |
| created_by | UUID | ✓ | FK ke user |
| created_at | TIMESTAMP | ✓ | |

---

# 12. Laporan & Analisis

## Catatan Penting: Fokus Laporan

**Tujuan Laporan:** Mengetahui JUMLAH master data yang di-order untuk analisa keseluruhan

**Fokus Analisa:**

- Produk: Berapa banyak yang di-order, produk terlaris
- Pelanggan: Berapa banyak order per pelanggan
- Wilayah: Distribusi order per wilayah
- Promo: Berapa banyak yang menggunakan promo
- Kategori: Perbandingan per kategori
- Sumber Order: Dari mana order masuk

**Laporan bukan hanya revenue, tapi ANALISA DATA MASTER secara menyeluruh**

## 12.1 Laporan Produk (Master Data Analytics)

### 12.1.1 Filter Options

| Filter | Tipe | Keterangan |
|--------|------|-------------|
| tanggal_awal | DATE | Required |
| tanggal_akhir | DATE | Required |
| periode | ENUM | harian, mingguan, bulanan |
| produk_id | UUID | Optional |
| kategori_id | UUID | Optional |
| brand_id | UUID | Optional (Superadmin) |

### 12.1.2 Output Fields - Fokus JUMLAH

- **Nama Produk** - Produk yang di-order
- **Kode Produk**
- **Kategori**
- **Jumlah Di-Order** - JUMLAH total produk di-order
- **Jumlah Order** - Ada berapa order untuk produk ini
- **Rata-rata per Order** - Rata-rata jumlah per order
- Tren (ApexCharts Line)
- Growth percentage

### 12.1.3 Visualization (ApexCharts)

- Bar Chart: Produk by JUMLAH di-order
- Line Chart: Tren order over time
- Donut Chart: Distribution by kategori
- Heatmap: Waktu tersibuk

### 12.1.4 Export Formats

- Print (HTML)
- Excel (.xlsx)
- PDF

## 12.2 Laporan Kategori Order

- Statistik berdasarkan kategori
- Filter sama dengan laporan penjualan
- ApexCharts visualization

## 12.3 Laporan Promo

- Efektivitas promo
- Total penjualan sebelum/sesudah
- Conversion rate
- ROI calculation

## 12.4 Laporan Pelanggan

- Data pelanggan + jumlah order
- Total transaksi
- Kategori pelanggan
- Wilayah breakdown
- ApexCharts heatmap by wilayah

## 12.5 Laporan Type Pelanggan

- Breakdown berdasarkan type
- Average order value per type
- Retention rate

## 12.6 Laporan Wilayah

- Pengelompokan berdasarkan provinsi
- Kabupaten breakdown
- Kecamatan breakdown
- Map visualization
- Top wilayah

## 12.7 Laporan Rijek

- Total rijek per PO
- Per jenis rijek
- Total biaya
- Trend rijek
- Operator analysis
- ApexCharts breakdown

## 12.8 Laporan Finance

- Revenue summary
- Cost breakdown
- Profit calculation
- Cash flow
- Outstanding payments
- Aging receivables

## 12.9 Comparison Report (Superadmin)

- Pilih brand A vs brand B atau multi-brand
- Semua metric comparison
- Side-by-side ApexCharts
- Delta analysis
- Best practices identification

## 12.10 Laporan Status PO

### 12.10.1 Filter Options

| Filter | Tipe | Keterangan |
|--------|------|-------------|
| tanggal_awal | DATE | Required |
| tanggal_akhir | DATE | Required |
| status | ENUM | PO Masuk, On Progress, Selesai, Delay |
| brand_id | UUID | Optional |

### 12.10.2 Output Fields

- **PO Masuk** - Jumlah order baru masuk
- **On Progress** - Jumlah order sedang diproses
- **Selesai** - Jumlah order sudah selesai
- **Delay** - Jumlah order terlambat
- Visualisasi pie/bar chart per status

### 12.10.3 Visualization (ApexCharts)

- Pie Chart: Distribusi PO per status
- Bar Chart: Perbandingan status per brand
- Line Chart: Tren perubahan status per hari

## 12.11 Laporan Distribusi Kerja Harian

### 12.11.1 Filter Options

| Filter | Tipe | Keterangan |
|--------|------|-------------|
| tanggal | DATE | Required |
| periode | ENUM | harian, mingguan, bulanan |
| brand_id | UUID | Optional |

### 12.11.2 Output Fields

- **Jumlah PO Perhari** - Total PO masuk per hari
- **Beban Kerja Per-tanggal** - Distribusi workload
- **Trend PO Mingguan** - Tren mingguan
- **Peak Hours** - Jam tersibuk

### 12.11.3 Visualization (ApexCharts)

- Bar Chart: PO per hari
- Area Chart: Beban kerja distribution
- Line Chart: Trend mingguan

## 12.12 Laporan Monitoring Deadline

### 12.12.1 Filter Options

| Filter | Tipe | Keterangan |
|--------|------|-------------|
| threshold_hari | INT | Default: 7 hari |
| status_d迟adline | ENUM | Mendekat, Terlambat, Express |
| brand_id | UUID | Optional |

### 12.12.2 Output Fields

- **PO Mendekati Deadline** - PO dengan deadline < threshold
- **PO Terlambat** - PO yang sudah melewati deadline
- **PO Express** - PO prioritas tinggi
- **Warning Alert** - Tingkat kepatuhan deadline

### 12.12.3 Visualization (ApexCharts)

- Table: List PO mendekati/terlambat
- Gauge Chart: Deadline compliance rate
- Bar Chart: Jumlah per kategori

## 12.13 Laporan Breakdown Produksi

### 12.13.1 Filter Options

| Filter | Tipe | Keterangan |
|--------|------|-------------|
| tanggal_awal | DATE | Required |
| tanggal_akhir | DATE | Required |
| periode | ENUM | harian, mingguan, bulanan |
| brand_id | UUID | Optional |

### 12.13.2 Output Fields

- **Qty Hari Ini** - Total produksi hari ini
- **Qty Minggu Ini** - Total produksi minggu ini
- **Qty Bulan Ini** - Total produksi bulan ini
- **Qty Jenis Produk** - Distribusi per jenis produk
- **Qty Tiap Brand** - Produksi per brand
- **Qty Detail Produk** - Detail produk yang diproses

### 12.13.3 Visualization (ApexCharts)

- Number Cards: Qty per periode
- Donut Chart: Per Jenis Produk
- Bar Chart: Per Brand
- Table: Detail produk

## 12.14 Laporan Analisis Bisnis

### 12.14.1 Filter Options

| Filter | Tipe | Keterangan |
|--------|------|-------------|
| tanggal_awal | DATE | Required |
| tanggal_akhir | DATE | Required |
| brand_id | UUID | Optional |

### 12.14.2 Output Fields

- **Brand Paling Banyak Order** - Brand dengan order tertinggi
- **Brand Paling Besar Volume** - Brand dengan quantity tertinggi
- **Produk Paling Sering Dipesan** - Top produk
- **Detail Produk Sering Dipakai** - Attribut yang sering digunakan

### 12.14.3 Visualization (ApexCharts)

- Horizontal Bar: Brand analysis
- List: Ranking brand
- Bar Chart: Produk populer
- Table: Detail attributes

---

# 13. Invoice Management

## 13.1 Overview

Invoice adalah modul untuk menghasilkan dokumen tagihan kepada pelanggan dengan integrasi WhatsApp untuk pengiriman. Invoice dibuat manual oleh Admin Keuangan dari PO yang sudah diterbitkan jika DP sudah tercatat di PO payment section atau PO ditandai sebagai pesanan khusus. Invoice ini berfungsi sebagai dokumen resmi yang bisa di-track oleh pelanggan.

## 13.2 Struktur Database

### 13.2.1 invoices

| Field | Tipe | Required | Keterangan |
|-------|------|----------|-------------|
| id | UUID | ✓ | Primary key |
| brand_id | UUID | ✓ | FK ke brand |
| order_id | UUID | ✓ | FK ke order |
| invoice_number | VARCHAR(50) | ✓ | Unique, customizable format per brand |
| tanggal_terbit | DATE | ✓ | Tanggal invoice dibuat |
| jatuh_tempo | DATE | - | Tanggal jatuh tempo |
| status | ENUM | ✓ | draft, validated, published, sent, paid, overdue, cancel |
| total_tagihan | DECIMAL(12,2) | ✓ | Total tagihan |
| total_bayar | DECIMAL(12,2) | ✓ | Total sudah dibayar |
| dp_amount | DECIMAL(12,2) | - | DP yang ditarik dari PO payment section |
| sisa_pembayaran | DECIMAL(12,2) | - | Sisa tagihan setelah DP/diskon |
| catatan | TEXT | - | Catatan invoice |
| peraturan | TEXT | - | Syarat & ketentuan (manual per brand) |
| faq | JSON | - | FAQ items |
| qr_code | VARCHAR(255) | - | Generated QR code (link tracking) |
| sent_via | ENUM | - | whatsapp, email, manual |
| sent_at | TIMESTAMP | - | Kapan dikirim |
| created_by | UUID | ✓ | FK ke user |
| created_at | TIMESTAMP | ✓ | |
| updated_at | TIMESTAMP | ✓ | |

### 13.2.2 invoice_items

| Field | Tipe | Required |
|-------|------|----------|
| id | UUID | ✓ |
| invoice_id | UUID | ✓ |
| produk | VARCHAR(255) | ✓ |
| jumlah | INT | ✓ |
| harga_satuan | DECIMAL(12,2) | ✓ |
| subtotal | DECIMAL(12,2) | ✓ |

## 13.3 Format Invoice Number

Format invoice number bisa dikonfigurasi per brand di pengaturan brand:

| Parameter | Contoh | Keterangan |
|-----------|--------|-------------|
| prefix | INV | Prefix standar |
| brand_code | SHU, NIS | Kode brand |
| separator | -, / | Pemisah |
| format_tanggal | YYYYMMDD, YYMM | Format tanggal |
| sequence | 0001 | Nomor urut |

Contoh format: `INV-SHU-20260415-0001` atau `SHU2604150001`

## 13.4 Komponen Invoice

### 13.4.1 Header Invoice

| Komponen | Keterangan |
|----------|-------------|
| Logo Brand | Logo brand dari pengaturan |
| Tagline | Tagline dari pengaturan brand (di bawah logo) |
| Nama Brand | Nama brand |
| Invoice Number | Nomor invoice (customizable) |
| Tanggal Terbit | Tanggal invoice dibuat |
| Jatuh Tempo | Tanggal jatuh tempo |

### 13.4.2 Info Pelanggan

| Field | Sumber |
|-------|--------|
| Nama Pelanggan | Master pelanggan |
| Nomor HP | Master pelanggan |
| Email | Master pelanggan |
| Alamat | Master pelanggan (provinsi, kabupaten, kecamatan, desa, detail) |

### 13.4.3 Detail Pesanan

| Field | Sumber |
|-------|--------|
| No. PO | Order |
| Tanggal Order | Order |
| Produk | Order items |
| Jumlah | Order items |
| Harga Satuan | Order items |
| Subtotal | Order items |

### 13.4.4 QR Code Tracking

- QR Code yang berisi link ke halaman tracking
- Link: `{BASE_URL}/track/{no_po}`
- Scan QR code untuk langsung ke halaman tracking

### 13.4.5 Progress Order

- Status terkini order
- Visual timeline progress
- Estimasi selesai

### 13.4.6 Keterangan

- Catatan dari order
- Informasi tambahan

### 13.4.7 Peraturan

- Syarat & ketentuan (manual input per brand)
- Kebijakan pembatalan
- Kebijakan garansi

### 13.4.8 FAQ Default

```json
[
  {
    "question": "Bagaimana cara melacak pesanan?",
    "answer": "Anda dapat melacak pesanan melalui link QR code di invoice ini atau mengunjungi halaman tracking."
  },
  {
    "question": "Berapa lama waktu produksi?",
    "answer": "Waktu produksi rata-rata 7-14 hari kerja terhitung sejak PO divalidasi dan DP diterima."
  },
  {
    "question": "Apa kebijakan pengembalian?",
    "answer": "Pengembalian hanya berlaku untuk produk cacat produksi. Syarat dan ketentuan berlaku."
  },
  {
    "question": "Bagaimana cara pembayaran?",
    "answer": "Pembayaran dapat dilakukan via transfer bank (BCA, BRI, Mandiri), QRIS, atau metode lain yang tersedia."
  },
  {
    "question": "Apakah ada biaya pengiriman?",
    "answer": "Biaya pengiriman menyesuaikan tujuan dan berat paket. Gratis ongkir untuk area tertentu."
  }
]
```

### 13.4.9 Footer Invoice

- Contact person
- Alamat email
- Nomor WhatsApp
- Social media (jika ada)

## 13.5 Pengiriman via WhatsApp

### 13.5.1 Integrasi Sidobe

Menggunakan API WhatsApp Sidobe yang sudah terintegrasi di sistem.

### 13.5.2 Template Pesan WhatsApp

Template pesan yang customizable:

```
Halo {nama_pelanggan}!

Terima kasih telah memesan di {nama_brand}. 

Berikut invoice pesanan Anda:
- Nomor Invoice: {invoice_number}
- Total Tagihan: {total_tagihan}
- Jatuh Tempo: {jatuh_tempo}

Anda dapat melihat detail lengkap dan melacak pesanan di:
{invoice_link}

{dimulai QR code image}

Terima kasih atas kepercayaan Anda!
{nama_brand}
```

### 13.5.3 Pengiriman Otomatis

- Kirim otomatis setelah invoice dipublish
- Kirim reminder otomatis jika jatuh tempo
- Kirim manual oleh admin

## 13.6 Halaman Invoice Public

### 13.6.1 URL

- URL Public: `/invoice/{invoice_number}`
- Tidak perlu login untuk mengakses
- Responsive design (mobile-friendly)

### 13.6.2 Fitur Halaman Public

| Fitur | Keterangan |
|-------|-------------|
| Detail Invoice | Semua info invoice |
| Detail Pelanggan | Info pelanggan |
| Detail Pesanan | Item-item pesanan |
| QR Code | QR code untuk tracking |
| Progress Order | Status terkini |
| Download PDF | Download sebagai PDF |
| Print | Print halaman |
| Track Order | Link ke halaman tracking |

### 13.6.3 Design Invoice

- Clean dan professional
- Sesuai dengan contoh: <https://kertas.smartlink.id/nota/n/SHU260411074050284>
- Menggunakan styling yang modern
- Print-friendly (tanpa elemen interaktif saat print)

## 13.7 Fitur Tambahan

### 13.7.1 Partial Payment

- Track pembayaran parsial
- History pembayaran
- Sisa tagihan

### 13.7.2 Payment Reminder

- Otomatis kirim reminder via WhatsApp
- Notifikasi sebelum jatuh tempo
- Notifikasi setelah jatuh tempo

### 13.7.3 Invoice Status

| Status | Keterangan |
|--------|-------------|
| Draft | Belum dikirim |
| Sent | Sudah dikirim |
| Paid | Lunas |
| Overdue | Jatuh tempo terlewat |
| Cancel | Dibatalkan |

### 13.7.4 Aging Invoices

- Listing invoice berdasar usia
- Overdue invoices
- Warning untuk invoice nearing due

## 13.8 Permissions

### Admin Brand/Reseller Permissions

| Fitur | Create | Read | Update | Delete |
|-------|--------|------|--------|--------|
| View Invoice Draft | - | ✓ | - | - |
| View Published Invoices | - | ✓ | - | - |
| Export Invoice | - | ✓ | - | - |

### Finance Permissions

| Fitur | Create | Read | Update | Delete |
|-------|--------|------|--------|--------|
| Create Invoice from PO | ✓ | ✓ | - | - |
| Invoice Validation | - | ✓ | ✓ | - |
| Validate Invoice Details | - | ✓ | ✓ | - |
| Verify Payment | - | ✓ | ✓ | - |
| Publish/Cancel Invoice | - | - | ✓ | - |
| View All Invoices | - | ✓ | - | - |
| Financial Reports | ✓ | ✓ | - | - |

---

# 14. Admin Tools (WhatsApp & AI)

## 14.1 WhatsApp Gateway (Sidobe)

### 14.1.1 Integrasi

- API Key configurable per brand
- Webhook untuk incoming messages
- Send message ke nomor tertentu / group
- Media upload support

### 14.1.2 Pengaturan

- Default recipient (nomor/group untuk laporan)
- Jadwal laporan (harian/mingguan/bulanan)
- Auto send jika ada order baru
- Template pesan

## 14.2 AI Integration

### 14.2.1 Gemini API (Multiple Keys)

- Configurable API keys (bisa multiple)
- Load balancer otomatis jika limit reached
- Fallback mechanism
- Rate limit monitoring

### 14.2.2 Chatbot AI

- Context-aware responses
- Training data per brand
- Schedule reports ke WhatsApp
- Custom knowledge base

## 14.3 Admin Toolbox

### 14.3.1 WhatsApp Reply Tool

**Input Form:**

| Field | Tipe | Options |
|-------|------|---------|
| Pesan asli | Textarea | - |
| Tujuan balasan | Dropdown | CS, Sales, Problem Solving, Follow-up, Penolakan, Personal |
| Tone & Gaya | Dropdown | Profesional, Ramah, Kasual, Empatik, Tegas |
| Target audiences | Dropdown | Klien VIP, Calon Pembeli, Rekan Kerja, Mahasiswa, Teman |
| Panjang pesan | Dropdown | Sangat Singkat, Sedang, Detail |
| CTA | Dropdown | Tidak ada, Bertanya, Link, Konfirmasi, Bantuan |
| Info spesifik | Input | Optional custom data |

**Output:** AI-generated reply

### 14.3.2 Apparel Copywriter

**Input Form:**

| Field | Tipe | Options |
|-------|------|---------|
| Platform | Dropdown | Instagram, Website, Facebook Ads, WhatsApp |
| Kategori produk | Dropdown | Jersey, Kaos, Jaket, Seragam |
| Target pasar | Dropdown | Tim/Klub, Gen Z, Penggemar, Komunitas |
| Tone | Dropdown | Hype, Eksklusif, Kasual, Persuasif |
| Framework | Dropdown | AIDA, PAS, FAB, Storytelling |
| Keunggulan | Input | Comma-separated |
| Promo | Input | Optional |
| CTA | Dropdown | Klik bio, DM, Website, Chat WA |

**Output:** AI-generated copywriting

### 14.3.3 Smart Order Summarizer

**Input Form:**

| Field | Tipe | Options |
|-------|------|---------|
| Pesan mentah | Textarea | - |
| Kategori harga | Dropdown | Normal, Diskon, Reseller |
| Status ongkir | Dropdown | Belum termasuk, Cek nanti, Gratis |
| Metode pembayaran | Dropdown | Transfer, E-Wallet |
| CTA | Dropdown | Minta ACC, Lengkapi alamat |
| Keterangan | Text | Optional |

**Output:** Ringkasan pesanan rapi

### 14.3.4 Order Formatter (Pengekstrak Detail)

**Input Form:**

| Field | Tipe | Options |
|-------|------|---------|
| Data mentah | Textarea | - |
| Kategori setelan | Dropdown | Atasan, Setelan, Lengkap |
| Atribut cetak | Checkbox | Nama, Nomor, Logo, Sponsor |
| Urgensi | Dropdown | Normal, Prioritas |
| Format output | Dropdown | Tabel Rekap, SPK Gudang |

**Output:** Format standar SPK

### 14.3.5 Complaint Handler

**Input Form:**

| Field | Tipe | Options |
|-------|------|---------|
| Keluhan | Textarea | - |
| Akar masalah | Dropdown | Produksi, Ekspedisi, Klien |
| Opsi solusi | Dropdown | Retur, Diskon, Next Order, Tolak |
| Syarat bukti | Dropdown | Video, Foto |
| Nada bicara | Dropdown | Empatik, Tegas |

**Output:** Response komplain

---

# 15. Notification System

## 15.1 Notification Types

| Event | Channel | Recipient |
|-------|--------|----------|
| Order baru masuk | WhatsApp, Telegram, Email | Admin Brand |
| Order masuk produksi | WhatsApp, Telegram | Admin Produksi |
| Progress updated | WhatsApp, Telegram | Customer |
| Order selesai | WhatsApp, Telegram, Email | Customer |
| Deadline mendekat | WhatsApp, Telegram, Email | Admin Produksi |
| Payment received | WhatsApp, Telegram | Admin Brand |
| Weekly report | WhatsApp, Telegram, Email | Custom recipients |
| Monthly report | WhatsApp, Telegram, Email | Custom recipients |

## 15.2 Notification Settings

| Field | Tipe |
|-------|------|
| brand_id | UUID |
| event | ENUM |
| enabled | BOOLEAN |
| channel | ENUM (whatsapp, telegram, email, all) |
| recipient_type | ENUM (user, number, group, chat_id) |
| recipient_value | VARCHAR |
| template | TEXT |

## 15.3 Scheduled Reports (WhatsApp & Telegram)

### 15.3.1 Jenis Laporan Otomatis

| Jenis Laporan | Penerima | Jadwal | Konfigurasi |
|---------------|----------|--------|-------------|
| Superadmin Report (Semua Brand) | Superadmin | Harian, Mingguan, Bulanan | Nomor/Grup di Sidobe |
| Admin Produksi Report | Admin Produksi | Harian | Nomor/Grup di Sidobe |
| Admin Brand Report | Admin Brand | Harian | Nomor/Grup di Sidobe |
| Admin Keuangan Report | Admin Keuangan | Harian, Mingguan, Bulanan | Nomor/Grup di Sidobe |
| Owner Report | Owner | Mingguan, Bulanan | Nomor/Grup di Sidobe |

**Catatan:** Setiap role dapat configure:

- Untuk WhatsApp: nomor/group melalui Sidobe API
- Untuk Telegram: Chat ID melalui Bot API

### 15.3.2 Isi Laporan Otomatis

**A. Superadmin Report**

- Total brand aktif
- Total order masuk (harian/mingguan/bulanan)
- Total revenue seluruh brand
- Brand dengan order tertinggi
- Brand dengan growth tertinggi
- Overall performance summary

**B. Admin Produksi Report**

- Total order dalam proses
- Order masuk hari ini
- Deadline mendekat (7 hari)
- Rijek rate hari ini/minggu ini
- Progress summary
- Operator performance

**C. Owner Report (Per Brand)**

- Total order masuk (harian/mingguan/bulanan)
- Total revenue brand
- PO Terlambat (list detail)
- Deadline < 3 hari (list detail)
- Brand performance summary
- Customer analysis
- Product popularity
- Revenue breakdown

**D. Admin Brand Report**

- Total order masuk hari ini
- Detail PO Baru (list)
- PO Dalam Proses (list)
- PO Selesai (list)
- PO Terlambat (list)
- Deadline Mendekati (list)
- Revenue: hari ini, minggu ini, bulan ini
- Customer baru & aktif
- Top produk & Kategori

**E. Admin Keuangan Report**

- Ringkasan pemasukan & pengeluaran (harian/bulanan)
- Laba bersih
- Daftar Refund menunggu divalidasi
- Daftar Invoice menunggu divalidasi
- Invoice jatuh tempo
- Cash flow status

### 15.3.3 AI-Powered Smart Alert & Intelligence

#### A. **Automated Alert Types**

| Alert Type | Threshold | Channel | AI Enhancement |
|------------|-----------|---------|----------------|
| Deadline Mendekat | < 3 hari | WhatsApp, Telegram | Prioritas berdasarkan nilai order |
| Rijek Tinggi | > 5% | WhatsApp, Telegram | Analisa trend dan root cause |
| Invoice Overdue | > jatuh_tempo | WhatsApp, Telegram | Customer risk assessment |
| User Tidak Aktif | > 7 hari | WhatsApp, Telegram | Re-engagement suggestions |
| Capacity Warning | > 80% workload | WhatsApp, Telegram | Production bottleneck prediction |

#### B. **AI-Enhanced Reporting**

- **Smart Summaries**: AI-generated insights dan rekomendasi
- **Trend Analysis**: Prediksi order masuk berdasarkan historical data
- **Customer Insights**: Analisa perilaku pelanggan per reseller
- **Performance Optimization**: Saran improvement berdasarkan data

#### C. **Context-Aware Notifications**

- **Role-Based Content**: Pesan berbeda untuk Brand vs Production admin
- **Urgency Levels**: Priority-based delivery (immediate, scheduled, summary)
- **Multi-Channel Fallback**: WhatsApp → Telegram → Email jika gagal
- **Template Personalization**: Dinamis berdasarkan brand/reseller context

### 15.3.4 Laporan Processing Flow

```
Scheduler (Cron Job)
    ↓
Generate Report Data
    ↓
AI Analysis (Summary & Insights)
    ↓
Format Message (WhatsApp/Telegram)
    ↓
Send via Sidobe API / Telegram Bot API
    ↓
Log Activity
```

---

# 16. Audit Trail System

## 16.1 Activity Log

| Field | Tipe | Keterangan |
|-------|------|-------------|
| id | UUID | Primary key |
| user_id | UUID | FK ke user |
| brand_id | UUID | FK ke brand |
| activity | ENUM | create, read, update, delete, export, login, logout |
| module | VARCHAR(100) | Nama modul |
| record_id | UUID | ID record yang diakses |
| changes | JSON | Perubahan data (before/after) |
| ip_address | VARCHAR(45) | IP user |
| user_agent | TEXT | Browser/device |
| created_at | TIMESTAMP | |

## 16.2 Login History

| Field | Tipe | Keterangan |
|-------|------|-------------|
| id | UUID | Primary key |
| user_id | UUID | FK ke user |
| brand_id | UUID | FK ke brand |
| login_at | TIMESTAMP | |
| logout_at | TIMESTAMP | |
| ip_address | VARCHAR(45) | |
| user_agent | TEXT | |
| status | ENUM | success, failed, blocked |

## 16.3 Report Access Log

| Field | Tipe | Keterangan |
|-------|------|-------------|
| id | UUID | Primary key |
| user_id | UUID | FK ke user |
| brand_id | UUID | FK ke brand |
| report_type | VARCHAR(100) | Jenis laporan |
| filters | JSON | Filter yang digunakan |
| exported | BOOLEAN | Apakah di-export |
| created_at | TIMESTAMP | |

---

# 17. Sistem Pengaturan

## 17.1 Pengaturan Brand

| Field | Tipe | Keterangan |
|-------|------|-------------|
| nama_brand | VARCHAR(100) | Nama brand |
| kode | VARCHAR(20) | Kode singkat |
| tagline | VARCHAR(255) | Tagline brand (display di header invoice) |
| deskripsi | TEXT | Deskripsi brand |
| logo | FILE | Logo brand |
| favicon | FILE | Favicon |
| email | VARCHAR(255) | Email resmi |
| no_hp | VARCHAR(20) | No. HP |
| alamat | TEXT | Alamat |
| timezone | VARCHAR(50) | Default: Asia/Jakarta |
| currency | VARCHAR(10) | Default: IDR |
| is_active | BOOLEAN | |

## 17.2 Pengaturan WhatsApp

| Field | Tipe | Keterangan |
|-------|------|-------------|
| brand_id | UUID | |
| api_key | VARCHAR(255) | Sidobe API Key |
| sender_id | VARCHAR(50) | Sender ID |
| default_group | VARCHAR(100) | Group ID tujuan |
| auto_forward | BOOLEAN | |
| webhook_url | VARCHAR(255) | Callback URL |

### 17.2.1 Pengaturan Laporan Otomatis

| Field | Tipe | Keterangan |
|-------|------|-------------|
| enable_auto_report | BOOLEAN | Aktifkan laporan otomatis |
| report_schedule | JSON | Jadwal: harian, mingguan, bulanan |
| superadmin_recipients | JSON | Array nomor/grup WhatsApp untuk Superadmin |
| produksi_recipients | JSON | Array nomor/grup WhatsApp untuk Admin Produksi |
| brand_report_recipients | JSON | Array nomor/grup WhatsApp untuk Admin Brand |
| keuangan_recipients | JSON | Array nomor/grup WhatsApp untuk Admin Keuangan |
| report_types | JSON | Jenis laporan: superadmin, produksi, brand, keuangan |
| daily_report_time | TIME | Waktu laporan harian (default: 08:00) |
| weekly_report_day | ENUM | Hari laporan mingguan (default: Senin) |
| monthly_report_date | INT | Tanggal laporan bulanan (default: 1) |

### 17.2.2 Template Laporan WhatsApp

**Catatan:** Semua template laporan WhatsApp dapat dikonfigurasi untuk menyertakan detail list PO, bukan hanya angka statistik.

#### A. Superadmin Report (Semua Brand)

```
📊 LAPORAN SUPERADMIN - {tanggal}

🏢 TOTAL BRAND: {x} brand aktif

📦 ORDER KESELURUHAN:
• Masuk: {x} order
• Proses: {y} order
• Selesai: {z} order
• Delay: {w} order

⚠️ PO TERLAMBAT (List):
{no_po_terlambat_1} - {pelanggan} - {status}
{no_po_terlambat_2} - {pelanggan} - {status}

⏰ DEADLINE < 3 HARI (List):
{no_po_1} - {pelanggan} - {deadline}
{no_po_2} - {pelanggan} - {deadline}
{no_po_3} - {pelanggan} - {deadline}

💰 REVENUE TOTAL:
• Hari ini: {Rp x}
• Minggu ini: {Rp y}
• Bulan ini: {Rp z}

🏆 TOP BRAND:
{list brand dengan performa terbaik}

📈 GROWTH:
{brand dengan growth tertinggi}

⚠️ ALERT:
{list kondisi kritis jika ada}

🤖 AI INSIGHT:
{ai_generated_insight}
```

#### B. Admin Produksi Report

```
📊 LAPORAN PRODUKSI - {brand_name}
📅 {tanggal}

📦 STATUS ORDER:
• Dalam Proses: {x} order
• Masuk Hari Ini: {x} order
• Selesai Hari Ini: {x} order

📋 DETAIL PO MASUK HARI INI:
1. {no_po} - {pelanggan} - {produk} x{qty}
2. {no_po} - {pelanggan} - {produk} x{qty}
3. {no_po} - {pelanggan} - {produk} x{qty}

⏰ DEADLINE < 7 HARI (List):
{no_po_1} - {pelanggan} - {deadline} (H-{hari})
{no_po_2} - {pelanggan} - {deadline} (H-{hari})
{no_po_3} - {pelanggan} - {deadline} (H-{hari})

⚠️ PO TERLAMBAT (List):
{no_po} - {pelanggan} - {hari_lambat} hari

🔴 PO EXPRESS (List):
{no_po} - {pelanggan} - PRIORITAS

⚠️ RIJEK:
• Rate: {x}%
• Total Hari Ini: {x} pcs

👷 OPERATOR PERFORMANCE:
{top performers}

⚠️ ALERT:
{list alert}

🤖 AI INSIGHT:
{ai_summary}
```

#### C. Admin Brand Report

```
📊 LAPORAN HARIAN - {brand_name}
📅 {tanggal}

📦 ORDER:
• Masuk: {x} order
• Proses: {y} order
• Selesai: {z} order
• Delay: {w} order

📋 DETAIL PO TERBARU:
1. {no_po} - {pelanggan} - {produk} x{qty}
2. {no_po} - {pelanggan} - {produk} x{qty}
3. {no_po} - {pelanggan} - {produk} x{qty}

⏰ DEADLINE MENDEKAT:
{no_po} - {deadline} - {pelanggan}

⚠️ PO TERLAMBAT:
{no_po} - {pelanggan} - {status}

💰 REVENUE:
• Hari ini: {Rp x}
• Minggu ini: {Rp y}
• Bulan ini: {Rp z}

👥 PELANGGAN:
• Baru: {x}
• Total Aktif: {x}

📊 TOPS:
• Produk Terlaris: {nama}
• Kategori Favorit: {nama}

🤖 AI INSIGHT:
{ai_summary}
```

#### D. Owner Report (Per Brand)

```
📊 LAPORAN OWNER - {brand_name}
📅 {tanggal}

🏢 BRAND: {brand_name}

📦 STATUS ORDER:
• Masuk: {x} order
• Proses: {y} order
• Selesai: {z} order
• Delay: {w} order

📋 PO BARU (HARI INI):
1. {no_po} - {pelanggan} - {produk} x{qty}
2. {no_po} - {pelanggan} - {produk} x{qty}
3. {no_po} - {pelanggan} - {produk} x{qty}

📋 PO SEDANG PRODUKSI:
1. {no_po} - {pelanggan} - {progress}
2. {no_po} - {pelanggan} - {progress}

📋 PO SELESAI (HARI INI):
1. {no_po} - {pelanggan} - {produk} x{qty}
2. {no_po} - {pelanggan} - {produk} x{qty}

⏰ DEADLINE < 3 HARI:
{no_po_1} - {pelanggan} - {deadline}
{no_po_2} - {pelanggan} - {deadline}

⚠️ PO TERLAMBAT:
{no_po} - {pelanggan} - {hari_lambat} hari

💰 REVENUE:
• Hari ini: {Rp x}
• Minggu ini: {Rp y}
• Bulan ini: {Rp z}

📈 PERTUMBUHAN:
• Vs Kemarin: {%}
• Vs Minggu Lalu: {%}

👥 PELANGGAN & TOPS:
• Baru Bulan Ini: {x}
• Total Aktif: {x}
• Produk Terlaris: {nama}
• Customer Terbesar: {nama}

⚠️ ALERT:
{list alert kritis}

🤖 AI INSIGHT:
{ai_summary}
```

#### E. Admin Keuangan Report

```
📊 LAPORAN KEUANGAN - {brand_name}
📅 {tanggal}

💰 RINGKASAN HARI INI:
• Pemasukan: {Rp x}
• Pengeluaran: {Rp y}
• Laba Bersih: {Rp z}

📈 RINGKASAN BULAN INI:
• Pemasukan: {Rp x}
• Pengeluaran: {Rp y}
• Laba Bersih: {Rp z}

📋 DAFTAR REFUND BARU:
1. {no_po} - {pelanggan} - {Rp refund}
2. {no_po} - {pelanggan} - {Rp refund}

🧾 INVOICE MENUNGGU VALIDASI:
• {x} Invoice (Total: {Rp x})

⚠️ INVOICE JATUH TEMPO:
1. {no_po} - {pelanggan} - {Rp tagihan}

🤖 AI INSIGHT:
{ai_summary}
```

---

## 17.3 Pengaturan Telegram Bot

| Field | Tipe | Keterangan |
|-------|------|-------------|
| brand_id | UUID | |
| bot_token | VARCHAR(255) | Token dari BotFather |
| default_chat_id | VARCHAR(50) | Chat ID tujuan |
| enable_notifications | BOOLEAN | Aktifkan notifikasi Telegram |
| webhook_url | VARCHAR(255) | Callback URL untuk receive message |

### 17.3.1 Konfigurasi BotFather

1. Buka @BotFather di Telegram
2. Buat bot baru dengan command /newbot
3. Dapatkan API Token dari BotFather
4. Dapatkan Chat ID dengan bot @userinfobot atau @getidsbot
5. Aktifkan privacy mode dan allow group members

### 17.3.2 Pengaturan Laporan Otomatis via Telegram

| Field | Tipe | Keterangan |
|-------|------|-------------|
| enable_auto_report | BOOLEAN | Aktifkan laporan otomatis via Telegram |
| superadmin_chat_ids | JSON | Array Chat ID untuk Superadmin |
| produksi_chat_ids | JSON | Array Chat ID untuk Admin Produksi |
| brand_chat_ids | JSON | Array Chat ID untuk Admin Brand |
| keuangan_chat_ids | JSON | Array Chat ID untuk Admin Keuangan |
| owner_chat_ids | JSON | Array Chat ID untuk Owner |

### 17.3.3 Template Laporan Telegram

**Catatan:** Template Telegram juga mendukung detail list PO sama seperti WhatsApp.

```
📊 LAPORAN SUPERADMIN - {tanggal}

🏢 TOTAL BRAND: {x} brand aktif

📦 ORDER:
• Masuk: {x} | Proses: {y} | Selesai: {z} | Delay: {w}

⚠️ PO TERLAMBAT:
{no_po_1} - {pelanggan}
{no_po_2} - {pelanggan}

⏰ DEADLINE < 3 HARI:
{no_po_1} - {pelanggan} - {deadline}
{no_po_2} - {pelanggan} - {deadline}

💰 REVENUE: Hari ini: {Rp x}
🏆 TOP BRAND: {list}
⚠️ ALERT: {list}
🤖 AI INSIGHT: {ai_summary}
```

---

## 17.4 Pengaturan AI (Global - Superadmin Only)

**Prinsip Terpusat:** Pengaturan API AI (Gemini) dikelola sepenuhnya oleh Superadmin secara global. Brand dan Reseller tidak perlu melakukan input API Key sendiri; mereka tinggal menggunakan fitur AI yang sudah aktif di dashboard masing-masing.

| Field | Tipe | Keterangan |
|-------|------|-------------|
| gemini_api_keys | JSON | Array of API keys (Load Balanced) |
| enabled_features | JSON | Feature flags (Global) |
| model | VARCHAR(50) | Model selection (e.g., Gemini 1.5 Pro/Flash) |
| temperature | DECIMAL(2,2) | AI temperature |
| max_tokens | INT | Max response tokens |

### 17.4.1 AI-Powered Reporting Features

**A. Data Analysis**

- Analisa tren penjualan
- Prediksi order masuk
- Analisa customer behavior
- Product popularity trends
- Revenue forecasting

**B. Natural Summary**

- Rangkuman kondisi sistem dalam bahasa Indonesia natural
- Format: "Hari ini ada X order masuk, Y order selesai, Z order dalam proses..."
- Include key insights untuk setiap laporan

**C. Smart Alert**

- Warning: Deadline < 3 hari
- Warning: Rijek rate > 5%
- Warning: Overdue invoice
- Warning: User tidak aktif > 7 hari

### 17.4.2 AI Report Generation

| Feature | Description |
|---------|-------------|
| Trend Analysis | Analisa data historis untuk prediksi |
| Anomaly Detection | Deteksi keanehan dalam data |
| Recommendation | Saran perbaikan berdasarkan data |
| Summary Generation | Generate ringkasan natural language |

## 17.5 Pengaturan Sistem

| Field | Tipe | Default |
|-------|------|--------|
| nama_aplikasi | VARCHAR(100) | NISReport |
| timezone | VARCHAR(50) | Asia/Jakarta |
| currency | VARCHAR(10) | IDR |
| bahasa | VARCHAR(10) | id_ID |
| date_format | VARCHAR(50) | DD MMMM YYYY |
| time_format | VARCHAR(50) | HH:mm |
| default_brand | UUID | - |
| notification_channel | ENUM (whatsapp, telegram, both) | whatsapp |
| telegram_enabled | BOOLEAN | false |
| whatsapp_enabled | BOOLEAN | true |

### 17.5.1 Pengaturan Channel Notifikasi (Superadmin)

Superadmin dapat memilih channel notifikasi yang akan digunakan:

| Opsi | Deskripsi |
|------|-------------|
| whatsapp | Hanya menggunakan WhatsApp (Sidobe) |
| telegram | Hanya menggunakan Telegram Bot |
| both | Menggunakan keduanya (WhatsApp & Telegram) |

Pengaturan ini dapat dikonfigurasi per brand atau global untuk semua brand.

## 17.6 Pengaturan Indikator Selesai & Status PO (Superadmin Only)

Superadmin dapat mengatur **di progress mana PO dianggap selesai** dan konfigurasi indikator pengiriman. Ini penting karena **selesai produksi ≠ dikirim** (bisa belum lunas, ada rijek, dll).

### 17.6.1 Konfigurasi Indikator Selesai

| Field | Tipe | Keterangan |
|-------|------|-------------|
| brand_id | UUID | FK ke brand (bisa per brand atau global) |
| completion_progress_id | UUID | FK ke master progress → tahapan mana yang dianggap "selesai produksi" |
| packing_progress_id | UUID | FK ke master progress → tahapan packing (indikator siap kirim) |
| require_full_payment | BOOLEAN | Default: false. Apakah harus lunas sebelum dianggap siap kirim |
| require_no_reject | BOOLEAN | Default: false. Apakah harus tanpa rijek aktif untuk siap kirim |

**Aturan Bisnis:**

- **Selesai Produksi**: PO dianggap selesai produksi ketika mencapai tahapan yang diset di `completion_progress_id`
- **Siap Dikirim**: PO dianggap siap dikirim ketika sudah di tahapan `packing_progress_id` DAN memenuhi syarat tambahan (lunas/tanpa rijek jika diaktifkan)
- **Sudah Dikirim**: PO yang sudah benar-benar dikirim ke customer (status manual oleh admin)

### 17.6.2 Status PO Lifecycle (Extended)

| Status | Warna | Indikator | Keterangan |
|--------|-------|-----------|-------------|
| PO Masuk | #3B82F6 (Blue) | 📦 | Order baru masuk, belum diproses |
| On Progress | #F59E0B (Amber) | ⚙️ | Sedang dalam proses produksi |
| Selesai Produksi | #22C55E (Green) | ✅ | Produksi selesai (sesuai setting completion_progress_id) |
| Siap Dikirim | #06B6D4 (Cyan) | 📦✈️ | Sudah packing + memenuhi syarat kirim |
| Sudah Dikirim | #8B5CF6 (Purple) | 🚚 | Barang sudah dikirim ke customer |
| Delay | #EF4444 (Red) | ⚠️ | Melewati deadline |
| Hold | #F97316 (Orange) | ⏸️ | Ditahan (belum lunas/rijek belum selesai) |

### 17.6.3 Widget Hitungan PO (Dashboard)

Berdasarkan konfigurasi di atas, dashboard menampilkan **widget terpisah**:

| Widget | Hitungan | Keterangan |
|--------|----------|-------------|
| PO Selesai Produksi | COUNT WHERE status = selesai_produksi | PO yang sudah selesai produksi |
| PO Siap Dikirim | COUNT WHERE status = siap_dikirim | PO sudah packing + syarat terpenuhi |
| PO Sudah Dikirim | COUNT WHERE status = sudah_dikirim | PO yang benar-benar sudah dikirim |
| PO Hold | COUNT WHERE status = hold | PO yang ditahan (belum lunas/rijek) |

**Setiap widget menampilkan angka + list 10 PO terbaru** (lihat Poin 4 - Dashboard Detailing).

---

# 18. Spesifikasi Teknis

## 18.1 Tech Stack

| Komponen | Teknologi |
|----------|------------|
| Framework | Laravel 12 |
| PHP | PHP 8.2+ |
| Database | MySQL 8.0 / MariaDB |
| Frontend | React + Vite + Tailwind CSS + shadcn/ui |
| Charts | ApexCharts + Chart.js dengan React wrapper |
| Export | Laravel Excel (Maatwebsite), DomPDF |
| Auth | Laravel Breeze + Sanctum |
| **Realtime** | **Laravel Reverb (WebSocket) + Laravel Echo** |
| **Broadcasting** | **Laravel Broadcasting (Pusher protocol via Reverb)** |
| API Docs | Swagger/OpenAPI |
| Testing | PHPUnit |
| Notification | WhatsApp (Sidobe), Mail |

### 18.1.1 Realtime & Broadcasting (NEW)

Perubahan operasional penting harus **realtime tanpa reload web**:

| Event | Channel | Broadcast To | Keterangan |
|-------|---------|-------------|-------------|
| Progress Updated | `po.{order_id}` | Admin Produksi, Admin Brand | Progress berubah → dashboard & statistik update otomatis |
| PO Status Changed | `brand.{brand_id}` | Semua user brand | Status PO berubah → widget angka update |
| Payment Received | `finance.{brand_id}` | Finance, Owner | Pembayaran masuk → dashboard finance update |
| PO Locked/Unlocked | `po.{order_id}` | Admin Brand, Admin Produksi | Lock status berubah |
| PO Changed (After Unlock) | `po.{order_id}` | Admin Produksi | Detailing PO diubah |
| New Reject Added | `production.{brand_id}` | Admin Produksi | Rijek baru ditambahkan |
| Deadline Alert | `alerts.{brand_id}` | Semua user brand | Deadline mendekat/terlewat |
| Dashboard Stats Refresh | `dashboard.{brand_id}` | All roles | Statistik dashboard berubah |

**Implementasi:**

- Gunakan **Laravel Reverb** sebagai WebSocket server (self-hosted, tanpa third-party)
- **Laravel Echo** di frontend untuk subscribe ke channels
- Broadcast hanya untuk event operasional penting, bukan semua model event
- Dashboard widgets auto-refresh tanpa reload halaman untuk counter dan list yang terdampak
- Notifikasi in-app muncul realtime (toast/bell icon)

## 18.2 Library yang Dibutuhkan

### 18.2.1 Composer Packages

```json
{
  "require": {
    "laravel/framework": "^12.0",
    "laravel/breeze": "^2.0",
    "laravel/sanctum": "^4.0",
    "barryvdh/laravel-dompdf": "^3.0",
    "spatie/laravel-permission": "^6.0",
    "maatwebsite/excel": "^3.1",
    "intervention/image": "^3.0",
    "laravel/telescope": "^5.0",
    "laravel/reverb": "^1.0",
    "darkaonline/l5-swagger": "^9.0",
    "simplesoftwareio/simple-qrcode": "^4.2"
  },
  "require-dev": {
    "phpunit/phpunit": "^11.0",
    "mockery/mockery": "^1.6",
    "fakerphp/faker": "^1.23"
  }
}
```

### 18.2.2 NPM Packages

```json
{
  "dependencies": {
    "@vitejs/plugin-react": "^4.2.0",
    "vite": "^5.0.0",
    "typescript": "^5.4.0",
    "react": "^18.2.0",
    "react-dom": "^18.2.0",
    "tailwindcss": "^3.4.0",
    "autoprefixer": "^10.4.0",
    "postcss": "^8.4.0",
    "axios": "^1.6.0",
    "lucide-react": "^0.468.0",
    "class-variance-authority": "^0.7.0",
    "clsx": "^2.1.0",
    "tailwind-merge": "^2.2.0",
    "apexcharts": "^3.45.0",
    "react-apexcharts": "^1.4.1",
    "chart.js": "^4.4.0",
    "react-chartjs-2": "^5.2.0",
    "laravel-echo": "^1.16.0",
    "pusher-js": "^8.4.0"
  }
}
```

## 18.3 Struktur Database Lengkap

### Tabel Utama

```
brands
users
user_brand_access
password_resets
sessions

-- Master Data (Brand & Reseller)
products
sublims
motifs
polas
bonuses
kategori_orders
sumber_orders
promos
customers
customer_types
progress

-- Master Data Finance
kategori_pemasukan
kategori_pengeluaran
bank_accounts

-- Order
orders
order_items
order_attributes
order_history
order_payments
order_progress_details
po_lock_status
po_change_logs

-- Finance & Billing
invoices
refunds
pemasukan
pengeluaran
payments

-- Production
rijeks

-- Reports & Analytics
reports_access_log

-- Notifications
notification_settings
notifications

-- Audit
activity_logs
login_history
```

---

# 19. API Endpoints Structure

```
/api/v1/auth
  - POST /login
  - POST /logout
  - POST /forgot-password
  - POST /reset-password
  - GET /user ( Sanctum SPA user)

/api/v1/brands
  - GET /
  - POST /
  - GET /{id}
  - PUT /{id}
  - DELETE /{id}
  - GET /{id}/switch

/api/v1/resellers
  - GET /
  - POST /
  - GET /{id}
  - PUT /{id}
  - DELETE /{id}

/api/v1/users
  - GET /
  - POST /
  - GET /{id}
  - PUT /{id}
  - DELETE /{id}
  - PUT /{id}/password

/api/v1/master
  - /products
  - /sublims
  - /motifs
  - /polas
  - /bonuses
  - /kategori-order
  - /sumber-order
  - /promos
  - /customers
  - /customer-types
  - /progress
  - /lokasi (API Indonesia)

/api/v1/master-finance
  - /kategori-pemasukan
  - /kategori-pengeluaran
  - /bank-accounts

/api/v1/orders
  - GET /
  - POST /
  - POST /{id}/publish (publish PO ke produksi, tanpa auto-create invoice)
  - POST /{id}/repeat
  - GET /{id}
  - PUT /{id}
  - DELETE /{id}*
  - PUT /{id}/progress
  - POST /{id}/riyek
  - GET /export
  - GET /statistics (dashboard data)

/api/v1/orders/{no_po}/track -> Public

/api/v1/finance
  - /invoices
  - POST /invoices/from-po/{order_id}
  - /invoices/{id}/validate
  - /invoices/{id}/publish
  - /pemasukan
  - /pengeluaran
  - /payments

/api/v1/refunds
  - GET /
  - POST /
  - GET /{id}
  - PUT /{id}
  - POST /{id}/publish
  - POST /{id}/reject

/api/v1/reports
  - /penjualan
  - /kategori
  - /promo
  - /pelanggan
  - /wilayah
  - /rijek
  - /finance
  - /refunds
  - /pemasukan
  - /pengeluaran
  - /laba-rugi
  - /cash-flow
  - /comparison (Superadmin)
  - GET /export

/api/v1/notifications
  - GET /
  - POST /mark-read
  - POST /settings

/api/v1/tools
  - /whatsapp-reply
  - /copywriter
  - /order-summarizer
  - /order-formatter
  - /complaint-handler
  - /chatbot

/api/v1/dashboard
  - GET /admin-brand
  - /admin-produksi
  - /admin-keuangan
  - /reseller
  - /superadmin
  - /owner

/api/v1/audit
  - GET /activity
  - GET /login-history
  - GET /report-access
```

---

# 20. Frontend Pages Structure

```
/login
/forgot-password
/reset-password/{token}

/dashboard
  - Dashboard Utama (Admin Brand)
  - Dashboard Owner (Consolidated)
  - Dashboard Admin Produksi
  - Dashboard Admin Keuangan
  - Dashboard Reseller

/master
  - /master/produk
  - /master/produk/create
  - /master/produk/{id}/edit
  - /master/sublim
  - /master/motif
  - /master/pola
  - /master/bonus
  - /master/kategori-order
  - /master/promo
  - /master/sumber-order
  - /master/pelanggan
  - /master/type-pelanggan
  - /master/progress

/finance
  - /finance/invoices
  - /finance/pemasukan
  - /finance/pengeluaran
  - /finance/refunds
  - /finance/kategori-pemasukan
  - /finance/kategori-pengeluaran

/order
  - /order
  - /order/create (Draft)
  - /order/{id}
  - /order/{id}/edit*
  - /order/track -> public

/produksi
  - /produksi/kanban
  - /produksi/gantt
  - /produksi/progress
  - /produksi/riyek

/laporan
  - /laporan/penjualan
  - /laporan/kategori
  - /laporan/promo
  - /laporan/pelanggan
  - /laporan/wilayah
  - /laporan/rijek
  - /laporan/finance (Laba Rugi, Cash Flow)
  - /laporan/refund
  - /laporan/comparison

/superadmin
  - /superadmin/brands
  - /superadmin/users
  - /superadmin/settings/ai
  - /superadmin/comparison

/pengaturan
  - /pengaturan/brand
  - /pengaturan/whatsapp
  - /pengaturan/notifikasi

/tools
  - /tools/whatsapp-reply
  - /tools/copywriter
  - /tools/order-summarizer
  - /tools/order-formatter
  - /tools/complaint-handler

/notifikasi
  - /notifikasi
```

---

# 21. Acceptance Criteria

## 21.1 Autentikasi

- [ ] User bisa login dengan email & password
- [ ] User dengan multi-brand bisa switch brand
- [ ] Reset password berfungsi dengan link email
- [ ] RBAC bekerja sesuai role
- [ ] Login history tersimpan

## 21.2 Master Data

- [ ] CRUD master data berfungsi
- [ ] Data tersedia dengan lokasi Indonesia API
- [ ] Soft delete diterapkan
- [ ] Import/Export Excel

## 21.3 Order

- [ ] Input order baru berhasil
- [ ] Setelah produksi edit/hapus diblokir
- [ ] Update progress hanya oleh produksi admin
- [ ] Tracking public berfungsi
- [ ] Auto generate no_po
- [ ] Publish PO langsung masuk dashboard produksi
- [ ] Publish PO tidak otomatis membuat invoice

## 21.4 Dashboard & Widget

- [ ] Semua widget statistics berfungsi
- [ ] Diamond & Gold display
- [ ] ApexCharts visualizations
- [ ] Gantt/Timeline chart
- [ ] Kanban board
- [ ] Real-time updates

## 21.5 Reports

- [ ] Semua filter berfungsi
- [ ] Export Excel berfungsi
- [ ] Export PDF berfungsi
- [ ] ApexCharts visualizations
- [ ] Comparison report

## 21.6 Admin Tools

- [ ] WhatsApp Reply tool berfungsi
- [ ] Copywriter tool berfungsi
- [ ] Order Summarizer berfungsi
- [ ] Order Formatter berfungsi
- [ ] Complaint Handler berfungsi

## 21.7 Notifications

- [ ] WhatsApp notification
- [ ] Email notification
- [ ] Public tracking menggunakan Nomor PO dengan rate limit
- [ ] Auto triggers

## 21.8 Audit Trail

- [ ] Activity logging
- [ ] Login history
- [ ] Report access log
- [ ]View by admin

## 21.9 Superadmin

- [ ] Comparison brand dapat dilakukan
- [ ] Semua laporan bisa di-comparison
- [ ] Global view works

## 21.10 Integrasi

- [ ] WhatsApp Sidobe terintegrasi
- [ ] AI Gemini terintegrasi dengan load balancer
- [ ] Semua sistem berfungsi

---

# 22. Timeline Pengembangan

## 22.1 Phase 1: Foundation (Minggu 1-2)

- Setup project Laravel 12
- Setup database schema lengkap
- Auth system & RBAC
- Brand management
- User management

## 22.2 Phase 2: Master Data (Minggu 3)

- CRUD master data
- Integration lokasi API
- Master data UI
- Import/Export

## 22.3 Phase 3: Order System (Minggu 4)

- Input order
- Protection rules
- Progress management
- Rijek management
- Public tracking

## 22.4 Phase 4: Dashboard & Visualisasi (Minggu 5)

- All dashboard widgets
- ApexCharts integration
- Diamond & Gold
- Kanban board
- Gantt chart

## 22.5 Phase 5: Reports (Minggu 6)

- All report types
- Export functionality
- Comparison reports

## 22.6 Phase 6: Admin Tools (Minggu 7)

- WhatsApp integration
- AI integration
- All tool features

## 22.7 Phase 7: Testing & Deployment (Minggu 8)

- Unit testing
- Integration testing
- Bug fixing
- Deployment

---

# 23. Catatan

- Semua input/output dalam bahasa Indonesia
- Format tanggal: DD MMMM YYYY (contoh: 15 April 2026)
- Format mata uang: Rp 1.000.000
- Timezone default: Asia/Jakarta (WIB)
- Menggunakan shadcn/ui untuk konsistensi desain
- Gunakan ApexCharts sebagai chart utama, Chart.js sebagai backup
- UI/UX: Clean, modern, responsive
- Mobile-friendly design

---

**Document Version:** 2.1  
**Last Updated:** 13 Mei 2026  
**Status:** Final Review - FULL BRD
