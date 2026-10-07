# Rencana Pengembangan Sistem HRIS / HCM (Human Capital Management) NISGroup

Dokumen ini merupakan perencanaan teknis, arsitektur data, logika bisnis, dan alur operasional komprehensif implementasi modul **HRIS / HCM** pada platform NISReport. Dokumen ini diperbarui secara faktual 100% berdasarkan analisis mendalam dan komparasi terhadap **`Blueprint Website HCM NIS 2.xlsx`** (mencakup seluruh 6 sheet: *Dashboard*, *Alur & Validasi*, *Business Rules*, *Database*, *DropDown*, dan *Struktur Fungsi Kerja*).

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
   - Bertanggung jawab penuh atas validitas data input (absensi, pengajuan izin/cuti/sakit, izin keluar kantor, penyesuaian/potongan gaji, jam lembur riil, dan rekap akumulasi hari hadir).
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
    │   ├── Manajemen Kontrak Aktif & Renewal
    │   └── Pengelompokan Masa Kerja (Bucket 1, 2, 3+ Thn)
    ├── 💰 Kompensasi & Gaji        (route: 'hcm.compensations.index')
    │   ├── Honor/Gaji & Riwayat Kenaikan (Increment Tracker)
    │   └── Penyesuaian & Potongan Gaji Bulanan (Deductions)
    ├── 💳 Payroll & Penggajian Terpadu (route: 'hcm.payroll.index')
    │   ├── Rekapitulasi Gaji per Departemen & Divisi
    │   ├── Pemrosesan Siklus Mundur Bulan (Work Period vs Payout Period)
    │   ├── Rincian Slip Gaji Digital & Ekspor Transfer Bank BRI
    │   └── Double Sign-Off Otorisasi Kas (HCM & Keuangan)
    ├── ⏱️ Presensi & Ketidakhadiran (route: 'hcm.attendance.index')
    │   ├── Matrix Editor Absensi Harian (Bulk Logger)
    │   ├── Pengajuan Cuti, Izin & Sakit
    │   └── Izin Keluar Kantor (Gate Pass)
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
  - **`admin_hcm`** *(Role Baru)*: Administrator utama Kepegawaian / HR Manager. Memiliki akses penuh ke seluruh modul Kepegawaian, otorisasi persetujuan cuti/izin, evaluasi kontrak PKWT, manajemen rekrutmen, Master Data dinamis, serta menandatangani `[Approve & Sign HCM]` untuk lembur, uang makan, dan draf payroll bulanan.
  - **`staff_hcm`** *(Role Baru)*: Staf operasional kepegawaian. Bertugas melakukan input absensi harian massal (Bulk Matrix Logger), input berkas pelamar, input data lembur harian, pencatatan buku agenda surat, dan jadwal event kalender.
  - **`admin_keuangan`** *(Role Eksisting)*: Bertindak sebagai **Gatekeeper Dana** yang mengevaluasi antrean lembur mingguan, uang makan bulanan, dan payroll terpadu yang telah disahkan HCM, lalu mengeksekusi penandatanganan `[Sign & Paid]`.
  - **`admin_produksi`** *(Role Eksisting)*: Memantau presensi tim produksinya di lantai pabrik dan mengajukan jam lembur regu kerja via fitur Bulk Overtime.
  - **`superadmin` & `owner`** *(Role Eksisting)*: Memiliki akses pengawasan menyeluruh terhadap dashboard kepegawaian, audit trail aktivitas, serta rekapitulasi pencairan kas yang telah disahkan.

- **Akses Lintas Entitas / Brand (`User::hasAccessToBrand`)**:
  Sebagaimana `admin_keuangan` dan `admin_produksi`, role `admin_hcm` dan `staff_hcm` ditambahkan ke dalam daftar role global pada method `hasAccessToBrand()` di model `User.php` agar dapat mengelola data karyawan lintas seluruh entitas hukum NISGroup (**CV Jersey Ekonomis**, **CV Apparel Allegiant**, **CV Bawang Merah**, dan **CV Bawang Putih**).

