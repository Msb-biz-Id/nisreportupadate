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
        if ($user && !$user->hasAccessToBrand($invoice->brand_id)) {
            abort(403, 'Unauthorized brand context.');
        }

        $invoice->load(['brand.parentBrand', 'bank', 'items', 'order.pelanggan', 'order.payments', 'order.iklan', 'order.creator.brands', 'order.items']);
        if ($invoice->order) {
            $resellerBrand = $invoice->order->resolveResellerBrand();
            if ($resellerBrand) {
                $resellerBrand->load('parentBrand');
                $invoice->setRelation('brand', $resellerBrand);
            }
        }

        if (!$invoice->bank_id) {
            $bankBrandId = \App\Support\BrandContext::masterDataId($request, $invoice->brand_id);
            $defaultBank = \App\Models\Master\BankAccount::active()->where('brand_id', $bankBrandId)->first();
            if ($defaultBank) {
                $invoice->setRelation('bank', $defaultBank);
                $invoice->bank_id = $defaultBank->id;
            }
        }
        $trackingUrl = url('/track/' . ($invoice->order?->no_po ?? ''));
        $qrCodeData = $this->qrCodeDataUri($trackingUrl);
        $headerBrand = $invoice->brand ? $invoice->brand->getHeaderBrand() : null;
        $logoData = $this->logoDataUri($headerBrand?->logo);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'headerBrand' => $headerBrand,
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

    public function publicShow(Request $request, string $invoiceNumber)
    {
        $query = Invoice::where('invoice_number', $invoiceNumber);

        $user = $request->user();
        $isAuthorized = $user && (
            $user->isSuperadmin() ||
            $user->hasRole('owner') ||
            $user->hasPermissionTo('finance.view') ||
            $user->hasPermissionTo('finance.manage-invoice') ||
            $user->hasPermissionTo('order.view')
        );

        if (!$isAuthorized) {
            $query->whereIn('status', ['published', 'sent', 'overdue', 'paid', 'validated']);
        }

        $invoice = $query->with(['brand.parentBrand', 'bank', 'items', 'order.pelanggan', 'order.payments.bank', 'order.progressDetails.progress', 'order.iklan', 'order.creator.brands', 'order.items'])
            ->firstOrFail();

        if ($invoice->order) {
            $resellerBrand = $invoice->order->resolveResellerBrand();
            if ($resellerBrand) {
                $resellerBrand->load('parentBrand');
                $invoice->setRelation('brand', $resellerBrand);
            }
        }

        if (!$invoice->bank_id) {
            $bankBrandId = \App\Support\BrandContext::masterDataId($request, $invoice->brand_id);
            $defaultBank = \App\Models\Master\BankAccount::active()->where('brand_id', $bankBrandId)->first();
            if ($defaultBank) {
                $invoice->setRelation('bank', $defaultBank);
                $invoice->bank_id = $defaultBank->id;
            }
        }

        if ($invoice->brand) {
            $headerBrand = $invoice->brand->getHeaderBrand();
            if ($headerBrand !== $invoice->brand) {
                $invoice->setRelation('brand', $headerBrand);
            }
        }

        if ($user && !$user->hasAccessToBrand($invoice->brand_id)) {
            abort(403, 'Unauthorized brand context.');
        }

        $trackingUrl = url('/track/' . ($invoice->order?->no_po ?? ''));

        $response = Inertia::render('Public/Invoice', [
            'invoice' => $invoice,
            'qr_code' => $this->qrCodeDataUri($trackingUrl),
            'tracking_url' => $trackingUrl,
        ])->withViewData(['title' => "Invoice " . $invoice->invoice_number])
          ->toResponse($request);

        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    public function publicPdf(Request $request, string $invoiceNumber)
    {
        $query = Invoice::where('invoice_number', $invoiceNumber);

        $user = $request->user();
        $isAuthorized = $user && (
            $user->isSuperadmin() ||
            $user->hasRole('owner') ||
            $user->hasPermissionTo('finance.view') ||
            $user->hasPermissionTo('finance.manage-invoice') ||
            $user->hasPermissionTo('order.view')
        );

        if (!$isAuthorized) {
            $query->whereIn('status', ['published', 'sent', 'overdue', 'paid', 'validated']);
        }

        $invoice = $query->with(['brand.parentBrand', 'bank', 'items', 'order.pelanggan', 'order.payments', 'order.iklan', 'order.creator.brands', 'order.items'])
            ->firstOrFail();

        if ($invoice->order) {
            $resellerBrand = $invoice->order->resolveResellerBrand();
            if ($resellerBrand) {
                $resellerBrand->load('parentBrand');
                $invoice->setRelation('brand', $resellerBrand);
            }
        }

        if (!$invoice->bank_id) {
            $bankBrandId = \App\Support\BrandContext::masterDataId($request, $invoice->brand_id);
            $defaultBank = \App\Models\Master\BankAccount::active()->where('brand_id', $bankBrandId)->first();
            if ($defaultBank) {
                $invoice->setRelation('bank', $defaultBank);
                $invoice->bank_id = $defaultBank->id;
            }
        }

        if ($user && !$user->hasAccessToBrand($invoice->brand_id)) {
            abort(403, 'Unauthorized brand context.');
        }

        $trackingUrl = url('/track/' . ($invoice->order?->no_po ?? ''));
        $qrCodeData = $this->qrCodeDataUri($trackingUrl);
        $headerBrand = $invoice->brand ? $invoice->brand->getHeaderBrand() : null;
        $logoData = $this->logoDataUri($headerBrand?->logo);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'headerBrand' => $headerBrand,
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
        if (empty($logoPath)) return '';
        try {
            // Clean/normalize path
            $normalizedPath = $logoPath;
            
            // If it's a URL, parse and get the path component
            if (str_starts_with($logoPath, 'http://') || str_starts_with($logoPath, 'https://')) {
                $parsed = parse_url($logoPath);
                $normalizedPath = ltrim($parsed['path'] ?? '', '/');
            }
            
            // Strip leading slashes and storage prefix
            $normalizedPath = ltrim($normalizedPath, '/');
            if (str_starts_with($normalizedPath, 'storage/')) {
                $normalizedPath = substr($normalizedPath, 8);
            }

            // Try candidate paths in order of preference
            $candidates = [
                storage_path('app/public/' . $normalizedPath),
                public_path('storage/' . $normalizedPath),
                public_path($normalizedPath),
                $logoPath, // fallback
            ];

            $fullPath = null;
            foreach ($candidates as $candidate) {
                if (!empty($candidate) && file_exists($candidate) && !is_dir($candidate)) {
                    $fullPath = $candidate;
                    break;
                }
            }

            if ($fullPath) {
                $type = pathinfo($fullPath, PATHINFO_EXTENSION);
                if (empty($type)) {
                    $type = 'png';
                }
                $data = file_get_contents($fullPath);
                return 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("logoDataUri failed in InvoiceController for {$logoPath}: " . $e->getMessage());
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
        if (is_null($selectedBrandId) || $selectedBrandId === '' || $selectedBrandId === 'all') {
            $selectedBrandId = 'all';
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

        // 1. Fetch aggregated sums grouped by brand_id to prevent N+1 loop queries
        $lunasSums = Invoice::where('status', 'paid')
            ->groupBy('brand_id')
            ->select('brand_id', DB::raw('SUM(total_tagihan) as total'))
            ->pluck('total', 'brand_id');

        $belumLunasSums = Invoice::where('status', '!=', 'paid')
            ->groupBy('brand_id')
            ->select('brand_id', DB::raw('SUM(sisa_pembayaran) as total'))
            ->pluck('total', 'brand_id');

        $tandaJadiSums = \App\Models\Order\DesignDeposit::whereIn('status', ['pending', 'verified'])
            ->groupBy('brand_id')
            ->select('brand_id', DB::raw('SUM(amount) as total'))
            ->pluck('total', 'brand_id');

        $pendingSums = OrderPayment::whereNull('verified_at')
            ->join('orders', 'order_payments.order_id', '=', 'orders.id')
            ->groupBy('orders.brand_id')
            ->select('orders.brand_id', DB::raw('SUM(order_payments.amount) as total'))
            ->pluck('total', 'orders.brand_id');

        // 2. Build Brand-wise breakdowns in memory
        $brandBreakdown = [];
        foreach ($brands as $b) {
            $brandBreakdown[] = [
                'id' => $b->id,
                'nama' => $b->nama_brand,
                'kode' => $b->kode,
                'lunas' => (float) ($lunasSums[$b->id] ?? 0.0),
                'belum_lunas' => (float) ($belumLunasSums[$b->id] ?? 0.0),
                'tanda_jadi' => (float) ($tandaJadiSums[$b->id] ?? 0.0),
                'pending' => (float) ($pendingSums[$b->id] ?? 0.0),
            ];
        }

        // 3. Compute totals dynamically from the breakdown in memory (saves another 4 queries)
        if ($selectedBrandId && $selectedBrandId !== 'all') {
            $matched = collect($brandBreakdown)->firstWhere('id', $selectedBrandId);
            $totalTagihanLunas = $matched['lunas'] ?? 0.0;
            $totalTagihanBelumLunas = $matched['belum_lunas'] ?? 0.0;
            $totalTJ = $matched['tanda_jadi'] ?? 0.0;
            $totalPending = $matched['pending'] ?? 0.0;
        } else {
            // 'all' context
            $totalTagihanLunas = (float) collect($brandBreakdown)->sum('lunas');
            $totalTagihanBelumLunas = (float) collect($brandBreakdown)->sum('belum_lunas');
            $totalTJ = (float) collect($brandBreakdown)->sum('tanda_jadi');
            $totalPending = (float) collect($brandBreakdown)->sum('pending');
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
                'brand_id' => $selectedBrandId,
            ],
            'can' => [
                'create' => $request->user() && ($request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan')),
                'validate' => $request->user() && ($request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan')),
                'publish' => $request->user() && ($request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan')),
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
        if (is_null($selectedBrandId) || $selectedBrandId === '' || $selectedBrandId === 'all') {
            $selectedBrandId = 'all';
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
            ->with([
                'order:id,no_po,nama_po,pelanggan_id,total_tagihan,brand_id,nama_ekspedisi,is_special_order', 
                'order.pelanggan:id,nama',
                'order.payments.masterJenisPembayaran',
                'order.payments.bank',
                'order.items:id,order_id,quantity,harga_satuan,discount_amount'
            ]);

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
            $query->where('tanggal_terbit', '>=', $startDate);
        }
        if ($endDate = $request->string('end_date')->toString()) {
            $query->where('tanggal_terbit', '<=', $endDate);
        }

        $allFiltered = Invoice::query()
            ->when($selectedBrandId && $selectedBrandId !== 'all', fn ($q) => $q->where('brand_id', $selectedBrandId))
            ->when($selectedBrandId === 'all' && ! $isAllBrandsRole, fn ($q) => $q->whereIn('brand_id', $userBrandIds))
            ->when($status = $request->string('status')->toString(), fn($q) => $q->where('status', $status))
            ->when($search = $request->string('q')->toString(), function ($q) use ($search) {
                $q->where(function ($qp) use ($search) {
                    $qp->where('invoice_number', 'like', "%{$search}%")
                      ->orWhereHas('order', fn ($x) => $x->where('no_po', 'like', "%{$search}%"));
                });
            })
            ->when($startDate = $request->string('start_date')->toString(), fn($q) => $q->where('tanggal_terbit', '>=', $startDate))
            ->when($endDate = $request->string('end_date')->toString(), fn($q) => $q->where('tanggal_terbit', '<=', $endDate))
            ->with(['order:id,no_po,pelanggan_id', 'order.pelanggan:id,nama'])
            ->orderByDesc('created_at')
            ->get(['id', 'invoice_number', 'order_id', 'tanggal_terbit', 'total_tagihan', 'sisa_pembayaran', 'status'])
            ->map(fn ($inv) => [
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

        $rawBankAccounts = \App\Models\Master\BankAccount::where('is_active', true)
            ->get(['id', 'bank', 'atas_nama', 'nomor_rekening', 'brand_id']);

        $bankAccounts = collect();
        foreach ($brands as $brand) {
            $brandId = $brand->id;
            $masterBrandId = \App\Support\BrandContext::masterDataId($request, $brandId);

            foreach ($rawBankAccounts as $bank) {
                if ($bank->brand_id === $brandId) {
                    $bankAccounts->push($bank);
                } elseif ($masterBrandId && $bank->brand_id === $masterBrandId) {
                    $cloned = clone $bank;
                    $cloned->brand_id = $brandId;
                    $bankAccounts->push($cloned);
                }
            }
        }
        $bankAccounts = $bankAccounts->unique(function ($item) {
            return $item->id . '-' . $item->brand_id;
        })->values();

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
            'master_jenis_pembayarans' => \App\Models\Finance\MasterJenisPembayaran::where('is_active', true)->orderBy('nama')->get(['id', 'nama', 'tipe_keuangan', 'efek_tagihan', 'deskripsi']),
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
                'brand_id' => $selectedBrandId,
                'start_date' => $request->string('start_date')->toString(),
                'end_date' => $request->string('end_date')->toString(),
            ],
            'statuses' => Invoice::STATUSES,
            'can' => [
                'create' => $request->user() && ($request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan')),
                'validate' => $request->user() && ($request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan')),
                'publish' => $request->user() && ($request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan')),
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
        if (is_null($selectedBrandId) || $selectedBrandId === '' || $selectedBrandId === 'all') {
            $selectedBrandId = 'all';
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
            foreach (\App\Models\Order\Order::whereIn('id', $orderIds)->with(['items', 'payments.masterJenisPembayaran', 'brand'])->get() as $ord) {
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
                'brand_id' => $selectedBrandId,
            ],
            'can' => [
                'validate' => $request->user() && ($request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan')),
            ],
        ]);
    }

    public function createFromOrder(Request $request, Order $order)
    {
        abort_unless(
            $request->user() && ($request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan')),
            403, 'Hanya Admin Keuangan dan Superadmin yang dapat membuat invoice.'
        );
        abort_if(Invoice::where('order_id', $order->id)->exists(), 422, 'Invoice untuk PO ini sudah ada.');

        $order->load('items', 'payments', 'brand');

        $autoPublishAndSend = false;
        $invoice = DB::transaction(function () use ($order, &$autoPublishAndSend) {
            $totalTagihan = (float) $order->totalTagihan();
            $totalPaid = (float) $order->totalPaid();
            $sisa = $order->sisaTagihan();

            $hasVerifiedDp = $order->payments()
                ->whereNotNull('verified_at')
                ->where(function ($q) {
                    $q->whereHas('masterJenisPembayaran', function ($mj) {
                        $mj->where('nama', 'DP');
                    })->orWhere(function ($qp) {
                        $qp->whereNull('master_jenis_pembayaran_id')
                           ->where('payment_type', 'dp');
                    });
                })
                ->exists();

            $status = 'draft';
            if ($sisa <= 0) {
                $status = 'paid';
            } elseif ($hasVerifiedDp) {
                $status = 'published';
                $autoPublishAndSend = true;
            }

            $invoice = Invoice::create([
                'brand_id' => $order->brand_id,
                'order_id' => $order->id,
                'invoice_number' => $this->numbers->generateInvoiceNumber($order->brand, $order),
                'tanggal_terbit' => $order->tanggal_masuk ?? now()->toDateString(),
                'jatuh_tempo' => $order->deadline_customer ? $order->deadline_customer : now()->addDays(14)->toDateString(),
                'status' => $status,
                'total_tagihan' => $totalTagihan,
                'total_bayar' => $totalPaid,
                'bank_id' => \App\Models\Master\BankAccount::active()->where('brand_id', \App\Support\BrandContext::masterDataId(request(), $order->brand_id))->first()?->id,
                // dp_amount = total verified payments at invoice creation (supports both old payment_type and new master_jenis_pembayaran)
                'dp_amount' => $totalPaid,
                'sisa_pembayaran' => $sisa,
                'created_by' => \Illuminate\Support\Facades\Auth::id(),
            ]);

            foreach ($order->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'produk' => $item->nama_produk . ($item->varian_label ? " ({$item->varian_label})" : ''),
                    'jumlah' => $item->quantity,
                    'harga_satuan' => $item->harga_satuan,
                    'subtotal' => $item->subtotal,
                    'is_addon' => (bool) $item->is_addon,
                    'discount_type' => $item->discount_type,
                    'discount_value' => $item->discount_value,
                    'discount_amount' => $item->discount_amount,
                ]);
            }

            return $invoice;
        });

        if ($autoPublishAndSend && $invoice) {
            try {
                \App\Services\ActivityLogger::log('publish', 'invoice', $invoice, "Auto publish invoice {$invoice->invoice_number} via verified DP presence at creation");
                
                $sidobe = \App\Services\Notifications\SidobeClient::fromSettings();
                $waService = new \App\Services\Notifications\InvoiceWhatsappService($sidobe);
                $invoice->load(['order.pelanggan', 'brand']);
                
                if ($sidobe->isConfigured()) {
                    $phone = $waService->phoneFromInvoice($invoice);
                    if ($phone !== '') {
                        $result = $waService->send($invoice, 'new_invoice');
                        if ($result['success'] && ! ($result['mock'] ?? false)) {
                            $invoice->update([
                                'status' => 'sent',
                                'sent_via' => 'whatsapp',
                                'sent_at' => now(),
                            ]);
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Failed to auto-send invoice WA on creation: ' . $e->getMessage());
            }
        }

        return redirect()->route('invoices.list')
            ->with('success', "Invoice {$invoice->invoice_number} dibuat sebagai draft.");
    }

    public function validateInvoice(Request $request, Invoice $invoice)
    {
        abort_unless(
            $request->user() && ($request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan')),
            403, 'Hanya Admin Keuangan dan Superadmin yang dapat memvalidasi invoice.'
        );

        $data = $request->validate([
            'diskon_type' => ['nullable', Rule::in(['persen', 'nominal'])],
            'diskon_value' => ['nullable', 'numeric', 'min:0'],
            'biaya_pengiriman' => ['nullable', 'numeric', 'min:0'],
            'jasa_pengiriman' => ['nullable', 'string', 'max:100'],
            'bank_id' => ['nullable', 'uuid', 'exists:bank_accounts,id'],
            'catatan' => ['nullable', 'string'],
        ]);

        $order = $invoice->order;
        if ($order) {
            $order->load(['items', 'payments']);
        }

        // Calculate gross subtotal of invoice/order items
        $grossSubtotal = $order 
            ? (float) $order->items->sum(fn($item) => $item->quantity * $item->harga_satuan) 
            : (float) $invoice->items->sum(fn($item) => $item->jumlah * $item->harga_satuan);

        // 1. Fetch discount from order items (discount_amount sum)
        $diskonNominalFromOrder = $order ? (float) $order->items->sum('discount_amount') : 0.0;
        
        $diskonType = $data['diskon_type'] ?? 'nominal';
        $diskonValue = (float) ($data['diskon_value'] ?? 0);

        if ($diskonNominalFromOrder > 0) {
            $diskonType = 'nominal';
            $diskonValue = $diskonNominalFromOrder;
            $diskonNominal = $diskonNominalFromOrder;
        } else {
            $diskonNominal = $diskonType === 'persen'
                ? ($grossSubtotal * $diskonValue / 100)
                : $diskonValue;
        }

        // 2. Fetch shipping cost from order payments (Ongkir)
        $biayaPengirimanFromOrder = 0.0;
        if ($order) {
            $biayaPengirimanFromOrder = (float) $order->payments()
                ->whereNotNull('verified_at')
                ->where(function ($q) {
                    $q->whereHas('masterJenisPembayaran', function ($mj) {
                        $mj->where('nama', 'Ongkir');
                    })->orWhere(function ($qp) {
                        $qp->whereNull('master_jenis_pembayaran_id')
                           ->where('payment_type', 'ongkir');
                    });
                })
                ->sum('amount');
        }

        $biayaPengiriman = ($order && $order->is_free_ongkir)
            ? 0.0
            : ($biayaPengirimanFromOrder > 0 
                ? $biayaPengirimanFromOrder 
                : (float) ($data['biaya_pengiriman'] ?? 0));

        // 3. Fetch shipping service from production (stored in orders.nama_ekspedisi)
        $jasaPengiriman = ($order && $order->nama_ekspedisi) 
            ? $order->nama_ekspedisi 
            : ($data['jasa_pengiriman'] ?? null);

        // Calculate other adjustments (excluding shipping)
        $penambahanExcludingOngkir = 0.0;
        $pengurangan = 0.0;
        if ($order) {
            $penambahanExcludingOngkir = (float) $order->payments()
                ->whereNotNull('verified_at')
                ->where(function ($q) {
                    $q->whereHas('masterJenisPembayaran', function ($mj) {
                        $mj->where('efek_tagihan', 'penambahan')->where('nama', '!=', 'Ongkir');
                    })->orWhere(function ($qp) {
                        $qp->whereNull('master_jenis_pembayaran_id')
                           ->where('payment_type', 'tambahan_produk');
                    });
                })
                ->sum('amount');

            $pengurangan = (float) $order->payments()
                ->whereNotNull('verified_at')
                ->where(function ($q) {
                    $q->whereHas('masterJenisPembayaran', function ($mj) {
                        $mj->where('efek_tagihan', 'pengurangan');
                    })->orWhere(function ($qp) {
                        $qp->whereNull('master_jenis_pembayaran_id')
                           ->whereIn('payment_type', ['cashback', 'return']);
                    });
                })
                ->sum('amount');
        }

        // Final Nett Invoice Tagihan
        $invoiceTotalTagihan = max(0, $grossSubtotal - $diskonNominal + $biayaPengiriman + $penambahanExcludingOngkir - $pengurangan);
        $totalPaid = $order ? (float) $order->totalPaid() : 0.0;
        $sisaPembayaran = max(0, $invoiceTotalTagihan - $totalPaid);

        DB::transaction(function () use ($invoice, $order, $data, $diskonType, $diskonValue, $biayaPengiriman, $jasaPengiriman, $invoiceTotalTagihan, $totalPaid, $sisaPembayaran) {
            $invoice->update([
                'bank_id' => $data['bank_id'] ?? $invoice->bank_id,
                'catatan' => $data['catatan'] ?? $invoice->catatan,
                'diskon_type' => $diskonType,
                'diskon_value' => $diskonValue,
                'biaya_pengiriman' => $biayaPengiriman,
                'jasa_pengiriman' => $jasaPengiriman,
                'status' => 'validated',
                'dp_amount' => $totalPaid,
                'total_bayar' => $totalPaid,
                'total_tagihan' => $invoiceTotalTagihan,
                'sisa_pembayaran' => $sisaPembayaran,
            ]);

            // Automatically update PO completion status to Lunas if fully paid
            if ($order && $sisaPembayaran <= 0) {
                $order->update([
                    'is_lunas' => true,
                    'lunas_at' => now(),
                    'lunas_by' => \Illuminate\Support\Facades\Auth::id(),
                ]);
            }
        });

        return back()->with('success', 'Invoice divalidasi.');
    }

    public function cancelValidation(Request $request, Invoice $invoice)
    {
        abort_unless(
            $request->user() && ($request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan')),
            403, 'Hanya Admin Keuangan dan Superadmin yang dapat membatalkan validasi invoice.'
        );
        abort_unless(in_array($invoice->status, ['validated', 'paid'], true), 422, 'Invoice harus berstatus validated atau paid.');

        DB::transaction(function () use ($invoice) {
            $status = 'published';
            if ($invoice->sisa_pembayaran <= 0) {
                $status = 'paid';
            } elseif ($invoice->sent_at !== null) {
                $status = 'sent';
            }

            $invoice->update([
                'status' => $status,
            ]);

            $order = $invoice->order;
            if ($order && $order->is_lunas) {
                $order->update([
                    'is_lunas' => false,
                    'lunas_at' => null,
                    'lunas_by' => null,
                ]);
            }
        });

        \App\Services\ActivityLogger::log('cancel-validation', 'invoice', $invoice, "Batal validasi invoice {$invoice->invoice_number}");

        return back()->with('success', "Validasi invoice {$invoice->invoice_number} berhasil dibatalkan.");
    }

    public function publish(Request $request, Invoice $invoice)
    {
        abort_unless(
            $request->user() && ($request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan')),
            403, 'Hanya Admin Keuangan dan Superadmin yang dapat mempublikasikan invoice.'
        );
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
        abort_unless(
            $request->user() && ($request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan')),
            403, 'Hanya Admin Keuangan dan Superadmin yang dapat mengirim invoice via WhatsApp.'
        );
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
        abort_unless(
            $request->user() && ($request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan')),
            403, 'Hanya Admin Keuangan dan Superadmin yang dapat memverifikasi pembayaran.'
        );

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
                $order->load(['items', 'payments']);
                // Rekalkulasi total_tagihan di DB
                $order->update(['total_tagihan' => $order->totalTagihan()]);
                
                $invoice = $order->invoices()->first();
                if ($invoice) {
                    $diskonNominalFromOrder = (float) $order->items->sum('discount_amount');
                    $diskonType = $invoice->diskon_type ?? 'nominal';
                    $diskonValue = (float) ($invoice->diskon_value ?? 0);
                    
                    $grossSubtotal = (float) $order->items->sum(fn($item) => $item->quantity * $item->harga_satuan);
                    
                    if ($diskonNominalFromOrder > 0) {
                        $diskonNominal = $diskonNominalFromOrder;
                    } else {
                        $diskonNominal = $diskonType === 'persen'
                            ? ($grossSubtotal * $diskonValue / 100)
                            : $diskonValue;
                    }
                    
                    $biayaPengiriman = (float) ($invoice->biaya_pengiriman ?? 0);
                    
                    $penambahanExcludingOngkir = (float) $order->payments()
                        ->whereNotNull('verified_at')
                        ->where(function ($q) {
                            $q->whereHas('masterJenisPembayaran', function ($mj) {
                                $mj->where('efek_tagihan', 'penambahan')->where('nama', '!=', 'Ongkir');
                            })->orWhere(function ($qp) {
                                $qp->whereNull('master_jenis_pembayaran_id')
                                   ->where('payment_type', 'tambahan_produk');
                            });
                        })
                        ->sum('amount');

                    $pengurangan = (float) $order->payments()
                        ->whereNotNull('verified_at')
                        ->where(function ($q) {
                            $q->whereHas('masterJenisPembayaran', function ($mj) {
                                $mj->where('efek_tagihan', 'pengurangan');
                            })->orWhere(function ($qp) {
                                $qp->whereNull('master_jenis_pembayaran_id')
                                   ->whereIn('payment_type', ['cashback', 'return']);
                            });
                        })
                        ->sum('amount');
                        
                    $invoiceTotalTagihan = max(0, $grossSubtotal - $diskonNominal + $biayaPengiriman + $penambahanExcludingOngkir - $pengurangan);
                    $totalPaid = $order->totalPaid();
                    $newSisa = max(0, $invoiceTotalTagihan - $totalPaid);
                    
                    $targetStatus = $invoice->status;
                    if ($newSisa <= 0) {
                        $targetStatus = ($invoice->status === 'validated') ? 'validated' : 'paid';
                    } else {
                        $isDp = $payment->payment_type === 'dp';
                        if ($isDp && in_array($invoice->status, ['draft', 'validated'], true)) {
                            $targetStatus = 'published';
                        } elseif (in_array($invoice->status, ['paid', 'validated'], true)) {
                            $targetStatus = $invoice->sent_at !== null ? 'sent' : 'published';
                        }
                    }

                    $invoice->update([
                        'total_tagihan' => $invoiceTotalTagihan,
                        'total_bayar' => $totalPaid,
                        'sisa_pembayaran' => $newSisa,
                        'status' => $targetStatus,
                    ]);
                }
            }
        });

        return back()->with('success', 'Pembayaran berhasil diverifikasi.');
    }

    public function updatePayment(Request $request, OrderPayment $payment)
    {
        abort_unless(
            $request->user() && ($request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan')),
            403, 'Hanya Admin Keuangan dan Superadmin yang dapat mengubah data pembayaran.'
        );

        $request->validate([
            'master_jenis_pembayaran_id' => 'required|exists:master_jenis_pembayarans,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'bank_id' => 'required|exists:bank_accounts,id',
            'notes' => 'nullable|string',
            'change_reason' => 'required|string|min:5',
        ]);

        $order = $payment->order;
        $user = $request->user();

        // Prepare old values details
        $oldMaster = $payment->masterJenisPembayaran;
        $oldMasterName = $oldMaster ? $oldMaster->nama : strtoupper($payment->payment_type ?? 'Pembayaran');
        $oldBank = $payment->bank;
        $oldBankName = $oldBank ? "{$oldBank->bank} ({$oldBank->nomor_rekening})" : '—';
        $oldAmountFormatted = 'Rp ' . number_format($payment->amount, 0, ',', '.');
        $oldDate = $payment->payment_date ? $payment->payment_date->toDateString() : '—';
        $oldNotes = $payment->notes ?? '—';

        $oldDetail = "Tipe: {$oldMasterName}, Nominal: {$oldAmountFormatted}, Tanggal: {$oldDate}, Bank: {$oldBankName}, Catatan: {$oldNotes}";

        // Retrieve the new MasterJenisPembayaran details
        $newMaster = \App\Models\Finance\MasterJenisPembayaran::find($request->master_jenis_pembayaran_id);
        $newMasterName = $newMaster ? $newMaster->nama : '—';
        $newBank = $request->bank_id ? \App\Models\Master\BankAccount::find($request->bank_id) : null;
        $newBankName = $newBank ? "{$newBank->bank} ({$newBank->nomor_rekening})" : '—';
        $newAmountFormatted = 'Rp ' . number_format($request->amount, 0, ',', '.');
        $newDate = $request->payment_date;
        $newNotes = $request->notes ?? '—';

        $newDetail = "Tipe: {$newMasterName}, Nominal: {$newAmountFormatted}, Tanggal: {$newDate}, Bank: {$newBankName}, Catatan: {$newNotes}";

        DB::transaction(function () use ($payment, $order, $request, $user, $oldDetail, $newDetail, $newMaster) {
            $map = [
                'DP' => 'dp',
                'Pelunasan' => 'pelunasan',
                'Ongkir' => 'ongkir',
                'Tambahan Produk' => 'tambahan_produk',
                'Cashback' => 'cashback',
                'Return' => 'return',
                'Lainnya' => 'lainnya',
            ];
            $paymentType = $map[$newMaster->nama] ?? 'lainnya';
            $isDebit = !in_array($paymentType, ['cashback', 'return']);

            // Update the payment record
            $payment->update([
                'master_jenis_pembayaran_id' => $request->master_jenis_pembayaran_id,
                'payment_type' => $paymentType,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'bank_id' => $request->bank_id,
                'notes' => $request->notes,
                'is_debit' => $isDebit,
            ]);

            // Create PO change log
            \App\Models\Order\POChangeLog::create([
                'order_id' => $order->id,
                'changed_by' => $user->id,
                'field_changed' => 'pembayaran_diedit',
                'old_value' => $oldDetail,
                'new_value' => $newDetail,
                'change_reason' => $request->change_reason,
            ]);

            // Sync with ledger if verified
            if ($payment->verified_at !== null) {
                $isPemasukan = ($newMaster->tipe_keuangan === 'pemasukan');
                $paymentName = $newMaster->nama;

                if ($isPemasukan) {
                    Pengeluaran::where('source_payment_id', $payment->id)->delete();

                    $kategori = KategoriPemasukan::firstOrCreate(
                        ['brand_id' => $order->brand_id, 'nama_kategori' => 'Pembayaran PO'],
                        [
                            'deskripsi' => 'Pembayaran pesanan dari customer',
                            'is_system'  => true,
                            'is_active'  => true,
                        ]
                    );

                    Pemasukan::updateOrCreate(
                        ['source_payment_id' => $payment->id],
                        [
                            'brand_id'             => $order->brand_id,
                            'kategori_pemasukan_id' => $kategori->id,
                            'order_id'             => $order->id,
                            'tanggal'              => $payment->payment_date,
                            'nominal'              => $payment->amount,
                            'keterangan'           => "{$paymentName} PO {$order->no_po} — {$order->pelanggan?->nama}",
                            'is_auto'              => true,
                            'created_by'           => $user->id,
                        ]
                    );
                } else {
                    Pemasukan::where('source_payment_id', $payment->id)->delete();

                    $kategoriName = $paymentType === 'cashback' ? 'Cashback PO' : 'Refund/Return PO';
                    $kategoriDesc = $paymentType === 'cashback' ? 'Cashback dari PO' : 'Pengembalian uang / return dari PO';

                    $kategori = KategoriPengeluaran::firstOrCreate(
                        ['brand_id' => $order->brand_id, 'nama_kategori' => $kategoriName],
                        [
                            'deskripsi' => $kategoriDesc,
                            'is_system'  => true,
                            'is_active'  => true,
                        ]
                    );

                    $label = match ($paymentType) {
                        'cashback' => 'Cashback',
                        'return'   => 'Refund/Return',
                        default    => 'Pengeluaran Lainnya',
                    };

                    Pengeluaran::updateOrCreate(
                        ['source_payment_id' => $payment->id],
                        [
                            'brand_id'               => $order->brand_id,
                            'kategori_pengeluaran_id' => $kategori->id,
                            'tanggal'                => $payment->payment_date,
                            'nominal'                => $payment->amount,
                            'keterangan'             => "{$label} PO {$order->no_po} — {$order->pelanggan?->nama}",
                            'is_auto'                => true,
                            'created_by'             => $user->id,
                        ]
                    );
                }
            }

            // Recalculate invoice & order totals
            $order->update(['total_tagihan' => $order->totalTagihan()]);
            $invoice = $order->invoices()->first();
            if ($invoice) {
                $newTotal = $order->totalTagihan();
                $newPaid  = $order->totalPaid();
                
                $diskonNominal = $invoice->diskon_type === 'persen'
                    ? ($newTotal * (float)$invoice->diskon_value / 100)
                    : (float)$invoice->diskon_value;
                $newSisa = max(0, $newTotal - $diskonNominal + (float)$invoice->biaya_pengiriman - $newPaid);
                
                $targetStatus = $invoice->status;
                if ($newSisa <= 0) {
                    $targetStatus = ($invoice->status === 'validated') ? 'validated' : 'paid';
                } else {
                    if (in_array($invoice->status, ['paid', 'validated'], true)) {
                        $targetStatus = $invoice->sent_at !== null ? 'sent' : 'published';
                    }
                }

                $invoice->update([
                    'total_tagihan'  => $newTotal,
                    'total_bayar'    => $newPaid,
                    'sisa_pembayaran' => $newSisa,
                    'status' => $targetStatus,
                ]);
            }
        });

        return back()->with('success', 'Data pembayaran dan catatan keuangan terkait berhasil diperbarui.');
    }

    public function destroyPayment(Request $request, OrderPayment $payment)
    {
        abort_unless(
            $request->user() && ($request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan')),
            403, 'Hanya Admin Keuangan dan Superadmin yang dapat menghapus data pembayaran.'
        );

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
                    
                    $diskonNominal = $invoice->diskon_type === 'persen'
                        ? ($newTotal * (float)$invoice->diskon_value / 100)
                        : (float)$invoice->diskon_value;
                    $newSisa = max(0, $newTotal - $diskonNominal + (float)$invoice->biaya_pengiriman - $newPaid);
                    
                    $targetStatus = $invoice->status;
                    if ($newSisa <= 0) {
                        $targetStatus = ($invoice->status === 'validated') ? 'validated' : 'paid';
                    } else {
                        if (in_array($invoice->status, ['paid', 'validated'], true)) {
                            $targetStatus = $invoice->sent_at !== null ? 'sent' : 'published';
                        }
                    }

                    $invoice->update([
                        'total_tagihan'  => $newTotal,
                        'total_bayar'    => $newPaid,
                        'sisa_pembayaran' => $newSisa,
                        'status' => $targetStatus,
                    ]);
                }
            }
        });

        return back()->with('success', 'Data pembayaran dan catatan keuangan terkait berhasil dihapus.');
    }
}
