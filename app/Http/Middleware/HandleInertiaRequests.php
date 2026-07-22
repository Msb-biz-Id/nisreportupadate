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
            $nameCol = 'nama_brand';

            if ($canSeeAllGlobalBrands) {
                $availableBrands = Brand::orderBy($nameCol)->get([
                    'id', 'nama_brand', 'kode', 'warna_primary', 'is_active',
                ]);
            } elseif ($user->hasRole('admin_reseller')) {
                // Admin reseller only has access to their explicitly assigned brands
                $availableBrands = $user->brands()
                    ->orderBy($nameCol)
                    ->get(['brands.id', 'nama_brand', 'kode', 'warna_primary', 'is_active']);
            } else {
                $availableBrands = $user->brands()
                    ->orderBy($nameCol)
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

        /** @var \Illuminate\Filesystem\FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');

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
                    'unread_notifications_count' => $user->unreadNotifications()->count(),
                    'recent_notifications' => $user->notifications()->take(10)->get()->map(fn ($n) => [
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
            'app' => [
                // Nama sistem dari Settings -> Pengaturan -> SEO (override APP_NAME di .env)
                'name'        => (function() use ($request, $publicDisk) {
                    $appName = SystemSetting::get('seo', 'site_name', config('app.name', 'Circle Sportwear - Tracking PO'));
                    $route = $request->route();
                    if ($route) {
                        $routeName = $route->getName();
                        if ($routeName === 'track.show') {
                            $noPo = $request->route('noPo');
                            $order = \App\Models\Order\Order::where('no_po', $noPo)->first();
                            $brand = $order ? $order->brand : null;
                            if (!$brand && count(explode('-', $noPo)) >= 2) {
                                $brandKode = explode('-', $noPo)[1];
                                $brand = Brand::where('kode', $brandKode)->first();
                            }
                            if ($brand) {
                                $appName = $brand->nama_brand;
                            }
                        } elseif ($routeName === 'invoice.public') {
                            $invoiceNumber = $request->route('invoiceNumber');
                            $invoice = \App\Models\Order\Invoice::where('invoice_number', $invoiceNumber)->first();
                            if ($invoice) {
                                $brand = null;
                                if ($invoice->order) {
                                    $resellerBrand = $invoice->order->resolveResellerBrand();
                                    $brand = $resellerBrand ? $resellerBrand->getHeaderBrand() : $invoice->brand?->getHeaderBrand();
                                } else {
                                    $brand = $invoice->brand?->getHeaderBrand();
                                }
                                if ($brand) {
                                    $appName = $brand->nama_brand;
                                }
                            }
                        }
                    }
                    return $appName;
                })(),
                'description' => (function() use ($request) {
                    $appDesc = SystemSetting::get('seo', 'site_description', 'Sistem tracking PO dan invoice secara aman dan privat.');
                    $route = $request->route();
                    if ($route) {
                        $routeName = $route->getName();
                        if ($routeName === 'track.show') {
                            $noPo = $request->route('noPo');
                            $order = \App\Models\Order\Order::where('no_po', $noPo)->first();
                            $brand = $order ? $order->brand : null;
                            if (!$brand && count(explode('-', $noPo)) >= 2) {
                                $brandKode = explode('-', $noPo)[1];
                                $brand = Brand::where('kode', $brandKode)->first();
                            }
                            if ($brand) {
                                $appDesc = "Lacak status pengerjaan pesanan Anda dengan nomor PO $noPo secara real-time di {$brand->nama_brand}.";
                            }
                        } elseif ($routeName === 'invoice.public') {
                            $invoiceNumber = $request->route('invoiceNumber');
                            $invoice = \App\Models\Order\Invoice::where('invoice_number', $invoiceNumber)->first();
                            if ($invoice) {
                                $brand = null;
                                if ($invoice->order) {
                                    $resellerBrand = $invoice->order->resolveResellerBrand();
                                    $brand = $resellerBrand ? $resellerBrand->getHeaderBrand() : $invoice->brand?->getHeaderBrand();
                                } else {
                                    $brand = $invoice->brand?->getHeaderBrand();
                                }
                                if ($brand) {
                                    $appDesc = "Lihat detail tagihan dan bayar invoice Anda dengan nomor $invoiceNumber di {$brand->nama_brand}.";
                                }
                            }
                        }
                    }
                    return $appDesc;
                })(),
                'logo_url'    => (function() use ($request, $publicDisk) {
                    $logo = SystemSetting::get('seo', 'logo');
                    $appLogo = null;
                    if ($logo) {
                        if ($logo === 'favicon.ico' || file_exists(public_path($logo))) {
                            $appLogo = asset($logo);
                        } else {
                            $appLogo = $publicDisk->url($logo);
                        }
                    }
                    $route = $request->route();
                    if ($route) {
                        $routeName = $route->getName();
                        if ($routeName === 'track.show') {
                            $noPo = $request->route('noPo');
                            $order = \App\Models\Order\Order::where('no_po', $noPo)->first();
                            $brand = $order ? $order->brand : null;
                            if (!$brand && count(explode('-', $noPo)) >= 2) {
                                $brandKode = explode('-', $noPo)[1];
                                $brand = Brand::where('kode', $brandKode)->first();
                            }
                            if ($brand && $brand->logo) {
                                $appLogo = \Illuminate\Support\Str::contains($brand->logo, 'http') ? $brand->logo : $publicDisk->url($brand->logo);
                            } elseif ($brand && ($brand->isResellerHub() || $brand->isResellerBranch())) {
                                $appLogo = null;
                            }
                        } elseif ($routeName === 'invoice.public') {
                            $invoiceNumber = $request->route('invoiceNumber');
                            $invoice = \App\Models\Order\Invoice::where('invoice_number', $invoiceNumber)->first();
                            if ($invoice) {
                                $brand = null;
                                if ($invoice->order) {
                                    $resellerBrand = $invoice->order->resolveResellerBrand();
                                    $brand = $resellerBrand ? $resellerBrand->getHeaderBrand() : $invoice->brand?->getHeaderBrand();
                                } else {
                                    $brand = $invoice->brand?->getHeaderBrand();
                                }
                                if ($brand && $brand->logo) {
                                    $appLogo = \Illuminate\Support\Str::contains($brand->logo, 'http') ? $brand->logo : $publicDisk->url($brand->logo);
                                } elseif ($brand && ($brand->isResellerHub() || $brand->isResellerBranch())) {
                                    $appLogo = null;
                                }
                            }
                        }
                    }
                    return \App\Support\UrlHelper::clean($appLogo, $request);
                })(),
                'favicon_url' => (function() use ($request, $publicDisk) {
                    $favicon = SystemSetting::get('seo', 'favicon');
                    $appFavicon = null;
                    if ($favicon) {
                        if ($favicon === 'favicon.ico' || file_exists(public_path($favicon))) {
                            $appFavicon = asset($favicon);
                        } else {
                            $appFavicon = $publicDisk->url($favicon);
                        }
                    }
                    $route = $request->route();
                    if ($route) {
                        $routeName = $route->getName();
                        if ($routeName === 'track.show') {
                            $noPo = $request->route('noPo');
                            $order = \App\Models\Order\Order::where('no_po', $noPo)->first();
                            $brand = $order ? $order->brand : null;
                            if (!$brand && count(explode('-', $noPo)) >= 2) {
                                $brandKode = explode('-', $noPo)[1];
                                $brand = Brand::where('kode', $brandKode)->first();
                            }
                            if ($brand && $brand->logo) {
                                $appFavicon = \Illuminate\Support\Str::contains($brand->logo, 'http') ? $brand->logo : $publicDisk->url($brand->logo);
                            } elseif ($brand && ($brand->isResellerHub() || $brand->isResellerBranch())) {
                                $appFavicon = null;
                            }
                        } elseif ($routeName === 'invoice.public') {
                            $invoiceNumber = $request->route('invoiceNumber');
                            $invoice = \App\Models\Order\Invoice::where('invoice_number', $invoiceNumber)->first();
                            if ($invoice) {
                                $brand = null;
                                if ($invoice->order) {
                                    $resellerBrand = $invoice->order->resolveResellerBrand();
                                    $brand = $resellerBrand ? $resellerBrand->getHeaderBrand() : $invoice->brand?->getHeaderBrand();
                                } else {
                                    $brand = $invoice->brand?->getHeaderBrand();
                                }
                                if ($brand && $brand->logo) {
                                    $appFavicon = \Illuminate\Support\Str::contains($brand->logo, 'http') ? $brand->logo : $publicDisk->url($brand->logo);
                                } elseif ($brand && ($brand->isResellerHub() || $brand->isResellerBranch())) {
                                    $appFavicon = null;
                                }
                            }
                        }
                    }
                    return \App\Support\UrlHelper::clean($appFavicon, $request);
                })(),
                'theme_color' => (function() use ($request) {
                    $appTheme = SystemSetting::get('system', 'theme_color', '#a8001c');
                    $route = $request->route();
                    if ($route) {
                        $routeName = $route->getName();
                        if ($routeName === 'track.show') {
                            $noPo = $request->route('noPo');
                            $order = \App\Models\Order\Order::where('no_po', $noPo)->first();
                            $brand = $order ? $order->brand : null;
                            if (!$brand && count(explode('-', $noPo)) >= 2) {
                                $brandKode = explode('-', $noPo)[1];
                                $brand = Brand::where('kode', $brandKode)->first();
                            }
                            if ($brand && $brand->warna_primary) {
                                $appTheme = $brand->warna_primary;
                            }
                        } elseif ($routeName === 'invoice.public') {
                            $invoiceNumber = $request->route('invoiceNumber');
                            $invoice = \App\Models\Order\Invoice::where('invoice_number', $invoiceNumber)->first();
                            if ($invoice) {
                                $brand = null;
                                if ($invoice->order) {
                                    $resellerBrand = $invoice->order->resolveResellerBrand();
                                    $brand = $resellerBrand ? $resellerBrand->getHeaderBrand() : $invoice->brand?->getHeaderBrand();
                                } else {
                                    $brand = $invoice->brand?->getHeaderBrand();
                                }
                                if ($brand && $brand->warna_primary) {
                                    $appTheme = $brand->warna_primary;
                                }
                            }
                        }
                    }
                    return $appTheme;
                })(),
                'target_view' => 'pcs',
            ],
        ];
    }
}
