<?php

namespace Database\Seeders\Purchasing;

use App\Models\Purchasing\PurchasingMasterOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PurchasingAssetCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'TNH', 'name' => 'Tanah'],
            ['code' => 'BGN', 'name' => 'Bangunan / Properti'],
            ['code' => 'KND', 'name' => 'Kendaraan'],
            ['code' => 'MSN', 'name' => 'Mesin & Peralatan Produksi'],
            ['code' => 'KTK', 'name' => 'Peralatan Kantor'],
            ['code' => 'IT',  'name' => 'Perangkat IT (Laptop / PC / Server)'],
            ['code' => 'ITP', 'name' => 'Periferal IT (Printer / Scanner / Router)'],
            ['code' => 'FRN', 'name' => 'Furniture & Fixture'],
            ['code' => 'GDG', 'name' => 'Peralatan Gudang'],
            ['code' => 'KMN', 'name' => 'Peralatan Keamanan (CCTV / Alarm / APAR)'],
            ['code' => 'TLS', 'name' => 'Peralatan Operasional (Tools)'],
            ['code' => 'MKT', 'name' => 'Aset Marketing / Branding'],
            ['code' => 'SFT', 'name' => 'Software / Lisensi'],
            ['code' => 'ATB', 'name' => 'Aset Tidak Berwujud'],
            ['code' => 'PRB', 'name' => 'Perbaikan / Improvement (Capex)'],
            ['code' => 'INF', 'name' => 'Infrastruktur & Instalasi'],
            ['code' => 'MDM', 'name' => 'Peralatan Studio / Multimedia'],
            ['code' => 'KRY', 'name' => 'Peralatan Karyawan (HP / Tablet)'],
            ['code' => 'FAS', 'name' => 'Peralatan Pantry & Facility'],
            ['code' => 'LGL', 'name' => 'Legal / Administratif'],
        ];

        foreach ($categories as $index => $item) {
            PurchasingMasterOption::firstOrCreate(
                [
                    'category' => 'asset_category',
                    'code' => $item['code'],
                ],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $item['name'],
                    'order_index' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
