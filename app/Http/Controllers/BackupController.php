<?php

namespace App\Http\Controllers;

use App\Models\Order\Order;
use App\Models\Settings\SystemSetting;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BackupController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('settings.system');

        $publicDisk = Storage::disk('public');
        $ordersPath = $publicDisk->path('orders');

        $totalSize = 0;
        $fileCount = 0;

        if (is_dir($ordersPath)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($ordersPath, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $totalSize += $file->getSize();
                    $fileCount++;
                }
            }
        }

        // Hitung file gambar yang aman untuk di-cleanup (Order status 'selesai' atau 'sudah_dikirim' yang berumur > 30 hari)
        $thresholdDate = now()->subDays(30);
        $cleanupCandidates = Order::whereIn('status_po', ['selesai', 'sudah_dikirim'])
            ->where('updated_at', '<', $thresholdDate)
            ->with('items')
            ->get();

        $cleanupSize = 0;
        $cleanupFileCount = 0;

        foreach ($cleanupCandidates as $order) {
            foreach ($order->items as $item) {
                $fields = ['gambar_desain', 'gambar_kerah', 'gambar_ket_tambahan'];
                foreach ($fields as $field) {
                    $filePath = $item->$field;
                    if ($filePath && $publicDisk->exists($filePath)) {
                        $fullPath = $publicDisk->path($filePath);
                        if (is_file($fullPath)) {
                            $cleanupSize += filesize($fullPath);
                            $cleanupFileCount++;
                        }
                    }
                }
            }
        }

        $clientId = SystemSetting::get('gdrive', 'client_id', '');
        $clientSecret = SystemSetting::get('gdrive', 'client_secret', '');
        $refreshToken = SystemSetting::get('gdrive', 'refresh_token', '');
        $connectedEmail = SystemSetting::get('gdrive', 'connected_email', '');

        return Inertia::render('Settings/Backup', [
            'stats' => [
                'total_size_human' => $this->formatBytes($totalSize),
                'total_size_bytes' => $totalSize,
                'file_count' => $fileCount,
                'cleanup_size_human' => $this->formatBytes($cleanupSize),
                'cleanup_size_bytes' => $cleanupSize,
                'cleanup_file_count' => $cleanupFileCount,
                'threshold_days' => 30,
            ],
            'gdrive' => [
                'is_enabled' => SystemSetting::get('gdrive', 'is_enabled', '0') === '1',
                'client_id' => $clientId,
                // Mask secret di frontend demi keamanan
                'client_secret_masked' => $clientSecret ? SystemSetting::maskedValue($clientSecret) : '',
                'has_secret' => !empty($clientSecret),
                'folder_id' => SystemSetting::get('gdrive', 'folder_id', ''),
                'is_connected' => !empty($refreshToken),
                'connected_email' => $connectedEmail,
            ]
        ]);
    }

    public function updateSettings(Request $request)
    {
        Gate::authorize('settings.system');

        $data = $request->validate([
            'is_enabled' => ['required', 'boolean'],
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:255'],
            'folder_id' => ['nullable', 'string', 'max:255'],
        ]);

        SystemSetting::set('gdrive', 'is_enabled', $data['is_enabled'] ? '1' : '0');
        SystemSetting::set('gdrive', 'client_id', $data['client_id'] ?? '');
        
        if ($request->filled('client_secret')) {
            SystemSetting::set('gdrive', 'client_secret', $data['client_secret']);
        }
        
        SystemSetting::set('gdrive', 'folder_id', $data['folder_id'] ?? '');

        return back()->with('success', 'Pengaturan aplikasi Google Drive berhasil disimpan.');
    }

    public function redirectToGoogle(Request $request)
    {
        Gate::authorize('settings.system');

        $clientId = SystemSetting::get('gdrive', 'client_id');
        if (!$clientId) {
            return back()->with('error', 'Google OAuth Client ID belum dikonfigurasi.');
        }

        $authUrl = GoogleDriveService::getAuthUrl($clientId);

        return Inertia::location($authUrl);
    }

    public function handleGoogleCallback(Request $request)
    {
        Gate::authorize('settings.system');

        if (!$request->has('code')) {
            return redirect()->route('settings.backup')->with('error', 'Otentikasi dibatalkan atau kode otentikasi tidak ditemukan.');
        }

        $clientId = SystemSetting::get('gdrive', 'client_id');
        $clientSecret = SystemSetting::get('gdrive', 'client_secret');

        if (!$clientId || !$clientSecret) {
            return redirect()->route('settings.backup')->with('error', 'Kredensial Google OAuth Client ID & Secret tidak lengkap.');
        }

        try {
            $tokens = GoogleDriveService::handleCallback(
                $request->string('code')->toString(),
                $clientId,
                $clientSecret
            );

            if (!empty($tokens['refresh_token'])) {
                SystemSetting::set('gdrive', 'refresh_token', $tokens['refresh_token'], encrypted: true);
            }
            
            if (!empty($tokens['email'])) {
                SystemSetting::set('gdrive', 'connected_email', $tokens['email']);
            }

            return redirect()->route('settings.backup')->with('success', 'Akun Google Drive berhasil dihubungkan.');
        } catch (\Throwable $e) {
            return redirect()->route('settings.backup')->with('error', 'Gagal menghubungkan Google Drive: ' . $e->getMessage());
        }
    }

    public function disconnectGoogle(Request $request)
    {
        Gate::authorize('settings.system');

        SystemSetting::set('gdrive', 'refresh_token', '');
        SystemSetting::set('gdrive', 'connected_email', '');

        return back()->with('success', 'Koneksi akun Google Drive berhasil diputuskan.');
    }

    public function runBackup(Request $request)
    {
        Gate::authorize('settings.system');

        $isEnabled = SystemSetting::get('gdrive', 'is_enabled', '0');
        if ($isEnabled !== '1') {
            return back()->with('error', 'Integrasi Google Drive dinonaktifkan. Silakan aktifkan terlebih dahulu.');
        }

        try {
            $exitCode = Artisan::call('backup:gdrive');
            $output = Artisan::output();

            if ($exitCode === 0) {
                return back()->with('success', 'Proses backup ke Google Drive berhasil diselesaikan.');
            } else {
                return back()->with('error', 'Gagal melakukan backup ke GDrive. Info: ' . trim($output));
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Error backup: ' . $e->getMessage());
        }
    }

    public function download(Request $request)
    {
        Gate::authorize('settings.system');

        $publicDisk = Storage::disk('public');
        $ordersPath = $publicDisk->path('orders');

        if (!is_dir($ordersPath) || count(scandir($ordersPath)) <= 2) {
            return back()->with('error', 'Tidak ada aset foto pesanan untuk dibackup.');
        }

        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0755, true);
        }

        $zipFileName = 'backup-foto-orders-' . date('Ymd-His') . '.zip';
        $zipFilePath = $backupDir . '/' . $zipFileName;

        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Gagal membuat file arsip ZIP.');
        }

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

        if ($addedFiles === 0) {
            @unlink($zipFilePath);
            return back()->with('error', 'Tidak ada file gambar yang berhasil dimasukkan ke ZIP.');
        }

        return response()->download($zipFilePath, $zipFileName)->deleteFileAfterSend(true);
    }

    public function cleanUp(Request $request)
    {
        Gate::authorize('settings.system');

        $request->validate([
            'confirm' => ['required', 'accepted']
        ]);

        $publicDisk = Storage::disk('public');
        $thresholdDate = now()->subDays(30);

        $cleanupCandidates = Order::whereIn('status_po', ['selesai', 'sudah_dikirim'])
            ->where('updated_at', '<', $thresholdDate)
            ->with('items')
            ->get();

        $deletedCount = 0;
        $freedBytes = 0;

        foreach ($cleanupCandidates as $order) {
            foreach ($order->items as $item) {
                $fields = ['gambar_desain', 'gambar_kerah', 'gambar_ket_tambahan'];
                foreach ($fields as $field) {
                    $filePath = $item->$field;
                    if ($filePath && $publicDisk->exists($filePath)) {
                        $fullPath = $publicDisk->path($filePath);
                        if (is_file($fullPath)) {
                            $fileSize = filesize($fullPath);
                            if ($publicDisk->delete($filePath)) {
                                $deletedCount++;
                                $freedBytes += $fileSize;
                                $item->$field = null;
                            }
                        }
                    }
                }
                $item->save();
            }
        }

        $freedHuman = $this->formatBytes($freedBytes);

        return back()->with('success', "Pembersihan berhasil. Sebanyak {$deletedCount} berkas foto lama dihapus, membebaskan {$freedHuman} ruang penyimpanan.");
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
