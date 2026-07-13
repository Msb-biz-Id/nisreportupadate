<?php

namespace App\Http\Controllers;

use App\Models\Order\Order;
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

        $r2Configured = !empty(config('filesystems.disks.r2.key')) && !empty(config('filesystems.disks.r2.secret'));
        $r2Bucket = config('filesystems.disks.r2.bucket', '');
        $r2Endpoint = config('filesystems.disks.r2.endpoint', '');

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
            'r2' => [
                'is_configured' => $r2Configured,
                'bucket' => $r2Bucket,
                'endpoint' => $r2Endpoint,
            ]
        ]);
    }

    public function runBackup(Request $request)
    {
        Gate::authorize('settings.system');

        $r2Configured = !empty(config('filesystems.disks.r2.key')) && !empty(config('filesystems.disks.r2.secret'));
        if (!$r2Configured) {
            return back()->with('error', 'Integrasi Cloudflare R2 belum dikonfigurasi di file .env.');
        }

        try {
            $exitCode = Artisan::call('backup:r2');
            $output = Artisan::output();

            if ($exitCode === 0) {
                return back()->with('success', 'Proses backup ke Cloudflare R2 berhasil diselesaikan.');
            } else {
                return back()->with('error', 'Gagal melakukan backup ke R2. Info: ' . trim($output));
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

    private function formatBytes(int|float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
