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
            if (!Schema::hasColumn('hcm_employees', 'division')) {
                $table->string('division', 100)->nullable()->after('department');
            }
            if (!Schema::hasColumn('hcm_employees', 'original_join_date')) {
                $table->date('original_join_date')->nullable()->after('join_date');
            }
        });

        // Patch data historis: isi original_join_date dengan join_date jika masih null
        DB::table('hcm_employees')
            ->whereNull('original_join_date')
            ->whereNotNull('join_date')
            ->update([
                'original_join_date' => DB::raw('join_date'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hcm_employees', function (Blueprint $table) {
            $dropCols = [];
            if (Schema::hasColumn('hcm_employees', 'division')) {
                $dropCols[] = 'division';
            }
            if (Schema::hasColumn('hcm_employees', 'original_join_date')) {
                $dropCols[] = 'original_join_date';
            }
            if (!empty($dropCols)) {
                $table->dropColumn($dropCols);
            }
        });
    }
};
