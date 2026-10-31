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
        // 1. Log Presensi Harian Karyawan (Matrix & Daily Log)
        Schema::create('hcm_attendances', function (Blueprint $table) {
            $table->id();
            $table->date('attendance_date')->index();
            $table->foreignId('employee_id')->constrained('hcm_employees')->cascadeOnDelete();
            $table->string('position', 100)->nullable();
            $table->string('attendance_category', 50)->index(); // Hadir, Terlambat, Pulang Cepat, Cuti, Izin, Sakit, Dinas Luar, Alpha/Mangkir, Libur/Cuti Bersama
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            $table->text('notes')->nullable();
            $table->string('attachment_status', 30)->default('Tidak Terlampir'); // Terlampir, Tidak Terlampir
            $table->string('attachment_url', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['attendance_date', 'employee_id']);
        });

        // 2. Pengajuan Cuti / Izin / Sakit (Leave Requests & Workflow)
        Schema::create('hcm_leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hcm_employees')->cascadeOnDelete();
            $table->string('leave_type', 50); // Cuti Tahunan, Izin, Sakit, Dinas Luar, Cuti Bersama
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->integer('total_days')->default(1);
            $table->text('reason');
            $table->string('attachment_url', 255)->nullable();
            $table->string('status', 50)->default('PENDING_REVIEW')->index(); // PENDING_REVIEW, APPROVED, REJECTED
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hcm_leave_requests');
        Schema::dropIfExists('hcm_attendances');
    }
};
