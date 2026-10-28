<?php

namespace App\Http\Controllers\Ai\Mcp;

use App\Http\Controllers\Controller;
use App\Models\Ai\AiMcpKey;
use App\Models\Ai\AiMcpLog;
use App\Services\Ai\Mcp\McpServer;
use App\Services\Ai\Mcp\ToolRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class McpProtocolController extends Controller
{
    protected McpServer $server;
    protected ToolRegistry $registry;

    public function __construct(McpServer $server, ToolRegistry $registry)
    {
        $this->server = $server;
        $this->registry = $registry;
    }

    /**
     * Endpoint utama JSON-RPC 2.0 (POST /api/ai/mcp).
     */
    public function handleRpc(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        /** @var AiMcpKey|null $apiKey */
        $apiKey = $request->attributes->get('ai_mcp_key');

        if (empty($payload)) {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => null,
                'error' => [
                    'code' => -32700,
                    'message' => 'Parse error: Request body must be valid JSON-RPC 2.0.'
                ]
            ], 400);
        }

        // Support batch JSON-RPC request
        if (isset($payload[0]) && is_array($payload[0])) {
            $responses = [];
            foreach ($payload as $singleCall) {
                $responses[] = $this->server->handle(
                    $singleCall,
                    $apiKey,
                    $request->ip(),
                    $request->userAgent()
                );
            }
            return response()->json($responses);
        }

        $response = $this->server->handle(
            $payload,
            $apiKey,
            $request->ip(),
            $request->userAgent()
        );

        return response()->json($response);
    }

    /**
     * REST Tool Execution untuk ChatGPT Actions (GET /api/ai/tools/{toolName}).
     */
    public function handleRestTool(Request $request, string $toolName): JsonResponse
    {
        $startTime = microtime(true);
        /** @var AiMcpKey|null $apiKey */
        $apiKey = $request->attributes->get('ai_mcp_key');
        $params = $request->query();
        $allowedScopes = $apiKey ? ($apiKey->scopes ?? ['*']) : ['*'];

        $result = $this->registry->executeTool($toolName, $params, $allowedScopes);
        $durationMs = (int) round((microtime(true) - $startTime) * 1000);
        $isError = !empty($result['isError']);

        if ($apiKey) {
            AiMcpLog::create([
                'ai_mcp_key_id' => $apiKey->id,
                'client_type' => 'chatgpt_action',
                'tool_name' => $toolName,
                'arguments_json' => $params,
                'status' => $isError ? 'error' : 'success',
                'execution_time_ms' => $durationMs,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string)$request->userAgent(), 0, 255),
                'error_message' => $isError ? ($result['content'][0]['text'] ?? 'Execution error') : null,
                'created_at' => now(),
            ]);
        }

        if ($isError) {
            return response()->json($result, 400);
        }

        return response()->json($result['raw_data'] ?? $result);
    }

    /**
     * SSE (Server-Sent Events) untuk Claude Desktop / Cursor over HTTP (GET /api/ai/mcp/sse).
     */
    public function handleSse(Request $request): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($request) {
            // Heartbeat & initial endpoint connection
            echo "event: endpoint\n";
            echo "data: " . route('ai.mcp.rpc') . "\n\n";
            ob_flush();
            flush();

            // Keep connection alive with lightweight comments
            for ($i = 0; $i < 5; $i++) {
                if (connection_aborted()) {
                    break;
                }
                echo ": keepalive\n\n";
                ob_flush();
                flush();
                sleep(2);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache, no-transform');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }
}
