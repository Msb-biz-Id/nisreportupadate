# Panduan Deployment Branch `main` ke `nis.msb.biz.id` di cPanel

Dokumentasi ini menjelaskan langkah demi langkah deployment aplikasi **ProTrack** dari branch `main` ke subdomain **`https://nis.msb.biz.id`** menggunakan hosting **cPanel**.

---

## Daftar Isi
1. [Prasyarat Server & cPanel](#1-prasyarat-server--cpanel)
2. [Arsitektur Direktori di cPanel](#2-arsitektur-direktori-di-cpanel)
3. [Persiapan di Komputer Lokal (Sebelum Deploy)](#3-persiapan-di-komputer-lokal-sebelum-deploy)
4. [Langkah 1: Setup Subdomain di cPanel](#langkah-1-setup-subdomain-di-cpanel)
5. [Langkah 2: Setup Database MySQL di cPanel](#langkah-2-setup-database-mysql-di-cpanel)
6. [Langkah 3: Upload / Clone Source Code](#langkah-3-upload--clone-source-code)
   - [Opsi A: Menggunakan SSH / Terminal cPanel & Git (Direkomendasikan)](#opsi-a-menggunakan-ssh--terminal-cpanel--git-direkomendasikan)
   - [Opsi B: Menggunakan File Manager (ZIP Upload)](#opsi-b-menggunakan-file-manager-zip-upload)
7. [Langkah 4: Konfigurasi File `.env`](#langkah-4-konfigurasi-file-env)
8. [Langkah 5: Instalasi Dependensi & Storage Link](#langkah-5-instalasi-dependensi--storage-link)
9. [Langkah 6: Eksekusi Migrate & Seeder Lengkap](#langkah-6-eksekusi-migrate--seeder-lengkap)
   - [A. Migrate & Seed Sekaligus (Instalasi Baru)](#a-migrate--seed-sekaligus-instalasi-baru)
   - [B. Eksekusi Seeder Spesifik per Modul](#b-eksekusi-seeder-spesifik-per-modul)
   - [C. Daftar Akun Default Hasil Seeder](#c-daftar-akun-default-hasil-seeder)
10. [Langkah 7: Optimasi Cache Production](#langkah-7-optimasi-cache-production)
11. [Langkah 8: Setup Cron Job (Scheduler)](#langkah-8-setup-cron-job-scheduler)
12. [Troubleshooting & Solusi Kendala Umum](#troubleshooting--solusi-kendala-umum)

---

## 1. Prasyarat Server & cPanel

Pastikan akun hosting cPanel Anda memenuhi kriteria berikut:

| Kebutuhan | Versi / Nilai Rekomendasi | Catatan |
| :--- | :--- | :--- |
| **PHP** | **8.2** atau **8.3** | Pilih via **Select PHP Version** / **MultiPHP Manager** di cPanel |
| **PHP Extensions** | `pdo_mysql`, `mbstring`, `xml`, `curl`, `zip`, `gd`, `bcmath`, `fileinfo`, `tokenizer`, `dom`, `intl` | Aktifkan di menu *PHP Extensions* |
| **PHP Options** | `memory_limit >= 256M`, `max_execution_time >= 120`, `upload_max_filesize >= 20M` | Atur di menu *PHP Options* |
| **Database** | MySQL 8.0+ / MariaDB 10.6+ | Karakter set `utf8mb4_unicode_ci` |
| **Node.js** | *Tidak wajib di server* | Build asset frontend (`npm run build`) dilakukan di lokal sebelum deploy |
| **Akses Terminal** | SSH atau menu **Terminal** cPanel | Sangat dianjurkan untuk menjalankan Composer & Artisan |

---

## 2. Arsitektur Direktori di cPanel

Untuk keamanan terbaik, letakkan source code Laravel di **luar** `public_html`, dan arahkan **Document Root** subdomain ke folder `public/`.

Contoh struktur:
```text
/home/USERNAME/
├── nis_app/                 <-- Root source code Laravel (Private & aman)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/              <-- Berisi index.php, .htaccess, build/ (Vite)
│   ├── resources/
│   ├── storage/
│   ├── vendor/
│   ├── .env
│   └── artisan
└── public_html/             <-- Atau langsung Document Root
```

> **Pengaturan Document Root Subdomain di cPanel:**
> Arahkan Document Root `nis.msb.biz.id` ke:  
> `/home/USERNAME/nis_app/public` (atau `/home/USERNAME/public_html/nis.msb.biz.id/public`)

---

## 3. Persiapan di Komputer Lokal (Sebelum Deploy)

Lakukan kompilasi asset frontend di komputer lokal agar folder `public/build/` siap pakai di server.

```bash
# 1. Pastikan berada di branch main dan kode terbaru
git checkout main
git pull origin main

# 2. Pastikan build asset frontend terbaru sudah selesai
npm run build

# 3. Pastikan git status bersih dan perubahan telah di-push ke repository
git status
git push origin main
```

---

## 4. Langkah 1: Setup Subdomain di cPanel

1. Masuk ke dashboard **cPanel**.
2. Cari menu **Domains** (atau **Subdomains**).
3. Klik **Create A New Domain**.
4. Isi data subdomain:
   - **Domain**: `nis.msb.biz.id`
   - Hilangkan centang *"Share document root (/home/username/public_html) with other domains"*.
   - **Document Root**: Isi dengan path yang mengarah ke folder `public`, contoh:  
     `nis_app/public` atau `public_html/nis.msb.biz.id/public`
5. Klik **Submit**.
6. Aktifkan **SSL (HTTPS)**:
   - Buka menu **SSL/TLS Status** di cPanel.
   - Cari `nis.msb.biz.id` dan klik **Run AutoSSL** hingga sertifikat aktif (ikon gembok hijau).

---

## 5. Langkah 2: Setup Database MySQL di cPanel

1. Buka menu **MySQL Database Wizard** di cPanel.
2. **Step 1: Create A Database**:
   - Masukkan nama database (misal: `nis_db`), hasil akhir akan menjadi `USERNAME_nis_db`.
3. **Step 2: Create Database Users**:
   - Masukkan username (misal: `nis_user`), hasil akhir `USERNAME_nis_user`.
   - Buat kata sandi yang kuat (gunakan *Password Generator*). Simpan sandi ini untuk `.env`.
4. **Step 3: Add User to Database**:
   - Centang **ALL PRIVILEGES**.
   - Klik **Make Changes**.

---

## 6. Langkah 3: Upload / Clone Source Code

Pilih salah satu metode berikut:

### Opsi A: Menggunakan SSH / Terminal cPanel & Git (Direkomendasikan)

Buka menu **Terminal** di cPanel atau hubungkan via SSH:

```bash
# 1. Pindah ke direktori home
cd ~

# 2. Clone repository ke folder nis_app
git clone -b main https://github.com/Msb-biz-Id/nisreportupadate.git nis_app

# 3. Masuk ke folder aplikasi
cd nis_app

# 4. Buat file .env dari template
cp .env.example .env
```

### Opsi B: Menggunakan File Manager (ZIP Upload)

Jika tidak menggunakan Git di server:
1. Di komputer lokal, compress project menjadi `.zip` (**KECUALIKAN** folder `node_modules`, `vendor`, `.git`).  
   *Pastikan folder `public/build` tetap terikut di dalam ZIP!*
2. Buka **File Manager** cPanel, upload file `.zip` ke folder `/home/USERNAME/`.
3. Klik kanan file `.zip` → **Extract** ke folder `nis_app`.

---

## 7. Langkah 4: Konfigurasi File `.env`

Edit file `.env` di dalam folder `nis_app`:

```env
APP_NAME="ProTrack"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://nis.msb.biz.id

APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=id_ID

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# Konfigurasi Database cPanel
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=USERNAME_nis_db
DB_USERNAME=USERNAME_nis_user
DB_PASSWORD=KATA_SANDI_DATABASE_ANDA

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=public
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Konfigurasi Mail (Gunakan SMTP cPanel atau Mailgun/Gmail)
MAIL_MAILER=smtp
MAIL_HOST=mail.msb.biz.id
MAIL_PORT=465
MAIL_USERNAME=no-reply@msb.biz.id
MAIL_PASSWORD=PASSWORD_EMAIL
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="no-reply@msb.biz.id"
MAIL_FROM_NAME="${APP_NAME}"

# Cloudflare Turnstile (Jika digunakan)
TURNSTILE_ENABLED=false
TURNSTILE_SITE_KEY=
TURNSTILE_SECRET_KEY=
```

---

## 8. Langkah 5: Instalasi Dependensi & Storage Link

Jalankan perintah berikut di Terminal cPanel:

```bash
cd ~/nis_app

# 1. Install dependensi PHP untuk production (tanpa dev packages)
composer install --no-dev --optimize-autoloader

# 2. Generate Application Key jika belum ada
php artisan key:generate --force

# 3. Hubungkan folder storage publik
php artisan storage:link

# 4. Atur permission direktori cache & storage
chmod -R 775 storage bootstrap/cache
```

---

## 9. Langkah 6: Eksekusi Migrate & Seeder Lengkap

DatabaseSeeder di project ini telah mencakup seluruh modul: **Role/Permission, Brand, User, Master Data, Finance, Customer, Ekspedisi, Order, HCM (Employee/Presensi/Lembur/THR), serta Purchasing**.

### A. Migrate & Seed Sekaligus (Instalasi Baru)

Gunakan flag `--force` karena aplikasi berjalan dalam mode `production`:

```bash
cd ~/nis_app

# Eksekusi migration dan langsung jalankan DatabaseSeeder
php artisan migrate --seed --force
```

> **Catatan Reset Total (Jika ingin mengulang dari database kosong):**
> ```bash
> php artisan migrate:fresh --seed --force
> ```
> *(PERINGATAN: Perintah `migrate:fresh` akan menghapus seluruh data tabel yang ada).*

---

### B. Eksekusi Seeder Spesifik per Modul

Jika Anda ingin menjalankan migrasi tabel terlebih dahulu lalu mengeksekusi seeder secara bertahap atau terpisah:

```bash
cd ~/nis_app

# 1. Jalankan migrasi tabel terlebih dahulu
php artisan migrate --force

# 2. Seeder Fondasi & Otentikasi
php artisan db:seed --class=RolePermissionSeeder --force
php artisan db:seed --class=BrandSeeder --force
php artisan db:seed --class=UserSeeder --force

# 3. Seeder Master Data & Bisnis
php artisan db:seed --class=MasterDataSeeder --force
php artisan db:seed --class=FinanceSeeder --force
php artisan db:seed --class=CustomerSeeder --force
php artisan db:seed --class=EkspedisiSeeder --force
php artisan db:seed --class=OrderSeeder --force

# 4. Seeder Human Capital Management (HCM)
php artisan db:seed --class=HcmMasterDataSeeder --force
php artisan db:seed --class=HcmUserSeeder --force
php artisan db:seed --class=HcmEmployeeSeeder --force
php artisan db:seed --class=HcmAttendanceAndLeaveSeeder --force
php artisan db:seed --class=HcmOvertimeSeeder --force
php artisan db:seed --class=HcmMealAllowanceAndRewardSeeder --force
php artisan db:seed --class=HcmRecruitmentAndDocumentSeeder --force
php artisan db:seed --class=HcmCompanyEventSeeder --force

# 5. Seeder Modul Purchasing & Suplai
php artisan db:seed --class="Database\Seeders\Purchasing\PurchasingMasterOptionSeeder" --force
php artisan db:seed --class="Database\Seeders\Purchasing\PurchasingAssetCategorySeeder" --force
php artisan db:seed --class="Database\Seeders\Purchasing\PurchasingAssetSuggestionSeeder" --force
```

---

### C. Daftar Akun Default Hasil Seeder

Seluruh akun hasil seeder menggunakan kata sandi default: **`password123`**

| Peran / Hak Akses | Email Akun | Password | Keterangan |
| :--- | :--- | :--- | :--- |
| **Superadmin** | `itidwarehouse@gmail.com` | `password123` | Akses penuh seluruh modul & setting |
| **Owner / Direksi** | `owner@nisreport.local` | `password123` | Dashboard eksekutif & monitoring multi-brand |
| **Keuangan / Finance** | `finance.nisgroup@gmail.com` | `password123` | Manajemen invoice, pembayaran, kas, & refund |
| **Keuangan Alternatif** | `keuangan.nisgroup@gmail.com` | `password123` | Operasional keuangan harian |
| **Admin Produksi** | `adminproduksi.nisgroup@gmail.com` | `password123` | SPK, timeline antrean produksi & Gantt chart |
| **Admin HCM** | `hcm@nisgroup.id` | `password123` | Manajemen SDM, absensi, cuti, & KPI karyawan |
| **Staff HCM** | `staff.hcm@nisgroup.id` | `password123` | Legalitas & operasional harian HR |
| **Admin Purchasing** | `purchasing@nisgroup.id` | `password123` | Pengadaan bahan baku & manajemen supplier |
| **Staff Purchasing** | `staff.purchasing@nisgroup.id` | `password123` | Pencatatan PO suplai |
| **Admin Brand (Allegiant)** | `allegiant.id@gmail.com` | `password123` | Akses brand ALG |
| **Admin Brand (Circle)** | `circlesportwear@gmail.com` | `password123` | Akses brand CRL |
| **Admin Brand (Drive)** | `sportweardrive@gmail.com` | `password123` | Akses brand DRV |
| **Admin Reseller** | `indonesiasportwarehouse@gmail.com` | `password123` | Portal reseller demo |
| **Supervisor** | `supervisor.nisgroup@gmail.com` | `password123` | Verifikasi operasional order & status |

> **Catatan Login:** Halaman login di branch `main` telah dilengkapi widget **Bantuan Akun Cepat** (1-klik langsung mengisi email & password) khusus untuk akun-akun non-superadmin di atas.

---

## 10. Langkah 7: Optimasi Cache Production

Jalankan perintah optimasi agar performa aplikasi cepat dan minim pemakaian I/O:

```bash
cd ~/nis_app

# Bersihkan cache lama
php artisan optimize:clear

# Buat cache konfigurasi, routing, dan view
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

> **PENTING:** Setiap kali Anda mengedit file `.env` di kemudian hari, jalankan `php artisan config:clear && php artisan config:cache` agar perubahan terbaca.

---

## 11. Langkah 8: Setup Cron Job (Scheduler)

Laravel membutuhkan satu baris Cron Job di cPanel untuk menjalankan jadwal otomatis (notifikasi, sinkronisasi presensi, dll).

1. Buka menu **Cron Jobs** di cPanel.
2. Di bagian **Common Settings**, pilih **Once Per Minute (* * * * *)**.
3. Di kolom **Command**, masukkan:
   ```bash
   /usr/local/bin/php /home/USERNAME/nis_app/artisan schedule:run >> /dev/null 2>&1
   ```
   *(Sesuaikan path `/home/USERNAME/nis_app/artisan` dengan username cPanel Anda).*
4. Klik **Add New Cron Job**.

---

## Troubleshooting & Solusi Kendala Umum

### 1. Error `HTTP 500 | Server Error` saat pertama kali dibuka
- Periksa log error Laravel:
  ```bash
  tail -n 50 ~/nis_app/storage/logs/laravel.log
  ```
- Pastikan permission folder storage sudah benar:
  ```bash
  chmod -R 775 ~/nis_app/storage ~/nis_app/bootstrap/cache
  ```
- Pastikan `APP_KEY` sudah terisi di `.env`. Jika kosong, jalankan `php artisan key:generate --force`.

### 2. Tampilan Berantakan / Error `Vite manifest not found at ...`
- Terjadi karena folder `public/build` belum ter-compile atau belum ter-upload.
- Solusi: Di komputer lokal jalankan `npm run build`, pastikan folder `public/build` ter-upload ke folder `public/build` di server cPanel.

### 3. Gambar atau Lampiran File Tidak Muncul (`404 Not Found`)
- Jalankan ulang pembuatan symlink storage:
  ```bash
  cd ~/nis_app
  rm public/storage
  php artisan storage:link
  ```

### 4. Error `SQLSTATE[HY000] [1045] Access denied for user`
- Kredensial database di file `.env` tidak cocok.
- Pastikan `DB_DATABASE` dan `DB_USERNAME` menyertakan prefix username cPanel (contoh: `u1234567_nisdb`).
- Pastikan user telah di-*assign* ke database dengan hak akses **ALL PRIVILEGES** di MySQL Databases cPanel.

### 5. Cara Melakukan Update / Redeploy di Masa Depan
Jika ada commit baru di branch `main`:
```bash
cd ~/nis_app
php artisan down
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```
