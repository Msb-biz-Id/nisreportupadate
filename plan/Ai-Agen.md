# Plan: AI Agent Total (Order + Keuangan + HCM + Purchasing, OpenAI-Compatible)

> Status: Roadmap Disahkan & Diperluas (Full Audit, Predictive Engines, Anomaly Matrix, & Complete Reports Integration).
> Tujuan: **Satu AI Agent membaca SELURUH data 4 modul** (Order, Keuangan, HCM, Purchasing) dan **SELURUH laporan yang ada di sistem**, menghitung angka secara deterministik di PHP/SQL (anti-halusinasi), mendeteksi anomali secara dini, menghitung proyeksi/prediksi masa depan, dan memberikan rekomendasi taktis.
> Antarmuka LLM: Provider **OpenAI-compatible** apa pun (DeepSeek, OpenAI, Groq, OpenRouter, Ollama lokal).
> Prinsip: Non-interference, read-only terhadap domain bisnis, namespace `Ai` mandiri, tabel `ai_*` terisolasi, zero breaking changes.

---

## 1. Audit Fakta Codebase vs AI Agent Plan

Berdasarkan audit langsung terhadap seluruh file model, controller, service, report runner, dan observer:

| Domain | Model Aktual | Controller & Laporan Siap Pakai | Kondisi & Temuan Audit |
| :--- | :--- | :--- | :--- |
| **Order & Produksi** | 13 Model: `Order`, `OrderItem`, `OrderNameset`, `OrderPayment`, `OrderProgressDetail`, `Rijek`, `Refund`, `Invoice`, `InvoiceItem`, `DesignDeposit`, `POLockStatus`, `POChangeLog`, `POVersion` | `ReportRunner` (7 laporan order), `ComparisonRunner`, `OrderController`, `ProductionController` (Gantt, Kanban, Lateness), `TrackingController` | **Lengkap.** Lifecycle 9 status terdefinisi jelas. Terdapat log perubahan PO (`POChangeLog`) dan lock status yang dapat dianalisis untuk deteksi PO tidak stabil / revisi abnormal. |
| **Keuangan** | 5 Model Inti + 4 Relasi: `Pemasukan`, `Pengeluaran`, `KategoriPemasukan`, `KategoriPengeluaran`, `MasterJenisPembayaran`, `Invoice`, `Refund`, `OrderPayment`, `DesignDeposit` | `ReportRunner` (pemasukan, pengeluaran, arus kas bank), `InvoiceController`, `RefundController`, `DesignDepositController` | **Catatan Audit C2:** Terdapat pencatatan ganda ledger `Pemasukan` (PO Published = total tagihan saat terbit + Pembayaran PO verified). **Agent wajib menerapkan aturan ketat §5.4:** kas riil hanya dari Pembayaran PO verified; PO Published dihitung sebagai piutang/pipeline. Arus kas bank tersedia rekonsiliasi mutasi rekening. |
| **HCM (SDM)** | 29 Model: `HcmEmployee`, `HcmContract`, `HcmAttendance`, `HcmLeaveRequest`, `HcmOvertime`, `HcmOvertimeBatch`, `HcmMealAllowanceBatch`, `HcmMealAllowanceItem`, `HcmPayroll`, `HcmPayrollItem`, `HcmCompensation`, `HcmCompensationHistory`, `HcmSalaryDeduction`, `HcmShiftRoster`, `HcmWorkShift`, `HcmJobPosting`, `HcmJobApplicant`, `HcmApplicantInterview`, `HcmEmployeeReward`, `HcmOfficeExitPermit`, `HcmIntern`, `HcmOnboarding`, `HcmOffboarding`, `HcmCompanyEvent`, `HcmAgendaLetter`, `HcmInternalDocument`, `HcmExternalLetter`, `HcmMasterCategory`, `HcmMasterOption` | `HcmDashboardController` (ringkasan 8 modul kepegawaian), `HcmAttendanceController`, `HcmPayrollController`, `HcmRecruitmentController`, `HcmPdfController` (8 generator dokumen PDF), 8 Excel Exports | **Sangat Kaya Data.** Presensi harian, lembur, uang makan, cuti, penggajian, rekrutmen, hingga habis kontrak PKWT H-30 dan evaluasi probation H-7 sudah ada agregasinya. Agent tinggal memanfaatkan data agregat ini tanpa mengirim data PII/gaji pokok individual ke LLM. |
| **Purchasing & Aset** | 13 Model: `PurchasingOrder`, `PurchasingOrderItem`, `PurchasingPayment`, `PurchasingVendor`, `PurchasingVendorContact`, `PurchasingAsset`, `PurchasingAssetMaintenance`, `PurchasingAssetMutation`, `PurchasingAssetRetirement`, `PurchasingAssetSuggestion`, `PurchasingMaterialStock`, `PurchasingMaterialUsage`, `PurchasingMasterOption` | `PurchasingReportController` (OPEX/CAPEX, rekapitulasi departemen, vendor spending, analisis TOP), `PurchasingMaterialController`, `PurchasingAssetController`, `PurchasingDashboardController` | **Lengkap & Terstruktur.** Sudah memiliki double sign-off pengadaan, TOP, kartu stok, dan rekap pemakaian material. **Catatan Audit Krusial (Material Silo):** Tabel master bahan order (`bahan_kains`) terisolasi dari tabel stok gudang (`purchasing_material_stocks`). Tanpa tabel pemetaan relasional eksplisit (`ai_material_standards`), pengingat pembelian rentan salah sasaran (PO 100 butuh Bahan A, sistem malah mengingatkan beli Bahan B karena hanya mengecek stok global). Wajib deterministik mapping! |
| **AI Existing** | `GeminiClient` (OpenAI-compatible), `AiToolService` (5 tool pasif), `AiToolLog`, `ChatMemory` | `AiToolsController`, `SettingsController` | Client sudah multi-provider dengan failover & masked keys. Perlu ditingkatkan dari sekadar form pasif menjadi Autonomous Agent Loop dengan function-calling deterministik. |

---

## 2. Katalog Laporan Sistem: Integrasi Total ke AI Agent

Seluruh laporan yang telah berjalan di sistem dijadikan **Sensor Data Utama** bagi AI Agent:

```
┌────────────────────────────────────────────────────────────────────────┐
│                   KATALOG LAPORAN SEBAGAI SUMBER DATA                  │
├──────────────────────────┬─────────────────────────┬───────────────────┤
│ 1. Penjualan & Produk    │ 6. Monitoring Deadline  │ 11. Arus Kas Bank │
│ 2. Pelanggan             │ 7. Rijek Produksi       │ 12. Kinerja Prod  │
│ 3. Wilayah               │ 8. Refund               │ 13. Brand Compare │
│ 4. Jenis PO              │ 9. Pemasukan            │ 14. Purchasing    │
│ 5. Status PO             │ 10. Pengeluaran         │ 15. HCM Dashboard │
└──────────────────────────┴─────────────────────────┴───────────────────┘
                                   │
                                   ▼
┌────────────────────────────────────────────────────────────────────────┐
│              DETERMINISTIC DATA ENGINE (PHP & SQL)                     │
│  - Agregasi Snapshot        - 30 Detektor Anomali (D1-D14, OA, FA, HA) │
│  - Rule Anti-Ganda          - 8 Detektor Fraud Pengadaan (F1-F8)       │
│  - Korelasi Lintas Modul    - 4 Detektor BOM Variance (M1-M4)          │
│  - 8 Model Prediktif        - Skor Risiko PO & Vendor (0-100)          │
└────────────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌────────────────────────────────────────────────────────────────────────┐
│              AI AGENT LOOP (OpenAI-Compatible LLM)                     │
│  - Analisis Naratif Berbasis Bukti Faktual                             │
│  - Prioritisasi Tindakan & Rekomendasi Taktis                          │
│  - Otomasi Notifikasi WhatsApp & Telegram (Severity High & Critical)   │
└────────────────────────────────────────────────────────────────────────┘
```

### 2.1. Domain Order & Operasional Pabrik

| Slug / Sumber Laporan | Tabel DB Asal | Metrik Fakta yang Dibaca | Deteksi Anomali Deterministik | Sifat Prediktif Spesifik (Model & Horizon) | Target Notifikasi WhatsApp |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `penjualan-produk` | `order_items` join `orders` | Total Qty, Total Order, Avg Qty/Order, Total Nilai per Produk, Add-on Share | Penjualan produk anjlok mendadak (>40%), produk mati (*dead SKU*), dominasi ekstrim 1 produk (>70%). | **Demand Forecasting 4–12 Minggu**: Model Exponential Smoothing dengan indeks musiman turnamen & event. | WhatsApp Grup Sales & Manajemen |
| `pelanggan` | `orders` join `customers` | Frekuensi order, total pcs, total nilai, tanggal order terakhir, repeat order rate | Pelanggan VIP tidak repeat order >60 hari, lonjakan piutang dari pelanggan riwayat bayar buruk. | **Churn Probability & CLV 90 Hari**: Skoring RFM (Recency, Frequency, Monetary) memprediksi probabilitas berhenti belanja. | WhatsApp Sales Executive & Owner |
| `wilayah` | `customers` join `orders` | Sebaran Provinsi, Kabupaten, Kecamatan; konsentrasi geografis | Biaya ongkir membengkak vs nilai barang, konsentrasi 1 wilayah >80% (risiko saturasi). | **Regional Growth & Freight Trend**: Proyeksi pertumbuhan klaster pemesanan regional & estimasi inflasi ongkos kirim. | WhatsApp Tim Ekspedisi & Sales |
| `jenis-po` | `orders` (`is_special_order`, `is_reseller_price`, `paket_order_id`) | Komposisi PO Normal vs Spesial vs Reseller; marjin & volume | Lonjakan Special Order >30% kapasitas (ancaman bottleneck tim desain/setting), marjin tergerus. | **Capacity Mix & Gross Margin Forecast**: Simulasi dampak pergeseran jenis order terhadap laba kotor 30 hari. | WhatsApp Owner & Kepala Desain |
| `status-po` | `orders.status_po` | Distribusi PO per status (Draft, Published, On Progress, Siap Kirim, Delay, Hold) | Penumpukan PO di status `on_progress` atau `siap_dikirim` >3 hari, status `hold` tanpa alasan >5 hari. | **Throughput Velocity Forecast**: Estimasi jumlah PO yang akan selesai per hari dalam 14 hari ke depan. | WhatsApp Spv Produksi & Pabrik |
| `monitoring-deadline` | `orders.deadline_customer`, `deadline_produksi`, `order_progress_details` | Sisa hari menuju deadline produksi & deadline customer, status keterlambatan | PO dengan selisih sisa hari minus tapi status fisik masih di awal (Setting/Printing). | **PO Delivery Slippage Score (0–100)**: Skoring probabilitas telat & estimasi keterlambatan hari ($T_{\text{late}}$). | WhatsApp Tim Produksi & Pabrik (Alert Real-time) |
| `kinerja-produksi` | `order_progress_details` join `progress` | Durasi pengerjaan per tahapan (Setting, Printing, Potong, Jahit, QC, Steam, Packing) | Durasi satu tahapan pengerjaan >2x median durasi historis (bottleneck aktif). | **Stage Cycle Time & Bottleneck Prediction**: Proyeksi titik kemacetan mesin/lini sebelum terjadi penumpukan fisik. | WhatsApp Spv Produksi & Kepala Jahit |

### 2.2. Domain Mutu, Finansial, & Rekonsiliasi Kas

| Slug / Sumber Laporan | Tabel DB Asal | Metrik Fakta yang Dibaca | Deteksi Anomali Deterministik | Sifat Prediktif Spesifik (Model & Horizon) | Target Notifikasi WhatsApp |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `rijek` | `rijeks` join `orders` | Total rijek per jenis, tingkat keparahan, kendala, tahapan produksi penyebab | Rasio rijek per batch >5%, rijek berulang pada operator atau mesin yang sama. | **Scrap Rate & Rework Cost Projection**: Estimasi biaya pemborosan pengerjaan ulang 30 hari ke depan. | WhatsApp Spv QC & Kepala Produksi |
| `refund` | `refunds` join `orders` | Nominal refund, jenis masalah (salah size, cacat sablon, telat kirim), rasio refund/omzet | Spike nominal refund mingguan >2x rata-rata 4 minggu, pola refund berulang pada 1 tim/lini. | **Warranty Liability & Customer Friction**: Proyeksi potensi kerugian klaim garansi & tren indeks kepuasan. | WhatsApp Manajer Keuangan & Owner |
| `pemasukan` | `pemasukans` (`is_auto`, kategori 'Pembayaran PO') | Kas masuk terverifikasi, kategori pemasukan, sumber (auto vs manual) | Setoran manual tanpa referensi invoice, tren kas masuk harian anjlok >30% dari rata-rata. | **Revenue Pacing & Inflow Runway**: Proyeksi pencapaian target bulanan brand (`BrandTarget`) & kas masuk 30 hari. | WhatsApp Manajer Keuangan & Owner |
| `pengeluaran` | `pengeluarans` | Realisasi biaya operasional, overhead, kategori pengeluaran, deviasi anggaran | Pengeluaran tak terduga melonjak >20% di luar pos rutin, biaya servis mesin membengkak. | **Operating Burn Rate Forecast**: Proyeksi konsumsi kas bulanan & deteksi dini pembengkakan overhead. | WhatsApp Admin Keuangan & Owner |
| `arus-kas-bank` | `bank_accounts`, mutasi kas | Mutasi debit/kredit per rekening bank, rekonsiliasi transfer customer vs mutasi riil | Mutasi kredit bank tak bertuan >48 jam, saldo rekening di bawah batas aman operasional. | **Cashflow Runway & Insolvency Risk**: Proyeksi titik nadir saldo bank 30 hari (kapan saldo menyentuh batas kritis). | WhatsApp Pribadi Owner & Keuangan |
| `ComparisonRunner` | Multibrand data agregat | Head-to-head performa multibrand: omzet, PO count, AOV, rasio rijek | Kanibalisasi antarmerek internal, brand dengan rasio rijek jauh di atas rata-rata grup. | **Market Share Shift & Brand Growth**: Pemodelan pergeseran pangsa pasar antarmerek grup. | WhatsApp Owner & Brand Manager |

### 2.3. Domain Pengadaan, Bahan Baku, & Aset (Purchasing)

| Slug / Sumber Laporan | Tabel DB Asal | Metrik Fakta yang Dibaca | Deteksi Anomali Deterministik | Sifat Prediktif Spesifik (Model & Horizon) | Target Notifikasi WhatsApp |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `PurchasingReport` (KPI) | `purchasing_orders`, `purchasing_order_items` | Belanja OPEX vs CAPEX, total order pengadaan, persentase terbayar vs terutang | Pembengkakan belanja OPEX divisi tanpa korelasi kenaikan output produksi, transaksi mendekati limit. | **Procurement Budget Runway**: Estimasi kebutuhan modal kerja pengadaan kuartalan & alokasi CAPEX. | WhatsApp Kepala Purchasing & Owner |
| Rekap Departemen & Divisi | `purchasing_orders` group by `department` | Alokasi pengeluaran per departemen, rata-rata nominal per pengajuan | Departemen dengan tren belanja melonjak >50% tanpa justifikasi pengerjaan PO. | **Departmental Spending Projection**: Prediksi beban pengeluaran tiap departemen bulan depan. | WhatsApp Admin Keuangan |
| Rekap Vendor & TOP | `purchasing_vendors`, `purchasing_payments` | Vendor Pareto, histori harga beli per satuan, status TOP (H-3, jatuh tempo, overdue) | Monopoli vendor tunggal >70%, harga naik >15% vs median 90 hari, penumpukan jatuh tempo serentak. | **Debt Maturity Wall 30 Hari**: Profil jatuh tempo utang & jadwal pembayaran termin vendor paling optimal. | WhatsApp Bagian Pembelian & Keuangan |
| Stok & Pemakaian Material | `purchasing_material_stocks`, `purchasing_material_usages` | Stok fisik akhir, konsumsi harian, kartu stok, mutasi masuk/keluar | Stok minus/koreksi manual abnormal, variansi pemakaian bahan vs standar BOM jersey (M1–M4). | **Dynamic Stockout Countdown & ROP**: Hitung mundur hari menuju kehabisan bahan & reorder point otomatis. | WhatsApp Kepala Gudang & Purchasing |
| Manajemen Aset & Servis | `purchasing_assets`, `purchasing_asset_maintenances` | Nilai buku aset, frekuensi servis, biaya perbaikan kumulatif vs harga beli awal | Biaya servis kumulatif >50% harga perolehan baru aset, mesin tanpa servis rutin >6 bulan. | **MTBF & Total Cost of Ownership (TCO)**: Prediksi siklus kerusakan mesin & waktu tepat penggantian aset baru. | WhatsApp Spv Maintenance & Pabrik |

