# Deployment Guide — ProTrack

Panduan menjalankan ProTrack di production server (Ubuntu 22.04+ / Debian 12).

## 1. Prasyarat Server

| Komponen | Versi Minimum | Catatan |
|---|---|---|
| PHP | 8.2 (sudah diuji di 8.3) | extensions: `pdo_mysql`, `mbstring`, `xml`, `curl`, `zip`, `gd`, `bcmath`, `openssl`, `fileinfo`, `tokenizer` |
| Composer | 2.x | |
| Node.js | 22.x LTS | hanya untuk build assets, tidak perlu di runtime |
| MariaDB/MySQL | MariaDB 10.6+ / MySQL 8.0+ | utf8mb4 |
| Nginx | 1.18+ | |
| Supervisor | 4.x | untuk queue worker + schedule |

## 2. Setup Awal

```bash
# Clone & install
git clone <repo-url> /var/www/protrack
cd /var/www/protrack
composer install --no-dev --optimize-autoloader
npm ci && npm run build
rm -rf node_modules  # tidak diperlukan di runtime

# Env
cp .env.production.example .env
php artisan key:generate
nano .env  # isi DB, APP_URL, mail, API keys

# Database
mysql -u root -p -e "CREATE DATABASE protrack CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root -p -e "CREATE USER 'protrack'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD'"
mysql -u root -p -e "GRANT ALL ON protrack.* TO 'protrack'@'localhost'; FLUSH PRIVILEGES"

php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\RolePermissionSeeder --force
php artisan db:seed --class=Database\\Seeders\\MasterDataSeeder --force
php artisan db:seed --class=Database\\Seeders\\FinanceSeeder --force
php artisan laravolt:indonesia:seed   # ~80k rows; butuh ~30 detik

# Permissions
chown -R www-data:www-data /var/www/protrack
chmod -R 775 storage bootstrap/cache

# Storage symlink (untuk uploaded files)
php artisan storage:link
```

## 3. Optimasi Production

**Wajib** sebelum go-live untuk performa maksimal:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
composer dump-autoload --optimize --classmap-authoritative
```

Setiap deploy/release, ulangi langkah di atas.

OPcache PHP harus ON (`/etc/php/8.3/fpm/php.ini`):
```
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0   # production
opcache.revalidate_freq=0
```

Restart: `sudo systemctl restart php8.3-fpm`

## 4. Nginx Config

File `/etc/nginx/sites-available/protrack`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name protrack.example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name protrack.example.com;
    root /var/www/protrack/public;

    ssl_certificate /etc/letsencrypt/live/protrack.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/protrack.example.com/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|svg|webp|css|js|woff|woff2)$ {
        expires 30d;
        access_log off;
        add_header Cache-Control "public, immutable";
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 60s;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 20M;  # untuk upload gambar
}
```

Aktifkan: `ln -s /etc/nginx/sites-available/protrack /etc/nginx/sites-enabled/ && nginx -t && systemctl reload nginx`

Generate SSL:
```bash
certbot --nginx -d protrack.example.com
```

## 5. Supervisor — Queue + Schedule

Laravel Schedule (cron) butuh runner setiap menit. Gunakan Supervisor untuk reliability:

File `/etc/supervisor/conf.d/protrack.conf`:

```ini
[program:protrack-schedule]
process_name=%(program_name)s
command=php /var/www/protrack/artisan schedule:work
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/supervisor/protrack-schedule.log
stopwaitsecs=3600

[program:protrack-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/protrack/artisan queue:work --queue=default --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/protrack-queue.log
stopwaitsecs=3600
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl status
```

Alternatif (cron):
```bash
# crontab -u www-data -e
* * * * * cd /var/www/protrack && php artisan schedule:run >> /dev/null 2>&1
```

## 6. Backup Strategy

### Database backup harian (kompres + retain 14 hari)

File `/usr/local/bin/protrack-backup.sh`:

