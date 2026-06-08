<?php
define('TOKEN', 'nis2024setup');
if (($_GET['t'] ?? '') !== TOKEN) { die('<b style="color:red">403 - Akses ditolak. Tambah ?t=nis2024setup di URL</b>'); }
define('BASE', dirname(__DIR__));
chdir(BASE);
set_time_limit(600);
ini_set('memory_limit', '256M');
$act = $_GET['a'] ?? '';
$out = [];

function execOk() {
    $d = ini_get('disable_functions');
    return function_exists('exec') && strpos($d, 'exec') === false;
}

function r($cmd) {
    $o = []; $c = 0;
    exec($cmd . ' 2>&1', $o, $c);
    return ['cmd' => $cmd, 'out' => implode("\n", $o), 'ok' => $c === 0];
}

// Bootstrap Laravel sekali, reuse untuk semua Artisan calls
function getApp() {
    static $kernel = null;
    if ($kernel === null) {
        if (!defined('LARAVEL_START')) define('LARAVEL_START', microtime(true));
        require_once BASE . '/vendor/autoload.php';
        $app    = require BASE . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
    }
    return $kernel;
}

function artisan($command, $params = []) {
    try {
        getApp();
        $exitCode = Illuminate\Support\Facades\Artisan::call($command, $params);
        $output   = Illuminate\Support\Facades\Artisan::output();
        return ['cmd' => 'artisan ' . $command, 'out' => trim($output) ?: '(selesai)', 'ok' => $exitCode === 0];
    } catch (Throwable $e) {
        return ['cmd' => 'artisan ' . $command, 'out' => 'ERROR: ' . $e->getMessage(), 'ok' => false];
    }
}

// ── Actions ──────────────────────────────────────────────

if ($act === 'check') {
    $e = execOk();
    $out[] = ['cmd'=>'exec() status','out'=>($e?'AKTIF':'NONAKTIF (tidak masalah, pakai metode langsung)'),'ok'=>true];
    $out[] = ['cmd'=>'PHP version','out'=>PHP_VERSION,'ok'=>version_compare(PHP_VERSION,'8.2','>=')];
    $out[] = ['cmd'=>'.env exists','out'=>file_exists(BASE.'/.env')?'ADA':'TIDAK ADA! Copy .env.example ke .env dulu','ok'=>file_exists(BASE.'/.env')];
    $out[] = ['cmd'=>'storage writable','out'=>is_writable(BASE.'/storage')?'OK':'Tidak writable','ok'=>is_writable(BASE.'/storage')];
    $out[] = ['cmd'=>'bootstrap/cache writable','out'=>is_writable(BASE.'/bootstrap/cache')?'OK':'Tidak writable','ok'=>is_writable(BASE.'/bootstrap/cache')];
    $out[] = ['cmd'=>'vendor/autoload.php','out'=>file_exists(BASE.'/vendor/autoload.php')?'ADA':'TIDAK ADA! Upload folder vendor/','ok'=>file_exists(BASE.'/vendor/autoload.php')];
}

if ($act === 'perm') {
    $dirs = ['storage','storage/app','storage/app/public','storage/framework','storage/framework/cache','storage/framework/sessions','storage/framework/views','storage/logs','bootstrap/cache'];
    foreach ($dirs as $d) {
        $p = BASE.'/'.$d;
        if (!is_dir($p)) mkdir($p, 0775, true);
        chmod($p, 0775);
        $out[] = ['cmd'=>"chmod 775 $d",'out'=>'OK','ok'=>true];
    }
}

