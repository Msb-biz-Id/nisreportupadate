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

        <meta name="app-name" content="{{ config('app.name', 'Laravel') }}">
        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|figtree:400,500,600&display=swap" rel="stylesheet" />

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
