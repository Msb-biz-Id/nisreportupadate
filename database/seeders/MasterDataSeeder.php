<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Master\BahanKain;
use App\Models\Master\BankAccount;
use App\Models\Master\CustomerType;
use App\Models\Master\KategoriOrder;
use App\Models\Master\Logo;
use App\Models\Master\PaketOrder;
use App\Models\Master\PolaJahitan;
use App\Models\Master\Printing;
use App\Models\Master\Product;
use App\Models\Master\Progress;
use App\Models\Master\Resleting;
use App\Models\Master\Size;
use App\Models\Master\SumberOrder;
use App\Models\Master\TipeOrder;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBahanKain();
        $this->seedLogo();
        $this->seedResleting();
        $this->seedPrinting();
        $this->seedPaketOrder();
        $this->seedTipeOrder();
        $this->seedSize();
        $this->seedPolaJahitan();
        $this->seedProgress();
        $this->seedKategoriOrder();
        $this->seedSumberOrder();
        $this->seedCustomerType();
        $this->seedBank();
        $this->seedProduct();
    }

    private function seedBahanKain(): void
    {
        $items = [
            ['nama' => 'Polyester Drifit', 'deskripsi' => 'Bahan ringan, cepat kering, breathable'],
            ['nama' => 'Microfiber', 'deskripsi' => 'Halus, anti-tembus tinta, cocok untuk jersey'],
            ['nama' => 'Hyget Adidas', 'deskripsi' => 'Bahan tipis ringan, harga ekonomis'],
            ['nama' => 'Honeycomb', 'deskripsi' => 'Tekstur sarang lebah, sirkulasi udara baik'],
            ['nama' => 'Tactel', 'deskripsi' => 'Premium, anti-mengembang, fit di badan'],
            ['nama' => 'Cotton Combed 30s', 'deskripsi' => 'Bahan kaos standar, nyaman dipakai'],
            ['nama' => 'Dryfit Sport', 'deskripsi' => 'Dryfit grade sport, anti-bakteri'],
            ['nama' => 'Lotto Original', 'deskripsi' => 'Bahan premium import'],
        ];
        foreach ($items as $i) BahanKain::firstOrCreate(['nama' => $i['nama']], $i + ['is_active' => true]);
    }

    private function seedLogo(): void
    {
        $items = [
            ['nama' => 'Bordir Komputer', 'deskripsi' => 'Logo bordir presisi tinggi'],
            ['nama' => 'Polyflex', 'deskripsi' => 'Sablon polyflex tahan lama'],
            ['nama' => 'Rubber', 'deskripsi' => 'Sablon karet timbul'],
            ['nama' => 'Polyflex Reflective', 'deskripsi' => 'Polyflex memantul cahaya'],
            ['nama' => 'Print Sublim', 'deskripsi' => 'Logo terprint langsung pada bahan'],
            ['nama' => 'Patch Velcro', 'deskripsi' => 'Logo patch bisa dilepas pasang'],
        ];
        foreach ($items as $i) Logo::firstOrCreate(['nama' => $i['nama']], $i + ['is_active' => true]);
    }

    private function seedResleting(): void
    {
        $items = [
            ['nama' => 'YKK Standard', 'deskripsi' => 'Resleting YKK kualitas standar'],
            ['nama' => 'YKK Heavy Duty', 'deskripsi' => 'Resleting tugas berat untuk jaket'],
            ['nama' => 'Resleting Plastik', 'deskripsi' => 'Resleting plastik ringan'],
            ['nama' => 'Resleting Metal', 'deskripsi' => 'Resleting metal premium'],
            ['nama' => 'Tanpa Resleting', 'deskripsi' => 'Produk tanpa resleting'],
        ];
        foreach ($items as $i) Resleting::firstOrCreate(['nama' => $i['nama']], $i + ['is_active' => true]);
    }

    private function seedPrinting(): void
    {
        $items = [
            ['nama' => 'Sublimasi Penuh', 'deskripsi' => 'Print sublimasi seluruh permukaan'],
            ['nama' => 'Sublimasi Parsial', 'deskripsi' => 'Print sublimasi pada area tertentu'],
            ['nama' => 'DTF', 'deskripsi' => 'Direct to Film printing'],
            ['nama' => 'DTG', 'deskripsi' => 'Direct to Garment printing'],
            ['nama' => 'Sablon Manual', 'deskripsi' => 'Sablon manual screen printing'],
        ];
        foreach ($items as $i) Printing::firstOrCreate(['nama' => $i['nama']], $i + ['is_active' => true]);
    }

    private function seedPaketOrder(): void
    {
        $items = [
            ['nama' => 'Reguler', 'deskripsi' => 'Pengerjaan standar 7-14 hari'],
            ['nama' => 'Express', 'deskripsi' => 'Pengerjaan kilat 3-5 hari, biaya +20%'],
            ['nama' => 'Premium', 'deskripsi' => 'Pengerjaan + bahan premium'],
            ['nama' => 'Bulk Order', 'deskripsi' => 'Order > 100 pcs, harga grosir'],
        ];
        foreach ($items as $i) PaketOrder::firstOrCreate(['nama' => $i['nama']], $i + ['is_active' => true]);
    }

    private function seedTipeOrder(): void
    {
        $items = [
            ['nama' => 'Jersey Tim', 'deskripsi' => 'Order jersey untuk tim/klub'],
            ['nama' => 'Jersey Komunitas', 'deskripsi' => 'Order jersey komunitas'],
            ['nama' => 'Seragam Sekolah', 'deskripsi' => 'Order seragam sekolah'],
            ['nama' => 'Kaos Event', 'deskripsi' => 'Kaos panitia / peserta event'],
            ['nama' => 'Jaket', 'deskripsi' => 'Order jaket / hoodie'],
            ['nama' => 'Custom', 'deskripsi' => 'Order custom sesuai request'],
        ];
        foreach ($items as $i) TipeOrder::firstOrCreate(['nama' => $i['nama']], $i + ['is_active' => true]);
    }

    private function seedSize(): void
    {
        // Mengikuti BRD: "33 Size Options" lintas kategori
        $map = [
            'ANAK' => ['XS', 'S', 'M', 'L', 'XL'],
            'LAKI-LAKI' => ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL', '6XL', '7XL', '8XL', '9XL', '10XL'],
            'PEREMPUAN' => ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL'],
            'UNISEX' => ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL', '6XL'],
            'CUSTOM' => ['CUSTOM'],
        ];
        foreach ($map as $kat => $ukurans) {
            foreach ($ukurans as $idx => $u) {
                Size::firstOrCreate(
                    ['kategori_size' => $kat, 'ukuran' => $u],
                    ['urutan' => $idx, 'is_active' => true]
                );
            }
        }
    }

    private function seedPolaJahitan(): void
    {
        $items = [
            ['jenis_pola' => 'Lengan', 'nama' => 'Lengan Panjang'],
            ['jenis_pola' => 'Lengan', 'nama' => 'Lengan Pendek'],
            ['jenis_pola' => 'Lengan', 'nama' => 'Lengan 3/4'],
            ['jenis_pola' => 'Kerah', 'nama' => 'Kerah O-Neck'],
            ['jenis_pola' => 'Kerah', 'nama' => 'Kerah V-Neck'],
            ['jenis_pola' => 'Kerah', 'nama' => 'Kerah Polo'],
            ['jenis_pola' => 'Kerah', 'nama' => 'Kerah Henley'],
            ['jenis_pola' => 'Bawah', 'nama' => 'Potongan Lurus'],
            ['jenis_pola' => 'Bawah', 'nama' => 'Potongan Slim Fit'],
            ['jenis_pola' => 'Pundak', 'nama' => 'Pundak Drop'],
            ['jenis_pola' => 'Pundak', 'nama' => 'Pundak Standar'],
        ];
        foreach ($items as $i) PolaJahitan::firstOrCreate(
            ['jenis_pola' => $i['jenis_pola'], 'nama' => $i['nama']],
            $i + ['is_active' => true]
        );
    }

    private function seedProgress(): void
    {
        // 12 default per BRD Section 5.12
        $items = [
            ['nama_progress' => 'SETTING', 'urutan' => 1, 'warna' => '#6B7280', 'is_skippable' => false],
            ['nama_progress' => 'PRINTING', 'urutan' => 2, 'warna' => '#3B82F6', 'is_skippable' => true],
            ['nama_progress' => 'POTONG SUBLIME', 'urutan' => 3, 'warna' => '#0EA5E9', 'is_skippable' => true],
            ['nama_progress' => 'PRESS SUBLIME', 'urutan' => 4, 'warna' => '#06B6D4', 'is_skippable' => true],
            ['nama_progress' => 'POTONG BAHAN', 'urutan' => 5, 'warna' => '#F59E0B', 'is_skippable' => false],
            ['nama_progress' => 'JAHIT', 'urutan' => 6, 'warna' => '#EAB308', 'is_skippable' => false],
            ['nama_progress' => 'QC JAHIT & BUANG BENANG', 'urutan' => 7, 'warna' => '#10B981', 'is_skippable' => false],
            ['nama_progress' => 'TALI CELANA', 'urutan' => 8, 'warna' => '#22C55E', 'is_skippable' => true],
            ['nama_progress' => 'PRINT PRESS POLYFLEX', 'urutan' => 9, 'warna' => '#A855F7', 'is_skippable' => true],
            ['nama_progress' => 'STEAM', 'urutan' => 10, 'warna' => '#EC4899', 'is_skippable' => true],
            ['nama_progress' => 'PACKING', 'urutan' => 11, 'warna' => '#06B6D4', 'is_skippable' => false],
            ['nama_progress' => 'SENDING', 'urutan' => 12, 'warna' => '#8B5CF6', 'is_skippable' => false],
        ];
        foreach ($items as $i) Progress::firstOrCreate(
            ['nama_progress' => $i['nama_progress']],
            $i + ['is_active' => true]
        );
    }

    private function seedKategoriOrder(): void
    {
        // Master global reseller (brand_id NULL) + sampel per brand
        $globals = [
            ['nama' => 'Jersey'],
            ['nama' => 'Jaket'],
            ['nama' => 'Celana'],
            ['nama' => 'Kaos Polos'],
            ['nama' => 'Seragam'],
        ];
        foreach ($globals as $g) KategoriOrder::firstOrCreate(
            ['brand_id' => null, 'nama' => $g['nama']],
            $g + ['is_active' => true]
        );

        foreach (Brand::all() as $brand) {
            foreach (['Jersey', 'Jaket', 'Hoodie', 'Polo Shirt'] as $nama) {
                KategoriOrder::firstOrCreate(
                    ['brand_id' => $brand->id, 'nama' => $nama],
                    ['is_active' => true]
                );
            }
        }
    }

    private function seedSumberOrder(): void
    {
        $sources = [
            ['nama' => 'Instagram'],
            ['nama' => 'WhatsApp'],
            ['nama' => 'Marketplace (Shopee/Tokopedia)'],
            ['nama' => 'Website'],
            ['nama' => 'Referral'],
            ['nama' => 'Walk-in'],
        ];
        foreach ($sources as $s) SumberOrder::firstOrCreate(
            ['brand_id' => null, 'nama' => $s['nama']],
            $s + ['is_active' => true]
        );

        foreach (Brand::all() as $brand) {
            foreach (['Instagram', 'WhatsApp', 'Marketplace'] as $nama) {
                SumberOrder::firstOrCreate(
                    ['brand_id' => $brand->id, 'nama' => $nama],
                    ['is_active' => true]
                );
            }
        }
    }

    private function seedCustomerType(): void
    {
        // Global reseller defaults
        foreach ([
            ['nama' => 'Reguler', 'diskon_default' => 0],
            ['nama' => 'VIP', 'diskon_default' => 5],
            ['nama' => 'Reseller', 'diskon_default' => 15],
        ] as $type) {
            CustomerType::firstOrCreate(
                ['brand_id' => null, 'nama' => $type['nama']],
                $type + ['is_active' => true]
            );
        }

        foreach (Brand::all() as $brand) {
            foreach ([
                ['nama' => 'Reguler', 'diskon_default' => 0],
                ['nama' => 'Member', 'diskon_default' => 3],
                ['nama' => 'Reseller', 'diskon_default' => 15],
            ] as $type) {
                CustomerType::firstOrCreate(
                    ['brand_id' => $brand->id, 'nama' => $type['nama']],
                    $type + ['is_active' => true]
                );
            }
        }
    }

    private function seedBank(): void
    {
        foreach (Brand::all() as $brand) {
            $banks = [
                ['bank' => 'BCA', 'atas_nama' => $brand->nama_brand, 'nomor_rekening' => '1234567890'],
                ['bank' => 'Mandiri', 'atas_nama' => $brand->nama_brand, 'nomor_rekening' => '9876543210'],
                ['bank' => 'BRI', 'atas_nama' => $brand->nama_brand, 'nomor_rekening' => '0987654321'],
            ];
            foreach ($banks as $b) BankAccount::firstOrCreate(
                ['brand_id' => $brand->id, 'bank' => $b['bank'], 'nomor_rekening' => $b['nomor_rekening']],
                $b + ['is_active' => true]
            );
        }
    }

    private function seedProduct(): void
    {
        // Master global reseller
        $globalProducts = [
            ['nama' => 'Jersey Custom Full Sublim', 'harga' => 95000],
            ['nama' => 'Jersey Polos', 'harga' => 65000],
            ['nama' => 'Jaket Bomber Custom', 'harga' => 185000],
            ['nama' => 'Hoodie Custom', 'harga' => 175000],
            ['nama' => 'Celana Training Sublim', 'harga' => 85000],
        ];
        foreach ($globalProducts as $p) {
            Product::firstOrCreate(
                ['brand_id' => null, 'nama' => $p['nama']],
                $p + ['is_active' => true, 'is_featured' => true]
            );
        }

        foreach (Brand::all() as $brand) {
            foreach (['Jersey ' . $brand->kode . ' Original', 'Jaket ' . $brand->kode . ' Premium'] as $nama) {
                Product::firstOrCreate(
                    ['brand_id' => $brand->id, 'nama' => $nama],
                    ['harga' => rand(80, 250) * 1000, 'is_active' => true]
                );
            }
        }
    }
}
