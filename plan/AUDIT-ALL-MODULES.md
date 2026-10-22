# Audit Total 4 Modul: Order, Keuangan, HCM, Purchasing

> Tanggal: 2026-10-22. Metode: baca file langsung (subagent read-only Order/Keuangan/HCM + verifikasi mandiri Purchasing).
> Scope: `app/Models`, `app/Http/Controllers`, `app/Observers`, `routes`, `resources/js/Pages`, `tests/Feature`.
> Auth global: `routes/web.php:72` — semua route bisnis di dalam `Route::middleware(['auth','verified'])`; route purchasing di-require di dalam grup auth (`web.php:505`).

---

## 1. Skor Ringkas

| Modul | Skor | Test | Kesimpulan 1 kalimat |
|---|---|---|---|
| Order | **74/100** | ~86 | Lifecycle + RBAC mayoritas rapi; tertahan 2 lubang brand-check + 1 store tanpa Gate. |
| Keuangan | **62/100** | 18 murni + 9 di OrderLifecycle | Ledger tutupi lifecycle; tapi pendapatan ganda by-design + 8 method tanpa brand-scope. |
| HCM | **68/100** | 29 (6 file) | Hire-to-retire jalan + double-sign; tapi klaim plan fiktif (loan/kasbon) + 3 risiko integritas. |
| Purchasing | **71/100** | 9 (1 file) | Double sign-off + kode aset + stok serve dengan baik; tapi 3 controller tanpa Gate + race code gen. |
| **Rata-rata** | **69/100** | **~142** | Fondasi kuat, pola berulang rapi (Gate + FormRequest + Service + Observer). 10 perbaikan kritis di bawah. |

---

## 2. Inventaris Fakta per Modul

### 2.1. ORDER — 13 model, 6 controller, ~60 route, 10 file UI, ~86 test

**Model** (`app/Models/Order/`): Order (9 status: draft→published→on_progress→selesai_produksi→siap_dikirim→sudah_dikirim + delay/hold/selesai; relasi 20+; scope `forBrand`/`published`), OrderItem, OrderNameset, OrderPayment, OrderProgressDetail (pending/on_progress/selesai/skipped), POLockStatus (`$table` eksplisit), POChangeLog (`$table` eksplisit), POVersion, Rijek (pending/proses/selesai), Invoice (7 status), InvoiceItem, Refund (5 status, 7 jenis masalah), DesignDeposit.

**Controller**: OrderController (29 method), ProductionController (9: gantt/kanban/progress/update/bulk/rijek×3/moveStatus), InvoiceController (15), RefundController (5), DesignDepositController (5), TrackingController (2).

**Route** (`routes/web.php:126-219`): publik throttle 6 (`/track`, `/track/{noPo}`, `/invoice/{n}`+pdf, `/fo/{noPo}`+pdf); auth: orders/ 26, produksi/ 9, invoices/ 14, design-deposits/ 4 (tanpa DELETE), refunds/ 5.

**UI**: Order/Index, Form (2676 baris, paste-nameset), Preview (2289, unlock/relock dialog), FoPreview; Production/Kanban, Progress, Gantt; Public/Track, TrackIndex, Invoice.

**Test ~86**: OrderLifecycle 30, Shipping 4, CleanLogs 1, POExport 3, Production 9, KanbanMove 8, Gantt 5, Lateness 2, Invoice 15, PaymentNotif 3, PublicAccess 6.

### 2.2. KEUANGAN — 9 model, 5 controller-area, ~27 route, 5 file UI, 18 test murni

**Model**: Pemasukan, Pengeluaran, KategoriPemasukan, KategoriPengeluaran (+parent/children), MasterJenisPembayaran (**global tanpa brand_id**), Invoice+Item, Refund, OrderPayment, DesignDeposit.

**Controller**: InvoiceController (1359 baris), RefundController (412), DesignDepositController (205), MasterJenisPembayaranController (74), + OrderController finance-adjacent (addPayment/bypassDp/markLunas/publish).

