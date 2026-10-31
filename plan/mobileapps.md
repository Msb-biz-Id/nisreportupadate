# RENCANA MOBILE APPS KARYAWAN (EMPLOYEE SELF-SERVICE) NISGROUP
## Portal Mandiri Karyawan — Absensi, Cuti/Izin, Payroll, Pengajuan Purchasing & Bukti Penyelesaian
### Diselaraskan 100% dengan fakta codebase `reportlaravel` (HCM + Purchasing + PWA existing)

---

## 1. Metadata & Status

- **Nama Produk**: NISStaff / Portal Karyawan (Employee Self-Service).
- **Fokus**: Sisi **karyawan** (bukan admin). Karyawan mengajukan izin/cuti, melihat payroll, melihat data absen, mengajukan pengadaan, dan menyelesaikan bukti pengajuan.
- **Platform (keputusan pemilik produk)**:
  1. **Fase ini: PWA mobile-first** di dalam aplikasi Laravel/Inertia yang sudah ada (installable, portrait, tanpa codebase terpisah).
  2. **Fase lanjut: React Native** yang mengonsumsi **API JSON v1 (Sanctum)** — dirancang sejak awal agar tidak rewrite.
- **Login (keputusan pemilik produk)**:
  - **URL login berbeda** dari portal admin (mis. `/app/login`).
  - **Identitas login = data karyawan** (Kode Karyawan / NIK).
  - **Password default = NIK** (`hcm_employees.nik_ktp`), **wajib ganti saat login pertama**.
- **Status Progres Keseluruhan**: **0% (Roadmap Disahkan, Eksekusi Belum Mulai)**.
- **Prinsip Eksekusi**: *Strict Phase-Gate Execution* — fase harus 100% tuntas & teruji sebelum lanjut.
- **Dokumen terkait**: [`plan/HRIS.md`](HRIS.md), [`plan/Purchasing.md`](Purchasing.md), [`plan/AUDIT_GAP_HCM.md`](AUDIT_GAP_HCM.md), [`CLAUDE.md`](../CLAUDE.md).

---

## 2. Ringkasan Audit Sistem Saat Ini (Fakta Aktual Codebase)

Mobile app **tidak dibangun dari nol** — mayoritas domain sudah ada. Yang kurang adalah *jalur akses karyawan* + *lapisan PWA/API mobile*.

### 2.1. Aset yang Bisa Dipakai Ulang Langsung

| Kapabilitas | Bukti di Codebase | Dipakai untuk |
| :--- | :--- | :--- |
| Master Karyawan | `app/Models/Hcm/HcmEmployee.php` (route key `employee_code`, scope `regular()`/`interns()`) | Identitas login karyawan |
| Presensi harian | `HcmAttendance` (`late_minutes`, `shift()`, `scopeForMonth`) | Riwayat & kalender absen |
| Cuti/Izin/Sakit | `HcmLeaveRequest` + `HcmLeaveRequestController` (store/approve/reject, lampiran, sinkron ke presensi) | Pengajuan izin |
| Izin keluar kantor (Gate Pass) | `HcmOfficeExitPermit` (`markReturned`, durasi) | Izin keluar & konfirmasi kembali |
| Lembur | `HcmOvertime` / `HcmOvertimeBatch` | Lihat/ajukan lembur |
| Uang makan | `HcmMealAllowanceItem` / `HcmMealAllowanceBatch` | Rekap uang makan |
| Payroll | `HcmPayroll` + `HcmPayrollItem` (`slip_token`, PDF slip) | Slip gaji karyawan |
| Reward | `HcmEmployeeReward` (`proof_url`) | Apresiasi karyawan |
| Pengajuan pengadaan | `PurchasingOrder` (`requester_employee_id`, status flow, `invoice_file_path`) | Pengajuan purchasing |
| Bukti pembayaran | `PurchasingPayment` (`receipt_file_path`), `PurchasingOrder.invoice_file_path` | Penyelesaian bukti |
| Notifikasi | `App\Models\Notification`, `NotificationController`, `SidobeClient`, `TelegramClient` | Notif pengajuan |
| PWA | `ManifestController` (`/manifest.json`, `display: standalone`, portrait) | Shell installable |
| Auth modern | Sanctum `^4.0` **sudah terinstall**, `TwoFactorController`, `OtpChallengeController` | Token API RN |
| Storage file | `HcmFileStorageService`, route `storage/{path}` | Upload foto/surat/nota |

### 2.2. Matriks Gap (Wajib Ditutup)

| # | Gap | Bukti | Dampak | Prioritas |
| :---: | :--- | :--- | :--- | :---: |
| 1 | Tidak ada tautan `User` ↔ `HcmEmployee` | `hcm_employees` tak punya `user_id` | Karyawan tak bisa login sebagai dirinya | **Kritis** |
| 2 | Belum ada role `karyawan` | `RolePermissionSeeder`: 11 role (superadmin…staff_purchasing), tak ada karyawan | Semua endpoint HCM di-gate `hcm.manage-*` (admin) | **Kritis** |
| 3 | Belum ada `routes/api.php` | `bootstrap/app.php` hanya load `web` + `commands` | React Native nanti tak punya endpoint | **Kritis** |
| 4 | Endpoint self-service tidak ada | `HcmLeaveRequestController::store` butuh `hcm.manage-attendance` & `employee_id` bebas | Karyawan tak bisa ajukan sendiri | **Kritis** |
| 5 | Absen mandiri (clock in/out) tak ada | `HcmAttendanceController` hanya `batchStore`/`setBulk` (admin) | Tak bisa absen dari HP | Tinggi |
| 6 | Lihat slip gaji sendiri tak ada | Hanya verifikasi QR publik (`verifySlipToken`) | Karyawan tak bisa arsip slip | Tinggi |
| 7 | Pengajuan purchasing karyawan tak ada | `PurchasingOrderController` di-gate `purchasing.manage-orders` | Karyawan tak bisa mengajukan | Tinggi |
| 8 | Unggah bukti pengajuan tak ada jalur karyawan | `realize()` di-gate admin | Bukti tak bisa diunggah pengaju | Tinggi |
| 9 | Login portal ganda | `routes/auth.php` satu pintu | Staf & karyawan bercampur | Sedang |
| 10 | Layout mobile-first belum ada | `AppLayout.jsx` desktop (sidebar) | UX HP buruk; wajib mobile-only (bottom nav ala app) sebagai acuan RN | Tinggi |
| 11 | Device token / push native belum ada | — | Belum perlu sampai RN | Rendah |

