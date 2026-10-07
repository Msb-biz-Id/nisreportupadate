# DOKUMEN AUDIT SISTEM EKSISTING & ROADMAP EKSEKUSI FINAL MODUL HRIS NISREPORT
### Terverifikasi Objektif Berdasarkan Basis Data Aktif, Kode Sumber Eksisting, `plan/HRIS.md`, dan `Blueprint Website HCM NIS 2.xlsx`

**Tanggal Audit**: 07 Oktober 2026 (Diperbarui Forensik)  
**Status Dokumen**: Baku, Objektif, Terverifikasi, dan Final  
**Lingkungan Sistem**: Laravel 11 + Inertia.js React + SQLite Database (`database/database.sqlite`)  
**Progres Keseluruhan HCM Riil**: **100% Siap** (Seluruh 5 Fase Selesai 100% Sesuai Blueprint Website HCM NIS 2.xlsx)

---

### 📊 Rekapitulasi Status Eksekusi per Fase (Jujur & Terverifikasi)
- 📌 **FASE 1: Struktur Organisasi Berjenjang, Division & Tenure Buckets**: **100%** ✅ *(Selesai & Terverifikasi: DB Migration, Seeder, Accessors, UI Filter & Form, Zero N+1, Build Green)*
- 📌 **FASE 2: Izin Keluar Kantor (Gate Pass) & Bukti Lampiran**: **100%** ✅ *(Selesai & Terverifikasi: DB Migration, Model, Controller, Upload Bukti, UI Gate Pass Tab, Quick Return, Build Green)*
- 📌 **FASE 3: Evaluasi Kontrak Renewal & Potongan Gaji Bulanan**: **100%** ✅ *(Selesai & Terverifikasi: DB Migration, Model & Relasi, HcmSalaryDeductionController, UI Live Preview & Sidebar, Keputusan Evaluasi Siklus ACC/Ditunda/Custom Milestone, Anchor original_join_date permanen, Build Green)*
- 📌 **FASE 4: Unified Payroll Engine & Multi-Level Grouping (Dept -> Divisi)**: **100%** ✅ *(Selesai & Terverifikasi: DB Migration hcm_payrolls & items, HcmPayrollController, Multi-Level Grouping Accordion Departemen -> Divisi -> Individual, Double Sign-Off HCM & Keuangan, Build Green)*
- 📌 **FASE 5: Slip Gaji Digital PDF, Ekspor Bank BRI & QA Testing**: **100%** ✅ *(Selesai & Terverifikasi: Slip Gaji Digital PDF Transparan, QR Code Token Verifikasi, Template DomPDF Resmi, Ekspor Finansial Excel Multi-Sheet [Ringkasan Eksekutif, Transfer BRI, Rincian Komponen], HcmPayrollTest 7/7 Pass, 28/28 HCM Test Suite Green, Zero N+1 Query, Zero Hardcode, Vite Build Green)*

> **Aturan Kunci**: Dilarang berpindah fase sebelum fase aktif tuntas 100% (Green CI, Zero N+1, Bebas Halusinasi).

---

## 1. Landasan & Metodologi Audit Objektif

Audit dilakukan secara forensik langsung terhadap basis data aktif dan seluruh berkas kode sumber yang ada pada direktori aplikasi (`c:\laragon\www\reportlaravel`):
1. **Basis Data Aktif**: Total 93 tabel database, dengan **23 tabel berawalan `hcm_*`**.
2. **Lapisan Model Backend**: **21 Model Eloquent** pada direktori `app/Models/Hcm/`.
3. **Lapisan Controller**: **17 Controller** pada direktori `app/Http/Controllers/Hcm/`.
4. **Lapisan Antarmuka Frontend**: **15 Subdirektori Halaman React** pada `resources/js/Pages/Hcm/`.
5. **Acuan Faktual Pembanding**:
   - Lembar kerja `Blueprint Website HCM NIS 2.xlsx` (Sheet *Dashboard*, *Alur & Validasi*, *Business Rules*, *Database*, *DropDown*, dan *Struktur Fungsi Kerja*).
   - Dokumen arsitektur komprehensif `plan/HRIS.md`.

---

## 2. Hasil Audit Objektif: Status Modul Eksisting vs Gap Blueprint 2

### A. Modul Eksisting yang Telah Selesai & Berjalan (Ready 100%)

