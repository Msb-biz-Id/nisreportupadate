# Rencana Pengembangan Sistem HRIS / HCM (Human Capital Management) NISGroup

Dokumen ini merupakan perencanaan teknis, arsitektur data, logika bisnis, dan alur operasional komprehensif implementasi modul **HRIS / HCM** pada platform NISReport. Dokumen ini diperbarui secara faktual 100% berdasarkan analisis mendalam terhadap **`Blueprint Website HCM NIS.xlsx`** (mencakup seluruh 5 sheet: *Dashboard*, *Alur & Validasi*, *Business Rules*, *Database*, dan *DropDown*).

---

## 1. Visi Arsitektur & Prinsip Tata Kelola

Sistem HCM NISGroup adalah sistem tata kelola sumber daya manusia dari hulu ke hilir (*hire-to-retire*) untuk industri manufaktur garmen dan operasional kantor pusat. Sistem ini mencakup seluruh tipe tenaga kerja (Managerial, Kontrak/PKWT, Borongan Produksi/Jahit/Potong, Harian, Freelance, dan Peserta Magang SMK) di bawah naungan entitas legal NISGroup (**CV Jersey Ekonomis**, **CV Apparel Allegiant**, **CV Bawang Merah**, dan **CV Bawang Putih**).

### A. Prinsip Double Sign-Off Governance (Pemisahan Tugas Finansial)
Semua transaksi keuangan kepegawaian (Lembur Mingguan & Uang Makan Bulanan) menganut prinsip pemisahan tugas (*Segregation of Duties*):
```
[Operasional / Lapangan] ──> Jam Lembur / Kehadiran Riil
                                      ↓
[HCM Admin / Gatekeeper Data] ─> Audit Absensi & Jam Riil ──> [Tombol: "Approve & Sign HCM"]
                                                                      ↓ (Status: APPROVED_BY_HCM)
                                                              [Data Terkunci untuk HCM]
                                                                      ↓
[Finance / Gatekeeper Dana]  ──> Audit Nominal Kas/Bank  ──> [Tombol: "Sign & Paid"]
                                                                      ↓ (Status: PAID_COMPLETED)
                                                              [Kunci Permanen / Read-Only]
```
1. **HCM Role (Gatekeeper Data)**:
   - Bertanggung jawab penuh atas validitas data input (absensi, pengajuan izin/cuti/sakit, jam lembur riil, dan rekap akumulasi hari hadir).
2. **Finance Role (Gatekeeper Dana)**:
   - Bertanggung jawab atas pencairan dana tunai/transfer perbankan berdasarkan data yang telah ditandatangani (*signed*) oleh HCM.
3. **Double Sign-Off Rule**:
   - Data pembayaran Lembur Mingguan dan Uang Makan Bulanan **tidak dianggap Closed/Selesai** jika Tim Keuangan belum melakukan pencairan fisik/transfer dan menekan tombol `[Sign & Paid]` / `[Sign & Mark as Paid]` di sistem. Pasca penandatanganan Finance, record berstatus `PAID_COMPLETED` dan terkunci permanen (*immutable*).

---

### B. Integrasi Navigasi Menu Sidebar "HCM Group"
Sistem HCM diintegrasikan ke dalam navigasi utama sistem (`SidebarContent.jsx`) sebagai kelompok menu tersendiri (**"HCM / Kepegawaian"**) yang sejajar dengan modul Operasional dan Keuangan:

```
[ SIDEBAR NISREPORT ]
├── Utama (Dashboard)
├── Administrasi (Brand, Target, User, Role)
├── Master Data (Produk, Pelanggan, Kategori)
├── Produksi (Order, Kanban, Tracking)
├── Keuangan (Arus Kas, Tagihan, Piutang)
│
└── 👥 KEPEGAWAIAN (Section Terpadu)
    ├── 📊 Dashboard                (route: 'hcm.dashboard')
    ├── 🗂️ Master Data              (route: 'hcm.master-data.index')
    ├── 👨‍💼 Master Karyawan & Magang (route: 'hcm.employees.index')
    ├── 📜 Kontrak & PKWT           (route: 'hcm.contracts.index')
    ├── 💰 Kompensasi & Gaji        (route: 'hcm.compensations.index')
    ├── ⏱️ Presensi & Ketidakhadiran (route: 'hcm.attendance.index')
    │   ├── Matrix Editor Absensi Harian (Bulk Logger)
    │   └── Pengajuan Cuti, Izin & Sakit
    ├── ⚡ Lembur Mingguan (Overtime)(route: 'hcm.overtime.index')
    ├── 🍽️ Uang Makan Bulanan       (route: 'hcm.meal-allowance.index')
    ├── 🎁 Reward & Penghargaan     (route: 'hcm.rewards.index')
    ├── 🎯 Loker & Rekrutmen         (route: 'hcm.recruitment.index')
    │   ├── Master Lowongan Kerja (Loker)
    │   ├── Pipeline Pelamar & Blacklist
    │   └── Laporan Performa Rekrutmen
    ├── 📁 Dokumen & Persuratan     (route: 'hcm.documents.index')
    │   ├── Dokumen Internal (Pengajuan RAB & Realisasi LPJ)
    │   ├── Korespondensi Eksternal (BPJS/Disnaker/Bank)
    │   └── Buku Agenda Penomoran Surat Resmi
    └── 📅 Kalender & Event Sosial  (route: 'hcm.calendar.index')
```

---

### C. Sinkronisasi RBAC & Sistem Notifikasi Existing (100% Selaras Arsitektur Sistem)

#### 1. Sinkronisasi Spatie Role & Permission (Selaras `RolePermissionSeeder.php` & `User.php`)
Sistem eksisting telah memiliki 7 role baku: `superadmin`, `owner`, `admin_brand`, `admin_reseller`, `admin_produksi`, `admin_keuangan`, dan `supervisor`. Integrasi modul Kepegawaian/HCM dirancang selaras dengan pola penamaan dan otorisasi eksisting:

- **Peran Pengguna (Roles)**:
  - **`admin_hcm`** *(Role Baru)*: Administrator utama Kepegawaian / HR Manager. Memiliki akses penuh ke seluruh modul Kepegawaian, otorisasi persetujuan cuti/izin, evaluasi kontrak PKWT, manajemen rekrutmen, Master Data dinamis, serta menandatangani `[Approve & Sign HCM]` untuk lembur dan uang makan.
  - **`staff_hcm`** *(Role Baru)*: Staf operasional kepegawaian. Bertugas melakukan input absensi harian massal (Bulk Matrix Logger), input berkas pelamar, input data lembur harian, pencatatan buku agenda surat, dan jadwal event kalender.
  - **`admin_keuangan`** *(Role Eksisting)*: Bertindak sebagai **Gatekeeper Dana** yang mengevaluasi antrean lembur mingguan dan uang makan bulanan yang telah disahkan HCM, lalu mengeksekusi penandatanganan `[Sign & Paid]`.
  - **`admin_produksi`** *(Role Eksisting)*: Memantau presensi tim produksinya di lantai pabrik dan mengajukan jam lembur regu kerja via fitur Bulk Overtime.
  - **`superadmin` & `owner`** *(Role Eksisting)*: Memiliki akses pengawasan menyeluruh terhadap dashboard kepegawaian, audit trail aktivitas, serta rekapitulasi pencairan kas yang telah disahkan.

- **Akses Lintas Entitas / Brand (`User::hasAccessToBrand`)**:
  Sebagaimana `admin_keuangan` dan `admin_produksi`, role `admin_hcm` dan `staff_hcm` ditambahkan ke dalam daftar role global pada method `hasAccessToBrand()` di model `User.php` agar dapat mengelola data karyawan lintas seluruh entitas hukum NISGroup (**CV Jersey Ekonomis**, **CV Apparel Allegiant**, **CV Bawang Merah**, dan **CV Bawang Putih**).

- **Daftar Izin Granular (`permissions`) Mengikuti Konvensi `<domain>.<action>`**:
  ```php
  // Permission Baru Modul Kepegawaian (HCM)
  'hcm.view',                   // Melihat menu dan dashboard Kepegawaian
  'hcm.manage-master',          // CRUD 22 kategori Master Data dinamis (Job Level, Divisi, Posisi, CV, dll.)
  'hcm.manage-employees',       // CRUD data master karyawan dan peserta magang SMK
  'hcm.manage-contracts',       // CRUD kontrak kerja PKWT dan evaluasi berkala
  'hcm.manage-compensation',    // Mengelola data gaji/honor dan riwayat kenaikan upah
  'hcm.manage-attendance',      // Akses Bulk Matrix Absensi Harian & approval cuti/izin/sakit
  'hcm.manage-overtime',        // Input jam lembur harian & penyusunan batch mingguan
  'hcm.sign-overtime',          // Otorisasi [Approve & Sign HCM] untuk mengunci lembur ke Keuangan
  'hcm.manage-meal-allowance',  // Rekapitulasi hari hadir, potongan kehadiran & hold logic uang makan
  'hcm.sign-meal-allowance',    // Otorisasi [Approve & Sign HCM] untuk mengunci uang makan ke Keuangan
  'hcm.manage-rewards',         // Mengelola rekapitulasi penyaluran reward/penghargaan karyawan
  'hcm.manage-recruitment',     // Mengelola master loker, pipeline pelamar, wawancara & blacklist
  'hcm.manage-documents',       // Mengelola arsip dokumen internal (RAB/LPJ) & eksternal (BPJS/Disnaker)
  'hcm.manage-agenda',          // Mengelola buku agenda penomoran surat resmi masuk/keluar
  'hcm.manage-events',          // Mengelola company calendar, agenda internal & undangan sosial
  'hcm.export-reports',         // Ekspor PDF Dossier Karyawan, slip lembur, rekap absensi, dan paklaring

  // Permission Tambahan untuk Divisi Keuangan
  'finance.sign-paid',          // Otorisasi [Sign & Paid] pencairan kas lembur & uang makan (diberikan ke admin_keuangan)
  ```

---

#### 2. Sinkronisasi Sistem Notifikasi (Selaras `SystemEventNotification.php` & `NotificationController.php`)
Sistem notifikasi kepegawaian memanfaatkan infrastruktur notifikasi bawaan platform:
- **Class Pengirim**: Menggunakan `App\Notifications\SystemEventNotification` yang berjalan secara asynchronous melalui antrean job (`ShouldQueue`).
- **Multi-Channel Dispatching**:
  - **In-App Database (`database`)**: Disimpan ke tabel `notifications` dan langsung tersinkronisasi ke dropdown lonceng di header aplikasi (`NotificationDropdown.jsx`) dan halaman riwayat notifikasi (`/notifications`).
  - **WhatsApp Channel (`whatsapp`)**: Diteruskan ke WhatsApp melalui `SidobeClient` menggunakan nomor seluler pada field `phone` tabel `users`.
  - **Telegram Channel (`telegram`)**: Diteruskan ke bot Telegram via `TelegramClient` menggunakan identifier pada field `telegram_chat_id` tabel `users`.
  - **Email (`mail`)**: Diteruskan via SMTP `ReportMail` sesuai pengaturan `SystemSetting`.
  - **Audio Alert**: Menggunakan parameter `'sound' => 'bell-chime'` yang secara bawaan memicu efek suara lonceng di antarmuka web.