---

## 3. Keputusan Arsitektur

### 3.1. Strategi Bertahap (PWA → React Native)
- **Sumber kebenaran logika = Service Layer.** Semua aturan bisnis ESS ditaruh di `app/Services/Employee/*` (mis. `EmployeeSelfService`, `PurchaseRequestService`). Controller Inertia (PWA) dan Controller API (RN) hanya pembungkus tipis → mencegah rewrite saat pindah ke React Native.
- **Fase PWA (sekarang)**: halaman Inertia mobile-first di `resources/js/Pages/Mobile/*` + `MobileLayout.jsx`, di-serve di prefix terpisah.
- **Fase RN (nanti)**: aktifkan `routes/api.php` + `app/Http/Controllers/Api/V1/*` (Sanctum) yang memanggil service yang sama. PWA tetap jalan.

### 3.2. Portal Terpisah
- Prefix karyawan: **`/app`** (dapat dikonfigurasi; alternatif `/staff`).
- Route login karyawan: **`/app/login`** (alur terpisah dari `/login` admin).
- Middleware baru: **`employee.portal`** → pastikan user ber-role `karyawan` **dan** punya `hcm_employees.user_id` valid; sediakan `currentEmployee` via konteks.

### 3.3. Model Otorisasi
- Role baru **`karyawan`** dengan permission ber-sufiks `.self` (lihat §5.1). Dilarang memakai permission admin.
- `Gate::before` superadmin (sudah ada) tetap berlaku.
- Setiap query **wajib** di-scope ke `currentEmployee->id` (tanpa exception) → Zero IDOR.

---

## 4. Ruang Lingkup Modul (Fokus Karyawan)

| # | Modul | Isi | Sumber Backend |
| :---: | :--- | :--- | :--- |
| 1 | **Beranda** | **Kartu Absen hari ini (status berangkat/pulang) + tombol aksi cepat Absen Berangkat/Pulang**, sisa cuti, slip terbaru, pengajuan pending, notifikasi | agregasi services |
| 2 | **Absensi** | **Absen Berangkat & Absen Pulang** mandiri **+ verifikasi wajah + geofence lokasi ruko/gudang + anti-fake GPS** (berlaku keduanya), **shift dinamis & aturan telat (§7.9)**, riwayat harian, kalender bulanan, rekap | `HcmAttendance`, `HcmShiftRoster`, `HcmWorkShift`, `hcm_locations`, `hcm_employee_face_profiles` |
| 3 | **Cuti & Izin** | Ajukan Cuti Tahunan/Izin/Sakit/Dinas Luar/Cuti Bersama + lampiran surat dokter, riwayat, batal (selama pending) | `HcmLeaveRequest` |
| 4 | **Izin Keluar Kantor** | Ajukan gate pass, tandai "Kembali" | `HcmOfficeExitPermit` |
| 5 | **Lembur** | Lihat riwayat & (opsional) ajukan lembur | `HcmOvertime` |
| 6 | **Uang Makan** | Rekap bulanan & status bayar | `HcmMealAllowanceItem` |
| 7 | **Payroll** | Daftar periode penggajian, rincian item, unduh slip PDF | `HcmPayrollItem` |
| 8 | **Reward** | Riwayat apresiasi & status penerimaan | `HcmEmployeeReward` |
| 9 | **Pengajuan Purchasing** | Buat pengajuan barang/jasa (Opex/Capex), pantau status approval (PIC → Finance), riwayat (record), **+ validasi lokasi/geofence ruko-gudang (Purchasing §F)** | `PurchasingOrder` |
| 10 | **Penyelesaian Bukti** | Unggah nota/invoice/bukti bayar setelah belanja, konfirmasi realisasi/settlement, **+ validasi lokasi/geofence ruko-gudang (Purchasing §F)** | `PurchasingOrder`, `PurchasingPayment` |
| 11 | **Notifikasi** | Inbox pengajuan (disetujui/ditolak/diminta bukti) | `Notification`, Sidobe/Telegram |
| 12 | **Profil** | Data diri, foto, ganti password/PIN, logout | `HcmEmployee`, `User` |

---

## 5. Desain Login & Identitas Karyawan

### 5.1. Role & Permission Baru (`RolePermissionSeeder`)
Role: **`karyawan`** (Employee). Permission baru (prefix `employee.` / `*.self`):

```
employee.self              # akses beranda & modul self-service
attendance.self            # clock in/out + lihat absen sendiri
leave.self                 # ajukan/lihat/batalkan cuti-izin sendiri
exit-permit.self           # ajukan gate pass sendiri
overtime.self              # lihat/ajukan lembur sendiri
meal-allowance.self        # lihat uang makan sendiri
payroll.self               # lihat slip payroll sendiri
reward.self                # lihat reward sendiri
purchasing.request         # buat pengajuan pengadaan
purchasing.proof           # unggah bukti penyelesaian
notification.self          # baca notifikasi sendiri
```

