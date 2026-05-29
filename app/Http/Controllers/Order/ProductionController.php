<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Master\Progress;
use App\Models\Order\Order;
use App\Models\Order\OrderProgressDetail;
use App\Models\Order\Rijek;
use App\Services\POStatusManager;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProductionController extends Controller
{
    public function __construct(private readonly POStatusManager $statusManager) {}

    public function gantt(Request $request)
    {
        Gate::authorize('order.view');
        $brandId = BrandContext::current($request);

        $orders = Order::query()
            ->forBrand($brandId)
            ->published()
            ->with('pelanggan:id,nama')
            ->orderBy('deadline_customer')
            ->get();

        $statusColors = [
            'published'        => '#3B82F6',
            'on_progress'      => '#F59E0B',
            'selesai_produksi' => '#22C55E',
            'siap_dikirim'     => '#06B6D4',
            'sudah_dikirim'    => '#8B5CF6',
            'delay'            => '#EF4444',
            'hold'             => '#F97316',
        ];

        $statusLabels = [
            'published'        => 'Baru Masuk',
            'on_progress'      => 'Sedang Produksi',
            'selesai_produksi' => 'Selesai Produksi',
            'siap_dikirim'     => 'Siap Dikirim',
            'sudah_dikirim'    => 'Sudah Dikirim',
            'delay'            => 'Delay',
            'hold'             => 'Hold',
        ];

        $items = $orders->map(function (Order $order) use ($statusColors, $statusLabels) {
            $start = $order->start_production_date ?? $order->tanggal_masuk;
            $end   = $order->end_production_date ?? $order->deadline_customer;

            // Pastikan end >= start supaya bar minimal 1 hari
            if ($end < $start) $end = $start;

            return [
                'id'               => $order->id,
                'no_po'            => $order->no_po,
                'nama_po'          => $order->nama_po,
                'pelanggan'        => $order->pelanggan?->nama,
                'status_po'        => $order->status_po,
                'status_label'     => $statusLabels[$order->status_po] ?? $order->status_po,
                'color'            => $statusColors[$order->status_po] ?? '#94A3B8',
                'tanggal_masuk'    => $order->tanggal_masuk?->toDateString(),
                'deadline_customer'=> $order->deadline_customer?->toDateString(),
                'start'            => $start?->toDateString(),
                'end'              => $end?->toDateString(),
                'days_remaining'   => $order->deadline_customer
                    ? now()->startOfDay()->diffInDays($order->deadline_customer, false)
                    : null,
                'detail_url'       => route('produksi.progress', $order->id),
            ];
        });

        return Inertia::render('Production/Gantt', [
            'items'        => $items,
            'statusColors' => $statusColors,
            'statusLabels' => $statusLabels,
        ]);
    }

    public function kanban(Request $request)
    {
        Gate::authorize('order.view');
        $brandId = BrandContext::current($request);

        $orders = Order::query()
            ->forBrand($brandId)
            ->published()
            ->with(['pelanggan:id,nama', 'lockStatus'])
            ->orderBy('deadline_customer')
            ->get();

        $columns = [
            'published' => ['label' => 'Baru Masuk', 'color' => '#3B82F6', 'orders' => []],
            'on_progress' => ['label' => 'Sedang Produksi', 'color' => '#F59E0B', 'orders' => []],
            'selesai_produksi' => ['label' => 'Selesai Produksi', 'color' => '#22C55E', 'orders' => []],
            'siap_dikirim' => ['label' => 'Siap Dikirim', 'color' => '#06B6D4', 'orders' => []],
            'sudah_dikirim' => ['label' => 'Sudah Dikirim', 'color' => '#8B5CF6', 'orders' => []],
            'delay' => ['label' => 'Delay', 'color' => '#EF4444', 'orders' => []],
            'hold' => ['label' => 'Hold', 'color' => '#F97316', 'orders' => []],
        ];

        foreach ($orders as $order) {
            $status = $order->status_po;
            if (! isset($columns[$status])) continue;
            $columns[$status]['orders'][] = [
                'id' => $order->id,
                'no_po' => $order->no_po,
                'nama_po' => $order->nama_po,
                'pelanggan' => $order->pelanggan?->nama,
                'deadline_customer' => $order->deadline_customer?->toDateString(),
                'is_locked' => $order->isLocked(),
                'days_remaining' => $order->deadline_customer ? now()->startOfDay()->diffInDays($order->deadline_customer, false) : null,
            ];
        }

        return Inertia::render('Production/Kanban', ['columns' => $columns]);
    }

    public function progress(Request $request, Order $order)
    {
        Gate::authorize('order.view');
        $this->guardBrandOwnership($request, $order);

        $order->load(['progressDetails.progress', 'progressDetails.updater', 'rijeks']);

        return Inertia::render('Production/Progress', [
            'order' => $order,
            'can' => [
                'update' => $request->user()->can('production.update-progress'),
                'addReject' => $request->user()->can('production.add-reject'),
            ],
        ]);
    }

    public function updateProgress(Request $request, Order $order, OrderProgressDetail $detail)
    {
        Gate::authorize('production.update-progress');
        $this->guardBrandOwnership($request, $order);
        abort_unless($detail->order_id === $order->id, 404);
        $detail->loadMissing('progress');

        $isSending = strtoupper($detail->progress->nama_progress ?? '') === 'SENDING';

        $data = $request->validate([
            'status'         => ['required', Rule::in(OrderProgressDetail::STATUSES)],
            'catatan'        => ['required_unless:status,pending', 'nullable', 'string'],
            'kendala'        => ['nullable', 'string'],
            'skipped_reason' => ['required_if:status,skipped', 'nullable', 'string'],
            'nama_ekspedisi' => [$isSending && $request->input('status') === 'selesai' ? 'required' : 'nullable', 'string', 'max:100'],
            'no_resi'        => ['nullable', 'string', 'max:100'],
        ]);

        $this->statusManager->updateProgressDetail(
            $order, $detail, $data['status'],
            $data['catatan'] ?? null, $data['kendala'] ?? null,
            $data['skipped_reason'] ?? null, $request->user()
        );

        if ($isSending && $data['status'] === 'selesai') {
            $order->update([
                'nama_ekspedisi' => $data['nama_ekspedisi'] ?? null,
                'no_resi'        => $data['no_resi'] ?? null,
            ]);
        }

        return back()->with('success', 'Progress berhasil diperbarui.');
    }

    public function storeRijek(Request $request, Order $order)
    {
        Gate::authorize('production.add-reject');
        $this->guardBrandOwnership($request, $order);

        $data = $request->validate([
            'progress_id' => ['nullable', 'uuid', 'exists:progress,id'],
            'order_item_id' => ['nullable', 'uuid'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'jenis' => ['required', Rule::in(Rijek::JENIS)],
            'tingkat' => ['required', Rule::in(Rijek::TINGKAT)],
            'kendala' => ['required', 'string'],
            'penanganan' => ['nullable', 'string'],
            'biaya_ganti' => ['nullable', 'numeric', 'min:0'],
        ]);

        Rijek::create([
            ...$data,
            'order_id' => $order->id,
            'status' => 'pending',
            'created_by' => $request->user()->id,
        ]);

        if (! empty($data['progress_id'])) {
            OrderProgressDetail::where('order_id', $order->id)
                ->where('progress_id', $data['progress_id'])
                ->update(['has_reject' => true]);
        }

        return back()->with('success', 'Rijek berhasil dicatat.');
    }

    /**
     * Drag-drop transitions di Kanban. Hanya manual transitions yang aman:
     * tidak override progress detail flow yang otomatis (PACKING→siap_dikirim, dll).
     */
    private const TRANSITIONS = [
        // from => [allowed to...]
        'published' => ['on_progress', 'hold'],
        'on_progress' => ['hold', 'delay'],
        'selesai_produksi' => ['siap_dikirim', 'hold'],
        'siap_dikirim' => ['sudah_dikirim', 'hold'],
        'sudah_dikirim' => [], // final
        'delay' => ['on_progress', 'hold'],
        'hold' => ['published', 'on_progress'],
    ];

    public function moveStatus(Request $request, Order $order)
    {
        Gate::authorize('production.update-progress');
        $this->guardBrandOwnership($request, $order);

        $data = $request->validate([
            'to_status' => ['required', 'string', Rule::in(array_keys(self::TRANSITIONS))],
        ]);

        $from = $order->status_po;
        $to = $data['to_status'];
        $allowed = self::TRANSITIONS[$from] ?? [];

        if (! in_array($to, $allowed, true)) {
            return response()->json([
                'success' => false,
                'error' => "Transisi '{$from}' → '{$to}' tidak diizinkan via Kanban. Gunakan halaman progress untuk update detail tahapan.",
            ], 422);
        }

        $order->update(['status_po' => $to]);

        return response()->json([
            'success' => true,
            'from' => $from,
            'to' => $to,
            'order_id' => $order->id,
        ]);
    }

    private function guardBrandOwnership(Request $request, Order $order): void
    {
        if ($request->user()->isSuperadmin()) return;
        $brandId = BrandContext::current($request);
        if ($order->brand_id !== $brandId) abort(403);
    }
}
