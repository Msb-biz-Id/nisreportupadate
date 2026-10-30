<?php

namespace App\Services\Ai\Mcp\Tools;

use App\Services\Ai\Mcp\McpSnapshotService;

class MenuTools
{
    protected McpSnapshotService $snapshotService;

    public function __construct(McpSnapshotService $snapshotService)
    {
        $this->snapshotService = $snapshotService;
    }

    /**
     * Menampilkan menu interaktif terarah untuk memandu AI dan User memilih tool dengan cepat.
     */
    public function getInteractiveMenu(array $params = []): array
    {
        $mode = $params['category'] ?? 'all';

        $menu = [
            'system_info' => [
                'name' => 'Garment Operating System AI Copilot (MCP)',
                'version' => '2.0.0-PROD',
                'description' => 'Asisten pintar operasional pabrik garmen terintegrasi data real-time, keuangan, dan tracking.',
                'status' => 'ONLINE (Shared-Hosting Optimized & Micro-Cached)'
            ],
            'categories' => [
                'finance' => [
                    'label' => '💰 1. Keuangan & Penagihan (Billing & Reminder)',
                    'description' => 'Cek KPI keuangan, omzet, piutang tertahan, reminder WA, faktur invoice, rekonsiliasi kas bank, dan arus kas.',
                    'quick_triggers' => [
                        'KPI Keuangan & Piutang pabrik saat ini',
                        'Sisa tagihan PO yang belum lunas',
                        'Daftar invoice (faktur) pelanggan',
                        'Saldo kas dan mutasi rekening bank',
                        'Buat draft pesan tagihan WhatsApp ke pelanggan',
                        'Ringkasan arus kas bulan ini'
                    ],
                    'available_tools' => [
                        ['tool' => 'get_finance_kpi', 'desc' => 'KPI komprehensif keuangan: omzet, piutang outstanding, pembayaran, rasio per brand'],
                        ['tool' => 'get_unpaid_reminders', 'desc' => 'Daftar semua PO dengan sisa tagihan / tunggakan + link WA penagihan otomatis'],
                        ['tool' => 'get_invoices', 'desc' => 'Daftar faktur invoice penagihan (params: status=unpaid|partial|paid|overdue, brand_id)'],
                        ['tool' => 'get_bank_reconciliation', 'desc' => 'Rekonsiliasi saldo kas rekening bank operasional pabrik'],
                        ['tool' => 'get_expense_summary', 'desc' => 'Rincian beban biaya operasional per kategori (params: brand_id, month, year)'],
                        ['tool' => 'get_complaint_and_refund_kpi', 'desc' => 'Statistik cacat rijek produksi QC dan nominal pengembalian dana (refund)'],
                        ['tool' => 'get_order_billing', 'desc' => 'Rincian invoice, DP, pelunasan & sisa tagihan per PO (params: order_id/spk_number)'],
                        ['tool' => 'get_cashflow_summary', 'desc' => 'Ringkasan penerimaan kas masuk & total piutang tertahan'],
                        ['tool' => 'get_overdue_invoices', 'desc' => 'Daftar order selesai yang belum melunasi pembayaran']
                    ]
                ],
                'orders' => [
                    'label' => '📦 2. Manajemen Order & Tracking (SPK)',
                    'description' => 'Pencarian status order, progres produksi, detail kuantiti, dan pelacakan resi pengiriman.',
                    'quick_triggers' => [
                        'Cari order SPK-xxxx',
                        'Daftar pesanan aktif dan statusnya',
                        'Lacak pengiriman dan ekspedisi',
                        'Statistik kurir & subsidi free ongkir'
                    ],
                    'available_tools' => [
                        ['tool' => 'get_active_orders', 'desc' => 'Daftar pesanan yang sedang berjalan di pabrik'],
                        ['tool' => 'get_order_detail', 'desc' => 'Detail lengkap item, ukuran, deadline, dan progress SPK'],
                        ['tool' => 'track_shipping', 'desc' => 'Lacak kurir, no resi, dan status pengantaran'],
                        ['tool' => 'get_shipping_performance', 'desc' => 'Statistik kurir ekspedisi terbanyak dipakai dan beban subsidi free ongkir']
                    ]
                ],
                'production' => [
                    'label' => '🏭 3. Antrian Produksi & Deadline (Production Floor)',
                    'description' => 'Monitoring KPI produksi, antrean per stasiun kerja (potong, jahit, sablon, bordir, QC, packing), peringatan keterlambatan & siap kirim.',
                    'quick_triggers' => [
                        'KPI Produksi menyeluruh & antrean workstation',
                        'Order yang mendekati deadline (H-3 / H-7)',
                        'Order yang sudah lewat deadline (terlambat)',
                        'Status antrian jahit dan sablon hari ini'
                    ],
                    'available_tools' => [
                        ['tool' => 'get_production_kpi', 'desc' => 'Dashboard KPI produksi lengkap: kartu ringkasan, antrean per stasiun kerja, deadline mepet, order terlambat & siap kirim'],
                        ['tool' => 'get_production_queue', 'desc' => 'Distribusi jumlah order per stasiun kerja'],
                        ['tool' => 'get_approaching_deadlines', 'desc' => 'Peringatan deadline kritis dalam X hari (params: days_ahead=7)'],
                        ['tool' => 'get_delayed_orders', 'desc' => 'Daftar PO yang telah melampaui tanggal deadline'],
                        ['tool' => 'get_defect_stats', 'desc' => 'Catatan kendala / reject QC jika tersedia']
                    ]
                ],
                'customer' => [
                    'label' => '👥 4. Data Pelanggan & Brand (CRM)',
                    'description' => 'Pencarian kontak pelanggan, brand owner, pelanggan order terbanyak (Top Customers), dan loyalitas transaksi.',
                    'quick_triggers' => [
                        'Pelanggan dengan order terbanyak brand Allegiant',
                        'Top pelanggan terbaik berdasarkan omzet / pcs',
                        'Cari nomor WhatsApp customer PT ABC',
                        'Riwayat pemesanan brand XYZ'
                    ],
                    'available_tools' => [
                        ['tool' => 'get_top_customers', 'desc' => 'Daftar peringkat pelanggan order terbanyak per Brand / Pabrik (params: brand, by=orders|pcs|revenue, limit)'],
                        ['tool' => 'search_customer', 'desc' => 'Cari pelanggan berdasarkan nama, perusahaan, no HP, atau nama brand'],
                        ['tool' => 'get_customer_history', 'desc' => 'Histori transaksi dan reputasi pembayaran pelanggan']
                    ]
                ],
                'reports' => [
                    'label' => '📊 5. Laporan Eksekutif, Pemasaran & Query Analitik',
                    'description' => 'Dashboard ringkasan harian/bulanan, sumber leads marketing, produk & bahan terlaris, target brand, dan analitik ad-hoc.',
                    'quick_triggers' => [
                        'Laporan ringkasan performa pabrik hari ini',
                        'Sumber leads marketing (TikTok, IG, Referral)',
                        'Kain dan produk yang paling laris',
                        'Cek pencapaian target penjualan brand bulan ini',
                        'Analisis kustom ad-hoc data pabrik'
                    ],
                    'available_tools' => [
                        ['tool' => 'get_marketing_leads_kpi', 'desc' => 'Statistik akuisisi order berdasarkan channel sumber leads (TikTok, IG, Referral, Web)'],
                        ['tool' => 'get_top_products_and_fabrics', 'desc' => 'Statistik bahan kain (fabrics) terpopuler dan kategori produk terlaris'],
                        ['tool' => 'get_brand_targets', 'desc' => 'Target penjualan bulanan (Omzet Rp & Pcs) vs aktual, persentase capai & sisa kejar target per brand'],
                        ['tool' => 'get_executive_report', 'desc' => 'KPI harian: total PO, omset, target produksi, piutang'],
                        ['tool' => 'get_brand_overview', 'desc' => 'Performa order & volume produksi per Brand'],
                        ['tool' => 'run_analytics_query', 'desc' => 'Mesin query agregasi ad-hoc dinamis untuk pertanyaan data kustom tanpa batas'],
                        ['tool' => 'get_factory_ai_skills', 'desc' => 'Paket Master Skill, SOP, formula bisnis garmen dan etika penagihan']
                    ]
                ]
            ],
            'ai_presentation_guide' => [
                'tone' => 'Profesional, ringkas, sopan, proaktif memberikan solusi penagihan dan mitigasi deadline.',
                'whatsapp_format' => 'Sertakan tombol/link format WhatsApp (https://wa.me/...) ketika menampilkan data penagihan.',
                'formatting' => 'Gunakan tabel Markdown rapi dan bullet points terstruktur untuk kemudahan baca.'
            ]
        ];

        if ($mode !== 'all' && isset($menu['categories'][$mode])) {
            return [
                'status' => 'success',
                'category_selected' => $mode,
                'data' => $menu['categories'][$mode],
                'ai_presentation_guide' => $menu['ai_presentation_guide']
            ];
        }

        return [
            'status' => 'success',
            'menu' => $menu
        ];
    }

