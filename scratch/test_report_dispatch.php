<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Brand;
use App\Models\User;
use App\Models\Settings\SystemSetting;
use App\Console\Commands\SendScheduledReport;

$cmd = new SendScheduledReport();
$reflection = new ReflectionClass($cmd);
$parseRecipientsMethod = $reflection->getMethod('parseRecipients');
$parseRecipientsMethod->setAccessible(true);

$brandHasActivityMethod = $reflection->getMethod('brandHasActivityOrOrders');
$brandHasActivityMethod->setAccessible(true);

echo "=== DEFAULT REPORT TYPES ===" . PHP_EOL;
$typesRaw = SystemSetting::get('reports', 'report_types', 'brand,produksi');
$types = array_filter(array_map('trim', explode(',', $typesRaw)));
echo "Active report types: " . implode(', ', $types) . PHP_EOL;

echo PHP_EOL . "=== BRAND ACTIVITY CHECK (periode=harian) ===" . PHP_EOL;
foreach (Brand::where('is_active', true)->get() as $brand) {
    $hasAct = $brandHasActivityMethod->invoke($cmd, $brand, 'harian');
    echo "Brand [{$brand->kode}] ({$brand->nama_brand}): " . ($hasAct ? "HAS ACTIVITY" : "SKIPPED (No activity)") . PHP_EOL;
}

echo PHP_EOL . "=== RECIPIENT RESOLUTION FOR EACH ROLE & BRAND ===" . PHP_EOL;

$rolesToTest = [
    'superadmin' => ['settingKey' => 'superadmin_recipients', 'roleName' => 'superadmin'],
    'produksi'   => ['settingKey' => 'produksi_recipients',   'roleName' => 'admin_produksi'],
    'brand'      => ['settingKey' => 'brand_recipients',      'roleName' => 'admin_brand'],
    'owner'      => ['settingKey' => 'owner_recipients',      'roleName' => 'owner'],
    'keuangan'   => ['settingKey' => 'keuangan_recipients',   'roleName' => 'admin_keuangan'],
];

foreach (Brand::where('is_active', true)->get() as $brand) {
    echo PHP_EOL . "--- Brand: {$brand->kode} ---" . PHP_EOL;
    foreach ($rolesToTest as $typeKey => $info) {
        $r = $parseRecipientsMethod->invoke($cmd, $info['settingKey'], $info['roleName'], $brand->id);
        echo "Role type [{$typeKey}] (Role: {$info['roleName']}): " . PHP_EOL;
        echo "  WA: " . implode(', ', $r['whatsapp']) . PHP_EOL;
        echo "  TG: " . implode(', ', $r['telegram']) . PHP_EOL;
        echo "  Email: " . implode(', ', $r['email']) . PHP_EOL;
    }
}
