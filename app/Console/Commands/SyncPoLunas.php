<?php

namespace App\Console\Commands;

use App\Models\Order\Order;
use Illuminate\Console\Command;

class SyncPoLunas extends Command
{
    protected $signature = 'po:sync-lunas {--dry-run : Menampilkan PO yang terpengaruh tanpa mengubah database}';

    protected $description = 'Sinkronkan status is_lunas pada seluruh PO yang sisa tagihannya sudah Rp 0';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $this->info($dryRun ? '=== DRY RUN MODE (Tidak ada data database yang diubah) ===' : '=== MEMULAI SINKRONISASI STATUS LUNAS PO ===');

        // Ambil PO non-draft yang belum ditandai lunas
        $orders = Order::where('status_po', '!=', 'draft')
            ->where('is_lunas', false)
            ->get();

        $updatedCount = 0;
        $this->info("Menemukan {$orders->count()} PO non-draft yang berstatus 'Belum Lunas'.");

        foreach ($orders as $order) {
            $totalTagihan = $order->totalTagihan();
            $totalPaid = $order->totalPaid();
            $sisa = $totalTagihan - $totalPaid;

            if ($sisa <= 0) {
                $this->line("- PO [{$order->no_po}] {$order->nama_po} - Tagihan: Rp " . number_format($totalTagihan, 0, ',', '.') . " | Terbayar: Rp " . number_format($totalPaid, 0, ',', '.') . " | Sisa: Rp " . number_format($sisa, 0, ',', '.'));
                
                if (!$dryRun) {
                    // Update field is_lunas ke true
                    $order->is_lunas = true;
                    // Simpan data order (ini juga akan menembak POStatusManager untuk mengevaluasi stage jika statusnya siap_dikirim)
                    $order->save();
                }
                $updatedCount++;
            }
        }

        $this->info("\nSinkronisasi selesai.");
        if ($dryRun) {
            $this->info("Ada {$updatedCount} PO yang akan ditandai Lunas.");
        } else {
            $this->info("Berhasil menandai {$updatedCount} PO sebagai Lunas.");
        }

        return Command::SUCCESS;
    }
}
