<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Modul 12 — Status & Transisi Kepegawaian (Onboarding & Offboarding),
     * selaras plan/HRIS.md.
     */
    public function up(): void
    {
        // 1. Rekap Onboarding Karyawan Baru
        Schema::create('hcm_onboardings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hcm_employees')->cascadeOnDelete();
            $table->string('position', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->date('join_date')->nullable();
            // Kelengkapan berkas: { ktp, bpjs, kontrak, seragam } (boolean)
            $table->json('status_checklist')->nullable();
            $table->date('approved_date')->nullable();
            $table->timestamps();

            $table->unique('employee_id');
        });

        // 2. Rekap Offboarding Karyawan Keluar
        Schema::create('hcm_offboardings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hcm_employees')->cascadeOnDelete();
            $table->string('position', 100)->nullable();
            $table->date('exit_date')->nullable();
            $table->string('exit_reason', 150)->nullable();
            $table->string('notice_compliance', 100)->nullable();
            $table->string('rights_status', 100)->nullable();
            $table->string('asset_clearance', 100)->nullable();
            $table->string('clearance_status', 50)->nullable();
            $table->text('offboarding_notes')->nullable();
            $table->timestamps();

            $table->unique('employee_id');
            $table->index('exit_date');
        });

        // Backfill: buat rekap untuk data karyawan yang sudah ada.
        $now = now();

        $employees = DB::table('hcm_employees')->select([
            'id', 'position', 'department', 'join_date', 'is_active',
            'nik_ktp', 'bpjs_kesehatan_no', 'bpjs_ketenagakerjaan_no', 'shirt_size',
        ])->get();

        foreach ($employees as $e) {
            $checklist = [
                'ktp'     => !empty($e->nik_ktp) && !str_starts_with((string) $e->nik_ktp, 'AUTO-'),
                'bpjs'    => !empty($e->bpjs_kesehatan_no) || !empty($e->bpjs_ketenagakerjaan_no),
                'kontrak' => DB::table('hcm_contracts')->where('employee_id', $e->id)->exists(),
                'seragam' => !empty($e->shirt_size),
            ];
            $approvedDate = !in_array(false, $checklist, true) ? $e->join_date : null;

            DB::table('hcm_onboardings')->insert([
                'employee_id'      => $e->id,
                'position'         => $e->position,
                'department'       => $e->department,
                'join_date'        => $e->join_date,
                'status_checklist' => json_encode($checklist),
                'approved_date'    => $approvedDate,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);

            if (!$e->is_active) {
                DB::table('hcm_offboardings')->insert([
                    'employee_id'  => $e->id,
                    'position'     => $e->position,
                    'exit_date'    => $now->toDateString(),
                    'exit_reason'  => 'Habis kontrak',
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hcm_offboardings');
        Schema::dropIfExists('hcm_onboardings');
    }
};
