<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Master\BahanKain;
use App\Models\Master\BankAccount;
use App\Models\Master\CustomerType;
use App\Models\Master\Iklan;
use App\Models\Master\JenisOrder;
use App\Models\Master\KategoriOrder;
use App\Models\Master\Logo;
use App\Models\Master\PaketOrder;
use App\Models\Master\PolaJahitan;
use App\Models\Master\Printing;
use App\Models\Master\JenisProduk;
use App\Models\Master\JenisSetelan;
use App\Models\Master\PolaProduksi;
use App\Models\Master\Product;
use App\Models\Master\Progress;
use App\Models\Master\Resleting;
use App\Models\Master\Size;
use App\Models\Master\SumberOrder;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $isMysql = \Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite';
        if ($isMysql) \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $this->seedBahanKain();
        $this->seedLogo();
        $this->seedResleting();
        $this->seedPrinting();
        $this->seedPaketOrder();
        $this->seedSize();
        $this->seedPolaJahitan();
        $this->seedProgress();
        $this->seedJenisSetelan();
        $this->seedPolaProduksi();
        $this->seedJenisProduk();
        $this->seedKategoriOrder();
        $this->seedJenisOrder();
        $this->seedSumberOrder();
        $this->seedCustomerType();
        $this->seedBank();
        $this->seedProduct();
        if ($isMysql) \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function seedBahanKain(): void
    {
        \Illuminate\Support\Facades\DB::table('bahan_kains')->delete();
        $items = [
            'Milano Coolmax Tech',
            'Milano Premium',
            'Airwalk',
            'Smash',
            'Emboss Curly',
            'Emboss Straw',
            'Emboss Topo',
            'Jacquard Teraria',
            'Wafle',
            'Pique',
            'Senna',
            'Olino',
            'Century',
            'Scuba',
            'Dropnidel',
            'Jacquard Triple S',
            'Parasut',
            'Mikro Dk Lite',
            'Monochrome',
            'Rib',
            'Evistra (Kemeja)',
            'Peles (Bendera)',
            'Satin (Bendera)',
        ];
        foreach ($items as $nama) BahanKain::firstOrCreate(['nama' => $nama], ['is_active' => true]);
    }

    private function seedLogo(): void
    {
        \Illuminate\Support\Facades\DB::table('logos')->delete();
        $items = ['Pvc', 'Pvc Hologram', 'Flock Tatami', 'Dtf', 'Bordir', 'Woven', 'Rubber', 'Chameleon', 'Printing'];
        foreach ($items as $nama) Logo::firstOrCreate(['nama' => $nama], ['is_active' => true]);
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
        \Illuminate\Support\Facades\DB::table('printings')->delete();
        $items = [
            'Non Print',
            'Non Print + Polyflex',
            'Full Printing Atasan + Polyflex',
            'Printing Depan Belakang',
            'Full Printing Atasan',
            'Full Printing Sampai Celana',
            'Full Printing Celana',
        ];
        foreach ($items as $nama) Printing::firstOrCreate(['nama' => $nama], ['is_active' => true]);
    }

    private function seedPaketOrder(): void
    {
        \Illuminate\Support\Facades\DB::table('paket_orders')->delete();
        // [nama, warna, prioritas] — warna tampil di Kanban badge
        $items = [
            ['Normal',      '#10B981', 0],  // hijau — normal
            ['Ekspress 1',  '#F59E0B', 1],  // kuning
            ['Ekspress 2',  '#F59E0B', 1],
            ['Ekspress 3',  '#F59E0B', 1],
            ['Ekspress 4',  '#F97316', 1],  // oranye
            ['Ekspress 5',  '#F97316', 1],
            ['Ekspress 6',  '#F97316', 1],
            ['Ekspress 7',  '#EF4444', 1],  // merah
            ['Ekspress 8',  '#EF4444', 1],
            ['Ekspress 9',  '#EF4444', 1],
            ['Ekspress 10', '#DC2626', 1],  // merah gelap
            ['Urgent',      '#7C3AED', 2],  // ungu — kritis
        ];
        foreach ($items as [$nama, $warna, $prioritas]) {
            PaketOrder::firstOrCreate(
                ['nama' => $nama],
                ['warna' => $warna, 'prioritas' => $prioritas, 'is_active' => true]
            );
        }
    }

    private function seedSize(): void
    {
        $ukurans = ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL', '6XL', '7XL', '8XL', '9XL', '10XL', 'CUSTOM'];
        foreach ($ukurans as $idx => $u) {
            $size = Size::withTrashed()->where(['ukuran' => $u])->first();
            if ($size) {
                $size->update([
                    'urutan' => $idx,
                    'is_active' => true,
                    'deleted_at' => null,
                ]);
            } else {
                Size::create([
                    'ukuran' => $u,
                    'urutan' => $idx,
                    'is_active' => true,
                ]);
            }
        }
    }

    private function seedPolaJahitan(): void
    {
        \Illuminate\Support\Facades\DB::table('pola_jahitans')->delete();
        // Pola Jahitan Utama — untuk field "Pola Jahitan" di form
        $polaUtama = [
            'Standart',
            'Raglan 1.0',
            'Raglan 2.0',
            'Pecah Pola Custom',
            'Reglan Stick',
        ];
        foreach ($polaUtama as $nama) {
            PolaJahitan::firstOrCreate(
                ['jenis_pola' => 'Pola', 'nama' => $nama],
                ['is_active' => true]
            );
        }

        // Pola Jahitan List Lengan — untuk field "Jahitan List Lengan" di form
        $polaLengan = ['Overdeck', 'Stick'];
        foreach ($polaLengan as $nama) {
            PolaJahitan::firstOrCreate(
                ['jenis_pola' => 'Lengan', 'nama' => $nama],
                ['is_active' => true]
            );
        }
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
        // Removed as per request
    }

    private function seedSumberOrder(): void
    {
        // Global only
        $sources = ['Instagram', 'WhatsApp', 'TikTok', 'Marketplace (Shopee/Tokopedia)', 'Website', 'Referral', 'Walk-in'];
        foreach ($sources as $nama) SumberOrder::firstOrCreate(
            ['brand_id' => null, 'nama' => $nama],
            ['is_active' => true]
        );
    }

    private function seedCustomerType(): void
    {
        // Global only
        $types = ['Reguler', 'Member', 'VIP', 'Reseller', 'Sekolah', 'Perusahaan', 'Tim / Komunitas'];
        foreach ($types as $nama) {
            CustomerType::firstOrCreate(
                ['brand_id' => null, 'nama' => $nama],
                ['diskon_default' => 0, 'is_active' => true]
            );
        }
    }

    private function seedBank(): void
    {
        $targetBrands = Brand::whereIn('kode', ['ALG', 'CRL', 'DRV', 'IDW'])->get();
        foreach ($targetBrands as $brand) {
            $banks = [
                ['bank' => 'CASH', 'atas_nama' => $brand->nama_brand, 'nomor_rekening' => 'CASH'],
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

    private function seedJenisSetelan(): void
    {
        $items = [
            ['nama' => 'Stell (Atasan + Bawahan)', 'deskripsi' => 'Setelan lengkap atas + bawah'],
            ['nama' => 'Non-Stell (Atasan Saja)',  'deskripsi' => 'Hanya bagian atasan'],
            ['nama' => 'Atasan Saja',              'deskripsi' => 'Khusus atasan'],
            ['nama' => 'Bawahan Saja',             'deskripsi' => 'Khusus bawahan / celana'],
        ];
        foreach ($items as $i) JenisSetelan::firstOrCreate(['nama' => $i['nama']], $i + ['is_active' => true]);
    }

    private function seedPolaProduksi(): void
    {
        $items = [
            ['nama' => 'Standart',   'deskripsi' => 'Pola produksi standar'],
            ['nama' => 'Perempuan',  'deskripsi' => 'Pola khusus perempuan (lebih slim)'],
            ['nama' => 'Slim Fit',   'deskripsi' => 'Pola body fit'],
            ['nama' => 'Oversize',   'deskripsi' => 'Pola longgar / oversize'],
        ];
        foreach ($items as $i) PolaProduksi::firstOrCreate(['nama' => $i['nama']], $i + ['is_active' => true]);
    }

    private function seedJenisProduk(): void
    {
        \Illuminate\Support\Facades\DB::table('jenis_produks')->delete();
        // Jenis Produk global — dikelola admin produksi, tanpa harga
        $items = [
            'Jersey',
            'Jersey Running',
            'Jersey Running Lekbong',
            'Jersey Padel/Tenis',
            'Jersey Basket',
            'Jersey Tanpa Lengan / Lekbong',
            'Jaket',
            'Celana Panjang',
            'Celana Pendek',
            'Celana Cewek',
            'Tunik',
            'Celana Panjang Slim',
            'Celana Panjang Non Slim',
            'Celana Rok Slim',
            'Celana Rok Non Slim',
            'Bendera',
        ];
        foreach ($items as $nama) {
            JenisProduk::firstOrCreate(['nama' => $nama], ['is_active' => true]);
        }
    }

    private function seedProduct(): void
    {
        // Global catalog — brand bisa tambah produk spesifik via UI
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
    }

    private function seedJenisOrder(): void
    {
        // Global only
        $globals = ['Baru', 'Repeat Order', 'Revisi', 'Sample'];
        foreach ($globals as $nama) JenisOrder::firstOrCreate(
            ['brand_id' => null, 'nama' => $nama],
            ['is_active' => true]
        );
    }
}
