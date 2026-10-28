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
     * Daftar order yang sudah selesai/dikirim namun pembayarannya masih belum lunas.
     */
    public function getOverdueInvoices(array $params = []): array
    {
        $brandId = !empty($params['brand_id']) ? (string)$params['brand_id'] : null;
        $limit = !empty($params['limit']) ? (int)$params['limit'] : 20;

        $data = McpSnapshotService::getUnpaidReminders($brandId, $limit);

        return [
            'status' => 'success',
            'total_overdue_count' => $data['total_po_belum_lunas'] ?? 0,
            'orders' => $data['items'] ?? [],
            'ai_presentation_tip' => 'Peringatkan user bahwa order pada daftar ini barangnya telah selesai / terkirim tetapi belum lunas. Sarankan penagihan prioritas.'
        ];
    }
}
