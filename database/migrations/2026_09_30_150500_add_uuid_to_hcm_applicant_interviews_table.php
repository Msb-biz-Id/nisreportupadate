<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hcm_applicant_interviews') && !Schema::hasColumn('hcm_applicant_interviews', 'uuid')) {
            Schema::table('hcm_applicant_interviews', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->index();
            });

            $rows = DB::table('hcm_applicant_interviews')->get(['id']);
            foreach ($rows as $row) {
                DB::table('hcm_applicant_interviews')->where('id', $row->id)->update([
                    'uuid' => (string) Str::uuid(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('hcm_applicant_interviews') && Schema::hasColumn('hcm_applicant_interviews', 'uuid')) {
            Schema::table('hcm_applicant_interviews', function (Blueprint $table) {
                $table->dropColumn('uuid');
            });
        }
    }
};
