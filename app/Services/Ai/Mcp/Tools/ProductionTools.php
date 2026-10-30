<?php

namespace App\Services\Ai\Mcp\Tools;

use App\Services\Ai\Mcp\McpSnapshotService;

class ProductionTools
{
    /**
     * Mengambil antrian produksi dan rincian beban kerja per workstation (Cutting, Sablon/Printing, Sewing, Finishing, QC, Packing, dll).
     */
    public function getProductionQueue(array $params = []): array
    {
        $brandId = !empty($params['brand_id']) ? (string)$params['brand_id'] : null;
        $station = !empty($params['station']) ? (string)$params['station'] : (!empty($params['workstation']) ? (string)$params['workstation'] : null);

        $queue = McpSnapshotService::getProductionQueue($brandId, $station);

        return [
            'status' => 'success',
            'filter_applied' => $station ? "Workstation: {$station}" : 'Seluruh Workstation',
            'queue' => $queue,
            'ai_presentation_tip' => 'WAJIB sajikan tabel breakdown antrean per workstation (Nama Stasiun, Kategori Alur, Jumlah PO, Total Pcs, dan % Beban Pcs). Jika stasiun tertentu difilter (misal Cutting, Jahit, Sablon), laporkan khusus beban stasiun tersebut dan daftar PO-nya. Tandai potensi bottleneck bila suatu stasiun menampung ≥ 25% total pcs antrean.'
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

