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
        Schema::create('purchasing_master_options', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('category', 50)->comment('location, item_category, unit, vendor_type, payment_type, asset_category, asset_unit, retirement_reason, final_condition, disposal_method, ink_variant');
            $table->string('code', 50);
            $table->string('name', 150);
            $table->unsignedInteger('order_index')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'is_active'], 'idx_pmo_category_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchasing_master_options');
    }
};
