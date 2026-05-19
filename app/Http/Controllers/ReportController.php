<?php

namespace App\Http\Controllers;

use App\Exports\GenericReportExport;
use App\Services\Reports\ReportRunner;
use App\Support\BrandContext;
use App\Support\ReportRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function __construct(private readonly ReportRunner $runner) {}

    public function show(Request $request, string $slug)
    {
        Gate::authorize('report.view');
        $config = $this->resolveConfig($slug);
        $brandId = BrandContext::current($request);

        $filters = $this->extractFilters($request, $config);
        $result = $this->runner->run($slug, $brandId, $filters);

        return Inertia::render('Report/Show', [
            'config' => $config,
            'filters' => $filters,
            'rows' => $result['rows'],
            'summary' => $result['summary'],
            'heatmapSeries' => $result['heatmapSeries'] ?? null,
            'groups' => ReportRegistry::groups(),
            'allReports' => collect(ReportRegistry::all())->values()->all(),
        ]);
    }

    public function exportExcel(Request $request, string $slug)
    {
        Gate::authorize('report.export');
        $config = $this->resolveConfig($slug);
        $brandId = BrandContext::current($request);
        $filters = $this->extractFilters($request, $config);

        $result = $this->runner->run($slug, $brandId, $filters);
        $filename = "report-{$slug}-" . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new GenericReportExport($config['label'], $config['columns'], $result['rows']),
            $filename
        );
    }

    public function exportPdf(Request $request, string $slug)
    {
        Gate::authorize('report.export');
        $config = $this->resolveConfig($slug);
        $brandId = BrandContext::current($request);
        $filters = $this->extractFilters($request, $config);

        $result = $this->runner->run($slug, $brandId, $filters);

        $pdf = Pdf::loadView('pdf.report', [
            'config' => $config,
            'rows' => $result['rows'],
            'summary' => $result['summary'],
            'filters' => $filters,
            'generated_at' => now(),
            'user' => $request->user(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download("report-{$slug}-" . now()->format('Ymd-His') . '.pdf');
    }

    private function resolveConfig(string $slug): array
    {
        $config = ReportRegistry::find($slug);
        abort_if(! $config, 404, "Laporan '{$slug}' tidak ditemukan.");
        return $config;
    }

    private function extractFilters(Request $request, array $config): array
    {
        $filters = [];
        foreach ($config['filters'] ?? [] as $f) {
            switch ($f) {
                case 'date_range':
                    $filters['from'] = $request->string('from')->toString() ?: now()->subMonth()->toDateString();
                    $filters['to'] = $request->string('to')->toString() ?: now()->toDateString();
                    break;
                case 'periode':
                    $filters['periode'] = $request->string('periode')->toString() ?: 'harian';
                    break;
                case 'status_po':
                    $filters['status'] = $request->string('status')->toString();
                    break;
                case 'threshold':
                    $filters['threshold'] = (int) ($request->string('threshold')->toString() ?: 7);
                    break;
                case 'refund_status':
                    $filters['refund_status'] = $request->string('refund_status')->toString();
                    break;
                case 'is_auto':
                    $filters['is_auto'] = $request->string('is_auto')->toString();
                    break;
            }
        }
        return $filters;
    }
}
