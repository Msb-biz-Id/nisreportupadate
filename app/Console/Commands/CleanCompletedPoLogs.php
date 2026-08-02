<?php

namespace App\Console\Commands;

use App\Models\Order\Order;
use App\Models\Order\POChangeLog;
use Illuminate\Console\Command;

class CleanCompletedPoLogs extends Command
{
    protected $signature = 'po:clean-logs {--all-completed : Juga hapus log untuk status sudah_dikirim}';

    protected $description = 'Hapus log perubahan (po_change_logs) untuk PO yang sudah berstatus selesai agar database tetap ringan';

    public function handle(): int
    {
        $statuses = ['selesai'];
        if ($this->option('all-completed')) {
            $statuses[] = 'sudah_dikirim';
        }

        $completedOrderIds = Order::whereIn('status_po', $statuses)->pluck('id');
        $deletedCount = POChangeLog::whereIn('order_id', $completedOrderIds)->delete();

        $this->info("Berhasil menghapus {$deletedCount} data log perubahan (POChangeLog) untuk PO berstatus: " . implode(', ', $statuses) . ".");

        return Command::SUCCESS;
    }
}