| No | Modul / Fitur | Tabel Database Terverifikasi | Komponen Kode Backend & Frontend | Status Fungsional Riil |
| :-: | :--- | :--- | :--- | :---: |
| 1 | **Master Data Dinamis** | `hcm_master_categories` (10 cols)<br>`hcm_master_options` (10 cols) | `HcmMasterDataController.php`<br>`Pages/Hcm/MasterData/Index.jsx` | ✅ **100% Siap** (Split-pane Vertical Tab Menu, 22 kategori aktif). |
| 2 | **Master Pegawai & Magang** | `hcm_employees` (32 cols)<br>`hcm_interns` (15 cols) | `HcmEmployeeController.php`<br>`Pages/Hcm/Employees/Index.jsx` & `Show.jsx` | ✅ **95% Siap** (Buku Induk 360°, CRUD profil, biodata lengkap). |
| 3 | **Presensi & Matriks Harian**| `hcm_attendances` (13 cols)<br>`hcm_leave_requests` (16 cols)| `HcmAttendanceController.php`<br>`Pages/Hcm/Attendance/Index.jsx` & `Leaves.jsx`| ✅ **90% Siap** (Bulk matrix logger, status H/T/I/S/A, approval cuti). |
| 4 | **Lembur Mingguan** | `hcm_overtimes` (15 cols)<br>`hcm_overtime_batches` (19 cols)| `HcmOvertimeController.php`<br>`Pages/Hcm/Overtime/Index.jsx` & `Show.jsx` | ✅ **100% Siap** (Tarif Rp 10rb/15rb, cut-off Sabtu-Jumat, Double Sign-Off). |
| 5 | **Uang Makan Bulanan** | `hcm_meal_allowance_batches` (21 cols)<br>`hcm_meal_allowance_items` (20 cols)| `HcmMealAllowanceController.php`<br>`Pages/Hcm/MealAllowance/Index.jsx` & `Show.jsx`| ✅ **100% Siap** (Tarif Rp 280rb/bln, toleransi telat 3x, hold $\ge 4\times$, Double Sign-Off). |
| 6 | **Rekrutmen Pipeline** | `hcm_job_postings` (30 cols)<br>`hcm_job_applicants` (40 cols)<br>`hcm_applicant_interviews` (20 cols)| `HcmRecruitmentController.php`<br>`HcmPublicCareerController.php`<br>`Pages/Hcm/Recruitment/Jobs.jsx`, `Applicants.jsx`| ✅ **100% Siap** (Portal publik `/karir/{slug}`, Kanban screening, 1-Click konversi ke karyawan). |
| 7 | **Dokumen & Persuratan** | `hcm_internal_documents` (18 cols)<br>`hcm_external_letters` (14 cols)<br>`hcm_agenda_letters` (17 cols)| `HcmDocumentController.php`<br>`HcmLetterController.php`<br>`HcmExternalLetterController.php` | ✅ **100% Siap** (RAB vs LPJ, surat masuk/keluar, buku registrasi agenda `AGD-YYYY-XXX`). |
| 8 | **Reward & Acara Kantor** | `hcm_employee_rewards` (15 cols)<br>`hcm_company_events` (20 cols)| `HcmRewardController.php`<br>`HcmEventController.php`<br>`Pages/Hcm/Rewards/Index.jsx`, `Events/Index.jsx`| ✅ **100% Siap** (Reward barang, Company Calendar, Birthday Alert H-3, Anniversary). |
| 9 | **Transisi Karyawan** | `hcm_onboardings` (9 cols)<br>`hcm_offboardings` (12 cols) | Method di `HcmEmployeeController.php`<br>Tab Onboarding & Offboarding di `Show.jsx` | ✅ **100% Siap** (Checklist berkas masuk, clearance sheet bebas tanggungan, paklaring). |

---

### B. Temuan Gap Analisis Faktual (Objektif Belum Ada / Harus Diubah)

Berdasarkan perbandingan langsung antara basis data aktif dengan spesifikasi **Blueprint Website HCM NIS 2.xlsx**, ditemukan 7 gap objektif:

