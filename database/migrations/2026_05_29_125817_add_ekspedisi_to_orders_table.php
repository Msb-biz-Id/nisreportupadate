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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('nama_ekspedisi', 100)->nullable()->after('end_production_date');
            $table->string('no_resi', 100)->nullable()->after('nama_ekspedisi');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['nama_ekspedisi', 'no_resi']);
        });
    }
};
