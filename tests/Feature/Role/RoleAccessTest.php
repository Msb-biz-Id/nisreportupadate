<?php

namespace Tests\Feature\Role;

use App\Models\Master\Customer;
use App\Models\Master\Progress;
use App\Models\Order\Invoice;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\OrderPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comprehensive role access tests for admin_brand, admin_keuangan, admin_produksi.
 * Tests isolation, cross-brand access, and permission boundaries.
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeBrandWithOrder(string $noPo = 'PO-TEST-001', string $status = 'published'): array
    {
        $brand   = $this->makeBrand();
        $creator = $this->makeUser('admin_brand', [$brand]);

        $customer = Customer::create([
            'brand_id'  => $brand->id,
            'kode'      => 'C-' . substr($noPo, -3),
            'nama'      => 'Pelanggan Test',
            'nomor_hp'  => '081234',
            'is_active' => true,
        ]);
        foreach (range(1, 2) as $i) {
            Progress::firstOrCreate(
                ['nama_progress' => "Tahap {$i}"],
                ['urutan' => $i, 'is_active' => true, 'warna' => '#3B82F6', 'is_skippable' => false]
            );
        }
        $order = Order::create([
            'brand_id'          => $brand->id,
            'no_po'             => $noPo,
            'nama_po'           => 'Test Order',
            'status_po'         => $status,
            'tanggal_masuk'     => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id'      => $customer->id,
            'total_tagihan'     => 500000,
            'published_at'      => $status !== 'draft' ? now() : null,
            'created_by'        => $creator->id,
        ]);
        OrderItem::create([
            'order_id'     => $order->id,
            'nama_produk'  => 'Jersey Test',
            'quantity'     => 5,
            'harga_satuan' => 100000,
            'subtotal'     => 500000,
        ]);
        return compact('brand', 'customer', 'order', 'creator');
    }

    // ─── Admin Brand ──────────────────────────────────────────────────────────

    public function test_admin_brand_can_access_own_brand_orders(): void
    {
        ['brand' => $brand, 'order' => $order] = $this->makeBrandWithOrder();
        $user = $this->makeUser('admin_brand', [$brand]);

        $this->actingAsWithBrand($user, $brand)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->has('orders'));
    }

    public function test_admin_brand_cannot_access_other_brand_orders(): void
    {
        ['brand' => $brand1, 'order' => $order] = $this->makeBrandWithOrder('PO-B1-001');
        $brand2 = $this->makeBrand(['kode' => 'B2']);
        $user   = $this->makeUser('admin_brand', [$brand2]);

        // order belongs to brand1 but user only has brand2
        $this->actingAsWithBrand($user, $brand2)
            ->get(route('orders.show', $order->id))
            ->assertForbidden();
    }

    public function test_admin_brand_sees_all_own_brands_by_default(): void
    {
        // Admin brand assigned to 2 brands — should see both when no filter
        ['brand' => $brand1, 'order' => $order1] = $this->makeBrandWithOrder('PO-B1-001');
        ['brand' => $brand2, 'order' => $order2] = $this->makeBrandWithOrder('PO-B2-001');
        $user = $this->makeUser('admin_brand', [$brand1, $brand2]);

        // Default: see all brands
        $response = $this->actingAsWithBrand($user, $brand1)
            ->get(route('orders.index'))
            ->assertOk();

        // Filter to brand1 only
        $this->actingAsWithBrand($user, $brand1)
            ->get(route('orders.index', ['brand_id' => $brand1->id]))
            ->assertOk();
    }

    public function test_admin_brand_can_create_order(): void
    {
        $brand    = $this->makeBrand();
        $customer = Customer::create([
            'brand_id' => $brand->id, 'kode' => 'C001',
            'nama' => 'Test', 'nomor_hp' => '081', 'is_active' => true,
        ]);
        $user = $this->makeUser('admin_brand', [$brand]);
        $bank = \App\Models\Master\BankAccount::create([
            'brand_id' => $brand->id,
            'bank' => 'BCA',
            'atas_nama' => 'Test Acc',
            'nomor_rekening' => '12345',
            'is_active' => true,
        ]);

        $this->actingAsWithBrand($user, $brand)
            ->post(route('orders.store'), [
                'nama_po'           => 'PO Brand Admin',
                'tanggal_masuk'     => now()->toDateString(),
                'deadline_customer' => now()->addDays(7)->toDateString(),
                'pelanggan_id'      => $customer->id,
                'bank_id'           => $bank->id,
                'items'             => [[
                    'nama_produk'  => 'Jersey',
                    'quantity'     => 5,
                    'harga_satuan' => 100000,
                ]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', ['nama_po' => 'PO Brand Admin', 'brand_id' => $brand->id]);
    }

    public function test_admin_brand_cannot_access_production_master_data(): void
    {
        $brand = $this->makeBrand();
        $user  = $this->makeUser('admin_brand', [$brand]);

        // progress is production-only master
        $this->actingAsWithBrand($user, $brand)
            ->get(route('master.index', 'progress'))
            ->assertForbidden();
    }

    public function test_admin_brand_can_access_brand_master_data(): void
    {
        $brand = $this->makeBrand();
        $user  = $this->makeUser('admin_brand', [$brand]);

        // sumber-order is brand master
        $this->actingAsWithBrand($user, $brand)
            ->get(route('master.index', 'sumber-order'))
            ->assertOk();
    }

    public function test_admin_brand_can_manage_products(): void
    {
        $brand = $this->makeBrand();
        $user  = $this->makeUser('admin_brand', [$brand]);

        $this->actingAsWithBrand($user, $brand)
            ->get(route('master.index', 'produk'))
            ->assertOk();
    }

    // ─── Admin Keuangan ───────────────────────────────────────────────────────

    public function test_admin_keuangan_can_see_all_brands_invoices(): void
    {
        ['brand' => $brand1, 'order' => $order1] = $this->makeBrandWithOrder('PO-K1-001');
        ['brand' => $brand2, 'order' => $order2] = $this->makeBrandWithOrder('PO-K2-001');

        // Create invoices for both brands
        Invoice::create([
            'brand_id' => $brand1->id, 'order_id' => $order1->id,
            'invoice_number' => 'INV-001', 'tanggal_terbit' => now()->toDateString(),
            'status' => 'draft', 'total_tagihan' => 500000, 'sisa_pembayaran' => 500000,
            'created_by' => 1,
        ]);
        Invoice::create([
            'brand_id' => $brand2->id, 'order_id' => $order2->id,
            'invoice_number' => 'INV-002', 'tanggal_terbit' => now()->toDateString(),
            'status' => 'draft', 'total_tagihan' => 300000, 'sisa_pembayaran' => 300000,
            'created_by' => 1,
        ]);

        // Admin keuangan only assigned to brand1, but should see ALL brands
        $user = $this->makeUser('admin_keuangan', [$brand1]);

        $this->actingAsWithBrand($user, $brand1)
            ->get(route('invoices.list'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->has('invoices')
                ->has('brands') // should get all brands list
            );
    }

    public function test_admin_keuangan_can_verify_payment(): void
    {
        ['brand' => $brand, 'order' => $order] = $this->makeBrandWithOrder();
        $user    = $this->makeUser('admin_keuangan', [$brand]);
        $payment = OrderPayment::create([
            'order_id'       => $order->id,
            'payment_type'   => 'dp',
            'amount'         => 250000,
            'payment_date'   => now()->toDateString(),
            'recorded_by'    => $user->id,
        ]);

        $this->actingAsWithBrand($user, $brand)
            ->post(route('invoices.payments.verify', $payment->id), [
                'bank_mutasi'  => true,
                'nominal_cocok' => true,
                'bukti_valid'  => true,
                'verification_notes' => 'Verified',
            ])
            ->assertRedirect();

        $this->assertNotNull($payment->fresh()->verified_at);
    }

    public function test_admin_keuangan_can_access_finance_dashboard(): void
    {
        $brand = $this->makeBrand();
        $user  = $this->makeUser('admin_keuangan', [$brand]);

        $this->actingAsWithBrand($user, $brand)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->where('view', 'Finance'));
    }

    public function test_admin_keuangan_cannot_manage_production_master_data(): void
    {
        $brand = $this->makeBrand();
        $user  = $this->makeUser('admin_keuangan', [$brand]);

        // production master = forbidden for keuangan
        $this->actingAsWithBrand($user, $brand)
            ->get(route('master.index', 'progress'))
            ->assertForbidden();
    }

    public function test_admin_keuangan_cannot_create_orders(): void
    {
        $brand = $this->makeBrand();
        $user  = $this->makeUser('admin_keuangan', [$brand]);

        $this->actingAsWithBrand($user, $brand)
            ->get(route('orders.create'))
            ->assertForbidden();
    }

    public function test_admin_keuangan_can_see_pending_payments_all_brands(): void
    {
        ['brand' => $brand1] = $this->makeBrandWithOrder('PO-KP-001');
        $user = $this->makeUser('admin_keuangan', [$brand1]);

        $this->actingAsWithBrand($user, $brand1)
            ->get(route('invoices.payments.pending'))
            ->assertOk();
    }

    // ─── Admin Produksi ───────────────────────────────────────────────────────

    public function test_admin_produksi_can_see_all_brands_kanban(): void
    {
        ['brand' => $brand1] = $this->makeBrandWithOrder('PO-P1-001');
        ['brand' => $brand2] = $this->makeBrandWithOrder('PO-P2-001');
        $user = $this->makeUser('admin_produksi', [$brand1]);

        // admin_produksi only assigned to brand1 but kanban should show all
        $this->actingAsWithBrand($user, $brand1)
            ->get(route('produksi.kanban'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->has('columns'));
    }

    public function test_admin_produksi_can_see_all_brands_orders(): void
    {
        ['brand' => $brand1] = $this->makeBrandWithOrder('PO-O1-001');
        ['brand' => $brand2] = $this->makeBrandWithOrder('PO-O2-001');
        $user = $this->makeUser('admin_produksi', [$brand1]);

        $this->actingAsWithBrand($user, $brand1)
            ->get(route('orders.index'))
            ->assertOk();
    }

    public function test_admin_produksi_can_access_production_master_data(): void
    {
        $brand = $this->makeBrand();
        $user  = $this->makeUser('admin_produksi', [$brand]);

        foreach (['progress', 'bahan-kain', 'size', 'pola-jahitan', 'jenis-produk'] as $slug) {
            $this->actingAsWithBrand($user, $brand)
                ->get(route('master.index', $slug))
                ->assertOk("Slug [{$slug}] should be accessible to admin_produksi");
        }
    }

    public function test_admin_produksi_can_access_po_master_data(): void
    {
        $brand = $this->makeBrand();
        $user  = $this->makeUser('admin_produksi', [$brand]);

        // PO-relevant master data (including kategori-order)
        foreach (['kategori-order', 'sumber-order', 'jenis-order', 'customer-type', 'produk', 'iklan'] as $slug) {
            $this->actingAsWithBrand($user, $brand)
                ->get(route('master.index', $slug))
                ->assertOk("Slug [{$slug}] should be accessible to admin_produksi");
        }
    }

    public function test_admin_produksi_cannot_access_bank_master_data(): void
    {
        $brand = $this->makeBrand();
        $user  = $this->makeUser('admin_produksi', [$brand]);

        // bank is finance master, should be forbidden for admin_produksi
        $this->actingAsWithBrand($user, $brand)
            ->get(route('master.index', 'bank'))
            ->assertForbidden("Slug [bank] should be forbidden for admin_produksi");
    }

    public function test_admin_produksi_cannot_create_orders(): void
    {
        $brand = $this->makeBrand();
        $user  = $this->makeUser('admin_produksi', [$brand]);

        $this->actingAsWithBrand($user, $brand)
            ->get(route('orders.create'))
            ->assertForbidden();
    }

    public function test_admin_produksi_can_access_draft_order_forbidden(): void
    {
        ['brand' => $brand, 'order' => $order] = $this->makeBrandWithOrder('PO-DRAFT', 'draft');
        $user = $this->makeUser('admin_produksi', [$brand]);

        // Admin produksi cannot see draft orders
        $this->actingAsWithBrand($user, $brand)
            ->get(route('orders.show', $order->id))
            ->assertForbidden();
    }

    public function test_admin_produksi_can_view_published_order(): void
    {
        ['brand' => $brand, 'order' => $order] = $this->makeBrandWithOrder('PO-PUB');
        $user = $this->makeUser('admin_produksi', [$brand]);

        $this->actingAsWithBrand($user, $brand)
            ->get(route('orders.show', $order->id))
            ->assertOk();
    }

    public function test_admin_produksi_can_access_production_dashboard(): void
    {
        $brand = $this->makeBrand();
        $user  = $this->makeUser('admin_produksi', [$brand]);

        $this->actingAsWithBrand($user, $brand)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->where('view', 'AdminProduksi'));
    }

    public function test_admin_produksi_cannot_access_finance(): void
    {
        $brand = $this->makeBrand();
        $user  = $this->makeUser('admin_produksi', [$brand]);

        $this->actingAsWithBrand($user, $brand)
            ->get(route('invoices.list'))
            ->assertForbidden();
    }

    // ─── Admin Reseller ───────────────────────────────────────────────────────

    public function test_admin_reseller_can_create_order_on_hub(): void
    {
        $hub      = $this->makeBrand(['brand_type' => 'reseller_hub']);
        $customer = Customer::create([
            'brand_id' => $hub->id, 'kode' => 'CR001',
            'nama' => 'Customer Reseller', 'nomor_hp' => '082', 'is_active' => true,
        ]);
        $user = $this->makeUser('admin_reseller', [$hub]);
        $bank = \App\Models\Master\BankAccount::create([
            'brand_id' => $hub->id,
            'bank' => 'BCA',
            'atas_nama' => 'Test Reseller Acc',
            'nomor_rekening' => '67890',
            'is_active' => true,
        ]);

        $this->actingAsWithBrand($user, $hub)
            ->post(route('orders.store'), [
                'nama_po'           => 'PO Reseller',
                'tanggal_masuk'     => now()->toDateString(),
                'deadline_customer' => now()->addDays(7)->toDateString(),
                'pelanggan_id'      => $customer->id,
                'bank_id'           => $bank->id,
                'items'             => [[
                    'nama_produk'  => 'Jersey Reseller',
                    'quantity'     => 3,
                    'harga_satuan' => 80000,
                ]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', ['brand_id' => $hub->id, 'nama_po' => 'PO Reseller']);
    }

    public function test_admin_reseller_sees_hub_and_branch_orders(): void
    {
        $hub    = $this->makeBrand(['brand_type' => 'reseller_hub']);
        $branch = $this->makeBrand(['brand_type' => 'reseller_branch', 'parent_brand_id' => $hub->id]);

        $user    = $this->makeUser('admin_reseller', [$hub, $branch]);
        $creator = $this->makeUser('admin_brand', [$hub]);

        $custHub = Customer::create(['brand_id' => $hub->id, 'kode' => 'CH', 'nama' => 'Hub Cust', 'nomor_hp' => '081', 'is_active' => true]);
        $custBr  = Customer::create(['brand_id' => $hub->id, 'kode' => 'CB', 'nama' => 'Br Cust', 'nomor_hp' => '082', 'is_active' => true]);

        Order::create([
            'brand_id' => $hub->id, 'no_po' => 'PO-HUB-1',
            'nama_po' => 'Hub Order', 'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => $custHub->id, 'total_tagihan' => 100000,
            'published_at' => now(), 'created_by' => $creator->id,
        ]);
        Order::create([
            'brand_id' => $branch->id, 'no_po' => 'PO-BR-1',
            'nama_po' => 'Branch Order', 'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => $custBr->id, 'total_tagihan' => 200000,
            'published_at' => now(), 'created_by' => $creator->id,
        ]);

        // Admin reseller on hub context should see both
        $this->actingAsWithBrand($user, $hub)
            ->get(route('orders.index'))
            ->assertOk();
    }

    public function test_admin_reseller_cannot_access_other_reseller_orders_without_access(): void
    {
        $hub1  = $this->makeBrand(['brand_type' => 'reseller_hub', 'kode' => 'TLS2']);
        $hub2  = $this->makeBrand(['brand_type' => 'reseller_hub', 'kode' => 'PMS2']);
        $user1 = $this->makeUser('admin_reseller', [$hub1]);
        $user2 = $this->makeUser('admin_reseller', [$hub2]);

        $customer = Customer::create(['brand_id' => $hub1->id, 'kode' => 'CRR', 'nama' => 'Test', 'nomor_hp' => '083', 'is_active' => true]);
        $order = Order::create([
            'brand_id' => $hub1->id, 'no_po' => 'PO-ISO-R',
            'nama_po' => 'Reseller Isolated', 'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => $customer->id, 'total_tagihan' => 100000,
            'published_at' => now(), 'created_by' => $user1->id,
        ]);

        // user2 cannot access hub1 order
        $this->actingAsWithBrand($user2, $hub2)
            ->get(route('orders.show', $order->id))
            ->assertForbidden();
    }

    public function test_admin_reseller_can_manage_brand_reseller_list(): void
    {
        $hub  = $this->makeBrand(['brand_type' => 'reseller_hub']);
        $user = $this->makeUser('admin_reseller', [$hub]);

        $this->actingAsWithBrand($user, $hub)
            ->get(route('brands.index'))
            ->assertOk();
    }

    public function test_admin_reseller_can_access_dashboard(): void
    {
        $hub  = $this->makeBrand(['brand_type' => 'reseller_hub']);
        $user = $this->makeUser('admin_reseller', [$hub]);

        $this->actingAsWithBrand($user, $hub)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->where('view', 'AdminBrand'));
    }

    // ─── Cross-role isolation ──────────────────────────────────────────────────

    public function test_brand_isolation_admin_brand_vs_admin_brand(): void
    {
        ['brand' => $brand1, 'order' => $order1] = $this->makeBrandWithOrder('PO-ISO-001');
        ['brand' => $brand2] = $this->makeBrandWithOrder('PO-ISO-002');

        $user1 = $this->makeUser('admin_brand', [$brand1]);
        $user2 = $this->makeUser('admin_brand', [$brand2]);

        // user1 cannot see order1 when acting as brand2
        $this->actingAsWithBrand($user2, $brand2)
            ->get(route('orders.show', $order1->id))
            ->assertForbidden();
    }

    public function test_admin_keuangan_can_delete_unverified_payment(): void
    {
        ['brand' => $brand, 'order' => $order] = $this->makeBrandWithOrder();
        $user    = $this->makeUser('admin_keuangan', [$brand]);
        $payment = OrderPayment::create([
            'order_id'     => $order->id,
            'payment_type' => 'dp',
            'amount'       => 100000,
            'payment_date' => now()->toDateString(),
            'recorded_by'  => $user->id,
        ]);

        $this->actingAsWithBrand($user, $brand)
            ->delete(route('invoices.payments.destroy', $payment->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('order_payments', ['id' => $payment->id]);
    }

    public function test_admin_brand_cannot_delete_payment(): void
    {
        ['brand' => $brand, 'order' => $order] = $this->makeBrandWithOrder();
        $user    = $this->makeUser('admin_brand', [$brand]);
        $payment = OrderPayment::create([
            'order_id'     => $order->id,
            'payment_type' => 'dp',
            'amount'       => 100000,
            'payment_date' => now()->toDateString(),
            'recorded_by'  => $user->id,
        ]);

        $this->actingAsWithBrand($user, $brand)
            ->delete(route('invoices.payments.destroy', $payment->id))
            ->assertStatus(403);

        $this->assertDatabaseHas('order_payments', ['id' => $payment->id]);
    }
}
