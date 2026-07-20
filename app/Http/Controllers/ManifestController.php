<?php

namespace App\Http\Controllers;

use App\Models\Settings\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ManifestController extends Controller
{
    public function index(Request $request)
    {
        // Ambil konfigurasi global dari pengaturan SEO & Branding
        $name = SystemSetting::get('seo', 'site_name', config('app.name', 'ProTrack'));
        $shortName = $name;
        $description = SystemSetting::get('seo', 'site_description', 'Sistem ERP dan CRM untuk tracking PO, Invoice, dan Analitik Pelanggan.');
        
        // Ambil warna tema sistem
        $themeColor = SystemSetting::get('system', 'theme_color', '#3b82f6');
        
        // Default icons fallback
        $iconUrl = '/pwa-icon-192.png';
        $icon512Url = '/pwa-icon-512.png';
        
        // Ambil logo global dari pengaturan SEO
        $logo = SystemSetting::get('seo', 'logo');
        if ($logo) {
            if (file_exists(public_path($logo))) {
                $iconUrl = asset($logo);
                $icon512Url = asset($logo);
            } else {
                $iconUrl = asset('storage/' . $logo);
                $icon512Url = $iconUrl;
            }
        }

        // Bersihkan URL dengan UrlHelper agar menjadi relatif root yang dinamis
        $iconUrl = \App\Support\UrlHelper::clean($iconUrl, $request);
        $icon512Url = \App\Support\UrlHelper::clean($icon512Url, $request);

        return response()->json([
            'name' => $name,
            'short_name' => $shortName,
            'description' => $description,
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#0f172a',
            'theme_color' => $themeColor,
            'orientation' => 'portrait',
            'icons' => [
                [
                    'src' => $iconUrl,
                    'sizes' => '192x192',
                    'type' => \Illuminate\Support\Str::endsWith($iconUrl, '.svg') ? 'image/svg+xml' : 'image/png',
                    'purpose' => 'any maskable'
                ],
                [
                    'src' => $icon512Url,
                    'sizes' => '512x512',
                    'type' => \Illuminate\Support\Str::endsWith($icon512Url, '.svg') ? 'image/svg+xml' : 'image/png',
                    'purpose' => 'any maskable'
                ]
            ]
        ])->header('Content-Type', 'application/json');
    }
}
