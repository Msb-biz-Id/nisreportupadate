<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pola_jahitans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('jenis_pola', 100);
            $table->string('nama', 100);
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('jenis_pola');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pola_jahitans');
    }
};
