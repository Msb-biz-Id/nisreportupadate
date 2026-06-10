<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order\Invoice;
use App\Models\Order\InvoiceItem;
use App\Models\Order\Order;
use App\Models\Order\OrderPayment;
use App\Models\Finance\Pemasukan;
use App\Models\Finance\Pengeluaran;
use App\Models\Finance\KategoriPemasukan;
use App\Models\Finance\KategoriPengeluaran;
use App\Services\NumberGenerator;
use App\Services\Notifications\InvoiceWhatsappService;
use App\Services\Notifications\SidobeClient;
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
        if ($user && !$user->isSuperadmin() && !$user->hasRole(['owner', 'admin_keuangan', 'admin_produksi'])) {
            $userBrandIds = $user->brands()->pluck('brands.id')->toArray();
            abort_unless(in_array($invoice->brand_id, $userBrandIds), 403, 'Unauthorized brand context.');
        }

        $invoice->load(['brand', 'bank', 'items', 'order.pelanggan']);
        if (!$invoice->bank_id) {
            $defaultBank = \App\Models\Master\BankAccount::active()->where('brand_id', $invoice->brand_id)->first();
            if ($defaultBank) {
                $invoice->setRelation('bank', $defaultBank);
                $invoice->bank_id = $defaultBank->id;
            }
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

        if (!$invoice->bank_id) {
            $defaultBank = \App\Models\Master\BankAccount::active()->where('brand_id', $invoice->brand_id)->first();
            if ($defaultBank) {
                $invoice->setRelation('bank', $defaultBank);
                $invoice->bank_id = $defaultBank->id;
            }
        }

        if ($user && !$user->isSuperadmin() && !$user->hasRole(['owner', 'admin_keuangan', 'admin_produksi'])) {
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

        if (!$invoice->bank_id) {
            $defaultBank = \App\Models\Master\BankAccount::active()->where('brand_id', $invoice->brand_id)->first();
            if ($defaultBank) {
                $invoice->setRelation('bank', $defaultBank);
                $invoice->bank_id = $defaultBank->id;
            }
        }

        if ($user && !$user->isSuperadmin() && !$user->hasRole(['owner', 'admin_keuangan', 'admin_produksi'])) {
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

        $selectedBrandId = $request->input('brand_id');
        if (is_null($selectedBrandId)) {
            $selectedBrandId = BrandContext::current($request) ?? 'all';
        }

        // admin_keuangan & admin_produksi = lintas-brand (lihat semua brand + reseller)
        $isAllBrandsRole = $user->isSuperadmin() || $user->hasRole(['owner', 'admin_keuangan', 'admin_produksi']);

        $brands = $isAllBrandsRole
            ? \App\Models\Brand::orderBy('nama_brand')->get(['id', 'nama_brand', 'kode'])
            : $user->brands()->orderBy('nama_brand')->get(['brands.id', 'nama_brand', 'kode']);

        $userBrandIds = $brands->pluck('id')->toArray();
        if ($selectedBrandId && $selectedBrandId !== 'all' && ! $isAllBrandsRole) {
            abort_unless(in_array($selectedBrandId, $userBrandIds), 403, 'Unauthorized brand context.');
        }

        // Aggregate Financial Metrics
        $totalTagihanLunas = Invoice::where('status', 'paid')
            ->when($selectedBrandId && $selectedBrandId !== 'all', fn($q) => $q->where('brand_id', $selectedBrandId))
            ->when($selectedBrandId === 'all' && ! $isAllBrandsRole, fn($q) => $q->whereIn('brand_id', $userBrandIds))
            ->sum('total_tagihan');

        $totalTagihanBelumLunas = Invoice::where('status', '!=', 'paid')
            ->when($selectedBrandId && $selectedBrandId !== 'all', fn($q) => $q->where('brand_id', $selectedBrandId))
            ->when($selectedBrandId === 'all' && ! $isAllBrandsRole, fn($q) => $q->whereIn('brand_id', $userBrandIds))
            ->sum('sisa_pembayaran');

        $totalTJ = \App\Models\Order\DesignDeposit::whereIn('status', ['pending', 'verified'])
            ->when($selectedBrandId && $selectedBrandId !== 'all', fn($q) => $q->where('brand_id', $selectedBrandId))
            ->when($selectedBrandId === 'all' && ! $isAllBrandsRole, fn($q) => $q->whereIn('brand_id', $userBrandIds))
            ->sum('amount');

        $totalPending = OrderPayment::whereNull('verified_at')
            ->when($selectedBrandId && $selectedBrandId !== 'all', fn($q) => $q->whereHas('order', fn($o) => $o->where('brand_id', $selectedBrandId)))
            ->when($selectedBrandId === 'all' && ! $isAllBrandsRole, fn($q) => $q->whereHas('order', fn($o) => $o->whereIn('brand_id', $userBrandIds)))
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
            ->when($selectedBrandId === 'all' && ! $isAllBrandsRole, fn($q) => $q->whereIn('brand_id', $userBrandIds))
            ->with(['order:id,no_po,nama_po,pelanggan_id', 'order.pelanggan:id,nama', 'brand'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $paidInvoices = Invoice::where('status', 'paid')
            ->when($selectedBrandId && $selectedBrandId !== 'all', fn($q) => $q->where('brand_id', $selectedBrandId))
            ->when($selectedBrandId === 'all' && ! $isAllBrandsRole, fn($q) => $q->whereIn('brand_id', $userBrandIds))
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

        $selectedBrandId = $request->input('brand_id');
        if (is_null($selectedBrandId)) {
            $selectedBrandId = BrandContext::current($request) ?? 'all';
        }

        $isAllBrandsRole = $user->isSuperadmin() || $user->hasRole(['owner', 'admin_keuangan', 'admin_produksi']);

        $brands = $isAllBrandsRole
            ? \App\Models\Brand::orderBy('nama_brand')->get(['id', 'nama_brand', 'kode'])
            : $user->brands()->orderBy('nama_brand')->get(['brands.id', 'nama_brand', 'kode']);

        $userBrandIds = $brands->pluck('id')->toArray();
        if ($selectedBrandId && $selectedBrandId !== 'all' && ! $isAllBrandsRole) {
            abort_unless(in_array($selectedBrandId, $userBrandIds), 403, 'Unauthorized brand context.');
        }

        $query = Invoice::query()
            ->when($selectedBrandId && $selectedBrandId !== 'all', fn ($q) => $q->where('brand_id', $selectedBrandId))
            ->when($selectedBrandId === 'all' && ! $isAllBrandsRole, fn ($q) => $q->whereIn('brand_id', $userBrandIds))
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
            ->when($selectedBrandId === 'all' && ! $isAllBrandsRole, fn ($q) => $q->whereIn('brand_id', $userBrandIds))
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
            ->when($selectedBrandId === 'all' && ! $isAllBrandsRole, fn ($q) => $q->whereIn('brand_id', $userBrandIds))
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

        $selectedBrandId = $request->input('brand_id');
        if (is_null($selectedBrandId)) {
            $selectedBrandId = BrandContext::current($request) ?? 'all';
        }

        $isAllBrandsRole = $user->isSuperadmin() || $user->hasRole(['owner', 'admin_keuangan', 'admin_produksi']);

        $brands = $isAllBrandsRole
            ? \App\Models\Brand::orderBy('nama_brand')->get(['id', 'nama_brand', 'kode'])
            : $user->brands()->orderBy('nama_brand')->get(['brands.id', 'nama_brand', 'kode']);

        $userBrandIds = $brands->pluck('id')->toArray();
        if ($selectedBrandId && $selectedBrandId !== 'all' && ! $isAllBrandsRole) {
            abort_unless(in_array($selectedBrandId, $userBrandIds), 403, 'Unauthorized brand context.');
        }

        $pendingPayments = OrderPayment::query()
            ->whereNull('verified_at')
            ->when($selectedBrandId && $selectedBrandId !== 'all', fn ($q) => $q->whereHas('order', fn ($o) => $o->where('brand_id', $selectedBrandId)))
            ->when($selectedBrandId === 'all' && ! $isAllBrandsRole, fn ($q) => $q->whereHas('order', fn ($o) => $o->whereIn('brand_id', $userBrandIds)))
            ->with([
                'order:id,no_po,nama_po,brand_id,status_po,total_tagihan,is_dp_bypassed',
                'order.brand:id,nama_brand,kode,min_dp_percentage',
                'recorder:id,name',
                'bank:id,atas_nama,nomor_rekening,bank',
                'masterJenisPembayaran:id,nama,tipe_keuangan',
            ])
            ->orderByDesc('created_at')
            ->get();

        // Attach DP status per payment so frontend can show "X% DP setelah diverifikasi"
        // Use batch query to avoid N+1
        $orderIds = $pendingPayments->pluck('order_id')->filter()->unique()->values();
        $dpPaidPerOrder = [];
        if ($orderIds->isNotEmpty()) {
            foreach (\App\Models\Order\Order::whereIn('id', $orderIds)->get() as $ord) {
                $dpPaidPerOrder[$ord->id] = [
                    'total_tagihan' => (float) $ord->totalTagihan(),
                    'total_paid'    => (float) $ord->totalPaid(),
                    'min_dp_pct'    => $ord->brand ? (float) ($ord->brand->min_dp_percentage ?? 0.50) : 0.50,
                    'is_bypassed'   => (bool) $ord->is_dp_bypassed,
                    'status_po'     => $ord->status_po,
                ];
            }
        }

        $enrichedPayments = $pendingPayments->map(function ($p) use ($dpPaidPerOrder) {
            $arr = $p->toArray();
            $info = $dpPaidPerOrder[$p->order_id] ?? null;
            if ($info) {
                $afterVerify = $info['total_paid'] + (float) $p->amount;
                $minDp       = $info['total_tagihan'] * $info['min_dp_pct'];
                $arr['dp_info'] = [
                    'current_paid'      => $info['total_paid'],
                    'after_verify'      => $afterVerify,
                    'total_tagihan'     => $info['total_tagihan'],
                    'min_dp_pct'        => $info['min_dp_pct'],
                    'min_dp'            => $minDp,
                    'current_pct'       => $info['total_tagihan'] > 0 ? round(($info['total_paid'] / $info['total_tagihan']) * 100, 0) : 0,
                    'after_pct'         => $info['total_tagihan'] > 0 ? round(($afterVerify / $info['total_tagihan']) * 100, 0) : 0,
                    'will_be_sufficient' => $afterVerify >= $minDp || $info['is_bypassed'],
                    'already_sufficient' => $info['total_paid'] >= $minDp || $info['is_bypassed'],
                    'order_status'      => $info['status_po'],
                ];
            }
            return $arr;
        });

        return Inertia::render('Finance/PaymentsPending', [
            'pending_payments' => $enrichedPayments,
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
                'bank_id' => \App\Models\Master\BankAccount::active()->where('brand_id', $order->brand_id)->first()?->id,
                // dp_amount = total verified payments at invoice creation (supports both old payment_type and new master_jenis_pembayaran)
                'dp_amount' => $totalPaid,
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
                    'is_addon' => (bool) $item->is_addon,
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

        // Auto-send WhatsApp jika dikonfigurasi dan pelanggan punya HP valid
        $sidobe    = SidobeClient::fromSettings();
        $waService = new InvoiceWhatsappService($sidobe);
        $invoice->load(['order.pelanggan', 'brand']);

        if ($sidobe->isConfigured()) {
            $phone = $waService->phoneFromInvoice($invoice);
            if ($phone !== '') {
                $result = $waService->send($invoice, 'new_invoice');
                if ($result['success'] && ! ($result['mock'] ?? false)) {
                    $invoice->update(['status' => 'sent', 'sent_via' => 'whatsapp', 'sent_at' => now()]);
                    return back()->with('success', "Invoice {$invoice->invoice_number} dipublish & dikirim ke WhatsApp {$phone}.");
                }
            }
        }

        return back()->with('success', "Invoice {$invoice->invoice_number} dipublish. Siap dikirim ke customer.");
    }

    /**
     * Kirim invoice via WhatsApp secara manual.
     * Mendukung: new_invoice | reminder | overdue
     */
    public function sendWhatsapp(Request $request, Invoice $invoice)
    {
        Gate::authorize('finance.manage-invoice');
        abort_unless(in_array($invoice->status, ['published', 'sent', 'overdue'], true), 422, 'Invoice harus berstatus published/sent/overdue untuk dikirim WA.');

        $condition = $request->input('condition', 'new_invoice');
        abort_unless(in_array($condition, ['new_invoice', 'reminder', 'overdue'], true), 422, 'Kondisi tidak valid.');

        $invoice->load(['order.pelanggan', 'brand']);

        $waService = new InvoiceWhatsappService(SidobeClient::fromSettings());
        $phone     = $waService->phoneFromInvoice($invoice);

        if ($phone === '') {
            return back()->with('error', 'Nomor HP pelanggan tidak tersedia atau tidak valid. Perbarui data pelanggan terlebih dahulu.');
        }

        $result = $waService->send($invoice, $condition);

        if ($result['success']) {
            $invoice->update(['status' => 'sent', 'sent_via' => 'whatsapp', 'sent_at' => now()]);
            $mockNote = ($result['mock'] ?? false) ? ' (Mock Mode — API belum dikonfigurasi)' : '';
            return back()->with('success', "Invoice dikirim ke {$phone}{$mockNote}.");
        }

        return back()->with('error', 'Gagal mengirim WA: ' . ($result['error'] ?? 'Unknown error'));
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

                // Sinkronisasi dengan sistem keuangan
                $master = $payment->masterJenisPembayaran;
                $paymentName = $master ? $master->nama : strtoupper($payment->payment_type ?? 'Pembayaran');
                
                if ($master && $master->tipe_keuangan === 'pemasukan' || (!$master && !in_array($payment->payment_type, ['cashback', 'return']))) {
                    $kategori = KategoriPemasukan::firstOrCreate(
                        ['brand_id' => $order->brand_id, 'nama_kategori' => 'Pembayaran PO'],
                        [
                            'deskripsi' => 'Pembayaran pesanan dari customer',
                            'is_system'  => true,
                            'is_active'  => true,
                        ]
                    );

                    Pemasukan::create([
                        'brand_id'             => $order->brand_id,
                        'kategori_pemasukan_id' => $kategori->id,
                        'order_id'             => $order->id,
                        'source_payment_id'    => $payment->id,
                        'tanggal'              => $payment->payment_date,
                        'nominal'              => $payment->amount,
                        'keterangan'           => "{$paymentName} PO {$order->no_po} — {$order->pelanggan?->nama}",
                        'is_auto'              => true,
                        'created_by'           => $request->user()->id,
                    ]);
                } elseif ($master && $master->tipe_keuangan === 'pengeluaran' || (!$master && in_array($payment->payment_type, ['cashback', 'return']))) {
                    $kategori = KategoriPengeluaran::firstOrCreate(
                        ['brand_id' => $order->brand_id, 'nama_kategori' => 'Refund / Cashback PO'],
                        [
                            'deskripsi' => 'Pengembalian dana atau cashback ke customer',
                            'is_system'  => true,
                            'is_active'  => true,
                        ]
                    );

                    Pengeluaran::create([
                        'brand_id'               => $order->brand_id,
                        'kategori_pengeluaran_id' => $kategori->id,
                        'source_payment_id'      => $payment->id,
                        'tanggal'                => $payment->payment_date,
                        'nominal'                => $payment->amount,
                        'keterangan'             => "{$paymentName} PO {$order->no_po} — {$order->pelanggan?->nama}",
                        'is_auto'                => true,
                        'created_by'             => $request->user()->id,
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

    public function destroyPayment(Request $request, OrderPayment $payment)
    {
        Gate::authorize('finance.manage-invoice');

        if ($request->user()->hasRole('admin_brand')) {
            abort(403, 'Hanya Admin Keuangan yang dapat menghapus data pembayaran.');
        }

        $order        = $payment->order;
        $wasVerified  = $payment->verified_at !== null;
        $paymentId    = $payment->id;

        DB::transaction(function () use ($payment, $order, $wasVerified, $paymentId) {
            // 1. Remove linked finance ledger entries created during verification.
            // Primary: match by source_payment_id (entries created after migration).
            // Fallback: match by order_id + tanggal + nominal for legacy entries (source_payment_id = NULL).
            if ($wasVerified) {
                $deletedPemasukan  = Pemasukan::where('source_payment_id', $paymentId)->delete();
                $deletedPengeluaran = Pengeluaran::where('source_payment_id', $paymentId)->delete();

                if ($deletedPemasukan === 0 && $order) {
                    Pemasukan::where('order_id', $order->id)
                        ->where('is_auto', true)
                        ->whereNull('source_payment_id')
                        ->where('nominal', $payment->amount)
                        ->where('tanggal', $payment->payment_date)
                        ->delete();
                }
                if ($deletedPengeluaran === 0 && $order) {
                    Pengeluaran::where('is_auto', true)
                        ->whereNull('source_payment_id')
                        ->where('nominal', $payment->amount)
                        ->where('tanggal', $payment->payment_date)
                        ->whereHas('kategori', fn ($q) => $q->where('brand_id', $order->brand_id))
                        ->delete();
                }
            }

            // 2. Delete the payment record itself
            $payment->delete();

            // 3. Recalculate order total and invoice balance
            if ($wasVerified && $order) {
                $order->update(['total_tagihan' => $order->totalTagihan()]);

                $invoice = $order->invoices()->first();
                if ($invoice) {
                    $newTotal = $order->totalTagihan();
                    $newPaid  = $order->totalPaid();
                    $newSisa  = max(0, $newTotal - $newPaid);
                    $invoice->update([
                        'total_tagihan'  => $newTotal,
                        'total_bayar'    => $newPaid,
                        'sisa_pembayaran' => $newSisa,
                        'status' => $newSisa <= 0 ? 'paid' : ($invoice->status === 'paid' ? 'validated' : $invoice->status),
                    ]);
                }
            }
        });

        return back()->with('success', 'Data pembayaran dan catatan keuangan terkait berhasil dihapus.');
    }
}
