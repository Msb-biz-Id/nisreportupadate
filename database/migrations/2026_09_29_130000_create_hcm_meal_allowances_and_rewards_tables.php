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
        // 1. Batch Rekap Uang Makan Bulanan
        Schema::create('hcm_meal_allowance_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code', 50)->unique(); // e.g. MA-2026-09
            $table->unsignedTinyInteger('period_month')->index();
            $table->unsignedSmallInteger('period_year')->index();
            $table->date('period_start');
            $table->date('period_end');
            $table->date('payout_date');
            $table->integer('total_employees')->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('total_held_amount', 15, 2)->default(0);
            $table->string('status', 50)->default('DRAFT')->index(); // DRAFT, APPROVED_BY_HCM, PAID_COMPLETED

            // Double Sign-Off HCM
            $table->foreignId('hcm_signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hcm_signed_at')->nullable();

            // Double Sign-Off Keuangan
            $table->foreignId('finance_signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finance_signed_at')->nullable();
            $table->string('payment_method', 50)->nullable(); // Kas Tunai, Transfer Bank
            $table->string('coa_code', 50)->nullable(); // Akun COA (e.g. 5-50110)
            $table->text('finance_notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Rincian Uang Makan Per Karyawan (dengan Delay & Hold Logic & Sanksi Izin)
        Schema::create('hcm_meal_allowance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('hcm_meal_allowance_batches')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hcm_employees')->cascadeOnDelete();
            $table->string('position', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->decimal('base_allowance', 15, 2)->default(280000);
            $table->integer('present_days')->default(0);
            $table->integer('late_days')->default(0); // Terlambat >= 4x = Hold
            $table->integer('half_days')->default(0); // Masuk setengah hari = Potong
            $table->integer('alpha_days')->default(0); // Alpha = Potong
            $table->integer('leave_days')->default(0); // Cuti/Dinas luar = Tetap berhak penuh
            $table->integer('permit_days')->default(0); // Izin pribadi > 2x = Batal bonus bulanan
            $table->decimal('deduction_amount', 15, 2)->default(0);
            $table->decimal('previous_hold_amount', 15, 2)->default(0);
            $table->decimal('payable_amount', 15, 2)->default(0);
            $table->boolean('is_hold')->default(false); // Penangguhan uang makan bulan ini
            $table->boolean('bonus_eligible')->default(true); // Flag hak bonus bulanan
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'employee_id']);
        });

        // 3. Rekapitulasi Penyaluran Reward & Apresiasi Karyawan
        Schema::create('hcm_employee_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hcm_employees')->cascadeOnDelete();
            $table->string('position', 100)->nullable();
            $table->string('reward_name', 150); // e.g. Tiket Liburan, Mesin Cuci, Piagam Karyawan Teladan
            $table->integer('reward_year')->index();
            $table->string('distribution_status', 50)->default('Belum Diterima')->index(); // Belum Diterima, Sudah Diterima, Sudah Ditransfer, Tertunda / Pending, Dibatalkan
            $table->date('received_date')->nullable();
            $table->string('document_status', 50)->nullable();
            $table->decimal('budget_amount', 15, 2)->nullable();
            $table->string('proof_url', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hcm_employee_rewards');
        Schema::dropIfExists('hcm_meal_allowance_items');
        Schema::dropIfExists('hcm_meal_allowance_batches');
    }
};
