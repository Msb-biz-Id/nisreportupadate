<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\BrandTarget;
use App\Models\Order\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BrandTargetController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isSuperadmin() || $user->hasRole(['owner', 'admin_reseller']), 403, 'Akses ditolak.');

        $year = $request->integer('year') ?: (int) now()->year;

        $brands = $user->isSuperadmin()
            ? Brand::active()->get(['id', 'nama_brand', 'kode', 'warna_primary'])
            : $user->brands()->active()->get(['brands.id as id', 'brands.nama_brand', 'brands.kode', 'brands.warna_primary']);

        $brandIds = $brands->pluck('id')->toArray();

        $targets = BrandTarget::whereIn('brand_id', $brandIds)
            ->where('year', $year)
            ->get()
            ->groupBy('brand_id');

        // Calculate actuals
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $monthExpr = $isSqlite ? 'CAST(strftime("%m", orders.tanggal_masuk) AS INTEGER)' : 'MONTH(orders.tanggal_masuk)';

        $actualRows = Order::query()
            ->leftJoin(DB::raw('(SELECT order_id, SUM(quantity) as qty FROM order_items GROUP BY order_id) as items_sum'), 'items_sum.order_id', '=', 'orders.id')
            ->whereIn('orders.brand_id', $brandIds)
            ->whereBetween('orders.tanggal_masuk', ["{$year}-01-01 00:00:00", "{$year}-12-31 23:59:59"])
            ->where('orders.status_po', '!=', 'draft')
            ->select(
                'orders.brand_id',
                DB::raw("$monthExpr as month"),
                DB::raw('SUM(orders.total_tagihan) as revenue'),
                DB::raw('COALESCE(SUM(items_sum.qty), 0) as pcs')
            )
            ->groupBy('orders.brand_id', 'month')
            ->get()
            ->groupBy('brand_id');

        return Inertia::render('BrandTarget/Index', [
            'brands' => $brands,
            'year' => $year,
            'targets' => $targets,
            'actuals' => $actualRows,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isSuperadmin() || $user->hasRole(['owner', 'admin_reseller']), 403, 'Akses ditolak.');

        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2050'],
            'targets' => ['required', 'array'],
            'targets.*.brand_id' => ['required', 'uuid', 'exists:brands,id'],
            'targets.*.month' => ['required', 'integer', 'min:1', 'max:12'],
            'targets.*.target_revenue' => ['required', 'numeric', 'min:0'],
            'targets.*.target_pcs' => ['required', 'integer', 'min:0'],
        ]);

        $year = $data['year'];

        foreach ($data['targets'] as $target) {
            abort_unless($user->hasAccessToBrand($target['brand_id']), 403, 'Akses ditolak untuk brand.');
        }

        DB::transaction(function () use ($data, $year) {
            foreach ($data['targets'] as $target) {
                BrandTarget::updateOrCreate(
                    [
                        'brand_id' => $target['brand_id'],
                        'year' => $year,
                        'month' => $target['month'],
                    ],
                    [
                        'target_revenue' => $target['target_revenue'],
                        'target_pcs' => $target['target_pcs'],
                    ]
                );
            }
        });

        Cache::flush();

        \App\Services\ActivityLogger::log('update', 'target', null, "Simpan target penjualan tahun {$year}");

        return redirect()->back()->with('success', 'Target penjualan berhasil disimpan.');
    }
}
