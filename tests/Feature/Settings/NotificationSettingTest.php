<?php

namespace Tests\Feature\Settings;

use App\Models\Settings\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_view_notification_settings(): void
    {
        $sa = $this->makeUser('superadmin', [$this->makeBrand()]);
        $this->actingAs($sa)->get(route('settings.notifikasi'))->assertOk();
    }

    public function test_admin_brand_cannot_view_notification_settings(): void
    {
        $user = $this->makeUser('admin_brand', [$this->makeBrand()]);
        $this->actingAs($user)->get(route('settings.notifikasi'))->assertForbidden();
    }

    public function test_superadmin_can_save_notification_matrix(): void
    {
        $sa = $this->makeUser('superadmin', [$this->makeBrand()]);

        $matrixPayload = [
            'matrix' => [
                'order_published' => [
                    'in_app' => true,
                    'whatsapp' => true,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['owner', 'admin_brand'],
                    'sound' => 'success-tada'
                ],
                'progress_updated' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => false,
                    'roles' => ['owner'],
                    'sound' => 'bell-chime'
                ]
            ]
        ];

        $this->actingAs($sa)
            ->put(route('settings.integrasi.matrix'), $matrixPayload)
            ->assertRedirect();

        $savedOrder = SystemSetting::get('notification_matrix', 'order_published');
        $this->assertNotNull($savedOrder);
        $decoded = json_decode($savedOrder, true);
        $this->assertIsArray($decoded);
        $this->assertTrue($decoded['whatsapp']);
        $this->assertFalse($decoded['telegram']);
        $this->assertEquals('success-tada', $decoded['sound']);
        $this->assertEquals(['owner', 'admin_brand'], $decoded['roles']);
    }

    public function test_dynamic_notification_dispatches_according_to_matrix(): void
    {
        $owner = $this->makeUser('owner', [$this->makeBrand()]);

        // Seed notification_matrix setting first
        SystemSetting::set('notification_matrix', 'order_published', json_encode([
            'in_app' => true,
            'whatsapp' => false,
            'telegram' => false,
            'os_desktop' => true,
            'roles' => ['owner'],
            'sound' => 'success-tada'
        ]));

        \App\Services\Notifications\DynamicNotificationService::dispatch('order_published', [
            'no_po' => 'PO-TEST-123',
            'brand_nama' => 'Circle Sportwear',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $owner->id,
            'title' => 'PO Baru Diterbitkan',
            'body' => 'PO PO-TEST-123 telah diterbitkan oleh Admin Brand untuk brand Circle Sportwear. Siap dikerjakan.',
        ]);
    }
}