**Route**: invoices.* 14, refunds.* 5, design-deposits.* 4, master-pembayaran.* 4, publik `/invoice/{n}`, cron `invoices:send-reminders` 09:00. **Tanpa CRUD manual pemasukan/pengeluaran** — semua via observer.

**Observer** (`AppServiceProvider:95-97`): OrderObserver (draft→publish→Pemasukan `total_tagihan`, exists-guard), RefundObserver (published→Pengeluaran + auto-create OrderPayment return **tanpa guard**, anti-ganda hanya via substring notes), OrderPaymentObserver (verified→ledger via `source_payment_id`).

**UI** (`Pages/Finance/`): InvoiceIndex, InvoiceList (~2000 baris), PaymentsPending, RefundIndex, MasterJenisPembayaran.

**Test**: InvoiceTest 15 + PaymentNotification 3 = 18; +9 finance di OrderLifecycle; suite total ~44 file Feature.

### 2.3. HCM — 29 model, 22 controller (146 method), ~146 route, 26 file UI, 29 test

**Model** (`app/Models/Hcm/`, 29 file, **0 SoftDeletes, 0 UUID, PK integer**): Employee core (nik_ktp unique; hasMany ke contracts/attendances/leaves/overtimes/meal-items/rewards/exit-permits/deductions/payroll-items/rosters), Intern, Onboarding, Offboarding, Contract, Compensation, CompensationHistory; Attendance (unique date+employee), WorkShift, ShiftRoster, LeaveRequest, OfficeExitPermit; Payroll (+items, hcm/finance sign), SalaryDeduction, OvertimeBatch/Overtime, MealAllowanceBatch/Item, EmployeeReward; JobPosting, JobApplicant, ApplicantInterview; AgendaLetter, InternalDocument, ExternalLetter, CompanyEvent; MasterCategory, MasterOption (self parent).

**Controller** (22, 146 method): Employee 12, Attendance 9, Overtime 13, MealAllowance 8, Payroll 7, Recruitment 14, Pdf 13, Shift 9, Compensation 6, Contract 5, SalaryDeduction 5, LeaveRequest 5, MasterData 5, Reward 6, Event 4, Letter 4, Document 4, ExternalLetter 4, ExitPermit 4 (tanpa index), Dashboard 1, Setting 5, PublicCareer 3.

**Route** (~146 di `web.php` + 4 console): publik 6 (karir ×3, slip verify ×3); grup hcm: dashboard 1, master-data 5, employees 14, contracts 5, compensations 6, salary-deductions 5, payroll 9, attendance 9, exit-permits 4, shifts 9, leaves 5, overtime 15, meal-allowance 10, rewards 6, events 4, recruitment 16, letters 4, documents 4, external-letters 4, settings 5. Console: daily-alerts 08:30, overtime-reminder, event-reminders.

**UI** (26 file, 18 subfolder): Attendance 4, Recruitment 3, Employees/Payroll/Overtime/MealAllowance @2, 11 subfolder @1.

**Test** (6 file, 29 test): Payroll 7, OvertimeAndRecruitment 10, AttendanceExportAndWa 4, SlipAndEmail 4, EmployeeMasterData 2, PdfViewer 2.

**Plan vs realita** (`plan/HRIS.md` 1134 baris; `plan/AUDIT_GAP_HCM.md` klaim "100% SELESAI 30 Sep 2026"): 3 gap — (a) modul Pinjaman+Kasbon "✅" tapi **nol file** (grep loan|kasbon di Models/Hcm kosong); (b) kuota cuti 12 hari "✅" tapi migrasi tanpa kolom quota, approve tanpa cek; (c) "PAID_COMPLETED kunci permanen" tapi store regen hanya tolak PAID, APPROVED_BY_HCM bisa didowngrade ke DRAFT + items dihapus.

