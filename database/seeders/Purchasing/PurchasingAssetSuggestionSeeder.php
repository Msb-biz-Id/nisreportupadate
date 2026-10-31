<?php

namespace Database\Seeders\Purchasing;

use App\Models\Purchasing\PurchasingAssetSuggestion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PurchasingAssetSuggestionSeeder extends Seeder
{
    public function run(): void
    {
        $suggestions = [
            ['keyword' => 'laptop', 'category_code' => 'IT', 'category_name' => 'Perangkat IT (Laptop / PC / Server)'],
            ['keyword' => 'notebook', 'category_code' => 'IT', 'category_name' => 'Perangkat IT (Laptop / PC / Server)'],
            ['keyword' => 'pc', 'category_code' => 'IT', 'category_name' => 'Perangkat IT (Laptop / PC / Server)'],
            ['keyword' => 'komputer', 'category_code' => 'IT', 'category_name' => 'Perangkat IT (Laptop / PC / Server)'],
            ['keyword' => 'server', 'category_code' => 'IT', 'category_name' => 'Perangkat IT (Laptop / PC / Server)'],
            ['keyword' => 'monitor', 'category_code' => 'IT', 'category_name' => 'Perangkat IT (Laptop / PC / Server)'],
            ['keyword' => 'printer', 'category_code' => 'ITP', 'category_name' => 'Periferal IT (Printer / Scanner / Router)'],
            ['keyword' => 'scanner', 'category_code' => 'ITP', 'category_name' => 'Periferal IT (Printer / Scanner / Router)'],
            ['keyword' => 'router', 'category_code' => 'ITP', 'category_name' => 'Periferal IT (Printer / Scanner / Router)'],
            ['keyword' => 'switch', 'category_code' => 'ITP', 'category_name' => 'Periferal IT (Printer / Scanner / Router)'],
            ['keyword' => 'access point', 'category_code' => 'ITP', 'category_name' => 'Periferal IT (Printer / Scanner / Router)'],
            ['keyword' => 'wifi', 'category_code' => 'ITP', 'category_name' => 'Periferal IT (Printer / Scanner / Router)'],
            ['keyword' => 'mesin jahit', 'category_code' => 'MSN', 'category_name' => 'Mesin & Peralatan Produksi'],
            ['keyword' => 'mesin obras', 'category_code' => 'MSN', 'category_name' => 'Mesin & Peralatan Produksi'],
            ['keyword' => 'mesin potong', 'category_code' => 'MSN', 'category_name' => 'Mesin & Peralatan Produksi'],
            ['keyword' => 'mesin press', 'category_code' => 'MSN', 'category_name' => 'Mesin & Peralatan Produksi'],
            ['keyword' => 'conveyor', 'category_code' => 'MSN', 'category_name' => 'Mesin & Peralatan Produksi'],
            ['keyword' => 'meja kerja', 'category_code' => 'KTK', 'category_name' => 'Peralatan Kantor'],
            ['keyword' => 'kursi kerja', 'category_code' => 'KTK', 'category_name' => 'Peralatan Kantor'],
            ['keyword' => 'lemari arsip', 'category_code' => 'KTK', 'category_name' => 'Peralatan Kantor'],
            ['keyword' => 'brankas', 'category_code' => 'KTK', 'category_name' => 'Peralatan Kantor'],
            ['keyword' => 'sofa', 'category_code' => 'FRN', 'category_name' => 'Furniture & Fixture'],
            ['keyword' => 'partisi', 'category_code' => 'FRN', 'category_name' => 'Furniture & Fixture'],
            ['keyword' => 'lampu', 'category_code' => 'FRN', 'category_name' => 'Furniture & Fixture'],
            ['keyword' => 'hand pallet', 'category_code' => 'GDG', 'category_name' => 'Peralatan Gudang'],
            ['keyword' => 'rak gudang', 'category_code' => 'GDG', 'category_name' => 'Peralatan Gudang'],
            ['keyword' => 'tangga', 'category_code' => 'GDG', 'category_name' => 'Peralatan Gudang'],
            ['keyword' => 'cctv', 'category_code' => 'KMN', 'category_name' => 'Peralatan Keamanan (CCTV / Alarm / APAR)'],
            ['keyword' => 'apar', 'category_code' => 'KMN', 'category_name' => 'Peralatan Keamanan (CCTV / Alarm / APAR)'],
            ['keyword' => 'fingerprint', 'category_code' => 'KMN', 'category_name' => 'Peralatan Keamanan (CCTV / Alarm / APAR)'],
            ['keyword' => 'bor', 'category_code' => 'TLS', 'category_name' => 'Peralatan Operasional (Tools)'],
            ['keyword' => 'obeng', 'category_code' => 'TLS', 'category_name' => 'Peralatan Operasional (Tools)'],
            ['keyword' => 'kamera', 'category_code' => 'MDM', 'category_name' => 'Peralatan Studio / Multimedia'],
            ['keyword' => 'mic', 'category_code' => 'MDM', 'category_name' => 'Peralatan Studio / Multimedia'],
            ['keyword' => 'tripod', 'category_code' => 'MDM', 'category_name' => 'Peralatan Studio / Multimedia'],
            ['keyword' => 'smartphone', 'category_code' => 'KRY', 'category_name' => 'Peralatan Karyawan (HP / Tablet)'],
            ['keyword' => 'tablet', 'category_code' => 'KRY', 'category_name' => 'Peralatan Karyawan (HP / Tablet)'],
            ['keyword' => 'kulkas', 'category_code' => 'FAS', 'category_name' => 'Peralatan Pantry & Facility'],
            ['keyword' => 'dispenser', 'category_code' => 'FAS', 'category_name' => 'Peralatan Pantry & Facility'],
            ['keyword' => 'ac', 'category_code' => 'FAS', 'category_name' => 'Peralatan Pantry & Facility'],
            ['keyword' => 'air conditioner', 'category_code' => 'FAS', 'category_name' => 'Peralatan Pantry & Facility'],
        ];

        foreach ($suggestions as $item) {
            PurchasingAssetSuggestion::firstOrCreate(
                ['keyword' => strtolower($item['keyword'])],
                [
                    'uuid' => (string) Str::uuid(),
                    'category_code' => $item['category_code'],
                    'category_name' => $item['category_name'],
                ]
            );
        }
    }
}
