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
        $user    = $request->user();
        $brandId = match(true) {
            $user->hasRole('admin_reseller') => BrandContext::effectiveBrandIds($request),
            $user->hasRole('admin_produksi') => null, // lintas-brand: tampil semua
            default                          => BrandContext::current($request),
        };

        $orders = Order::query()
            ->forBrand($brandId)
            ->published()
            ->where('status_po', '!=', 'sudah_dikirim')
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
        $user    = $request->user();
        $brandId = match(true) {
            $user->hasRole('admin_reseller') => BrandContext::effectiveBrandIds($request),
            $user->hasRole('admin_produksi') => null, // lintas-brand: tampil semua
            default                          => BrandContext::current($request),
        };

        $orders = Order::query()
            ->forBrand($brandId)
            ->published()
            ->whereNotIn('status_po', ['sudah_dikirim', 'selesai'])
            ->with(['pelanggan:id,nama', 'lockStatus', 'brand:id,kode,warna_primary', 'paketOrder:id,nama,warna,prioritas'])
            ->withCount(['rijeks as has_rijek' => fn ($q) => $q->whereNull('resolved_at')])
            ->withSum('items', 'quantity')
            ->orderBy('deadline_customer')
            ->get();

        $columns = [
            'published'        => ['label' => 'Baru Masuk',       'color' => '#3B82F6', 'orders' => []],
            'on_progress'      => ['label' => 'Sedang Produksi',  'color' => '#F59E0B', 'orders' => []],
            'selesai_produksi' => ['label' => 'Selesai Produksi', 'color' => '#22C55E', 'orders' => []],
            'siap_dikirim'     => ['label' => 'Siap Dikirim',     'color' => '#06B6D4', 'orders' => []],
            'sudah_dikirim'    => ['label' => 'Sudah Dikirim',    'color' => '#8B5CF6', 'orders' => []],
            'delay'            => ['label' => 'Delay',            'color' => '#EF4444', 'orders' => []],
            'hold'             => ['label' => 'Hold',             'color' => '#F97316', 'orders' => []],
        ];

        foreach ($orders as $order) {
            $status = $order->status_po;
            if (! isset($columns[$status])) continue;

            $daysRemaining = $order->deadline_customer
                ? (int) now()->startOfDay()->diffInDays($order->deadline_customer, false)
                : null;

            $columns[$status]['orders'][] = [
                'id'                => $order->id,
                'no_po'             => $order->no_po,
                'nama_po'           => $order->nama_po,
                'pelanggan'         => $order->pelanggan?->nama,
                'brand_kode'        => $order->brand?->kode,
                'brand_warna'       => $order->brand?->warna_primary,
                'deadline_customer' => $order->deadline_customer?->toDateString(),
                'is_locked'         => $order->isLocked(),
                'is_special_order'  => (bool) $order->is_special_order,
                'has_rijek'         => $order->has_rijek > 0,
                'total_items'       => (int) ($order->items_sum_quantity ?? 0),
                'days_remaining'    => $daysRemaining,
                'paket_order'       => $order->paketOrder ? [
                    'nama'      => $order->paketOrder->nama,
                    'warna'     => $order->paketOrder->warna,
                    'prioritas' => $order->paketOrder->prioritas,
                ] : null,
            ];
        }

        return Inertia::render('Production/Kanban', ['columns' => $columns]);
    }

    public function progress(Request $request, Order $order)
    {
        Gate::authorize('order.view');
        $this->guardBrandOwnership($request, $order);

        $order->load(['progressDetails.progress', 'progressDetails.updater', 'rijeks.progress', 'rijeks.creator']);

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

        if ($order->status_po === 'selesai') {
            return back()->with('error', 'Tidak dapat memperbarui progress karena PO sudah selesai.');
        }

        $isSending = strtoupper($detail->progress->nama_progress ?? '') === 'SENDING';

        if ($isSending && ! $order->is_lunas && ! $order->is_special_order) {
            return back()->with('error', 'Tahap Sending belum bisa diupdate. Konfirmasi LUNAS dari Keuangan diperlukan terlebih dahulu.');
        }

        $data = $request->validate([
            'status'         => ['required', Rule::in(OrderProgressDetail::STATUSES)],
            'catatan'        => ['nullable', 'string'],
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

    public function bulkUpdateProgress(Request $request, Order $order)
    {
        Gate::authorize('production.update-progress');
        $this->guardBrandOwnership($request, $order);

        if ($order->status_po === 'selesai') {
            return back()->with('error', 'Tidak dapat memperbarui progress karena PO sudah selesai.');
        }

        $data = $request->validate([
            'ids'            => ['required', 'array'],
            'ids.*'          => ['required', 'uuid', 'exists:order_progress_details,id'],
            'status'         => ['required', Rule::in(OrderProgressDetail::STATUSES)],
            'catatan'        => ['nullable', 'string'],
            'kendala'        => ['nullable', 'string'],
            'skipped_reason' => ['required_if:status,skipped', 'nullable', 'string'],
            'nama_ekspedisi' => ['nullable', 'string', 'max:100'],
            'no_resi'        => ['nullable', 'string', 'max:100'],
        ]);

        $details = OrderProgressDetail::whereIn('id', $data['ids'])
            ->where('order_id', $order->id)
            ->with('progress')
            ->get();

        foreach ($details as $detail) {
            $isSending = strtoupper($detail->progress->nama_progress ?? '') === 'SENDING';

            // Skip updating SENDING if it's locked by payment
            if ($isSending && ! $order->is_lunas && ! $order->is_special_order) {
                continue;
            }

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


        }

        return back()->with('success', 'Progress berhasil diperbarui secara massal.');
    }

    public function storeRijek(Request $request, Order $order)
    {
        Gate::authorize('production.add-reject');
        $this->guardBrandOwnership($request, $order);

        if ($order->status_po === 'selesai') {
            return back()->with('error', 'Tidak dapat mencatat rijek karena PO sudah selesai.');
        }

        $data = $request->validate([
            'progress_id' => ['nullable', 'uuid', 'exists:progress,id'],
            'order_item_id' => ['nullable', 'uuid'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'jenis' => ['required', Rule::in(Rijek::JENIS)],
            'tingkat' => ['required', Rule::in(Rijek::TINGKAT)],
            'kendala' => ['required', 'string'],
            'penanganan' => ['nullable', 'string'],
        ]);

        $rijek = Rijek::create([
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

        $stageName = 'Produksi';
        if (! empty($data['progress_id'])) {
            $progressObj = Progress::find($data['progress_id']);
            if ($progressObj) {
                $stageName = $progressObj->nama_progress;
            }
        }



        return back()->with('success', 'Rijek berhasil dicatat.');
    }

    public function updateRijek(Request $request, Order $order, Rijek $rijek)
    {
        Gate::authorize('production.add-reject');
        $this->guardBrandOwnership($request, $order);
        abort_unless($rijek->order_id === $order->id, 404);

        if (in_array($order->status_po, ['sudah_dikirim', 'selesai'], true)) {
            return back()->with('error', 'Tidak dapat mengubah rijek karena PO sudah dikirim/selesai.');
        }

        $data = $request->validate([
            'progress_id' => ['nullable', 'uuid', 'exists:progress,id'],
            'order_item_id' => ['nullable', 'uuid'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'jenis' => ['required', Rule::in(Rijek::JENIS)],
            'tingkat' => ['required', Rule::in(Rijek::TINGKAT)],
            'kendala' => ['required', 'string'],
            'penanganan' => ['nullable', 'string'],
        ]);

        $oldProgressId = $rijek->progress_id;
        $rijek->update($data);

        if (! empty($data['progress_id'])) {
            OrderProgressDetail::where('order_id', $order->id)
                ->where('progress_id', $data['progress_id'])
                ->update(['has_reject' => true]);
        }

        if (! empty($oldProgressId) && $oldProgressId !== ($data['progress_id'] ?? null)) {
            $otherExists = Rijek::where('order_id', $order->id)
                ->where('progress_id', $oldProgressId)
                ->exists();
            if (! $otherExists) {
                OrderProgressDetail::where('order_id', $order->id)
                    ->where('progress_id', $oldProgressId)
                    ->update(['has_reject' => false]);
            }
        }

        return back()->with('success', 'Rijek berhasil diperbarui.');
    }

    public function destroyRijek(Request $request, Order $order, Rijek $rijek)
    {
        Gate::authorize('production.add-reject');
        $this->guardBrandOwnership($request, $order);
        abort_unless($rijek->order_id === $order->id, 404);

        if (in_array($order->status_po, ['sudah_dikirim', 'selesai'], true)) {
            return back()->with('error', 'Tidak dapat menghapus rijek karena PO sudah dikirim/selesai.');
        }

        $progressId = $rijek->progress_id;
        $rijek->delete();

        if (! empty($progressId)) {
            $otherExists = Rijek::where('order_id', $order->id)
                ->where('progress_id', $progressId)
                ->exists();
            if (! $otherExists) {
                OrderProgressDetail::where('order_id', $order->id)
                    ->where('progress_id', $progressId)
                    ->update(['has_reject' => false]);
            }
        }

        return back()->with('success', 'Rijek berhasil dihapus.');
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
        'selesai' => [],
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

        if ($to === 'sudah_dikirim' && ! $order->is_lunas && ! $order->is_special_order) {
            return response()->json([
                'success' => false,
                'error' => 'Gagal memindahkan. Konfirmasi LUNAS dari Keuangan diperlukan terlebih dahulu sebelum pesanan dapat dikirim.',
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
        $user = $request->user();
        if ($user->isSuperadmin()) return;
        abort_unless($user->hasAccessToBrand($order->brand_id), 403);
    }
}
