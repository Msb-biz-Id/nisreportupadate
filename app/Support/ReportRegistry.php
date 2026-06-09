<?php

namespace App\Support;

class ReportRegistry
{
    public static function all(): array
    {
        return [
            'penjualan-produk' => [
                'slug' => 'penjualan-produk',
                'label' => 'Penjualan & Produk',
                'icon' => 'Package',
                'group' => 'penjualan',
                'description' => 'Analisa jumlah produk yang di-order, top produk, dan tren penjualan.',
                'filters' => ['date_range', 'periode'],
                'columns' => [
                    ['key' => 'nama_produk', 'label' => 'Produk'],
                    ['key' => 'total_qty', 'label' => 'Total Qty', 'format' => 'number'],
                    ['key' => 'total_order', 'label' => 'Jumlah Order', 'format' => 'number'],
                    ['key' => 'avg_qty', 'label' => 'Rata² per Order', 'format' => 'number'],
                    ['key' => 'total_value', 'label' => 'Total Nilai', 'format' => 'currency'],
                ],
                'chart' => ['type' => 'bar', 'x' => 'nama_produk', 'y' => 'total_qty', 'title' => 'Top Produk by Quantity'],
            ],
            'pelanggan' => [
                'slug' => 'pelanggan',
                'label' => 'Pelanggan',
                'icon' => 'Users',
                'group' => 'penjualan',
                'description' => 'Top pelanggan berdasarkan jumlah order dan total transaksi.',
                'filters' => ['date_range'],
                'columns' => [
                    ['key' => 'kode', 'label' => 'Kode'],
                    ['key' => 'nama', 'label' => 'Nama'],
                    ['key' => 'nomor_hp', 'label' => 'HP'],
                    ['key' => 'total_order', 'label' => 'Total Order', 'format' => 'number'],
                    ['key' => 'total_qty', 'label' => 'Total Pcs', 'format' => 'number'],
                    ['key' => 'total_value', 'label' => 'Total Transaksi', 'format' => 'currency'],
                    ['key' => 'last_order', 'label' => 'Order Terakhir', 'format' => 'date'],
                ],
                'chart' => ['type' => 'bar', 'x' => 'nama', 'y' => 'total_value', 'title' => 'Top 10 Pelanggan by Nilai'],
            ],
            'wilayah' => [
                'slug' => 'wilayah',
                'label' => 'Wilayah',
                'icon' => 'MapPin',
                'group' => 'penjualan',
                'description' => 'Distribusi order per wilayah secara hirarkis (Provinsi, Kabupaten, Kecamatan, Desa).',
                'filters' => ['date_range', 'level_wilayah'],
                'columns' => [
                    ['key' => 'provinsi', 'label' => 'Provinsi'],
                    ['key' => 'kabupaten', 'label' => 'Kabupaten/Kota'],
                    ['key' => 'kecamatan', 'label' => 'Kecamatan'],
                    ['key' => 'desa', 'label' => 'Desa/Kelurahan'],
                    ['key' => 'total_pelanggan', 'label' => 'Pelanggan', 'format' => 'number'],
                    ['key' => 'total_order', 'label' => 'Total Order', 'format' => 'number'],
                    ['key' => 'total_value', 'label' => 'Total Nilai', 'format' => 'currency'],
                ],
                'chart' => ['type' => 'bar', 'x' => 'kabupaten', 'y' => 'total_order', 'title' => 'Top Wilayah by Order'],
            ],
            'kategori' => [
                'slug' => 'kategori',
                'label' => 'Kategori Order',
                'icon' => 'Tag',
                'group' => 'penjualan',
                'description' => 'Distribusi order per kategori.',
                'filters' => ['date_range'],
                'columns' => [
                    ['key' => 'kategori', 'label' => 'Kategori'],
                    ['key' => 'total_order', 'label' => 'Total Order', 'format' => 'number'],
                    ['key' => 'total_qty', 'label' => 'Total Qty', 'format' => 'number'],
                    ['key' => 'total_value', 'label' => 'Total Nilai', 'format' => 'currency'],
                ],
                'chart' => ['type' => 'donut', 'label' => 'kategori', 'value' => 'total_order', 'title' => 'Distribusi per Kategori'],
            ],
            'status-po' => [
                'slug' => 'status-po',
                'label' => 'Status PO',
                'icon' => 'PackageCheck',
                'group' => 'operasional',
                'description' => 'Daftar PO dengan filter status dan periode.',
                'filters' => ['date_range', 'status_po'],
                'columns' => [
                    ['key' => 'no_po', 'label' => 'No PO'],
                    ['key' => 'nama_po', 'label' => 'Nama PO'],
                    ['key' => 'pelanggan', 'label' => 'Pelanggan'],
                    ['key' => 'tanggal_masuk', 'label' => 'Tgl Masuk', 'format' => 'date'],
                    ['key' => 'deadline', 'label' => 'Deadline', 'format' => 'date'],
                    ['key' => 'status', 'label' => 'Status', 'format' => 'status_badge'],
                    ['key' => 'total', 'label' => 'Total', 'format' => 'currency'],
                ],
            ],
            'monitoring-deadline' => [
                'slug' => 'monitoring-deadline',
                'label' => 'Monitoring Deadline',
                'icon' => 'AlarmClock',
                'group' => 'operasional',
                'description' => 'PO mendekati deadline atau sudah terlambat.',
                'filters' => ['threshold'],
                'columns' => [
                    ['key' => 'no_po', 'label' => 'No PO'],
                    ['key' => 'pelanggan', 'label' => 'Pelanggan'],
                    ['key' => 'deadline', 'label' => 'Deadline', 'format' => 'date'],
                    ['key' => 'days', 'label' => 'Hari', 'format' => 'days_indicator'],
                    ['key' => 'status', 'label' => 'Status', 'format' => 'status_badge'],
                ],
            ],
            'rijek' => [
                'slug' => 'rijek',
                'label' => 'Rijek Produksi',
                'icon' => 'AlertTriangle',
                'group' => 'produksi',
                'description' => 'Detail rijek produksi: jenis, tingkat, kendala.',
                'filters' => ['date_range'],
                'columns' => [
                    ['key' => 'no_po', 'label' => 'No PO'],
                    ['key' => 'tahapan', 'label' => 'Tahapan'],
                    ['key' => 'jenis', 'label' => 'Jenis'],
                    ['key' => 'tingkat', 'label' => 'Tingkat', 'format' => 'badge'],
                    ['key' => 'jumlah', 'label' => 'Jumlah', 'format' => 'number'],
                    ['key' => 'kendala', 'label' => 'Kendala'],
                    ['key' => 'tanggal', 'label' => 'Tanggal', 'format' => 'date'],
                ],
            ],
            'refund' => [
                'slug' => 'refund',
                'label' => 'Refund',
                'icon' => 'RotateCcw',
                'group' => 'keuangan',
                'description' => 'Detail refund per periode, jenis masalah, dan status.',
                'filters' => ['date_range', 'refund_status'],
                'columns' => [
                    ['key' => 'refund_number', 'label' => 'No Refund'],
                    ['key' => 'no_po', 'label' => 'No PO'],
                    ['key' => 'jenis_masalah', 'label' => 'Jenis Masalah'],
                    ['key' => 'jumlah_item', 'label' => 'Qty', 'format' => 'number'],
                    ['key' => 'nominal_refund', 'label' => 'Nominal', 'format' => 'currency'],
                    ['key' => 'status', 'label' => 'Status', 'format' => 'badge'],
                    ['key' => 'tanggal', 'label' => 'Diajukan', 'format' => 'date'],
                ],
            ],
            'pemasukan' => [
                'slug' => 'pemasukan',
                'label' => 'Pemasukan',
                'icon' => 'TrendingUp',
                'group' => 'keuangan',
                'description' => 'Detail semua pemasukan (otomatis dari PO + manual).',
                'filters' => ['date_range', 'is_auto'],
                'columns' => [
                    ['key' => 'tanggal', 'label' => 'Tanggal', 'format' => 'date'],
                    ['key' => 'kategori', 'label' => 'Kategori'],
                    ['key' => 'keterangan', 'label' => 'Keterangan'],
                    ['key' => 'nominal', 'label' => 'Nominal', 'format' => 'currency'],
                    ['key' => 'sumber', 'label' => 'Sumber', 'format' => 'badge'],
                ],
                'chart' => ['type' => 'bar', 'x' => 'kategori', 'y' => 'nominal', 'title' => 'Pemasukan per Kategori'],
            ],
            'pengeluaran' => [
                'slug' => 'pengeluaran',
                'label' => 'Pengeluaran',
                'icon' => 'TrendingDown',
                'group' => 'keuangan',
                'description' => 'Detail semua pengeluaran (otomatis dari refund + manual).',
                'filters' => ['date_range', 'is_auto'],
                'columns' => [
                    ['key' => 'tanggal', 'label' => 'Tanggal', 'format' => 'date'],
                    ['key' => 'kategori', 'label' => 'Kategori'],
                    ['key' => 'keterangan', 'label' => 'Keterangan'],
                    ['key' => 'nominal', 'label' => 'Nominal', 'format' => 'currency'],
                    ['key' => 'sumber', 'label' => 'Sumber', 'format' => 'badge'],
                ],
                'chart' => ['type' => 'bar', 'x' => 'kategori', 'y' => 'nominal', 'title' => 'Pengeluaran per Kategori'],
            ],
            'analisis-marketing' => [
                'slug' => 'analisis-marketing',
                'label' => 'Analisa Marketing',
                'icon' => 'TrendingUp',
                'group' => 'penjualan',
                'description' => 'Analisis gabungan Sumber Order dan Kategori Pelanggan untuk melacak omset.',
                'filters' => ['date_range', 'customer_type', 'sumber_order', 'brand', 'region', 'product'],
                'columns' => [
                    ['key' => 'sumber_order', 'label' => 'Sumber Order'],
                    ['key' => 'kategori_pelanggan', 'label' => 'Kategori Pelanggan'],
                    ['key' => 'total_order', 'label' => 'Jumlah Order', 'format' => 'number'],
                    ['key' => 'total_qty', 'label' => 'Total Qty', 'format' => 'number'],
                    ['key' => 'total_value', 'label' => 'Total Omset', 'format' => 'currency'],
                    ['key' => 'percentage', 'label' => 'Kontribusi (%)', 'format' => 'number'],
                ],
                'chart' => ['type' => 'bar', 'x' => 'sumber_order', 'y' => 'total_value', 'title' => 'Analisis Saluran Marketing by Omset'],
            ],
        ];
    }

    public static function find(string $slug): ?array
    {
        return self::all()[$slug] ?? null;
    }

    public static function groups(): array
    {
        return [
            'penjualan' => 'Penjualan & Pelanggan',
            'operasional' => 'Operasional & PO',
            'produksi' => 'Produksi & Rijek',
            'keuangan' => 'Keuangan',
        ];
    }
}
