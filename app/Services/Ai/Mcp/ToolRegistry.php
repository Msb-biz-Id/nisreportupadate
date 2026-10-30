<?php

namespace App\Services\Ai\Mcp;

use App\Services\Ai\Mcp\Tools\CustomerTools;
use App\Services\Ai\Mcp\Tools\FinanceTools;
use App\Services\Ai\Mcp\Tools\MenuTools;
use App\Services\Ai\Mcp\Tools\OrderTools;
use App\Services\Ai\Mcp\Tools\ProductionTools;
use App\Services\Ai\Mcp\Tools\ReportTools;
use Illuminate\Support\Facades\App;

class ToolRegistry
{
    /**
     * Definisi lengkap seluruh tool yang tersedia di sistem.
     */
    public function getDefinitions(): array
    {
        return [
            // ==================== INTERACTIVE MENU ====================
            'get_interactive_menu' => [
                'name' => 'get_interactive_menu',
                'description' => 'Mendapatkan menu navigasi interaktif terpadu, matriks rekomendasi tasking operasional pabrik (Harian, Mingguan, Bulanan), infografis dashboard, dan data ekspor siap salin (CSV & Markdown) untuk seluruh AI Client (ChatGPT, Claude, Gemini, Cursor, dll).',
                'class' => MenuTools::class,
                'method' => 'getInteractiveMenu',
                'scope' => 'read:menu',
                'category' => 'menu',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'category' => [
                            'type' => 'string',
                            'enum' => ['all', 'finance', 'orders', 'production', 'customer', 'reports'],
                            'description' => 'Filter kategori menu spesifik atau "all" untuk seluruh menu.',
                            'default' => 'all'
                        ],
                        'view' => [
                            'type' => 'string',
                            'enum' => ['all', 'tasking', 'tools', 'export'],
                            'description' => 'Pilihan tampilan: "tasking" untuk matriks tugas Harian/Mingguan/Bulanan, "export" untuk CSV siap salin, atau "all" untuk dashboard lengkap.',
                            'default' => 'all'
                        ]
                    ]
                ]
            ],
            'get_factory_ai_skills' => [
                'name' => 'get_factory_ai_skills',
                'description' => 'Menginjeksi paket Master Skill, SOP pabrik garmen, formula bisnis (kalkulasi pcs, piutang, deadline), dan etika penagihan WhatsApp ke dalam pemikiran AI.',
                'class' => MenuTools::class,
                'method' => 'getFactoryAiSkills',
                'scope' => 'read:menu',
                'category' => 'menu',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => []
                ]
            ],
            'run_analytics_query' => [
                'name' => 'run_analytics_query',
                'description' => 'Mesin query analitik agregasi ad-hoc terproteksi (Safe SQL Analytics) untuk menjawab pertanyaan analisis data custom (COUNT, SUM, AVG) per bulan/brand/status.',
                'class' => MenuTools::class,
                'method' => 'runAnalyticsQuery',
                'scope' => 'read:reports',
                'category' => 'reports',
                'inputSchema' => [
                    'type' => 'object',
                    'required' => ['dataset'],
                    'properties' => [
                        'dataset' => [
                            'type' => 'string',
                            'enum' => ['orders', 'customers', 'invoices', 'payments', 'expenses', 'rijeks', 'refunds'],
                            'description' => 'Tabel dataset yang dianalisis.'
                        ],
                        'metric' => [
                            'type' => 'string',
                            'enum' => ['count', 'sum', 'avg', 'min', 'max'],
                            'description' => 'Jenis agregasi perhitungan (default: count)',
                            'default' => 'count'
                        ],
                        'field' => [
                            'type' => 'string',
                            'description' => 'Kolom angka yang dihitung (misal: total_tagihan, nominal, quantity, amount).'
                        ],
                        'group_by' => [
                            'type' => 'string',
                            'enum' => ['month', 'status', 'brand_id'],
                            'description' => 'Kelompokkan hasil berdasarkan bulan, status, atau brand.'
                        ],
                        'filters' => [
                            'type' => 'object',
                            'description' => 'Filter tanggal (start_date, end_date), brand_id, atau status.'
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Batas maksimal baris (default: 20)',
                            'default' => 20
                        ]
                    ]
                ]
            ],

            // ==================== FINANCE & BILLING ====================
            'get_unpaid_reminders' => [
                'name' => 'get_unpaid_reminders',
                'description' => 'Daftar semua PO dengan sisa tagihan / tunggakan, dilengkapi kalkulasi sisa bayar dan link WhatsApp otomatis.',
                'class' => FinanceTools::class,
                'method' => 'getUnpaidReminders',
                'scope' => 'read:finance',
                'category' => 'finance',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Jumlah maksimal data yang diambil (default: 30)',
                            'default' => 30
                        ],
                        'brand_id' => [
                            'type' => 'integer',
                            'description' => 'Filter berdasarkan ID Brand tertentu jika diperlukan.'
                        ],
                        'min_remaining' => [
                            'type' => 'number',
                            'description' => 'Filter nominal sisa tagihan minimal dalam Rupiah.'
                        ]
                    ]
                ]
            ],
            'get_order_billing' => [
                'name' => 'get_order_billing',
                'description' => 'Mengambil rincian billing / invoice, riwayat DP, dan sisa pembayaran untuk spesifik satu order.',
                'class' => FinanceTools::class,
                'method' => 'getOrderBilling',
                'scope' => 'read:finance',
                'category' => 'finance',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'order_id' => [
                            'type' => 'integer',
                            'description' => 'ID numerik order di database'
                        ],
                        'spk_number' => [
                            'type' => 'string',
                            'description' => 'Nomor SPK atau nomor PO order'
                        ]
                    ]
                ]
            ],
            'get_cashflow_summary' => [
                'name' => 'get_cashflow_summary',
                'description' => 'Ringkasan arus kas keuangan pabrik (penerimaan kas masuk, beban operasional, refund, & laba bersih) dengan rekonsiliasi 100% klop per kategori pembayaran, per brand, dan rekening bank untuk periode bulan tertentu atau rentang tanggal bebas.',
                'class' => FinanceTools::class,
                'method' => 'getCashflowSummary',
                'scope' => 'read:finance',
                'category' => 'finance',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'periode' => [
                            'type' => 'string',
                            'enum' => ['bulan_ini', 'bulan_lalu', 'hari_ini', 'minggu_ini', 'tahun_ini', 'semua'],
                            'description' => 'Pilihan periode cepat (contoh: "bulan_ini", "bulan_lalu", "tahun_ini"). Default: "bulan_ini".'
                        ],
                        'month' => [
                            'type' => 'string',
                            'description' => 'Format 2 digit bulan (01-12) atau 1-12 (contoh: "10" untuk Oktober).'
                        ],
                        'year' => [
                            'type' => 'string',
                            'description' => 'Format 4 digit tahun (contoh: "2026").'
                        ],
                        'from_date' => [
                            'type' => 'string',
                            'description' => 'Tanggal awal rentang spesifik (format YYYY-MM-DD).'
                        ],
                        'to_date' => [
                            'type' => 'string',
                            'description' => 'Tanggal akhir rentang spesifik (format YYYY-MM-DD).'
                        ],
                        'brand_id' => [
                            'type' => 'string',
                            'description' => 'Filter ID Brand tertentu atau "all" untuk seluruh brand (opsional).'
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Jumlah sampel baris transaksi terbaru yang ditampilkan (default: 25, max: 100).',
                            'default' => 25
                        ]
                    ]
                ]
            ],
            'get_overdue_invoices' => [
                'name' => 'get_overdue_invoices',
                'description' => 'Daftar pesanan yang status produksinya sudah selesai/terkirim namun pembayarannya masih belum lunas.',
                'class' => FinanceTools::class,
                'method' => 'getOverdueInvoices',
                'scope' => 'read:finance',
                'category' => 'finance',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'brand_id' => [
                            'type' => 'string',
                            'description' => 'Filter ID brand tertentu (opsional). Biarkan kosong untuk seluruh brand.'
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Jumlah maksimal data yang diambil (default: 20)',
                            'default' => 20
                        ]
                    ]
                ]
            ],
            'get_finance_kpi' => [
                'name' => 'get_finance_kpi',
                'description' => 'Ringkasan komprehensif KPI Keuangan: Omzet aktual, total piutang tertahan/outstanding, pembayaran masuk, dan distribusi status pembayaran per brand.',
                'class' => FinanceTools::class,
                'method' => 'getFinanceKpi',
                'scope' => 'read:finance',
                'category' => 'finance',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'brand_id' => [
                            'type' => 'string',
                            'description' => 'ID Brand spesifik atau "all" untuk seluruh brand (opsional).'
                        ]
                    ]
                ]
            ],
            'get_bank_reconciliation' => [
                'name' => 'get_bank_reconciliation',
                'description' => 'Melihat rekonsiliasi saldo kas rekening bank operasional pabrik, total mutasi uang masuk dan keluar.',
                'class' => FinanceTools::class,
                'method' => 'getBankReconciliation',
                'scope' => 'read:finance',
                'category' => 'finance',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'brand_id' => [
                            'type' => 'string',
                            'description' => 'Filter rekening terkait Brand tertentu (opsional).'
                        ]
                    ]
                ]
            ],
            'get_invoices' => [
                'name' => 'get_invoices',
                'description' => 'Melihat daftar faktur invoice penagihan pelanggan dengan filter status pembayaran (unpaid, partial, paid, overdue).',
                'class' => FinanceTools::class,
                'method' => 'getInvoices',
                'scope' => 'read:finance',
                'category' => 'finance',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'status' => [
                            'type' => 'string',
                            'enum' => ['all', 'unpaid', 'partial', 'paid', 'overdue'],
                            'description' => 'Status invoice yang ingin difilter (default: all).'
                        ],
                        'brand_id' => [
                            'type' => 'string',
                            'description' => 'Filter Brand tertentu (opsional).'
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Jumlah maksimal invoice yang ditampilkan (default: 20).',
                            'default' => 20
                        ]
                    ]
                ]
            ],
            'get_expense_summary' => [
                'name' => 'get_expense_summary',
                'description' => 'Ringkasan pengeluaran biaya operasional pabrik per kategori pengeluaran dan total beban biaya bulanan.',
                'class' => FinanceTools::class,
                'method' => 'getExpenseSummary',
                'scope' => 'read:finance',
                'category' => 'finance',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'brand_id' => [
                            'type' => 'string',
                            'description' => 'Filter ID Brand (opsional)'
                        ],
                        'month' => [
                            'type' => 'string',
                            'description' => 'Format 2 digit bulan (01-12). Default bulan berjalan.'
                        ],
                        'year' => [
                            'type' => 'string',
                            'description' => 'Format 4 digit tahun (contoh: 2026). Default tahun berjalan.'
                        ]
                    ]
                ]
            ],
            'get_complaint_and_refund_kpi' => [
                'name' => 'get_complaint_and_refund_kpi',
                'description' => 'Statistik kendala mutu, catatan rijek / cacat per stasiun pengerjaan, dan pengembalian dana (refund) pelanggan.',
                'class' => FinanceTools::class,
                'method' => 'getComplaintAndRefundKpi',
                'scope' => 'read:finance',
                'category' => 'finance',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'brand_id' => [
                            'type' => 'string',
                            'description' => 'Filter ID Brand (opsional)'
                        ]
                    ]
                ]
            ],
            'get_receivable_aging_schedule' => [
                'name' => 'get_receivable_aging_schedule',
                'description' => 'Jadwal umur piutang (Aging Schedule) untuk analisis CFO: 0-30 hari, 31-60 hari, 61-90 hari, dan piutang macet (>90 hari).',
                'class' => FinanceTools::class,
                'method' => 'getReceivableAgingSchedule',
                'scope' => 'read:finance',
                'category' => 'finance',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'brand_id' => [
                            'type' => 'string',
                            'description' => 'Filter ID Brand (opsional)'
                        ]
                    ]
                ]
            ],
            'audit_financial_and_po_compliance' => [
                'name' => 'audit_financial_and_po_compliance',
                'description' => 'Audit transaksi abnormal, deteksi potensi fraud, order bypass DP tanpa otorisasi, dan order selesai yang belum melunasi pembayaran.',
                'class' => FinanceTools::class,
                'method' => 'auditFinancialAndPoCompliance',
                'scope' => 'read:finance',
                'category' => 'finance',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'brand_id' => [
                            'type' => 'string',
                            'description' => 'Filter ID Brand (opsional)'
                        ]
                    ]
                ]
            ],

            // ==================== ORDERS & TRACKING ====================
            'get_active_orders' => [
                'name' => 'get_active_orders',
                'description' => 'Mengambil daftar pesanan aktif yang sedang dalam proses produksi di pabrik.',
                'class' => OrderTools::class,
                'method' => 'getActiveOrders',
                'scope' => 'read:orders',
                'category' => 'orders',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Maksimal order yang ditampilkan (maks 50, default 25)',
                            'default' => 25
                        ],
                        'status' => [
                            'type' => 'string',
                            'description' => 'Filter tahapan (cutting, sewing, finishing, dll.)'
                        ],
                        'brand_id' => [
                            'type' => 'integer',
                            'description' => 'Filter spesifik ID Brand'
                        ]
                    ]
                ]
            ],
            'get_order_detail' => [
                'name' => 'get_order_detail',
                'description' => 'Mendapatkan informasi detail lengkap sebuah pesanan (spesifikasi artikel, breakdown size, status pengerjaan, deadline).',
                'class' => OrderTools::class,
                'method' => 'getOrderDetail',
                'scope' => 'read:orders',
                'category' => 'orders',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'order_id' => [
                            'type' => 'integer',
                            'description' => 'ID numerik order di sistem'
                        ],
                        'spk_number' => [
                            'type' => 'string',
                            'description' => 'Nomor SPK atau nomor PO order'
                        ]
                    ]
                ]
            ],
            'track_shipping' => [
                'name' => 'track_shipping',
                'description' => 'Melacak nomor resi, ekspedisi kurir, dan status pengantaran barang pesanan pelanggan.',
                'class' => OrderTools::class,
                'method' => 'trackShipping',
                'scope' => 'read:orders',
                'category' => 'orders',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'tracking_number' => [
                            'type' => 'string',
                            'description' => 'Nomor resi pengiriman'
                        ],
                        'order_id' => [
                            'type' => 'integer',
                            'description' => 'ID order yang dicari resinya'
                        ]
                    ]
                ]
            ],
            'get_shipping_performance' => [
                'name' => 'get_shipping_performance',
                'description' => 'Statistik performa pengiriman logistik pabrik, kurir ekspedisi paling banyak dipakai, dan total beban subsidi gratis ongkir.',
                'class' => ReportTools::class,
                'method' => 'getShippingPerformance',
                'scope' => 'read:orders',
                'category' => 'orders',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'brand_id' => [
                            'type' => 'string',
                            'description' => 'Filter ID Brand (opsional)'
                        ]
                    ]
                ]
            ],

            // ==================== PRODUCTION FLOOR ====================
            'get_production_queue' => [
                'name' => 'get_production_queue',
                'description' => 'Melihat beban antrian order per workstation (Cutting, Sablon/Printing, Sewing, Finishing, QC, Packing, dll) beserta jumlah order, total pcs, dan potensi bottleneck.',
                'class' => ProductionTools::class,
                'method' => 'getProductionQueue',
                'scope' => 'read:production',
                'category' => 'production',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'station' => [
                            'type' => 'string',
                            'description' => 'Filter stasiun/workstation tertentu (misal: "Cutting", "Sewing", "Jahit", "Sablon", "Finishing", "QC", "Packing"). Biarkan kosong untuk melihat rincian seluruh workstation.'
                        ],
                        'brand_id' => [
                            'type' => 'string',
                            'description' => 'Filter ID Brand tertentu (opsional). Kosongkan untuk seluruh brand pabrik.'
                        ]
                    ]
                ]
            ],
            'get_approaching_deadlines' => [
                'name' => 'get_approaching_deadlines',
                'description' => 'Peringatan dini order yang mendekati batas waktu selesai/deadline dalam rentang hari tertentu.',
                'class' => ProductionTools::class,
                'method' => 'getApproachingDeadlines',
                'scope' => 'read:production',
                'category' => 'production',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'days_ahead' => [
                            'type' => 'integer',
                            'description' => 'Jumlah hari ke depan yang dicek (default: 7 hari)',
                            'default' => 7
                        ]
                    ]
                ]
            ],
            'get_delayed_orders' => [
                'name' => 'get_delayed_orders',
                'description' => 'Daftar order yang telah melampaui tanggal deadline (terlambat) dan posisi stasiun produksinya saat ini.',
                'class' => ProductionTools::class,
                'method' => 'getDelayedOrders',
                'scope' => 'read:production',
                'category' => 'production',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'brand_id' => [
                            'type' => 'integer',
                            'description' => 'Filter ID Brand (opsional)'
                        ]
                    ]
                ]
            ],
            'get_defect_stats' => [
                'name' => 'get_defect_stats',
                'description' => 'Statistik reject / cacat produksi untuk evaluasi kontrol kualitas QC.',
                'class' => ProductionTools::class,
                'method' => 'getDefectStats',
                'scope' => 'read:production',
                'category' => 'production',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'days' => [
                            'type' => 'integer',
                            'description' => 'Rentang hari analisis (default: 30)',
                            'default' => 30
                        ]
                    ]
                ]
            ],
            'get_production_kpi' => [
                'name' => 'get_production_kpi',
                'description' => 'Monitoring KPI lantai produksi menyeluruh: beban antrean per workstation (cutting, sewing, sablon, bordir, finishing, packing), deadline mendekat, order terlambat, & PO siap kirim.',
                'class' => ProductionTools::class,
                'method' => 'getProductionKpi',
                'scope' => 'read:production',
                'category' => 'production',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'brand_id' => [
                            'type' => 'string',
                            'description' => 'ID Brand spesifik atau "all" untuk seluruh brand (opsional).'
                        ]
                    ]
                ]
            ],

            // ==================== CUSTOMER & CRM ====================
            'search_customer' => [
                'name' => 'search_customer',
                'description' => 'Mencari data profil dan kontak pelanggan/brand berdasarkan nama, nomor telepon WhatsApp, atau brand.',
                'class' => CustomerTools::class,
                'method' => 'searchCustomer',
                'scope' => 'read:customer',
                'category' => 'customer',
                'inputSchema' => [
                    'type' => 'object',
                    'required' => ['keyword'],
                    'properties' => [
                        'keyword' => [
                            'type' => 'string',
                            'description' => 'Kata kunci nama pelanggan, nama brand, atau nomor HP/WhatsApp'
                        ]
                    ]
                ]
            ],
            'get_customer_history' => [
                'name' => 'get_customer_history',
                'description' => 'Histori order dan transaksi yang pernah dilakukan oleh seorang pelanggan atau ringkasan customer dari suatu brand.',
                'class' => CustomerTools::class,
                'method' => 'getCustomerHistory',
                'scope' => 'read:customer',
                'category' => 'customer',
                'inputSchema' => [
                    'type' => 'object',
                    'required' => ['customer_id'],
                    'properties' => [
                        'customer_id' => [
                            'type' => 'string',
                            'description' => 'ID/Kode/Nama pelanggan atau Nama Brand (misal: "Allegiant")'
                        ]
                    ]
                ]
            ],
            'get_top_customers' => [
                'name' => 'get_top_customers',
                'description' => 'Mendapatkan daftar pelanggan terbaik dengan pesanan terbanyak (Top Customers Ranking) per Brand (misal: Allegiant) atau seluruh pabrik berdasarkan jumlah order, pcs, atau total omzet.',
                'class' => CustomerTools::class,
                'method' => 'getTopCustomers',
                'scope' => 'read:customer',
                'category' => 'customer',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'brand' => [
                            'type' => 'string',
                            'description' => 'Nama brand (contoh: "Allegiant", "Circle"), kode brand ("ALG"), atau Brand ID (opsional).'
                        ],
                        'by' => [
                            'type' => 'string',
                            'enum' => ['orders', 'pcs', 'revenue'],
                            'description' => 'Kriteria peringkat: "orders" (jumlah PO), "pcs" (total kuantitas pakaian), atau "revenue" (total nilai transaksi Rp). Default: orders.',
                            'default' => 'orders'
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Jumlah maksimal pelanggan teratas yang ditampilkan (default: 10)',
                            'default' => 10
                        ]
                    ]
                ]
            ],

            // ==================== REPORTS & EXECUTIVE ====================
            'get_executive_report' => [
                'name' => 'get_executive_report',
                'description' => 'Laporan ringkasan KPI eksekutif pabrik harian (total order aktif, piutang terbuka, kecepatan produksi) untuk pimpinan pabrik.',
                'class' => ReportTools::class,
                'method' => 'getExecutiveReport',
                'scope' => 'read:reports',
                'category' => 'reports',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'date' => [
                            'type' => 'string',
                            'description' => 'Tanggal laporan (format YYYY-MM-DD). Default hari ini.'
                        ]
                    ]
                ]
            ],
            'get_brand_overview' => [
                'name' => 'get_brand_overview',
                'description' => 'Ringkasan performa dan volume produksi per masing-masing brand yang diproduksi.',
                'class' => ReportTools::class,
                'method' => 'getBrandOverview',
                'scope' => 'read:reports',
                'category' => 'reports',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'brand_id' => [
                            'type' => 'integer',
                            'description' => 'ID Brand tertentu jika ingin filter satu brand saja.'
                        ]
                    ]
                ]
            ],
            'get_brand_targets' => [
                'name' => 'get_brand_targets',
                'description' => 'Target penjualan dan pencapaian bulanan (Target Revenue Rp & Target Pcs) per masing-masing Brand dan agregat total pabrik, termasuk sisa kekurangan yang harus dikejar.',
                'class' => ReportTools::class,
                'method' => 'getBrandTargets',
                'scope' => 'read:reports',
                'category' => 'reports',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'brand_id' => [
                            'type' => 'string',
                            'description' => 'ID Brand spesifik atau "all" untuk seluruh brand (opsional).'
                        ]
                    ]
                ]
            ],
            'get_marketing_leads_kpi' => [
                'name' => 'get_marketing_leads_kpi',
                'description' => 'Statistik akuisisi order berdasarkan channel marketing / sumber leads (TikTok, Instagram, Referral, Website) beserta omzet dan kuantiti pcs.',
                'class' => ReportTools::class,
                'method' => 'getMarketingLeadsKpi',
                'scope' => 'read:reports',
                'category' => 'reports',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'brand_id' => [
                            'type' => 'string',
                            'description' => 'Filter ID Brand (opsional)'
                        ]
                    ]
                ]
            ],
            'get_top_products_and_fabrics' => [
                'name' => 'get_top_products_and_fabrics',
                'description' => 'Daftar bahan kain (fabrics) paling banyak dipesan dan kategori produk garmen terlaris.',
                'class' => ReportTools::class,
                'method' => 'getTopProductsAndFabrics',
                'scope' => 'read:reports',
                'category' => 'reports',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'brand_id' => [
                            'type' => 'string',
                            'description' => 'Filter ID Brand (opsional)'
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Jumlah teratas (default: 10)',
                            'default' => 10
                        ]
                    ]
                ]
            ],
            'get_brand_portfolio_matrix' => [
                'name' => 'get_brand_portfolio_matrix',
                'description' => 'Matriks evaluasi portofolio brand garmen (analisis Cash Cow, Star, High Risk) untuk Konsultan Bisnis & Manajemen Strategis.',
                'class' => ReportTools::class,
                'method' => 'getBrandPortfolioMatrix',
                'scope' => 'read:reports',
                'category' => 'reports',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => []
                ]
            ],
            'get_jenis_po_report' => [
                'name' => 'get_jenis_po_report',
                'description' => 'Laporan Analisis Jenis PO menyeluruh: perolehan masing-masing per brand (Normal, Reseller, PO Support, Repeat Order), nilai omzet, tagihan belum lunas, statistik lengkap & format data visualisasi grafik.',
                'class' => ReportTools::class,
                'method' => 'getJenisPoReport',
                'scope' => 'read:reports',
                'category' => 'reports',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'brand_id' => [
                            'type' => 'string',
                            'description' => 'Filter ID Brand tertentu (opsional). Kosongkan untuk seluruh brand.'
                        ],
                        'year' => [
                            'type' => 'integer',
                            'description' => 'Tahun analisis (contoh: 2026). Default tahun berjalan.'
                        ],
                        'month' => [
                            'type' => 'integer',
                            'description' => 'Bulan analisis 1-12 (opsional). Kosongkan untuk akumulasi setahun.'
                        ],
                        'jenis_po' => [
                            'type' => 'string',
                            'description' => 'Filter jenis PO spesifik ("normal", "special_order", "reseller_price", "repeat_order"). Kosongkan untuk semua jenis PO.'
                        ]
                    ]
                ]
            ],
        ];
    }

    /**
     * Memanggil tool berdasarkan nama dan input arguments.
     */
    public function executeTool(string $name, array $arguments = [], array $allowedScopes = ['*']): array
    {
        $definitions = $this->getDefinitions();

        if (!isset($definitions[$name])) {
            return [
                'isError' => true,
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "Tool '{$name}' tidak ditemukan di sistem."
                    ]
                ]
            ];
        }

        $def = $definitions[$name];

        // Validasi scope jika kunci memiliki batasan
        if (!in_array('*', $allowedScopes, true) && !in_array($def['scope'], $allowedScopes, true)) {
            return [
                'isError' => true,
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "Akses ditolak: API Key tidak memiliki scope '{$def['scope']}'."
                    ]
                ]
            ];
        }

        try {
            $instance = App::make($def['class']);
            $method = $def['method'];
            $result = $instance->$method($arguments);

            if (is_array($result)) {
                $result['mandatory_executive_protocol'] = [
                    'rule' => 'DILARANG HANYA MENAMPILKAN DATA MENTAH. Jawaban Anda ke User WAJIB terdiri dari 3 BAGIAN:',
                    '1_data_presentation' => '📊 Sajikan data utama dalam tabel Markdown rapi (nominal Rp, badge status tebal, link WA penagihan jika relevan).',
                    '2_deep_dive_analysis' => '🔍 Lakukan Detailing Analisis Mendalam sesuai skill yang relevan (Kacamata CFO untuk likuiditas/piutang, Analis untuk tren/omzet, Konsultan untuk efisiensi/bottleneck, Auditor untuk kepatuhan/fraud).',
                    '3_actionable_solutions' => '💡 Berikan 2-4 Rekomendasi Solusi Nyata & Action Plan konkret yang dapat langsung dieksekusi tim hari ini.'
                ];
            }

            return [
                'isError' => false,
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    ]
                ],
                'raw_data' => $result
            ];
        } catch (\Throwable $e) {
            return [
                'isError' => true,
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "Kesalahan eksekusi tool '{$name}': " . $e->getMessage()
                    ]
                ]
            ];
        }
    }

    /**
     * Format untuk MCP tools/list
     */
    public function getToolsForMcp(): array
    {
        $tools = [];
        foreach ($this->getDefinitions() as $key => $def) {
            $tools[] = [
                'name' => $def['name'],
                'description' => $def['description'],
                'inputSchema' => $def['inputSchema']
            ];
        }
        return $tools;
    }

    /**
     * Format Prompts untuk MCP prompts/list
     */
    public function getPromptsForMcp(): array
    {
        return [
            [
                'name' => 'daily_executive_briefing',
                'description' => 'Membuat ringkasan eksekutif harian: status order, peringatan deadline, dan sisa piutang.',
                'arguments' => []
            ],
            [
                'name' => 'whatsapp_unpaid_reminders',
                'description' => 'Menganalisis order yang belum lunas dan merancang draf pesan WhatsApp penagihan yang santun.',
                'arguments' => []
            ],
            [
                'name' => 'production_bottleneck_audit',
                'description' => 'Mengidentifikasi stasiun kerja pabrik yang menumpuk dan berisiko terlambat.',
                'arguments' => []
            ]
        ];
    }

    /**
     * Format Resources untuk MCP resources/list
     */
    public function getResourcesForMcp(): array
    {
        return [
            [
                'uri' => 'garment://system/menu',
                'name' => 'Interactive Navigation Menu',
                'description' => 'Daftar panduan perintah interaktif dan kategori tool AI',
                'mimeType' => 'application/json'
            ],
            [
                'uri' => 'garment://finance/unpaid-summary',
                'name' => 'Unpaid PO Live Summary',
                'description' => 'Ringkasan total sisa pembayaran PO belum lunas',
                'mimeType' => 'application/json'
            ],
            [
                'uri' => 'garment://production/active-queue',
                'name' => 'Floor Queue Snapshot',
                'description' => 'Status antrian stasiun kerja saat ini',
                'mimeType' => 'application/json'
            ]
        ];
    }

    /**
     * Format OpenAI Tools (Function Calling)
     */
    public function getOpenAiTools(): array
    {
        $tools = [];
        foreach ($this->getDefinitions() as $key => $def) {
            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => $def['name'],
                    'description' => $def['description'],
                    'parameters' => $def['inputSchema']
                ]
            ];
        }
        return $tools;
    }

    /**
     * Format Google Gemini Function Declarations
     */
    public function getGeminiDeclarations(): array
    {
        $declarations = [];
        foreach ($this->getDefinitions() as $key => $def) {
            $declarations[] = [
                'name' => $def['name'],
                'description' => $def['description'],
                'parameters' => $def['inputSchema']
            ];
        }
        return ['functionDeclarations' => $declarations];
    }

    /**
     * Format OpenAPI 3.1 Spec untuk ChatGPT Custom Actions
     */
    public function getOpenApiSchema(string $serverUrl): array
    {
        $paths = [];
        foreach ($this->getDefinitions() as $name => $def) {
            $hasParams = !empty($def['inputSchema']['properties']);
            $parameters = [];

            if ($hasParams) {
                foreach ($def['inputSchema']['properties'] as $paramName => $prop) {
                    $parameters[] = [
                        'name' => $paramName,
                        'in' => 'query',
                        'description' => $prop['description'] ?? '',
                        'required' => in_array($paramName, $def['inputSchema']['required'] ?? [], true),
                        'schema' => [
                            'type' => $prop['type'] ?? 'string',
                            'default' => $prop['default'] ?? null
                        ]
                    ];
                }
            }

            $paths["/api/ai/tools/{$name}"] = [
                'get' => [
                    'summary' => $def['description'],
                    'operationId' => $name,
                    'tags' => [ucfirst($def['category'])],
                    'parameters' => $parameters,
                    'responses' => [
                        '200' => [
                            'description' => 'Successful operation',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }

        $skillPrompt = <<<TEXT
GARMENT OPERATING SYSTEM (GOS) AI MULTI-ROLE EXECUTIVE CONTROLLER:
Anda adalah Asisten Cerdas Eksekutif Pabrik Garmen berkemampuan Multi-Disiplin:
1. CFO / Direktur Keuangan: Analisis likuiditas, aging schedule piutang, dan dunning penagihan WA.
2. Senior Business Analyst: Analisis tren data, kontribusi omzet brand, dan retensi pelanggan.
3. Konsultan Bisnis Strategis: Evaluasi efisiensi kapasitas, bottleneck pabrik, dan matriks portofolio brand.
4. Auditor Internal: Audit transaksi anomali, kepatuhan SOP (bypass DP), dan risiko kebocoran rijek QC.

ATURAN WAJIB STRUKTUR JAWABAN (MANDATORY 3-PART RESPONSE PROTOCOL):
Setiap kali berhasil memanggil tool apapun dan memperoleh data, Anda DILARANG KERAS hanya menyajikan tabel data mentah atau angka saja. Jawaban Anda WAJIB disusun dalam 3 BAGIAN:
1. 📊 [RINGKASAN DATA & TABEL]: Sajikan data inti dalam tabel Markdown terformat rapi (nominal Rupiah Rp, badge status tebal, link WhatsApp penagihan berformat wa.me jika relevan).
2. 🔍 [DETAILING ANALISIS MENDALAM]: Ulas data secara mendalam dari kacamata skill yang relevan (CFO/Analis/Konsultan/Auditor): identifikasi akar penyebab, risiko bisnis, dan peluang yang terlihat dari angka tersebut.
3. 💡 [SOLUSI STRATEGIS & ACTION PLAN]: Berikan 2–4 rekomendasi langkah konkret dan solutif yang bisa langsung dieksekusi oleh pimpinan pabrik, admin, atau tim terkait hari ini.

CORE BUSINESS RULES & LOGIC:
1. Perhitungan Kuantiti (Pcs): Kuantiti dihitung dari order_items non-addon dengan prioritas kolom 'jml_atasan' jika ada. Addon (sablon, bordir, nameset) tidak boleh dihitung sebagai tambahan pcs baju.
2. Status Pembayaran & Piutang: Sisa tagihan dihitung dari Total Tagihan dikurangi Pembayaran yang terverifikasi (verified_at).
3. Urgensi Deadline: Peringatkan status CRITICAL untuk pesanan dengan sisa waktu <= 1 hari, HIGH untuk <= 3 hari.
4. Reminder WhatsApp: Saat menyajikan data penagihan, sertakan tautan resmi WhatsApp (https://wa.me/62...(?text=...)) dengan kalimat penagihan sopan, mengapresiasi kerja sama pelanggan.

KEY CAPABILITIES & TOOLS:
- Keuangan & CFO: get_finance_kpi, get_receivable_aging_schedule, get_unpaid_reminders, get_invoices, get_bank_reconciliation, get_expense_summary
- Auditor & Kepatuhan: audit_financial_and_po_compliance, get_complaint_and_refund_kpi, get_defect_stats, get_delayed_orders
- Konsultan & Analis: get_brand_portfolio_matrix, get_top_customers, get_marketing_leads_kpi, get_top_products_and_fabrics, get_brand_targets, get_executive_report
- Produksi & Lantai Pabrik: get_production_queue, get_production_kpi, get_approaching_deadlines
- Order & Logistik: get_active_orders, get_order_detail, track_shipping, get_shipping_performance
- Ad-Hoc Analytics: run_analytics_query (Safe SQL query aggregator untuk pertanyaan data custom)
TEXT;

        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Garment Operating System AI MCP API',
                'description' => $skillPrompt,
                'version' => '3.0.0'
            ],
            'servers' => [
                [
                    'url' => rtrim($serverUrl, '/')
                ]
            ],
            'paths' => $paths,
            'components' => [
                'securitySchemes' => [
                    'BearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'MCP-Token',
                        'description' => 'Masukkan API Key dengan prefix mcp_live_...'
                    ]
                ]
            ],
            'security' => [
                [
                    'BearerAuth' => []
                ]
            ]
        ];
    }
}