- **Daftar Event Notifikasi Kepegawaian & Sasaran Penerima**:

  | Event Key | Trigger & Kondisi | Penerima (Target Role) | Payload Notifikasi | Channel Aktif |
  | :--- | :--- | :--- | :--- | :--- |
  | `hcm_probation_warning` | Masa training sisa H-7 s.d. H-3 (dijalankan via Scheduler harian) | `admin_hcm` | Title: *Evaluasi Probation Karyawan*<br>Body: *Sdri. Alisa Firda Riana – Masa training usai dalam 7 hari.*<br>Action: `/hcm/contracts` | In-App, WA, Telegram |
  | `hcm_contract_expired_warning` | Kontrak PKWT sisa H-60 dan H-30 (dijalankan via Scheduler harian) | `admin_hcm` | Title: *Peringatan Berakhirnya Kontrak PKWT*<br>Body: *Sdr. Ahmad Rizky – Kontrak habis dalam 30 hari. Perlu keputusan perpanjang/putus.*<br>Action: `/hcm/contracts` | In-App, WA, Telegram |
  | `hcm_unexcused_absence` | Pukul 08:30 WIB ada karyawan tidak clock-in tanpa surat izin/sakit | `admin_hcm` | Title: *Peringatan Mangkir (Unexcused Absence)*<br>Body: *2 Karyawan belum melakukan konfirmasi ketidakhadiran hari ini tanpa keterangan.*<br>Action: `/hcm/attendance` | In-App, Sound Bell |
  | `hcm_overtime_validation_reminder` | Jumat sore (H-1) & Sabtu pagi sebelum cut-off pencairan | `admin_hcm` | Title: *Pengingat Validasi Lembur Mingguan*<br>Body: *Mohon validasi total jam lembur minggu ini sebelum diteruskan ke Keuangan.*<br>Action: `/hcm/overtime` | In-App, WA |
  | `hcm_overtime_ready_to_pay` | Saat HCM menekan tombol `[Approve & Sign HCM]` pada batch lembur | `admin_keuangan` | Title: *Rekap Lembur Siap Dicairkan*<br>Body: *Rekap Lembur Minggu W36 telah disetujui HCM & siap dicairkan.*<br>Action: `/hcm/overtime` | In-App, WA, Telegram, Sound Bell |
  | `hcm_meal_allowance_ready_to_pay`| Saat HCM menekan tombol `[Approve & Sign HCM]` pada rekap uang makan | `admin_keuangan` | Title: *Rekap Uang Makan Siap Dibayarkan*<br>Body: *Rekap Uang Makan Periode [Bulan] telah disahkan HCM & siap diproses.*<br>Action: `/hcm/meal-allowance` | In-App, WA, Telegram |
  | `hcm_payout_completed` | Saat Keuangan menekan `[Sign & Paid]` | `admin_hcm`, `owner`, `superadmin` | Title: *Pencairan Kas Selesai*<br>Body: *Pencairan Lembur W36 sebesar Rp [Total] telah berstatus Lunas (PAID_COMPLETED).*<br>Action: `/hcm/overtime` | In-App, Email |

---

## 2. Modul Master Data HCM (Tab Menu Vertikal untuk Pengelolaan Dropdown Dinamis)

Berdasarkan lembar kerja **`DropDown `** pada Blueprint Excel, kebutuhan seluruh data pilihan/dropdown dikelompokkan ke dalam modul **Master Data HCM** (`/hcm/master-data`):
1. **Fokus Khusus Data Dropdown Dinamis**: Modul ini khusus difokuskan untuk mengelola seluruh opsi dropdown yang ada di sistem (seperti Job Level, Divisi, Posisi, Status Ketenagakerjaan, CV, Kategori Dokumen, dll.) agar dapat ditambah, diubah, atau dinonaktifkan secara mandiri oleh Admin HCM melalui panel admin tanpa perlu mengubah kode program (*zero code deployment*).
2. **Hak Akses Khusus**: Pengaturan CRUD Master Data HCM dibatasi hanya untuk role `hcm_manager` / `superadmin` (permission `hcm.manage-master-data`). Staf dan karyawan operasional hanya dapat membaca dan memilih opsi aktif saat mengisi formulir.

### Arsitektur Antarmuka: Split-Pane dengan Tab Menu Vertikal
Halaman Master Data HCM (`resources/js/Pages/Hcm/MasterData/Index.jsx`) mengadopsi tata letak **Vertical Tab Menu** (Sisi Kiri: Menu Kategori Dropdown, Sisi Kanan: Panel CRUD Data Opsi):

```
+---------------------------------------------------------------------------------------------------------------+
| 🗂️ MASTER DATA HCM (PENGELOLAAN DROPDOWN DINAMIS)                             [ + Tambah Data pada Kategori ]|
+----------------------------------------------------+----------------------------------------------------------+
| 🔍 [ Cari Kategori Master Data... ]                | 📌 Kategori: POSISI / JABATAN (Total: 29 Opsi Aktif)     |
+----------------------------------------------------+----------------------------------------------------------+
| 👥 KEPEGAWAIAN & STRUKTUR                          | 🔍 [ Cari nama posisi... ]     Filter: [ Semua Status v ]|
|  ├─ [👔] Job Level               (9)               +----+--------+--------------------+---------+--------+---+|
|  ├─ [🏢] Divisi                  (6)               | No | Urutan | Nama Posisi/Jabatan| Status  | Aksi   |   ||
|  ├─ [💼] Posisi / Jabatan        (29)  <-- AKTIF   +----+--------+--------------------+---------+--------+---+|
|  ├─ [🏷️] Status Ketenagakerjaan  (9)               | 1  | 1      | Finance            | [Aktif] | [Edit] [x]||
|  └─ [⚖️] Entitas Legal (CV)      (4)               | 2  | 2      | Accounting         | [Aktif] | [Edit] [x]||
| 📜 KONTRAK & KOMPENSASI                            | 3  | 3      | Jahit              | [Aktif] | [Edit] [x]||
|  ├─ [📑] Status Review Kontrak   (9)               | 4  | 4      | Potong Bahan       | [Aktif] | [Edit] [x]||
|  └─ [💵] Status Pengajuan/Honor  (9)               | 5  | 5      | Quality Control    | [Aktif] | [Edit] [x]||
| ⏱️ PRESENSI & OPERASIONAL                          +----+--------+--------------------+---------+--------+---+|
|  ├─ [📅] Kategori Kehadiran      (9)               |                                                          |
|  └─ [⚡] Jenis Hari Lembur       (2)               | [ Modal Form CRUD ]:                                     |
| 📁 DOKUMEN & PERSURATAN                            | - Nama / Label Opsi  : [ Input teks ]                    |
|  ├─ [📝] Dokumen Pengajuan       (4)               | - Kode / Identifier  : [ Auto / Custom Slug ]            |
|  ├─ [📊] Dokumen Realisasi       (3)               | - Urutan Tampil (No) : [ Input angka ]                   |
|  └─ [📂] Dokumen Kebijakan & SK  (9)               | - Keterangan         : [ Input textarea ]                |
| 🚪 OFFBOARDING & TRANSISI                          | - Status Aktif       : [ Toggle ON / OFF ]               |
|  ├─ [⌛] Kepatuhan Notice Period (5)               |                                      [ Batal ] [ Simpan ]|
|  ├─ [💰] Hak Sisa Karyawan       (4)               +----------------------------------------------------------+
|  ├─ [📦] Pengembalian Aset/Paklaring (3)           |                                                          |
|  └─ [📋] Status Clearance Sheet  (2)               |                                                          |
| 🎁 REWARD & DEMOGRAFI                              |                                                          |
|  ├─ [🏆] Status Penyaluran Reward(5)               |                                                          |
|  ├─ [🚻] Jenis Kelamin           (2)               |                                                          |
|  ├─ [🕌] Agama                   (6)               |                                                          |
|  ├─ [🎓] Tingkat Pendidikan      (10)              |                                                          |
|  ├─ [💍] Status Pernikahan       (4)               |                                                          |
|  └─ [👕] Ukuran Baju Seragam     (7)               |                                                          |
+----------------------------------------------------+----------------------------------------------------------+
```

### Rincian 22 Kategori Master Data HRIS (100% Sesuai Blueprint Excel):

| No | Kategori Data Master | Kelompok Grup | Opsi Default dari Blueprint Excel |
| :---: | :--- | :--- | :--- |
| 1 | **Job Level** | Kepegawaian & Struktur | Direksi, Manager, PIC, Supervisor, Leader, Staff, Trainee, Harian, Borongan |
| 2 | **Divisi** | Kepegawaian & Struktur | Keuangan, Human Capital Management, Marketing, Produksi, Media Internal, Media Eksternal |
| 3 | **Posisi / Jabatan** | Kepegawaian & Struktur | Finance, Accounting, Purchasing, Human Capital Management, Admin HCM, Marketing, Admin Brand, Designer, Produksi, Admin Produksi, Setting Printing, Potong Bahan, Press Sublime, Potong Pola, Jahit, Quality Control, Finishing (Press), Finishing (Steam), Finishing (Packing), Operasional, Media Internal, Media Spesialist, Publisher, Editor, Planner, Media Eksternal, Web Editor, Web Developer |
| 4 | **Status Ketenagakerjaan** | Kepegawaian & Struktur | Tetap (PKWTT), Kontrak (PKWT), PKWT Lanjutan, Freelance / Lepas, Trainee (Probation), Paruh Waktu (Part-Time), Harian, Borongan, Magang (Internship) |
| 5 | **Entitas Legal (CV)** | Kepegawaian & Struktur | CV Jersey Ekonomis, CV Apparel Allegiant, CV Bawang Merah, CV Bawang Putih |
| 6 | **Status Review Kontrak** | Kontrak & Legalitas | Aktif (Aman / Jauh dari Masa Berakhir), Mendekati Evaluasi (H-60 Kontrak Berakhir), Wajib Review & Tindak Lanjut (H-30 Kontrak Berakhir), Masa Tenggang / Proses Keputusan (H-14 s.d. Hari H), Pengajuan Perpanjangan (Renewal Process), Disetujui untuk Diperpanjang, Pengangkatan Menjadi Karyawan Tetap (Converted to Permanent), Kontrak Selesai & Tidak Diperpanjang (Non-Renewal / Offboarding), Resign / Berhenti atas Permintaan Sendiri selama Masa Kontrak |
| 7 | **Status Pengajuan / Honor** | Kompensasi & Gaji | Draft, Sedang Diajukan / Pending, Menunggu Persetujuan Atasan / Manager, Menunggu Verifikasi HR / Finance, Revisi / Perbaikan, Disetujui (Menunggu Masa Berlaku), Aktif / Berlaku Bulan Ini (Ready to Pay), Selesai (Paid), Berakhir / Expired |
| 8 | **Kategori Kehadiran** | Presensi & Absensi | Hadir, Terlambat, Pulang Cepat, Cuti, Izin, Sakit, Dinas Luar, Alpha/Mangkir, Libur/Cuti Bersama |
| 9 | **Jenis Hari Lembur** | Presensi & Lembur | Lembur Hari Kerja, Lembur Hari Libur |
| 10 | **Kategori Dokumen Pengajuan** | Dokumen Internal | Pengajuan RAB (Rencana Anggaran Biaya), Proposal Kegiatan / Acara, Pengajuan Pembelian Aset / Inventaris, Pengajuan Perjalanan Dinas / Surat Tugas |
| 11 | **Kategori Dokumen Realisasi** | Dokumen Internal | LPJ (Laporan Pertanggungjawaban) Kegiatan, Realisasi Pembelian Aset & Nota/Faktur Pembelanjaan, Laporan & Bukti Pengeluaran Perjalanan Dinas (Reimburse / Settlement) |
| 12 | **Dokumen Administratif & Kebijakan** | Dokumen Perusahaan | Surat Keputusan (SK) & Kebijakan Internal, Kontrak / Perjanjian Kerjasama (Vendor / Partner), Standard Operating Procedure (SOP), Surat Peringatan (SP 1 / SP 2 / SP 3), Surat Keputusan / Pemberitahuan PHK, Surat Pengalaman Kerja (Paklaring), Surat Pengumuman Internal (Mutasi, Promosi, atau Kebijakan), Berita Acara / Surat Klarifikasi, Surat Tugas & Perjalanan Dinas (SPPD) |
| 13 | **Kepatuhan Notice Period** | Offboarding & Terminasi | Sesuai Ketentuan (1 Bulan / Full Notice), Kurang dari Ketentuan (Short Notice < 1 Bulan), Tanpa Notice (Immediate / Walk Out), Garden Leave (Dibebastugaskan), Pemutusan oleh Perusahaan (Immediate Termination) |
| 14 | **Hak Karyawan (Sisa Hak)** | Offboarding & Terminasi | Lunas & Dibayarkan Penuh (Full Settlement Paid), Dipotong / Ada Penyesuaian (Deducted / Adjusted), Ditahan Sebagian (Partially Held), Belum Dibayarkan / Pending (Unpaid) |
| 15 | **Pengembalian Aset & Paklaring** | Offboarding & Terminasi | Lengkap & Terbit, Belum Lengkap / Aset Ditahan, Tidak Terbit |
| 16 | **Status Clearance Sheet** | Offboarding & Terminasi | Pending, Selesai (Clear) |
| 17 | **Status Penyaluran Reward** | Apresiasi & Reward | Belum Diterima, Sudah Diterima (Serah Terima Langsung), Sudah Ditransfer, Tertunda / Pending, Dibatalkan |
| 18 | **Jenis Kelamin** | Demografi Karyawan | Laki-Laki, Perempuan |
| 19 | **Agama** | Demografi Karyawan | Islam, Kristen, Katolik, Hindu, Buddha, Konghucu |
| 20 | **Pendidikan Terakhir** | Demografi Karyawan | SD / Sederajat, SMP / Sederajat, SMA / SMK / Sederajat, Diploma 1 (D1), Diploma 2 (D2), Diploma 3 (D3), Diploma 4 (D4), Strata 1 (S1), Strata 2 (S2), Strata 3 (S3) |
| 21 | **Status Pernikahan** | Demografi Karyawan | Belum Menikah, Menikah, Cerai Hidup, Cerai Mati |
| 22 | **Ukuran Baju Seragam** | Fasilitas & Atribut | S, M, L, XL, XXL, XXXL, XXXXL |

