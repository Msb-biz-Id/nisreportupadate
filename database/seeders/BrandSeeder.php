<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            [
                'nama_brand' => 'Apparel Aleegiant',
                'kode' => 'ALG',
                'tagline' => 'Premium Athletic & Lifestyle Wear',
                'deskripsi' => 'Brand fashion & apparel olahraga premium dengan fokus pada kenyamanan dan performa terbaik.',
                'email' => 'hello@aleegiant.id',
                'no_hp' => '081223344556',
                'alamat' => 'Sudirman Central Business District, Jakarta Selatan',
                'warna_primary' => '#000000ff', // Indigo modern
                'is_active' => true,
            ],
            [
                'nama_brand' => 'Circle Sportwear',
                'kode' => 'CRL',
                'tagline' => 'Innovative Activewear Solutions',
                'deskripsi' => 'Pakaian olahraga inovatif untuk komunitas, atlet, dan gaya hidup aktif modern.',
                'email' => 'cs@circlesportwear.id',
                'no_hp' => '081334455667',
                'alamat' => 'Kawasan Industri Rungkut, Surabaya',
                'warna_primary' => '#EF4444', // Red modern
                'is_active' => true,
            ],
            [
                'nama_brand' => 'Drive Sportwear',
                'kode' => 'DRV',
                'tagline' => 'Engineered for Performance',
                'deskripsi' => 'Apparel olahraga teknikal untuk performa maksimal dan durabilitas tinggi.',
                'email' => 'info@drivesportwear.id',
                'no_hp' => '081445566778',
                'alamat' => 'Dago Elos No. 24, Bandung',
                'warna_primary' => '#10B981', // Emerald modern
                'is_active' => true,
            ],
        ];

        foreach ($brands as $data) {
            Brand::firstOrCreate(['kode' => $data['kode']], $data);
        }
    }
}
