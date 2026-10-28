<?php

namespace App\Http\Controllers\Ai\Mcp;

use App\Http\Controllers\Controller;
use App\Services\Ai\Mcp\ToolRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UniversalSchemaController extends Controller
{
    protected ToolRegistry $registry;

    public function __construct(ToolRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * OpenAPI 3.1 Specification untuk ChatGPT Custom GPTs / Actions.
     */
    public function openapi(Request $request): JsonResponse
    {
        $serverUrl = $request->root();
        $schema = $this->registry->getOpenApiSchema($serverUrl);

        return response()->json($schema, 200, [
            'Access-Control-Allow-Origin' => '*',
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * OpenAI Functions / Tools Schema format.
     */
    public function openAiTools(Request $request): JsonResponse
    {
        return response()->json([
            'tools' => $this->registry->getOpenAiTools()
        ], 200, [
            'Access-Control-Allow-Origin' => '*'
        ]);
    }

    /**
     * Google Gemini Function Declarations Schema format.
     */
    public function geminiTools(Request $request): JsonResponse
    {
        return response()->json(
            $this->registry->getGeminiDeclarations(),
            200,
            ['Access-Control-Allow-Origin' => '*']
        );
    }

    /**
     * Snippet konfigurasi Claude Desktop claude_desktop_config.json.
     */
    public function claudeDesktopConfig(Request $request): JsonResponse
    {
        $serverUrl = $request->root();
        $config = [
            'mcpServers' => [
                'garment-os' => [
                    'command' => 'npx',
                    'args' => [
                        '-y',
                        'mcp-remote',
                        "{$serverUrl}/api/ai/mcp/sse",
                        '--header',
                        'Authorization: Bearer YOUR_MCP_API_KEY'
                    ]
                ]
            ]
        ];

        return response()->json($config, 200, [
            'Access-Control-Allow-Origin' => '*'
        ]);
    }
}
