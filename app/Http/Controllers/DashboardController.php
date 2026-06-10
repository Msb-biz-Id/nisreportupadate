<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $service) {}

    public function __invoke(Request $request)
    {
        $user = $request->user();
        $brandId = BrandContext::current($request);
        $role = $user->getRoleNames()->first();

        $filterBrand = $request->string('brand_id')->toString() ?: null;

        // For admin_reseller: resolve to branch IDs (hub has no orders of its own)
        $effectiveIds = in_array($role, ['admin_reseller'])
            ? BrandContext::effectiveBrandIds($request)
            : null;

        $cacheKey = "dashboard:{$role}:{$user->id}:{$brandId}:{$filterBrand}";

        $data = Cache::remember($cacheKey, 60, fn () => match ($role) {
            'superadmin' => [
                'view' => 'Superadmin',
                'stats' => $this->service->superadminStats(),
            ],
            'owner' => [
                'view' => 'Owner',
                'stats' => $this->service->ownerStats($user, $filterBrand),
            ],
            // admin_produksi & admin_keuangan = lintas-brand (lihat semua brand + reseller)
            // null = tanpa filter brand → tampilkan semua
            'admin_produksi' => [
                'view' => 'AdminProduksi',
                'stats' => $this->service->adminProduksiStats($brandId),
            ],
            'admin_keuangan' => [
                'view' => 'Finance',
                'stats' => $this->service->financeStats($brandId),
            ],
            'admin_brand' => [
                'view' => 'AdminBrand',
                'stats' => $this->service->adminBrandStats($brandId),
            ],
            'admin_reseller' => [
                'view' => 'AdminBrand',
                // Pass array of branch IDs so hub context shows all branch data
                'stats' => $this->service->adminBrandStats($effectiveIds ?: $brandId),
            ],
            default => [
                'view' => 'AdminBrand',
                'stats' => $this->service->adminBrandStats($brandId),
            ],
        });

        return Inertia::render('Dashboard', [
            'role' => $role ?? 'guest',
            'view' => $data['view'],
            'stats' => $data['stats'],
        ]);
    }
}