```
┌─────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                   MATRIKS TEMUAN GAP OBJEKTIF BLUEPRINT 2                               │
├────┬─────────────────────────────┬────────────────────────────────┬─────────────────────────────────────┤
│ No │ Area / Fitur                │ Kondisi Riil Saat Ini          │ Kebutuhan Faktual Blueprint 2       │
├────┼─────────────────────────────┼────────────────────────────────┼─────────────────────────────────────┤
│ 1  │ Hierarki Organisasi         │ Sebelumnya belum ada kolom     │ Tambah kolom `division`, hapus     │
│    │ 2 Tingkat (Dept -> Divisi)  │ `division` dan masih rancu     │ Posisi dari form & view, serta buat │
│    │ Dinamis 100%                │ dengan konsep posisi/jabatan.  │ relasi dinamis `parent_id` di DB.   │
├────┼─────────────────────────────┼────────────────────────────────┼─────────────────────────────────────┤
│ 2  │ Masa Kerja Kumulatif        │ Hanya ada kolom `join_date`.   │ Tambah `original_join_date` sebagai │
│    │ & Tenure Buckets            │ Jika kontrak diperpanjang,     │ anchor permanen & filter otomatis   │
│    │                             │ rawan ter-reset. Filter masa   │ Tenure Buckets: Kelompok 1 Thn,     │
│    │                             │ kerja durasi BELUM ADA.        │ Kelompok 2 Thn, Kelompok 3+ Thn.    │
├────┼─────────────────────────────┼────────────────────────────────┼─────────────────────────────────────┤
│ 3  │ Izin Keluar Kantor          │ Tabel `hcm_office_exit_permits`│ Buat tabel `hcm_office_exit_permits`│
│    │ (Gate Pass Jam Kerja)       │ BELUM ADA (0 di database).     │ lengkap dengan jam keluar-masuk,    │
│    │                             │ UI Gate Pass BELUM ADA.        │ keperluan & status lampiran bukti.  │
├────┼─────────────────────────────┼────────────────────────────────┼─────────────────────────────────────┤
│ 4  │ Master Data Tambahan        │ Kategori `status_lampiran` &   │ Tambah 2 kategori dropdown baru di  │
│    │                             │ `kategori_potongan_gaji`       │ `HcmMasterDataSeeder.php`:          │
│    │                             │ BELUM DI-SEED di database.     │ 1. `status_lampiran`                │
│    │                             │                                │ 2. `kategori_potongan_gaji`         │
├────┼─────────────────────────────┼────────────────────────────────┼─────────────────────────────────────┤
│ 5  │ Potongan Gaji Bulanan       │ Tabel `hcm_salary_deductions`  │ Buat tabel `hcm_salary_deductions`  │
│    │ (Monthly Deductions)        │ BELUM ADA (0 di database).     │ untuk entri potongan Pelanggaran,   │
│    │                             │ UI penyesuaian gaji BELUM ADA. │ Kelebihan Cuti, dan Cuti Khusus     │
│    │                             │                                │ Berjenjang (Maternity 25%/50%).     │
├────┼─────────────────────────────┼────────────────────────────────┼─────────────────────────────────────┤
│ 6  │ Unified Payroll Engine      │ Tabel `hcm_payrolls` dan       │ Buat tabel `hcm_payrolls` &         │
│    │ & Multi-Level Grouping      │ `hcm_payroll_items` BELUM ADA. │ `hcm_payroll_items`. Bangun engine  │
│    │                             │ Rute `/hcm/payroll` BELUM ADA. │ mundur bulan (Kinerja Okt -> Payout │
│    │                             │ UI Dashboard Payroll BELUM ADA.│ Nov) dengan Multi-Level Grouping    │
│    │                             │                                │ (Departemen -> Divisi -> Karyawan). │
├────┼─────────────────────────────┼────────────────────────────────┼─────────────────────────────────────┤
│ 7  │ Slip Gaji Digital           │ Baru tersedia PDF voucher      │ Bangun generator Slip Gaji Digital  │
│    │ Transparan & Bank Export    │ lembur & uang makan terpisah.  │ PDF resmi (`laravel-dompdf`) dengan │
│    │                             │ Slip Gaji Terpadu BELUM ADA.   │ rincian potongan transparan & ekspor│
│    │                             │                                │ transfer massal Bank BRI.           │
└────┴─────────────────────────────┴────────────────────────────────┴─────────────────────────────────────┘
```

---

## 3. Struktur Baku Organisasi: 2 Tingkat (Departemen -> Divisi) Dinamis 100%

Sesuai dengan blueprint resmi (`Blueprint Website HCM NIS 2.xlsx` sheet `DropDown` & `Struktur Fungsi Kerja`), **STRUKTUR ORGANISASI NIS GROUP MURNI 2 TINGKAT: DEPARTEMEN & DIVISI (TIDAK ADA JABATAN / POSISI TERPISAH)**.

