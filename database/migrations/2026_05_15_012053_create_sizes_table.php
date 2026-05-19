<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sizes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kategori_size', 50);
            $table->string('ukuran', 20);
            $table->integer('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['kategori_size', 'ukuran']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sizes');
    }
};
