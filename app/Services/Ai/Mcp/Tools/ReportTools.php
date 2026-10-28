<?php

namespace App\Services\Ai\Mcp\Tools;

use App\Services\Ai\Mcp\McpSnapshotService;

class ReportTools
{
    /**
     * Mengambil ringkasan eksekutif KPI pabrik untuk Owner/Direksi/Admin.
     */
    public function getExecutiveReport(array $params = []): array
    {
        $role = $params['role'] ?? 'superadmin';
        $periode = $params['periode'] ?? 'harian';
        $brandId = !empty($params['brand_id']) ? (string)$params['brand_id'] : null;

        $reportText = McpSnapshotService::getExecutiveReport($role, $periode, $brandId);

        return [
            'status' => 'success',
            'role' => $role,
            'periode' => $periode,
            'report_content' => $reportText,
            'ai_presentation_tip' => 'Format laporan eksekutif ini dengan rapi menggunakan bolding penting, KPI highlight, dan action items yang perlu ditindaklanjuti.'
        ];
    }

    /**
     * Mengambil performa dan volume produksi per brand.
     */
    public function getBrandOverview(array $params = []): array
    {
        $overview = McpSnapshotService::getBrandOverview();

        return [
            'status' => 'success',
            'brands' => $overview,
            'ai_presentation_tip' => 'Tampilkan data order aktif per masing-masing brand.'
        ];
    }
}
