<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penyelarasan dengan plan/HRIS.md:
     * - Modul 8 (Dokumen Internal): register RAB/LPJ (Pengajuan & Realisasi).
     * - Modul 9 (Korespondensi Eksternal): status disposisi (unified di hcm_agenda_letters).
     * - Modul 11 (Rekrutmen): field loker & pipeline pelamar sesuai plan.
     */
    public function up(): void
    {
        Schema::table('hcm_internal_documents', function (Blueprint $table) {
            $table->string('document_stage', 20)->nullable()->default('Pengajuan')->after('title'); // Pengajuan | Realisasi
            $table->string('department', 100)->nullable()->after('category');
            $table->date('submission_date')->nullable()->after('effective_date');
            $table->date('approval_date')->nullable()->after('submission_date');
            $table->decimal('proposed_budget', 15, 2)->nullable()->after('revision_number');
            $table->decimal('actual_budget', 15, 2)->nullable()->after('proposed_budget');
        });

        Schema::table('hcm_agenda_letters', function (Blueprint $table) {
            $table->string('disposition_status', 100)->nullable()->after('category');
        });

        Schema::table('hcm_job_postings', function (Blueprint $table) {
            $table->string('job_code', 50)->nullable()->unique()->after('id');
            $table->string('legal_entity', 100)->nullable()->after('department');
            $table->unsignedInteger('fulfilled_count')->default(0)->after('quota');
            $table->decimal('salary_range_min', 15, 2)->nullable()->after('salary_range');
            $table->decimal('salary_range_max', 15, 2)->nullable()->after('salary_range_min');
            $table->date('start_date')->nullable()->after('deadline');
            $table->date('end_date')->nullable()->after('start_date');
            $table->string('recruitment_channel', 100)->nullable()->after('end_date');
            $table->unsignedBigInteger('recruiter_id')->nullable()->after('recruitment_channel');
            $table->string('status', 50)->nullable()->default('Aktif')->after('is_active');
        });

        Schema::table('hcm_job_applicants', function (Blueprint $table) {
            $table->date('apply_date')->nullable()->after('job_posting_id');
            $table->string('invitation_status', 50)->nullable()->default('Belum Diundang')->after('status');
            $table->string('interview_result', 50)->nullable()->after('invitation_status');
            $table->string('onboarding_attendance', 50)->nullable()->after('interview_result');
            $table->boolean('is_blacklisted')->default(false)->after('onboarding_attendance');
            $table->text('hcm_notes')->nullable()->after('is_blacklisted');
        });
    }

    public function down(): void
    {
        Schema::table('hcm_job_applicants', function (Blueprint $table) {
            $table->dropColumn(['apply_date', 'invitation_status', 'interview_result', 'onboarding_attendance', 'is_blacklisted', 'hcm_notes']);
        });

        Schema::table('hcm_job_postings', function (Blueprint $table) {
            $table->dropColumn(['job_code', 'legal_entity', 'fulfilled_count', 'salary_range_min', 'salary_range_max', 'start_date', 'end_date', 'recruitment_channel', 'recruiter_id', 'status']);
        });

        Schema::table('hcm_agenda_letters', function (Blueprint $table) {
            $table->dropColumn('disposition_status');
        });

        Schema::table('hcm_internal_documents', function (Blueprint $table) {
            $table->dropColumn(['document_stage', 'department', 'submission_date', 'approval_date', 'proposed_budget', 'actual_budget']);
        });
    }
};