### 2.4. Domain Sumber Daya Manusia & Kapasitas (HCM)

| Modul / Laporan HCM | Tabel DB Asal | Metrik Fakta yang Dibaca | Deteksi Anomali Deterministik | Sifat Prediktif Spesifik (Model & Horizon) | Target Notifikasi WhatsApp |
| :--- | :--- | :--- | :--- | :--- | :--- |
| Presensi Harian & Matriks | `hcm_attendances` | Tingkat kehadiran, keterlambatan, pulang cepat, izin/sakit, alpha/mangkir | Mangkir massal divisi produksi (>10%), lonjakan mangkir hari Senin/Jumat, absensi manual abnormal. | **Lost Time Rate & Absenteeism Forecast**: Estimasi jam kerja hilang bulanan & prediksi absensi pasca-libur. | WhatsApp HRD / HCM Manager |
| Lembur (Overtime) | `hcm_overtimes`, `hcm_overtime_batches` | Jam lembur per orang/divisi, biaya lembur mingguan, kepatuhan batas lembur | Lembur >20 jam/minggu per orang, klaim lembur pada tanggal absen (*ghost overtime* F8). | **Overtime Cost & Fatigue Projection**: Estimasi biaya lembur bulanan & prediksi risiko cacat akibat kelelahan. | WhatsApp Spv QC & HRD Manager |
| Cuti & Perizinan | `hcm_leave_requests` | Cuti diajukan, cuti disetujui, cuti pending per divisi & rentang tanggal | Pengajuan cuti bersamaan oleh >20% personel di divisi krusial (misal: jahit) di masa deadline padat. | **Workforce Deficit Projection**: Prediksi defisit jam kerja efektif per divisi 14 hari ke depan. | WhatsApp HRD & Kepala Produksi |
| Penggajian (Payroll) | `hcm_payrolls`, `hcm_payroll_items`, `hcm_compensations` | Total take-home pay, komponen tunjangan, potongan, status double sign-off | Deviasi total payroll bulanan >15% tanpa penambahan staf, pembatalan/downgrade status approval. | **Direct Labor Cost per Unit (DLCL)**: Proyeksi beban biaya tenaga kerja langsung per potong pakaian jadi. | WhatsApp Manajer Keuangan & Owner |
| Kontrak PKWT & Probation | `hcm_contracts`, `hcm_employees` | Tanggal habis kontrak, status probation, masa kerja, histori evaluasi | Kontrak aktif sisa $\le 30$ hari (H-30) tanpa evaluasi perpanjangan, probation mendekati 90 hari (H-7). | **Turnover & Expiry Wave Forecast**: Proyeksi gelombang habis kontrak & risiko kekosongan posisi krusial. | WhatsApp HRD / HCM Manager |
| Rekrutmen & Pelamar | `hcm_job_postings`, `hcm_job_applicants`, `hcm_applicant_interviews` | Total pelamar per loker, time-to-hire, channel ROI, rasio wawancara ke penawaran | Lowongan krusial pabrik (penjahit/operator) belum terisi >30 hari, saluran rekrutmen konversi 0%. | **Time-to-Fill Forecast**: Estimasi hari pemenuhan kebutuhan staf baru berdasarkan saluran rekrutmen. | WhatsApp Tim Rekrutmen & HRD |

### 2.5. Audit & Sinkronisasi Total: `ReportMessageBuilder` vs `ReportRunner` (Single Source of Truth)

> **Temuan Kritis Lapangan**:
> Saat ini, sistem memiliki dua generator laporan yang berjalan sendiri-sendiri tanpa sinkronisasi:
> 1. `ReportRunner.php`: Menghitung data analitik untuk antarmuka web dashboard (`/reports/{slug}`).
> 2. `ReportMessageBuilder.php`: Menghitung data untuk pesan otomatis WhatsApp/Telegram harian/mingguan via perintah artisan `reports:send`.
>
> **Fakta Audit Kode**: `ReportMessageBuilder.php` **SAMA SEKALI TIDAK MEMANGGIL `ReportRunner.php`**!
> Keduanya menjalankan kueri SQL masing-masing dengan formula yang berpotensi menghasilkan angka berselisih (*Data Discrepancy*).

#### 1. Titik Kritis Perbedaan Data (Dual-Query Divergence)

| Dimensi Laporan | Formula di `ReportRunner.php` (Web Dashboard) | Formula di `ReportMessageBuilder.php` (WhatsApp Bot) | Dampak & Risiko Mismatch |
| :--- | :--- | :--- | :--- |
| **Omzet & Pendapatan** | `SUM(order_items.subtotal)` hanya pada item utama (`is_addon = false`) dengan join `order_items`. | `Order::where('status_po', '!=', 'draft')->sum('total_tagihan')`. | **Selisih Angka!** Jika ada diskon, ongkos kirim, atau item addon, angka omzet di WhatsApp akan berbeda dengan angka di layar dashboard web. |
| **Keterlambatan PO** | Berbasis matriks durasi tahapan fisik dan sisa hari `deadline_customer`. | Kueri mentah `COALESCE(end_production_date, deadline_customer) < ?` dan `diffInDays(..., false)`. | PO yang ditandai telat di WhatsApp bisa tidak sesuai dengan status monitoring deadline di web. |
| **Sisa Tagihan / Piutang** | Berbasis filter kas terverifikasi (`is_auto = true`) di tabel `pemasukans`. | Me-load seluruh data via `Order::get()` lalu melooping fungsi PHP `$o->sisaTagihan() > 0.99`. | **Bahaya N+1 & Memory Spike!** Beban memori melonjak jika volume PO mencapai ratusan/ribuan baris. |
| **Cakupan Modul** | Meliputi 15 laporan (Order, Finance, Rijek, Kas Bank, dll). | **Hanya meng-cover Order & Keuangan.** Modul Purchasing (Stok Bahan Kritis, Vendor TOP) dan HCM (Absensi, Lembur, PKWT) **0% belum ada**. | Manajemen tidak pernah menerima peringatan bahan habis atau lembur bengkak via pesan terjadwal WhatsApp. |
| **Kualitas AI Insight** | Menggunakan 12 Agent spesialis dengan 30 detektor deterministik. | Hanya fungsi singkat `aiInsight($aiCtx)` dengan teks ringkasan 2 kalimat ke Gemini. | Insight AI di WhatsApp sering berupa saran umum/generik (*AI slop*), bukan berbasis anomali riil. |

#### 2. Rencana Refactoring: Penyatuan Menjadi Single Source of Truth

Untuk menjamin angka di WhatsApp dan angka di Web Dashboard **100% kembar identik**:

1. **Jadikan `ReportRunner` & Snapshot Cache sebagai Satu-Satunya Sumber Data**:
   - `ReportMessageBuilder` dilarang menulis kueri SQL mentah sendiri.
   - Inject `ReportRunner` dan `AiSnapshotCache` ke dalam konstruktor `ReportMessageBuilder`:
     ```php
     class ReportMessageBuilder
     {
         public function __construct(
             private readonly ReportRunner $runner,
             private readonly ?LlmClient $ai = null
         ) {}
     }
     ```
2. **Konsumsi Metrik Agregat Resmi**:
   - Laporan Harian Superadmin menarik angka dari `$this->runner->run('penjualan-produk', ...)` dan `$this->runner->run('monitoring-deadline', ...)`.
   - Laporan Keuangan menarik angka dari `$this->runner->run('pemasukan', ...)` dan `$this->runner->run('arus-kas-bank', ...)`.
3. **Prinsip Mutlak Preservasi Format (100% Visual Preservation of `ReportMessageBuilder.php`)**:
   - **Tampilan Luar Tidak Berubah**: Seluruh tata letak, emoji resmi (`📊`, `📦`, `📈`, `🚦`, `⚠️`, `⏰`, `💰`, `🏆`, `🛑`, `🟢`, `🟡`, `🔴`), progress bar `makeProgressBar()` (`[████░░░░░░] 40%`), format penanggalan, struktur sub-bullet (`  -`), dan footer penutup (`_ProTrack · BRAND · PERIODE · d/m/Y H:i_`) **DIJAMIN 100% SAMA PERSIS**.
   - **Yang Diubah HANYA Mesin di Balik Layar (Backend Engine Only)**: Kueri SQL duplikat yang rawan N+1 diganti dengan data bersih dari `ReportRunner`. Mata pembaca di WhatsApp melihat format laporan yang familiar dan konsisten, namun angka di dalamnya kini 100% akurat dan sinkron dengan dashboard web.
4. **Ekspansi Modul Purchasing & HCM Mengikuti Bahasa Desain yang Sama**:
   - Template Purchasing baru menggunakan tata letak identik: `📦 *STATUS STOK MATERIAL:*`, `⚠️ *DEFISIT BAHAN ANTREAN PO:*`, `💰 *KEWAJIBAN TOP VENDOR:*`.
   - Template HCM baru menggunakan tata letak identik: `👥 *KAPASITAS & PRESENSI SDM:*`, `⏰ *LEMBUR DIVISI PRODUKSI:*`, `📋 *KONTRAK PKWT H-30:*`.
5. **Penyuntikan Insight AI Faktual**:
   - Mengganti teks Gemini pasif dengan output resmi dari `AiSnapshotCache` (memuat daftar anomali, skor risiko, dan checklist prioritas tindakan yang sudah divalidasi).

---

### 2.6. Pemetaan Lengkap: Laporan Terjadwal (`ReportMessageBuilder.php`) vs Sensor AI Agent

Agar batasan tugas dan KPI menjadi sangat jelas, sistem membagi aliran data menjadi dua kanal yang saling melengkapi:

1. **Kanal 1: Laporan Operasional Terjadwal (Harian, Mingguan, Bulanan)**
   - Bertujuan memberikan rekapitulasi angka bisnis berkala kepada penanggung jawab divisi sesuai peran (*role*).
   - Format pesan dan tata letak visual **100% mengikuti `ReportMessageBuilder.php`**.
   - Jadwal pengiriman otomatis via artisan command `reports:send {periode}`.
2. **Kanal 2: Sensor Cerdas AI Agent (Autonomous Sentinel & On-Demand Diagnostics)**
   - Bertujuan mendeteksi anomali tersembunyi, menghitung probabilitas masa depan (prediktif), mendeteksi potensi fraud/pemborosan, dan memicu **peringatan darurat seketika (*event-driven alert*)** saat ada kondisi kritis tanpa harus menunggu jadwal laporan rutin.
   - Menyediakan fitur tanya-jawab interaktif berbasis bukti di antarmuka web chat (`AgentChat.jsx`).

#### Matriks Peran, Periode, KPI Output, & Sinergi Data

