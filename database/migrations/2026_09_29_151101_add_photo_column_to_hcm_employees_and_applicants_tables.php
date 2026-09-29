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
        if (Schema::hasTable('hcm_employees') && !Schema::hasColumn('hcm_employees', 'photo')) {
            Schema::table('hcm_employees', function (Blueprint $table) {
                $table->string('photo', 255)->nullable()->after('is_active');
            });
        }

        if (Schema::hasTable('hcm_job_applicants') && !Schema::hasColumn('hcm_job_applicants', 'photo')) {
            Schema::table('hcm_job_applicants', function (Blueprint $table) {
                $table->string('photo', 255)->nullable()->after('portfolio_file_url');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('hcm_employees') && Schema::hasColumn('hcm_employees', 'photo')) {
            Schema::table('hcm_employees', function (Blueprint $table) {
                $table->dropColumn('photo');
            });
        }

        if (Schema::hasTable('hcm_job_applicants') && Schema::hasColumn('hcm_job_applicants', 'photo')) {
            Schema::table('hcm_job_applicants', function (Blueprint $table) {
                $table->dropColumn('photo');
            });
        }
    }
};
