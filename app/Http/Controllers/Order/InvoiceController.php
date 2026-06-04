<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order\Invoice;
use App\Models\Order\InvoiceItem;
use App\Models\Order\Order;
use App\Models\Order\OrderPayment;
use App\Services\NumberGenerator;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class InvoiceController extends Controller
{
    public function __construct(private readonly NumberGenerator $numbers) {}

    public function pdf(Request $request, Invoice $invoice)
    {
        $user = $request->user();
        if ($user && !$user->isSuperadmin() && !$user->hasRole('owner')) {
            $userBrandIds = $user->brands()->pluck('brands.id')->toArray();
            abort_unless(in_array($invoice->brand_id, $userBrandIds), 403, 'Unauthorized brand context.');
        }

        $invoice->load(['brand', 'bank', 'items', 'order.pelanggan']);
        $trackingUrl = url('/track/' . ($invoice->order?->no_po ?? ''));
        $qrCodeData = $this->qrCodeDataUri($trackingUrl);
        $logoData = $this->logoDataUri($invoice->brand?->logo);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'qrCodeData' => $qrCodeData,
            'logoData' => $logoData,
        ])->setPaper('a4', 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Invoice-' . $invoice->invoice_number . '.pdf"',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive, nosnippet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function publicShow(string $invoiceNumber)
    {
        $query = Invoice::where('invoice_number', $invoiceNumber);

        $user = auth()->user();
        $isAuthorized = $user && (
            $user->isSuperadmin() ||
            $user->hasRole('owner') ||
            $user->hasPermissionTo('finance.view') ||
            $user->hasPermissionTo('finance.manage-invoice') ||
            $user->hasPermissionTo('order.view')
        );

        if (!$isAuthorized) {
            $query->whereIn('status', ['published', 'sent', 'paid']);
        }

        $invoice = $query->with(['brand', 'bank', 'items', 'order.pelanggan', 'order.payments.bank', 'order.progressDetails.progress'])
            ->firstOrFail();

        if ($user && !$user->isSuperadmin() && !$user->hasRole('owner')) {
            $userBrandIds = $user->brands()->pluck('brands.id')->toArray();
            abort_unless(in_array($invoice->brand_id, $userBrandIds), 403, 'Unauthorized brand context.');
        }

        $trackingUrl = url('/track/' . ($invoice->order?->no_po ?? ''));

        return Inertia::render('Public/Invoice', [
            'invoice' => $invoice,
            'qr_code' => $this->qrCodeDataUri($trackingUrl),
            'tracking_url' => $trackingUrl,
        ])->toResponse(request())->withHeaders([
            'X-Robots-Tag' => 'noindex, nofollow, noarchive, nosnippet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function publicPdf(string $invoiceNumber)
    {
        $query = Invoice::where('invoice_number', $invoiceNumber);

        $user = auth()->user();
        $isAuthorized = $user && (
            $user->isSuperadmin() ||
            $user->hasRole('owner') ||
            $user->hasPermissionTo('finance.view') ||
            $user->hasPermissionTo('finance.manage-invoice') ||
            $user->hasPermissionTo('order.view')
        );

        if (!$isAuthorized) {
            $query->whereIn('status', ['published', 'sent', 'paid']);
        }

        $invoice = $query->with(['brand', 'bank', 'items', 'order.pelanggan'])
            ->firstOrFail();

        if ($user && !$user->isSuperadmin() && !$user->hasRole('owner')) {
            $userBrandIds = $user->brands()->pluck('brands.id')->toArray();
            abort_unless(in_array($invoice->brand_id, $userBrandIds), 403, 'Unauthorized brand context.');
        }

        $trackingUrl = url('/track/' . ($invoice->order?->no_po ?? ''));
        $qrCodeData = $this->qrCodeDataUri($trackingUrl);
        $logoData = $this->logoDataUri($invoice->brand?->logo);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'qrCodeData' => $qrCodeData,
            'logoData' => $logoData,
        ])->setPaper('a4', 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Invoice-' . $invoice->invoice_number . '.pdf"',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive, nosnippet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    private function logoDataUri(?string $logoPath): string
    {
        if (!$logoPath) return '';
        try {
            $fullPath = public_path('storage/' . $logoPath);
            if (file_exists($fullPath)) {
                $type = pathinfo($fullPath, PATHINFO_EXTENSION);
                $data = file_get_contents($fullPath);
                return 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        } catch (\Throwable $e) {
            // fallback
        }
        return '';
    }

    private function qrCodeDataUri(string $url): string
    {
        try {
            $svg = QrCode::format('svg')->size(220)->margin(0)->generate($url);
            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->can('finance.view')) {
            abort(403);
        }

        $selectedBrandId = $request->input('brand_id', 'all');

        $brands = ($user->isSuperadmin() || $user->hasRole('owner'))
            ? \App\Models\Brand::orderBy('nama_brand')->get(['id', 'nama_brand', 'kode'])
            : $user->brands()->orderBy('nama_brand')->get(['brands.id', 'nama_brand', 'kode']);

        $userBrandIds = $brands->pluck('id')->toArray();
        if ($selectedBrandId && $selectedBrandId !== 'all') {
            if (!in_array($selectedBrandId, $userBrandIds)) {
                abort(403, 'Unauthorized brand context.');
            }
        }

        // Aggregate Financial Metrics
        $totalTagihanLunas = Invoice::where('status', 'paid')
            ->when($selectedBrandId && $selectedBrandId !== 'all', fn($q) => $q->where('brand_id', $selectedBrandId))
            ->when($selectedBrandId === 'all' && !$user->isSuperadmin() && !$user->hasRole('owner'), fn($q) => $q->whereIn('brand_id', $userBrandIds))
            ->sum('total_tagihan');

        $totalTagihanBelumLunas = Invoice::where('status', '!=', 'paid')
            ->when($selectedBrandId && $selectedBrandId !== 'all', fn($q) => $q->where('brand_id', $selectedBrandId))
            ->when($selectedBrandId === 'all' && !$user->isSuperadmin() && !$user->hasRole('owner'), fn($q) => $q->whereIn('brand_id', $userBrandIds))
            ->sum('sisa_pembayaran');

        $totalTJ = \App\Models\Order\DesignDeposit::whereIn('status', ['pending', 'verified'])
            ->when($selectedBrandId && $selectedBrandId !== 'all', fn($q) => $q->where('brand_id', $selectedBrandId))
            ->when($selectedBrandId === 'all' && !$user->isSuperadmin() && !$user->hasRole('owner'), fn($q) => $q->whereIn('brand_id', $userBrandIds))
            ->sum('amount');

        $totalPending = OrderPayment::whereNull('verified_at')
            ->when($selectedBrandId && $selectedBrandId !== 'all', fn($q) => $q->whereHas('order', fn($o) => $o->where('brand_id', $selectedBrandId)))
            ->when($selectedBrandId === 'all' && !$user->isSuperadmin() && !$user->hasRole('owner'), fn($q) => $q->whereHas('order', fn($o) => $o->whereIn('brand_id', $userBrandIds)))
            ->sum('amount');

        // Brand-wise breakdowns
        $brandBreakdown = [];
        foreach ($brands as $b) {
            $brandBreakdown[] = [
                'id' => $b->id,
                'nama' => $b->nama_brand,
                'kode' => $b->kode,
                'lunas' => (float) Invoice::where('status', 'paid')->where('brand_id', $b->id)->sum('total_tagihan'),
                'belum_lunas' => (float) Invoice::where('status', '!=', 'paid')->where('brand_id', $b->id)->sum('sisa_pembayaran'),
                'tanda_jadi' => (float) \App\Models\Order\DesignDeposit::whereIn('status', ['pending', 'verified'])->where('brand_id', $b->id)->sum('amount'),
                'pending' => (float) OrderPayment::whereNull('verified_at')->whereHas('order', fn($o) => $o->where('brand_id', $b->id))->sum('amount'),
            ];
        }

        // Recent Invoices / PO lists (unpaid & paid)
        $unpaidInvoices = Invoice::where('status', '!=', 'paid')
            ->where('sisa_pembayaran', '>', 0)
            ->when($selectedBrandId && $selectedBrandId !== 'all', fn($q) => $q->where('brand_id', $selectedBrandId))
            ->when($selectedBrandId === 'all' && !$user->isSuperadmin() && !$user->hasRole('owner'), fn($q) => $q->whereIn('brand_id', $userBrandIds))
            ->with(['order:id,no_po,nama_po,pelanggan_id', 'order.pelanggan:id,nama', 'brand'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $paidInvoices = Invoice::where('status', 'paid')
            ->when($selectedBrandId && $selectedBrandId !== 'all', fn($q) => $q->where('brand_id', $selectedBrandId))
            ->when($selectedBrandId === 'all' && !$user->isSuperadmin() && !$user->hasRole('owner'), fn($q) => $q->whereIn('brand_id', $userBrandIds))
            ->with(['order:id,no_po,nama_po,pelanggan_id', 'order.pelanggan:id,nama', 'brand'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return Inertia::render('Finance/InvoiceIndex', [
            'brands' => $brands,
            'total_lunas' => (float) $totalTagihanLunas,
            'total_belum_lunas' => (float) $totalTagihanBelumLunas,
            'total_tanda_jadi' => (float) $totalTJ,
            'total_pending' => (float) $totalPending,
            'brand_breakdown' => $brandBreakdown,
            'unpaid_invoices' => $unpaidInvoices,
            'paid_invoices' => $paidInvoices,
            'filters' => [
                'brand_id' => $request->string('brand_id', $selectedBrandId)->toString(),
            ],
            'can' => [
                'create' => $request->user()->can('finance.manage-invoice'),
                'validate' => $request->user()->can('finance.manage-invoice'),
                'publish' => $request->user()->can('finance.manage-invoice'),
            ],
        ]);
    }

    public function list(Request $request)
    {
        $user = $request->user();
        if (!$user->can('finance.view') && !$user->can('finance.manage-invoice')) {
            abort(403);
        }

        $selectedBrandId = $request->input('brand_id', 'all');

        $brands = ($user->isSuperadmin() || $user->hasRole('owner'))
            ? \App\Models\Brand::orderBy('nama_brand')->get(['id', 'nama_brand', 'kode'])
            : $user->brands()->orderBy('nama_brand')->get(['brands.id', 'nama_brand', 'kode']);

        $userBrandIds = $brands->pluck('id')->toArray();
        if ($selectedBrandId && $selectedBrandId !== 'all') {
            if (!in_array($selectedBrandId, $userBrandIds)) {
                abort(403, 'Unauthorized brand context.');
            }
        }

        $query = Invoice::query()
            ->when($selectedBrandId && $selectedBrandId !== 'all', fn ($q) => $q->where('brand_id', $selectedBrandId))
            ->when($selectedBrandId === 'all' && !$user->isSuperadmin() && !$user->hasRole('owner'), fn ($q) => $q->whereIn('brand_id', $userBrandIds))
            ->with(['order:id,no_po,nama_po,pelanggan_id', 'order.pelanggan:id,nama']);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('order', fn ($x) => $x->where('no_po', 'like', "%{$search}%"));
            });
        }
        if ($startDate = $request->string('start_date')->toString()) {
            $query->whereDate('tanggal_terbit', '>=', $startDate);
        }
        if ($endDate = $request->string('end_date')->toString()) {
            $query->whereDate('tanggal_terbit', '<=', $endDate);
        }

        $allFiltered = (clone $query)->orderByDesc('created_at')->get()->map(fn ($inv) => [
            'invoice_number' => $inv->invoice_number,
            'no_po' => $inv->order?->no_po ?? '—',
            'pelanggan' => $inv->order?->pelanggan?->nama ?? '—',
            'tanggal_terbit' => $inv->tanggal_terbit,
            'total_tagihan' => $inv->total_tagihan,
            'sisa_pembayaran' => $inv->sisa_pembayaran,
            'status' => $inv->status,
        ]);

        $invoices = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        // Fetch Design Deposits (Tanda Jadi)
        $depositsQuery = \App\Models\Order\DesignDeposit::query()
            ->when($selectedBrandId && $selectedBrandId !== 'all', fn ($q) => $q->where('brand_id', $selectedBrandId))
            ->when($selectedBrandId === 'all' && !$user->isSuperadmin() && !$user->hasRole('owner'), fn ($q) => $q->whereIn('brand_id', $userBrandIds))
            ->with(['brand:id,nama_brand,kode', 'recorder:id,name', 'verifier:id,name', 'bank:id,atas_nama,nomor_rekening,bank', 'order:id,no_po'])
            ->orderByDesc('created_at');

        if ($search = $request->string('q')->toString()) {
            $depositsQuery->where(function ($q) use ($search) {
                $q->where('deposit_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $designDeposits = $depositsQuery->get();

        $availableOrders = Order::query()
            ->when($selectedBrandId && $selectedBrandId !== 'all', fn ($q) => $q->where('brand_id', $selectedBrandId))
            ->when($selectedBrandId === 'all' && !$user->isSuperadmin() && !$user->hasRole('owner'), fn ($q) => $q->whereIn('brand_id', $userBrandIds))
            ->whereDoesntHave('invoices')
            ->orderByDesc('created_at')
            ->get(['id', 'no_po', 'nama_po', 'total_tagihan']);

        $bankAccounts = \App\Models\Master\BankAccount::where('is_active', true)
            ->whereIn('brand_id', $userBrandIds)
            ->get(['id', 'bank', 'atas_nama', 'nomor_rekening', 'brand_id']);

        $customers = \App\Models\Master\Customer::where('is_active', true)
            ->whereIn('brand_id', $userBrandIds)
            ->orderBy('nama')
            ->get(['id', 'nama', 'brand_id']);

        return Inertia::render('Finance/InvoiceList', [
            'invoices' => $invoices,
            'all_filtered_invoices' => $allFiltered,
            'brands' => $brands,
            'design_deposits' => $designDeposits,
            'available_orders' => $availableOrders,
            'bank_accounts' => $bankAccounts,
            'customers' => $customers,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
                'brand_id' => $request->string('brand_id', $selectedBrandId)->toString(),
                'start_date' => $request->string('start_date')->toString(),
                'end_date' => $request->string('end_date')->toString(),
            ],
            'statuses' => Invoice::STATUSES,
            'can' => [
                'create' => $request->user()->can('finance.manage-invoice'),
                'validate' => $request->user()->can('finance.view'),
                'publish' => $request->user()->can('finance.manage-invoice'),
            ],
        ]);
    }

    public function paymentsPending(Request $request)
    {
        $user = $request->user();
        if (!$user->can('finance.view')) {
            abort(403);
        }

        $selectedBrandId = $request->input('brand_id', 'all');

        $brands = ($user->isSuperadmin() || $user->hasRole('owner'))
            ? \App\Models\Brand::orderBy('nama_brand')->get(['id', 'nama_brand', 'kode'])
            : $user->brands()->orderBy('nama_brand')->get(['brands.id', 'nama_brand', 'kode']);

        $userBrandIds = $brands->pluck('id')->toArray();
        if ($selectedBrandId && $selectedBrandId !== 'all') {
            if (!in_array($selectedBrandId, $userBrandIds)) {
                abort(403, 'Unauthorized brand context.');
            }
        }

        $pendingPayments = OrderPayment::query()
            ->whereNull('verified_at')
            ->when($selectedBrandId && $selectedBrandId !== 'all', fn ($q) => $q->whereHas('order', fn ($o) => $o->where('brand_id', $selectedBrandId)))
            ->when($selectedBrandId === 'all' && !$user->isSuperadmin() && !$user->hasRole('owner'), fn ($q) => $q->whereHas('order', fn ($o) => $o->whereIn('brand_id', $userBrandIds)))
            ->with(['order:id,no_po,nama_po,brand_id', 'order.brand:id,nama_brand,kode', 'recorder:id,name', 'bank:id,atas_nama,nomor_rekening,bank'])
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Finance/PaymentsPending', [
            'pending_payments' => $pendingPayments,
            'brands' => $brands,
            'filters' => [
                'brand_id' => $request->string('brand_id', $selectedBrandId)->toString(),
            ],
            'can' => [
                'validate' => $request->user()->can('finance.manage-invoice'),
            ],
        ]);
    }

    public function createFromOrder(Request $request, Order $order)
    {
        Gate::authorize('finance.manage-invoice');
        abort_if(Invoice::where('order_id', $order->id)->exists(), 422, 'Invoice untuk PO ini sudah ada.');

        $order->load('items', 'payments', 'brand');

        $invoice = DB::transaction(function () use ($order) {
            $totalTagihan = (float) $order->totalTagihan();
            $totalPaid = (float) $order->totalPaid();

            $invoice = Invoice::create([
                'brand_id' => $order->brand_id,
                'order_id' => $order->id,
                'invoice_number' => $this->numbers->generateInvoiceNumber($order->brand, $order),
                'tanggal_terbit' => now()->toDateString(),
                'jatuh_tempo' => now()->addDays(14)->toDateString(),
                'status' => 'draft',
                'total_tagihan' => $totalTagihan,
                'total_bayar' => $totalPaid,
                'dp_amount' => (float) $order->payments()->where('payment_type', 'dp')->whereNotNull('verified_at')->sum('amount'),
                'sisa_pembayaran' => $order->sisaTagihan(),
                'created_by' => auth()->id(),
            ]);

            foreach ($order->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'produk' => $item->nama_produk . ($item->varian_label ? " ({$item->varian_label})" : ''),
                    'jumlah' => $item->quantity,
                    'harga_satuan' => $item->harga_satuan,
                    'subtotal' => $item->subtotal,
                ]);
            }

            return $invoice;
        });

        return redirect()->route('invoices.list')
            ->with('success', "Invoice {$invoice->invoice_number} dibuat sebagai draft.");
    }

    public function validateInvoice(Request $request, Invoice $invoice)
    {
        Gate::authorize('finance.manage-invoice');

        $data = $request->validate([
            'diskon_type' => ['nullable', Rule::in(['persen', 'nominal'])],
            'diskon_value' => ['nullable', 'numeric', 'min:0'],
            'biaya_pengiriman' => ['nullable', 'numeric', 'min:0'],
            'jasa_pengiriman' => ['nullable', 'string', 'max:100'],
            'bank_id' => ['nullable', 'uuid', 'exists:bank_accounts,id'],
            'catatan' => ['nullable', 'string'],
        ]);

        $tagihan = (float) $invoice->total_tagihan;
        $diskonNominal = $data['diskon_type'] === 'persen'
            ? ($tagihan * ($data['diskon_value'] ?? 0) / 100)
            : ($data['diskon_value'] ?? 0);
        $afterDiskon = $tagihan - $diskonNominal + ($data['biaya_pengiriman'] ?? 0);

        $invoice->update([
            ...$data,
            'status' => 'validated',
            'sisa_pembayaran' => max(0, $afterDiskon - (float) $invoice->dp_amount),
        ]);

        return back()->with('success', 'Invoice divalidasi.');
    }

    public function publish(Request $request, Invoice $invoice)
    {
        Gate::authorize('finance.manage-invoice');
        abort_unless(in_array($invoice->status, ['draft', 'validated'], true), 422);

        $invoice->update(['status' => 'published']);

        \App\Services\ActivityLogger::log('publish', 'invoice', $invoice, "Publish invoice {$invoice->invoice_number}");

        return back()->with('success', "Invoice {$invoice->invoice_number} dipublish. Siap dikirim ke customer.");
    }

    public function verifyPayment(Request $request, OrderPayment $payment)
    {
        Gate::authorize('finance.manage-invoice');

        $data = $request->validate([
            'bank_mutasi' => ['required', 'boolean'],
            'nominal_cocok' => ['required', 'boolean'],
            'bukti_valid' => ['required', 'boolean'],
            'verification_notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($payment, $request, $data) {
            $payment->update([
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'verification_checks' => [
                    'bank_mutasi' => (bool)$data['bank_mutasi'],
                    'nominal_cocok' => (bool)$data['nominal_cocok'],
                    'bukti_valid' => (bool)$data['bukti_valid'],
                ],
                'verification_notes' => $data['verification_notes'],
            ]);

            // Automatically update the associated invoice's remaining balance
            $order = $payment->order;
            if ($order) {
                // Rekalkulasi total_tagihan di DB
                $order->update(['total_tagihan' => $order->totalTagihan()]);
                
                $invoice = $order->invoices()->first();
                if ($invoice) {
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
            }
        });

        $order = $payment->order;
        \App\Services\Notifications\DynamicNotificationService::dispatch('payment_verified', [
            'no_po' => $order->no_po,
            'brand_id' => $order->brand_id,
            'brand_nama' => $order->brand?->nama_brand ?? $order->brand_id,
            'nominal' => 'Rp ' . number_format($payment->amount, 0, ',', '.'),
            'action_url' => "/orders/{$order->id}"
        ]);

        return back()->with('success', 'Pembayaran berhasil diverifikasi.');
    }
}
