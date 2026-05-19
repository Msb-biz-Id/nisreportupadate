<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignUuid('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('nama_produk', 255);
            $table->string('varian_label', 100)->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('harga_satuan', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);

            // Spesifikasi (refer ke master)
            $table->foreignUuid('bahan_kain_id')->nullable()->constrained('bahan_kains')->nullOnDelete();
            $table->enum('jenis_setelan', ['stell', 'non_stell', 'atasan_saja', 'bawahan_saja'])->nullable();
            $table->foreignUuid('logo_id')->nullable()->constrained('logos')->nullOnDelete();
            $table->foreignUuid('printing_id')->nullable()->constrained('printings')->nullOnDelete();
            $table->foreignUuid('resleting_id')->nullable()->constrained('resletings')->nullOnDelete();
            $table->foreignUuid('pola_jahitan_lengan_id')->nullable()->constrained('pola_jahitans')->nullOnDelete();
            $table->foreignUuid('pola_jahitan_kerah_id')->nullable()->constrained('pola_jahitans')->nullOnDelete();
            $table->foreignUuid('pola_jahitan_bawah_id')->nullable()->constrained('pola_jahitans')->nullOnDelete();
            $table->foreignUuid('pola_jahitan_pundak_id')->nullable()->constrained('pola_jahitans')->nullOnDelete();
            $table->string('warna', 100)->nullable();
            $table->string('gambar_desain')->nullable();
            $table->string('gambar_kerah')->nullable();
            $table->string('jenis_kerah', 100)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('order_id');
        });

        Schema::create('order_namesets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->string('nama_punggung', 100)->nullable();
            $table->string('nomor_punggung', 20)->nullable();
            $table->foreignUuid('size_id')->nullable()->constrained('sizes')->nullOnDelete();
            $table->string('size_label', 50)->nullable();
            $table->text('keterangan')->nullable();
            $table->integer('urutan')->default(0);
            $table->timestamps();

            $table->index('order_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_namesets');
        Schema::dropIfExists('order_items');
    }
};
