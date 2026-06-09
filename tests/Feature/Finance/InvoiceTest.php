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
            'verified_at' => now(),
            'verified_by' => $finance->id,
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

    public function test_can_create_invoice_from_draft_po(): void
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
            ->assertRedirect();
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

    public function test_invoice_pdf_generation_as_admin_keuangan(): void
    {
        $brand = $this->makeBrand([
            'alamat' => 'Jalan Test No. 123',
            'no_hp' => '08123456789',
            'email' => 'brand@test.com',
            'tagline' => 'Tagline Brand',
            'deskripsi' => 'Deskripsi Brand',
        ]);
        $finance = $this->makeUser('admin_keuangan', [$brand]);
        $customer = Customer::create([
            'brand_id' => $brand->id,
            'kode' => 'C1',
            'nama' => 'John Doe',
            'nomor_hp' => '08123456',
            'email' => 'john@doe.com',
            'detail_alamat' => 'Jalan Customer 45',
            'kabupaten_nama' => 'Bandung',
            'provinsi_nama' => 'Jawa Barat',
            'kodepos' => '40123',
            'is_active' => true
        ]);

        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-PDF-001',
            'nama_po' => 'Order Seragam',
            'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 1000000,
            'published_at' => now(),
            'created_by' => $finance->id,
        ]);

        $invoice = Invoice::create([
            'brand_id' => $brand->id,
            'order_id' => $order->id,
            'invoice_number' => 'INV-PDF-001',
            'tanggal_terbit' => now()->toDateString(),
            'status' => 'published',
            'total_tagihan' => 1000000,
            'sisa_pembayaran' => 1000000,
            'created_by' => $finance->id,
        ]);

        $response = $this->actingAsWithBrand($finance, $brand)
            ->get(route('invoices.pdf', $invoice->id));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_invoice_pdf_generation_with_missing_relations(): void
    {
        $brand = $this->makeBrand([
            'alamat' => null,
            'no_hp' => null,
            'email' => null,
            'tagline' => null,
            'deskripsi' => null,
            'logo' => null,
        ]);
        $finance = $this->makeUser('admin_keuangan', [$brand]);

        $customer = Customer::create([
            'brand_id' => $brand->id,
            'kode' => 'C_MIN',
            'nama' => 'Minimal Customer',
            'nomor_hp' => '00000',
            'email' => null,
            'detail_alamat' => null,
            'kabupaten_nama' => null,
            'provinsi_nama' => null,
            'kodepos' => null,
            'is_active' => true,
        ]);

        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-NULL-001',
            'nama_po' => 'Minimal Order',
            'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 0,
            'created_by' => $finance->id,
        ]);

        $invoice = Invoice::create([
            'brand_id' => $brand->id,
            'order_id' => $order->id,
            'invoice_number' => 'INV-NULL-001',
            'tanggal_terbit' => now()->toDateString(),
            'status' => 'published',
            'total_tagihan' => 0,
            'sisa_pembayaran' => 0,
            'created_by' => $finance->id,
        ]);

        $response = $this->actingAsWithBrand($finance, $brand)
            ->get(route('invoices.pdf', $invoice->id));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_public_invoice_pdf_generation(): void
    {
        $brand = $this->makeBrand();
        $finance = $this->makeUser('admin_keuangan', [$brand]);
        $customer = Customer::create([
            'brand_id' => $brand->id,
            'kode' => 'C2',
            'nama' => 'Public Cust',
            'nomor_hp' => '08222',
            'is_active' => true
        ]);

        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-PUBLIC-01',
            'nama_po' => 'Public Order',
            'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 200000,
            'published_at' => now(),
            'created_by' => $finance->id,
        ]);

        $invoice = Invoice::create([
            'brand_id' => $brand->id,
            'order_id' => $order->id,
            'invoice_number' => 'INV-PUB-01',
            'tanggal_terbit' => now()->toDateString(),
            'status' => 'published', // publicPdf route requires published/sent/paid status
            'total_tagihan' => 200000,
            'sisa_pembayaran' => 200000,
            'created_by' => $finance->id,
        ]);

        $response = $this->get(route('invoice.public.pdf', $invoice->invoice_number));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_public_routes_allow_authorized_users_when_draft(): void
    {
        $brand = $this->makeBrand();
        $finance = $this->makeUser('admin_keuangan', [$brand]);
        $brandAdmin = $this->makeUser('admin_brand', [$brand]);
        $unauthorizedUser = $this->makeUser('admin_produksi', [$brand]);
        $otherBrand = $this->makeBrand();
        $otherBrandAdmin = $this->makeUser('admin_brand', [$otherBrand]);

        $customer = Customer::create([
            'brand_id' => $brand->id,
            'kode' => 'C3',
            'nama' => 'Draft Cust',
            'nomor_hp' => '08333',
            'is_active' => true
        ]);

        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-DRAFT-01',
            'nama_po' => 'Draft Order',
            'status_po' => 'draft',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 150000,
            'created_by' => $finance->id,
        ]);

        $invoice = Invoice::create([
            'brand_id' => $brand->id,
            'order_id' => $order->id,
            'invoice_number' => 'INV-DFT-01',
            'tanggal_terbit' => now()->toDateString(),
            'status' => 'draft',
            'total_tagihan' => 150000,
            'sisa_pembayaran' => 150000,
            'created_by' => $finance->id,
        ]);

        // 1. Guest access should return 404 (Not Found)
        $this->get(route('invoice.public', $invoice->invoice_number))->assertStatus(404);
        $this->get(route('invoice.public.pdf', $invoice->invoice_number))->assertStatus(404);

        // 2. Authorized Finance user should succeed
        $this->actingAsWithBrand($finance, $brand)
            ->get(route('invoice.public', $invoice->invoice_number))->assertStatus(200);
        $this->actingAsWithBrand($finance, $brand)
            ->get(route('invoice.public.pdf', $invoice->invoice_number))->assertStatus(200);

        // 3. Authorized Brand Admin user should succeed
        $this->actingAsWithBrand($brandAdmin, $brand)
            ->get(route('invoice.public', $invoice->invoice_number))->assertStatus(200);
        $this->actingAsWithBrand($brandAdmin, $brand)
            ->get(route('invoice.public.pdf', $invoice->invoice_number))->assertStatus(200);

        // 4. User from another brand should be forbidden (403)
        $this->actingAsWithBrand($otherBrandAdmin, $otherBrand)
            ->get(route('invoice.public', $invoice->invoice_number))->assertStatus(403);
        $this->actingAsWithBrand($otherBrandAdmin, $otherBrand)
            ->get(route('invoice.public.pdf', $invoice->invoice_number))->assertStatus(403);
    }

    public function test_invoice_creation_retains_addon_flag(): void
    {
        $brand = $this->makeBrand();
        $finance = $this->makeUser('admin_keuangan', [$brand]);
        Customer::create(['brand_id' => $brand->id, 'kode' => 'C_ADDON', 'nama' => 'Test Addon', 'nomor_hp' => '0899', 'is_active' => true]);

        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-ADDON-01',
            'nama_po' => 'PO Addon',
            'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => Customer::first()->id,
            'total_tagihan' => 150000,
            'published_at' => now(),
            'created_by' => $finance->id,
        ]);

        // Create a regular item
        $item1 = OrderItem::create([
            'order_id' => $order->id,
            'nama_produk' => 'Main Product',
            'quantity' => 1,
            'harga_satuan' => 100000,
            'subtotal' => 100000,
            'is_addon' => false,
        ]);

        // Create an add-on item
        $item2 = OrderItem::create([
            'order_id' => $order->id,
            'nama_produk' => 'Keychain Addon',
            'quantity' => 5,
            'harga_satuan' => 10000,
            'subtotal' => 50000,
            'is_addon' => true,
        ]);

        // Calculate and verify total tagihan
        $this->assertEquals(150000, $order->totalTagihan());

        // Call the endpoint to create invoice
        $response = $this->actingAsWithBrand($finance, $brand)
            ->post(route('invoices.create-from-order', $order->id));
        
        $response->assertRedirect();

        $invoice = Invoice::where('order_id', $order->id)->first();
        $this->assertNotNull($invoice);

        // Verify items were created and is_addon copied
        $invoiceItems = $invoice->items;
        $this->assertCount(2, $invoiceItems);

        $mainItem = $invoiceItems->where('produk', 'Main Product')->first();
        $this->assertNotNull($mainItem);
        $this->assertFalse((bool)$mainItem->is_addon);

        $addonItem = $invoiceItems->where('produk', 'Keychain Addon')->first();
        $this->assertNotNull($addonItem);
        $this->assertTrue((bool)$addonItem->is_addon);

        // Verify invoice PDF generation with addons works without layout distortion/crashes
        $response = $this->actingAsWithBrand($finance, $brand)
            ->get(route('invoices.pdf', $invoice->id));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
