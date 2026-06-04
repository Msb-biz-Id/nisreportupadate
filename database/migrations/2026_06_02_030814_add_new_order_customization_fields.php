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
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignUuid('bahan_kain_bawahan_id')->nullable()->constrained('bahan_kains')->nullOnDelete()->after('bahan_kain_id');
            $table->string('gambar_ket_tambahan')->nullable()->after('gambar_kerah');
        });

        Schema::table('order_namesets', function (Blueprint $table) {
            $table->string('nama_punggung_2', 100)->nullable()->after('nomor_punggung_2');
            $table->foreignUuid('size_celana_id')->nullable()->constrained('sizes')->nullOnDelete()->after('size_label');
            $table->string('size_celana_label', 50)->nullable()->after('size_celana_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_namesets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('size_celana_id');
            $table->dropColumn(['nama_punggung_2', 'size_celana_label']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bahan_kain_bawahan_id');
            $table->dropColumn(['gambar_ket_tambahan']);
        });
    }
};
