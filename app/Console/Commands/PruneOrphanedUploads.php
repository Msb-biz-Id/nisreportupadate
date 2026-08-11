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
    protected $signature = 'uploads:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely prune orphaned uploaded files that are no longer referenced in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Scanning for orphaned uploads...');

        if (!Storage::disk('public')->exists('orders')) {
            $this->warn('No "orders" directory found on public disk.');
            return;
        }

        $files = Storage::disk('public')->allFiles('orders');
        $totalFiles = count($files);
        $deletedCount = 0;

        foreach ($files as $file) {
            // Safe grace period: abaikan file yang diupload dalam 7 hari terakhir
            $lastModified = Storage::disk('public')->lastModified($file);
            if ((time() - $lastModified) < (7 * 86400)) {
                continue;
            }

            $filename = basename($file);

            // Cek di seluruh tabel terkait yang menyimpan gambar
            $inItems = OrderItem::where('gambar_desain', 'like', "%{$filename}%")
                ->orWhere('gambar_kerah', 'like', "%{$filename}%")
                ->orWhere('gambar_ket_tambahan', 'like', "%{$filename}%")
                ->exists();

            $inOrders = Order::where('desain_pola', 'like', "%{$filename}%")
                ->orWhere('file_attachment', 'like', "%{$filename}%")
                ->exists();

            $inPayments = OrderPayment::where('bukti_transfer', 'like', "%{$filename}%")->exists();
            $inBrands = Brand::where('logo', 'like', "%{$filename}%")->exists();

            if (!$inItems && !$inOrders && !$inPayments && !$inBrands) {
                Storage::disk('public')->delete($file);
                $deletedCount++;
                $this->line("Deleted orphaned file: {$file}");
            }
        }

        $this->info("Pruning complete. Deleted {$deletedCount} out of {$totalFiles} files.");
    }
}
