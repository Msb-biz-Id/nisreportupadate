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
        $invoice->load(['brand', 'bank', 'items', 'order.pelanggan']);
        $trackingUrl = url('/track/' . $invoice->order->no_po);
        $qrCodeData = $this->qrCodeDataUri($trackingUrl);
        $logoData = $this->logoDataUri($invoice->brand->logo);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'qrCodeData' => $qrCodeData,
            'logoData' => $logoData,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("Invoice-{$invoice->invoice_number}.pdf");
    }

    public function publicShow(string $invoiceNumber)
    {
        $invoice = Invoice::where('invoice_number', $invoiceNumber)
            ->whereIn('status', ['published', 'sent', 'paid'])
            ->with(['brand', 'bank', 'items', 'order.pelanggan', 'order.payments.bank', 'order.progressDetails.progress'])
            ->firstOrFail();

        $trackingUrl = url('/track/' . $invoice->order->no_po);

        return Inertia::render('Public/Invoice', [
            'invoice' => $invoice,
            'qr_code' => $this->qrCodeDataUri($trackingUrl),
            'tracking_url' => $trackingUrl,
        ]);
    }

    public function publicPdf(string $invoiceNumber)
    {
        $invoice = Invoice::where('invoice_number', $invoiceNumber)
            ->whereIn('status', ['published', 'sent', 'paid'])
            ->with(['brand', 'bank', 'items', 'order.pelanggan'])
            ->firstOrFail();

        $trackingUrl = url('/track/' . $invoice->order->no_po);
        $qrCodeData = $this->qrCodeDataUri($trackingUrl);
        $logoData = $this->logoDataUri($invoice->brand->logo);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'qrCodeData' => $qrCodeData,
            'logoData' => $logoData,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("Invoice-{$invoice->invoice_number}.pdf");
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
        Gate::authorize('finance.view');
        $brandId = BrandContext::current($request);
        $selectedBrandId = $request->input('brand_id', $brandId);

        $query = Invoice::query()
            ->when($selectedBrandId && $selectedBrandId !== 'all', fn ($q) => $q->where('brand_id', $selectedBrandId))
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

        $user = $request->user();
        $brands = $user->isSuperadmin()
            ? \App\Models\Brand::orderBy('nama_brand')->get(['id', 'nama_brand', 'kode'])
            : $user->brands()->orderBy('nama_brand')->get(['brands.id', 'nama_brand', 'kode']);

        $pendingPayments = OrderPayment::query()
            ->whereNull('verified_at')
            ->with(['order:id,no_po,nama_po,brand_id', 'order.brand:id,nama_brand,kode', 'recorder:id,name', 'bank:id,account_name,account_number,bank_name'])
            ->orderByDesc('created_at')
            ->get();

        // Fetch Design Deposits (Tanda Jadi)
        $depositsQuery = \App\Models\Order\DesignDeposit::query()
            ->when($selectedBrandId && $selectedBrandId !== 'all', fn ($q) => $q->where('brand_id', $selectedBrandId))
            ->with(['brand:id,nama_brand,kode', 'recorder:id,name', 'verifier:id,name', 'bank:id,account_name,account_number,bank_name', 'order:id,no_po'])
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
            ->whereDoesntHave('invoices')
            ->orderByDesc('created_at')
            ->get(['id', 'no_po', 'nama_po', 'total_tagihan']);

        $bankAccounts = \App\Models\Master\BankAccount::where('is_active', true)
            ->get(['id', 'bank_name', 'account_name', 'account_number']);

        return Inertia::render('Finance/InvoiceIndex', [
            'invoices' => $invoices,
            'all_filtered_invoices' => $allFiltered,
            'brands' => $brands,
            'pending_payments' => $pendingPayments,
            'design_deposits' => $designDeposits,
            'available_orders' => $availableOrders,
            'bank_accounts' => $bankAccounts,
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
                'validate' => $request->user()->can('finance.manage-invoice'),
                'publish' => $request->user()->can('finance.manage-invoice'),
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

        return redirect()->route('invoices.index')
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

        DB::transaction(function () use ($payment, $request) {
            $payment->update([
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
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
