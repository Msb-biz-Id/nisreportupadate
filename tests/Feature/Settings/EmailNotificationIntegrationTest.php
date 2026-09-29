<?php

namespace Tests\Feature\Settings;

use App\Models\Settings\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReportMail;
use Tests\TestCase;

class EmailNotificationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_system_settings_with_email_enabled_and_all_channels(): void
    {
        $superadmin = $this->makeUser('superadmin');

        $this->actingAs($superadmin)
            ->put(route('settings.integrasi.system'), [
                'notification_channel' => 'all',
                'whatsapp_enabled' => true,
                'telegram_enabled' => true,
                'email_enabled' => true,
                'customer_import_enabled' => false,
                'theme_color' => '#a8001c',
                'target_view' => 'pcs',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals('all', SystemSetting::get('system', 'notification_channel'));
        $this->assertEquals('1', SystemSetting::get('system', 'email_enabled'));
    }

    public function test_can_trigger_test_email(): void
    {
        Mail::fake();

        $superadmin = $this->makeUser('superadmin');

        $this->actingAs($superadmin)
            ->post(route('settings.integrasi.test.mail'), [
                'to' => 'test@example.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_scheduled_report_dispatches_email_when_email_channel_active(): void
    {
        Mail::fake();

        SystemSetting::set('system', 'notification_channel', 'email');
        SystemSetting::set('system', 'email_enabled', '1');
        SystemSetting::set('reports', 'enable_auto_report', '1');
        SystemSetting::set('reports', 'report_types', 'superadmin');
        SystemSetting::set('reports', 'superadmin_recipients', 'admin@example.com');

        $this->artisan('reports:send', ['periode' => 'harian', '--force' => true])
            ->assertExitCode(0);

        Mail::assertSent(ReportMail::class, function ($mail) {
            return $mail->hasTo('admin@example.com');
        });
    }
}
