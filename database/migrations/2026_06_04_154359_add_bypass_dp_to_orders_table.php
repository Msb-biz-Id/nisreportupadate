<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_dp_bypassed')->default(false)->after('status_po');
            $table->foreignId('dp_bypassed_by')->nullable()->after('is_dp_bypassed')->constrained('users')->nullOnDelete();
            $table->timestamp('dp_bypassed_at')->nullable()->after('dp_bypassed_by');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['dp_bypassed_by']);
            $table->dropColumn(['is_dp_bypassed', 'dp_bypassed_by', 'dp_bypassed_at']);
        });
    }
};
