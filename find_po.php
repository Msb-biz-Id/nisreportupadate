<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order\Order;
use App\Models\Finance\Invoice;
use App\Models\Finance\OrderPayment;
use App\Models\Brand;

echo "=== BRANDS ===\n";
foreach (Brand::all() as $brand) {
    echo "ID: {$brand->id} | Name: {$brand->nama_brand} | Code: {$brand->kode} | WA: {$brand->whatsapp} | HP: {$brand->no_hp}\n";
}

echo "\n=== POs / ORDERS ===\n";
$orders = Order::with(['brand', 'pelanggan', 'payments', 'items'])->get();
foreach ($orders as $order) {
    $paymentsCount = $order->payments->count();
    $itemsCount = $order->items->count();
    echo "ID: {$order->id} | PO: {$order->no_po} | Status PO: {$order->status_po} | Total: {$order->total_tagihan} | Payments: {$paymentsCount} | Items: {$itemsCount}\n";
}

echo "\n=== INVOICES ===\n";
$invoices = Invoice::with(['order', 'brand'])->get();
foreach ($invoices as $inv) {
    echo "ID: {$inv->id} | Inv No: {$inv->invoice_number} | PO: {$inv->order->no_po} | Status: {$inv->status} | Sisa: {$inv->sisa_pembayaran}\n";
}
