<?php

namespace App\Services\Ai\Mcp\Tools;

use App\Services\Ai\Mcp\McpSnapshotService;

class CustomerTools
{
    /**
     * Cari pelanggan berdasarkan nama, nomor telepon, atau instansi.
     */
    public function searchCustomer(array $params = []): array
    {
        $keyword = $params['keyword'] ?? $params['query'] ?? null;
        if (!$keyword) {
            return [
                'status' => 'error',
                'message' => 'Keyword pencarian pelanggan diperlukan.'
            ];
        }

        $results = McpSnapshotService::searchCustomer((string)$keyword);

        return [
            'status' => 'success',
            'keyword' => $keyword,
            'total_found' => $results['total_found'] ?? 0,
            'customers' => $results['customers'] ?? [],
            'ai_presentation_tip' => 'Tampilkan data kontak (nama, nomor telepon, instansi) dan riwayat total order yang pernah dibuat.'
        ];
    }

    /**
     * Histori transaksi dan profil pelanggan.
     */
    public function getCustomerHistory(array $params = []): array
    {
        $customerId = $params['customer_id'] ?? $params['keyword'] ?? null;
        if (!$customerId) {
            return [
                'status' => 'error',
                'message' => 'Parameter customer_id diperlukan.'
            ];
        }

        $history = McpSnapshotService::getCustomerHistory((string)$customerId);
        if (isset($history['error'])) {
            return [
                'status' => 'not_found',
                'message' => $history['error']
            ];
        }

        return [
            'status' => 'success',
            'data' => $history,
            'ai_presentation_tip' => 'Analisis loyalitas pelanggan, total nilai transaksi (omzet), total pcs, dan riwayat pesanan.'
        ];
    }
}