### 5.2. Penautan Akun
- Migrasi: tambah **`hcm_employees.user_id`** (nullable, unique, FK `users`).
- Command `php artisan employees:provision-accounts`:
  - Untuk tiap karyawan aktif tanpa `user_id`: buat `User` (name = `name`, email = `email` atau `{employee_code}@karyawan.local`, is_active = true), assign role `karyawan`, `password` = NIK.
  - Set `hcm_employees.user_id`.
  - Idempotent (aman dijalankan ulang).
- **Bila NIK kosong** → fallback password = `employee_code`; tandai `must_change_password`.

### 5.3. Alur Login Karyawan
1. Buka `/app/login` → input **Kode Karyawan atau NIK** + password.
2. Sistem cari karyawan aktif → resolve `user_id` → autentikasi.
3. Jika `must_change_password` (masih default) → **paksa layar ganti password** sebelum masuk beranda.
4. Buat sesi + catat `last_login_at` / `last_login_ip` (kolom sudah ada).
5. **Hardening**: rate limit login (throttle), lockout setelah N gagal, audit via `ActivityLogger`.
6. **Opsi lanjut**: PIN 6-digit + biometrik (native) untuk membuka app, bukan menggantikan login pertama.

---

## 6. Fondasi Teknis

### 6.1. Backend
- Aktifkan API: `bootstrap/app.php` → `->withRouting(api: __DIR__.'/../routes/api.php', apiPrefix: 'api')`.
- Install Sanctum migration (`personal_access_tokens`) untuk kebutuhan RN (Fase 6); PWA memakai session.
- Service baru: `app/Services/Employee/EmployeeSelfService.php`, `PurchaseRequestService.php`.
- Controller: `app/Http/Controllers/Mobile/*` (PWA) dan `app/Http/Controllers/Api/V1/*` (RN).
- Form Request terdedikasi: `app/Http/Requests/Mobile/*`.
- Middleware: `app/Http/Middleware/EnsureEmployeePortal.php` (alias `employee.portal`).

### 6.2. Frontend PWA
- `resources/js/Layouts/MobileLayout.jsx`: **mobile-only**, bottom-tab bar (Beranda, Absen, Izin, Slip, Profil), top app bar ringkas, safe-area. **Bukan** `AppLayout.jsx` (sidebar desktop).
- Halaman: `resources/js/Pages/Mobile/{Home,Attendance/Clock,Attendance/History,Attendance/Calendar,Leaves,ExitPermits,Payroll,PurchaseRequests,PurchaseRequestDetail,Notifications,Profile}.jsx`.
- PWA: perluas `ManifestController` (`start_url: /app`), tambah `public/sw.js` + registrasi service worker, meta `theme-color`, `apple-mobile-web-app-capable`.
- Ikon & komponen: `lucide-react` (dilarang emoji mentah), pola `@/Components/ui/*` + komponen mobile di `@/Components/Mobile/*`.

### 6.3. Spesifikasi Mobile UI/UX (Mobile-Only) — Acuan React Native
Portal karyawan **wajib 100% mobile-only**: tidak memakai `AppLayout.jsx` (sidebar desktop), tanpa tabel lebar, tanpa layout multi-kolom. Seluruh pengalaman meniru aplikasi native (installable PWA) dan menjadi **kontrak desain** untuk React Native.

**Prinsip:**
- Satu kolom, scroll vertikal, target sentuh ≥ 44px, font ≥ 14px.
- Aman safe-area (notch/home indicator) via `env(safe-area-inset-*)`.
- `display: standalone`, portrait, tanpa address bar saat di-install.
- Tanpa hover-only; ada feedback tekan (active state), pull-to-refresh, skeleton loading.

**Kerangka Shell (ala app):**
- **Splash** singkat (logo + nama) saat boot.
- **Top App Bar**: judul halaman + ikon notifikasi (badge) + avatar/aksi kontekstual.
- **Konten** (scroll area).
- **Bottom Tab Bar** tetap (fixed), 5 tab: **Beranda, Absen, Izin, Slip, Profil**; ikon `lucide-react` + label; state aktif jelas.
- **Tombol aksi utama / FAB** untuk absen cepat (Absen Berangkat/Pulang) di Beranda & Absen.

**Peta Navigasi (tabs → layar):**

| Tab | Layar utama | Layar turunan (stack) |
| :-- | :-- | :-- |
| Beranda | Ringkasan + widget Absen Berangkat/Pulang | Notifikasi |
| Absen | Absen Berangkat/Pulang + status hari ini | Riwayat, Kalender bulanan |
| Izin | Daftar pengajuan + tombol "Ajukan" | Form Cuti/Izin, Detail, Izin Keluar Kantor, Lembur |
| Slip | Daftar payroll | Detail slip + unduh PDF, Uang Makan, Reward |
| Profil | Data karyawan | Ganti password/PIN, Enrollment wajah, Pengaturan |

**Inventaris layar**: `Mobile/Auth/Login`, `ForceChangePassword`, `Home`, `Attendance/Clock`, `Attendance/History`, `Attendance/Calendar`, `Leaves/Index`, `Leaves/Form`, `Leaves/Show`, `ExitPermits/*`, `Overtime/*`, `Payroll/Index`, `Payroll/Show`, `MealAllowance/Index`, `Rewards/Index`, `PurchaseRequests/Index`, `PurchaseRequests/Form`, `PurchaseRequests/Show`, `PurchaseRequests/Proof`, `Notifications/Index`, `Profile/Index`, `Profile/ChangePassword`, `Profile/FaceEnrollment`.

