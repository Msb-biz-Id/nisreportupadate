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
        Schema::create('purchasing_asset_suggestions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('keyword', 100)->unique();
            $table->string('category_code', 10);
            $table->string('category_name', 150);
            $table->timestamps();

            $table->index('keyword', 'idx_pas_keyword');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchasing_asset_suggestions');
    }
};
