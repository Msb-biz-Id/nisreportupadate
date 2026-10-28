<?php

use App\Http\Controllers\Ai\Mcp\McpProtocolController;
use App\Http\Controllers\Ai\Mcp\SuperadminMcpController;
use App\Http\Controllers\Ai\Mcp\UniversalSchemaController;
use App\Http\Middleware\AuthenticateAiMcpKey;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AI & Model Context Protocol (MCP) Routes
|--------------------------------------------------------------------------
|
| Terisolasi penuh untuk integrasi multi-AI: ChatGPT Actions, Claude Desktop,
| Google Gemini, Cursor, Cline, dan OpenCode.
|
*/

// ==================== UNIVERSAL SCHEMAS (PUBLIC / INTEGRATION BUILDER) ====================
Route::prefix('api/ai/mcp')->name('ai.mcp.')->group(function () {
    Route::get('openapi.json', [UniversalSchemaController::class, 'openapi'])->name('openapi');
    Route::get('openai-tools.json', [UniversalSchemaController::class, 'openAiTools'])->name('openai_tools');
    Route::get('gemini-tools.json', [UniversalSchemaController::class, 'geminiTools'])->name('gemini_tools');
    Route::get('claude-desktop-config.json', [UniversalSchemaController::class, 'claudeDesktopConfig'])->name('claude_config');
});

// ==================== MCP PROTOCOL & REST EXECUTION (PROTECTED BY API KEY) ====================
Route::prefix('api/ai')->middleware([AuthenticateAiMcpKey::class])->name('ai.')->group(function () {
    // Standard MCP Protocol JSON-RPC 2.0
    Route::post('mcp', [McpProtocolController::class, 'handleRpc'])->name('mcp.rpc');
    Route::get('mcp/sse', [McpProtocolController::class, 'handleSse'])->name('mcp.sse');

    // REST Tool Execution (Untuk ChatGPT Actions / Direct HTTP Tools)
    Route::get('tools/{toolName}', [McpProtocolController::class, 'handleRestTool'])->name('tools.call');
});

// ==================== SUPERADMIN UI & MANAGEMENT ====================
Route::middleware(['auth', 'verified'])->prefix('settings/mcp')->name('settings.mcp.')->group(function () {
    Route::get('/', [SuperadminMcpController::class, 'index'])->name('index');
    Route::post('generate-key', [SuperadminMcpController::class, 'generateKey'])->name('generate_key');
    Route::post('toggle/{id}', [SuperadminMcpController::class, 'toggleKey'])->name('toggle_key');
    Route::delete('revoke/{id}', [SuperadminMcpController::class, 'revokeKey'])->name('revoke_key');
    Route::post('clear-cache', [SuperadminMcpController::class, 'clearCache'])->name('clear_cache');
    Route::get('logs', [SuperadminMcpController::class, 'getLogs'])->name('logs');
});
