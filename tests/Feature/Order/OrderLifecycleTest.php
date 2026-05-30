<?php

namespace Tests\Feature\Order;

use App\Models\Finance\Pemasukan;
use App\Models\Master\Customer;
use App\Models\Master\Product;
use App\Models\Master\Progress;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\Refund;
use App\Services\NumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
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
        Product::create([
            'brand_id' => $brand->id,
            'nama' => 'Jersey Test', 'harga' => 100000, 'is_active' => true,
        ]);
        foreach (range(1, 3) as $i) {
            Progress::create([
                'nama_progress' => "Tahap $i", 'urutan' => $i, 'is_active' => true,
                'warna' => '#3B82F6', 'is_skippable' => false,
            ]);
        }
        return $brand;
    }

    public function test_admin_brand_can_create_draft_po(): void
    {
        $brand = $this->setupBrandWithMasters();
        $user = $this->makeUser('owner', [$brand]);
        $customer = Customer::where('brand_id', $brand->id)->first();

        $this->actingAsWithBrand($user, $brand)
            ->post(route('orders.store'), [
                'nama_po' => 'PO Test',
                'tanggal_masuk' => now()->toDateString(),
                'deadline_customer' => now()->addDays(14)->toDateString(),
                'pelanggan_id' => $customer->id,
                'items' => [[
                    'nama_produk' => 'Jersey Test',
                    'quantity' => 10,
                    'harga_satuan' => 100000,
                ]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', ['nama_po' => 'PO Test', 'status_po' => 'draft', 'total_tagihan' => 1000000]);
        $order = Order::where('nama_po', 'PO Test')->first();
        $this->assertMatchesRegularExpression('/^PO-[A-Z0-9]+-[A-Z0-9]+-\d{3}$/', $order->no_po);
    }

    public function test_publish_po_creates_progress_details_and_auto_pemasukan(): void
    {
        $brand = $this->setupBrandWithMasters();
        $user = $this->makeUser('owner', [$brand]);
        $customer = Customer::where('brand_id', $brand->id)->first();

        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => app(NumberGenerator::class)->generateOrderNumber($brand),
            'nama_po' => 'To Publish', 'status_po' => 'draft',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(14)->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 500000,
            'created_by' => $user->id,
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'nama_produk' => 'X',
            'quantity' => 5, 'harga_satuan' => 100000, 'subtotal' => 500000,
        ]);

        // Create verified DP payment (500,000)
        \App\Models\Order\OrderPayment::create([
            'order_id' => $order->id,
            'payment_type' => 'dp',
            'amount' => 500000,
            'payment_date' => now()->toDateString(),
            'recorded_by' => $user->id,
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);

        // Create validated invoice
        \App\Models\Order\Invoice::create([
            'brand_id' => $brand->id,
            'order_id' => $order->id,
            'invoice_number' => 'INV-TEST-001',
            'tanggal_terbit' => now()->toDateString(),
            'jatuh_tempo' => now()->addDays(14)->toDateString(),
            'status' => 'validated',
            'total_tagihan' => 500000,
            'total_bayar' => 500000,
            'dp_amount' => 500000,
            'sisa_pembayaran' => 0,
            'created_by' => $user->id,
        ]);

        $this->actingAsWithBrand($user, $brand)
            ->post(route('orders.publish', $order->id))
            ->assertRedirect();

        $order = $order->fresh();
        $this->assertEquals('published', $order->status_po);
        $this->assertCount(3, $order->progressDetails); // 3 progress tahapan
        $this->assertNotNull($order->published_at);

        // Observer harus bikin pemasukan
        $this->assertDatabaseHas('pemasukan', [
            'order_id' => $order->id,
            'nominal' => 500000,
            'is_auto' => true,
        ]);
    }

    public function test_cannot_publish_po_without_items(): void
    {
        $brand = $this->setupBrandWithMasters();
        $user = $this->makeUser('owner', [$brand]);
        $customer = Customer::where('brand_id', $brand->id)->first();

        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-EMPTY',
            'nama_po' => 'Empty PO', 'status_po' => 'draft',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 0,
            'created_by' => $user->id,
        ]);

        $this->actingAsWithBrand($user, $brand)
            ->post(route('orders.publish', $order->id))
            ->assertStatus(422);
    }

    public function test_owner_cannot_delete_published_po(): void
    {
        $brand = $this->setupBrandWithMasters();
        $user = $this->makeUser('owner', [$brand]);
        $customer = Customer::where('brand_id', $brand->id)->first();

        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-PUB-001',
            'nama_po' => 'Already Published', 'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 100000,
            'published_at' => now(),
            'created_by' => $user->id,
        ]);

        $this->actingAsWithBrand($user, $brand)
            ->delete(route('orders.destroy', $order->id))
            ->assertStatus(422);
    }

    public function test_repeat_order_creates_new_draft_with_reference(): void
    {
        $brand = $this->setupBrandWithMasters();
        $user = $this->makeUser('owner', [$brand]);
        $customer = Customer::where('brand_id', $brand->id)->first();

        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-ORIG-001',
            'nama_po' => 'Original', 'status_po' => 'published',
            'tanggal_masuk' => now()->subDays(5)->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 250000,
            'published_at' => now()->subDays(5),
            'created_by' => $user->id,
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'nama_produk' => 'P1',
            'quantity' => 2, 'harga_satuan' => 125000, 'subtotal' => 250000,
        ]);

        $this->actingAsWithBrand($user, $brand)
            ->post(route('orders.repeat', $order->id))
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'is_repeat_order' => true,
            'repeat_from_po_id' => $order->id,
            'status_po' => 'draft',
        ]);
    }

    public function test_refund_published_auto_creates_pengeluaran(): void
    {
        $brand = $this->setupBrandWithMasters();
        $admin = $this->makeUser('admin_brand', [$brand]);
        $finance = $this->makeUser('admin_keuangan', [$brand]);
        $customer = Customer::where('brand_id', $brand->id)->first();

        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-REF-001',
            'nama_po' => 'Refund Source', 'status_po' => 'sudah_dikirim',
            'tanggal_masuk' => now()->subDays(20)->toDateString(),
            'deadline_customer' => now()->subDays(5)->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 1000000,
            'published_at' => now()->subDays(20),
            'created_by' => $admin->id,
        ]);

        $refund = Refund::create([
            'brand_id' => $brand->id,
            'order_id' => $order->id,
            'refund_number' => 'REF-TEST-001',
            'alasan' => 'Produk cacat',
            'jenis_masalah' => 'produk_cacat',
            'jumlah_item' => 2,
            'nominal_refund' => 150000,
            'status' => 'pending_review',
            'created_by' => $admin->id,
        ]);

        $this->actingAsWithBrand($finance, $brand)
            ->post(route('refunds.publish', $refund->id))
            ->assertRedirect();

        $this->assertEquals('published', $refund->fresh()->status);
        $this->assertDatabaseHas('pengeluaran', [
            'refund_id' => $refund->id,
            'nominal' => 150000,
            'is_auto' => true,
        ]);
    }
}
