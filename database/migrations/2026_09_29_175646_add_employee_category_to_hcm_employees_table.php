<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hcm_employees', function (Blueprint $table) {
            $table->string('employee_category', 30)->default('REGULAR')->after('employee_code');
            $table->index('employee_category');
        });

        // Update data eksisting: Kategori INTERN untuk yang berstatus/job_level Magang atau memiliki record di hcm_interns
        DB::table('hcm_employees')
            ->where('job_level', 'Magang')
            ->orWhere('employment_status', 'Magang')
            ->orWhereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('hcm_interns')
                    ->whereColumn('hcm_interns.employee_id', 'hcm_employees.id');
            })
            ->update(['employee_category' => 'INTERN']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hcm_employees', function (Blueprint $table) {
            $table->dropIndex(['employee_category']);
            $table->dropColumn('employee_category');
        });
    }
};
