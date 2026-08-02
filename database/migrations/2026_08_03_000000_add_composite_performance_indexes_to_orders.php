<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Composite index for brand + tanggal_masuk + status_po (used heavily in reports & dashboard filtering)
            $table->index(['brand_id', 'tanggal_masuk', 'status_po'], 'idx_orders_brand_tanggal_status');
        });

        Schema::table('order_items', function (Blueprint $table) {
            // Composite index for order_id + is_addon lookup
            $table->index(['order_id', 'is_addon'], 'idx_items_order_addon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_brand_tanggal_status');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('idx_items_order_addon');
        });
    }
};
