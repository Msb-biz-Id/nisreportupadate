<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupToR2Test extends TestCase
{
    use RefreshDatabase;

    public function test_fails_gracefully_when_r2_credentials_missing(): void
    {
        config([
            'filesystems.disks.r2.bucket' => null,
            'filesystems.disks.r2.key' => null,
            'filesystems.disks.r2.secret' => null,
        ]);

        $this->artisan('backup:r2')
            ->expectsOutputToContain('Cloudflare R2 disk is not properly configured')
            ->assertExitCode(1);
    }

    public function test_runs_successfully_when_r2_fake_disk_configured(): void
    {
        config([
            'filesystems.disks.r2.bucket' => 'my-test-bucket',
            'filesystems.disks.r2.key' => 'test-key',
            'filesystems.disks.r2.secret' => 'test-secret',
        ]);

        Storage::fake('r2');

        $this->artisan('backup:r2', ['--type' => 'daily'])
            ->expectsOutputToContain('Starting daily backup to Cloudflare R2')
            ->assertExitCode(0);

        // Verify backups files uploaded
        $files = Storage::disk('r2')->allFiles('backups/daily');
        $this->assertNotEmpty($files);
    }
}
