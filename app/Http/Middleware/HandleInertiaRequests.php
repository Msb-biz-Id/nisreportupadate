<?php

namespace App\Http\Middleware;

use App\Models\Brand;
use App\Models\Settings\SystemSetting;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $availableBrands = collect();
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
            } elseif ($user->hasRole('admin_reseller')) {
                // Admin reseller has access to their assigned brands (hubs/branches), their children, and IDW
                $assignedIds = $user->brands()->pluck('brands.id')->toArray();
                $availableBrands = Brand::whereIn('id', $assignedIds)
                    ->orWhere('kode', 'IDW')
                    ->orWhereIn('parent_brand_id', $assignedIds)
                    ->orderBy('nama_brand')
                    ->get(['id', 'nama_brand', 'kode', 'warna_primary', 'is_active']);
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
                    'unread_notifications_count' => $user->unreadNotifications()->count(),
                    'recent_notifications' => $user->notifications()->take(10)->get()->map(fn ($n) => [
                        'id' => $n->id,
                        'title' => $n->data['title'] ?? '',
                        'body' => $n->data['body'] ?? '',
                        'no_po' => $n->data['no_po'] ?? '',
                        'action_url' => $n->data['action_url'] ?? '',
                        'sound' => $n->data['sound'] ?? 'bell-chime',
                        'is_read' => ! is_null($n->read_at),
                        'created_at' => $n->created_at->toIso8601String(),
                    ])->values()->all(),
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
                'name'        => SystemSetting::get('seo', 'site_name', config('app.name', 'Circle Sportwear - Tracking PO')),
                'description' => SystemSetting::get('seo', 'site_description', 'Sistem tracking PO dan invoice secara aman dan privat.'),
                'logo_url'    => SystemSetting::get('seo', 'logo')
                    ? Storage::disk('public')->url(SystemSetting::get('seo', 'logo'))
                    : null,
                'favicon_url' => SystemSetting::get('seo', 'favicon')
                    ? Storage::disk('public')->url(SystemSetting::get('seo', 'favicon'))
                    : null,
            ],
        ];
    }
}
