<?php

namespace Tests\Feature\Settings;

use App\Models\Settings\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_setting_round_trip_unencrypted(): void
    {
        SystemSetting::set('ai', 'model', 'gemini-1.5-flash');
        $this->assertEquals('gemini-1.5-flash', SystemSetting::get('ai', 'model'));
    }

    public function test_setting_round_trip_encrypted(): void
    {
        SystemSetting::set('whatsapp', 'api_key', 'super-secret-key-xyz', encrypted: true);
        $stored = SystemSetting::where('group', 'whatsapp')->where('key', 'api_key')->first();

        // Stored value harus ter-encrypt (bukan plain)
        $this->assertNotEquals('super-secret-key-xyz', $stored->value);
        // Tapi accessor decrypt-nya
        $this->assertEquals('super-secret-key-xyz', SystemSetting::get('whatsapp', 'api_key'));
    }

    public function test_masking_helper(): void
    {
        $this->assertEquals('AIza' . str_repeat('•', 16) . 'OXYZ', SystemSetting::maskedValue('AIzaSyABCDEFGHIJKLMNOXYZ'));
        $this->assertEquals('••••', SystemSetting::maskedValue('abcd'));
        $this->assertNull(SystemSetting::maskedValue(null));
    }

    public function test_superadmin_can_view_integration_settings(): void
    {
        $sa = $this->makeUser('superadmin', [$this->makeBrand()]);
        $this->actingAs($sa)->get(route('settings.integrasi'))->assertOk();
    }

    public function test_admin_brand_cannot_view_integration_settings(): void
    {
        $user = $this->makeUser('admin_brand', [$this->makeBrand()]);
        $this->actingAs($user)->get(route('settings.integrasi'))->assertForbidden();
    }

    public function test_superadmin_can_save_ai_settings(): void
    {
        $sa = $this->makeUser('superadmin', [$this->makeBrand()]);

        $this->actingAs($sa)
            ->put(route('settings.integrasi.ai'), [
                'model' => 'gemini-1.5-pro',
                'temperature' => 0.9,
                'max_tokens' => 4096,
                'gemini_api_keys' => 'AIzaTestKey,AIzaSecondKey',
            ])
            ->assertRedirect();

        $this->assertEquals('gemini-1.5-pro', SystemSetting::get('ai', 'model'));
        $this->assertEquals('AIzaTestKey,AIzaSecondKey', SystemSetting::get('ai', 'gemini_api_keys'));
    }

    public function test_superadmin_can_save_system_settings_with_theme_color(): void
    {
        $sa = $this->makeUser('superadmin', [$this->makeBrand()]);

        $this->actingAs($sa)
            ->put(route('settings.integrasi.system'), [
                'notification_channel' => 'whatsapp',
                'whatsapp_enabled' => true,
                'telegram_enabled' => false,
                'customer_import_enabled' => true,
                'theme_color' => '#ff0055',
                'target_view' => 'both',
            ])
            ->assertRedirect();

        $this->assertEquals('#ff0055', SystemSetting::get('system', 'theme_color'));
    }
}
