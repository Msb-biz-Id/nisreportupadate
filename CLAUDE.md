# CLAUDE.md — ProTrack

Referensi cepat untuk Claude (atau dev manusia) yang melanjutkan project ini.

## Project Overview

**ProTrack** = Sistem Manajemen Order Multi-Brand untuk bisnis apparel/jersey custom di Indonesia. Spec lengkap di [docs/BRD.md](docs/BRD.md) (~4000 baris).

**Tech stack:**
- Backend: Laravel 12 + PHP 8.2/8.3 + MariaDB
- Frontend: Inertia.js + React 18 + Tailwind + shadcn-style components
- Auth: Laravel Breeze + Spatie Permission
- Charts: ApexCharts (lazy-loaded)
- PDF: barryvdh/laravel-dompdf
- Excel: maatwebsite/excel
- AI: Gemini API
- Notif: Sidobe (WhatsApp) + Telegram Bot

## Status Implementasi

| Phase | Status | Highlight |
|---|---|---|
| 1. Foundation | ✅ | Auth, RBAC, Brand & User mgmt, Brand switcher, layout responsive |
| 2. Master Data | ✅ | 15 master via [MasterRegistry](app/Support/MasterRegistry.php) + Indonesia regions (laravolt) |
| 3. Order Management | ✅ | PO lifecycle, draft→publish→produksi→kirim, lock/unlock, repeat, refund, tracking publik |
| 4. Dashboard & Visualisasi | ✅ | 5 dashboard role-specific via [DashboardService](app/Services/DashboardService.php) |
| 5. Reports & Export | ✅ | 11 laporan via [ReportRegistry](app/Support/ReportRegistry.php) + Excel/PDF, PDF SPK, PDF Invoice + QR |
| 6. Admin Tools | ✅ | Gemini AI (5 tools), Sidobe WA, Telegram Bot, scheduled reports |
| 7. Production Readiness | ✅ | Tests (64+), DEPLOYMENT.md, .env.production.example |
| 4.5 Polish Dashboard | ⏳ | Gantt, drag-drop kanban, Reverb realtime (deferred) |
| 3.5 Polish Order | ⏳ | Cropper.js, paste-from-Excel size (deferred) |

## Arsitektur Penting

### Multi-Brand Isolation
Tiap brand punya master data sendiri. Akses dikontrol via:
- [`App\Support\BrandContext`](app/Support/BrandContext.php) — resolve current brand dari session
- Pivot `user_brand_access` — user → brand many-to-many dengan `is_default` flag
- Helper `User::hasAccessToBrand()` + scope `forBrand()` di models
- Reseller pakai master data dengan `brand_id = null` (global)

### RBAC
- [`RolePermissionSeeder`](database/seeders/RolePermissionSeeder.php) — 6 role + 33 permission
- Superadmin bypass via `Gate::before` di [AppServiceProvider](app/Providers/AppServiceProvider.php)
- Controllers pakai `Gate::authorize('xxx.yyy')`

### Registry Pattern (kurang kode, banyak fitur)
- [`MasterRegistry`](app/Support/MasterRegistry.php) — 14 master data dari 1 controller (generic CRUD)
- [`ReportRegistry`](app/Support/ReportRegistry.php) — 11 laporan dari 1 controller (filter + Excel + PDF)
- Tambah master/report baru = tambah entry di registry + 1 method di runner

### Auto-recording Finance via Observer
- [`OrderObserver`](app/Observers/OrderObserver.php) — PO `draft → published` auto-insert ke `pemasukan`
- [`RefundObserver`](app/Observers/RefundObserver.php) — Refund `→ published` auto-insert ke `pengeluaran`
- Kategori system di-create lazy jika belum ada
- Idempotent (cek existing row sebelum insert)

### PO Lifecycle
```
draft → published → on_progress → selesai_produksi → siap_dikirim → sudah_dikirim
                                                          ↘ delay (deadline lewat)
                                                          ↘ hold (manual)
```
- [`POStatusManager`](app/Services/POStatusManager.php) — handle publish, auto-init progress details, auto-lock, status recalc, unlock + change log
- Auto-lock saat tahap pertama jadi `on_progress`
- Auto-status: PACKING selesai → siap_dikirim; SENDING selesai → sudah_dikirim

### AI Mock Fallback
- [`GeminiClient`](app/Services/Ai/GeminiClient.php) — kalau API key kosong, return placeholder text. UI tetap jalan, tidak ada panggilan API.
- Sama untuk [`SidobeClient`](app/Services/Notifications/SidobeClient.php) + [`TelegramClient`](app/Services/Notifications/TelegramClient.php)
- Settings ter-encrypt via Crypt facade di [`SystemSetting`](app/Models/Settings/SystemSetting.php)

## Layout File

