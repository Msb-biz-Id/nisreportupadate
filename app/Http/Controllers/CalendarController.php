<?php

namespace App\Http\Controllers;

use App\Models\Order\Order;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CalendarController extends Controller
{
    private const STATUS_COLORS = [
        'published'        => '#3B82F6',
        'on_progress'      => '#F59E0B',
        'selesai_produksi' => '#22C55E',
        'siap_dikirim'     => '#06B6D4',
        'sudah_dikirim'    => '#8B5CF6',
        'delay'            => '#EF4444',
        'hold'             => '#F97316',
    ];

    private const STATUS_LABELS = [
        'published'        => 'Baru Masuk',
        'on_progress'      => 'Sedang Produksi',
        'selesai_produksi' => 'Selesai Produksi',
        'siap_dikirim'     => 'Siap Dikirim',
        'sudah_dikirim'    => 'Sudah Dikirim',
        'delay'            => 'Delay',
        'hold'             => 'Hold',
    ];

    public function index(Request $request)
    {
        Gate::authorize('order.view');

        $brandId = $request->string('brand_id')->toString();
        if (empty($brandId)) {
            $brandId = BrandContext::current($request) ?? 'all';
        }

        $orders = Order::query()
            ->forBrand($brandId)
            ->published()
            ->with(['pelanggan:id,nama', 'brand:id,nama_brand,kode'])
            ->withCount(['items as total_pcs' => fn ($q) => $q->selectRaw('SUM(quantity)')])
            ->orderBy('deadline_customer')
            ->get();

        $events = $orders->map(function (Order $o) {
            $start = ($o->start_production_date ?? $o->tanggal_masuk)?->toDateString();
            $end = ($o->end_production_date ?? $o->deadline_customer)?->toDateString();

            $brandPrefix = $o->brand?->nama_brand ? "({$o->brand->nama_brand}) " : "";

            return [
                'id'            => $o->id,
                'title'         => "[{$o->no_po}] " . $brandPrefix . ($o->nama_po ?? ''),
                'start'         => $start,
                'end'           => $end,
                'status'        => $o->status_po,
                'statusLabel'   => self::STATUS_LABELS[$o->status_po] ?? $o->status_po,
                'color'         => self::STATUS_COLORS[$o->status_po] ?? '#94A3B8',
                'pelanggan'     => $o->pelanggan?->nama,
                'noPo'          => $o->no_po,
                'namaPo'        => $o->nama_po,
                'brandName'     => $o->brand?->nama_brand,
                'brandKode'     => $o->brand?->kode,
                'daysRemaining' => $o->deadline_customer
                    ? now()->startOfDay()->diffInDays($o->deadline_customer, false)
                    : null,
                'totalPcs'      => (int) ($o->total_pcs ?? 0),
                'detailUrl'     => route('orders.show', $o->id),
                'progressUrl'   => route('produksi.progress', $o->id),
            ];
        });

        return Inertia::render('Calendar/Index', [
            'events'       => $events,
            'statusColors' => self::STATUS_COLORS,
            'statusLabels' => self::STATUS_LABELS,
            'filters'      => [
                'brand_id' => $brandId,
            ],
        ]);
    }
}
