<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Modul 11.4 — Rekap Hasil Wawancara Kandidat (plan/HRIS.md).
     * Satu pelamar dapat memiliki beberapa ronde wawancara (HRD, User, Manager).
     */
    public function up(): void
    {
        Schema::create('hcm_applicant_interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('hcm_job_applicants')->cascadeOnDelete();

            $table->string('interview_round', 50)->nullable();      // HRD, User, Manager, Final
            $table->string('interviewer_name', 100)->nullable();
            $table->date('interview_date')->nullable();
            $table->string('interview_result', 50)->nullable();     // Disarankan Diterima, Dipertimbangkan, Ditolak

            // Profil tersimpan saat wawancara (auto-isi dari data pelamar bila kosong)
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('marital_status', 30)->nullable();
            $table->string('education', 50)->nullable();
            $table->text('last_experience')->nullable();
            $table->string('daily_activity', 100)->nullable();
            $table->text('core_skills')->nullable();

            // Evaluasi & offering
            $table->decimal('salary_expectation', 15, 2)->nullable();
            $table->string('offering_status', 50)->nullable();      // Diterima (Join), Dipertimbangkan Kembali, Ditolak Pelamar, Pending
            $table->string('interview_decision', 50)->nullable();   // Diterima, Pending, Ditolak
            $table->text('offering_notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['applicant_id', 'interview_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hcm_applicant_interviews');
    }
};
