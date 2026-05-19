<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('invoice_number', 50);
            $table->date('tanggal_terbit');
            $table->date('jatuh_tempo')->nullable();
            $table->enum('status', ['draft', 'validated', 'published', 'sent', 'paid', 'overdue', 'cancel'])->default('draft');
            $table->decimal('total_tagihan', 12, 2);
            $table->decimal('total_bayar', 12, 2)->default(0);
            $table->decimal('dp_amount', 12, 2)->default(0);
            $table->decimal('sisa_pembayaran', 12, 2)->default(0);
            $table->enum('diskon_type', ['persen', 'nominal'])->nullable();
            $table->decimal('diskon_value', 12, 2)->default(0);
            $table->decimal('biaya_pengiriman', 12, 2)->default(0);
            $table->string('jasa_pengiriman', 100)->nullable();
            $table->foreignUuid('bank_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->text('peraturan')->nullable();
            $table->json('faq')->nullable();
            $table->string('qr_code')->nullable();
            $table->enum('sent_via', ['whatsapp', 'email', 'manual'])->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['brand_id', 'invoice_number']);
            $table->index('order_id');
            $table->index('status');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('produk', 255);
            $table->integer('jumlah');
            $table->decimal('harga_satuan', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();

            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