| Role Penerima | Periode Jadwal | KPI & Output Visual Baku `ReportMessageBuilder.php` | Sumber Data Sinkron (`ReportRunner.php`) | Nilai Tambah Khusus AI Agent (`AiSnapshotCache`) |
| :--- | :--- | :--- | :--- | :--- |
| **Superadmin** | **Harian** (07:00 WIB) | • Total Brand Aktif<br>• Status Order: Masuk, Proses, Selesai, Delay<br>• Progress Bar Penyelesaian: `[████░░░░░░] %`<br>• Indikator Lampu: 🟢/🟡/🔴<br>• Daftar PO Terlambat & Deadline < 3 Hari<br>• PO Siap Kirim Belum Lunas<br>• Revenue Hari ini, Minggu ini, Bulan ini<br>• Top 3 Brand Terlaris Bulan Ini | `ReportRunner::statusPo`, `monitoringDeadline`, `penjualanProduk` | **Skor Kesehatan Operasional Grup (0–100%)**:<br>Deteksi anomali keterlambatan sistemik lintas cabang dan daftar 3 tindakan mitigasi prioritas pagi. |
| **Superadmin** | **Mingguan** (Senin 07:00) | • Rekap Order Masuk vs Selesai 7 Hari<br>• Revenue Mingguan per Brand<br>• Evaluasi Bottleneck Antarcabang | `ReportRunner::penjualanProduk` (filter mingguan), `monitoringDeadline` | **Throughput Velocity Forecast**:<br>Proyeksi kapasitas pengerjaan minggu depan dan deteksi ketimpangan beban antarpabrik. |
| **Superadmin** | **Bulanan** (Tgl 1 07:00) | • Pertumbuhan Omzet MoM (`+x% vs bulan lalu`)<br>• Evaluasi Target Brand Bulanan<br>• Rekapitulasi Pembagian Pasar Antarmerek | `ReportRunner::penjualanProduk` (filter bulanan), `ComparisonRunner` | **Market Share Shift & Churn Trend**:<br>Analisis tren migrasi pelanggan dan peramalan target penjualan kuartalan. |
| **Admin Produksi** | **Harian** (07:30 WIB) | • Status Order: Dalam Proses, Masuk Hari ini, Selesai Hari ini<br>• Detail 5 PO Baru Masuk (Produk & Qty)<br>• Deadline Mendekat (< 7 Hari)<br>• Daftar PO Terlambat (Hari Keterlambatan)<br>• Tingkat Rijek Kumulatif 30 Hari (%) | `ReportRunner::kinerjaProduksi`, `monitoringDeadline`, `rijek` | **PO Delivery Slippage Score**:<br>Skor risiko keterlambatan PO (0–100) dan deteksi PO diam 24 jam (`OA3A`). |
| **Admin Produksi** | **Mingguan** (Senin 07:30) | • Total PO Baru Masuk vs Selesai Produksi (Pcs)<br>• Progress Bar Mingguan<br>• Rijek Rate Mingguan (Pcs & %)<br>• Antrean Deadline 7 Hari ke Depan | `ReportRunner::kinerjaProduksi` (mingguan), `rijek` | **Stage Cycle Time Bottleneck**:<br>Deteksi pos macet (misal: jahitan menumpuk, setting lambat) sebelum antrean meledak. |
| **Admin Produksi** | **Bulanan** (Tgl 1 07:30) | • Total Throughput Produksi Sebulan (Pcs)<br>• Evaluasi Scrap Rate per Tim/Lini<br>• Rekap PO Selesai Tepat Waktu vs Terlambat | `ReportRunner::kinerjaProduksi` (bulanan), `rijek` | **Quality Rework Costing**:<br>Estimasi total pemborosan biaya pengerjaan ulang (kain & tinta) akibat rijek. |
| **Admin Brand** | **Harian** (08:00 WIB) | • Aktivitas PO Baru Masuk Hari Ini (Pcs)<br>• Breakdown: PO Normal, Reseller Price, PO Support<br>• Rincian 5 PO Terbaru<br>• Deadline Mendekat & PO Terlambat Brand | `ReportRunner::jenisPo`, `penjualanProduk`, `monitoringDeadline` | **Customer Retention Warning**:<br>Peringatan pelanggan VIP yang belum repeat order mendekati ambang churn. |
| **Admin Brand** | **Mingguan** (Senin 08:00) | • Rekapitulasi Sumber Order (Instagram, WA, Web, Reseller)<br>• Omzet Mingguan Brand<br>• Daftar PO Tunggakan Menunggu Pelunasan | `ReportRunner::jenisPo`, `pelanggan`, `pemasukan` | **Channel ROI & Reseller Performance**:<br>Analisis efektivitas kanal promosi dan performa keaktifan reseller. |
| **Admin Brand** | **Bulanan** (Tgl 1 08:00) | • Total PO & Pcs Bulanan Brand<br>• Produk Terlaris Bulan Ini (Pcs)<br>• Jumlah Pelanggan Baru Bulan Ini<br>• Evaluasi Pencapaian Target Brand Bulanan | `ReportRunner::penjualanProduk`, `pelanggan` | **Demand Forecasting 4 Minggu**:<br>Proyeksi produk jersey yang akan melonjak berdasarkan tren musiman event. |
| **Owner** | **Harian** (06:30 WIB) | • Aktivitas PO Baru Hari Ini (Pcs & Jenis)<br>• Ringkasan Status Fisik: Masuk, Proses, Selesai, Delay<br>• 15 PO Baru Masuk Hari Ini<br>• PO Sedang Berjalan & Selesai Hari Ini<br>• Revenue Hari ini, Minggu ini, Bulan ini<br>• Top Produk & Customer Terbesar | `ReportRunner::penjualanProduk`, `statusPo`, `pelanggan` | **Executive Brief & 3 Prioritas Utama**:<br>Sintesis 3 tindakan paling mendesak yang butuh instruksi langsung dari Owner hari ini. |
| **Owner** | **Mingguan** (Senin 06:30) | • Rekap PO Mingguan (Normal, Reseller, Support)<br>• Evaluasi Sumber Order Terbanyak<br>• Revenue Minggu Ini vs Minggu Lalu (`📈 +x%`)<br>• Daftar 5 Tunggakan PO Terbesar | `ReportRunner::jenisPo`, `penjualanProduk`, `pemasukan` | **Cashflow Runway Alert**:<br>Peringatan titik nadir kas jika ada kewajiban jatuh tempo mendahului pelunasan piutang. |
| **Owner** | **Bulanan** (Tgl 1 06:30) | • Pertumbuhan Omzet MoM vs Bulan Lalu<br>• Rekap 10 Tunggakan Invoice Terbesar<br>• Pareto Pelanggan & Produk Penopang Profit | `ReportRunner::penjualanProduk`, `pemasukan`, `pelanggan` | **Gross Margin & Risk Outlook**:<br>Analisis marjin laba kotor grup dan proyeksi ketahanan modal kerja 90 hari. |
| **Admin Keuangan** | **Harian** (08:30 WIB) | • Pemasukan & Pengeluaran Hari Ini (Kas Riil)<br>• Estimasi Laba Bersih Harian<br>• Refund Baru Menunggu Review (Pending)<br>• Jumlah Invoice Menunggu Validasi (Rp)<br>• Daftar Invoice Jatuh Tempo (< 3 Hari) | `ReportRunner::pemasukan`, `pengeluaran`, `refund`, `Invoice` | **Anti-Double Count Verification**:<br>Verifikasi saldo riil mutasi bank vs pencatatan kas (aturan ketat §5.4). |
| **Admin Keuangan** | **Mingguan** (Senin 08:30) | • Rekap Kas Masuk vs Kas Keluar 7 Hari<br>• Laba/Rugi Operasional Mingguan<br>• Daftar Invoice Jatuh Tempo (7 Hari ke Depan) | `ReportRunner::pemasukan`, `pengeluaran`, `Invoice` | **Receivable Aging & Default Risk**:<br>Skoring risiko piutang macet dan profil usia tagihan pelanggan. |
| **Admin Keuangan** | **Bulanan** (Tgl 1 08:30) | • Total Pemasukan vs Pengeluaran Sebulan<br>• Laba Bersih Operasional Bulanan<br>• Daftar Invoice Jatuh Tempo (14 Hari ke Depan) | `ReportRunner::pemasukan`, `pengeluaran`, `arusKasBank` | **30-Day Cashflow Projection**:<br>Proyeksi arus kas 30 hari ke depan membandingkan piutang cair vs beban rutin. |
| **Purchasing** *(Ekspansi)* | **Harian & Mingguan** | • Saldo Bahan Baku Kritis di Bawah Batas Minimum<br>• **Defisit Bahan Antrean PO Aktif (Anti-Mismatch)**<br>• Daftar Tagihan TOP Vendor Jatuh Tempo Hari ini & H-3 | `PurchasingMaterialStock`, `ai_material_standards`, `PurchasingPayment` | **Dynamic Stockout Countdown & Fraud Sentinel**:<br>Hitung mundur hari kehabisan bahan dan skrining 8 indikasi kecurangan belanja (F1–F8). |
| **HCM (SDM)** *(Ekspansi)* | **Harian & Mingguan** | • Tingkat Kehadiran & Mangkir Divisi Produksi<br>• Akumulasi Jam Lembur Mingguan per Karyawan<br>• Peringatan Kontrak PKWT Habis H-30 & Evaluasi Probation | `HcmAttendance`, `HcmOvertime`, `HcmContract` | **Cumulative Fatigue Index & Turnover Forecast**:<br>Prediksi lonjakan cacat akibat lembur ekstrem dan proyeksi kekosongan staf jahit. |

---

### 2.7. Laporan Operasional Harian Faktual (Standar KPI Purchasing & HCM)

> **Latar Belakang**: Modul Purchasing dan HCM belum memiliki laporan harian resmi di `ReportMessageBuilder.php`. Bagian ini menetapkan **Standar KPI Harian Faktual** murni berbasis pencatatan transaksi riil hari ini (tanpa prediksi AI), yang dapat langsung dikirim via WhatsApp rutin.

#### 1. KPI Harian Modul Purchasing & Gudang Bahan Baku
* **Fokus**: Mengetahui kondisi fisik riil stok gudang dan kewajiban pengadaan yang harus dieksekusi hari ini.
* **Tabel DB Sumber**: `purchasing_material_stocks`, `purchasing_material_usages`, `purchasing_payments`, `purchasing_orders`.
* **Daftar KPI Harian Faktual**:
  1. **Saldo Fisik Gudang**: Total stok on-hand kain putih (roll), kain warna (kg), kertas (roll), dan tinta (botol).
  2. **Mutasi Fisik Hari Ini**: Volume barang masuk (*inbound*) dari supplier dan barang keluar (*outbound*) ke meja potong/cetak.
  3. **Alert Stok Kritis Statis**: Daftar item yang saldo fisiknya $\le$ batas minimum (`current_stock <= minimum_threshold`).
  4. **Kewajiban Tagihan TOP Jatuh Tempo Hari Ini**: Tagihan termin vendor yang wajib dibayar hari ini (`due_date = today()`).
  5. **Pengajuan PO Belanja Menunggu Persetujuan**: Antrean PO pengadaan berstatus `pending_approval` yang butuh tanda tangan.
* **Format Output Pesan Harian (Template WhatsApp)**:
```
📦 *LAPORAN HARIAN GUDANG & PENGADAAN*
📅 Tanggal: 23 Oktober 2026, 08:00 WIB

📊 *STATUS STOK FISIK DI GUDANG:*
• Kain Putih: 42 Roll (2 varian mendekati batas minimum)
• Kain Warna: 185 Kg (Stok proyek aman)
• Kertas Sublime: 14 Roll
• Tinta Printing: 38 Botol (Varian Cyan sisa 3 botol)

🔄 *MUTASI BARANG HARI INI:*
• Barang Masuk (Inbound): 10 Roll Kain Milano Putih (PO-PUR-089)
• Barang Keluar (Pemakaian): 4 Roll Kain & 6 Botol Tinta

⚠️ *STOK MENYENTUH BATAS MINIMUM:*
1. Tinta Sublime Cyan (MAT-TNT-001): Sisa 3 Botol (Batas Min: 5 Botol)
2. Dryfit Bintik Putih (MAT-KP-003): Sisa 3 Roll (Batas Min: 5 Roll)

💰 *TAGIHAN TOP VENDOR JATUH TEMPO HARI INI:*
1. PT Tekstil Mandiri: Rp 14.500.000 (Termin 2 - PO-PUR-075)

_ProTrack · PURCHASING · HARIAN · 23/10/2026 08:00_
```

#### 2. KPI Harian Modul HCM (SDM & Disiplin Pabrik)
* **Fokus**: Mengetahui kesiapan tenaga kerja fisik dan kepatuhan jam kerja shift hari ini.
* **Tabel DB Sumber**: `hcm_attendances`, `hcm_overtimes`, `hcm_leave_requests`, `hcm_employees`.
* **Daftar KPI Harian Faktual**:
  1. **Headcount Presensi Hari Ini**: Total staf hadir fisik, izin, sakit, dan mangkir (alpha).
  2. **Tingkat Ketidakhadiran per Divisi**: Persentase absen di pos kritis (Jahit, Sablon/Printing, QC Finishing).
  3. **Alokasi Jam Lembur Hari Ini**: Total jam lembur shift sore/malam yang telah disetujui SPV.
  4. **Disiplin Jam Kerja**: Rekap staf terlambat masuk dan pulang cepat.
  5. **Karyawan Cuti Aktif**: Daftar staf yang sedang mengambil cuti resmi hari ini.
* **Format Output Pesan Harian (Template WhatsApp)**:
```
👥 *LAPORAN HARIAN PRESENSI & SDM PABRIK*
📅 Tanggal: 23 Oktober 2026, 08:00 WIB

🏢 *HEADCOUNT PRESENSI SHIFT HARI INI:*
• Total Karyawan Aktif: 45 Orang
• Hadir Bekerja: 42 Orang (Tingkat Kehadiran: 93,3%)
• Izin: 2 Orang (Divisi Finishing)
• Sakit: 1 Orang (Divisi Jahit)
• Alpha / Mangkir: 0 Orang

⏰ *KEDISIPLINAN & LEMBUR:*
• Terlambat Hadir: 3 Orang (Rata-rata 15 menit)
• Pengajuan Lembur Disetujui Hari Ini: 14 Jam (5 Penjahit untuk PO Rush Order)

📋 *STAF CUTI HARI INI:*
• Ahmad Fauzi (Jahit) - Cuti Tahunan (s/d 24 Okt)

_ProTrack · HCM · HARIAN · 23/10/2026 08:00_
```

---

### 2.8. Modul Prediktif & Sensor Cerdas AI (Khusus Purchasing & HCM)

> **Latar Belakang**: Berbeda dengan laporan harian yang hanya mencatat apa yang terjadi hari ini, **Modul Prediktif AI** bekerja menggunakan formula proyeksi matematika, time-series, dan deteksi anomali untuk memprediksi **apa yang akan terjadi dalam 3 s.d. 30 hari ke depan**.
> Modul ini memicu peringatan darurat seketika (*instant alert*) saat risiko terdeteksi, atau disisipkan sebagai rekomendasi taktis pada laporan rutin.

#### 1. Fitur Prediktif AI untuk Purchasing & Bahan Baku
* **Model yang Diterapkan**:
  1. **Hitung Mundur Habis Bahan (*Dynamic Stockout Countdown*)**:
     - *Formula*: $\text{Sisa Hari} = \text{Stok Tersedia} / \text{Laju Konsumsi Harian 30 Hari}$.
     - *Output*: Notifikasi sisa hari menuju kehabisan bahan sebelum lini potong berhenti.
  2. **Defisit Alokasi Bahan Antrean PO (*Demand-Allocated Net Deficit*)**:
     - *Formula*: $\text{Free Stock} = \text{Stok Fisik} - \sum (\text{Qty PO Aktif} \times \text{Standar BOM})$.
     - *Fungsi*: **Mencegah salah rekomendasi beli**. Memastikan reminder membeli Bahan A yang dipakai PO 100, bukan membeli Bahan B yang sedang tidak memiliki antrean PO.
  3. **Prediksi Pembengkakan Harga Vendor (*Price Trend Estimator*)**:
     - Membandingkan tren kenaikan harga beli satuan vendor dalam 90 hari terakhir untuk mengantisipasi pembengkakan modal kerja pengadaan.
  4. **Detektor Fraud & Pelanggaran Pengadaan (F1–F8)**:
     - Skrining otomatis transaksi belanja dipecah-pecah (*split PO*) untuk menghindari batas approval, nota belanja fiktif, dan monopoli vendor tunggal.
* **Format Output Peringatan Prediktif AI (Alert Instan WhatsApp)**:
```
🚨 *PREDIKSI AI: ANCAMAN DEFISIT BAHAN BAKU (PO ALLOCATION)*
Tingkat Urgensi: *CRITICAL*
Horizon Waktu: *2 Hari ke Depan (Jumat Siang)*

Hasil audit matematis ketersediaan bahan terhadap antrean PO aktif:

📦 *STATUS ALOKASI BAHAN:*
• Bahan: Dryfit Milano Putih (MAT-KP-001)
• Stok Fisik Gudang Saat Ini: 6,0 Roll
• Kebutuhan Antrean PO Berjalan: 7,5 Roll (PO-100 & PO-102)
• Sisa Stok Bebas (Free Stock): -1,5 Roll (DEFISIT!)

⚠️ *PROYEKSI DAMPAK:*
Stok fisik akan habis total saat pemotongan kain PO-102 besok siang pukul 13:00 WIB. Lini potong terancam berhenti selama 2 hari jika kain tidak tiba besok pagi.

💡 *PENCEGAHAN SALAH BELI:*
Stok Serena Putih (MAT-KP-002) fisik sisa 2 Roll, tetapi *0 PO aktif* yang membutuhkan Serena. Prioritaskan 100% anggaran pengadaan untuk *Milano Putih Polos*.

📌 *REKOMENDASI TINDAKAN:*
[ ] Terbitkan PO pengadaan darurat 6,5 Roll Dryfit Milano hari ini.
[ ] Hubungi PT Tekstil Mandiri untuk pengiriman ekspres besok pagi.
```

#### 2. Fitur Prediktif AI untuk HCM & Kapasitas Tenaga Kerja
* **Model yang Diterapkan**:
  1. **Indeks Kelelahan Lembur (*Cumulative Fatigue Index - CFI*)**:
     - Menganalisis akumulasi lembur 14 hari berturut-turut pada operator.
     - *Prediksi*: Menghitung probabilitas lonjakan produk cacat (rijek jahitan) dan absensi sakit minggu depan akibat kelelahan fisik.
  2. **Proyeksi Keseimbangan Kapasitas vs Antrean PO (*Workforce Capacity Gap*)**:
     - *Formula*: $\text{Kapasitas Jam Efektif 14 Hari} - \sum (\text{Menit Standar Antrean PO} / 60)$.
     - *Prediksi*: Mengetahui lebih awal apakah divisi jahit akan mengalami bottleneck 2 minggu ke depan.
  3. **Proyeksi Gelombang Habis Kontrak PKWT (Jendela H-30)**:
     - Melacak tanggal berakhirnya kontrak kerja karyawan untuk memproyeksikan risiko kekosongan posisi krusial pabrik sebelum terjadi kekosongan orang.
  4. **Detektor Lembur Fiktif (*Ghost Overtime F8*)**:
     - Memverifikasi silang klaim lembur terhadap mesin absensi untuk mendeteksi klaim lembur pada hari di mana staf tercatat tidak hadir.
* **Format Output Peringatan Prediktif AI (Alert Instan WhatsApp)**:
```
⚠️ *PREDIKSI AI: RISIKO CACAT AKIBAT KELELAHAN LEMBUR (CFI)*
Tingkat Urgensi: *HIGH*
Divisi Terdampak: *Lini Jahit (12 Operator)*

Hasil analisis korelasi lembur kumulatif terhadap mutu pengerjaan:

📊 *INDIKATOR KELELAHAN (FATIGUE RISK):*
• Rata-rata Lembur: 18,5 Jam/Orang/Minggu (2 minggu berturut-turut)
• Indeks Kelelahan: 84 / 100 (Kategori: Kelelahan Ekstrem)

📈 *PROYEKSI DAMPAK 7 HARI KE DEPAN:*
1. Diproyeksikan tingkat rijek jahitan naik +14% (potensi kerugian rework Rp 3.200.000).
2. Estimasi 2 s.d. 3 operator jahit akan izin sakit pada hari Senin/Selasa depan.

📌 *REKOMENDASI TINDAKAN:*
[ ] Moratorium lembur lini jahit maksimal 1,5 jam/hari untuk sisa minggu ini.
[ ] Alihkan 3 personel finishing yang memiliki keahlian jahit dasar untuk membantu obras.
[ ] Jadwalkan istirahat tambahan 15 menit pada shift lembur malam.
```

