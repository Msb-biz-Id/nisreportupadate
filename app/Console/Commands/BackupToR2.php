<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BackupToR2 extends Command
{
    protected $signature = 'backup:r2
                            {--type=daily : Backup type: daily, monthly, yearly}
                            {--retention-daily=30 : Days to keep daily backups}
                            {--retention-monthly=12 : Months to keep monthly backups}
                            {--retention-yearly=5 : Years to keep yearly backups}';

    protected $description = 'Backup database dan storage ke Cloudflare R2';

    public function handle(): int
    {
        if (! config('filesystems.disks.r2')) {
            $this->error('Cloudflare R2 disk not configured. Set R2_* env variables.');
            return self::FAILURE;
        }

        $type = $this->option('type');
        $now  = Carbon::now();

        $this->info("🚀 Starting {$type} backup to Cloudflare R2...");

        // --- 1. Database Dump ---
        $dbDumpPath = $this->dumpDatabase($now);
        if (! $dbDumpPath) {
            $this->error('Database dump failed.');
            return self::FAILURE;
        }

        // --- 2. Upload DB dump to R2 ---
        $prefix   = "backups/{$type}/{$now->format('Y-m-d_H-i-s')}";
        $r2DbKey  = "{$prefix}/database.sql.gz";

        Storage::disk('r2')->put($r2DbKey, gzencode(file_get_contents($dbDumpPath)));
        @unlink($dbDumpPath);
        $this->line("  ✓ Database uploaded → {$r2DbKey}");

        // --- 3. Upload public storage files (e.g. refund bukti) ---
        $storageFiles = $this->collectStorageFiles();
        $uploadCount  = 0;
        foreach ($storageFiles as $relativePath => $fullPath) {
            $r2Key = "{$prefix}/storage/{$relativePath}";
            Storage::disk('r2')->put($r2Key, file_get_contents($fullPath));
            $uploadCount++;
        }
        $this->line("  ✓ Storage files uploaded: {$uploadCount} files");

        // --- 4. Write manifest ---
        $manifest = json_encode([
            'type'       => $type,
            'timestamp'  => $now->toIso8601String(),
            'app_url'    => config('app.url'),
            'app_name'   => config('app.name'),
            'db_files'   => [$r2DbKey],
            'storage_files_count' => $uploadCount,
        ], JSON_PRETTY_PRINT);
        Storage::disk('r2')->put("{$prefix}/manifest.json", $manifest);

        // --- 5. Cleanup old backups ---
        $this->cleanOldBackups($type, $now);

        $this->info("✅ Backup selesai: {$prefix}");
        return self::SUCCESS;
    }

    private function dumpDatabase(Carbon $now): ?string
    {
        $tmpFile = storage_path("app/tmp_backup_{$now->timestamp}.sql");
        $db = config('database.default');

        if ($db === 'sqlite') {
            $dbPath = config('database.connections.sqlite.database');
            if (! copy($dbPath, $tmpFile)) {
                return null;
            }
        } elseif ($db === 'mysql') {
            $c    = config('database.connections.mysql');
            $host = escapeshellarg($c['host']);
            $port = escapeshellarg($c['port'] ?? '3306');
            $user = escapeshellarg($c['username']);
            $pass = $c['password'] ? "-p" . escapeshellarg($c['password']) : '';
            $name = escapeshellarg($c['database']);

            exec("mysqldump -h{$host} -P{$port} -u{$user} {$pass} {$name} > " . escapeshellarg($tmpFile) . " 2>&1", $out, $code);
            if ($code !== 0) {
                $this->warn('mysqldump output: ' . implode(' ', $out));
                return null;
            }
        } else {
            $this->warn("Unsupported DB driver '{$db}' for dump. Skipping DB backup.");
            file_put_contents($tmpFile, "# DB driver '{$db}' not supported for automated dump.");
        }

        return $tmpFile;
    }

    private function collectStorageFiles(): array
    {
        $basePath = storage_path('app/public');
        if (! is_dir($basePath)) return [];

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relative = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $files[$relative] = $file->getPathname();
            }
        }
        return $files;
    }

    private function cleanOldBackups(string $type, Carbon $now): void
    {
        $retentionMap = [
            'daily'   => ['option' => 'retention-daily',   'unit' => 'days'],
            'monthly' => ['option' => 'retention-monthly',  'unit' => 'months'],
            'yearly'  => ['option' => 'retention-yearly',   'unit' => 'years'],
        ];

        if (! isset($retentionMap[$type])) return;

        $retentionOption = $retentionMap[$type]['option'];
        $retentionUnit   = $retentionMap[$type]['unit'];
        $retentionValue  = (int) $this->option($retentionOption);
        $cutoff          = $now->copy()->sub($retentionUnit, $retentionValue);

        $directories = Storage::disk('r2')->directories("backups/{$type}");
        $deleted = 0;

        foreach ($directories as $dir) {
            // Directory name format: 2024-01-15_02-00-00
            $datePart = basename($dir);
            try {
                $dirDate = Carbon::createFromFormat('Y-m-d_H-i-s', $datePart);
                if ($dirDate->lt($cutoff)) {
                    $files = Storage::disk('r2')->allFiles($dir);
                    foreach ($files as $file) {
                        Storage::disk('r2')->delete($file);
                    }
                    $deleted++;
                }
            } catch (\Exception $e) {
                // Skip dirs with unexpected naming
            }
        }

        if ($deleted > 0) {
            $this->line("  🗑  Cleaned {$deleted} old {$type} backup(s)");
        }
    }
}