- **Daftar Izin Granular (`permissions`) Mengikuti Konvensi `<domain>.<action>`**:
  ```php
  // Permission Baru Modul Kepegawaian (HCM)
  'hcm.view',                   // Melihat menu dan dashboard Kepegawaian
  'hcm.manage-master',          // CRUD 23 kategori Master Data dinamis (Job Level, Divisi, Posisi, CV, Status Lampiran, dll.)
  'hcm.manage-employees',       // CRUD data master karyawan dan peserta magang SMK
  'hcm.manage-contracts',       // CRUD kontrak kerja PKWT, renewal & grouping masa kerja
  'hcm.manage-compensation',    // Mengelola data gaji/honor, riwayat kenaikan upah & siklus evaluasi
  'hcm.manage-salary-adjustments', // Mengelola penyesuaian & pemotongan gaji bulanan (deductions)
  'hcm.manage-payroll',         // Menyusun periode payroll, kalkulasi take-home pay terpadu & grouping departemen-divisi
  'hcm.sign-payroll',           // Otorisasi [Approve & Sign HCM] mengunci draf payroll bulanan ke Keuangan
  'hcm.manage-attendance',      // Akses Bulk Matrix Absensi Harian & approval cuti/izin/sakit
  'hcm.manage-exit-permits',    // Mengelola log & approval izin keluar kantor (gate pass)
  'hcm.manage-overtime',        // Input jam lembur harian & penyusunan batch mingguan
  'hcm.sign-overtime',          // Otorisasi [Approve & Sign HCM] untuk mengunci lembur ke Keuangan
  'hcm.manage-meal-allowance',  // Rekapitulasi hari hadir, potongan kehadiran & hold logic uang makan
  'hcm.sign-meal-allowance',    // Otorisasi [Approve & Sign HCM] untuk mengunci uang makan ke Keuangan
  'hcm.manage-rewards',         // Mengelola rekapitulasi penyaluran reward/penghargaan karyawan
  'hcm.manage-recruitment',     // Mengelola master loker, pipeline pelamar, wawancara & blacklist
  'hcm.manage-documents',       // Mengelola arsip dokumen internal (RAB/LPJ) & eksternal (BPJS/Disnaker)
  'hcm.manage-agenda',          // Mengelola buku agenda penomoran surat resmi masuk/keluar
  'hcm.manage-events',          // Mengelola company calendar, agenda internal & undangan sosial
  'hcm.export-reports',         // Ekspor PDF Dossier Karyawan, slip gaji, slip lembur, rekap absensi, dan paklaring

  // Permission Tambahan untuk Divisi Keuangan
  'finance.sign-paid',          // Otorisasi [Sign & Paid] pencairan kas lembur & uang makan (diberikan ke admin_keuangan)
  'finance.manage-payroll',     // Mengakses dashboard payroll & memproses final take-home pay pasca potongan HCM
  'finance.sign-payroll',       // Otorisasi [Sign & Paid] pencairan kas payroll bulanan terpadu (admin_keuangan)
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

Berdasarkan lembar kerja **`DropDown `** dan sheet baru **`Struktur Fungsi Kerja `** pada Blueprint Excel 2, kebutuhan seluruh data pilihan/dropdown dikelompokkan ke dalam modul **Master Data HCM** (`/hcm/master-data`):
1. **Fokus Khusus Data Dropdown Dinamis**: Modul ini khusus difokuskan untuk mengelola seluruh opsi dropdown yang ada di sistem (seperti Job Level, Departemen, Divisi, Posisi, Status Lampiran, Status Ketenagakerjaan, CV, Kategori Dokumen, dll.) agar dapat ditambah, diubah, atau dinonaktifkan secara mandiri oleh Admin HCM melalui panel admin tanpa perlu mengubah kode program (*zero code deployment*).
2. **Hak Akses Khusus**: Pengaturan CRUD Master Data HCM dibatasi hanya untuk role `admin_hcm` / `superadmin` (permission `hcm.manage-master`). Staf dan karyawan operasional hanya dapat membaca dan memilih opsi aktif saat mengisi formulir.

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

### Rincian 24 Kategori Master Data HRIS (100% Sesuai Blueprint Excel 2):

| No | Kategori Data Master | Kelompok Grup | Opsi Default dari Blueprint Excel 2 |
| :---: | :--- | :--- | :--- |
| 1 | **Job Level** | Kepegawaian & Struktur | Direksi, Manager, PIC, Supervisor, Leader, Staff, Trainee, Harian, Borongan |
| 2 | **Departemen** *(Baru)* | Kepegawaian & Struktur | Finance, Human Capital Management, Marketing, Produksi, Media Internal, Media Eksternal |
| 3 | **Divisi** | Kepegawaian & Struktur | Finance, HCM, Marketing, Produksi, Media Internal, Media Eksternal |
| 4 | **Posisi / Jabatan** | Kepegawaian & Struktur | Finance, Accounting, Purchasing, Human Capital Management, Admin HCM, Marketing, Admin Brand, Designer, Produksi, Admin Produksi, Setting Printing, Potong Bahan, Press Sublime, Potong Pola, Jahit, Quality Control, Finishing (Press), Finishing (Steam), Finishing (Packing), Operasional, Media Internal, Media Spesialist, Publisher, Editor, Planner, Media Eksternal, Web Editor, Web Developer |
| 5 | **Status Ketenagakerjaan** | Kepegawaian & Struktur | Tetap (PKWTT), Kontrak (PKWT), PKWT Lanjutan, Freelance / Lepas, Trainee (Probation), Paruh Waktu (Part-Time), Harian, Borongan, Magang (Internship) |
| 6 | **Entitas Legal (CV)** | Kepegawaian & Struktur | CV Jersey Ekonomis, CV Apparel Allegiant, CV Bawang Merah, CV Bawang Putih |
| 7 | **Status Lampiran** *(Baru)* | Kepegawaian & Struktur | Terlampir, Tidak Terlampir |
| 8 | **Status Review Kontrak** | Kontrak & Legalitas | Aktif (Aman / Jauh dari Masa Berakhir), Mendekati Evaluasi (H-60 Kontrak Berakhir), Wajib Review & Tindak Lanjut (H-30 Kontrak Berakhir), Masa Tenggang / Proses Keputusan (H-14 s.d. Hari H), Pengajuan Perpanjangan (Renewal Process), Disetujui untuk Diperpanjang, Pengangkatan Menjadi Karyawan Tetap (Converted to Permanent), Kontrak Selesai & Tidak Diperpanjang (Non-Renewal / Offboarding), Resign / Berhenti atas Permintaan Sendiri selama Masa Kontrak |
| 9 | **Status Pengajuan / Honor** | Kompensasi & Gaji | Draft, Sedang Diajukan / Pending, Menunggu Persetujuan Atasan / Manager, Menunggu Verifikasi HR / Finance, Revisi / Perbaikan, Disetujui (Menunggu Masa Berlaku), Aktif / Berlaku Bulan Ini (Ready to Pay), Selesai (Paid), Berakhir / Expired |
| 10 | **Kategori Potongan Gaji** *(Baru)* | Kompensasi & Gaji | Pelanggaran (Disciplinary Penalty), Kelebihan Pengambilan Cuti (Leave Exceed / Unpaid Leave), Cuti Khusus Berjenjang (Maternity Leave / Tiered Deduction) |
| 11 | **Kategori Kehadiran** | Presensi & Absensi | Hadir, Terlambat, Pulang Cepat, Cuti, Izin, Sakit, Dinas Luar, Alpha/Mangkir, Libur/Cuti Bersama |
| 12 | **Jenis Hari Lembur** | Presensi & Lembur | Lembur Hari Kerja, Lembur Hari Libur |
| 13 | **Kategori Dokumen Pengajuan** | Dokumen Internal | Pengajuan RAB (Rencana Anggaran Biaya), Proposal Kegiatan / Acara, Pengajuan Pembelian Aset / Inventaris, Pengajuan Perjalanan Dinas / Surat Tugas |
| 14 | **Kategori Dokumen Realisasi** | Dokumen Internal | LPJ (Laporan Pertanggungjawaban) Kegiatan, Realisasi Pembelian Aset & Nota/Faktur Pembelanjaan, Laporan & Bukti Pengeluaran Perjalanan Dinas (Reimburse / Settlement) |
| 15 | **Dokumen Administratif & Kebijakan** | Dokumen Perusahaan | Surat Keputusan (SK) & Kebijakan Internal, Kontrak / Perjanjian Kerjasama (Vendor / Partner), Standard Operating Procedure (SOP), Surat Peringatan (SP 1 / SP 2 / SP 3), Surat Keputusan / Pemberitahuan PHK, Surat Pengalaman Kerja (Paklaring), Surat Pengumuman Internal (Mutasi, Promosi, atau Kebijakan), Berita Acara / Surat Klarifikasi, Surat Tugas & Perjalanan Dinas (SPPD) |
| 16 | **Kepatuhan Notice Period** | Offboarding & Terminasi | Sesuai Ketentuan (1 Bulan / Full Notice), Kurang dari Ketentuan (Short Notice < 1 Bulan), Tanpa Notice (Immediate / Walk Out), Garden Leave (Dibebastugaskan), Pemutusan oleh Perusahaan (Immediate Termination) |
| 17 | **Hak Karyawan (Sisa Hak)** | Offboarding & Terminasi | Lunas & Dibayarkan Penuh (Full Settlement Paid), Dipotong / Ada Penyesuaian (Deducted / Adjusted), Ditahan Sebagian (Partially Held), Belum Dibayarkan / Pending (Unpaid) |
| 18 | **Pengembalian Aset & Dokumen** | Offboarding & Terminasi | Lengkap & Terbit, Belum Lengkap / Aset Ditahan, Tidak Terbit |
| 19 | **Status Clearance Sheet** | Offboarding & Terminasi | Pending, Selesai (Clear) |
| 20 | **Status Penyaluran Reward** | Apresiasi & Reward | Belum Diterima, Sudah Diterima (Serah Terima Langsung), Sudah Ditransfer, Tertunda / Pending, Dibatalkan |
| 21 | **Jenis Kelamin** | Demografi Karyawan | Laki-Laki, Perempuan |
| 22 | **Agama** | Demografi Karyawan | Islam, Kristen, Katolik, Hindu, Buddha, Konghucu |
| 23 | **Pendidikan Terakhir** | Demografi Karyawan | SD / Sederajat, SMP / Sederajat, SMA / SMK / Sederajat, Diploma 1 (D1), Diploma 2 (D2), Diploma 3 (D3), Diploma 4 (D4), Strata 1 (S1), Strata 2 (S2), Strata 3 (S3) |
| 24 | **Status Pernikahan** | Demografi Karyawan | Belum Menikah, Menikah, Cerai Hidup, Cerai Mati |
| 25 | **Ukuran Baju Seragam** | Fasilitas & Atribut | S, M, L, XL, XXL, XXXL, XXXXL |

---

### Struktur Organisasi & Pemetaan Fungsi Kerja (Sheet: `Struktur Fungsi Kerja`)

Berdasarkan sheet baru **`Struktur Fungsi Kerja `** dan keselarasan dengan migrasi resmi (`2026_10_06_090000_sync_hcm_departments_with_official_codes.php`), **KODE DEPARTEMEN RESMI TETAP MENGGUNAKAN KODE BAKU PERUSAHAAN**, namun strukturnya kini **DIKELOMPOKKAN SECARA HIERARKIS 3 TINGKAT**:
1. **Tingkat 1: Departemen (Department)**: Unit organisasi induk dengan Kode Departemen Resmi NIS Group (`FIN`, `HCM`, `BRM`, `SCP`, `PRD`, `MIN`, `MEX`).
2. **Tingkat 2: Divisi (Division)**: Sub-unit fungsional di bawah naungan departemen.
3. **Tingkat 3: Posisi / Jabatan (Job Position)**: Peran penugasan operasional karyawan di bawah divisi.

#### Matriks Hierarki Resmi Departemen $\rightarrow$ Divisi $\rightarrow$ Posisi

| No | Kode Dept Resmi | Nama Departemen Resmi | Divisi Sub-Unit | Posisi / Jabatan Operasional yang Dibawahi |
| :---: | :---: | :--- | :--- | :--- |
| 1 | **FIN** | **Finance & Accounting** | Divisi Finance | Accounting, Purchasing |
| 2 | **HCM** | **Human Capital Management** | Divisi HCM | Admin HCM |
| 3 | **BRM** | **Brand & Marketing** | Divisi Marketing | Admin Brand, Designer |
| 4 | **PRD** | **Produksi** | Divisi Produksi | Admin Produksi, Setting Printing, Potong Bahan, Press Sublime, Potong Pola, Jahit, Quality Control, Finishing (Press), Finishing (Steam), Finishing (Packing), Operasional |
| 5 | **MIN** | **Media Internal** | Divisi Media Internal | Media Spesialist, Publisher, Editor, Planner |
| 6 | **MEX** | **Media Eksternal** | Divisi Media Eksternal | Media Spesialist, Web Editor, Web Developer |

```
NIS GROUP HIERARCHICAL STRUCTURE
├── 🏢 [FIN] FINANCE & ACCOUNTING
│   └── 🏛️ Divisi Finance
│       ├── Accounting
│       └── Purchasing
│
├── 🏢 [HCM] HUMAN CAPITAL MANAGEMENT
│   └── 🏛️ Divisi HCM
│       └── Admin HCM
│
├── 🏢 [BRM] BRAND & MARKETING
│   └── 🏛️ Divisi Marketing
│       ├── Admin Brand
│       └── Designer
│
├── 🏢 [PRD] PRODUKSI
│   └── 🏛️ Divisi Produksi
│       ├── Admin Produksi
│       ├── Setting Printing
│       ├── Potong Bahan
│       ├── Press Sublime
│       ├── Potong Pola
│       ├── Jahit
│       ├── Quality Control
│       ├── Finishing (Press)
│       ├── Finishing (Steam)
│       ├── Finishing (Packing)
│       └── Operasional
│
├── 🏢 [MIN] MEDIA INTERNAL
│   └── 🏛️ Divisi Media Internal
│       ├── Media Spesialist
│       ├── Publisher
│       ├── Editor
│       └── Planner
│
└── 🏢 [MEX] MEDIA EKSTERNAL
    └── 🏛️ Divisi Media Eksternal
        ├── Media Spesialist
        ├── Web Editor
        └── Web Developer
