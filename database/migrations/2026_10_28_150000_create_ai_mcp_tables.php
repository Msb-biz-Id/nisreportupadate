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
        // 1. Materialized Snapshot Cache Table (Super Cepat < 1ms, Anti-N+1)
        if (!Schema::hasTable('ai_snapshots_cache')) {
            Schema::create('ai_snapshots_cache', function (Blueprint $table) {
                $table->id();
                $table->string('domain', 50)->comment('orders, finance, production, customer, executive');
                $table->string('scope_key', 100)->comment('all_brands, brand_1, weekly, daily');
                $table->longText('payload_json')->comment('JSON padat siap baca AI (token-dense)');
                $table->unsignedInteger('record_count')->default(0);
                $table->timestamp('generated_at')->useCurrent();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->unique(['domain', 'scope_key'], 'uk_ai_domain_scope');
                $table->index(['domain', 'expires_at'], 'idx_ai_domain_expires');
            });
        }

        // 2. Multi-AI API Keys Table (ChatGPT, Claude, Gemini, Cursor, OpenCode)
        if (!Schema::hasTable('ai_mcp_keys')) {
            Schema::create('ai_mcp_keys', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->comment('Nama Klien: misal ChatGPT Superadmin, Claude Desktop, Cursor');
                $table->string('key_hash', 64)->unique()->comment('SHA-256 Hash dari token');
                $table->string('key_prefix', 16)->comment('Prefix token: mcp_live_xxxx');
                $table->string('client_type', 50)->default('universal')->comment('chatgpt, claude, cursor, gemini, opencode, universal');
                $table->json('allowed_brands')->nullable()->comment('Array brand_id (null = semua brand)');
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index('is_active', 'idx_ai_key_active');
            });
        }

        // 3. Multi-AI Audit Log Table
        if (!Schema::hasTable('ai_mcp_logs')) {
            Schema::create('ai_mcp_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ai_mcp_key_id')->nullable()->constrained('ai_mcp_keys')->nullOnDelete();
                $table->string('client_type', 50)->nullable();
                $table->string('tool_name', 100);
                $table->json('arguments_json')->nullable();
                $table->unsignedInteger('execution_time_ms')->default(0);
                $table->enum('status', ['success', 'error'])->default('success');
                $table->text('error_message')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['tool_name'], 'idx_ai_log_tool');
                $table->index(['created_at'], 'idx_ai_log_created');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_mcp_logs');
        Schema::dropIfExists('ai_mcp_keys');
        Schema::dropIfExists('ai_snapshots_cache');
    }
};
