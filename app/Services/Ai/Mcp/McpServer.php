<?php

namespace App\Services\Ai\Mcp;

use App\Models\Ai\AiMcpKey;
use App\Models\Ai\AiMcpLog;
use Illuminate\Support\Facades\Log;

class McpServer
{
    protected ToolRegistry $registry;

    public function __construct(ToolRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Memproses payload JSON-RPC 2.0
     */
    public function handle(array $payload, ?AiMcpKey $apiKey = null, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        $startTime = microtime(true);
        $jsonrpc = $payload['jsonrpc'] ?? '2.0';
        $id = $payload['id'] ?? null;
        $method = $payload['method'] ?? '';
        $params = $payload['params'] ?? [];

        if (empty($method)) {
            return $this->errorResponse($id, -32600, 'Invalid Request: Method is required.');
        }

        try {
            switch ($method) {
                case 'initialize':
                    $result = [
                        'protocolVersion' => '2024-11-05',
                        'capabilities' => [
                            'tools' => [
                                'listChanged' => false
                            ],
                            'prompts' => [
                                'listChanged' => false
                            ],
                            'resources' => [
                                'subscribe' => false,
                                'listChanged' => false
                            ]
                        ],
                        'serverInfo' => [
                            'name' => 'garment-os-mcp-server',
                            'version' => '2.0.0'
                        ],
                        'instructions' => 'Asisten pintar operasional pabrik garmen. Gunakan tool get_interactive_menu terlebih dahulu untuk mengetahui kategori dan perintah yang tersedia.'
                    ];
                    break;

                case 'notifications/initialized':
                    return []; // Notification does not require a response

                case 'ping':
                    $result = ['status' => 'pong'];
                    break;

                case 'tools/list':
                    $result = [
                        'tools' => $this->registry->getToolsForMcp()
                    ];
                    break;

                case 'tools/call':
                    $toolName = $params['name'] ?? '';
                    $arguments = $params['arguments'] ?? [];
                    $allowedScopes = $apiKey ? ($apiKey->scopes ?? ['*']) : ['*'];

                    $toolResult = $this->registry->executeTool($toolName, $arguments, $allowedScopes);
                    $result = $toolResult;

                    // Log audit execution jika ada API key
                    $this->recordLog($apiKey, $toolName, $arguments, $toolResult, $startTime, $ipAddress, $userAgent);
                    break;

                case 'prompts/list':
                    $result = [
                        'prompts' => $this->registry->getPromptsForMcp()
                    ];
                    break;

                case 'prompts/get':
                    $promptName = $params['name'] ?? '';
                    $result = $this->handlePromptGet($promptName);
                    break;

                case 'resources/list':
                    $result = [
                        'resources' => $this->registry->getResourcesForMcp()
                    ];
                    break;

                case 'resources/read':
                    $uri = $params['uri'] ?? '';
                    $result = $this->handleResourceRead($uri);
                    break;

                default:
                    return $this->errorResponse($id, -32601, "Method '{$method}' not found.");
            }

            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => $result
            ];
        } catch (\Throwable $e) {
            Log::error("MCP Server Error: {$e->getMessage()}", ['trace' => $e->getTraceAsString()]);
            return $this->errorResponse($id, -32603, 'Internal error: ' . $e->getMessage());
        }
    }

    protected function handlePromptGet(string $name): array
    {
        switch ($name) {
            case 'daily_executive_briefing':
                return [
                    'description' => 'Executive Briefing',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                'type' => 'text',
                                'text' => 'Gunakan tool get_executive_report, get_approaching_deadlines, dan get_unpaid_reminders untuk merangkum kondisi pabrik hari ini.'
                            ]
                        ]
                    ]
                ];

            case 'whatsapp_unpaid_reminders':
                return [
                    'description' => 'Draft WhatsApp Penagihan',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                'type' => 'text',
                                'text' => 'Gunakan tool get_unpaid_reminders untuk menampilkan daftar PO yang belum lunas dan buatkan draft pesan penagihan WhatsApp yang sopan.'
                            ]
                        ]
                    ]
                ];

            case 'production_bottleneck_audit':
                return [
                    'description' => 'Audit Beban Produksi',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                'type' => 'text',
                                'text' => 'Gunakan tool get_production_queue dan get_delayed_orders untuk menganalisis stasiun kerja mana yang paling menumpuk.'
                            ]
                        ]
                    ]
                ];

            default:
                return [
                    'description' => 'General Inquiry',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                'type' => 'text',
                                'text' => 'Panggil get_interactive_menu untuk melihat menu yang tersedia.'
                            ]
                        ]
                    ]
                ];
        }
    }

    protected function handleResourceRead(string $uri): array
    {
        if ($uri === 'garment://system/menu') {
            $toolResult = $this->registry->executeTool('get_interactive_menu', []);
            return [
                'contents' => [
                    [
                        'uri' => $uri,
                        'mimeType' => 'application/json',
                        'text' => json_encode($toolResult['raw_data'] ?? [], JSON_PRETTY_PRINT)
                    ]
                ]
            ];
        }

        if ($uri === 'garment://finance/unpaid-summary') {
            $toolResult = $this->registry->executeTool('get_unpaid_reminders', ['limit' => 5]);
            return [
                'contents' => [
                    [
                        'uri' => $uri,
                        'mimeType' => 'application/json',
                        'text' => json_encode($toolResult['raw_data']['summary'] ?? [], JSON_PRETTY_PRINT)
                    ]
                ]
            ];
        }

        return [
            'contents' => [
                [
                    'uri' => $uri,
                    'mimeType' => 'text/plain',
                    'text' => "Resource not found for URI: {$uri}"
                ]
            ]
        ];
    }

    protected function recordLog(?AiMcpKey $apiKey, string $toolName, array $arguments, array $result, float $startTime, ?string $ipAddress, ?string $userAgent): void
    {
        if (!$apiKey) {
            return;
        }

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);
        $isError = !empty($result['isError']);

        AiMcpLog::create([
            'ai_mcp_key_id' => $apiKey->id,
            'client_type' => $apiKey->client_type ?? 'mcp',
            'tool_name' => $toolName,
            'arguments_json' => $arguments,
            'status' => $isError ? 'error' : 'success',
            'execution_time_ms' => $durationMs,
            'ip_address' => $ipAddress,
            'user_agent' => substr((string)$userAgent, 0, 255),
            'error_message' => $isError ? ($result['content'][0]['text'] ?? 'Tool execution error') : null,
            'created_at' => now(),
        ]);
    }

    protected function errorResponse(string|int|null $id, int $code, string $message): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ];
    }
}
