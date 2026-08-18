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
        $brandId = match (true) {
            $user->hasRole('admin_reseller') => BrandContext::effectiveBrandIds($request),
            $user->hasRole('admin_produksi') => null, // lintas-brand: tampil semua
            default                          => BrandContext::current($request),
        };

        $statusPoCol = 'status_po';
        $orders = Order::query()
            ->forBrand($brandId)
            ->published()
            ->where($statusPoCol, '!=', 'sudah_dikirim')
            ->select([
                'id', 'no_po', 'nama_po', 'pelanggan_id', 'brand_id', 'status_po',
                'tanggal_masuk', 'deadline_customer', 'start_production_date', 'end_production_date',
                'created_at'
            ])
            ->with([
                'pelanggan:id,nama',
                'brand:id,kode,nama_brand',
                'items:id,order_id,is_addon,quantity,jml_atasan'
            ])
            ->orderByDesc('created_at')
            ->get();

        $statusColors = [
            'published'        => '#3B82F6',
            'on_progress'      => '#F59E0B',
            'selesai_produksi' => '#22C55E',
            'siap_dikirim'     => '#06B6D4',
            'sudah_dikirim'    => '#8B5CF6',
            'delay'            => '#EF4444',
            'hold'             => '#F97316',
            'selesai'          => '#10B981',
        ];

        $statusLabels = [
            'published'        => 'Baru Masuk',
            'on_progress'      => 'Sedang Produksi',
            'selesai_produksi' => 'Selesai Produksi',
            'siap_dikirim'     => 'Siap Dikirim',
            'sudah_dikirim'    => 'Sudah Dikirim',
            'delay'            => 'Delay',
            'hold'             => 'Hold',
            'selesai'          => 'Selesai',
        ];

        $items = $orders->map(function (Order $order) use ($statusColors, $statusLabels) {
            $start = $order->start_production_date ?? $order->tanggal_masuk;
            $end   = $order->end_production_date ?? $order->deadline_customer;

            // Pastikan end >= start supaya bar minimal 1 hari
            if ($end < $start) $end = $start;

            $effectiveDeadline = $order->end_production_date ?? $order->deadline_customer;
            $today = now()->startOfDay();
            $daysRemaining = null;
            if ($effectiveDeadline) {
                $deadlineCarbon = \Carbon\Carbon::parse((string) $effectiveDeadline)->startOfDay();
                $daysRemaining = (int) $today->diffInDays($deadlineCarbon, false);
                if ($deadlineCarbon < $today && $daysRemaining > 0) {
                    $daysRemaining = -$daysRemaining;
                }
            }

            return [
                'id'                  => $order->id,
                'no_po'               => $order->no_po,
                'nama_po'             => $order->nama_po,
                'pelanggan'           => $order->pelanggan?->nama,
                'brand_kode'          => $order->brand?->kode,
                'total_pcs'           => (int) $order->items->sum('jml_atasan'),
                'status_po'           => $order->status_po,
                'status_label'        => $statusLabels[$order->status_po] ?? $order->status_po,
                'color'               => $statusColors[$order->status_po] ?? '#94A3B8',
                'tanggal_masuk'       => $order->tanggal_masuk ? \Carbon\Carbon::parse((string) $order->tanggal_masuk)->format('Y-m-d') : null,
                'deadline_customer'   => $order->deadline_customer ? \Carbon\Carbon::parse((string) $order->deadline_customer)->format('Y-m-d') : null,
                'end_production_date' => $order->end_production_date ? \Carbon\Carbon::parse((string) $order->end_production_date)->format('Y-m-d') : null,
                'start'               => $start ? \Carbon\Carbon::parse((string) $start)->format('Y-m-d') : null,
                'end'                 => $end ? \Carbon\Carbon::parse((string) $end)->format('Y-m-d') : null,
                'days_remaining'      => $daysRemaining,
                'detail_url'          => route('produksi.progress', $order->id),
                'created_at'          => $order->created_at ? $order->created_at->toIso8601String() : null,
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
        $brandId = match (true) {
            $user->hasRole('admin_reseller') => BrandContext::effectiveBrandIds($request),
            $user->hasRole('admin_produksi') => null, // lintas-brand: tampil semua
            default                          => BrandContext::current($request),
        };

        $statusPoCol = 'status_po';
        $orders = Order::query()
            ->forBrand($brandId)
            ->published()
            ->whereNotIn($statusPoCol, ['selesai', 'sudah_dikirim'])
            ->select([
                'id', 'no_po', 'nama_po', 'pelanggan_id', 'brand_id', 'paket_order_id',
                'status_po', 'tanggal_masuk', 'deadline_customer', 'start_production_date',
                'end_production_date', 'is_special_order', 'created_at', 'updated_at'
            ])
            ->with([
                'pelanggan:id,nama',
                'lockStatus:id,order_id,is_locked',
                'brand:id,kode,warna_primary',
                'paketOrder:id,nama,warna,prioritas',
                'progressDetails:id,order_id,progress_id,status',
                'progressDetails.progress:id,nama_progress,warna,urutan',
                'items:id,order_id,is_addon,quantity,jml_atasan'
            ])
            ->withCount(['rijeks as has_rijek' => fn($q) => $q->where('status', '!=', 'selesai')])
            ->orderByRaw('COALESCE(end_production_date, deadline_customer) ASC')
            ->get();

        $columns = [
            'published'        => ['label' => 'Baru Masuk',       'color' => '#3B82F6', 'orders' => []],
            'on_progress'      => ['label' => 'Sedang Produksi',  'color' => '#F59E0B', 'orders' => []],
            'selesai_produksi' => ['label' => 'Selesai Produksi', 'color' => '#22C55E', 'orders' => []],
            'siap_dikirim'     => ['label' => 'Siap Dikirim',     'color' => '#06B6D4', 'orders' => []],
            'sudah_dikirim'    => ['label' => 'Sudah Dikirim',    'color' => '#8B5CF6', 'orders' => []],
            'hold'             => ['label' => 'Hold',             'color' => '#F97316', 'orders' => []],
        ];

        foreach ($orders as $order) {
            $status = $order->status_po;
            // Delay orders are in production workflow, map them to on_progress column
            $columnKey = ($status === 'delay') ? 'on_progress' : $status;
            if (! isset($columns[$columnKey])) continue;

            $effectiveDeadline = $order->end_production_date ?? $order->deadline_customer;
            $daysRemaining = $effectiveDeadline
                ? (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse((string)$effectiveDeadline), false)
                : null;

            // Find active stages (status === 'on_progress')
            $activeStages = $order->progressDetails
                ->filter(fn($d) => $d->status === 'on_progress')
                ->map(fn($d) => [
                    'nama' => $d->progress?->nama_progress,
                    'warna' => $d->progress?->warna,
                ])
                ->values()
                ->toArray();

            if (empty($activeStages)) {
                if ($status === 'on_progress' || $status === 'delay') {
                    $firstPending = $order->progressDetails
                        ->filter(fn($d) => $d->status === 'pending')
                        ->sortBy(fn($d) => $d->progress?->urutan ?? 0)
                        ->first();
                    if ($firstPending) {
                        $activeStages[] = [
                            'nama' => $firstPending->progress?->nama_progress,
                            'warna' => $firstPending->progress?->warna,
                        ];
                    }
                }
            }

            $columns[$columnKey]['orders'][] = [
                'id'                  => $order->id,
                'status_po'           => $order->status_po,
                'no_po'               => $order->no_po,
                'nama_po'             => $order->nama_po,
                'pelanggan'           => $order->pelanggan?->nama,
                'brand_kode'          => $order->brand?->kode,
                'brand_warna'         => $order->brand?->warna_primary,
                'deadline_customer'   => $order->deadline_customer ? \Carbon\Carbon::parse((string) $order->deadline_customer)->format('Y-m-d') : null,
                'end_production_date' => $order->end_production_date ? \Carbon\Carbon::parse((string) $order->end_production_date)->format('Y-m-d') : null,
                'effective_deadline'  => $effectiveDeadline ? \Carbon\Carbon::parse((string) $effectiveDeadline)->format('Y-m-d') : null,
                'is_locked'           => $order->isLocked(),
                'is_special_order'    => (bool) $order->is_special_order,
                'has_rijek'           => $order->has_rijek > 0,
                'total_items'         => (int) (function() use ($order) {
                    $coreItems = $order->items->filter(fn($i) => empty($i->is_addon));
                    $hasAnyJmlAtasan = $coreItems->contains(fn($i) => $i->jml_atasan !== null && $i->jml_atasan !== '');
                    return $coreItems->sum(function ($i) use ($hasAnyJmlAtasan) {
                        if ($i->jml_atasan !== null && $i->jml_atasan !== '') {
                            return (int)$i->jml_atasan;
                        }
                        return $hasAnyJmlAtasan ? 0 : (int)$i->quantity;
                    });
                })(),
                'days_remaining'      => $daysRemaining,
                'paket_order'       => $order->paketOrder ? [
                    'nama'      => $order->paketOrder->nama,
                    'warna'     => $order->paketOrder->warna,
                    'prioritas' => $order->paketOrder->prioritas,
                ] : null,
                'active_stages'     => $activeStages,
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
            'ekspedisis' => \App\Models\Master\Ekspedisi::active()->orderBy('nama')->get(['id', 'nama']),
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
            'ekspedisi_id'   => ['nullable', 'uuid', 'exists:ekspedisi,id'],
            'nama_ekspedisi' => [$isSending && $request->input('status') === 'selesai' && ! $order->isPickupCod() && !$request->filled('ekspedisi_id') ? 'required' : 'nullable', 'string', 'max:100'],
            'no_resi'        => ['nullable', 'string', 'max:100'],
        ]);

        $this->statusManager->updateProgressDetail(
            $order,
            $detail,
            $data['status'],
            $data['catatan'] ?? null,
            $data['kendala'] ?? null,
            $data['skipped_reason'] ?? null,
            $request->user()
        );

        if ($isSending && $data['status'] === 'selesai') {
            $ekspedisiName = null;
            if (!empty($data['ekspedisi_id'])) {
                $ekspedisiName = \App\Models\Master\Ekspedisi::find($data['ekspedisi_id'])?->nama;
            } elseif (!empty($data['nama_ekspedisi'])) {
                $ekspedisiName = $data['nama_ekspedisi'];
            }

            $order->update([
                'ekspedisi_id'   => $data['ekspedisi_id'] ?? null,
                'nama_ekspedisi' => $order->isPickupCod() ? ($ekspedisiName ?: 'Ambil di Tempat / COD') : ($ekspedisiName ?? null),
                'no_resi'        => $data['no_resi'] ?? null,
            ]);

            // Sync with Invoice
            /** @var \App\Models\Order\Invoice|null $invoice */
            $invoice = $order->invoices()->first();
            if ($invoice) {
                $invoice->update([
                    'jasa_pengiriman' => $order->nama_ekspedisi,
                ]);
            }
        }

        \App\Services\Notifications\IdealNotificationService::dispatch('progress_updated', [
            'no_po' => $order->no_po,
            'brand_id' => $order->brand_id,
            'brand_nama' => $order->brand?->nama_brand ?? $order->brand_id,
            'stage' => $detail->progress->nama_progress ?? '-',
            'action_url' => route('produksi.progress', $order->id),
        ]);

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
            'ekspedisi_id'   => ['nullable', 'uuid', 'exists:ekspedisi,id'],
            'nama_ekspedisi' => ['nullable', 'string', 'max:100'],
            'no_resi'        => ['nullable', 'string', 'max:100'],
        ]);

        $details = OrderProgressDetail::whereIn('id', $data['ids'])
            ->where(['order_id' => $order->id])
            ->with('progress')
            ->get();

        foreach ($details as $detail) {
            $isSending = strtoupper($detail->progress->nama_progress ?? '') === 'SENDING';

            // Skip updating SENDING if it's locked by payment
            if ($isSending && ! $order->is_lunas && ! $order->is_special_order) {
                continue;
            }

            $this->statusManager->updateProgressDetail(
                $order,
                $detail,
                $data['status'],
                $data['catatan'] ?? null,
                $data['kendala'] ?? null,
                $data['skipped_reason'] ?? null,
                $request->user()
            );

            if ($isSending && $data['status'] === 'selesai') {
                $ekspedisiName = null;
                if (!empty($data['ekspedisi_id'])) {
                    $ekspedisiName = \App\Models\Master\Ekspedisi::find($data['ekspedisi_id'])?->nama;
                } elseif (!empty($data['nama_ekspedisi'])) {
                    $ekspedisiName = $data['nama_ekspedisi'];
                }

                $order->update([
                    'ekspedisi_id'   => $data['ekspedisi_id'] ?? null,
                    'nama_ekspedisi' => $ekspedisiName ?? null,
                    'no_resi'        => $data['no_resi'] ?? null,
                ]);

                // Sync with Invoice
                /** @var \App\Models\Order\Invoice|null $invoice */
                $invoice = $order->invoices()->first();
                if ($invoice) {
                    $invoice->update([
                        'jasa_pengiriman' => $order->nama_ekspedisi,
                    ]);
                }
            }

            \App\Services\Notifications\IdealNotificationService::dispatch('progress_updated', [
                'no_po' => $order->no_po,
                'brand_id' => $order->brand_id,
                'brand_nama' => $order->brand?->nama_brand ?? $order->brand_id,
                'stage' => $detail->progress->nama_progress ?? '-',
                'action_url' => route('produksi.progress', $order->id),
            ]);
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
            OrderProgressDetail::where([
                'order_id' => $order->id,
                'progress_id' => $data['progress_id'],
            ])->update(['has_reject' => true]);
        }

        $stageName = 'Produksi';
        if (! empty($data['progress_id'])) {
            $progressObj = Progress::find($data['progress_id']);
            if ($progressObj) {
                $stageName = $progressObj->nama_progress;
            }
        }

        \App\Services\Notifications\IdealNotificationService::dispatch('rijek_reported', [
            'no_po' => $order->no_po,
            'brand_id' => $order->brand_id,
            'brand_nama' => $order->brand?->nama_brand ?? $order->brand_id,
            'stage' => $stageName,
            'action_url' => route('produksi.progress', $order->id),
        ]);

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
            OrderProgressDetail::where([
                'order_id' => $order->id,
                'progress_id' => $data['progress_id'],
            ])->update(['has_reject' => true]);
        }

        if (! empty($oldProgressId) && $oldProgressId !== ($data['progress_id'] ?? null)) {
            $otherExists = Rijek::where([
                'order_id' => $order->id,
                'progress_id' => $oldProgressId,
            ])->exists();
            if (! $otherExists) {
                OrderProgressDetail::where([
                    'order_id' => $order->id,
                    'progress_id' => $oldProgressId,
                ])->update(['has_reject' => false]);
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
            $otherExists = Rijek::where([
                'order_id' => $order->id,
                'progress_id' => $progressId,
            ])->exists();
            if (! $otherExists) {
                OrderProgressDetail::where([
                    'order_id' => $order->id,
                    'progress_id' => $progressId,
                ])->update(['has_reject' => false]);
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
            $errorMsg = "Transisi '{$from}' → '{$to}' tidak diizinkan via Kanban. Gunakan halaman progress untuk update detail tahapan.";
            if ($request->hasHeader('X-Inertia')) {
                return back()->with('error', $errorMsg);
            }
            return response()->json([
                'success' => false,
                'error' => $errorMsg,
            ], 422);
        }

        if ($to === 'sudah_dikirim' && ! $order->is_lunas && ! $order->is_special_order) {
            $errorMsg = 'Gagal memindahkan. Konfirmasi LUNAS dari Keuangan diperlukan terlebih dahulu sebelum pesanan dapat dikirim.';
            if ($request->hasHeader('X-Inertia')) {
                return back()->with('error', $errorMsg);
            }
            return response()->json([
                'success' => false,
                'error' => $errorMsg,
            ], 422);
        }

        $order->update(['status_po' => $to]);

        if ($request->hasHeader('X-Inertia')) {
            $statusLabel = $to === 'hold' ? 'ditangguhkan (hold)' : 'diaktifkan kembali';
            return back()->with('success', "Status PO berhasil diperbarui menjadi {$statusLabel}.");
        }

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
