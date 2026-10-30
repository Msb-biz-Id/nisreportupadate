<?php

namespace App\Services\Ai\Mcp\Tools;

use App\Services\Ai\Mcp\McpSnapshotService;

class FinanceTools
{
    /**
     * Mengambil daftar PO dengan sisa tagihan / tunggakan, dilengkapi link reminder WhatsApp.
     */
    public function getUnpaidReminders(array $params = []): array
    {
        $brandId = !empty($params['brand_id']) ? (string)$params['brand_id'] : null;
        $limit = !empty($params['limit']) ? (int)$params['limit'] : 20;

        $data = McpSnapshotService::getUnpaidReminders($brandId, $limit);

        return [
            'status' => 'success',
            'summary' => [
                'total_po_belum_lunas' => $data['total_po_belum_lunas'] ?? 0,
                'total_pcs_belum_lunas' => $data['total_pcs_belum_lunas'] ?? 0,
                'total_tunggakan_rp' => $data['total_tunggakan_rp'] ?? 0,
                'formatted_total_tunggakan' => 'Rp ' . number_format($data['total_tunggakan_rp'] ?? 0, 0, ',', '.'),
            ],
            'orders' => $data['items'] ?? [],
            'ai_presentation_tip' => 'Sajikan tabel rapi dengan kolom: No PO, Pelanggan, Pcs, Total Tagihan, Sisa Tunggakan, dan Tautan WhatsApp. Berikan kesimpulan dan rekomendasikan penagihan prioritas.'
        ];
    }

    /**
     * Mengambil rincian billing / pembayaran spesifik untuk satu order.
     */
    public function getOrderBilling(array $params = []): array
    {
        $po = $params['spk_number'] ?? $params['no_po'] ?? $params['order_id'] ?? null;
        if (!$po) {
            return [
                'status' => 'error',
                'message' => 'Parameter spk_number atau no_po diperlukan.'
            ];
        }

        $detail = McpSnapshotService::getOrderDetail((string)$po);
        if (isset($detail['error'])) {
            return [
                'status' => 'not_found',
                'message' => $detail['error']
            ];
        }

        return [
            'status' => 'success',
            'no_po' => $detail['no_po'],
            'nama_po' => $detail['nama_po'],
            'pelanggan' => $detail['pelanggan'],
            'keuangan' => $detail['keuangan'],
            'ai_presentation_tip' => 'Jelaskan riwayat pembayaran (DP vs Pelunasan) secara kronologis, dan status pelunasan saat ini.'
        ];
    }

    /**
     * Ringkasan arus kas & laba bersih.
     */
    public function getCashflowSummary(array $params = []): array
    {
        $periode = $params['periode'] ?? 'bulanan';
        $brandId = !empty($params['brand_id']) ? (string)$params['brand_id'] : null;

        $data = McpSnapshotService::getCashflowSummary($periode, $brandId);

        return [
            'status' => 'success',
            'cashflow' => $data,
            'ai_presentation_tip' => 'Sajikan dalam bentuk bullet points perbandingan penerimaan kas vs piutang/pengeluaran.'
        ];
    }

    /**
     * Ringkasan KPI Keuangan menyeluruh (Omzet bulanan, Piutang outstanding, Penerimaan kas, Beban biaya).
     */
    public function getFinanceKpi(array $params = []): array
    {
        $brandId = !empty($params['brand_id']) ? (string)$params['brand_id'] : null;
        $data = McpSnapshotService::getFinanceKpi($brandId);

        return [
            'status' => 'success',
            'kpi' => $data,
            'ai_presentation_tip' => 'Paparkan KPI keuangan utama dalam bentuk summary eksekutif yang memuat: Omset, Piutang Tertahan, Pembayaran Masuk, dan Rincian per Brand.'
        ];
    }

