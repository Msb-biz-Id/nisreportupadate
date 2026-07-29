<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust semua proxy (termasuk nginx reverse proxy yang terminasi SSL)
        $middleware->trustProxies(at: '*');

        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/dashboard');

        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        // Register named middleware aliases
        $middleware->alias([
            'turnstile' => \App\Http\Middleware\VerifyTurnstile::class,
        ]);

        // Exclude Sidobe & Telegram webhook dari CSRF — endpoint public yang diakses server luar
        $middleware->validateCsrfTokens(except: [
            'webhooks/sidobe',
            'webhooks/telegram',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
