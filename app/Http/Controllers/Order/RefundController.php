<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Models\Order\Refund;
use App\Services\NumberGenerator;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RefundController extends Controller
{
    public function __construct(private readonly NumberGenerator $numbers) {}

    public function index(Request $request)
    {
        $brandId = BrandContext::current($request);

        $query = Refund::query()
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
            ->with(['order:id,no_po,nama_po,pelanggan_id', 'order.pelanggan:id,nama', 'creator:id,name']);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('refund_number', 'like', "%{$search}%")
                  ->orWhereHas('order', fn ($x) => $x->where('no_po', 'like', "%{$search}%"));
            });
        }

        $refunds = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return Inertia::render('Finance/RefundIndex', [
            'refunds' => $refunds,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
            ],
            'statuses' => Refund::STATUSES,
            'jenis_options' => Refund::JENIS_MASALAH,
            'can' => [
                'create' => $request->user()->can('order.refund'),
                'review' => $request->user()->can('finance.manage-refund'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('order.refund');

        $data = $request->validate([
            'order_id' => ['required', 'uuid', 'exists:orders,id'],
            'alasan' => ['required', 'string', 'min:5'],
            'jenis_masalah' => ['required', Rule::in(Refund::JENIS_MASALAH)],
            'jumlah_item' => ['required', 'integer', 'min:1'],
            'nominal_refund' => ['required', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string'],
        ]);

        $order = Order::findOrFail($data['order_id']);
        abort_if($order->isDraft(), 422, 'PO draft tidak bisa di-refund.');

        $brandId = BrandContext::current($request);
        if (! $request->user()->isSuperadmin() && $order->brand_id !== $brandId) abort(403);

        if ($data['nominal_refund'] > $order->total_tagihan) {
            return back()->withErrors(['nominal_refund' => 'Nominal refund tidak boleh melebihi total tagihan PO.']);
        }

        Refund::create([
            ...$data,
            'brand_id' => $order->brand_id,
            'refund_number' => $this->numbers->generateRefundNumber($order->brand),
            'status' => 'pending_review',
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Refund berhasil diajukan dan menunggu review keuangan.');
    }

    public function publish(Request $request, Refund $refund)
    {
        Gate::authorize('finance.manage-refund');
        abort_unless(in_array($refund->status, ['pending_review', 'approved'], true), 422);

        $refund->update([
            'status' => 'published',
            'published_by' => $request->user()->id,
            'published_at' => now(),
        ]);

        \App\Services\ActivityLogger::log('publish', 'refund', $refund, "Terbitkan refund {$refund->refund_number}");

        return back()->with('success', "Refund {$refund->refund_number} diterbitkan.");
    }

    public function reject(Request $request, Refund $refund)
    {
        Gate::authorize('finance.manage-refund');
        abort_unless($refund->status === 'pending_review', 422);

        $data = $request->validate([
            'rejected_reason' => ['required', 'string', 'min:5'],
        ]);

        $refund->update([
            'status' => 'rejected',
            'rejected_reason' => $data['rejected_reason'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('info', 'Refund ditolak. Pengaju dapat revisi dan ajukan ulang.');
    }
}
