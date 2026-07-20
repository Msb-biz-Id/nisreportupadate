<?php

namespace App\Http\Controllers;

use App\Support\BrandContext;
use Illuminate\Http\Request;

class ManifestController extends Controller
{
    public function index(Request $request)
    {
        $brand = BrandContext::currentBrand($request);
        
        $name = $brand ? $brand->nama_brand : config('app.name', 'ProTrack');
        $shortName = $brand ? $brand->kode : config('app.name', 'ProTrack');
        $themeColor = $brand ? ($brand->warna_primary ?: '#3b82f6') : '#3b82f6';
        
        // Default icons
        $iconUrl = '/pwa-icon-192.png';
        $icon512Url = '/pwa-icon-512.png';
        
        // If brand has custom logo, use it dynamically for PWA icons
        if ($brand && $brand->logo) {
            $publicDisk = \Illuminate\Support\Facades\Storage::disk('public');
            $resolvedUrl = \App\Support\UrlHelper::clean(
                \Illuminate\Support\Str::contains($brand->logo, 'http') 
                    ? $brand->logo 
                    : $publicDisk->url($brand->logo)
            );
            $iconUrl = $resolvedUrl;
            $icon512Url = $resolvedUrl;
        }

        return response()->json([
            'name' => $name,
            'short_name' => $shortName,
            'description' => "Sistem ERP dan CRM untuk tracking PO, Invoice, dan Analitik Pelanggan.",
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#0f172a',
            'theme_color' => $themeColor,
            'orientation' => 'portrait',
            'icons' => [
                [
                    'src' => $iconUrl,
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ],
                [
                    'src' => $icon512Url,
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ]
            ]
        ])->header('Content-Type', 'application/json');
    }
}
