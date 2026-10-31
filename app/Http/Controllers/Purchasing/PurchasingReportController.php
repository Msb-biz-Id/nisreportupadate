<?php

namespace App\Http\Controllers\Purchasing;

use App\Exports\Purchasing\PurchasingReportExport;
use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmMasterOption;
use App\Models\Purchasing\PurchasingMasterOption;
use App\Models\Purchasing\PurchasingOrder;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PurchasingReportController extends Controller
{
    /**
     * Tampilkan halaman analitik dan rekapitulasi laporan pengadaan.
     */
    public function index(Request $request): Response
    {
        $period = $request->input('period', 'this_month');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $department = $request->input('department');
        $division = $request->input('division');
        $locationId = $request->input('location_id');
        $orderType = $request->input('order_type', 'ALL');

        // Normalisasi tanggal berdasarkan period
        if ($period === 'this_month' && empty($dateFrom)) {
            $dateFrom = now()->startOfMonth()->toDateString();
            $dateTo = now()->endOfMonth()->toDateString();
        } elseif ($period === 'last_month' && empty($dateFrom)) {
            $dateFrom = now()->subMonth()->startOfMonth()->toDateString();
            $dateTo = now()->subMonth()->endOfMonth()->toDateString();
        } elseif ($period === 'this_year' && empty($dateFrom)) {
            $dateFrom = now()->startOfYear()->toDateString();
            $dateTo = now()->endOfYear()->toDateString();
        }

        $query = PurchasingOrder::with(['vendor', 'location', 'payments'])
            ->where('status', '!=', 'CANCELLED');

        if (!empty($dateFrom)) {
            $query->whereDate('transaction_date', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->whereDate('transaction_date', '<=', $dateTo);
        }
        if (!empty($orderType) && $orderType !== 'ALL') {
            $query->where('order_type', $orderType);
        }
        if (!empty($department)) {
            $query->where('department', $department);
        }
        if (!empty($division)) {
            $query->where('division', $division);
        }
        if (!empty($locationId)) {
            $query->where('location_id', $locationId);
        }

        $allOrders = $query->orderByDesc('transaction_date')->get();

        // 1. Ringkasan Eksekutif KPI
        $totalOpex = (float) $allOrders->where('order_type', 'OPEX')->sum('grand_total');
        $totalCapex = (float) $allOrders->where('order_type', 'CAPEX')->sum('grand_total');
        $totalSpending = $totalOpex + $totalCapex;
        $totalOrders = $allOrders->count();

        $totalPaid = 0;
        foreach ($allOrders as $order) {
            $totalPaid += (float) $order->payments->where('status', 'PAID')->sum('amount');
        }
        $totalUnpaid = max(0, $totalSpending - $totalPaid);

        $summary = [
            'total_opex' => $totalOpex,
            'total_capex' => $totalCapex,
            'total_spending' => $totalSpending,
            'total_orders' => $totalOrders,
            'total_paid' => $totalPaid,
            'total_unpaid' => $totalUnpaid,
        ];

        // 2. Rekapitulasi per Departemen
        $deptGroups = $allOrders->groupBy('department')->map(function ($items, $dept) use ($totalSpending) {
            $opex = (float) $items->where('order_type', 'OPEX')->sum('grand_total');
            $capex = (float) $items->where('order_type', 'CAPEX')->sum('grand_total');
            $total = $opex + $capex;
            return [
                'department' => $dept ?: 'Tanpa Departemen',
                'total_opex' => $opex,
                'total_capex' => $capex,
                'total_spending' => $total,
                'order_count' => $items->count(),
                'percentage' => $totalSpending > 0 ? round(($total / $totalSpending) * 100, 1) : 0,
            ];
        })->values()->sortByDesc('total_spending')->values()->all();

        // 3. Rekapitulasi per Divisi
        $divGroups = $allOrders->groupBy('division')->map(function ($items, $div) {
            $dept = $items->first()?->department ?: '-';
            $total = (float) $items->sum('grand_total');
            return [
                'division' => $div ?: 'Umum / Tanpa Divisi',
                'department' => $dept,
                'total_spending' => $total,
                'order_count' => $items->count(),
            ];
        })->values()->sortByDesc('total_spending')->values()->all();

        // 4. Rekapitulasi per Lokasi Ruko
        $locGroups = $allOrders->groupBy(function ($item) {
            return $item->location?->name ?: 'Pusat / Head Office';
        })->map(function ($items, $loc) {
            return [
                'location_name' => $loc,
                'total_spending' => (float) $items->sum('grand_total'),
                'order_count' => $items->count(),
            ];
        })->values()->sortByDesc('total_spending')->values()->all();

        // 5. Top 8 Vendor Terbesar
        $topVendors = $allOrders->groupBy(function ($item) {
            return $item->vendor?->name ?: ($item->vendor_name_manual ?: 'Vendor Lainnya');
        })->map(function ($items, $vName) {
            return [
                'vendor_name' => $vName,
                'total_spending' => (float) $items->sum('grand_total'),
                'order_count' => $items->count(),
            ];
        })->values()->sortByDesc('total_spending')->take(8)->values()->all();

        // 6. Transaksi Terbaru (Limit 15 untuk tampilan tabel ringkasan)
        $recentOrders = $allOrders->take(20)->map(function ($o) {
            return [
                'id' => $o->id,
                'po_number' => $o->po_number,
                'transaction_date' => $o->transaction_date?->format('d M Y'),
                'order_type' => $o->order_type,
                'department' => $o->department,
                'division' => $o->division,
                'requester_name' => $o->requester_name,
                'vendor_name' => $o->vendor?->name ?: ($o->vendor_name_manual ?: '-'),
                'item_category' => $o->item_category,
                'status' => $o->status,
                'grand_total' => (float) $o->grand_total,
            ];
        });

        // 7. Master filter options
        $locations = PurchasingMasterOption::byCategory('lokasi')->select(['id', 'name', 'code'])->get();
        $departments = class_exists(HcmMasterOption::class) ? HcmMasterOption::getDepartmentsWithCodes() : [];
        $divisions = class_exists(HcmMasterOption::class) ? HcmMasterOption::getOptions('divisi') : [];

        return Inertia::render('Purchasing/Reports/Index', [
            'summary' => $summary,
            'dept_breakdown' => $deptGroups,
            'div_breakdown' => $divGroups,
            'loc_breakdown' => $locGroups,
            'top_vendors' => $topVendors,
            'recent_orders' => $recentOrders,
            'locations' => $locations,
            'departments' => $departments,
            'divisions' => $divisions,
            'filters' => [
                'period' => $period,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'department' => $department,
                'division' => $division,
                'location_id' => $locationId,
                'order_type' => $orderType,
            ],
        ]);
    }

    /**
     * Unduh Laporan Pengadaan dalam format Excel (.xlsx).
     */
    public function exportExcel(Request $request): BinaryFileResponse
    {
        $filters = $this->buildFilterPayload($request);
        $fileName = 'Laporan-Purchasing-' . now()->format('Ymd-His') . '.xlsx';

        ActivityLogger::log('export', 'purchasing', null, "Ekspor Laporan Pengadaan ke Excel: {$fileName}");

        return Excel::download(new PurchasingReportExport($filters), $fileName);
    }

    /**
     * Unduh Laporan Pengadaan dalam format PDF resmi.
     */
    public function exportPdf(Request $request)
    {
        $filters = $this->buildFilterPayload($request);

        $query = PurchasingOrder::with(['vendor', 'location', 'payments'])
            ->where('status', '!=', 'CANCELLED');

        if (!empty($filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('transaction_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['order_type']) && $filters['order_type'] !== 'ALL') {
            $query->where('order_type', $filters['order_type']);
        }
        if (!empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }
        if (!empty($filters['division'])) {
            $query->where('division', $filters['division']);
        }
        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        $orders = $query->orderByDesc('transaction_date')->get();

        $totalOpex = (float) $orders->where('order_type', 'OPEX')->sum('grand_total');
        $totalCapex = (float) $orders->where('order_type', 'CAPEX')->sum('grand_total');
        $summary = [
            'total_opex' => $totalOpex,
            'total_capex' => $totalCapex,
            'total_spending' => $totalOpex + $totalCapex,
            'total_orders' => $orders->count(),
        ];

        $pdf = Pdf::loadView('pdf.purchasing.report', [
            'orders' => $orders,
            'summary' => $summary,
            'period_label' => $filters['period_label'],
            'title' => 'Laporan Rekapitulasi Pengadaan',
        ])->setPaper('a4', 'landscape');

        ActivityLogger::log('export', 'purchasing', null, "Ekspor Laporan Pengadaan ke PDF");

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Laporan-Purchasing-' . now()->format('Ymd-His') . '.pdf"',
        ]);
    }

    private function buildFilterPayload(Request $request): array
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $period = $request->input('period', 'this_month');

        if ($period === 'this_month' && empty($dateFrom)) {
            $dateFrom = now()->startOfMonth()->toDateString();
            $dateTo = now()->endOfMonth()->toDateString();
        } elseif ($period === 'last_month' && empty($dateFrom)) {
            $dateFrom = now()->subMonth()->startOfMonth()->toDateString();
            $dateTo = now()->subMonth()->endOfMonth()->toDateString();
        } elseif ($period === 'this_year' && empty($dateFrom)) {
            $dateFrom = now()->startOfYear()->toDateString();
            $dateTo = now()->endOfYear()->toDateString();
        }

        $periodLabel = 'Semua Waktu';
        if (!empty($dateFrom) && !empty($dateTo)) {
            $periodLabel = date('d/m/Y', strtotime($dateFrom)) . ' - ' . date('d/m/Y', strtotime($dateTo));
        }

        return [
            'period' => $period,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'period_label' => $periodLabel,
            'order_type' => $request->input('order_type', 'ALL'),
            'department' => $request->input('department'),
            'division' => $request->input('division'),
            'location_id' => $request->input('location_id'),
        ];
    }
}
