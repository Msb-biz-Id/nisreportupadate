<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_produks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama', 100);
            $table->string('deskripsi', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // order_items: tambah jenis_produk_id untuk tracking checkbox Buka Modul
        Schema::table('order_items', function (Blueprint $table) {
            $table->uuid('jenis_produk_id')->nullable()->after('product_id');
            $table->foreign('jenis_produk_id')->references('id')->on('jenis_produks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['jenis_produk_id']);
            $table->dropColumn('jenis_produk_id');
        });
        Schema::dropIfExists('jenis_produks');
    }
};
