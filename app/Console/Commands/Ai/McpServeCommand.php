<?php

namespace App\Console\Commands\Ai;

use App\Services\Ai\Mcp\McpServer;
use Illuminate\Console\Command;

class McpServeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:mcp:serve';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Garment OS MCP Server over standard I/O (stdio) for local Claude Desktop or OpenCode';

    /**
     * Execute the console command.
     */
    public function handle(McpServer $server): int
    {
        // Read line by line from stdin (JSON-RPC stdio protocol)
        while ($line = fgets(STDIN)) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $payload = json_decode($line, true);
            if (!is_array($payload)) {
                $errorResponse = [
                    'jsonrpc' => '2.0',
                    'id' => null,
                    'error' => [
                        'code' => -32700,
                        'message' => 'Parse error'
                    ]
                ];
                fwrite(STDOUT, json_encode($errorResponse) . "\n");
                fflush(STDOUT);
                continue;
            }

            $response = $server->handle($payload, null, '127.0.0.1', 'CLI-stdio');
            if (!empty($response)) {
                fwrite(STDOUT, json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
                fflush(STDOUT);
            }
        }

        return Command::SUCCESS;
    }
}
