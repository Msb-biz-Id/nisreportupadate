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
                'tagline' => 'Pro jersey For Pro Team',
                'deskripsi' => 'Custom jersey express specialist',
                'logo' => 'brand_logos/alle.png',
                'email' => 'allegiant.id@gmail.com',
                'no_hp' => '08113027007',
                'whatsapp' => '089696311671',
                'alamat' => 'Komplek Ruko Artomoro, Jl. Pahlawan No.10, Pagerwojo, Sukomulyo, Kec. Lamongan, Kabupaten Lamongan, Jawa Timur 62213',
                'facebook' => 'Apparel Allegiant',
                'instagram' => 'apparelallegiant',
                'tiktok' => 'apparelallegiant',
                'warna_primary' => '#000000',
                'is_active' => true,
                'brand_type' => Brand::TYPE_REGULAR,
            ],
            [
                'nama_brand' => 'Circle Sportwear',
                'kode' => 'CRL',
                'tagline' => 'Jersey Elit Harga irit',
                'deskripsi' => 'Jersey Elit Harga irit',
                'logo' => 'brand_logos/Circle.png',
                'email' => 'circlesportwear@gmail.com',
                'no_hp' => '6285136562550',
                'whatsapp' => '6285136562550',
                'alamat' => 'Ruko Green Flower, Jl. Soekarno Hatta No.7, Karangmulyo, Sukomulyo, Kec. Lamongan, Kabupaten Lamongan, Jawa Timur 62216',
                'facebook' => 'circle sportwear indonesia',
                'instagram' => 'circlesportwear',
                'tiktok' => 'circlesportwear_',
                'warna_primary' => '#10b981',
                'is_active' => true,
                'brand_type' => Brand::TYPE_REGULAR,
            ],
            [
                'nama_brand' => 'Drive Sportwear',
                'kode' => 'DRV',
                'tagline' => 'Jersey ekonomis terbaik di kelasnya',
                'deskripsi' => 'Jersey ekonomis terbaik di kelasnya',
                'logo' => 'brand_logos/RJXS1uvCk33DrybK88h3bl7IJ4dNH5JJXUa0YoRn.png',
                'email' => 'sportweardrive@gmail.com',
                'no_hp' => '6285143849390',
                'whatsapp' => '6285143849390',
                'alamat' => 'Perumahan Green Flower, Ruko Green Flower, Jl. Soekarno Hatta No.7, Karangmulyo, Sukomulyo, Kec. Lamongan, Kabupaten Lamongan, Jawa Timur 62216',
                'facebook' => 'DRIVE. Sportwear',
                'instagram' => 'drivesportwear',
                'tiktok' => 'drivesportwear',
                'warna_primary' => '#820000',
                'is_active' => true,
                'brand_type' => Brand::TYPE_REGULAR,
            ],
            [
                'nama_brand' => 'INDOWAREHOUSE',
                'kode' => 'IDW',
                'tagline' => 'PUSAT CUSTOM JERSEY TERBAIK',
                'deskripsi' => 'Brand Utama Reseller — INDOWAREHOUSE',
                'logo' => null,
                'email' => 'indonesiasportwarehouse@gmail.com',
                'no_hp' => '62 858-5027-3293',
                'whatsapp' => '62 858-5027-3293',
                'alamat' => 'JL. LARAS – LIRIS NO.102 SIDOKUMPUL LAMONGAN',
                'instagram' => 'indowarehouse_',
                'warna_primary' => '#EF4444',
                'is_active' => true,
                'brand_type' => Brand::TYPE_RESELLER_HUB,
            ],
        ];

        foreach ($regularBrands as $data) {
            Brand::updateOrCreate(['kode' => $data['kode']], $data);
        }

        // No Brand milik Allegiant (reseller_hub, anak dari ALG)
        $algBrand = Brand::where('kode', 'ALG')->first();
        Brand::updateOrCreate(
            ['kode' => 'NOB-ALG'],
            [
                'nama_brand'      => 'No Brand',
                'kode'            => 'NOB-ALG',
                'tagline'         => '',
                'deskripsi'       => 'Order tanpa brand — Allegiant',
                'email'           => 'nobrand.alg@allegiant.local',
                'no_hp'           => '',
                'alamat'          => 'Indonesia',
                'warna_primary'   => '#6B7280',
                'is_active'       => true,
                'brand_type'      => Brand::TYPE_RESELLER_HUB,
                'parent_brand_id' => $algBrand?->id,
            ]
        );

        // Hapus data reseller demo lama (RSL, RSL-BDG, RSL-JKT) jika masih ada
        Brand::whereIn('kode', ['RSL', 'RSL-BDG', 'RSL-JKT'])->delete();

        // Seed global reseller branding in system settings
        \App\Models\Settings\SystemSetting::set('reseller_branding', 'nama_brand', 'INDOWAREHOUSE');
        \App\Models\Settings\SystemSetting::set('reseller_branding', 'logo', null);
        \App\Models\Settings\SystemSetting::set('reseller_branding', 'tagline', 'PUSAT CUSTOM JERSEY TERBAIK');
        \App\Models\Settings\SystemSetting::set('reseller_branding', 'no_hp', '62 858-5027-3293');
        \App\Models\Settings\SystemSetting::set('reseller_branding', 'alamat', 'JL. LARAS – LIRIS NO.102 SIDOKUMPUL LAMONGAN');
        \App\Models\Settings\SystemSetting::set('reseller_branding', 'email', 'indonesiasportwarehouse@gmail.com');
        \App\Models\Settings\SystemSetting::set('reseller_branding', 'instagram', 'indowarehouse_');
        \App\Models\Settings\SystemSetting::set('system', 'theme_color', '#a8001c');

        // Seed SEO branding settings
        \App\Models\Settings\SystemSetting::set('seo', 'site_name', 'ProTrack');
        \App\Models\Settings\SystemSetting::set('seo', 'site_description', 'Sistem tracking PO dan invoice secara aman dan privat.');
        \App\Models\Settings\SystemSetting::set('seo', 'logo', 'system/favicon.svg');
        \App\Models\Settings\SystemSetting::set('seo', 'favicon', 'system/favicon.svg');

        // Reseller nyata — masing-masing entitas reseller mandiri (tipe: reseller_hub)
        // Bisa punya branch sendiri nanti. Master data shared di level hub.
        $idwBrand = Brand::where(['kode' => 'IDW'])->first();
        $idwBrandId = $idwBrand ? $idwBrand->id : null;

        $resellerBrands = [
            ['nama_brand' => 'Telulas',       'kode' => 'TLS',     'warna_primary' => '#F59E0B'],
            ['nama_brand' => 'Pamos',         'kode' => 'PMS',     'warna_primary' => '#3B82F6'],
            ['nama_brand' => 'Sfitt Apparel', 'kode' => 'SFT',     'warna_primary' => '#10B981'],
            ['nama_brand' => 'Sir Sportware', 'kode' => 'SIR',     'warna_primary' => '#EF4444'],
            ['nama_brand' => 'Balga',         'kode' => 'BLG',     'warna_primary' => '#8B5CF6'],
            ['nama_brand' => 'No Brand',      'kode' => 'NOB-IDW', 'warna_primary' => '#6B7280'],
            ['nama_brand' => 'Indra Ruteng',  'kode' => 'IRT',     'warna_primary' => '#3B82F6'],
        ];

        // Hapus kode lama NOB jika masih ada (diganti NOB-IDW)
        Brand::where('kode', 'NOB')->delete();
        foreach ($resellerBrands as $data) {
            Brand::updateOrCreate(
                ['kode' => $data['kode']],
                array_merge([
                    'tagline'    => '',
                    'deskripsi'  => 'Reseller — ' . $data['nama_brand'],
                    'email'      => strtolower(str_replace(' ', '', $data['nama_brand'])) . '@reseller.local',
                    'no_hp'      => '',
                    'alamat'     => 'Indonesia',
                    'is_active'  => true,
                    'brand_type' => Brand::TYPE_RESELLER_HUB,
                    'parent_brand_id' => $idwBrandId,
                ], $data)
            );
        }
    }
}
