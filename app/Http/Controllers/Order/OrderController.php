<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Master\BahanKain;
use App\Models\Master\BankAccount;
use App\Models\Master\Customer;
use App\Models\Master\Iklan;
use App\Models\Master\JenisOrder;
use App\Models\Master\KategoriOrder;
use App\Models\Master\Logo;
use App\Models\Master\PolaJahitan;
use App\Models\Master\Printing;
use App\Models\Master\Product;
use App\Models\Master\Resleting;
use App\Models\Master\Size;
use App\Models\Master\SumberOrder;
use App\Models\Master\Reseller;
use App\Models\Order\Invoice;
use App\Models\Order\InvoiceItem;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\OrderNameset;
use App\Models\Order\OrderPayment;
use App\Services\NumberGenerator;
use App\Services\POStatusManager;
use App\Services\Notifications\DynamicNotificationService;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
{
    public function __construct(
        private readonly NumberGenerator $numbers,
        private readonly POStatusManager $statusManager,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('order.view');

        $user      = $request->user();
        $brandId   = BrandContext::current($request);
        $canSeeMultiBrand = $user->isSuperadmin() || $user->hasRole(['owner', 'admin_keuangan', 'admin_produksi']);

        $query = Order::query()
            ->forBrand($brandId)
            ->with(['pelanggan:id,nama', 'brand:id,nama_brand,kode'])
            ->withCount(['items', 'progressDetails']);

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('no_po', 'like', "%{$search}%")
                  ->orWhere('nama_po', 'like', "%{$search}%")
                  ->orWhereHas('pelanggan', fn ($x) => $x->where('nama', 'like', "%{$search}%"));
            });
        }

        if ($user->hasRole('admin_produksi')) {
            $query->where('status_po', '!=', 'draft');
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status_po', $status);
        }

        if ($canSeeMultiBrand && ($filterBrand = $request->string('brand_id')->toString())) {
            $query->where('orders.brand_id', $filterBrand);
        }

        if ($dateFrom = $request->string('date_from')->toString()) {
            $query->whereDate('tanggal_masuk', '>=', $dateFrom);
        }
        if ($dateTo = $request->string('date_to')->toString()) {
            $query->whereDate('tanggal_masuk', '<=', $dateTo);
        }

        // Summary per status — query terpisah tanpa with/withCount agar GROUP BY aman
        $statusCounts = Order::query()
            ->forBrand($brandId)
            ->when($user->hasRole('admin_produksi'), fn ($q) => $q->where('status_po', '!=', 'draft'))
            ->when($canSeeMultiBrand && $request->string('brand_id')->toString(), fn ($q, $v) => $q->where('orders.brand_id', $v))
            ->when($request->string('q')->toString(), fn ($q, $v) => $q->where(function ($w) use ($v) {
                $w->where('no_po', 'like', "%{$v}%")->orWhere('nama_po', 'like', "%{$v}%");
            }))
            ->when($request->string('date_from')->toString(), fn ($q, $v) => $q->whereDate('tanggal_masuk', '>=', $v))
            ->when($request->string('date_to')->toString(), fn ($q, $v) => $q->whereDate('tanggal_masuk', '<=', $v))
            ->selectRaw('status_po, count(*) as total')
            ->groupBy('status_po')
            ->pluck('total', 'status_po')
            ->toArray();

        $orders = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        $brands = $canSeeMultiBrand
            ? Brand::orderBy('nama_brand')->get(['id', 'nama_brand', 'kode'])
            : [];

        $visibleStatuses = $user->hasRole('admin_produksi')
            ? array_values(array_filter(Order::STATUSES, fn ($s) => $s !== 'draft'))
            : Order::STATUSES;

        return Inertia::render('Order/Index', [
            'orders' => $orders,
            'filters' => [
                'q'        => $request->string('q')->toString(),
                'status'   => $request->string('status')->toString(),
                'brand_id' => $request->string('brand_id')->toString(),
                'date_from' => $request->string('date_from')->toString(),
                'date_to'   => $request->string('date_to')->toString(),
            ],
            'statuses'     => $visibleStatuses,
            'statusCounts' => $statusCounts,
            'brands'       => $brands,
            'can' => [
                'create'          => $user->can('order.create'),
                'update'          => $user->can('order.update'),
                'delete'          => $user->can('order.delete'),
                'publish'         => $user->can('order.publish'),
                'filter_by_brand' => $canSeeMultiBrand,
            ],
        ]);
    }

    public function create(Request $request)
    {
        Gate::authorize('order.create');
        $brandId = BrandContext::current($request);
        abort_unless($brandId, 400, 'Brand aktif belum dipilih');

        return Inertia::render('Order/Form', [
            'mode' => 'create',
            'masters' => $this->mastersForForm($brandId),
            'order' => null,
        ]);
    }

    public function edit(Request $request, Order $order)
    {
        Gate::authorize('order.update');
        $this->guardBrandOwnership($request, $order);

        if (! $order->isDraft() && $order->isLocked()) {
            return redirect()->route('orders.show', $order->id)
                ->with('error', 'PO sudah ter-lock. Untuk edit, lakukan unlock dengan alasan dari halaman preview.');
        }

        $order->load(['items.namesets', 'items.polaJahitan', 'payments.bank']);

        return Inertia::render('Order/Form', [
            'mode' => 'edit',
            'masters' => $this->mastersForForm($order->brand_id),
            'order' => $order,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('order.create');
        $brandId = BrandContext::current($request);
        abort_unless($brandId, 400);

        $data = $this->validatePayload($request);
        $user = $request->user();
        $brand = Brand::findOrFail($brandId);

        $order = DB::transaction(function () use ($brand, $data, $user) {
            $order = Order::create([
                'brand_id' => $brand->id,
                'no_po' => $this->numbers->generateOrderNumber($brand, $data['nama_po']),
                'nama_po' => $data['nama_po'],
                'status_po' => 'draft',
                'is_special_order' => $data['is_special_order'] ?? false,
                'tanggal_masuk' => $data['tanggal_masuk'],
                'deadline_customer' => $data['deadline_customer'],
                'kategori_order_id' => $data['kategori_order_id'] ?? null,
                'jenis_order_id' => $data['jenis_order_id'] ?? null,
                'sumber_order_id' => $data['sumber_order_id'] ?? null,
                'pelanggan_id' => $data['pelanggan_id'],
                'reseller_id' => $data['reseller_id'] ?? null,
                'printing_ids' => $data['printing_ids'] ?? null,
                'iklan_id' => $data['iklan_id'] ?? null,
                'catatan' => $data['catatan'] ?? null,
                'created_by' => $user->id,
            ]);

            $this->syncItems($order, $data['items'] ?? []);
            $this->syncPayments($order, $data['payments'] ?? [], $user->id);
            $order->update(['total_tagihan' => $order->items()->sum('subtotal')]);

            $order->load('items');
            $totalTagihan = (float) $order->total_tagihan;
            $dp = (float) $order->payments()->where('payment_type', 'dp')->sum('amount');

            $invoice = Invoice::create([
                'brand_id'        => $order->brand_id,
                'order_id'        => $order->id,
                'invoice_number'  => $this->numbers->generateInvoiceNumber($brand, $order),
                'tanggal_terbit'  => now()->toDateString(),
                'jatuh_tempo'     => now()->addDays(14)->toDateString(),
                'status'          => 'draft',
                'total_tagihan'   => $totalTagihan,
                'dp_amount'       => $dp,
                'sisa_pembayaran' => max(0, $totalTagihan - $dp),
                'created_by'      => $user->id,
            ]);

            foreach ($order->items as $item) {
                InvoiceItem::create([
                    'invoice_id'   => $invoice->id,
                    'produk'       => $item->nama_produk . ($item->varian_label ? " ({$item->varian_label})" : ''),
                    'jumlah'       => $item->quantity,
                    'harga_satuan' => $item->harga_satuan,
                    'subtotal'     => $item->subtotal,
                    'is_addon'     => (bool) $item->is_addon,
                ]);
            }

            return $order;
        });

        return redirect()->route('orders.show', $order->id)->with('success', 'PO draft berhasil dibuat.');
    }

    public function update(Request $request, Order $order)
    {
        Gate::authorize('order.update');
        $this->guardBrandOwnership($request, $order);

        if (! $order->isDraft() && $order->isLocked()) {
            abort(403, 'PO ter-lock');
        }

        $data = $this->validatePayload($request);
        $user = $request->user();

        DB::transaction(function () use ($order, $data, $user) {
            $updateData = [
                'nama_po' => $data['nama_po'],
                'is_special_order' => $data['is_special_order'] ?? false,
                'tanggal_masuk' => $data['tanggal_masuk'],
                'deadline_customer' => $data['deadline_customer'],
                'kategori_order_id' => $data['kategori_order_id'] ?? null,
                'jenis_order_id' => $data['jenis_order_id'] ?? null,
                'sumber_order_id' => $data['sumber_order_id'] ?? null,
                'pelanggan_id' => $data['pelanggan_id'],
                'reseller_id' => $data['reseller_id'] ?? null,
                'printing_ids' => $data['printing_ids'] ?? null,
                'iklan_id' => $data['iklan_id'] ?? null,
                'catatan' => $data['catatan'] ?? null,
                'updated_by' => $user->id,
            ];


            $order->update($updateData);

            $this->syncItems($order, $data['items'] ?? []);
            $order->update(['total_tagihan' => $order->items()->sum('subtotal')]);

            // Sync invoice
            $invoice = $order->invoices()->first();
            if ($invoice) {
                $invoice->items()->delete();
                foreach ($order->items as $item) {
                    InvoiceItem::create([
                        'invoice_id'   => $invoice->id,
                        'produk'       => $item->nama_produk . ($item->varian_label ? " ({$item->varian_label})" : ''),
                        'jumlah'       => $item->quantity,
                        'harga_satuan' => $item->harga_satuan,
                        'subtotal'     => $item->subtotal,
                        'is_addon'     => (bool) $item->is_addon,
                    ]);
                }

                $totalTagihan = $order->totalTagihan();
                $totalPaid = $order->totalPaid();
                $newSisa = max(0, $totalTagihan - $totalPaid);

                $invoice->update([
                    'total_tagihan' => $totalTagihan,
                    'total_bayar' => $totalPaid,
                    'sisa_pembayaran' => $newSisa,
                    'status' => $newSisa <= 0 ? 'paid' : $invoice->status,
                ]);
            }
        });

        return redirect()->route('orders.show', $order->id)->with('success', 'PO berhasil diperbarui.');
    }

    public function show(Request $request, Order $order)
    {
        Gate::authorize('order.view');
        $this->guardBrandOwnership($request, $order);

        if ($request->user()->hasRole('admin_produksi') && $order->status_po === 'draft') {
            abort(403, 'Draft PO tidak dapat diakses oleh Admin Produksi.');
        }

        $order->load([
            'brand', 'pelanggan', 'reseller', 'kategoriOrder', 'jenisOrder', 'sumberOrder', 'iklan',
            'items.namesets.size', 'items.bahanKain', 'items.logo', 'items.printing', 'items.resleting', 'items.polaJahitan',
            'payments.bank', 'payments.recorder', 'payments.verifier',
            'progressDetails.progress', 'progressDetails.updater',
            'rijeks.progress', 'rijeks.creator',
            'lockStatus.lockedBy',
            'changeLogs.changer',
            'invoices',
            'refunds.creator', 'refunds.publisher',
            'repeats', 'repeatFrom',
        ]);

        foreach ($order->items as $item) {
            $item->logo_names = !empty($item->logo_ids)
                ? \App\Models\Master\Logo::whereIn('id', $item->logo_ids)->pluck('nama')->toArray()
                : [];
        }

        $printings = collect();
        if (!empty($order->printing_ids)) {
            $printings = \App\Models\Master\Printing::whereIn('id', $order->printing_ids)->get(['id', 'nama']);
        }

        $brandId = $order->brand_id;
        $banks = BankAccount::active()->where('brand_id', $brandId)->orderBy('bank')->get(['id', 'bank', 'atas_nama', 'nomor_rekening']);

        return Inertia::render('Order/Preview', [
            'order' => $order,
            'printings' => $printings,
            'banks' => $banks,
            'can' => [
                'edit' => $request->user()->can('order.update') && ($order->isDraft() || ! $order->isLocked()),
                'delete' => $request->user()->can('order.delete') && $order->isDraft(),
                'publish' => $request->user()->can('order.publish') && $order->isDraft(),
                'unlock' => $request->user()->isSuperadmin() || $request->user()->hasRole(['owner', 'admin_brand']),
                'repeat' => $request->user()->can('order.create') && ! $order->isDraft(),
                'manage_invoice' => $request->user()->can('finance.manage-invoice'),
                'add_payment' => ! $request->user()->hasRole('admin_produksi'),
                'edit_timeline' => $request->user()->hasRole('admin_produksi'),
                'mark_lunas' => $request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan'),
            ],
        ]);
    }

    public function publish(Request $request, Order $order)
    {
        Gate::authorize('order.publish');
        $this->guardBrandOwnership($request, $order);
        abort_unless($order->isDraft(), 422, 'PO sudah diterbitkan.');
        abort_if($order->items()->count() === 0, 422, 'PO tanpa produk tidak bisa diterbitkan.');

        // Enforce Keuangan validation before PO can be published to production
        $invoice = $order->invoices()->first();
        if (!$invoice || !in_array($invoice->status, ['validated', 'published', 'paid'], true)) {
            return back()->with('error', 'PO tidak bisa diterbitkan karena invoice belum divalidasi oleh Admin Keuangan.');
        }

        // Enforce brand-specific DP percentage before PO can be published
        $brand = $order->brand;
        $minDpPercentage = $brand ? (float) ($brand->min_dp_percentage ?? 0.50) : 0.50;

        $totalTagihan = (float) $order->totalTagihan();
        $totalPaid = (float) $order->totalPaid();
        $minDp = $totalTagihan * $minDpPercentage;
        $minDpFormattedPercent = number_format($minDpPercentage * 100, 0) . '%';

        if ($totalPaid < $minDp) {
            return back()->with('error', 'PO tidak bisa diterbitkan karena total pembayaran terverifikasi (Rp ' . number_format($totalPaid, 0, ',', '.') . ') belum mencapai minimal ' . $minDpFormattedPercent . ' DP dari total tagihan (Rp ' . number_format($totalTagihan, 0, ',', '.') . ').');
        }

        $this->statusManager->publish($order, $request->user());

        \App\Services\ActivityLogger::log('publish', 'order', $order, "Publish PO {$order->no_po}");

        DynamicNotificationService::dispatch('order_published', [
            'no_po' => $order->no_po,
            'brand_id' => $order->brand_id,
            'brand_nama' => $order->brand?->nama_brand ?? $order->brand_id,
            'action_url' => "/orders/{$order->id}"
        ]);

        return back()->with('success', 'PO berhasil diterbitkan dan masuk ke dashboard produksi.');
    }

    public function repeat(Request $request, Order $order)
    {
        Gate::authorize('order.create');
        $this->guardBrandOwnership($request, $order);

        $user = $request->user();
        $brand = $order->brand;

        $newOrder = DB::transaction(function () use ($order, $user, $brand) {
            $clone = $order->replicate([
                'no_po', 'status_po', 'is_repeat_order', 'repeat_from_po_id',
                'published_at', 'published_by', 'created_at', 'updated_at',
            ]);
            $clone->no_po = $this->numbers->generateOrderNumber($brand, $order->nama_po);
            $clone->status_po = 'draft';
            $clone->is_repeat_order = true;
            $clone->repeat_from_po_id = $order->id;
            $clone->tanggal_masuk = now()->toDateString();
            $clone->published_at = null;
            $clone->published_by = null;
            $clone->created_by = $user->id;
            $clone->updated_by = null;
            $clone->save();

            foreach ($order->items as $item) {
                $newItem = $item->replicate(['order_id']);
                $newItem->order_id = $clone->id;
                $newItem->save();

                foreach ($item->namesets as $ns) {
                    $newNs = $ns->replicate(['order_item_id']);
                    $newNs->order_item_id = $newItem->id;
                    $newNs->save();
                }
            }
            $clone->update(['total_tagihan' => $clone->items()->sum('subtotal')]);

            return $clone;
        });

        return redirect()->route('orders.edit', $newOrder->id)
            ->with('success', 'PO baru hasil repeat order. Silakan review & terbitkan.');
    }

    public function destroy(Request $request, Order $order)
    {
        Gate::authorize('order.delete');
        $this->guardBrandOwnership($request, $order);
        abort_unless($order->isDraft(), 422, 'Hanya PO draft yang bisa dihapus.');

        $order->delete();
        return redirect()->route('orders.index')->with('success', 'PO draft berhasil dihapus.');
    }

    public function unlock(Request $request, Order $order)
    {
        $this->guardBrandOwnership($request, $order);
        abort_if($order->isDraft(), 422);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:8', 'max:1000'],
        ]);

        $this->statusManager->unlock($order, $request->user());
        $this->statusManager->logChange($order, $request->user(), $data['reason'], '_unlock', 'locked', 'unlocked');

        return back()->with('info', 'PO di-unlock. Lakukan perubahan, kemudian re-lock untuk mengembalikan proteksi.');
    }

    public function relock(Request $request, Order $order)
    {
        $this->guardBrandOwnership($request, $order);
        $this->statusManager->relock($order, $request->user());
        return back()->with('success', 'PO kembali ter-lock.');
    }

    public function addPayment(Request $request, Order $order)
    {
        Gate::authorize('order.update');
        $this->guardBrandOwnership($request, $order);

        $data = $request->validate([
            'payment_type' => ['required', Rule::in(['dp', 'pelunasan', 'ongkir', 'cashback', 'tambahan_produk', 'return', 'lainnya'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date'],
            'bank_id' => ['nullable', 'uuid', 'exists:bank_accounts,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $isFinanceOrAdmin = $user->hasRole('superadmin') || $user->hasRole('owner') || $user->hasRole('admin_keuangan');

        $payment = OrderPayment::create([
            ...$data,
            'order_id' => $order->id,
            'recorded_by' => $user->id,
            'verified_by' => $isFinanceOrAdmin ? $user->id : null,
            'verified_at' => $isFinanceOrAdmin ? now() : null,
        ]);

        // Also dynamically recalculate/update order's total_tagihan field to match dynamic totalTagihan()
        if ($payment->verified_at !== null) {
            $order->update(['total_tagihan' => $order->totalTagihan()]);
        }

        if (!$isFinanceOrAdmin) {
            \App\Services\Notifications\DynamicNotificationService::dispatch('payment_submitted', [
                'no_po' => $order->no_po,
                'brand_id' => $order->brand_id,
                'brand_nama' => $order->brand?->nama_brand ?? $order->brand_id,
                'nominal' => 'Rp ' . number_format($payment->amount, 0, ',', '.'),
                'action_url' => "/invoices"
            ]);
            return back()->with('success', 'Transaksi berhasil dicatat. Menunggu validasi dari Admin Keuangan.');
        }

        // For verified ones, immediately update the order total_tagihan
        $order->update(['total_tagihan' => $order->totalTagihan()]);

        return back()->with('success', 'Transaksi berhasil dicatat dan diverifikasi.');
    }

    public function draftPdf(Request $request): \Illuminate\Http\Response
    {
        abort_unless(auth()->check(), 401);

        $brandId = BrandContext::current($request);
        $brand   = Brand::findOrFail($brandId);
        $raw     = $request->all();

        $pelanggan  = !empty($raw['pelanggan_id'])      ? Customer::find($raw['pelanggan_id'])           : null;
        $kategori   = !empty($raw['kategori_order_id']) ? KategoriOrder::find($raw['kategori_order_id']) : null;
        $jenisOrder = !empty($raw['jenis_order_id'])    ? JenisOrder::find($raw['jenis_order_id'])       : null;
        $sumber     = !empty($raw['sumber_order_id'])   ? SumberOrder::find($raw['sumber_order_id'])     : null;

        $items = collect($raw['items'] ?? [])->map(function ($item) {
            $item['_bahan_kain']   = !empty($item['bahan_kain_id'])   ? BahanKain::find($item['bahan_kain_id'])?->nama   : null;
            $item['_bahan_kain_bawahan'] = !empty($item['bahan_kain_bawahan_id']) ? BahanKain::find($item['bahan_kain_bawahan_id'])?->nama : null;
            $item['_logo']         = !empty($item['logo_id'])         ? Logo::find($item['logo_id'])?->nama             : null;
            $item['_logos']        = !empty($item['logo_ids'])        ? Logo::whereIn('id', $item['logo_ids'])->pluck('nama')->toArray() : [];
            $item['_resleting']    = !empty($item['resleting_id'])    ? Resleting::find($item['resleting_id'])?->nama    : null;
            $item['_pola_jahitan'] = !empty($item['pola_jahitan_id']) ? PolaJahitan::find($item['pola_jahitan_id'])      : null;

            $item['namesets'] = collect($item['namesets'] ?? [])->map(function ($ns) {
                if (!empty($ns['size_id'])) {
                    $sz = Size::find($ns['size_id']);
                    $ns['_size_label'] = $sz ? "{$sz->kategori_size} - {$sz->ukuran}" : ($ns['size_label'] ?? '-');
                } else {
                    $ns['_size_label'] = $ns['size_label'] ?? '-';
                }

                if (!empty($ns['size_celana_id'])) {
                    $szc = Size::find($ns['size_celana_id']);
                    $ns['_size_celana_label'] = $szc ? "{$szc->kategori_size} - {$szc->ukuran}" : ($ns['size_celana_label'] ?? '-');
                } else {
                    $ns['_size_celana_label'] = $ns['size_celana_label'] ?? '-';
                }
                return $ns;
            })->all();

            return $item;
        });

        $pdf = Pdf::loadView('pdf.spk_draft', compact('brand', 'raw', 'pelanggan', 'kategori', 'jenisOrder', 'sumber', 'items'))
            ->setPaper('a4', 'portrait');

        $filename = 'SPK-DRAFT-' . $brand->kode . '-' . now()->format('YmdHis') . '.pdf';
        return $pdf->download($filename);
    }

    public function spkPdf(Request $request, Order $order)
    {
        Gate::authorize('order.view');
        $this->guardBrandOwnership($request, $order);

        $order->load([
            'brand', 'pelanggan', 'kategoriOrder', 'jenisOrder', 'sumberOrder',
            'items.bahanKain', 'items.bahanKainBawahan', 'items.logo', 'items.resleting', 'items.polaJahitan',
            'items.namesets.size', 'items.namesets.sizeCelana',
        ]);

        $pdf = Pdf::loadView('pdf.spk', ['order' => $order])->setPaper('a4', 'portrait');

        return $pdf->download("SPK-{$order->no_po}.pdf");
    }

    public function updateTimeline(Request $request, Order $order)
    {
        Gate::authorize('production.update-progress');
        $this->guardBrandOwnership($request, $order);

        $data = $request->validate([
            'start_production_date' => ['nullable', 'date'],
            'end_production_date'   => ['nullable', 'date', 'after_or_equal:start_production_date'],
        ]);

        $order->update($data);

        return back()->with('success', 'Timeline produksi berhasil diperbarui.');
    }

    public function markLunas(Request $request, Order $order)
    {
        abort_unless(
            $request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan'),
            403
        );
        $this->guardBrandOwnership($request, $order);

        $toggle = ! $order->is_lunas;

        $order->update([
            'is_lunas'  => $toggle,
            'lunas_at'  => $toggle ? now() : null,
            'lunas_by'  => $toggle ? $request->user()->id : null,
        ]);

        return back()->with('success', $toggle ? 'Order ditandai LUNAS.' : 'Status lunas dibatalkan.');
    }

    private function mastersForForm(string $brandId): array
    {
        $brandQ = fn ($q) => $q->where(function ($w) use ($brandId) {
            $w->where('brand_id', $brandId)->orWhereNull('brand_id');
        });

        return [
            'kategori_orders' => KategoriOrder::active()->where($brandQ)->orderBy('nama')->get(['id', 'nama']),
            'jenis_orders' => JenisOrder::active()->where($brandQ)->orderBy('nama')->get(['id', 'nama']),
            'sumber_orders' => SumberOrder::active()->where($brandQ)->orderBy('nama')->get(['id', 'nama']),
            'iklans' => Iklan::active()->where($brandQ)->orderBy('nama')->get(['id', 'nama', 'platform']),
            'pelanggan' => Customer::active()->where('brand_id', $brandId)->orderBy('nama')->limit(500)->get(['id', 'kode', 'nama', 'nomor_hp']),
            'produk' => Product::active()->where($brandQ)->orderBy('nama')->get(['id', 'nama', 'harga']),
            'bahan_kains' => BahanKain::active()->orderBy('nama')->get(['id', 'nama']),
            'logos' => Logo::active()->orderBy('nama')->get(['id', 'nama']),
            'printings' => Printing::active()->orderBy('nama')->get(['id', 'nama']),
            'resletings' => Resleting::active()->orderBy('nama')->get(['id', 'nama']),
            'pola_jahitans' => PolaJahitan::active()->orderBy('jenis_pola')->orderBy('nama')
                ->get(['id', 'jenis_pola', 'nama']),
            'sizes' => Size::active()->orderBy('kategori_size')->orderBy('urutan')->get(['id', 'kategori_size', 'ukuran']),
            'banks' => BankAccount::active()->where('brand_id', $brandId)->orderBy('bank')->get(['id', 'bank', 'atas_nama', 'nomor_rekening']),
            'resellers' => Reseller::active()->orderBy('nama')->get(['id', 'nama']),
        ];
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'nama_po' => ['required', 'string', 'max:255'],
            'is_special_order' => ['boolean'],
            'tanggal_masuk' => ['required', 'date'],
            'deadline_customer' => ['required', 'date', 'after_or_equal:tanggal_masuk'],
            'kategori_order_id' => ['nullable', 'uuid'],
            'jenis_order_id' => ['nullable', 'uuid'],
            'sumber_order_id' => ['nullable', 'uuid'],
            'pelanggan_id' => ['required', 'uuid', 'exists:customers,id'],
            'reseller_id' => ['nullable', 'uuid', 'exists:resellers,id'],
            'printing_ids' => ['nullable', 'array'],
            'printing_ids.*' => ['uuid', 'exists:printings,id'],
            'iklan_id' => ['nullable', 'uuid', 'exists:iklans,id'],
            'catatan' => ['nullable', 'string'],
            'items' => ['array'],
            'items.*.is_addon' => ['nullable', 'boolean'],
            'items.*.product_id' => ['nullable', 'uuid'],
            'items.*.nama_produk' => ['required', 'string', 'max:255'],
            'items.*.varian_label' => ['nullable', 'string', 'max:100'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.harga_satuan' => ['required', 'numeric', 'min:0'],
            'items.*.bahan_kain_id' => ['nullable', 'uuid'],
            'items.*.bahan_kain_bawahan_id' => ['nullable', 'uuid'],
            'items.*.jenis_setelan' => ['nullable', Rule::in(['stell', 'non_stell', 'atasan_saja', 'bawahan_saja'])],
            'items.*.pola' => ['nullable', 'string', 'max:50'],
            'items.*.logo_id' => ['nullable', 'uuid'],
            'items.*.logo_ids' => ['nullable', 'array'],
            'items.*.logo_ids.*' => ['uuid', 'exists:logos,id'],
            'items.*.printing_id' => ['nullable', 'uuid'],
            'items.*.resleting_id' => ['nullable', 'uuid'],
            'items.*.jenis_rib' => ['nullable', 'string', 'max:100'],
            'items.*.tutup_kerah' => ['nullable', 'string', 'max:100'],
            'items.*.list_kerah' => ['nullable', 'string', 'max:100'],
            'items.*.list_lengan' => ['nullable', 'string', 'max:100'],
            'items.*.list_samping_celana' => ['nullable', 'string', 'max:100'],
            'items.*.list_bawah_celana' => ['nullable', 'string', 'max:100'],
            'items.*.pola_jahitan_lengan_id' => ['nullable', 'uuid'],
            'items.*.pola_jahitan_kerah_id' => ['nullable', 'uuid'],
            'items.*.pola_jahitan_bawah_id' => ['nullable', 'uuid'],
            'items.*.pola_jahitan_pundak_id' => ['nullable', 'uuid'],
            'items.*.pola_jahitan_id' => ['nullable', 'uuid'],
            'items.*.jahitan_list_lengan' => ['nullable', Rule::in(['overdeck', 'stick'])],
            'items.*.warna' => ['nullable', 'string', 'max:100'],
            'items.*.jml_atasan' => ['nullable', 'string', 'max:100'],
            'items.*.jml_bawahan' => ['nullable', 'string', 'max:100'],
            'items.*.jenis_kerah' => ['nullable', 'string', 'max:100'],
            'items.*.catatan' => ['nullable', 'string'],
            'items.*.gambar_desain' => ['nullable', 'string', 'max:255'],
            'items.*.ket_atasan' => ['nullable', 'string'],
            'items.*.ket_bawahan' => ['nullable', 'string'],
            'items.*.gambar_kerah' => ['nullable', 'string', 'max:255'],
            'items.*.gambar_ket_tambahan' => ['nullable', 'string', 'max:255'],
            'items.*.namesets' => ['array'],
            'items.*.namesets.*.nama_punggung' => ['nullable', 'string', 'max:100'],
            'items.*.namesets.*.nomor_punggung' => ['nullable', 'string', 'max:20'],
            'items.*.namesets.*.nama_dada' => ['nullable', 'string', 'max:100'],
            'items.*.namesets.*.nomor_dada' => ['nullable', 'string', 'max:20'],
            'items.*.namesets.*.nama_lengan' => ['nullable', 'string', 'max:100'],
            'items.*.namesets.*.nomor_lengan' => ['nullable', 'string', 'max:20'],
            'items.*.namesets.*.nomor_punggung_2' => ['nullable', 'string', 'max:20'],
            'items.*.namesets.*.nama_punggung_2' => ['nullable', 'string', 'max:100'],
            'items.*.namesets.*.size_id' => ['nullable', 'uuid'],
            'items.*.namesets.*.size_label' => ['nullable', 'string', 'max:50'],
            'items.*.namesets.*.size_celana_id' => ['nullable', 'uuid'],
            'items.*.namesets.*.size_celana_label' => ['nullable', 'string', 'max:50'],
            'items.*.namesets.*.keterangan' => ['nullable', 'string'],
            'payments' => ['nullable', 'array'],
            'payments.*.payment_type' => ['required', Rule::in(['dp', 'pelunasan', 'ongkir', 'cashback', 'tambahan_produk', 'return', 'lainnya'])],
            'payments.*.amount' => ['required', 'numeric', 'min:0'],
            'payments.*.payment_date' => ['required', 'date'],
            'payments.*.bank_id' => ['nullable', 'uuid'],
            'payments.*.notes' => ['nullable', 'string'],
        ]);
    }

    private function syncItems(Order $order, array $items): void
    {
        $order->items()->each(function ($i) {
            $i->namesets()->delete();
            $i->delete();
        });

        foreach ($items as $item) {
            $namesets = $item['namesets'] ?? [];
            unset($item['namesets']);

            $item['order_id'] = $order->id;
            $item['subtotal'] = ($item['quantity'] ?? 0) * ($item['harga_satuan'] ?? 0);

            $created = OrderItem::create($item);

            foreach ($namesets as $idx => $ns) {
                $ns['order_item_id'] = $created->id;
                $ns['urutan'] = $idx;
                OrderNameset::create($ns);
            }
        }
    }

    private function syncPayments(Order $order, array $payments, int $userId): void
    {
        // Untuk simplicity, payment hanya ditambah (tidak overwrite existing).
        // CRUD pembayaran lengkap tersedia via addPayment().
        $user = auth()->user();
        $isFinanceOrAdmin = $user && ($user->hasRole('superadmin') || $user->hasRole('owner') || $user->hasRole('admin_keuangan'));

        foreach ($payments as $p) {
            OrderPayment::create([
                ...$p,
                'order_id' => $order->id,
                'recorded_by' => $userId,
                'verified_by' => $isFinanceOrAdmin ? $userId : null,
                'verified_at' => $isFinanceOrAdmin ? now() : null,
            ]);
        }
    }

    private function guardBrandOwnership(Request $request, Order $order): void
    {
        if ($request->user()->isSuperadmin()) return;
        $brandId = BrandContext::current($request);
        if ($order->brand_id !== $brandId) abort(403);
    }
}
