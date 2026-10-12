<?php

namespace App\Services\Purchasing;

use App\Models\Purchasing\PurchasingAsset;
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
        // Sinkronisasi status termin pembayaran jatuh tempo hari ini & overdue
        app(PurchasingPaymentService::class)->checkAndUpdateDueStatuses();

        $today = Carbon::today();
        $startOfMonth = $today->copy()->startOfMonth();
        $endOfMonth = $today->copy()->endOfMonth();

        // 1. KPI Metrik Finansial Nyata (Hanya yang benar-benar selesai / lunas)
        $monthlyOpexSingle = (float) PurchasingOrder::where('order_type', 'OPEX')
            ->whereDoesntHave('payments')
            ->whereIn('status', ['PURCHASE_COMPLETED', 'PAID_COMPLETED'])
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('grand_total');
        $monthlyOpexTerms = (float) PurchasingPayment::whereHas('order', fn ($q) => $q->where('order_type', 'OPEX'))
            ->where('status', 'PAID')
            ->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');
        $monthlyOpex = $monthlyOpexSingle + $monthlyOpexTerms;

        $monthlyCapexSingle = (float) PurchasingOrder::where('order_type', 'CAPEX')
            ->whereDoesntHave('payments')
            ->whereIn('status', ['PURCHASE_COMPLETED', 'PAID_COMPLETED'])
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('grand_total');
        $monthlyCapexTerms = (float) PurchasingPayment::whereHas('order', fn ($q) => $q->where('order_type', 'CAPEX'))
            ->where('status', 'PAID')
            ->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');
        $monthlyCapex = $monthlyCapexSingle + $monthlyCapexTerms;

        $unpaidTopAmount = (float) PurchasingPayment::where('status', '!=', 'PAID')
            ->sum('amount');

        $paidThisMonth = (float) PurchasingPayment::where('status', 'PAID')
            ->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $pendingPicCount = PurchasingOrder::where('status', 'PENDING_PIC_CHECK')->count();
        $pendingFinanceCount = PurchasingOrder::where('status', 'APPROVED_BY_PIC')->count();
        $dueTodayCount = PurchasingPayment::where('status', 'DUE_TODAY')->count();
        $overdueCount = PurchasingPayment::where('status', 'OVERDUE')->count();

        // 2. KELOMPOK 1: URGENT & ACTION NEEDED (Butuh Tindakan Immediate)
        // A. Pending Pembayaran & Tagihan (TOP Jatuh Tempo Hari Ini)
        $dueTodayList = PurchasingPayment::with(['order.vendor.primaryContact'])
            ->where('status', 'DUE_TODAY')
            ->orderBy('due_date')
            ->limit(10)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'uuid' => $p->uuid,
                    'po_number' => $p->order?->po_number,
                    'item_name' => $p->order?->item_name ?? $p->term_name,
                    'term_name' => $p->term_name,
                    'amount' => (float) $p->amount,
                    'due_date' => $p->due_date?->format('Y-m-d'),
                    'vendor_name' => $p->order?->vendor?->name ?? 'Vendor Langsung',
                    'vendor_phone' => $p->order?->vendor?->primaryContact?->phone ?? $p->order?->vendor?->phone,
                    'action_label' => 'Lihat Detail',
                    'target_url' => route('purchasing.orders.show', $p->order?->po_number ?? ''),
                ];
            });

        // B. Tagihan Terlambat (Overdue Payments)
        $overdueList = PurchasingPayment::with(['order.vendor.primaryContact'])
            ->where('status', 'OVERDUE')
            ->orderBy('due_date')
            ->limit(10)
            ->get()
            ->map(function ($p) use ($today) {
                $daysLate = (int) $today->diffInDays($p->due_date);
                return [
                    'id' => $p->id,
                    'uuid' => $p->uuid,
                    'po_number' => $p->order?->po_number,
                    'invoice_number' => $p->invoice_number ?? $p->order?->invoice_number ?? $p->order?->po_number,
                    'item_name' => $p->order?->item_name ?? $p->term_name,
                    'term_name' => $p->term_name,
                    'amount' => (float) $p->amount,
                    'days_late' => $daysLate,
                    'due_date' => $p->due_date?->format('Y-m-d'),
                    'vendor_name' => $p->order?->vendor?->name ?? 'Vendor Langsung',
                    'vendor_phone' => $p->order?->vendor?->primaryContact?->phone ?? $p->order?->vendor?->phone,
                    'badge' => 'Perlu Segera Dilunasi',
                    'action_label' => 'Lihat Detail',
                    'target_url' => route('purchasing.orders.show', $p->order?->po_number ?? ''),
                ];
            });

        // C. Pending Approvals (Menunggu Persetujuan)
        $pendingApprovals = PurchasingOrder::whereIn('status', ['PENDING_PIC_CHECK', 'APPROVED_BY_PIC'])
            ->with(['vendor.primaryContact', 'requester'])
            ->orderByDesc('transaction_date')
            ->limit(10)
            ->get()
            ->map(function ($o) {
                $stageLabel = $o->status === 'PENDING_PIC_CHECK' ? 'Verifikasi PIC' : 'Otorisasi Keuangan';
                return [
                    'id' => $o->id,
                    'po_number' => $o->po_number,
                    'item_name' => $o->item_name,
                    'department' => $o->department,
                    'division' => $o->division,
                    'requester_name' => $o->requester_name ?? 'Pemohon',
                    'grand_total' => (float) $o->grand_total,
                    'status' => $o->status,
                    'stage_label' => $stageLabel,
                    'transaction_date' => $o->transaction_date?->format('Y-m-d'),
                    'action_label' => 'Review & Setujui',
                    'target_url' => route('purchasing.orders.show', $o->po_number),
                ];
            });

        // 3. KELOMPOK 2: UPCOMING SCHEDULES & MONITORING (Jadwal & Peringatan Mendatang)
        // A. Masa Garansi Aset Berakhir
        $warrantyExpiring = PurchasingAsset::where('status', 'ACTIVE')
            ->whereNotNull('warranty_expires_at')
            ->whereBetween('warranty_expires_at', [$today->toDateString(), $today->copy()->addDays(90)->toDateString()])
            ->with('location')
            ->orderBy('warranty_expires_at')
            ->limit(10)
            ->get()
            ->map(function ($ast) use ($today) {
                $daysLeft = (int) $today->diffInDays($ast->warranty_expires_at);
                return [
                    'id' => $ast->id,
                    'asset_code' => $ast->asset_code,
                    'asset_name' => $ast->asset_name,
                    'department' => $ast->department,
                    'location_name' => $ast->location?->name ?? 'Pusat',
                    'days_left' => $daysLeft,
                    'expiry_date' => $ast->warranty_expires_at?->format('d M Y'),
                    'target_url' => route('purchasing.assets.show', $ast->asset_code),
                ];
            });

        // B. Perpanjangan Langganan / Kontrak Berakhir
        $subscriptions = PurchasingOrder::where(function ($q) {
            $q->where('item_name', 'like', '%langganan%')
                ->orWhere('item_name', 'like', '%software%')
                ->orWhere('item_name', 'like', '%canva%')
                ->orWhere('item_name', 'like', '%workspace%')
                ->orWhere('item_name', 'like', '%sewa%')
                ->orWhere('item_name', 'like', '%kontrak%')
                ->orWhere('item_name', 'like', '%lisensi%')
                ->orWhere('specification', 'like', '%langganan%')
                ->orWhere('specification', 'like', '%kontrak%')
                ->orWhere('specification', 'like', '%lisensi%')
                ->orWhereHas('vendor', fn ($vq) => $vq->where('category', 'Langganan Digital / Software'));
        })
            ->whereNotIn('status', ['REJECTED', 'DRAFT'])
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($o) use ($today) {
                $txnDate = $o->transaction_date ? \Carbon\Carbon::parse($o->transaction_date) : $today;
                if (stripos($o->item_name, '1 Tahun') !== false || stripos($o->specification ?? '', '1 Tahun') !== false || stripos($o->notes ?? '', '1 Tahun') !== false) {
                    $expiry = $txnDate->copy()->addYear();
                } else {
                    $expiry = $txnDate->copy()->addMonth();
                    while ($expiry->lt($today)) {
                        $expiry->addMonth();
                    }
                }
                $daysLeft = (int) $today->diffInDays($expiry, false);

                return [
                    'id' => $o->id,
                    'po_number' => $o->po_number,
                    'item_name' => $o->item_name,
                    'vendor_name' => $o->vendor?->name ?? ($o->vendor_name_manual ?? 'Vendor Layanan'),
                    'department' => $o->department,
                    'grand_total' => (float) $o->grand_total,
                    'expiry_date' => $expiry->format('d M Y'),
                    'days_left' => max(0, $daysLeft),
                    'decision_note' => 'Perlu keputusan: Perpanjang / Putus',
                    'target_url' => route('purchasing.orders.show', $o->po_number),
                ];
            });

        // C. Stok Bahan Baku Kritis (Minimum Stock Alert)
        $criticalStocks = PurchasingMaterialStock::whereRaw('current_stock <= minimum_threshold')
            ->where('material_type', '!=', 'KAIN_WARNA')
            ->orderByRaw('(current_stock - minimum_threshold) ASC')
            ->limit(10)
            ->get()
            ->map(function ($cs) {
                return [
                    'id' => $cs->id,
                    'name' => $cs->name,
                    'material_type' => $cs->material_type,
                    'current_stock' => (float) $cs->current_stock,
                    'minimum_threshold' => (float) $cs->minimum_threshold,
                    'unit' => $cs->unit,
                    'shortage' => max(0, (float) ($cs->minimum_threshold - $cs->current_stock)),
                    'action_label' => 'Segera Beli',
                    'target_url' => route('purchasing.orders.create') . '?item_name=' . urlencode($cs->name),
                ];
            });

        // 4. Upcoming TOP Payments List (10 data terdekat)
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
            // 2 Kelompok Utama Dashboard Sesuai Alur Bisnis
            'urgent_actions' => [
                'due_today' => $dueTodayList,
                'overdue' => $overdueList,
                'pending_approvals' => $pendingApprovals,
            ],
            'upcoming_monitoring' => [
                'warranty_expiring' => $warrantyExpiring,
                'subscriptions' => $subscriptions,
                'critical_stocks' => $criticalStocks,
            ],
            'alerts' => [
                'red' => $dueTodayList->concat($overdueList)->concat($criticalStocks),
                'amber' => $warrantyExpiring->concat($subscriptions),
                'blue' => [],
                'green' => [],
                'grey' => [],
            ],
            'upcoming_payments' => $upcomingPayments,
        ];
    }
}
