<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_lunas')->default(false)->after('total_tagihan');
            $table->timestamp('lunas_at')->nullable()->after('is_lunas');
            $table->foreignId('lunas_by')->nullable()->constrained('users')->nullOnDelete()->after('lunas_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lunas_by');
            $table->dropColumn(['is_lunas', 'lunas_at']);
        });
    }
};
