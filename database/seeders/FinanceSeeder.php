<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Finance\KategoriPemasukan;
use App\Models\Finance\KategoriPengeluaran;
use App\Models\Finance\Pemasukan;
use App\Models\Finance\Pengeluaran;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class FinanceSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Pemasukan::where('is_auto', false)->delete();
        Pengeluaran::truncate();
        KategoriPemasukan::truncate();
        KategoriPengeluaran::truncate();
        Schema::enableForeignKeyConstraints();

        $superadmin = User::where('email', 'superadmin@nisreport.local')->first();
        if (!$superadmin) {
            $superadmin = User::first();
        }

        $brands = Brand::all();
        if ($brands->isEmpty()) {
            return;
        }

        foreach ($brands as $brand) {
            $creator = User::where('email', 'like', '%' . strtolower($brand->kode) . '%')
                ->whereHas('roles', fn($q) => $q->where('name', 'admin_brand'))
                ->first() ?? $superadmin;

            // Kategori Pemasukan default (system)
            $catPublish = KategoriPemasukan::firstOrCreate(
                ['brand_id' => $brand->id, 'nama_kategori' => 'PO Published'],
                ['deskripsi' => 'Pemasukan otomatis dari PO yang diterbitkan', 'is_system' => true, 'is_active' => true]
            );
            $catInvoice = KategoriPemasukan::firstOrCreate(
                ['brand_id' => $brand->id, 'nama_kategori' => 'Pembayaran Invoice'],
                ['deskripsi' => 'Pemasukan otomatis dari pembayaran invoice', 'is_system' => true, 'is_active' => true]
            );

            // Kategori Pemasukan custom (samples)
            $customPemasukanCats = [];
            foreach (['Investasi', 'Pinjaman', 'Penjualan Limbah Sisa Kain', 'Pendapatan Bunga Bank'] as $custom) {
                $customPemasukanCats[$custom] = KategoriPemasukan::firstOrCreate(
                    ['brand_id' => $brand->id, 'nama_kategori' => $custom],
                    ['is_system' => false, 'is_active' => true]
                );
            }

            // Kategori Pengeluaran default (system)
            $catRefund = KategoriPengeluaran::firstOrCreate(
                ['brand_id' => $brand->id, 'nama_kategori' => 'Refund PO'],
                ['deskripsi' => 'Pengeluaran otomatis dari refund PO', 'is_system' => true, 'is_active' => true]
            );

            // Kategori Pengeluaran custom (hierarchy samples)
            $operasional = KategoriPengeluaran::firstOrCreate(
                ['brand_id' => $brand->id, 'nama_kategori' => 'Operasional', 'parent_id' => null],
                ['is_active' => true]
            );
            
            $subOperasionalCats = [];
            foreach (['Gaji Karyawan', 'Listrik & Air', 'Sewa Gedung', 'Internet'] as $sub) {
                $subOperasionalCats[$sub] = KategoriPengeluaran::firstOrCreate(
                    ['brand_id' => $brand->id, 'parent_id' => $operasional->id, 'nama_kategori' => $sub],
                    ['is_active' => true]
                );
            }

            $produksi = KategoriPengeluaran::firstOrCreate(
                ['brand_id' => $brand->id, 'nama_kategori' => 'Produksi', 'parent_id' => null],
                ['is_active' => true]
            );
            
            $subProduksiCats = [];
            foreach (['Pembelian Bahan Baku', 'Pembelian Tinta', 'Maintenance Mesin'] as $sub) {
                $subProduksiCats[$sub] = KategoriPengeluaran::firstOrCreate(
                    ['brand_id' => $brand->id, 'parent_id' => $produksi->id, 'nama_kategori' => $sub],
                    ['is_active' => true]
                );
            }

            // --- Seed Manual Incomes (Pemasukan) ---
            // 1. Owner's investment
            Pemasukan::create([
                'brand_id' => $brand->id,
                'kategori_pemasukan_id' => $customPemasukanCats['Investasi']->id,
                'tanggal' => Carbon::now()->subDays(25)->toDateString(),
                'nominal' => 25000000.00,
                'keterangan' => 'Suntikan modal awal owner untuk operasional brand ' . $brand->nama_brand,
                'is_auto' => false,
                'created_by' => $creator->id,
            ]);

            // 2. Sales of leftover fabric waste
            for ($k = 1; $k <= 3; $k++) {
                Pemasukan::create([
                    'brand_id' => $brand->id,
                    'kategori_pemasukan_id' => $customPemasukanCats['Penjualan Limbah Sisa Kain']->id,
                    'tanggal' => Carbon::now()->subDays($k * 7)->toDateString(),
                    'nominal' => rand(150000, 450000),
                    'keterangan' => 'Penjualan kain perca limbah produksi batch #' . $k,
                    'is_auto' => false,
                    'created_by' => $creator->id,
                ]);
            }

            // --- Seed Manual Expenses (Pengeluaran) ---
            // 1. Rent (Sewa Gedung)
            Pengeluaran::create([
                'brand_id' => $brand->id,
                'kategori_pengeluaran_id' => $subOperasionalCats['Sewa Gedung']->id,
                'tanggal' => Carbon::now()->subDays(20)->toDateString(),
                'nominal' => 3500000.00,
                'keterangan' => 'Sewa ruko/gedung produksi bulanan',
                'is_auto' => false,
                'created_by' => $creator->id,
            ]);

            // 2. Internet
            Pengeluaran::create([
                'brand_id' => $brand->id,
                'kategori_pengeluaran_id' => $subOperasionalCats['Internet']->id,
                'tanggal' => Carbon::now()->subDays(18)->toDateString(),
                'nominal' => 450000.00,
                'keterangan' => 'Tagihan Biznet WiFi Kantor',
                'is_auto' => false,
                'created_by' => $creator->id,
            ]);

            // 3. Electricity (Listrik & Air)
            Pengeluaran::create([
                'brand_id' => $brand->id,
                'kategori_pengeluaran_id' => $subOperasionalCats['Listrik & Air']->id,
                'tanggal' => Carbon::now()->subDays(15)->toDateString(),
                'nominal' => rand(800000, 1500000),
                'keterangan' => 'Tagihan token PLN & PDAM gedung produksi',
                'is_auto' => false,
                'created_by' => $creator->id,
            ]);

            // 4. Employee Salaries (Gaji Karyawan)
            Pengeluaran::create([
                'brand_id' => $brand->id,
                'kategori_pengeluaran_id' => $subOperasionalCats['Gaji Karyawan']->id,
                'tanggal' => Carbon::now()->subDays(5)->toDateString(),
                'nominal' => 12000000.00,
                'keterangan' => 'Gaji 3 staff produksi & admin brand ' . $brand->nama_brand,
                'is_auto' => false,
                'created_by' => $creator->id,
            ]);

            // 5. Purchase of Raw Materials (Pembelian Bahan Baku)
            for ($k = 1; $k <= 3; $k++) {
                Pengeluaran::create([
                    'brand_id' => $brand->id,
                    'kategori_pengeluaran_id' => $subProduksiCats['Pembelian Bahan Baku']->id,
                    'tanggal' => Carbon::now()->subDays(rand(2, 28))->toDateString(),
                    'nominal' => rand(1500000, 4000000),
                    'keterangan' => 'Belanja kain sublim / katun untuk stok produksi #' . $k,
                    'is_auto' => false,
                    'created_by' => $creator->id,
                ]);
            }

            // 6. Purchase of Ink (Pembelian Tinta)
            Pengeluaran::create([
                'brand_id' => $brand->id,
                'kategori_pengeluaran_id' => $subProduksiCats['Pembelian Tinta']->id,
                'tanggal' => Carbon::now()->subDays(12)->toDateString(),
                'nominal' => 1800000.00,
                'keterangan' => 'Restock tinta sublim Epson 4 warna (CMYK)',
                'is_auto' => false,
                'created_by' => $creator->id,
            ]);

            // 7. Machine maintenance
            Pengeluaran::create([
                'brand_id' => $brand->id,
                'kategori_pengeluaran_id' => $subProduksiCats['Maintenance Mesin']->id,
                'tanggal' => Carbon::now()->subDays(8)->toDateString(),
                'nominal' => 600000.00,
                'keterangan' => 'Servis berkala mesin printing & mesin press sublim',
                'is_auto' => false,
                'created_by' => $creator->id,
            ]);
        }
    }
}
