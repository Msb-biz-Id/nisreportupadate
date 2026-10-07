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
        // 1. Header Batch Penggajian Terpadu (Unified Payroll)
        Schema::create('hcm_payrolls', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('period_code', 50)->unique(); // e.g. PAY-2026-10
            $table->string('work_period_month', 7)->index(); // YYYY-MM (Bulan kinerja/kehadiran)
            $table->string('payout_period_month', 7)->index(); // YYYY-MM (Bulan realisasi pembayaran)
            $table->date('payout_date')->nullable(); // Tanggal transfer gaji
            $table->integer('total_employees')->default(0);
            $table->decimal('total_base_salary', 15, 2)->default(0);
            $table->decimal('total_meal_allowance', 15, 2)->default(0);
            $table->decimal('total_overtime_pay', 15, 2)->default(0);
            $table->decimal('total_adjustments', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('total_net_payout', 15, 2)->default(0);
            $table->string('status', 50)->default('DRAFT_HCM')->index(); // DRAFT_HCM, APPROVED_BY_HCM, APPROVED_BY_FINANCE, PAID_COMPLETED

            // Double Sign-Off HCM
            $table->foreignId('hcm_signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hcm_signed_at')->nullable();

            // Double Sign-Off Keuangan
            $table->foreignId('finance_signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finance_signed_at')->nullable();
            $table->string('payment_method', 50)->default('Transfer Bank'); // Transfer Bank, Kas Tunai
            $table->string('payment_proof_url', 255)->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Detail Item Penggajian per Karyawan (Multi-Level Grouping: Departemen -> Divisi)
        Schema::create('hcm_payroll_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('payroll_id')->constrained('hcm_payrolls')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hcm_employees')->cascadeOnDelete();

            // Struktur Organisasi Murni 2 Tingkat (Zero Posisi)
            $table->string('department', 100)->nullable()->index();
            $table->string('division', 100)->nullable()->index();
            $table->string('employment_status', 50)->nullable();
            $table->string('legal_entity', 100)->nullable();

            // Rekening Pembayaran Karyawan
            $table->string('bank_name', 50)->nullable();
            $table->string('bank_account_no', 50)->nullable();
            $table->string('bank_account_name', 100)->nullable();

            // Komponen Pendapatan (Earnings)
            $table->decimal('base_salary', 15, 2)->default(0);
            $table->decimal('meal_allowance', 15, 2)->default(0);
            $table->decimal('overtime_pay', 15, 2)->default(0);
            $table->decimal('increment_adjustment', 15, 2)->default(0);
            $table->decimal('total_earnings', 15, 2)->default(0);

            // Komponen Pemotongan Itemized (Deductions)
            $table->decimal('penalty_deduction', 15, 2)->default(0); // Sanksi / Pelanggaran
            $table->decimal('leave_deduction', 15, 2)->default(0); // Kelebihan Cuti
            $table->decimal('tiered_deduction', 15, 2)->default(0); // Cuti Khusus Berjenjang (Maternity)
            $table->decimal('total_deductions', 15, 2)->default(0);

            // Take-Home Pay Bersih
            $table->decimal('net_salary', 15, 2)->default(0);

            // Akses Slip Digital
            $table->string('slip_token', 64)->unique()->nullable();
            $table->boolean('is_paid')->default(false);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['payroll_id', 'employee_id']);
            $table->index(['department', 'division']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hcm_payroll_items');
        Schema::dropIfExists('hcm_payrolls');
    }
};
