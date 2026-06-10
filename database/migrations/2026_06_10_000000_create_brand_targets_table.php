<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_targets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->integer('year');
            $table->integer('month');
            $table->decimal('target_revenue', 15, 2)->default(0);
            $table->integer('target_pcs')->default(0);
            $table->timestamps();

            $table->unique(['brand_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_targets');
    }
};
