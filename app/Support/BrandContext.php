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

        $current = $request->session()->get(self::SESSION_KEY) ?? $user->last_brand_id;
        $brand = $current ? $brands->firstWhere('id', $current) : null;

        if (! $brand) {
            $brand = $brands->firstWhere('is_active', true) ?? $brands->first();
            $request->session()->put(self::SESSION_KEY, $brand->id);
            if ($user->last_brand_id !== $brand->id) {
                $user->forceFill(['last_brand_id' => $brand->id])->saveQuietly();
            }
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
        $user->forceFill(['last_brand_id' => $brandId])->saveQuietly();

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

        $availableBrands = $user->isSuperadmin()
            ? \App\Models\Brand::orderBy('nama_brand')->get(['id', 'nama_brand', 'kode', 'warna_primary', 'is_active'])
            : $user->brands()->orderBy('nama_brand')->get(['brands.id', 'nama_brand', 'kode', 'warna_primary', 'is_active']);

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
     * Untuk reseller_branch → pakai parent hub brand_id agar master data di-share.
     * Untuk brand regular/hub → pakai brand_id itu sendiri.
     */
    public static function masterDataId(Request $request, ?string $brandId = null): ?string
    {
        $id = $brandId ?? self::current($request);
        if (! $id) {
            return null;
        }

        $brand = Brand::select('id', 'brand_type', 'parent_brand_id')->find($id);
        if ($brand && $brand->brand_type === Brand::TYPE_RESELLER_BRANCH && $brand->parent_brand_id) {
            return $brand->parent_brand_id;
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