### 2.4. PURCHASING — 13 model, 8 controller (50 method), ~42 route, 22 file UI, 9 test

**Model** (`app/Models/Purchasing/`, 13 file): PurchasingOrder (route key `po_number`, resolve `po_number|uuid|id`; relasi vendor/requester/location/itemCategory/picApprover/financeApprover/creator/payments/items/assets), PurchasingPayment, PurchasingOrderItem, PurchasingVendor (+Contacts multi-PIC + primary), PurchasingAsset (route key `asset_code`), PurchasingAssetMaintenance/Mutation/Retirement, PurchasingMaterialStock, PurchasingMaterialUsage, PurchasingMasterOption, PurchasingAssetSuggestion.

**Controller** (8, 50 method): Order 13 (index/create/store/show/edit/update/destroy/approve/disburse/realize/settle/history), Payment 2 (storeTerms/pay), Asset 8, Material 6 (index/storeStock/updateStock/storeTransaction/storeBulk/destroyTransaction), Dashboard 1, Report 3, Vendor 7, MasterOption 5 (+toggle).

**Route** (`routes/purchasing.php`, 42): orders/ 14, payments.pay 1, master-data/ 5, vendors/ 7, assets/ 7, materials/ 5, reports/ 3.

**Service** (6): AssetCodeService (`[Kategori].[Dept].[Tahun3Digit].[Urut3Digit]`), AssetService, HrisService, PaymentService, DashboardService, OrderService.

**UI** (22 file): Orders 7 (Index/Create/Edit/Show + 3 komponen), Assets 5, Materials 5 (termasuk BulkTransactionModal baru), Dashboard 1, MasterData 1, Vendors 1, Reports 1.

**Test**: PurchasingEndToEndTest 9 (akses, lifecycle double-sign-off, coding+mutasi, stok, reports, TOP, vendor multi-PIC, capex→asset, multi-item split terms).

---

## 3. Temuan Lintas Modul (prioritas)

### KRITIS (perbaiki sebelum rilis berikutnya)

| # | Temuan | Lokasi | Dampak |
|---|---|---|---|
| C1 | **8 method Invoice tanpa brand-scope** — createFromOrder, validateInvoice, cancelValidation, publish, sendWhatsapp, verifyPayment, updatePayment, destroyPayment cek role `admin_keuangan` saja | `Order/InvoiceController.php:716,849,1004,1039,1074,1105,1148,1306` | Admin keuangan brand A kelola uang brand B via enumerasi ID. Refund store (:164) sudah ada `hasAccessToBrand` — tiru polanya. |
| C2 | **Double-count pendapatan** — Pemasukan 'PO Published' = `total_tagihan` + tiap payment verified catat lagi 'Pembayaran PO' | `Observers/OrderObserver.php:105` + `Observers/OrderPaymentObserver.php:287-297` | `sum(pemasukan)` ≈ 2× riil. Putuskan: PO Published = piutang (bukan revenue), atau tandai kategori agar laporan exclude salah satu. |
| C3 | **Purchasing tanpa Gate (3 controller)** — MaterialController (6 method), AssetController (8 method), ReportController (3 method): **nol `Gate::`/`authorize`** | `Purchasing/PurchasingMaterialController.php`, `PurchasingAssetController.php`, `PurchasingReportController.php` | Siapa pun login (termasuk reseller/karyawan) bisa ubah stok/aset/lihat laporan. Tambah `Gate::authorize('purchasing.view'/'purchasing.manage-orders')` seperti OrderController. |
| C4 | **MasterOption store/update tanpa Gate** — destroy/toggle ada, store/update tidak | `Purchasing/PurchasingMasterOptionController.php:79,93` | User view-only ubah dropdown global (kategori item, lokasi, satuan). Tambah `purchasing.manage-master`. |
| C5 | **DesignDeposit::store tanpa Gate/brand-check** | `Order/DesignDepositController.php:24-65` | User mana pun buat deposit untuk brand arbitrari. |
| C6 | **updateShipping tanpa brand-check** — hanya `can(order.update\|production.update-progress)`, bypass lock by-design | `Order/OrderController.php:2401-2410` | Cross-brand update ekspedisi/resi + sync invoice. Tambah `guardBrandOwnership` seperti updateTimeline (:1872). |