---

## 3. Kamus Data & Spesifikasi Detail Field (12 Modul Database Blueprint)

Berdasarkan lembar kerja **`Database`** pada Blueprint Excel, berikut adalah skema lengkap setiap tabel:

### A. Modul 1: Master Karyawan Data Umum (`hcm_employees`)
Menampung seluruh tenaga kerja Managerial, Kontrak, Borongan, dan Harian.

| Label Kolom UI | Nama Kolom DB | Tipe Data | Validasi / Aturan | Keterangan & Kontrol UI |
| :--- | :--- | :--- | :--- | :--- |
| **Kategori Tenaga Kerja** | `category` | `varchar(50)` | `required` | Managerial, Kontrak, Borongan, Harian, Magang |
| **Nama Lengkap** | `full_name` | `varchar(150)` | `required, string` | Nama lengkap resmi sesuai KTP |
| **Nama Panggilan** | `nickname` | `varchar(50)` | `required, string` | Nama panggilan akrab (untuk badge & matrix) |
| **Divisi** | `department` | `varchar(100)` | `required` | Pilihan dari dropdown Divisi |
| **Posisi / Jabatan** | `position` | `varchar(100)` | `required` | Pilihan dari dropdown Posisi |
| **Level / Jenjang** | `job_level` | `varchar(50)` | `required` | Pilihan dari dropdown Job Level |
| **No. HP Pribadi** | `phone_number` | `varchar(25)` | `required` | Nomor telepon seluler / WhatsApp |
| **Jenis Kelamin** | `gender` | `varchar(20)` | `required` | Laki-Laki / Perempuan |
| **Agama** | `religion` | `varchar(30)` | `required` | Islam, Kristen, Katolik, Hindu, Buddha, Konghucu |
| **Pendidikan Terakhir** | `education` | `varchar(50)` | `required` | Pilihan dari dropdown Pendidikan (SD s.d. S3) |
| **Status Pernikahan** | `marital_status`| `varchar(30)` | `required` | Belum Menikah, Menikah, Cerai Hidup, Cerai Mati |
| **Tempat Lahir** | `birth_place` | `varchar(100)` | `required` | Kabupaten / Kota kelahiran |
| **Tanggal Lahir** | `birth_date` | `date` | `required, date` | Tanggal lahir (Pemicu Alert Ultah H-3) |
| **Nomor KTP (NIK)** | `nik_ktp` | `varchar(20)` | `required, digits:16, unique` | 16 digit NIK KTP |
| **No. BPJS Kesehatan** | `bpjs_kesehatan_no` | `varchar(30)` | `nullable` | Nomor kartu BPJS Kesehatan |
| **No. BPJS Ketenagakerjaan** | `bpjs_ketenagakerjaan_no` | `varchar(30)` | `nullable` | Nomor KPJ BPJS-TK |
| **Ukuran Baju Seragam** | `shirt_size` | `varchar(10)` | `required` | Pilihan: S, M, L, XL, XXL, XXXL, XXXXL |
| **Alamat Domisili** | `address` | `text` | `required` | RT/RW, Desa/Kelurahan, Kecamatan, Kab/Kota, Provinsi |
| **No. Rekening BRI** | `bank_account_no` | `varchar(50)` | `nullable` | Nomor Rekening Bank BRI untuk payroll |
| **Email Pribadi** | `email` | `varchar(100)` | `nullable, email` | Alamat surel pribadi |
| **Status Aktif** | `is_active` | `boolean` | `default:true` | Switch toggle status aktif / keluar |

---

### B. Modul 2: Master Peserta Magang SMK (`hcm_interns` / Atribut Magang)
Menampung data siswa SMK Praktik Kerja Lapangan (PKL) / Magang.

| Label Kolom UI | Nama Kolom DB | Tipe Data | Validasi / Aturan | Keterangan & Kontrol UI |
| :--- | :--- | :--- | :--- | :--- |
| **Relasi Karyawan** | `employee_id` | `foreignId` | `required, exists:hcm_employees,id` | Foreign Key ke record master |
| **Nama Sekolah** | `intern_school_name` | `varchar(150)` | `required` | e.g. SMK Negeri 2 Lamongan |
| **Kelas** | `intern_class` | `varchar(20)` | `required` | e.g. X, XI, XII |
| **Jurusan** | `intern_major` | `varchar(100)` | `required` | e.g. Tata Busana, Multimedia, RPL |
| **No. Induk Siswa (NIS)** | `intern_nis` | `varchar(50)` | `required` | Nomor Induk Siswa dari sekolah |
| **No. HP Siswa** | `intern_phone` | `varchar(25)` | `required` | Kontak seluler siswa magang |
| **Tanggal Bergabung** | `intern_start_date` | `date` | `required, date` | Tanggal awal mulai magang |
| **Tanggal Berakhir** | `intern_end_date` | `date` | `required, date` | Tanggal penarikan kembali oleh sekolah |
| **Durasi Magang** | `intern_duration_text`| `varchar(50)` | `required` | e.g. 3 Bulan, 4 Bulan, 6 Bulan |
| **Guru Pendamping** | `intern_mentor_teacher`| `varchar(100)`| `required` | Nama guru pembimbing sekolah (e.g. Bu Ningsih) |
| **No. HP Guru Pendamping**| `intern_mentor_phone` | `varchar(25)` | `required` | Kontak darurat pihak sekolah |
| **Alamat Siswa** | `intern_address` | `text` | `required` | Alamat tempat tinggal / kost |

---

### C. Modul 3: Kontrak & Legalitas PKWT (`hcm_contracts`)
Pencatatan riwayat perjanjian kerja waktu tertentu (PKWT) dan sistem evaluasi berkala.

| Label Kolom UI | Nama Kolom DB | Tipe Data | Validasi / Aturan | Keterangan & Kontrol UI |
| :--- | :--- | :--- | :--- | :--- |
| **Karyawan** | `employee_id` | `foreignId` | `required, exists:hcm_employees,id` | Relasi ke master karyawan |
| **Posisi / Jabatan** | `position` | `varchar(100)` | `required` | Jabatan dalam kontrak kerja |
| **Status Ketenagakerjaan** | `employment_status` | `varchar(50)` | `required` | Tetap (PKWTT), Kontrak (PKWT), PKWT Lanjutan, Trainee, dll |
| **Kontrak Ke-** | `contract_sequence` | `integer` | `required, min:1` | Urutan perpanjangan (1, 2, 3) |
| **Nomor Kontrak Resmi** | `contract_number` | `varchar(100)` | `required, unique` | Format: `XXX/OWR/PKWT/X/XXXX` |
| **Badan Usaha / CV** | `legal_entity` | `varchar(100)` | `required` | Pilihan: CV Jersey Ekonomis, CV Apparel Allegiant, CV Bawang Merah, CV Bawang Putih |
| **Masa Kontrak** | `duration_text` | `varchar(50)` | `required` | e.g. 1 Tahun, 2 Tahun, Tetap |
| **Bulan Mulai Trainee** | `trainee_start_month` | `varchar(50)` | `nullable` | Bulan awal masa percobaan |
| **Bulan Berakhir Trainee** | `trainee_end_month` | `varchar(50)` | `nullable` | Bulan akhir masa probation |
| **Bulan Kontrak** | `contract_month` | `varchar(50)` | `nullable` | Bulan penerbitan kontrak kerja |
| **Tahun Mulai Kontrak** | `start_year` | `integer` | `required` | e.g. 2026 |
| **Tahun Berakhir Kontrak** | `end_year` | `varchar(10)` | `nullable` | e.g. 2027, 2028, atau `-` (Tetap) |
| **Tanggal Mulai Kontrak** | `start_date` | `date` | `required, date` | Tanggal efektif mulai kontrak |
| **Tanggal Berakhir Kontrak** | `end_date` | `date` | `nullable, date` | Tanggal berakhir (null jika Tetap) |
| **Sisa Masa Kontrak (Hari)** | `days_remaining` | `virtual / calc` | Auto-calculated | `DATEDIFF(end_date, CURDATE())` |
| **Status Review Kontrak** | `review_status` | `varchar(100)` | `required` | Dropdown Status Review (Aktif, Mendekati H-60, Wajib Review H-30, Masa Tenggang H-14, dll) |
| **File Dokumen Digital** | `file_contract_url` | `varchar(255)` | `nullable` | Link Google Drive scan kontrak fisik |

---

### D. Modul 4: Kompensasi & Riwayat Honor/Gaji (`hcm_compensations` & `histories`)
Pencatatan gaji/honor, siklus peninjauan berkala, dan rekam jejak kenaikan upah.

