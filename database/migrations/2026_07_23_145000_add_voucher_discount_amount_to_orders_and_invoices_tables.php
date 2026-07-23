<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('voucher_discount_amount', 15, 2)->default(0.00)->after('ongkir');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('voucher_discount_amount', 15, 2)->default(0.00)->after('biaya_pengiriman');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('voucher_discount_amount');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('voucher_discount_amount');
        });
    }
};
