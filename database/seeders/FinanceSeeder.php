<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Finance\KategoriPemasukan;
use App\Models\Finance\KategoriPengeluaran;
use Illuminate\Database\Seeder;

class FinanceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Brand::all() as $brand) {
            // Kategori Pemasukan default (system)
            KategoriPemasukan::firstOrCreate(
                ['brand_id' => $brand->id, 'nama_kategori' => 'PO Published'],
                ['deskripsi' => 'Pemasukan otomatis dari PO yang diterbitkan', 'is_system' => true, 'is_active' => true]
            );
            KategoriPemasukan::firstOrCreate(
                ['brand_id' => $brand->id, 'nama_kategori' => 'Pembayaran Invoice'],
                ['deskripsi' => 'Pemasukan otomatis dari pembayaran invoice', 'is_system' => true, 'is_active' => true]
            );

            // Kategori Pemasukan custom (samples)
            foreach (['Investasi', 'Pinjaman', 'Pendapatan Lain-lain'] as $custom) {
                KategoriPemasukan::firstOrCreate(
                    ['brand_id' => $brand->id, 'nama_kategori' => $custom],
                    ['is_system' => false, 'is_active' => true]
                );
            }

            // Kategori Pengeluaran default (system)
            KategoriPengeluaran::firstOrCreate(
                ['brand_id' => $brand->id, 'nama_kategori' => 'Refund PO'],
                ['deskripsi' => 'Pengeluaran otomatis dari refund PO', 'is_system' => true, 'is_active' => true]
            );

            // Kategori Pengeluaran custom (hierarchy samples)
            $operasional = KategoriPengeluaran::firstOrCreate(
                ['brand_id' => $brand->id, 'nama_kategori' => 'Operasional', 'parent_id' => null],
                ['is_active' => true]
            );
            foreach (['Gaji Karyawan', 'Listrik & Air', 'Sewa Gedung', 'Internet'] as $sub) {
                KategoriPengeluaran::firstOrCreate(
                    ['brand_id' => $brand->id, 'parent_id' => $operasional->id, 'nama_kategori' => $sub],
                    ['is_active' => true]
                );
            }

            $produksi = KategoriPengeluaran::firstOrCreate(
                ['brand_id' => $brand->id, 'nama_kategori' => 'Produksi', 'parent_id' => null],
                ['is_active' => true]
            );
            foreach (['Pembelian Bahan Baku', 'Pembelian Tinta', 'Maintenance Mesin'] as $sub) {
                KategoriPengeluaran::firstOrCreate(
                    ['brand_id' => $brand->id, 'parent_id' => $produksi->id, 'nama_kategori' => $sub],
                    ['is_active' => true]
                );
            }
        }
    }
}
