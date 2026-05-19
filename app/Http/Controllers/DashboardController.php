<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $service) {}

    public function __invoke(Request $request)
    {
        $user = $request->user();
        $brandId = BrandContext::current($request);
        $role = $user->getRoleNames()->first();

        $data = match ($role) {
            'superadmin' => [
                'view' => 'Superadmin',
                'stats' => $this->service->superadminStats(),
            ],
            'owner' => [
                'view' => 'Owner',
                'stats' => $this->service->ownerStats($user, $request->string('brand_id')->toString() ?: null),
            ],
            'admin_produksi' => [
                'view' => 'AdminProduksi',
                'stats' => $this->service->adminProduksiStats($brandId),
            ],
            'admin_keuangan' => [
                'view' => 'Finance',
                'stats' => $this->service->financeStats($brandId),
            ],
            'reseller', 'admin_brand' => [
                'view' => 'AdminBrand',
                'stats' => $this->service->adminBrandStats($brandId),
            ],
            default => [
                'view' => 'AdminBrand',
                'stats' => $this->service->adminBrandStats($brandId),
            ],
        };

        return Inertia::render('Dashboard', [
            'role' => $role ?? 'guest',
            'view' => $data['view'],
            'stats' => $data['stats'],
        ]);
    }
}
