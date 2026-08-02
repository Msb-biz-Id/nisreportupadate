<?php

namespace App\Console\Commands;

use App\Models\Order\Order;
use App\Models\Order\POChangeLog;
use Illuminate\Console\Command;

class CleanCompletedPoLogs extends Command
{
    protected $signature = 'po:clean-logs {--days=30 : Jumlah hari masa simpan log PO setelah diselesaikan}';

    protected $description = 'Hapus log perubahan (POChangeLog) untuk PO yang berstatus selesai dan sudah berumur lebih dari 30 hari';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days);

        // Ambil ID PO berstatus 'selesai' yang waktu selesainya (updated_at) sudah lebih dari $days hari
        $completedOrderIds = Order::where('status_po', 'selesai')
            ->where('updated_at', '<=', $cutoffDate)
            ->pluck('id');

        $deletedCount = POChangeLog::whereIn('order_id', $completedOrderIds)->delete();

        $this->info("Berhasil menghapus {$deletedCount} data log perubahan untuk PO berstatus selesai yang sudah berumur lebih dari {$days} hari (sebelum {$cutoffDate->format('Y-m-d H:i:s')}).");

        return Command::SUCCESS;
    }
}
