<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemasukan', function (Blueprint $table) {
            // Links auto-created pemasukan back to the order_payment that triggered it.
            // NULL for manually-created entries or pre-existing auto entries.
            $table->uuid('source_payment_id')->nullable()->after('invoice_id');
            $table->foreign('source_payment_id')->references('id')->on('order_payments')->nullOnDelete();
        });

        Schema::table('pengeluaran', function (Blueprint $table) {
            $table->uuid('source_payment_id')->nullable()->after('refund_id');
            $table->foreign('source_payment_id')->references('id')->on('order_payments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pemasukan', function (Blueprint $table) {
            $table->dropForeign(['source_payment_id']);
            $table->dropColumn('source_payment_id');
        });

        Schema::table('pengeluaran', function (Blueprint $table) {
            $table->dropForeign(['source_payment_id']);
            $table->dropColumn('source_payment_id');
        });
    }
};
