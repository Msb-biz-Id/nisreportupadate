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
     * Menampilkan menu interaktif terpadu, matriks rekomendasi tasking (Harian, Mingguan, Bulanan),
     * infografis dashboard, dan format ekspor data (CSV & Markdown) untuk seluruh AI Client (ChatGPT, Claude, Gemini, Cursor, dll).
     */
    public function getInteractiveMenu(array $params = []): array
    {
        $mode = $params['category'] ?? 'all';
        $view = $params['view'] ?? 'all';

        // 1. MATRIKS TASKING PERIODIK (Harian, Mingguan, Bulanan)
        $dailyTasks = [
            [
                'no' => 1,
                'prioritas' => '🔴 TINGGI',
                'task' => 'Pantau Antrean Workstation & Bottleneck Produksi',
                'pic' => 'Admin Produksi & SPV',
                'tool' => 'get_production_queue',
                'action_prompt' => 'Cek beban antrean produksi per workstation hari ini',
                'web_url' => 'https://nis.biz.id/admin-produksi',
                'objektif' => 'Deteksi penumpukan bahan di stasiun Cutting, Sablon, Jahit, QC, Finishing, & Packing.'
            ],
            [
                'no' => 2,
                'prioritas' => '🔴 TINGGI',
                'task' => 'Peringatan Dini PO Mendekati Deadline (H-1 s/d H-3)',
                'pic' => 'Admin Produksi & Logistik',
                'tool' => 'get_approaching_deadlines',
                'action_prompt' => 'Tampilkan pesanan yang mendekati batas deadline 3 hari ke depan',
                'web_url' => 'https://nis.biz.id/monitoring-deadline',
                'objektif' => 'Pencegahan keterlambatan pengiriman pakaian ke pelanggan.'
            ],
            [
                'no' => 3,
                'prioritas' => '🟡 SEDANG',
                'task' => 'Verifikasi Kas Masuk & Rekonsiliasi Rekening Hari Ini',
                'pic' => 'Admin Keuangan',
                'tool' => 'get_bank_reconciliation',
                'action_prompt' => 'Tampilkan mutasi dan saldo kas rekening bank operasional hari ini',
                'web_url' => 'https://nis.biz.id/reports/arus-kas-bank',
                'objektif' => 'Validasi setoran DP & pelunasan pelanggan di seluruh rekening bank.'
            ],
            [
                'no' => 4,
                'prioritas' => '🟡 SEDANG',
                'task' => 'Follow-up Penagihan PO Selesai Belum Lunas (WhatsApp)',
                'pic' => 'Admin Keuangan & CS',
                'tool' => 'get_overdue_invoices',
                'action_prompt' => 'Tampilkan PO berstatus selesai yang belum lunas beserta link WhatsApp',
                'web_url' => 'https://nis.biz.id/finance/pending-payments',
                'objektif' => 'Percepatan pencairan piutang order jadi yang siap dikirim.'
            ]
        ];

        $weeklyTasks = [
            [
                'no' => 1,
                'prioritas' => '🔴 TINGGI',
                'task' => 'Evaluasi Mutu & Cacat Pakaian (Rijek QC)',
                'pic' => 'QC & Kepala Produksi',
                'tool' => 'get_defect_stats',
                'action_prompt' => 'Evaluasi statistik rijek dan cacat produksi QC minggu ini',
                'web_url' => 'https://nis.biz.id/reports/rijek',
                'objektif' => 'Identifikasi akar masalah cacat di workstation sablon/jahit.'
            ],
            [
                'no' => 2,
                'prioritas' => '🔴 TINGGI',
                'task' => 'Audit Pesanan Terlambat (Overdue Orders)',
                'pic' => 'Supervisor Produksi',
                'tool' => 'get_delayed_orders',
                'action_prompt' => 'Daftar pesanan yang telah melampaui deadline beserta workstation terakhirnya',
                'web_url' => 'https://nis.biz.id/monitoring-deadline',
                'objektif' => 'Tindakan mitigasi lembur atau percepatan stasiun macet.'
            ],
            [
                'no' => 3,
                'prioritas' => '🟡 SEDANG',
                'task' => 'Jadwal Umur Piutang (Aging Schedule 0-90 Hari)',
                'pic' => 'CFO / Direktur Keuangan',
                'tool' => 'get_receivable_aging_schedule',
                'action_prompt' => 'Analisis jadwal umur piutang aging schedule pelanggan',
                'web_url' => 'https://nis.biz.id/finance',
                'objektif' => 'Mitigasi piutang macet di atas 30 dan 60 hari.'
            ],
            [
                'no' => 4,
                'prioritas' => '🟢 RUTIN',
                'task' => 'Evaluasi Performa Kurir Logistik & Subsidi Ongkir',
                'pic' => 'Admin Logistik',
                'tool' => 'get_shipping_performance',
                'action_prompt' => 'Statistik performa kurir pengiriman dan beban gratis ongkir',
                'web_url' => 'https://nis.biz.id/orders/track',
                'objektif' => 'Efisiensi beban subsidi ongkos kirim per brand.'
            ]
        ];

        $monthlyTasks = [
            [
                'no' => 1,
                'prioritas' => '🔴 TINGGI',
                'task' => 'Laporan Arus Kas, Laba Bersih & Rekonsiliasi 100% Klop',
                'pic' => 'Direktur / CFO',
                'tool' => 'get_cashflow_summary',
                'action_prompt' => 'Laporan arus kas dan laba bersih bulan ini bagaimana?',
                'web_url' => 'https://nis.biz.id/reports/pemasukan',
                'objektif' => 'Evaluasi kesehatan kas masuk vs beban operasional pabrik.'
            ],
            [
                'no' => 2,
                'prioritas' => '🔴 TINGGI',
                'task' => 'Pencapaian Target Penjualan (Revenue & Pcs) per Brand',
                'pic' => 'Manajer Marketing & Brand',
                'tool' => 'get_brand_targets',
                'action_prompt' => 'Pencapaian target penjualan bulanan omzet dan pcs per brand',
                'web_url' => 'https://nis.biz.id/reports/penjualan-produk',
                'objektif' => 'Mengukur % pencapaian target dan sisa omzet yang perlu dikejar.'
            ],
            [
                'no' => 3,
                'prioritas' => '🟡 SEDANG',
                'task' => 'Laporan Jenis PO (Normal, Reseller, Support, Repeat Order)',
                'pic' => 'Kepala Analis Data',
                'tool' => 'get_jenis_po_report',
                'action_prompt' => 'Laporan jenis PO per brand dan perolehan omzet',
                'web_url' => 'https://nis.biz.id/reports/jenis-po',
                'objektif' => 'Analisis rasio order baru vs repeat order dari pelanggan loyal.'
            ],
            [
                'no' => 4,
                'prioritas' => '🟡 SEDANG',
                'task' => 'Matriks Portofolio Brand (Cash Cow, Star, High Risk)',
                'pic' => 'Konsultan Bisnis / Owner',
                'tool' => 'get_brand_portfolio_matrix',
                'action_prompt' => 'Matriks portofolio brand untuk analisis strategi bisnis',
                'web_url' => 'https://nis.biz.id/dashboard/owner',
                'objektif' => 'Alokasi modal dan kapasitas produksi ke brand paling profitable.'
            ],
            [
                'no' => 5,
                'prioritas' => '🔴 TINGGI',
                'task' => 'Audit Kepatuhan Operasional, Bypass DP & Deteksi Fraud',
                'pic' => 'Auditor Internal',
                'tool' => 'audit_financial_and_po_compliance',
                'action_prompt' => 'Audit kepatuhan transaksi, bypass DP tanpa izin, dan deteksi fraud',
                'web_url' => 'https://nis.biz.id/audit-log',
                'objektif' => 'Penilaian kepatuhan SOP pabrik dan perlindungan aset owner.'
            ]
        ];

        // 2. GENERATE FORMAT EKSPOR (CSV & MARKDOWN SIAP SALIN)
        $csvRows = [];
        $csvRows[] = "Frekuensi,No,Prioritas,Nama Task,PIC Penanggung Jawab,Target Tool MCP,Tautan Web Sistem,Objektif & Hasil Akhir";
        foreach ($dailyTasks as $t) {
            $csvRows[] = "Harian,{$t['no']},\"{$t['prioritas']}\",\"{$t['task']}\",\"{$t['pic']}\",\"{$t['tool']}\",\"{$t['web_url']}\",\"{$t['objektif']}\"";
        }
        foreach ($weeklyTasks as $t) {
            $csvRows[] = "Mingguan,{$t['no']},\"{$t['prioritas']}\",\"{$t['task']}\",\"{$t['pic']}\",\"{$t['tool']}\",\"{$t['web_url']}\",\"{$t['objektif']}\"";
        }
        foreach ($monthlyTasks as $t) {
            $csvRows[] = "Bulanan,{$t['no']},\"{$t['prioritas']}\",\"{$t['task']}\",\"{$t['pic']}\",\"{$t['tool']}\",\"{$t['web_url']}\",\"{$t['objektif']}\"";
        }
        $exportableCsv = implode("\n", $csvRows);

        $menu = [
            'system_info' => [
                'name' => 'ProTrack AI Executive Copilot',
                'app_name' => config('app.name', 'ProTrack'),
                'version' => '3.6.0-ENTERPRISE-UNIVERSAL',
                'description' => 'Asisten pintar analitik & operasional pabrik garmen terintegrasi data real-time untuk ChatGPT, Claude Desktop, Google Gemini, OpenCode, Cursor, dan Cline.',
                'status' => '🟢 Terhubung Real-Time (ProTrack Live DB)',
                'total_tools_available' => 33,
                'ai_compatibility' => 'Universal Multi-Client Protocol (OpenAPI 3.1, JSON-RPC 2.0, SSE Stream)'
            ],
            'executive_skills' => [
                'CFO' => 'Direktur Keuangan (Arus kas, aging piutang, penagihan WA, efisiensi beban operasional, rekonsiliasi bank)',
                'DATA_ANALYST' => 'Kepala Analis Data (Statistik volume pcs, Laporan Jenis PO, tren per brand, ranking pelanggan)',
                'BUSINESS_CONSULTANT' => 'Konsultan Bisnis Strategis (Matriks portofolio brand, evaluasi target penjualan, strategi repeat order)',
                'INTERNAL_AUDITOR' => 'Auditor Internal & Kepatuhan (Deteksi fraud, audit bypass DP, kontrol mutu rijek QC, PO selesai belum lunas)'
            ],
            'periodic_tasking_matrix' => [
                'daily_operational_routine' => [
                    'label' => '🌅 Tasking Harian (Daily Operational Routine)',
                    'description' => 'Fokus: Kelancaran pengerjaan fisik lantai pabrik, pencegahan bottleneck antrean, dan monitoring kas masuk harian.',
                    'tasks' => $dailyTasks
                ],
                'weekly_tactical_routine' => [
                    'label' => '📅 Tasking Mingguan (Weekly Tactical Routine)',
                    'description' => 'Fokus: Pengawasan mutu pakaian, evaluasi rijek QC, audit keterlambatan PO, dan mitigasi risiko piutang.',
                    'tasks' => $weeklyTasks
                ],
                'monthly_strategic_routine' => [
                    'label' => '📊 Tasking Bulanan (Monthly Strategic & Executive Routine)',
                    'description' => 'Fokus: Laba bersih arus kas, evaluasi target penjualan brand, laporan jenis PO, dan audit kepatuhan SOP.',
                    'tasks' => $monthlyTasks
                ]
            ],
            'export_ready_data' => [
                'csv_tasking_checklist' => $exportableCsv,
                'copy_instruction' => 'Salin teks CSV di atas dan tempel (paste) langsung ke Microsoft Excel, Google Sheets, atau Notion untuk membuat checklist kerja tim operasional pabrik.'
            ],
            'categories' => [
                'finance' => [
                    'category_code' => 'finance',
                    'label' => '💰 1. Keuangan, Piutang & Penagihan (Finance & Billing)',
                    'total_tools' => 11,
                    'description' => 'Monitoring kesehatan finansial, piutang tertahan, aging schedule, reminder WA, rekonsiliasi kas bank, arus kas, dan audit risiko.',
                    'quick_triggers' => [
                        'KPI Keuangan & Piutang pabrik saat ini',
                        'Daftar PO yang masih ada sisa tagihan/tunggakan',
                        'PO berstatus selesai/terkirim yang belum lunas',
                        'Jadwal umur piutang (Aging Schedule) 0-30, 31-60, 61-90, >90 hari',
                        'Daftar faktur invoice penagihan pelanggan',
                        'Rekonsiliasi saldo kas rekening bank operasional',
                        'Rincian pengeluaran beban biaya operasional bulan ini',
                        'Audit transaksi anomali, bypass DP, dan deteksi fraud',
                        'Rincian tagihan, DP, dan sisa per nomor PO'
                    ],
                    'available_tools' => [
                        ['tool' => 'get_finance_kpi', 'desc' => 'Ringkasan KPI keuangan: omzet aktual, total piutang tertahan/outstanding, pembayaran masuk, per brand'],
                        ['tool' => 'get_unpaid_reminders', 'desc' => 'Daftar semua PO dengan sisa tagihan / tunggakan + link WA penagihan otomatis'],
                        ['tool' => 'get_overdue_invoices', 'desc' => 'Daftar pesanan selesai/terkirim yang belum lunas (dilengkapi link penagihan WA)'],
                        ['tool' => 'get_receivable_aging_schedule', 'desc' => 'Jadwal umur piutang (Aging Schedule 0-30, 31-60, 61-90, >90 hari macet) untuk CFO'],
                        ['tool' => 'get_invoices', 'desc' => 'Daftar faktur invoice penagihan pelanggan (filter: status=unpaid|partial|paid|overdue, brand_id)'],
                        ['tool' => 'get_cashflow_summary', 'desc' => 'Ringkasan arus kas keuangan pabrik (penerimaan kas masuk vs pengeluaran operasional)'],
                        ['tool' => 'get_bank_reconciliation', 'desc' => 'Rekonsiliasi saldo kas rekening bank operasional pabrik dan mutasi masuk/keluar'],
                        ['tool' => 'get_expense_summary', 'desc' => 'Rincian pengeluaran operasional per kategori beban biaya (params: brand_id, month, year)'],
                        ['tool' => 'get_complaint_and_refund_kpi', 'desc' => 'Statistik kendala mutu, catatan rijek per stasiun, dan pengembalian dana (refund)'],
                        ['tool' => 'audit_financial_and_po_compliance', 'desc' => 'Audit transaksi anomali, deteksi fraud, order bypass DP tanpa izin, dan order selesai belum lunas'],
                        ['tool' => 'get_order_billing', 'desc' => 'Rincian invoice, riwayat DP, dan sisa pembayaran untuk spesifik satu nomor PO']
                    ]
                ],
                'production' => [
                    'category_code' => 'production',
                    'label' => '🏭 2. Lantai Produksi & Workstation (Production Floor)',
                    'total_tools' => 5,
                    'description' => 'Monitoring KPI lantai produksi, beban antrean per workstation (Cutting, Sablon/Printing, Sewing, Finishing, QC, Packing), deteksi bottleneck, dan deadline.',
                    'quick_triggers' => [
                        'KPI Produksi menyeluruh & antrean workstation',
                        'Antrean beban produksi per workstation (Cutting, Sablon, Jahit, Finishing, QC, Packing)',
                        'Berapa beban antrean khusus di stasiun Cutting dan Jahit?',
                        'Order yang mendekati batas deadline (H-1, H-3, H-7)',
                        'Order yang sudah melampaui deadline (terlambat)',
                        'Statistik rijek / cacat produksi kontrol mutu QC'
                    ],
                    'available_tools' => [
                        ['tool' => 'get_production_kpi', 'desc' => 'Dashboard KPI produksi lengkap: kartu ringkasan, antrean per workstation, deadline mendekat, order terlambat & siap kirim'],
                        ['tool' => 'get_production_queue', 'desc' => 'Beban antrean per workstation (Cutting, Sablon/Printing, Sewing, Finishing, QC, Packing) beserta total order, pcs, dan penanda bottleneck'],
                        ['tool' => 'get_approaching_deadlines', 'desc' => 'Peringatan dini order yang mendekati deadline (params: days_ahead=7)'],
                        ['tool' => 'get_delayed_orders', 'desc' => 'Daftar pesanan yang telah melampaui deadline (terlambat) dan posisi stasiun produksinya'],
                        ['tool' => 'get_defect_stats', 'desc' => 'Statistik cacat/reject hasil produksi untuk evaluasi kontrol kualitas QC']
                    ]
                ],
                'orders' => [
                    'category_code' => 'orders',
                    'label' => '📦 3. Manajemen Order, Tracking & Logistik (Orders & Shipping)',
                    'total_tools' => 4,
                    'description' => 'Pencarian status order, progres produksi fisik, spesifikasi artikel, tracking resi ekspedisi, dan performa kurir pengiriman.',
                    'quick_triggers' => [
                        'Daftar pesanan aktif yang sedang diproses di pabrik',
                        'Cari detail pesanan PO-xxx atau SPK-xxx',
                        'Lacak nomor resi ekspedisi dan status pengiriman',
                        'Statistik kurir logistik dan beban subsidi gratis ongkir'
                    ],
                    'available_tools' => [
                        ['tool' => 'get_active_orders', 'desc' => 'Mengambil daftar pesanan aktif yang sedang dalam proses produksi di pabrik'],
                        ['tool' => 'get_order_detail', 'desc' => 'Detail lengkap pesanan: spesifikasi artikel, breakdown size, status pengerjaan, dan pembayaran (params: no_po)'],
                        ['tool' => 'track_shipping', 'desc' => 'Melacak nomor resi, kurir ekspedisi, dan status pengantaran barang pesanan'],
                        ['tool' => 'get_shipping_performance', 'desc' => 'Statistik performa pengiriman logistik pabrik, kurir terbanyak dipakai, dan beban subsidi ongkir']
                    ]
                ],
                'customer' => [
                    'category_code' => 'customer',
                    'label' => '👥 4. Data Pelanggan & CRM (Customer Intelligence)',
                    'total_tools' => 3,
                    'description' => 'Pencarian kontak pelanggan, nomor WhatsApp, riwayat order, dan peringkat pelanggan dengan order terbanyak.',
                    'quick_triggers' => [
                        'Pelanggan dengan order terbanyak brand Allegiant',
                        'Peringkat top pelanggan terbaik berdasarkan omzet / pcs',
                        'Cari kontak WhatsApp customer PT ABC',
                        'Riwayat pemesanan pelanggan XYZ'
                    ],
                    'available_tools' => [
                        ['tool' => 'get_top_customers', 'desc' => 'Peringkat pelanggan order terbanyak per Brand / Pabrik (params: brand, by=orders|pcs|revenue, limit)'],
                        ['tool' => 'search_customer', 'desc' => 'Cari pelanggan berdasarkan nama, perusahaan, nomor telepon WhatsApp, atau brand'],
                        ['tool' => 'get_customer_history', 'desc' => 'Histori transaksi, frekuensi pemesanan, dan reputasi pembayaran pelanggan']
                    ]
                ],
                'reports' => [
                    'category_code' => 'reports',
                    'label' => '📊 5. Laporan Eksekutif, Portofolio Brand & Analisis Jenis PO (Reports & Strategy)',
                    'total_tools' => 8,
                    'description' => 'Laporan jenis PO (Normal, Reseller, Support, Repeat Order), pencapaian target penjualan, matriks portofolio brand, channel leads marketing, dan mesin query SQL ad-hoc.',
                    'quick_triggers' => [
                        'Laporan Jenis PO per brand (Normal, Reseller, Support, Repeat Order)',
                        'Komparasi omzet dan tagihan belum lunas masing-masing brand',
                        'Pencapaian target penjualan bulanan (Omzet & Pcs) per brand',
                        'Matriks portofolio brand (Cash Cow, Star, High Risk)',
                        'Channel marketing & sumber leads paling efektif (TikTok, IG, Web)',
                        'Bahan kain (fabrics) terpopuler dan produk terlaris',
                        'Laporan ringkasan eksekutif KPI harian pabrik',
                        'Analisis kustom ad-hoc data pabrik'
                    ],
                    'available_tools' => [
                        ['tool' => 'get_jenis_po_report', 'desc' => 'Laporan Jenis PO: perolehan per brand, nilai omzet, tagihan belum lunas, statistik lengkap & format grafik visualisasi'],
                        ['tool' => 'get_brand_targets', 'desc' => 'Pencapaian target penjualan bulanan (Target Revenue Rp & Target Pcs) vs aktual, % capai & sisa kejar target per brand'],
                        ['tool' => 'get_brand_portfolio_matrix', 'desc' => 'Matriks portofolio brand (Cash Cow, Star, High Risk) untuk analisis Konsultan Bisnis'],
                        ['tool' => 'get_brand_overview', 'desc' => 'Performa order dan volume produksi per masing-masing brand'],
                        ['tool' => 'get_marketing_leads_kpi', 'desc' => 'Efektivitas channel pemasaran & sumber leads (TikTok, Instagram, Referral, Website)'],
                        ['tool' => 'get_top_products_and_fabrics', 'desc' => 'Statistik bahan kain (fabrics) terpopuler dan kategori produk garmen terlaris'],
                        ['tool' => 'get_executive_report', 'desc' => 'Laporan ringkasan KPI eksekutif pabrik harian untuk pimpinan pabrik'],
                        ['tool' => 'run_analytics_query', 'desc' => 'Mesin query agregasi ad-hoc terproteksi (Safe SQL Analytics) untuk pertanyaan analisis kustom tanpa batas']
                    ]
                ],
                'ai_system' => [
                    'category_code' => 'ai_system',
                    'label' => '🧠 6. Navigasi Menu & Protokol 4 Skill AI (AI Architecture)',
                    'total_tools' => 2,
                    'description' => 'Navigasi interaktif, injeksi paket Master Skill pabrik, formula kalkulasi pcs, dan aturan wajib 3-part protocol.',
                    'quick_triggers' => [
                        'Tampilkan menu interaktif utama',
                        'Lihat paket skill AI yang terinjeksi (CFO, Analis, Konsultan, Auditor)',
                        'SOP dan formula bisnis pabrik garmen'
                    ],
                    'available_tools' => [
                        ['tool' => 'get_interactive_menu', 'desc' => 'Menampilkan menu navigasi interaktif terarah dan trigger perintah cepat seluruh 33 tools'],
                        ['tool' => 'get_factory_ai_skills', 'desc' => 'Paket Master Skill 4 persona, SOP pabrik garmen, formula bisnis, dan etika penagihan WhatsApp']
                    ]
                ]
            ],
            'ai_presentation_guide' => [
                'infographic_dashboard_layout' => [
                    '1_header_and_overview' => 'Sajikan judul "ProTrack AI Executive Copilot" yang elegan dengan badge [🟢 ProTrack Live Online].',
                    '2_tasking_matrix_tables' => 'DILARANG HANYA MENAMPILKAN DAFTAR TEKS 1-5 BIASA. Anda WAJIB menyajikan 3 Tabel Matriks Tasking Infografis: (1) 🌅 Tasking Harian (Daily Routine), (2) 📅 Tasking Mingguan (Weekly Routine), (3) 📊 Tasking Bulanan (Monthly Routine). Kolom wajib: No | Prioritas | Agenda Tugas & Target | PIC | Perintah Cepat / Tautan Web.',
                    '3_export_ready_section' => 'Sediakan blok kode Markdown (```csv) bertanda "📋 Salin / Ekspor ke Excel/Sheets (Format CSV)" agar pengguna dapat langsung menyalin (Copy) seluruh agenda tugas dalam 1 klik.',
                    '4_menu_category_accordion' => 'Tampilkan ringkasan 6 kategori pilar pabrik untuk navigasi eksplorasi mandiri.'
                ],
                'universal_ai_rules' => 'Aturan ini berlaku untuk semua AI Client (ChatGPT, Claude Desktop, Gemini, OpenCode, Cursor, Cline). Wajib merender tabel infografis yang interaktif, profesional, dan siap eksekusi.'
            ]
        ];

        // 3. GENERATE PRE-RENDERED MARKDOWN DASHBOARD UNTUK MENJAMIN AI TIDAK MERINGKAS MENJADI LIST BIASA
        $fullMarkdown = $this->generateFullDashboardMarkdown($dailyTasks, $weeklyTasks, $monthlyTasks, $exportableCsv, $menu['categories']);

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
            'dashboard_markdown' => $fullMarkdown,
            'copy_ready_csv' => $exportableCsv,
            'menu' => $menu,
            'mandatory_instruction_for_ai' => 'PERINGATAN WAJIB UNTUK AI: DILARANG HANYA MENAMPILKAN DAFTAR 1-6 TEKS RINGKAS! Anda WAJIB langsung mencetak isi teks dari field "dashboard_markdown" secara lengkap dan rapi ke layar chat sekarang juga.'
        ];
    }

    /**
     * Membangun tampilan Dashboard Infografis Markdown lengkap siap saji.
     */
    protected function generateFullDashboardMarkdown(array $daily, array $weekly, array $monthly, string $csv, array $categories): string
    {
        $md = "# 🏭 ProTrack AI Executive Copilot — Dashboard Operasional Pabrik Garmen\n\n";
        $md .= "> **Status Sistem:** 🟢 `PROTRACK LIVE ONLINE` | **Database:** Real-Time Terintegrasi | **Multi-Role:** CFO, Analis Data, Konsultan Bisnis, Auditor Internal\n\n";
        $md .= "Selamat datang di pusat kendali operasional pabrik garmen. Di bawah ini adalah **Matriks Tasking Terjadwal** dan **Katalog Menu Cepat** yang dapat langsung Anda jalankan atau ekspor ke lembar kerja tim:\n\n";

        // Tabel Harian
        $md .= "### 🌅 1. Matriks Tasking Harian (Daily Operational Routine)\n";
        $md .= "*Fokus: Kelancaran lantai produksi, pencegahan bottleneck antrean, dan pengawasan kas masuk harian.*\n\n";
        $md .= "| No | Prioritas | Agenda Tugas Harian & Target | PIC | Perintah Cepat / Tautan Web |\n";
        $md .= "| :-: | :-: | :--- | :--- | :--- |\n";
        foreach ($daily as $t) {
            $md .= "| **{$t['no']}** | {$t['prioritas']} | **{$t['task']}**<br>_{$t['objektif']}_ | {$t['pic']} | [▶️ Jalankan Tugas]({$t['web_url']})<br>`{$t['tool']}` |\n";
        }
        $md .= "\n";

        // Tabel Mingguan
        $md .= "### 📅 2. Matriks Tasking Mingguan (Weekly Tactical Routine)\n";
        $md .= "*Fokus: Pengawasan mutu pakaian, evaluasi rijek QC, audit keterlambatan PO, dan mitigasi piutang.*\n\n";
        $md .= "| No | Prioritas | Agenda Tugas Mingguan & Target | PIC | Perintah Cepat / Tautan Web |\n";
        $md .= "| :-: | :-: | :--- | :--- | :--- |\n";
        foreach ($weekly as $t) {
            $md .= "| **{$t['no']}** | {$t['prioritas']} | **{$t['task']}**<br>_{$t['objektif']}_ | {$t['pic']} | [▶️ Jalankan Tugas]({$t['web_url']})<br>`{$t['tool']}` |\n";
        }
        $md .= "\n";

        // Tabel Bulanan
        $md .= "### 📊 3. Matriks Tasking Bulanan (Monthly Strategic & Executive Routine)\n";
        $md .= "*Fokus: Arus kas bersih, evaluasi target penjualan brand, laporan jenis PO, dan audit kepatuhan SOP.*\n\n";
        $md .= "| No | Prioritas | Agenda Tugas Bulanan & Target | PIC | Perintah Cepat / Tautan Web |\n";
        $md .= "| :-: | :-: | :--- | :--- | :--- |\n";
        foreach ($monthly as $t) {
            $md .= "| **{$t['no']}** | {$t['prioritas']} | **{$t['task']}**<br>_{$t['objektif']}_ | {$t['pic']} | [▶️ Jalankan Tugas]({$t['web_url']})<br>`{$t['tool']}` |\n";
        }
        $md .= "\n";

        // Blok Salin CSV
        $md .= "### 📋 4. Salin / Ekspor Agenda Tugas (Format CSV Siap Salin)\n";
        $md .= "Klik tombol **Salin** pada kotak kode berikut untuk menempelkannya langsung ke **Microsoft Excel**, **Google Sheets**, atau **Notion**:\n\n";
        $md .= "```csv\n" . $csv . "\n```\n\n";

        // Ringkasan 6 Pilar Kategori Tools
        $md .= "### 🧭 5. Katalog Lengkap 6 Pilar Operasional (33 Tools Tersedia)\n\n";
        $md .= "| No | Pilar Kategori | Total Tools | Cakupan Operasional | Contoh Perintah Cepat |\n";
        $md .= "| :-: | :--- | :-: | :--- | :--- |\n";
        $md .= "| 1 | 💰 **Keuangan & Piutang** | 11 Tools | Arus kas, aging piutang, penagihan WA, rekonsiliasi kas bank | `Laporan arus kas bulan ini` |\n";
        $md .= "| 2 | 🏭 **Produksi & Workstation** | 5 Tools | Antrean Cutting, Sablon, Jahit, QC, Finishing, Packing | `Beban antrean workstation` |\n";
        $md .= "| 3 | 📦 **Order & Logistik** | 4 Tools | Tracking SPK, progres fisik, spesifikasi artikel, lacak resi | `Detail pesanan PO-xxx` |\n";
        $md .= "| 4 | 👥 **Customer & CRM** | 3 Tools | Ranking pelanggan setia, kontak WA, riwayat repeat order | `Top pelanggan terbaik` |\n";
        $md .= "| 5 | 📊 **Laporan & Strategi Bisnis** | 8 Tools | Laporan Jenis PO, target omzet brand, matriks portofolio | `Laporan jenis PO per brand` |\n";
        $md .= "| 6 | 🧠 **SOP & AI Architecture** | 2 Tools | SOP formula pcs garmen, protokol 4 persona spesialis AI | `Injeksi master skills` |\n\n";
        $md .= "💡 _Silakan ketik nomor agenda atau klik tautan perintah di atas untuk langsung menjalankan analisis data!_\n";

        return $md;
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

