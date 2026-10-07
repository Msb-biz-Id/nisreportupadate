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
        Schema::create('hcm_office_exit_permits', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('employee_id')->constrained('hcm_employees')->cascadeOnDelete();
            $table->date('permit_date')->index();
            $table->string('exit_time', 10); // Format: HH:mm
            $table->string('return_time', 10)->nullable(); // Format: HH:mm
            $table->string('purpose', 255); // Keperluan izin keluar (Urusan Bank, Dinas Luar, Keperluan Keluarga, dsb)
            $table->text('notes')->nullable(); // Catatan tambahan atau rincian lokasi
            $table->string('attachment_status', 50)->default('Tidak Terlampir'); // 'Terlampir' / 'Tidak Terlampir'
            $table->string('attachment_url', 500)->nullable(); // URL bukti foto / scan surat tugas / struk
            $table->string('status', 50)->default('Masih di Luar'); // 'Masih di Luar', 'Kembali', 'Dibatalkan'
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['permit_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hcm_office_exit_permits');
    }
};
