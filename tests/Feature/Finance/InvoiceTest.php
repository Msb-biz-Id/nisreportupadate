<?php

namespace Tests\Feature\Finance;

use App\Models\Master\Customer;
use App\Models\Order\Invoice;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\OrderPayment;
use App\Services\NumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_invoice_from_po_with_dp(): void
    {
        $brand = $this->makeBrand();
        $admin = $this->makeUser('admin_brand', [$brand]);
        $finance = $this->makeUser('admin_keuangan', [$brand]);

        Customer::create(['brand_id' => $brand->id, 'kode' => 'C1', 'nama' => 'T', 'nomor_hp' => '081', 'is_active' => true]);

        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-X-001',
            'nama_po' => 'X', 'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => Customer::first()->id,
            'total_tagihan' => 1000000,
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'nama_produk' => 'A',
            'quantity' => 10, 'harga_satuan' => 100000, 'subtotal' => 1000000,
        ]);
        OrderPayment::create([
            'order_id' => $order->id, 'payment_type' => 'dp',
            'amount' => 300000, 'payment_date' => now()->toDateString(),
            'recorded_by' => $admin->id,
        ]);

        $this->actingAsWithBrand($finance, $brand)
            ->post(route('invoices.create-from-order', $order->id))
            ->assertRedirect();

        $inv = Invoice::where('order_id', $order->id)->first();
        $this->assertNotNull($inv);
        $this->assertEquals(300000, $inv->dp_amount);
        $this->assertEquals(700000, $inv->sisa_pembayaran);
        $this->assertEquals('draft', $inv->status);
    }

    public function test_cannot_create_invoice_from_draft_po(): void
    {
        $brand = $this->makeBrand();
        $finance = $this->makeUser('admin_keuangan', [$brand]);
        Customer::create(['brand_id' => $brand->id, 'kode' => 'C1', 'nama' => 'T', 'nomor_hp' => '081', 'is_active' => true]);

        $order = Order::create([
            'brand_id' => $brand->id, 'no_po' => 'PO-DRAFT', 'nama_po' => 'D',
            'status_po' => 'draft',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => Customer::first()->id,
            'total_tagihan' => 100000,
            'created_by' => $finance->id,
        ]);

        $this->actingAsWithBrand($finance, $brand)
            ->post(route('invoices.create-from-order', $order->id))
            ->assertStatus(422);
    }

    public function test_publish_invoice(): void
    {
        $brand = $this->makeBrand();
        $finance = $this->makeUser('admin_keuangan', [$brand]);
        Customer::create(['brand_id' => $brand->id, 'kode' => 'C1', 'nama' => 'T', 'nomor_hp' => '081', 'is_active' => true]);

        $order = Order::create([
            'brand_id' => $brand->id, 'no_po' => 'PO-PUB',
            'nama_po' => 'P', 'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => Customer::first()->id,
            'total_tagihan' => 500000,
            'published_at' => now(),
            'created_by' => $finance->id,
        ]);

        $invoice = Invoice::create([
            'brand_id' => $brand->id, 'order_id' => $order->id,
            'invoice_number' => 'INV-TEST-001',
            'tanggal_terbit' => now()->toDateString(),
            'status' => 'draft',
            'total_tagihan' => 500000,
            'sisa_pembayaran' => 500000,
            'created_by' => $finance->id,
        ]);

        $this->actingAsWithBrand($finance, $brand)
            ->post(route('invoices.publish', $invoice->id))
            ->assertRedirect();

        $this->assertEquals('published', $invoice->fresh()->status);
    }
}
