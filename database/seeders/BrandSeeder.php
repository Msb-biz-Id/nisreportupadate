<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $regularBrands = [
            [
                'nama_brand' => 'Apparel Allegiant',
                'kode' => 'ALG',
                'tagline' => 'Premium Athletic & Lifestyle Wear',
                'deskripsi' => 'Brand fashion & apparel olahraga premium dengan fokus pada kenyamanan dan performa terbaik.',
                'email' => 'hello@allegiant.id',
                'no_hp' => '081223344556',
                'alamat' => 'Sudirman Central Business District, Jakarta Selatan',
                'warna_primary' => '#000000ff',
                'is_active' => true,
                'brand_type' => Brand::TYPE_REGULAR,
            ],
            [
                'nama_brand' => 'Circle Sportwear',
                'kode' => 'CRL',
                'tagline' => 'Innovative Activewear Solutions',
                'deskripsi' => 'Pakaian olahraga inovatif untuk komunitas, atlet, dan gaya hidup aktif modern.',
                'email' => 'cs@circlesportwear.id',
                'no_hp' => '081334455667',
                'alamat' => 'Kawasan Industri Rungkut, Surabaya',
                'warna_primary' => '#EF4444',
                'is_active' => true,
                'brand_type' => Brand::TYPE_REGULAR,
            ],
            [
                'nama_brand' => 'Drive Sportwear',
                'kode' => 'DRV',
                'tagline' => 'Engineered for Performance',
                'deskripsi' => 'Apparel olahraga teknikal untuk performa maksimal dan durabilitas tinggi.',
                'email' => 'info@drivesportwear.id',
                'no_hp' => '081445566778',
                'alamat' => 'Dago Elos No. 24, Bandung',
                'warna_primary' => '#10B981',
                'is_active' => true,
                'brand_type' => Brand::TYPE_REGULAR,
            ],
        ];

        foreach ($regularBrands as $data) {
            Brand::firstOrCreate(['kode' => $data['kode']], $data);
        }

        // Hapus data reseller demo lama (RSL, RSL-BDG, RSL-JKT) jika masih ada
        Brand::whereIn('kode', ['RSL', 'RSL-BDG', 'RSL-JKT'])->delete();

        // Reseller nyata — masing-masing entitas reseller mandiri (tipe: reseller_hub)
        // Bisa punya branch sendiri nanti. Master data shared di level hub.
        $resellerBrands = [
            ['nama_brand' => 'Telulas',       'kode' => 'TLS', 'warna_primary' => '#F59E0B'],
            ['nama_brand' => 'Pamos',         'kode' => 'PMS', 'warna_primary' => '#3B82F6'],
            ['nama_brand' => 'Sfitt Apparel', 'kode' => 'SFT', 'warna_primary' => '#10B981'],
            ['nama_brand' => 'Sir Sportware', 'kode' => 'SIR', 'warna_primary' => '#EF4444'],
            ['nama_brand' => 'Balga',         'kode' => 'BLG', 'warna_primary' => '#8B5CF6'],
        ];
        foreach ($resellerBrands as $data) {
            Brand::firstOrCreate(
                ['kode' => $data['kode']],
                array_merge($data, [
                    'tagline'    => '',
                    'deskripsi'  => 'Reseller — ' . $data['nama_brand'],
                    'email'      => strtolower(str_replace(' ', '', $data['nama_brand'])) . '@reseller.local',
                    'no_hp'      => '',
                    'alamat'     => 'Indonesia',
                    'is_active'  => true,
                    'brand_type' => Brand::TYPE_RESELLER_HUB,
                ])
            );
        }
    }
}