---

## 3. Matriks Deteksi Anomali Lengkap (Deterministik)

Seluruh deteksi anomali dijalankan secara deterministik di level kode PHP/SQL sebelum memanggil LLM. LLM **tidak pernah diminta menebak anomali**.

```
┌────────────────────────────────────────────────────────────────────────┐
│                      MATRIKS DETEKSI ANOMALI DATA                      │
├─────────────────────────┬─────────────────────────┬────────────────────┤
│ Order (OA1 - OA6)       │ Finance (FA1 - FA6)     │ Cross-Module (CMA) │
│ - Lonjakan Volume       │ - Jebakan Double Count  │ - Lembur vs Rijek  │
│ - Deadline Mepet        │ - Mutasi Bank Menggantung│ - Beban vs Orang  │
│ - Stagnasi Progress     │ - Refund Spike          │ - Siap Kirim vs AR │
│ - Nameset Mismatch      │ - Piutang Macet         │ - Stok Kritis vs PO│
├─────────────────────────┼─────────────────────────┼────────────────────┤
│ HCM (HA1 - HA8)         │ Purchasing (PA1 - PA8)  │ Fraud & BOM        │
│ - Overtime Fatigue      │ - Lonjakan Harga Satuan │ - 8 Pola Fraud     │
│ - Alpha Tak Wajar       │ - Stok Kritis           │   Pengadaan (F1-F8)│
│ - Habis Kontrak H-30    │ - TOP Jatuh Tempo       │ - 4 Pola Pemborosan│
│ - Lembur vs Absen       │ - Biaya Servis Bengkak  │   BOM Jersey (M1-M4│
└─────────────────────────┴─────────────────────────┴────────────────────┘
```

### 3.1. Anomali Order & Produksi (OA1 – OA6)

| Kode | Nama Anomali | Formula / Logika Query SQL | Threshold Pemicu | Severity |
| :--- | :--- | :--- | :--- | :--- |
| **OA1** | Lonjakan Order Tak Wajar | Qty PO baru vs rata-rata volume historis pelanggan yang sama dalam 12 bulan terakhir. | `qty_po > 3x avg_historical` (min 100 pcs) | Medium |
| **OA2** | Penumpukan Rush Order | Jumlah PO dengan selisih `deadline_customer - tanggal_masuk` di bawah batas standar paket. | `sisa_hari <= 4 hari` melebihi 25% antrean aktif | High |
| **OA3A** | Zero Progress Harian (PO Diam 1 Hari) | PO aktif (`published` atau `on_progress`) pada hari kerja tanpa perpindahan status atau update `OrderProgressDetail` selama $\ge 24\text{ jam}$. | $\ge 24\text{ jam kerja tanpa update}$ (Severity *Critical* jika sisa hari $\le 3\text{ hari}$) | Medium $\rightarrow$ Critical |
| **OA3B** | Stagnasi Kronis Tahapan | `now() - updated_at` pada `OrderProgressDetail` berstatus `on_progress` pada satu tahapan tertentu. | $> 3\text{ hari}$ macet di satu pos pengerjaan | High |
| **OA4** | Ketidakcocokan Nameset vs Item | Jumlah baris di `OrderNameset` dibandingkan dengan `quantity` pada `OrderItem` kategori jersey. | `count(nameset) != sum(quantity)` pada item nameset | Critical |
| **OA5** | Uang Muka Desain Menggantung | `DesignDeposit` berstatus aktif yang belum terikat ke PO terbit (`order_id is null`). | `umur_deposit > 30 hari` | Medium |
| **OA6** | Frekuensi Pembukaan Kunci PO | Jumlah entri di `POLockStatus` / `POChangeLog` untuk satu nomor PO. | `unlock_count >= 3 kali` pasca-approval | High |

### 3.2. Anomali Keuangan & Arus Kas (FA1 – FA6)

| Kode | Nama Anomali | Formula / Logika Query SQL | Threshold Pemicu | Severity |
| :--- | :--- | :--- | :--- | :--- |
| **FA1** | Jebakan Pendapatan Ganda | Pengecekan agar query laporan tidak menjumlahkan Pemasukan 'PO Published' dengan 'Pembayaran PO'. | Terdeteksi di kode reporting / snapshot | Critical |
| **FA2** | Mutasi Rekening Menggantung | Mutasi kredit bank pada `arus-kas-bank` yang belum diverifikasi menjadi `OrderPayment`. | `selisih_waktu > 48 jam` sejak mutasi masuk | High |
| **FA3** | Lonjakan Nominal Refund | Rasio `sum(nominal_refund) / sum(omzet_verified)` dalam jendela 7 hari berjalan. | Rasio refund `> 5%` dari omzet mingguan | High |
| **FA4** | Pelunasan Tanpa Verifikasi Bank | PO ditandai `is_lunas = true` atau `bypass_dp` tanpa referensi ID transaksi bank. | Pembayaran tanpa bukti transfer valid | High |
| **FA5** | Penumpukan Piutang Jatuh Tempo | Total piutang invoice terbit berstatus `UNPAID` atau `PARTIAL` melewati tanggal jatuh tempo. | Usia invoice `> 30 hari` atau total `> Rp 50.000.000` | High |
| **FA6** | Selisih Arus Kas vs Saldo Bank | Deviasi antara pencatatan pembukuan kas masuk/keluar dengan saldo riil rekening koran aktif. | Selisih rekonsiliasi `> Rp 1.000.000` | Critical |

### 3.3. Anomali Kepegawaian & Presensi (HA1 – HA8)

| Kode | Nama Anomali | Formula / Logika Query SQL | Threshold Pemicu | Severity |
| :--- | :--- | :--- | :--- | :--- |
| **HA1** | Beban Lembur Ekstrem | Akumulasi `duration_hours` pada `HcmOvertime` per individu dalam 1 periode minggu berjalan. | `> 20 jam / minggu` per karyawan | High |
| **HA2** | Mangkir Massal Produksi | Persentase karyawan bagian produksi dengan kategori absensi `Alpha/Mangkir` pada hari kerja aktif. | `> 10%` dari total staf produksi aktif | Critical |
| **HA3** | Lembur Tanpa Presensi | Entri pengajuan lembur yang disetujui pada tanggal di mana `HcmAttendance` karyawan tidak hadir. | `jam_lembur > 0` saat status hadir `null` atau `Alpha` | Critical |
| **HA4** | Kontrak PKWT Kritis | Kontrak aktif pada `HcmContract` dengan sisa durasi menuju `end_date`. | `sisa_hari <= 30 hari` (H-30 habis kontrak) | Medium |
| **HA5** | Evaluasi Probation Terlewat | Karyawan status `Probation` mendekati genap 3 bulan dari `join_date` tanpa review tercatat. | `sisa_hari <= 7 hari` menuju 90 hari kerja | Medium |
| **HA6** | Pengajuan Cuti di Puncak Beban | Pengajuan cuti bersamaan oleh lebih dari 20% personel di satu divisi krusial (misal: penjahit). | `cuti_diajukan > 20% headcount divisi` | High |
| **HA7** | Koreksi Manual Absensi Abnormal | Perubahan jam masuk/keluar absensi manual oleh admin melebihi ambang batas toleransi. | `> 5 kali revisi manual per karyawan per bulan` | Medium |
| **HA8** | Jam Lembur Tanpa Output PO | Lonjakan lembur divisi sablon/jahit pada minggu di mana throughput pengerjaan PO justru turun. | `lembur naik >30%` tapi `pcs selesai turun >15%` | High |

### 3.4. Anomali Pengadaan, Aset, & Fraud (PA1 – PA8, F1 – F8, M1 – M4)

| Kode | Nama Anomali | Formula / Logika Query SQL | Threshold Pemicu | Severity |
| :--- | :--- | :--- | :--- | :--- |
| **PA1** | Kenaikan Harga Beli Tajam | `unit_price` PO baru dibandingkan rata-rata tertimbang harga 90 hari terakhir untuk item yang sama. | `unit_price > 15% di atas rata-rata` | High |
| **PA2** | Pemecahan PO Hindari Approval (F2) | N pengajuan PO dari pemohon sama untuk item sejenis dalam rentang 7 hari dengan nilai di bawah batas persetujuan. | `>= 3 PO di bawah limit`, total `> limit approval` | Critical |
| **PA3** | Monopoli Vendor Tanpa Banding (F3) | Pangsa belanja pada 1 vendor dalam kategori pengadaan yang memiliki vendor alternatif aktif. | `share vendor > 70%` dalam 90 hari | Medium |
| **PA4** | Selisih Kas Bon Pengadaan (F4) | Selisih antara dana dikeluarkan (`disbursed_amount`) vs realisasi belanja tanpa nota pengembalian. | `selisih > 5%` atau `> Rp 500.000` tanpa penjelasan | High |
| **PA5** | Pembayaran Mendahului Barang (F5) | Pembayaran TOP berstatus `PAID` sebelum status PO mencapai `PURCHASE_COMPLETED` atau penerimaan gudang. | Pembayaran diverifikasi sebelum fisik barang di-QC | Critical |
| **PA6** | Barang Datang Tanpa Jejak (F6) | PO pengadaan bahan baku selesai dibayar tetapi tidak ada catatan penambahan stok kartu gudang dalam 7 hari. | `> 7 hari` tanpa mutasi stok masuk | High |
| **PA7** | Pembengkakan Biaya Servis Aset | Total biaya `PurchasingAssetMaintenance` kumulatif dibandingkan nilai perolehan awal aset. | `biaya_servis_kumulatif > 50% harga beli aset` | High |
| **PA8** | Penghapusan Aset Dini | Pengajuan `PurchasingAssetRetirement` pada aset yang belum mencapai 50% estimasi masa pakai ekonomis. | Masa pakai `< 50% umur ekonomis` | Medium |
| **M1** | Pemborosan Bahan Baku (BOM) | `(pemakaian riil - [pcs produksi x std BOM]) / [pcs produksi x std BOM]` per jenis material jersey. | Pemakaian melebihi toleransi standar (`> 8%`) | High |
| **M2** | Deviasi Susut Antarlini | Variansi pemakaian bahan dipecah per operator atau per mesin potong/jahit. | 1 operator/lini `> 2x rata-rata deviasi lini lain` | High |
| **M3** | Kain Warna Tanpa Distribusi | Pembelian kain khusus berstatus diterima di gudang namun tidak terdistribusi ke potong dalam 14 hari. | Pembelian `> 0` tapi distribusi pengerjaan `0` | Medium |
| **M4** | Stok Minus / Koreksi Liar | Saldo akhir kartu stok bernilai negatif atau terdapat koreksi stok manual tanpa berita acara resmi. | `current_stock < 0` atau koreksi `> 10% volume` | Critical |
| **MA1** | Master Bahan Order Tidak Terpetakan | Item PO aktif (`OrderItem`) menggunakan `bahan_kain_id` yang belum memiliki relasi di `ai_material_standards`. | `bahan_kain_id is null` pada tabel pemetaan | Critical |
| **MA2** | Defisit Alokasi Stok PO Aktif | Sisa stok bebas $\text{Free Stock} < 0$ setelah dikurangi kebutuhan antrean PO berstatus `published` & `on_progress`. | $\text{Current Stock} - \text{Allocated PO} < 0$ | Critical |
| **MA3** | Rekomendasi Pengadaan Salah Sasaran | Sistem memicu peringatan beli Bahan B padahal antrean PO aktif membutuhkan Bahan A. | Terdeteksi di pre-dispatch notification guard | Critical |

### 3.4B. Sinkronisasi Time-Lag: Pencocokan Konsumsi Bahan vs Output Fisik Produksi

> **Problem Lapangan**: Antara waktu pengeluaran bahan baku di gudang dengan waktu selesainya order/PCS **pasti terjadi jeda waktu (time-lag mismatch)**.
> - Tinta keluar di awal pengerjaan pada **Tahap 2 (`PRINTING`)**, kain keluar di **Tahap 3 (`POTONG BAHAN`)**.
> - Namun, PO baru berstatus `selesai_produksi` atau `PACKING` setelah melewati 11 tahapan pengerjaan (membutuhkan 5–10 hari).
> - Jika sistem membandingkan "tinta keluar minggu ini" dengan "PO yang selesai packing minggu ini", angka konsumsi pasti tidak akan pernah cocok (*mismatch*).
> - Ditambah lagi, operator mesin cetak sering melakukan **batching** (mencetak beberapa PO sekaligus dalam 1 gulungan roll kertas sublim) atau mengambil tinta per botol harian untuk mesin.

Untuk mengatasi time-lag dan batching ini, Agent menggunakan **Dua Mekanisme Pencocokan Sinkron**:

#### 1. Mekanisme Direct Stage-Bound Binding (Jika `production_ref` Terisi Nomor PO)
* **Titik Ukur**: Mengunci perhitungan ke tabel `order_progress_details` saat tahap spesifik mencapai `completed_at`, **BUKAN menunggu status akhir PO**.
  - **Tinta Sublim / Kertas Sublim**: Diikatkan ke tahap `progress.nama_progress = 'PRINTING'` (Urutan 2).
  - **Kain Jersey**: Diikatkan ke tahap `progress.nama_progress = 'POTONG BAHAN'` (Urutan 3).
* **Alur Eksekusi**:
  1. Operator mengambil 15 botol tinta dengan catatan `production_ref = 'PO-001'` (100 pcs jersey).
  2. Saat operator cetak menekan tombol "Selesai" pada progress `PRINTING` untuk PO-001 di sistem, Agent langsung mengunci akumulasi tinta yang keluar untuk PO tersebut.
  3. Perhitungan anomali langsung berjalan di hari tahap `PRINTING` selesai, tanpa perlu menunggu baju selesai dijahit atau dipacking.

#### 2. Mekanisme Jendela Waktu Bergulir / WIP Equivalency (Jika Tinta Diambil Harian / Per Mesin)
Jika operator mengambil tinta secara gelondongan per shift/mesin tanpa mencatat nomor PO spesifik:
* Agent menggunakan metode **Work-in-Process (WIP) Equivalency** berbasis jendela waktu mingguan (*sliding window* 7 hari):
  $$\text{Throughput Pcs Dicetak (W42)} = \sum_{\text{PO}} \text{Qty Item dari seluruh PO yang tahap PRINTING-nya } \text{status} = \text{'selesai' di W42}$$
  $$\text{Realisasi Tinta Keluar (W42)} = \sum \text{quantity\_out (TINTA) di tabel purchasing\_material\_usages pada W42}$$
* **Formula Variansi Sinkron**:
  $$\text{Ekspektasi Tinta} = \text{Throughput Pcs Dicetak} \times \text{Standar BOM per Pcs}$$
  $$\text{Variansi Tinta} = \text{Realisasi Tinta Keluar} - \text{Ekspektasi Tinta}$$
* **Penyesuaian Faktor Rijek Cetak (Reprint Adjustment)**:
  $$\text{Toleransi Rework} = \sum (\text{Rijek Pcs pada Tahap Printing}) \times \text{Standar BOM}$$
  $$\text{Anomali Murni (Unexplained)} = \text{Variansi Tinta} - \text{Toleransi Rework}$$
* Jika $\text{Anomali Murni} > 8\%$, sistem menerbitkan peringatan investigasi ke tim QC dan Kepala Produksi.

---

### 3.4C. Integritas Pemetaan Bahan Baku: Anti-Mismatch Rekomendasi Beli (Solusi Kasus PO 100 Bahan A vs Pengingat Beli Bahan B)

