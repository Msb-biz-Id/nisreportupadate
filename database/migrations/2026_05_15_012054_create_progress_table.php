<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama_progress', 100);
            $table->string('warna', 20)->default('#3B82F6');
            $table->integer('urutan')->default(0);
            $table->boolean('is_skippable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('urutan');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress');
    }
};
