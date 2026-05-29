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
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'iklan_id')) {
                $table->foreignUuid('iklan_id')->nullable()->after('sumber_order_id')
                    ->constrained('iklans')->nullOnDelete();
            } else {
                $table->foreign('iklan_id')->references('id')->on('iklans')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['iklan_id']);
            $table->dropColumn('iklan_id');
        });
    }
};
