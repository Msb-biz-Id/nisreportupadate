<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah indikator visual ke paket_orders
        Schema::table('paket_orders', function (Blueprint $table) {
            $table->string('warna', 20)->default('#6B7280')->after('deskripsi');      // hex color
            $table->unsignedTinyInteger('prioritas')->default(0)->after('warna');     // 0=normal, 1=ekspress, 2=urgent
        });

        // Hubungkan paket_order ke orders
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('paket_order_id')->nullable()->after('sumber_order_id');
            $table->foreign('paket_order_id')->references('id')->on('paket_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['paket_order_id']);
            $table->dropColumn('paket_order_id');
        });
        Schema::table('paket_orders', function (Blueprint $table) {
            $table->dropColumn(['warna', 'prioritas']);
        });
    }
};
