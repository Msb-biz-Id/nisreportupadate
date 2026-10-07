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
        // 1. Tambah parent_id pada hcm_master_options untuk hierarki dinamis (Divisi -> Departemen)
        if (!Schema::hasColumn('hcm_master_options', 'parent_id')) {
            Schema::table('hcm_master_options', function (Blueprint $table) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('category_id')
                    ->constrained('hcm_master_options')
                    ->nullOnDelete();
            });
        }

        // 2. Buat kolom position pada hcm_employees menjadi nullable
        Schema::table('hcm_employees', function (Blueprint $table) {
            $table->string('position', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('hcm_master_options', 'parent_id')) {
            Schema::table('hcm_master_options', function (Blueprint $table) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            });
        }
    }
};