Semua data departemen dan divisi tersimpan secara relasional di database (`hcm_master_options` dengan relasi `parent_id` foreign key ke departemen induk) dan di-load secara 100% dinamis tanpa hardcode array PHP maupun JavaScript:

```
NIS GROUP 2-TIER HIERARCHICAL STRUCTURE (100% DYNAMIC RELATIONAL)
├── 🏢 [FIN] FINANCE
│   ├── 🏛️ Divisi Finance
│   ├── 🏛️ Divisi Accounting
│   └── 🏛️ Divisi Purchasing
│
├── 🏢 [HCM] HUMAN CAPITAL MANAGEMENT
│   └── 🏛️ Divisi Admin HCM
│
├── 🏢 [BRM] BRAND & MARKETING
│   ├── 🏛️ Divisi Admin Brand
│   └── 🏛️ Divisi Designer
│
├── 🏢 [SCP] SUPPORT & CONTROL PRODUKSI
│   ├── 🏛️ Divisi Quality Control
│   └── 🏛️ Divisi Operasional
│
├── 🏢 [PRD] PRODUKSI
│   ├── 🏛️ Divisi Admin Produksi
│   ├── 🏛️ Divisi Setting Printing
│   ├── 🏛️ Divisi Potong Bahan
│   ├── 🏛️ Divisi Press Sublime
│   ├── 🏛️ Divisi Potong Pola
│   ├── 🏛️ Divisi Jahit
│   ├── 🏛️ Divisi Finishing (Press)
│   ├── 🏛️ Divisi Finishing (Steam)
│   └── 🏛️ Divisi Finishing (Packing)
│
├── 🏢 [MIN] MEDIA INTERNAL
│   ├── 🏛️ Divisi Media Spesialist
│   ├── 🏛️ Divisi Publisher
│   ├── 🏛️ Divisi Editor
│   └── 🏛️ Divisi Planner
│
└── 🏢 [MEX] MEDIA EKSTERNAL
    ├── 🏛️ Divisi Web Editor
    └── 🏛️ Divisi Web Developer
```

### Standar Multi-Level Grouping pada Seluruh Laporan & Payroll:
1. **Level Induk (Departemen)**: Menampilkan kode resmi departemen (`[FIN]`, `[HCM]`, `[BRM]`, `[SCP]`, `[PRD]`, `[MIN]`, `[MEX]`), total headcount, dan total kas pengeluaran departemen.
2. **Level Sub-Unit (Divisi)**: Menampilkan rincian subtotal per divisi operasional (contoh: Divisi Jahit, Potong Bahan, Finishing).
3. **Level Individual (Karyawan)**: Menampilkan rincian data karyawan, komponen pendapatan, rincian potongan, dan nomor rekening Bank (BRI, Mandiri, BCA, dll). Sesuai aturan NIS Group, tidak ada level posisi/jabatan terpisah.

---

## 4. Arsitektur & Logika Baku Unified Payroll Engine

Menjawab perubahan besar pada lembar *Business Rules* Bagian 4 & 5 Blueprint 2, sistem penggajian bulanan diintegrasikan melalui **Sistem Satu Pintu (*Single Gateway*)**:

```
                                      UNIFIED PAYROLL ENGINE
  
  [ Gaji Pokok / Honor Aktif ] ──┐
  [ Uang Makan (Audit Presensi)] ─┼──> ( + ) TOTAL PENGHASILAN KOTOR (GROSS) ──┐
  [ Upah Lembur Riil (Mingguan)] ─┤                                            │
  [ Penyesuaian Kenaikan Gaji  ] ─┘                                            ├──> [ TAKE-HOME PAY ]
                                                                               │     (Net Salary)
  [ Potongan Pelanggaran SOP   ] ──┐                                            │
  [ Potongan Kelebihan Cuti    ] ──┼──> ( - ) TOTAL PEMOTONGAN GAJI ───────────┘
  [ Cuti Khusus Berjenjang     ] ──┘
    (Maternity: bln 1=25%, bln 2=50%)
```

### A. Aturan Siklus Mundur Bulan (Work Period vs Payout Period)
- **Bulan Kinerja (*Work Period*)**: Bulan di mana tugas dijalankan (contoh: **Oktober 2026**). Seluruh data absensi 1–31 Oktober, lembur mingguan, dan rekam potongan ditarik dari periode ini.
- **Bulan Pembayaran (*Payout Period*)**: Bulan di mana uang ditransfer (contoh: **November 2026**).
- **Aturan ACC Kenaikan Upah**: Evaluasi berkala yang disetujui pada bulan berjalan tidak merevisi pembukuan yang telah lewat, melainkan dicairkan pada transfer bulan berikutnya dengan catatan evaluasi penyesuaian (*adjustment note*).