| Label Kolom UI | Nama Kolom DB | Tipe Data | Validasi / Aturan | Keterangan & Kontrol UI |
| :--- | :--- | :--- | :--- | :--- |
| **Karyawan** | `employee_id` | `foreignId` | `required, exists:hcm_employees,id` | Relasi data karyawan |
| **Status Ketenagakerjaan** | `employment_status` | `varchar(50)` | `required` | Karyawan Tetap, PKWT, PKWT Lanjutan |
| **CV Entitas** | `legal_entity` | `varchar(100)` | `required` | Entitas pemberi kerja |
| **No. Kontrak Terkait** | `contract_number` | `varchar(100)` | `nullable` | Referensi naskah kontrak kerja |
| **Masa Kontrak** | `duration_text` | `varchar(50)` | `nullable` | e.g. 1 Tahun, 2 Tahun, Tetap |
| **Masa Kerja Trainee (Bulan)**| `trainee_duration_months`| `integer` | `nullable` | Durasi training (e.g. 8, 12, 24 bulan) |
| **Siklus Evaluasi (Bulan)** | `evaluation_cycle_months`| `integer` | `required, default:6` | Siklus review kenaikan gaji (e.g. 4 atau 6 bulan) |
| **Honor Awal Kontrak (Rp)** | `initial_salary` | `decimal(15,2)` | `required, min:0` | Nominal gaji permulaan kerja |
| **Honor Saat Ini (Rp)** | `current_salary` | `decimal(15,2)` | `required, min:0` | Nominal gaji yang sedang berjalan aktif |
| **Total Kenaikan (Kali)** | `salary_increment_count` | `integer` | `default:0` | Frekuensi perolehan kenaikan honor |
| **Histori Kenaikan 1 (Rp)** | `increment_1_amount` | `decimal(15,2)` | `nullable` | Riwayat nominal kenaikan pertama |
| **Histori Kenaikan 2 (Rp)** | `increment_2_amount` | `decimal(15,2)` | `nullable` | Riwayat nominal kenaikan kedua |
| **Histori Kenaikan 3 (Rp)** | `increment_3_amount` | `decimal(15,2)` | `nullable` | Riwayat nominal kenaikan ketiga |
| **Status Pengajuan/Honor** | `salary_status` | `varchar(100)` | `required` | Dropdown Status Pengajuan/Honor (Draft, Sedang Diajukan, Telah Berlaku, Selesai, dll) |

---

### E. Modul 5: Kehadiran Bulanan & Log Absensi Harian (`hcm_attendances` & `leaves`)

#### 1. Tabel Log Absensi Harian (`hcm_attendances`)
| Label Kolom UI | Nama Kolom DB | Tipe Data | Validasi / Aturan | Keterangan & Kontrol UI |
| :--- | :--- | :--- | :--- | :--- |
| **Tanggal Absensi** | `attendance_date` | `date` | `required, date` | Tanggal riil pencatatan absensi |
| **Karyawan** | `employee_id` | `foreignId` | `required, exists:hcm_employees,id` | Relasi karyawan |
| **Posisi / Jabatan** | `position` | `varchar(100)` | `nullable` | Posisi penugasan hari tersebut |
| **Kategori Kehadiran** | `attendance_category` | `varchar(50)` | `required` | Pilihan: Hadir, Terlambat, Pulang Cepat, Cuti, Izin, Sakit, Dinas Luar, Alpha/Mangkir, Libur/Cuti Bersama |
| **Jam Masuk** | `clock_in` | `time` | `nullable` | Jam clock-in karyawan (shift default: 08:00) |
| **Jam Keluar** | `clock_out` | `time` | `nullable` | Jam clock-out karyawan (shift default: 17:00) |
| **Catatan / Alasan** | `notes` | `text` | `nullable` | Alasan terlambat/izin (e.g. Ban Bocor, Sakit) |
| **Status Lampiran** | `attachment_status` | `varchar(30)` | `required` | Terlampir / Tidak Terlampir |
| **File Bukti Lampiran** | `attachment_url` | `varchar(255)` | `nullable` | Link Google Drive surat dokter / surat izin |

#### 2. Tabel Pengajuan Cuti / Izin / Sakit (`hcm_leave_requests`)
- `employee_id`, `leave_type` (Cuti Tahunan, Izin, Sakit, Dinas Luar).
- `start_date`, `end_date`, `total_days`, `reason`.
- `status`: `PENDING_REVIEW` $\rightarrow$ `APPROVED` / `REJECTED`.
- `rejection_reason`: Wajib diisi jika ditolak oleh Atasan/HCM.
- *Hari H Trigger*: Jika Approved, otomatis muncul di widget Dashboard *"Izin Hari Ini"*. Jika Rejected dan karyawan tidak hadir, otomatis berstatus **Unexcused Absence (Alert Merah)**.

---

### F. Modul 6: Rekap Lembur Mingguan (`hcm_overtimes` & `batches`)

#### 1. Rincian Lembur Harian Karyawan (`hcm_overtimes`)
| Label Kolom UI | Nama Kolom DB | Tipe Data | Validasi / Aturan | Keterangan & Kontrol UI |
| :--- | :--- | :--- | :--- | :--- |
| **Tanggal Lembur** | `overtime_date` | `date` | `required, date` | Tanggal lembur dilaksanakan |
| **Karyawan** | `employee_id` | `foreignId` | `required, exists:hcm_employees,id` | Relasi karyawan pelaksana lembur |
| **Posisi / Jabatan** | `position` | `varchar(100)` | `nullable` | Posisi operasional |
| **Jam Lembur (Jam)** | `duration_hours` | `decimal(4,2)` | `required, min:0.5` | Durasi lembur riil dalam jam/desimal |
| **Jenis Hari Lembur** | `day_type` | `varchar(50)` | `required` | Pilihan: Lembur Hari Kerja / Lembur Hari Libur |
| **Tarif Per Jam (Rp)** | `hourly_rate` | `decimal(15,2)` | `auto-calculated` | Sesuai Business Rules (Rp 10.000 / Rp 15.000) |
| **Total Bayar Lembur (Rp)** | `total_amount` | `decimal(15,2)` | `auto-calculated` | Dihitung otomatis sesuai formula backend |
| **Batch Mingguan Relasi**| `batch_id` | `foreignId` | `nullable, exists:hcm_overtime_batches,id` | Dikelompokkan untuk pencairan Sabtu |

#### 2. Batch Pencairan Lembur Mingguan (`hcm_overtime_batches`)
- `batch_code`: Kode unik mingguan (e.g. `OT-2026-W36`).
- `period_start` (Sabtu 00:00) s.d. `period_end` (Jumat 23:59).
- `payout_date`: Hari Sabtu berikutnya.
- `grand_total_hours`, `grand_total_amount`.
- `status`: `DRAFT_OVERTIME` $\rightarrow$ `APPROVED_BY_HCM` $\rightarrow$ `PENDING_FINANCE_SIGN` $\rightarrow$ `PAID_COMPLETED`.
- `hcm_signed_by`, `hcm_signed_at` $\rightarrow$ Mengunci data untuk HCM.
- `finance_signed_by`, `finance_signed_at`, `payment_method` (Cash/Bank Transfer), `payout_proof_url` $\rightarrow$ Kunci permanen.

---

### G. Modul 7: Rekapitulasi Uang Makan Bulanan (`hcm_meal_allowance_batches` & `items`)
- **Periode Hitung**: Tanggal 1 s.d. akhir bulan berjalan. Pencairan dilaksanakan pada akhir bulan.
- **Rincian Per Karyawan (`hcm_meal_allowance_items`)**:
  - `employee_id`, `actual_present_days`, `half_days_count`, `alpha_days_count`, `late_count`.
  - `standard_allowance_amount` (Rp 280.000 / bulan).
  - `deduction_amount` (Potongan alpha/setengah hari).
  - `is_held`: Status penangguhan (*Hold*) jika terlambat $\ge 4$ kali.
  - `held_from_previous_month_amount`: Akumulasi uang makan yang ditahan dari bulan lalu untuk dicairkan dobel.
  - `net_payable_amount`: Total nominal bersih yang siap dibayarkan.
- **Batch Approval Status**: Identik Double Sign-Off (`DRAFT` $\rightarrow$ `APPROVED_BY_HCM` $\rightarrow$ `PENDING_FINANCE_SIGN` $\rightarrow$ `PAID_COMPLETED`).

---

### H. Modul 8: Arsip Dokumen Internal Perusahaan (`hcm_internal_documents`)
Mencakup dokumen Pengajuan (RAB, Proposal) dan Realisasi (LPJ, Pembelian Aset).

| Label Kolom UI | Nama Kolom DB | Tipe Data | Validasi / Aturan | Keterangan & Kontrol UI |
| :--- | :--- | :--- | :--- | :--- |
| **No. Registrasi** | `registration_no` | `varchar(50)` | `required, unique` | e.g. `DOC-INT-001` |
| **Nomor Dokumen Internal** | `document_code` | `varchar(100)` | `required` | e.g. `RAB_Produksi_Juni26_v1`, `PROP_Marketing_Juli26_v7` |
| **Tipe Siklus Dokumen** | `document_stage` | `enum` | `required, in:Pengajuan,Realisasi` | Pembeda siklus usulan vs SPJ |
| **Kategori Dokumen** | `category` | `varchar(100)` | `required` | Dropdown Kategori Pengajuan / Realisasi |
| **Departemen Pembuat** | `department` | `varchar(100)` | `required` | Produksi, Marketing, Media Eksternal, HCM, dll |
| **Tanggal Diajukan** | `submission_date` | `date` | `required, date` | Tanggal berkas diajukan |
| **Tanggal Disetujui** | `approval_date` | `date` | `nullable, date` | Tanggal pengesahan manajemen |
| **Status Dokumen** | `status` | `varchar(50)` | `required` | Berlaku, Selesai, Revisi, Dibatalkan |
| **Anggaran Diajukan (Rp)** | `proposed_budget` | `decimal(15,2)` | `required, min:0` | Usulan anggaran dana |
| **Realisasi Anggaran (Rp)**| `actual_budget` | `decimal(15,2)` | `nullable, min:0` | Serapan biaya riil (khusus tipe Realisasi) |
| **Link File Digital (GDrive)**| `file_digital_url`| `varchar(255)` | `required, url` | Tautan Google Drive + Modal Preview In-App |

---

### I. Modul 9: Arsip Korespondensi Eksternal (`hcm_external_letters`)
Mencatat surat-menyurat dengan instansi eksternal (BPJS-TK, Disnaker, Bank, Mitra).

| Label Kolom UI | Nama Kolom DB | Tipe Data | Validasi / Aturan | Keterangan & Kontrol UI |
| :--- | :--- | :--- | :--- | :--- |
| **No. Registrasi** | `registration_no` | `varchar(50)` | `required, unique` | e.g. `DOC-EXT-001` |
| **Tanggal Dokumen** | `letter_date` | `date` | `required, date` | Tanggal terbit surat fisik |
| **Kategori Dokumen** | `direction` | `varchar(50)` | `required` | Surat Masuk / Surat Keluar |
| **Nomor Dokumen Eksternal**| `external_letter_no`| `varchar(100)` | `required` | e.g. `055/BPJS-TK/IX/2024` |
| **Pengirim / Instansi** | `sender` | `varchar(150)` | `required` | e.g. BPJS Ketenagakerjaan, Dinas Tenaga Kerja |
| **Penerima Intern / Tujuan**| `recipient` | `varchar(150)` | `required` | e.g. HCM Dept, Direksi |
| **Perihal Dokumen** | `subject` | `text` | `required` | e.g. Undangan Sosialisasi Program JKP |
| **Link File Scan (GDrive)** | `file_scan_url` | `varchar(255)` | `required, url` | Direct link scan berkas fisik di Google Drive |

---

### J. Modul 10: Buku Agenda Penomoran Surat Masuk & Keluar (`hcm_agenda_letters`)
Buku registrasi penomoran surat resmi perusahaan agar tidak terjadi nomor surat ganda.

