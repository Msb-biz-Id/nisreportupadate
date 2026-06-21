<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('reseller_display_brand_id')->nullable()->after('brand_id');
            $table->foreign('reseller_display_brand_id')
                  ->references('id')
                  ->on('brands')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['reseller_display_brand_id']);
            $table->dropColumn('reseller_display_brand_id');
        });
    }
};
