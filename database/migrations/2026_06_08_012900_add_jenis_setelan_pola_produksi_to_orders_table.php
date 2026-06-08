<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('jenis_setelan_id')->nullable()->after('paket_order_id');
            $table->uuid('pola_produksi_id')->nullable()->after('jenis_setelan_id');

            $table->foreign('jenis_setelan_id')->references('id')->on('jenis_setelan')->nullOnDelete();
            $table->foreign('pola_produksi_id')->references('id')->on('pola_produksi')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['jenis_setelan_id']);
            $table->dropForeign(['pola_produksi_id']);
            $table->dropColumn(['jenis_setelan_id', 'pola_produksi_id']);
        });
    }
};