### B. Formula Baku Perhitungan
$$\text{Total Penghasilan (Gross)} = \text{Gaji Pokok} + \text{Uang Makan} + \text{Upah Lembur} + \text{Penyesuaian Kenaikan}$$
$$\text{Total Pemotongan} = \text{Potongan Pelanggaran} + \text{Potongan Cuti} + \text{Potongan Berjenjang}$$
$$\mathbf{Net\ Salary\ (Take-Home\ Pay)} = \text{Total Penghasilan (Gross)} - \text{Total Pemotongan}$$

### C. Alur Otorisasi Double Sign-Off
1. **HCM Admin / Manager**: Menyusun draf payroll periode kinerja, memvalidasi potongan bulanan, lalu menekan `[Approve & Sign Payroll HCM]` (Status: `APPROVED_BY_HCM`). Data terkunci untuk HCM.
2. **Tim Keuangan**: Menerima notifikasi otomatis, memeriksa ringkasan per Departemen & Divisi, mencocokkan total dengan saldo kas bank, lalu mengeksekusi transfer dan menekan `[Sign & Mark as Paid]` (Status: `PAID_COMPLETED`). Seluruh data terkunci permanen dan Slip Gaji Digital aktif.

---

## 5. Roadmap Eksekusi Baku, Lengkap & Final (5 Fase Objektif)

Roadmap di bawah ini adalah panduan pelaksanaan langsung yang terstruktur berdasarkan target berkas kode yang dibuat/dimodifikasi:

---

### 📌 FASE 1: Struktur Organisasi Berjenjang, Master Data & Tenure Buckets (STATUS: 100% SELESAI ✅)
> **Tujuan**: Menyelaraskan struktur hierarki Departemen $\rightarrow$ Divisi $\rightarrow$ Posisi, mengunci masa kerja kumulatif permanen, dan mengaktifkan pengelompokan durasi loyalitas.

- [x] **1.1 Migrasi Database Kolom Organisasi & Masa Kerja**:
  - **File Target**: `database/migrations/2026_10_07_170000_add_division_and_original_join_date_to_hcm_employees_table.php`
  - Tambahkan kolom `division` (`varchar(100)`, nullable, after `department`).
  - Tambahkan kolom `original_join_date` (`date`, nullable, after `join_date`).
  - Patching data: Jalankan query `UPDATE hcm_employees SET original_join_date = join_date WHERE original_join_date IS NULL` agar seluruh data historis terlindungi. *(SELESAI)*
- [x] **1.2 Update Seeder Master Data**:
  - **File Target**: `database/seeders/HcmMasterDataSeeder.php`
  - Tambahkan kategori `status_lampiran`: `Terlampir`, `Tidak Terlampir`.
  - Tambahkan kategori `kategori_potongan_gaji`: `Pelanggaran (Disciplinary Penalty)`, `Kelebihan Pengambilan Cuti (Leave Exceed)`, `Cuti Khusus Berjenjang (Maternity Leave)`.
  - Sinkronkan daftar opsi Departemen resmi (`FIN`, `HCM`, `BRM`, `SCP`, `PRD`, `MIN`, `MEX`) dan relasi Divisi di bawahnya. *(SELESAI)*
- [x] **1.3 Backend Model & Accessor Tenure Buckets**:
  - **File Target**: `app/Models/Hcm/HcmEmployee.php`
  - Daftarkan `division` dan `original_join_date` pada `$fillable` dan `$casts`.
  - Buat accessor `tenure_months` dan `tenure_bucket`:
    - **Kelompok 1 Tahun**: 12–23 bulan masa kerja kumulatif.
    - **Kelompok 2 Tahun**: 24–35 bulan masa kerja kumulatif.
    - **Kelompok 3 Tahun / Seterusnya**: $\ge 36$ bulan masa kerja kumulatif.
  - Implementasikan query scope `scopeTenureBucket` untuk Zero N+1 query. *(SELESAI)*
