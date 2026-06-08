# Panduan Deploy NISReport ke cPanel via Terminal cPanel

> **Target:** cPanel Shared Hosting menggunakan Terminal bawaan cPanel  
> **URL:** https://test.nis.biz.id  
> **PHP:** 8.3.31 | **Database:** MariaDB/MySQL | **Laravel:** 12.x  
> **Catatan:** Semua perintah dijalankan di Terminal cPanel, bukan terminal lokal

---

## Daftar Isi

1. [Persiapan di Komputer Lokal](#1-persiapan-di-komputer-lokal)
2. [Buka Terminal di cPanel](#2-buka-terminal-di-cpanel)
3. [Upload File ke Server](#3-upload-file-ke-server)
4. [Setup Database](#4-setup-database)
5. [Konfigurasi .env](#5-konfigurasi-env)
6. [Setup Laravel via Terminal cPanel](#6-setup-laravel-via-terminal-cpanel)
7. [Konfigurasi .htaccess & Subdomain](#7-konfigurasi-htaccess--subdomain)
8. [Verifikasi & Testing](#8-verifikasi--testing)
9. [Troubleshooting](#9-troubleshooting)
10. [Workflow Update di Masa Depan](#10-workflow-update-di-masa-depan)

---

## 1. Persiapan di Komputer Lokal

Jalankan semua ini di komputer developer sebelum upload ke hosting.

### 1.1 Install Dependencies & Build

```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

Pastikan folder `public/build/` terbuat setelah `npm run build`.

### 1.2 Buat ZIP Project

**Windows (PowerShell) — dari dalam folder project:**
```powershell
cd d:\AI\Nisreport\nisreportupadate

# Buat ZIP (node_modules dan .env tidak ikut)
Compress-Archive -Path app, bootstrap, config, database, public, resources, routes, storage, vendor, artisan, composer.json, composer.lock, package.json, vite.config.js, tailwind.config.js, postcss.config.js, jsconfig.json, phpunit.xml, .htaccess -DestinationPath ..\nisreport_deploy.zip
```

**Atau cara mudah — ZIP semua lalu hapus yang tidak perlu:**
```powershell
# Buat ZIP seluruh folder
Compress-Archive -Path "d:\AI\Nisreport\nisreportupadate\*" -DestinationPath "d:\AI\Nisreport\nisreport_deploy.zip" -Force
```

> File yang tidak perlu di-upload: `node_modules/`, `.git/`, `.env`, `*.zip`  
> Jika ikut ter-ZIP tidak apa-apa, tidak akan berpengaruh ke fungsi aplikasi.

---

## 2. Buka Terminal di cPanel

### 2.1 Akses Terminal

1. Login ke **cPanel** (biasanya `nis.biz.id/cpanel` atau `nis.biz.id:2083`)
2. Di kolom pencarian ketik **"Terminal"**
3. Klik ikon **Terminal**
4. Browser akan membuka emulator terminal langsung di halaman cPanel

### 2.2 Verifikasi Lingkungan Server

Setelah Terminal terbuka, jalankan:

```bash
# Cek posisi folder saat ini
pwd

# Cek versi PHP
php -v

# Cek Composer tersedia
composer --version

# Lihat isi public_html
ls ~/public_html/
```

> PHP sudah 8.3.31 — tidak perlu mengubah PHP Selector.

---

## 3. Upload File ke Server

### 3.1 Upload ZIP via File Manager cPanel

1. cPanel → **File Manager**
2. Navigasi ke `public_html/`
3. Klik **Upload** (pojok kanan atas)
4. Upload file `nisreport_deploy.zip` dari komputer lokal
5. Tunggu upload selesai (progress bar 100%)

### 3.2 Ekstrak ZIP via Terminal cPanel

Buka Terminal cPanel, lalu:

```bash
# Masuk ke public_html
cd ~/public_html

# Buat folder untuk project
mkdir nisreport

# Pindahkan ZIP ke dalam folder (jika terupload langsung ke public_html)
mv nisreport_deploy.zip nisreport/

# Masuk folder dan ekstrak
cd nisreport
unzip nisreport_deploy.zip

# Cek hasilnya
ls -la
```

**Jika file ZIP terupload langsung ke dalam folder `nisreport/`:**
```bash
cd ~/public_html/nisreport
unzip nisreport_deploy.zip
ls -la
```

**Jika ada subfolder hasil ekstrak (misal `nisreportupadate/`):**
```bash
# Pindahkan semua file ke level atas
mv nisreportupadate/* .
mv nisreportupadate/.* . 2>/dev/null
rmdir nisreportupadate

# Verifikasi
ls -la
```

### 3.3 Verifikasi File Terupload

```bash
# Pastikan file-file penting ada
ls ~/public_html/nisreport/app/
ls ~/public_html/nisreport/public/build/   # hasil npm run build
ls ~/public_html/nisreport/vendor/         # hasil composer install
```

---

## 4. Setup Database

### 4.1 Buat Database di cPanel

1. cPanel → **MySQL Databases**
2. **Create New Database:** isi nama misal `nisreport` → klik **Create Database**
   - Nama lengkap akan jadi: `cpaneluser_nisreport`
3. **Create New User:** isi username misal `dbuser` dan password kuat → **Create User**
   - Username lengkap: `cpaneluser_dbuser`
4. **Add User To Database:** pilih user dan database → centang **All Privileges** → **Make Changes**

> Catat nama database lengkap, username, dan password — akan dipakai di `.env`.

### 4.2 Import Struktur Database via Terminal cPanel

Upload file SQL dulu ke server (via File Manager → upload `nisreport_structure.sql` ke `~/`), lalu di Terminal:

```bash
# Import SQL ke database
mysql -u cpaneluser_dbuser -p cpaneluser_nisreport < ~/nisreport_structure.sql
# masukkan password database saat diminta
```

> Jika tidak punya file SQL, skip langkah ini — jalankan `php artisan migrate` di step 6 nanti.

**Verifikasi import berhasil:**
```bash
mysql -u cpaneluser_dbuser -p -e "USE cpaneluser_nisreport; SHOW TABLES;"
```

---

## 5. Konfigurasi .env

### 5.1 Buat File .env

Di Terminal cPanel:

```bash
cd ~/public_html/nisreport
cp .env.example .env
```

### 5.2 Edit .env via Terminal

```bash
nano .env
```

Edit nilai berikut (gunakan tombol panah untuk navigasi):

```env
APP_NAME="NISReport"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://test.nis.biz.id

APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cpaneluser_nisreport
DB_USERNAME=cpaneluser_dbuser
DB_PASSWORD=passwordanda

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=public
QUEUE_CONNECTION=sync
CACHE_STORE=file

MAIL_MAILER=smtp
MAIL_SCHEME=ssl
MAIL_HOST=mail.nis.biz.id
MAIL_PORT=465
MAIL_USERNAME=noreply@nis.biz.id
MAIL_PASSWORD=passwordemail
MAIL_FROM_ADDRESS="noreply@nis.biz.id"
MAIL_FROM_NAME="${APP_NAME}"

GEMINI_API_KEYS=
SIDOBE_API_KEY=
```

**Simpan dan keluar dari nano:**
- `Ctrl + O` → Enter (simpan)
- `Ctrl + X` (keluar)

### 5.3 Keamanan .env

```bash
chmod 640 .env
```

---

## 6. Setup Laravel via Terminal cPanel

Semua perintah dijalankan dari folder root Laravel:

```bash
cd ~/public_html/nisreport
```

### 6.1 Fix Permission Folder

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache

mkdir -p storage/app/public
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache
```

### 6.2 Generate App Key

```bash
php artisan key:generate
```

Output sukses: `Application key set successfully.`

Verifikasi:
```bash
grep APP_KEY .env
```

### 6.3 Jalankan Migrasi Database

```bash
php artisan migrate --force
```

Jika ada error koneksi database, cek dulu:
```bash
php artisan db:show
```

### 6.4 Jalankan Seeders

```bash
# Seed semua sekaligus
php artisan db:seed --force
```

Atau per seeder jika ingin selektif:
```bash
php artisan db:seed --class=RolePermissionSeeder --force
php artisan db:seed --class=BrandSeeder --force
php artisan db:seed --class=MasterDataSeeder --force
php artisan db:seed --class=UserSeeder --force
php artisan db:seed --class=FinanceSeeder --force
```

Seed data wilayah Indonesia:
```bash
php artisan laravolt:indonesia:seed
```

### 6.5 Buat Storage Link

```bash
php artisan storage:link
```

Output sukses: `The [public/storage] link has been connected to [storage/app/public].`

Jika gagal, buat symlink manual:
```bash
ln -s ~/public_html/nisreport/storage/app/public ~/public_html/nisreport/public/storage
```

Verifikasi:
```bash
ls -la ~/public_html/nisreport/public/storage
```

### 6.6 Optimasi Cache

```bash
php artisan optimize
```

Atau satu per satu:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 6.7 Ringkasan Urutan Perintah

Salin dan jalankan semua sekaligus (setelah `.env` sudah diisi):

```bash
cd ~/public_html/nisreport && \
chmod -R 775 storage bootstrap/cache && \
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache && \
php artisan key:generate && \
php artisan migrate --force && \
php artisan db:seed --force && \
php artisan laravolt:indonesia:seed && \
php artisan storage:link && \
php artisan optimize
```

---

## 7. Konfigurasi .htaccess & Subdomain

### 7.1 Arahkan Subdomain ke Folder /public (Direkomendasikan)

1. cPanel → **Subdomains**
2. Subdomain: `test` | Domain: `nis.biz.id`
3. Document Root: `/home/cpaneluser/public_html/nisreport/public`
4. Klik **Create**

Dengan ini `test.nis.biz.id` langsung mengarah ke folder public Laravel tanpa `.htaccess` tambahan.

### 7.2 Jika Deploy di Subfolder (tanpa subdomain)

Akses via `nis.biz.id/nisreport/` — buat `.htaccess` di root Laravel:

```bash
nano ~/public_html/nisreport/.htaccess
```

Isi:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

Simpan: `Ctrl+O` → Enter → `Ctrl+X`

### 7.3 Verifikasi .htaccess di public/

```bash
cat ~/public_html/nisreport/public/.htaccess
```

Harus ada baris `RewriteRule ^ index.php [L]`.

---

## 8. Verifikasi & Testing

### 8.1 Cek Status via Terminal cPanel

```bash
cd ~/public_html/nisreport

# Cek konfigurasi
grep -E "APP_ENV|APP_DEBUG|APP_KEY|DB_DATABASE" .env

# Cek storage symlink
ls -la public/storage

# Cek permission
ls -la storage/

# Lihat log jika ada error
tail -n 50 storage/logs/laravel.log
```

### 8.2 Test via Browser

| Halaman | Yang Dicek |
|---------|------------|
| `https://test.nis.biz.id` | Halaman login muncul |
| Login superadmin | Masuk ke dashboard |
| Buka Master Data | Data terisi dari seeder |
| Upload gambar di PO | File tersimpan di storage |
| Download PDF SPK | PDF ter-generate |

### 8.3 Akun Default Setelah Seeder

| Email | Password | Role |
|-------|----------|------|
| `superadmin@nisreport.local` | `password` | Superadmin |
| `owner@nisreport.local` | `password` | Owner |
| `admin.allegiant@nisreport.local` | `password` | Admin Brand |
| `reseller@nisreport.local` | `password` | Admin Reseller |
| `produksi@nisreport.local` | `password` | Admin Produksi |
| `keuangan@nisreport.local` | `password` | Admin Keuangan |

> Segera ganti semua password setelah login pertama!

### 8.4 Security Checklist

```bash
# Pastikan APP_DEBUG=false
grep APP_DEBUG ~/public_html/nisreport/.env

# Pastikan tidak ada file berbahaya di public/
ls ~/public_html/nisreport/public/setup.php   # harus error
ls ~/public_html/nisreport/public/*.sql        # harus error
```

Test dari browser: `https://test.nis.biz.id/.env` → harus tampil **403 atau 404**, bukan isi file.

---

## 9. Troubleshooting

### Error 500: Internal Server Error

```bash
# Lihat log Laravel
tail -n 100 ~/public_html/nisreport/storage/logs/laravel.log
```

| Penyebab | Solusi |
|----------|--------|
| APP_KEY kosong | `php artisan key:generate` |
| Permission storage | `chmod -R 775 storage bootstrap/cache` |
| Cache lama | `php artisan optimize:clear` |
| .env belum dibuat | `cp .env.example .env` lalu isi ulang |

### Error: Database Connection Refused

```bash
# Cek isi konfigurasi DB
grep -E "DB_" ~/public_html/nisreport/.env

# Test koneksi manual
mysql -u cpaneluser_dbuser -p -e "SHOW DATABASES;"
```

Pastikan `DB_HOST=localhost` (bukan `127.0.0.1`).

### Error: "No application encryption key"

```bash
cd ~/public_html/nisreport
php artisan key:generate
php artisan config:clear
```

### Storage/Gambar Tidak Tampil

```bash
cd ~/public_html/nisreport

# Cek symlink
ls -la public/storage

# Hapus dan buat ulang jika rusak
rm -f public/storage
php artisan storage:link

# Jika masih gagal, manual:
rm -f public/storage
ln -s ~/public_html/nisreport/storage/app/public ~/public_html/nisreport/public/storage
```

### 404 untuk Semua Halaman

- Pastikan Document Root subdomain mengarah ke `.../nisreport/public`
- Atau pastikan `.htaccess` di root Laravel sudah ada
- Hubungi support hosting untuk memastikan `mod_rewrite` aktif

### PHP Versi Berbeda di Terminal

```bash
php -v  # harus menampilkan PHP 8.3.31
```

Jika tidak sesuai, gunakan path eksplisit:
```bash
/opt/cpanel/ea-php83/root/usr/bin/php artisan --version
```

### Class Not Found / Composer Error

```bash
cd ~/public_html/nisreport
composer dump-autoload --optimize
```

---

## 10. Workflow Update di Masa Depan

### 10.1 Alur Update

**Di komputer lokal:**
```bash
# 1. Edit kode, lalu build ulang
npm run build

# 2. Buat ZIP baru
Compress-Archive -Path "d:\AI\Nisreport\nisreportupadate\*" -DestinationPath "d:\AI\Nisreport\nisreport_update.zip" -Force
```

**Upload ZIP baru via File Manager cPanel**, lalu di Terminal cPanel:

```bash
cd ~/public_html

# Backup folder lama
cp -r nisreport nisreport_backup_$(date +%Y%m%d)

# Ekstrak update (overwrite file yang ada)
unzip -o ~/nisreport_update.zip -d nisreport/

# Jalankan update
cd nisreport
php artisan optimize:clear
php artisan migrate --force
php artisan optimize
```

### 10.2 Backup Database Sebelum Update

```bash
# Backup database ke home folder
mysqldump -u cpaneluser_dbuser -p cpaneluser_nisreport > ~/backup_$(date +%Y%m%d).sql
```

### 10.3 Rollback Jika Gagal

```bash
# Restore file lama
rm -rf ~/public_html/nisreport
mv ~/public_html/nisreport_backup_YYYYMMDD ~/public_html/nisreport

# Restore database
mysql -u cpaneluser_dbuser -p cpaneluser_nisreport < ~/backup_YYYYMMDD.sql

# Clear cache
cd ~/public_html/nisreport
php artisan optimize:clear
```

---

## Ringkasan Perintah Penting

```bash
# ===== SETUP AWAL (jalankan berurutan) =====
cd ~/public_html/nisreport
cp .env.example .env && nano .env         # isi konfigurasi
chmod -R 775 storage bootstrap/cache
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan laravolt:indonesia:seed
php artisan storage:link
php artisan optimize

# ===== CEK STATUS =====
php artisan about
tail -n 50 storage/logs/laravel.log
ls -la public/storage

# ===== UPDATE RUTIN =====
php artisan optimize:clear
php artisan migrate --force
php artisan optimize

# ===== TROUBLESHOOT =====
php artisan optimize:clear
chmod -R 775 storage bootstrap/cache
composer dump-autoload --optimize
```

---

## Catatan Penting

| Setting | Nilai untuk Shared Hosting |
|---------|---------------------------|
| PHP | 8.3.31 |
| `SESSION_DRIVER` | `file` |
| `QUEUE_CONNECTION` | `sync` |
| `CACHE_STORE` | `file` |
| `APP_DEBUG` | `false` |
| `DB_HOST` | `localhost` |

---

*NISReport — Multi-Brand Order Management System*  
*Dokumentasi deployment via Terminal bawaan cPanel*
