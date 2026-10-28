<?php

namespace App\Console\Commands\Ai;

use App\Models\Ai\AiMcpKey;
use Illuminate\Console\Command;

class McpGenerateKeyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:mcp:key:generate 
                            {name : Name / label for the API Key}
                            {--client=custom : Client type (chatgpt, claude, gemini, opencode, cursor, custom)}
                            {--scopes=* : Allowed scopes or default *}
                            {--days= : Expiration in days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new API Key for AI Model Context Protocol (MCP)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $clientType = $this->option('client');
        $scopes = $this->option('scopes') ?: ['*'];
        $days = $this->option('days');

        $expiresAt = $days ? now()->addDays((int)$days) : null;

        $keyData = AiMcpKey::generateKey($name, $clientType, $scopes, null, $expiresAt);

        $this->info("✓ MCP API Key successfully generated!");
        $this->table(['Key Name', 'Client Type', 'Scopes', 'Expires At'], [
            [
                $name,
                $clientType,
                implode(', ', $scopes),
                $expiresAt ? $expiresAt->toDateTimeString() : 'Never'
            ]
        ]);

        $this->warn("IMPORTANT: Copy this token now. It will NOT be shown again:");
        $this->line("<fg=green;options=bold>{$keyData['plain_text_token']}</>");

        return Command::SUCCESS;
    }
}