**Komponen bersama** (`resources/js/Components/Mobile/*`): `AppShell`, `TabBar`, `AppBar`, `BottomSheet`, `Card`, `ListItem`, `PullToRefresh`, `Skeleton`, `EmptyState`, `AmountText` (format Rupiah), `StatusBadge`, `CameraCapture` (wajah), `GeofenceNotice`.

**Kontrak untuk React Native** (agar tak rewrite):
- Nama tab, path route, dan **bentuk payload API v1** (Fase 6) dibakukan di sini.
- Komponen dipetakan ke padanan RN (`View`/`Text`/`FlatList`/`Modal`); token warna & spacing ikut `tailwind.config.js`.
- Kamera/geolokasi lewat abstraksi service (web `getUserMedia` → RN `react-native-vision-camera` / `expo-location`).

### 6.4. Master Lokasi Kerja HCM (`hcm_locations`) — Mandiri untuk Absensi

HCM memiliki master lokasi **sendiri dan mandiri** (tidak berbagi tabel dengan Purchasing). Tiap lokasi kerja (ruko/gudang/pabrik) menyimpan `latitude, longitude, radius_m, address, maps_url, geofence_enabled`.

- **Penempatan karyawan**: `hcm_employees.location_id` (FK `hcm_locations`) → grouping karyawan per lokasi.
- **Geofence absensi (utama)**: saat Absen Berangkat/Pulang, **server** menghitung Haversine terhadap lokasi karyawan: `distance_m <= radius_m` → **LULUS**; selebihnya **GAGAL** (detail §7.3).
- **Map picker admin** (Leaflet + OpenStreetMap, tanpa API key) di master HCM untuk menaruh pin + slider radius.
- **Independen dari Purchasing**: Purchasing punya daftar lokasinya **sendiri** (`purchasing_master_options` kategori `location`) sebagai dimensi biaya — **tidak** dipakai absensi (lihat Purchasing §F). Tidak ada ketergantungan silang.
- Service internal HCM: `HcmLocationService` + `GeoService` (Haversine).

---

## 7. Absensi Biometrik: Wajah + Geofence + Anti-Fake GPS

Absensi karyawan wajib lolos **3 lapis verifikasi**, dengan lapis ke-4 sebagai jaring pengaman manusia.

### 7.1. Arsitektur Verifikasi (Defense in Depth)

| Lapis | Komponen | Kapan | Jika gagal |
| :---: | :--- | :--- | :--- |
| **A** | Wajah + Liveness | Clock-in/out | Flag `face` |
| **B** | Geofence lokasi | Clock-in/out | Flag `geo` |
| **C** | Anti-Fake GPS / integritas device | Clock-in/out | Flag `integrity` |
| **D** | Review manual HCM | Asinkron | Antrean verifikasi |

Aturan: **semua lulus → `VERIFIED`** (dihitung sah); ada yang gagal → **`PERLU_VERIFIKASI`** (`is_flagged = true`) dan masuk antrean HCM. Absen tidak ditolak mentah, tetapi tidak sah sampai diverifikasi.

### 7.2. Lapis A — Pengenalan Wajah & Liveness
> Berlaku untuk **absen berangkat** maupun **absen pulang** (verifikasi ulang setiap aksi).
- **Enrollment** (sekali, saat onboarding / menu Profil): ambil 3–5 sampel → hitung *face embedding* di **on-device** (`face-api.js` / MediaPipe / TFJS). Yang dikirim & disimpan ke server hanya **embedding + thumbnail kecil**, bukan video. **Alur lengkap: §7.10.**
- **Verifikasi**: kamera depan → deteksi wajah → **liveness challenge** (kedip / tengok kanan-kiri / senyum acak) → embedding + foto dikirim.
- **Pencocokan**: server hitung *cosine similarity* vs embedding terdaftar; ambang mis. `>= 0.6` lulus. Simpan `face_match_score` & `liveness_score` untuk audit.
- **Anti-spoof wajah**: liveness aktif (challenge gerak); RN nanti + deteksi tekstur/depth.
- Kamera butuh HTTPS + `getUserMedia`; bila kamera tak tersedia → absen `PERLU_VERIFIKASI`.

### 7.3. Lapis B — Geofence Lokasi
- Master lokasi = **`hcm_locations`** (HCM, mandiri) dengan `latitude, longitude, radius_m, address, maps_url`.
- Tiap karyawan punya lokasi kerja **`hcm_employees.location_id`** (FK `hcm_locations`).
- Klien kirim `latitude, longitude, accuracy_m`. **Perhitungan jarak (Haversine) dilakukan server-side** → `distance_m`, `is_within_geofence`.
- **Batas toleransi** = `radius_m` lokasi tsb. `distance_m > radius_m` → **GAGAL** (di luar wilayah).
- Toleransi `accuracy_m` (mis. maks 50 m); akurasi buruk → flag. Waktu **server-authoritative**, bukan dari klien.

### 7.4. Lapis C — Anti-Fake GPS (GPS Spoofing)
> Tidak ada solusi tunggal 100%. Gunakan **berlapis** dan naikkan bukti fisik. PWA **tidak bisa** membaca `isMockProvider`, sehingga sinyal tambahan jadi kunci di fase ini.

| Teknik | PWA sekarang | React Native nanti | Kekuatan |
| :--- | :---: | :---: | :---: |
| Geofence divalidasi server | Ya | Ya | Sedang |
| Cross-check IP geolocation vs GPS + jarak wajar | Ya | Ya | Sedang |
| Deteksi *impossible travel* / lonjakan kecepatan | Ya (server) | Ya | Sedang |
| Clock skew & timestamp server | Ya | Ya | Sedang |
| **QR dinamis di gerbang** (TOTP 30 detik) di-scan saat absen | **Ya** | Ya | **Kuat** |
| `Location.isFromMockProvider()` / `isMock` | Tidak | Ya | Kuat |
| **Play Integrity API / Apple App Attest** (root/emulator/mock) | Tidak | Ya | **Sangat kuat** |
| **BLE beacon** di pintu (opsional) | Tidak | Ya | **Sangat kuat** |