> **Kasus Nyata (Problem Statement)**:
> Pada pesanan **PO 100**, customer memesan 100 potong jersey menggunakan **Bahan A (misal: Dryfit Milano Putih)**.
> Namun di modul pengingat/reminder, sistem malah memunculkan peringatan: *"Segera beli Bahan B (misal: Serena Putih atau Kain TC)"*, sementara Bahan A yang nyata-nyata dibutuhkan untuk memproses PO 100 tidak diingatkan!

#### 1. Bedah Akar Masalah Struktural (Audit Forensik)

1. **Terputusnya Relasi Database (*Data Silo Disconnect*)**:
   - Di modul Order: Bahan disimpan sebagai `OrderItem.bahan_kain_id` yang merujuk ke tabel master `bahan_kains.id` (nama: `"Milano"`, `"Dryfit Bintik"`).
   - Di modul Purchasing: Bahan disimpan di tabel `purchasing_material_stocks.id` (nama: `"Milano Putih Polos"`, `"Kain Putih TC"`).
   - **Fakta Audit**: Tidak ada foreign key atau kolom relasional yang menghubungkan kedua tabel ini. Jika sistem atau LLM mengandalkan *fuzzy search* atau kesamaan teks nama bahan, terjadi salah identifikasi fatal antarvarian bahan sejenis.
2. **Logika Reminder Global Buta Antrean PO (*Blind Global Stockout vs Demand-Allocated Reorder*)**:
   - Logika dashboard lama di `PurchasingDashboardService::criticalStocks` hanya mengeksekusi:
     `whereRaw('current_stock <= minimum_threshold')`
   - Logika ini hanya melihat saldo fisik statis gudang, **sama sekali tidak membaca antrean PO yang sedang masuk/antre potong**.
   - Akibatnya: Jika Bahan B saldo fisiknya 2 roll (kebetulan di bawah minimum 4 roll), reminder otomatis berbunyi untuk Bahan B. Sementara Bahan A saldo fisiknya 6 roll (di atas minimum 5 roll) padahal PO 100 membutuhkan 7 roll! Sistem menganggap Bahan A aman, padahal realitanya **defisit 1 roll untuk PO 100**.
3. **Kompleksitas Multi-Bahan dalam Satu Item PO**:
   - Satu baris `OrderItem` memiliki relasi bahan majemuk: `bahan_kain_id` (badan utama), `bahan_kain_ids` (variasi/kombinasi), `bahan_kain_bawahan_id` (celana), dan `bahan_kain_bawahan_ids`.
   - Tanpa pemilahan komponen (*part segmentation*), sistem rentan mengingatkan bahan bawahan celana (Bahan B) padahal yang habis adalah bahan badan atasan (Bahan A).

#### 2. Arsitektur Solusi Deterministik Anti-Salah Bahan

```
┌────────────────────────────────────────────────────────────────────────┐
│                   ALUR INTEGRITAS BAHAN BAKU PO                        │
├────────────────────────────────────────────────────────────────────────┤
│ 1. ORDER MASUK: PO-100 (100 pcs)                                       │
│    - Atasan Utama: bahan_kain_id = 3 (Dryfit Milano)                   │
│    - Bawahan: bahan_kain_bawahan_id = 8 (Lotto Super)                  │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│ 2. DETERMINISTIC MAPPING TABLE (ai_material_standards)                 │
│    - bahan_kain_id: 3  ───► material_stock_id: 1 (MAT-KP-001 Milano)   │
│    - bahan_kain_id: 8  ───► material_stock_id: 12 (MAT-KW-004 Lotto)   │
│    * DILARANG STRING MATCHING / DILARANG TEBAKAN LLM *                 │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│ 3. DEMAND-ALLOCATED STOCK BALANCE ENGINE (PHP / SQL)                   │
│    Bahan A (Milano):                                                   │
│    - Stok Fisik Gudang (On-Hand)     : 6.0 Roll                        │
│    - Alokasi PO Aktif (PO-100, PO-102): 7.5 Roll                        │
│    - Stok Bebas Efektif (Free Stock) : 6.0 - 7.5 = -1.5 Roll (DEFISIT!)│
│    ==> STATUS: KRITIS! TRIGGER REMINDER PENGADAAN BAHAN A              │
│                                                                        │
│    Bahan B (Serena - 0 PO Aktif):                                      │
│    - Stok Fisik: 2.0 Roll (Min: 4.0 Roll), Tapi Alokasi PO: 0 Roll     │
│    ==> STATUS: ROUTINE REORDER (TIDAK DIBERI LABEL URGENT PO)          │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│ 4. 3-LAYER PRE-DISPATCH GUARD (Anti-Mismatch Blocker)                  │
│    Layer 1: Verifikasi PO Sumber (Pastikan PO-100 berstatus aktif)    │
│    Layer 2: Validasi Relasi Bahan Faktual (ID 3 == MAT-KP-001)         │
│    Layer 3: Cross-Check Nama Bahan (Cegah nama bahan asing muncul)    │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│ 5. PESAN WHATSAPP PURCHASING DENGAN ATRIBUSI RESMI PO                  │
│    "Peringatan Pembelian: KAIN DRYFIT MILANO (PO-100 & PO-102)"        │
└────────────────────────────────────────────────────────────────────────┘
```

#### 3. Spesifikasi Skema Database Pemetaan Relasional (`ai_material_standards`)

Untuk menghilangkan putusnya data antar-modul, tabel `ai_material_standards` dirancang mengikat kedua domain secara permanen:

```sql
CREATE TABLE ai_material_standards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bahan_kain_id BIGINT UNSIGNED NOT NULL COMMENT 'FK ke master bahan order (bahan_kains.id)',
    material_stock_id BIGINT UNSIGNED NOT NULL COMMENT 'FK ke master stok purchasing (purchasing_material_stocks.id)',
    part_type ENUM('ATASAN_UTAMA', 'KOMBINASI', 'BAWAHAN', 'KERAH_RIB') NOT NULL DEFAULT 'ATASAN_UTAMA',
    standard_consumption_per_unit DECIMAL(8,4) NOT NULL COMMENT 'Kebutuhan standar bahan per pcs pakaian (misal: 0.35 kg atau 0.28 roll/100pcs)',
    unit VARCHAR(20) NOT NULL DEFAULT 'Roll',
    waste_tolerance_pct DECIMAL(5,2) NOT NULL DEFAULT 5.00 COMMENT 'Toleransi susut/potong (%)',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uk_bahan_stock_part (bahan_kain_id, material_stock_id, part_type),
    CONSTRAINT fk_ams_bahan_kain FOREIGN KEY (bahan_kain_id) REFERENCES bahan_kains(id) ON DELETE CASCADE,
    CONSTRAINT fk_ams_material_stock FOREIGN KEY (material_stock_id) REFERENCES purchasing_material_stocks(id) ON DELETE CASCADE
);
```

#### 4. Formula Perhitungan Stok Bebas & Kebutuhan Pengadaan (*Demand-Allocated Net Deficit*)

Agent menjalankan perhitungan stok efektif setiap kali ada PO baru diterbitkan (`published`) atau berstatus `on_progress`:

1. **Akumulasi Kebutuhan Bahan dari Seluruh PO Aktif**:
   $$\text{Allocated Demand}(M) = \sum_{\text{PO} \in \{\text{published, on\_progress}\}} \sum_{\text{Item} \in \text{PO}} \left( \text{quantity} \times \text{standard\_consumption} \times \left(1 + \frac{\text{waste\_tolerance}}{100}\right) \right)$$
2. **Kalkulasi Stok Bebas Efektif (*Free Available Stock*)**:
   $$\text{Free Stock}(M) = \text{current\_stock}(M) - \text{Allocated Demand}(M)$$
3. **Kondisi Pemicu Pengingat Pengadaan Prioritas PO**:
   - Jika $\text{Free Stock}(M) < 0$: **STATUS DEFISIT KRITIS**. Pengadaan mendesak, lini potong terancam mogok.
   - Jika $0 \le \text{Free Stock}(M) \le \text{minimum\_threshold}(M)$: **STATUS WASPADA REORDER**. Stok hanya cukup untuk PO berjalan, tidak bisa menerima PO baru.
4. **Penetapan Jumlah Saran Pembelian (*Recommended Order Quantity*)**:
   $$\text{Saran Beli}(M) = |\text{Free Stock}(M)| + \text{minimum\_threshold}(M)$$

#### 5. Protokol Validasi 3 Lapis Sebelum Kirim Pesan (Pre-Dispatch Guardrail)

Sebelum fungsi pengirim notifikasi WhatsApp (`NotificationDispatcher`) memicu pesan pembelian bahan:
* **Validasi Lapis 1 (PO Integrity Check)**:
  Ambil nomor PO dari antrean defisit. Pastikan status PO masih aktif (`published` atau `on_progress`). Jika PO sudah dibatalkan atau sudah melewati tahap potong bahan (`order_progress_details.nama_progress = 'POTONG BAHAN'` selesai), PO dikeluarkan dari daftar pemicu defisit.
* **Layer 2 (Exact Relational Cross-Check)**:
  Sistem mengecek langsung ke database:
  `OrderItem::where('order_id', $po->id)->pluck('bahan_kain_id')`
  Apakah ID bahan kain pada PO tersebut benar-benar berelasi dengan `material_stock_id` yang tertera pada pesan draft?
  - Jika **COCOK**: Lanjutkan.
  - Jika **TIDAK COCOK**: Batalkan pengiriman pesan seketika, catat anomali `MA3` ke log sistem, dan terbitkan alert error ke admin: *"Pencegahan Mismatch: Saran pembelian bahan [B] diblokir karena PO-100 menggunakan bahan [A]"*.
* **Layer 3 (Narrative Consistency Guard)**:
  Format pesan WA wajib menyandingkan nama bahan di order dengan nama bahan di kartu stok secara berdampingan:
  `Bahan PO: Dryfit Milano (Order ID: 3) ──► Item Gudang: Milano Putih Polos (MAT-KP-001)`.
  Dengan penyandingan transparan ini, staf gudang dan bagian purchasing dapat memverifikasi secara visual dalam 2 detik.

---

### 3.5. Anomali Sinergi Lintas Modul (CMA1 – CMA5)

| Kode | Nama Sinergi Anomali | Korelasi Lintas Domain | Indikasi Masalah Bisnis | Severity |
| :--- | :--- | :--- | :--- | :--- |
| **CMA1** | Lembur Naik + Cacat Naik | HCM (`HcmOvertime`) x Order (`Rijek`) | Kelelahan operator jahitan/finishing menurunkan kualitas, memicu pemborosan kain dan risiko pengerjaan ulang. | High |
| **CMA2** | Beban Meluap + Tenaga Kurang | Order (`monitoring-deadline`) x HCM (`HcmEmployee` - `HcmLeaveRequest`) | Kapasitas jam kerja efektif yang tersedia tidak sanggup menyelesaikan antrean PO; potensi telat massal. | Critical |
| **CMA3** | Bahan Habis + PO Berjalan | Purchasing (`PurchasingMaterialStock`) x Order (`OrderItem`) | Bahan baku utama (misal: Milano/Dryfit) habis di gudang saat PO berstatus `on_progress`; lini produksi terhenti. | Critical |
| **CMA4** | Barang Jadi + Tagihan Macet | Order (`status: siap_dikirim`) x Keuangan (`Invoice UNPAID`) | Barang tertahan di gudang ekspedisi karena customer belum melunasi, mengunci modal kerja dan ruang penyimpanan. | High |
| **CMA5** | Utang Jatuh Tempo + Piutang Seret | Purchasing (`PurchasingPayment TOP`) x Keuangan (`Pemasukan`) | Tagihan vendor jatuh tempo minggu ini melebihi ekspektasi penerimaan kas masuk; krisis likuiditas mendesak. | Critical |

---

## 4. Mesin Prediktif & Proyeksi Deterministik (Statistik & Time-Series)

Agent dibekali modul proyeksi matematis deterministik di PHP. Hasil proyeksi selalu memuat: **Nilai Proyeksi**, **Rentang Keyakinan (±%)**, **Ukuran Sampel Data Historis**, dan **Tingkat Kepastian**.

```
┌────────────────────────────────────────────────────────────────────────┐
│                   ARSITEKTUR MESIN PREDIKTIF DETERMINISTIK             │
├─────────────────────────┬─────────────────────────┬────────────────────┤
│ Demand & Penjualan      │ Lead-Time & Slippage    │ Arus Kas 30 Hari   │
│ - Moving Average 4/12   │ - Durasi Tiap Tahapan   │ - Piutang x Aging  │
│ - Indeks Musiman Jersey │ - Skor Risiko Telat PO  │ - Beban Utang TOP  │
├─────────────────────────┼─────────────────────────┼────────────────────┤
│ Kebutuhan Bahan (BOM)   │ Ketahanan Stok          │ Beban vs Kapasitas │
│ - Proyeksi Pcs x BOM    │ - Sisa Hari Habis (ROP) │ - Standar Menit    │
│ - Safety Stock 10-15%   │ - Konsumsi Dinamis      │ - Headcount Efektif│
└─────────────────────────┴─────────────────────────┴────────────────────┘
```

### 4.1. Model Prediksi Permintaan & Musiman Jersey (`OrderForecast`)
* **Tabel Sumber**: `order_items` join `orders` (`ReportRunner::penjualanProduk`).
* **Metode**: Double Exponential Smoothing / 4-Week Moving Average dipadukan dengan Indeks Musiman Bulanan ($S_m$).
* **Formula Indeks Musiman**:
  $$S_m = \frac{\bar{D}_m}{\bar{D}_{\text{global}}}$$
* **Output**: *"Prediksi pesanan minggu W44: 1.450 pcs jersey (±8%), dipengaruhi tren musiman turnamen akhir tahun (+22% di atas normal)."*

### 4.2. Model Skor Risiko Keterlambatan PO & Bottleneck (`LeadTimeEstimator`)
* **Tabel Sumber**: `orders.deadline_customer`, `deadline_produksi`, `order_progress_details` (`ReportRunner::monitoringDeadline`).
* **Metode**: Skoring Risiko Tertimbang Multivariat (Skor 0–100).
* **Komponen Bobot**:
  1. Rasio sisa hari vs median durasi historis tahapan berjalan: **Bobot 40%**.
  2. Rasio beban kapasitas lini produksi saat ini vs throughput normal: **Bobot 25%**.
  3. Ketersediaan stok bahan baku di gudang: **Bobot 20%**.
  4. Histori rijek dan tingkat rework pada jenis bahan tersebut: **Bobot 15%**.
* **Klasifikasi**: Skor $\ge 70$ (Kritis), $40-69$ (Waspada), $< 40$ (Aman).
* **Output**: *"PO-2026-1089 memiliki skor risiko keterlambatan 82/100. Estimasi terlambat 3 hari kerja akibat antrean jahit melebihi kapasitas dan bahan rib leher belum tiba."*

### 4.3. Model Churn Pelanggan & Estimasi Repeat Order (`CustomerRetentionPredictor`)
* **Tabel Sumber**: `customers` join `orders` (`ReportRunner::pelanggan`).
* **Metode**: Recency-Frequency-Monetary (RFM) Decay Function.
* **Formula Probabilitas Churn**:
  $$P(\text{Churn}) = 1 - e^{-\lambda \cdot t}$$
  di mana $\lambda$ adalah laju frekuensi historis order pelanggan dan $t$ adalah hari sejak order terakhir.
* **Output**: *"Pelanggan VIP 'Komunitas Futsal Juara' (CLV Rp 45.000.000) belum order selama 68 hari. Probabilitas Churn: 78%. Rekomendasi: Hubungi dengan penawaran katalog bahan terbaru."*

### 4.4. Model Pertumbuhan Regional & Estimasi Biaya Logistik (`RegionalGrowthEstimator`)
* **Tabel Sumber**: `customers.provinsi_id`, `kabupaten_id`, `orders.ongkir` (`ReportRunner::wilayah`).
* **Metode**: Spatial Growth Velocity & Linear Freight Inflation Trend.
* **Output**: *"Pertumbuhan order wilayah Jawa Timur naik +35% MoM. Rata-rata beban ongkos kirim per kg naik 4,2%. Rekomendasi: Evaluasi kerja sama korporat dengan kargo ekspedisi alternatif."*

