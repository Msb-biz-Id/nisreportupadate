<?php

namespace Tests\Feature\Settings;

use App\Models\Brand;
use App\Models\Settings\SystemSetting;
use App\Models\User;
use App\Mail\ReportMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ScheduledReportDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduled_report_dispatches_to_correct_roles_and_brands(): void
    {
        Mail::fake();

        // System settings for email dispatch
        SystemSetting::set('system', 'notification_channel', 'email');
        SystemSetting::set('system', 'email_enabled', '1');
        SystemSetting::set('reports', 'enable_auto_report', '1');
        SystemSetting::set('reports', 'report_types', 'superadmin,brand,produksi,owner,keuangan');
        \Illuminate\Support\Facades\Cache::flush();

        // Create Users and Brands with role helper
        $superadmin = $this->makeUser('superadmin', [], ['email' => 'super@example.com']);

        $brandAlg = $this->makeBrand(['kode' => 'ALG', 'nama_brand' => 'Allegiant', 'created_by' => $superadmin->id]);
        $brandIdw = $this->makeBrand(['kode' => 'IDW', 'nama_brand' => 'Indowarehouse', 'created_by' => $superadmin->id]);

        $brandAdminAlg = $this->makeUser('admin_brand', [$brandAlg], ['email' => 'brand_alg@example.com']);
        $resellerAdminIdw = $this->makeUser('admin_reseller', [$brandIdw], ['email' => 'reseller_idw@example.com']);
        $ownerUser = $this->makeUser('owner', [$brandAlg], ['email' => 'owner@example.com']);

        // Run artisan reports:send with --force
        \Illuminate\Support\Facades\Artisan::call('reports:send', ['periode' => 'harian', '--force' => true]);

        // Verify Superadmin email received Superadmin report
        Mail::assertSent(ReportMail::class, function ($mail) {
            return $mail->hasTo('super@example.com') && str_contains($mail->subjectTitle, 'Superadmin');
        });

        // Verify Brand Admin ALG received Brand report
        Mail::assertSent(ReportMail::class, function ($mail) {
            return $mail->hasTo('brand_alg@example.com') && str_contains($mail->subjectTitle, 'ALG');
        });

        // Verify Reseller Admin IDW received Brand report
        Mail::assertSent(ReportMail::class, function ($mail) {
            return $mail->hasTo('reseller_idw@example.com') && str_contains($mail->subjectTitle, 'IDW');
        });

        // Verify Owner received Owner report
        Mail::assertSent(ReportMail::class, function ($mail) {
            return $mail->hasTo('owner@example.com') && str_contains($mail->subjectTitle, 'Executive');
        });

        // Verify Superadmin did NOT receive per-brand admin report directly
        Mail::assertNotSent(ReportMail::class, function ($mail) {
            return $mail->hasTo('super@example.com') && str_contains($mail->subjectTitle, 'Laporan Brand harian — ALG');
        });
    }
}