```
app/
├── Console/Commands/SendScheduledReport.php  # cron: reports:send harian|mingguan|bulanan
├── Exports/GenericReportExport.php           # universal Excel exporter
├── Http/Controllers/
│   ├── BrandController, UserController       # Phase 1
│   ├── DashboardController                   # dispatch ke 5 view per role
│   ├── Master/                               # MasterController generic + CustomerController khusus + RegionController
│   ├── Order/                                # OrderController, ProductionController, InvoiceController, RefundController, TrackingController
│   ├── ReportController                      # dispatch via ReportRegistry
│   ├── SettingsController                    # AI/WA/TG config
│   └── Tools/AiToolsController               # 5 AI tools (mock fallback)
├── Models/
│   ├── Brand, User, UserBrandAccess         # Phase 1
│   ├── Concerns/HasUuidAndSoftDeletes        # shared trait
│   ├── Finance/{KategoriPemasukan,KategoriPengeluaran,Pemasukan,Pengeluaran}
│   ├── Master/{BahanKain,Logo,Resleting,Printing,PaketOrder,TipeOrder,Size,PolaJahitan,BankAccount,Progress,KategoriOrder,SumberOrder,CustomerType,Product,Customer}
│   ├── Order/{Order,OrderItem,OrderNameset,OrderPayment,OrderProgressDetail,POLockStatus,POChangeLog,Rijek,Invoice,InvoiceItem,Refund}
│   ├── Settings/SystemSetting
│   └── AiToolLog
├── Observers/{OrderObserver,RefundObserver}  # auto-finance recording
├── Services/
│   ├── DashboardService                      # query agregat per role
│   ├── NumberGenerator                       # PO/INV/REF numbering
│   ├── POStatusManager                       # lifecycle + auto-lock
│   ├── Ai/{GeminiClient,AiToolService}       # Phase 6
│   ├── Notifications/{SidobeClient,TelegramClient,NotificationDispatcher}
│   └── Reports/ReportRunner                  # 11 query aggregator
└── Support/{BrandContext,MasterRegistry,ReportRegistry}

resources/js/
├── Components/
│   ├── ui/                                   # shadcn-style: button/card/dialog/sheet/select/dll
│   ├── Chart.jsx                             # ApexCharts wrapper (lazy)
│   ├── Widgets.jsx                           # StatCard, StatusBreakdown, POListWidget, TopList
│   └── RegionPicker.jsx                      # cascading 4-level
├── Layouts/AppLayout.jsx                     # sidebar + topbar + brand switcher + toast
└── Pages/
    ├── Dashboard.jsx + Dashboards/{AdminBrand,AdminProduksi,Superadmin,Owner,Finance}.jsx
    ├── Brand/Index.jsx, User/Index.jsx
    ├── Master/Index.jsx (generic) + Master/Customer/Index.jsx (special dengan RegionPicker)
    ├── Order/{Index,Form,Preview}.jsx
    ├── Production/{Kanban,Progress}.jsx
    ├── Finance/{InvoiceIndex,RefundIndex}.jsx
    ├── Report/Show.jsx (generic)
    ├── Public/{Track,Invoice}.jsx
    ├── Tools/{AiIndex,AiToolPage}.jsx
    └── Settings/Integrations.jsx

resources/views/pdf/
├── report.blade.php    # universal Excel/PDF report
├── spk.blade.php       # SPK A4 portrait
└── invoice.blade.php   # Invoice publik dengan QR
```

## Common Dev Commands

```bash
# Dev server
php artisan serve              # backend :8000
npm run dev                    # vite HMR :5173

# Database
php artisan migrate:fresh --seed  # reset + seed semua (jaga Indonesia regions hilang)
php artisan laravolt:indonesia:seed  # re-seed regions setelah migrate:fresh

# Tests
php artisan test               # full suite (butuh DB protrack_test)
php artisan test --filter=AuthFlowTest
php artisan test --parallel    # paralel (lebih cepat)

# Build
npm run build                  # production assets

# Scheduled
php artisan reports:send harian   # manual trigger
php artisan schedule:work         # development scheduler
php artisan schedule:list         # lihat semua scheduled tasks
```

## Akun Default (Dev Seeded)

Semua password: `password`

| Email | Role | Brand |
|---|---|---|
| superadmin@nisreport.local | Superadmin | All (SHU + NIS) |
| owner@nisreport.local | Owner | SHU (default) + NIS |
| admin.shu@nisreport.local | Admin Brand | SHU |
| admin.nis@nisreport.local | Admin Brand | NIS |
| reseller@nisreport.local | Reseller | SHU |
| produksi@nisreport.local | Admin Produksi | SHU |
| keuangan@nisreport.local | Admin Keuangan | SHU + NIS |

## Konvensi Coding di Project Ini

1. **UUID untuk semua master/order/finance tables** (kecuali users + Spatie tables yang pakai bigint)
2. **Snake_case** untuk field DB & PHP, **camelCase** untuk PHP method, **PascalCase** untuk class
3. **`scope` method** pada model: `active()`, `forBrand($brandId)` untuk reusable query
4. **Brand-scoped query** selalu lewat `BrandContext::current($request)` atau `forBrand()` scope
5. **Auth via Gate** (`Gate::authorize('xxx.yyy')`) — Spatie permission name = `resource.action`
6. **Form validation di controller** (bukan FormRequest terpisah, kecuali kompleks)
7. **Inertia response** selalu via `Inertia::render('Path/Component', ['key' => value])`
8. **Frontend**: components di `@/Components/ui/*`, pages di `@/Pages/*`, layouts di `@/Layouts/*`
9. **Test**: pakai `RefreshDatabase` + helper `$this->makeBrand()` / `$this->makeUser($role, $brands)` / `$this->actingAsWithBrand($user, $brand)`
10. **Mock fallback** untuk integrasi pihak ke-3 — UI tetap functional tanpa API key

