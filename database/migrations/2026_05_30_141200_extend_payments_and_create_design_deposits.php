<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Expand order_payments to support all transaction types
        Schema::table('order_payments', function (Blueprint $table) {
            // Workaround: SQLite cannot alter enum columns, so we add new columns
            $table->integer('dp_sequence')->nullable()->after('payment_type');
            $table->boolean('is_debit')->default(true)->after('dp_sequence');
        });

        // 2. Create design_deposits table (Tanda Jadi)
        Schema::create('design_deposits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('deposit_number', 50);
            $table->string('customer_name', 255);
            $table->string('description', 500);
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->foreignUuid('bank_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->string('proof_file')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'verified', 'converted', 'refunded', 'expired'])->default('pending');
            $table->foreignUuid('converted_to_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['brand_id', 'deposit_number']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_deposits');

        Schema::table('order_payments', function (Blueprint $table) {
            $table->dropColumn(['dp_sequence', 'is_debit']);
        });
    }
};
