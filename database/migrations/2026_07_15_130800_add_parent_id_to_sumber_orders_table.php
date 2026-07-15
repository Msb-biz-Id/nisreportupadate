<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sumber_orders', function (Blueprint $table) {
            $table->uuid('parent_id')->nullable()->after('brand_id');
            $table->foreign('parent_id')->references('id')->on('sumber_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sumber_orders', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
