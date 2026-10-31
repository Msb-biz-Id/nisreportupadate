<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchasing_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('po_number', 50)->unique();
            $table->date('transaction_date');
            $table->enum('order_type', ['OPEX', 'CAPEX'])->default('OPEX');
            
            // Relasi Read-Only ke Karyawan HRIS
            $table->foreignId('requester_employee_id')->nullable()->constrained('hcm_employees')->nullOnDelete();
            // Immutable Snapshots
            $table->string('requester_name', 150)->nullable();
            $table->string('department', 100);
            $table->string('division', 100)->nullable();
            $table->string('position', 100);

            $table->unsignedBigInteger('location_id')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained('purchasing_vendors')->nullOnDelete();
            $table->string('vendor_name_manual', 150)->nullable();
            $table->unsignedBigInteger('item_category_id')->nullable();
            $table->string('item_name', 150);
            $table->text('specification')->nullable();
            $table->string('unit', 50);
            $table->decimal('quantity', 12, 2)->default(1.00);
            $table->decimal('unit_price', 15, 2)->default(0.00);
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('shipping_cost', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('grand_total', 15, 2)->default(0.00);
            $table->string('payment_type', 50);
            $table->string('invoice_number', 100)->nullable();
            $table->string('invoice_file_path', 255)->nullable();
            $table->string('status', 50)->default('DRAFT');

            $table->unsignedBigInteger('pic_approved_by')->nullable();
            $table->timestamp('pic_approved_at')->nullable();
            $table->unsignedBigInteger('finance_approved_by')->nullable();
            $table->timestamp('finance_approved_at')->nullable();

            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['transaction_date', 'status'], 'idx_po_date_status');
            $table->index(['order_type', 'department'], 'idx_po_type_dept');
            $table->index('requester_employee_id', 'idx_po_requester');
        });

        Schema::create('purchasing_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('order_id')->constrained('purchasing_orders')->cascadeOnDelete();
            $table->string('invoice_number', 100);
            $table->unsignedInteger('term_step')->default(1);
            $table->string('term_name', 100);
            $table->decimal('term_percentage', 5, 2);
            $table->decimal('amount', 15, 2);
            $table->date('due_date');
            $table->enum('status', ['PENDING', 'DUE_TODAY', 'OVERDUE', 'PAID'])->default('PENDING');
            $table->date('paid_at')->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('receipt_file_path', 255)->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['due_date', 'status'], 'idx_pp_due_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchasing_payments');
        Schema::dropIfExists('purchasing_orders');
    }
};
