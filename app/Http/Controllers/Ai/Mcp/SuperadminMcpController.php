<?php

namespace App\Http\Controllers\Ai\Mcp;

use App\Http\Controllers\Controller;
use App\Models\Ai\AiMcpKey;
use App\Models\Ai\AiMcpLog;
use App\Models\Ai\AiSnapshotCache;
use App\Services\Ai\Mcp\McpSnapshotService;
use App\Services\Ai\Mcp\ToolRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SuperadminMcpController extends Controller
{
    protected ToolRegistry $registry;
    protected McpSnapshotService $snapshotService;

    public function __construct(ToolRegistry $registry, McpSnapshotService $snapshotService)
    {
        $this->registry = $registry;
        $this->snapshotService = $snapshotService;
    }

    /**
     * Halaman Utama Pengelolaan MCP & AI Multi-Client.
     */
    public function index(Request $request): Response
    {
        $keys = AiMcpKey::orderBy('created_at', 'desc')->get();

        $recentLogs = AiMcpLog::with('key:id,name,client_type')
            ->orderBy('id', 'desc')
            ->limit(100)
            ->get();

        $tools = $this->registry->getDefinitions();
        $formattedTools = [];
        foreach ($tools as $name => $tool) {
            $formattedTools[] = [
                'name' => $name,
                'description' => $tool['description'],
                'category' => $tool['category'],
                'scope' => $tool['scope']
            ];
        }

        $stats = [
            'total_keys' => $keys->count(),
            'active_keys' => $keys->where('is_active', true)->count(),
            'total_requests' => AiMcpLog::count(),
            'total_errors' => AiMcpLog::where('status', 'error')->count(),
            'cached_snapshots' => AiSnapshotCache::count(),
            'server_url' => $request->root()
        ];

        return Inertia::render('Settings/Mcp/Index', [
            'keys' => $keys,
            'recentLogs' => $recentLogs,
            'tools' => $formattedTools,
            'stats' => $stats,
            'flash' => [
                'created_token' => session('created_token'),
                'created_key_name' => session('created_key_name'),
                'success' => session('success'),
                'error' => session('error')
            ]
        ]);
    }

    /**
     * Generate API Key baru.
     */
    public function generateKey(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'client_type' => 'required|string|in:chatgpt,claude,gemini,opencode,cursor,custom',
            'scopes' => 'nullable|array',
            'rate_limit_per_minute' => 'nullable|integer|min:5|max:300',
            'expires_days' => 'nullable|integer|min:1|max:365'
        ]);

        $expiresAt = !empty($validated['expires_days'])
            ? now()->addDays((int) $validated['expires_days'])
            : null;

        $keyData = AiMcpKey::generateKey(
            $validated['name'],
            $validated['client_type'],
            $validated['scopes'] ?? ['*'],
            $request->user()?->id,
            $expiresAt
        );

        if (!empty($validated['rate_limit_per_minute'])) {
            $keyData['model']->update([
                'rate_limit_per_minute' => (int) $validated['rate_limit_per_minute']
            ]);
        }

        return redirect()->back()->with([
            'success' => 'API Key berhasil dibuat!',
            'created_token' => $keyData['plain_text_token'],
            'created_key_name' => $validated['name']
        ]);
    }

    /**
     * Aktifkan / Nonaktifkan API Key.
     */
    public function toggleKey(Request $request, int $id): RedirectResponse
    {
        $key = AiMcpKey::findOrFail($id);
        $key->update(['is_active' => !$key->is_active]);

        $status = $key->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "API Key '{$key->name}' berhasil {$status}.");
    }

    /**
     * Hapus / Revoke API Key permanen.
     */
    public function revokeKey(int $id): RedirectResponse
    {
        $key = AiMcpKey::findOrFail($id);
        $key->delete();

        return redirect()->back()->with('success', "API Key '{$key->name}' berhasil dihapus.");
    }

    /**
     * Reset / Flush Cache Snapshot data untuk memuat data terbaru segera.
     */
    public function clearCache(): RedirectResponse
    {
        $this->snapshotService->flushAllAiCache();

        return redirect()->back()->with('success', 'Seluruh snapshot cache AI & MCP berhasil di-refresh!');
    }

    /**
     * Fetch logs via JSON for live refresh / filtering.
     */
    public function getLogs(Request $request): JsonResponse
    {
        $query = AiMcpLog::with('key:id,name,client_type')
            ->orderBy('id', 'desc');

        if ($request->filled('key_id')) {
            $query->where('ai_mcp_key_id', $request->key_id);
        }

        if ($request->filled('tool_name')) {
            $query->where('tool_name', $request->tool_name);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $logs = $query->paginate(50);
        return response()->json($logs);
    }
}
