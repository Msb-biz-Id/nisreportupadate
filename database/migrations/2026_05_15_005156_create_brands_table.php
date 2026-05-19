<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama_brand', 100);
            $table->string('kode', 20)->unique();
            $table->string('tagline', 255)->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();
            $table->string('email')->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->string('timezone', 50)->default('Asia/Jakarta');
            $table->string('currency', 10)->default('IDR');
            $table->string('warna_primary', 20)->default('#3B82F6');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('kode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
