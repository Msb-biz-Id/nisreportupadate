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
        \App\Models\Master\BankAccount::create([
            'brand_id' => $brand->id,
            'bank' => 'BCA',
            'atas_nama' => 'Test Acc',
            'nomor_rekening' => '12345',
            'is_active' => true,
        ]);
        return $brand;
    }

    public function test_admin_brand_can_create_draft_po(): void
    {
        $brand = $this->setupBrandWithMasters();
        $user = $this->makeUser('admin_brand', [$brand]);
        $customer = Customer::where('brand_id', $brand->id)->first();

        $bank = \App\Models\Master\BankAccount::where('brand_id', $brand->id)->first();

        $this->actingAsWithBrand($user, $brand)
            ->post(route('orders.store'), [
                'nama_po' => 'PO Test',
                'tanggal_masuk' => now()->toDateString(),
                'deadline_customer' => now()->addDays(14)->toDateString(),
                'pelanggan_id' => $customer->id,
                'bank_id' => $bank->id,
                'items' => [[
                    'nama_produk' => 'Jersey Test',
                    'quantity' => 10,
                    'harga_satuan' => 100000,
                ]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', ['nama_po' => 'PO Test', 'status_po' => 'draft', 'total_tagihan' => 1000000]);
        $order = Order::where('nama_po', 'PO Test')->first();
        $this->assertMatchesRegularExpression('/^PO-[A-Z0-9]+-[A-Z0-9_-]+-\d{3}$/', $order->no_po);
    }

    public function test_publish_po_creates_progress_details_and_auto_pemasukan(): void
    {
        $brand = $this->setupBrandWithMasters();
        $user = $this->makeUser('admin_brand', [$brand]);
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

    public function test_publish_po_enforces_brand_specific_min_dp(): void
    {
        $brand = $this->setupBrandWithMasters();
        $brand->update(['min_dp_percentage' => 0.30]);
        $user = $this->makeUser('admin_brand', [$brand]);
        $customer = Customer::where('brand_id', $brand->id)->first();

        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => app(NumberGenerator::class)->generateOrderNumber($brand),
            'nama_po' => 'DP 30 Percent Test', 'status_po' => 'draft',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(14)->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 1000000,
            'created_by' => $user->id,
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'nama_produk' => 'Y',
            'quantity' => 10, 'harga_satuan' => 100000, 'subtotal' => 1000000,
        ]);

        // Create validated invoice
        \App\Models\Order\Invoice::create([
            'brand_id' => $brand->id,
            'order_id' => $order->id,
            'invoice_number' => 'INV-TEST-002',
            'tanggal_terbit' => now()->toDateString(),
            'jatuh_tempo' => now()->addDays(14)->toDateString(),
            'status' => 'validated',
            'total_tagihan' => 1000000,
            'total_bayar' => 0,
            'dp_amount' => 0,
            'sisa_pembayaran' => 1000000,
            'created_by' => $user->id,
        ]);

        // Try publishing without any payment - should fail (since 30% DP is required)
        $this->actingAsWithBrand($user, $brand)
            ->post(route('orders.publish', $order->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        // Add 20% payment - should still fail
        $payment = \App\Models\Order\OrderPayment::create([
            'order_id' => $order->id,
            'payment_type' => 'dp',
            'amount' => 200000,
            'payment_date' => now()->toDateString(),
            'recorded_by' => $user->id,
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);

        $this->actingAsWithBrand($user, $brand)
            ->post(route('orders.publish', $order->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        // Update payment to 30% (300,000) - should succeed
        $payment->update(['amount' => 300000]);

        $this->actingAsWithBrand($user, $brand)
            ->post(route('orders.publish', $order->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals('published', $order->fresh()->status_po);
    }

    public function test_bank_accounts_are_strictly_isolated_by_brand(): void
    {
        $brandA = $this->setupBrandWithMasters();
        $brandB = \App\Models\Brand::create(['nama_brand' => 'Brand B', 'kode' => 'BRB', 'is_active' => true]);

        // Clean up seeder bank accounts to test isolatedly
        \App\Models\Master\BankAccount::query()->forceDelete();

        $bankA = \App\Models\Master\BankAccount::create([
            'brand_id' => $brandA->id,
            'bank' => 'BCA A',
            'atas_nama' => 'AN Brand A',
            'nomor_rekening' => '11111',
            'is_active' => true,
        ]);

        $bankB = \App\Models\Master\BankAccount::create([
            'brand_id' => $brandB->id,
            'bank' => 'BCA B',
            'atas_nama' => 'AN Brand B',
            'nomor_rekening' => '22222',
            'is_active' => true,
        ]);

        // Assert model scope works
        $banksForA = \App\Models\Master\BankAccount::forBrand($brandA->id)->get();
        $this->assertTrue($banksForA->contains($bankA));
        $this->assertFalse($banksForA->contains($bankB));

        $banksForB = \App\Models\Master\BankAccount::forBrand($brandB->id)->get();
        $this->assertTrue($banksForB->contains($bankB));
        $this->assertFalse($banksForB->contains($bankA));
    }

    public function test_cannot_publish_po_without_items(): void
    {
        $brand = $this->setupBrandWithMasters();
        $user = $this->makeUser('admin_brand', [$brand]);
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
        $user = $this->makeUser('admin_brand', [$brand]);
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
        $user = $this->makeUser('admin_brand', [$brand]);
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

    public function test_end_to_end_order_fulfillment_workflow_lifecycle(): void
    {
        // 1. Setup brand and users
        $brand = $this->makeBrand();
        Customer::create([
            'id' => '019e2969-0000-0000-0000-000000000002',
            'brand_id' => $brand->id,
            'kode' => 'CUST-002', 'nama' => 'Test Pelanggan 2', 'nomor_hp' => '082222',
            'is_active' => true,
        ]);
        Product::create([
            'brand_id' => $brand->id,
            'nama' => 'Jersey Premium', 'harga' => 100000, 'is_active' => true,
        ]);
        \App\Models\Master\BankAccount::create([
            'brand_id' => $brand->id,
            'bank' => 'BCA',
            'atas_nama' => 'Test Acc Premium',
            'nomor_rekening' => '54321',
            'is_active' => true,
        ]);

        // Recreate the progresses exactly as needed for status recalculation and is_lunas sending validation
        \App\Models\Master\Progress::query()->delete();
        $progress1 = \App\Models\Master\Progress::create([
            'nama_progress' => 'Tahap 1', 'urutan' => 1, 'is_active' => true,
            'warna' => '#3B82F6', 'is_skippable' => false,
        ]);
        $progressPacking = \App\Models\Master\Progress::create([
            'nama_progress' => 'PACKING', 'urutan' => 2, 'is_active' => true,
            'warna' => '#06B6D4', 'is_skippable' => false,
        ]);
        $progressSending = \App\Models\Master\Progress::create([
            'nama_progress' => 'SENDING', 'urutan' => 3, 'is_active' => true,
            'warna' => '#8B5CF6', 'is_skippable' => false,
        ]);

        $adminBrand = $this->makeUser('admin_brand', [$brand]);
        $adminFinance = $this->makeUser('admin_keuangan', [$brand]);
        $adminProduction = $this->makeUser('admin_produksi', [$brand]);
        $customer = Customer::where('brand_id', $brand->id)->first();

        $bank = \App\Models\Master\BankAccount::where('brand_id', $brand->id)->first();

        // Step 1: Admin Brand membuat draft order
        $this->actingAsWithBrand($adminBrand, $brand)
            ->post(route('orders.store'), [
                'nama_po' => 'PO Workflow Test',
                'tanggal_masuk' => now()->toDateString(),
                'deadline_customer' => now()->addDays(14)->toDateString(),
                'pelanggan_id' => $customer->id,
                'bank_id' => $bank->id,
                'items' => [[
                    'nama_produk' => 'Jersey Premium',
                    'quantity' => 10,
                    'harga_satuan' => 100000,
                ]],
            ])
            ->assertRedirect();

        $order = Order::where('nama_po', 'PO Workflow Test')->first();
        $this->assertNotNull($order);
        $this->assertEquals('draft', $order->status_po);
        $this->assertEquals(1000000, $order->total_tagihan);

        $invoice = $order->invoices()->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('draft', $invoice->status);

        // Step 2: Request pembayaran DP (50%) di-validasi oleh Admin Keuangan
        $paymentDp = \App\Models\Order\OrderPayment::create([
            'order_id' => $order->id,
            'payment_type' => 'dp',
            'amount' => 500000,
            'payment_date' => now()->toDateString(),
            'recorded_by' => $adminBrand->id,
        ]);

        // Finance Admin validates the invoice
        $this->actingAsWithBrand($adminFinance, $brand)
            ->post(route('invoices.validate', $invoice->id), [
                'diskon_type' => 'nominal',
                'diskon_value' => 0,
                'biaya_pengiriman' => 0,
                'jasa_pengiriman' => 'Self Pickup',
                'catatan' => 'Validation OK',
            ])
            ->assertRedirect();
        
        $this->assertEquals('validated', $invoice->fresh()->status);

        // Finance Admin verifies the payment
        $this->actingAsWithBrand($adminFinance, $brand)
            ->post(route('invoices.payments.verify', $paymentDp->id), [
                'bank_mutasi' => true,
                'nominal_cocok' => true,
                'bukti_valid' => true,
                'verification_notes' => 'Pembayaran DP diverifikasi',
            ])
            ->assertRedirect();
        
        $this->assertNotNull($paymentDp->fresh()->verified_at);

        // Step 3: Masuk Produksi (Publish PO by Admin Brand)
        $this->actingAsWithBrand($adminBrand, $brand)
            ->post(route('orders.publish', $order->id))
            ->assertRedirect();

        $order = $order->fresh();
        $this->assertEquals('published', $order->status_po);
        $this->assertCount(3, $order->progressDetails);

        // Step 4: Tracking progress oleh Admin Produksi & "Lunas" Status Enforcement
        $details = $order->progressDetails()->with('progress')->get();
        $stage1Detail = $details->first(fn($d) => $d->progress->nama_progress === 'Tahap 1');
        $packingDetail = $details->first(fn($d) => $d->progress->nama_progress === 'PACKING');
        $sendingDetail = $details->first(fn($d) => $d->progress->nama_progress === 'SENDING');

        // Update Tahap 1 to on_progress
        $this->actingAsWithBrand($adminProduction, $brand)
            ->put(route('produksi.progress.update', [$order->id, $stage1Detail->id]), [
                'status' => 'on_progress',
                'catatan' => 'Tahap 1 in progress',
            ])
            ->assertRedirect();

        $order = $order->fresh();
        $this->assertEquals('on_progress', $order->status_po);

        // Update Tahap 1 to selesai
        $this->actingAsWithBrand($adminProduction, $brand)
            ->put(route('produksi.progress.update', [$order->id, $stage1Detail->id]), [
                'status' => 'selesai',
                'catatan' => 'Tahap 1 completed',
            ])
            ->assertRedirect();

        $order = $order->fresh();
        $this->assertEquals('published', $order->status_po);

        // Update PACKING to selesai
        $this->actingAsWithBrand($adminProduction, $brand)
            ->put(route('produksi.progress.update', [$order->id, $packingDetail->id]), [
                'status' => 'selesai',
                'catatan' => 'Packing completed',
            ])
            ->assertRedirect();

        $order = $order->fresh();
        $this->assertEquals('siap_dikirim', $order->status_po);

        // Try to update SENDING to selesai before fully paid (lunas). It must fail.
        $this->actingAsWithBrand($adminProduction, $brand)
            ->put(route('produksi.progress.update', [$order->id, $sendingDetail->id]), [
                'status' => 'selesai',
                'catatan' => 'Kirim barang',
                'nama_ekspedisi' => 'J&T',
                'no_resi' => 'JT998877',
            ])
            ->assertSessionHas('error');

        $this->assertNotEquals('sudah_dikirim', $order->fresh()->status_po);

        // Step 5: Finance Admin melakukan validasi pelunasan & menandai Lunas
        $paymentPelunasan = \App\Models\Order\OrderPayment::create([
            'order_id' => $order->id,
            'payment_type' => 'pelunasan',
            'amount' => 500000,
            'payment_date' => now()->toDateString(),
            'recorded_by' => $adminBrand->id,
        ]);

        $this->actingAsWithBrand($adminFinance, $brand)
            ->post(route('invoices.payments.verify', $paymentPelunasan->id), [
                'bank_mutasi' => true,
                'nominal_cocok' => true,
                'bukti_valid' => true,
                'verification_notes' => 'Pembayaran Pelunasan diverifikasi',
            ])
            ->assertRedirect();

        $this->actingAsWithBrand($adminFinance, $brand)
            ->post(route('orders.mark-lunas', $order->id))
            ->assertRedirect();

        $order = $order->fresh();
        $this->assertTrue($order->is_lunas);

        // Step 6: Kirim barang (SENDING status update becomes possible now)
        $this->actingAsWithBrand($adminProduction, $brand)
            ->put(route('produksi.progress.update', [$order->id, $sendingDetail->id]), [
                'status' => 'selesai',
                'catatan' => 'Dikirim lewat J&T',
                'nama_ekspedisi' => 'J&T',
                'no_resi' => 'JT998877',
            ])
            ->assertRedirect();

        $order = $order->fresh();
        $this->assertEquals('sudah_dikirim', $order->status_po);
        $this->assertEquals('J&T', $order->nama_ekspedisi);
        $this->assertEquals('JT998877', $order->no_resi);

        // Step 7: Admin Brand validasi refund & request refund, Finance Admin validasi/publish refund
        $this->actingAsWithBrand($adminBrand, $brand)
            ->post(route('refunds.store'), [
                'order_id' => $order->id,
                'alasan' => 'Ada item robek minor',
                'jenis_masalah' => 'produk_cacat',
                'jumlah_item' => 1,
                'nominal_refund' => 100000,
            ])
            ->assertRedirect();

        $refund = Refund::where('order_id', $order->id)->first();
        $this->assertNotNull($refund);
        $this->assertEquals('pending_review', $refund->status);

        // Finance Admin approves the refund
        $this->actingAsWithBrand($adminFinance, $brand)
            ->post(route('refunds.publish', $refund->id))
            ->assertRedirect();

        $this->assertEquals('published', $refund->fresh()->status);
        $this->assertDatabaseHas('pengeluaran', [
            'refund_id' => $refund->id,
            'nominal' => 100000,
            'is_auto' => true,
        ]);

        $this->assertEquals('sudah_dikirim', $order->fresh()->status_po);
    }

    public function test_refund_can_be_created_using_po_number_or_link(): void
    {
        $brand = $this->setupBrandWithMasters();
        $user = $this->makeUser('admin_brand', [$brand]);
        $customer = \App\Models\Master\Customer::where('brand_id', $brand->id)->first();

        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => app(NumberGenerator::class)->generateOrderNumber($brand),
            'nama_po' => 'Refund Test PO', 'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(14)->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 500000,
            'created_by' => $user->id,
        ]);

        // Submit refund using PO number (no_po)
        $this->actingAsWithBrand($user, $brand)
            ->post(route('refunds.store'), [
                'order_id' => $order->no_po,
                'alasan' => 'Ada item cacat produksi',
                'jenis_masalah' => 'produk_cacat',
                'jumlah_item' => 1,
                'nominal_refund' => 50000,
            ])
            ->assertRedirect();

        $refund = Refund::where('nominal_refund', 50000)->first();
        $this->assertNotNull($refund);
        $this->assertEquals($order->id, $refund->order_id);

        // Submit refund using PO link URL
        $poUrl = "http://127.0.0.1:8181/track/{$order->no_po}";
        $this->actingAsWithBrand($user, $brand)
            ->post(route('refunds.store'), [
                'order_id' => $poUrl,
                'alasan' => 'Item cacat produksi lain',
                'jenis_masalah' => 'produk_cacat',
                'jumlah_item' => 1,
                'nominal_refund' => 25000,
            ])
            ->assertRedirect();

        $refund2 = Refund::where('nominal_refund', 25000)->first();
        $this->assertNotNull($refund2);
        $this->assertEquals($order->id, $refund2->order_id);
    }

    public function test_can_create_order_with_extended_nameset_fields(): void
    {
        $brand = $this->setupBrandWithMasters();
        $user = $this->makeUser('admin_brand', [$brand]);
        $customer = Customer::where('brand_id', $brand->id)->first();
        
        $size = \App\Models\Master\Size::create([
            'kategori_size' => 'Dewasa',
            'ukuran' => 'L',
            'urutan' => 1,
            'is_active' => true,
        ]);

        $bank = \App\Models\Master\BankAccount::where('brand_id', $brand->id)->first();

        $this->actingAsWithBrand($user, $brand)
            ->post(route('orders.store'), [
                'nama_po' => 'PO Nameset Test',
                'tanggal_masuk' => now()->toDateString(),
                'deadline_customer' => now()->addDays(14)->toDateString(),
                'pelanggan_id' => $customer->id,
                'bank_id' => $bank->id,
                'items' => [[
                    'nama_produk' => 'Jersey Test',
                    'quantity' => 1,
                    'harga_satuan' => 100000,
                    'namesets' => [[
                        'nama_punggung' => 'Ahmad',
                        'nomor_punggung' => '7',
                        'nama_dada' => 'AHMAD DADA',
                        'nomor_dada' => '7D',
                        'nama_lengan' => 'AHMAD LENGAN',
                        'nomor_lengan' => '7L',
                        'nomor_punggung_2' => '7-2',
                        'nama_punggung_2' => 'AHMAD PUNGGUNG 2',
                        'size_id' => $size->id,
                        'size_label' => 'Dewasa - L',
                        'size_celana_id' => $size->id,
                        'size_celana_label' => 'Dewasa - L',
                        'keterangan' => 'Keterangan Custom',
                    ]]
                ]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', ['nama_po' => 'PO Nameset Test']);
        $order = Order::where('nama_po', 'PO Nameset Test')->first();
        $this->assertNotNull($order);
        
        $item = $order->items()->first();
        $this->assertNotNull($item);
        
        $nameset = $item->namesets()->first();
        $this->assertNotNull($nameset);
        $this->assertEquals('AHMAD PUNGGUNG 2', $nameset->nama_punggung_2);
        $this->assertEquals($size->id, $nameset->size_celana_id);
        $this->assertEquals('Dewasa - L', $nameset->size_celana_label);
    }

    public function test_published_po_is_locked_by_default_and_cannot_be_edited_unless_unlocked(): void
    {
        $brand = $this->setupBrandWithMasters();
        $user = $this->makeUser('admin_brand', [$brand]);
        $customer = Customer::where('brand_id', $brand->id)->first();

        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-LOCKED-TEST',
            'nama_po' => 'Locked PO',
            'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 100000,
            'created_by' => $user->id,
        ]);

        $this->actingAsWithBrand($user, $brand)
            ->get(route('orders.edit', $order->id))
            ->assertRedirect(); // Should redirect because it's locked by default

        // Let's unlock the PO
        app(\App\Services\POStatusManager::class)->unlock($order, $user);

        $this->actingAsWithBrand($user, $brand)
            ->get(route('orders.edit', $order->id))
            ->assertOk(); // Should succeed now that it's unlocked!
    }

    public function test_admin_brand_can_delete_draft_po(): void
    {
        $brand = $this->setupBrandWithMasters();
        $user = $this->makeUser('admin_brand', [$brand]);
        $customer = Customer::where('brand_id', $brand->id)->first();

        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-DRAFT-DEL',
            'nama_po' => 'Draft PO to Delete',
            'status_po' => 'draft',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 100000,
            'created_by' => $user->id,
        ]);

        $this->actingAsWithBrand($user, $brand)
            ->delete(route('orders.destroy', $order->id))
            ->assertRedirect(route('orders.index'));

        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }

    public function test_draft_po_with_payments_cannot_be_deleted(): void
    {
        $brand = $this->setupBrandWithMasters();
        $user = $this->makeUser('admin_brand', [$brand]);
        $customer = Customer::where('brand_id', $brand->id)->first();

        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-DRAFT-WITH-PAYMENT',
            'nama_po' => 'Draft PO with Payment',
            'status_po' => 'draft',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 100000,
            'created_by' => $user->id,
        ]);

        \App\Models\Order\OrderPayment::create([
            'order_id' => $order->id,
            'payment_type' => 'dp',
            'amount' => 50000,
            'payment_date' => now()->toDateString(),
            'recorded_by' => $user->id,
        ]);

        // Try deleting it
        $this->actingAsWithBrand($user, $brand)
            ->delete(route('orders.destroy', $order->id))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'deleted_at' => null]);
    }

    public function test_admin_produksi_can_update_timeline(): void
    {
        $brand = $this->setupBrandWithMasters();
        $user = $this->makeUser('admin_produksi', [$brand]);
        $customer = Customer::where('brand_id', $brand->id)->first();

        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-TIMELINE-TEST',
            'nama_po' => 'Timeline Test PO',
            'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 100000,
            'created_by' => $user->id,
        ]);

        $this->actingAsWithBrand($user, $brand)
            ->patch(route('orders.timeline.update', $order->id), [
                'start_production_date' => '2026-06-12',
                'end_production_date' => '2026-06-28',
            ])
            ->assertRedirect();

        $order = $order->fresh();
        $this->assertEquals('2026-06-12', $order->start_production_date->toDateString());
        $this->assertEquals('2026-06-28', $order->end_production_date->toDateString());

        // Assert POChangeLog recorded for start_production_date
        $this->assertDatabaseHas('po_change_logs', [
            'order_id' => $order->id,
            'changed_by' => $user->id,
            'field_changed' => 'start_production_date',
            'new_value' => '2026-06-12',
        ]);

        // Assert POChangeLog recorded for end_production_date
        $this->assertDatabaseHas('po_change_logs', [
            'order_id' => $order->id,
            'changed_by' => $user->id,
            'field_changed' => 'end_production_date',
            'new_value' => '2026-06-28',
        ]);
    }

    public function test_unlock_and_relock_po_permissions_and_audit_logging(): void
    {
        $brand = $this->setupBrandWithMasters();
        $customer = Customer::where('brand_id', $brand->id)->first();

        // 1. Test admin_brand can unlock and relock
        $adminBrand = $this->makeUser('admin_brand', [$brand]);
        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-UNLOCK-AB',
            'nama_po' => 'Unlock Brand PO',
            'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 100000,
            'created_by' => $adminBrand->id,
        ]);

        // Lock it first
        app(\App\Services\POStatusManager::class)->relock($order, $adminBrand);
        $this->assertTrue($order->fresh()->isLocked());

        // Create a supervisor
        $supervisor = $this->makeUser('supervisor', [$brand]);

        // 1. Admin Brand requests unlock
        $reason = 'Salah input detail ukuran produk';
        $this->actingAsWithBrand($adminBrand, $brand)
            ->post(route('orders.unlock', $order->id), [
                'reason' => $reason
            ])
            ->assertRedirect();

        // Should still be locked (requires supervisor approval)
        $this->assertTrue($order->fresh()->isLocked());
        $this->assertDatabaseHas('po_lock_status', [
            'order_id' => $order->id,
            'unlock_requested_by' => $adminBrand->id,
            'unlock_request_reason' => $reason,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $order->id,
            'activity' => 'unlock_request',
            'user_id' => $adminBrand->id,
        ]);

        // 2. Supervisor approves unlock request
        $this->actingAsWithBrand($supervisor, $brand)
            ->post(route('orders.unlock.approve', $order->id))
            ->assertRedirect();

        $this->assertFalse($order->fresh()->isLocked());
        $this->assertDatabaseHas('po_change_logs', [
            'order_id' => $order->id,
            'changed_by' => $adminBrand->id,
            'approved_by' => $supervisor->id,
            'field_changed' => '_unlock',
            'change_reason' => $reason,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $order->id,
            'activity' => 'unlock_approve',
            'user_id' => $supervisor->id,
        ]);

        // 3. Admin Brand requests relock
        $this->actingAsWithBrand($adminBrand, $brand)
            ->post(route('orders.relock', $order->id))
            ->assertRedirect();

        // Should still be unlocked (requires approval)
        $this->assertFalse($order->fresh()->isLocked());
        $this->assertDatabaseHas('po_lock_status', [
            'order_id' => $order->id,
            'relock_requested_by' => $adminBrand->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $order->id,
            'activity' => 'relock_request',
            'user_id' => $adminBrand->id,
        ]);

        // 4. Supervisor approves relock request
        $this->actingAsWithBrand($supervisor, $brand)
            ->post(route('orders.relock.approve', $order->id))
            ->assertRedirect();

        $this->assertTrue($order->fresh()->isLocked());
        $this->assertDatabaseHas('po_change_logs', [
            'order_id' => $order->id,
            'changed_by' => $adminBrand->id,
            'approved_by' => $supervisor->id,
            'field_changed' => '_relock',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $order->id,
            'activity' => 'relock_approve',
            'user_id' => $supervisor->id,
        ]);

        // 5. Test Admin Produksi requests unlock, then supervisor rejects it
        $adminProd = $this->makeUser('admin_produksi', [$brand]);
        $this->actingAsWithBrand($adminProd, $brand)
            ->post(route('orders.unlock', $order->id), [
                'reason' => 'Admin produksi request unlock'
            ])
            ->assertRedirect();

        $this->assertTrue($order->fresh()->isLocked());
        $this->assertDatabaseHas('po_lock_status', [
            'order_id' => $order->id,
            'unlock_requested_by' => $adminProd->id,
        ]);

        // Reject it
        $this->actingAsWithBrand($supervisor, $brand)
            ->post(route('orders.unlock.reject', $order->id))
            ->assertRedirect();

        $this->assertTrue($order->fresh()->isLocked());
        // Request columns should be cleared
        $this->assertDatabaseHas('po_lock_status', [
            'order_id' => $order->id,
            'unlock_requested_by' => null,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $order->id,
            'activity' => 'unlock_reject',
            'user_id' => $supervisor->id,
        ]);

        // 6. Test Admin Keuangan is blocked from approving
        $adminKeu = $this->makeUser('admin_keuangan', [$brand]);
        $this->actingAsWithBrand($adminKeu, $brand)
            ->post(route('orders.unlock.approve', $order->id))
            ->assertStatus(403);

        // 7. Test Supervisor can directly unlock and relock
        $this->actingAsWithBrand($supervisor, $brand)
            ->post(route('orders.unlock', $order->id), [
                'reason' => 'Supervisor direct unlock'
            ])
            ->assertRedirect();

        $this->assertFalse($order->fresh()->isLocked());

        $this->actingAsWithBrand($supervisor, $brand)
            ->post(route('orders.relock', $order->id))
            ->assertRedirect();

        $this->assertTrue($order->fresh()->isLocked());
    }

    public function test_order_creation_and_invoice_sync_with_product_level_discounts(): void
    {
        $brand = $this->setupBrandWithMasters();
        $user = $this->makeUser('admin_brand', [$brand]);
        $customer = Customer::where('brand_id', $brand->id)->first();
        $bank = \App\Models\Master\BankAccount::where('brand_id', $brand->id)->first();

        // 1. Create Order with items having discounts
        $this->actingAsWithBrand($user, $brand)
            ->post(route('orders.store'), [
                'nama_po' => 'Discount PO Test',
                'tanggal_masuk' => now()->toDateString(),
                'deadline_customer' => now()->addDays(14)->toDateString(),
                'pelanggan_id' => $customer->id,
                'bank_id' => $bank->id,
                'items' => [
                    [
                        'nama_produk' => 'Jersey A',
                        'quantity' => 10,
                        'harga_satuan' => 100000,
                        'discount_type' => 'persen',
                        'discount_value' => 10, // 10% discount -> amount: 100,000
                    ],
                    [
                        'nama_produk' => 'Jersey B',
                        'quantity' => 2,
                        'harga_satuan' => 200000,
                        'discount_type' => 'nominal',
                        'discount_value' => 50000, // 50,000 nominal discount
                    ],
                    [
                        'nama_produk' => 'Jersey C',
                        'quantity' => 1,
                        'harga_satuan' => 50000,
                        'discount_type' => '',
                        'discount_value' => 0,
                    ]
                ],
            ])
            ->assertRedirect();

        // Total Tagihan = (10 * 100,000 - 10%) + (2 * 200,000 - 50,000) + (1 * 50,000 - 0)
        // = (1,000,000 - 100,000) + (400,000 - 50,000) + 50,000 = 900,000 + 350,000 + 50,000 = 1,300,000
        $this->assertDatabaseHas('orders', [
            'nama_po' => 'Discount PO Test',
            'status_po' => 'draft',
            'total_tagihan' => 1300000
        ]);

        $order = Order::where('nama_po', 'Discount PO Test')->first();

        // Verify Order Items
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'nama_produk' => 'Jersey A',
            'discount_type' => 'persen',
            'discount_value' => 10,
            'discount_amount' => 100000,
            'subtotal' => 900000,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'nama_produk' => 'Jersey B',
            'discount_type' => 'nominal',
            'discount_value' => 50000,
            'discount_amount' => 50000,
            'subtotal' => 350000,
        ]);

        // Verify Invoice & Invoice Items
        $this->assertDatabaseHas('invoices', [
            'order_id' => $order->id,
            'total_tagihan' => 1300000,
        ]);

        $invoice = $order->invoices()->first();

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'produk' => 'Jersey A',
            'discount_type' => 'persen',
            'discount_value' => 10,
            'discount_amount' => 100000,
            'subtotal' => 900000,
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'produk' => 'Jersey B',
            'discount_type' => 'nominal',
            'discount_value' => 50000,
            'discount_amount' => 50000,
            'subtotal' => 350000,
        ]);

        // 2. Update Order items and verify discounts and subtotals recalculate and sync to invoice items
        $jerseyA = $order->items()->where('nama_produk', 'Jersey A')->first();
        $jerseyB = $order->items()->where('nama_produk', 'Jersey B')->first();
        $jerseyC = $order->items()->where('nama_produk', 'Jersey C')->first();

        $this->actingAsWithBrand($user, $brand)
            ->put(route('orders.update', $order->id), [
                'nama_po' => 'Discount PO Test Updated',
                'tanggal_masuk' => now()->toDateString(),
                'deadline_customer' => now()->addDays(14)->toDateString(),
                'pelanggan_id' => $customer->id,
                'bank_id' => $bank->id,
                'items' => [
                    [
                        'id' => $jerseyA->id,
                        'nama_produk' => 'Jersey A',
                        'quantity' => 10,
                        'harga_satuan' => 100000,
                        'discount_type' => 'persen',
                        'discount_value' => 20, // increased discount to 20% -> amount: 200,000 -> subtotal: 800,000
                    ],
                    [
                        'id' => $jerseyB->id,
                        'nama_produk' => 'Jersey B',
                        'quantity' => 2,
                        'harga_satuan' => 200000,
                        'discount_type' => 'nominal',
                        'discount_value' => 100000, // increased discount to 100,000 -> subtotal: 300,000
                    ],
                    // jersey C removed, which is supported by syncItems delete logic
                ],
            ])
            ->assertRedirect();

        // New total tagihan: 800,000 + 300,000 = 1,100,000
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'nama_po' => 'Discount PO Test Updated',
            'total_tagihan' => 1100000,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'nama_produk' => 'Jersey A',
            'discount_type' => 'persen',
            'discount_value' => 20,
            'discount_amount' => 200000,
            'subtotal' => 800000,
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'produk' => 'Jersey A',
            'discount_type' => 'persen',
            'discount_value' => 20,
            'discount_amount' => 200000,
            'subtotal' => 800000,
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'produk' => 'Jersey B',
            'discount_type' => 'nominal',
            'discount_value' => 100000,
            'discount_amount' => 100000,
            'subtotal' => 300000,
        ]);
    }

    public function test_spk_pdf_and_preview_access(): void
    {
        $brand = $this->setupBrandWithMasters();
        $user = $this->makeUser('admin_brand', [$brand]);
        $customer = Customer::where('brand_id', $brand->id)->first();

        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => app(NumberGenerator::class)->generateOrderNumber($brand),
            'nama_po' => 'SPK Test PO',
            'status_po' => 'draft',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(14)->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 100000,
            'created_by' => $user->id,
        ]);

        // Test PDF download access
        $this->actingAsWithBrand($user, $brand)
            ->get(route('orders.spk.pdf', $order->id))
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');

        // Test Web Preview access
        $response = $this->actingAsWithBrand($user, $brand)
            ->get(route('orders.spk.preview', $order->id))
            ->assertStatus(200);

        $response->assertViewHas('isWebPreview', true);
        $response->assertViewHas('order');
    }

    public function test_admin_keuangan_can_edit_payment_and_generate_audit_trail()
    {
        $brand = $this->setupBrandWithMasters();
        $adminBrand = $this->makeUser('admin_brand', [$brand]);
        $adminKeuangan = $this->makeUser('admin_keuangan', [$brand]);
        $customer = Customer::where('brand_id', $brand->id)->first();
        $bank = \App\Models\Master\BankAccount::where('brand_id', $brand->id)->first();

        // Create the order via the store endpoint
        $this->actingAsWithBrand($adminBrand, $brand)
            ->post(route('orders.store'), [
                'nama_po' => 'PO Edit Payment Test',
                'tanggal_masuk' => now()->toDateString(),
                'deadline_customer' => now()->addDays(14)->toDateString(),
                'pelanggan_id' => $customer->id,
                'bank_id' => $bank->id,
                'items' => [[
                    'nama_produk' => 'Jersey Test',
                    'quantity' => 10,
                    'harga_satuan' => 100000,
                ]],
            ])
            ->assertRedirect();

        $order = Order::where('nama_po', 'PO Edit Payment Test')->first();
        $invoice = $order->invoices()->first();

        $jp = \App\Models\Finance\MasterJenisPembayaran::firstOrCreate(
            ['nama' => 'DP'],
            ['tipe_keuangan' => 'pemasukan', 'deskripsi' => 'Down Payment']
        );
        $jp2 = \App\Models\Finance\MasterJenisPembayaran::firstOrCreate(
            ['nama' => 'Pelunasan'],
            ['tipe_keuangan' => 'pemasukan', 'deskripsi' => 'Pelunasan']
        );

        $payment = \App\Models\Order\OrderPayment::create([
            'order_id' => $order->id,
            'master_jenis_pembayaran_id' => $jp->id,
            'payment_type' => 'dp',
            'amount' => 400000,
            'payment_date' => now()->toDateString(),
            'bank_id' => $bank->id,
            'is_debit' => true,
            'verified_at' => now(),
            'verified_by' => null,
            'recorded_by' => $adminBrand->id,
        ]);

        $kategori = \App\Models\Finance\KategoriPemasukan::firstOrCreate(
            ['brand_id' => $brand->id, 'nama_kategori' => 'Pembayaran PO'],
            ['deskripsi' => 'Pembayaran pesanan dari customer', 'is_system' => true, 'is_active' => true]
        );
        $pemasukan = Pemasukan::create([
            'brand_id' => $brand->id,
            'kategori_pemasukan_id' => $kategori->id,
            'order_id' => $order->id,
            'source_payment_id' => $payment->id,
            'tanggal' => $payment->payment_date,
            'nominal' => $payment->amount,
            'keterangan' => 'DP PO test',
            'is_auto' => true,
            'created_by' => $adminBrand->id,
        ]);

        $this->actingAsWithBrand($adminBrand, $brand)
            ->put(route('invoices.payments.update', $payment->id), [
                'master_jenis_pembayaran_id' => $jp2->id,
                'amount' => 500000,
                'payment_date' => now()->addDay()->toDateString(),
                'bank_id' => $bank->id,
                'notes' => 'Catatan revisi',
                'change_reason' => 'Perbaikan nominal pembayaran',
            ])
            ->assertStatus(403);

        $this->actingAsWithBrand($adminKeuangan, $brand)
            ->put(route('invoices.payments.update', $payment->id), [
                'master_jenis_pembayaran_id' => $jp2->id,
                'amount' => 500000,
                'payment_date' => now()->addDay()->toDateString(),
                'bank_id' => $bank->id,
                'notes' => 'Catatan revisi',
                'change_reason' => 'Perbaikan nominal pembayaran',
            ])
            ->assertStatus(302);

        $payment->refresh();
        $this->assertEquals(500000, $payment->amount);
        $this->assertEquals($jp2->id, $payment->master_jenis_pembayaran_id);
        $this->assertEquals('Catatan revisi', $payment->notes);

        $this->assertDatabaseHas('po_change_logs', [
            'order_id' => $order->id,
            'field_changed' => 'pembayaran_diedit',
            'change_reason' => 'Perbaikan nominal pembayaran',
            'changed_by' => $adminKeuangan->id,
        ]);

        $pemasukan->refresh();
        $this->assertEquals(500000, $pemasukan->nominal);
    }
}


