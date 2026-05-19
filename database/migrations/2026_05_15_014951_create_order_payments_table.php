<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->enum('payment_type', ['dp', 'pelunasan', 'lainnya']);
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->foreignUuid('bank_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->string('proof_file')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('payment_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};
