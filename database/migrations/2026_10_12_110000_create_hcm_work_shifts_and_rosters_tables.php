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
        // 1. Master Shift Kerja
        Schema::create('hcm_work_shifts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 100);
            $table->string('shift_code', 20)->unique();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedInteger('late_tolerance_minutes')->default(15);
            $table->string('color_code', 30)->default('#2563eb');
            $table->boolean('is_overnight')->default(false);
            $table->boolean('is_off_day')->default(false);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Tambah default_shift_id ke hcm_employees
        Schema::table('hcm_employees', function (Blueprint $table) {
            $table->foreignId('default_shift_id')
                ->nullable()
                ->after('employment_status')
                ->constrained('hcm_work_shifts')
                ->nullOnDelete();
        });

        // 3. Tambah shift_id ke hcm_attendances
        Schema::table('hcm_attendances', function (Blueprint $table) {
            $table->foreignId('shift_id')
                ->nullable()
                ->after('position')
                ->constrained('hcm_work_shifts')
                ->nullOnDelete();
        });

        // 4. Tabel Roster Penugasan Shift Karyawan
        Schema::create('hcm_shift_rosters', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('employee_id')->constrained('hcm_employees')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('hcm_work_shifts')->cascadeOnDelete();
            $table->date('roster_date')->index();
            $table->string('notes', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'roster_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hcm_shift_rosters');

        Schema::table('hcm_attendances', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropColumn('shift_id');
        });

        Schema::table('hcm_employees', function (Blueprint $table) {
            $table->dropForeign(['default_shift_id']);
            $table->dropColumn('default_shift_id');
        });

        Schema::dropIfExists('hcm_work_shifts');
    }
};
