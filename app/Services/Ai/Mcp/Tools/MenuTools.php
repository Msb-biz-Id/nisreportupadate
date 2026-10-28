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
                    'description' => 'Cek sisa tagihan PO, reminder otomatis WhatsApp, dan arus kas.',
                    'quick_triggers' => [
                        'Sisa tagihan PO yang belum lunas',
                        'Buat draft pesan tagihan WhatsApp ke pelanggan',
                        'Ringkasan arus kas bulan ini',
                        'Daftar invoice jatuh tempo'
                    ],
                    'available_tools' => [
                        ['tool' => 'get_unpaid_reminders', 'desc' => 'Daftar semua PO dengan sisa tagihan / tunggakan + link WA'],
                        ['tool' => 'get_order_billing', 'desc' => 'Rincian invoice, DP, pelunasan & sisa tagihan per PO (params: order_id/spk_number)'],
                        ['tool' => 'get_cashflow_summary', 'desc' => 'Ringkasan pembayaran masuk & total piutang tertahan'],
                        ['tool' => 'get_overdue_invoices', 'desc' => 'Daftar order selesai yang belum melunasi pembayaran']
                    ]
                ],
                'orders' => [
                    'label' => '📦 2. Manajemen Order & Tracking (SPK)',
                    'description' => 'Pencarian status order, progres produksi, detail kuantiti, dan pelacakan resi pengiriman.',
                    'quick_triggers' => [
                        'Cari order SPK-xxxx',
                        'Daftar pesanan aktif dan statusnya',
                        'Lacak pengiriman dan ekspedisi'
                    ],
                    'available_tools' => [
                        ['tool' => 'get_active_orders', 'desc' => 'Daftar pesanan yang sedang berjalan di pabrik'],
                        ['tool' => 'get_order_detail', 'desc' => 'Detail lengkap item, ukuran, deadline, dan progress SPK'],
                        ['tool' => 'track_shipping', 'desc' => 'Lacak kurir, no resi, dan status pengantaran']
                    ]
                ],
                'production' => [
                    'label' => '🏭 3. Antrian Produksi & Deadline (Production Floor)',
                    'description' => 'Monitoring antrian stasiun kerja (potong, jahit, sablon, bordir, QC, packing) dan peringatan keterlambatan.',
                    'quick_triggers' => [
                        'Order yang mendekati deadline (H-3 / H-7)',
                        'Order yang sudah lewat deadline (terlambat)',
                        'Status antrian jahit dan sablon hari ini'
                    ],
                    'available_tools' => [
                        ['tool' => 'get_production_queue', 'desc' => 'Distribusi jumlah order per stasiun kerja'],
                        ['tool' => 'get_approaching_deadlines', 'desc' => 'Peringatan deadline kritis dalam X hari (params: days_ahead=7)'],
                        ['tool' => 'get_delayed_orders', 'desc' => 'Daftar PO yang telah melampaui tanggal deadline'],
                        ['tool' => 'get_defect_stats', 'desc' => 'Catatan kendala / reject QC jika tersedia']
                    ]
                ],
                'customer' => [
                    'label' => '👥 4. Data Pelanggan & Brand (CRM)',
                    'description' => 'Pencarian kontak pelanggan, brand owner, riwayat order, dan loyalitas.',
                    'quick_triggers' => [
                        'Cari nomor WhatsApp customer PT ABC',
                        'Riwayat pemesanan brand XYZ'
                    ],
                    'available_tools' => [
                        ['tool' => 'search_customer', 'desc' => 'Cari pelanggan berdasarkan nama, perusahaan, no HP, atau brand'],
                        ['tool' => 'get_customer_history', 'desc' => 'Histori transaksi dan reputasi pembayaran pelanggan']
                    ]
                ],
                'reports' => [
                    'label' => '📊 5. Laporan Eksekutif & Ringkasan (Executive Summary)',
                    'description' => 'Dashboard ringkasan harian/bulanan untuk Owner dan Direksi.',
                    'quick_triggers' => [
                        'Laporan ringkasan performa pabrik hari ini',
                        'Analisis omset per brand'
                    ],
                    'available_tools' => [
                        ['tool' => 'get_executive_report', 'desc' => 'KPI harian: total PO, omset, target produksi, piutang'],
                        ['tool' => 'get_brand_overview', 'desc' => 'Performa order & volume produksi per Brand']
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
}
