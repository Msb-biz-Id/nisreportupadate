<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'hcm_contracts',
            'hcm_compensations',
            'hcm_leave_requests',
            'hcm_overtimes',
            'hcm_company_events',
            'hcm_employee_rewards',
            'hcm_external_letters',
            'hcm_master_options',
        ];

        foreach ($tables as $tbl) {
            if (Schema::hasTable($tbl) && !Schema::hasColumn($tbl, 'uuid')) {
                Schema::table($tbl, function (Blueprint $table) {
                    $table->uuid('uuid')->nullable()->index();
                });

                // Populate UUID for existing rows
                $rows = DB::table($tbl)->get(['id']);
                foreach ($rows as $row) {
                    DB::table($tbl)->where('id', $row->id)->update([
                        'uuid' => (string) Str::uuid(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'hcm_contracts',
            'hcm_compensations',
            'hcm_leave_requests',
            'hcm_overtimes',
            'hcm_company_events',
            'hcm_employee_rewards',
            'hcm_external_letters',
            'hcm_master_options',
        ];

        foreach ($tables as $tbl) {
            if (Schema::hasTable($tbl) && Schema::hasColumn($tbl, 'uuid')) {
                Schema::table($tbl, function (Blueprint $table) {
                    $table->dropColumn('uuid');
                });
            }
        }
    }
};
