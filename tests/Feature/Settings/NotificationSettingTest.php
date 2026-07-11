<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Models\Brand;
use App\Services\Notifications\IdealNotificationService;
use App\Models\Settings\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Database\Seeders\RolePermissionSeeder;
use Tests\TestCase;

class NotificationSettingTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $adminProduksi;
    private User $adminBrand;
    private User $adminReseller;
    private User $adminKeuangan;
    private Brand $brand;
    private Brand $resellerBrand;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->seed(RolePermissionSeeder::class);

        // Create users with different roles
        $this->owner = User::factory()->create();
        $this->owner->assignRole('owner');

        $this->adminProduksi = User::factory()->create();
        $this->adminProduksi->assignRole('admin_produksi');

        $this->adminBrand = User::factory()->create();
        $this->adminBrand->assignRole('admin_brand');

        $this->adminReseller = User::factory()->create();
        $this->adminReseller->assignRole('admin_reseller');

        $this->adminKeuangan = User::factory()->create();
        $this->adminKeuangan->assignRole('admin_keuangan');

        // Create brand and assign to admin_brand
        $this->brand = Brand::factory()->create(['brand_type' => 'master']);
        $this->adminBrand->brands()->attach($this->brand->id);

        // Create reseller brand and assign to admin_reseller
        $this->resellerBrand = Brand::factory()->create([
            'brand_type' => 'reseller_hub',
            'parent_brand_id' => $this->brand->id
        ]);
        $this->resellerBrand->parentBrand()->associate($this->brand);
        $this->resellerBrand->save();
        $this->adminReseller->brands()->attach($this->resellerBrand->id);

        Notification::fake();
        Cache::flush();
    }

    public function test_notification_dispatch_with_default_settings(): void
    {
        $payload = [
            'no_po' => 'PO-001',
            'stage' => 'produksi',
            'status' => 'published',
            'brand_id' => $this->brand->id,
        ];

        $recipients = IdealNotificationService::dispatch('order_published', $payload);

        // Default settings for order_published: admin_produksi, owner
        $this->assertContains($this->owner->id, $recipients);
        $this->assertContains($this->adminProduksi->id, $recipients);
        $this->assertNotContains($this->adminBrand->id, $recipients);
        $this->assertNotContains($this->adminReseller->id, $recipients);
        $this->assertNotContains($this->adminKeuangan->id, $recipients);

        Notification::assertSentTo([$this->owner, $this->adminProduksi], \App\Notifications\SystemEventNotification::class);
    }

    public function test_notification_dispatch_with_custom_settings(): void
    {
        $customSettings = [
            'in_app' => true,
            'whatsapp' => false,
            'telegram' => false,
            'os_desktop' => true,
            'roles' => ['admin_brand', 'admin_keuangan'],
            'sound' => 'bell-chime'
        ];

        SystemSetting::set('notification_matrix', 'order_published', json_encode($customSettings));

        $payload = [
            'no_po' => 'PO-002',
            'stage' => 'produksi',
            'status' => 'published',
            'brand_id' => $this->brand->id,
        ];

        $recipients = IdealNotificationService::dispatch('order_published', $payload);

        // Only admin_brand with access to this brand and admin_keuangan (global)
        $this->assertContains($this->adminBrand->id, $recipients);
        $this->assertContains($this->adminKeuangan->id, $recipients);
        $this->assertNotContains($this->owner->id, $recipients);
        $this->assertNotContains($this->adminProduksi->id, $recipients);
        $this->assertNotContains($this->adminReseller->id, $recipients);
    }

    public function test_notification_brand_access_filtering(): void
    {
        $payload = [
            'no_po' => 'PO-003',
            'stage' => 'produksi',
            'status' => 'published',
            'brand_id' => $this->brand->id,
        ];

        // Custom settings targeting admin_brand and admin_reseller
        $customSettings = [
            'in_app' => true,
            'whatsapp' => false,
            'telegram' => false,
            'os_desktop' => true,
            'roles' => ['admin_brand', 'admin_reseller'],
            'sound' => 'bell-chime'
        ];

        SystemSetting::set('notification_matrix', 'order_published', json_encode($customSettings));

        $recipients = IdealNotificationService::dispatch('order_published', $payload);

        // admin_brand has access to the brand, admin_reseller does not (different brand)
        $this->assertContains($this->adminBrand->id, $recipients);
        $this->assertNotContains($this->adminReseller->id, $recipients);
    }

    public function test_notification_reseller_brand_access(): void
    {
        $payload = [
            'no_po' => 'PO-004',
            'stage' => 'produksi',
            'status' => 'published',
            'brand_id' => $this->resellerBrand->id,
        ];

        // Default settings for progress_updated: admin_brand, admin_reseller, owner
        $recipients = IdealNotificationService::dispatch('progress_updated', $payload);

        // admin_reseller has access to reseller brand
        $this->assertContains($this->owner->id, $recipients);
        $this->assertContains($this->adminReseller->id, $recipients);
        // admin_brand also has access because resellerBrand has parentBrand
        $this->assertContains($this->adminBrand->id, $recipients);
    }

    public function test_notification_deduplication_lock(): void
    {
        $payload = [
            'no_po' => 'PO-005',
            'stage' => 'produksi',
            'status' => 'published',
            'brand_id' => $this->brand->id,
        ];

        // First dispatch
        $recipients1 = IdealNotificationService::dispatch('order_published', $payload);
        $this->assertNotEmpty($recipients1);

        // Second dispatch within 15 seconds - should be deduplicated
        $recipients2 = IdealNotificationService::dispatch('order_published', $payload);
        $this->assertEmpty($recipients2);

        // Wait for lock to expire (simulate by clearing cache)
        Cache::flush();

        // Third dispatch after lock expired
        $recipients3 = IdealNotificationService::dispatch('order_published', $payload);
        $this->assertNotEmpty($recipients3);
    }

    public function test_notification_all_events_have_defaults(): void
    {
        $events = [
            'order_published',
            'progress_updated',
            'rijek_reported',
            'refund_submitted',
            'refund_processed',
            'payment_submitted',
            'payment_verified',
            'unlock_requested',
            'order_unlocked',
            'relock_requested',
            'order_locked',
        ];

        $payload = [
            'no_po' => 'PO-TEST',
            'stage' => 'test',
            'status' => 'test',
            'brand_id' => $this->brand->id,
        ];

        foreach ($events as $event) {
            $recipients = IdealNotificationService::dispatch($event, $payload);
            $this->assertNotEmpty($recipients, "Event {$event} should have default recipients");
        }
    }

    public function test_notification_without_brand_id(): void
    {
        $payload = [
            'no_po' => 'PO-006',
            'stage' => 'produksi',
            'status' => 'published',
            'brand_id' => null,
        ];

        $recipients = IdealNotificationService::dispatch('order_published', $payload);

        // All users with the role should receive (no brand filtering)
        $this->assertContains($this->owner->id, $recipients);
        $this->assertContains($this->adminProduksi->id, $recipients);
    }

    public function test_notification_payload_includes_settings(): void
    {
        $payload = [
            'no_po' => 'PO-007',
            'stage' => 'produksi',
            'status' => 'published',
            'brand_id' => $this->brand->id,
        ];

        IdealNotificationService::dispatch('order_published', $payload);

        // Multiple users may receive notifications (owner, admin_brand, admin_reseller)
        // Check that owner received notification
        Notification::assertSentTo($this->owner, \App\Notifications\SystemEventNotification::class);
    }

    public function test_refund_events_target_correct_roles(): void
    {
        $payload = [
            'no_po' => 'PO-008',
            'stage' => 'refund',
            'status' => 'submitted',
            'brand_id' => $this->brand->id,
        ];

        // refund_submitted targets admin_keuangan, owner
        $recipients = IdealNotificationService::dispatch('refund_submitted', $payload);
        $this->assertContains($this->owner->id, $recipients);
        $this->assertContains($this->adminKeuangan->id, $recipients);
        $this->assertNotContains($this->adminBrand->id, $recipients);

        // refund_processed targets admin_brand, admin_reseller, owner
        $payload['status'] = 'processed';
        $recipients = IdealNotificationService::dispatch('refund_processed', $payload);
        $this->assertContains($this->owner->id, $recipients);
        $this->assertContains($this->adminBrand->id, $recipients);
        $this->assertNotContains($this->adminKeuangan->id, $recipients);
    }

    public function test_payment_events_target_correct_roles(): void
    {
        $payload = [
            'no_po' => 'PO-009',
            'stage' => 'payment',
            'status' => 'submitted',
            'brand_id' => $this->brand->id,
        ];

        // payment_submitted targets admin_keuangan, owner
        $recipients = IdealNotificationService::dispatch('payment_submitted', $payload);
        $this->assertContains($this->owner->id, $recipients);
        $this->assertContains($this->adminKeuangan->id, $recipients);

        // payment_verified targets admin_brand, owner
        $payload['status'] = 'verified';
        $recipients = IdealNotificationService::dispatch('payment_verified', $payload);
        $this->assertContains($this->owner->id, $recipients);
        $this->assertContains($this->adminBrand->id, $recipients);
        $this->assertNotContains($this->adminKeuangan->id, $recipients);
    }

    public function test_rijek_reported_targets_correct_roles(): void
    {
        $payload = [
            'no_po' => 'PO-010',
            'stage' => 'produksi',
            'status' => 'rejek',
            'brand_id' => $this->brand->id,
        ];

        // rijek_reported targets admin_brand, owner
        $recipients = IdealNotificationService::dispatch('rijek_reported', $payload);
        $this->assertContains($this->owner->id, $recipients);
        $this->assertContains($this->adminBrand->id, $recipients);
        $this->assertNotContains($this->adminProduksi->id, $recipients);
        $this->assertNotContains($this->adminKeuangan->id, $recipients);
    }
}
