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

    /**
     * Mengambil pencapaian target penjualan bulanan (Revenue & Pcs) per Brand dan agregat pabrik.
     */
    public function getBrandTargets(array $params = []): array
    {
        $brandId = !empty($params['brand_id']) ? (string)$params['brand_id'] : null;
        $targets = McpSnapshotService::getBrandTargets($brandId);

        return [
            'status' => 'success',
            'target_achievement' => $targets,
            'ai_presentation_tip' => 'Bandingkan target vs aktual omzet & pcs bulan ini, tampilkan persentase ketercapaian, dan sisa kekurangan target per brand.'
        ];
    }

    /**
     * Mengambil performa akuisisi order berdasarkan channel pemasaran / sumber leads (TikTok, Instagram, Referral, Website).
     */
    public function getMarketingLeadsKpi(array $params = []): array
    {
        $brandId = !empty($params['brand_id']) ? (string)$params['brand_id'] : null;
        $data = McpSnapshotService::getMarketingLeadsKpi($brandId);

        return [
            'status' => 'success',
            'leads_kpi' => $data,
            'ai_presentation_tip' => 'Identifikasi channel pemasaran yang paling efektif menghasilkan PO dan omzet penjualan terbesar.'
        ];
    }

    /**
     * Mengambil statistik jenis produk dan bahan kain (fabrics) terpopuler yang paling banyak dipesan.
     */
    public function getTopProductsAndFabrics(array $params = []): array
    {
        $brandId = !empty($params['brand_id']) ? (string)$params['brand_id'] : null;
        $limit = !empty($params['limit']) ? (int)$params['limit'] : 10;
        $data = McpSnapshotService::getTopProductsAndFabrics($brandId, $limit);

        return [
            'status' => 'success',
            'data' => $data,
            'ai_presentation_tip' => 'Tampilkan peringkat bahan kain favorit pelanggan dan kategori produk garmen paling laris.'
        ];
    }

    /**
     * Mengambil statistik pengiriman logistik, kurir ekspedisi paling banyak dipakai, dan rasio free ongkir.
     */
    public function getShippingPerformance(array $params = []): array
    {
        $brandId = !empty($params['brand_id']) ? (string)$params['brand_id'] : null;
        $data = McpSnapshotService::getShippingPerformance($brandId);

        return [
            'status' => 'success',
            'shipping_kpi' => $data,
            'ai_presentation_tip' => 'Evaluasi kurir ekspedisi yang paling diandalkan dan beban subsidi gratis ongkir yang ditanggung pabrik.'
        ];
    }

    /**
     * Mengambil matriks portofolio brand (Klasifikasi Cash Cow, Star, High Risk) untuk analisis Konsultan Bisnis & Manajemen Strategis.
     */
    public function getBrandPortfolioMatrix(array $params = []): array
    {
        $data = McpSnapshotService::getBrandPortfolioMatrix();

        return [
            'status' => 'success',
            'portfolio_matrix' => $data,
            'ai_presentation_tip' => 'Sebagai Konsultan Bisnis Strategis (McKinsey/BCG style), berikan rekomendasi alokasi kapasitas pabrik, brand mana yang harus dipush pemasarannya, dan brand mana yang perlu mitigasi risiko piutang.'
        ];
    }

    /**
     * Mengambil Laporan Jenis PO: Rincian jenis PO per brand, perolehan omset, tagihan yang belum lunas, statistik lengkap, dan grafik visualisasi.
     */
    public function getJenisPoReport(array $params = []): array
    {
        $brandId = !empty($params['brand_id']) ? (string)$params['brand_id'] : null;
        $year = !empty($params['year']) ? (int)$params['year'] : null;
        $month = !empty($params['month']) ? (int)$params['month'] : null;
        $jenisPo = !empty($params['jenis_po']) ? (string)$params['jenis_po'] : null;

        $report = McpSnapshotService::getJenisPoReport($brandId, $year, $month, $jenisPo);

        return [
            'status' => 'success',
            'data' => $report,
            'ai_presentation_tip' => 'WAJIB sajikan tabel perbandingan performa per brand (Jumlah PO, Total Pcs, Nilai Omset, Nilai Belum Lunas, Collection Rate). Tampilkan juga breakdown 4 jenis PO (Normal, Reseller, PO Support, Repeat Order) dan sajikan rincian data grafik komparasi. Terapkan analisis 4 skill: CFO (evaluasi piutang belum lunas & likuiditas), Analis Data (komposisi jenis PO & rata-rata order size), Konsultan Bisnis (strategi konversi & loyalitas pelanggan), dan Auditor Internal (pengawasan diskon reseller & otorisasi PO support).'
        ];
    }
}



