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
        Schema::create('purchasing_order_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('order_id')->constrained('purchasing_orders')->cascadeOnDelete();
            $table->unsignedBigInteger('item_category_id')->nullable();
            $table->string('item_name', 150);
            $table->text('specification')->nullable();
            $table->string('unit', 50)->default('Pcs');
            $table->decimal('quantity', 12, 2)->default(1.00);
            $table->decimal('unit_price', 15, 2)->default(0.00);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->unsignedInteger('order_index')->default(1);
            $table->timestamps();

            $table->index('order_id', 'idx_poi_order_id');
            $table->index('item_category_id', 'idx_poi_cat_id');
        });

        // Add payment_mode column to purchasing_orders if not present
        if (Schema::hasTable('purchasing_orders') && !Schema::hasColumn('purchasing_orders', 'payment_mode')) {
            Schema::table('purchasing_orders', function (Blueprint $table) {
                $table->enum('payment_mode', ['SINGLE', 'SPLIT'])->default('SINGLE')->after('payment_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchasing_order_items');

        if (Schema::hasTable('purchasing_orders') && Schema::hasColumn('purchasing_orders', 'payment_mode')) {
            Schema::table('purchasing_orders', function (Blueprint $table) {
                $table->dropColumn('payment_mode');
            });
        }
    }
};
