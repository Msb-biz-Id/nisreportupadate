<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <meta name="description" content="{{ \App\Models\Settings\SystemSetting::get('seo', 'site_description', 'Sistem tracking PO dan invoice secara aman dan privat.') }}">

        @php
            $faviconPath = \App\Models\Settings\SystemSetting::get('seo', 'favicon');
            $faviconUrl = $faviconPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($faviconPath) : asset('favicon.ico');
        @endphp
        <link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}">

        <!-- PWA Meta and Links -->
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#3b82f6">
        <link rel="apple-touch-icon" href="/pwa-icon-192.png">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

        <!-- Service Worker Registration -->
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js')
                        .then((reg) => {
                            console.log('Service Worker registered successfully:', reg.scope);
                        })
                        .catch((err) => {
                            console.error('Service Worker registration failed:', err);
                        });
                });
            }
        </script>

        @php $appName = \App\Models\Settings\SystemSetting::get('seo', 'site_name', config('app.name', 'Circle Sportwear - Tracking PO')); @endphp
        <meta name="app-name" content="{{ $appName }}">
        <title inertia>{{ $appName }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;700&family=Noto+Sans+JP:wght@400;750&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
