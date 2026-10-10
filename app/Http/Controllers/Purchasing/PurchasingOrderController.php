<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\ApprovePurchasingOrderRequest;
use App\Http\Requests\Purchasing\StorePurchasingOrderRequest;
use App\Http\Requests\Purchasing\UpdatePurchasingOrderRequest;
use App\Models\Purchasing\PurchasingMasterOption;
use App\Models\Purchasing\PurchasingOrder;
use App\Models\Purchasing\PurchasingVendor;
use App\Services\ActivityLogger;
use App\Services\Purchasing\PurchasingHrisService;
use App\Services\Purchasing\PurchasingOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PurchasingOrderController extends Controller
{
    public function __construct(
        protected PurchasingOrderService $orderService,
        protected PurchasingHrisService $hrisService
    ) {}

    /**
     * Display listing of Purchasing Orders with multi-criteria filtering
     */
    public function index(Request $request): Response
    {
        Gate::authorize('purchasing.view');

        $search = $request->query('search');
        $status = $request->query('status');
        $orderType = $request->query('order_type');
        $department = $request->query('department');
        $locationId = $request->query('location_id');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $orders = PurchasingOrder::query()
            ->with([
                'vendor.primaryContact',
                'location',
                'itemCategory',
                'requester',
                'picApprover',
                'financeApprover',
            ])
            ->when($search, function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('po_number', 'like', "%{$s}%")
                        ->orWhere('item_name', 'like', "%{$s}%")
                        ->orWhere('specification', 'like', "%{$s}%")
                        ->orWhere('requester_name', 'like', "%{$s}%")
                        ->orWhere('vendor_name_manual', 'like', "%{$s}%")
                        ->orWhereHas('vendor', fn ($vq) => $vq->where('name', 'like', "%{$s}%"));
                });
            })
            ->when($status, fn ($q, $st) => $q->where('status', $st))
            ->when($orderType, fn ($q, $ot) => $q->where('order_type', $ot))
            ->when($department, fn ($q, $dept) => $q->where('department', $dept))
            ->when($locationId, fn ($q, $loc) => $q->where('location_id', $loc))
            ->when($startDate, fn ($q, $sd) => $q->whereDate('transaction_date', '>=', $sd))
            ->when($endDate, fn ($q, $ed) => $q->whereDate('transaction_date', '<=', $ed))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Rekapitulasi metrik ringkas untuk header cards
        $metrics = [
            'total_orders' => PurchasingOrder::count(),
            'pending_pic' => PurchasingOrder::where('status', 'PENDING_PIC_CHECK')->count(),
            'pending_finance' => PurchasingOrder::where('status', 'APPROVED_BY_PIC')->count(),
            'completed' => PurchasingOrder::whereIn('status', ['PURCHASE_COMPLETED', 'PAID_COMPLETED'])->count(),
            'total_opex_amount' => (float) PurchasingOrder::where('order_type', 'OPEX')->whereIn('status', ['APPROVED_BY_PIC', 'PURCHASE_COMPLETED', 'PAID_COMPLETED'])->sum('grand_total'),
        ];

        // Master dropdowns
        $statuses = PurchasingMasterOption::getOptions('purchase_status');
        $locations = PurchasingMasterOption::getOptions('location');
        $departments = $this->hrisService->getDepartments();

        return Inertia::render('Purchasing/Orders/Index', [
            'orders' => $orders,
            'metrics' => $metrics,
            'statuses' => $statuses,
            'locations' => $locations,
            'departments' => $departments,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'order_type' => $orderType,
                'department' => $department,
                'location_id' => $locationId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Show form to create new Purchasing Order
     */
    public function create(): Response
    {
        Gate::authorize('purchasing.manage-orders');

        $vendors = PurchasingVendor::where('is_active', true)
            ->with('primaryContact')
            ->orderBy('name')
            ->get(['id', 'uuid', 'vendor_code', 'name', 'category', 'phone', 'term_days']);

        $itemCategories = PurchasingMasterOption::getOptions('item_category');
        $units = PurchasingMasterOption::getOptions('unit');
        $locations = PurchasingMasterOption::getOptions('location');
        $paymentTypes = PurchasingMasterOption::getOptions('payment_type');
        $hrisEmployees = $this->hrisService->getActiveEmployees();
        $hrisReferences = $this->hrisService->getHrisReferences();

        return Inertia::render('Purchasing/Orders/Create', [
            'vendors' => $vendors,
            'itemCategories' => $itemCategories,
            'units' => $units,
            'locations' => $locations,
            'paymentTypes' => $paymentTypes,
            'hrisEmployees' => $hrisEmployees,
            'hrisReferences' => $hrisReferences,
        ]);
    }

    /**
     * Store newly created Purchasing Order
     */
    public function store(StorePurchasingOrderRequest $request): RedirectResponse
    {
        $order = $this->orderService->createOrder(
            $request->validated(),
            $request->file('invoice_file'),
            $request->user()
        );

        return redirect()
            ->route('purchasing.orders.show', $order->po_number)
            ->with('success', "Order Pembelian {$order->po_number} berhasil dibuat.");
    }

    /**
     * Display detailed Purchasing Order
     */
    public function show(PurchasingOrder $order): Response
    {
        Gate::authorize('purchasing.view');

        $order->load([
            'vendor.contacts',
            'location',
            'itemCategory',
            'requester',
            'picApprover',
            'financeApprover',
            'creator',
            'payments',
        ]);

        return Inertia::render('Purchasing/Orders/Show', [
            'order' => $order,
        ]);
    }

    /**
     * Show form to edit existing Purchasing Order
     */
    public function edit(PurchasingOrder $order): Response
    {
        Gate::authorize('purchasing.manage-orders');

        if (!in_array($order->status, ['DRAFT', 'PENDING_PIC_CHECK', 'REJECTED'])) {
            abort(403, 'Order ini sudah masuk tahap approval dan tidak dapat diubah lagi.');
        }

        $order->load(['vendor.primaryContact', 'requester']);

        $vendors = PurchasingVendor::where('is_active', true)
            ->with('primaryContact')
            ->orderBy('name')
            ->get(['id', 'uuid', 'vendor_code', 'name', 'category', 'phone', 'term_days']);

        $itemCategories = PurchasingMasterOption::getOptions('item_category');
        $units = PurchasingMasterOption::getOptions('unit');
        $locations = PurchasingMasterOption::getOptions('location');
        $paymentTypes = PurchasingMasterOption::getOptions('payment_type');
        $hrisEmployees = $this->hrisService->getActiveEmployees();
        $hrisReferences = $this->hrisService->getHrisReferences();

        return Inertia::render('Purchasing/Orders/Edit', [
            'order' => $order,
            'vendors' => $vendors,
            'itemCategories' => $itemCategories,
            'units' => $units,
            'locations' => $locations,
            'paymentTypes' => $paymentTypes,
            'hrisEmployees' => $hrisEmployees,
            'hrisReferences' => $hrisReferences,
        ]);
    }

    /**
     * Update existing Purchasing Order
     */
    public function update(UpdatePurchasingOrderRequest $request, PurchasingOrder $order): RedirectResponse
    {
        $updatedOrder = $this->orderService->updateOrder(
            $order,
            $request->validated(),
            $request->file('invoice_file'),
            $request->user()
        );

        return redirect()
            ->route('purchasing.orders.show', $updatedOrder->po_number)
            ->with('success', "Order Pembelian {$updatedOrder->po_number} berhasil diperbarui.");
    }

    /**
     * Soft delete an order
     */
    public function destroy(PurchasingOrder $order): RedirectResponse
    {
        Gate::authorize('purchasing.manage-orders');

        $poNumber = $order->po_number;
        $order->delete();

        ActivityLogger::log('delete', 'purchasing', $order, "Menghapus PO {$poNumber}");

        return redirect()
            ->route('purchasing.orders.index')
            ->with('success', "Order Pembelian {$poNumber} berhasil dihapus.");
    }

    /**
     * Double Sign-Off Approval & Rejection Handler
     */
    public function approve(ApprovePurchasingOrderRequest $request, PurchasingOrder $order): RedirectResponse
    {
        $action = $request->input('action');
        $notes = $request->input('notes');

        if ($action === 'approve_pic') {
            $this->orderService->approvePic($order, $request->user(), $notes);
            $message = "Verifikasi teknis PIC untuk {$order->po_number} berhasil disetujui.";
        } elseif ($action === 'approve_finance') {
            $this->orderService->approveFinance($order, $request->user(), $notes);
            $message = "Otorisasi anggaran Keuangan (Double Sign-Off) untuk {$order->po_number} berhasil disetujui.";
        } elseif ($action === 'reject') {
            $reason = $request->input('rejection_reason', 'Tanpa alasan');
            $this->orderService->rejectOrder($order, $request->user(), $reason);
            $message = "Order Pembelian {$order->po_number} telah ditolak.";
        } else {
            return back()->with('error', 'Aksi tidak valid.');
        }

        return back()->with('success', $message);
    }

    /**
     * API JSON Live Purchase History Search for price benchmarks
     */
    public function history(Request $request): JsonResponse
    {
        Gate::authorize('purchasing.view');

        $query = $request->query('q', '');
        $results = $this->orderService->searchPurchaseHistory($query, 15);

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }
}
