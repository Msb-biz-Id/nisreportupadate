<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('activity', 30);        // create, update, delete, publish, login, export, etc.
            $table->string('module', 50);          // brand, user, order, refund, invoice, master, ai, settings
            $table->string('subject_type', 100)->nullable();  // App\Models\Order\Order
            $table->string('subject_id', 50)->nullable();     // id of subject (UUID or bigint as string)
            $table->string('description', 500)->nullable();
            $table->json('changes')->nullable();   // before/after diff atau metadata
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('brand_id');
            $table->index('module');
            $table->index('activity');
            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
