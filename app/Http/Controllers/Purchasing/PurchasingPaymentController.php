<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\RecordPurchasingPaymentRequest;
use App\Http\Requests\Purchasing\StorePurchasingPaymentTermsRequest;
use App\Models\Purchasing\PurchasingOrder;
use App\Models\Purchasing\PurchasingPayment;
use App\Services\Purchasing\PurchasingPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PurchasingPaymentController extends Controller
{
    public function __construct(
        protected PurchasingPaymentService $paymentService
    ) {}

    /**
     * Store or replace payment terms schedule for a PO
     */
    public function storeTerms(StorePurchasingPaymentTermsRequest $request, PurchasingOrder $order): RedirectResponse
    {
        try {
            $this->paymentService->generateTerms(
                $order,
                $request->validated()['terms'],
                $request->user()
            );

            return back()->with('success', "Jadwal termin pembayaran untuk PO {$order->po_number} berhasil disimpan.");
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['terms' => $e->getMessage()]);
        }
    }

    /**
     * Record payment clearance & transfer receipt by Finance
     */
    public function pay(RecordPurchasingPaymentRequest $request, PurchasingPayment $payment): RedirectResponse
    {
        $this->paymentService->recordPayment(
            $payment,
            $request->validated(),
            $request->file('receipt_file'),
            $request->user()
        );

        return back()->with('success', "Pembayaran {$payment->term_name} untuk PO {$payment->order?->po_number} berhasil diverifikasi & dicatat.");
    }
}
