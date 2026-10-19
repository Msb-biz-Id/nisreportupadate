<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmMasterOption;
use App\Models\Purchasing\PurchasingMasterOption;
use App\Models\Purchasing\PurchasingMaterialStock;
use App\Models\Purchasing\PurchasingMaterialUsage;
use App\Models\Purchasing\PurchasingOrder;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PurchasingMaterialController extends Controller
{
    /**
     * Tampilkan halaman kontrol stok dan log pemakaian material khusus (4 Dedicated Tabs).
     */
    public function index(Request $request): Response
    {
        $validTabs = ['KAIN_PUTIH', 'KAIN_WARNA', 'KERTAS', 'TINTA'];
        $activeTab = strtoupper($request->input('tab', 'KAIN_PUTIH'));
        if (!in_array($activeTab, $validTabs)) {
            $activeTab = 'KAIN_PUTIH';
        }

        $search = $request->input('search');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // 1. Data Stok Material untuk Tab Aktif
        $stocks = PurchasingMaterialStock::where('material_type', $activeTab)
            ->withCount('usages')
            ->orderBy('name')
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'uuid' => $s->uuid,
                    'code' => $s->code,
                    'name' => $s->name,
                    'material_type' => $s->material_type,
                    'unit' => $s->unit,
                    'minimum_threshold' => (float) $s->minimum_threshold,
                    'current_stock' => (float) $s->current_stock,
                    'is_critical' => (float) $s->current_stock <= (float) $s->minimum_threshold,
                    'usages_count' => $s->usages_count,
                ];
            });

        // 2. Log Transaksi Masuk/Keluar untuk Tab Aktif
        $usagesQuery = PurchasingMaterialUsage::where('material_type', $activeTab)
            ->with('stock')
            ->orderByDesc('usage_date')
            ->orderByDesc('id');

        if (!empty($search)) {
            $usagesQuery->where(function ($q) use ($search) {
                $q->where('material_name', 'like', "%{$search}%")
                    ->orWhere('production_ref', 'like', "%{$search}%")
                    ->orWhere('operator_name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (!empty($dateFrom)) {
            $usagesQuery->whereDate('usage_date', '>=', $dateFrom);
        }

        if (!empty($dateTo)) {
            $usagesQuery->whereDate('usage_date', '<=', $dateTo);
        }

        $usages = $usagesQuery->paginate(15)->withQueryString();

        // 3. Ringkasan Metrik KPI Tab Aktif
        $thisMonth = now()->startOfMonth()->toDateString();

        // Khusus Kain Warna: Hitung rekap total biaya pembelian masuk (Inbound Purchase Log)
        $totalCostMonth = 0;
        if ($activeTab === 'KAIN_WARNA') {
            $totalCostMonth = (float) PurchasingOrder::where(function ($q) {
                $q->whereHas('itemCategory', function ($c) {
                    $c->where('name', 'like', '%Kain Warna%');
                })->orWhere('item_name', 'like', '%Kain Warna%');
            })->where('status', '!=', 'CANCELLED')->sum('grand_total');
        }

        $metrics = [
            'total_items' => $stocks->count(),
            'critical_items' => $stocks->where('is_critical', true)->count(),
            'total_inbound_month' => (float) PurchasingMaterialUsage::where('material_type', $activeTab)
                ->where('usage_date', '>=', $thisMonth)
                ->sum('quantity_in'),
            'total_outbound_month' => (float) PurchasingMaterialUsage::where('material_type', $activeTab)
                ->where('usage_date', '>=', $thisMonth)
                ->sum('quantity_out'),
            'total_purchase_cost' => $totalCostMonth,
        ];

        // 4. Dropdowns Pembantu & Data Seluruh Kelompok (Untuk Bulk Outbound & Auto-fill PO)
        $locations = PurchasingMasterOption::getCategoryModels('location');

        $departments = class_exists(HcmMasterOption::class)
            ? HcmMasterOption::getDepartmentsWithCodes()
            : [];

        $divisions = class_exists(HcmMasterOption::class)
            ? HcmMasterOption::getOptions('divisi')
            : [];

        // Seluruh master item material lintas kelompok (agar bisa bulk keluar tanpa pindah tab)
        $allStocks = PurchasingMaterialStock::orderBy('material_type')
            ->orderBy('name')
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'uuid' => $s->uuid,
                    'code' => $s->code,
                    'name' => $s->name,
                    'material_type' => $s->material_type,
                    'unit' => $s->unit,
                    'minimum_threshold' => (float) $s->minimum_threshold,
                    'current_stock' => (float) $s->current_stock,
                    'is_critical' => (float) $s->current_stock <= (float) $s->minimum_threshold,
                ];
            });

        // Daftar Pengajuan PO aktif untuk auto-tarik data transaksi masuk (Inbound)
        $purchaseOrders = PurchasingOrder::with(['vendor:id,name', 'items:id,order_id,item_name,quantity,unit'])
            ->whereNotIn('status', ['CANCELLED', 'REJECTED'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(80)
            ->get(['id', 'uuid', 'po_number', 'item_name', 'quantity', 'unit', 'grand_total', 'location_id', 'department', 'division', 'status', 'transaction_date', 'vendor_id', 'notes'])
            ->map(function ($po) {
                // Kumpulkan line items (jika PO punya multiple items di tabel items)
                $lineItems = $po->items && $po->items->count() > 0
                    ? $po->items->map(fn($it) => [
                        'item_name' => $it->item_name,
                        'quantity' => (float) $it->quantity,
                        'unit' => $it->unit,
                    ])->values()->all()
                    : [[
                        'item_name' => $po->item_name,
                        'quantity' => (float) $po->quantity,
                        'unit' => $po->unit,
                    ]];

                return [
                    'id' => $po->id,
                    'po_number' => $po->po_number,
                    'item_name' => $po->item_name,
                    'quantity' => (float) $po->quantity,
                    'unit' => $po->unit,
                    'grand_total' => (float) $po->grand_total,
                    'location_id' => $po->location_id ? (string) $po->location_id : '',
                    'department' => $po->department ?? 'Produksi',
                    'division' => $po->division ?? 'Potong Bahan',
                    'status' => $po->status,
                    'transaction_date' => $po->transaction_date ? (is_string($po->transaction_date) ? substr($po->transaction_date, 0, 10) : $po->transaction_date->format('Y-m-d')) : null,
                    'vendor_name' => $po->vendor?->name ?? 'Vendor Supplier',
                    'notes' => $po->notes ?? '',
                    'items' => $lineItems,
                ];
            });

        return Inertia::render('Purchasing/Materials/Index', [
            'active_tab' => $activeTab,
            'stocks' => $stocks,
            'all_stocks' => $allStocks,
            'purchase_orders' => $purchaseOrders,
            'usages' => $usages,
            'metrics' => $metrics,
            'locations' => $locations,
            'departments' => $departments,
            'divisions' => $divisions,
            'filters' => [
                'search' => $search,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    /**
     * Tambah master item material baru.
     */
    public function storeStock(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'material_type' => ['required', 'string', 'in:KAIN_PUTIH,KAIN_WARNA,KERTAS,TINTA'],
            'name' => ['required', 'string', 'max:150'],
            'unit' => ['required', 'string', 'max:50'],
            'minimum_threshold' => ['required', 'numeric', 'min:0'],
            'initial_stock' => ['nullable', 'numeric', 'min:0'],
            'code' => ['nullable', 'string', 'max:50'],
        ]);

        $prefixMap = [
            'KAIN_PUTIH' => 'MAT-KP',
            'KAIN_WARNA' => 'MAT-KW',
            'KERTAS' => 'MAT-KRT',
            'TINTA' => 'MAT-TNT',
        ];

        $prefix = $prefixMap[$validated['material_type']] ?? 'MAT';
        $code = !empty($validated['code'])
            ? strtoupper(trim($validated['code']))
            : $prefix . '-' . str_pad((string) (PurchasingMaterialStock::where('material_type', $validated['material_type'])->count() + 1), 3, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($validated, $code) {
            $initialStock = (float) ($validated['initial_stock'] ?? 0);

            $stock = PurchasingMaterialStock::create([
                'uuid' => (string) Str::uuid(),
                'code' => $code,
                'name' => $validated['name'],
                'material_type' => $validated['material_type'],
                'unit' => $validated['unit'],
                'minimum_threshold' => $validated['minimum_threshold'],
                'current_stock' => $initialStock,
            ]);

            if ($initialStock > 0) {
                PurchasingMaterialUsage::create([
                    'uuid' => (string) Str::uuid(),
                    'material_stock_id' => $stock->id,
                    'usage_date' => now()->toDateString(),
                    'material_type' => $stock->material_type,
                    'material_name' => $stock->name,
                    'unit' => $stock->unit,
                    'quantity_in' => $initialStock,
                    'quantity_out' => 0,
                    'balance_stock' => $initialStock,
                    'operator_name' => Auth::user()?->name ?? 'Admin Purchasing',
                    'production_ref' => 'SALDO-AWAL',
                    'notes' => 'Pencatatan saldo pembuka master item baru',
                ]);
            }

            ActivityLogger::log('create', 'purchasing', $stock, "Menambahkan master material {$stock->code}: {$stock->name} (Stok awal: {$initialStock} {$stock->unit})");
        });

        return back()->with('success', "Master material {$validated['name']} berhasil ditambahkan.");
    }

    /**
     * Update batas minimum atau informasi master material.
     */
    public function updateStock(Request $request, PurchasingMaterialStock $stock): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'unit' => ['required', 'string', 'max:50'],
            'minimum_threshold' => ['required', 'numeric', 'min:0'],
        ]);

        $stock->update($validated);

        ActivityLogger::log('update', 'purchasing', $stock, "Memperbarui batas minimum stok {$stock->code} ({$stock->name}) menjadi {$stock->minimum_threshold} {$stock->unit}");

        return back()->with('success', "Data stok material {$stock->name} berhasil diperbarui.");
    }

    /**
     * Catat transaksi Masuk (Inbound / Pembelian) atau Keluar (Outbound / Pemakaian Produksi).
     */
    public function storeTransaction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'material_stock_id' => ['required', 'exists:purchasing_material_stocks,id'],
            'transaction_type' => ['required', 'in:IN,OUT'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'usage_date' => ['required', 'date'],
            'location_id' => ['nullable', 'integer'],
            'department' => ['nullable', 'string', 'max:100'],
            'division' => ['nullable', 'string', 'max:100'],
            'operator_name' => ['nullable', 'string', 'max:100'],
            'production_ref' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($validated) {
            $stock = PurchasingMaterialStock::lockForUpdate()->findOrFail($validated['material_stock_id']);
            $qty = (float) $validated['quantity'];

            if ($validated['transaction_type'] === 'IN') {
                $qtyIn = $qty;
                $qtyOut = 0;
                $newBalance = (float) $stock->current_stock + $qty;
            } else {
                $qtyIn = 0;
                $qtyOut = $qty;
                $newBalance = max(0, (float) $stock->current_stock - $qty);
            }

            $stock->current_stock = $newBalance;
            $stock->save();

            $usage = PurchasingMaterialUsage::create([
                'uuid' => (string) Str::uuid(),
                'material_stock_id' => $stock->id,
                'usage_date' => $validated['usage_date'],
                'material_type' => $stock->material_type,
                'material_name' => $stock->name,
                'unit' => $stock->unit,
                'quantity_in' => $qtyIn,
                'quantity_out' => $qtyOut,
                'balance_stock' => $newBalance,
                'location_id' => $validated['location_id'] ?? null,
                'department' => $validated['department'] ?? null,
                'division' => $validated['division'] ?? null,
                'operator_name' => $validated['operator_name'] ?? (Auth::user()?->name ?? 'Admin'),
                'production_ref' => $validated['production_ref'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $typeLabel = $validated['transaction_type'] === 'IN' ? 'Barang Masuk (Inbound)' : 'Pemakaian (Outbound)';
            ActivityLogger::log('stock-update', 'purchasing', $stock, "Input {$typeLabel} {$stock->name}: {$qty} {$stock->unit} (Saldo akhir: {$newBalance} {$stock->unit})");
        });

        return back()->with('success', 'Transaksi keluar/masuk material berhasil dicatat dan saldo stok diperbarui.');
    }

    /**
     * Catat transaksi Pemakaian / Pengeluaran secara massal (Bulk Record) lintas kelompok material tanpa harus pindah tab.
     */
    public function storeBulkTransaction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'transaction_type' => ['required', 'in:IN,OUT'],
            'usage_date' => ['required', 'date'],
            'location_id' => ['nullable', 'integer'],
            'department' => ['nullable', 'string', 'max:100'],
            'division' => ['nullable', 'string', 'max:100'],
            'operator_name' => ['nullable', 'string', 'max:100'],
            'production_ref' => ['nullable', 'string', 'max:100'],
            'general_notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.material_stock_id' => ['required', 'exists:purchasing_material_stocks,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated) {
            $type = $validated['transaction_type'];
            $usageDate = $validated['usage_date'];
            $locationId = $validated['location_id'] ?? null;
            $department = $validated['department'] ?? 'Produksi';
            $division = $validated['division'] ?? 'Produksi';
            $operatorName = $validated['operator_name'] ?? (Auth::user()?->name ?? 'Admin Purchasing');
            $prodRef = $validated['production_ref'] ?? null;
            $generalNotes = $validated['general_notes'] ?? null;

            $totalItems = count($validated['items']);
            $materialNames = [];

            foreach ($validated['items'] as $item) {
                $stock = PurchasingMaterialStock::lockForUpdate()->findOrFail($item['material_stock_id']);
                $qty = (float) $item['quantity'];

                if ($type === 'IN') {
                    $qtyIn = $qty;
                    $qtyOut = 0;
                    $newBalance = (float) $stock->current_stock + $qty;
                } else {
                    $qtyIn = 0;
                    $qtyOut = $qty;
                    $newBalance = max(0, (float) $stock->current_stock - $qty);
                }

                $stock->current_stock = $newBalance;
                $stock->save();

                $itemNotes = trim(($item['notes'] ?? '') . ($generalNotes ? ($item['notes'] ? " | {$generalNotes}" : $generalNotes) : ''));

                PurchasingMaterialUsage::create([
                    'uuid' => (string) Str::uuid(),
                    'material_stock_id' => $stock->id,
                    'usage_date' => $usageDate,
                    'material_type' => $stock->material_type,
                    'material_name' => $stock->name,
                    'unit' => $stock->unit,
                    'quantity_in' => $qtyIn,
                    'quantity_out' => $qtyOut,
                    'balance_stock' => $newBalance,
                    'location_id' => $locationId,
                    'department' => $department,
                    'division' => $division,
                    'operator_name' => $operatorName,
                    'production_ref' => $prodRef,
                    'notes' => $itemNotes ?: null,
                ]);

                $materialNames[] = "{$stock->name} ({$qty} {$stock->unit})";
            }

            $typeLabel = $type === 'IN' ? 'Barang Masuk (Bulk Inbound)' : 'Pemakaian Produksi (Bulk Outbound)';
            $summary = implode(', ', $materialNames);
            ActivityLogger::log('stock-update', 'purchasing', null, "Input {$typeLabel} untuk {$totalItems} item [Ref: {$prodRef}]: {$summary}");
        });

        return back()->with('success', 'Catatan transaksi massal (bulk) material berhasil disimpan dan saldo stok diperbarui.');
    }

    /**
     * Hapus / batalkan log transaksi dan sesuaikan kembali saldo stok.
     */
    public function destroyTransaction(PurchasingMaterialUsage $usage): RedirectResponse
    {
        DB::transaction(function () use ($usage) {
            $stock = PurchasingMaterialStock::lockForUpdate()->findOrFail($usage->material_stock_id);

            // Revert dampak transaksi
            if ($usage->quantity_in > 0) {
                $stock->current_stock = max(0, (float) $stock->current_stock - (float) $usage->quantity_in);
            } elseif ($usage->quantity_out > 0) {
                $stock->current_stock = (float) $stock->current_stock + (float) $usage->quantity_out;
            }

            $stock->save();

            $deletedName = $usage->material_name;
            $usage->delete();

            ActivityLogger::log('delete', 'purchasing', $stock, "Membatalkan log transaksi pemakaian/masuk {$deletedName} tanggal {$usage->usage_date}");
        });

        return back()->with('success', 'Catatan transaksi berhasil dibatalkan dan saldo stok telah disesuaikan kembali.');
    }
}
