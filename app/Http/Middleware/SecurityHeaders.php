<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Mencegah Clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Mencegah MIME Sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // XSS Protection (untuk browser lama yang belum mendukung CSP penuh)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Content Security Policy dasar yang aman untuk Inertia & Vite dev server / production
        // Membuka koneksi ws/wss untuk hot-reload Vite
        $response->headers->set('Content-Security-Policy', "object-src 'none'; base-uri 'self'; frame-ancestors 'self';");

        return $response;
    }
}
