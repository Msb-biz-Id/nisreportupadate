<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HcmFileStorageService
{
    /**
     * Subfolder standar penyimpanan dokumen HCM:
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
     * Simpan berkas unggahan ke storage publik lokal.
     * Mengembalikan array [ 'path', 'url', 'filename', 'original_name', 'mime_type', 'size' ].
     */
    public static function uploadFile(UploadedFile $file, string $subfolder): array
    {
        $extension = $file->getClientOriginalExtension();
        $safeOriginalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $fileName = Str::slug($safeOriginalName) . '-' . time() . '-' . Str::random(6) . '.' . $extension;

        $targetDirectory = 'hcm/' . Str::slug($subfolder);
        $storedPath = $file->storeAs($targetDirectory, $fileName, 'public');

        return [
            'path' => $storedPath,
            'url' => '/storage/' . $storedPath,
            'filename' => $fileName,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ];
    }

    /**
     * Hapus berkas dari storage lokal.
     */
    public static function deleteFile(?string $storedPathOrUrl): void
    {
        if (empty($storedPathOrUrl)) {
            return;
        }

        $cleanPath = str_replace('/storage/', '', $storedPathOrUrl);

        if (! str_starts_with($cleanPath, 'http') && Storage::disk('public')->exists($cleanPath)) {
            Storage::disk('public')->delete($cleanPath);
        }
    }
}
