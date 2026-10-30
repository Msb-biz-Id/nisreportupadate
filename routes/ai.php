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
| SINGLE UNIVERSAL ENTRYPOINT:
| URL: https://yourdomain.com/api/ai/mcp
|
| - GET  /api/ai/mcp           => Auto OpenAPI 3.1 Spec (ChatGPT) / SSE Stream (Claude/Cursor)
| - POST /api/ai/mcp           => Standard JSON-RPC 2.0 Protocol (Semua AI Client)
| - GET  /api/ai/tools/{tool}  => Direct REST Tool Call (ChatGPT Actions)
|
*/

// ==================== 1. SINGLE UNIVERSAL GENERAL MCP URL ====================
Route::prefix('api/ai')->group(function () {
    // Single URL GET: otomatis mengenali apakah ChatGPT (OpenAPI) atau Claude (SSE)
    Route::get('mcp', [McpProtocolController::class, 'handleGeneralGet'])->name('ai.mcp.general');

    // Single URL POST: eksekusi JSON-RPC 2.0 resmi (dilindungi API Key)
    Route::post('mcp', [McpProtocolController::class, 'handleRpc'])
        ->middleware([AuthenticateAiMcpKey::class])
        ->name('ai.mcp.rpc');

    // REST Tool Execution (dilindungi API Key)
    Route::get('tools/{toolName}', [McpProtocolController::class, 'handleRestTool'])
        ->middleware([AuthenticateAiMcpKey::class])
        ->name('ai.tools.call');

    // Injeksi Skill & SOP Pabrik Garmen untuk AI / ChatGPT
    Route::get('skills', [McpProtocolController::class, 'handleSkillPrompt'])->name('ai.skills');

    // OPTIONS Preflight untuk browser / web client
    Route::options('mcp', fn () => response('', 200, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
        'Access-Control-Allow-Headers' => '*',
    ]));
    Route::options('mcp/sse', fn () => response('', 200, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
        'Access-Control-Allow-Headers' => '*',
    ]));
});

// ==================== 2. LEGACY / SPECIFIC SCHEMAS (BACKWARDS COMPATIBILITY) ====================
Route::prefix('api/ai/mcp')->name('ai.mcp.')->group(function () {
    Route::get('openapi.json', [UniversalSchemaController::class, 'openapi'])->name('openapi');
    Route::get('openai-tools.json', [UniversalSchemaController::class, 'openAiTools'])->name('openai_tools');
    Route::get('gemini-tools.json', [UniversalSchemaController::class, 'geminiTools'])->name('gemini_tools');
    Route::get('claude-desktop-config.json', [UniversalSchemaController::class, 'claudeDesktopConfig'])->name('claude_config');
    Route::get('sse', [McpProtocolController::class, 'handleSse'])->name('sse');
});

// ==================== 3. SUPERADMIN UI & MANAGEMENT ====================
Route::middleware(['auth', 'verified'])->prefix('settings/mcp')->name('settings.mcp.')->group(function () {
    Route::get('/', [SuperadminMcpController::class, 'index'])->name('index');
    Route::post('generate-key', [SuperadminMcpController::class, 'generateKey'])->name('generate_key');
    Route::put('update/{id}', [SuperadminMcpController::class, 'updateKey'])->name('update_key');
    Route::post('toggle/{id}', [SuperadminMcpController::class, 'toggleKey'])->name('toggle_key');
    Route::delete('revoke/{id}', [SuperadminMcpController::class, 'revokeKey'])->name('revoke_key');
    Route::post('clear-cache', [SuperadminMcpController::class, 'clearCache'])->name('clear_cache');
    Route::get('logs', [SuperadminMcpController::class, 'getLogs'])->name('logs');
});
