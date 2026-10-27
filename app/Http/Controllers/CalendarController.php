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
        'baru_masuk'       => '#3B82F6',
        'review'           => '#8B5CF6',
        'published'        => '#3B82F6',
        'on_progress'      => '#F59E0B',
        'selesai_produksi' => '#22C55E',
        'siap_dikirim'     => '#06B6D4',
        'sudah_dikirim'    => '#6366F1',
        'delay'            => '#EF4444',
        'hold'             => '#F97316',
    ];

    private const STATUS_LABELS = [
        'baru_masuk'       => 'Baru Masuk (Belum Ada Deadline)',
        'review'           => 'Review Produksi',
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
            ->whereNotIn('status_po', ['selesai', 'sudah_dikirim'])
            ->with(['pelanggan:id,nama', 'brand:id,nama_brand,kode'])
            ->withCount(['items as total_pcs' => fn ($q) => $q->selectRaw('SUM(quantity)')])
            ->orderBy('deadline_customer')
            ->get();

        $events = $orders->map(function (Order $o) {
            $startDate = $o->start_production_date ?? $o->tanggal_masuk;
            $endDate = $o->end_production_date ?? $o->deadline_customer;

            $start = $startDate ? \Illuminate\Support\Carbon::parse((string) $startDate)->toDateString() : null;
            $end = $endDate ? \Illuminate\Support\Carbon::parse((string) $endDate)->toDateString() : null;
            $deadlineCustomer = $o->deadline_customer ? \Illuminate\Support\Carbon::parse((string) $o->deadline_customer)->toDateString() : null;
            $deadlineProduksi = $o->end_production_date ? \Illuminate\Support\Carbon::parse((string) $o->end_production_date)->toDateString() : null;

            // Hitung deadline efektif & sisa hari persis seperti Gantt Chart Produksi
            $today = now()->startOfDay();
            $effectiveDeadline = $o->end_production_date ?? $o->deadline_customer;
            $deadlineCarbon = $effectiveDeadline ? \Illuminate\Support\Carbon::parse((string) $effectiveDeadline)->startOfDay() : null;

            $daysRemaining = null;
            if ($deadlineCarbon) {
                $daysRemaining = (int) $today->diffInDays($deadlineCarbon, false);
                if ($deadlineCarbon < $today && $daysRemaining > 0) {
                    $daysRemaining = -$daysRemaining;
                }
            }

            $isFinished = in_array($o->status_po, ['selesai_produksi', 'siap_dikirim', 'sudah_dikirim', 'selesai']);
            $isOverdue = $deadlineCarbon !== null && $deadlineCarbon < $today && !$isFinished;
            $isDelayed = $o->status_po === 'delay' || $isOverdue;

            $stageStatus = $o->status_po;
            if ($o->status_po === 'published') {
                $stageStatus = empty($o->end_production_date) ? 'baru_masuk' : 'review';
            }

            $effectiveStatus = $isDelayed ? 'delay' : $stageStatus;
            $brandPrefix = $o->brand?->nama_brand ? "({$o->brand->nama_brand}) " : "";

            return [
                'id'               => $o->id,
                'title'            => "[{$o->no_po}] " . $brandPrefix . ($o->nama_po ?? ''),
                'start'            => $start,
                'end'              => $end,
                'deadlineCustomer' => $deadlineCustomer,
                'deadlineProduksi' => $deadlineProduksi,
                'status'           => $effectiveStatus,
                'stageStatus'      => $stageStatus,
                'needsDeadline'    => empty($o->end_production_date),
                'rawStatus'        => $o->status_po,
                'isDelayed'        => $isDelayed,
                'statusLabel'      => self::STATUS_LABELS[$effectiveStatus] ?? self::STATUS_LABELS[$stageStatus] ?? $o->status_po,
                'color'            => self::STATUS_COLORS[$effectiveStatus] ?? self::STATUS_COLORS[$stageStatus] ?? '#94A3B8',
                'pelanggan'        => $o->pelanggan?->nama,
                'noPo'             => $o->no_po,
                'namaPo'           => $o->nama_po,
                'brandName'        => $o->brand?->nama_brand,
                'brandKode'        => $o->brand?->kode,
                'daysRemaining'    => $daysRemaining,
                'totalPcs'         => (int) ($o->total_pcs ?? 0),
                'detailUrl'        => route('orders.show', $o->id),
                'progressUrl'      => route('produksi.progress', $o->id),
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
