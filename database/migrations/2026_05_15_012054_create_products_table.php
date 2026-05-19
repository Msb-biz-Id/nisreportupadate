<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('brand_id')->nullable()->constrained('brands')->cascadeOnDelete();
            $table->string('nama', 255);
            $table->string('kode', 50)->nullable();
            $table->decimal('harga', 12, 2)->default(0);
            $table->text('deskripsi')->nullable();
            $table->string('gambar')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('brand_id');
            $table->index('is_active');
            $table->index('is_featured');
            $table->index('nama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