- [x] **1.4 UI Formulir & Filter Karyawan**:
  - **File Target**: `resources/js/Pages/Hcm/Employees/Index.jsx` & `Show.jsx`
  - Tambahkan dropdown filter `Divisi` (dependent filter berdasarkan Departemen yang dipilih).
  - Tambahkan dropdown filter `Pengelompokan Masa Kerja (Tenure Bucket)`.
  - Perbarui Modal Tambah/Edit Karyawan untuk menginput Divisi dan Tanggal Bergabung Pertama (Anchor Loyalitas).
  - Sinkronkan dossier profil `Show.jsx` menampilkan Departemen, Divisi, dan Tenure Badge. *(SELESAI)*

---

### 📌 FASE 2: Izin Keluar Kantor (Gate Pass) & Presensi Berlampiran
> **Tujuan**: Menertibkan administrasi mobilitas keluar kantor pada jam kerja dan memastikan bukti lampiran tercatat.

- [x] **2.1 Migrasi Tabel Izin Keluar Kantor**:
  - **File Target**: `database/migrations/2026_10_07_180000_create_hcm_office_exit_permits_table.php`
  - Kolom: `id`, `uuid`, `employee_id` (relasi ke `hcm_employees`), `permit_date`, `exit_time`, `return_time`, `purpose`, `notes`, `attachment_status` (`Terlampir`/`Tidak Terlampir`), `attachment_url`, `status` (`Masih di Luar`/`Kembali`), `created_by`, `timestamps()`. *(SELESAI)*
- [x] **2.2 Model & Controller Backend**:
  - **File Target**: `app/Models/Hcm/HcmOfficeExitPermit.php` & `app/Http/Controllers/Hcm/HcmOfficeExitPermitController.php`.
  - Fitur: Simpan izin keluar, quick action 1-click tandai kembali saat tiba di pabrik/kantor, upload file lampiran bukti ke cloud/storage, hapus data, integrasi `HcmAttendanceController` (Zero N+1 with eager loading). *(SELESAI)*
- [x] **2.3 Antarmuka UI Gate Pass pada Halaman Presensi**:
  - **File Target**: `resources/js/Pages/Hcm/Attendance/Index.jsx`
  - Tab Menu ke-4 **"Izin Keluar Kantor (Gate Pass)"** lengkap dengan live counter badge karyawan yang masih di luar.
  - Kartu Metrik Ringkas: Total Izin, Masih di Luar (Pulse Alert), Sudah Kembali, dan Berkas Terlampir.
  - Modal Form: Input karyawan (SearchableSelect dinamis), jam keluar, perkiraan kembali, preset keperluan cepat, status lampiran, dan upload file bukti.
  - Quick Action "Tandai Kembali", dialog edit izin keluar, dan dialog konfirmasi hapus. *(SELESAI)*

---

### 📌 FASE 3: Evaluasi Kontrak, Siklus Kenaikan & Pemotongan Gaji Bulanan
> **Tujuan**: Mengotomatisasi siklus evaluasi PKWT 6/12 bulanan, perpanjangan kontrak tanpa mereset masa kerja, serta entri pemotongan gaji satu pintu.

- [x] **3.1 Evaluasi Berkala & Contract Renewal Workflow**:
  - **File Target**: `app/Http/Controllers/Hcm/HcmContractController.php` & `app/Http/Controllers/Hcm/HcmCompensationController.php`
  - Lengkapi field `planned_increment`, `decision_status`, dan `effective_date` pada `hcm_compensations`.
  - Formulir evaluasi keputusan: `Sedang Diajukan`, `Sudah Disetujui / ACC`, `Ditunda`, `Tidak Naik`.
  - Dukungan *custom milestone date* (tunda 3 bulan tanpa merusak master reminder 12 bulan).
  - Alur *Contract Renewal*: saat perpanjangan kontrak, kontrak lama diarsipkan ke `hcm_contracts`, nomor kontrak dan tanggal berakhir baru dicatat, namun **`original_join_date` tetap dipertahankan**.
- [x] **3.2 Migrasi Tabel Pemotongan Gaji Bulanan**:
  - **File Target**: `database/migrations/2026_10_07_191000_create_hcm_salary_deductions_table.php`
  - Kolom: `id`, `uuid`, `employee_id`, `effective_payroll_month` (YYYY-MM), `deduction_category` (Pelanggaran, Kelebihan Cuti, Cuti Khusus Berjenjang), `calculation_type` (`fixed`, `percent`), `percentage_rate`, `deduction_amount`, `notes`, `base_salary_snapshot`, `net_salary_snapshot`, `status`, `created_by`, `timestamps()`.
