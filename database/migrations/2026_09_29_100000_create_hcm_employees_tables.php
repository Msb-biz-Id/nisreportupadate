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
        // 1. Master Karyawan
        Schema::create('hcm_employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 50)->unique();
            $table->string('name', 150);
            $table->string('nickname', 50);
            $table->string('department', 100);       // Divisi Kerja (e.g. Produksi, Marketing, HCM, Keuangan)
            $table->string('position', 100);         // Posisi/Jabatan (e.g. PIC Produksi, Potong Bahan, Jahit)
            $table->string('job_level', 50);         // Level/Jenjang (e.g. Managerial, Kontrak, Borongan, Magang)
            $table->string('employment_status', 50); // Status Ketenagakerjaan (Karyawan Tetap, PKWT, PKWT Lanjutan, Borongan, Magang)
            $table->string('legal_entity', 100)->nullable(); // Badan Usaha (e.g. CV Bawang Merah, CV Bawang Putih, dll)
            $table->string('phone_number', 25);
            $table->string('gender', 20);            // Laki Laki, Perempuan
            $table->string('religion', 30)->default('Islam');
            $table->string('education', 50)->default('SMA Sederajat');
            $table->string('marital_status', 30)->default('Belum Menikah');
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('nik_ktp', 20)->unique();
            $table->string('bpjs_kesehatan_no', 50)->nullable();
            $table->string('bpjs_ketenagakerjaan_no', 50)->nullable();
            $table->string('shirt_size', 10)->default('L');
            $table->text('address')->nullable();
            $table->string('bank_account_no', 50)->nullable();
            $table->string('bank_name', 50)->default('Bank BRI');
            $table->string('email', 100)->nullable();
            $table->date('join_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('photo_url', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'department']);
            $table->index('job_level');
            $table->index('employment_status');
        });

        // 2. Data Peserta Magang SMK (1-to-1 dengan hcm_employees)
        Schema::create('hcm_interns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hcm_employees')->cascadeOnDelete();
            $table->string('school_name', 150);      // SMK 2 Lamongan
            $table->string('class', 20)->default('XII'); // X, XI, XII
            $table->string('major', 100)->nullable(); // Tata Busana, RPL, dll
            $table->string('nis', 50)->nullable();    // Nomor Induk Siswa
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('duration_text', 50)->nullable(); // e.g. 3 Bulan
            $table->string('mentor_teacher_name', 100)->nullable(); // Bu. Ningsih
            $table->string('mentor_teacher_phone', 25)->nullable();
            $table->timestamps();
        });

        // 3. Kontrak & Legalitas PKWT (1-to-many dengan hcm_employees)
        Schema::create('hcm_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hcm_employees')->cascadeOnDelete();
            $table->string('contract_number', 100)->unique(); // XXX/OWR/PKWT/X/XXXX
            $table->unsignedInteger('contract_sequence')->default(1); // 1, 2, 3
            $table->string('employment_status', 50); // Karyawan Tetap, PKWT, PKWT Lanjutan
            $table->string('position', 100)->nullable();
            $table->string('legal_entity', 100);     // CV Bawang Merah, dll
            $table->string('duration_text', 50)->default('1 Tahun'); // Tetap, 1 Tahun, 2 Tahun
            $table->string('trainee_start_month', 50)->nullable();
            $table->string('trainee_end_month', 50)->nullable();
            $table->string('contract_month', 50)->nullable();
            $table->unsignedSmallInteger('start_year')->default(2026);
            $table->string('end_year', 10)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('review_status', 100)->default('Aktif'); // Aktif, Mendekati H-60, Wajib Review H-30, dll
            $table->string('file_contract_url', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'contract_sequence']);
            $table->index('review_status');
        });

        // 4. Data Kompensasi & Gaji/Honor (1-to-1 dengan hcm_employees)
        Schema::create('hcm_compensations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hcm_employees')->cascadeOnDelete();
            $table->string('employment_status', 50);
            $table->string('legal_entity', 100);
            $table->string('contract_number', 100)->nullable();
            $table->string('duration_text', 50)->nullable();
            $table->unsignedInteger('trainee_duration_months')->nullable(); // 8, 12, 24 bulan
            $table->unsignedInteger('evaluation_cycle_months')->default(6); // 4 atau 6 bulan
            $table->decimal('initial_salary', 15, 2)->default(0);
            $table->decimal('current_salary', 15, 2)->default(0);
            $table->unsignedInteger('salary_increment_count')->default(0);
            $table->decimal('increment_1_amount', 15, 2)->nullable();
            $table->decimal('increment_2_amount', 15, 2)->nullable();
            $table->decimal('increment_3_amount', 15, 2)->nullable();
            $table->string('salary_status', 100)->default('Telah Berlaku');
            $table->timestamps();
        });

        // 5. Histori Kenaikan Honor / Gaji
        Schema::create('hcm_compensation_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compensation_id')->constrained('hcm_compensations')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hcm_employees')->cascadeOnDelete();
            $table->decimal('previous_salary', 15, 2);
            $table->decimal('new_salary', 15, 2);
            $table->decimal('increment_amount', 15, 2);
            $table->date('effective_date');
            $table->string('reason', 255)->nullable(); // Evaluasi Siklus 6 Bulan
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hcm_compensation_histories');
        Schema::dropIfExists('hcm_compensations');
        Schema::dropIfExists('hcm_contracts');
        Schema::dropIfExists('hcm_interns');
        Schema::dropIfExists('hcm_employees');
    }
};
