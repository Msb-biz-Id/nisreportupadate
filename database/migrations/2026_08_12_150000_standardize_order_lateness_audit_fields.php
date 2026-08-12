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
            if (!Schema::hasColumn('orders', 'packing_completed_at')) {
                $table->timestamp('packing_completed_at')->nullable()->after('end_production_date');
            }
            if (!Schema::hasColumn('orders', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('packing_completed_at');
            }
            if (!Schema::hasColumn('orders', 'production_days_late')) {
                $table->integer('production_days_late')->default(0)->after('completed_at');
            }
            if (!Schema::hasColumn('orders', 'customer_days_late')) {
                $table->integer('customer_days_late')->default(0)->after('production_days_late');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'packing_completed_at',
                'completed_at',
                'production_days_late',
                'customer_days_late',
            ]);
        });
    }
};
