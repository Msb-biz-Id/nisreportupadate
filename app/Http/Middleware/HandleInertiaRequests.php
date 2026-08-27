<?php

namespace App\Http\Middleware;

use App\Models\Brand;
use App\Models\Settings\SystemSetting;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        $userNotifData = ['unread_count' => 0, 'recent' => []];

        if ($user) {
            $userRoles = $user->getRoleNames()->all();
            $userPermissions = $user->getAllPermissions()->pluck('name')->all();

            $canSeeAllGlobalBrands = $user->isSuperadmin() || $user->hasRole(['owner', 'supervisor', 'admin_keuangan', 'admin_produksi']);
            $nameCol = 'nama_brand';

            $cacheKey = $canSeeAllGlobalBrands ? 'global_available_brands' : "user_available_brands:{$user->id}";
            $rawBrands = Cache::remember($cacheKey, 300, function () use ($user, $canSeeAllGlobalBrands, $nameCol) {
                if ($canSeeAllGlobalBrands) {
                    return Brand::orderBy($nameCol)->get([
                        'id', 'nama_brand', 'kode', 'warna_primary', 'is_active',
                    ]);
                }
                return $user->brands()
                    ->orderBy($nameCol)
                    ->get(['brands.id', 'nama_brand', 'kode', 'warna_primary', 'is_active']);
            });
            $availableBrands = collect($rawBrands);

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

            // Cache user notifications for 60 seconds to eliminate per-request DB queries
            $userNotifData = Cache::remember("user_notifs:{$user->id}", 60, function () use ($user) {
                return [
                    'unread_count' => $user->unreadNotifications()->count(),
                    'recent' => $user->notifications()->take(10)->get()->map(fn ($n) => [
                        'id' => $n->id,
                        'type' => $n->data['type'] ?? $n->data['event_key'] ?? $n->type ?? '',
                        'title' => $n->data['title'] ?? '',
                        'body' => $n->data['body'] ?? '',
                        'no_po' => $n->data['no_po'] ?? '',
                        'action_url' => $n->data['action_url'] ?? '',
                        'sound' => $n->data['sound'] ?? 'bell-chime',
                        'is_read' => ! is_null($n->read_at),
                        'created_at' => $n->created_at->toIso8601String(),
                    ])->values()->all(),
                ];
            });
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');

        // Consolidated single-pass metadata calculation (SEO, App Name, Logo, Theme)
        $seoContext = (function () use ($request, $publicDisk) {
            $appName = SystemSetting::get('seo', 'site_name', config('app.name', 'Circle Sportwear - Tracking PO'));
            $appDesc = SystemSetting::get('seo', 'site_description', 'Sistem tracking PO dan invoice secara aman dan privat.');
            $appTheme = SystemSetting::get('system', 'theme_color', '#a8001c');

            $logo = SystemSetting::get('seo', 'logo');
            $appLogo = $logo ? ($logo === 'favicon.ico' || file_exists(public_path($logo)) ? asset($logo) : $publicDisk->url($logo)) : null;

            $favicon = SystemSetting::get('seo', 'favicon');
            $appFavicon = $favicon ? ($favicon === 'favicon.ico' || file_exists(public_path($favicon)) ? asset($favicon) : $publicDisk->url($favicon)) : null;

            $route = $request->route();
            if ($route) {
                $routeName = $route->getName();
                $brand = null;

                if ($routeName === 'track.show') {
                    $noPo = $request->route('noPo');
                    $order = \App\Models\Order\Order::where('no_po', $noPo)->select('id', 'brand_id')->with('brand:id,nama_brand,kode,logo,warna_primary,brand_type')->first();
                    $brand = $order ? $order->brand : null;
                    if (! $brand && count(explode('-', $noPo)) >= 2) {
                        $brandKode = explode('-', $noPo)[1];
                        $brand = Brand::where('kode', $brandKode)->select('id', 'nama_brand', 'kode', 'logo', 'warna_primary', 'brand_type')->first();
                    }
                    if ($brand) {
                        $appName = $brand->nama_brand;
                        $appDesc = "Lacak status pengerjaan pesanan Anda dengan nomor PO {$noPo} secara real-time di {$brand->nama_brand}.";
                    }
                } elseif ($routeName === 'invoice.public') {
                    $invoiceNumber = $request->route('invoiceNumber');
                    $invoice = \App\Models\Order\Invoice::where('invoice_number', $invoiceNumber)->select('id', 'order_id', 'brand_id')->with(['order', 'brand'])->first();
                    if ($invoice) {
                        if ($invoice->order) {
                            $resellerBrand = $invoice->order->resolveResellerBrand();
                            $brand = $resellerBrand ? $resellerBrand->getHeaderBrand() : $invoice->brand?->getHeaderBrand();
                        } else {
                            $brand = $invoice->brand?->getHeaderBrand();
                        }
                        if ($brand) {
                            $appName = $brand->nama_brand;
                            $appDesc = "Lihat detail tagihan dan bayar invoice Anda dengan nomor {$invoiceNumber} di {$brand->nama_brand}.";
                        }
                    }
                }

                if ($brand) {
                    if ($brand->logo) {
                        $logoUrl = \Illuminate\Support\Str::contains($brand->logo, 'http') ? $brand->logo : $publicDisk->url($brand->logo);
                        $appLogo = $logoUrl;
                        $appFavicon = $logoUrl;
                    } elseif ($brand->isResellerHub() || $brand->isResellerBranch()) {
                        $appLogo = null;
                        $appFavicon = null;
                    }
                    if ($brand->warna_primary) {
                        $appTheme = $brand->warna_primary;
                    }
                }
            }

            return [
                'name' => $appName,
                'description' => $appDesc,
                'logo_url' => \App\Support\UrlHelper::clean($appLogo, $request),
                'favicon_url' => \App\Support\UrlHelper::clean($appFavicon, $request),
                'theme_color' => $appTheme,
                'target_view' => 'pcs',
            ];
        })();

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
                    'allowed_reports' => $user->getAllowedReports(),
                    'unread_notifications_count' => $userNotifData['unread_count'],
                    'recent_notifications' => $userNotifData['recent'],
                ] : null,
            ],
            'brandContext' => [
                'current' => $currentBrand,
                'available' => $availableBrands,
            ],
            'reports_list' => array_merge(
                array_values(array_map(fn ($r) => [
                    'slug' => $r['slug'],
                    'name' => $r['label'],
                    'group' => \App\Support\ReportRegistry::groups()[$r['group']] ?? ucfirst($r['group']),
                ], \App\Support\ReportRegistry::all())),
                [[
                    'slug' => 'comparison',
                    'name' => 'Comparison Multi-Brand',
                    'group' => 'Keuangan',
                ]]
            ),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'app' => $seoContext,
        ];
    }
}

