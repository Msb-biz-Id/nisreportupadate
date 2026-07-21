<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        @php
            $appData = $page['props']['app'] ?? [];
            
            $themeColor = $appData['theme_color'] ?? \App\Models\Settings\SystemSetting::get('system', 'theme_color', '#a8001c');
            $faviconUrl = $appData['favicon_url'] ?? null;
            if (!$faviconUrl) {
                $faviconPath = \App\Models\Settings\SystemSetting::get('seo', 'favicon');
                $faviconUrl = $faviconPath ? \Illuminate\Support\Facades\Storage::url($faviconPath) : asset('favicon.ico');
            }
            
            $appName = $appData['name'] ?? \App\Models\Settings\SystemSetting::get('seo', 'site_name', config('app.name', 'Circle Sportwear - Tracking PO'));
            $metaDescription = $appData['description'] ?? \App\Models\Settings\SystemSetting::get('seo', 'site_description', 'Sistem tracking PO dan invoice secara aman dan privat.');

            // Convert hex to HSL
            $hex = ltrim($themeColor, '#');
            if (strlen($hex) == 3) {
                $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            }
            $r = hexdec(substr($hex, 0, 2)) / 255;
            $g = hexdec(substr($hex, 2, 2)) / 255;
            $b = hexdec(substr($hex, 4, 2)) / 255;
            $max = max($r, $g, $b);
            $min = min($r, $g, $b);
            $h = 0; $s = 0; $l = ($max + $min) / 2;
            if ($max != $min) {
                $d = $max - $min;
                $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
                if ($max == $r) {
                    $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
                } elseif ($max == $g) {
                    $h = ($b - $r) / $d + 2;
                } else {
                    $h = ($r - $g) / $d + 4;
                }
                $h /= 6;
            }
            $h = round($h * 360);
            $s = round($s * 100);
            $l = round($l * 100);
            
            $hslColor = "$h $s% $l%";
            $accentColor = "$h $s% 96%";
        @endphp
        <meta name="description" content="{{ $metaDescription }}">

        @if(\Illuminate\Support\Str::endsWith($faviconUrl, '.svg'))
            <link rel="icon" type="image/svg+xml" href="{{ $faviconUrl }}">
        @elseif(\Illuminate\Support\Str::endsWith($faviconUrl, '.png'))
            <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
        @else
            <link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}">
        @endif

        <!-- PWA Meta and Links -->
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="{{ $themeColor }}">
        <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

        <style>
            :root {
                --primary: {{ $hslColor }} !important;
                --ring: {{ $hslColor }} !important;
                --sidebar-accent: {{ $hslColor }} !important;
                --accent: {{ $accentColor }} !important;
                --accent-foreground: {{ $hslColor }} !important;
            }
        </style>

        <!-- Service Worker Registration & PWA Handler -->
        <script>
            window.deferredPwaPrompt = null;
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                window.deferredPwaPrompt = e;
            });

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

        <meta name="app-name" content="{{ $appName }}">
        <title inertia>{{ isset($title) ? "$title - $appName" : $appName }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;700&family=Noto+Sans+JP:wght@400;750&family=Noto+Sans+Javanese:wght@400;700&display=swap" rel="stylesheet">

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
