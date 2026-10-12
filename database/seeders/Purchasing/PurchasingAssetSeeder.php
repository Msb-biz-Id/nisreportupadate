<?php

namespace Database\Seeders\Purchasing;

use App\Models\Purchasing\PurchasingAsset;
use App\Models\Purchasing\PurchasingAssetMutation;
use App\Models\Purchasing\PurchasingAssetRetirement;
use App\Models\Purchasing\PurchasingMasterOption;
use App\Models\Purchasing\PurchasingOrder;
use App\Models\Purchasing\PurchasingVendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PurchasingAssetSeeder extends Seeder
{
    public function run(): void
    {
        $locations = PurchasingMasterOption::whereIn('category', ['location', 'lokasi'])->pluck('id', 'name')->toArray();
        $assetCatIds = PurchasingMasterOption::where('category', 'asset_category')->pluck('id', 'code')->toArray();

        $larasLirisId = $locations['Laras Liris'] ?? null;
        $artomoroId = $locations['Artomoro'] ?? null;
        $greenHcmId = $locations['Green HCM'] ?? null;
        $greenAdminId = $locations['Green Admin'] ?? null;
        $walisongoId = $locations['Walisongo'] ?? null;

        $vBudi = PurchasingVendor::where('vendor_code', 'VND-002')->first();
        $vElektronik = PurchasingVendor::where('vendor_code', 'VND-004')->first();

        $poMesin = PurchasingOrder::where('po_number', 'PO-202603-0004')->first();
        $poMeja = PurchasingOrder::where('po_number', 'PO-202603-0005')->first();
        $poLaptop = PurchasingOrder::where('po_number', 'PO-202603-0007')->first();

        // 1. Aset Mesin Potong (Dari Excel Sheet 3 Row 18)
        PurchasingAsset::updateOrCreate(
            ['asset_code' => 'MSN.PRD.026.001'],
            [
                'category_code' => 'MSN',
                'asset_category_id' => $assetCatIds['MSN'] ?? null,
                'department_code' => 'PRD',
                'year_code' => '026',
                'sequence_number' => 1,
                'registration_stage' => 'COMPLETED',
                'asset_name' => 'MESIN POTONG BESAR KAISAR',
                'specification' => 'MESIN POTONG KAIN BARU 10 INCH HIGH SPEED',
                'unit' => 'Unit',
                'quantity' => 1,
                'location_id' => $larasLirisId,
                'department' => 'Produksi',
                'division' => 'Potong Bahan',
                'position' => 'Potong Bahan',
                'vendor_id' => $vBudi?->id,
                'supplier_name' => $vBudi?->name,
                'purchase_date' => '2026-03-15',
                'received_date' => '2026-03-16',
                'acquisition_cost' => 3285036.00,
                'warranty_duration' => '12 Bulan',
                'warranty_expires_at' => now()->addMonths(6)->toDateString(),
                'serial_number' => 'SN-KSR-10IN-9921',
                'barcode_qr_code' => 'MSN.PRD.026.001',
                'status' => 'ACTIVE',
                'notes' => 'Aset utama pemotongan kain ruko Laras Liris',
            ]
        );

        // 1B. Mesin Jahit Industri High-Speed #12 (AST-PROD-041) - Garansi Habis dalam 2 Hari
        PurchasingAsset::updateOrCreate(
            ['asset_code' => 'AST-PROD-041'],
            [
                'category_code' => 'MSN',
                'asset_category_id' => $assetCatIds['MSN'] ?? null,
                'department_code' => 'PRD',
                'year_code' => '026',
                'sequence_number' => 41,
                'registration_stage' => 'COMPLETED',
                'order_id' => $poMesin?->id,
                'asset_name' => 'Mesin Jahit Industri High-Speed #12',
                'specification' => 'Mesin Jahit High-Speed Juki DDL-8700 Full Set Meja + Dinamo',
                'unit' => 'Unit',
                'quantity' => 1,
                'location_id' => $larasLirisId,
                'department' => 'Produksi',
                'division' => 'Jahit & Finishing',
                'position' => 'Operator Jahit',
                'vendor_id' => $vBudi?->id,
                'supplier_name' => $vBudi?->name,
                'purchase_date' => now()->subYear()->addDays(2)->toDateString(),
                'received_date' => now()->subYear()->addDays(3)->toDateString(),
                'acquisition_cost' => 4500000.00,
                'warranty_duration' => '12 Bulan',
                'warranty_expires_at' => now()->addDays(2)->toDateString(), // Habis dalam 2 hari!
                'serial_number' => 'JK-DDL8700-041',
                'barcode_qr_code' => 'AST-PROD-041',
                'status' => 'ACTIVE',
                'notes' => 'Mesin jahit industri high-speed unit 12 lini produksi',
            ]
        );

        // 2. Aset Meja Print (Dari Excel Sheet 3 Row 19)
        PurchasingAsset::updateOrCreate(
            ['asset_code' => 'FRN.PRD.026.001'],
            [
                'category_code' => 'FRN',
                'asset_category_id' => $assetCatIds['FRN'] ?? null,
                'department_code' => 'PRD',
                'year_code' => '026',
                'sequence_number' => 1,
                'registration_stage' => 'COMPLETED',
                'order_id' => $poMeja?->id,
                'asset_name' => 'MEJA PRINT TS100 ERGONOMIS',
                'specification' => 'Meja khusus printer sublime rangka besi hollow tebal',
                'unit' => 'Unit',
                'quantity' => 1,
                'location_id' => $larasLirisId,
                'department' => 'Produksi',
                'division' => 'Setting Printing',
                'position' => 'Setting Printing',
                'vendor_id' => $vBudi?->id,
                'supplier_name' => $vBudi?->name,
                'purchase_date' => '2026-03-22',
                'received_date' => '2026-03-24',
                'acquisition_cost' => 3285037.00,
                'warranty_duration' => '6 Bulan',
                'warranty_expires_at' => now()->addMonths(3)->toDateString(),
                'serial_number' => 'FUR-PRD-014',
                'barcode_qr_code' => 'FRN.PRD.026.001',
                'status' => 'ACTIVE',
            ]
        );

        // 3. Aset Laptop HCM
        PurchasingAsset::updateOrCreate(
            ['asset_code' => 'IT.HCM.026.001'],
            [
                'category_code' => 'IT',
                'asset_category_id' => $assetCatIds['IT'] ?? null,
                'department_code' => 'HCM',
                'year_code' => '026',
                'sequence_number' => 1,
                'registration_stage' => 'COMPLETED',
                'order_id' => $poLaptop?->id,
                'asset_name' => 'Laptop Lenovo ThinkPad T14 Gen 4',
                'specification' => 'Intel Core i7-1355U / 16GB RAM / 512GB SSD',
                'unit' => 'Unit',
                'quantity' => 1,
                'location_id' => $greenHcmId,
                'department' => 'Human Capital Management',
                'division' => 'HCM',
                'position' => 'Admin HCM',
                'vendor_id' => $vElektronik?->id,
                'supplier_name' => $vElektronik?->name,
                'purchase_date' => '2026-03-25',
                'received_date' => '2026-03-26',
                'acquisition_cost' => 16500000.00,
                'warranty_duration' => '36 Bulan',
                'warranty_expires_at' => now()->addMonths(24)->toDateString(),
                'serial_number' => 'PF-4X889K-TP14',
                'barcode_qr_code' => 'IT.HCM.026.001',
                'status' => 'ACTIVE',
            ]
        );

        // 4. Aset PC Accounting Finance
        PurchasingAsset::updateOrCreate(
            ['asset_code' => 'IT.FIN.026.001'],
            [
                'category_code' => 'IT',
                'asset_category_id' => $assetCatIds['IT'] ?? null,
                'department_code' => 'FIN',
                'year_code' => '026',
                'sequence_number' => 1,
                'registration_stage' => 'COMPLETED',
                'asset_name' => 'PC All-in-One HP Pavilion 27 inch',
                'specification' => 'Intel Core i7-13700T / 16GB RAM / 1TB SSD / Win 11 Home',
                'unit' => 'Unit',
                'quantity' => 1,
                'location_id' => $greenAdminId,
                'department' => 'Finance & Accounting',
                'division' => 'Accounting',
                'position' => 'Accounting',
                'vendor_id' => $vElektronik?->id,
                'supplier_name' => $vElektronik?->name,
                'purchase_date' => '2026-02-10',
                'received_date' => '2026-02-12',
                'acquisition_cost' => 14500000.00,
                'warranty_duration' => '12 Bulan',
                'warranty_expires_at' => now()->addDays(5)->toDateString(), // H-5 Garansi habis (Amber Alert!)
                'serial_number' => 'HP-AIO-27-00918',
                'barcode_qr_code' => 'IT.FIN.026.001',
                'status' => 'ACTIVE',
            ]
        );

        // 5. Aset Kamera Media Internal
        PurchasingAsset::updateOrCreate(
            ['asset_code' => 'MDM.MIN.026.001'],
            [
                'category_code' => 'MDM',
                'asset_category_id' => $assetCatIds['MDM'] ?? null,
                'department_code' => 'MIN',
                'year_code' => '026',
                'sequence_number' => 1,
                'registration_stage' => 'COMPLETED',
                'asset_name' => 'Kamera Sony Alpha A7 IV Kit 24-70mm GM',
                'specification' => 'Full-frame Mirrorless 33MP 4K60p + Lensa FE 24-70mm F2.8',
                'unit' => 'Unit',
                'quantity' => 1,
                'location_id' => $walisongoId,
                'department' => 'Media Internal',
                'division' => 'Media Internal',
                'position' => 'Media Spesialist',
                'vendor_id' => $vElektronik?->id,
                'supplier_name' => $vElektronik?->name,
                'purchase_date' => '2026-01-15',
                'received_date' => '2026-01-16',
                'acquisition_cost' => 38000000.00,
                'warranty_duration' => '24 Bulan',
                'warranty_expires_at' => now()->addMonths(18)->toDateString(),
                'serial_number' => 'SN-SONY-A7IV-77821',
                'barcode_qr_code' => 'MDM.MIN.026.001',
                'status' => 'ACTIVE',
            ]
        );

        // 6. Aset yang Dimutasi: IT.HCM.026.002 -> IT.FIN.026.002
        $mutatedAsset = PurchasingAsset::updateOrCreate(
            ['asset_code' => 'IT.FIN.026.002'],
            [
                'category_code' => 'IT',
                'asset_category_id' => $assetCatIds['IT'] ?? null,
                'department_code' => 'FIN',
                'year_code' => '026',
                'sequence_number' => 2,
                'registration_stage' => 'COMPLETED',
                'asset_name' => 'Laptop Dell Latitude 5430',
                'specification' => 'Intel Core i5-1245U / 16GB RAM / 256GB SSD',
                'unit' => 'Unit',
                'quantity' => 1,
                'location_id' => $greenAdminId,
                'department' => 'Finance & Accounting',
                'division' => 'Finance',
                'position' => 'Finance',
                'purchase_date' => '2026-01-10',
                'received_date' => '2026-01-12',
                'acquisition_cost' => 12500000.00,
                'status' => 'ACTIVE',
            ]
        );

        PurchasingAssetMutation::firstOrCreate(
            ['new_asset_code' => 'IT.FIN.026.002'],
            [
                'asset_id' => $mutatedAsset->id,
                'old_asset_code' => 'IT.HCM.026.002',
                'new_asset_code' => 'IT.FIN.026.002',
                'from_department_code' => 'HCM',
                'to_department_code' => 'FIN',
                'from_department_name' => 'Human Capital Management',
                'to_department_name' => 'Finance & Accounting',
                'mutation_date' => '2026-03-01',
                'notes' => 'Aset laptop dipindahkan dari staff rekrutmen ke tim kasir finance',
            ]
        );

        // 7. Aset Retirement (Dari Excel Sheet 3 Row 24): Mesin Potong Rusak Dijual
        $retiredAsset1 = PurchasingAsset::updateOrCreate(
            ['asset_code' => 'MSN.PRD.025.013'],
            [
                'category_code' => 'MSN',
                'asset_category_id' => $assetCatIds['MSN'] ?? null,
                'department_code' => 'PRD',
                'year_code' => '025',
                'sequence_number' => 13,
                'registration_stage' => 'COMPLETED',
                'asset_name' => 'MESIN POTONG BESAR LAMA',
                'specification' => 'MESIN POTONG KAIN SERI TAHUN 2024',
                'unit' => 'Unit',
                'quantity' => 1,
                'location_id' => $larasLirisId,
                'department' => 'Produksi',
                'division' => 'Potong Bahan',
                'position' => 'Potong Bahan',
                'purchase_date' => '2024-05-10',
                'acquisition_cost' => 3297036.00,
                'status' => 'RETIRED',
            ]
        );

        PurchasingAssetRetirement::firstOrCreate(
            ['asset_id' => $retiredAsset1->id],
            [
                'retired_at' => '2026-08-28',
                'department' => 'Produksi',
                'division' => 'Potong Bahan',
                'position' => 'Potong Bahan',
                'location_id' => $larasLirisId,
                'retirement_reason' => 'Rusak Berat (Tidak Layak Pakai)',
                'final_condition' => 'Tidak Layak Pakai / Scrap',
                'disposal_method' => 'Dijual / Pelepasan Komersial',
                'book_value' => 3297036.00,
                'disposal_price' => 2297036.00,
                'notes' => 'Dijual ke pihak ketiga karena motor penggerak terbakar',
            ]
        );
    }
}