| Label Kolom UI | Nama Kolom DB | Tipe Data | Validasi / Aturan | Keterangan & Kontrol UI |
| :--- | :--- | :--- | :--- | :--- |
| **No. Agenda** | `agenda_no` | `varchar(50)` | `required, unique` | e.g. `AGD-2024-001` |
| **Nomor Surat Resmi** | `official_letter_no`| `varchar(100)` | `required` | e.g. `001/HCM-MEMO/I/2024`, `88/EXT-SUP/VIII/2024` |
| **Jenis Surat** | `letter_scope` | `varchar(50)` | `required` | Surat Masuk, Surat Keluar (Internal), Surat Keluar (Eksternal) |
| **Kategori Surat** | `letter_category` | `varchar(100)` | `required` | Paklaring, Surat Jalan, SP, SOP, SK, SPPD, dll |
| **Tanggal Surat** | `letter_date` | `date` | `required, date` | Tanggal resmi surat dibuat |
| **Perihal / Ringkasan** | `summary` | `text` | `required` | Ringkasan pokok isi surat |
| **Tujuan / Dari** | `target_party` | `varchar(150)` | `required` | Pihak yang dituju atau pengirim surat |
| **Status Disposisi** | `disposition_status`| `varchar(100)` | `required` | Selesai, Diteruskan ke HCM Manager, Menunggu Tindak Lanjut |
| **Link Scan Surat (GDrive)**| `scan_url` | `varchar(255)` | `nullable, url` | Tautan arsip digital di Google Drive |

---

### K. Modul 11: Rekrutmen Pipeline, Loker & Public Career Form (`hcm_job_postings`, `applicants`, `interviews`)

Modul ini mengelola seluruh rantai pasok talenta dari publikasi lowongan kerja, pendaftaran mandiri oleh pelamar via tautan publik, screening, wawancara, hingga konversi otomatis menjadi karyawan baru.

#### 1. Master Lowongan Kerja / Loker (`hcm_job_postings`)
- Kolom Tabel:
  - `job_code` (e.g. `LKR-2026-001`), `job_title` (e.g. Operator Sewing Batch 4), `slug` (e.g. `operator-sewing-batch-4`), `department`, `legal_entity` (CV).
  - `target_quota` (Kebutuhan kuota orang), `fulfilled_count` (Jumlah yang sudah diterima), `salary_range_min`, `salary_range_max`.
  - `start_date`, `end_date`, `recruitment_channel`, `recruiter_id` (PIC HCM).
  - `status` (`Draft`, `Aktif / Buka`, `Ditutup`, `Terpenuhi`), `requirements_text`.
- **Fitur Publikasi: Public Shareable Link & QR Code**:
  - Setiap kali Loker berstatus `Aktif / Buka`, sistem secara otomatis mengenerate **Tautan Pendaftaran Publik** yang ramah dibagikan:
    `https://[domain-nisreport]/karir/{slug}`
  - Tombol Aksi di Panel Admin HCM:
    - **`[ 🔗 Salin Link Pendaftaran ]`**: Menyalin link langsung untuk di-share ke WhatsApp Group, Instagram, atau portal loker.
    - **`[ 📱 Unduh QR Code ]`**: Menghasilkan file gambar QR Code siap cetak untuk ditempel di mading/spanduk pabrik garment.

---

#### 2. Formulir Pendaftaran Publik Pelamar (`/karir/{slug}`)
Halaman pendaftaran publik yang dapat diakses calon pelamar secara mandiri tanpa harus login (*Public Guest Route*). Desain formulir mengadopsi field yang sejalan dengan **Master Data Karyawan (`hcm_employees`)**, namun dirancang **semi-lengkap (tidak 100% mengisi data internal perusahaan)**:

##### A. Field yang Wajib & Diisi Mandiri oleh Pelamar:
1. **Identitas Diri Dasar (Sesuai KTP)**:
   - Nama Lengkap (Sesuai KTP)
   - Nama Panggilan Akrab
   - Nomor WhatsApp Aktif (Format angka valid)
   - Alamat Email Pribadi
   - Jenis Kelamin (Laki-Laki / Perempuan)
   - Agama (Islam, Kristen, Katolik, Hindu, Buddha, Konghucu)
   - Tempat & Tanggal Lahir (Otomatis menghitung usia pelamar)
   - Status Pernikahan (Belum Menikah, Menikah, Cerai)
   - Alamat Domisili Lengkap (RT/RW, Desa, Kecamatan, Kab/Kota)
2. **Riwayat Pendidikan & Kompetensi**:
   - Tingkat Pendidikan Terakhir (SMP, SMA/SMK, D3, S1, dll.)
   - Asal Sekolah / Universitas & Jurusan
   - Keterampilan / Skill Utama yang Dikuasai (e.g. Menggambar, Mesin Jahit Jarum 1/2, Obras)
3. **Pengalaman & Minat Kerja**:
   - Pengalaman Kerja Terakhir (Nama Perusahaan, Posisi Terakhir, Lama Bekerja, atau Fresh Graduate)
   - Kegiatan Sehari-hari Saat Ini (e.g. Masih Bekerja, Mencari Kerja, Wirausaha)
   - Ekspektasi Nominal Gaji yang Diharapkan (Rp per bulan)
4. **Unggah Berkas Pendukung (Upload File)**:
   - Unggah Foto KTP / Pasfoto Digital (JPG/PNG)
   - Unggah Dokumen CV / Resume (PDF)
   - *Catatan Sinkronisasi File*: File unggahan pelamar dialirkan langsung ke Google Drive subfolder `📁 Google Drive / NISGroup HCM / 05_Rekrutmen_Pelamar` sehingga disk hosting lokal tetap 0 MB.

##### B. Field Internal yang Dikecualikan (TIDAK Diisi Pelamar):
Demi kepraktisan dan perlindungan privasi kandidat sebelum diterima, field-field internal berikut **hanya diinput oleh Admin HCM pasca kandidat lolos & masuk tahap onboarding**:
- NIK KTP 16-Digit (diverifikasi fisik saat interview/onboarding)
- Nomor BPJS Kesehatan & Nomor BPJS Ketenagakerjaan
- Nomor Rekening Bank BRI (untuk payroll)
- Ukuran Baju Seragam Kerja (`S` s.d. `XXXXL`)
- Status Hubungan Kerja & Nomor Kontrak PKWT Resmi
- Nominal Gaji Pokok Resmi Disetujui & Siklus Evaluasi Upah

---

#### 3. Pipeline Pelamar Terikat Loker (`hcm_job_applicants`)
Setiap data pelamar yang masuk dari formulir online otomatis tercatat di tabel pipeline loker:

| Label Kolom UI | Nama Kolom DB | Tipe Data | Validasi / Aturan | Keterangan & Kontrol UI |
| :--- | :--- | :--- | :--- | :--- |
| **Relasi Loker** | `job_posting_id` | `foreignId` | `required, exists:hcm_job_postings,id` | Referensi ke loker yang dilamar |
| **Tanggal Melamar** | `apply_date` | `date` | `required, date` | Tanggal pendaftaran online masuk |
| **Nama Pelamar** | `applicant_name` | `varchar(150)` | `required, string` | Nama lengkap pelamar |
| **No. HP / WhatsApp** | `phone_number` | `varchar(25)` | `required` | Tautan langsung `wa.me/` |
| **Status Talent Pool** | `pipeline_status` | `varchar(50)` | `default:Screening` | Screening, Dipanggil Interview, Rejected at Screening, Keep for Next Batch, Offered, Hired |
| **Status Undangan** | `invitation_status`| `varchar(50)` | `default:Belum Diundang` | Belum Diundang, Diundang, Hadir Interview, Tidak Diundang |
| **Hasil Interview** | `interview_result` | `varchar(50)` | `nullable` | Disarankan Diterima, Dipertimbangkan, Ditolak, `-` |
| **Status Kehadiran Kerja**| `onboarding_attendance`| `varchar(50)`| `nullable` | Hadir, Tidak Hadir, `-` |
| **Status Blacklist** | `is_blacklisted` | `boolean` | `default:false` | Ya / Tidak (Otomatis flag jika mangkir kerja) |
| **Alasan Detail / Catatan**| `hcm_notes` | `text` | `nullable` | Catatan rekam jejak evaluasi HCM |

---

#### 4. Rekap Hasil Wawancara Kandidat (`hcm_applicant_interviews`)
- `applicant_id`: Relasi ke data pelamar.
- Data Profil Terisi Otomatis dari Form Pelamar: `age` (Umur), `marital_status`, `education`, `last_experience`, `daily_activity`, `core_skills`.
- Evaluasi Wawancara: `salary_expectation`, `offering_status` (`Diterima (Join)`, `Dipertimbangkan Kembali`, `Ditolak Pelamar`, `Pending`), `interview_decision` (`Diterima`, `Pending`, `Ditolak`), `offering_notes`.

---

#### 5. Fitur Konversi Otomatis ke Master Karyawan (1-Klik Auto-Convert)
- Ketika pelamar berstatus **`Diterima (Join)`**, pada antarmuka admin HCM muncul tombol **`[ 🚀 Konversi Jadi Karyawan Baru ]`**.
- Sistem menyalin seluruh data diri pelamar (Nama, Panggilan, Telepon, Email, Gender, Agama, Pendidikan, TTL, Alamat Domisili, Status Nikah) langsung ke tabel **Master Karyawan (`hcm_employees`)**.
- Admin HCM diarahkan ke formulir finalisasi untuk melengkapi field khusus karyawan (Nomor Rekening BRI, No BPJS, Ukuran Seragam, dan Penerbitan Draf Kontrak PKWT) tanpa perlu melakukan entri ulang data identitas.
- Sistem otomatis menambah counter `fulfilled_count` pada Loker terkait. Jika kuota telah terpenuhi (`fulfilled_count >= target_quota`), status Loker otomatis berganti menjadi `Terpenuhi / Closed`.

---

### L. Modul 12: Status & Transisi Kepegawaian (Onboarding & Offboarding)

#### 1. Rekap Onboarding Karyawan Baru (`hcm_onboardings`)
- `employee_id`, `position`, `join_date`, `status_checklist` (Kelengkapan berkas KTP, BPJS, TTD Kontrak, Seragam), `approved_date`, `department`.

#### 2. Rekap Offboarding Karyawan Keluar (`hcm_offboardings`)
| Label Kolom UI | Nama Kolom DB | Tipe Data | Validasi / Aturan | Keterangan & Kontrol UI |
| :--- | :--- | :--- | :--- | :--- |
| **Karyawan** | `employee_id` | `foreignId` | `required, exists:hcm_employees,id` | Karyawan yang keluar/terminasi |
| **Posisi Terakhir** | `position` | `varchar(100)` | `required` | Jabatan terakhir |
| **Tanggal Keluar** | `exit_date` | `date` | `required, date` | Tanggal efektif berhenti bekerja |
| **Alasan Keluar** | `exit_reason` | `varchar(150)` | `required` | e.g. Menikah, Mendapat pekerjaan baru, Habis kontrak |
| **Kepatuhan Notice Period**| `notice_compliance`| `varchar(100)`| `required` | Pilihan: Sesuai Ketentuan (1 Bulan), Kurang dari Ketentuan, Tanpa Notice, Garden Leave, Pemutusan Perusahaan |
| **Hak Karyawan (Sisa)** | `rights_status` | `varchar(100)`| `required` | Lunas & Dibayarkan Penuh, Dipotong/Ada Penyesuaian, Ditahan Sebagian, Belum Dibayarkan |
| **Pengembalian Aset & Paklaring**| `asset_clearance`| `varchar(100)`| `required` | Lengkap & Terbit, Belum Lengkap / Aset Ditahan, Tidak Terbit |
| **Status Clearance Sheet**| `clearance_status` | `varchar(50)` | `required` | Pending / Selesai (Clear) |
| **Catatan / Keterangan** | `offboarding_notes`| `text` | `nullable` | e.g. *Surat Pengalaman Kerja (Paklaring) sudah diserahkan* |

