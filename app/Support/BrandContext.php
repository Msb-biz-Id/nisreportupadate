<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BrandContext
{
    public const SESSION_KEY = 'current_brand_id';

    public static function resolve(Request $request, User $user, Collection|array $availableBrands): ?array
    {
        $brands = $availableBrands instanceof Collection
            ? $availableBrands
            : collect($availableBrands);

        if ($brands->isEmpty()) {
            return null;
        }

        $canSeeAll = $user->hasAccessToBrand('all');
        
        $current = $request->session()->get(self::SESSION_KEY);
        if (! $current) {
            $defaultBrand = $user->defaultBrand();
            if ($defaultBrand && $brands->firstWhere('id', $defaultBrand->id)) {
                $current = $defaultBrand->id;
            } else {
                $current = $user->last_brand_id;
            }
        }

        if ($current === 'all' && $canSeeAll) {
            return [
                'id' => 'all',
                'nama_brand' => 'Semua Brand',
                'kode' => 'ALL',
                'warna_primary' => '#6366F1',
                'is_active' => true,
            ];
        }

        $brand = $current ? $brands->firstWhere('id', $current) : null;

        if (! $brand) {
            if ($canSeeAll) {
                $request->session()->put(self::SESSION_KEY, 'all');
                return [
                    'id' => 'all',
                    'nama_brand' => 'Semua Brand',
                    'kode' => 'ALL',
                    'warna_primary' => '#6366F1',
                    'is_active' => true,
                ];
            }
            $brand = $brands->firstWhere('is_active', true) ?? $brands->first();
        }

        $request->session()->put(self::SESSION_KEY, $brand->id);
        if ($user->last_brand_id !== $brand->id) {
            $user->forceFill(['last_brand_id' => $brand->id === 'all' ? null : $brand->id])->saveQuietly();
        }

        return [
            'id' => $brand->id,
            'nama_brand' => $brand->nama_brand,
            'kode' => $brand->kode,
            'warna_primary' => $brand->warna_primary,
            'is_active' => (bool) $brand->is_active,
        ];
    }

    public static function set(Request $request, User $user, string $brandId): bool
    {
        if (! $user->hasAccessToBrand($brandId)) {
            return false;
        }

        $request->session()->put(self::SESSION_KEY, $brandId);
        $user->forceFill(['last_brand_id' => $brandId === 'all' ? null : $brandId])->saveQuietly();

        return true;
    }

    public static function current(Request $request): ?string
    {
        $fromSession = $request->session()->get(self::SESSION_KEY);
        if ($fromSession) {
            return $fromSession;
        }

        // Fallback: session kosong, coba resolve dari user
        $user = $request->user();
        if (! $user) {
            return null;
        }

        $canSeeAllGlobalBrands = $user->isSuperadmin() || $user->hasRole(['owner', 'admin_keuangan', 'admin_produksi']);

        if ($canSeeAllGlobalBrands) {
            $availableBrands = \App\Models\Brand::orderBy('nama_brand')->get(['id', 'nama_brand', 'kode', 'warna_primary', 'is_active']);
        } else {
            $availableBrands = $user->brands()->orderBy('nama_brand')->get(['brands.id', 'nama_brand', 'kode', 'warna_primary', 'is_active']);
        }

        $resolved = self::resolve($request, $user, $availableBrands);

        return $resolved ? $resolved['id'] : null;
    }

    /**
     * Resolve effective brand IDs untuk query operasional (order, invoice, finance, laporan).
     * Hub dan semua brand (regular/hub/branch) punya fasilitas yang sama.
     * - reseller_hub  → return [hub_id, branch1_id, branch2_id, ...] (hub + semua branches)
     * - reseller_branch → return [$brandId]
     * - regular brand  → return [$brandId]
     */
    public static function effectiveBrandIds(Request $request, ?string $brandId = null): array
    {
        $id = $brandId ?? self::current($request);
        if (! $id) {
            return [];
        }

        if ($id === 'all') {
            $user = $request->user();
            if (! $user) {
                return [];
            }

            $canSeeAllGlobalBrands = $user->isSuperadmin() || $user->hasRole(['owner', 'admin_keuangan', 'admin_produksi']);
            if ($canSeeAllGlobalBrands) {
                return Brand::pluck('id')->toArray();
            }

            if ($user->hasRole('admin_reseller')) {
                $assignedIds = $user->brands()->pluck('brands.id')->toArray();

                // Get branches of explicitly assigned hubs
                $assignedHubIds = Brand::whereIn('id', $assignedIds)->where('brand_type', Brand::TYPE_RESELLER_HUB)->pluck('id')->toArray();
                $assignedBranchIds = Brand::whereIn('parent_brand_id', $assignedHubIds)->pluck('id')->toArray();

                return array_values(array_unique(array_merge($assignedIds, $assignedBranchIds)));
            }

            // Other users (e.g. admin_brand)
            $assignedIds = $user->brands()->pluck('brands.id')->toArray();
            $assignedHubIds = Brand::whereIn('id', $assignedIds)->where('brand_type', Brand::TYPE_RESELLER_HUB)->pluck('id')->toArray();
            $assignedBranchIds = Brand::whereIn('parent_brand_id', $assignedHubIds)->pluck('id')->toArray();

            return array_values(array_unique(array_merge($assignedIds, $assignedBranchIds)));
        }

        $brand = Brand::select('id', 'brand_type')->find($id);
        if ($brand && $brand->brand_type === Brand::TYPE_RESELLER_HUB) {
            // Hub ikut serta (bisa punya order langsung) + semua branches
            $branches = Brand::where('parent_brand_id', $id)->pluck('id')->toArray();
            return array_unique(array_merge([$id], $branches));
        }

        return [$id];
    }

    /**
     * Resolve brand_id untuk query master data.
     * Untuk reseller (hub/branch) → pakai parent brand_id agar master data di-share.
     * Jika tidak ada parent brand, fall back ke brand utama pertama di sistem.
     * Untuk brand regular → pakai brand_id itu sendiri.
     */
    public static function masterDataId(Request $request, ?string $brandId = null): ?string
    {
        $id = $brandId ?? self::current($request);
        if (! $id) {
            return null;
        }

        if ($id === 'all') {
            $user = $request->user();
            if ($user) {
                $firstBrandId = $user->brands()->first()?->id;
                if ($firstBrandId) {
                    return self::masterDataId($request, $firstBrandId);
                }
            }
            $firstRegular = Brand::where('brand_type', Brand::TYPE_REGULAR)->first();
            return $firstRegular ? $firstRegular->id : null;
        }

        $brand = Brand::select('id', 'brand_type', 'parent_brand_id')->find($id);
        if ($brand && in_array($brand->brand_type, [Brand::TYPE_RESELLER_BRANCH, Brand::TYPE_RESELLER_HUB])) {
            $root = $brand;
            while ($root->parent_brand_id) {
                $parent = Brand::select('id', 'brand_type', 'parent_brand_id')->find($root->parent_brand_id);
                if (! $parent) {
                    break;
                }
                $root = $parent;
            }

            // If the root brand in the chain is a reseller hub/branch (no regular parent brand exists),
            // we fall back to the INDOWAREHOUSE brand (IDW) so that all reseller hubs share the same master data.
            // If IDW is not found, we fall back to the first reseller hub in the system.
            if (in_array($root->brand_type, [Brand::TYPE_RESELLER_BRANCH, Brand::TYPE_RESELLER_HUB])) {
                $idwBrand = Brand::where('kode', 'IDW')->first();
                if ($idwBrand) {
                    return $idwBrand->id;
                }
                $firstHub = Brand::where('brand_type', Brand::TYPE_RESELLER_HUB)->first();
                return $firstHub ? $firstHub->id : $root->id;
            }

            return $root->id;
        }

        return $id;
    }

    /**
     * Resolve brand object lengkap untuk context saat ini.
     */
    public static function currentBrand(Request $request): ?Brand
    {
        $id = self::current($request);
        if (! $id) {
            return null;
        }

        return Brand::find($id);
    }
}
