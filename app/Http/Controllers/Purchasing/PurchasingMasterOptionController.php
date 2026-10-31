<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\StorePurchasingMasterOptionRequest;
use App\Models\Purchasing\PurchasingMasterOption;
use App\Services\ActivityLogger;
use App\Services\Purchasing\PurchasingHrisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PurchasingMasterOptionController extends Controller
{
    public function __construct(
        protected PurchasingHrisService $hrisService
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('purchasing.view');

        $activeCategory = $request->query('category', 'item_category');
        $search = $request->query('search');

        $query = PurchasingMasterOption::where('category', $activeCategory)
            ->when($search, function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('name', 'like', "%{$s}%")
                        ->orWhere('code', 'like', "%{$s}%");
                });
            })
            ->orderBy('order_index')
            ->orderBy('name');

        $options = $query->paginate(30)->withQueryString();

        // Rekap count per category untuk badge tab
        $categoryCounts = PurchasingMasterOption::selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        // Ambil referensi HRIS untuk tab Read-Only Departemen & Divisi
        $hrisReferences = $this->hrisService->getHrisReferences();

        $categoryLabels = [
            'item_category' => 'Kategori Item Pembelian',
            'unit' => 'Satuan Barang',
            'location' => 'Lokasi Ruko / Gudang',
            'vendor_type' => 'Kategori Vendor',
            'payment_type' => 'Jenis Transaksi Pembayaran',
            'purchase_status' => 'Status Pembelian',
            'asset_category' => 'Kategori Aset (20 Kode Resmi)',
            'asset_unit' => 'Satuan Aset Tetap',
            'retirement_reason' => 'Alasan Aset Retirement',
            'final_condition' => 'Kondisi Akhir Aset',
            'disposal_method' => 'Metode Pelepasan Aset',
            'ink_variant' => 'Varian Tinta & Maintenance',
            'hris_organization' => 'Struktur HRIS (Read-Only Single Source of Truth)',
        ];

        return Inertia::render('Purchasing/MasterData/Index', [
            'options' => $options,
            'activeCategory' => $activeCategory,
            'categoryCounts' => $categoryCounts,
            'categoryLabels' => $categoryLabels,
            'hrisReferences' => $hrisReferences,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(StorePurchasingMasterOptionRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        if (empty($validated['code'])) {
            $validated['code'] = Str::slug($validated['name'], '_');
        }

        $option = PurchasingMasterOption::create($validated);

        ActivityLogger::log('create', 'purchasing', $option, "Menambah opsi master {$option->category}: {$option->name}");

        return back()->with('success', "Opsi master \"{$option->name}\" berhasil ditambahkan.");
    }

    public function update(StorePurchasingMasterOptionRequest $request, PurchasingMasterOption $option): RedirectResponse
    {
        $validated = $request->validated();
        if (empty($validated['code'])) {
            $validated['code'] = Str::slug($validated['name'], '_');
        }

        $option->update($validated);

        ActivityLogger::log('update', 'purchasing', $option, "Memperbarui opsi master {$option->category}: {$option->name}");

        return back()->with('success', "Opsi master \"{$option->name}\" berhasil diperbarui.");
    }

    public function destroy(PurchasingMasterOption $option): RedirectResponse
    {
        Gate::authorize('purchasing.manage-master');

        $name = $option->name;
        $category = $option->category;
        $option->delete();

        ActivityLogger::log('delete', 'purchasing', $option, "Menghapus opsi master {$category}: {$name}");

        return back()->with('success', "Opsi master \"{$name}\" berhasil dihapus.");
    }

    public function toggleActive(PurchasingMasterOption $option): RedirectResponse
    {
        Gate::authorize('purchasing.manage-master');

        $option->update([
            'is_active' => !$option->is_active,
        ]);

        ActivityLogger::log('update', 'purchasing', $option, "Toggle status aktif opsi master {$option->name} menjadi " . ($option->is_active ? 'Aktif' : 'Non-aktif'));

        return back()->with('success', "Status opsi \"{$option->name}\" berhasil diperbarui.");
    }
}
