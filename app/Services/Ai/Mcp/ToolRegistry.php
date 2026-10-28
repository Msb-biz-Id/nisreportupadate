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
                'description' => 'Mendapatkan menu navigasi interaktif terarah untuk memandu AI dan User memilih tool dengan tepat dan cepat.',
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
                            'description' => 'Filter kategori menu tertentu atau "all" untuk seluruh menu.',
                            'default' => 'all'
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
                'description' => 'Ringkasan arus kas keuangan pabrik (pembayaran masuk vs piutang tertahan) untuk periode bulan tertentu.',
                'class' => FinanceTools::class,
                'method' => 'getCashflowSummary',
                'scope' => 'read:finance',
                'category' => 'finance',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
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
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Jumlah maksimal data yang diambil (default: 20)',
                            'default' => 20
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

            // ==================== PRODUCTION FLOOR ====================
            'get_production_queue' => [
                'name' => 'get_production_queue',
                'description' => 'Melihat beban antrian order per stasiun kerja (Cutting, Sablon/Bordir, Sewing, Finishing, QC) untuk mencegah bottleneck.',
                'class' => ProductionTools::class,
                'method' => 'getProductionQueue',
                'scope' => 'read:production',
                'category' => 'production',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'station' => [
                            'type' => 'string',
                            'description' => 'Filter stasiun tertentu (opsional)'
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
                'description' => 'Histori order dan transaksi yang pernah dilakukan oleh seorang pelanggan.',
                'class' => CustomerTools::class,
                'method' => 'getCustomerHistory',
                'scope' => 'read:customer',
                'category' => 'customer',
                'inputSchema' => [
                    'type' => 'object',
                    'required' => ['customer_id'],
                    'properties' => [
                        'customer_id' => [
                            'type' => 'integer',
                            'description' => 'ID numerik pelanggan'
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

        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Garment Operating System AI MCP API',
                'description' => 'Universal Model Context Protocol & Tools API for Garment Factory Production & Billing Tracking.',
                'version' => '2.0.0'
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