**Gelombang 1 (PWA)**: wajibkan **QR dinamis di gerbang**, digabung geofence + cross-check IP + deteksi kecepatan.
**Gelombang 2 (RN)**: tambah mock-location detection + Play Integrity / App Attest + BLE opsional.

### 7.5. Model Data Tambahan (Absensi)
Kolom baru di `hcm_attendances` (berlaku **berangkat & pulang**):
`check_in_lat, check_in_lng, check_in_accuracy_m, check_in_distance_m, check_in_method, check_in_face_score, check_in_liveness_score, check_out_lat, check_out_lng, check_out_accuracy_m, check_out_distance_m, check_out_method, check_out_face_score, check_out_liveness_score, early_leave_minutes, device_attest_status, ip_address, is_flagged, verification_status, verified_by, verified_at, flag_reasons (json)`.

### 7.6. Alur Keputusan & Audit
Berlaku identik untuk **absen berangkat** dan **absen pulang**:
```
absen berangkat / absen pulang
   → [A wajah+liveness] + [B geofence] + [C integritas]
   ├─ semua lulus → VERIFIED (dihitung sah)
   └─ ada gagal   → PERLU_VERIFIKASI + is_flagged + flag_reasons
                       → antrean HCM (approve/tolak, pola existing)
```
- Absen berangkat: hitung `late_minutes` (via shift). Absen pulang: hitung `early_leave_minutes`.
- Semua mutasi via `ActivityLogger`; simpan skor & alasan flag; **JANGAN** simpan embedding di log.
- Notifikasi karyawan & HCM saat status berubah (Sidobe/Telegram + fallback).

### 7.7. Privasi & Kepatuhan (UU PDP No. 27/2022)
- Data biometrik = **data pribadi spesifik** → wajib **consent eksplisit** saat enrollment (checkbox + teks tujuan).
- Embedding **terenkripsi at-rest**; foto mentah tidak disimpan permanen (hapus setelah verifikasi); retensi terbatas.
- Sediakan hak akses & penghapusan data (termasuk saat offboarding).

### 7.8. Offline & Jaringan Buruk
- Antrean lokal (IndexedDB PWA / SQLite RN), sinkron saat online.
- Timestamp tetap server-authoritative saat sinkron; tandai `synced_offline` untuk review.

### 7.9. Dinamika Shift & Aturan Telat (Dynamic Shift Resolution)

**Aturan inti:** jam telat **TIDAK** dihitung dari jam tetap (mis. 08:00), melainkan dari **shift efektif karyawan pada tanggal absen**. Bila karyawan absen di luar shift efektifnya **tanpa perombakan shift yang disetujui**, maka **tetap dihitung TELAT**.

**Resolusi shift efektif** (server-side, service `ShiftResolver`):
1. `hcm_shift_rosters` untuk (`employee_id`, `roster_date`) → shift hasil penugasan/perombakan harian.
2. Fallback `hcm_employees.default_shift_id`.
3. Fallback shift default perusahaan (setting).
4. Jika shift `is_off_day` → hari libur (absen masuk dianggap lembur/flag, bukan telat).

**Perhitungan (dinamis):**
- **Berangkat**: `threshold = shift.start_time + shift.late_tolerance_minutes`. Bila `clock_in > threshold` → `late_minutes = start_time → clock_in`; selain itu `0`.
- **Pulang**: `clock_out < shift.end_time` → `early_leave_minutes`.
- `is_overnight` → `end_time` dianggap **+1 hari**.
- Saat absen, simpan **snapshot** `shift_id` + `shift_source` (`roster` / `default` / `company`) di `hcm_attendances`, agar rekap historis stabil; resolusi tetap dilakukan ulang secara dinamis pada tanggal absen.
- Accessor `late_minutes` pada `HcmAttendance` diselaraskan agar memakai shift efektif ini (bukan hardcode 08:00).

**Perombakan Shift (Shift Change Request):**
- Tabel baru **`hcm_shift_change_requests`**: `uuid, employee_id, from_shift_id, to_shift_id, effective_date, end_date (nullable), reason, attachment_url, status (PENDING_APPROVAL / APPROVED / REJECTED), reviewed_by, reviewed_at, rejection_reason, created_by`.
- Alur: karyawan ajukan → HCM/supervisor **approve** → sistem menulis `hcm_shift_rosters` untuk rentang tanggal → absen dihitung terhadap **shift baru** (tidak telat).
- **Tanpa persetujuan → roster tidak berubah → absen di luar shift tetap TELAT** (persis aturan di atas).
- Opsional fase lanjut: *shift swap* antar dua karyawan (saling tukar).

**Integrasi:** notifikasi HCM bila `late_minutes` melewati ambang; rekap bulanan menampilkan jumlah telat/pulang cepat per shift.

### 7.10. Alur Pendaftaran Wajah (Face Enrollment)

**Kapan:**
- **Onboarding**: setelah wajib ganti password default → langkah **Enrollment Wajah** (boleh *skip*; bila di-skip, absen mandiri nonaktif sampai enroll, dengan **fallback approval HCM**).
- **Kapan saja**: Profil → *Enroll / Perbarui Wajah*.
- **Re-enroll wajib** bila: `status = REVOKED` (direset HCM), verifikasi sering gagal, atau perubahan wajah signifikan.