## Cara Menambah Fitur

### Menambah master data baru (generic)
1. Buat migration + model di `app/Models/Master/Xxx.php`
2. Tambah entry di [`MasterRegistry::all()`](app/Support/MasterRegistry.php) — config slug, label, fields, columns
3. Tambah item di sidebar `MASTER_ITEMS` di [AppLayout](resources/js/Layouts/AppLayout.jsx)
4. Selesai. Generic [Master/Index.jsx](resources/js/Pages/Master/Index.jsx) menghandle CRUD.

### Menambah laporan baru
1. Tambah entry di [`ReportRegistry::all()`](app/Support/ReportRegistry.php) — slug, columns, filters, chart config
2. Tambah method di [`ReportRunner`](app/Services/Reports/ReportRunner.php) dengan slug yang sama
3. Tambah link di sidebar `REPORT_ITEMS` di AppLayout
4. Excel + PDF export otomatis jalan dari controller generic.

### Menambah role/permission baru
1. Update [`RolePermissionSeeder`](database/seeders/RolePermissionSeeder.php) (tambah permission ke array `$permissions` + assign ke role yang sesuai)
2. Update `ROLE_LABELS` di [lib/utils.js](resources/js/lib/utils.js)
3. Update menu logic di AppLayout `buildMenu()` kalau perlu visibility-based

### Menambah AI tool baru
1. Tambah entry di [`AiToolService::tools()`](app/Services/Ai/AiToolService.php)
2. Tambah method `xxxPrompt(array $i)` di service yang sama (build Gemini prompt)
3. Tambah field config di [`AiToolsController::fieldsFor()`](app/Http/Controllers/Tools/AiToolsController.php)
4. UI generic — tidak perlu page baru.

## Apa yang Tidak Diimplementasi

Berdasarkan BRD lengkap, beberapa fitur **defer** untuk Phase 3.5 / 4.5 polish:

- **Cropper.js image upload** untuk gambar desain PO (sekarang text URL)
- **Paste-from-Excel size input** (sekarang baris manual)
- **Multi-variant product module** dengan modular UI (sekarang single variant per item)
- **PDF SPK super-detailed** sesuai layout BRD 8.2.3 (sekarang clean A4 functional)
- **Gantt chart** produksi (sekarang Kanban only)
- **Drag-drop Kanban** (sekarang static + tombol Update)
- **Realtime Reverb WebSocket** (sekarang static, refresh manual)
- **Comparison Report** cross-brand cross-period
- **Heatmap peak hours**
- **Audit log lengkap** (sekarang ada `po_change_logs` tapi tidak global)
- **Notification settings per-user**
- **2FA TOTP** (field sudah ada di DB)
- **Mobile push notifications**

Semua ini bisa di-add tanpa overhaul karena registry pattern + clean separation. Tinggal sambung integrasi.

## Database — Module Tables Mapping

```
brands, users, user_brand_access, password_reset_tokens, sessions
roles, permissions, model_has_*, role_has_*  (Spatie)
indonesia_provinces, indonesia_cities, indonesia_districts, indonesia_villages (laravolt)

-- Master Data (Phase 2)
bahan_kains, logos, resletings, printings, paket_orders, tipe_orders     [global]
sizes, pola_jahitans, progress                                            [global]
bank_accounts                                                             [brand-scoped]
kategori_orders, sumber_orders, customer_types                            [brand-nullable]
products, customers                                                       [brand-nullable]

-- Order (Phase 3)
orders, order_items, order_namesets, order_payments
order_progress_details, po_lock_status, po_change_logs, rijeks
invoices, invoice_items, refunds

-- Finance (Phase 5)
kategori_pemasukan, kategori_pengeluaran, pemasukan, pengeluaran

-- Settings (Phase 6)
system_settings, ai_tool_logs
```

## Penting: Hal yang Tidak Boleh Direvert

- **Pengecekan brand_id ambiguous** di query dengan JOIN — selalu prefix `orders.brand_id` (BRD bug #1)
- **`$table = 'po_change_logs'`** explicit di POChangeLog model (Laravel snake_case bug)
- **`$table = 'po_lock_status'`** explicit di POLockStatus model
- **Observer auto-record** Pemasukan/Pengeluaran — idempotent, jangan dibikin trigger ulang
- **Indonesia seed** harus dijalankan terpisah setelah migrate:fresh karena RefreshDatabase mereset semua

---

Phase 7 selesai. Next iteration: pilih polish (Phase 3.5/4.5) atau fitur baru sesuai BRD yang belum ada.