---

### M. Modul 13: Rekapitulasi Penyaluran Reward & Penghargaan (`hcm_employee_rewards`)
*Modul Baru Faktual dari Blueprint Excel (Sheet Database Baris 84–87 & DropDown Kolom V)*:
Pencatatan apresiasi, bonus non-gaji, dan barang reward (seperti tiket liburan, mesin cuci, piagam prestasi) untuk karyawan berprestasi.

| Label Kolom UI | Nama Kolom DB | Tipe Data | Validasi / Aturan | Keterangan & Kontrol UI |
| :--- | :--- | :--- | :--- | :--- |
| **Karyawan** | `employee_id` | `foreignId` | `required, exists:hcm_employees,id` | Karyawan penerima reward |
| **Posisi / Jabatan** | `position` | `varchar(100)` | `nullable` | Jabatan saat penghargaan diberikan |
| **Jenis Reward / Penghargaan**| `reward_name` | `varchar(150)` | `required` | e.g. Tiket Liburan, Mesin Cuci, Karyawan Teladan |
| **Periode / Tahun** | `reward_year` | `integer` | `required` | e.g. 2026 |
| **Status Penyaluran** | `distribution_status`| `varchar(50)` | `required` | Pilihan Dropdown: Belum Diterima, Sudah Diterima (Serah Terima Langsung), Sudah Ditransfer, Tertunda / Pending, Dibatalkan |
| **Tanggal Diterima** | `received_date` | `date` | `nullable, date` | Tanggal serah terima fisik/transfer reward |
| **Status Dokumen** | `document_status` | `varchar(50)` | `nullable` | Status berita acara / tanda terima |
| **Anggaran Diajukan (Rp)**| `budget_amount` | `decimal(15,2)` | `nullable` | Biaya reward |
| **Link File Digital / Bukti** | `proof_url` | `varchar(255)` | `nullable, url` | Tautan foto dokumentasi penyerahan / berita acara di Google Drive |

---

### N. Modul 14: Company Events & Social Calendar (`hcm_company_events`)
Menampung agenda internal kantor, undangan sosial dari karyawan, dan hari libur nasional tahunan.

| Label Kolom UI | Nama Kolom DB | Tipe Data | Validasi / Aturan | Keterangan & Kontrol UI |
| :--- | :--- | :--- | :--- | :--- |
| **Judul Agenda / Event** | `event_title` | `varchar(150)` | `required` | e.g. Makan Bersama All Team, Gathering, Pernikahan |
| **Kategori Event** | `event_type` | `varchar(50)` | `required` | Internal Perusahaan, Undangan Karyawan, Agenda Tahunan, Libur Nasional |
| **Penyelenggara / Pengundang**| `organizer_name`| `varchar(100)` | `required` | Manajemen NISGroup, Sdr. Rian (Sewing), dsb |
| **Waktu Mulai** | `start_datetime` | `datetime` | `required` | Tanggal & jam acara (WIB) |
| **Waktu Selesai** | `end_datetime` | `datetime` | `nullable` | Perkiraan selesai kegiatan |
| **Lokasi Kegiatan** | `location` | `varchar(150)` | `required` | Pabrik Garment, Gedung Serbaguna, dll |
| **Target Peserta** | `target_audience` | `varchar(100)` | `default:Semua Tim` | Divisi sasaran acara |
| **Pengingat (Reminder H-N)**| `reminder_days` | `integer` | `default:3` | Notifikasi H-3, H-1, Hari H |
| **Berulang Tahunan?** | `is_annual_recurring`| `boolean` | `default:false` | True untuk HUT RI 17 Agustus |
| **Lampiran / Undangan** | `invitation_file_url`| `varchar(255)` | `nullable, url` | Tautan scan kartu undangan fisik di Google Drive |

---

## 4. Business Rules & Logika Kalkulasi Otomatis (Calculation Engine)

Berdasarkan lembar kerja **`Business Rules`** pada Blueprint Excel, sistem HRIS wajib mengeksekusi perhitungan backend secara presisi:

### A. Logika Kalkulasi Upah Lembur (Overtime Calculation)
1. **Parameter Tarif Dasar Lembur (Fixed Rate)**:
   - **Tarif Lembur Hari Kerja (Weekdays)**:
     - Tarif Dasar: **Rp 10.000 / jam**
     - Aturan Khusus 30 Menit Pertama: **Rp 5.000**
     - Kelipatan Menyesuaikan secara proporsional.
   - **Tarif Lembur Hari Libur / Rest Day / Tanggal Merah (Weekend)**:
     - Tarif Dasar: **Rp 15.000 / jam**
     - Aturan Khusus 30 Menit Pertama: **Rp 10.000**
     - Kelipatan Menyesuaikan secara proporsional.
2. **Formula Perhitungan Menit & Jam Lembur**:
   - Durasi $< 30$ Menit: Tidak dihitung lembur (dibulatkan ke bawah).
   - Durasi $30$ Menit s.d. $< 60$ Menit: Dihitung tarif 30 menit pertama (Rp 5.000 pada hari kerja; Rp 10.000 pada hari libur).
   - Durasi $\ge 1$ Jam: Dihitung proporsional berdasarkan tarif per jam (contoh: 2 jam di weekend = $2 \times Rp 15.000 = Rp 30.000$; 4 jam di weekdays = $4 \times Rp 10.000 = Rp 40.000$).
3. **Siklus Waktu Rekapitulasi (Cut-off Cycle Lembur)**:
   - **Periode Rekap**: Mulai **Sabtu (minggu lalu) pukul 00:00:00** sampai dengan **Jumat (minggu ini) pukul 23:59:59**.
   - **Jadwal Pencairan / Pembayaran**: Setiap **Hari Sabtu berikutnya** (langsung dibayarkan tunai atau ditransfer bersamaan dengan rekap mingguan).
4. **Logika Auto-Kalkulasi Backend**:
   - Sistem secara otomatis mengagregasi seluruh entri data lembur harian yang diinput HCM dalam rentang cut-off Sabtu s.d. Jumat, mengalikan dengan tarif hari kerja/libur, dan menyusunnya ke dalam *Batch Lembur Mingguan*.

---

### B. Logika Kalkulasi & Penyaluran Uang Makan (Meal Allowance Calculation)
1. **Jadwal & Periode Perhitungan**:
   - Uang makan dihitung berdasarkan total kehadiran dari **awal bulan sampai akhir bulan** (tanggal 1 s.d. tanggal terakhir bulan kalender).
   - Pencairan atau penunaian uang makan dilakukan pada **akhir bulan**.
2. **Tarif Standar Uang Makan**:
   - Tarif per minggu: **Rp 70.000**
   - Asumsi 1 bulan dihitung 4 minggu, sehingga total standar per bulan adalah: **Rp 280.000 / orang**.
3. **Aturan Pemotongan Kehadiran (Deduction Rules)**:
   - **Tidak Hadir (Alpha / Mangkir Tanpa Keterangan)**: Masuk 1x tidak hadir akan memotong uang makan (*nominal potongan dapat dikonfigurasi melalui modul pengaturan keuangan*).
   - **Masuk Setengah Hari (Half-Day)**: Masuk setengah hari akan memotong uang makan (*nominal potongan dapat dikonfigurasi melalui modul pengaturan keuangan*).
   - **Cuti atau Dinas Luar (Leave & Official Duty)**: Karyawan yang mengambil hak cuti resmi atau menjalankan tugas dinas luar **TETAP BERHAK MENDAPATKAN UANG MAKAN SECARA PENUH (TIDAK DIPOTONG)**.
4. **Aturan Akumulasi Keterlambatan & Penangguhan (Delay & Hold Logic)**:
   - **Batas Toleransi Keterlambatan**: Batas maksimal keterlambatan adalah **3 kali dalam satu bulan**.
   - **Sanksi Keterlambatan $\ge 4$ Kali**:
     - Jika jumlah keterlambatan karyawan mencapai **4 kali atau lebih** dalam bulan berjalan, maka pembayaran uang makan pada bulan tersebut **DITANGGUHKAN (HOLD)** dan **TIDAK DIBAYARKAN PADA AKHIR BULAN TERSEBUT**.
     - Nominal uang makan yang ditahan akan **digabung ke bulan berikutnya**, sehingga pada bulan berikutnya karyawan menerima pembayaran **dobel (2 bulan sekaligus)** jika memenuhi syarat kedisiplinan.
   - **Ketentuan Berulang (Continuous Hold Rule)**:
     - Jika di bulan berikutnya karyawan **kembali terlambat $\ge 4$ kali**, maka penangguhan uang makan akan **terus berulang ditahan** ke bulan depannya lagi dengan pola yang sama sampai karyawan memperbaiki disiplin absensinya.
5. **Aturan Sanksi Izin & Bonus Bulanan**:
   - **Izin $> 2\times$ Sebulan**: Jika karyawan mengambil izin (alasan pribadi di luar cuti sakit darurat) **lebih dari 2 kali dalam satu bulan kalender**, maka secara otomatis karyawan tersebut **TIDAK BERHAK MENDAPATKAN BONUS BULANAN**. Sistem secara otomatis menandai flag `bonus_eligible = false` pada laporan audit bulanan.

---

## 5. Matriks Alur Kerja & Validasi Operasional (Workflow Matrix)

Berdasarkan lembar kerja **`Alur & Validasi`** pada Blueprint Excel, berikut adalah 3 alur operasional utama:

### Tabel Matriks Workflow Blueprint:

