<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo 'Total Orders: ' . \App\Models\Order\Order::count() . PHP_EOL;
echo 'Orders by status:' . PHP_EOL;
foreach (\App\Models\Order\Order::selectRaw('status_po, count(*) as count')->groupBy('status_po')->get() as $row) {
    echo " - {$row->status_po}: {$row->count}" . PHP_EOL;
}

echo 'Total Invoices: ' . \App\Models\Order\Invoice::count() . PHP_EOL;
echo 'Invoices by status:' . PHP_EOL;
foreach (\App\Models\Order\Invoice::selectRaw('status, count(*) as count')->groupBy('status')->get() as $row) {
    echo " - {$row->status}: {$row->count}" . PHP_EOL;
}

$totalUnpaidCount = \App\Models\Order\Invoice::where('status', '!=', 'paid')->where('sisa_pembayaran', '>', 0)->count();
$totalPaidCount = \App\Models\Order\Invoice::where(fn($q) => $q->where('status', 'paid')->orWhere('sisa_pembayaran', '<=', 0))->count();
echo "Unpaid Count logic: {$totalUnpaidCount}" . PHP_EOL;
echo "Paid Count logic: {$totalPaidCount}" . PHP_EOL;

echo 'Orders without invoices count: ' . \App\Models\Order\Order::whereDoesntHave('invoices')->count() . PHP_EOL;
