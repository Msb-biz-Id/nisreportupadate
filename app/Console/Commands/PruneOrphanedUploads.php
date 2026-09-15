<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Order\OrderItem;
use App\Models\Order\Order;
use App\Models\Order\OrderPayment;
use App\Models\Brand;

class PruneOrphanedUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploads:prune {--dry-run : Simulasi pengecekan tanpa menghapus file fisik} {--days=14 : Batas hari grace period sebelum file diperiksa (default: 14 hari)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely prune orphaned uploaded files that are no longer referenced anywhere in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $graceDays = max(7, (int) $this->option('days'));

        $this->info("Scanning for orphaned uploads (Grace period: {$graceDays} days" . ($dryRun ? ' [DRY-RUN MODE]' : '') . ")...");

        if (!Storage::disk('public')->exists('orders')) {
            $this->warn('No "orders" directory found on public disk.');
            return;
        }

        $files = Storage::disk('public')->allFiles('orders');
        $totalFiles = count($files);
        $orphanedCount = 0;
        $deletedCount = 0;

        foreach ($files as $file) {
            // Safe grace period: abaikan file yang diupload dalam kurun grace period
            $lastModified = @Storage::disk('public')->lastModified($file) ?: 0;
            if ((time() - $lastModified) < ($graceDays * 86400)) {
                continue;
            }

            // Gunakan FileReferenceChecker yang memeriksa seluruh tabel (termasuk brand, payment, product, order items)
            $isReferenced = \App\Support\FileReferenceChecker::isReferenced($file);

            // Tambahan perlindungan untuk order / item yang soft-deleted
            if (!$isReferenced) {
                $filename = basename($file);
                $inTrashedItems = OrderItem::withTrashed()
                    ->where('gambar_desain', 'like', "%{$filename}%")
                    ->orWhere('gambar_kerah', 'like', "%{$filename}%")
                    ->orWhere('gambar_ket_tambahan', 'like', "%{$filename}%")
                    ->exists();

                $inTrashedOrders = Order::withTrashed()
                    ->where('desain_pola', 'like', "%{$filename}%")
                    ->orWhere('file_attachment', 'like', "%{$filename}%")
                    ->exists();

                if ($inTrashedItems || $inTrashedOrders) {
                    $isReferenced = true;
                }
            }

            if (!$isReferenced) {
                $orphanedCount++;
                if ($dryRun) {
                    $this->line("[DRY-RUN] Would delete orphaned file: {$file}");
                } else {
                    Storage::disk('public')->delete($file);
                    $deletedCount++;
                    $this->line("Deleted orphaned file: {$file}");
                }
            }
        }

        if ($dryRun) {
            $this->info("Scan complete. Found {$orphanedCount} orphaned files out of {$totalFiles} total files. (Tidak ada file yang dihapus karena mode dry-run).");
        } else {
            $this->info("Pruning complete. Deleted {$deletedCount} out of {$totalFiles} files.");
        }
    }
}