```

> **Aturan Multi-Level Grouping pada Sistem & Payroll**:
> 1. Pada data master karyawan (`hcm_employees`), atribut `department`, `division`, dan `position` saling terikat (cascading dependency).
> 2. Pada seluruh laporan manajerial, rekapitulasi kehadiran, lembur mingguan, uang makan bulanan, dan **terutama Rekapitulasi Penggajian (Payroll)**, data wajib disajikan dalam format **bertingkat (Multi-Level Grouping)**:
>    - **Level Group Induk**: Departemen (menampilkan total headcount dan subtotal biaya departemen).
>    - **Level Sub-Group**: Divisi (menampilkan rincian subtotal per divisi, contoh: Subtotal Divisi Jahit, Divisi Setting Printing).
>    - **Level Detail Item**: Data masing-masing karyawan beserta jabatannya.

---

## 3. Kamus Data & Spesifikasi Detail Field (16 Modul Database Blueprint 2)

Berdasarkan lembar kerja **`Database`** pada Blueprint Excel 2, berikut adalah skema lengkap setiap tabel:

### A. Modul 1: Master Karyawan Data Umum (`hcm_employees`)
Menampung seluruh tenaga kerja Managerial, Kontrak, Borongan, Harian, dan Siswa Magang.

| Label Kolom UI | Nama Kolom DB | Tipe Data | Validasi / Aturan | Keterangan & Kontrol UI |
| :--- | :--- | :--- | :--- | :--- |
| **Kategori Tenaga Kerja** | `category` | `varchar(50)` | `required` | Managerial, Kontrak, Borongan, Harian, Magang |
| **Nama Lengkap** | `full_name` | `varchar(150)` | `required, string` | Nama lengkap resmi sesuai KTP |
| **Nama Panggilan** | `nickname` | `varchar(50)` | `required, string` | Nama panggilan akrab (untuk badge & matrix) |
| **Departemen** *(Baru)* | `department` | `varchar(100)` | `required` | Finance, HCM, Marketing, Produksi, Media Internal, Media Eksternal |
| **Divisi** | `division` | `varchar(100)` | `nullable` | Divisi unit kerja spesifik |
| **Posisi / Jabatan** | `position` | `varchar(100)` | `required` | Pilihan dari 29 posisi resmi Blueprint 2 |
| **Level / Jenjang** | `job_level` | `varchar(50)` | `required` | Pilihan dari dropdown Job Level |
| **Tanggal Bergabung Pertama**| `original_join_date`| `date` | `required, date` | **Permanen / Kunci Utama**: Menghitung masa kerja kumulatif |
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
Pencatatan riwayat perjanjian kerja waktu tertentu (PKWT), perpanjangan (renewal), dan pengelompokan masa kerja.

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
| **Tanggal Mulai Kontrak Aktif**| `start_date` | `date` | `required, date` | Tanggal efektif mulai kontrak yang sedang berjalan |
| **Tanggal Berakhir Kontrak** | `end_date` | `date` | `nullable, date` | Tanggal berakhir (null jika Tetap) |
| **Sisa Masa Kontrak (Hari)** | `days_remaining` | `virtual / calc` | Auto-calculated | `DATEDIFF(end_date, CURDATE())` |
| **Status Review Kontrak** | `review_status` | `varchar(100)` | `required` | Dropdown Status Review (Aktif, Mendekati H-60, Wajib Review H-30, Masa Tenggang H-14, dll) |
| **Pengelompokan Masa Kerja**| `tenure_bucket` | `virtual / calc` | Auto-calculated | **Kelompok 1 Tahun** (12-23 bln), **Kelompok 2 Tahun** (24-35 bln), **Kelompok 3 Tahun+** (36+ bln) |
| **File Dokumen Digital** | `file_contract_url` | `varchar(255)` | `nullable` | Link Google Drive scan kontrak fisik |

> **Aturan Contract Renewal Workflow**:
> Ketika kontrak diperpanjang, nomor kontrak baru dan masa berlaku baru dicatat, kontrak lama diarsipkan ke tabel historis, namun **`original_join_date` tetap dipertahankan** agar rekam jejak loyalitas dan kenaikan upah kumulatif tidak hilang.

---

### D. Modul 4: Kompensasi & Riwayat Honor/Gaji (`hcm_compensations` & `histories`)
Pencatatan gaji/honor, evaluasi berkala kenaikan upah, dan perencanaan anggaran (*planned increment*).

| Label Kolom UI | Nama Kolom DB | Tipe Data | Validasi / Aturan | Keterangan & Kontrol UI |
| :--- | :--- | :--- | :--- | :--- |
| **Karyawan** | `employee_id` | `foreignId` | `required, exists:hcm_employees,id` | Relasi data karyawan |
| **Status Ketenagakerjaan** | `employment_status` | `varchar(50)` | `required` | Karyawan Tetap, PKWT, PKWT Lanjutan |
| **CV Entitas** | `legal_entity` | `varchar(100)` | `required` | Entitas pemberi kerja |
| **No. Kontrak Terkait** | `contract_number` | `varchar(100)` | `nullable` | Referensi naskah kontrak kerja |
| **Masa Kontrak** | `duration_text` | `varchar(50)` | `nullable` | e.g. 1 Tahun, 2 Tahun, Tetap |
| **Masa Kerja Trainee (Bulan)**| `trainee_duration_months`| `integer` | `nullable` | Durasi training (e.g. 8, 12, 24 bulan) |
| **Siklus Evaluasi (Bulan)** | `evaluation_cycle_months`| `integer` | `required, default:6` | Siklus review berkala (default: 6 bulan, dapat custom milestone) |
| **Honor Awal Kontrak (Rp)** | `initial_salary` | `decimal(15,2)` | `required, min:0` | Nominal gaji permulaan kerja |
| **Honor Saat Ini (Rp)** | `current_salary` | `decimal(15,2)` | `required, min:0` | Nominal gaji yang sedang berjalan aktif |
| **Rencana Kenaikan Berikutnya**| `planned_increment` | `decimal(15,2)` | `nullable` | Proyeksi rencana kenaikan upah untuk pelaporan ke owner |
| **Total Kenaikan (Kali)** | `salary_increment_count` | `integer` | `default:0` | Frekuensi perolehan kenaikan honor |
| **Histori Kenaikan 1 (Rp)** | `increment_1_amount` | `decimal(15,2)` | `nullable` | Riwayat nominal kenaikan pertama |
| **Histori Kenaikan 2 (Rp)** | `increment_2_amount` | `decimal(15,2)` | `nullable` | Riwayat nominal kenaikan kedua |
| **Histori Kenaikan 3 (Rp)** | `increment_3_amount` | `decimal(15,2)` | `nullable` | Riwayat nominal kenaikan ketiga |
| **Status Keputusan Evaluasi**| `decision_status` | `varchar(50)` | `default:Sedang Diajukan` | Pilihan: `Sedang Diajukan`, `Sudah Disetujui / ACC`, `Ditunda`, `Tidak Naik` |
| **Tanggal Efektif Kenaikan**| `effective_date` | `date` | `nullable` | Tanggal mulai berlakunya nominal baru (terpisah dari tgl evaluasi) |
| **Status Pengajuan/Honor** | `salary_status` | `varchar(100)` | `required` | Dropdown Status Pengajuan/Honor (Draft, Sedang Diajukan, Telah Berlaku, Selesai, dll) |

> **Logika Mundur Bulan (Work Period vs Payout Period)**:
> Gaji bulan kinerja berjalan (contoh: Oktober) dicairkan di bulan berikutnya (November). Jika kenaikan gaji di-ACC pada evaluasi Oktober, gaji Oktober tetap memakai nominal lama, dan pencairan November memasukkan nominal baru dengan catatan penyesuaian evaluasi.

---

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

### F. Modul 6: Izin Keluar Kantor / Gate Pass (`hcm_office_exit_permits`)
*Modul Baru Faktual dari Blueprint Excel 2 (Sheet Database Baris 33–36)*:
Pencatatan izin meninggalkan area pabrik/kantor selama jam kerja (misal: servis kendaraan/ganti oli, urusan perbankan, dinas mendadak).

| Label Kolom UI | Nama Kolom DB | Tipe Data | Validasi / Aturan | Keterangan & Kontrol UI |
| :--- | :--- | :--- | :--- | :--- |
| **Tanggal Izin** | `permit_date` | `date` | `required, date` | Tanggal izin keluar dilaksanakan |
| **Karyawan** | `employee_id` | `foreignId` | `required, exists:hcm_employees,id` | Karyawan pemohon izin keluar |
| **Posisi / Jabatan** | `position` | `varchar(100)` | `nullable` | Posisi penugasan karyawan |
| **Jam Keluar** | `exit_time` | `time` | `required` | Jam keluar meninggalkan kantor (e.g. 14:30) |
| **Jam Kembali** | `return_time` | `time` | `nullable` | Jam kembali tiba di kantor (e.g. 15:30) |
| **Keterangan / Keperluan** | `purpose` | `varchar(150)` | `required` | e.g. Ganti Oli, Pembuatan Rekening, Keperluan Dinas |
| **Catatan / Alasan Detail** | `notes` | `text` | `nullable` | Keterangan tambahan atau catatan atasan |
| **Status Lampiran** | `attachment_status` | `varchar(30)` | `required` | Pilihan: `Terlampir` / `Tidak Terlampir` |
| **File Bukti Lampiran** | `attachment_url` | `varchar(255)` | `nullable` | Tautan scan/foto bukti urusan di Google Drive |
| **Status Approval** | `status` | `varchar(50)` | `default:APPROVED` | Status persetujuan: Disetujui Leader / HCM |

---

### G. Modul 7: Rekap Lembur Mingguan (`hcm_overtimes` & `batches`)

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

### H. Modul 8: Rekapitulasi Uang Makan Bulanan (`hcm_meal_allowance_batches` & `items`)
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

### I. Modul 9: Penyesuaian & Pemotongan Gaji Bulanan (`hcm_salary_deductions`)
*Modul Baru Faktual dari Blueprint Excel 2 (Sheet Business Rules Bagian 5)*:
Mencatat pemotongan gaji bulanan (Pelanggaran, Kelebihan Cuti, Cuti Khusus Berjenjang) yang diinput oleh HCM dan otomatis terpotong saat Tim Keuangan mencairkan gaji.

| Label Kolom UI | Nama Kolom DB | Tipe Data | Validasi / Aturan | Keterangan & Kontrol UI |
| :--- | :--- | :--- | :--- | :--- |
| **Karyawan** | `employee_id` | `foreignId` | `required, exists:hcm_employees,id` | Karyawan yang terkena penyesuaian |
| **Bulan Gaji Berlaku** | `effective_payroll_month`| `varchar(7)` | `required` | Format: `YYYY-MM` (Target bulan pencairan gaji) |
| **Kategori Pemotongan** | `deduction_category` | `varchar(100)` | `required` | Pilihan: `Pelanggaran`, `Kelebihan Pengambilan Cuti`, `Cuti Khusus Berjenjang` |
| **Tipe Perhitungan** | `calculation_type` | `enum` | `required, in:fixed,percent` | Potongan nominal rupiah tetap atau persentase gaji |
| **Persentase Potongan (%)**| `percentage_rate` | `decimal(5,2)` | `nullable` | Misal 25.00% (Cuti melahirkan bln 1), 50.00% (bln 2) |
| **Nominal Potongan (Rp)** | `deduction_amount` | `decimal(15,2)` | `required, min:0` | Total nominal rupiah pemotongan |
| **Catatan / Keterangan** | `notes` | `text` | `required` | e.g. *"Potongan cuti melahirkan bulan ke-2 sebesar 50%"* |
| **Gaji Pokok Sebelum Potong**| `base_salary_snapshot`| `decimal(15,2)`| `required` | Snapshot gaji pokok aktif bulan berjalan |
| **Gaji Bersih (Take-Home)**| `net_salary_snapshot` | `decimal(15,2)` | `auto-calculated` | `base_salary_snapshot - deduction_amount` |
| **Status Pemrosesan** | `status` | `varchar(50)` | `default:SUBMITTED` | `SUBMITTED` (HCM) $\rightarrow$ `APPLIED_IN_PAYROLL` (Finance) |

---

### J. Modul 10: Master Rekapitulasi Payroll Bulanan & Slip Gaji Terpadu (`hcm_payrolls` & `items`)
*Modul Penggajian Komprehensif Sesuai Business Rules Blueprint 2*:
Mengintegrasikan seluruh komponen penggajian (Gaji Pokok, Tunjangan Uang Makan, Upah Lembur, Penyesuaian Kenaikan, dan Pemotongan Gaji) ke dalam satu batch periode terpadu dengan **Pengelompokan Bertingkat Departemen $\rightarrow$ Divisi**.

#### 1. Header Batch Penggajian Bulanan (`hcm_payrolls`)
| Label Kolom UI | Nama Kolom DB | Tipe Data | Validasi / Aturan | Keterangan & Kontrol UI |
| :--- | :--- | :--- | :--- | :--- |
| **Kode Periode Payroll** | `period_code` | `varchar(50)` | `required, unique` | e.g. `PAY-2026-11` (Batch Pencairan November 2026) |
| **Bulan Kinerja (Work Period)**| `work_period_month`| `varchar(7)` | `required` | Format: `YYYY-MM` (e.g. `2026-10` untuk Kinerja Oktober) |
| **Bulan Pencairan (Payout)** | `payout_period_month`| `varchar(7)` | `required` | Format: `YYYY-MM` (e.g. `2026-11` untuk Pencairan November) |
| **Tanggal Pencairan / Transfer**| `payout_date` | `date` | `required, date` | Tanggal eksekusi transfer dana ke karyawan |
| **Total Headcount** | `total_employees` | `integer` | `required, min:1` | Jumlah karyawan yang masuk dalam payroll batch |
| **Total Gaji Pokok (Rp)** | `total_base_salary`| `decimal(15,2)` | `required` | Akumulasi honor/gaji pokok seluruh karyawan |
| **Total Uang Makan (Rp)**| `total_meal_allowance`| `decimal(15,2)`| `required` | Akumulasi uang makan yang lolos syarat absensi |
| **Total Lembur (Rp)** | `total_overtime_pay`| `decimal(15,2)` | `required` | Akumulasi upah lembur dari cut-off mingguan |
| **Total Penyesuaian (Rp)**| `total_adjustments`| `decimal(15,2)` | `default:0` | Akumulasi kenaikan honor/penyesuaian disetujui |
| **Total Potongan Gaji (Rp)**| `total_deductions`| `decimal(15,2)` | `required` | Akumulasi potongan pelanggaran, cuti & khusus |
| **Grand Total Take-Home Pay**| `total_net_payout` | `decimal(15,2)` | `required` | Total kas bersih yang dikeluarkan perusahaan |
| **Status Approval Batch** | `status` | `varchar(50)` | `default:DRAFT_HCM` | `DRAFT_HCM` $\rightarrow$ `APPROVED_BY_HCM` $\rightarrow$ `APPROVED_BY_FINANCE` $\rightarrow$ `PAID_COMPLETED` |
| **Disahkan Oleh HCM** | `hcm_signed_by` | `foreignId` | `nullable, exists:users,id` | User Admin HCM yang memvalidasi draf |
| **Waktu Tanda Tangan HCM** | `hcm_signed_at` | `timestamp` | `nullable` | Timestamp otorisasi [Approve & Sign HCM] |
| **Disahkan Oleh Keuangan**| `finance_signed_by`| `foreignId` | `nullable, exists:users,id` | User Admin Keuangan yang memproses pencairan |
| **Waktu Tanda Tangan Fin** | `finance_signed_at`| `timestamp` | `nullable` | Timestamp otorisasi [Sign & Paid] Keuangan |
| **Metode Pembayaran** | `payment_method` | `varchar(50)` | `default:Bank Transfer BRI`| Pilihan: Transfer Bank BRI, Tunai / Kas Kecil |
| **Bukti Transfer / Dokumen**| `payment_proof_url`| `varchar(255)`| `nullable, url` | Tautan bukti rekap transfer bank di Google Drive |

#### 2. Detail Rincian Gaji per Karyawan (`hcm_payroll_items`)
| Label Kolom UI | Nama Kolom DB | Tipe Data | Validasi / Aturan | Keterangan & Kontrol UI |
| :--- | :--- | :--- | :--- | :--- |
| **Relasi Batch Payroll** | `payroll_id` | `foreignId` | `required, exists:hcm_payrolls,id` | Referensi batch penggajian |
| **Karyawan** | `employee_id` | `foreignId` | `required, exists:hcm_employees,id` | Karyawan penerima gaji |
| **Departemen** | `department` | `varchar(100)` | `required` | Nama & Kode Departemen Resmi (e.g. `[PRD] Produksi`) |
| **Divisi** | `division` | `varchar(100)` | `required` | Unit divisi kerja (e.g. `Divisi Jahit`) |
| **Posisi / Jabatan** | `position` | `varchar(100)` | `required` | Jabatan riil karyawan saat periode kinerja |
| **Level Jabatan** | `job_level` | `varchar(50)` | `required` | Jenjang jabatan karyawan |
| **Nama Bank & Rekening** | `bank_account_no` | `varchar(50)` | `nullable` | Nomor Rekening Bank BRI untuk transfer massal |
| **Gaji Pokok / Honor (Rp)**| `base_salary` | `decimal(15,2)` | `required, min:0` | Gaji pokok aktif bulan kinerja berjalan |
| **Uang Makan Bersih (Rp)** | `meal_allowance` | `decimal(15,2)` | `default:0` | Hasil audit absensi bulan berjalan (+ hold bulan lalu jika ada) |
| **Upah Lembur Riil (Rp)** | `overtime_pay` | `decimal(15,2)` | `default:0` | Akumulasi total upah lembur batch mingguan |
| **Penyesuaian Kenaikan (Rp)**| `increment_adjustment`| `decimal(15,2)`| `default:0` | Kenaikan gaji hasil evaluasi kinerja efektif |
| **Catatan Penyesuaian** | `adjustment_notes` | `text` | `nullable` | e.g. *"Penyesuaian kenaikan evaluasi kinerja bln Oktober"* |
| **Potongan Pelanggaran (Rp)**| `penalty_deduction`| `decimal(15,2)` | `default:0` | Potongan pelanggaran disiplin/SOP |
| **Potongan Cuti (Rp)** | `leave_deduction` | `decimal(15,2)` | `default:0` | Potongan kelebihan hak cuti tahunan |
| **Potongan Berjenjang (Rp)**| `tiered_deduction` | `decimal(15,2)` | `default:0` | Potongan cuti khusus berjenjang (e.g. Melahirkan 25%/50%) |
| **Total Penghasilan (Gross)**| `total_earnings` | `decimal(15,2)` | `auto-calculated` | `base_salary + meal_allowance + overtime_pay + increment_adjustment` |
| **Total Potongan (Rp)** | `total_deductions` | `decimal(15,2)` | `auto-calculated` | `penalty_deduction + leave_deduction + tiered_deduction` |
| **Gaji Bersih (Take-Home)**| `net_salary` | `decimal(15,2)` | `auto-calculated` | `total_earnings - total_deductions` |
| **Token Slip Gaji Unik** | `slip_token` | `varchar(64)` | `required, unique` | Hash token unik aman untuk unduh/lihat slip digital |
| **Status Pembayaran** | `is_paid` | `boolean` | `default:false` | True saat Keuangan telah menyelesaikan pembayaran |

---

### K. Modul 11: Arsip Dokumen Internal Perusahaan (`hcm_internal_documents`)
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

### K. Modul 11: Arsip Korespondensi Eksternal (`hcm_external_letters`)
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

### L. Modul 12: Buku Agenda Penomoran Surat Masuk & Keluar (`hcm_agenda_letters`)
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

### M. Modul 13: Rekrutmen Pipeline, Loker & Public Career Form (`hcm_job_postings`, `applicants`, `interviews`)

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

### N. Modul 14: Status & Transisi Kepegawaian (Onboarding & Offboarding)

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

### O. Modul 15: Rekapitulasi Penyaluran Reward & Penghargaan (`hcm_employee_rewards`)
*Modul Faktual dari Blueprint Excel (Sheet Database & DropDown Kolom W)*:
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

### P. Modul 16: Company Events & Social Calendar (`hcm_company_events`)
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

### C. Logika Alur & Sistem Reminder Kontrak Karyawan (Sheet Business Rules Bagian 3)
Untuk mendukung pengelolaan siklus kontrak kerja PKWT dan evaluasi berkala secara otomatis:

1. **Skenario A: Evaluasi Berkala & Keputusan Kenaikan Gaji**:
   - **Siklus Berkala Default**: Kontrak 2 tahun (24 bulan) dengan siklus evaluasi setiap 6 bulan.
   - **Bulan ke-6**: Sistem otomatis memunculkan reminder di dashboard HCM bahwa karyawan memasuki periode evaluasi pertama.
   - **Tindakan HCM / Manajemen**: Mengisi formulir evaluasi dan memilih status keputusan:
     - `Sedang Diajukan`
     - `Sudah Disetujui / ACC` $\rightarrow$ Gaji diperbarui, tercatat ke riwayat kenaikan gaji ke-1, dan sistem otomatis menjadwalkan reminder evaluasi berikutnya pada bulan ke-12.
     - `Ditunda` $\rightarrow$ Gaji tetap, sistem dapat menjadwalkan reminder ulang atau melanjutkan siklus reguler ke bulan ke-12 dengan catatan evaluasi sebelumnya.
     - `Tidak Naik` $\rightarrow$ Gaji tetap, tercatat dalam evaluasi kinerja.
   - **Custom Milestone Date**: Jika evaluasi dilakukan pada bulan ke-6 lalu diputuskan kenaikan berikutnya menyusul dalam 3 bulan (bulan ke-9), sistem wajib mengakomodasi *custom milestone date* tanpa merusak master reminder utama di bulan ke-12.

2. **Skenario C: Reminder Masa Berakhir Kontrak (Contract Expiry)**:
   - Sistem wajib mendeteksi **H-30** atau **H-14** sebelum `Tanggal Berakhir Kontrak` (`end_date`) untuk memicu alert ke HCM apakah kontrak akan diperpanjang (PKWT lanjutan), diangkat menjadi karyawan tetap (PKWTT), atau dihentikan (offboarding).

---

### D. Logika Masa Kerja Kumulatif, Perbaruan Kontrak & Pengelompokan (Grouping)

1. **Pemisahan 2 Parameter Tanggal Kunci**:
   - **`Tanggal Mulai Kontrak Aktif (Current Contract Start Date)`**: Melacak masa berlaku naskah kontrak kerja yang sedang berjalan saat ini (misal: kontrak ke-2 dimulai 01 Januari 2026).
   - **`Tanggal Bergabung Pertama (Original Join Date / First Contract Start Date)`**: **Kunci Utama Permanen** (*immutable*). Tanggal ini tidak berubah meskipun karyawan sudah memperpanjang kontrak berkali-kali.

2. **Rumus Kalkulasi Masa Kerja Berjalan**:
   - Sistem menghitung selisih waktu (tahun dan bulan) antara `Original Join Date` hingga Tanggal Hari Ini.
   - *Contoh*: Masuk pertama 10 Januari 2024. Pada Januari 2026 masuk kontrak ke-2. Pada Oktober 2026 masa kerja dihitung dari 10 Januari 2024 = 2 Tahun 9 Bulan (Masuk Kategori Kelompok Tahun ke-2).

3. **Bucket Pengelompokan Durasi Masa Kerja (Grouping Buckets)**:
   - **Kelompok 1 Tahun**: Karyawan dengan masa kerja kumulatif 12 s.d. 23 bulan.
   - **Kelompok 2 Tahun**: Karyawan dengan masa kerja kumulatif 24 s.d. 35 bulan.
   - **Kelompok 3 Tahun / Seterusnya**: Karyawan dengan masa kerja kumulatif $\ge 36$ bulan.
   - *Fungsi*: Memudahkan HCM dan Manajemen menyaring data karyawan yang telah loyal bertahan untuk evaluasi jangka panjang, bonus loyalitas, atau pertimbangan pengangkatan tetap (PKWTT).

4. **Alur Kerja Pembaruan Kontrak (Contract Renewal Workflow)**:
   - Ketika kontrak akan habis dan disetujui untuk diperpanjang:
     1. HCM membuka menu *Contract Renewal*, menginput nomor kontrak baru dan masa durasi baru.
     2. Sistem otomatis mengarsipkan kontrak lama ke riwayat historis kontrak karyawan (`hcm_contracts` archive).
     3. Sistem memperbarui nomor dokumen kontrak aktif dan tanggal berakhir baru.
     4. **Sistem TETAP MEMPERTAHANKAN `Original Join Date`** agar kalkulasi masa kerja kumulatif dan histori kenaikan gaji tidak terhapus.

---

### E. Payroll & Kenaikan Gaji Berbasis Siklus Kontrak (Sheet Business Rules Bagian 4)

1. **Alur Evaluasi Kenaikan Gaji & Sistem Reminder**:
   - **Pemicu Otomatis (Trigger by Contract)**: Jadwal evaluasi dibaca otomatis dari data kontrak.
   - **Notifikasi/Reminder ke HCM**: Dikirim ke dashboard HCM pada bulan evaluasi berjalan.
   - **Pemisahan Tanggal**: `Tanggal Evaluasi` dipisahkan secara tegas dari `Tanggal Efektif Kenaikan (Effective Date)`. Misal evaluasi bulan ke-6, namun disepakati kenaikan baru berlaku efektif 1 bulan atau 3 bulan ke depan.

2. **Rekapitulasi Historis Kenaikan Gaji (Salary Increment Tracker)**:
   - Mencatat: Masa Kerja Kontrak Ke-, Riwayat Nominal Kenaikan Sebelumnya (+Rp 300.000, +Rp 500.000, dll.), Gaji Pokok Saat Ini, Rencana Kenaikan Berikutnya (*Planned Increment* untuk proyeksi keuangan owner), dan Status Pengajuan Honor.

3. **Logika Efektif Gaji & Siklus Payroll (Aturan Mundur Bulan)**:
   - **Prinsip Dasar**: Gaji bulan berjalan dibayarkan/diterima di bulan berikutnya (Gaji bulan Oktober dibayarkan bulan November).
   - **Pemisahan Periode**:
     - *Bulan Kinerja (Work Period)*: Bulan di mana karyawan bertugas (contoh: Kinerja Oktober).
     - *Bulan Pembayaran (Payout Period)*: Bulan di mana gaji dicairkan/ditransfer (contoh: Cair November).
   - **Aturan Saat ACC Kenaikan Gaji**:
     - Jika HCM menyetujui kenaikan dengan *Effective Date* bulan Oktober:
       - *Gaji Periode Oktober*: Tetap menggunakan nominal lama (tidak mengubah pembukuan yang sudah lewat).
       - *Pencairan Bulan November*: Sistem memasukkan nominal baru dan mencantumkan catatan/label penyesuaian (*adjustment note*) bahwa kenaikan bersumber dari evaluasi Oktober.

---

### F. Payroll & Pemotongan Gaji Bulanan (Sheet Business Rules Bagian 5)

1. **Jenis-Jenis Kategori Pemotongan Gaji (Deduction Categories)**:
   - **Pelanggaran (Disciplinary Penalty)**: Pemotongan nominal rupiah tetap atau persentase akibat pelanggaran SOP atau aturan kedisiplinan.
   - **Kelebihan Pengambilan Cuti (Leave Exceed / Unpaid Leave)**: Pemotongan otomatis atau manual ketika karyawan mengambil cuti melampaui kuota jatah cuti resmi.
   - **Cuti Khusus Berjenjang (Maternity Leave / Tiered Deduction)**: Pemotongan bertahap sesuai kebijakan perusahaan (misal: bulan ke-1 masa cuti melahirkan dipotong 25%, bulan ke-2 dipotong 50%, dsb.).

2. **Alur Kerja (Workflow): HCM Input, Keuangan Eksekusi**:
   - **Langkah 1 (Input HCM)**:
     - HCM membuka menu *Monthly Salary Adjustment*.
     - Memilih nama karyawan, kategori pemotongan, nominal (Rp) atau persentase (%), menentukan *Effective Payroll Month* (misal `2026-11`), dan mengisi catatan/alasan.
   - **Langkah 2 (Sinkronisasi ke Dashboard Keuangan)**:
     - Tim Keuangan melihat rekapitulasi data potongan yang telah divalidasi HCM.
     - Sistem otomatis menghitung `Net Salary (Take-Home Pay) = Gaji Pokok/Honor Saat Ini - Total Potongan Bulan Tersebut`.
   - **Langkah 3 (Pembayaran & Rekapitulasi)**:
     - Tim Keuangan mengeksekusi pencairan sesuai take-home pay dengan rincian potongan transparan pada slip gaji karyawan.
   - **Transparansi & Audit Trail**:
     - Seluruh riwayat pemotongan per karyawan terekam dalam database untuk audit manajemen/owner dan pertimbangan evaluasi kinerja berkala.

---

### G. Rekapitulasi Payroll Terpadu & Multi-Level Grouping (Departemen $\rightarrow$ Divisi)

Untuk memastikan akurasi dan transparansi keuangan operasional, sistem penggajian bulanan NIS Group menggabungkan seluruh komponen keuangan ke dalam satu kesatuan sistem terpadu (*Unified Payroll Engine*):

1. **Penyatuan 4 Pilar Komponen Penggajian**:
   - **Gaji Pokok / Honor Aktif**: Ditarik dari `hcm_compensations.current_salary`.
   - **Tunjangan Uang Makan Bulanan**: Ditarik otomatis dari rekapitulasi `hcm_meal_allowance_batches` bulan berjalan (setelah memperhitungkan potongan kehadiran dan aturan penahanan/hold jika terlambat $\ge 4\times$).
   - **Upah Lembur Riil**: Ditarik otomatis dari akumulasi batch lembur mingguan (`hcm_overtime_batches`) dalam cut-off bulan kinerja.
   - **Penyesuaian Kenaikan Gaji**: Ditarik dari evaluasi berkala yang telah di-ACC dengan *Effective Date* bulan berjalan.
   - **Pemotongan Gaji Bulanan**: Ditarik dari `hcm_salary_deductions` (Pelanggaran Disiplin, Kelebihan Cuti, dan Cuti Khusus Berjenjang seperti Melahirkan 25%/50%).

2. **Formula Baku Net Salary (Take-Home Pay)**:
   $$\text{Total Penghasilan (Gross)} = \text{Gaji Pokok} + \text{Uang Makan} + \text{Upah Lembur} + \text{Penyesuaian Kenaikan}$$
   $$\text{Total Pemotongan} = \text{Potongan Pelanggaran} + \text{Potongan Cuti} + \text{Potongan Berjenjang}$$
   $$\mathbf{Net\ Salary\ (Take-Home\ Pay)} = \text{Total Penghasilan (Gross)} - \text{Total Pemotongan}$$

3. **Multi-Level Grouping Penggajian (Departemen $\rightarrow$ Divisi)**:
   Seluruh dashboard penggajian dan dokumen laporan keuangan menyajikan ringkasan bertingkat:
   - **Level 1 (Departemen Induk)**: Menampilkan subtotal headcount dan total belanja gaji per departemen dengan kode resmi (`[FIN]`, `[HCM]`, `[BRM]`, `[SCP]`, `[PRD]`, `[MIN]`, `[MEX]`).
   - **Level 2 (Divisi Kerja)**: Menampilkan rincian subtotal per divisi operasional (contoh: dalam Departemen Produksi terdapat Subtotal Divisi Jahit, Divisi Setting Printing, Divisi Sablon, Divisi Finishing).
   - **Level 3 (Karyawan & Posisi)**: Rincian data individual per karyawan beserta rincian slip, nomor rekening Bank BRI, dan status pembayaran.

4. **Slip Gaji Digital Transparan (PDF)**:
   - Dihasilkan otomatis saat status payroll mencapai `PAID_COMPLETED`.
   - Menampilkan rincian pendapatan, rincian potongan itemized, catatan evaluasi/alasan potongan, subtotal departemen-divisi, dan QR verification hash.

---

## 5. Matriks Alur Kerja & Validasi Operasional (Workflow Matrix)

Berdasarkan lembar kerja **`Alur & Validasi`** dan **`Business Rules`** pada Blueprint Excel, berikut adalah 4 alur operasional utama:

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
| **Alur 4: Siklus Payroll Bulanan Terpadu (Single Gateway)** | **1. Draf & Aggregation** | HCM Admin | Susun draf payroll periode kinerja, tarik lembur, uang makan & input potongan gaji | `DRAFT_HCM` | Sistem memvalidasi kelengkapan data potongan & status evaluasi kenaikan upah. | Tampil preview draf rekap gaji per departemen & divisi. |
| | **2. HCM Sign-Off** | HCM Manager | Review rekapitulasi & Klik tombol `[Approve & Sign Payroll HCM]` | `APPROVED_BY_HCM` | Draf payroll terkunci untuk HCM (Read-Only). Notifikasi otomatis meluncur ke Keuangan. | Masuk antrean Dashboard Keuangan & Payroll. |
| | **3. Finance Audit & Verification**| Tim Keuangan | Review subtotal per Departemen & Divisi, cek rekening Bank BRI | `PENDING_FINANCE_SIGN` | Tim Keuangan mencocokkan total take-home pay dengan saldo bank perusahaan. | Menyiapkan batch transfer massal Bank BRI. |
| | **4. Finance Payout & Sign-Off** | Tim Keuangan | Eksekusi transfer & Klik tombol `[Sign & Mark as Paid]` | `PAID_COMPLETED` | Seluruh data payroll terkunci permanen. Slip Gaji Digital otomatis aktif untuk seluruh karyawan. | Rekapitulasi final tersimpan ke laporan Owner & audit trail. |

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

## 8. Roadmap Pelaksanaan Bertahap (Execution Plan Ideal Berbasis Perubahan Blueprint 2)

Rencana aksi pelaksanaan disusun secara sistematis agar penambahan hierarki Departemen-Divisi dan modul Payroll terpadu terimplementasi secara kokoh tanpa mengganggu fungsi sistem yang sedang berjalan:

### Fase 1: Struktur Organisasi Berjenjang & Master Data Sinkron (100% Selesai ✅)
- [x] **Migrasi Kolom Organisasi & Integritas Masa Kerja**:
  - Tambahkan kolom `division` (varchar 100, nullable) pada tabel `hcm_employees`.
  - Tambahkan kolom `original_join_date` (date) pada tabel `hcm_employees` sebagai *immutable anchor* masa kerja kumulatif.
- [x] **Sinkronisasi Kode Departemen Baku & Opsi Master Data**:
  - Pastikan Kode Departemen Resmi NIS Group tetap menggunakan kode resmi: `FIN` (Finance & Accounting), `HCM` (Human Capital Management), `BRM` (Brand & Marketing), `SCP` (Support & Control Produksi), `PRD` (Produksi), `MIN` (Media Internal), `MEX` (Media Eksternal).
  - Seed master data baru pada `HcmMasterDataSeeder.php`:
    - `status_lampiran`: `Terlampir`, `Tidak Terlampir`.
    - `kategori_potongan_gaji`: `Pelanggaran`, `Kelebihan Pengambilan Cuti`, `Cuti Khusus Berjenjang`.
  - Perbarui relasi hierarkis Departemen $\rightarrow$ Divisi $\rightarrow$ Posisi pada formulir registrasi karyawan dan filter dashboard.
- [x] **Logika Pengelompokan Masa Kerja (Tenure Buckets)**:
  - Buat helper/accessor di model `HcmEmployee` untuk menghitung masa kerja kumulatif dari `original_join_date`:
    - **Kelompok 1 Tahun**: 12–23 bulan.
    - **Kelompok 2 Tahun**: 24–35 bulan.
    - **Kelompok 3 Tahun / Seterusnya**: $\ge 36$ bulan.
  - Tambahkan filter tenure bucket pada daftar karyawan dan kontrak.

### Fase 2: Izin Keluar Kantor (Gate Pass) & Presensi (100% Selesai ✅)
- [x] **Migrasi & Model Izin Keluar Kantor (`hcm_office_exit_permits`)**:
  - Kolom: `employee_id`, `permit_date`, `position`, `exit_time`, `return_time`, `purpose`, `notes`, `attachment_status`, `attachment_url`, `status`.
- [x] **Controller & Komponen Antarmuka**:
  - Buat controller `HcmOfficeExitPermitController` atau integrasikan ke `HcmAttendanceController`.
  - Buat tab / modal *"Izin Keluar Kantor (Gate Pass)"* pada halaman `/hcm/attendance` dengan status lampiran (`Terlampir` / `Tidak Terlampir`).

### Fase 3: Evaluasi Kontrak, Kenaikan Gaji & Pemotongan Gaji Bulanan (Deductions) (100% Selesai ✅)
- [x] **Engine Evaluasi Berkala & Contract Renewal**:
  - Form evaluasi berkala (siklus 6 bulan) dengan opsi keputusan: `Sedang Diajukan`, `Sudah Disetujui / ACC`, `Ditunda`, `Tidak Naik`.
  - Dukungan *custom milestone date* (tunda 3 bulan) tanpa merusak pengingat utama.
  - Workflow *Contract Renewal*: simpan kontrak lama ke arsip historis, perbarui masa berlaku baru, pertahankan `original_join_date`.
- [x] **Migrasi & Modul Pemotongan Gaji Bulanan (`hcm_salary_deductions`)**:
  - Kolom: `employee_id`, `effective_payroll_month`, `deduction_category`, `calculation_type`, `percentage_rate`, `deduction_amount`, `notes`, `base_salary_snapshot`, `net_salary_snapshot`, `status`.
  - Formulir input HCM (*Monthly Salary Adjustment*) dengan opsi pemotongan disiplin, kelebihan cuti, dan potongan berjenjang (Maternity: 25%, 50%).

### Fase 4: Engine Penggajian Terpadu & Multi-Level Grouping (Departemen $\rightarrow$ Divisi) (100% Selesai ✅)
- [x] **Migrasi Tabel Payroll Terpadu**:
  - Buat tabel `hcm_payrolls` (Batch Penggajian): `period_code`, `work_period_month`, `payout_period_month`, `payout_date`, `total_employees`, `total_base_salary`, `total_meal_allowance`, `total_overtime_pay`, `total_adjustments`, `total_deductions`, `total_net_payout`, `status`, `hcm_signed_by`, `hcm_signed_at`, `finance_signed_by`, `finance_signed_at`, `payment_method`, `payment_proof_url`.
  - Buat tabel `hcm_payroll_items` (Rincian per Karyawan): `payroll_id`, `employee_id`, `department`, `division`, `position`, `job_level`, `bank_account_no`, `base_salary`, `meal_allowance`, `overtime_pay`, `increment_adjustment`, `penalty_deduction`, `leave_deduction`, `tiered_deduction`, `total_earnings`, `total_deductions`, `net_salary`, `slip_token`, `is_paid`.
- [x] **Backend Payroll Service & Logic Mundur Bulan**:
  - Tarik data periode kinerja (contoh: Kinerja 1–31 Oktober) untuk dibayarkan pada periode pencairan (November).
  - Agregasi otomatis: Gaji Pokok + Uang Makan (lolos audit absensi & hold rule $\ge 4\times$ telat) + Lembur Mingguan + Penyesuaian Kenaikan - Total Potongan Bulanan = Net Salary (Take-Home Pay).
- [x] **Antarmuka Rekapitulasi Penggajian Multi-Level Grouping (`/hcm/payroll`)**:
  - Tampilan ringkasan berjenjang:
    - **Header**: Total Anggaran Penggajian Perusahaan.
    - **Level 1**: Card / Accordion subtotal per **Departemen** (`[FIN]`, `[HCM]`, `[BRM]`, `[SCP]`, `[PRD]`, `[MIN]`, `[MEX]`).
    - **Level 2**: Sub-tabel subtotal per **Divisi** di bawah departemen terkait.
    - **Level 3**: Rincian gaji per karyawan beserta rekening Bank BRI.
  - Otorisasi Double Sign-Off: Tombol `[Approve & Sign Payroll HCM]` dan tombol `[Sign & Paid Keuangan]`.

### Fase 5: Slip Gaji Digital Transparan (PDF) & Validasi Sistem (100% Selesai ✅)
- [x] **Generator Slip Gaji Digital Transparan**:
  - Template PDF profesional via `barryvdh/laravel-dompdf` menampilkan rincian pendapatan, potongan itemized, alasan pemotongan, nomor rekening Bank BRI, watermark status `PAID`, dan verifikasi digital.
- [x] **Ekspor Laporan Finansial**:
  - Ekspor Excel rekapitulasi penggajian per Departemen & Divisi via `maatwebsite/excel`.
  - Ekspor format transfer massal bank (*payroll disbursement batch*).
- [x] **Testing Menyeluruh (Unit & Feature Test)**:
  - Uji kalkulasi take-home pay, aturan hold uang makan, formula lembur, potongan berjenjang cuti hamil, dan grouping Departemen-Divisi.

---

## 9. Standar Kualitas & Kriteria Selesai (Definition of Done)

1. **Akurasi 100% Terhadap Blueprint 2**: Seluruh tabel, field, rumus perhitungan, opsi dropdown, dan alur validasi dari keenam sheet `Blueprint Website HCM NIS 2.xlsx` (*Dashboard*, *Alur & Validasi*, *Business Rules*, *Database*, *DropDown*, dan *Struktur Fungsi Kerja*) terimplementasi penuh tanpa ada halusinasi data.
2. **Harmoni Sistem & Navigasi**: Modul HCM menyatu mulus di navigasi `SidebarContent.jsx` NISReport, terikat dengan Spatie Permission granular, dan terintegrasi dengan kanal notifikasi (In-App, WhatsApp, Telegram, Email).
3. **Integritas Masa Kerja & Kontrak**: Penggunaan `original_join_date` menjamin data loyalitas karyawan tidak ter-reset saat perpanjangan kontrak PKWT, dan sistem bucket durasi (1, 2, 3+ tahun) akurat.
4. **Kejelasan Finansial (Payroll & Deductions)**: Pemisahan bulan kinerja dan bulan pencairan berjalan presisi; seluruh pemotongan kedisiplinan dan cuti yang diinput HCM tersinkronisasi mulus ke dashboard Keuangan dengan Take-Home Pay yang transparan.
5. **Tertib Administrasi Jam Kerja**: Fitur *Izin Keluar Kantor (Gate Pass)* mengontrol mobilitas keluar-masuk karyawan pada jam kerja dengan validasi status lampiran.