### TINGGI (perbaiki sprint ini)

| # | Temuan | Lokasi | Dampak |
|---|---|---|---|
| H1 | **Refund return-payment tanpa guard + anti-ganda via substring notes** | `Observers/RefundObserver.php:68-80` + `OrderPaymentObserver.php:80` | Re-publish / ubah wording notes → Pengeluaran ganda + return duplikat tak terbatas. Guard by `(refund_id)` atau unique. |
| H2 | **Semua idempotency tanpa unique DB** — exists-check tanpa lock/transaksi di 3 observer + pre-check invoice/refund | Observer ×3; `InvoiceController:722`; `RefundController:234` | Double-click / race → duplikat ledger. Tambah unique index (`source_payment_id`, `order_id+kategori`, `refund_id+kategori`, `order_id` di invoices). |
| H3 | **MasterJenisPembayaran CRUD pakai `finance.view`** | `Finance/MasterJenisPembayaranController.php:34,51,68` | View-only ubah master global yang drive mapping payment_type semua brand. Ganti ke permission manage. |
| H4 | **Payroll regen downgrade** — hanya tolak PAID_COMPLETED; APPROVED_BY_HCM → DRAFT + items dihapus | `Hcm/HcmPayrollController.php:104-125` | Tanda tangan HCM hilang tanpa jejak. Tolak regen bila status != DRAFT_HCM. |
| H5 | **SoD payroll lemah** — signHcm+signFinance sama-sama `hcm.manage-compensation`; overtime/meal pakai 2 permission beda | `Hcm/HcmPayrollController.php:314-338` vs `HcmOvertimeController:451,486,504` | Satu role sign dua kali. Samakan pola overtime/meal. |
| H6 | **Hapus employee cascade ke seluruh histori + 0 soft delete di 29 model HCM** | `Hcm/HcmEmployeeController.php:773-775`; migrasi `cascadeOnDelete` | Hapus 1 karyawan → gaji/cuti/absen lenyap. Ganti restrict + tambah SoftDeletes. |
| H7 | **Cuti tanpa kuota/overlap-check** — approve `updateOrCreate` attendance; reject/destroy tak bersihkan | `Hcm/HcmLeaveRequestController.php:151-244` | Dobel cuti + attendance yatim. Tambah cek overlap + kolom saldo. |
| H8 | **Refund LIKE tanpa brand-check** — resolve order via `LIKE %input%` sebelum cek | `Order/RefundController.php:120-133,276+` | Input parsial tempel refund ke PO orang lain. Cek brand dulu. |
| H9 | **Tracking publik null-crash** — `$order->pelanggan->nama` tanpa guard | `Order/TrackingController.php:121-124` | Pelanggan terhapus → 500 di route publik. Null-safe + withDefault. |
| H10 | **destroyPayment fallback hapus by nominal+tanggal** | `Order/InvoiceController.php:1325-1340` | Bisa hapus baris payment lain yang kebetulan sama. Hapus fallback, wajibkan `source_payment_id`. |

### SEDANG (backlog terjadwal)

