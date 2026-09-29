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
        Schema::create('hcm_master_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('group', 100)->default('Umum');
            $table->string('icon', 50)->nullable();
            $table->text('description')->nullable();
            $table->integer('order_index')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'order_index']);
        });

        Schema::create('hcm_master_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('hcm_master_categories')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('code', 100)->nullable();
            $table->integer('order_index')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category_id', 'is_active', 'order_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hcm_master_options');
        Schema::dropIfExists('hcm_master_categories');
    }
};