```bash
#!/usr/bin/env bash
set -euo pipefail
BACKUP_DIR=/var/backups/protrack
DATE=$(date +%Y%m%d-%H%M)
mkdir -p "$BACKUP_DIR"

# Backup DB
mysqldump --single-transaction --quick --routines --triggers protrack \
  | gzip > "$BACKUP_DIR/db-$DATE.sql.gz"

# Backup uploaded files (storage/app/public)
tar -czf "$BACKUP_DIR/storage-$DATE.tar.gz" -C /var/www/protrack/storage/app/public . 2>/dev/null || true

# Retain 14 hari
find "$BACKUP_DIR" -mtime +14 -type f -delete
```

Cron: `0 2 * * * /usr/local/bin/protrack-backup.sh`

Untuk backup offsite, gunakan `rclone sync $BACKUP_DIR remote:protrack-backup/`.

## 7. Security Hardening Checklist

- [ ] `.env` mode permission 600 (`chmod 600 .env`)
- [ ] `APP_DEBUG=false` di production
- [ ] `APP_ENV=production`
- [ ] DB user dengan privilege terbatas (tidak SUPER, hanya GRANT ALL pada DB project)
- [ ] HTTPS aktif + HSTS header
- [ ] Rate limiting `throttle` aktif (sudah default di Laravel 12)
- [ ] Update OS reguler: `apt update && apt upgrade`
- [ ] Firewall: UFW allow 80, 443, 22 only
- [ ] Fail2ban untuk SSH
- [ ] Session cookies: HttpOnly + Secure + SameSite=Lax (sudah default)
- [ ] Backup database terverifikasi bisa di-restore (test sekali sebulan)
- [ ] Log rotation di `storage/logs/laravel.log` (gunakan `logrotate`)
- [ ] Public invoice & tracking sudah rate-limited 60/menit
- [ ] Kredensial Sidobe/Gemini/Telegram disimpan terenkripsi di `system_settings`

## 8. Update / Redeploy

```bash
cd /var/www/protrack
php artisan down --refresh=15
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache route:cache view:cache event:cache
php artisan storage:link  # safe, no-op kalau sudah ada
sudo supervisorctl restart all
php artisan up
```

## 9. Konfigurasi Integrasi (Setelah Go-Live)

Login sebagai **superadmin** → **Pengaturan → Integrasi**:

1. **Gemini AI** — paste API key dari https://aistudio.google.com/apikey (free tier ~60 RPM)
2. **WhatsApp (Sidobe)** — API URL + API key + default recipient
3. **Telegram** — bot token dari `@BotFather` + chat ID dari `@userinfobot`
4. **Channel Default** — pilih `whatsapp`, `telegram`, atau `both`

Klik **Test Koneksi** di tiap section untuk verifikasi.

Tanpa konfigurasi ini, sistem akan jalan dalam **mock mode** — UI tetap berfungsi tapi pesan tidak terkirim (aman untuk testing).

## 10. Troubleshooting

| Gejala | Penyebab umum | Fix |
|---|---|---|
| 500 server error | `APP_DEBUG=false` + log kosong | `tail -100 storage/logs/laravel.log` + `chown -R www-data storage` |
| Asset CSS/JS 404 | Build belum dijalankan | `npm run build` |
| Login loop redirect | Session driver salah | Cek `SESSION_DRIVER=database` + table `sessions` ada |
| PDF SPK kosong | DomPDF font issue | Pastikan `gd` extension aktif, `chmod 775 storage/framework/cache` |
| Scheduled report tidak jalan | Supervisor mati | `supervisorctl status protrack-schedule` |
| AI mock terus | API key tidak ke-save | Cek pengaturan, klik Test Koneksi, lihat log Laravel |
| Permission denied di storage | Owner salah | `chown -R www-data:www-data storage bootstrap/cache` |

## 11. Monitoring (Opsional)

- **Application logs**: `tail -f storage/logs/laravel.log`
- **Nginx access**: `/var/log/nginx/access.log`
- **PHP-FPM slow log**: `/var/log/php8.3-fpm-slow.log` (aktifkan di `www.conf`)
- **MariaDB slow query**: `slow_query_log = 1` di `/etc/mysql/mariadb.conf.d/50-server.cnf`
- **Uptime check**: gunakan UptimeRobot / BetterStack untuk endpoint `/login`

Untuk APM lebih mendalam: pasang Laravel Telescope (dev only) atau Sentry/New Relic untuk error tracking production.
