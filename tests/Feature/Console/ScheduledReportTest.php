<?php

namespace Tests\Feature\Console;

use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduledReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_runs_without_brands(): void
    {
        $this->artisan('reports:send', ['periode' => 'harian'])
            ->assertExitCode(0);
    }

    public function test_command_runs_with_brand(): void
    {
        $this->makeBrand();

        // Command sekarang cek enable_auto_report dulu (default false), langsung keluar dengan pesan warn
        $this->artisan('reports:send', ['periode' => 'harian'])
            ->expectsOutputToContain('Laporan otomatis tidak aktif')
            ->assertExitCode(0);
    }

    public function test_command_force_runs_with_brand(): void
    {
        $brand = $this->makeBrand(['kode' => 'TST']);

        $this->artisan('reports:send', ['periode' => 'harian', '--force' => true])
            ->expectsOutputToContain($brand->kode)
            ->assertExitCode(0);
    }

    public function test_command_accepts_brand_filter(): void
    {
        $b1 = $this->makeBrand(['kode' => 'B1']);
        $b2 = $this->makeBrand(['kode' => 'B2']);

        $this->artisan('reports:send', ['periode' => 'mingguan', '--brand' => $b1->id])
            ->assertExitCode(0);
    }

    public function test_command_skips_brands_with_no_activity_for_all_periods(): void
    {
        \App\Models\Settings\SystemSetting::set('reports', 'enable_auto_report', '1');

        // Brand 1: No activity at all
        $brandNoActivity = $this->makeBrand(['kode' => 'NOACT']);

        // Brand 2: Has active order (should not be skipped)
        $brandWithActive = $this->makeBrand(['kode' => 'WITHACT']);
        $user = $this->makeUser('superadmin');
        
        $customer = \App\Models\Master\Customer::create([
            'brand_id' => $brandWithActive->id,
            'kode' => 'CUST01',
            'nama' => 'Test Customer',
            'nomor_hp' => '08123456789',
            'is_active' => true
        ]);

        \App\Models\Order\Order::create([
            'brand_id' => $brandWithActive->id,
            'no_po' => 'PO-ACT-001',
            'nama_po' => 'Active PO',
            'status_po' => 'produksi', // active status
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(5)->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 500000,
            'created_by' => $user->id,
        ]);

        // Run for daily (harian)
        $this->artisan('reports:send', ['periode' => 'harian'])
            ->expectsOutputToContain('NOACT (Dilewati - Tidak ada order aktif atau aktivitas)')
            ->expectsOutputToContain('Brand: WITHACT')
            ->assertExitCode(0);

        // Run for weekly (mingguan)
        $this->artisan('reports:send', ['periode' => 'mingguan'])
            ->expectsOutputToContain('NOACT (Dilewati - Tidak ada order aktif atau aktivitas)')
            ->expectsOutputToContain('Brand: WITHACT')
            ->assertExitCode(0);

        // Run for monthly (bulanan)
        $this->artisan('reports:send', ['periode' => 'bulanan'])
            ->expectsOutputToContain('NOACT (Dilewati - Tidak ada order aktif atau aktivitas)')
            ->expectsOutputToContain('Brand: WITHACT')
            ->assertExitCode(0);
    }

    public function test_command_does_not_skip_completed_orders_with_recent_updates(): void
    {
        \App\Models\Settings\SystemSetting::set('reports', 'enable_auto_report', '1');

        $brand = $this->makeBrand(['kode' => 'RECENT']);
        $user = $this->makeUser('superadmin');
        
        $customer = \App\Models\Master\Customer::create([
            'brand_id' => $brand->id,
            'kode' => 'CUST02',
            'nama' => 'Test Customer 2',
            'nomor_hp' => '08123456789',
            'is_active' => true
        ]);

        $order = \App\Models\Order\Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-COMP-001',
            'nama_po' => 'Completed PO',
            'status_po' => 'selesai', // completed status
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(5)->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 500000,
            'created_by' => $user->id,
        ]);

        $order->updated_at = now();
        $order->save();

        $this->artisan('reports:send', ['periode' => 'harian'])
            ->expectsOutputToContain('Brand: RECENT')
            ->assertExitCode(0);
    }
}
