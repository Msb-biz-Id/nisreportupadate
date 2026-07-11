<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $brandAlg = Brand::where(['kode' => 'ALG'])->first();
        $brandCrl = Brand::where(['kode' => 'CRL'])->first();
        $brandDrv = Brand::where(['kode' => 'DRV'])->first();
        // Demo reseller account — pakai Telulas (TLS) sebagai contoh
        $brandTelulas = Brand::where(['kode' => 'TLS'])->first();

        // Superadmin (akses semua brand)
        $super = User::updateOrCreate(
            ['email' => 'itidwarehouse@gmail.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password'),
                'phone' => '081111111111',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $super->syncRoles(['superadmin']);
        $super->brands()->syncWithoutDetaching([
            $brandAlg->id => ['is_default' => true, 'assigned_at' => now()],
            $brandCrl->id => ['is_default' => false, 'assigned_at' => now()],
            $brandDrv->id => ['is_default' => false, 'assigned_at' => now()],
        ]);

        // Owner multi-brand (akses ALG, CRL, DRV, default ALG)
        $owner = User::updateOrCreate(
            ['email' => 'owner@nisreport.local'],
            [
                'name' => 'Pemilik Bisnis',
                'password' => Hash::make('password'),
                'phone' => '081222222222',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $owner->syncRoles(['owner']);
        $owner->brands()->syncWithoutDetaching([
            $brandAlg->id => ['is_default' => true, 'assigned_at' => now(), 'assigned_by' => $super->id],
            $brandCrl->id => ['is_default' => false, 'assigned_at' => now(), 'assigned_by' => $super->id],
            $brandDrv->id => ['is_default' => false, 'assigned_at' => now(), 'assigned_by' => $super->id],
        ]);

        // Admin Brand ALG (Apparel Allegiant)
        $adminAlg = User::updateOrCreate(
            ['email' => 'allegiant.id@gmail.com'],
            [
                'name' => 'Admin Brand Allegiant',
                'password' => Hash::make('password'),
                'phone' => '08113027007',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $adminAlg->syncRoles(['admin_brand']);
        $adminAlg->brands()->syncWithoutDetaching([
            $brandAlg->id => ['is_default' => true, 'assigned_at' => now(), 'assigned_by' => $super->id],
        ]);

        // Admin Brand CRL (Circle Sportwear)
        $adminCrl = User::updateOrCreate(
            ['email' => 'circlesportwear@gmail.com'],
            [
                'name' => 'Admin Brand Circle',
                'password' => Hash::make('password'),
                'phone' => '082139220211',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $adminCrl->syncRoles(['admin_brand']);
        $adminCrl->brands()->syncWithoutDetaching([
            $brandCrl->id => ['is_default' => true, 'assigned_at' => now(), 'assigned_by' => $super->id],
        ]);

        // Admin Brand DRV (Drive Sportwear)
        $adminDrv = User::updateOrCreate(
            ['email' => 'sportweardrive@gmail.com'],
            [
                'name' => 'Admin Brand Drive',
                'password' => Hash::make('password'),
                'phone' => '085143849390',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $adminDrv->syncRoles(['admin_brand']);
        $adminDrv->brands()->syncWithoutDetaching([
            $brandDrv->id => ['is_default' => true, 'assigned_at' => now(), 'assigned_by' => $super->id],
        ]);

        // Admin Reseller demo — diarahkan ke brand INDOWAREHOUSE (IDW)
        $brandIdw = Brand::where(['kode' => 'IDW'])->first();
        if ($brandIdw) {
            $adminReseller = User::updateOrCreate(
                ['email' => 'indonesiasportwarehouse@gmail.com'],
                [
                    'name' => 'INDOWAREHOUSE',
                    'password' => Hash::make('password'),
                    'phone' => '62 858-5027-3293',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $adminReseller->syncRoles(['admin_reseller']);
            $adminReseller->brands()->sync([
                $brandIdw->id => ['is_default' => true, 'assigned_at' => now(), 'assigned_by' => $super->id],
            ]);
        }


        // PIC Produksi
        $picProduksi = User::updateOrCreate(
            ['email' => 'produksi.nisgroup@gmail.com'],
            [
                'name' => 'PIC Produksi',
                'password' => Hash::make('password'),
                'phone' => '081666666666',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $picProduksi->syncRoles(['admin_produksi']);
        $picProduksi->brands()->syncWithoutDetaching([
            $brandAlg->id => ['is_default' => true, 'assigned_at' => now(), 'assigned_by' => $super->id],
        ]);

        // Admin Produksi
        $adminProduksi = User::updateOrCreate(
            ['email' => 'adminproduksi.nisgroup@gmail.com'],
            [
                'name' => 'Admin Produksi',
                'password' => Hash::make('password'),
                'phone' => '081666666667',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $adminProduksi->syncRoles(['admin_produksi']);
        $adminProduksi->brands()->syncWithoutDetaching([
            $brandAlg->id => ['is_default' => true, 'assigned_at' => now(), 'assigned_by' => $super->id],
        ]);

        // Keuangan
        $keuangan = User::updateOrCreate(
            ['email' => 'keuangan.nisgroup@gmail.com'],
            [
                'name' => 'Keuangan',
                'password' => Hash::make('password'),
                'phone' => '081777777777',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $keuangan->syncRoles(['admin_keuangan']);
        $keuangan->brands()->syncWithoutDetaching([
            $brandAlg->id => ['is_default' => true, 'assigned_at' => now(), 'assigned_by' => $super->id],
            $brandCrl->id => ['is_default' => false, 'assigned_at' => now(), 'assigned_by' => $super->id],
            $brandDrv->id => ['is_default' => false, 'assigned_at' => now(), 'assigned_by' => $super->id],
        ]);

        // Finance
        $finance = User::updateOrCreate(
            ['email' => 'finance.nisgroup@gmail.com'],
            [
                'name' => 'Finance',
                'password' => Hash::make('password'),
                'phone' => '081777777778',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $finance->syncRoles(['admin_keuangan']);
        $finance->brands()->syncWithoutDetaching([
            $brandAlg->id => ['is_default' => true, 'assigned_at' => now(), 'assigned_by' => $super->id],
            $brandCrl->id => ['is_default' => false, 'assigned_at' => now(), 'assigned_by' => $super->id],
            $brandDrv->id => ['is_default' => false, 'assigned_at' => now(), 'assigned_by' => $super->id],
        ]);

        // Supervisor (Akses multi-brand untuk audit/unlock PO)
        $supervisor = User::updateOrCreate(
            ['email' => 'supervisor.nisgroup@gmail.com'],
            [
                'name' => 'Supervisor Utama',
                'password' => Hash::make('password'),
                'phone' => '081999999999',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $supervisor->syncRoles(['supervisor']);
        $supervisor->brands()->syncWithoutDetaching([
            $brandAlg->id => ['is_default' => true, 'assigned_at' => now(), 'assigned_by' => $super->id],
            $brandCrl->id => ['is_default' => false, 'assigned_at' => now(), 'assigned_by' => $super->id],
            $brandDrv->id => ['is_default' => false, 'assigned_at' => now(), 'assigned_by' => $super->id],
        ]);
    }
}
