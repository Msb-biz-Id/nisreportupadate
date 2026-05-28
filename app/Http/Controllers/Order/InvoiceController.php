<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order\Invoice;
use App\Models\Order\InvoiceItem;
use App\Models\Order\Order;
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

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'qrCodeData' => $qrCodeData,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("Invoice-{$invoice->invoice_number}.pdf");
    }

    public function publicShow(string $invoiceNumber)
    {
        $invoice = Invoice::where('invoice_number', $invoiceNumber)
            ->whereIn('status', ['published', 'sent', 'paid'])
            ->with(['brand', 'bank', 'items', 'order.pelanggan', 'order.progressDetails.progress'])
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

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'qrCodeData' => $qrCodeData,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("Invoice-{$invoice->invoice_number}.pdf");
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

        $query = Invoice::query()
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
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

        $invoices = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return Inertia::render('Finance/InvoiceIndex', [
            'invoices' => $invoices,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
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
        abort_if($order->isDraft(), 422, 'Hanya PO terbitkan yang bisa dibuatkan invoice.');
        abort_if(Invoice::where('order_id', $order->id)->exists(), 422, 'Invoice untuk PO ini sudah ada.');

        $order->load('items', 'payments', 'brand');

        $invoice = DB::transaction(function () use ($order) {
            $totalTagihan = (float) $order->total_tagihan;
            $dp = (float) $order->payments()->where('payment_type', 'dp')->sum('amount');

            $invoice = Invoice::create([
                'brand_id' => $order->brand_id,
                'order_id' => $order->id,
                'invoice_number' => $this->numbers->generateInvoiceNumber($order->brand),
                'tanggal_terbit' => now()->toDateString(),
                'jatuh_tempo' => now()->addDays(14)->toDateString(),
                'status' => 'draft',
                'total_tagihan' => $totalTagihan,
                'dp_amount' => $dp,
                'sisa_pembayaran' => max(0, $totalTagihan - $dp),
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
}
