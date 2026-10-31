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
        Schema::create('purchasing_asset_maintenances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('asset_id')->constrained('purchasing_assets')->cascadeOnDelete();
            $table->date('maintenance_date');
            $table->string('service_type', 100)->default('Perbaikan / Servis');
            $table->string('vendor_workshop', 150)->nullable();
            $table->text('issue_description');
            $table->text('action_taken')->nullable();
            $table->decimal('cost', 15, 2)->default(0.00);
            $table->enum('status', ['IN_PROGRESS', 'COMPLETED'])->default('COMPLETED');
            $table->unsignedBigInteger('performed_by_user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'maintenance_date'], 'idx_pam_asset_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchasing_asset_maintenances');
    }
};