    /**
     * Mengambil Master Skill, SOP Operasional, Panduan Persona & Formula Bisnis Pabrik Garmen untuk diinjeksi langsung ke AI / ChatGPT.
     */
    public function getFactoryAiSkills(array $params = []): array
    {
        $skills = McpSnapshotService::getFactoryAiSkills();

        return [
            'status' => 'success',
            'skill_package' => $skills,
            'ai_presentation_tip' => 'Gunakan instruksi skill ini sebagai fondasi penalaran, etika berkomunikasi, dan pemahaman operasional pabrik.'
        ];
    }

    /**
     * Menjalankan query agregasi analitik dinamis (Safe Read-Only SQL Analytics) untuk pertanyaan analisis kustom yang tidak tercover oleh tool biasa.
     */
    public function runAnalyticsQuery(array $params = []): array
    {
        $dataset = $params['dataset'] ?? 'orders';
        $filters = $params['filters'] ?? [];
        $metric = $params['metric'] ?? 'count';
        $field = $params['field'] ?? null;
        $groupBy = $params['group_by'] ?? null;
        $limit = !empty($params['limit']) ? (int)$params['limit'] : 20;

        $res = McpSnapshotService::runAnalyticsQuery($dataset, $filters, $metric, $field, $groupBy, $limit);

        return [
            'status' => isset($res['error']) ? 'error' : 'success',
            'analytics_data' => $res,
            'ai_presentation_tip' => 'Visualisasikan hasil analitik ini dalam bentuk tabel ringkas atau perbandingan angka yang mudah dipahami pimpinan pabrik.'
        ];
    }
}

