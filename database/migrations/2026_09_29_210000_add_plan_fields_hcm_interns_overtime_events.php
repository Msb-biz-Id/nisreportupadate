<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kelengkapan mengikuti plan/HRIS.md:
     * - Modul 2 (Peserta Magang): kontak & alamat siswa.
     * - Modul 6 (Batch Lembur): bukti bayar (payout proof).
     * - Modul 14 (Company Events): penyelenggara, target peserta, reminder, ulang tahunan, lampiran undangan.
     */
    public function up(): void
    {
        Schema::table('hcm_interns', function (Blueprint $table) {
            $table->string('student_phone', 25)->nullable()->after('nis');
            $table->text('student_address')->nullable()->after('student_phone');
        });

        Schema::table('hcm_overtime_batches', function (Blueprint $table) {
            $table->string('payout_proof_url', 255)->nullable()->after('finance_notes');
        });

        Schema::table('hcm_company_events', function (Blueprint $table) {
            $table->string('organizer_name', 100)->nullable()->after('event_type');
            $table->string('target_audience', 100)->nullable()->default('Semua Tim')->after('location');
            $table->unsignedInteger('reminder_days')->nullable()->default(3)->after('target_audience');
            $table->boolean('is_annual_recurring')->default(false)->after('reminder_days');
            $table->string('invitation_file_url', 255)->nullable()->after('is_annual_recurring');
        });
    }

    public function down(): void
    {
        Schema::table('hcm_company_events', function (Blueprint $table) {
            $table->dropColumn(['organizer_name', 'target_audience', 'reminder_days', 'is_annual_recurring', 'invitation_file_url']);
        });

        Schema::table('hcm_overtime_batches', function (Blueprint $table) {
            $table->dropColumn('payout_proof_url');
        });

        Schema::table('hcm_interns', function (Blueprint $table) {
            $table->dropColumn(['student_phone', 'student_address']);
        });
    }
};
