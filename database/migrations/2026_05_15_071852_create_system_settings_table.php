<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Generic key-value config store. `group` membantu pengelompokan UI.
     * Sensitive values di-encrypt via Crypt facade di model accessor.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->index();
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['group', 'key']);
        });

        Schema::create('ai_tool_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tool_slug', 50);
            $table->json('input');
            $table->longText('output')->nullable();
            $table->integer('tokens_used')->nullable();
            $table->string('model', 50)->nullable();
            $table->string('status', 20)->default('success');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('tool_slug');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_tool_logs');
        Schema::dropIfExists('system_settings');
    }
};
