<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_namesets', function (Blueprint $table) {
            $table->string('nama_dada', 100)->nullable()->after('nomor_punggung');
            $table->string('nomor_dada', 20)->nullable()->after('nama_dada');
            $table->string('nama_lengan', 100)->nullable()->after('nomor_dada');
            $table->string('nomor_lengan', 20)->nullable()->after('nama_lengan');
            $table->string('nomor_punggung_2', 20)->nullable()->after('nomor_lengan');
        });
    }

    public function down(): void
    {
        Schema::table('order_namesets', function (Blueprint $table) {
            $table->dropColumn(['nama_dada', 'nomor_dada', 'nama_lengan', 'nomor_lengan', 'nomor_punggung_2']);
        });
    }
};
