<?php

namespace App\Services;

use App\Models\Settings\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleDriveClient
{
    private const TOKEN_URI = 'https://oauth2.googleapis.com/token';
    private const DRIVE_API_BASE = 'https://www.googleapis.com/drive/v3';
    private const UPLOAD_API_BASE = 'https://www.googleapis.com/upload/drive/v3';
    private const SCOPE = 'https://www.googleapis.com/auth/drive';

    /**
     * Dapatkan kredensial Service Account dari SystemSetting.
     * Mengembalikan array jika valid, atau null jika belum dikonfigurasi.
     */
    public static function getCredentials(): ?array
    {
        $raw = SystemSetting::get('hcm_storage', 'service_account_json');
        if (empty($raw)) {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key'])) {
            return null;
        }

        return $decoded;
    }

    /**
     * Dapatkan Root Folder ID dari SystemSetting.
     */
    public static function getRootFolderId(): ?string
    {
        return SystemSetting::get('hcm_storage', 'root_folder_id') ?: null;
    }

    /**
     * Dapatkan Access Token Google OAuth2 menggunakan JWT Service Account.
     * Token di-cache selama 50 menit.
     */
    public static function getAccessToken(): ?string
    {
        $credentials = self::getCredentials();
        if (!$credentials) {
            return null;
        }

        $cacheKey = 'hcm_gdrive_access_token_' . md5($credentials['client_email']);
        
        return Cache::remember($cacheKey, 3000, function () use ($credentials) {
            $now = time();
            $header = ['alg' => 'RS256', 'typ' => 'JWT'];
            $claim = [
                'iss' => $credentials['client_email'],
                'scope' => self::SCOPE,
                'aud' => self::TOKEN_URI,
                'exp' => $now + 3600,
                'iat' => $now,
            ];

            $base64Header = self::base64UrlEncode(json_encode($header));
            $base64Claim = self::base64UrlEncode(json_encode($claim));
            $signatureInput = $base64Header . '.' . $base64Claim;

            $privateKey = $credentials['private_key'];
            $signature = '';
            $binarySignature = '';

            $signed = openssl_sign($signatureInput, $binarySignature, $privateKey, OPENSSL_ALGO_SHA256);
            if (!$signed) {
                Log::error('HCM Google Drive: Gagal menandatangani JWT Service Account. Pastikan private_key valid.');
                return null;
            }

            $jwt = $signatureInput . '.' . self::base64UrlEncode($binarySignature);

            $response = Http::asForm()->post(self::TOKEN_URI, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['access_token'] ?? null;
            }

            Log::error('HCM Google Drive: Gagal mendapatkan access token dari Google OAuth', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        });
    }

    /**
     * Uji koneksi ke Google Drive API.
     * Mengembalikan array info akun, kuota, dan validitas folder root.
     */
    public static function testConnection(): array
    {
        $credentials = self::getCredentials();
        if (!$credentials) {
            return [
                'success' => false,
                'message' => 'Kredensial Service Account belum dikonfigurasi.',
            ];
        }

        // Hapus cache token untuk tes langsung
        Cache::forget('hcm_gdrive_access_token_' . md5($credentials['client_email']));
        $token = self::getAccessToken();

        if (!$token) {
            return [
                'success' => false,
                'message' => 'Gagal mengautentikasi Service Account ke Google. Periksa private_key dan client_email.',
            ];
        }

        // 1. Cek info akun dan kuota drive
        $aboutResponse = Http::withToken($token)->get(self::DRIVE_API_BASE . '/about', [
            'fields' => 'user,storageQuota',
        ]);

        if (!$aboutResponse->successful()) {
            return [
                'success' => false,
                'message' => 'Koneksi ke Google Drive API gagal: ' . ($aboutResponse->json('error.message') ?? $aboutResponse->body()),
            ];
        }

        $aboutData = $aboutResponse->json();
        $rootFolderId = self::getRootFolderId();
        $rootFolderInfo = null;

        // 2. Jika root folder dikonfigurasi, validasi akses folder
        if ($rootFolderId) {
            $folderResponse = Http::withToken($token)->get(self::DRIVE_API_BASE . "/files/{$rootFolderId}", [
                'fields' => 'id,name,capabilities,trashed',
                'supportsAllDrives' => 'true',
            ]);

            if ($folderResponse->successful()) {
                $rootFolderInfo = $folderResponse->json();
                if ($rootFolderInfo['trashed'] ?? false) {
                    return [
                        'success' => false,
                        'message' => 'Root Folder ID ditemukan namun berada di folder Sampah (Trash) Google Drive.',
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'Root Folder ID tidak ditemukan atau Service Account belum diberikan akses editor ke folder tersebut.',
                    'root_folder_id' => $rootFolderId,
                    'service_account_email' => $credentials['client_email'],
                ];
            }
        }

        return [
            'success' => true,
            'message' => 'Koneksi Google Drive berhasil terverifikasi.',
            'service_account_email' => $credentials['client_email'],
            'project_id' => $credentials['project_id'] ?? null,
            'root_folder_id' => $rootFolderId,
            'root_folder_name' => $rootFolderInfo['name'] ?? '(Default My Drive / Root)',
            'storage_quota' => $aboutData['storageQuota'] ?? [],
        ];
    }

    /**
     * Cari atau buat subfolder terstruktur di dalam root folder.
     */
    public static function getOrCreateFolder(string $folderName, ?string $parentFolderId = null): ?string
    {
        $token = self::getAccessToken();
        if (!$token) {
            return null;
        }

        $parent = $parentFolderId ?: self::getRootFolderId();
        $cacheKey = 'hcm_gdrive_folder_' . md5($folderName . '_' . ($parent ?: 'root'));

        return Cache::remember($cacheKey, 86400, function () use ($token, $folderName, $parent) {
            // Cari apakah folder sudah ada
            $query = "name = '" . str_replace("'", "\\'", $folderName) . "' and mimeType = 'application/vnd.google-apps.folder' and trashed = false";
            if ($parent) {
                $query .= " and '{$parent}' in parents";
            }

            $searchResponse = Http::withToken($token)->get(self::DRIVE_API_BASE . '/files', [
                'q' => $query,
                'fields' => 'files(id, name)',
                'supportsAllDrives' => 'true',
                'includeItemsFromAllDrives' => 'true',
            ]);

            if ($searchResponse->successful()) {
                $files = $searchResponse->json('files');
                if (!empty($files) && isset($files[0]['id'])) {
                    return $files[0]['id'];
                }
            }

            // Jika belum ada, buat folder baru
            $createPayload = [
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder',
            ];
            if ($parent) {
                $createPayload['parents'] = [$parent];
            }

            $createResponse = Http::withToken($token)
                ->post(self::DRIVE_API_BASE . '/files?supportsAllDrives=true', $createPayload);

            if ($createResponse->successful()) {
                return $createResponse->json('id');
            }

            Log::error("HCM Google Drive: Gagal membuat folder '{$folderName}'", [
                'status' => $createResponse->status(),
                'body' => $createResponse->body(),
            ]);

            return null;
        });
    }

    /**
     * Upload berkas ke Google Drive secara multipart.
     * Mengembalikan array metadata file: [ id, name, webViewLink, webContentLink, embedUrl ]
     */
    public static function uploadFile(
        string $filePath,
        string $fileName,
        string $mimeType,
        ?string $folderId = null
    ): ?array {
        $token = self::getAccessToken();
        if (!$token || !file_exists($filePath)) {
            return null;
        }

        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            return null;
        }

        $boundary = '-------' . uniqid('', true);
        $metadata = [
            'name' => $fileName,
        ];
        if ($folderId) {
            $metadata['parents'] = [$folderId];
        }

        $body = "--{$boundary}\r\n"
            . "Content-Type: application/json; charset=UTF-8\r\n\r\n"
            . json_encode($metadata) . "\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: {$mimeType}\r\n\r\n"
            . $fileContent . "\r\n"
            . "--{$boundary}--";

        $response = Http::withToken($token)
            ->withHeaders([
                'Content-Type' => 'multipart/related; boundary=' . $boundary,
                'Content-Length' => strlen($body),
            ])
            ->send('POST', self::UPLOAD_API_BASE . '/files?uploadType=multipart&supportsAllDrives=true&fields=id,name,webViewLink,webContentLink', [
                'body' => $body,
            ]);

        if (!$response->successful()) {
            Log::error("HCM Google Drive: Gagal mengunggah berkas '{$fileName}'", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        $data = $response->json();
        $fileId = $data['id'] ?? null;

        if ($fileId) {
            // Berikan izin baca publik (anyone with link can view) agar iframe preview dapat dirender
            self::setPublicPermission($fileId);
        }

        return [
            'id' => $fileId,
            'name' => $data['name'] ?? $fileName,
            'webViewLink' => $data['webViewLink'] ?? "https://drive.google.com/file/d/{$fileId}/view",
            'webContentLink' => $data['webContentLink'] ?? "https://drive.google.com/uc?id={$fileId}&export=download",
            'embedUrl' => "https://drive.google.com/file/d/{$fileId}/preview",
        ];
    }

    /**
     * Set permission file agar dapat dilihat publik via link (anyone with link = reader).
     */
    public static function setPublicPermission(string $fileId): bool
    {
        $token = self::getAccessToken();
        if (!$token) {
            return false;
        }

        $response = Http::withToken($token)->post(self::DRIVE_API_BASE . "/files/{$fileId}/permissions?supportsAllDrives=true", [
            'role' => 'reader',
            'type' => 'anyone',
        ]);

        return $response->successful();
    }

    /**
     * Hapus berkas dari Google Drive berdasarkan File ID.
     */
    public static function deleteFile(string $fileId): bool
    {
        $token = self::getAccessToken();
        if (!$token) {
            return false;
        }

        $response = Http::withToken($token)->delete(self::DRIVE_API_BASE . "/files/{$fileId}?supportsAllDrives=true");

        return $response->successful() || $response->status() === 404;
    }

    /**
     * Ekstrak Google Drive File ID dari URL share/view/preview.
     */
    public static function extractFileIdFromUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        // Format 1: drive.google.com/file/d/{id}/...
        if (preg_match('#drive\.google\.com/file/d/([a-zA-Z0-9_-]+)#', $url, $matches)) {
            return $matches[1];
        }

        // Format 2: drive.google.com/open?id={id} atau uc?id={id}
        if (preg_match('#[?&]id=([a-zA-Z0-9_-]+)#', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private static function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
}
