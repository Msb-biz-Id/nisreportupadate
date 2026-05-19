<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Services\Reports\ComparisonRunner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ComparisonController extends Controller
{
    public function __construct(private readonly ComparisonRunner $runner) {}

    public function show(Request $request)
    {
        Gate::authorize('report.view');

        $user = $request->user();
        $availableBrands = $user->isSuperadmin()
            ? Brand::active()->orderBy('nama_brand')->get(['id', 'nama_brand', 'kode', 'warna_primary'])
            : $user->brands()->where('is_active', true)->orderBy('nama_brand')->get(['brands.id', 'nama_brand', 'kode', 'warna_primary']);

        // Hanya superadmin/owner yang punya akses ≥2 brand bisa comparison
        if ($availableBrands->count() < 2) {
            return Inertia::render('Comparison/NotEligible', [
                'reason' => 'Comparison report butuh minimal 2 brand. User Anda hanya memiliki akses ke ' . $availableBrands->count() . ' brand.',
                'availableBrands' => $availableBrands,
            ]);
        }

        $selectedBrandIds = (array) $request->input('brand_ids', $availableBrands->pluck('id')->take(2)->toArray());
        // Filter agar hanya brand yang user punya akses
        $selectedBrandIds = array_values(array_intersect($selectedBrandIds, $availableBrands->pluck('id')->toArray()));

        $from = $request->string('from')->toString() ?: now()->subMonth()->toDateString();
        $to = $request->string('to')->toString() ?: now()->toDateString();

        $result = count($selectedBrandIds) >= 2
            ? $this->runner->run($selectedBrandIds, $from, $to)
            : ['brands' => [], 'summary' => [], 'periode' => '', 'from' => $from, 'to' => $to];

        return Inertia::render('Comparison/Show', [
            'availableBrands' => $availableBrands,
            'selectedBrandIds' => $selectedBrandIds,
            'filters' => ['from' => $from, 'to' => $to],
            'result' => $result,
        ]);
    }
}