if ($act === 'key') {
    $envPath = BASE.'/.env';
    if (!file_exists($envPath)) {
        $out[] = ['cmd'=>'key:generate','out'=>'ERROR: .env tidak ada! Copy .env.example ke .env dulu.','ok'=>false];
    } elseif (execOk()) {
        $out[] = r('php artisan key:generate --force');
    } else {
        // Direct PHP — tidak butuh exec()
        $key = 'base64:'.base64_encode(random_bytes(32));
        $env = file_get_contents($envPath);
        $new = preg_match('/^APP_KEY=/m', $env)
            ? preg_replace('/^APP_KEY=.*/m', 'APP_KEY='.$key, $env)
            : $env."\nAPP_KEY=".$key;
        $ok  = (bool) file_put_contents($envPath, $new);
        $out[] = ['cmd'=>'key:generate (PHP langsung)','out'=>$ok ? "Berhasil!\nAPP_KEY = $key" : 'GAGAL menulis .env — cek permission file .env','ok'=>$ok];
    }
}

if ($act === 'migrate') {
    // Pakai Artisan::call() langsung — tidak butuh exec()
    $out[] = artisan('migrate', ['--force' => true]);
}

if ($act === 'seed') {
    // Pakai Artisan::call() langsung — tidak butuh exec()
    $seeders = ['RolePermissionSeeder','BrandSeeder','MasterDataSeeder','UserSeeder','FinanceSeeder'];
    foreach ($seeders as $s) {
        $out[] = artisan('db:seed', ['--class' => $s, '--force' => true]);
    }
}

if ($act === 'storage') {
    $target = BASE.'/storage/app/public';
    $link   = BASE.'/public/storage';
    if (file_exists($link) || is_link($link)) {
        $out[] = ['cmd'=>'storage:link','out'=>'Sudah ada, skip.','ok'=>true];
    } elseif (symlink($target, $link)) {
        $out[] = ['cmd'=>'storage:link','out'=>'Symlink berhasil dibuat!','ok'=>true];
    } else {
        $out[] = ['cmd'=>'storage:link','out'=>'GAGAL buat symlink — hubungi support hosting','ok'=>false];
    }
}

if ($act === 'cache') {
    if (execOk()) {
        foreach (['php artisan config:cache','php artisan route:cache','php artisan view:cache','php artisan optimize'] as $c) {
            $out[] = r($c);
        }
    } else {
        // Artisan::call langsung
        foreach (['config:cache','route:cache','view:cache','optimize'] as $c) {
            $out[] = artisan($c);
        }
    }
}

if ($act === 'clear') {
    // Hapus cache files langsung tanpa exec()
    $cacheFiles = array_merge(
        glob(BASE.'/bootstrap/cache/*.php') ?: [],
        glob(BASE.'/storage/framework/cache/data/*') ?: [],
        glob(BASE.'/storage/framework/views/*.php') ?: []
    );
    $count = 0;
    foreach ($cacheFiles as $f) { if (is_file($f)) { unlink($f); $count++; } }
    $out[] = ['cmd'=>'clear cache','out'=>"Dihapus $count file cache",'ok'=>true];
    if (!execOk()) {
        foreach (['config:clear','route:clear','view:clear','cache:clear'] as $c) {
            $out[] = artisan($c);
        }
    } else {
        $out[] = r('php artisan optimize:clear');
    }
}

if ($act === 'log') {
    $logFile = BASE . '/storage/logs/laravel.log';
    if (!file_exists($logFile)) {
        echo '<pre style="background:#111;color:#f38ba8;padding:20px">Log file tidak ada: ' . $logFile . '</pre>';
    } else {
        $lines = file($logFile);
        $last  = array_slice($lines, -100);
        echo '<pre style="background:#111;color:#cdd6f4;padding:20px;font-size:12px;overflow:auto;max-height:80vh">';
        echo htmlspecialchars(implode('', $last));
        echo '</pre>';
    }
    exit;
}

if ($act === 'info') { phpinfo(); exit; }

