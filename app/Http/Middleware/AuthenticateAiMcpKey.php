<?php

namespace App\Http\Middleware;

use App\Models\Ai\AiMcpKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAiMcpKey
{
    /**
     * Handle an incoming request for AI / MCP endpoints.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        // Support token dari header custom atau query param (untuk SSE event-stream jika client tidak bisa kirim header)
        if (!$token) {
            $token = $request->header('X-MCP-API-KEY') ?? $request->query('api_key');
        }

        if (!$token) {
            return response()->json([
                'error' => 'Unauthorized: API Key is required. Format Bearer mcp_live_...',
                'code' => 401
            ], 401);
        }

        $hash = hash('sha256', $token);
        $apiKey = AiMcpKey::where('key_hash', $hash)->first();

        if (!$apiKey) {
            return response()->json([
                'error' => 'Unauthorized: Invalid API Key.',
                'code' => 401
            ], 401);
        }

        if (!$apiKey->is_active) {
            return response()->json([
                'error' => 'Forbidden: API Key has been revoked or deactivated.',
                'code' => 403
            ], 403);
        }

        if ($apiKey->expires_at && $apiKey->expires_at->isPast()) {
            return response()->json([
                'error' => 'Forbidden: API Key has expired.',
                'code' => 403
            ], 403);
        }

        // Shared Hosting Rate Limiting Protection (anti-DDoS / runaway loops)
        $rateLimitKey = "mcp_rate:{$apiKey->id}:" . date('YmdHi');
        $currentMinuteRequests = (int) Cache::get($rateLimitKey, 0);
        $maxPerMinute = $apiKey->rate_limit_per_minute ?? 60;

        if ($currentMinuteRequests >= $maxPerMinute) {
            return response()->json([
                'error' => 'Too Many Requests: Rate limit exceeded (' . $maxPerMinute . ' requests/minute). Please slow down to preserve shared hosting capacity.',
                'code' => 429
            ], 429);
        }

        Cache::put($rateLimitKey, $currentMinuteRequests + 1, 120);

        // Update stats asynchronously / lightweight
        $apiKey->recordUsage();

        // Attach to request
        $request->attributes->set('ai_mcp_key', $apiKey);

        return $next($request);
    }
}
