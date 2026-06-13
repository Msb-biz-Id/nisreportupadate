<?php

namespace App\Http\Middleware;

use App\Models\Brand;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $currentBrand = null;
        $availableBrands = [];
        $userRoles = [];
        $userPermissions = [];

        if ($user) {
            $userRoles = $user->getRoleNames()->all();
            $userPermissions = $user->getAllPermissions()->pluck('name')->all();

            $canSeeAllGlobalBrands = $user->isSuperadmin() || $user->hasRole(['owner', 'admin_keuangan', 'admin_produksi']);

            if ($canSeeAllGlobalBrands) {
                $availableBrands = Brand::orderBy('nama_brand')->get([
                    'id', 'nama_brand', 'kode', 'warna_primary', 'is_active',
                ]);
            } else {
                $availableBrands = $user->brands()
                    ->orderBy('nama_brand')
                    ->get(['brands.id', 'nama_brand', 'kode', 'warna_primary', 'is_active']);
            }

            if ($canSeeAllGlobalBrands || $availableBrands->count() > 1) {
                $allBrand = new Brand();
                $allBrand->id = 'all';
                $allBrand->nama_brand = 'Semua Brand';
                $allBrand->kode = 'ALL';
                $allBrand->warna_primary = '#6366F1';
                $allBrand->is_active = true;
                $availableBrands->prepend($allBrand);
            }

            $currentBrand = BrandContext::resolve($request, $user, $availableBrands);
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar,
                    'roles' => $userRoles,
                    'permissions' => $userPermissions,
                    'is_superadmin' => $user->isSuperadmin(),
                    'unread_notifications_count' => $user->notifications()->where('is_read', false)->count(),
                    'recent_notifications' => $user->notifications()->take(10)->get(),
                ] : null,
            ],
            'brandContext' => [
                'current' => $currentBrand,
                'available' => $availableBrands,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'app' => [
                // Nama sistem dari Settings → Pengaturan → SEO (override APP_NAME di .env)
                'name'        => \App\Models\Settings\SystemSetting::get('seo', 'site_name', config('app.name', 'Circle Sportwear - Tracking PO')),
                'description' => \App\Models\Settings\SystemSetting::get('seo', 'site_description', 'Sistem tracking PO dan invoice secara aman dan privat.'),
                'logo_url'    => \App\Models\Settings\SystemSetting::get('seo', 'logo')
                    ? \Illuminate\Support\Facades\Storage::disk('public')->url(\App\Models\Settings\SystemSetting::get('seo', 'logo'))
                    : null,
                'favicon_url' => \App\Models\Settings\SystemSetting::get('seo', 'favicon')
                    ? \Illuminate\Support\Facades\Storage::disk('public')->url(\App\Models\Settings\SystemSetting::get('seo', 'favicon'))
                    : null,
            ],
        ];
    }
}
