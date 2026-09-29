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
        Schema::create('hcm_company_events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('event_type', 50)->default('Acara Perusahaan');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('start_time', 10)->nullable();
            $table->string('end_time', 10)->nullable();
            $table->string('location', 200)->nullable();
            $table->text('description')->nullable();
            $table->string('color_code', 25)->default('#3b82f6');
            $table->boolean('is_public')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
            $table->index('event_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hcm_company_events');
    }
};
