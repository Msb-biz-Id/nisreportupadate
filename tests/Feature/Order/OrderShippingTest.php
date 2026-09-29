<?php

namespace Tests\Feature\Order;

use App\Models\Master\Customer;
use App\Models\Master\Ekspedisi;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\POChangeLog;
use App\Models\Order\OrderProgressDetail;
use App\Models\Master\Progress;
use App\Services\NumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderShippingTest extends TestCase
{
    use RefreshDatabase;

    private function setupBrandWithMasters()
    {
        $brand = $this->makeBrand();
        Customer::create([
            'id' => '019e2969-0000-0000-0000-000000000001',
            'brand_id' => $brand->id,
            'kode' => 'CUST-001', 'nama' => 'Test Pelanggan', 'nomor_hp' => '081111',
            'is_active' => true,
        ]);
        \App\Models\Master\BankAccount::create([
            'brand_id' => $brand->id,
            'bank' => 'BCA',
            'atas_nama' => 'Test Acc',
            'nomor_rekening' => '12345',
            'is_active' => true,
        ]);
        
        // Seed Ekspedisi
        Ekspedisi::create([
            'id' => '019e2969-e000-0000-0000-000000000001',
            'nama' => 'JNE',
            'deskripsi' => 'Jalur Nugraha Ekakurir',
            'is_active' => true,
        ]);

        return $brand;
    }

    public function test_create_order_saves_ekspedisi_fields_and_syncs_invoice(): void
    {
        $brand = $this->setupBrandWithMasters();
        $user = $this->makeUser('admin_brand', [$brand]);
        $customer = Customer::where('brand_id', $brand->id)->first();
        $bank = \App\Models\Master\BankAccount::where('brand_id', $brand->id)->first();
        $ekspedisi = Ekspedisi::first();

        $this->actingAsWithBrand($user, $brand)
            ->post(route('orders.store'), [
                'nama_po' => 'PO Shipping Test',
                'tanggal_masuk' => now()->toDateString(),
                'deadline_customer' => now()->addDays(14)->toDateString(),
                'pelanggan_id' => $customer->id,
                'bank_id' => $bank->id,
                'ekspedisi_id' => $ekspedisi->id,
                'tipe_pengiriman' => 'ongkir',
                'ongkir' => 15000,
                'items' => [[
                    'nama_produk' => 'Jersey Test',
                    'quantity' => 10,
                    'harga_satuan' => 100000,
                ]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'nama_po' => 'PO Shipping Test',
            'ekspedisi_id' => $ekspedisi->id,
            'nama_ekspedisi' => 'JNE',
            'ongkir' => 15000,
        ]);

        $order = Order::where('nama_po', 'PO Shipping Test')->first();
        $invoice = $order->invoices()->first();

        $this->assertNotNull($invoice);
        $this->assertEquals('JNE', $invoice->jasa_pengiriman);
    }

    public function test_update_order_updates_ekspedisi_fields_and_syncs_invoice(): void
    {
        $brand = $this->setupBrandWithMasters();
        $user = $this->makeUser('admin_brand', [$brand]);
        $customer = Customer::where('brand_id', $brand->id)->first();
        $bank = \App\Models\Master\BankAccount::where('brand_id', $brand->id)->first();
        $ekspedisi = Ekspedisi::first();

        // Create a PO first
        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => app(NumberGenerator::class)->generateOrderNumber($brand),
            'nama_po' => 'PO Update Shipping',
            'status_po' => 'draft',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(14)->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 1000000,
            'created_by' => $user->id,
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'nama_produk' => 'X',
            'quantity' => 10, 'harga_satuan' => 100000, 'subtotal' => 1000000,
        ]);
        $invoice = \App\Models\Order\Invoice::create([
            'brand_id' => $brand->id,
            'order_id' => $order->id,
            'invoice_number' => 'INV-TEST',
            'tanggal_terbit' => $order->tanggal_masuk,
            'jatuh_tempo' => $order->deadline_customer,
            'status' => 'draft',
            'total_tagihan' => 1000000,
            'bank_id' => $bank->id,
            'sisa_pembayaran' => 1000000,
            'created_by' => $user->id,
        ]);

        $this->actingAsWithBrand($user, $brand)
            ->put(route('orders.update', $order->id), [
                'nama_po' => 'PO Update Shipping Edited',
                'tanggal_masuk' => now()->toDateString(),
                'deadline_customer' => now()->addDays(14)->toDateString(),
                'pelanggan_id' => $customer->id,
                'bank_id' => $bank->id,
                'ekspedisi_id' => $ekspedisi->id,
                'tipe_pengiriman' => 'ongkir',
                'ongkir' => 20000,
                'items' => [[
                    'nama_produk' => 'X',
                    'quantity' => 10,
                    'harga_satuan' => 100000,
                ]],
            ])
            ->assertRedirect();

        $order->refresh();
        $invoice->refresh();

        $this->assertEquals($ekspedisi->id, $order->ekspedisi_id);
        $this->assertEquals('JNE', $order->nama_ekspedisi);
        $this->assertEquals('JNE', $invoice->jasa_pengiriman);
    }

    public function test_update_shipping_bypasses_lock_and_logs_to_change_log(): void
    {
        $brand = $this->setupBrandWithMasters();
        $user = $this->makeUser('admin_produksi', [$brand]); // admin_produksi can edit progress
        $customer = Customer::where('brand_id', $brand->id)->first();
        $bank = \App\Models\Master\BankAccount::where('brand_id', $brand->id)->first();
        $ekspedisi = Ekspedisi::first();

        // Create locked order (status: on_progress)
        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => app(NumberGenerator::class)->generateOrderNumber($brand),
            'nama_po' => 'Locked PO',
            'status_po' => 'on_progress',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(14)->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 1000000,
            'created_by' => $user->id,
        ]);
        $invoice = \App\Models\Order\Invoice::create([
            'brand_id' => $brand->id,
            'order_id' => $order->id,
            'invoice_number' => 'INV-TEST-LOCKED',
            'tanggal_terbit' => $order->tanggal_masuk,
            'jatuh_tempo' => $order->deadline_customer,
            'status' => 'draft',
            'total_tagihan' => 1000000,
            'bank_id' => $bank->id,
            'sisa_pembayaran' => 1000000,
            'created_by' => $user->id,
        ]);

        $this->actingAsWithBrand($user, $brand)
            ->patch(route('orders.shipping.update', $order->id), [
                'ekspedisi_id' => $ekspedisi->id,
                'no_resi' => 'RESI12345',
            ])
            ->assertRedirect();

        $order->refresh();
        $invoice->refresh();

        $this->assertEquals($ekspedisi->id, $order->ekspedisi_id);
        $this->assertEquals('JNE', $order->nama_ekspedisi);
        $this->assertEquals('RESI12345', $order->no_resi);
        $this->assertEquals('JNE', $invoice->jasa_pengiriman);

        // Check POChangeLog is recorded
        $this->assertDatabaseHas('po_change_logs', [
            'order_id' => $order->id,
            'field_changed' => 'nama_ekspedisi',
            'new_value' => 'JNE',
            'changed_by' => $user->id,
        ]);
        $this->assertDatabaseHas('po_change_logs', [
            'order_id' => $order->id,
            'field_changed' => 'no_resi',
            'new_value' => 'RESI12345',
            'changed_by' => $user->id,
        ]);
    }

    public function test_production_progress_update_at_sending_stage_syncs_ekspedisi_fields_and_invoice(): void
    {
        $brand = $this->setupBrandWithMasters();
        $user = $this->makeUser('admin_produksi', [$brand]);
        $customer = Customer::where('brand_id', $brand->id)->first();
        $bank = \App\Models\Master\BankAccount::where('brand_id', $brand->id)->first();
        $ekspedisi = Ekspedisi::first();

        // Create SENDING progress master stage
        $sendingProgress = Progress::create([
            'nama_progress' => 'SENDING',
            'urutan' => 10,
            'warna' => '#8B5CF6',
            'is_skippable' => false,
            'is_active' => true,
        ]);

        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => app(NumberGenerator::class)->generateOrderNumber($brand),
            'nama_po' => 'PO Sending Stage Test',
            'status_po' => 'on_progress',
            'is_lunas' => true, // Lunas so sending stage is not locked by payment
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(14)->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 1000000,
            'created_by' => $user->id,
        ]);
        $invoice = \App\Models\Order\Invoice::create([
            'brand_id' => $brand->id,
            'order_id' => $order->id,
            'invoice_number' => 'INV-TEST-SENDING',
            'tanggal_terbit' => $order->tanggal_masuk,
            'jatuh_tempo' => $order->deadline_customer,
            'status' => 'paid',
            'total_tagihan' => 1000000,
            'bank_id' => $bank->id,
            'sisa_pembayaran' => 0,
            'created_by' => $user->id,
        ]);

        $progressDetail = OrderProgressDetail::create([
            'order_id' => $order->id,
            'progress_id' => $sendingProgress->id,
            'status' => 'pending',
        ]);

        $this->actingAsWithBrand($user, $brand)
            ->put(route('produksi.progress.update', ['order' => $order->id, 'detail' => $progressDetail->id]), [
                'status' => 'selesai',
                'ekspedisi_id' => $ekspedisi->id,
                'no_resi' => 'RESI999',
            ])
            ->assertRedirect();

        $order->refresh();
        $invoice->refresh();

        $this->assertEquals($ekspedisi->id, $order->ekspedisi_id);
        $this->assertEquals('JNE', $order->nama_ekspedisi);
        $this->assertEquals('RESI999', $order->no_resi);
        $this->assertEquals('JNE', $invoice->jasa_pengiriman);
    }
}
