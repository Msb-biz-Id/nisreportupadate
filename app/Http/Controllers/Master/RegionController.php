<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * RegionController — data wilayah Indonesia via database lokal (laravolt/indonesia)
 * dengan fallback ke API publik emsifa jika database kosong.
 */
class RegionController extends Controller
{
    private const BASE = 'https://www.emsifa.com/api-wilayah-indonesia/api';
    private const TTL  = 60 * 60 * 24 * 7; // 7 hari

    public function provinces(): JsonResponse
    {
        $prefix = config('laravolt.indonesia.table_prefix', 'indonesia_');
        $tableName = $prefix . 'provinces';

        if (Schema::hasTable($tableName) && DB::table($tableName)->exists()) {
            $data = DB::table($tableName)
                ->select('code', 'name')
                ->orderBy('name')
                ->get();
            return response()->json($data);
        }

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

        $prefix = config('laravolt.indonesia.table_prefix', 'indonesia_');
        $tableName = $prefix . 'cities';

        if (Schema::hasTable($tableName) && DB::table($tableName)->exists()) {
            $data = DB::table($tableName)
                ->where('province_code', $province)
                ->select('code', 'name')
                ->orderBy('name')
                ->get();
            return response()->json($data);
        }

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

        $prefix = config('laravolt.indonesia.table_prefix', 'indonesia_');
        $tableName = $prefix . 'districts';

        if (Schema::hasTable($tableName) && DB::table($tableName)->exists()) {
            $data = DB::table($tableName)
                ->where('city_code', $city)
                ->select('code', 'name')
                ->orderBy('name')
                ->get();
            return response()->json($data);
        }

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

        $prefix = config('laravolt.indonesia.table_prefix', 'indonesia_');
        $tableName = $prefix . 'villages';

        if (Schema::hasTable($tableName) && DB::table($tableName)->exists()) {
            $data = DB::table($tableName)
                ->where('district_code', $district)
                ->select('code', 'name')
                ->orderBy('name')
                ->get();
            return response()->json($data);
        }

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
        return Cache::remember("regions.{$key}", self::TTL, function () use ($fetch, $key) {
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
