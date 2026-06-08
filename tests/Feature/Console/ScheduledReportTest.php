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
}
