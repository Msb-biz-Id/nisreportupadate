<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * Cache TTL configurations in seconds
     */
    public const TTL_SHORT = 60;           // 1 minute
    public const TTL_MEDIUM = 300;         // 5 minutes
    public const TTL_LONG = 1800;          // 30 minutes
    public const TTL_VERY_LONG = 3600;     // 1 hour
    public const TTL_DAILY = 86400;        // 24 hours

    /**
     * Cache key prefixes
     */
    private const PREFIX_DASHBOARD = 'dashboard:';
    private const PREFIX_BRAND = 'brand:';
    private const PREFIX_ORDER = 'order:';
    private const PREFIX_CUSTOMER = 'customer:';
    private const PREFIX_MASTER = 'master:';

    /**
     * Remember dashboard stats with automatic key generation
     */
    public static function rememberDashboardStats(string $method, string|array|null $brandId, callable $callback, int $ttl = self::TTL_MEDIUM): mixed
    {
        $key = self::PREFIX_DASHBOARD . $method . ':' . self::normalizeBrandId($brandId);
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Alias for rememberDashboardStats
     */
    public static function rememberDashboard(string $method, string|array|null $brandId, callable $callback, int $ttl = self::TTL_MEDIUM): mixed
    {
        return self::rememberDashboardStats($method, $brandId, $callback, $ttl);
    }

    /**
     * Remember brand data
     */
    public static function rememberBrand(int $brandId, callable $callback, int $ttl = self::TTL_LONG): mixed
    {
        $key = self::prefixBrand($brandId);
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Remember order-related data
     */
    public static function rememberOrder(string $key, callable $callback, int $ttl = self::TTL_SHORT): mixed
    {
        return Cache::remember(self::PREFIX_ORDER . $key, $ttl, $callback);
    }

    /**
     * Remember customer data
     */
    public static function rememberCustomer(int $customerId, callable $callback, int $ttl = self::TTL_LONG): mixed
    {
        $key = self::PREFIX_CUSTOMER . $customerId;
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * List of dashboard cache methods to clean deterministically
     */
    private const DASHBOARD_METHODS = [
        'adminBrandStats',
        'adminProduksiStats',
        'superadminStats',
        'ownerStats',
        'financeStats',
        'statusBreakdown',
        'trendHarian',
        'produkTerpopuler',
        'kategoriDistribusi',
        'sumberDistribusi',
        'kategoriPelangganDistribusi',
    ];

    /**
     * Remember master data (rarely changes)
     */
    public static function rememberMaster(string $type, string $key, callable $callback, int $ttl = self::TTL_DAILY): mixed
    {
        return Cache::remember(self::PREFIX_MASTER . $type . ':' . $key, $ttl, $callback);
    }

    /**
     * Invalidate cache for a specific brand
     */
    public static function forgetBrand(int $brandId): void
    {
        Cache::forget(self::prefixBrand($brandId));
        
        // Forget specific dashboard keys for this brand and 'all'
        foreach (self::DASHBOARD_METHODS as $method) {
            Cache::forget(self::PREFIX_DASHBOARD . $method . ':' . $brandId);
            Cache::forget(self::PREFIX_DASHBOARD . $method . ':all');
        }

        // Also forget all dashboard caches for this brand using pattern fallback
        Cache::forgetPattern(self::PREFIX_DASHBOARD . '*:' . $brandId);
    }

    /**
     * Invalidate cache for a specific order
     */
    public static function forgetOrder(string $key): void
    {
        Cache::forget(self::PREFIX_ORDER . $key);
    }

    /**
     * Invalidate all dashboard caches
     */
    public static function forgetDashboard(): void
    {
        // Deterministically forget dashboard keys for all active brands + 'all'
        try {
            $brandIds = \App\Models\Brand::pluck('id')->toArray();
            $brandIds[] = 'all';
            
            foreach (self::DASHBOARD_METHODS as $method) {
                foreach ($brandIds as $id) {
                    Cache::forget(self::PREFIX_DASHBOARD . $method . ':' . $id);
                }
            }
        } catch (\Exception $e) {
            // Fallback to flush if DB query fails
            Cache::flush();
        }

        // Also run pattern fallback
        Cache::forgetPattern(self::PREFIX_DASHBOARD . '*');
    }

    /**
     * Normalize brand ID for cache key
     */
    private static function normalizeBrandId(string|array|null $brandId): string
    {
        if (is_null($brandId)) {
            return 'all';
        }

        if (is_array($brandId)) {
            return implode(',', array_unique($brandId));
        }

        return $brandId;
    }

    /**
     * Generate brand cache key
     */
    private static function prefixBrand(int $brandId): string
    {
        return self::PREFIX_BRAND . $brandId;
    }

    /**
     * Generate composite cache key
     */
    public static function key(string $prefix, array $parts): string
    {
        return $prefix . ':' . implode(':', $parts);
    }
}
