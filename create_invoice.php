<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order\Order;
use App\Models\Order\Invoice;
use App\Models\Order\InvoiceItem;
use App\Models\Order\OrderPayment;
use App\Models\Master\BankAccount;
use App\Models\User;
use App\Services\NumberGenerator;
use Illuminate\Support\Facades\DB;

// Find the PO potimfutsalk which is 'siap_dikirim'
$order = Order::where('no_po', 'PO-CRL-POTIMFUTSALK-001')->with(['items', 'brand'])->first();
if (!$order) {
    die("PO not found!");
}

echo "Found PO: {$order->no_po} | Brand: {$order->brand->nama_brand} | Total Tagihan: {$order->total_tagihan}\n";

// Delete existing invoice if any for clean slate
Invoice::where('order_id', $order->id)->delete();

$bank = BankAccount::first();
if (!$bank) {
    $bank = BankAccount::create([
        'bank_name' => 'BCA',
        'account_name' => 'Circle Sportwear Resmi',
        'account_number' => '1234567890',
        'is_active' => true
    ]);
}

$user = User::first();
if (!$user) {
    die("No user found in the database!");
}
echo "Using User: {$user->name} (ID: {$user->id})\n";

$numbers = app(NumberGenerator::class);

$invoice = DB::transaction(function () use ($order, $numbers, $bank, $user) {
    $totalTagihan = (float) $order->totalTagihan();
    
    // Let's create an order payment that is already verified!
    // Since "finance verification first" is required, let's verify any existing payments or create a verified DP payment!
    $payment = $order->payments()->first();
    if (!$payment) {
        $payment = OrderPayment::create([
            'order_id' => $order->id,
            'payment_type' => 'dp',
            'amount' => 3000000.00,
            'payment_date' => now()->toDateString(),
            'dp_sequence' => 1,
            'bank_id' => $bank->id,
            'recorder_id' => $user->id,
            'verified_at' => now(),
            'verified_by' => $user->id
        ]);
    } else {
        $payment->update([
            'verified_at' => now(),
            'verified_by' => $user->id
        ]);
    }

    $totalPaid = (float) $order->totalPaid();

    $invNum = $numbers->generateInvoiceNumber($order->brand, $order);
    $invoice = Invoice::create([
        'brand_id' => $order->brand_id,
        'order_id' => $order->id,
        'invoice_number' => $invNum,
        'tanggal_terbit' => now()->toDateString(),
        'jatuh_tempo' => now()->addDays(14)->toDateString(),
        'status' => 'published', // set directly to published
        'total_tagihan' => $totalTagihan,
        'total_bayar' => $totalPaid,
        'dp_amount' => (float) $payment->amount,
        'sisa_pembayaran' => max(0, $totalTagihan - $totalPaid),
        'bank_id' => $bank->id,
        'biaya_pengiriman' => 150000.00,
        'jasa_pengiriman' => 'JNE OKE',
        'catatan' => 'Silakan lakukan sisa pelunasan sebelum barang dikirim.',
        'created_by' => $user->id,
    ]);

    foreach ($order->items as $item) {
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'produk' => $item->nama_produk . ($item->varian_label ? " ({$item->varian_label})" : ''),
            'jumlah' => $item->quantity,
            'harga_satuan' => $item->harga_satuan,
            'subtotal' => $item->subtotal,
        ]);
    }

    return $invoice;
});

echo "SUCCESSFULLY CREATED INVOICE!\n";
echo "Invoice Number: {$invoice->invoice_number}\n";
echo "Invoice Status: {$invoice->status}\n";
echo "Invoice Link: http://127.0.0.1:8000/invoice/{$invoice->invoice_number}\n";
