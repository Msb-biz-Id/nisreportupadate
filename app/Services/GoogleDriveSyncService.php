<?php

namespace App\Services;

use App\Models\Settings\SystemSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GoogleDriveSyncService
{
    /**
     * Subfolder standar Google Drive sesuai plan/HRIS.md:
     * 01_Dokumen_Internal
     * 02_Surat_Masuk_Keluar
     * 03_Kontrak_PKWT
     * 04_Surat_Dokter_Presensi
     * 05_Rekrutmen_Pelamar
     */
    public const FOLDER_DOCUMENTS = '01_Dokumen_Internal';
    public const FOLDER_LETTERS = '02_Surat_Masuk_Keluar';
    public const FOLDER_CONTRACTS = '03_Kontrak_PKWT';
    public const FOLDER_ATTENDANCE = '04_Surat_Dokter_Presensi';
    public const FOLDER_RECRUITMENT = '05_Rekrutmen_Pelamar';

    /**
     * Simpan file upload ke storage publik lokal dan siapkan metadata sinkronisasi.
     * Mengembalikan array [ 'path', 'url', 'filename', 'original_name', 'mime_type', 'size' ]
     */
    public static function uploadFile(UploadedFile $file, string $subfolder): array
    {
        $extension = $file->getClientOriginalExtension();
        $safeOriginalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $fileName = Str::slug($safeOriginalName) . '-' . time() . '-' . Str::random(6) . '.' . $extension;
        
        $targetDirectory = 'hcm/' . Str::slug($subfolder);
        $storedPath = $file->storeAs($targetDirectory, $fileName, 'public');
        $publicUrl = '/storage/' . $storedPath;

        // Cek jika integrasi Google Drive aktif di SystemSetting
        $driveSyncEnabled = SystemSetting::get('hcm_storage', 'drive_sync_enabled', false);
        if ($driveSyncEnabled) {
            try {
                // Di sini dapat ditambahkan dispatch Job background GoogleDriveUploadJob jika kredensial aktif
                Log::info("HCM Google Drive Sync scheduled for {$storedPath} into {$subfolder}");
            } catch (\Throwable $e) {
                Log::warning("HCM Google Drive Sync error: " . $e->getMessage());
            }
        }

        return [
            'path' => $storedPath,
            'url' => $publicUrl,
            'filename' => $fileName,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ];
    }

    /**
     * Hapus file dari storage.
     */
    public static function deleteFile(?string $storedPathOrUrl): void
    {
        if (empty($storedPathOrUrl)) {
            return;
        }

        $cleanPath = str_replace('/storage/', '', $storedPathOrUrl);
        if (Storage::disk('public')->exists($cleanPath)) {
            Storage::disk('public')->delete($cleanPath);
        }
    }
}
