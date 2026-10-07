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
        if (Schema::hasTable('hcm_meal_allowance_items') && !Schema::hasColumn('hcm_meal_allowance_items', 'uuid')) {
            Schema::table('hcm_meal_allowance_items', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->index()->after('id');
            });

            // Populate UUID untuk baris yang ada
            $rows = DB::table('hcm_meal_allowance_items')->get(['id']);
            foreach ($rows as $row) {
                DB::table('hcm_meal_allowance_items')->where('id', $row->id)->update([
                    'uuid' => (string) Str::uuid(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('hcm_meal_allowance_items') && Schema::hasColumn('hcm_meal_allowance_items', 'uuid')) {
            try {
                Schema::table('hcm_meal_allowance_items', function (Blueprint $table) {
                    $table->dropIndex(['uuid']);
                });
            } catch (\Throwable $e) {
                // Ignore if index doesn't exist
            }

            Schema::table('hcm_meal_allowance_items', function (Blueprint $table) {
                $table->dropColumn('uuid');
            });
        }
    }
};