| # | Temuan | Lokasi |
|---|---|---|
| M1 | Tiga rumus neto berbeda (Model vs Controller vs JSX) — selisih sen/status inkonsisten | `Order/Invoice.php:66-79`; `InvoiceController:468-511`; `InvoiceList.jsx:207-242` |
| M2 | `min:0` di refund/payment/deposit → Rp0 masuk ledger + notif | `RefundController:144`; `OrderController:1474`; `InvoiceController:1157`; `DesignDepositController:30` |
| M3 | Mapping payment_type by string nama (+typo `'Refurn'`) — rename master jatuhkan ke 'lainnya' | `OrderPaymentObserver:18-42`; `InvoiceController:1192-1202` |
| M4 | Role-check (bukan permission Spatie) di 8 method Invoice + markLunas — peran custom terblokir | `InvoiceController` ×8; `OrderController:1923-1926` |
| M5 | Fillable Order sensitif (status_po/is_lunas/bypass/total_tagihan) — andalkan whitelist validatePayload | `Order/Order.php:45-61` |
| M6 | DesignDeposit convert self-verify payment (verified langsung oleh requester) | `DesignDepositController:88-132` |
| M7 | NIK update sudah pakai `ignore($id)` — AMAN, tutup H5-sementara | `HcmEmployeeController:386` ✔ |
| M8 | DesignDeposit::destroy tanpa route + frontend panggil route tak ada — dead code | `DesignDepositController:193-204`; `InvoiceList.jsx:918` |
| M9 | Asset code gen `count()+1` tanpa lock — race → duplikat kode | `PurchasingMaterialController:214-217` (pola sama di AssetCodeService — verifikasi lock) |
| M10 | Publik `/karir/apply` tanpa captcha/rate-limit; OfficeExitPermit tanpa index | `HcmPublicCareerController:94`; `HcmOfficeExitPermitController` |
| M11 | Enumerasi no_po/invoice publik (throttle saja, data di-mask — risiko rendah) | `routes/web.php:43-50` |
| M12 | Klaim plan fiktif: modul Pinjaman+Kasbon HCM "✅" tapi nol file | `plan/AUDIT_GAP_HCM.md` vs `app/Models/Hcm/` |

### Terkonfirmasi AMAN (bukan temuan — dicatat agar tak diulang)

* approveUnlock/rejectUnlock/approveRelock/rejectRelock sudah `abort_unless can('order.lock-unlock')` + `guardBrandOwnership` (`OrderController:1208,1259,1354,1405`).
* unlock/relock tanpa Gate by-design (can→langsung, non-can→permohonan) + guard tetap jalan.
* OrderController 19 method + ProductionController 7 method sudah Gate+guard konsisten.
* Invoice publik + tracking memakai masking nama/HP.

---

## 4. Rencana Perbaikan (10 langkah, urut dampak)

1. **C3+C4**: tambah Gate di Purchasing Material/Asset/Report + MasterOption store/update (1 file政策, 30 menit, tanpa migrasi).
2. **C1+C6**: tambah `guardBrandOwnership`/`hasAccessToBrand` di 8 method Invoice + updateShipping (tiru Refund store:164).
3. **H3**: Gate master pembayaran → permission manage (1 baris ×3).
4. **C5**: Gate + brand-check di DesignDeposit::store.
5. **H2**: unique index `source_payment_id` (pemasukan/pengeluaran), `order_id` (invoices), `(refund_id+kategori)`, `(order_id+kategori)` + hapus H10-fallback.
6. **C2**: putuskan semantik PO Published (piutang vs revenue) + sesuaikan ReportRunner/laporan.
7. **H1**: guard refund return-payment by refund_id + hapus cek substring.
8. **H4+H5**: payroll regen-guard + split permission sign (samakan overtime/meal).
9. **H6+H7**: employee destroy → restrict + SoftDeletes bertahap; cuti overlap-check + saldo.
10. **H8+H9+M2**: brand-check refund resolve; null-guard tracking; `min:0` → `min:1`/`gt:0` di 4 lokasi.

Estimasi: langkah 1–4 = 1 hari (tanpa migrasi, test RBAC existing hijau). Langkah 5–7 = 2–3 hari (migrasi unique + backfill dedup). Langkah 8–10 = 3–5 hari. Total **1–2 minggu** untuk nol kritis.
