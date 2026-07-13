<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyTurnstile
{
    /**
     * Handle an incoming request.
     * Validates Cloudflare Turnstile token before allowing login.
     * Can be disabled via TURNSTILE_ENABLED=false in .env
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if Turnstile is disabled
        if (! config('turnstile.enabled', false)) {
            return $next($request);
        }

        $token = $request->input('cf_turnstile_response');

        if (! $token) {
            return back()->withErrors([
                'cf_turnstile_response' => 'Verifikasi keamanan diperlukan. Harap selesaikan tantangan Turnstile.',
            ])->withInput($request->except('password'));
        }

        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret'   => config('turnstile.secret_key'),
            'response' => $token,
            'remoteip' => $request->ip(),
        ]);

        if (! $response->successful() || ! $response->json('success')) {
            Log::warning('Turnstile verification failed', [
                'ip'          => $request->ip(),
                'error_codes' => $response->json('error-codes', []),
            ]);

            return back()->withErrors([
                'cf_turnstile_response' => 'Verifikasi keamanan gagal. Silakan coba lagi.',
            ])->withInput($request->except('password'));
        }

        return $next($request);
    }
}
