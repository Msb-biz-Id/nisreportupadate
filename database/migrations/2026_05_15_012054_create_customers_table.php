<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('brand_id')->nullable()->constrained('brands')->cascadeOnDelete();
            $table->string('kode', 50);
            $table->string('nama', 255);
            $table->string('nomor_hp', 20);
            $table->string('email')->nullable();
            $table->foreignUuid('type_pelanggan_id')->nullable()->constrained('customer_types')->nullOnDelete();
            $table->foreignUuid('sumber_daftar_id')->nullable()->constrained('sumber_orders')->nullOnDelete();
            $table->string('provinsi_code', 10)->nullable();
            $table->string('provinsi_nama', 100)->nullable();
            $table->string('kabupaten_code', 10)->nullable();
            $table->string('kabupaten_nama', 100)->nullable();
            $table->string('kecamatan_code', 10)->nullable();
            $table->string('kecamatan_nama', 100)->nullable();
            $table->string('desa_code', 15)->nullable();
            $table->string('desa_nama', 100)->nullable();
            $table->text('detail_alamat')->nullable();
            $table->string('kodepos', 10)->nullable();
            $table->text('notes')->nullable();
            $table->integer('total_order')->default(0);
            $table->decimal('total_transaksi', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['brand_id', 'kode']);
            $table->index('brand_id');
            $table->index('is_active');
            $table->index('nama');
            $table->index('nomor_hp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
