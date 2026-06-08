<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Bahan Kain multiple — atasan bisa kombinasi beberapa bahan
            $table->json('bahan_kain_ids')->nullable()->after('bahan_kain_id');
            // Bahan Bawahan multiple
            $table->json('bahan_kain_bawahan_ids')->nullable()->after('bahan_kain_bawahan_id');
            // Pola Jahitan dinamis: {"Kerah": "uuid", "Lengan": "uuid", ...}
            $table->json('pola_jahitan_config')->nullable()->after('pola_jahitan_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['bahan_kain_ids', 'bahan_kain_bawahan_ids', 'pola_jahitan_config']);
        });
    }
};