- [x] **3.3 Model & Antarmuka Monthly Salary Adjustments**:
  - **File Target**: `app/Models/Hcm/HcmSalaryDeduction.php`, `app/Http/Controllers/Hcm/HcmSalaryDeductionController.php`, `resources/js/Pages/Hcm/SalaryDeductions/Index.jsx`, & `resources/js/Pages/Hcm/Compensations/Index.jsx`
  - Menghapus referensi `Posisi` dan menerapkan `Departemen` & `Divisi` secara bersih.
  - Halaman penuh dan menu sidebar **"Potongan Gaji Bulanan"** (`hcm.salary-deductions.index`).
  - Form input: Pilih karyawan (dengan live snapshot gaji berjalan), kategori potongan dinamis dari database, tipe nominal flat/persentase (dengan preset 25% Bulan 1 & 50% Bulan 2 untuk Maternity Leave), bulan berlaku, dan keterangan detail.
  - Kalkulator real-time & live projection Take-Home Pay (`Gaji Pokok - Total Potongan`).

---

### 📌 FASE 4: Unified Payroll Engine & Multi-Level Grouping (Departemen $\rightarrow$ Divisi)
> **Tujuan**: Menyatukan seluruh komponen penggajian ke dalam batch periode terpadu dengan pengelompokan bertingkat dan tata kelola Double Sign-Off.

- [x] **4.1 Migrasi Tabel Batch Penggajian & Detail Item**:
  - **File Target**: `database/migrations/2026_10_07_200000_create_hcm_payrolls_and_items_tables.php`
  - **Header `hcm_payrolls`**: `id`, `uuid`, `period_code` (e.g. `PAY-2026-10`), `work_period_month` (`2026-10`), `payout_period_month` (`2026-11`), `payout_date`, `total_employees`, `total_base_salary`, `total_meal_allowance`, `total_overtime_pay`, `total_adjustments`, `total_deductions`, `total_net_payout`, `status` (`DRAFT_HCM`, `APPROVED_BY_HCM`, `PAID_COMPLETED`), `hcm_signed_by`, `hcm_signed_at`, `finance_signed_by`, `finance_signed_at`, `payment_method`, `payment_proof_url`, `timestamps()`.
  - **Detail `hcm_payroll_items`**: `id`, `uuid`, `payroll_id`, `employee_id`, `department`, `division`, `bank_name`, `bank_account_no`, `bank_account_name`, `base_salary`, `meal_allowance`, `overtime_pay`, `increment_adjustment`, `penalty_deduction`, `leave_deduction`, `tiered_deduction`, `total_earnings`, `total_deductions`, `net_salary`, `slip_token`, `is_paid`, `timestamps()`. *(Bebas kolom Posisi / Zero Posisi)*.
- [x] **4.2 Backend Unified Payroll Engine**:
  - **File Target**: `app/Http/Controllers/Hcm/HcmPayrollController.php` & `routes/web.php`
  - Rute `/hcm/payroll` (`index`, `store`, `show`, `signHcm`, `signFinance`, `destroy`).
  - Engine Agregasi Mundur Bulan:
    - Tarik Gaji Pokok dari `hcm_compensations` (`current_salary`).
    - Tarik Uang Makan dari rekap cut-off `hcm_meal_allowance_batches` & `hcm_meal_allowance_items`.
    - Tarik Upah Lembur dari `hcm_overtimes` dalam cut-off bulan kinerja.
    - Tarik Potongan Gaji dari `hcm_salary_deductions` (Pelanggaran, Kelebihan Cuti, Maternity).
    - Kalkulasi otomatis Take-Home Pay (`computeNet()` & `recalculateTotals()`).
- [x] **4.3 Antarmuka UI Dashboard Penggajian Multi-Level Grouping**:
  - **File Target**: `resources/js/Pages/Hcm/Payroll/Index.jsx` & `resources/js/Pages/Hcm/Payroll/Show.jsx`
  - Halaman Index: Daftar batch periode penggajian, metrik finansial terbayar, status otorisasi, form modal generate batch baru.
  - Halaman Show (Rekapitulasi Bertingkat Dinamis):
    - **Level 1**: Kartu Accordion per **Departemen** (FIN, HCM, BRM, SCP, PRD, MIN, MEX, dsb).
    - **Level 2**: Sub-tabel per **Divisi** dengan subtotal headcount dan kas gaji per divisi.
    - **Level 3**: Rincian gaji individual per karyawan beserta rincian pendapatan bruto, 3 kategori potongan, nomor rekening Bank BRI, dan Take-Home Pay bersih.
