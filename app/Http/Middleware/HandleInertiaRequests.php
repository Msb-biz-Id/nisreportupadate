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

            if ($user->isSuperadmin() || $user->hasRole('owner')) {
                $availableBrands = Brand::orderBy('nama_brand')->get([
                    'id', 'nama_brand', 'kode', 'warna_primary', 'is_active',
                ]);
            } else {
                $availableBrands = $user->brands()
                    ->orderBy('nama_brand')
                    ->get(['brands.id', 'nama_brand', 'kode', 'warna_primary', 'is_active']);
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
                'name'        => \App\Models\Settings\SystemSetting::get('seo', 'site_name', config('app.name', 'NISReport')),
                'description' => \App\Models\Settings\SystemSetting::get('seo', 'site_description', 'Sistem Manajemen Order Multi-Brand'),
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
