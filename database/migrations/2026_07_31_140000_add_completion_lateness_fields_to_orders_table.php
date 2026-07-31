<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'was_delayed_on_completion')) {
                $table->boolean('was_delayed_on_completion')->default(false)->after('end_production_date');
            }
            if (!Schema::hasColumn('orders', 'days_late_on_completion')) {
                $table->unsignedInteger('days_late_on_completion')->default(0)->after('was_delayed_on_completion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'was_delayed_on_completion')) {
                $table->dropColumn('was_delayed_on_completion');
            }
            if (Schema::hasColumn('orders', 'days_late_on_completion')) {
                $table->dropColumn('days_late_on_completion');
            }
        });
    }
};