| Modul / Alur | Tahap Proses | Aktor / Role | Tindakan / Trigger | Kode Status Sistem | Aturan Validasi & Gerbang (*Gate*) | Tindakan Lanjutan |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Alur 1: Ketidakhadiran (Cuti/Izin/Sakit)** | **1. Logging** | HCM Admin | Menerima pengajuan H-N karyawan & input di web | `PENDING_REVIEW` | Wajib input jenis izin, tanggal mulai-selesai, & alasan lengkap. | HCM koordinasi offline/konfirmasi ke PIC divisi. |
| | **2. Decision Input** | HCM Admin / Manager | Input keputusan hasil komunikasi dengan PIC | `APPROVED` atau `REJECTED` | Jika ditolak (`REJECTED`), wajib mengisi alasan penolakan di sistem. | Tersimpan di rekam medis / catatan evaluasi karyawan. |
| | **3. Hari H Trigger** | System (Web HCM) | Otomatis dipicu pada tanggal izin berjalan | `ACTIVE` atau `UNEXCUSED` | Jika `APPROVED`: Otomatis tampil di widget *"Izin Hari Ini"*. Jika `REJECTED` namun tidak hadir: Pop-up Alert Merah Mangkir + Masuk rekam evaluasi & potong uang makan. | Dipakai untuk audit absensi bulanan. |
| **Alur 2: Lembur Mingguan (Pencairan Sabtu)** | **1. Input Jam & Auto-Calculate** | HCM Admin | Input jam mulai & jam selesai lembur (atau ukur absensi riil) | `DRAFT_OVERTIME` | Auto-Calculate System: Hitung total jam riil dan total nominal Rp (weekday/weekend). | Jam lembur diverifikasi dengan SPL (Surat Perintah Lembur) & absensi riil. |
| | **2. HCM Approval** | HCM Admin / Manager | Audit hasil kalkulasi sistem & Klik tombol `[Approve & Sign HCM]` | `APPROVED_BY_HCM` | Data & nominal terkunci untuk HCM (Read-Only). Notifikasi otomatis terkirim ke Keuangan. | Masuk ke antrean Dashboard Keuangan. |
| | **3. Payout Execution** | Tim Keuangan | Cek total nominal hasil kalkulasi & cairkan dana (hari Sabtu) | `PENDING_FINANCE_SIGN` | Keuangan mencocokkan total pengeluaran dengan anggaran kas tunai / transfer bank. | Siap di-sign oleh Tim Keuangan. |
| | **4. Finance Sign-Off** | Tim Keuangan | Klik tombol `[Sign & Mark as Paid]` | `PAID_COMPLETED` | Sistem terkunci total secara permanen (Read-Only). Menghasilkan slip bukti bayar resmi. | Arsip digital & Audit Trail kepegawaian. |
| **Alur 3: Uang Makan Bulanan (Cut-Off)** | **1. Auto-Calculate** | System (Web HCM) | Sistem mengakumulasi total kehadiran riil $\times$ rate uang makan | `DRAFT` | Potongan otomatis memotong hari saat alpha/setengah hari; cek aturan hold jika telat $\ge 4\times$. | Siap diaudit oleh HCM. |
| | **2. HCM Approval** | HCM Admin / Manager | Audit rekap hari hadir & Klik tombol `[Approve & Sign HCM]` | `APPROVED_BY_HCM` | Data total hari kerja dan nominal terkunci untuk HCM. Tidak bisa diedit lagi. | Diteruskan ke antrean Tim Keuangan. |
| | **3. Payout Execution** | Tim Keuangan | Cek nominal hasil kalkulasi & proses pembayaran akhir bulan | `PENDING_FINANCE_SIGN` | Keuangan mengeksekusi pencairan bersamaan payroll / kas tunai. | Siap di-sign oleh Tim Keuangan. |
| | **4. Finance Sign-Off** | Tim Keuangan | Klik tombol `[Sign & Paid]` | `PAID_COMPLETED` | Sistem terkunci total (Read-Only). Menutup buku uang makan bulan berjalan secara resmi. | Arsip digital & pembukuan kas selesai. |

---

## 6. Dashboard HCM, Alerts & Color-Coding System

Berdasarkan lembar kerja **`Dashboard`** pada Blueprint Excel, tampilan muka (*homepage*) HCM dirancang sebagai *Command Center* proaktif dengan 4 blok widget utama dan sistem warna 5 kategori:

### A. 4 Blok Widget Dashboard Utama

#### 1. Blok Urgent & Action Needed (Butuh Tindakan Immediate)
- **Masa Evaluasi Probation / Training**:
  - Alert menyala jika ada karyawan yang masa training-nya usai dalam 7 hari ke depan (Contoh fakta Excel: *Sdri. Alisa Firda Riana – Masa Training usai dalam 7 hari (14 Sep 2026)*).
- **Masa Berakhir Kontrak (PKWT)**:
  - Alert menyala jika kontrak habis dalam 30 hari ke depan (Contoh fakta Excel: *Sdr. Ahmad Rizky – Kontrak habis dalam 30 hari (Perlu keputusan: Perpanjang/Putus)*).
- **Pending Approvals**:
  - Counter badge merah: *3 Pengajuan cuti/izin baru menunggu persetujuan Atasan/HR*.

#### 2. Blok Daily Schedule & Attendance (Agenda & Absensi Hari Ini)
- **Rekap Izin & Cuti Hari Ini (Scheduled Leave Alert)**:
  - Otomatis menampilkan siapa saja yang berizin/cuti resmi hari ini berdasarkan tiket pengajuan tanggal-tanggal sebelumnya (Contoh fakta Excel: *Total: 3 Orang: 1. Ahmad Rizky (Sewing) – Cuti Tahunan; 2. Dewi Larasati (HR) – Izin Keperluan Keluarga; 3. Eko Prasetyo (Gudang) – Cuti Sakit / Surat Dokter*).
- **Peringatan Absensi (Unexcused Absence Alert)**:
  - Alert Merah pukul 08:30 WIB: *2 Karyawan belum melakukan konfirmasi ketidakhadiran hari ini tanpa keterangan (mangkir)*.

#### 3. Blok Payroll & Time Management Reminders (Pengingat Penggajian & Kehadiran)
- **Validasi Total Lembur Mingguan (Jumat Sore / Sabtu Pagi Alert)**:
  - Muncul setiap H-1 / Sabtu pagi sebelum pencairan lembur mingguan:
    - *Pesan*: "Mohon lakukan validasi & persetujuan total jam lembur minggu ini sebelum diteruskan ke Keuangan."
    - *Status*: Belum Divalidasi $\rightarrow$ Tombol cepat: `[Validasi & Kirim ke Keuangan]`.
- **Validasi Kehadiran & Rekap Absensi Bulanan (Akhir Bulan Alert)**:
  - Muncul H-3 sebelum akhir bulan / Cut-Off Payroll:
    - *Pesan*: "Batas akhir validasi kehadiran, cuti, dan izin bulan ini. Setelah divalidasi oleh HCM, data akan dikunci untuk eksekusi penggajian (payroll) oleh Tim Keuangan."
    - *Status*: Pending Check $\rightarrow$ Tombol cepat: `[Kunci Data & Transfer ke Keuangan]`.

#### 4. Blok Company Events & Social Calendar (Agenda, Event & Ulang Tahun)
- **Agenda & Event Perusahaan / Undangan Terjadwal**:
  - Menampilkan agenda internal maupun undangan sosial yang telah diinput (Contoh fakta Excel:
    1. *Makan Bersama All Team Garment – Jumat, 11 September 2026 (12.00 WIB)*
    2. *Undangan Pernikahan (Sdr. Rian - Sewing) – Sabtu, 19 September 2026*
    3. *Company Gathering 2026 – Sabtu-Minggu, 10-11 Oktober 2026*
    4. *Peringatan HUT RI Kemerdekaan Kantor – 17 Agustus (Agenda Tahunan)*
    5. Tombol aksi: `[+ Tambah Agenda/Undangan]`).
- **Pengingat Ulang Tahun Karyawan (H-3 Warning)**:
  - Otomatis dihitung dari `birth_date` karyawan aktif (Contoh fakta Excel: *1. Siti Rahma (Sewing) – Ulang tahun pada 10 September 2026 (3 hari lagi); 2. Budi Prasetyo (Finishing) – Ulang tahun pada 11 September 2026 (4 hari lagi)*).
- **Ulang Tahun Masa Kerja (Work Anniversary)**:
  - Otomatis dihitung dari tanggal bergabung awal (Contoh fakta Excel: *Sdr. Budi Santoso – Genap 5 tahun bekerja hari ini*).

---

### B. Skema Indikator Warna UX (Color-Coding System)

| Sistem Warna | Kode Warna & Aksen | Makna & Penggunaan Operasional | Contoh Kasus Nyata di Sistem |
| :--- | :--- | :--- | :--- |
| **Red System** | **Merah Kritis**<br>`#DC2626` / `#EF4444` | Mendesak, menghambat proses finansial/legal, atau butuh tindakan langsung di hari yang sama. | 1. Validasi lembur di hari Sabtu belum disetujui padahal batas waktu pencairan kas.<br>2. Cut-Off Payroll bulanan pada Hari H.<br>3. Karyawan mangkir / unexcused absence (tidak clock-in tanpa kabar pukul 08:30 WIB).<br>4. Masa probation atau PKWT habis $< 3$ hari lagi dan belum ada tindakan HR.<br>5. Pengajuan izin/cuti darurat yang belum direspon. |
| **Yellow / Amber** | **Kuning / Oranye**<br>`#D97706` / `#F59E0B` | Peringatan awal, persiapan dokumen, dan proses yang mendekati tenggat waktu (*upcoming deadline*). | 1. Pengingat H-3 sebelum Cut-Off Payroll bulanan atau H-1 validasi lembur.<br>2. Trainee yang lulus training dan butuh draf PKWT, jadwal TTD, serta pendaftaran BPJS-TK.<br>3. Peringatan habis kontrak dalam jangka waktu 7–30 hari lagi.<br>4. Pengajuan cuti/izin biasa berstatus *Pending Approval*. |
| **Blue System** | **Biru Operasional**<br>`#2563EB` / `#3B82F6` | Informasi operasional harian, presensi, dan agenda/event mendatang. | 1. Rekap daftar karyawan yang sedang cuti/izin resmi hari ini (*Scheduled Leave*).<br>2. Agenda internal perusahaan (makan bersama, gathering, rapat, HUT RI).<br>3. Undangan pribadi/sosial dari karyawan (pernikahan, khitanan, dll). |
| **Green System** | **Hijau Sukses**<br>`#16A34A` / `#22C55E` | Konfirmasi status selesai (*completed*) dan perayaan/milestone positif. | 1. Validasi lembur/absensi berhasil dikunci & terkirim ke Keuangan (*Submitted to Finance*).<br>2. Pencairan kas selesai (`PAID_COMPLETED`).<br>3. Kontrak baru/PKWT berhasil ditandatangani & BPJS aktif.<br>4. Ulang tahun karyawan (H-3 s.d. Hari H) & Work Anniversary (ulang tahun masa kerja). |
| **Grey System** | **Abu-abu Netral**<br>`#4B5563` / `#6B7280` | Data arsip, agenda yang sudah lewat, atau status non-aktif. | 1. Notifikasi yang sudah dibaca / diselesaikan (*Marked as Read*).<br>2. Agenda atau event sosial yang telah berlalu.<br>3. Karyawan yang berstatus resign / habis kontrak (*Offboarding Completed*). |

---

## 7. Fitur Spesial Operasional & Pengalaman Pengguna (UX)

### A. Bulk Daily Attendance Matrix (Editor Absensi Harian Massal)
Mengingat tingginya jumlah staf manufaktur di bagian jahit, potong, dan finishing, input absensi wajib menggunakan tabel matriks 1 halaman:
1. **Filter Header**: Tanggal (Default: Hari ini) dan Filter Divisi (Semua, Sewing, Potong, QC, Finishing, Office).
2. **Aksi 1-Klik**: Tombol `[Set Semua Hadir (Default 08:00 - 17:00)]`.
3. **Pintasan Cepat Status (Quick Toggle Buttons)**:
   - `[H]` Hadir (Hijau)
   - `[T]` Terlambat (Kuning) $\rightarrow$ Memunculkan input jam masuk
   - `[I]` Izin (Biru) $\rightarrow$ Membuka popover alasan izin
   - `[S]` Sakit (Kuning Tua) $\rightarrow$ Modal upload surat dokter
   - `[A]` Alpha/Mangkir (Merah) $\rightarrow$ Memanggil alarm unexcused absence
4. **Pintasan Keyboard**: `Ctrl + S` untuk menyimpan seluruh rekapitulasi harian secara massal.

---

### B. Bulk Overtime Dispatcher (Editor Lembur Massal Regu Kerja)
1. **Multi-Select Karyawan**: Checkbox untuk memilih seluruh regu (contoh: 12 operator sewing lembur serentak).
2. **Batch Parameter**: Input jam mulai, jam selesai, dan jenis hari (*Lembur Hari Kerja* vs *Lembur Hari Libur*).
3. **Kalkulasi Otomatis Backend**: Menghitung tarif 30 menit pertama dan jam berikutnya secara otomatis, menjumlahkan total nominal, dan memasukkannya ke dalam draf batch mingguan.

