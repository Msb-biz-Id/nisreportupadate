<?php

namespace App\Services\Purchasing;

use App\Models\Purchasing\PurchasingMaterialStock;
use App\Models\Purchasing\PurchasingOrder;
use App\Models\Purchasing\PurchasingPayment;
use Carbon\Carbon;

class PurchasingDashboardService
{
    /**
     * Gather comprehensive dashboard metrics and 5-Tier Color Coding alerts.
     */
    public function getDashboardData(): array
    {
        $today = Carbon::today();
        $startOfMonth = $today->copy()->startOfMonth();
        $endOfMonth = $today->copy()->endOfMonth();

        // 1. KPI Metrik Finansial
        $monthlyOpex = (float) PurchasingOrder::where('order_type', 'OPEX')
            ->whereIn('status', ['APPROVED_BY_PIC', 'PURCHASE_COMPLETED', 'PAID_COMPLETED'])
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('grand_total');

        $monthlyCapex = (float) PurchasingOrder::where('order_type', 'CAPEX')
            ->whereIn('status', ['APPROVED_BY_PIC', 'PURCHASE_COMPLETED', 'PAID_COMPLETED'])
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('grand_total');

        $unpaidTopAmount = (float) PurchasingPayment::where('status', '!=', 'PAID')
            ->sum('amount');

        $paidThisMonth = (float) PurchasingPayment::where('status', 'PAID')
            ->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $pendingPicCount = PurchasingOrder::where('status', 'PENDING_PIC_CHECK')->count();
        $pendingFinanceCount = PurchasingOrder::where('status', 'APPROVED_BY_PIC')->count();
        $dueTodayCount = PurchasingPayment::where('status', 'DUE_TODAY')->count();
        $overdueCount = PurchasingPayment::where('status', 'OVERDUE')->count();

        // 2. 5-Tier Color Coding Alert Matrix
        // Red Alerts: Kritis (Hari H, Overdue, Stagnan > 3 hari, Stok Bahan Kritis)
        $redAlerts = [];
        $dueTodayPayments = PurchasingPayment::with(['order.vendor.primaryContact'])
            ->where('status', 'DUE_TODAY')
            ->limit(10)
            ->get();
        foreach ($dueTodayPayments as $p) {
            $redAlerts[] = [
                'type' => 'due_today',
                'title' => "Tagihan {$p->term_name} JATUH TEMPO HARI INI",
                'subtitle' => "PO {$p->order?->po_number} • {$p->order?->vendor?->name}",
                'amount' => (float) $p->amount,
                'target_url' => route('purchasing.orders.show', $p->order?->po_number ?? ''),
                'created_at' => $p->due_date?->format('Y-m-d'),
            ];
        }

        $overduePayments = PurchasingPayment::with(['order.vendor.primaryContact'])
            ->where('status', 'OVERDUE')
            ->limit(10)
            ->get();
        foreach ($overduePayments as $p) {
            $daysLate = (int) $today->diffInDays($p->due_date);
            $redAlerts[] = [
                'type' => 'overdue',
                'title' => "Tagihan Lewat Tempo ({$daysLate} Hari Terlambat)",
                'subtitle' => "PO {$p->order?->po_number} • {$p->term_name} • {$p->order?->vendor?->name}",
                'amount' => (float) $p->amount,
                'target_url' => route('purchasing.orders.show', $p->order?->po_number ?? ''),
                'created_at' => $p->due_date?->format('Y-m-d'),
            ];
        }

        // Cek stok material kritis (jika ada data material stock)
        try {
            $criticalStocks = PurchasingMaterialStock::whereRaw('current_stock <= minimum_stock_alert')
                ->limit(5)
                ->get();
            foreach ($criticalStocks as $cs) {
                $redAlerts[] = [
                    'type' => 'critical_stock',
                    'title' => "Stok Kritis: {$cs->material_name}",
                    'subtitle' => "Sisa {$cs->current_stock} {$cs->unit} (Batas Min: {$cs->minimum_stock_alert})",
                    'amount' => null,
                    'target_url' => route('purchasing.orders.create'),
                    'created_at' => $cs->updated_at?->format('Y-m-d'),
                ];
            }
        } catch (\Throwable) {
            // Abaikan jika tabel belum ada data
        }

        // Amber Alerts: H-3 s.d H-1 jatuh tempo & Pending Finance
        $amberAlerts = [];
        $h1to3 = $today->copy()->addDays(3);
        $upcomingTerms = PurchasingPayment::with(['order.vendor.primaryContact'])
            ->where('status', 'PENDING')
            ->whereBetween('due_date', [$today->copy()->addDay()->toDateString(), $h1to3->toDateString()])
            ->limit(10)
            ->get();
        foreach ($upcomingTerms as $p) {
            $daysLeft = (int) $today->diffInDays($p->due_date);
            $amberAlerts[] = [
                'type' => 'due_soon',
                'title' => "Mendekati Jatuh Tempo (H-{$daysLeft})",
                'subtitle' => "PO {$p->order?->po_number} • {$p->term_name} • {$p->order?->vendor?->name}",
                'amount' => (float) $p->amount,
                'target_url' => route('purchasing.orders.show', $p->order?->po_number ?? ''),
                'created_at' => $p->due_date?->format('Y-m-d'),
            ];
        }

        $pendingFinanceOrders = PurchasingOrder::where('status', 'APPROVED_BY_PIC')
            ->limit(5)
            ->get();
        foreach ($pendingFinanceOrders as $o) {
            $amberAlerts[] = [
                'type' => 'pending_finance',
                'title' => "Menunggu Otorisasi Anggaran (Finance)",
                'subtitle' => "PO {$o->po_number} • {$o->item_name} ({$o->department})",
                'amount' => (float) $o->grand_total,
                'target_url' => route('purchasing.orders.show', $o->po_number),
                'created_at' => $o->transaction_date?->format('Y-m-d'),
            ];
        }

        // Blue Alerts: Operasional Aktif (Order Baru Hari Ini)
        $blueAlerts = [];
        $todayOrders = PurchasingOrder::whereDate('transaction_date', $today->toDateString())
            ->limit(5)
            ->get();
        foreach ($todayOrders as $o) {
            $blueAlerts[] = [
                'type' => 'active_order',
                'title' => "Order Baru Masuk Hari Ini",
                'subtitle' => "PO {$o->po_number} • {$o->item_name} oleh {$o->requester_name}",
                'amount' => (float) $o->grand_total,
                'target_url' => route('purchasing.orders.show', $o->po_number),
                'created_at' => $o->transaction_date?->format('Y-m-d'),
            ];
        }

        // Green Alerts: Lunas & Selesai Bulan Ini
        $greenAlerts = [];
        $completedThisMonth = PurchasingOrder::whereIn('status', ['PURCHASE_COMPLETED', 'PAID_COMPLETED'])
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->limit(5)
            ->get();
        foreach ($completedThisMonth as $o) {
            $greenAlerts[] = [
                'type' => 'completed',
                'title' => "Pengadaan Selesai & Terverifikasi",
                'subtitle' => "PO {$o->po_number} • {$o->item_name}",
                'amount' => (float) $o->grand_total,
                'target_url' => route('purchasing.orders.show', $o->po_number),
                'created_at' => $o->transaction_date?->format('Y-m-d'),
            ];
        }

        // Grey Alerts: PO Ditolak / Arsip
        $greyAlerts = [];
        $rejectedOrders = PurchasingOrder::where('status', 'REJECTED')
            ->limit(5)
            ->get();
        foreach ($rejectedOrders as $o) {
            $greyAlerts[] = [
                'type' => 'rejected',
                'title' => "Order Ditolak / Dibatalkan",
                'subtitle' => "PO {$o->po_number} • {$o->item_name}",
                'amount' => (float) $o->grand_total,
                'target_url' => route('purchasing.orders.show', $o->po_number),
                'created_at' => $o->transaction_date?->format('Y-m-d'),
            ];
        }

        // 3. Upcoming TOP Payments List (10 data terdekat)
        $upcomingPayments = PurchasingPayment::with(['order.vendor.primaryContact'])
            ->where('status', '!=', 'PAID')
            ->orderBy('due_date')
            ->limit(10)
            ->get()
            ->map(function ($p) {
                return [
                    'uuid' => $p->uuid,
                    'po_number' => $p->order?->po_number,
                    'vendor_name' => $p->order?->vendor?->name ?? 'Vendor Langsung',
                    'vendor_phone' => $p->order?->vendor?->primaryContact?->phone ?? $p->order?->vendor?->phone,
                    'term_name' => $p->term_name,
                    'term_step' => $p->term_step,
                    'amount' => (float) $p->amount,
                    'due_date' => $p->due_date?->format('Y-m-d'),
                    'status' => $p->status,
                ];
            });

        return [
            'metrics' => [
                'monthly_opex' => $monthlyOpex,
                'monthly_capex' => $monthlyCapex,
                'unpaid_top_amount' => $unpaidTopAmount,
                'paid_this_month' => $paidThisMonth,
                'pending_pic_count' => $pendingPicCount,
                'pending_finance_count' => $pendingFinanceCount,
                'due_today_count' => $dueTodayCount,
                'overdue_count' => $overdueCount,
            ],
            'alerts' => [
                'red' => $redAlerts,
                'amber' => $amberAlerts,
                'blue' => $blueAlerts,
                'green' => $greenAlerts,
                'grey' => $greyAlerts,
            ],
            'upcoming_payments' => $upcomingPayments,
        ];
    }
}
