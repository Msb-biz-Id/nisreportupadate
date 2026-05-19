<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('refund_number', 50)->unique();
            $table->text('alasan');
            $table->enum('jenis_masalah', [
                'produk_cacat', 'ukuran_salah', 'warna_tidak_sesuai',
                'bahan_salah', 'printing_error', 'jahitan_rusak', 'lainnya',
            ]);
            $table->integer('jumlah_item');
            $table->decimal('nominal_refund', 12, 2);
            $table->json('bukti')->nullable();
            $table->text('catatan')->nullable();
            $table->enum('status', ['draft', 'pending_review', 'approved', 'published', 'rejected'])->default('pending_review');
            $table->text('rejected_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('brand_id');
            $table->index('order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
