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
        Schema::create('hcm_salary_deductions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('employee_id')->constrained('hcm_employees')->cascadeOnDelete();
            $table->string('effective_payroll_month', 7)->index(); // Format: YYYY-MM (e.g. 2026-10)
            $table->string('deduction_category', 100)->index(); // Pelanggaran (Disciplinary Penalty), Kelebihan Pengambilan Cuti (Leave Exceed), Cuti Khusus Berjenjang (Maternity Leave)
            $table->string('calculation_type', 20)->default('fixed'); // 'fixed' (nominal tetap), 'percent' (persentase dari gaji pokok)
            $table->decimal('percentage_rate', 5, 2)->nullable(); // e.g. 25.00, 50.00
            $table->decimal('deduction_amount', 12, 2); // Nominal potongan dalam Rupiah
            $table->decimal('base_salary_snapshot', 12, 2)->default(0); // Snapshot gaji pokok saat pemotongan dicatat
            $table->decimal('net_salary_snapshot', 12, 2)->default(0); // Proyeksi take-home pay setelah potongan
            $table->text('notes')->nullable(); // Alasan/uraian pemotongan
            $table->string('status', 50)->default('APPROVED')->index(); // 'PENDING', 'APPROVED', 'APPLIED', 'REJECTED'
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['effective_payroll_month', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hcm_salary_deductions');
    }
};
