# Deployment Guide — ProTrack ke cPanel

Panduan lengkap deployment aplikasi **ProTrack** (Laravel 12 + Inertia/React) ke server **cPanel** yang mendukung terminal (SSH access).

> **Dokumentasi ini mencakup 2 metode:**
> 1. **Metode GitHub** — Clone dari repository via terminal cPanel
> 2. **Metode Manual Upload** — Upload file via File Manager cPanel

---

## Table of Contents

- [1. Prasyarat Server](#1-prasyarat-server)
- [2. Persiapan Local (Sebelum Deploy)](#2-persiapan-local-sebelum-deploy)
- [3. Metode 1 — Deploy via GitHub (SSH/Terminal)](#3-metode-1--deploy-via-github-sshterminal)
- [4. Metode 2 — Deploy via Manual Upload](#4-metode-2--deploy-via-manual-upload)
- [5. Konfigurasi Environment (.env)](#5-konfigurasi-environment-env)
- [6. Setup Database](#6-setup-database)
- [7. Konfigurasi cPanel](#7-konfigurasi-cpanel)
- [8. Post-Deployment (Setelah Upload)](#8-post-deployment-setelah-upload)
- [9. Optimasi Production](#9-optimasi-production)
- [10. Konfigurasi Integrasi](#10-konfigurasi-integrasi)
- [11. Update / Redeploy](#11-update--redeploy)
- [12. Backup Strategy](#12-backup-strategy)
- [13. Security Hardening](#13-security-hardening)
- [14. Troubleshooting](#14-troubleshooting)
- [15. Checklist Go-Live](#15-checklist-go-live)

---

## 1. Prasyarat Server

### Minimum Requirements

| Komponen | Versi Minimum | Catatan |
|---|---|---|
| **PHP** | 8.2 (tested di 8.3) | Extensions wajib: `pdo_mysql`, `mbstring`, `xml`, `curl`, `zip`, `gd`, `bcmath`, `openssl`, `fileinfo`, `tokenizer`, `dom` |
| **Node.js** | 22.x LTS | Hanya untuk build assets lokal, **tidak perlu di server runtime** |
| **MariaDB/MySQL** | MariaDB 10.6+ / MySQL 8.0+ | Character set: `utf8mb4` |
| **Composer** | 2.x | Bisa via SSH atau bundled di vendor |
| **SSH/Terminal Access** | cPanel Terminal atau SSH | **Wajib** untuk Composer, Artisan, dan CLI commands |

### Cek Versi PHP di cPanel

```bash
# Login SSH ke server
ssh username@yourdomain.com

# Cek versi PHP
php -v

# Cek extensions yang aktif
php -m

# Pastikan extensions wajib ada:
# pdo_mysql, mbstring, xml, curl, zip, gd, bcmath, openssl, fileinfo, tokenizer, dom
```

### Cek via cPanel Dashboard

1. Login cPanel → cari **"Select PHP Version"** atau **"MultiPHP Manager"**
2. Pastikan PHP 8.2 atau 8.3 aktif
3. Di **"PHP Extensions"** → pastikan semua extension di atas centang aktif

> **Catatan Penting:** Jika extension belum aktif, hubungi provider hosting untuk mengaktifkan, atau aktifkan sendiri via cPanel → Select PHP Version → PHP Extensions.

---

## 2. Persiapan Local (Sebelum Deploy)

### 2.1 Build Assets Production

Di komputer lokal, jalankan build untuk menghasilkan file JavaScript/CSS production:

```bash
# Pastikan dependencies terinstall
npm ci

# Build untuk production (menghasilkan file di public/build/)
npm run build
```

### 2.2 Pastikan File .env.production.example Ada

```bash
# File ini sudah ada di repo sebagai template
ls .env.production.example
```

### 2.3 Buat .gitignore Bersih

Pastikan `.gitignore` sudah benar (file ini sudah benar di repo):

```
/node_modules
/public/build
/public/hot
/storage/*.key
/vendor
.env
.env.backup
.env.production
.phpunit.result.cache
Homestead.json
Homestead.yaml
auth.json
npm-debug.log
yarn-error.log
/.fleet
/.idea
/.vscode
```

> **PENTING:** File `.env` **TIDAK BOLEH** di-commit ke git karena berisi secrets.

### 2.4 Commit & Push ke GitHub

```bash
# Pastikan semua perubahan ter-commit
git add .
git status  # review files

# Commit
git commit -m "prep: build assets for production deployment"

# Push ke GitHub
git push origin main
```

---

## 3. Metode 1 — Deploy via GitHub (SSH/Terminal)

> **Metode ini REKOMENDASI** karena lebih cepat, clean, dan mudah update.
> **Syarat:** cPanel harus support SSH access dan Git sudah terinstall.

### 3.1 Login ke Server via SSH

```bash
ssh username@yourdomain.com
# Masukkan password saat diminta
```

Atau gunakan **cPanel Terminal**:
1. Login cPanel → cari **"Terminal"** di bagian Advanced
2. Klik **"Terminal"** → buka terminal web-based

### 3.2 Navigate ke Home Directory

```bash
# Biasanya home directory cPanel ada di:
cd ~

# Atau langsung ke public_html (document root)
cd ~/public_html
```

> **Catatan:** Document root cPanel biasanya `~/public_html` atau `~/public_html/yourdomain.com`. Sesuaikan dengan konfigurasi server Anda.

### 3.3 Clone Repository

```bash
# Clone repo ke folder protrack (atau nama yang diinginkan)
git clone https://github.com/username/protrack.git protrack

# Masuk ke folder project
cd protrack
```

**Jika repo private**, gunakan Personal Access Token:
```bash
# Generate token di GitHub → Settings → Developer settings → Personal access tokens
git clone https://YOUR_GITHUB_TOKEN@github.com/username/protrack.git protrack
```

### 3.4 Install PHP Dependencies

```bash
# Install Composer dependencies (exclude dev dependencies)
composer install --no-dev --optimize-autoloader
```

> **Jika Composer belum terinstall di server:**
> ```bash
> # Download Composer
> curl -sS https://getcomposer.org/installer | php
> 
> # Jadikan global
> sudo mv composer.phar /usr/local/bin/composer
> 
> # Cek versi
> composer --version
> ```

### 3.5 Build Assets (Node.js)

> **Metode A — Build di server (jika Node.js tersedia):**
> ```bash
> npm ci
> npm run build
> rm -rf node_modules  # tidak perlu di server runtime
> ```

> **Metode B — Build lokal, push, pull (REKOMENDASI):**
> Build di komputer lokal (`npm run build`), commit `public/build/` ke git, lalu pull di server. Ini menghemat resource server.

```bash
# Jika build di lokal sudah di-commit, cukup pull
git pull origin main
```

### 3.6 Setup Environment File

```bash
# Copy template
cp .env.production.example .env

# Generate APP_KEY
php artisan key:generate

# Edit .env (gunakan nano atau vi)
nano .env
```

Isi konfigurasi `.env` sesuai server (lihat [Bagian 5](#5-konfigurasi-environment-env) untuk detail).

### 3.7 Setup Database

```bash
# Buat database dan user via cPanel MySQL Databases
# Atau via terminal jika akses MySQL available:
mysql -u root -p -e "CREATE DATABASE protrack CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root -p -e "CREATE USER 'protrack_user'@'localhost' IDENTIFIED BY 'PASSWORD_KUAT_ANDA'"
mysql -u root -p -e "GRANT ALL ON protrack.* TO 'protrack_user'@'localhost'; FLUSH PRIVILEGES"
```

Jalankan migrasi dan seed:
```bash
# Jalankan migrasi
php artisan migrate --force

# Seed role & permission (6 role + 33 permissions)
php artisan db:seed --class=Database\\Seeders\\RolePermissionSeeder --force

# Seed brand default
php artisan db:seed --class=Database\\Seeders\\BrandSeeder --force

# Seed user default (superadmin, owner, dll)
php artisan db:seed --class=Database\\Seeders\\UserSeeder --force

# Seed master data (15 master + progress stages)
php artisan db:seed --class=Database\\Seeders\\MasterDataSeeder --force

# Seed data wilayah Indonesia (~80.000 rows, butuh ~30 detik)
php artisan laravolt:indonesia:seed
```

### 3.8 Set Permissions & Storage Link

```bash
# Set ownership (ganti 'username' dengan cPanel username Anda)
chown -R username:username /home/username/public_html/protrack

# Set permissions untuk storage dan cache
chmod -R 775 storage bootstrap/cache

# Buat storage symlink untuk uploaded files
php artisan storage:link
```

#### Jika `storage:link` Gagal — Opsi Alternatif

**Opsi A — Buat symlink manual:**
```bash
# Hapus public/storage jika sudah ada
rm -rf public/storage

# Buat symlink manual
ln -s /home/username/public_html/protrack/storage/app/public /home/username/public_html/protrack/public/storage
```

**Opsi B — Copy file langsung (jika symlink diblokir hosting):**
```bash
# Buat folder public/storage jika belum ada
mkdir -p public/storage

# Copy seluruh isi storage/app/public ke public/storage
cp -r storage/app/public/* public/storage/

# Copy juga subfolder jika ada
cp -r storage/app/public/.* public/storage/ 2>/dev/null || true
```
> **Catatan Penting:** Opsi B **tidak otomatis update** — setiap kali ada file baru di-upload, Anda harus copy ulang manual. Symlink (Opsi A / `artisan storage:link`) jauh lebih baik karena otomatis ter-sync.

**Opsi C — Buat script cron untuk sync otomatis (jika symlink diblokir):**
```bash
# Buat file sync script
nano ~/scripts/sync-storage.sh
```
```bash
#!/usr/bin/env bash
# Sync storage/app/public → public/storage (hanya file baru/updated)
rsync -av --update storage/app/public/ public/storage/
```
```bash
chmod +x ~/scripts/sync-storage.sh
```
Lalu tambahkan cron job di cPanel → **Cron Jobs**:
```
*/5 * * * * /bin/bash ~/scripts/sync-storage.sh >> /dev/null 2>&1
```
> Script ini jalan setiap 5 menit, sync hanya file baru/updated (tidak redundant).

**Opsi D — Hubungi provider hosting:**
Jika semua opsi di atas gagal, kemungkinan server memblokir symlink. Hubungi support provider hosting untuk:
1. Minta aktifkan symlink support
2. Atau minta dibuatkan symlink manual dari panel server

### 3.9 Konfigurasi Document Root (Web Root)

**PENTING:** Laravel menggunakan folder `public/` sebagai document root.

#### Opsi A — Subdomain mengarah ke /public (REKOMENDASI)

Di cPanel → **Subdomains** atau **Addon Domains**:
- Document Root: `/home/username/public_html/protrack/public`

#### Opsi B — Menggunakan .htaccess redirect

Jika document root harus `public_html/protrack/` (bukan `/public`), buat file `.htaccess` di root:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

#### Opsi C — Symbolic Link (Symlink)

```bash
# Dari public_html, buat symlink ke public/
cd ~/public_html
ln -s /home/username/public_html/protrack/public protrack
```

### 3.10 Verifikasi Deployment

```bash
# Cek status Laravel
php artisan --version

# Cek routes
php artisan route:list

# Cek config
php artisan config:cache

# Test app bisa diakses
curl -I https://yourdomain.com/login
```

---

## 4. Metode 2 — Deploy via Manual Upload

> **Metode ini untuk cPanel yang TIDAK support SSH/terminal, atau Anda lebih nyaman upload manual.**
> **Kekurangan:** Update manual lebih ribet, tidak ada version control di server.

### 4.1 Persiapan Local — Build & Kompres

Di komputer lokal:

```bash
# 1. Build assets production
npm ci
npm run build

# 2. Install composer dependencies
composer install --no-dev --optimize-autoloader

# 3. Hapus file/file yang tidak perlu di upload
rm -rf node_modules
rm -rf .git
rm -rf tests
rm -rf .phpunit.cache

# 4. Kompres semua file menjadi ZIP
# Windows (PowerShell):
Compress-Archive -Path * -DestinationPath protrack-upload.zip -Force

# macOS/Linux:
zip -r protrack-upload.zip . -x "node_modules/*" ".git/*" "tests/*"
```

> **Tips:** Jangan kompres `node_modules/` dan `.git/` karena tidak diperlukan di server.

### 4.2 Upload via cPanel File Manager

1. Login cPanel → buka **"File Manager"**
2. Navigate ke `public_html` (atau subfolder yang diinginkan, misal `public_html/protrack`)
3. Klik **"Upload"** di toolbar
4. Pilih file `protrack-upload.zip`
5. Tunggu hingga upload selesai
6. Klik kanan file ZIP → **"Extract"** → pilih destination → **"Extract File(s)"**

### 4.3 Setup via cPanel Terminal

Setelah file ter-extract, buka **cPanel Terminal** untuk menjalankan commands:

```bash
cd ~/public_html/protrack  # atau lokasi extract

# Install Composer dependencies (jika vendor belum di-upload)
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev --optimize-autoloader

# Setup .env
cp .env.production.example .env
php artisan key:generate

# Edit .env
nano .env
```

> **Catatan:** Jika Anda sudah meng-upload file `vendor/` yang sudah di-install lokal, langkah `composer install` bisa dilewati. Namun **disarankan tetap jalankan `composer install` di server** untuk compatibility.

### 4.4 Setup Database

Buat database via cPanel → **MySQL Databases**:

1. Buat Database baru: `protrack`
2. Buat Database User baru dengan password kuat
3. Tambahkan User ke Database dengan **ALL PRIVILEGES**

Atau via terminal:
```bash
mysql -u root -p -e "CREATE DATABASE protrack CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root -p -e "CREATE USER 'protrack_user'@'localhost' IDENTIFIED BY 'PASSWORD_KUAT'"
mysql -u root -p -e "GRANT ALL ON protrack.* TO 'protrack_user'@'localhost'; FLUSH PRIVILEGES"
```

Jalankan migrasi & seed:
```bash
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\RolePermissionSeeder --force
php artisan db:seed --class=Database\\Seeders\\BrandSeeder --force
php artisan db:seed --class=Database\\Seeders\\UserSeeder --force
php artisan db:seed --class=Database\\Seeders\\MasterDataSeeder --force
php artisan laravolt:indonesia:seed
```

### 4.5 Set Permissions & Storage Link

```bash
# Set permissions
chmod -R 775 storage bootstrap/cache

# Buat storage symlink
php artisan storage:link
```

> Jika `storage:link` gagal di shared hosting, lihat opsi alternatif lengkap di [Bagian 3.8 — Jika storage:link Gagal](#38-set-permissions--storage-link).

### 4.6 Konfigurasi Document Root

Sama seperti Metode 1, lihat [Bagian 3.9](#39-konfigurasi-document-root-web-root).

---

## 5. Konfigurasi Environment (.env)

Edit file `.env` di server (via Terminal/nano atau cPanel File Manager → Edit):

```bash
nano .env
```

### Template Konfigurasi Production

```env
# ========================
# ProTrack — Production
# ========================

# --- Application ---
APP_NAME=ProTrack
APP_ENV=production
APP_KEY=base64:XXX...XXX  # otomatis terisi oleh key:generate
APP_DEBUG=false
APP_URL=https://protrack.example.com  # GANTI dengan domain Anda

APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID
APP_TIMEZONE=Asia/Jakarta

APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

# --- Logging ---
LOG_CHANNEL=daily
LOG_STACK=daily
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning
LOG_DAILY_DAYS=30

# --- Database ---
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=protrack          # GANTI dengan nama DB Anda
DB_USERNAME=protrack_user     # GANTI dengan username DB Anda
DB_PASSWORD=PASSWORD_KUAT     # GANTI dengan password DB Anda

# --- Session ---
SESSION_DRIVER=database
SESSION_LIFETIME=1440
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# --- Queue & Cache ---
QUEUE_CONNECTION=database
CACHE_STORE=database

# --- Mail (untuk reset password, dll) ---
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@protrack.example.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Penjelasan Field Penting

| Field | Keterangan | Contoh |
|---|---|---|
| `APP_KEY` | Auto-generated oleh `php artisan key:generate` | `base64:xxx...` |
| `APP_URL` | URL lengkap tanpa trailing slash | `https://protrack.example.com` |
| `APP_DEBUG` | **WAJIB `false`** di production | `false` |
| `DB_HOST` | Biasanya `127.0.0.1` atau `localhost` di cPanel | `127.0.0.1` |
| `DB_DATABASE` | Nama database dari cPanel MySQL | `username_protrack` |
| `DB_USERNAME` | Username database dari cPanel | `username_protrack` |
| `SESSION_DRIVER` | Gunakan `database` (paling compatible di cPanel) | `database` |
| `MAIL_USERNAME` | Email atau username SMTP | `your-email@gmail.com` |

---

## 6. Setup Database

### 6.1 Buat Database & User di cPanel

**Via cPanel MySQL Databases:**

1. Login cPanel → **MySQL Databases**
2. Di bagian **"Create New Database"**:
   - Nama: `protrack` → Klik **Create Database**
3. Di bagian **"MySQL Users" → "Add New User"**:
   - Username: `protrack_user`
   - Password: **gunakan password kuat** (simpan di tempat aman)
   - Klik **Create User**
4. Di bagian **"Add User To Database"**:
   - Pilih User: `protrack_user`
   - Pilih Database: `protrack`
   - Privileges: centang **ALL PRIVILEGES**
   - Klik **Make Changes**

> **Catatan:** Nama database di cPanel biasanya otomatis di-prefix dengan username cPanel, misal `cpaneluser_protrack`.

### 6.2 Jalankan Migrasi & Seed

```bash
cd ~/public_html/protrack  # atau lokasi project Anda

# Migrasi tabel
php artisan migrate --force

# Seed role & permissions (6 role + 33 permissions)
php artisan db:seed --class=Database\\Seeders\\RolePermissionSeeder --force

# Seed brand default
php artisan db:seed --class=Database\\Seeders\\BrandSeeder --force

# Seed user default (superadmin, owner, dll)
php artisan db:seed --class=Database\\Seeders\\UserSeeder --force

# Seed master data (15 master + progress stages)
php artisan db:seed --class=Database\\Seeders\\MasterDataSeeder --force

# Seed data wilayah Indonesia (~80.000 rows, ~30 detik)
php artisan laravolt:indonesia:seed
```

### 6.3 Buat Session Table (untuk SESSION_DRIVER=database)

Jika menggunakan `SESSION_DRIVER=database`, pastikan tabel `sessions` sudah ada. Jalankan:

```bash
php artisan session:table
php artisan migrate --force
```

> Tabel ini biasanya sudah dimigrasi otomatis. Cek dengan:
> ```bash
> mysql -u protrack_user -p -e "SHOW TABLES LIKE 'sessions';" protrack
> ```

### 6.4 Seed Default Users

Setelah seed di atas, akun default yang terbuat:

| Email | Role | Password |
|---|---|---|
| superadmin@nisreport.local | Superadmin | `password` |
| owner@nisreport.local | Owner | `password` |
| admin.shu@nisreport.local | Admin Brand | `password` |
| admin.nis@nisreport.local | Admin Brand | `password` |
| reseller@nisreport.local | Reseller | `password` |
| produksi@nisreport.local | Admin Produksi | `password` |
| keuangan@nisreport.local | Admin Keuangan | `password` |

> **WAJIB:** Setelah login pertama, **ganti semua password default** untuk keamanan.

---

## 7. Konfigurasi cPanel

### 7.1 Document Root Laravel

**Ini langkah paling penting!** Laravel menggunakan `public/` sebagai web root.

#### Metode A — Subdomain (Paling Mudah)

1. cPanel → **Subdomains**
2. Buat subdomain:
   - Subdomain: `app` (hasil: `app.yourdomain.com`)
   - Document Root: `/home/username/public_html/protrack/public`
3. Klik **Create**

#### Metode B — Addon Domain

1. cPanel → **Addon Domains**
2. Isi:
   - New Domain Name: `yourdomain.com`
   - Document Root: `/home/username/public_html/protrack/public`
3. Klik **Add Domain**

#### Metode C — Parked/Addon dengan .htaccess

Jika document root harus di `public_html/protrack/` (di luar `/public`):

Buat file `.htaccess` di `public_html/protrack/`:

```apache
<IfModule mod_rewrite.c>
    Options -MultiViews -Indexes
    RewriteEngine On

    # Handle Front Controller
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]

    # Redirect all requests to public folder
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

### 7.2 PHP Version & Extensions

1. cPanel → **"Select PHP Version"** atau **"MultiPHP Manager"**
2. Pilih PHP **8.2** atau **8.3** (yang lebih baru)
3. Di **PHP Extensions**, pastikan centang aktif:

| Extension | Status |
|---|---|
| `pdo_mysql` | ✅ Wajib |
| `mbstring` | ✅ Wajib |
| `xml` | ✅ Wajib |
| `curl` | ✅ Wajib |
| `zip` | ✅ Wajib |
| `gd` | ✅ Wajib |
| `bcmath` | ✅ Wajib |
| `openssl` | ✅ Wajib |
| `fileinfo` | ✅ Wajib |
| `tokenizer` | ✅ Wajib |
| `dom` | ✅ Wajib |
| `fileinfo` | ✅ Wajib |
| `intl` | ⚠️ Recommended |
| `imagick` | ⚠️ Optional (lebih baik dari gd untuk image processing) |

### 7.3 PHP Settings (via cPanel)

1. cPanel → **"Select PHP Version"** → **"Options"** atau **"PHP Configuration"**
2. Set nilai berikut:

```ini
upload_max_filesize = 20M
post_max_size = 25M
max_execution_time = 120
max_input_time = 120
memory_limit = 256M
```

> Nilai ini memungkinkan upload gambar desain PO yang cukup besar.

### 7.4 Cron Jobs (Schedule)

Laravel butuh cron job untuk menjalankan scheduled tasks (scheduled reports, dll).

1. cPanel → **Cron Jobs**
2. Set **Cron Email** (untuk notifikasi error)
3. Tambahkan cron entry berikut:

```
* * * * * cd /home/username/public_html/protrack && php artisan schedule:run >> /dev/null 2>&1
```

> **Tips:** Jika cron entry di atas tidak jalan, coba gunakan path lengkap PHP:
> ```
> * * * * * /usr/bin/php /home/username/public_html/protrack/artisan schedule:run >> /dev/null 2>&1
> ```
> Cek path PHP: `which php` di terminal.

### 7.5 Error Pages & Logging

1. Pastikan `storage/logs/` bisa ditulis:
   ```bash
   chmod -R 775 storage/logs
   ```

2. Untuk melihat logs via cPanel:
   - cPanel → **File Manager** → navigate ke `storage/logs/`
   - Buka file `laravel.log`

3. Atau via terminal:
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## 8. Post-Deployment (Setelah Upload)

### 8.1 Jalankan Optimization Commands

```bash
cd ~/public_html/protrack  # atau lokasi project

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views (Blade templates)
php artisan view:cache

# Cache events
php artisan event:cache

# Optimize Composer autoloader
composer dump-autoload --optimize --classmap-authoritative
```

### 8.2 Buat Storage Symlink

```bash
php artisan storage:link
```

Ini membuat symlink dari `public/storage` → `storage/app/public` agar file upload bisa diakses publik (gambar invoice, logo brand, dll).

#### Verifikasi Symlink

```bash
# Cek apakah symlink sudah ada dan benar
ls -la public/storage
# Harus menunjukkan: public/storage -> ../storage/app/public

# Test akses gambar (ganti dengan path gambar yang ada)
curl -I https://yourdomain.com/storage/path-ke-gambar.jpg
# Harus return HTTP/2 00 (bukan 404)
```

#### Jika `storage:link` Gagal — Opsi Alternatif

Lihat opsi alternatif lengkap di [Bagian 3.8 — Jika storage:link Gagal](#38-set-permissions--storage-link):
- **Opsi A:** Symlink manual via `ln -s`
- **Opsi B:** Copy file langsung via `cp -r` (tidak otomatis sync)
- **Opsi C:** Script cron sync otomatis via `rsync` setiap 5 menit
- **Opsi D:** Hubungi provider hosting untuk aktifkan symlink support

> **Yang paling umum di shared hosting:** Opsi B (copy manual) atau Opsi C (cron sync) karena symlink sering diblokir.

### 8.3 Verifikasi Deployment

```bash
# Cek Laravel version
php artisan --version

# Cek routes
php artisan route:list --columns=method,uri

# Test HTTP response
curl -I https://yourdomain.com/login
# Harus return HTTP/2 200 (bukan 500 atau 301 loop)
```

### 8.4 Login & Test Fitur

1. Buka browser → `https://yourdomain.com/login`
2. Login dengan akun default:
   - Email: `superadmin@nisreport.local`
   - Password: `password`
3. Test fitur utama:
   - Dashboard
   - Manajemen Order (CRUD)
   - Invoice Preview & PDF
   - Upload gambar
   - Role switching (brand switcher)

### 8.5 Ganti Password Default

**WAJIB setelah login pertama:**
1. Login sebagai Superadmin
2. Ganti password semua akun default ke password production yang kuat

---

## 9. Optimasi Production

### 9.1 Laravel Optimization

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
composer dump-autoload --optimize --classmap-authoritative
```

> Jalankan ulang optimization setiap kali ada update code.

### 9.2 OPcache (Jika Server Menggunakan PHP-FPM)

Jika server menggunakan PHP-FPM (bukan shared hosting), edit `php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
```

> Di shared hosting cPanel, OPcache biasanya sudah di-manage oleh provider. Tidak perlu setting manual.

### 9.3 File Permission Final

```bash
# Pastikan permissions benar
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;

# .env harus readable hanya oleh user
chmod 600 .env
```

### 9.4 Disable Debug Mode

Pastikan di `.env`:
```
APP_DEBUG=false
```

Dan pastikan `vendor/laravel/framework/src/Illuminate/Foundation/ErrorHandler.php` tidak di-patch (default sudah benar).

---

## 10. Konfigurasi Integrasi

Setelah aplikasi berjalan, konfigurasi integrasi pihak ke-3 via UI:

1. Login sebagai **Superadmin**
2. Buka **Pengaturan → Integrasi**
3. Konfigurasi:

| Layanan | Keterangan | Status |
|---|---|---|
| **Gemini AI** | API key dari [aistudio.google.com/apikey](https://aistudio.google.com/apikey) | Opsional (mock mode jika kosong) |
| **WhatsApp (Sidobe)** | API URL + API key + default recipient | Opsional |
| **Telegram Bot** | Bot token dari `@BotFather` + chat ID dari `@userinfobot` | Opsional |
| **Channel Default** | Pilih `whatsapp`, `telegram`, atau `both` | Opsional |

> **Tanpa konfigurasi ini**, sistem tetap berjalan dalam **mock mode** — UI berfungsi penuh, pesan tidak terkirim. Aman untuk testing.

---

## 11. Update / Redeploy

> **PRINSIP UTAMA:** Database aman — seeder pakai `firstOrCreate`/`updateOrCreate`, tidak hapus data produksi.

### 11.1 Backup Sebelum Update (WAJIB)

```bash
# Backup database
mysqldump -u protrack_user -p protrack > ~/backup/protrack-db-$(date +%Y%m%d-%H%M).sql

# Backup file (opsional tapi direkomendasikan)
tar -czf ~/backup/protrack-files-$(date +%Y%m%d-%H%M).tar.gz ~/public_html/protrack/ --exclude=node_modules --exclude=vendor
```

### 11.2 Via SSH (Metode GitHub)

```bash
cd ~/public_html/protrack

# 1. Aktifkan maintenance mode
php artisan down --refresh=15

# 2. Pull update terbaru
git pull origin main

# 3. Update dependencies
composer install --no-dev --optimize-autoloader

# 4. Build assets (jika build di server)
npm ci && npm run build

# 5. Jalankan migrasi (aman — tidak hapus kolom/tabel lama)
php artisan migrate --force

# 6. Seed ulang master data (updateOrCreate — aman, tidak duplikat)
php artisan db:seed --class=Database\\Seeders\\RolePermissionSeeder --force
php artisan db:seed --class=Database\\Seeders\\BrandSeeder --force
php artisan db:seed --class=Database\\Seeders\\UserSeeder --force
php artisan db:seed --class=Database\\Seeders\\MasterDataSeeder --force

# 7. Clear semua cache, lalu re-cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 8. Clear bootstrap cache (opsional tapi disarankan)
rm -rf bootstrap/cache/*

# 9. Pastikan storage link masih ada
php artisan storage:link

# 10. Matikan maintenance mode
php artisan up
```

### 11.3 Via Manual Upload

1. Build & kompres di lokal (lihat [Bagian 4.1](#41-persiapan-local--build--kompres))
2. Upload ZIP ke cPanel File Manager
3. **Backup dulu!** — jangan extract langsung ke atas file lama
4. Extract → Replace file yang berubah
5. Jalankan commands di terminal:
   ```bash
   cd ~/public_html/protrack

   # Dependencies
   composer install --no-dev --optimize-autoloader

   # Migrasi
   php artisan migrate --force

   # Seed master data (updateOrCreate — aman)
   php artisan db:seed --class=Database\\Seeders\\RolePermissionSeeder --force
   php artisan db:seed --class=Database\\Seeders\\BrandSeeder --force
   php artisan db:seed --class=Database\\Seeders\\UserSeeder --force
   php artisan db:seed --class=Database\\Seeders\\MasterDataSeeder --force

   # Clear & re-cache
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   php artisan event:clear
   rm -rf bootstrap/cache/*
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan event:cache

   # Storage link
   php artisan storage:link
   ```

### 11.4 Rollback Jika Gagal

```bash
# Rollback migrasi terakhir
php artisan migrate:rollback

# Restore database dari backup
mysql -u protrack_user -p protrack < ~/backup/protrack-db-YYYYMMDD-HHMM.sql

# Matikan maintenance mode
php artisan up
```

### 11.5 Yang Tidak Berubah Saat Update

| Item | Catatan |
|---|---|
| **Data produksi** (orders, customers, invoices) | Tidak terpengaruh oleh seeder |
| **Uploaded files** (gambar, logo) | Aman di `storage/app/public` — tidak di-overwrite git |
| **`.env` configuration** | Tidak di-overwrite git pull |
| **Database schema** | Migrasi bersifat additive (tambah kolom/tabel, tidak hapus) |

---

## 12. Backup Strategy

### 12.1 Database Backup Harian

Buat script backup di `~/scripts/backup-protrack.sh`:

```bash
#!/usr/bin/env bash
set -euo pipefail

BACKUP_DIR=~/backups/protrack
DATE=$(date +%Y%m%d-%H%M)
mkdir -p "$BACKUP_DIR"

# Backup database
mysqldump --single-transaction --quick protrack \
  | gzip > "$BACKUP_DIR/db-$DATE.sql.gz"

# Backup uploaded files
tar -czf "$BACKUP_DIR/storage-$DATE.tar.gz" \
  -C ~/public_html/protrack/storage/app/public . 2>/dev/null || true

# Retain 14 hari
find "$BACKUP_DIR" -mtime +14 -type f -delete
```

### 12.2 Cron Job Backup

1. cPanel → **Cron Jobs**
2. Tambahkan:
   ```
   0 2 * * * /bin/bash ~/scripts/backup-protrack.sh
   ```

### 12.3 Manual Backup via cPanel

1. cPanel → **Backup** atau **Backup Wizard**
2. **Download a Full Account Backup** — backup semua
3. **Download a MySQL Database Backup** — backup hanya database

---

## 13. Security Hardening

### Checklist Keamanan

- [ ] `.env` permission 600 → `chmod 600 .env`
- [ ] `APP_DEBUG=false` di production
- [ ] `APP_ENV=production`
- [ ] Database user dengan privilege terbatas (hanya ALL pada DB project)
- [ ] HTTPS aktif (aktifkan di cPanel → **SSL/TLS** → **Let's Encrypt**)
- [ ] `SESSION_SECURE_COOKIE=true` di `.env`
- [ ] `SESSION_ENCRYPT=true` di `.env`
- [ ] Semua password default sudah diganti
- [ ] File `storage/logs/laravel.log` tidak publicly accessible
- [ ] Folder `vendor/` tidak publicly accessible
- [ ] File `.git/` tidak publicly accessible

### 13.1 Aktifkan HTTPS (SSL)

**Via cPanel Let's Encrypt (gratis):**

1. cPanel → **SSL/TLS** → **Let's Encrypt**
2. Pilih domain → **Issue** → selesai

**Atau via AutoSSL:**
1. cPanel → **SSL/TLS** → **SSL Certificate Status**
2. Klik **"Check"** atau **"Run AutoSSL"**

### 13.2 Protect Sensitive Directories

Tambahkan file `.htaccess` di direktori berikut untuk mencegah akses langsung:

**`vendor/.htaccess`:**
```apache
Deny from all
```

**`storage/.htaccess`:**
```apache
Deny from all

# Kecuali public storage
<Files "logs/laravel.log">
    Deny from all
</Files>
```

### 13.3 Rate Limiting

Laravel 12 sudah built-in rate limiting. Endpoint publik (invoice, tracking) sudah di-rate-limit 60/menit.

---

## 14. Troubleshooting

### Masalah Umum & Solusi

| Masalah | Kemungkinan Penyebab | Solusi |
|---|---|---|
| **500 Server Error** | `.env` belum diisi, APP_KEY kosong, atau permission salah | Cek `storage/logs/laravel.log`, pastikan `.env` terisi & `APP_KEY` ada |
| **404 Not Found** | Document root salah (tidak mengarah ke `/public`) | Pastikan document root = `protrack/public` |
| **CSS/JS 404** | Assets belum di-build | Jalankan `npm run build` di lokal, push, lalu pull di server |
| **White Blank Page** | PHP error tersembunyi | Cek error log: `tail -50 storage/logs/laravel.log` |
| **Login Loop Redirect** | Session driver salah | Pastikan `SESSION_DRIVER=database` + tabel `sessions` ada |
| **"No Application encryption key has been specified"** | APP_KEY belum di-generate | Jalankan `php artisan key:generate` |
| **PDF SPK Kosong** | DomPDF font issue | Pastikan extension `gd` aktif di PHP |
| **Upload Gambar Gagal** | `upload_max_filesize` terlalu kecil | Set `upload_max_filesize = 20M` di PHP settings cPanel |
| **Permission Denied di storage** | Ownership/permission salah | `chown -R username:username storage` + `chmod -R 775 storage` |
| **Cron Schedule tidak jalan** | Cron job salah atau PHP path salah | Cek cron job di cPanel, gunakan path PHP lengkap |
| **Database Connection Refused** | DB_HOST salah atau DB belum dibuat | Pastikan `DB_HOST=127.0.0.1` + database sudah dibuat di cPanel |
| **Composer Not Found** | Composer belum terinstall | Install: `curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer` |
| **Node Module Errors** | `node_modules` terbawa di upload | Hapus `node_modules` jika menggunakan manual upload |
| **Gambar/Tampilan upload tidak muncul (404)** | Symlink `storage:link` belum jalan atau diblokir hosting | Cek `ls -la public/storage`, jika tidak ada symlink → buat manual atau copy folder. Lihat [Bagian 3.8](#38-set-permissions--storage-link) |
| **Gambar hilang setelah redeploy (manual upload)** | Folder `public/storage` tidak ter-upload / ter-overwrite | Upload ulang isi `storage/app/public/` ke `public/storage/`, atau gunakan Opsi C (cron sync) dari [Bagian 3.8](#38-set-permissions--storage-link) |
| **Update error: Class not found** | `composer install` belum dijalankan setelah pull | Jalankan `composer install --no-dev --optimize-autoloader` lalu clear cache |
| **Update error: Column not found / Table doesn't exist** | Migrasi belum dijalankan | Jalankan `php artisan migrate --force` |
| **Menu/Role hilang setelah update** | Role/permission berubah di codebase | Jalankan ulang seeder: `php artisan db:seed --class=Database\\Seeders\\RolePermissionSeeder --force` |
| **Tampilan berantakan setelah update** | Assets belum di-rebuild | Clear cache: `php artisan config:clear && php artisan view:clear && rm -rf bootstrap/cache/*` lalu `php artisan config:cache` |

### Melihat Error Log

```bash
# Via terminal
tail -100 storage/logs/laravel.log

# Atau filter error saja
grep -i "error\|exception" storage/logs/laravel.log | tail -50

# Bersihkan log setelah fix
php artisan log:clear
```

### Reset Cache

```bash
# Jika ada masalah cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# Re-cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 15. Checklist Go-Live

Sebelum aplikasi digunakan production, pastikan semua terpenuhi:

### Pre-Launch

- [ ] Semua file sudah ter-upload (Metode GitHub atau Manual)
- [ ] `composer install --no-dev --optimize-autoloader` sudah dijalankan
- [ ] `npm run build` sudah dijalankan (assets di `public/build/` ada)
- [ ] `.env` sudah diisi lengkap dengan `APP_DEBUG=false`
- [ ] `APP_KEY` sudah di-generate
- [ ] Database sudah dibuat + user dengan privileges
- [ ] `php artisan migrate --force` sudah dijalankan
- [ ] Seeder dijalankan satu per satu:
  - [ ] `php artisan db:seed --class=Database\\Seeders\\RolePermissionSeeder --force`
  - [ ] `php artisan db:seed --class=Database\\Seeders\\BrandSeeder --force`
  - [ ] `php artisan db:seed --class=Database\\Seeders\\UserSeeder --force`
  - [ ] `php artisan db:seed --class=Database\\Seeders\\MasterDataSeeder --force`
- [ ] `php artisan storage:link` sudah dijalankan **ATAU** alternatif (copy/cron sync) sudah di-setup jika symlink diblokir hosting
- [ ] Document root sudah dikonfigurasi ke `public/`
- [ ] PHP version 8.2+ aktif + semua extensions aktif
- [ ] SSL/HTTPS sudah aktif
- [ ] Cron job schedule sudah ditambahkan di cPanel

### Security

- [ ] Semua password default sudah diganti
- [ ] `.env` permission 600
- [ ] `APP_DEBUG=false`
- [ ] Database user hanya punya privilege di DB project
- [ ] Folder `vendor/` dan `storage/` di-protect dari akses publik
- [ ] HTTPS aktif + `SESSION_SECURE_COOKIE=true`

### Functional Testing

- [ ] Halaman login bisa diakses
- [ ] Login dengan akun superadmin berhasil
- [ ] Dashboard menampilkan data dengan benar
- [ ] CRUD Order berfungsi (create, edit, delete)
- [ ] Invoice preview & PDF berfungsi
- [ ] Upload gambar berfungsi
- [ ] Brand switcher berfungsi
- [ ] Scheduled reports jalan via cron
- [ ] Integrasi AI/WA/Telegram sudah dikonfigurasi (atau mock mode acceptable)

### Post-Launch Monitoring

- [ ] Monitor `storage/logs/laravel.log` untuk error
- [ ] Monitor cron job execution
- [ ] Backup harian terverifikasi bisa di-restore
- [ ] Uptime monitoring (UptimeRobot / BetterStack)

---

## Quick Reference — Command Cheat Sheet

```bash
# === SETUP AWAL ===
git clone https://github.com/username/protrack.git protrack
cd protrack
composer install --no-dev --optimize-autoloader
cp .env.production.example .env
php artisan key:generate
nano .env                          # isi konfigurasi
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\RolePermissionSeeder --force
php artisan db:seed --class=Database\\Seeders\\BrandSeeder --force
php artisan db:seed --class=Database\\Seeders\\UserSeeder --force
php artisan db:seed --class=Database\\Seeders\\MasterDataSeeder --force
php artisan laravolt:indonesia:seed
chmod -R 775 storage bootstrap/cache
php artisan storage:link

# === OPTIMIZATION ===
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
composer dump-autoload --optimize --classmap-authoritative

# === UPDATE/REDEPLOY ===
php artisan down --refresh=15
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build                   # jika build di server
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\RolePermissionSeeder --force
php artisan db:seed --class=Database\\Seeders\\BrandSeeder --force
php artisan db:seed --class=Database\\Seeders\\UserSeeder --force
php artisan db:seed --class=Database\\Seeders\\MasterDataSeeder --force
php artisan config:clear route:clear view:clear event:clear
rm -rf bootstrap/cache/*
php artisan config:cache route:cache view:cache event:cache
php artisan storage:link
php artisan up

# === TROUBLESHOOTING ===
tail -100 storage/logs/laravel.log
php artisan config:clear && php artisan config:cache
php artisan route:clear && php artisan route:cache
php artisan view:clear && php artisan view:cache
php artisan --version
php artisan route:list

# === BACKUP ===
mysqldump -u protrack_user -p protrack > backup.sql
```

---

## Perbedaan Metode GitHub vs Manual Upload

| Aspek | GitHub (SSH) | Manual Upload |
|---|---|---|
| **Kecepatan Setup** | ⚡ Cepat (clone + install) | 🐌 Lambat (upload ZIP + extract) |
| **Update/Redeploy** | ⚡ `git pull` + migrate | 🐌 Upload ZIP baru + extract |
| **Version Control** | ✅ Ada (git history) | ❌ Tidak ada |
| **Rollback** | ✅ Mudah (`git revert`) | ⚠️ Harus backup manual |
| **File Size Upload** | ✅ Incremental (hanya perubahan) | ❌ Full ZIP setiap update |
| **SSH Required** | ✅ Ya | ❌ Tidak (bisa File Manager saja) |
| **Node.js Required** | ⚠️ Opsional (bisa build lokal) | ❌ Build di lokal wajib |
| **Complexity** | Medium (butuh SSH familiar) | Low (cPanel familiar) |
| **Rekomendasi** | ⭐ **REKOMENDASI** | Untuk shared hosting tanpa SSH |

---

> **Dokumentasi ini dibuat untuk ProTrack v1.0 (Phase 7) — Laravel 12 + Inertia/React**
> Terakhir diperbarui: Juli 2026