**Prasyarat:** consent UU PDP (§7.7) diterima; kamera depan; cahaya cukup; karyawan aktif; **satu profil per karyawan** (`employee_id` unique).

**State profil:** `UNENROLLED → PENDING (mengambil sampel) → ENROLLED`; dapat → `REVOKED` (reset admin) → enroll ulang.

**Langkah (embedding selalu dihitung on-device):**
1. **Consent & informasi** — layar tujuan, retensi, hak akses/hapus → centang setuju → simpan `consent_at` + `consent_version` (bila kebijakan berubah versi, minta consent ulang).
2. **Panduan kamera** — oval/guide frame, wajib 1 wajah, tanpa masker/kacamata gelap; feedback real-time (gelap / kabur / wajah tak terdeteksi).
3. **Ambil 3–5 sampel** dengan variasi (lurus, sedikit kiri/kanan/atas). Tiap sampel divalidasi kualitas (blur, pencahayaan, ukuran) → sampel gagal diulang.
4. **Hitung embedding** di klien (`face-api.js` / MediaPipe / TFJS). **Hanya embedding + thumbnail kecil** dikirim; **video/foto mentah tidak pernah**.
5. **Kirim ke server** — `embedding[]`, `thumbnail` (kecil), `sample_count`, `quality_score`, info device.
6. **Server** — validasi minimal N sampel lolos → simpan ke `hcm_employee_face_profiles` (embedding **terenkripsi**, `status = ENROLLED`, `enrolled_at`, `consent_at/version`); tandai karyawan siap absen. Catat via `ActivityLogger` (**tanpa** embedding).
7. **Konfirmasi** sukses + opsi ulangi / perbarui.

**Pencocokan (saat absen, §7.2):** server hitung *cosine similarity* terhadap template terdaftar (max/mean antar sampel); ambang terkonfigurasi (mis. `>= 0.6`) + **liveness wajib** (challenge gerak acak).

**Reset/Revoke (HCM):** admin dapat `REVOKE` profil (mis. karyawan resign / ganti device) → `revoked_at/revoked_by`; karyawan wajib enroll ulang. **Hak hapus** biometrik saat offboarding (§7.7).

**Fallback (jangan *dead-end*):** bila kamera ditolak atau enroll gagal terus → sediakan jalur absensi alternatif via persetujuan HCM (tercatat), bukan memblokir total.

**UI mobile:** `Mobile/Profile/FaceEnrollment` — stepper **Intro/Consent → Camera Guide (progress sampling) → Review → Success**; memakai `CameraCapture` + `GeofenceNotice`.

---

## 8. Perubahan Data (Migrasi)

| Tabel | Aksi | Keterangan |
| :--- | :--- | :--- |
| `hcm_employees` | `+ user_id` (nullable, unique, FK `users`) | Tautan identitas login |
| `users` | `+ must_change_password` (boolean) | Paksa ganti password default |
| `personal_access_tokens` | buat (Sanctum) | Token API RN (Fase 6) |
| `mobile_devices` | buat: `user_id, platform, fcm_token, last_seen_at` | Push native (Fase 6, opsional) |
| `purchasing_order_proofs` | buat (opsional): `uuid, order_id, type, file_path, uploaded_by, notes` | Bila bukti perlu >1 dokumen (nota + invoice + transfer) |
| `purchasing_master_options` | `+ latitude, longitude, radius_m, address, maps_url, geofence_enabled` (khusus kategori `location`) | Master lokasi **milik Purchasing sendiri** (terpisah dari HCM) — Purchasing §F |
| `purchasing_orders` | `+ check_lat, check_lng, check_distance_m, location_check_status (LULUS/GAGAL/SKIPPED), location_checked_at` | Audit geofence purchasing (Purchasing §F) |
| `hcm_locations` | buat: `uuid, code, name, address, latitude, longitude, radius_m, maps_url, geofence_enabled, brand_id (nullable), is_active` | Master lokasi kerja HCM (mandiri) — penempatan & geofence absensi (§6.4, §7.3) |
| `hcm_employees` | `+ location_id` (FK `hcm_locations`) | Penempatan karyawan per lokasi (§7.3) |
| `hcm_employee_locations` | opsional (multi-lokasi): `uuid, employee_id, location_id, is_active` | Bila karyawan berhak di >1 lokasi |
| `hcm_employee_face_profiles` | buat: `uuid, employee_id (unique), embedding (encrypted), thumbnail_url, sample_count, quality_score, status (UNENROLLED/ENROLLED/REVOKED), consent_at, consent_version, enrolled_at, revoked_at, revoked_by` | Profil biometrik wajah (§7.2, §7.10, §7.7) |
| `hcm_attendances` | `+ check_in_lat, check_in_lng, check_in_accuracy_m, check_in_distance_m, check_in_method, check_in_face_score, check_in_liveness_score, check_out_lat, check_out_lng, check_out_accuracy_m, check_out_distance_m, check_out_method, check_out_face_score, check_out_liveness_score, early_leave_minutes, device_attest_status, ip_address, is_flagged, verification_status, verified_by, verified_at, flag_reasons` | Keamanan absensi (§7.5) |
| `hcm_attendances` | `+ shift_id (snapshot), shift_source (roster/default/company)` | Shift efektif snapshot (§7.9) |
| `hcm_shift_change_requests` | buat: `uuid, employee_id, from_shift_id, to_shift_id, effective_date, end_date, reason, attachment_url, status, reviewed_by, reviewed_at, rejection_reason, created_by` | Perombakan shift (§7.9) |

> Tidak mengubah/likuidasi tabel modul lain. Tabel baru wajib ber-`uuid` + `resolveRouteBinding` (pola existing), non-ID URL.

---

## 9. Prinsip Rekayasa & Kepatuhan (Quality Gate)

