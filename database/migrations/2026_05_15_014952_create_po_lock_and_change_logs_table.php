<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_lock_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->boolean('is_locked')->default(true);
            $table->timestamp('locked_at');
            $table->foreignId('locked_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('po_change_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->text('change_reason');
            $table->string('field_changed', 100);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_change_logs');
        Schema::dropIfExists('po_lock_status');
    }
};
