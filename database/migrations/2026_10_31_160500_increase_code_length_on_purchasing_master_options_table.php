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
        if (Schema::hasTable('purchasing_master_options') && Schema::hasColumn('purchasing_master_options', 'code')) {
            Schema::table('purchasing_master_options', function (Blueprint $table) {
                $table->string('code', 100)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('purchasing_master_options') && Schema::hasColumn('purchasing_master_options', 'code')) {
            Schema::table('purchasing_master_options', function (Blueprint $table) {
                $table->string('code', 50)->change();
            });
        }
    }
};