$t = TOKEN;
?><!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>NISReport Setup</title>
<style>
body{font-family:monospace;background:#0f0f23;color:#cdd6f4;margin:0;padding:20px}
h1{color:#89b4fa;margin:0 0 5px}
.sub{color:#6c7086;font-size:.82rem;margin-bottom:15px}
.warn{background:#3b1111;border-left:4px solid #f38ba8;padding:10px 14px;margin:10px 0;color:#f38ba8;font-size:.82rem}
.info{background:#112233;border-left:4px solid #89b4fa;padding:10px 14px;margin:10px 0;color:#89b4fa;font-size:.82rem}
h2{color:#a6e3a1;font-size:.82rem;text-transform:uppercase;letter-spacing:1px;margin:18px 0 6px}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px;margin:6px 0}
a.btn{display:block;padding:10px 14px;background:#1e1e2e;border:1px solid #45475a;color:#cdd6f4;text-decoration:none;border-radius:6px;font-size:.8rem;line-height:1.5}
a.btn:hover{border-color:#89b4fa;color:#89b4fa}
.num{background:#89b4fa;color:#1e1e2e;border-radius:3px;padding:1px 6px;margin-right:6px;font-weight:bold;font-size:.75rem}
.res{border:1px solid #45475a;border-radius:6px;overflow:hidden;margin:5px 0}
.res.ok{border-color:#a6e3a1}.res.fail{border-color:#f38ba8}
.rh{padding:7px 12px;background:#1e1e2e;font-size:.8rem;display:flex;gap:8px;align-items:center}
.rb{padding:8px 12px;background:#11111b;font-size:.77rem;white-space:pre-wrap;color:#a6adc8;max-height:300px;overflow-y:auto}
.badge{padding:2px 8px;border-radius:3px;font-size:.7rem;font-weight:bold}
.ok .badge{background:#a6e3a1;color:#1e1e2e}.fail .badge{background:#f38ba8;color:#1e1e2e}
hr{border-color:#313244;margin:20px 0}
</style></head><body>

<h1>NISReport Setup</h1>
<p class="sub">cPanel Deployment Helper — exec() tidak diperlukan</p>
<div class="warn">⚠️ HAPUS file setup2.php setelah semua langkah selesai!</div>
<div class="info">ℹ️ Migrate &amp; Seed berjalan langsung via PHP (tidak butuh exec())</div>

<h2>Diagnosa</h2>
<div class="grid">
<a class="btn" href="?t=<?=$t?>&a=check">Cek Status Sistem</a>
<a class="btn" href="?t=<?=$t?>&a=info">PHP Info</a>
</div>

<h2>Langkah Setup — jalankan berurutan</h2>
<div class="grid">
<a class="btn" href="?t=<?=$t?>&a=perm"><span class="num">1</span>Fix Permissions</a>
<a class="btn" href="?t=<?=$t?>&a=key"><span class="num">2</span>Generate App Key</a>
<a class="btn" href="?t=<?=$t?>&a=migrate"><span class="num">3</span>Run Migrations</a>
<a class="btn" href="?t=<?=$t?>&a=seed"><span class="num">4</span>Run Seeders</a>
<a class="btn" href="?t=<?=$t?>&a=storage"><span class="num">5</span>Storage Link</a>
<a class="btn" href="?t=<?=$t?>&a=cache"><span class="num">6</span>Cache Optimize</a>
</div>

<h2>Tools</h2>
<div class="grid">
<a class="btn" href="?t=<?=$t?>&a=clear">Clear Cache</a>
</div>

<?php if ($out): ?>
<hr>
<h2>Hasil</h2>
<?php foreach ($out as $r): ?>
<div class="res <?=$r['ok']?'ok':'fail'?>">
<div class="rh"><span class="badge"><?=$r['ok']?'OK':'GAGAL'?></span><code><?=htmlspecialchars($r['cmd'])?></code></div>
<?php if (!empty($r['out'])): ?><div class="rb"><?=htmlspecialchars($r['out'])?></div><?php endif ?>
</div>
<?php endforeach ?>
<?php endif ?>

<p style="color:#f38ba8;margin-top:30px;font-size:.8rem">⚠️ Ingat: Hapus setup2.php setelah setup selesai!</p>
</body></html>
