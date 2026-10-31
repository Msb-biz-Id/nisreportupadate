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
     * Simpan file upload ke storage publik lokal dan alirkan ke Google Drive jika aktif.
     * Mengembalikan array [ 'path', 'url', 'filename', 'original_name', 'mime_type', 'size', 'gdrive_id', 'is_gdrive' ]
     */
    public static function uploadFile(UploadedFile $file, string $subfolder): array
    {
        $extension = $file->getClientOriginalExtension();
        $safeOriginalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $fileName = Str::slug($safeOriginalName) . '-' . time() . '-' . Str::random(6) . '.' . $extension;
        
        $targetDirectory = 'hcm/' . Str::slug($subfolder);
        $storedPath = $file->storeAs($targetDirectory, $fileName, 'public');
        $publicUrl = '/storage/' . $storedPath;
        $fullLocalPath = Storage::disk('public')->path($storedPath);

        $gdriveId = null;
        $isGdrive = false;

        // Cek jika integrasi Google Drive aktif di SystemSetting
        $driveSyncEnabled = (bool) SystemSetting::get('hcm_storage', 'drive_sync_enabled', false);

        if ($driveSyncEnabled) {
            try {
                $targetFolderId = GoogleDriveClient::getOrCreateFolder($subfolder);
                
                $gdriveFile = GoogleDriveClient::uploadFile(
                    $fullLocalPath,
                    $file->getClientOriginalName(),
                    $file->getClientMimeType() ?: 'application/octet-stream',
                    $targetFolderId
                );

                if ($gdriveFile && !empty($gdriveFile['id'])) {
                    $gdriveId = $gdriveFile['id'];
                    $publicUrl = $gdriveFile['webViewLink'];
                    $isGdrive = true;

                    // Opsi Auto-Unlink: Hapus berkas lokal untuk menjaga hosting tetap 0 MB waste
                    $autoUnlink = (bool) SystemSetting::get('hcm_storage', 'auto_unlink_local', false);
                    if ($autoUnlink && Storage::disk('public')->exists($storedPath)) {
                        Storage::disk('public')->delete($storedPath);
                        $storedPath = 'gdrive:' . $gdriveId;
                    }

                    Log::info("HCM Google Drive Sync berhasil: {$file->getClientOriginalName()} -> GDrive ID: {$gdriveId} di folder {$subfolder}");
                }
            } catch (\Throwable $e) {
                Log::warning("HCM Google Drive Sync error (fallback ke lokal): " . $e->getMessage(), [
                    'file' => $file->getClientOriginalName(),
                    'subfolder' => $subfolder,
                ]);
            }
        }

        return [
            'path' => $storedPath,
            'url' => $publicUrl,
            'filename' => $fileName,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'gdrive_id' => $gdriveId,
            'is_gdrive' => $isGdrive,
        ];
    }

    /**
     * Hapus file dari Google Drive dan/atau storage lokal.
     */
    public static function deleteFile(?string $storedPathOrUrl): void
    {
        if (empty($storedPathOrUrl)) {
            return;
        }

        // 1. Cek apakah ini tautan Google Drive
        $gdriveFileId = GoogleDriveClient::extractFileIdFromUrl($storedPathOrUrl);
        if (str_starts_with($storedPathOrUrl, 'gdrive:')) {
            $gdriveFileId = str_replace('gdrive:', '', $storedPathOrUrl);
        }

        if ($gdriveFileId) {
            try {
                GoogleDriveClient::deleteFile($gdriveFileId);
                Log::info("HCM Google Drive File dihapus: ID {$gdriveFileId}");
            } catch (\Throwable $e) {
                Log::warning("Gagal menghapus file dari Google Drive: " . $e->getMessage());
            }
        }

        // 2. Cek apakah ada salinan di storage lokal
        $cleanPath = str_replace('/storage/', '', $storedPathOrUrl);
        if (!str_starts_with($cleanPath, 'http') && Storage::disk('public')->exists($cleanPath)) {
            Storage::disk('public')->delete($cleanPath);
        }
    }
}