| Pilar | Standar Wajib |
| :--- | :--- |
| **Isolasi** | Kode baru di `app/Http/Controllers/Mobile/*`, `Api/V1/*`, `Services/Employee/*`, `resources/js/Pages/Mobile/*`. Tabel baru ber-prefix jelas. |
| **Otorisasi Ketat (Zero IDOR)** | Semua query di-scope `currentEmployee->id`; route model binding + policy; larang akses lintas karyawan. |
| **Non-ID Base URL** | Route key: `employee_code` (karyawan), `uuid` (cuti/exit-permit/lembur/payroll item), `po_number` (pengajuan purchasing). Zero raw DB ID. |
| **Zero N+1** | Eager loading eksplisit, `withCount`/`withSum`, pagination ketat di semua listing. |
| **Keamanan Password** | Password default NIK **wajib** ganti; hash via cast `'password' => 'hashed'`; throttle + lockout; JANGAN log nilai NIK/password. |
| **Zero SQL Injection / XSS** | Eloquent/Query Builder ber-parameter; auto-escape React; validasi MIME & nama file acak pada upload. |
| **Audit** | Semua mutasi (login, pengajuan, upload bukti) via `ActivityLogger`. |
| **Mock Fallback** | Notifikasi tetap jalan tanpa API key Sidobe/Telegram (pola existing). |
| **Anti-Spoof & Biometrik** | Liveness wajib; validasi geofence & integritas **di server** (bukan klien); ambang skor terkonfigurasi (bukan hardcode); consent UU PDP; embedding wajah terenkripsi. |
| **UI** | `lucide-react` SVG, layout mobile-first, aksesibel (touch target ≥44px), aman safe-area. |

---

## 10. Roadmap Fase (Phase-Gate)

### FASE 1 — Identitas & Kerangka Portal
- [ ] Migrasi `hcm_employees.user_id` + `users.must_change_password`.
- [ ] Role `karyawan` + 11 permission baru di `RolePermissionSeeder` & assign.
- [ ] Command `employees:provision-accounts` (idempotent, default password NIK).
- [ ] Route group `/app` + `/app/login` + middleware `employee.portal`.
- [ ] `MobileLayout.jsx` **mobile-only** (bottom tab bar ala app, tanpa sidebar desktop) + Beranda dengan widget Absen Berangkat/Pulang (§6.3).
- [ ] Paksa ganti password saat login pertama + throttle/lockout + audit.
- **Lulus**: karyawan bisa login di URL terpisah, mengganti password, dan melihat Beranda berisi data dirinya saja.

### FASE 2 — HR Self-Service (Absensi, Cuti/Izin, Gate Pass, Lembur, Uang Makan)
- [ ] Master lokasi kerja HCM ber-koordinat (`hcm_locations` + `hcm_employees.location_id`) + enrollment wajah (`hcm_employee_face_profiles`).
- [ ] **ShiftResolver** (roster → default → company) + perhitungan telat/pulang cepat dinamis (§7.9).
- [ ] **Perombakan shift** (`hcm_shift_change_requests`): ajukan → approve → tulis roster; tanpa approve tetap TELAT.
- [ ] **Absen Berangkat & Absen Pulang** mandiri: **wajah + liveness + geofence + anti-fake GPS** (lihat §7) → `HcmAttendance`.
- [ ] Bukti fisik gelombang 1: **QR dinamis gerbang (TOTP)** + cross-check IP + deteksi *impossible travel*.
- [ ] Alur `PERLU_VERIFIKASI` + antrean review/approve HCM.
- [ ] Riwayat harian + kalender bulanan absen.
- [ ] Ajukan Cuti/Izin/Sakit + unggah lampiran, riwayat, batal saat pending.
- [ ] Izin keluar kantor: ajukan + tandai "Kembali".
- [ ] Lembur: lihat riwayat (+ajukan, opsional).
- [ ] Uang makan: rekap bulanan.
- **Lulus**: seluruh pengajuan HR tercatat dengan `employee_id` = pengaju (tak bisa diubah), absen berangkat/pulang terverifikasi (atau ber-flag untuk review), ter-scope aman, dan muncul di sisi admin HCM untuk approval.

### FASE 3 — Payroll, Reward & Notifikasi
- [ ] Daftar periode payroll karyawan + rincian item + unduh slip PDF.
- [ ] Riwayat reward/apresiasi.
- [ ] Inbox notifikasi + status baca; kirim notif saat status pengajuan berubah (Sidobe/Telegram + fallback).
- **Lulus**: karyawan hanya bisa melihat slip miliknya; notifikasi status pengajuan terkirim.

### FASE 4 — Pengajuan Purchasing & Penyelesaian Bukti
- [ ] Buat pengajuan pengadaan (barang/jasa, Opex/Capex) → `PurchasingOrder` status awal pending PIC.
- [ ] Pantau status approval (PIC → Finance) + riwayat (record).
- [ ] Unggah bukti (nota/invoice/bukti transfer) → `invoice_file_path` / `receipt_file_path` (atau `purchasing_order_proofs`).
- [ ] Konfirmasi realisasi/settlement (alur `realize()`/`settle()` existing).
- **Lulus**: karyawan mengajukan → PIC/Finance approve (sisi admin) → karyawan unggah bukti → status terselesaikan; semua tercatat.

### FASE 5 — Hardening & QA
- [ ] Audit Zero N+1, verifikasi role & scope, cek IDOR lintas karyawan.
- [ ] Feature test `tests/Feature/Mobile/EmployeeSelfServiceTest.php` (login, izin, payroll, pengajuan, bukti) 100% green.
- [ ] Audit keamanan password default & policy ganti password.
- [ ] `npm run build` bersih (exit code 0).
- **Lulus**: seluruh test hijau, tanpa akses lintas karyawan.

