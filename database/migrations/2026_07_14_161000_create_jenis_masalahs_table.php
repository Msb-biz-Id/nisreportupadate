<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_masalahs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama', 100)->unique();
            $table->string('deskripsi', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->string('jenis_masalah', 100)->change();
        });
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->enum('jenis_masalah', [
                'produk_cacat', 'ukuran_salah', 'warna_tidak_sesuai',
                'bahan_salah', 'printing_error', 'jahitan_rusak', 'lainnya',
            ])->change();
        });

        Schema::dropIfExists('jenis_masalahs');
    }
};
