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
        Schema::create('purchasing_assets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('asset_code', 50)->unique()->comment('[Kategori].[Dept].[Tahun3Digit].[Urut3Digit] e.g. IT.HCM.026.001');
            $table->string('category_code', 10);
            $table->string('department_code', 10);
            $table->string('year_code', 5);
            $table->unsignedInteger('sequence_number');
            $table->enum('registration_stage', ['QUICK_REGISTERED', 'COMPLETED'])->default('QUICK_REGISTERED');
            
            $table->foreignId('order_id')->nullable()->constrained('purchasing_orders')->nullOnDelete();
            // Pemegang Aset Fisik dari HRIS
            $table->foreignId('pic_employee_id')->nullable()->constrained('hcm_employees')->nullOnDelete();
            $table->string('pic_employee_name', 150)->nullable();
            
            $table->string('asset_name', 150);
            $table->unsignedBigInteger('asset_category_id')->nullable();
            $table->text('specification')->nullable();
            $table->string('unit', 50)->default('Unit');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('location_id')->nullable();
            
            // Snapshot Unit Kerja
            $table->string('department', 100);
            $table->string('division', 100)->nullable();
            $table->string('position', 100);

            $table->foreignId('vendor_id')->nullable()->constrained('purchasing_vendors')->nullOnDelete();
            $table->string('supplier_name', 150)->nullable();
            $table->date('purchase_date');
            $table->date('received_date')->nullable();
            $table->decimal('acquisition_cost', 15, 2)->default(0.00);
            $table->string('warranty_duration', 50)->nullable();
            $table->date('warranty_expires_at')->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('barcode_qr_code', 100)->nullable();
            $table->enum('status', ['ACTIVE', 'MAINTENANCE', 'RETIRED'])->default('ACTIVE');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_code', 'department_code', 'year_code'], 'idx_pa_code_lookup');
            $table->index(['status', 'location_id'], 'idx_pa_status_loc');
            $table->index('pic_employee_id', 'idx_pa_pic_employee');
            $table->index('warranty_expires_at', 'idx_pa_warranty');
        });

        Schema::create('purchasing_asset_mutations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('asset_id')->constrained('purchasing_assets')->cascadeOnDelete();
            $table->string('old_asset_code', 50);
            $table->string('new_asset_code', 50);
            $table->string('from_department_code', 10);
            $table->string('to_department_code', 10);
            $table->string('from_department_name', 100);
            $table->string('to_department_name', 100);
            $table->date('mutation_date');
            $table->unsignedBigInteger('pic_user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('asset_id', 'idx_pam_asset');
        });

        Schema::create('purchasing_asset_retirements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('asset_id')->constrained('purchasing_assets')->cascadeOnDelete();
            $table->date('retired_at');
            $table->string('department', 100);
            $table->string('division', 100)->nullable();
            $table->string('position', 100);
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('retirement_reason', 150);
            $table->string('final_condition', 150);
            $table->string('disposal_method', 150);
            $table->decimal('book_value', 15, 2)->default(0.00);
            $table->decimal('disposal_price', 15, 2)->nullable();
            $table->unsignedBigInteger('finance_approved_by')->nullable();
            $table->timestamp('finance_approved_at')->nullable();
            $table->string('handover_document_path', 255)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('retired_at', 'idx_par_retired_at');
            $table->index('department', 'idx_par_dept');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchasing_asset_retirements');
        Schema::dropIfExists('purchasing_asset_mutations');
        Schema::dropIfExists('purchasing_assets');
    }
};
