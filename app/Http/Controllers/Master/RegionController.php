<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * RegionController — data wilayah Indonesia via API publik emsifa.
 *
 * Tidak bergantung pada tabel DB laravolt (tidak rusak saat migrate:fresh).
 * Data di-cache 7 hari — sangat jarang berubah.
 *
 * Source: https://www.emsifa.com/api-wilayah-indonesia
 * Format kode: BPS (sama dengan laravolt) — tidak perlu migrasi data pelanggan.
 */
class RegionController extends Controller
{
    private const BASE = 'https://www.emsifa.com/api-wilayah-indonesia/api';
    private const TTL  = 60 * 60 * 24 * 7; // 7 hari

    public function provinces(): JsonResponse
    {
        $data = $this->cached('provinces', fn () =>
            Http::timeout(10)->get(self::BASE . '/provinces.json')->json()
        );

        return response()->json(
            collect($data)->map(fn ($r) => ['code' => $r['id'], 'name' => $r['name']])
                          ->sortBy('name')->values()
        );
    }

    public function cities(Request $request): JsonResponse
    {
        $province = $request->string('province')->toString();
        if (! $province) return response()->json([]);

        $data = $this->cached("cities.{$province}", fn () =>
            Http::timeout(10)->get(self::BASE . "/regencies/{$province}.json")->json()
        );

        return response()->json(
            collect($data)->map(fn ($r) => ['code' => $r['id'], 'name' => $r['name']])
                          ->sortBy('name')->values()
        );
    }

    public function districts(Request $request): JsonResponse
    {
        $city = $request->string('city')->toString();
        if (! $city) return response()->json([]);

        $data = $this->cached("districts.{$city}", fn () =>
            Http::timeout(10)->get(self::BASE . "/districts/{$city}.json")->json()
        );

        return response()->json(
            collect($data)->map(fn ($r) => ['code' => $r['id'], 'name' => $r['name']])
                          ->sortBy('name')->values()
        );
    }

    public function villages(Request $request): JsonResponse
    {
        $district = $request->string('district')->toString();
        if (! $district) return response()->json([]);

        $data = $this->cached("villages.{$district}", fn () =>
            Http::timeout(10)->get(self::BASE . "/villages/{$district}.json")->json()
        );

        return response()->json(
            collect($data)->map(fn ($r) => ['code' => $r['id'], 'name' => $r['name']])
                          ->sortBy('name')->values()
        );
    }

    /**
     * Cache wrapper — jika request ke API gagal, return array kosong.
     */
    private function cached(string $key, callable $fetch): array
    {
        return Cache::remember("regions.{$key}", self::TTL, function () use ($fetch) {
            try {
                $result = $fetch();
                return is_array($result) ? $result : [];
            } catch (\Throwable $e) {
                Log::warning("Region API failed for key: regions.{$key}", ['error' => $e->getMessage()]);
                return [];
            }
        });
    }
}
