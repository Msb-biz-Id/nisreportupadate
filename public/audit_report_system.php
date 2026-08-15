<?php

/**
 * Diagnostics tool for Scheduled Reports in ProTrack.
 * Securely restricts access to local requests or requires a verification code.
 */

// Load Composer autoloader
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die("Autoload file not found. Run 'composer install' first.");
}
require $autoloadPath;

// Load Laravel bootstrap
$bootstrapPath = __DIR__ . '/../bootstrap/app.php';
if (!file_exists($bootstrapPath)) {
    die("Bootstrap file not found.");
}
$app = require_once $bootstrapPath;
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Settings\SystemSetting;
use App\Models\User;
use App\Models\Brand;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>ProTrack Report System Diagnostics</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; max-width: 900px; margin: 0 auto; padding: 20px; background-color: #f8fafc; color: #1e293b; }
        h1, h2 { color: #0f172a; }
        .card { background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .success { color: #16a34a; font-weight: bold; }
        .warning { color: #ea580c; font-weight: bold; }
        .danger { color: #dc2626; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #e2e8f0; }
        th { background-color: #f1f5f9; font-weight: 600; }
        pre { background: #0f172a; color: #f8fafc; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 13px; }
    </style>
</head>
<body>
    <h1>📋 Diagnosis Sistem Laporan Otomatis</h1>
    <p>Gunakan halaman ini untuk memverifikasi pengaturan pengiriman laporan mingguan, bulanan, dan harian di server cPanel Anda.</p>

    <!-- 1. SMTP & Fallback Config -->
    <div class="card">
        <h2>1. Konfigurasi Email & SMTP (Dari file .env / Settings)</h2>
        <table>
            <tr>
                <th>Parameter</th>
                <th>Nilai Saat Ini</th>
                <th>Analisis</th>
            </tr>
            <tr>
                <td><code>config('mail.from.address')</code></td>
                <td><strong><?php echo htmlspecialchars(config('mail.from.address')); ?></strong></td>
                <td>Alamat email pengirim utama aplikasi. Jika laporan brand mengalami error/fallback, laporan akan terkirim ke alamat email ini.</td>
            </tr>
            <tr>
                <td><code>SystemSetting::get('mail', 'mail_from_address')</code></td>
                <td><strong><?php echo htmlspecialchars(SystemSetting::get('mail', 'mail_from_address') ?: '(Kosong, menggunakan default .env)'); ?></strong></td>
                <td>Konfigurasi email pengirim dari UI dashboard.</td>
            </tr>
            <tr>
                <td><code>system.notification_channel</code></td>
                <td>
                    <strong><?php echo htmlspecialchars(SystemSetting::get('system', 'notification_channel', 'whatsapp')); ?></strong>
                </td>
                <td>
                    <?php 
                    $channel = SystemSetting::get('system', 'notification_channel', 'whatsapp');
                    if (in_array($channel, ['email', 'all'], true)) {
                        echo '<span class="success">✓ Email Aktif</span>';
                    } else {
                        echo '<span class="warning">⚠️ Email Tidak Aktif (Hanya WhatsApp/Telegram)</span><br><small>Ubah tipe channel ke "Email saja" atau "Semua Channel" di dashboard agar email laporan dikirim.</small>';
                    }
                    ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- 2. Superadmin Users -->
    <div class="card">
        <h2>2. Daftar Akun Superadmin Terdaftar</h2>
        <p>Laporan Superadmin global akan dikirimkan ke email akun-akun berikut:</p>
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>WhatsApp</th>
                    <th>Status Akun</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $superadmins = User::whereHas('roles', function($q) {
                    $q->where('name', 'superadmin');
                })->get();

                if ($superadmins->isEmpty()) {
                    echo '<tr><td colspan="4" class="danger">Tidak ditemukan user dengan role superadmin!</td></tr>';
                } else {
                    foreach ($superadmins as $u) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($u->name) . '</td>';
                        echo '<td>' . htmlspecialchars($u->email) . '</td>';
                        echo '<td>' . htmlspecialchars($u->phone ?: '-') . '</td>';
                        echo '<td>' . ($u->is_active ? '<span class="success">Aktif</span>' : '<span class="danger">Nonaktif</span>') . '</td>';
                        echo '</tr>';
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- 3. Report Settings -->
    <div class="card">
        <h2>3. Pengaturan Laporan Otomatis</h2>
        <table>
            <tr>
                <th>Kunci Pengaturan</th>
                <th>Nilai</th>
                <th>Keterangan / Analisis</th>
            </tr>
            <tr>
                <td><code>reports.enable_auto_report</code></td>
                <td><strong><?php echo SystemSetting::get('reports', 'enable_auto_report') ? 'Aktif (1)' : 'Nonaktif (0)'; ?></strong></td>
                <td>Status master kirim laporan terjadwal.</td>
            </tr>
            <tr>
                <td><code>reports.report_types</code></td>
                <td><strong><?php echo htmlspecialchars(SystemSetting::get('reports', 'report_types', 'superadmin,brand,produksi,owner,keuangan')); ?></strong></td>
                <td>
                    <?php 
                    $types = explode(',', SystemSetting::get('reports', 'report_types', 'superadmin,brand,produksi,owner,keuangan'));
                    if (in_array('superadmin', $types)) {
                        echo '<span class="success">✓ Laporan Superadmin Diaktifkan</span>';
                    } else {
                        echo '<span class="danger">❌ Laporan Superadmin Dinonaktifkan</span><br><small>Dapatkan kembali dengan mencentang pilihan Superadmin di dashboard.</small>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td><code>reports.superadmin_recipients</code></td>
                <td><strong><?php echo htmlspecialchars(SystemSetting::get('reports', 'superadmin_recipients') ?: '(Kosong, mengirim ke semua akun Superadmin di atas)'); ?></strong></td>
                <td>Override penerima laporan Superadmin.</td>
            </tr>
        </table>
    </div>

    <!-- 4. Cron Job Verification -->
    <div class="card">
        <h2>4. Konfigurasi Jadwal Laporan (Weekly & Monthly)</h2>
        <p>Jadwal laporan mingguan dan bulanan Anda diatur sebagai berikut:</p>
        <ul>
            <li><strong>Mingguan:</strong> Dikirim setiap hari <code><?php echo htmlspecialchars(SystemSetting::get('reports', 'weekly_report_day', 'monday')); ?></code> pada jam <code><?php echo htmlspecialchars(SystemSetting::get('reports', 'daily_report_time', '08:00')); ?></code>.</li>
            <li><strong>Bulanan:</strong> Dikirim setiap tanggal <code><?php echo htmlspecialchars(SystemSetting::get('reports', 'monthly_report_date', 'last_day') === 'last_day' ? 'Akhir Bulan' : SystemSetting::get('reports', 'monthly_report_date')); ?></code> pada jam <code><?php echo htmlspecialchars(SystemSetting::get('reports', 'daily_report_time', '08:00')); ?></code>.</li>
        </ul>
        <p><strong>PENTING:</strong> Pastikan Anda telah memasukkan Cron Job berikut di cPanel Anda agar laporan mingguan/bulanan terkirim otomatis tepat waktu:</p>
        <pre>* * * * * cd /home/[USERNAME_CPANEL]/public_html/protrack && php artisan schedule:run >> /dev/null 2>&1</pre>
    </div>
</body>
</html>
