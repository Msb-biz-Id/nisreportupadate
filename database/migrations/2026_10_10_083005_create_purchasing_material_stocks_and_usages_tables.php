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
        Schema::create('purchasing_material_stocks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->enum('material_type', ['KAIN_PUTIH', 'KAIN_WARNA', 'KERTAS', 'TINTA']);
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('unit', 50);
            $table->decimal('minimum_threshold', 12, 2)->default(0.00);
            $table->decimal('current_stock', 12, 2)->default(0.00);
            $table->timestamps();

            $table->index('material_type', 'idx_pms_type');
        });

        Schema::create('purchasing_material_usages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('material_stock_id')->constrained('purchasing_material_stocks')->cascadeOnDelete();
            $table->date('usage_date');
            $table->enum('material_type', ['KAIN_PUTIH', 'KAIN_WARNA', 'KERTAS', 'TINTA']);
            $table->string('material_name', 150);
            $table->string('unit', 50);
            $table->decimal('quantity_in', 12, 2)->default(0.00);
            $table->decimal('quantity_out', 12, 2)->default(0.00);
            $table->decimal('balance_stock', 12, 2);
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('department', 100)->nullable();
            $table->string('division', 100)->nullable();
            $table->string('operator_name', 100)->nullable();
            $table->string('production_ref', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['usage_date', 'material_stock_id'], 'idx_pmu_date_mat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchasing_material_usages');
        Schema::dropIfExists('purchasing_material_stocks');
    }
};
