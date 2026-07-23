<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Order\Order;
use App\Services\POStatusManager;

return new class extends Migration
{
    public function up(): void
    {
        $orders = Order::whereNotIn('status_po', ['sudah_dikirim', 'selesai'])->get();
        $statusManager = app(POStatusManager::class);

        foreach ($orders as $order) {
            $statusManager->recalculateOrderStatus($order);
        }
    }

    public function down(): void
    {
        // No rollback needed for data transformation
    }
};