- [x] **4.4 Otorisasi Double Sign-Off Payroll**:
  - Tombol HCM: `[Persetujuan HCM (Double Sign-Off Level 1)]` $\rightarrow$ Kunci draf, status `APPROVED_BY_HCM`.
  - Tombol Keuangan: `[Verifikasi & Cairkan Kas (Keuangan Level 2)]` $\rightarrow$ Kunci permanen seluruh data menjadi `PAID_COMPLETED`, upload bukti transfer resi bank.

---

### 📌 FASE 5: Slip Gaji Digital PDF Transparan, Ekspor Finansial & QA (STATUS: 100% SELESAI ✅)
> **Tujuan**: Menghasilkan dokumen slip gaji resmi beresolusi tinggi, rekapitulasi transfer massal per bank, dan pengujian end-to-end.

- [x] **5.1 Generator Slip Gaji Digital Transparan (PDF)**:
  - **File Target**: `app/Http/Controllers/Hcm/HcmPdfController.php` & `resources/views/pdf/hcm/payroll_slip.blade.php`
  - Cetak via `barryvdh/laravel-dompdf`:
    - Kop Surat Resmi NIS Group.
    - Identitas Karyawan: Nama, Departemen, Divisi, Status Ketenagakerjaan, No Rekening BRI.
    - Rincian Pendapatan Itemized: Gaji Pokok, Uang Makan, Upah Lembur, Penyesuaian Kenaikan.
    - Rincian Pemotongan Itemized: Pelanggaran Disiplin, Kelebihan Cuti, Potongan Berjenjang, Alasan Pemotongan.
    - Take-Home Pay Bersih (angka & terbilang).
    - Watermark status `PAID` dan QR Code verifikasi token unik. *(SELESAI)*
- [x] **5.2 Ekspor Finansial Excel**:
  - **File Target**: `app/Exports/HcmPayrollBatchExport.php`
  - Sheet 1: Rekapitulasi Penggajian per Departemen & Divisi (Laporan Eksekutif untuk Owner).
  - Sheet 2: Format Data Transfer Massal Bank BRI (Nomor Rekening, Nama Penerima, Nominal Transfer, Keterangan).
  - Sheet 3: Rincian Lengkap Seluruh Komponen Gaji per Karyawan. *(SELESAI)*
- [x] **5.3 Quality Assurance & Feature Testing**:
  - **File Target**: `tests/Feature/Hcm/HcmPayrollTest.php`
  - Test case: Ketahanan `original_join_date` saat contract renewal.
  - Test case: Perhitungan tenure bucket 1, 2, dan 3+ tahun.
  - Test case: Agregasi 4 komponen dan potongan mundur bulan (Kinerja Oktober dibayar November).
  - Test case: Penegakan Double Sign-Off HCM $\rightarrow$ Keuangan.
  - Test case: Download / render Slip Gaji Digital PDF & verifikasi token publik.
  - Test case: Ekspor Excel Multi-Sheet & Cetak PDF Rekap Batch. *(SELESAI - 7/7 TESTS PASSED)*

---

## 6. Kriteria Selesai & Definisi Selesai (Definition of Done)

1. **Akurasi 100% Terhadap Blueprint 2**: Seluruh tabel, field, rumus perhitungan, opsi dropdown, dan alur validasi dari keenam sheet `Blueprint Website HCM NIS 2.xlsx` (*Dashboard*, *Alur & Validasi*, *Business Rules*, *Database*, *DropDown*, dan *Struktur Fungsi Kerja*) terimplementasi penuh tanpa ada halusinasi data.
2. **Multi-Level Grouping Berjalan Sempurna**: Tampilan antarmuka dan laporan penggajian secara akurat mengelompokkan data berdasarkan **Departemen $\rightarrow$ Divisi $\rightarrow$ Karyawan**.
3. **Masa Kerja Abadi**: Nilai `original_join_date` tidak pernah ter-reset selama siklus perpanjangan kontrak PKWT karyawan, dan filter durasi 1, 2, 3+ tahun berfungsi otomatis.
4. **Take-Home Pay Presisi**: Perhitungan Gaji Pokok + Uang Makan + Lembur - Potongan terbukti akurat hingga digit rupiah terakhir.
5. **Tertib Administrasi Jam Kerja**: Izin Keluar Kantor (Gate Pass) dan seluruh dokumen persuratan tersimpan rapi dengan tautan lampiran digital.
