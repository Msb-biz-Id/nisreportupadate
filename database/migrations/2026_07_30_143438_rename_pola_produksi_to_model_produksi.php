<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop Foreign Keys in order_items and orders
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['pola_produksi_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['pola_produksi_id']);
        });

        // 2. Rename table pola_produksi to model_produksi
        Schema::rename('pola_produksi', 'model_produksi');

        // 3. Rename columns in orders and order_items
        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('pola_produksi_id', 'model_produksi_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->renameColumn('pola_produksi_id', 'model_produksi_id');
            $table->renameColumn('pola', 'model');
        });

        // 4. Recreate Foreign Keys referencing the renamed table
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('model_produksi_id')->references('id')->on('model_produksi')->nullOnDelete();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('model_produksi_id')->references('id')->on('model_produksi')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // 1. Drop Foreign Keys
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['model_produksi_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['model_produksi_id']);
        });

        // 2. Rename columns back
        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('model_produksi_id', 'pola_produksi_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->renameColumn('model_produksi_id', 'pola_produksi_id');
            $table->renameColumn('model', 'pola');
        });

        // 3. Rename table model_produksi back to pola_produksi
        Schema::rename('model_produksi', 'pola_produksi');

        // 4. Recreate old Foreign Keys
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('pola_produksi_id')->references('id')->on('pola_produksi')->nullOnDelete();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('pola_produksi_id')->references('id')->on('pola_produksi')->nullOnDelete();
        });
    }
};