### 4.5. Model Proyeksi Arus Kas 30/60/90 Hari & Titik Nadir (`CashflowProjector`)
* **Tabel Sumber**: `invoices`, `pemasukans`, `purchasing_payments` (TOP), `hcm_payrolls`, `bank_accounts`.
* **Metode**: Aging-Weighted Receivable Inflow vs Scheduled Contractual Liabilities.
* **Komponen Arus Masuk**:
  $$\text{Inflow} = \sum (\text{Piutang Invoice Aktif} \times \text{Koefisien Pembayaran Berdasarkan Usia})$$
  *(Usia 0–15h: 0.90, 16–30h: 0.70, >30h: 0.35)*.
* **Komponen Arus Keluar**:
  $$\text{Outflow} = \text{Utang TOP Vendor} + \text{Payroll HCM} + \text{Uang Makan} + \text{Rata-rata Opex Rutin}$$
* **Output**: *"Proyeksi arus kas 30 hari ke depan: Saldo aman hingga tanggal 25. Titik nadir kas diperkirakan terjadi pada tanggal 28 dengan potensi defisit Rp 34.500.000 jika pembayaran termin vendor PT Kain Utama dicairkan sebelum piutang 3 invoice customer dilunasi."*

### 4.6. Model Keseimbangan Beban vs Kapasitas Tenaga Kerja (`WorkforceProjector`)
* **Tabel Sumber**: `orders`, `order_items`, `hcm_employees`, `hcm_leave_requests`, `hcm_work_shifts`.
* **Metode**: Standard Minute Value (SMV) Workload Balancing.
* **Formula**:
  $$\text{Kapasitas Jam Efektif} = (\text{Headcount Aktif} - \text{Cuti Disetujui}) \times 7\text{ jam/hari} \times \text{Hari Kerja}$$
  $$\text{Kebutuhan Jam} = \sum (\text{Pcs PO Terjadwal} \times \text{Standar Menit per Pcs}) / 60$$
* **Output**: *"Kapasitas tenaga kerja minggu depan: Beban kerja sebesar 128% dari kapasitas normal lini Jahit. Dibutuhkan alokasi lembur 2,5 jam/hari selama 3 hari kerja atau perbantuan 4 personel dari lini finishing untuk mencegah bottleneck."*

### 4.7. Model Prediksi Kelelahan Lembur & Ketidakhadiran Staf (`FatigueAbsenteeismPredictor`)
* **Tabel Sumber**: `hcm_overtimes`, `hcm_attendances` (`ReportRunner::kinerjaProduksi` x HCM).
* **Metode**: Cumulative Fatigue Index (CFI) & Logistic Absenteeism Risk.
* **Output**: *"Divisi Jahit telah lembur rata-rata 18,5 jam/orang selama 2 minggu berturut-turut. Indeks kelelahan tinggi (CFI 84). Diproyeksikan tingkat mangkir/sakit naik +15% minggu depan jika lembur tidak dibatasi."*

### 4.8. Model Proyeksi Habis Kontrak PKWT & Gelombang Turnover (`ContractTurnoverProjector`)
* **Tabel Sumber**: `hcm_contracts`, `hcm_employees` (`HcmDashboardController`).
* **Metode**: Expiry Wave Analysis (Jendela H-60 dan H-30).
* **Output**: *"Sebanyak 6 operator jahit berpengalaman akan habis masa kontrak PKWT dalam 25 hari ke depan. Risiko kekosongan lini 15% jika evaluasi perpanjangan kontrak tidak diselesaikan minggu ini."*

### 4.9. Model Proyeksi Tingkat Cacat Rijek & Biaya Rework (`QualityReworkProjector`)
* **Tabel Sumber**: `rijeks`, `order_items` (`ReportRunner::rijek`).
* **Metode**: Rolling Scrap Rate Trend & Material Rework Costing.
* **Output**: *"Tingkat rijek cetak minggu W42 sebesar 4,2% (naik dari 2,1% di W41). Proyeksi biaya pengerjaan ulang bulan ini: Rp 4.800.000 dengan konsumsi tambahan 32 botol tinta dan 45 meter kain."*

### 4.10. Model Liabilitas Garansi & Proyeksi Klaim Refund (`RefundLiabilityForecaster`)
* **Tabel Sumber**: `refunds`, `orders` (`ReportRunner::refund`).
* **Metode**: Defect-to-Refund Velocity Correlation.
* **Output**: *"Klaim refund masalah ukuran meningkat 2x pasca-perubahan pola potong baru. Diproyeksikan potensi klaim refund tambahan sebesar Rp 6.200.000 dalam 14 hari ke depan jika standar toleransi ukuran tidak diperketat."*

### 4.11. Model Kebutuhan Bahan (BOM) & Hitung Mundur Kehabisan Stok (`MaterialForecast`)
* **Tabel Sumber**: `purchasing_material_stocks`, `purchasing_material_usages`, `ai_material_standards`, `orders`, `order_items`.
* **Metode**: Hybrid Model (PO Pipeline Demand Allocation + Historical Consumption ROP).
* **Komponen 1: Alokasi Antrean PO Aktif (Short-Term Demand)**:
  $$\text{Kebutuhan PO}(M) = \sum_{\text{PO} \in \{\text{published, on\_progress}\}} \sum_{\text{Item}} (\text{Qty} \times \text{Standar Pemakaian})$$
  $$\text{Stok Bebas Efektif}(M) = \text{Stok Fisik}(M) - \text{Kebutuhan PO}(M)$$
* **Komponen 2: Dynamic Reorder Point (Mid-Term Buffer)**:
  $$\text{ROP}(M) = (\text{Rata-rata Konsumsi Harian 30 Hari} \times \text{Lead Time Vendor}) + \text{Safety Stock}$$
* **Komponen 3: Hitung Mundur Kehabisan Bahan (*Run-Out Countdown*)**:
  $$\text{Sisa Hari Menuju Kritis} = \frac{\max(0, \text{Stok Bebas Efektif})}{\text{Rata-rata Konsumsi Harian}}$$
* **Output Prediktif**: *"Kain Dryfit Milano Putih (MAT-KP-001): Saldo fisik 6 roll, telah teralokasi 7,5 roll untuk antrean PO aktif (PO-100 dan PO-102). Defisit riil: -1,5 roll. Stok habis saat pemotongan PO-102 besok siang. Rekomendasi: Terbitkan PO pengadaan darurat hari ini sebanyak 6,5 roll (menutupi defisit 1,5 roll + safety stock 5 roll)."*

### 4.12. Model Siklus Kerusakan Mesin (MTBF) & Proyeksi TCO Aset (`AssetLifecycleProjector`)
* **Tabel Sumber**: `purchasing_assets`, `purchasing_asset_maintenances`.
* **Metode**: Mean Time Between Failures (MTBF) & Total Cost of Ownership Threshold.
* **Output**: *"Mesin Sublimasi 01 (AST-PRN-001) mengalami 4 kali kerusakan dalam 90 hari terakhir (MTBF turun menjadi 22 hari). Biaya servis kumulatif telah mencapai 54% harga beli aset baru. Rekomendasi: Lakukan penggantian unit pada penganggaran CAPEX kuartal depan."*

---

## 5. Arsitektur Teknis, Snapshots, & Registry

### 5.1. Spesifikasi Teknis Isolasi Kode (Zero Breaking Changes)

1. **Namespace Mandiri**:
   - `App\Services\Ai\Shared\` (AgentRegistry, AgentRunner, SnapshotBuilder, AgentLogger, FraudScorer)
   - `App\Services\Ai\Order\` (OrderSnapshot, OrderTools, OrderDetectors, OrderForecast)
   - `App\Services\Ai\Finance\` (FinanceSnapshot, FinanceTools, FinanceDetectors, FinanceForecast)
   - `App\Services\Ai\Hcm\` (HcmSnapshot, HcmTools, HcmDetectors, HcmForecast)
   - `App\Services\Ai\Purchasing\` (PurchasingSnapshot, PurchasingTools, PurchasingDetectors, MaterialAnomaly, FraudDetectors, MaterialForecast)
2. **Kepatuhan Brand Context**: Seluruh query agregasi wajib menghormati `BrandContext::current()` atau mengisolasi data per-brand bagi user cabang/reseller, serta mode all-brands bagi owner/superadmin.
3. **Budget Token Ketat**: Snapshot gabungan 4 modul dipadatkan ke JSON terstruktur dengan batas maksimal **4.000 token** (~16.000 karakter). Anomali dan temuan berisiko tinggi diprioritaskan di awal payload.
4. **Guardrail Keamanan Data**:
   - Dilarang keras mengirim data PII sensitif ke LLM: NIK KTP karyawan, nomor rekening pribadi karyawan, rincian gaji pokok individu.
   - Hak cipta data bisnis terjaga, prompt system mewajibkan penalaran berbasis angka nyata.

### 5.2. Registri 12 Spesialis Agent (`AgentRegistry`)

| # | Agent Slug | Nama Spesialis | Fokus Kerja & Trigger | Sumber Laporan & Tools |
| :--- | :--- | :--- | :--- | :--- |
| 1 | `order-analyst` | Analis Penjualan & Produk | Tren produk terlaris, analisis pelanggan berulang, breakdown jenis PO. Harian 07:00. | `penjualan-produk`, `pelanggan`, `wilayah`, `jenis-po` |
| 2 | `deadline-guardian` | Pengawal Deadline & Produksi | Pemantauan keterlambatan PO, proyeksi sisa hari, deteksi bottleneck antartahap. Harian 07:30 & saat PO terbit. | `monitoring-deadline`, `kinerja-produksi`, `status-po` |
| 3 | `quality-watch` | Pengawas Mutu & Kepuasan | Korelasi cacat produksi (rijek), klaim refund, dan kelelahan operator lembur. Harian 08:00. | `rijek`, `refund`, `HcmOvertime` |
| 4 | `cashflow-forecaster` | Analis Arus Kas & Piutang | Proyeksi arus kas 30 hari, pengawasan piutang macet, rekonsiliasi mutasi bank. Mingguan Senin 07:00. | `pemasukan`, `pengeluaran`, `arus-kas-bank`, `Invoice` |
| 5 | `purchasing-watch` | Pemantau Pengadaan & TOP | Pengawasan pengadaan macet, jadwal jatuh tempo termin vendor, kenaikan harga beli. Harian 08:30. | `PurchasingReport`, `PurchasingPayment`, `PurchasingVendor` |
| 6 | `stock-guardian` | Penjaga Ketahanan Material | Proyeksi habis bahan baku, rekomendasi reorder point dinamis, peringatan stok kritis. 2x sehari (07:00, 14:00). | `PurchasingMaterialStock`, `PurchasingMaterialUsage` |
| 7 | `material-analyst` | Analis Standar BOM Jersey | Audit variansi pemakaian bahan (kain, sublim, tinta) vs standar produksi jersey per pcs. Harian 09:00. | Standar BOM (`ai_material_standards`), kartu pemakaian material |
| 8 | `fraud-sentinel` | Satuan Anti-Fraud Pengadaan | Skrining 8 pola kecurangan pengadaan (pemecahan PO, mark-up, vendor fiktif, kas bon ganjil). 2x sehari & tiap approval PO. | Detektor F1–F8, riwayat vendor, audit approval |
| 9 | `workforce-advisor` | Penasihat Kapasitas SDM | Keseimbangan beban kerja vs kapasitas orang aktif, kepatuhan batas lembur, evaluasi absensi. Harian 07:15. | `HcmAttendance`, `HcmOvertime`, `HcmLeaveRequest` |
| 10 | `hr-compliance-guardian` | Pengawal Kepatuhan & Kontrak | Peringatan dini habis kontrak PKWT (H-30), evaluasi masa percobaan (H-7), kelengkapan dokumen. Mingguan. | `HcmContract`, `HcmEmployee`, `HcmMasterOption` |
| 11 | `asset-custodian` | Pengawas Aset & Perawatan | Pemantauan siklus servis mesin, pembengkakan biaya pemeliharaan vs nilai buku aset. Bulanan. | `PurchasingAsset`, `PurchasingAssetMaintenance` |
| 12 | `executive-brief` | Ringkasan Eksekutif Terpadu | Sintesis lintas 4 modul untuk Pemilik Usaha/Direksi: Keuangan, Order, Pabrik, & Karyawan + 3 Prioritas Utama Hari Ini. Harian 06:30. | Seluruh snapshot teragregasi |

### 5.3. Panduan Implementasi: Eliminasi Masalah N+1 & Token-Dense JSON Builder

Untuk mencegah database down akibat *N+1 query problem* saat membaca puluhan PO dan itemnya, sistem menggunakan 3 prinsip implementasi teknis:

#### 1. Single-Shot DB Aggregation (`JSON_ARRAYAGG` / Raw Subquery)
Alih-alih melooping model Eloquent di PHP:
```php
// DILARANG: Memicu ratusan N+1 Query ke database
foreach (Order::where('status_po', 'published')->get() as $order) {
    foreach ($order->items as $item) { // N+1 Query per baris item
        $bahan = $item->bahanKain->nama; // N+1 Query per bahan
    }
}
```
Implementasi yang wajib digunakan di `OrderSnapshot.php`:
```php
// BENAR: 1 Query Tunggal Menggunakan Eager Load Terbatas & Kolom Spesifik
$activeOrders = Order::query()
    ->select(['id', 'no_po', 'customer_id', 'status_po', 'deadline_customer', 'total_amount'])
    ->with([
        'customer:id,nama',
        'items' => function ($q) {
            $q->select(['id', 'order_id', 'nama_produk', 'quantity', 'bahan_kain_id', 'bahan_kain_bawahan_id']);
        },
        'items.bahanKain:id,nama',
        'progressDetails' => function ($q) {
            $q->select(['id', 'order_id', 'progress_id', 'status', 'completed_at', 'updated_at']);
        }
    ])
    ->whereIn('status_po', ['published', 'on_progress'])
    ->get();
