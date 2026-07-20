<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Order\OrderItem;

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
    protected $description = 'Prune orphaned uploaded files that are no longer referenced in the database';

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
            // Check if file path is referenced in order_items table
            $referenced = OrderItem::where('gambar_desain', 'like', "%{$file}%")
                ->orWhere('gambar_kerah', 'like', "%{$file}%")
                ->orWhere('gambar_ket_tambahan', 'like', "%{$file}%")
                ->exists();

            if (!$referenced) {
                Storage::disk('public')->delete($file);
                $deletedCount++;
                $this->line("Deleted orphaned file: {$file}");
            }
        }

        $this->info("Pruning complete. Deleted {$deletedCount} out of {$totalFiles} files.");
    }
}
