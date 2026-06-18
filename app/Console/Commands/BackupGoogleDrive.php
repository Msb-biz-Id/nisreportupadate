<?php

namespace App\Console\Commands;

use App\Services\GoogleDriveService;
use App\Models\Settings\SystemSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupGoogleDrive extends Command
{
    /**
     * Nama command Artisan.
     */
    protected $signature = 'backup:gdrive';

    /**
     * Deskripsi command.
     */
    protected $description = 'Melakukan backup otomatis database SQLite dan file aset foto orders ke Google Drive';

    /**
     * Eksekusi command.
     */
    public function handle()
    {
        $this->info('Memulai proses backup ke Google Drive...');
        
        $isEnabled = SystemSetting::get('gdrive', 'is_enabled', '0');
        if ($isEnabled !== '1') {
            $this->warn('Integrasi Google Drive dalam keadaan dinonaktifkan di pengaturan.');
            return self::FAILURE;
        }

        $tempDir = storage_path('app/backups');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $timestamp = date('Ymd-His');
        
        // ----------------------------------------------------
        // Bagian A: Backup Database SQLite
        // ----------------------------------------------------
        $dbPath = database_path('database.sqlite');
        if (file_exists($dbPath)) {
            $this->info('Membackup database SQLite...');
            $dbBackupFileName = "backup-db-nisreport-{$timestamp}.sqlite";
            $dbBackupFilePath = "{$tempDir}/{$dbBackupFileName}";
            
            // Lakukan salin file database (SQLite aman disalin langsung saat tidak ada penulisan berat)
            if (@copy($dbPath, $dbBackupFilePath)) {
                try {
                    GoogleDriveService::uploadFile($dbBackupFilePath, $dbBackupFileName, 'application/x-sqlite3');
                    $this->info("Database berhasil diunggah ke Google Drive.");
                    @unlink($dbBackupFilePath); // Bersihkan file temp lokal
                } catch (\Throwable $e) {
                    $this->error("Gagal mengunggah database ke GDrive: " . $e->getMessage());
                    Log::error("Backup GDrive Database Failed: " . $e->getMessage());
                    @unlink($dbBackupFilePath);
                }
            } else {
                $this->error('Gagal menyalin berkas database SQLite.');
            }
        } else {
            $this->warn('File database SQLite tidak ditemukan di: ' . $dbPath);
        }

        // ----------------------------------------------------
        // Bagian B: Backup Aset Foto Orders
        // ----------------------------------------------------
        $publicDisk = Storage::disk('public');
        $ordersPath = $publicDisk->path('orders');

        if (is_dir($ordersPath) && count(scandir($ordersPath)) > 2) {
            $this->info('Mengompres berkas foto orders ke ZIP...');
            $zipFileName = "backup-foto-orders-{$timestamp}.zip";
            $zipFilePath = "{$tempDir}/{$zipFileName}";

            $zip = new \ZipArchive();
            if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($ordersPath, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );

                $addedFiles = 0;
                foreach ($files as $name => $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = 'orders/' . substr($filePath, strlen($ordersPath) + 1);
                        $zip->addFile($filePath, $relativePath);
                        $addedFiles++;
                    }
                }
                $zip->close();

                if ($addedFiles > 0) {
                    $this->info("Mengunggah arsip foto ({$addedFiles} file) ke Google Drive...");
                    try {
                        GoogleDriveService::uploadFile($zipFilePath, $zipFileName, 'application/zip');
                        $this->info("Arsip foto berhasil diunggah ke Google Drive.");
                    } catch (\Throwable $e) {
                        $this->error("Gagal mengunggah arsip foto ke GDrive: " . $e->getMessage());
                        Log::error("Backup GDrive Assets Failed: " . $e->getMessage());
                    }
                } else {
                    $this->warn('Arsip ZIP kosong, tidak ada berkas ditambahkan.');
                }
                @unlink($zipFilePath); // Bersihkan file temp lokal
            } else {
                $this->error('Gagal membuat berkas arsip ZIP foto.');
            }
        } else {
            $this->warn('Direktori foto orders kosong atau tidak ditemukan.');
        }

        $this->info('Proses backup selesai.');
        return self::SUCCESS;
    }
}
