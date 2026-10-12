<?php

namespace Database\Seeders\Purchasing;

use App\Models\Purchasing\PurchasingMaterialStock;
use App\Models\Purchasing\PurchasingMaterialUsage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PurchasingMaterialStockSeeder extends Seeder
{
    public function run(): void
    {
        $stocks = [
            // 1. KAIN PUTIH (Inventory Tracking dengan Minimum Threshold)
            [
                'code' => 'MAT-KP-001',
                'name' => 'Milano Putih Polos',
                'material_type' => 'KAIN_PUTIH',
                'unit' => 'Roll',
                'minimum_threshold' => 5,
                'current_stock' => 12,
            ],
            [
                'code' => 'MAT-KP-002',
                'name' => 'Serena Putih',
                'material_type' => 'KAIN_PUTIH',
                'unit' => 'Roll',
                'minimum_threshold' => 4,
                'current_stock' => 8,
            ],
            [
                'code' => 'MAT-KP-003',
                'name' => 'Dryfit Bintik Putih',
                'material_type' => 'KAIN_PUTIH',
                'unit' => 'Roll',
                'minimum_threshold' => 5,
                'current_stock' => 3, // Kritis (Trigger Red Alert)
            ],
            [
                'code' => 'MAT-KP-004',
                'name' => 'Nike / Pique Putih',
                'material_type' => 'KAIN_PUTIH',
                'unit' => 'Roll',
                'minimum_threshold' => 3,
                'current_stock' => 6,
            ],
            [
                'code' => 'MAT-KP-005',
                'name' => 'Kain Putih TC (Gudang Bahan Utama)',
                'material_type' => 'KAIN_PUTIH',
                'unit' => 'Roll',
                'minimum_threshold' => 10,
                'current_stock' => 8, // Sisa 8 Roll (Batas Min: 10 Roll) -> Segera Beli!
            ],

            // 2. KAIN WARNA (Inbound Purchase Log & Project Distribution)
            [
                'code' => 'MAT-KW-001',
                'name' => 'Kain Warna Airwalk Abu Tua',
                'material_type' => 'KAIN_WARNA',
                'unit' => 'Kg',
                'minimum_threshold' => 10,
                'current_stock' => 45,
            ],
            [
                'code' => 'MAT-KW-002',
                'name' => 'Kain Warna Milano Navy',
                'material_type' => 'KAIN_WARNA',
                'unit' => 'Kg',
                'minimum_threshold' => 10,
                'current_stock' => 30,
            ],
            [
                'code' => 'MAT-KW-003',
                'name' => 'Kain Warna Serena Hitam Pekat',
                'material_type' => 'KAIN_WARNA',
                'unit' => 'Kg',
                'minimum_threshold' => 15,
                'current_stock' => 50,
            ],
            [
                'code' => 'MAT-KW-004',
                'name' => 'Kain Warna Milano Merah Cabe',
                'material_type' => 'KAIN_WARNA',
                'unit' => 'Kg',
                'minimum_threshold' => 10,
                'current_stock' => 8, // Kritis
            ],

            // 3. KERTAS PRINTING (Media Cetak)
            [
                'code' => 'MAT-KRT-001',
                'name' => 'Kertas Sublime Roll 100gr 1.6m',
                'material_type' => 'KERTAS',
                'unit' => 'Roll',
                'minimum_threshold' => 6,
                'current_stock' => 14,
            ],
            [
                'code' => 'MAT-KRT-002',
                'name' => 'Kertas Sublime Roll 70gr 1.6m',
                'material_type' => 'KERTAS',
                'unit' => 'Roll',
                'minimum_threshold' => 5,
                'current_stock' => 10,
            ],
            [
                'code' => 'MAT-KRT-003',
                'name' => 'Kertas Proteksi / Kraft Roll 1.6m',
                'material_type' => 'KERTAS',
                'unit' => 'Roll',
                'minimum_threshold' => 4,
                'current_stock' => 2, // Kritis
            ],

            // 4. TINTA PRINTING (8 Varian Tinta & Cairan Maintenance)
            [
                'code' => 'MAT-TNT-001',
                'name' => 'Tinta Sublime Cyan (C)',
                'material_type' => 'TINTA',
                'unit' => 'Botol',
                'minimum_threshold' => 4,
                'current_stock' => 12,
            ],
            [
                'code' => 'MAT-TNT-002',
                'name' => 'Tinta Sublime Magenta (M)',
                'material_type' => 'TINTA',
                'unit' => 'Botol',
                'minimum_threshold' => 4,
                'current_stock' => 10,
            ],
            [
                'code' => 'MAT-TNT-003',
                'name' => 'Tinta Sublime Yellow (Y)',
                'material_type' => 'TINTA',
                'unit' => 'Botol',
                'minimum_threshold' => 4,
                'current_stock' => 11,
            ],
            [
                'code' => 'MAT-TNT-004',
                'name' => 'Tinta Sublime Black (K)',
                'material_type' => 'TINTA',
                'unit' => 'Botol',
                'minimum_threshold' => 6,
                'current_stock' => 5, // Kritis
            ],
            [
                'code' => 'MAT-TNT-005',
                'name' => 'Tinta Sublime Fluorescent Pink (FP)',
                'material_type' => 'TINTA',
                'unit' => 'Botol',
                'minimum_threshold' => 2,
                'current_stock' => 4,
            ],
            [
                'code' => 'MAT-TNT-006',
                'name' => 'Tinta Sublime Fluorescent Yellow (FY)',
                'material_type' => 'TINTA',
                'unit' => 'Botol',
                'minimum_threshold' => 2,
                'current_stock' => 3,
            ],
            [
                'code' => 'MAT-TNT-007',
                'name' => 'Cairan Pembersih Head (Cleaning Solution)',
                'material_type' => 'TINTA',
                'unit' => 'Botol',
                'minimum_threshold' => 3,
                'current_stock' => 6,
            ],
        ];

        foreach ($stocks as $item) {
            $created = PurchasingMaterialStock::updateOrCreate(
                ['code' => $item['code']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $item['name'],
                    'material_type' => $item['material_type'],
                    'unit' => $item['unit'],
                    'minimum_threshold' => $item['minimum_threshold'],
                    'current_stock' => $item['current_stock'],
                ]
            );

            // Tambahkan 1 transaksi awal (Inbound) sebagai saldo pembuka
            if ($created->wasRecentlyCreated || $created->usages()->count() === 0) {
                PurchasingMaterialUsage::create([
                    'uuid' => (string) Str::uuid(),
                    'material_stock_id' => $created->id,
                    'usage_date' => now()->subDays(5)->toDateString(),
                    'material_type' => $created->material_type,
                    'material_name' => $created->name,
                    'unit' => $created->unit,
                    'quantity_in' => $created->current_stock,
                    'quantity_out' => 0,
                    'balance_stock' => $created->current_stock,
                    'department' => 'Produksi',
                    'division' => 'Produksi',
                    'operator_name' => 'Admin Gudang',
                    'production_ref' => 'INBOUND-AWAL',
                    'notes' => 'Pencatatan saldo pembuka master stok',
                ]);
            }
        }
    }
}
