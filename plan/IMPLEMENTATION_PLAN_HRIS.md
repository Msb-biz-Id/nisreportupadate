# MASTER IMPLEMENTATION PLAN: HRIS & UNIFIED PAYROLL ECOSYSTEM
## NIS Group Enterprise Platform (Laravel 11 + Inertia.js React + Tailwind CSS)
### Diselaraskan Faktual dengan `Blueprint Website HCM NIS 2.xlsx`, Audit Sistem, & Modul Penilaian Kinerja Berjenjang

---

## DAFTAR ISI
1. [Ringkasan Eksekutif & Tujuan Arsitektur](#1-ringkasan-eksekutif--tujuan-arsitektur)
2. [Hierarki Organisasi Baku: Departemen, Divisi & Posisi](#2-hierarki-organisasi-baku-departemen-divisi--posisi)
3. [Modul Pengukuran Kinerja (KPI & Performance Engine)](#3-modul-pengukuran-kinerja-kpi--performance-engine)
4. [Unified Payroll Engine (Sistem Penggajian Terpadu Satu Pintu)](#4-unified-payroll-engine-sistem-penggajian-terpadu-satu-pintu)
5. [Kamus Skema Basis Data (Database Schema Blueprint)](#5-kamus-skema-basis-data-database-schema-blueprint)
6. [Tahapan Eksekusi Bertahap (6 Fase Pengerjaan Terstruktur)](#6-tahapan-eksekusi-bertahap-6-fase-pengerjaan-terstruktur)
7. [Desain Antarmuka Pengguna & Navigasi Sistem](#7-desain-antarmuka-pengguna--navigasi-sistem)
8. [Standar Kualitas & Kriteria Penerimaan (Definition of Done)](#8-standar-kualitas--kriteria-penerimaan-definition-of-done)

---

## 1. Ringkasan Eksekutif & Tujuan Arsitektur

Dokumen ini merupakan panduan implementasi lengkap, baku, dan utuh untuk menyempurnakan ekosistem **Human Capital Management (HCM) / HRIS NIS Group** pada aplikasi NISReport. Rencana ini disusun berdasarkan temuan audit sistem eksisting dan penyesuaian terhadap spesifikasi **Blueprint Website HCM NIS 2.xlsx**.

### 5 Pilar Utama Arsitektur:
1. **Hierarki Organisasi 3 Tingkat**: Menjaga kode resmi departemen (`FIN`, `HCM`, `BRM`, `SCP`, `PRD`, `MIN`, `MEX`) dengan pemetaan bertingkat ke **Divisi** dan **Posisi/Jabatan**.
2. **Integritas Masa Kerja Kumulatif**: Mengunci tanggal pertama bergabung (`original_join_date`) agar masa kerja tidak pernah ter-reset saat perpanjangan kontrak PKWT, dilengkapi klasifikasi *Tenure Buckets* (1 Tahun, 2 Tahun, 3+ Tahun).
3. **Modul Pengukuran Kinerja (Hybrid KPI Engine)**: Mengukur hasil kerja riil sesuai bidang masing-masing (garmen, marketing, media, finance) yang terhubung otomatis ke keputusan kenaikan gaji, insentif, dan penalti.
4. **Unified Payroll Engine (Siklus Mundur Bulan)**: Mengintegrasikan Gaji Pokok, Uang Makan (audit absensi & hold $\ge 4\times$ telat), Lembur Mingguan, dan Pemotongan Gaji (Disiplin, Cuti, Maternity 25%/50%) dengan tampilan *Multi-Level Grouping* per Departemen & Divisi.
5. **Double Sign-Off Finansial**: Pemisahan tegas antara pengesahan data operasional oleh HCM (`admin_hcm`) dan otorisasi pencairan dana oleh Keuangan (`admin_keuangan`).

---

## 2. Hierarki Organisasi Baku: Departemen, Divisi & Posisi

Mengacu pada sheet *Struktur Fungsi Kerja* Blueprint 2 dan migrasi resmi database, struktur organisasi NIS Group dibakukan ke dalam 3 tingkat:

```
NIS GROUP HIERARCHICAL STRUCTURE
├── 🏢 [FIN] FINANCE & ACCOUNTING
│   └── 🏛️ Divisi Finance
│       ├── Accounting (Staff & Lead)
│       └── Purchasing (Staff & Lead)
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
├── 🏢 [SCP] SUPPORT & CONTROL PRODUKSI
│   └── 🏛️ Divisi Support Produksi
│       ├── Quality Control (QC)
│       └── Operasional Lapangan
│
├── 🏢 [PRD] PRODUKSI
│   └── 🏛️ Divisi Produksi
│       ├── Admin Produksi
│       ├── Setting Printing
│       ├── Potong Bahan
│       ├── Press Sublime
│       ├── Potong Pola
│       ├── Jahit (Sewing)
│       ├── Finishing (Press)
│       ├── Finishing (Steam)
│       └── Finishing (Packing)
│
├── 🏢 [MIN] MEDIA INTERNAL
│   └── 🏛️ Divisi Media Internal
│       ├── Media Specialist
│       ├── Publisher
│       ├── Editor
│       └── Planner
│
└── 🏢 [MEX] MEDIA EKSTERNAL
    └── 🏛️ Divisi Media Eksternal
        ├── Media Specialist
        ├── Web Editor
        └── Web Developer
```

### Aturan Multi-Level Grouping pada Sistem:
- **Tingkat 1 (Departemen Induk)**: Menampilkan kode resmi departemen, jumlah total tenaga kerja, dan total kas belanja gaji departemen.
- **Tingkat 2 (Divisi Fungsional)**: Menampilkan subtotal headcount dan belanja kas per unit kerja operasional (contoh: Subtotal Divisi Jahit, Divisi Sablon/Print, Divisi Finishing).
- **Tingkat 3 (Individual Karyawan)**: Rincian penerimaan dan potongan per karyawan lengkap dengan nomor rekening Bank BRI.

---

## 3. Modul Pengukuran Kinerja (KPI & Performance Engine)

Karena setiap divisi memiliki output hasil kerja yang berbeda (kuantitas fisik, omzet, konten, laporan audit), sistem menerapkan **Weighted Output & Behavior Matrix**:

### A. Bobot Penilaian Universal
1. **Hard Output / Hasil Kerja Riil (Bobot 70%)**: Metrik kuantitatif terukur sesuai bidang masing-masing.
2. **Core Behavior & Kedisiplinan (Bobot 30%)**:
   - Presensi & Ketepatan Waktu (15%): Dihitung otomatis dari log absensi (jam masuk, tidak mangkir, tidak telat).
   - Kepatuhan SOP & Kerjasama Tim (15%): Dinilai oleh atasan langsung / PIC divisi.

### B. Matriks Indikator Spesifik per Divisi

| Departemen & Divisi | Jabatan Kunci | Parameter Ukur (KPI Metric) | Target Standar | Sumber Bukti di Sistem |
| :--- | :--- | :--- | :--- | :--- |
| **`[PRD] Produksi`**<br>(Jahit, Potong, Sablon) | Operator Jahit / PIC Sablon | 1. Output Target Harian (pcs)<br>2. Defect Rate Jahit / Reject Sablon<br>3. Ketepatan Waktu Batch SPK | $\ge 95\%$ target<br>$< 2\%$ defect<br>100% on-time | Input rekap SPK harian / QC pass report |
| **`[SCP] Support Produksi`**<br>(Quality Control) | Staff QC | 1. Zero Retur Pelanggan (Cacat Lolos)<br>2. Kecepatan Audit Batch Pasca-Jahit | 0 retur komplain<br>Selesai H+0 | Rekap temuan defect vs komplain customer |
| **`[BRM] Marketing`**<br>(Admin Brand, Designer) | Designer / Admin Brand | 1. Desain Approved per Minggu<br>2. Pencapaian Target Omzet / Closing Leads<br>3. Waktu Respon Customer | $\ge 10$ artwork/bln<br>100% kuota omzet<br>$< 5$ menit respon | Jumlah artwork disetujui & log omzet penjualan |
| **`[MIN] & [MEX] Media`**<br>(Publisher, Editor, Web Dev) | Media Specialist / Web Developer | 1. Kepatuhan Kalender Konten<br>2. Pertumbuhan Engagement & Reach<br>3. Uptime Web & Ketepatan Rilis Fitur | 100% on-schedule<br>$\ge 10\%$ MoM growth<br>99.9% uptime | Checklist kalender posting & task sprint tiket |
| **`[FIN] Finance`**<br>(Accounting, Purchasing) | Staff Accounting / Purchasing | 1. Ketepatan Tanggal Closing Laporan<br>2. Zero Selisih Rekonsiliasi Bank/Kas<br>3. Efisiensi Biaya Bahan Baku (*Cost Saving*) | Maksimal Tgl 5<br>Rp 0 selisih<br>$\ge 3\%$ saving | Log audit closing & berita acara kas opname |
| **`[HCM] Human Capital`**<br>(Admin HCM) | Admin HCM | 1. Waktu Pemenuhan Loker (*Time-to-Hire*)<br>2. Akurasi Gaji & Zero Komplain Payroll<br>3. Kepatuhan Berkas Legal Karyawan | $< 14$ hari<br>0 komplain salah<br>100% lengkap | Metrik Loker & Log audit slip gaji |

### C. Skala Grade Baku & Dampak Otomatisnya ke Gaji & Kontrak

```
                    SKALA GRADE KINERJA NIS GROUP
  
  ┌───────────────┬────────────────────────────┬─────────────────────────────┐
  │ Skor Indeks   │ Grade Kinerja              │ Dampak Otomatis Sistem      │
  ├───────────────┼────────────────────────────┼─────────────────────────────┤
  │ 90 - 100      │ Grade A (Istimewa)         │ • Evaluasi Gaji: ACC Naik   │
  │               │                            │ • Payroll: Bonus Maksimal   │
  │               │                            │ • Kontrak PKWT: Angkat Tetap│
  ├───────────────┼────────────────────────────┼─────────────────────────────┤
  │ 75 - 89       │ Grade B (Sesuai Standar)   │ • Evaluasi Gaji: Standar    │
  │               │                            │ • Payroll: Insentif Normal  │
  │               │                            │ • Kontrak PKWT: Lanjut PKWT │
  ├───────────────┼────────────────────────────┼─────────────────────────────┤
  │ 60 - 74       │ Grade C (Perlu Pembinaan)  │ • Evaluasi Gaji: Tunda 3 Bln│
  │               │                            │ • Payroll: Tanpa Bonus      │
  │               │                            │ • Kontrak: Evaluasi Khusus  │
  ├───────────────┼────────────────────────────┼─────────────────────────────┤
  │ < 60          │ Grade D (Di Bawah Standar) │ • Evaluasi Gaji: Tidak Naik │
  │               │                            │ • Payroll: Penalti SP       │
  │               │                            │ • Kontrak: Putus / Terminasi│
  └───────────────┴────────────────────────────┴─────────────────────────────┘
```

---

## 4. Unified Payroll Engine (Sistem Penggajian Terpadu Satu Pintu)

### A. Alur Siklus Mundur Bulan (Work Period vs Payout Period)
- **Periode Kinerja (*Work Period*)**: Bulan di mana karyawan menjalankan tugas (contoh: **Oktober 2026**). Seluruh kehadiran 1–31 Oktober, jam lembur mingguan, dan rekam potongan disiplin ditarik dari periode ini.
- **Periode Pencairan (*Payout Period*)**: Bulan di mana gaji ditransfer ke rekening bank (contoh: **November 2026**).
- **Aturan Evaluasi Kenaikan**: Kenaikan gaji yang disetujui pada bulan berjalan otomatis dicairkan pada periode transfer berikutnya tanpa mengubah catatan pembukuan masa lalu, disertai label catatan penyesuaian (*adjustment note*).

### B. Formula Baku Perhitungan
$$\text{Gross Earnings} = \text{Gaji Pokok} + \text{Uang Makan} + \text{Upah Lembur} + \text{Penyesuaian/Insentif Kinerja}$$
$$\text{Total Deductions} = \text{Potongan Pelanggaran} + \text{Potongan Kelebihan Cuti} + \text{Potongan Cuti Berjenjang}$$
$$\mathbf{Net\ Salary\ (Take-Home\ Pay)} = \text{Gross Earnings} - \text{Total Deductions}$$

### C. Alur Tata Kelola Double Sign-Off Finansial
```
  [ HCM Admin ]  ──> Susun Draf Payroll & Agregasi Potongan (Status: DRAFT_HCM)
        │
        ▼
  [ HCM Manager] ──> Verifikasi & Klik [Approve & Sign HCM] (Status: APPROVED_BY_HCM)
        │            (Data terkunci untuk HCM, notifikasi meluncur ke Keuangan)
        ▼
  [ Keuangan ]   ──> Audit Grouping Dept-Divisi & Cek Rekening BRI (Status: PENDING_FINANCE_SIGN)
        │
        ▼
  [ Keuangan ]   ──> Eksekusi Transfer & Klik [Sign & Mark as Paid] (Status: PAID_COMPLETED)
                     (Terkunci total permanen, Slip Gaji Digital aktif)
```

---

## 5. Kamus Skema Basis Data (Database Schema Blueprint)

### A. Tabel Baru yang Akan Dibuat

#### 1. Tabel Izin Keluar Kantor (`hcm_office_exit_permits`)
```sql
CREATE TABLE hcm_office_exit_permits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    employee_id INTEGER NOT NULL REFERENCES hcm_employees(id) ON DELETE CASCADE,
    permit_date DATE NOT NULL,
    position VARCHAR(100),
    exit_time TIME NOT NULL,
    return_time TIME,
    purpose VARCHAR(150) NOT NULL,
    notes TEXT,
    attachment_status VARCHAR(30) DEFAULT 'Tidak Terlampir', -- Terlampir / Tidak Terlampir
    attachment_url VARCHAR(255),
    status VARCHAR(50) DEFAULT 'APPROVED',
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### 2. Tabel Pemotongan Gaji Bulanan (`hcm_salary_deductions`)
```sql
CREATE TABLE hcm_salary_deductions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    employee_id INTEGER NOT NULL REFERENCES hcm_employees(id) ON DELETE CASCADE,
    effective_payroll_month VARCHAR(7) NOT NULL, -- YYYY-MM (Bulan pencairan target)
    deduction_category VARCHAR(100) NOT NULL,    -- Pelanggaran, Kelebihan Cuti, Cuti Khusus Berjenjang
    calculation_type VARCHAR(20) NOT NULL,       -- fixed / percent
    percentage_rate DECIMAL(5,2),                -- misal: 25.00, 50.00
    deduction_amount DECIMAL(15,2) NOT NULL,
    notes TEXT NOT NULL,                         -- Keterangan alasan potongan
    base_salary_snapshot DECIMAL(15,2) NOT NULL,
    net_salary_snapshot DECIMAL(15,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'SUBMITTED',      -- SUBMITTED -> APPLIED_IN_PAYROLL
    created_by INTEGER REFERENCES users(id),
    approved_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### 3. Tabel Batch Penggajian Terpadu (`hcm_payrolls`)
```sql
CREATE TABLE hcm_payrolls (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    period_code VARCHAR(50) UNIQUE NOT NULL,     -- e.g. PAY-2026-11
    work_period_month VARCHAR(7) NOT NULL,       -- YYYY-MM (Bulan Kinerja, e.g. 2026-10)
    payout_period_month VARCHAR(7) NOT NULL,     -- YYYY-MM (Bulan Pencairan, e.g. 2026-11)
    payout_date DATE NOT NULL,
    total_employees INTEGER NOT NULL DEFAULT 0,
    total_base_salary DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_meal_allowance DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_overtime_pay DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_adjustments DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_deductions DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_net_payout DECIMAL(15,2) NOT NULL DEFAULT 0,
    status VARCHAR(50) DEFAULT 'DRAFT_HCM',      -- DRAFT_HCM -> APPROVED_BY_HCM -> APPROVED_BY_FINANCE -> PAID_COMPLETED
    hcm_signed_by INTEGER REFERENCES users(id),
    hcm_signed_at TIMESTAMP,
    finance_signed_by INTEGER REFERENCES users(id),
    finance_signed_at TIMESTAMP,
    payment_method VARCHAR(50) DEFAULT 'Bank Transfer BRI',
    payment_proof_url VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### 4. Tabel Detail Item Penggajian per Karyawan (`hcm_payroll_items`)
```sql
CREATE TABLE hcm_payroll_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    payroll_id INTEGER NOT NULL REFERENCES hcm_payrolls(id) ON DELETE CASCADE,
    employee_id INTEGER NOT NULL REFERENCES hcm_employees(id),
    department VARCHAR(100) NOT NULL,            -- e.g. [PRD] Produksi
    division VARCHAR(100) NOT NULL,              -- e.g. Divisi Jahit
    position VARCHAR(100) NOT NULL,              -- e.g. Operator Jahit
    job_level VARCHAR(50) NOT NULL,
    bank_name VARCHAR(50) DEFAULT 'Bank BRI',
    bank_account_no VARCHAR(50),
    base_salary DECIMAL(15,2) NOT NULL DEFAULT 0,
    meal_allowance DECIMAL(15,2) NOT NULL DEFAULT 0,
    overtime_pay DECIMAL(15,2) NOT NULL DEFAULT 0,
    increment_adjustment DECIMAL(15,2) NOT NULL DEFAULT 0,
    adjustment_notes TEXT,
    penalty_deduction DECIMAL(15,2) NOT NULL DEFAULT 0,
    leave_deduction DECIMAL(15,2) NOT NULL DEFAULT 0,
    tiered_deduction DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_earnings DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_deductions DECIMAL(15,2) NOT NULL DEFAULT 0,
    net_salary DECIMAL(15,2) NOT NULL DEFAULT 0,
    slip_token VARCHAR(64) UNIQUE NOT NULL,      -- Hash acak unik untuk akses Slip PDF
    is_paid BOOLEAN DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### 5. Tabel Template Indikator Kinerja (`hcm_kpi_templates`)
```sql
CREATE TABLE hcm_kpi_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    department VARCHAR(100) NOT NULL,
    division VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    indicator_name VARCHAR(150) NOT NULL,
    target_unit VARCHAR(50) NOT NULL,            -- pcs, %, omzet, task
    target_value DECIMAL(15,2) NOT NULL,
    weight_percentage DECIMAL(5,2) NOT NULL,     -- total per posisi = 70% (hard output)
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### 6. Tabel Penilaian Kinerja Bulanan (`hcm_kpi_appraisals`)
```sql
CREATE TABLE hcm_kpi_appraisals (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    employee_id INTEGER NOT NULL REFERENCES hcm_employees(id) ON DELETE CASCADE,
    period_month VARCHAR(7) NOT NULL,            -- YYYY-MM
    evaluator_id INTEGER REFERENCES users(id),   -- Atasan / Lead Divisi
    hard_output_score DECIMAL(5,2) NOT NULL,     -- 0 - 100
    behavior_score DECIMAL(5,2) NOT NULL,        -- 0 - 100
    final_score DECIMAL(5,2) NOT NULL,           -- (Hard*70%) + (Behavior*30%)
    final_grade VARCHAR(5) NOT NULL,             -- A, B, C, D
    status VARCHAR(50) DEFAULT 'DRAFT',          -- DRAFT -> SUBMITTED -> APPROVED_BY_HCM
    evaluator_notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### B. Modifikasi Tabel Eksisting

#### Kolom Tambahan pada `hcm_employees`:
- `division`: `VARCHAR(100) NULL AFTER department`
- `original_join_date`: `DATE NULL AFTER join_date`

---

## 6. Tahapan Eksekusi Bertahap (6 Fase Pengerjaan Terstruktur)

Rencana aksi pelaksanaan disusun secara bertahap dan teratur agar tidak mengganggu operasional sistem yang sedang berjalan:

```
[ FASE 1: HIERARKI ORGANISASI & TENURE BUCKETS ]
  ├── Migrasi kolom `division` & `original_join_date` di `hcm_employees`
  ├── Seeder Master Data `status_lampiran` & `kategori_potongan_gaji`
  └── Accessor model Tenure Buckets (1 Thn, 2 Thn, 3+ Thn) & Filter UI
         │
         ▼
[ FASE 2: IZIN KELUAR KANTOR (GATE PASS) & PRESENSI ]
  ├── Migrasi tabel `hcm_office_exit_permits`
  ├── Backend Model, Controller & Validasi Lampiran
  └── UI Tab Gate Pass pada `/hcm/attendance`
         │
         ▼
[ FASE 3: EVALUASI KONTRAK & PEMOTONGAN GAJI BULANAN ]
  ├── Evaluasi siklus 6 bulan, keputusan ACC/Tunda/Tidak, custom milestone
  ├── Contract Renewal Workflow (simpan `original_join_date` permanen)
  ├── Migrasi tabel `hcm_salary_deductions`
  └── UI Monthly Salary Adjustments (Disiplin, Cuti, Maternity 25%/50%)
         │
         ▼
[ FASE 4: UNIFIED PAYROLL ENGINE & MULTI-LEVEL GROUPING ]
  ├── Migrasi tabel `hcm_payrolls` & `hcm_payroll_items`
  ├── Backend Engine Siklus Mundur Bulan (Kinerja Okt -> Payout Nov)
  ├── UI Dashboard `/hcm/payroll` bertingkat: Departemen -> Divisi -> Karyawan
  └── Double Sign-Off Finansial: HCM Sign -> Keuangan Paid & Lock
         │
         ▼
[ FASE 5: MODUL PENILAIAN KINERJA (KPI ENGINE) ]
  ├── Migrasi tabel `hcm_kpi_templates` & `hcm_kpi_appraisals`
  ├── Setup Template Indikator Output per Divisi (Produksi, QC, Marketing, Media, Finance)
  └── Integrasi trigger nilai Grade A/B/C/D ke bonus payroll & evaluasi kenaikan gaji
         │
         ▼
[ FASE 6: SLIP GAJI DIGITAL PDF, EKSPOR FINANSIAL & TESTING ]
  ├── Generator Slip Gaji Digital Transparan (PDF `laravel-dompdf`) + QR Hash
  ├── Ekspor Excel Rekapitulasi Penggajian per Departemen & Divisi
  ├── Ekspor Format Transfer Massal Bank BRI
  └── End-to-End Feature Testing & Validasi Sistem
```

---

## 7. Desain Antarmuka Pengguna & Navigasi Sistem

### A. Tampilan Menu Navigasi Sidebar (`SidebarContent.jsx`)
Seksi menu **👥 KEPEGAWAIAN** memiliki struktur lengkap sebagai berikut:
```
👥 KEPEGAWAIAN
 ├── 📊 Dashboard HCM
 ├── 🗂️ Master Data (Vertical Split-Pane)
 ├── 👨‍💼 Data Karyawan & Magang (Filter Divisi & Tenure Bucket)
 ├── 📜 Kontrak & PKWT (Renewal & Evaluasi Berkala)
 ├── 🎯 Penilaian Kinerja (KPI Appraisal)
 ├── 💰 Kompensasi & Gaji (Riwayat Kenaikan & Potongan Bulanan)
 ├── 💳 Payroll & Penggajian Terpadu (Grouping Dept-Divisi)
 ├── ⏱️ Presensi & Log Harian (Presensi Matrix & Gate Pass)
 ├── ⚡ Lembur Mingguan (Double Sign-Off)
 ├── 🍽️ Uang Makan Bulanan (Hold Rule 4x Telat)
 ├── 🎁 Reward & Penghargaan
 ├── 🎯 Rekrutmen & Loker
 ├── 📁 Dokumen & Persuratan (RAB/LPJ & Agenda Surat)
 └── 📅 Kalender & Agenda Perusahaan
```

### B. Tampilan Dashboard Payroll Multi-Level Grouping (`/hcm/payroll/{batch}`)
Antarmuka menyajikan ringkasan bertingkat yang bersih dan elegan:
- **Baris Header Ringkasan**: Total Headcount, Total Belanja Gaji Bersih, Status Double Sign-Off, Tombol Aksi.
- **Accordion Level 1 (Departemen)**:
  - `[FIN] FINANCE & ACCOUNTING` — Rp XX.XXX.XXX (Total 4 Karyawan)
  - `[PRD] PRODUKSI` — Rp XX.XXX.XXX (Total 28 Karyawan)
- **Sub-Tabel Level 2 (Divisi di Bawah Departemen)**:
  - Divisi Setting Printing — Rp X.XXX.XXX
  - Divisi Jahit (Sewing) — Rp X.XXX.XXX
  - Divisi Finishing — Rp X.XXX.XXX
- **Tabel Rincian Level 3 (Individual Karyawan)**:
  - Kolom: Nama Pegawai | Posisi | Gaji Pokok | Uang Makan | Lembur | Penyesuaian | Potongan | Net Take-Home Pay | No Rekening BRI | Aksi (Lihat Slip).

---

## 8. Standar Kualitas & Kriteria Penerimaan (Definition of Done)

1. **Akurasi 100% Bebas Halusinasi**: Seluruh skema tabel, formula hitung, opsi dropdown, dan alur validasi merujuk persis pada Blueprint Excel 2.
2. **Integritas Masa Kerja Permanen**: Nilai `original_join_date` tidak berubah saat pembaharuan kontrak PKWT.
3. **Multi-Level Grouping Berfungsi Sempurna**: Seluruh rekapitulasi penggajian terkelompok rapi per Departemen $\rightarrow$ Divisi $\rightarrow$ Karyawan.
4. **Siklus Mundur Bulan Akurat**: Kinerja bulan berjalan (cut-off 1–31) dibayarkan pada bulan berikutnya tanpa merusak pembukuan masa lalu.
5. **Kinerja Tersinkronisasi Finansial**: Nilai KPI Grade A, B, C, D terhubung langsung dengan keputusan evaluasi berkala dan bonus/penalti payroll.
6. **Slip Gaji Digital Transparan**: Menampilkan rincian pendapatan kotor, potongan itemized, alasan pemotongan, watermark status `PAID`, dan QR verifikasi.
