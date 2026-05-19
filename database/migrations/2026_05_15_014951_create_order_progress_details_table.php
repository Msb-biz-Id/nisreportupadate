<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_progress_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignUuid('progress_id')->constrained('progress')->cascadeOnDelete();
            $table->enum('status', ['pending', 'on_progress', 'selesai', 'skipped'])->default('pending');
            $table->text('catatan')->nullable();
            $table->text('kendala')->nullable();
            $table->boolean('has_reject')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('skipped_reason')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['order_id', 'progress_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_progress_details');
    }
};
