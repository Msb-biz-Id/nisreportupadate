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
        Schema::table('hcm_compensations', function (Blueprint $table) {
            $table->decimal('planned_increment', 12, 2)->nullable()->after('increment_3_amount');
            $table->string('decision_status', 50)->default('Sedang Diajukan')->after('planned_increment'); // 'Sedang Diajukan', 'Sudah Disetujui / ACC', 'Ditunda', 'Tidak Naik'
            $table->date('effective_date')->nullable()->after('decision_status');
            $table->date('custom_milestone_date')->nullable()->after('effective_date'); // Dukungan penundaan (custom milestone) tanpa merusak master siklus
            $table->text('decision_notes')->nullable()->after('custom_milestone_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hcm_compensations', function (Blueprint $table) {
            $table->dropColumn([
                'planned_increment',
                'decision_status',
                'effective_date',
                'custom_milestone_date',
                'decision_notes',
            ]);
        });
    }
};