```

#### 2. Schema Flattening: Kompresi JSON untuk Efisiensi Token LLM
Data hasil kueri di atas diubah menjadi JSON padat (*token-dense format*) yang membuang seluruh atribut timestamps dan metadata internal yang tidak relevan:
```php
$compactJson = $activeOrders->map(fn($o) => [
    'po' => $o->no_po,
    'cust' => $o->customer?->nama,
    'dl' => $o->deadline_customer?->format('Y-m-d'),
    'items' => $o->items->map(fn($i) => [
        'name' => $i->nama_produk,
        'qty' => (int) $i->quantity,
        'bhn_id' => $i->bahan_kain_id,
        'bhn_name' => $i->bahanKain?->nama,
    ])
]);
```
Format ini memangkas ukuran payload hingga **75%** dan menjamin prompt tidak akan pernah melampaui batas batas konteks LLM.

---

### 5.4. Arsitektur Snapshot Cache & Pemisahan Beban (Zero-Impact Production)

Untuk memastikan sistem kasir, input PO, dan tracking produksi di web **tetap responsif dan tidak terbebani sedikit pun oleh aktivitas AI**:

```
┌──────────────────────────────────────────────────────────────┐
│                    ALUR KERJA MATERIALIZED CACHE             │
├──────────────────────────────────────────────────────────────┤
│ 1. TRIGGER PERIODIK (Cron / Artisan Command / Event)          │
│    - Jadwal: 06:30 WIB (Pagi), 12:00 WIB (Siang), 17:00 WIB  │
│    - Event: Saat PO diterbitkan ('OrderPublishedEvent')      │
└──────────────────────────────┬───────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────┐
│ 2. LARAVEL QUEUE WORKER (Background Process)                 │
│    - Mengeksekusi Single-Shot Aggregation Query              │
│    - Menghitung formula matematika & anomali (PHP Murni)     │
│    - Menghasilkan 1 Payload JSON Matang                      │
│    - Menyimpan ke tabel 'ai_snapshots_cache'                 │
└──────────────────────────────┬───────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────┐
│ 3. PENGGUNAAN OLEH AI AGENT & DASHBOARD                      │
│    - Waktu Baca: < 1 Milidetik (Cukup 'SELECT payload_json   │
│      FROM ai_snapshots_cache WHERE domain = ? LIMIT 1')      │
│    - AI Loop tidak menyentuh tabel operasional sama sekali   │
│    - Zero Lock pada tabel 'orders', 'purchasing', dll.       │
└──────────────────────────────────────────────────────────────┘
```

---

## 6. Skema Database Terisolasi (`ai_*`)

Terdapat 6 tabel baru yang dijamin tidak mengubah tabel bisnis yang sudah ada:

```sql
-- 1. Standar Pemakaian Bahan Baku (BOM Jersey)
CREATE TABLE ai_material_standards (
    id CHAR(36) PRIMARY KEY,
    material_type VARCHAR(50) NOT NULL,
    kategori_produk VARCHAR(100) NOT NULL,
    std_qty_per_pcs DECIMAL(12, 4) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    tolerance_pct DECIMAL(5, 2) DEFAULT 8.00,
    is_active BOOLEAN DEFAULT TRUE,
    updated_by BIGINT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uq_material_kategori (material_type, kategori_produk)
);

-- 2. Kasus Deteksi Fraud & Pelanggaran Pengadaan
CREATE TABLE ai_fraud_cases (
    id CHAR(36) PRIMARY KEY,
    detector_code VARCHAR(10) NOT NULL, -- F1 s.d. F8
    subject_type VARCHAR(30) NOT NULL,  -- 'po', 'vendor', 'requester', 'employee'
    subject_id VARCHAR(50) NOT NULL,    -- nomor PO, kode vendor, atau ID pemohon
    risk_score TINYINT UNSIGNED NOT NULL,
    severity ENUM('medium', 'high', 'critical') NOT NULL,
    status ENUM('open', 'reviewed', 'cleared') DEFAULT 'open',
    evidence_json JSON NOT NULL,
    reviewed_by BIGINT NULL,
    review_note TEXT NULL,
    notified_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_fraud_status (status, severity),
    INDEX idx_detector (detector_code)
);

-- 3. Rekam Eksekusi Agent (Audit Trail)
CREATE TABLE ai_agent_runs (
    id CHAR(36) PRIMARY KEY,
    agent_slug VARCHAR(50) NOT NULL,
    trigger_type ENUM('schedule', 'manual', 'webhook', 'chat') NOT NULL,
    scope_json JSON NOT NULL, -- { brand_id, date_from, date_to }
    status ENUM('success', 'partial', 'failed') NOT NULL,
    tokens_used INT UNSIGNED DEFAULT 0,
    model_used VARCHAR(100) NOT NULL,
    duration_ms INT UNSIGNED NOT NULL,
    error_message TEXT NULL,
    created_by BIGINT NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_agent_runs (agent_slug, created_at)
);

-- 4. Temuan, Anomali, Prediksi, & Rekomendasi
CREATE TABLE ai_agent_insights (
    id CHAR(36) PRIMARY KEY,
    run_id CHAR(36) NOT NULL,
    agent_slug VARCHAR(50) NOT NULL,
    insight_type ENUM('anomaly', 'prediction', 'summary', 'recommendation', 'fraud') NOT NULL,
    severity ENUM('info', 'medium', 'high', 'critical') NOT NULL,
    title VARCHAR(255) NOT NULL,
    body_md TEXT NOT NULL,
    data_json JSON NULL, -- metrik pendukung, nomor PO, tautan bukti
    is_read BOOLEAN DEFAULT FALSE,
    is_dismissed BOOLEAN DEFAULT FALSE,
    notified_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_insights_severity (severity, is_dismissed),
    INDEX idx_insights_slug (agent_slug, created_at),
    FOREIGN KEY (run_id) REFERENCES ai_agent_runs(id) ON DELETE CASCADE
);

-- 5. Konfigurasi Jadwal & Distribusi Notifikasi
CREATE TABLE ai_agent_schedules (
    id CHAR(36) PRIMARY KEY,
    agent_slug VARCHAR(50) NOT NULL UNIQUE,
    frequency ENUM('twice_daily', 'daily', 'weekly', 'monthly') NOT NULL,
    schedule_time TIME NOT NULL,
    schedule_day VARCHAR(10) NULL, -- 'Monday', dll. untuk mingguan
    is_active BOOLEAN DEFAULT TRUE,
    channels_json JSON NOT NULL,   -- [{ channel: 'whatsapp', target: '0812...' }, { channel: 'telegram', target: '-100...' }]
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- 6. Materialized Snapshot Cache Table (Bebas N+1 & Super Cepat < 1ms)
CREATE TABLE ai_snapshots_cache (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    domain VARCHAR(50) NOT NULL COMMENT 'order, finance, hcm, purchasing, composite',
    scope_key VARCHAR(100) NOT NULL COMMENT 'all_brands, brand_1, weekly_w42, dll',
    payload_json LONGTEXT NOT NULL COMMENT 'JSON padat siap baca AI Agent (token-dense)',
    record_count INT UNSIGNED DEFAULT 0,
    anomalies_detected_count INT UNSIGNED DEFAULT 0,
    generated_at TIMESTAMP NOT NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uk_domain_scope (domain, scope_key),
    INDEX idx_domain_expires (domain, expires_at)
);
```

---

## 7. Rencana Antarmuka Pengguna (UI React / Inertia)

Antarmuka dibangun dengan komponen standar sistem yang sudah ada (`DataTable`, `StatCard`, `Chart.jsx`, `AppLayout`, `Badge`):

1. **AI Command Center (`Pages/Ai/Dashboard/AgentIndex.jsx`)**:
   - Kartu metrik live: Total Temuan Hari Ini, Status Anomali Kritis (merah), Kasus Fraud Aktif (kuning), Skor Kesehatan Operasional (0–100%).
   - Tabulasi 12 Agent dengan tombol "Jalankan Analisis", status run terakhir, dan waktu eksekusi terjadwal.
   - Panel Feed "Butuh Perhatian Segera" (insight unread dengan severity `high` dan `critical`).
2. **Pusat Penanganan Fraud & Anomali (`Pages/Ai/Fraud/FraudInbox.jsx`)**:
   - Antrean kasus dugaan fraud pengadaan (F1–F8) dan anomali pemborosan bahan (M1–M4).
   - Panel bedah bukti: angka pembanding, histori transaksi vendor, selisih anggaran.
   - Tindakan berwenang: tombol "Tandai Telah Diverifikasi / Clear" dengan kewajiban mengisi catatan investigasi resmi.
3. **Papan Proyeksi & Prediksi Finansial (`Pages/Ai/Finance/CashflowBoard.jsx`)**:
   - Grafik interaktif proyeksi arus kas 30 hari ke depan dengan batas garis aman saldo bank.
   - Tabel simulasi pembayaran: dampak terhadap kas jika tagihan vendor dimajukan vs ditunda.
4. **Papan Keseimbangan Beban Produksi & SDM (`Pages/Ai/Hcm/WorkforceBoard.jsx`)**:
   - Grafik perbandingan jam kerja kapasitas pabrik vs antrean PO terjadwal per minggu.
   - Analisis korelasi lembur vs rijek per divisi jahit dan sublimasi.
5. **Papan Kendali Bahan Baku & BOM (`Pages/Ai/Purchasing/MaterialBoard.jsx`)**:
   - Status ketahanan stok (sisa hari hingga habis).
   - Tabel audit variansi BOM per jenis kain jersey dengan penanda status boros/efisien.
6. **Chat Analis Interaktif (`Pages/Ai/Chat/AgentChat.jsx`)**:
   - Antarmuka tanya-jawab bebas mengenai seluruh laporan sistem.
   - Menampilkan chip referensi sumber data resmi (misal: "Berdasarkan Laporan Monitoring Deadline & Arus Kas Bank").

---

## 8. Notifikasi Proaktif WhatsApp & Telegram: Standar Narasi Baku & Sumber Ideal

Seluruh temuan penting dari AI Agent dialirkan ke **WhatsApp** (via `SidobeClient` / `NotificationDispatcher`) dan Telegram. Pengiriman notifikasi memegang prinsip: **baku, berbasis data riil, bebas basa-basi (anti-slop), dan wajib menyertakan sumber data yang dapat diverifikasi secara langsung.**

### 8.1. Jalur Pengiriman WhatsApp & Matriks Penerima

Pengiriman menggunakan [`NotificationDispatcher`](file:///d:/AI/Nisreport/nisreportupadate/app/Services/Notifications/NotificationDispatcher.php) yang sudah aktif di sistem, dipetakan berdasarkan domain dan tingkat keparahan (*severity*):

| Kategori Notifikasi | Target Nomor / Grup WhatsApp | Trigger Pengiriman | Ambang Batas Severity |
| :--- | :--- | :--- | :--- |
| **Ringkasan Eksekutif** (`executive-brief`) | WhatsApp Pribadi Owner / Direksi | Harian 06:30 | Info, High, Critical |
| **Anomali Keterlambatan PO** (`deadline-guardian`) | WhatsApp Grup Tim Produksi & Kepala Pabrik | Harian 07:30 & Real-time saat PO terbit | High, Critical (H-1 / Overdue) |
| **Peringatan Mutu & Rijek** (`quality-watch`) | WhatsApp Supervisor QC & Kepala Jahit/Cetak | Harian 08:00 & Real-time saat ada rijek >5% | High, Critical |
| **Anomali Bahan & BOM** (`material-analyst`) | WhatsApp Kepala Gudang & Manajer Produksi | Harian 09:00 & Real-time saat variansi >8% | High, Critical |
| **Dugaan Fraud & Belanja Macet** (`fraud-sentinel`) | WhatsApp Pribadi Owner & Admin Keuangan | 2x Sehari (10:00, 16:00) & Real-time approval | Critical (Skor $\ge 60$) |
| **Peringatan Kas & Piutang** (`cashflow-forecaster`) | WhatsApp Manajer Keuangan & Owner | Mingguan Senin 07:00 & saat defisit terdeteksi | High, Critical |
| **Peringatan SDM & Kontrak** (`hr-compliance`) | WhatsApp HRD / HCM Manager | Mingguan Senin 08:30 | Medium, High (H-30 PKWT) |

---

### 8.2. Standar Narasi Baku (Format Resmi Eksekutif & Non-Slop)

Format pesan WhatsApp disusun dengan standar baku:
1. **Bahasa Indonesia Baku & Profesional**: Menggunakan istilah resmi (bukan bahasa percakapan santai, tanpa kata-kata klise seperti "revolusioner", "cerdas", "luar biasa").
2. **Tanpa Tanda Em-Dash (`—`)**: Mengikuti pedoman kepatuhan tipografi sistem (gunakan titik dua `:`, kurung `()`, atau tanda pisah biasa `-`).
3. **Struktur Pesan 5 Blok**:
   - **Blok 1 (Header Resmi)**: Jenis Peringatan, Nama Agent, Waktu Eksekusi.
   - **Blok 2 (Fakta Inti / Temuan)**: 1-2 kalimat ringkas dengan angka riil dari DB.
   - **Blok 3 (Rincian Deviasi / Anomali)**: Angka ekspektasi vs realisasi, persentase selisih, dampak waktu/biaya.
   - **Blok 4 (Rekomendasi Tindakan / Action Items)**: 1-3 tindakan konkret dalam bentuk checklist `[ ]`.
   - **Blok 5 (Sumber Data Ideal & Tautan Verifikasi)**: Nama tabel/laporan sumber dan tautan web resmi ke sistem internal untuk audit 1-klik.

---

### 8.3. Sumber Data yang Ideal & Terverifikasi (Data Traceability)

Setiap pesan WhatsApp **wajib mencantumkan sumber data riil**:
* **Bukan Asumsi**: Angka tidak boleh ditulis "diperkirakan banyak rijek", melainkan "ditemukan 12 pcs rijek pada tahap Jahit".
* **Atribut Sumber yang Wajib Ada di Footer Pesan**:
  - `Sumber Data`: Menyebutkan laporan resmi (misal: *Laporan Monitoring Deadline*, *Kartu Stok Bahan*, *Rekapitulasi Bank*).
  - `ID Entitas`: Menyebutkan nomor unik (`no_po`, `asset_code`, `batch_code`, `vendor_name`).
  - `Tautan Langsung`: URL yang langsung membuka dokumen atau baris bersangkutan di dashboard aplikasi.

---

### 8.4. Contoh Format Pesan WhatsApp Riil

#### Contoh 1: Notifikasi Anomali Pemborosan Bahan Baku (Material Analyst)
```
🔴 *PERINGATAN ANOMALI BAHAN: MATERIAL-ANALYST*
Tanggal: 22 Oktober 2026, 09:00 WIB
Tingkat Urgensi: *CRITICAL*

Telah terdeteksi pemborosan pemakaian Tinta Sublimasi melebihi batas toleransi standar pada pengerjaan batch cetak minggu ini.

📊 *RINCIAN VARIANSI PEMAKAIAN:*
- Output Fisik: 300 pcs jersey (Tahap PRINTING selesai)
- Standar Kebutuhan: 30,0 botol (BOM: 0,10 botol/pcs)
- Realisasi Pengeluaran: 45,0 botol
- Selisih Pemborosan: *+15,0 botol (+50,0%)*
- Nilai Kerugian Bahan: *Rp 2.250.000* (Estimasi Rp 150.000/botol)
- Riwayat Rijek Cetak: 6 pcs (Kompensasi wajar: 0,6 botol)
- Selisih Tak Terjelaskan: *14,4 botol*

📌 *REKOMENDASI TINDAKAN:*
[ ] Periksa kalibrasi densitas semprotan tinta pada Mesin Sublim 02.
[ ] Evaluasi operator shift malam terkait prosedur pembersihan head mesin.
[ ] Cocokkan fisik sisa botol di lemari produksi dengan kartu stok gudang.

🔎 *SUMBER DATA TERVERIFIKASI:*
- Basis Data: purchasing_material_usages & order_progress_details
- Referensi Lini: Divisi Cetak Sublimasi (Operator: Budi S.)
- Tautan Verifikasi: https://protrack.app/ai-agents/material-analyst?ref=W42
```

#### Contoh 2: Notifikasi Dugaan Fraud Pengadaan (Fraud Sentinel)
```
⚠️ *PERINGATAN FRAUD PENGADAAN: FRAUD-SENTINEL*
Waktu Audit: 22 Oktober 2026, 16:00 WIB
Status Kasus: *OPEN (Skor Risiko: 85/100)*

Terdeteksi indikasi pemecahan transaksi pembelian (PO Splitting) untuk menghindari eskalasi persetujuan Direksi (Aturan F2).

📋 *BUKTI TRANSAKSI TERKAIT:*
- Pemohon: Bagian Perlengkapan (User ID: 14)
- Vendor: CV Berkah Mandiri
- Periode: Rentang 3 hari (19-21 Oktober 2026)
- Rincian PO Terbit:
  1. PO-PUR-0891: Rp 4.800.000 (Kain TC)
  2. PO-PUR-0895: Rp 4.900.000 (Kain TC)
  3. PO-PUR-0902: Rp 4.750.000 (Kain TC)
- Total Akumulasi: *Rp 14.450.000* (Batas Approval Wajib Direksi: Rp 5.000.000)

📌 *REKOMENDASI TINDAKAN:*
[ ] Tunda pencairan pembayaran termin (TOP) pada 3 PO terkait.
[ ] Lakukan klarifikasi resmi kepada pemohon pengadaan.
[ ] Buka menu Fraud Inbox untuk memverifikasi atau menandai kasus ini.

🔎 *SUMBER DATA TERVERIFIKASI:*
- Basis Data: purchasing_orders join purchasing_order_items
- Tautan Investigasi: https://protrack.app/ai-agents/fraud-inbox?case=CAS-2026-004
```

#### Contoh 3: Ringkasan Eksekutif Pagi (Executive Brief)
```
👔 *EXECUTIVE BRIEF HARIAN: DIREKSI & OWNER*
Tanggal: 23 Oktober 2026, 06:30 WIB
Brand Scope: All Brands

Ringkasan kesehatan operasional dan 3 fokus utama untuk hari ini:

📈 *RINGKASAN 4 MODUL:*
1. Kas & Piutang: Kas masuk kemarin Rp 48.500.000. Terdapat 3 invoice jatuh tempo senilai Rp 32.000.000 yang perlu ditagih.
2. Order & Pabrik: 120 PO aktif (5.400 pcs). Sebanyak 2 PO berisiko telat kirim hari ini (PO-1089 dan PO-1094).
3. Pengadaan: Stok Kain Milano Putih kritis (sisa 4 hari pengerjaan). 1 TOP vendor Rp 18.000.000 jatuh tempo besok.
4. SDM: 42 staf aktif, 3 cuti disetujui, lembur divisi jahit kemarin 32 jam.

🎯 *3 PRIORITAS EKSEKUTIF HARI INI:*
1. Instruksikan tim finance untuk penagihan prioritas invoice PT Cipta Kreasi (Rp 18.500.000).
2. Setujui PO pengadaan kain Milano untuk mencegah lini potong berhenti hari Jumat.
3. Alihkan 2 personel finishing untuk membantu packing PO-1089 agar terkirim sore ini.

#### Contoh 4: Peringatan PO Diam / Zero Progress Harian (Deadline Guardian)
```
⚠️ *PERINGATAN STAGNASI PO: DEADLINE-GUARDIAN*
Waktu Audit: 23 Oktober 2026, 17:00 WIB (Evaluasi Shift Sore)
Tingkat Urgensi: *HIGH / CRITICAL*

Terdeteksi 3 PO aktif tidak mengalami pergerakan tahapan fisik sama sekali dalam 24 jam terakhir (Hari Kerja).

📋 *DAFTAR PO MENGANGGUR (ZERO PROGRESS 24 JAM):*
1. *PO-2026-1045* (150 pcs, Kaos Voli - Juara Club)
   - Status: on_progress (Macet di Pos: JAHIT)
   - Waktu Update Terakhir: 22 Okt 2026, 14:15 WIB (26,8 jam lalu)
   - Deadline Customer: 25 Okt 2026 (*Sisa 2 Hari! - STATUS KRITIS*)
   - Kendala Terakhir: "Menunggu benang warna navy tiba"

2. *PO-2026-1052* (80 pcs, Jersey Basket - Alumni SMA 1)
   - Status: on_progress (Macet di Pos: PRESS SUBLIME)
   - Waktu Update Terakhir: 22 Okt 2026, 15:30 WIB (25,5 jam lalu)
   - Deadline Customer: 27 Okt 2026 (Sisa 4 Hari)
   - Kendala Terakhir: Tidak ada catatan kendala

3. *PO-2026-1060* (200 pcs, Jaket Komunitas - Biker Nusantara)
   - Status: published (Belum ditarik ke tahap SETTING / POTONG)
   - Waktu Publish: 22 Okt 2026, 11:00 WIB (30 jam lalu)
   - Deadline Customer: 30 Okt 2026 (Sisa 7 Hari)

📌 *REKOMENDASI TINDAKAN:*
[ ] Prioritaskan eksekusi PO-2026-1045 malam ini agar tidak melanggar deadline H-2.
[ ] Tegur penanggung jawab pos Press Sublime untuk segera memproses kain PO-2026-1052.
[ ] Tarik file PO-2026-1060 dari antrean admin ke meja antrean setting produksi.

🔎 *SUMBER DATA TERVERIFIKASI:*
- Basis Data: orders join order_progress_details (Status: on_progress / published)
- Tautan Produksi: https://protrack.app/produksi/kanban?filter=zero_progress
```

#### Contoh 5: Peringatan Pembelian Bahan Baku Akurat Sesuai PO (Anti-Mismatch Bahan)
```
🚨 *PERINGATAN DEFISIT BAHAN BAKU: MATERIAL-SENTINEL*
Waktu Audit: 24 Oktober 2026, 08:00 WIB
Tingkat Urgensi: *CRITICAL (POTENSI LINI POTONG TERHENTI)*

Peringatan pengadaan bahan baku berdasarkan alokasi pesanan PO aktif. Sistem telah memverifikasi relasi ID bahan secara deterministik (Anti-Mismatch).

📦 *RINCIAN BAHAN YANG WAJIB DIBELI:*
- *Nama Bahan di Order*: Dryfit Milano Putih (Master Kain ID: 3)
- *Item Stok Gudang*: MAT-KP-001 (Milano Putih Polos - Roll)
- *Bagian Pakaian*: ATASAN_UTAMA (Badan Depan & Belakang)

📊 *ANALISIS NERACA STOK FISIK VS ANTREAN PO:*
1. Stok Fisik di Gudang: *6,00 Roll* (Batas Minimum: 5,00 Roll)
2. Total Kebutuhan Antrean PO Aktif: *7,50 Roll*
   - *PO-2026-100* (100 pcs, Jersey Futsal - Garuda Team): Butuh 1,50 Roll
   - *PO-2026-102* (400 pcs, Jersey Event - Maratona 10K): Butuh 6,00 Roll
3. Sisa Stok Bebas (*Free Stock*): *6,00 - 7,50 = -1,50 Roll (DEFISIT!)*
4. Estimasi Titik Kritis: Stok fisik akan habis saat pemotongan kain PO-2026-102 besok siang pukul 13:00 WIB.

💡 *CATATAN ANTI-MISMATCH / PENCEGAHAN SALAH BELI:*
- Bahan Serena Putih (MAT-KP-002) fisik memang tersisa 2 Roll (< threshold 4 Roll), TETAPI saat ini *0 PO aktif* yang membutuhkan Serena Putih.
- *JANGAN MEMBELI SERENA PUTIH HARI INI.* Alihkan prioritas anggaran 100% untuk membeli *DRYFIT MILANO PUTIH (MAT-KP-001)* agar PO-100 & PO-102 tidak mangkrak!

📌 *REKOMENDASI TINDAKAN SEGERA:*
[ ] Terbitkan PO Pengadaan Bahan untuk *Milano Putih Polos (MAT-KP-001)* sebanyak minimal *6,5 Roll* (menutup defisit 1,5 roll + cadangan safety stock 5 roll).
[ ] Hubungi Supplier Utama (PT Tekstil Mandiri) untuk jadwal kirim mendesak hari ini.
[ ] Koordinasikan dengan tim Potong Bahan agar memprioritaskan pemotongan PO-100 terlebih dahulu selagi menunggu pasokan tambahan.

🔎 *SUMBER DATA TERVERIFIKASI:*
- Basis Data: `orders` join `order_items` (ID Bahan: 3) join `ai_material_standards` join `purchasing_material_stocks` (MAT-KP-001)
- Tautan Buat PO Pengadaan: https://protrack.app/purchasing/orders/create?item_code=MAT-KP-001&suggested_qty=6.5
```

---


## 9. Roadmap & Timeline Eksekusi Bertahap yang Realistis (Phase-Gate Roadmap)

Roadmap disusun secara bertahap (*phase-gate*) dengan prinsip **Zero-Downtime, Zero-Data-Risk, dan Non-Destructive**. Setiap fase memiliki kriteria kelulusan (*gate acceptance*) yang dapat diuji secara mandiri sebelum melangkah ke fase berikutnya.

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                   TIMELINE IMPLEMENTASI BERTAHAP (ESTIMASI 18 HARI KERJA)              │
├─────────────────┬─────────────────┬─────────────────┬─────────────────┬────────────────┤
│ Fase 1 (H 1-3)  │ Fase 2 (H 4-6)  │ Fase 3 (H 7-9)  │ Fase 4 (H 10-12)│ Fase 5-6(H13-18│
│ Skema ai_* &    │ Detektor Anomali│ Safe Refactor   │ LLM Client &    │ UI React Web & │
│ Mapping Bahan   │ & Anti-Mismatch │ MessageBuilder  │ AI Loop Dispatch│ Go-Live Trial  │
└─────────────────┴─────────────────┴─────────────────┴─────────────────┴────────────────┘
```

| Fase | Target Deliverable Teknis | Kegiatan & Cakupan Pekerjaan | Uji Validasi (*Gate Acceptance*) | Estimasi Waktu |
| :--- | :--- | :--- | :--- | :--- |
| **Fase 1: Fondasi Skema, Mapping Bahan, & Snapshot Cache** | 1. Migrasi 6 tabel terisolasi `ai_*` (`ai_snapshots_cache`, `ai_material_standards`, `ai_fraud_cases`, `ai_agent_runs`, `ai_agent_insights`, `ai_agent_schedules`).<br>2. Seeding pemetaan bahan `bahan_kains.id` $\leftrightarrow$ `purchasing_material_stocks.id`.<br>3. Pembangunan 4 service Snapshot Builder (`Order`, `Finance`, `Hcm`, `Purchasing`). | • Menghubungkan silo data bahan order dengan stok gudang.<br>• Menerapkan single-shot database aggregation dengan kolom terpilih (anti-N+1).<br>• Mengimplementasikan kompresi JSON padat (*token-dense format*). | • Migrasi rollback dan re-run sukses 100%.<br>• Seeding bahan jersey utama (Milano, Serena, Bintik, Lotto) terpetakan tanpa error.<br>• Snapshot JSON terkompresi $\le 4.000$ token dan waktu generasi $< 50\text{ ms}$. | **Hari 1 – 3** (3 Hari) |
| **Fase 2: Detektor Anomali & Engine Anti-Mismatch** | 1. 30 Detektor Anomali Deterministik (OA, FA, HA, PA, CMA).<br>2. Detektor Stagnasi PO Diam 24 Jam (`OA3A`).<br>3. Engine Defisit Bahan Berbasis Antrean PO Aktif (`MA1–MA3`).<br>4. Formula Binding Time-Lag Tahapan (`PRINTING` vs `POTONG BAHAN`). | • Menulis logika kueri SQL/PHP murni untuk mendeteksi anomali tanpa bantuan AI.<br>• Menghitung formula neraca bahan: $\text{Free Stock} = \text{Fisik} - \text{Alokasi PO Aktif}$.<br>• Menerapkan 3-Layer Pre-Dispatch Guardrail. | • Simulasi pengujian kasus nyata: Saat PO 100 dibuat dengan Bahan A, sistem terbukti **hanya memicu peringatan beli Bahan A, dan 100% memblokir Bahan B**.<br>• Unit test deteksi PO diam 24 jam lulus uji. | **Hari 4 – 6** (3 Hari) |
| **Fase 3: Safe Refactor `ReportMessageBuilder` (Single Source of Truth)** | 1. Backup utuh file asli ke `ReportMessageBuilder.backup.php`.<br>2. Refactoring internal `ReportMessageBuilder` untuk menarik angka resmi dari `ReportRunner` via helper `resolveDateFilter($periode)`.<br>3. Penambahan Standar Laporan Harian Faktual untuk Purchasing & HCM (§2.7).<br>4. Artisan command uji coba: `php artisan reports:preview {periode} --role={role}`. | • Mengganti kueri SQL duplikat dengan pemanggilan `ReportRunner` terukur.<br>• Memasang blok `try-catch` dengan mekanisme *safe fallback* otomatis ke kode lama jika terjadi kendala.<br>• Memastikan seluruh tata letak, emoji, font tebal, progress bar, dan footer penutup **100% sama persis**. | • Hasil uji `reports:preview` di terminal mencetak format visual yang identik dengan WhatsApp lama.<br>• Angka omzet di teks WhatsApp terbukti 100% cocok dengan angka di dashboard web `/reports/penjualan-produk`. | **Hari 7 – 9** (3 Hari) |
| **Fase 4: Multi-Provider LLM Client & Autonomous AI Loop** | 1. Pembangunan `LlmClient.php` OpenAI-Compatible generik.<br>2. Konfigurasi multi-provider (DeepSeek-V3 / Gemini Flash sebagai utama, OpenAI gpt-4o-mini sebagai failover).<br>3. Registri 12 Agent Spesialis (`AgentRegistry`) & `AgentRunner`.<br>4. Integrasi pengiriman peringatan darurat instan (*event-driven*) via `NotificationDispatcher`. | • Membangun handler failover otomatis saat API provider utama mengalami gangguan/kuota habis.<br>• Prompt engineering narasi baku profesional Indonesia (anti-slop) dengan rincian bukti data dan 3 checklist prioritas tindakan.<br>• Integrasi scheduler background worker untuk kompilasi snapshot rutin. | • Tes failover buatan (simulasi DeepSeek error) otomatis beralih ke OpenAI tanpa downtime.<br>• Waktu respons analisis AI $< 4\text{ detik}$.<br>• Pesan notifikasi terkirim sukses ke nomor WhatsApp pengujian. | **Hari 10 – 12** (3 Hari) |
| **Fase 5: Antarmuka Web Dashboard AI & Interactive Chat** | 1. Route terisolasi `routes/ai.php` dan `AiAgentController.php`.<br>2. 6 Halaman React / Inertia (`AgentIndex.jsx`, `FraudInbox.jsx`, `CashflowBoard.jsx`, `WorkforceBoard.jsx`, `MaterialBoard.jsx`, `AgentChat.jsx`).<br>3. Aksi operasional: Tombol Mark Read, Dismiss, dan Cleared Kasus Fraud dengan catatan wajib. | • Membangun UI modern berstandar Inertia & Tailwind yang menyatu dengan tema sistem yang sudah ada.<br>• Menampilkan grafik proyeksi arus kas 30 hari dan hitung mundur stok bahan.<br>• Mengaktifkan antarmuka tanya-jawab bebas mengenai seluruh laporan sistem. | • Pengujian alur UI lengkap di browser berjalan mulus tanpa error konsol.<br>• Fitur chat mampu menjawab pertanyaan analitik dengan mengutip sumber data resmi sistem. | **Hari 13 – 15** (3 Hari) |
| **Fase 6: Uji Paralel Lapangan, Kalibrasi Threshold, & Go-Live** | 1. Masa uji coba paralel (*Parallel Run Trial*) selama 3–5 hari kerja.<br>2. Kalibrasi batas toleransi (persentase susut BOM, ambang lembur CFI, limit approval PO).<br>3. Serah terima dokumentasi teknis & SOP penggunaan. | • Menjalankan pengiriman laporan terjadwal harian/mingguan ke grup internal manajemen.<br>• Mengumpulkan umpan balik dari tim produksi, purchasing, dan keuangan untuk penyesuaian sensitivitas detektor anomali. | • Seluruh PIC divisi mengonfirmasi angka laporan WhatsApp akurat dan sesuai kondisi lapangan.<br>• Sistem disahkan berjalan penuh (*Full Production Go-Live*). | **Hari 16 – 18** (3 Hari) |

---

## 10. Pertanyaan Keputusan Pemilik Sistem (Konfirmasi Sebelum Eksekusi)

1. **Provider Utama & Sekunder**: Apakah disetujui menggunakan **DeepSeek Chat** sebagai provider hemat biaya harian (est. biaya token < Rp 100.000/bulan untuk seluruh agent) dengan failover otomatis ke **OpenAI gpt-4o-mini**?
2. **Penerima Peringatan Kritis**: Mohon daftarkan nomor WhatsApp grup atau ID Telegram khusus untuk menerima alert tingkat `critical` (misal: indikasi fraud pengadaan atau ancaman kas defisit).
3. **Nilai Ambang Batas Approval Pengadaan (F2)**: Berapa batas nominal pengajuan belanja yang membutuhkan persetujuan berjenjang (misal: Rp 5.000.000) untuk acuan detektor pemecahan transaksi?
4. **Data Awal Standar Bahan (BOM)**: Apakah dapat disediakan nilai rata-rata konsumsi awal per potong jersey (contoh: kain 1,5 meter/pcs, kertas sublim 1,1 lembar/pcs, tinta 8 ml/pcs) agar modul efisiensi material dapat langsung diaktifkan?
