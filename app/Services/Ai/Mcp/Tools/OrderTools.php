<?php

namespace App\Services\Ai\Mcp\Tools;

use App\Services\Ai\Mcp\McpSnapshotService;

class OrderTools
{
    /**
     * Mengambil daftar pesanan aktif yang sedang diproses.
     */
    public function getActiveOrders(array $params = []): array
    {
        $limit = isset($params['limit']) ? (int) $params['limit'] : 25;
        $brandId = !empty($params['brand_id']) ? (string)$params['brand_id'] : null;

        $result = McpSnapshotService::getActiveOrders($brandId, $limit);

        return [
            'status' => 'success',
            'total_active_orders' => $result['total_active_orders'] ?? 0,
            'displayed_orders' => $result['displayed_orders'] ?? 0,
            'orders' => $result['orders'] ?? [],
            'ai_presentation_tip' => 'Kelompokkan berdasarkan status tahapan (published, on_progress, siap_dikirim) agar pengguna mudah memahami progress keseluruhan pabrik.'
        ];
    }

    /**
     * Mengambil detail lengkap satu pesanan berdasarkan spk_number / no_po.
     */
    public function getOrderDetail(array $params = []): array
    {
        $noPo = $params['spk_number'] ?? $params['no_po'] ?? $params['order_id'] ?? null;

        if (!$noPo) {
            return [
                'status' => 'error',
                'message' => 'Parameter spk_number atau no_po wajib diisi.'
            ];
        }

        $detail = McpSnapshotService::getOrderDetail((string)$noPo);
        if (isset($detail['error'])) {
            return [
                'status' => 'not_found',
                'message' => $detail['error']
            ];
        }

        return [
            'status' => 'success',
            'order' => $detail,
            'ai_presentation_tip' => 'Sajikan ringkasan spesifikasi artikel, kuantiti total per varian ukuran, status timeline produksi fisik, dan status pembayaran.'
        ];
    }

    /**
     * Melacak status pengiriman dan resi ekspedisi.
     */
    public function trackShipping(array $params = []): array
    {
        $noPo = $params['spk_number'] ?? $params['no_po'] ?? $params['order_id'] ?? null;

        if (!$noPo) {
            return [
                'status' => 'error',
                'message' => 'Parameter spk_number atau no_po diperlukan untuk melacak pengiriman.'
            ];
        }

        $detail = McpSnapshotService::getOrderDetail((string)$noPo);
        if (isset($detail['error'])) {
            return [
                'status' => 'not_found',
                'message' => $detail['error']
            ];
        }

        return [
            'status' => 'success',
            'no_po' => $detail['no_po'],
            'pelanggan' => $detail['pelanggan'],
            'pengiriman' => $detail['pengiriman'],
            'ai_presentation_tip' => 'Informasikan kurir/ekspedisi, nomor resi, tanggal pengiriman, dan alamat tujuan.'
        ];
    }
}
