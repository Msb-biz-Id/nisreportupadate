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
| **Fase 1** | Fondasi Database, Seeder RBAC, Direktori Suplier Multi-PIC, Master Data & Integrasi HRIS | 10 Migrasi, 4 Seeder, 2 UI Pages, HRIS Service Bridge | **100%** | **DONE** | Teruji aman, Zero N+1 query, non-ID URL. |
| **Fase 2** | Pembelian Operasional (Opex), Kalkulasi Biaya, Purchase History Search & Double Sign-Off | CRUD PO, History Search, Approval State Machine, ActivityLogger | **100%** | **DONE** | Teruji aman, Double Sign-Off Segregation of Duties, Zero N+1 query. |
| **Fase 3** | Sistem Termin TOP, Pemantauan Jatuh Tempo & Dashboard Alert Center | Cicilan TOP, Alert Matrix 5 Warna, Scheduled Command, Notifikasi | **100%** | **DONE** | Teruji aman, validasi cicilan exact balance, 5-tier alert matrix. |
| **Fase 4** | Manajemen Aset Tetap, Kodifikasi Otomatis, Mutasi Departemen & Asset Retirement | Generator Kode, Quick/Deep Reg, Mutasi Log, Retirement Flow, Clickable Drawer | **100%** | **DONE** | Teruji aman, format [Kategori].[Dept].[Tahun3Digit].[Urut3Digit]. |
| **Fase 5** | Material Khusus (Kain Putih, Warna, Kertas, Tinta) & Dedicated Tab Reports | Log Harian Stok, Reorder Alert, 4 Tab Views, Rekapitulasi & Ekspor PDF/Excel | **100%** | **DONE** | Teruji aman, UI Laporan, ekspor PDF & Excel verified. |
| **Fase 6** | Quality Assurance, Security Audit, E2E Testing & Final Deployment Sign-Off | Zero N+1 Audit, Role Verification, Feature Tests Green 100% | **100%** | **DONE** | Automated Test Suite `tests/Feature/Purchasing/PurchasingEndToEndTest.php` (7 tests, 41 assertions) 100% Green. |

---

### FASE 5: Modul Material Khusus & Dedicated Views
*Status: DONE (100%)*

#### A. Logika Stok Harian & Reorder Alert
- [x] Logika mutasi stok harian $\text{Stok Akhir} = \text{Stok Awal} + \text{Masuk} - \text{Keluar}$ pada `PurchasingMaterialController`.
- [x] Pemantauan batas minimum (*Minimum Threshold*) dengan pemicu otomatis Red Alert jika stok kritis.
- [x] Pemisahan penanganan: Bahan rekap harian (Kain Putih, Kertas, Tinta) vs Bahan sekali habis proyek (Kain Warna).

#### B. 4 Dedicated Tab Views di Frontend
- [x] **Tab 1: Stok & Pemakaian Kain Putih**: Sisa stok vs threshold, log keluar-masuk harian, riwayat supplier.
- [x] **Tab 2: Rekap Pembelian Kain Warna**: Inbound log pengadaan proyek, distribusi per SPK produksi.
- [x] **Tab 3: Stok & Pemakaian Kertas**: Monitoring media cetak ATK sublim.
- [x] **Tab 4: Stok & Pemakaian Tinta**: Kontrol 8 varian warna (Cyan, Magenta, Yellow, Hitam, Flow Pink, Flow Yellow, Wipercloth, Liquid Maintenance).

#### C. Laporan Rekapitulasi & Ekspor Dokumen
- [x] Rekapitulasi biaya belanja Opex & Capex per Departemen, Divisi, dan Lokasi Ruko di `resources/js/Pages/Purchasing/Reports/Index.jsx`.
- [x] Ekspor laporan ke PDF (`resources/views/pdf/purchasing/report.blade.php`) dan Excel (`PurchasingReportExport.php`).
- [x] Registrasi menu navigasi di `SidebarContent.jsx`.

#### Kriteria Lulus Fase 5 (Phase-Gate 5):
> **STATUS: LULUS 100% (VERIFIED)**.
> - Keempat tab material khusus mencatat stok dan pemakaian dengan rumus presisi.
> - Threshold alert memicu notifikasi saat stok menipis.
> - Ekspor laporan Excel (.xlsx) dan PDF resmi berjalan mulus.
> - Automated test suite `PurchasingEndToEndTest.php` lulus 7 dari 7 test (41 assertions).

---

## 5. Log Riwayat Perubahan & Catatan Audit

| Versi / Tanggal | Pelaksana | Perubahan / Aktivitas | Catatan Kepatuhan |
| :--- | :--- | :--- | :--- |
| **v1.0** — 10 Okt 2026 | AI Agent & Developer | Penyusunan Master Roadmap Pembelian & Aset berdasarkan dekonstruksi 100% Blueprint Excel & `Purchasing.md`. | Status Progres: 0%. |
| **v1.1** — 10 Okt 2026 | AI Agent & Developer | Penyelesaian Fase 1–4: Migrasi Database, Model, Service, Controller, dan UI Frontend. | Status Progres: 80%. |
| **v1.2** — 10 Okt 2026 | AI Agent & Developer | Penyelesaian Fase 5: Modul Material Khusus, UI Laporan Rekapitulasi, Ekspor Excel/PDF, Sidebar Navigation, dan Perbaikan Validasi. | Status Progres: 100%. |
| **v1.3** — 10 Okt 2026 | AI Agent & Developer | Penyelesaian Test Suite Faktual: `tests/Feature/Purchasing/PurchasingEndToEndTest.php` (7 tests, 41 assertions) 100% LULUS (GREEN). | Status Progres: 100% Tuntas & Terverifikasi. |

---

> [!IMPORTANT]
> **Protokol Eksekusi Bertahap (Phase-Gate Rule)**:
> Dilarang melompat ke Fase berikutnya sebelum seluruh checklist pada fase aktif berstatus selesai `[x]`, teruji tanpa bug, dan terverifikasi 100% memenuhi standar *Zero Hardcoding*, *Zero SQL Injection*, *Zero XSS*, *Zero N+1 Query*, serta *Non-ID Base URL*.
