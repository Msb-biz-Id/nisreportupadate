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
        // 1. Master Lowongan Kerja (Job Postings)
        Schema::create('hcm_job_postings', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('slug', 180)->unique();
            $table->string('department', 100);
            $table->string('position', 100);
            $table->string('job_type', 50)->default('Penuh Waktu (Full Time)');
            $table->string('location', 100)->default('Pabrik Klaten');
            $table->unsignedInteger('quota')->default(1);
            $table->string('min_education', 50)->default('SMA Sederajat');
            $table->unsignedInteger('min_experience_years')->default(0);
            $table->string('salary_range', 100)->nullable();
            $table->text('description');
            $table->text('requirements');
            $table->text('benefits')->nullable();
            $table->date('deadline')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('views_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'deadline']);
            $table->index('department');
        });

        // 2. Data Pelamar Kerja (Job Applicants) dengan field yang kompatibel 100% untuk konversi jadi Karyawan
        Schema::create('hcm_job_applicants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained('hcm_job_postings')->cascadeOnDelete();
            $table->string('applicant_code', 50)->unique(); // APL-YYYY-XXXX
            $table->string('name', 150);
            $table->string('nickname', 50)->nullable();
            $table->string('gender', 20)->default('Laki Laki');
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone_number', 25);
            $table->string('email', 100)->nullable();
            $table->string('education', 50)->default('SMA Sederajat');
            $table->string('major', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('nik_ktp', 20)->nullable();
            $table->string('marital_status', 30)->default('Belum Menikah');
            $table->string('shirt_size', 10)->default('L');
            $table->decimal('expected_salary', 15, 2)->nullable();
            $table->date('available_start_date')->nullable();
            $table->text('experience_summary')->nullable();
            $table->text('skills')->nullable();

            // Dokumen Unggahan Pelamar
            $table->string('resume_file_url', 255)->nullable();
            $table->string('ktp_file_url', 255)->nullable();
            $table->string('portfolio_file_url', 255)->nullable();
            $table->string('photo_url', 255)->nullable();

            // Pipeline Seleksi
            $table->string('status', 50)->default('SUBMITTED'); // SUBMITTED, SCREENING, INTERVIEW, ACCEPTED, REJECTED
            $table->dateTime('interview_date')->nullable();
            $table->string('interview_location', 200)->nullable();
            $table->text('interviewer_notes')->nullable();
            $table->text('rejection_reason')->nullable();

            // 1-Klik Konversi Pelamar ke Master Data Karyawan
            $table->foreignId('converted_employee_id')->nullable()->constrained('hcm_employees')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();

            $table->timestamps();

            $table->index(['job_posting_id', 'status']);
            $table->index('phone_number');
        });

        // 3. Buku Agenda Surat Masuk & Surat Keluar
        Schema::create('hcm_agenda_letters', function (Blueprint $table) {
            $table->id();
            $table->string('letter_type', 30); // SURAT_MASUK, SURAT_KELUAR, INTERNAL_MEMO, SK_DIREKSI
            $table->string('agenda_number', 50)->unique(); // AGD-YYYY-XXXX
            $table->string('letter_number', 100);
            $table->date('letter_date');
            $table->date('received_or_sent_date')->nullable();
            $table->string('sender', 150);
            $table->string('recipient', 150);
            $table->string('subject', 255);
            $table->string('category', 100)->nullable();
            $table->string('file_url', 255)->nullable();
            $table->string('physical_location', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['letter_type', 'letter_date']);
        });

        // 4. Dokumen Internal Perusahaan (SOP, Peraturan, Formulir)
        Schema::create('hcm_internal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_code', 50)->unique();
            $table->string('title', 200);
            $table->string('category', 100); // SOP, Peraturan Perusahaan, Formulir Standar, Dokumen K3
            $table->string('revision_number', 20)->default('Rev. 00');
            $table->date('effective_date');
            $table->string('status', 50)->default('Aktif');
            $table->string('file_url', 255)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['category', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hcm_internal_documents');
        Schema::dropIfExists('hcm_agenda_letters');
        Schema::dropIfExists('hcm_job_applicants');
        Schema::dropIfExists('hcm_job_postings');
    }
};
