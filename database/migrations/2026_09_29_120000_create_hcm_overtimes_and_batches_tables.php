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
        // 1. Batch Pencairan Lembur Mingguan (Cut-off Sabtu - Jumat, Pencairan Sabtu)
        Schema::create('hcm_overtime_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code', 50)->unique(); // e.g. OT-2026-W39
            $table->date('period_start')->index();
            $table->date('period_end')->index();
            $table->date('payout_date')->index();
            $table->decimal('total_hours', 8, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status', 50)->default('DRAFT')->index(); // DRAFT, APPROVED_BY_HCM, PAID_COMPLETED
            
            // Double Sign-Off HCM
            $table->foreignId('hcm_signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hcm_signed_at')->nullable();

            // Double Sign-Off Keuangan
            $table->foreignId('finance_signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finance_signed_at')->nullable();
            $table->string('payment_method', 50)->nullable(); // Kas Tunai, Transfer Bank
            $table->string('coa_code', 50)->nullable(); // Akun Akuntansi / COA (e.g. 5-50100)
            $table->text('finance_notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Rincian Lembur Harian Karyawan
        Schema::create('hcm_overtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->nullable()->constrained('hcm_overtime_batches')->nullOnDelete();
            $table->date('overtime_date')->index();
            $table->foreignId('employee_id')->constrained('hcm_employees')->cascadeOnDelete();
            $table->string('position', 100)->nullable();
            $table->string('day_type', 50); // Lembur Hari Kerja, Lembur Hari Libur
            $table->decimal('duration_hours', 4, 2); // e.g. 0.50, 1.00, 2.50
            $table->decimal('hourly_rate', 15, 2);
            $table->decimal('first_half_rate', 15, 2);
            $table->decimal('total_amount', 15, 2);
            $table->text('task_description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['overtime_date', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hcm_overtimes');
        Schema::dropIfExists('hcm_overtime_batches');
    }
};