---

### C. Profil Karyawan 360° (Tab-Based Dossier) & Ekspor PDF
Saat membuka profil detail karyawan (`/hcm/employees/{id}`), disajikan antarmuka 6 tab terpadu:
1. **Tab 1: Biodata & Identitas Lengkap**:
   - NIK KTP (16 Digit), TTL, Agama, Jenis Kelamin, Status Pernikahan, Pendidikan, HP/WA, Email, Alamat Domisili.
   - Metadata Kerja: Rekening BRI, No BPJS Kesehatan, No BPJS Ketenagakerjaan, Ukuran Baju Seragam (`S` s.d. `XXXXL`).
   - *Khusus Siswa Magang*: Sekolah, Kelas, Jurusan, NIS, Tanggal Bergabung & Berakhir, Guru Pendamping & HP Guru.
2. **Tab 2: Riwayat Kontrak & Legalitas PKWT**:
   - Timeline kontrak, Nomor Kontrak Resmi (`XXX/OWR/PKWT/X/XXXX`), Entitas CV, Masa Kontrak, Hitungan mundur sisa masa kontrak (Hari), Status Review, dan tautan scan kontrak digital.
3. **Tab 3: Rekam Kompensasi & Histori Gaji**:
   - Gaji awal kontrak vs gaji saat ini, siklus evaluasi berkala (4 / 6 bulan), log riwayat kenaikan gaji 1, 2, dan 3.
4. **Tab 4: Rekap Kehadiran Bulanan**:
   - Matriks kalender 1–31 harian, statistik hadir riil, akumulasi keterlambatan, izin, sakit, dan mangkir.
5. **Tab 5: Rekam Lembur & Reward**:
   - Riwayat lembur harian & batch pencairan Sabtu `PAID_COMPLETED`, serta riwayat penerimaan reward/penghargaan.
6. **Tab 6: Transisi & Offboarding (Jika Non-Aktif)**:
   - Data pengunduran diri/terminasi, kepatuhan notice period, clearance sheet bebas tanggungan, dan status penerbitan Paklaring resmi.

---

### D. Mekanisme Sinkronisasi Google Drive & In-App PDF Viewer
Untuk menjamin kapasitas disk hosting lokal tetap ringan (*0 MB Local Storage Waste*):
1. **Auto-Stream ke Google Drive**:
   - Setiap berkas yang diunggah (Kontrak PKWT, Bukti Sakit, RAB, Proposal, LPJ, Surat Eksternal) dialirkan langsung ke Google Drive perusahaan melalui API ke dalam subfolder terstruktur:
     - `📁 Google Drive / NISGroup HCM / 01_Dokumen_Internal`
     - `📁 Google Drive / NISGroup HCM / 02_Surat_Masuk_Keluar`
     - `📁 Google Drive / NISGroup HCM / 03_Kontrak_PKWT`
     - `📁 Google Drive / NISGroup HCM / 04_Surat_Dokter_Presensi`
   - File temporer di server lokal langsung dihapus otomatis (*auto-unlink*).
2. **In-App PDF Viewer (Baca Langsung Tanpa Download)**:
   - Pengguna dapat mengklik tombol **`[ 👁️ Lihat Dokumen ]`** untuk membuka Modal interaktif di dalam web (menggunakan Google Drive PDF Preview iframe) lengkap dengan fitur zoom, scroll, dan baca halaman penuh tanpa harus mengunduh file ke komputer lokal.
   - Tersedia pula tombol sekunder **`[ ↗ Buka di Google Drive ]`** untuk membuka tautan asli di tab baru.

---

## 8. Roadmap Pelaksanaan Bertahap (Execution Plan)

### Tahap 1: Pondasi Database, Master Data HCM, RBAC & Profil 360° (Minggu 1)
- [ ] Buat file migrasi database:
  - `hcm_master_categories` & `hcm_master_options` (Manajemen mandiri 22 kategori Master Data HCM untuk dropdown dinamis).
  - `hcm_employees` & `hcm_interns` (Master Karyawan Umum & Siswa Magang SMK).
  - `hcm_contracts` (Kontrak PKWT, hitungan sisa hari, review status).
  - `hcm_compensations` & `hcm_compensation_histories` (Gaji & siklus evaluasi 4/6 bulan).
  - `hcm_employee_rewards` (Pencatatan reward barang/tiket & status penyaluran).
- [ ] Buat Seeder Faktual Blueprint:
  - Seed seluruh 22 kategori Master Data HCM beserta seluruh opsi bawaan dari lembar kerja *DropDown*.
  - Seed master karyawan faktual dari lembar kerja *Database*: Bambang Sadewo (Managerial/Tetap), Puji Astuti (Kontrak/PKWT Lanjutan), Danang (Borongan/PKWT), dan siswa magang SMK 2 Lamongan.
- [ ] Konfigurasi Spatie Role & Permission (tambahkan role `admin_hcm` & `staff_hcm`, integrasikan 16 permissions `hcm.*`, berikan `finance.sign-paid` ke `admin_keuangan`, serta daftarkan `admin_hcm` ke `hasAccessToBrand` di `User.php`).
- [ ] Integrasikan Section Menu **"👥 KEPEGAWAIAN"** di `SidebarContent.jsx`.
- [ ] Bangun Halaman **Master Data dengan Tab Menu Vertikal** (`resources/js/Pages/Hcm/MasterData/Index.jsx`).
- [ ] Bangun Antarmuka Master Karyawan & Profil 360° Berbasis 6 Tab (`resources/js/Pages/Hcm/Employees/Show.jsx`).

### Tahap 2: Absensi Harian, Bulk Matrix & Kalkulasi Lembur Mingguan (Minggu 2)
- [ ] Buat migrasi `hcm_attendances`, `hcm_leave_requests`, `hcm_overtimes`, `hcm_overtime_batches`.
- [ ] Bangun antarmuka **Bulk Daily Attendance Matrix Editor** (Quick Logger H/T/I/S/A, set semua hadir, upload bukti sakit).
- [ ] Bangun modul Pengajuan & Persetujuan Cuti/Izin/Sakit dengan validasi alasan penolakan.
- [ ] Implementasikan Backend Engine Kalkulasi Lembur:
  - Aturan Rp 10.000 (weekday) vs Rp 15.000 (weekend).
  - Aturan 30 menit pertama (Rp 5.000 / Rp 10.000).
  - Cut-off mingguan: Sabtu 00:00 s.d. Jumat 23:59.
- [ ] Bangun antarmuka **Bulk Overtime Dispatcher** (Input lembur regu kerja massal).
- [ ] Implementasikan tombol `[Approve & Sign HCM]` untuk mengunci batch lembur mingguan ke status `APPROVED_BY_HCM`.

### Tahap 3: Double Sign-Off Keuangan, Uang Makan, Events & Dashboard HCM (Minggu 3)
- [ ] Bangun antarmuka Divisi Keuangan: Review antrean lembur mingguan & eksekusi tombol `[Sign & Paid]`.
- [ ] Implementasikan Backend Engine Uang Makan Bulanan:
  - Perhitungan standar Rp 70.000/minggu (Rp 280.000/bulan).
  - Aturan pemotongan Alpha & Setengah Hari; Cuti/Dinas Luar tidak dipotong.
  - Aturan toleransi keterlambatan 3x; Sanksi telat $\ge 4\times$ ditahan (*Hold*) ke bulan berikutnya.
  - Aturan penandaan pembatalan bonus jika izin $> 2\times$ sebulan.
- [ ] Buat migrasi `hcm_company_events` untuk agenda internal, undangan karyawan, dan libur tahunan.
- [ ] Bangun **Dynamic Birthday & Work Anniversary Engine** (otomatis agregasi tanggal lahir dan masa kerja).
- [ ] Bangun **Dashboard Interaktif HCM**:
  - Blok Urgent & Action Needed (Probation H-7, Kontrak H-30, Pending Approvals).
  - Blok Daily Schedule (Daftar Izin Hari Ini, Unexcused Absence Alert Merah).
  - Blok Payroll Reminders (Lembur Sabtu, Cut-Off Bulanan H-3).
  - Blok Events & Social Calendar (Kalender Interaktif, Ultah H-3, Work Anniversary).
  - Penerapan konsisten 5 kode warna (Merah, Kuning, Biru, Hijau, Abu-abu).

### Tahap 4: Arsip Dokumen, Persuratan, Google Drive & Rekrutmen Pipeline (Minggu 4)
- [ ] Modul Arsip Dokumen Internal: Pengajuan RAB/Proposal dan Realisasi LPJ/Aset.
- [ ] Modul Korespondensi Eksternal: Surat Masuk & Surat Keluar (BPJS, Disnaker, Bank).
- [ ] Modul Buku Agenda Penomoran Surat Resmi (`AGD-YYYY-XXX`).
- [ ] Integrasi Google Drive API & In-App PDF Modal Viewer (baca langsung tanpa download).
- [ ] Modul Master Lowongan Kerja (Loker): Kuota, departemen, saluran rekrutmen, PIC HCM.
- [ ] Pipeline Pelamar & Rekap Wawancara: Kanban pelamar, screening, catatan wawancara, offering response, dan Blacklist Engine.
- [ ] Dashboard Laporan Performa Rekrutmen: *Fulfillment Rate*, *Funnel Conversion*, *Time-to-Hire*, dan ROI saluran.
- [ ] Modul Transisi: Onboarding checklist dan Offboarding Clearance Sheet (termasuk status Paklaring).
- [ ] Modul Penyaluran Reward & Penghargaan Karyawan.

### Tahap 5: Cetak Dokumen PDF Resmi (Laravel-DomPDF), Audit & Uji Sistem (Minggu 5)
- [ ] Integrasi `ActivityLog` untuk audit trail perubahan data krusial dan otorisasi finansial.
- [ ] Implementasi Template PDF Resmi via `barryvdh/laravel-dompdf`:
  - Cetak Buku Profil Karyawan Lengkap (*Employee Dossier PDF*)
  - Cetak Slip Bukti Bayar Lembur Mingguan (*Payment Voucher PDF*)
  - Cetak Rekapitulasi Presensi & Uang Makan Bulanan
  - Cetak Surat Pengalaman Kerja Resmi (*Paklaring PDF*)
- [ ] Fitur Ekspor Excel Rekapitulasi via `maatwebsite/excel`.
- [ ] Pengujian menyeluruh (*Feature & Unit Testing* pada double sign-off, formula kalkulasi upah, dan proteksi role).

---

## 9. Standar Kualitas & Kriteria Selesai (Definition of Done)

1. **Akurasi 100% Terhadap Blueprint**: Seluruh tabel, field, rumus perhitungan, opsi dropdown, dan alur validasi dari kelima sheet `Blueprint Website HCM NIS.xlsx` terimplementasi penuh tanpa ada halusinasi data.
2. **Harmoni Sistem & Navigasi**: Modul HCM menyatu mulus di `SidebarContent.jsx` NISReport dan terikat dengan Spatie Permission serta tabel notifikasi existing.
3. **Efisiensi Bulk Logger**: Input absensi 50+ karyawan dapat diselesaikan dalam hitungan detik melalui *Bulk Attendance Matrix Editor*.
4. **Keamanan Finansial Terjamin**: Alur *Double Sign-Off* memastikan uang lembur dan uang makan terkunci permanen pasca persetujuan Tim Keuangan.
5. **Transparansi Dokumen Tanpa Beban Hosting**: Seluruh berkas digital tersimpan aman di Google Drive dan dapat langsung dibaca di web melalui *In-App PDF Viewer*.
