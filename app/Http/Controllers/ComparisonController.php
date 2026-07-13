<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Services\Reports\ComparisonRunner;
use App\Exports\ComparisonReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
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
        if ($user && !in_array('comparison', $user->getAllowedReports())) {
            abort(403, 'Anda tidak memiliki akses ke laporan ini.');
        }

        $isGlobal = $user && ($user->isSuperadmin() || $user->hasRole(['owner', 'admin_keuangan', 'admin_produksi']));
        $availableBrands = $isGlobal
            ? Brand::active()->orderBy('nama_brand')->get(['id', 'nama_brand', 'kode', 'warna_primary'])
            : $user->brands()->where('is_active', true)->orderBy('nama_brand')->get(['brands.id', 'nama_brand', 'kode', 'warna_primary']);

        if ($availableBrands->count() < 2) {
            return Inertia::render('Comparison/NotEligible', [
                'reason' => 'Anda harus memiliki minimal 2 brand untuk melakukan perbandingan.',
                'availableBrands' => $availableBrands,
            ]);
        }

        // Mode: 'brands' (cross brand same year), 'years' (multi year same brand)
        $mode = $request->string('mode', 'brands')->toString();

        $singleBrandId = $request->string('brand_id', $availableBrands->first()->id)->toString();
        $singleYear = $request->integer('year', (int) now()->year);

        $selectedBrandIds = (array) $request->input('brand_ids', $availableBrands->pluck('id')->take(3)->toArray());
        $selectedBrandIds = array_values(array_intersect($selectedBrandIds, $availableBrands->pluck('id')->toArray()));

        $defaultYears = [now()->year, now()->subYear()->year];
        $selectedYears = (array) $request->input('years', $defaultYears);
        $selectedYears = array_map('intval', $selectedYears);
        sort($selectedYears);

        $from = $request->input('from');
        $to = $request->input('to');
        if ($from || $to) {
            $result = $this->runner->run($selectedBrandIds, $from, $to);
            $mode = 'range';
        } else {
            $result = $this->runner->runAdvanced($mode, $selectedBrandIds, $selectedYears, $singleBrandId, $singleYear);
        }

        // Only include years that actually have order data
        $isSqlite = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite';
        $yearExpr = $isSqlite ? "CAST(strftime('%Y', tanggal_masuk) AS INTEGER)" : "YEAR(tanggal_masuk)";
        $dataYears = \App\Models\Order\Order::where('status_po', '!=', 'draft')
            ->selectRaw("DISTINCT {$yearExpr} as yr")
            ->orderByDesc('yr')
            ->pluck('yr')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        // Always include the current year even if no data yet
        $currentYear = (int) now()->year;
        if (!in_array($currentYear, $dataYears)) {
            array_unshift($dataYears, $currentYear);
        }
        rsort($dataYears);
        $availableYears = array_values($dataYears);

        return Inertia::render('Comparison/Show', [
            'availableYears' => $availableYears,
            'availableBrands' => $availableBrands,
            'selectedBrandIds' => $selectedBrandIds,
            'selectedYears' => $selectedYears,
            'singleBrandId' => $singleBrandId,
            'singleYear' => $singleYear,
            'mode' => $mode,
            'result' => $result,
        ]);
    }

    public function exportExcel(Request $request)
    {
        Gate::authorize('report.export');

        $user = $request->user();
        if ($user && !in_array('comparison', $user->getAllowedReports())) {
            abort(403, 'Anda tidak memiliki akses ke laporan ini.');
        }

        $isGlobal = $user && ($user->isSuperadmin() || $user->hasRole(['owner', 'admin_keuangan', 'admin_produksi']));
        $availableBrands = $isGlobal
            ? Brand::active()->orderBy('nama_brand')->get(['id', 'nama_brand', 'kode', 'warna_primary'])
            : $user->brands()->where('is_active', true)->orderBy('nama_brand')->get(['brands.id', 'nama_brand', 'kode', 'warna_primary']);

        $mode = $request->string('mode', 'brands')->toString();
        $singleBrandId = $request->string('brand_id', $availableBrands->first()->id)->toString();
        $singleYear = $request->integer('year', (int) now()->year);

        $selectedBrandIds = (array) $request->input('brand_ids', $availableBrands->pluck('id')->toArray());
        $selectedBrandIds = array_values(array_intersect($selectedBrandIds, $availableBrands->pluck('id')->toArray()));

        $selectedYears = (array) $request->input('years', [now()->year, now()->subYear()->year]);
        $selectedYears = array_map('intval', $selectedYears);
        sort($selectedYears);

        $result = $this->runner->runAdvanced($mode, $selectedBrandIds, $selectedYears, $singleBrandId, $singleYear);

        $headings = ['Bulan'];
        $rows = [];
        $monthsNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        if ($mode === 'brands') {
            $row1 = ['Bulan'];
            $row2 = [''];
            foreach ($result['data'] as $b) {
                $row1[] = $b['brand_name'] . ' (' . $b['kode'] . ')';
                $row1[] = '';
                $row1[] = '';
                
                $row2[] = 'PO';
                $row2[] = 'Pcs';
                $row2[] = 'Omset';
            }
            $headings = [$row1, $row2];

            foreach ($monthsNames as $num => $name) {
                $row = [$name];
                foreach ($result['data'] as $b) {
                    $m = $b['months'][$num] ?? ['total_po' => 0, 'total_pcs' => 0, 'total_omset' => 0];
                    $row[] = $m['total_po'];
                    $row[] = $m['total_pcs'];
                    $row[] = $m['total_omset'];
                }
                $rows[] = $row;
            }
            $totalRow = ['TOTAL TAHUNAN'];
            foreach ($result['data'] as $b) {
                $totalRow[] = $b['totals']['total_po'];
                $totalRow[] = $b['totals']['total_pcs'];
                $totalRow[] = $b['totals']['total_omset'];
            }
            $rows[] = $totalRow;

            $title = "Perbandingan Lintas Brand {$singleYear}";
        } else {
            $brandName = Brand::find($singleBrandId)?->nama_brand ?? 'Brand';
            $row1 = ['Bulan'];
            $row2 = [''];
            foreach ($selectedYears as $y) {
                $row1[] = 'Tahun ' . $y;
                $row1[] = '';
                $row1[] = '';
                
                $row2[] = 'PO';
                $row2[] = 'Pcs';
                $row2[] = 'Omset';
            }
            $headings = [$row1, $row2];

            foreach ($monthsNames as $num => $name) {
                $row = [$name];
                foreach ($selectedYears as $y) {
                    $m = $result['data'][$y]['months'][$num] ?? ['total_po' => 0, 'total_pcs' => 0, 'total_omset' => 0];
                    $row[] = $m['total_po'];
                    $row[] = $m['total_pcs'];
                    $row[] = $m['total_omset'];
                }
                $rows[] = $row;
            }
            $totalRow = ['TOTAL TAHUNAN'];
            foreach ($selectedYears as $y) {
                $totals = $result['data'][$y]['totals'] ?? ['total_po' => 0, 'total_pcs' => 0, 'total_omset' => 0];
                $totalRow[] = $totals['total_po'];
                $totalRow[] = $totals['total_pcs'];
                $totalRow[] = $totals['total_omset'];
            }
            $rows[] = $totalRow;

            $title = "Perbandingan Multi Tahun - {$brandName}";
        }

        $brand = $mode === 'years' ? Brand::find($singleBrandId) : null;
        $primaryColor = ($brand?->warna_primary)
            ?? \App\Models\Settings\SystemSetting::get('system', 'theme_color', '#a8001c');
        $hexColor = ltrim($primaryColor, '#');

        $filename = 'laporan-perbandingan-' . now()->format('Ymd-His') . '.xlsx';
        return Excel::download(
            new ComparisonReportExport($title, $mode, $headings, $rows, $hexColor),
            $filename
        );
    }

    public function exportPdf(Request $request)
    {
        Gate::authorize('report.export');

        $user = $request->user();
        if ($user && !in_array('comparison', $user->getAllowedReports())) {
            abort(403, 'Anda tidak memiliki akses ke laporan ini.');
        }

        $isGlobal = $user && ($user->isSuperadmin() || $user->hasRole(['owner', 'admin_keuangan', 'admin_produksi']));
        $availableBrands = $isGlobal
            ? Brand::active()->orderBy('nama_brand')->get(['id', 'nama_brand', 'kode', 'warna_primary'])
            : $user->brands()->where('is_active', true)->orderBy('nama_brand')->get(['brands.id', 'nama_brand', 'kode', 'warna_primary']);

        $mode = $request->string('mode', 'brands')->toString();
        $singleBrandId = $request->string('brand_id', $availableBrands->first()->id)->toString();
        $singleYear = $request->integer('year', (int) now()->year);

        $selectedBrandIds = (array) $request->input('brand_ids', $availableBrands->pluck('id')->toArray());
        $selectedBrandIds = array_values(array_intersect($selectedBrandIds, $availableBrands->pluck('id')->toArray()));

        $selectedYears = (array) $request->input('years', [now()->year, now()->subYear()->year]);
        $selectedYears = array_map('intval', $selectedYears);
        sort($selectedYears);

        $result = $this->runner->runAdvanced($mode, $selectedBrandIds, $selectedYears, $singleBrandId, $singleYear);
        $brand = $mode === 'years' ? Brand::find($singleBrandId) : null;

        $pdf = Pdf::loadView('pdf.comparison', [
            'mode' => $mode,
            'result' => $result,
            'brand' => $brand,
            'year' => $singleYear,
            'years' => $selectedYears,
            'generated_at' => now(),
            'user' => $user,
        ])->setPaper('a4', 'landscape');

        $filename = 'laporan-perbandingan-' . now()->format('Ymd-His') . '.pdf';
        return $pdf->download($filename);
    }
}
