<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rijeks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignUuid('progress_id')->nullable()->constrained('progress')->nullOnDelete();
            $table->foreignUuid('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->integer('jumlah');
            $table->enum('jenis', ['sablon', 'printing', 'jahit', 'ukuran', 'lain']);
            $table->enum('tingkat', ['ringan', 'sedang', 'berat']);
            $table->text('kendala');
            $table->text('penanganan')->nullable();
            $table->decimal('biaya_ganti', 12, 2)->default(0);
            $table->enum('status', ['pending', 'proses', 'selesai'])->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rijeks');
    }
};
