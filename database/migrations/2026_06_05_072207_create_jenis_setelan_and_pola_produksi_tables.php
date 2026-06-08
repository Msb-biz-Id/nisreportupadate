<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_setelan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama', 100);
            $table->string('deskripsi', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pola_produksi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama', 100);
            $table->string('deskripsi', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Tambah FK ke order_items
        Schema::table('order_items', function (Blueprint $table) {
            $table->uuid('jenis_setelan_id')->nullable()->after('jenis_setelan');
            $table->uuid('pola_produksi_id')->nullable()->after('pola');
            $table->foreign('jenis_setelan_id')->references('id')->on('jenis_setelan')->nullOnDelete();
            $table->foreign('pola_produksi_id')->references('id')->on('pola_produksi')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['jenis_setelan_id']);
            $table->dropForeign(['pola_produksi_id']);
            $table->dropColumn(['jenis_setelan_id', 'pola_produksi_id']);
        });
        Schema::dropIfExists('jenis_setelan');
        Schema::dropIfExists('pola_produksi');
    }
};
