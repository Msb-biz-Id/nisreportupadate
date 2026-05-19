<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegionController extends Controller
{
    public function provinces(): JsonResponse
    {
        $rows = DB::table('indonesia_provinces')
            ->select('code', 'name')
            ->orderBy('name')
            ->get();
        return response()->json($rows);
    }

    public function cities(Request $request): JsonResponse
    {
        $provinceCode = $request->string('province')->toString();
        $rows = DB::table('indonesia_cities')
            ->where('province_code', $provinceCode)
            ->select('code', 'name')
            ->orderBy('name')
            ->get();
        return response()->json($rows);
    }

    public function districts(Request $request): JsonResponse
    {
        $cityCode = $request->string('city')->toString();
        $rows = DB::table('indonesia_districts')
            ->where('city_code', $cityCode)
            ->select('code', 'name')
            ->orderBy('name')
            ->get();
        return response()->json($rows);
    }

    public function villages(Request $request): JsonResponse
    {
        $districtCode = $request->string('district')->toString();
        $rows = DB::table('indonesia_villages')
            ->where('district_code', $districtCode)
            ->select('code', 'name')
            ->orderBy('name')
            ->get();
        return response()->json($rows);
    }
}
