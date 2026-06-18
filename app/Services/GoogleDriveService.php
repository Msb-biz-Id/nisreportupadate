<?php

namespace App\Services;

use App\Models\Settings\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    /**
     * Dapatkan URL Otentikasi Google OAuth2.
     */
    public static function getAuthUrl(string $clientId): string
    {
        $redirectUri = route('settings.backup.gdrive.callback');
        $scopes = [
            'https://www.googleapis.com/auth/drive.file',
            'https://www.googleapis.com/auth/userinfo.email'
        ];

        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'access_type' => 'offline',
            'prompt' => 'consent select_account',
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * Tukarkan Authorization Code dari redirect callback menjadi Refresh Token & Access Token.
     */
    public static function handleCallback(string $code, string $clientId, string $clientSecret): array
    {
        $redirectUri = route('settings.backup.gdrive.callback');

        $response = Http::post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        if (!$response->successful()) {
            throw new \Exception('Gagal menukarkan kode otentikasi Google: ' . $response->body());
        }

        $tokens = $response->json();
        
        // Dapatkan informasi email user untuk ditampilkan di UI
        $email = '';
        if (isset($tokens['access_token'])) {
            $userResponse = Http::withToken($tokens['access_token'])->get('https://www.googleapis.com/oauth2/v2/userinfo');
            if ($userResponse->successful()) {
                $email = $userResponse->json()['email'] ?? '';
            }
        }

        return [
            'access_token' => $tokens['access_token'] ?? '',
            'refresh_token' => $tokens['refresh_token'] ?? '',
            'email' => $email
        ];
    }

    /**
     * Dapatkan Access Token baru menggunakan Refresh Token.
     */
    public static function getAccessToken(string $clientId, string $clientSecret, string $refreshToken): string
    {
        $response = Http::post('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->successful()) {
            throw new \Exception('Gagal memperbarui token Google Drive: ' . $response->body());
        }

        return $response->json()['access_token'] ?? '';
    }

    /**
     * Unggah berkas lokal ke Google Drive Folder menggunakan OAuth2 Token.
     */
    public static function uploadFile(string $localFilePath, string $targetFileName, string $mimeType): string
    {
        $clientId = SystemSetting::get('gdrive', 'client_id');
        $clientSecret = SystemSetting::get('gdrive', 'client_secret');
        $refreshToken = SystemSetting::get('gdrive', 'refresh_token');
        $folderId = SystemSetting::get('gdrive', 'folder_id');

        if (!$clientId || !$clientSecret) {
            throw new \Exception('Kredensial Google OAuth Client ID & Secret belum dikonfigurasi di pengaturan.');
        }
        if (!$refreshToken) {
            throw new \Exception('Akun Google Drive belum dihubungkan. Harap hubungkan akun Anda terlebih dahulu.');
        }
        if (!$folderId) {
            throw new \Exception('Google Drive Folder ID belum dikonfigurasi di pengaturan.');
        }

        if (!is_file($localFilePath)) {
            throw new \Exception("Berkas lokal tidak ditemukan: {$localFilePath}");
        }

        // 1. Dapatkan Access Token GDrive via Refresh Token
        $accessToken = self::getAccessToken($clientId, $clientSecret, $refreshToken);

        // 2. Kirim Multipart Request ke Google Drive Upload API
        $metadata = json_encode([
            'name' => $targetFileName,
            'parents' => [$folderId]
        ]);

        $fileStream = @fopen($localFilePath, 'r');
        if (!$fileStream) {
            throw new \Exception("Gagal membuka berkas lokal untuk dibaca.");
        }

        $response = Http::withToken($accessToken)
            ->asMultipart()
            ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart', [
                [
                    'name' => 'metadata',
                    'contents' => $metadata,
                    'headers' => ['Content-Type' => 'application/json; charset=UTF-8']
                ],
                [
                    'name' => 'file',
                    'contents' => $fileStream,
                    'headers' => ['Content-Type' => $mimeType]
                ]
            ]);

        if (is_resource($fileStream)) {
            fclose($fileStream);
        }

        if (!$response->successful()) {
            throw new \Exception('Google Drive API Error: ' . $response->body());
        }

        $fileId = $response->json()['id'] ?? '';
        
        Log::info("Backup berhasil diunggah ke Google Drive dengan ID File: {$fileId}");

        return $fileId;
    }
}
