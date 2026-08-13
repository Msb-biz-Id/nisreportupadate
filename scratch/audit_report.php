<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Brand;
use App\Models\Order\Order;
use App\Models\Settings\SystemSetting;

echo "=== USERS & ROLES & BRANDS ===" . PHP_EOL;
User::with(['roles', 'brands'])->get()->each(function($u) {
    echo "ID: {$u->id} | Name: {$u->name} | Email: {$u->email} | Phone: {$u->phone} | TG: {$u->telegram_chat_id} | Active: {$u->is_active} | Roles: " . $u->getRoleNames()->implode(', ') . " | Brands: " . $u->brands->pluck('kode')->implode(', ') . PHP_EOL;
});

echo PHP_EOL . "=== BRANDS ===" . PHP_EOL;
Brand::all()->each(function($b) {
    echo "ID: {$b->id} | Kode: {$b->kode} | Nama: {$b->nama_brand} | Active: {$b->is_active}" . PHP_EOL;
});

echo PHP_EOL . "=== REPORT SETTINGS ===" . PHP_EOL;
SystemSetting::where('group', 'reports')->get()->each(function($s) {
    echo "{$s->key}: {$s->value}" . PHP_EOL;
});

echo PHP_EOL . "=== SYSTEM NOTIFICATION SETTINGS ===" . PHP_EOL;
SystemSetting::where('group', 'system')->get()->each(function($s) {
    echo "{$s->key}: {$s->value}" . PHP_EOL;
});

echo PHP_EOL . "=== ORDERS PER BRAND STATUS & ACTIVITY ===" . PHP_EOL;
Brand::all()->each(function($b) {
    $totalOrders = Order::where('brand_id', $b->id)->count();
    $activeOrders = Order::where('brand_id', $b->id)->whereNotIn('status_po', ['draft', 'selesai', 'sudah_dikirim'])->count();
    $updatedToday = Order::where('brand_id', $b->id)->whereBetween('updated_at', [today()->startOfDay(), today()->endOfDay()])->count();
    echo "Brand [{$b->kode}] ({$b->nama_brand}): Total Orders={$totalOrders}, Active Orders={$activeOrders}, Updated Today={$updatedToday}" . PHP_EOL;
});
