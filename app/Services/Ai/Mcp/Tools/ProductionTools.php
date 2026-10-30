<?php

namespace App\Services\Ai\Mcp\Tools;

use App\Services\Ai\Mcp\McpSnapshotService;

class ProductionTools
{
    /**
     * Mengambil antrian produksi dan beban kerja per status.
     */
    public function getProductionQueue(array $params = []): array
    {
        $brandId = !empty($params['brand_id']) ? (string)$params['brand_id'] : null;
        $queue = McpSnapshotService::getProductionQueue($brandId);

        return [
            'status' => 'success',
            'queue' => $queue,
            'ai_presentation_tip' => 'Visualisasikan antrian beban kerja produksi untuk menemukan potensi bottleneck pabrik.'
        ];
    }

    /**
     * Memeriksa order yang mendekati batas waktu / deadline (misal dalam 3 atau 7 hari ke depan).
     */
    public function getApproachingDeadlines(array $params = []): array
    {
        $days = isset($params['days_ahead']) ? (int) $params['days_ahead'] : 7;
        $brandId = !empty($params['brand_id']) ? (string)$params['brand_id'] : null;

        $deadlines = McpSnapshotService::getApproachingDeadlines($days, $brandId);

        return [
            'status' => 'success',
            'days_ahead' => $days,
            'critical_count' => $deadlines['total_approaching'] ?? 0,
            'orders' => $deadlines['orders'] ?? [],
            'ai_presentation_tip' => 'Urutkan dari urgensi CRITICAL (H-0/H-1) ke HIGH. Berikan rekomendasi prioritas pengerjaan di lantai produksi.'
        ];
    }

    /**
     * Mengambil daftar order yang telah melampaui tanggal deadline (terlambat).
     */
    public function getDelayedOrders(array $params = []): array
    {
        $brandId = !empty($params['brand_id']) ? (string)$params['brand_id'] : null;
        $delayed = McpSnapshotService::getDelayedOrders($brandId);

        return [
            'status' => 'success',
            'delayed_count' => $delayed['total_delayed'] ?? 0,
            'orders' => $delayed['orders'] ?? [],
            'ai_presentation_tip' => 'Beri peringatan urgensi tinggi untuk pesanan yang terlambat beserta jumlah hari keterlambatan.'
        ];
    }

    /**
     * Mengambil data cacat / reject QC.
     */
    public function getDefectStats(array $params = []): array
    {
        $brandId = !empty($params['brand_id']) ? (string)$params['brand_id'] : null;
        $stats = McpSnapshotService::getDefectStats($brandId);

        return [
            'status' => 'success',
            'defect_summary' => $stats,
            'ai_presentation_tip' => 'Sajikan evaluasi kontrol kualitas hasil produksi dan rijek.'
        ];
    }

    /**
     * Mengambil KPI Produksi menyeluruh (Antrean per workstation, kapasitas produksi, deadline mepet, order terlambat, & PO siap kirim).
     */
    public function getProductionKpi(array $params = []): array
    {
        $brandId = !empty($params['brand_id']) ? (string)$params['brand_id'] : null;
        $data = McpSnapshotService::getProductionKpi($brandId);

        return [
            'status' => 'success',
            'production_kpi' => $data,
            'ai_presentation_tip' => 'Paparkan KPI lantai produksi secara terstruktur: kartu ringkasan, antrean per stasiun kerja (potong, jahit, sablon, bordir, dll), deadline mendekat, dan order siap kirim.'
        ];
    }
}

