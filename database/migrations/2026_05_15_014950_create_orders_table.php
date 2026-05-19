<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->string('no_po', 50);
            $table->string('nama_po', 255);
            $table->enum('status_po', [
                'draft', 'published', 'on_progress', 'selesai_produksi',
                'siap_dikirim', 'sudah_dikirim', 'delay', 'hold',
            ])->default('draft');
            $table->boolean('is_special_order')->default(false);

            $table->date('tanggal_masuk');
            $table->date('deadline_customer');
            $table->date('start_production_date')->nullable();
            $table->date('end_production_date')->nullable();

            $table->foreignUuid('kategori_order_id')->nullable()->constrained('kategori_orders')->nullOnDelete();
            $table->foreignUuid('sumber_order_id')->nullable()->constrained('sumber_orders')->nullOnDelete();
            $table->foreignUuid('pelanggan_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignUuid('repeat_from_po_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->boolean('is_repeat_order')->default(false);

            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('total_tagihan', 12, 2)->default(0);
            $table->text('catatan')->nullable();

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['brand_id', 'no_po']);
            $table->index('brand_id');
            $table->index('status_po');
            $table->index('tanggal_masuk');
            $table->index('deadline_customer');
            $table->index('pelanggan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
