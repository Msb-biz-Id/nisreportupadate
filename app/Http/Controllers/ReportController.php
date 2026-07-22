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
        if (! ReportRegistry::find($slug)) {
            abort(404, "Laporan '{$slug}' tidak ditemukan.");
        }

        Gate::authorize('report.view');
        
        $user = $request->user();
        if ($user && !in_array($slug, $user->getAllowedReports())) {
            abort(403, 'Anda tidak memiliki akses ke laporan ini.');
        }

        $config        = $this->resolveConfig($slug, $request);
        $effectiveId   = $this->effectiveBrandId($request);
        $masterBrandId = BrandContext::masterDataId($request);

        $filters         = $this->extractFilters($request, $config);
        $queryBrandScope = $this->resolveQueryBrandScope($request, $filters);
        $result          = $this->runner->run($slug, $queryBrandScope, $filters);

        $role            = $user?->getRoleNames()->first();
        $isGlobal        = $user && ($user->isSuperadmin() || $user->hasRole(['owner', 'supervisor', 'admin_keuangan', 'admin_produksi']));

        $bankAccounts    = $this->getBankAccounts($request, $isGlobal, $effectiveId);
        $props           = $this->getShowProps($request, $user, $role, $isGlobal, $effectiveId, $masterBrandId, $bankAccounts);

        return Inertia::render('Report/Show', array_merge([
            'config'        => $config,
            'filters'       => $filters,
            'rows'          => $result['rows'],
            'summary'       => $result['summary'],
            'heatmapSeries' => $result['heatmapSeries'] ?? null,
            'groups'        => ReportRegistry::groups(),
        ], $props));
    }

    private function getBankAccounts(Request $request, bool $isGlobal, mixed $effectiveId): array
    {
        $bankAccountsQuery = \App\Models\Master\BankAccount::query()->active();
        if (! $isGlobal) {
            $bankAccountsQuery->whereIn('brand_id', (array)$effectiveId);
        }
        return $bankAccountsQuery
            ->with('brand:id,nama_brand')
            ->orderBy('bank')
            ->get(['id', 'brand_id', 'bank', 'nomor_rekening', 'atas_nama'])
            ->map(fn($b) => [
                'id' => $b->id,
                'brand_id' => $b->brand_id,
                'label' => "{$b->brand?->nama_brand} - {$b->bank} ({$b->nomor_rekening})",
                'bank' => $b->bank,
                'nomor_rekening' => $b->nomor_rekening,
                'atas_nama' => $b->atas_nama,
            ])
            ->all();
    }

    private function getShowProps(Request $request, ?\App\Models\User $user, ?string $role, bool $isGlobal, mixed $effectiveId, mixed $masterBrandId, array $bankAccounts): array
    {
        $allowedReports = $user ? $user->getAllowedReports() : [];
        $allReports = array_map(function ($r) use ($request) {
            return $r['slug'] === 'wilayah' ? $this->resolveConfig('wilayah', $request) : $r;
        }, array_values(array_filter(ReportRegistry::all(), fn ($r) => in_array($r['slug'], $allowedReports, true))));

        return [
            'allReports' => $allReports,
            'customerTypes' => \App\Models\Master\CustomerType::query()
                ->when($masterBrandId, fn($q) => $q->where('brand_id', $masterBrandId)->orWhereNull('brand_id'))
                ->get(['id', 'nama'])
                ->all(),
            'sumberOrders' => \App\Models\Master\SumberOrder::query()
                ->when($masterBrandId, fn($q) => $q->where('brand_id', $masterBrandId)->orWhereNull('brand_id'))
                ->with('parent:id,nama')
                ->get(['id', 'nama', 'parent_id'])
                ->map(fn($s) => [
                    'id' => $s->id,
                    'nama' => $s->parent_id && $s->parent ? "{$s->parent->nama} — {$s->nama}" : $s->nama,
                ])
                ->sortBy('nama')
                ->values()
                ->all(),
            'brands' => $isGlobal 
                ? \App\Models\Brand::active()->orderBy('nama_brand')->get(['id', 'nama_brand', 'kode'])->all()
                : ($role === 'admin_reseller' 
                    ? \App\Models\Brand::whereIn('id', (array)$effectiveId)->get(['id', 'nama_brand', 'kode'])->all()
                    : \App\Models\Brand::where('id', BrandContext::current($request))->get(['id', 'nama_brand', 'kode'])->all()
                ),
            'products' => \App\Models\Master\Product::query()
                ->when($masterBrandId, fn($q) => $q->where('brand_id', $masterBrandId)->orWhereNull('brand_id'))
                ->get(['id', 'nama'])
                ->all(),
            'bankAccounts' => $bankAccounts,
        ];
    }

    public function exportExcel(Request $request, string $slug)
    {
        if (! ReportRegistry::find($slug)) {
            abort(404, "Laporan '{$slug}' tidak ditemukan.");
        }

        Gate::authorize('report.export');

        $user = $request->user();
        if ($user && !in_array($slug, $user->getAllowedReports())) {
            abort(403, 'Anda tidak memiliki akses ke laporan ini.');
        }

        $config = $this->resolveConfig($slug, $request);
        $filters = $this->extractFilters($request, $config);
        $queryBrandScope = $this->resolveQueryBrandScope($request, $filters);

        $brandId = is_string($queryBrandScope) ? $queryBrandScope : (is_array($queryBrandScope) && count($queryBrandScope) === 1 ? reset($queryBrandScope) : null);
        $brand = $brandId ? \App\Models\Brand::find($brandId) : null;
        $activeBrandId = BrandContext::current($request);
        $activeBrand = ($activeBrandId && $activeBrandId !== 'all') ? \App\Models\Brand::find($activeBrandId) : null;
        $primaryColor = ($brand?->warna_primary)
            ?? ($activeBrand?->warna_primary)
            ?? \App\Models\Settings\SystemSetting::get('system', 'theme_color', '#a8001c');
        $hexColor = ltrim($primaryColor, '#');

        $result = $this->runner->run($slug, $queryBrandScope, $filters);
        $filename = "report-{$slug}-" . now()->format('Ymd-His') . '.xlsx';

        \App\Services\ActivityLogger::log('export', 'report', null, "Ekspor Excel laporan {$config['label']}");

        return Excel::download(
            new GenericReportExport($config['label'], $config['columns'], $result['rows'], $hexColor),
            $filename
        );
    }

    public function exportPdf(Request $request, string $slug)
    {
        if (! ReportRegistry::find($slug)) {
            abort(404, "Laporan '{$slug}' tidak ditemukan.");
        }

        Gate::authorize('report.export');

        $user = $request->user();
        if ($user && !in_array($slug, $user->getAllowedReports())) {
            abort(403, 'Anda tidak memiliki akses ke laporan ini.');
        }

        $config = $this->resolveConfig($slug, $request);
        $filters = $this->extractFilters($request, $config);
        $queryBrandScope = $this->resolveQueryBrandScope($request, $filters);

        $brandId = is_string($queryBrandScope) ? $queryBrandScope : (is_array($queryBrandScope) && count($queryBrandScope) === 1 ? reset($queryBrandScope) : null);
        $brand = $brandId ? \App\Models\Brand::find($brandId) : null;
        $activeBrandId = BrandContext::current($request);
        $activeBrand = ($activeBrandId && $activeBrandId !== 'all') ? \App\Models\Brand::find($activeBrandId) : null;
        $primaryColor = ($brand?->warna_primary)
            ?? ($activeBrand?->warna_primary)
            ?? \App\Models\Settings\SystemSetting::get('system', 'theme_color', '#a8001c');

        $result = $this->runner->run($slug, $queryBrandScope, $filters);

        \App\Services\ActivityLogger::log('export', 'report', null, "Ekspor PDF laporan {$config['label']}");

        $pdf = Pdf::loadView('pdf.report', [
            'config' => $config,
            'rows' => $result['rows'],
            'summary' => $result['summary'],
            'filters' => $filters,
            'generated_at' => now(),
            'user' => $request->user(),
            'primaryColor' => $primaryColor,
        ])->setPaper('a4', 'landscape');

        return $pdf->download("report-{$slug}-" . now()->format('Ymd-His') . '.pdf');
    }

    /** Resolves the brand scope to filter queries by based on user permissions and selected filter. */
    private function resolveQueryBrandScope(Request $request, array $filters): string|array|null
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }
        $isGlobal = $user->isSuperadmin() || $user->hasRole(['owner', 'supervisor', 'admin_keuangan', 'admin_produksi']);
        $selectedBrandId = $filters['brand_id'] ?? null;
        if ($selectedBrandId === '__all__') {
            $selectedBrandId = null;
        }

        if ($isGlobal) {
            return $selectedBrandId ?: null;
        }

        $allowedIds = BrandContext::effectiveBrandIds($request);
        if ($selectedBrandId) {
            return in_array($selectedBrandId, $allowedIds) ? $selectedBrandId : $allowedIds;
        }
        return $allowedIds;
    }

    /** Returns effective brand ID(s) for report queries. */
    private function effectiveBrandId(Request $request): string|array|null
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }
        return BrandContext::effectiveBrandIds($request);
    }

    private function resolveConfig(string $slug, Request $request = null): array
    {
        $config = ReportRegistry::find($slug);
        abort_if(! $config, 404, "Laporan '{$slug}' tidak ditemukan.");

        if ($slug === 'wilayah' && $request) {
            $level = $request->string('level_wilayah', 'kabupaten')->toString();
            $cols = [];
            if ($level === 'provinsi') {
                $cols[] = ['key' => 'provinsi', 'label' => 'Provinsi'];
            } elseif ($level === 'kabupaten') {
                $cols[] = ['key' => 'provinsi', 'label' => 'Provinsi'];
                $cols[] = ['key' => 'kabupaten', 'label' => 'Kabupaten/Kota'];
            } elseif ($level === 'kecamatan') {
                $cols[] = ['key' => 'provinsi', 'label' => 'Provinsi'];
                $cols[] = ['key' => 'kabupaten', 'label' => 'Kabupaten/Kota'];
                $cols[] = ['key' => 'kecamatan', 'label' => 'Kecamatan'];
            } elseif ($level === 'desa') {
                $cols[] = ['key' => 'provinsi', 'label' => 'Provinsi'];
                $cols[] = ['key' => 'kabupaten', 'label' => 'Kabupaten/Kota'];
                $cols[] = ['key' => 'kecamatan', 'label' => 'Kecamatan'];
                $cols[] = ['key' => 'desa', 'label' => 'Desa/Kelurahan'];
            }
            $cols[] = ['key' => 'total_pelanggan', 'label' => 'Pelanggan', 'format' => 'number'];
            $cols[] = ['key' => 'total_order', 'label' => 'Total Order', 'format' => 'number'];
            $cols[] = ['key' => 'total_value', 'label' => 'Total Nilai', 'format' => 'currency'];

            $config['columns'] = $cols;

            $config['chart']['x'] = $level === 'provinsi' ? 'provinsi' : 
                                    ($level === 'kabupaten' ? 'kabupaten' : 
                                    ($level === 'kecamatan' ? 'kecamatan' : 'desa'));
            $config['chart']['title'] = 'Top Wilayah Berdasarkan Order (' . ucfirst($level) . ')';
        }

        if ($slug === 'kinerja-produksi') {
            $progresses = \App\Models\Master\Progress::active()->ordered()->get();
            $cols = $config['columns'];
            foreach ($progresses as $p) {
                $key = 'progress_' . strtolower(str_replace(' ', '_', $p->nama_progress));
                $cols[] = ['key' => $key, 'label' => $p->nama_progress, 'format' => 'badge'];
            }
            $config['columns'] = $cols;
        }

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
                case 'jenis_po':
                    $filters['jenis_po'] = $request->string('jenis_po')->toString();
                    break;
                case 'threshold':
                    $filters['threshold'] = (int) ($request->string('threshold')->toString() ?: 7);
                    break;
                case 'refund_status':
                    $filters['refund_status'] = $request->string('refund_status')->toString();
                    break;
                case 'lateness_status':
                    $filters['lateness_status'] = $request->string('lateness_status')->toString();
                    break;
                case 'is_auto':
                    $filters['is_auto'] = $request->string('is_auto')->toString();
                    break;
                case 'customer_type':
                    $filters['customer_type_id'] = $request->string('customer_type_id')->toString();
                    break;
                case 'sumber_order':
                    $filters['sumber_order_id'] = $request->string('sumber_order_id')->toString();
                    break;
                case 'level_wilayah':
                    $filters['level_wilayah'] = $request->string('level_wilayah', 'kabupaten')->toString();
                    break;
                case 'brand':
                    $brandVal = $request->string('brand_id')->toString();
                    $filters['brand_id'] = ($brandVal === '' || $brandVal === '__all__') ? null : $brandVal;
                    break;
                case 'bank_accounts':
                    $val = $request->input('bank_ids');
                    if (is_string($val)) {
                        $filters['bank_ids'] = array_filter(explode(',', $val));
                    } elseif (is_array($val)) {
                        $filters['bank_ids'] = array_filter($val);
                    } else {
                        $filters['bank_ids'] = [];
                    }
                    break;
                case 'region':
                    $filters['region'] = $request->string('region')->toString();
                    break;
                case 'product':
                    $filters['product_id'] = $request->string('product_id')->toString();
                    break;
            }
        }
        return $filters;
    }
}
