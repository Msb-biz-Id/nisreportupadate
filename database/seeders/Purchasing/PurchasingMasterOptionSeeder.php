<?php

namespace Database\Seeders\Purchasing;

use App\Models\Purchasing\PurchasingMasterOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PurchasingMasterOptionSeeder extends Seeder
{
    public function run(): void
    {
        $dataset = [
            'location' => [
                'Laras Liris',
                'Walisongo',
                'Artomoro',
                'Green HCM',
                'Green Admin',
            ],
            'item_category' => [
                'ATK',
                'Consumable Printer / IT',
                'Pantry / Konsumsi Kantor',
                'Kebersihan Kantor',
                'Maintenance / Perawatan',
                'Operasional Kantor',
                'Safety / K3',
                'Kesejahteraan Karyawan',
                'Printing / Percetakan',
                'Kain Putih',
                'Kain Warna',
                'Jarum',
                'Kancing',
                'Size',
                'DTF Size',
                'Benang',
                'Poliester',
                'Resleting',
                'Bawahan',
                'Kerah',
                'Woffin / Wishtag',
                'Perlengkapan Jahit',
                'Perlengkapan Steam',
                'Polyflex',
                'Kemasan',
                'Sticker',
                'Logo',
                'Materi Marketing',
                'Perlengkapan Packing',
                'Perlengkapan QC',
            ],
            'unit' => [
                'Pcs',
                'Box / Dus',
                'Pack / Pak',
                'Rim',
                'Roll / Rol',
                'Lusin',
                'Kodi',
                'Gross',
                'Set',
                'Botol',
                'Galon',
                'Jerigen',
                'Pail / Drum',
                'Sachet / Tube',
                'Yard',
                'Meter',
                'Kilogram (Kg)',
                'Roll',
                'Bulan',
                'Tahun / Year',
                'Jam / Hari',
                'Lembar / Sheet',
                'Tabung',
            ],
            'vendor_type' => [
                'E-Commerce / Marketplace Online',
                'Pembelian Langsung / Offline',
                'Vendor Kontrak / Langganan',
                'Langganan Digital / Software',
            ],
            'payment_type' => [
                'Tunai / Kas Kecil (Cash / Petty Cash)',
                'Transfer Bank (Bank Transfer / Virtual Account)',
                'E-Wallet / Dompet Digital',
                'QRIS',
                'Kartu Kredit / Kartu Debit (Credit / Debit Card)',
                'Tempo / Kredit (Term of Payment / TOP)',
                'Reimburse / Dana Talangan Pribadi',
            ],
            'purchase_status' => [
                'Draft',
                'Menunggu Persetujuan (Pending Approval)',
                'Disetujui (Approved)',
                'Ditolak (Rejected)',
                'Diproses / Dipesan (In Process / Ordered)',
                'Sebagian Diterima (Partial Received)',
                'Selesai / Barang Diterima (Completed / Received)',
                'Menunggu Validasi Invoice / Keuangan (Pending Finance Verification)',
                'Lunas (Paid)',
                'Dibatalkan (Cancelled)',
            ],
            'asset_unit' => [
                'Pcs',
                'Unit',
                'Set',
                'Pasang (Pair)',
                'Batang',
                'Lembar / Sheet',
                'Roll / Rol',
                'Box / Dus',
                'Pack / Pak',
                'Paket / Project',
                'Meter',
                'Meter Persegi',
                'Titik',
            ],
            'retirement_reason' => [
                'Rusak Berat (Tidak Layak Pakai)',
                'Usang / Ketinggalan Teknologi',
                'Biaya Perbaikan Terlalu Mahal',
                'Dijual / Pelepasan Komersial',
                'Hilang / Dicuri',
                'Rusak karena Bencana / Kecelakaan',
                'Masa Manfaat Habis',
                'Ditukar Tambah',
                'Dimusnahkan / Scrap Total',
                'Pengembalian ke Pihak Ketiga',
                'Hibah / Donasi ke Pihak Lain',
                'Penutupan Lokasi / Ruko / Divisi',
                'Hasil Temuan Audit / Penghapusan Fisik',
                'Penurunan Kapasitas / Perubahan Fungsi Operasional',
                'Cacat Produksi / Bawaan Pabrik',
            ],
            'final_condition' => [
                'Sangat Baik (Seperti Baru / Mint Condition)',
                'Baik (Berfungsi Normal / Pemakaian Normal)',
                'Cukup / Layak Pakai (Ada Tanda Pemakaian / Minor Wear)',
                'Rusak Ringan (Perlu Servis / Bisa Diperbaiki)',
                'Rusak Sedang (Fungsi Terganggu / Sebagian Komponen Rusak)',
                'Rusak Berat / Total (Tidak Dapat Berfungsi Sama Sekali)',
                'Sisa Komponen Saja / Kanibalan (Spare Parts Only / Stripped)',
                'Scrap / Besi Tua / Bahan Daur Ulang',
                'Hilang Total (Tidak Ada Wujud Fisik)',
                'Utuh & Layak Jual (Resaleable / Good for Secondary Market)',
            ],
            'disposal_method' => [
                'Lelang Terbuka / Publik',
                'Penjualan Langsung / Negosiasi',
                'Penjualan ke Karyawan',
                'Tukar Tambah',
                'Hibah / Donasi Amal',
                'Pemusnahan / Penghancuran Total',
                'Pengembalian ke Lessor / Vendor',
                'Kanibalan Komponen',
                'Klaim Asuransi',
                'Dibuang / Dimusnahkan Langsung',
                'Ditarik Kembali ke Gudang Pusat',
                'Disimpan Sebagai Cadangan / Standby',
            ],
            'ink_variant' => [
                'Cyan',
                'Magenta',
                'Yellow',
                'Liquid Maintanance',
                'Wipercloth',
                'Flow Pink',
                'Flow Yellow',
                'Hitam',
            ],
        ];

        foreach ($dataset as $category => $items) {
            foreach ($items as $index => $itemName) {
                $code = Str::slug($itemName, '_');
                PurchasingMasterOption::firstOrCreate(
                    [
                        'category' => $category,
                        'name' => $itemName,
                    ],
                    [
                        'uuid' => (string) Str::uuid(),
                        'code' => $code,
                        'order_index' => $index + 1,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