    /**
     * Ringkasan rekonsiliasi dan saldo kas rekening bank operasional pabrik.
     */
    public function getBankReconciliation(array $params = []): array
    {
        $brandId = !empty($params['brand_id']) ? (string)$params['brand_id'] : null;
        $data = McpSnapshotService::getBankReconciliation($brandId);

        return [
            'status' => 'success',
            'data' => $data,
            'ai_presentation_tip' => 'Tampilkan saldo dan mutasi kas bank penerimaan secara transparan.'
        ];
    }

    /**
     * Mengambil daftar invoice / faktur penagihan pelanggan dengan filter status (unpaid, partial, paid, overdue).
     */
    public function getInvoices(array $params = []): array
    {
        $brandId = !empty($params['brand_id']) ? (string)$params['brand_id'] : null;
        $status = !empty($params['status']) ? (string)$params['status'] : null;
        $limit = !empty($params['limit']) ? (int)$params['limit'] : 20;

        $data = McpSnapshotService::getInvoices($brandId, $status, $limit);

        return [
            'status' => 'success',
            'invoices_data' => $data,
            'ai_presentation_tip' => 'Tampilkan tabel tagihan faktur dengan nomor invoice, PO, nama pelanggan, sisa tagihan, dan tanggal jatuh tempo.'
        ];
    }

    /**
     * Mengambil ringkasan pengeluaran operasional pabrik per kategori pengeluaran dan total biaya.
     */
    public function getExpenseSummary(array $params = []): array
    {
        $brandId = !empty($params['brand_id']) ? (string)$params['brand_id'] : null;
        $month = !empty($params['month']) ? (string)$params['month'] : null;
        $year = !empty($params['year']) ? (string)$params['year'] : null;

        $data = McpSnapshotService::getExpenseSummary($brandId, $month, $year);

        return [
            'status' => 'success',
            'expenses' => $data,
            'ai_presentation_tip' => 'Sajikan rincian pengeluaran per kategori, total beban biaya operasional, dan persentase pengeluaran terbesar.'
        ];
    }

    /**
     * Mengambil statistik komplain mutu, insiden rijek produksi QC, dan pengembalian dana (refund).
     */
    public function getComplaintAndRefundKpi(array $params = []): array
    {
        $brandId = !empty($params['brand_id']) ? (string)$params['brand_id'] : null;
        $data = McpSnapshotService::getComplaintAndRefundKpi($brandId);

        return [
            'status' => 'success',
            'quality_kpi' => $data,
            'ai_presentation_tip' => 'Evaluasi total insiden rijek pakaian per stasiun pengerjaan dan total nominal refund yang diajukan pelanggan.'
        ];
    }

    /**
     * Mengambil jadwal umur piutang (Aging Schedule) untuk analisis CFO: 0-30 hari, 31-60 hari, 61-90 hari, dan piutang macet (>90 hari).
     */
    public function getReceivableAgingSchedule(array $params = []): array
    {
        $brandId = !empty($params['brand_id']) ? (string)$params['brand_id'] : null;
        $data = McpSnapshotService::getReceivableAgingSchedule($brandId);

        return [
            'status' => 'success',
            'aging_report' => $data,
            'ai_presentation_tip' => 'Sebagai CFO / Direktur Keuangan, berikan analisis risiko likuiditas piutang dan rekomendasikan penagihan prioritas untuk kategori keterlambatan di atas 30 hari.'
        ];
    }

    /**
     * Mengaudit transaksi abnormal, bypass DP tanpa izin, dan order terkirim yang belum lunas (Audit Internal & Fraud Detection).
     */
    public function auditFinancialAndPoCompliance(array $params = []): array
    {
        $brandId = !empty($params['brand_id']) ? (string)$params['brand_id'] : null;
        $data = McpSnapshotService::auditFinancialAndPoCompliance($brandId);

        return [
            'status' => 'success',
            'audit_findings' => $data,
            'ai_presentation_tip' => 'Sebagai Auditor Internal, beri penilaian status kepatuhan (Compliance Rating), temuan anomali bypass DP, dan tindakan perbaikan operasional.'
        ];
    }
}



