<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['printing_id']);
            $table->dropColumn('printing_id');
            $table->json('printing_ids')->nullable()->after('sumber_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('printing_ids');
            $table->foreignUuid('printing_id')->nullable()->constrained('printings')->nullOnDelete()->after('sumber_order_id');
        });
    }
};
