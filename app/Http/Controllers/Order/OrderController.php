<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Master\BahanKain;
use App\Models\Master\BankAccount;
use App\Models\Master\Customer;
use App\Models\Master\KategoriOrder;
use App\Models\Master\Logo;
use App\Models\Master\PolaJahitan;
use App\Models\Master\Printing;
use App\Models\Master\Product;
use App\Models\Master\Resleting;
use App\Models\Master\Size;
use App\Models\Master\SumberOrder;
use App\Models\Order\Invoice;
use App\Models\Order\InvoiceItem;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\OrderNameset;
use App\Models\Order\OrderPayment;
use App\Services\NumberGenerator;
use App\Services\POStatusManager;
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

        $brandId = BrandContext::current($request);
        $query = Order::query()
            ->forBrand($brandId)
            ->with(['pelanggan:id,nama,nomor_hp', 'kategoriOrder:id,nama'])
            ->withCount(['items', 'progressDetails']);

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('no_po', 'like', "%{$search}%")
                  ->orWhere('nama_po', 'like', "%{$search}%")
                  ->orWhereHas('pelanggan', fn ($x) => $x->where('nama', 'like', "%{$search}%"));
            });
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status_po', $status);
        }

        $orders = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return Inertia::render('Order/Index', [
            'orders' => $orders,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
            ],
            'statuses' => Order::STATUSES,
            'can' => [
                'create' => $request->user()->can('order.create'),
                'update' => $request->user()->can('order.update'),
                'delete' => $request->user()->can('order.delete'),
                'publish' => $request->user()->can('order.publish'),
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

        $order->load(['items.namesets', 'payments.bank']);

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
                'no_po' => $this->numbers->generateOrderNumber($brand),
                'nama_po' => $data['nama_po'],
                'status_po' => 'draft',
                'is_special_order' => $data['is_special_order'] ?? false,
                'tanggal_masuk' => $data['tanggal_masuk'],
                'deadline_customer' => $data['deadline_customer'],
                'kategori_order_id' => $data['kategori_order_id'] ?? null,
                'sumber_order_id' => $data['sumber_order_id'] ?? null,
                'pelanggan_id' => $data['pelanggan_id'],
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
                'invoice_number'  => $this->numbers->generateInvoiceNumber($brand),
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
            $order->update([
                'nama_po' => $data['nama_po'],
                'is_special_order' => $data['is_special_order'] ?? false,
                'tanggal_masuk' => $data['tanggal_masuk'],
                'deadline_customer' => $data['deadline_customer'],
                'kategori_order_id' => $data['kategori_order_id'] ?? null,
                'sumber_order_id' => $data['sumber_order_id'] ?? null,
                'pelanggan_id' => $data['pelanggan_id'],
                'catatan' => $data['catatan'] ?? null,
                'updated_by' => $user->id,
            ]);

            $this->syncItems($order, $data['items'] ?? []);
            $this->syncPayments($order, $data['payments'] ?? [], $user->id);
            $order->update(['total_tagihan' => $order->items()->sum('subtotal')]);
        });

        return redirect()->route('orders.show', $order->id)->with('success', 'PO berhasil diperbarui.');
    }

    public function show(Request $request, Order $order)
    {
        Gate::authorize('order.view');
        $this->guardBrandOwnership($request, $order);

        $order->load([
            'pelanggan', 'kategoriOrder', 'sumberOrder',
            'items.namesets.size', 'items.bahanKain', 'items.logo', 'items.printing',
            'payments.bank', 'payments.recorder',
            'progressDetails.progress', 'progressDetails.updater',
            'rijeks.progress', 'rijeks.creator',
            'lockStatus.lockedBy',
            'changeLogs.changer',
            'invoices',
            'refunds.creator', 'refunds.publisher',
            'repeats', 'repeatFrom',
        ]);

        return Inertia::render('Order/Preview', [
            'order' => $order,
            'can' => [
                'edit' => $request->user()->can('order.update') && ($order->isDraft() || ! $order->isLocked()),
                'delete' => $request->user()->can('order.delete') && $order->isDraft(),
                'publish' => $request->user()->can('order.publish') && $order->isDraft(),
                'unlock' => $request->user()->isSuperadmin() || $request->user()->hasRole(['owner', 'admin_brand']),
                'repeat' => $request->user()->can('order.create') && ! $order->isDraft(),
                'manage_invoice' => $request->user()->can('finance.manage-invoice'),
            ],
        ]);
    }

    public function publish(Request $request, Order $order)
    {
        Gate::authorize('order.publish');
        $this->guardBrandOwnership($request, $order);
        abort_unless($order->isDraft(), 422, 'PO sudah diterbitkan.');
        abort_if($order->items()->count() === 0, 422, 'PO tanpa produk tidak bisa diterbitkan.');

        $this->statusManager->publish($order, $request->user());

        \App\Services\ActivityLogger::log('publish', 'order', $order, "Publish PO {$order->no_po}");

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
            $clone->no_po = $this->numbers->generateOrderNumber($brand);
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
            'payment_type' => ['required', Rule::in(['dp', 'pelunasan', 'lainnya'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date'],
            'bank_id' => ['nullable', 'uuid', 'exists:bank_accounts,id'],
            'notes' => ['nullable', 'string'],
        ]);

        OrderPayment::create([
            ...$data,
            'order_id' => $order->id,
            'recorded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Pembayaran berhasil dicatat.');
    }

    public function spkPdf(Request $request, Order $order)
    {
        Gate::authorize('order.view');
        $this->guardBrandOwnership($request, $order);

        $order->load([
            'brand', 'pelanggan', 'kategoriOrder', 'sumberOrder',
            'items.bahanKain', 'items.logo', 'items.printing', 'items.resleting',
            'items.namesets.size',
        ]);

        $pdf = Pdf::loadView('pdf.spk', ['order' => $order])->setPaper('a4', 'portrait');

        return $pdf->download("SPK-{$order->no_po}.pdf");
    }

    private function mastersForForm(string $brandId): array
    {
        $brandQ = fn ($q) => $q->where(function ($w) use ($brandId) {
            $w->where('brand_id', $brandId)->orWhereNull('brand_id');
        });

        return [
            'kategori_orders' => KategoriOrder::active()->where($brandQ)->orderBy('nama')->get(['id', 'nama']),
            'sumber_orders' => SumberOrder::active()->where($brandQ)->orderBy('nama')->get(['id', 'nama']),
            'pelanggan' => Customer::active()->where($brandQ)->orderBy('nama')->limit(500)->get(['id', 'kode', 'nama', 'nomor_hp']),
            'produk' => Product::active()->where($brandQ)->orderBy('nama')->get(['id', 'nama', 'harga']),
            'bahan_kains' => BahanKain::active()->orderBy('nama')->get(['id', 'nama']),
            'logos' => Logo::active()->orderBy('nama')->get(['id', 'nama']),
            'printings' => Printing::active()->orderBy('nama')->get(['id', 'nama']),
            'resletings' => Resleting::active()->orderBy('nama')->get(['id', 'nama']),
            'pola_jahitans' => PolaJahitan::active()->orderBy('jenis_pola')->orderBy('nama')
                ->get(['id', 'jenis_pola', 'nama']),
            'sizes' => Size::active()->orderBy('kategori_size')->orderBy('urutan')->get(['id', 'kategori_size', 'ukuran']),
            'banks' => BankAccount::active()->where($brandQ)->orderBy('bank')->get(['id', 'bank', 'atas_nama', 'nomor_rekening']),
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
            'sumber_order_id' => ['nullable', 'uuid'],
            'pelanggan_id' => ['required', 'uuid', 'exists:customers,id'],
            'catatan' => ['nullable', 'string'],
            'items' => ['array'],
            'items.*.product_id' => ['nullable', 'uuid'],
            'items.*.nama_produk' => ['required', 'string', 'max:255'],
            'items.*.varian_label' => ['nullable', 'string', 'max:100'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.harga_satuan' => ['required', 'numeric', 'min:0'],
            'items.*.bahan_kain_id' => ['nullable', 'uuid'],
            'items.*.jenis_setelan' => ['nullable', Rule::in(['stell', 'non_stell', 'atasan_saja', 'bawahan_saja'])],
            'items.*.logo_id' => ['nullable', 'uuid'],
            'items.*.printing_id' => ['nullable', 'uuid'],
            'items.*.resleting_id' => ['nullable', 'uuid'],
            'items.*.pola_jahitan_lengan_id' => ['nullable', 'uuid'],
            'items.*.pola_jahitan_kerah_id' => ['nullable', 'uuid'],
            'items.*.pola_jahitan_bawah_id' => ['nullable', 'uuid'],
            'items.*.pola_jahitan_pundak_id' => ['nullable', 'uuid'],
            'items.*.warna' => ['nullable', 'string', 'max:100'],
            'items.*.jenis_kerah' => ['nullable', 'string', 'max:100'],
            'items.*.catatan' => ['nullable', 'string'],
            'items.*.gambar_desain' => ['nullable', 'string', 'max:255'],
            'items.*.gambar_kerah' => ['nullable', 'string', 'max:255'],
            'items.*.namesets' => ['array'],
            'items.*.namesets.*.nama_punggung' => ['nullable', 'string', 'max:100'],
            'items.*.namesets.*.nomor_punggung' => ['nullable', 'string', 'max:20'],
            'items.*.namesets.*.size_id' => ['nullable', 'uuid'],
            'items.*.namesets.*.size_label' => ['nullable', 'string', 'max:50'],
            'items.*.namesets.*.keterangan' => ['nullable', 'string'],
            'payments' => ['array'],
            'payments.*.payment_type' => ['required', Rule::in(['dp', 'pelunasan', 'lainnya'])],
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
        foreach ($payments as $p) {
            OrderPayment::create([
                ...$p,
                'order_id' => $order->id,
                'recorded_by' => $userId,
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
