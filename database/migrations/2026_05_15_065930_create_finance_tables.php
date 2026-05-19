<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_pemasukan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->string('nama_kategori', 100);
            $table->text('deskripsi')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('brand_id');
            $table->index('is_system');
        });

        Schema::create('kategori_pengeluaran', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignUuid('parent_id')->nullable()->references('id')->on('kategori_pengeluaran')->nullOnDelete();
            $table->string('nama_kategori', 100);
            $table->text('deskripsi')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('brand_id');
            $table->index('parent_id');
            $table->index('is_system');
        });

        Schema::create('pemasukan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignUuid('kategori_pemasukan_id')->constrained('kategori_pemasukan')->cascadeOnDelete();
            $table->foreignUuid('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignUuid('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->date('tanggal');
            $table->decimal('nominal', 15, 2);
            $table->text('keterangan');
            $table->json('bukti')->nullable();
            $table->boolean('is_auto')->default(false);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('brand_id');
            $table->index('tanggal');
            $table->index('is_auto');
        });

        Schema::create('pengeluaran', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignUuid('kategori_pengeluaran_id')->constrained('kategori_pengeluaran')->cascadeOnDelete();
            $table->foreignUuid('refund_id')->nullable()->constrained('refunds')->nullOnDelete();
            $table->date('tanggal');
            $table->decimal('nominal', 15, 2);
            $table->text('keterangan');
            $table->json('bukti')->nullable();
            $table->boolean('is_auto')->default(false);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('brand_id');
            $table->index('tanggal');
            $table->index('is_auto');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengeluaran');
        Schema::dropIfExists('pemasukan');
        Schema::dropIfExists('kategori_pengeluaran');
        Schema::dropIfExists('kategori_pemasukan');
    }
};
