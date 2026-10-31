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

    public function test_admin_produksi_cannot_access_brand_master_data(): void
    {
        $brand = $this->makeBrand();
        $user  = $this->makeUser('admin_produksi', [$brand]);

        // PO-relevant master data / brand-only master should be forbidden for admin_produksi
        foreach (['sumber-order', 'jenis-order', 'customer-type', 'produk', 'iklan'] as $slug) {
            $this->actingAsWithBrand($user, $brand)
                ->get(route('master.index', $slug))
                ->assertForbidden("Slug [{$slug}] should be forbidden for admin_produksi");
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

    public function test_reseller_branch_uses_regular_parent_brand_master_data(): void
    {
        $regular = $this->makeBrand(['brand_type' => \App\Models\Brand::TYPE_REGULAR]);
        $branch  = $this->makeBrand(['brand_type' => \App\Models\Brand::TYPE_RESELLER_BRANCH, 'parent_brand_id' => $regular->id]);

        $user = $this->makeUser('admin_brand', [$branch]);

        // Create some master data on the parent regular brand
        $category = \App\Models\Master\SumberOrder::create([
            'brand_id'  => $regular->id,
            'nama'      => 'Sumber Parent',
            'is_active' => true,
        ]);

        // When acting as the reseller branch, we should be able to see this category because of masterDataId resolution
        $this->actingAsWithBrand($user, $branch)
            ->get(route('master.index', 'sumber-order'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->has('items.data', 1)
                ->where('items.data.0.nama', 'Sumber Parent')
            );
    }

    public function test_reseller_hub_uses_parent_brand_master_data(): void
    {
        $regular = $this->makeBrand(['brand_type' => \App\Models\Brand::TYPE_REGULAR]);
        $hub     = $this->makeBrand(['brand_type' => \App\Models\Brand::TYPE_RESELLER_HUB, 'parent_brand_id' => $regular->id]);

        $user = $this->makeUser('admin_brand', [$hub]);

        // Create some master data on the parent regular brand
        $category = \App\Models\Master\SumberOrder::create([
            'brand_id'  => $regular->id,
            'nama'      => 'Sumber Hub Parent',
            'is_active' => true,
        ]);

        // When acting as the reseller hub, we should be able to see this category because of masterDataId resolution
        $this->actingAsWithBrand($user, $hub)
            ->get(route('master.index', 'sumber-order'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->has('items.data', 1)
                ->where('items.data.0.nama', 'Sumber Hub Parent')
            );
    }

    public function test_reseller_without_parent_falls_back_to_first_reseller_hub_master_data(): void
    {
        // First reseller hub (acts as the master for reseller data)
        $firstHub = $this->makeBrand(['brand_type' => \App\Models\Brand::TYPE_RESELLER_HUB, 'kode' => 'H1']);
        
        // Second reseller hub without a parent brand
        $secondHub = $this->makeBrand(['brand_type' => \App\Models\Brand::TYPE_RESELLER_HUB, 'kode' => 'H2', 'parent_brand_id' => null]);

        $user = $this->makeUser('admin_brand', [$secondHub]);

        // Create some master data on the first reseller hub (the default master)
        $category = \App\Models\Master\SumberOrder::create([
            'brand_id'  => $firstHub->id,
            'nama'      => 'Sumber Reseller Master',
            'is_active' => true,
        ]);

        // When acting as the second reseller hub (without parent), it should fallback to the first reseller hub
        $this->actingAsWithBrand($user, $secondHub)
            ->get(route('master.index', 'sumber-order'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->has('items.data', 1)
                ->where('items.data.0.nama', 'Sumber Reseller Master')
            );
    }

    public function test_reseller_bank_accounts_are_inherited_and_have_dynamic_reseller_atas_nama(): void
    {
        // Setup reseller global settings name
        \App\Models\Settings\SystemSetting::set('reseller_branding', 'nama_brand', 'INDOWAREHOUSE');

        $regular = $this->makeBrand(['brand_type' => \App\Models\Brand::TYPE_REGULAR, 'nama_brand' => 'Allegiant Owner']);
        $hub     = $this->makeBrand(['brand_type' => \App\Models\Brand::TYPE_RESELLER_HUB, 'parent_brand_id' => $regular->id, 'nama_brand' => 'INDOWAREHOUSE']);

        $user = $this->makeUser('admin_brand', [$hub]);

        // Create a bank account on regular brand
        $regularBank = \App\Models\Master\BankAccount::create([
            'brand_id' => $regular->id,
            'bank' => 'BCA',
            'atas_nama' => 'Original Owner',
            'nomor_rekening' => '1111111111',
            'is_active' => true,
        ]);

        // 1. Verify that when accessing Master Bank under Hub context, we see the inherited Regular brand's bank account,
        // and its 'atas_nama' is dynamically resolved to 'INDOWAREHOUSE' (reseller global settings brand name).
        $this->actingAsWithBrand($user, $hub)
            ->get(route('master.index', 'bank'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->has('items.data', 1)
                ->where('items.data.0.id', $regularBank->id)
                ->where('items.data.0.atas_nama', 'INDOWAREHOUSE')
            );

        // 2. Verify invoice fallback uses the same bank account from the masterDataId brand context
        $customer = \App\Models\Master\Customer::create([
            'brand_id' => $hub->id,
            'kode' => 'C-HUB',
            'nama' => 'Hub Customer',
            'nomor_hp' => '081234',
            'is_active' => true,
        ]);

        $order = \App\Models\Order\Order::create([
            'brand_id' => $hub->id,
            'no_po' => 'PO-HUB-01',
            'nama_po' => 'Test Hub Order',
            'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 100000,
            'created_by' => $user->id,
        ]);

        $invoiceWithoutHubBank = \App\Models\Order\Invoice::create([
            'invoice_number' => 'INV-TEST-FALLBACK',
            'brand_id' => $hub->id,
            'order_id' => $order->id,
            'status' => 'draft',
            'total_tagihan' => 100000,
            'sisa_pembayaran' => 100000,
            'tanggal_terbit' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        // Simulate session context for test model access
        session([\App\Support\BrandContext::SESSION_KEY => $hub->id]);

        $invoice = \App\Models\Order\Invoice::where('id', $invoiceWithoutHubBank->id)->with('brand.parentBrand')->first();
        $this->assertNull($invoice->bank_id);
        
        // Simulating the fallback logic in controller:
        $bankBrandId = \App\Support\BrandContext::masterDataId(request(), $invoice->brand_id);
        $defaultBank = \App\Models\Master\BankAccount::active()->where('brand_id', $bankBrandId)->first();
        
        $this->assertNotNull($defaultBank);
        $this->assertEquals($regularBank->id, $defaultBank->id);
        $this->assertEquals('INDOWAREHOUSE', $defaultBank->atas_nama);
    }

    public function test_non_superadmin_non_owner_cannot_see_or_modify_superadmin_and_owner_users(): void
    {
        $brand = $this->makeBrand();
        
        // 1. Create a reseller admin
        $resellerAdmin = $this->makeUser('admin_reseller', [$brand]);
        
        // 2. Create a superadmin user
        $superadmin = $this->makeUser('superadmin', [$brand], ['email' => 'super@test.com']);
        
        // 3. Create an owner user
        $owner = $this->makeUser('owner', [$brand], ['email' => 'owner@test.com']);
        
        // 4. Create a regular brand user (which the reseller should see since they share the brand)
        $brandAdmin = $this->makeUser('admin_brand', [$brand], ['email' => 'brandadmin@test.com']);

        // A. Listing users as reseller admin:
        // They should see the brandAdmin, but NOT the superadmin or owner
        $this->actingAsWithBrand($resellerAdmin, $brand)
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->has('users.data')
                // Verify superadmin and owner are NOT in the list:
                ->where('users.data', function ($users) {
                    $emails = collect($users)->pluck('email');
                    return ! $emails->contains('super@test.com') && ! $emails->contains('owner@test.com');
                })
            );

        // B. Trying to update superadmin: should be Forbidden (403)
        $this->actingAsWithBrand($resellerAdmin, $brand)
            ->put(route('users.update', $superadmin->id), [
                'name' => 'Updated Super',
                'email' => 'super@test.com',
                'role' => 'superadmin',
                'brand_ids' => [$brand->id],
                'default_brand_id' => $brand->id,
            ])
            ->assertStatus(403);

        // C. Trying to update owner: should be Forbidden (403)
        $this->actingAsWithBrand($resellerAdmin, $brand)
            ->put(route('users.update', $owner->id), [
                'name' => 'Updated Owner',
                'email' => 'owner@test.com',
                'role' => 'owner',
                'brand_ids' => [$brand->id],
                'default_brand_id' => $brand->id,
            ])
            ->assertStatus(403);

        // D. Trying to delete superadmin: should be Forbidden (403)
        $this->actingAsWithBrand($resellerAdmin, $brand)
            ->delete(route('users.destroy', $superadmin->id))
            ->assertStatus(403);

        // E. Trying to delete owner: should be Forbidden (403)
        $this->actingAsWithBrand($resellerAdmin, $brand)
            ->delete(route('users.destroy', $owner->id))
            ->assertStatus(403);
    }
}
