<?php

namespace Database\Seeders;

use App\Models\Master\Ekspedisi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EkspedisiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ekspedisis = [
            [
                'nama' => 'JNE',
                'deskripsi' => 'Jalur Nugraha Ekakurir - Layanan pengiriman reguler, YES, OKE',
            ],
            [
                'nama' => 'J&T Express',
                'deskripsi' => 'J&T Express - Layanan pengiriman reguler dan cepat',
            ],
            [
                'nama' => 'SiCepat Ekspres',
                'deskripsi' => 'SiCepat Ekspres - Layanan pengiriman cepat (SiUntung, BEST, HALU)',
            ],
            [
                'nama' => 'POS Indonesia',
                'deskripsi' => 'Pos Indonesia - POS Reguler, POS NextDay, Pos Sameday',
            ],
            [
                'nama' => 'Tiki',
                'deskripsi' => 'Titipan Kilat - Layanan pengiriman reguler dan ONS',
            ],
            [
                'nama' => 'Wahana',
                'deskripsi' => 'Wahana Prestasi Logistik - Layanan logistik ekonomis',
            ],
            [
                'nama' => 'Ninja Xpress',
                'deskripsi' => 'Ninja Xpress - Layanan pengiriman e-commerce',
            ],
            [
                'nama' => 'Anteraja',
                'deskripsi' => 'Anteraja - Layanan reguler, nextday, dan sameday',
            ],
            [
                'nama' => 'Lion Parcel',
                'deskripsi' => 'Lion Parcel - Layanan kargo udara',
            ],
            [
                'nama' => 'Sentral Cargo',
                'deskripsi' => 'Sentral Cargo - Layanan kargo darat, laut, dan udara terpercaya',
            ],
            [
                'nama' => 'Indah Cargo',
                'deskripsi' => 'Indah Logistik Cargo - Layanan pengiriman barang besar dan kargo',
            ],
            [
                'nama' => 'J&T Cargo',
                'deskripsi' => 'J&T Cargo - Layanan pengiriman kargo dan barang berat J&T',
            ],
            [
                'nama' => 'Shopee Express',
                'deskripsi' => 'Shopee Express (SPX) - Layanan pengiriman e-commerce Shopee',
            ],
            [
                'nama' => 'GoSend / GrabExpress',
                'deskripsi' => 'Layanan instan / sameday kurir motor aplikasi ojek online',
            ],
        ];

        foreach ($ekspedisis as $ekspedisi) {
            Ekspedisi::create([
                'id' => (string) Str::uuid(),
                'nama' => $ekspedisi['nama'],
                'deskripsi' => $ekspedisi['deskripsi'],
                'is_active' => true,
            ]);
        }
    }
}
