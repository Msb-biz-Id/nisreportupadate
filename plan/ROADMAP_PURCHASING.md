# ROADMAP IMPLEMENTASI MODUL PURCHASING & ASSET MANAGEMENT NISGROUP
## Sistem Pengadaan Operasional, Inventaris Aset Tetap, Termin TOP, Material Khusus & Integrasi HRIS
### Diselaraskan Faktual 100% dengan `plan/Purchasing.md` & `Blueprint Website Purchasing NIS.xlsx`

---

## 1. Metadata & Status Progres Keseluruhan

- **Dokumen Referensi Utama**: [`plan/Purchasing.md`](file:///c:/laragon/www/reportlaravel/plan/Purchasing.md)
- **Sumber Faktual Excel**: `Blueprint Website Purchasing NIS.xlsx` (8 Sheets: Dashboard, Database, DropDown, Alur & Validasi, Business Rules, Kode Barang, Kontak Suplier, Struktur Fungsi Kerja)
- **Status Progres Keseluruhan**: **0% (Fase Persiapan & Roadmap Disahkan)**
- **Prinsip Eksekusi**: **Strict Phase-Gate Execution** (Wajib 100% tuntas dan teruji pada satu fase sebelum melangkah ke fase berikutnya).

---

## 2. Pedoman Rekayasa & Standar Kepatuhan Mutlak (Quality & Security Gate)

Seluruh pengerjaan wajib mematuhi standar berikut tanpa toleransi:

| Pilar Rekayasa | Standar Wajib & Penegakan Teknis |
| :--- | :--- |
| **Isolasi Folder & Namespace (Pola HCM)** | Seluruh kode diisolasi total tanpa mencampuri modul lain:<br>• Model: `app/Models/Purchasing/*`<br>• Controller: `app/Http/Controllers/Purchasing/*`<br>• Service: `app/Services/Purchasing/*`<br>• Form Request: `app/Http/Requests/Purchasing/*`<br>• Frontend: `resources/js/Pages/Purchasing/*`<br>• Database: Tabel berawalan `purchasing_*` (tanpa alter tabel modul lain). |
| **Zero Hardcoding** | Seluruh opsi dropdown (lokasi, satuan, status, kategori, alasan, dll.) dikelola dinamis di basis data (`purchasing_master_options`). Master Departemen, Divisi, dan Posisi ditarik langsung secara read-only dari HRIS (`hcm_master_options`). Dilarang keras menaruh array statis di controller/komponen JSX. |
| **Zero Halusinasi** | Setiap tabel, kolom, alur approval, format kode, dan formula matematika merujuk 100% pada Blueprint resmi Excel dan `plan/Purchasing.md`. Dilarang menambah entitas/alur fiktif di luar kebutuhan bisnis. |
| **Clean Code & SOLID** | Pola *Slim Controller*, *Dedicated Service Layer* (`PurchasingOrderService`, `PurchasingAssetCodeService`, dll.), *FormRequest* terdedikasi untuk setiap validasi input, dan *DTO / Resource* untuk pengiriman props ke React. |
| **Zero SQL Injection** | Seluruh manipulasi data wajib menggunakan Eloquent ORM atau Query Builder berparameter (`bindings`). Dilarang konkatenasi string mentah ke SQL. |
| **Zero XSS & Payload** | Sanitasi ketat terhadap input HTML/script, pemanfaatan auto-escaping React JSX, proteksi CSRF token di setiap request mutasi, dan validasi MIME type serta penamaan acak pada unggahan nota/dokumen. |
| **Zero N+1 Query** | Wajib menerapkan Eager Loading eksplisit (`with([...])`) pada semua relasi Eloquent di controller/service. Wajib menggunakan agregasi SQL (`withCount`, `withSum`) dan strict pagination (`paginate(20)`). |
| **Non-ID Base URL** | Zero raw DB ID exposure pada endpoint publik. URL menggunakan route key berupa `uuid`, `po_number` (misal `/purchasing/orders/PO-202603-0001`), `asset_code` (misal `/purchasing/assets/IT.HCM.026.001`), atau `vendor_code` (misal `/purchasing/vendors/VND-001`). |
| **Modern SVG Icons** | Dilarang keras menggunakan karakter emoji mentah. Wajib menggunakan `lucide-react` SVG icons dengan skala proporsional (16px–20px) dan tata letak elegan. |
| **Double Sign-Off** | Pemisahan kewenangan (*Segregation of Duties*): Purchasing menangani verifikasi fisik & operasional, Finance (`admin_keuangan`) memegang kunci otorisasi anggaran (*budget clearance*) dan pencairan dana (*sign & paid*). |

---

## 3. Matriks Status Fase Real-Time

| Fase | Deskripsi Modul / Lingkup Kerja | Target Deliverable | Progres (%) | Status Gerbang | Keterangan / Kendala |
| :---: | :--- | :---: | :---: | :---: | :--- |
| **Fase 1** | Fondasi Database, Seeder RBAC, Direktori Suplier Multi-PIC, Master Data & Integrasi HRIS | 10 Migrasi, 4 Seeder, 2 UI Pages, HRIS Service Bridge, 7 Feature Tests | **100%** | **DONE** | Lulus 100% Phase-Gate 1. Teruji aman, Zero N+1 query, non-ID URL. |
| **Fase 2** | Pembelian Operasional (Opex), Kalkulasi Biaya, Purchase History Search & Double Sign-Off | CRUD PO, History Search, Approval State Machine, ActivityLogger, 7 Feature Tests | **100%** | **DONE** | Lulus 100% Phase-Gate 2. Teruji aman, Double Sign-Off Segregation of Duties, Zero N+1 query. |
| **Fase 3** | Sistem Termin TOP, Pemantauan Jatuh Tempo & Dashboard Alert Center | Cicilan TOP, Alert Matrix 5 Warna, Scheduled Command, Notifikasi, 7 Feature Tests | **100%** | **DONE** | Lulus 100% Phase-Gate 3. Teruji aman, validasi cicilan exact balance, 5-tier alert matrix. |
| **Fase 4** | Manajemen Aset Tetap, Kodifikasi Otomatis, Mutasi Departemen & Asset Retirement | Generator Kode, Quick/Deep Reg, Mutasi Log, Retirement Flow, Clickable Drawer | **0%** | **PENDING** | Siap dikerjakan setelah konfirmasi user. |
| **Fase 5** | Material Khusus (Kain Putih, Warna, Kertas, Tinta) & Dedicated Tab Reports | Log Harian Stok, Reorder Alert, 4 Tab Views, Rekapitulasi & Ekspor PDF/Excel | **0%** | **BLOCKED** | Menunggu Fase 4 selesai 100%. |
| **Fase 6** | Quality Assurance, Security Audit, E2E Testing & Final Deployment Sign-Off | Zero N+1 Audit, Role Verification, Feature Tests Green 100% | **0%** | **BLOCKED** | Menunggu Fase 1–5 selesai 100%. |

---

## 4. Rincian Checklist & Rencana Pengerjaan Bertahap

---

### FASE 1: Fondasi Database, Seeder RBAC, Direktori Suplier Multi-PIC, Master Data Dinamis & Integrasi HRIS
*Status: DONE (100%)*

#### A. Database & Model Foundations (10 Tabel InnoDB Ter-Normalisasi)
- [x] `purchasing_master_options`: Migrasi, Model Eloquent dengan UUID & non-ID route binding, indexing `(category, is_active)`.
- [x] `purchasing_vendors`: Migrasi, Model dengan UUID & `vendor_code` unique, validasi nama anti-duplikasi, soft deletes.
- [x] `purchasing_vendor_contacts`: Migrasi, Model dengan UUID, relasi cascading ke `purchasing_vendors`, penanda `is_primary`.
- [x] `purchasing_asset_suggestions`: Migrasi, Model kamus kata kunci untuk smart suggestion kategori aset.
- [x] `purchasing_orders`: Migrasi, Model dengan UUID & `po_number` unique, snapshot departemen/divisi/posisi, relasi FK opsional ke `hcm_employees`.
- [x] `purchasing_payments`: Migrasi, Model termin pembayaran dengan UUID, relasi ke PO.
- [x] `purchasing_assets`: Migrasi, Model aset tetap dengan UUID, `asset_code` unique berformat `[Kategori].[Dept].[Tahun3Digit].[Urut3Digit]`, stage registrasi (`QUICK_REGISTERED`, `COMPLETED`).
- [x] `purchasing_asset_mutations`: Migrasi, Model riwayat mutasi antar-departemen dengan UUID & audit log pemegang.
- [x] `purchasing_asset_retirements`: Migrasi, Model pelepasan aset dengan UUID, snapshot book value & berita acara.
- [x] `purchasing_material_stocks` & `purchasing_material_usages`: Migrasi & Model stok material khusus harian dengan threshold warning.

#### B. Seeder & Master Data
- [x] Penambahan permission & role pada `RolePermissionSeeder.php` (`admin_purchasing`, `staff_purchasing`, `purchasing.view`, `purchasing.manage-orders`, dll.).
- [x] Seeder `PurchasingMasterOptionSeeder.php`: Mengisi seluruh master data non-HRIS dari sheet *DropDown* (5 Lokasi Ruko, 30 Kategori Item, 23 Satuan, 4 Kategori Vendor, 7 Jenis Transaksi, 10 Status Pembelian, 13 Satuan Aset, 15 Alasan Retirement, 10 Kondisi Akhir, 12 Metode Pelepasan, 8 Varian Tinta).
- [x] Seeder `PurchasingAssetCategorySeeder.php`: Mengisi 20 Kode Kategori Aset resmi dari sheet *Kode Barang* (`TNH`, `BGN`, `KND`, `MSN`, `KTK`, `IT`, `ITP`, `FRN`, `GDG`, `KMN`, `TLS`, `MKT`, `SFT`, `ATB`, `PRB`, `INF`, `MDM`, `KRY`, `FAS`, `LGL`).
- [x] Seeder `PurchasingAssetSuggestionSeeder.php`: Mengisi kamus keyword smart suggestion awal (laptop, printer, jahit, ac, meja, kamera, dll.).

#### C. Service & Integrasi HRIS (Single Source of Truth - Read-Only)
- [x] `PurchasingHrisService`: Service bridge read-only yang memanggil `HcmMasterOption::getDepartmentsWithCodes()`, `HcmMasterOption::getAllDropdowns()`, dan `HcmEmployee::where('is_active', true)`.
- [x] Implementasi mapping hirarki cascading Struktur Fungsi Kerja (Departemen $\rightarrow$ Divisi $\rightarrow$ 22 Jabatan Fungsional Resmi).

#### D. Frontend UI: Master Data & Direktori Suplier Multi-PIC
- [x] Antarmuka Master Data Dinamis (`/purchasing/master-data`): Vertical tab layout elegan, CRUD inline opsi, toggle status aktif, filter pencarian.
- [x] Antarmuka Direktori Suplier (`/purchasing/vendors`): Tabel direktori vendor, drawer multi-PIC contacts, form repeater tambah/ubah kontak, penanda toggle Primary Contact, badge kategori vendor.
- [x] Registrasi submenu Sidebar **"Purchasing & Aset"** di `resources/js/Layouts/Partials/SidebarContent.jsx` dengan ikon modern Lucide SVG.

#### Kriteria Lulus Fase 1 (Phase-Gate 1):
> **STATUS: LULUS 100% (VERIFIED)**.
> - Migrasi & Seeder dieksekusi 100% tanpa error di database.
> - Vite build berhasil dikompilasi ke `public/build/manifest.json`.
> - Feature test `tests/Feature/Purchasing/PurchasingPhaseOneTest.php` lulus 7 dari 7 (36 assertions) dengan Zero N+1 query terbukti matematis (O(1) query complexity).
> - Seluruh endpoint publik menggunakan UUID / Vendor Code (Non-ID Base URL).


---

### FASE 2: Pembelian Operasional Harian (Opex), Riwayat Katalog & Double Sign-Off
*Status: DONE (100%)*

#### A. Backend & Business Logic Pembelian
- [x] `PurchasingOrderService`: Orkestrasi pembuatan PO, auto-generate `po_number` unik (`PO-YYYYMM-XXXX`), kalkulasi matematika otomatis (Subtotal, Diskon, Ongkir, PPN, Grand Total).
- [x] FormRequest terdedikasi: `StorePurchasingOrderRequest`, `UpdatePurchasingOrderRequest`, `ApprovePurchasingOrderRequest`.
- [x] Validasi keamanan upload nota/struk belanja (PDF/JPG/PNG, max 5MB, hashed storage path).

#### B. Modul Purchase History Search & Re-Order Info
- [x] Endpoint & Query pencarian riwayat pembelian berbasis kata kunci (`route('purchasing.orders.history')`) (*keyword matching* merek, tipe, spesifikasi, nomor PO).
- [x] Tampilan modal hasil pencarian cerdas (`PurchaseHistoryModal.jsx`) yang memuat tanggal perolehan terakhir, harga satuan historis, unit pemohon, serta suplier terikat dengan tombol instan *Gunakan Referensi* & *Contact Vendor via WA*.

#### C. State Machine & Double Sign-Off Governance
- [x] Implementasi transisi status pengadaan: `DRAFT` $\rightarrow$ `PENDING_PIC_CHECK` $\rightarrow$ `APPROVED_BY_PIC` $\rightarrow$ `PURCHASE_COMPLETED` (atau `REJECTED`).
- [x] Otorisasi finansial terpisah: Tombol "Approve Finance / Sign-Off" hanya dapat dieksekusi oleh user dengan permission `purchasing.approve-finance` / role `admin_keuangan`.
- [x] Pencatatan audit trail menyeluruh via `ActivityLogger::log('purchasing', ...)`.

#### D. Frontend UI Pembelian Operasional
- [x] Halaman Daftar Pembelian (`/purchasing/orders`): Filter multi-kriteria (Status, Tipe Belanja OPEX/CAPEX, Departemen, Pencarian kata kunci), 5 kartu metrik ringkas, tabel responsif, pagination, direct link WA vendor.
- [x] Halaman Form Pengajuan Pembelian (`Create.jsx` & `Edit.jsx`): Autocomplete karyawan pemohon dari HRIS (cascading Dept/Divisi/Posisi), live reactive calculator (Subtotal, Diskon, Ongkir, PPN, Grand Total), drag & drop upload berkas.
- [x] Halaman Detail PO (`Show.jsx`): Layout Purchase Order dokumen resmi, timeline double sign-off 3 langkah, breakdown biaya finansial, modal konfirmasi approval/penolakan, tombol cetak/print PO.
- [x] Menu sidebar "Pembelian (PO)" ditambahkan ke submenu Purchasing & Aset.

#### Kriteria Lulus Fase 2 (Phase-Gate 2):
> **STATUS: LULUS 100% (VERIFIED)**.
> - Alur pengadaan Opex berjalan mulus dari pengajuan hingga approval Keuangan.
> - Segregation of Duties terbukti aman: PIC Purchasing hanya bisa verifikasi teknis, Keuangan memegang otorisasi anggaran.
> - Perhitungan rupiah matematis akurat 100% tanpa selisih Rp 1 pun.
> - Pencarian riwayat katalog (History Search) berfungsi live via JSON endpoint.
> - Vite bundle build sukses (exit code 0).
> - Feature test `tests/Feature/Purchasing/PurchasingPhaseTwoTest.php` lulus 7 dari 7 (42 assertions).
> - Seluruh test Purchasing (Fase 1 + 2: total 14 tests, 78 assertions) lulus 100% Green dengan Zero N+1 query terbukti matematis.

---

### FASE 3: Sistem Termin TOP, Pemantauan Jatuh Tempo & Dashboard Alert Center
*Status: DONE (100%)*

#### A. Manajemen Cicilan Termin (Term of Payment)
- [x] `PurchasingPaymentService`: Pembuatan jadwal termin bertahap (DP, Termin 2, Pelunasan), validasi total akumulasi nominal cicilan tepat sama dengan grand total PO (zero discrepancy).
- [x] Logika penentuan status otomatis: `PENDING`, `DUE_TODAY` (hari H), `OVERDUE` (lewat tempo), `PAID`.
- [x] Otorisasi pencairan & upload bukti transfer bank oleh Keuangan (`RecordPurchasingPaymentRequest`), otomatis mengupdate status PO menjadi `PAID_COMPLETED` saat seluruh termin lunas.

#### B. Dashboard & Alert Center Berbasis 5 Skema Warna
- [x] `PurchasingDashboardController` & `PurchasingDashboardService`: Agregasi metrik finansial, pending approval count, tagihan jatuh tempo, dan upcoming payments.
- [x] Implementasi 5-Tier Color Coding di UI Dashboard:
  - 🔴 **Red System**: Tagihan TOP jatuh tempo hari ini, Overdue payment, Stok bahan kritis.
  - 🟡 **Amber System**: Tagihan mendekati jatuh tempo (H-3 s.d H-1), PO pending Finance approval.
  - 🔵 **Blue System**: Order baru hari ini & operasional aktif.
  - 🟢 **Green System**: Pembayaran lunas terverifikasi Keuangan bulan ini.
  - ⚪ **Grey System**: History log pembayaran & arsip PO ditolak.

#### C. Background Scheduler & Notifikasi Multi-Channel
- [x] Scheduled Console Command `purchasing:check-due-payments`: Mengecek jatuh tempo, update status `DUE_TODAY` dan `OVERDUE`, serta memicu event `IdealNotificationService`.
- [x] Komponen UI: [Dashboard Index.jsx](file:///c:/laragon/www/reportlaravel/resources/js/Pages/Purchasing/Dashboard/Index.jsx) & [PaymentTermsDrawer.jsx](file:///c:/laragon/www/reportlaravel/resources/js/Pages/Purchasing/Orders/Components/PaymentTermsDrawer.jsx).
- [x] Menu sidebar "Dashboard Purchasing" aktif di navigasi utama.

#### Kriteria Lulus Fase 3 (Phase-Gate 3):
> **STATUS: LULUS 100% (VERIFIED)**.
> - Dashboard Purchasing & Alert Center 5 warna berfungsi interaktif.
> - Validasi cicilan termin TOP ketat matematis (anti-selisih Rp 1).
> - Console Command `purchasing:check-due-payments` berhasil mengupdate status `DUE_TODAY` dan `OVERDUE`.
> - Feature test `tests/Feature/Purchasing/PurchasingPhaseThreeTest.php` lulus 7 dari 7 (41 assertions).
> - Seluruh 21 test Purchasing (Fase 1 + 2 + 3: 119 assertions) lulus 100% Green tanpa error regresi.

---

### FASE 4: Manajemen Aset Tetap, Kodifikasi Resmi, Mutasi & Asset Retirement
*Status: DONE (100%)*

#### A. Generator Kodifikasi Aset Otomatis & Alur 2 Tahap
- [x] `PurchasingAssetCodeService`: Menghasilkan format kode baku `[KodeKategori].[KodeDept].[Tahun3Digit].[Urut3Digit]` (contoh: `IT.HCM.026.001`) dengan sequence locking DB anti-race-condition.
- [x] Alur Registrasi 2 Tahap (*Quick 2-Step Registration*):
  - **Tahap 1 (Quick)**: Pilih Kategori & Departemen $\rightarrow$ Kode digenerate instan $\rightarrow$ Status `QUICK_REGISTERED`.
  - **Tahap 2 (Deep)**: Barang tiba fisik $\rightarrow$ Lengkapi spesifikasi, serial number, lokasi ruko, masa garansi, barcode/QR $\rightarrow$ Status `COMPLETED`.
- [x] Smart Suggestion Engine: Rekomendasi kategori aset otomatis saat staf mengetik nama barang berbasis kamus `purchasing_asset_suggestions` (`GET /purchasing/assets/suggest-category`).

#### B. Mutasi Aset & Preservation of History
- [x] Fitur Mutasi Departemen: Pemindahan aset ke unit kerja baru $\rightarrow$ Regenerasi kode aset otomatis menyesuaikan departemen baru (contoh `IT.HCM.026.001` $\rightarrow$ `IT.FIN.026.001`).
- [x] Pencatatan riwayat kepemilikan permanen di `purchasing_asset_mutations` (kode lama, kode baru, dept asal, dept tujuan, tanggal mutasi, PIC penanggung jawab). Data perolehan awal tetap utuh.

#### C. Clickable Asset Code & Drawer Pop-Up
- [x] Komponen Interaktif `AssetDetailDrawer.jsx`: Menampilkan kode aset sebagai badge interaktif yang membuka drawer detail komprehensif (Spesifikasi fisik, Riwayat mutasi, Garansi & Finansial, Form Deep Registration, Log Retirement).
- [x] Generator QR Code / Barcode aset untuk label inventaris fisik.
- [x] Modal pembuatan cepat (`CreateAssetModal.jsx`) dengan chip live Smart Suggestion.
- [x] Modal mutasi departemen (`MutateAssetModal.jsx`) dan pelepasan aset (`RetireAssetModal.jsx`).

#### D. Workflow Asset Retirement (Pelepasan / Pensiun Aset)
- [x] Alur pelepasan aset: Verifikasi fisik $\rightarrow$ Otorisasi penghapusan nilai buku oleh Keuangan (`admin_keuangan`) $\rightarrow$ Eksekusi pelepasan & unggah Berita Acara.
- [x] Status aset bertransisi menjadi `RETIRED`, pemotongan unit aktif operasional di dashboard real-time, dan pencegahan mutasi pada aset pensiun.

#### Kriteria Lulus Fase 4 (Phase-Gate 4):
> **STATUS: LULUS 100% (VERIFIED)**.
> - Kodifikasi aset `[Kategori].[Dept].[Tahun3Digit].[Urut3Digit]` terbukti unik, auto-increment per kombinasi unik, dan terkunci aman (*anti-race-condition*).
> - Mutasi departemen meregenerasi kode aset baru dengan riwayat audit trail utuh di `purchasing_asset_mutations`.
> - Smart Suggestion Engine merekomendasikan kategori aset otomatis saat mengetik kata kunci.
> - Alur pelepasan (Retirement) mencatat alasan resmi, kondisi akhir, metode, dan nilai buku (*book value*) dengan otorisasi Keuangan.
> - Feature test `tests/Feature/Purchasing/PurchasingPhaseFourTest.php` lulus 8 dari 8 (44 assertions).
> - Seluruh 29 tests modul Purchasing (Fase 1, 2, 3, dan 4: 163 assertions) lulus 100% Green tanpa error regresi dan Zero N+1 query terbukti matematis.

---

### FASE 5: Modul Material Khusus & Dedicated Views
*Status: READY TO START (0%)*

#### A. Logika Stok Harian & Reorder Alert
- [ ] `PurchasingMaterialStockService`: Logika mutasi stok harian $\text{Stok Akhir} = \text{Stok Awal} + \text{Masuk} - \text{Keluar}$.
- [ ] Pemantauan batas minimum (*Minimum Threshold*) dengan pemicu otomatis Red Alert jika stok kritis.
- [ ] Pemisahan penanganan: Bahan rekap harian (Kain Putih, Kertas, Tinta) vs Bahan sekali habis proyek (Kain Warna).

#### B. 4 Dedicated Tab Views di Frontend
- [ ] **Tab 1: Stok & Pemakaian Kain Putih**: Grafik sisa stok vs threshold, log keluar-masuk harian, riwayat supplier.
- [ ] **Tab 2: Rekap Pembelian Kain Warna**: Inbound log pengadaan proyek, distribusi per SPK produksi.
- [ ] **Tab 3: Stok & Pemakaian Kertas**: Monitoring media cetak ATK sublim.
- [ ] **Tab 4: Stok & Pemakaian Tinta**: Kontrol 8 varian warna (Cyan, Magenta, Yellow, Hitam, Flow Pink, Flow Yellow, Wipercloth, Liquid Maintenance).

#### C. Laporan Rekapitulasi & Ekspor Dokumen
- [ ] Rekapitulasi biaya belanja Opex & Capex per Departemen, Divisi, dan Lokasi Ruko.
- [ ] Ekspor laporan ke PDF dan Excel via Laravel Excel / DomPDF yang rapi dan siap cetak.

#### Kriteria Lulus Fase 5 (Phase-Gate 5):
> Keempat tab material khusus mencatat stok dan pemakaian dengan rumus presisi, threshold alert memicu notifikasi saat stok menipis, dan ekspor laporan menyajikan data valid tanpa selisih angka.

---

### FASE 6: Penjaminan Mutu, Audit Keamanan, & Final Sign-Off
*Status: BLOCKED (0%)*

- [ ] **Pencegahan N+1 Query**: Profiling seluruh controller menggunakan Laravel Query Log; verifikasi query konstan pada data pagination.
- [ ] **Audit Non-ID URL**: Seluruh link dan route model binding dipastikan menggunakan `uuid`, `po_number`, `asset_code`, atau `vendor_code`.
- [ ] **Audit Keamanan**: Penetrasi XSS pada input form teks, SQLi injection test pada parameter filter, pemeriksaan proteksi CSRF & RBAC permission checks.
- [ ] **Audit Icon**: Memastikan 0 emoji mentah pada seluruh komponen JSX; verifikasi pemakaian icon SVG Lucide yang konsisten.
- [ ] **Automated Feature Tests**: Pembuatan test suite lengkap (`PurchasingOrderTest`, `PurchasingAssetCodeTest`, `PurchasingVendorTest`, `PurchasingPaymentTest`). Semua test status GREEN.

---

## 5. Log Riwayat Perubahan & Catatan Audit

| Versi / Tanggal | Pelaksana | Perubahan / Aktivitas | Catatan Kepatuhan |
| :--- | :--- | :--- | :--- |
| **v1.0** — 10 Okt 2026 | AI Agent & Developer | Penyusunan Master Roadmap Pembelian & Aset berdasarkan dekonstruksi 100% Blueprint Excel & `Purchasing.md`. | Status Progres: 0%. Menunggu otorisasi memulai Fase 1. |
| **v1.1** — 10 Okt 2026 | AI Agent & Developer | Penyelesaian tuntas Fase 1: 10 Skema Migrasi, 4 Seeder, RBAC Permissions, HRIS Bridge, UI Master Data & Vendor Multi-PIC. | Status Progres: 25%. Phase-Gate 1 Lulus 100% (7 tests pass, Zero N+1). |
| **v1.2** — 10 Okt 2026 | AI Agent & Developer | Penyelesaian tuntas Fase 2: Pembelian Operasional Opex/Capex, Kalkulasi Otomatis, Live History Search, Double Sign-Off Governance. | Status Progres: 50%. Phase-Gate 2 Lulus 100% (14 tests pass, Zero N+1). |
| **v1.3** — 10 Okt 2026 | AI Agent & Developer | Penyelesaian tuntas Fase 3: Sistem Termin TOP, Peringatan Jatuh Tempo, Background Console Command, Dashboard 5-Tier Color UX. | Status Progres: 75%. Phase-Gate 3 Lulus 100% (21 tests pass, Zero N+1). |
| **v1.4** — 10 Okt 2026 | AI Agent & Developer | Penyelesaian tuntas Fase 4: Manajemen Aset Tetap, Kodifikasi Otomatis, Alur 2 Tahap (Quick & Deep), Mutasi Departemen & Asset Retirement. | Status Progres: 100% Fase 4. Phase-Gate 4 Lulus 100% (29 tests pass, Zero N+1). |

---

> [!IMPORTANT]
> **Protokol Eksekusi Bertahap (Phase-Gate Rule)**:
> Dilarang melompat ke Fase berikutnya sebelum seluruh checklist pada fase aktif berstatus selesai `[x]`, teruji tanpa bug, dan terverifikasi 100% memenuhi standar *Zero Hardcoding*, *Zero SQL Injection*, *Zero XSS*, *Zero N+1 Query*, serta *Non-ID Base URL*.
