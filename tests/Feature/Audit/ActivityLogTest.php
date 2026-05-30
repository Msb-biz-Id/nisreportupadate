<?php

namespace Tests\Feature\Audit;

use App\Models\ActivityLog;
use App\Models\Master\Customer;
use App\Models\Order\Order;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_creates_activity_log(): void
    {
        $user = $this->makeUser('admin_brand', [$this->makeBrand()]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'activity' => 'login',
            'module' => 'auth',
        ]);
    }

    public function test_order_publish_creates_activity_log(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('admin_brand', [$brand]);
        Customer::create(['brand_id' => $brand->id, 'kode' => 'C', 'nama' => 'T', 'nomor_hp' => '081', 'is_active' => true]);

        $order = Order::create([
            'brand_id' => $brand->id, 'no_po' => 'PO-LOG-001',
            'nama_po' => 'X', 'status_po' => 'draft',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => Customer::first()->id,
            'total_tagihan' => 100000, 'created_by' => $user->id,
        ]);
        \App\Models\Order\OrderItem::create([
            'order_id' => $order->id, 'nama_produk' => 'X',
            'quantity' => 1, 'harga_satuan' => 100000, 'subtotal' => 100000,
        ]);
        // Init at least one progress so publish doesn't fail
        \App\Models\Master\Progress::create(['nama_progress' => 'Test', 'urutan' => 1, 'is_active' => true, 'warna' => '#3B82F6', 'is_skippable' => false]);

        // Create verified DP payment (50,000, which is 50% DP)
        \App\Models\Order\OrderPayment::create([
            'order_id' => $order->id,
            'payment_type' => 'dp',
            'amount' => 50000,
            'payment_date' => now()->toDateString(),
            'recorded_by' => $user->id,
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);

        // Create validated invoice
        \App\Models\Order\Invoice::create([
            'brand_id' => $brand->id,
            'order_id' => $order->id,
            'invoice_number' => 'INV-TEST-002',
            'tanggal_terbit' => now()->toDateString(),
            'jatuh_tempo' => now()->addDays(7)->toDateString(),
            'status' => 'validated',
            'total_tagihan' => 100000,
            'total_bayar' => 50000,
            'dp_amount' => 50000,
            'sisa_pembayaran' => 50000,
            'created_by' => $user->id,
        ]);

        $this->actingAsWithBrand($user, $brand)
            ->post(route('orders.publish', $order->id))
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'activity' => 'publish',
            'module' => 'order',
            'subject_id' => $order->id,
        ]);
    }

    public function test_logger_captures_user_ip_and_brand(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('admin_brand', [$brand]);

        $this->actingAsWithBrand($user, $brand)
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])
            ->get(route('dashboard'));

        // Trigger log manual via service
        ActivityLogger::log('test_action', 'test_module', null, 'Test description');

        $log = ActivityLog::latest()->first();
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals($brand->id, $log->brand_id);
        $this->assertEquals('test_action', $log->activity);
    }

    public function test_audit_index_requires_audit_view_permission(): void
    {
        $reseller = $this->makeUser('reseller', [$this->makeBrand()]);
        $this->actingAs($reseller)->get(route('audit.index'))->assertForbidden();
    }

    public function test_superadmin_can_view_audit_index(): void
    {
        $sa = $this->makeUser('superadmin');
        ActivityLog::create([
            'user_id' => $sa->id, 'activity' => 'create',
            'module' => 'brand', 'description' => 'Test log',
        ]);

        $this->actingAs($sa)
            ->get(route('audit.index'))
            ->assertOk();
    }

    public function test_owner_can_view_audit_log(): void
    {
        $brand = $this->makeBrand();
        $owner = $this->makeUser('owner', [$brand]);

        $this->actingAsWithBrand($owner, $brand)
            ->get(route('audit.index'))
            ->assertOk();
    }

    public function test_audit_filter_by_module(): void
    {
        $sa = $this->makeUser('superadmin');
        ActivityLog::create(['user_id' => $sa->id, 'activity' => 'create', 'module' => 'brand', 'description' => 'B1']);
        ActivityLog::create(['user_id' => $sa->id, 'activity' => 'create', 'module' => 'user', 'description' => 'U1']);

        $response = $this->actingAs($sa)->get(route('audit.index', ['module' => 'brand']));
        $response->assertOk();
        $logs = $response->viewData('page')['props']['logs']['data'];
        $this->assertCount(1, $logs);
        $this->assertEquals('brand', $logs[0]['module']);
    }
}