### FASE 6 — API v1 (Sanctum) & Persiapan React Native
- [ ] `routes/api.php` + `Api/V1` controllers (reuse service Fase 2–4).
- [ ] Login token (employee_code/NIK) + `change-password`, expiry & revoke.
- [ ] Endpoint mobile: beranda, absensi, cuti, gate pass, payroll, pengajuan, bukti, notifikasi.
- [ ] Keamanan absensi native: `isFromMockProvider`, Play Integrity API / App Attest, BLE (lihat §7.4).
- [ ] `mobile_devices` + push (FCM) opsional.
- **Lulus**: API v1 teruji (Postman/Feature test) siap dikonsumsi React Native tanpa mengubah domain.

---

## 11. Matriks Status Fase Real-Time

| Fase | Lingkup | Deliverable Utama | Progres | Gerbang |
| :---: | :--- | :--- | :---: | :---: |
| **1** | Identitas & Kerangka Portal | user_id link, role karyawan, provisioning, `/app/login`, **MobileLayout mobile-only (bottom nav) + Beranda widget absen** | **0%** | BELUM |
| **2** | HR Self-Service | Absensi biometrik (wajah+geofence+anti-spoof), Cuti/Izin, Gate Pass, Lembur, Uang Makan | **0%** | BELUM |
| **3** | Payroll, Reward & Notifikasi | Slip payroll, reward, inbox notif | **0%** | BELUM |
| **4** | Purchasing & Bukti | Pengajuan + tracking + unggah bukti + settlement | **0%** | BELUM |
| **5** | Hardening & QA | Zero N+1 audit, feature test, build bersih | **0%** | BELUM |
| **6** | API v1 (Sanctum) | routes/api.php, endpoints RN, device token | **0%** | BELUM |

---

## 12. Log Riwayat Perubahan & Catatan Audit

| Versi / Tanggal | Pelaksana | Perubahan / Aktivitas | Catatan Kepatuhan |
| :--- | :--- | :--- | :--- |
| **v1.0** — 14 Okt 2026 | AI Agent & Developer | Audit codebase HCM & Purchasing; penyusunan roadmap Mobile Employee Self-Service; keputusan PWA → React Native, login portal terpisah, password default NIK. | Status Progres: 0%. |
| **v1.1** — 14 Okt 2026 | AI Agent & Developer | Tambah §7 Absensi Biometrik (wajah + geofence + anti-fake GPS berlapis), model data keamanan absensi, serta kepatuhan UU PDP. | Status Progres: 0%. |
| **v1.2** — 14 Okt 2026 | AI Agent & Developer | Pisah **Absen Berangkat & Absen Pulang** (keduanya ber-lapis verifikasi); widget absen di Beranda; tambah §6.3 Spesifikasi **Mobile-Only UI** (bottom nav ala app) sebagai acuan React Native. | Status Progres: 0%. |
| **v1.3** — 14 Okt 2026 | AI Agent & Developer | Tambah §7.9 **Dinamika Shift & Aturan Telat**: telat dihitung dari shift efektif (roster→default→company); absen di luar shift tanpa perombakan disetujui = TELAT; tabel `hcm_shift_change_requests`. | Status Progres: 0%. |
| **v1.4** — 14 Okt 2026 | AI Agent & Developer | Hapus opsi NFC **dan ketergantungan Wi-Fi kantor** (kurang relevan). Anti-fake GPS fokus pada QR dinamis gerbang + geofence + cross-check IP + integritas device. | Status Progres: 0%. |
| **v1.5** — 14 Okt 2026 | AI Agent & Developer | Tambah §6.4 **Validasi Lokasi Ruko/Gudang (Geofence + Peta)**: koordinat + radius toleransi pada `purchasing_master_options`, service Haversine bersama, status **GAGAL** bila di luar toleransi; selaras Purchasing §F. | Status Progres: 0%. |
| **v1.6** — 14 Okt 2026 | AI Agent & Developer | Klarifikasi: master lokasi ruko/gudang ber-koordinat dipakai **untuk absensi** (bukan hanya pengajuan). `hcm_employees.work_location_id` + §7.3 diperbarui; `hcm_work_locations` jadi opsi multi-lokasi. | Status Progres: 0%. |
| **v1.7** — 14 Okt 2026 | AI Agent & Developer | **Decoupling**: HCM punya master lokasi **sendiri & mandiri** (`hcm_locations`) untuk penempatan + geofence absensi; Purchasing tetap punya daftar lokasinya **sendiri** (`purchasing_master_options`). Tidak ada ketergantungan silang. | Status Progres: 0%. |
| **v1.8** — 14 Okt 2026 | AI Agent & Developer | Tambah §7.10 **Alur Pendaftaran Wajah (Face Enrollment)**: consent, state profil, 3–5 sampel on-device, revoke/reset HCM, fallback anti-dead-end; perluas skema `hcm_employee_face_profiles`. | Status Progres: 0%. |

---

> [!IMPORTANT]
> **Protokol Eksekusi Bertahap (Phase-Gate Rule)**:
> Dilarang melompat ke Fase berikutnya sebelum seluruh checklist fase aktif berstatus selesai `[x]`, teruji tanpa bug, dan terverifikasi 100% memenuhi standar *Zero IDOR*, *Zero SQL Injection*, *Zero XSS*, *Zero N+1 Query*, serta *Non-ID Base URL*.
>
> **Catatan Keamanan**: password default = NIK bersifat sementara. Wajib dipaksa ganti saat login pertama, dan NIK tidak boleh dicatat di log maupun dikirim ke klien.
