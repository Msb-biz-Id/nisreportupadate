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
                'nama_brand' => 'Shubuh Apparel',
                'kode' => 'SHU',
                'tagline' => 'Custom Jersey Bermutu Tinggi',
                'deskripsi' => 'Brand jersey custom untuk klub & komunitas seluruh Indonesia.',
                'email' => 'cs@shubuh.id',
                'no_hp' => '081234567890',
                'alamat' => 'Jl. Industri Raya No. 12, Jakarta',
                'warna_primary' => '#3B82F6',
                'is_active' => true,
            ],
            [
                'nama_brand' => 'Nisha Sport',
                'kode' => 'NIS',
                'tagline' => 'Performa Sehari-Hari',
                'deskripsi' => 'Apparel olahraga lokal dengan kualitas ekspor.',
                'email' => 'cs@nishasport.id',
                'no_hp' => '081298765432',
                'alamat' => 'Jl. Mawar No. 5, Bandung',
                'warna_primary' => '#10B981',
                'is_active' => true,
            ],
        ];

        foreach ($brands as $data) {
            Brand::firstOrCreate(['kode' => $data['kode']], $data);
        }
    }
}
