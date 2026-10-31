<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\MutatePurchasingAssetRequest;
use App\Http\Requests\Purchasing\RetirePurchasingAssetRequest;
use App\Http\Requests\Purchasing\StorePurchasingAssetRequest;
use App\Http\Requests\Purchasing\UpdatePurchasingAssetRequest;
use App\Models\Purchasing\PurchasingAsset;
use App\Models\Purchasing\PurchasingMasterOption;
use App\Services\Purchasing\PurchasingAssetService;
use App\Services\Purchasing\PurchasingHrisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchasingAssetController extends Controller
{
    public function __construct(
        protected PurchasingAssetService $assetService,
        protected PurchasingHrisService $hrisService
    ) {}

    /**
     * Tampilkan daftar inventaris aset tetap beserta filter & metrik statistik.
     */
    public function index(Request $request): Response
    {
        $query = PurchasingAsset::with([
            'picEmployee:id,uuid,employee_code,name,department,division,position',
            'order:id,uuid,po_number,grand_total',
            'vendor:id,uuid,vendor_code,name',
            'mutations' => function ($q) {
                $q->with('picUser:id,name')->orderByDesc('mutation_date');
            },
            'retirement.financeApprover:id,name',
            'retirement.creator:id,name',
        ]);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('asset_code', 'LIKE', "%{$search}%")
                    ->orWhere('asset_name', 'LIKE', "%{$search}%")
                    ->orWhere('serial_number', 'LIKE', "%{$search}%")
                    ->orWhere('barcode_qr_code', 'LIKE', "%{$search}%")
                    ->orWhere('pic_employee_name', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('category_code')) {
            $query->where('category_code', $request->category_code);
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('registration_stage')) {
            $query->where('registration_stage', $request->registration_stage);
        }

        $assets = $query->orderByDesc('id')->paginate(15)->withQueryString();

        // Agregasi metrik hero
        $stats = [
            'total_assets' => PurchasingAsset::count(),
            'active_assets' => PurchasingAsset::where('status', 'ACTIVE')->count(),
            'quick_registered' => PurchasingAsset::where('registration_stage', 'QUICK_REGISTERED')->where('status', 'ACTIVE')->count(),
            'retired_assets' => PurchasingAsset::where('status', 'RETIRED')->count(),
            'total_acquisition_cost' => (float) PurchasingAsset::sum('acquisition_cost'),
        ];

        // Opsi-opsi master data pendukung
        $assetCategories = PurchasingMasterOption::getOptions('asset_category');
        $locations = PurchasingMasterOption::getOptions('location');
        $units = PurchasingMasterOption::getOptions('asset_unit');
        $retirementReasons = PurchasingMasterOption::getOptions('retirement_reason');
        $finalConditions = PurchasingMasterOption::getOptions('final_condition');
        $disposalMethods = PurchasingMasterOption::getOptions('disposal_method');
        $hrisData = $this->hrisService->getHrisReferences();
        $employees = $this->hrisService->getActiveEmployees();

        return Inertia::render('Purchasing/Assets/Index', [
            'assets' => $assets,
            'stats' => $stats,
            'filters' => $request->only(['search', 'category_code', 'department', 'status', 'registration_stage']),
            'masterOptions' => [
                'categories' => $assetCategories,
                'locations' => $locations,
                'units' => $units,
                'retirement_reasons' => $retirementReasons,
                'final_conditions' => $finalConditions,
                'disposal_methods' => $disposalMethods,
                'departments' => $hrisData['departments_with_codes'],
                'division_position_map' => $hrisData['division_position_map'],
                'employees' => $employees,
            ],
        ]);
    }

    /**
     * Simpan pendaftaran aset baru (Quick Registration).
     */
    public function store(StorePurchasingAssetRequest $request): RedirectResponse
    {
        $asset = $this->assetService->quickRegister($request->validated(), $request->user());

        return redirect()->back()->with('success', "Aset baru {$asset->asset_code} ({$asset->asset_name}) berhasil didaftarkan.");
    }

    /**
     * Dapatkan detail aset lengkap (bisa diakses via drawer / dialog).
     */
    public function show(PurchasingAsset $asset): JsonResponse
    {
        $asset->load([
            'picEmployee:id,uuid,employee_code,name,department,division,position',
            'order:id,uuid,po_number,grand_total,transaction_date',
            'vendor:id,uuid,vendor_code,name',
            'mutations' => function ($q) {
                $q->with('picUser:id,name')->orderByDesc('mutation_date');
            },
            'retirement.financeApprover:id,name',
            'retirement.creator:id,name',
        ]);

        return response()->json([
            'asset' => $asset,
        ]);
    }

    /**
     * Lengkapi spesifikasi fisik aset (Deep Registration).
     */
    public function update(UpdatePurchasingAssetRequest $request, PurchasingAsset $asset): RedirectResponse
    {
        $this->assetService->completeRegistration($asset, $request->validated(), $request->user());

        return redirect()->back()->with('success', "Data fisik aset {$asset->asset_code} berhasil dilengkapi dan disinkronkan.");
    }

    /**
     * Mutasi aset ke departemen baru.
     */
    public function mutate(MutatePurchasingAssetRequest $request, PurchasingAsset $asset): RedirectResponse
    {
        $mutation = $this->assetService->mutateAsset($asset, $request->validated(), $request->user());

        return redirect()->back()->with('success', "Aset berhasil dimutasi ke {$mutation->to_department_name}. Kode aset baru: {$mutation->new_asset_code}.");
    }

    /**
     * Pelepasan / pensiun aset tetap.
     */
    public function retire(RetirePurchasingAssetRequest $request, PurchasingAsset $asset): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('handover_document')) {
            $path = $request->file('handover_document')->store('purchasing/retirements', 'public');
            $data['handover_document_path'] = $path;
        }

        $retirement = $this->assetService->retireAsset($asset, $data, $request->user());

        return redirect()->back()->with('success', "Aset {$asset->asset_code} telah resmi dilepas/dipensiunkan dengan metode {$retirement->disposal_method}.");
    }

    /**
     * Smart Suggestion Engine untuk pencocokan kategori aset saat mengetik nama barang.
     */
    public function suggestCategory(Request $request): JsonResponse
    {
        $keyword = (string) $request->query('keyword', '');
        $suggestions = $this->assetService->suggestCategories($keyword);

        return response()->json([
            'suggestions' => $suggestions,
        ]);
    }
}
