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
        $brandAlg = Brand::where('kode', 'ALG')->first();
        $brandCrl = Brand::where('kode', 'CRL')->first();
        $brandDrv = Brand::where('kode', 'DRV')->first();
        // Demo reseller account — pakai Telulas (TLS) sebagai contoh
        $brandTelulas = Brand::where('kode', 'TLS')->first();

        // Superadmin (akses semua brand)
        $super = User::updateOrCreate(
            ['email' => 'superadmin@nisreport.local'],
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
            ['email' => 'admin.allegiant@nisreport.local'],
            [
                'name' => 'Admin Brand Allegiant',
                'password' => Hash::make('password'),
                'phone' => '081333333333',
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
            ['email' => 'admin.circle@nisreport.local'],
            [
                'name' => 'Admin Brand Circle',
                'password' => Hash::make('password'),
                'phone' => '081444444444',
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
            ['email' => 'admin.drive@nisreport.local'],
            [
                'name' => 'Admin Brand Drive',
                'password' => Hash::make('password'),
                'phone' => '081888888888',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $adminDrv->syncRoles(['admin_brand']);
        $adminDrv->brands()->syncWithoutDetaching([
            $brandDrv->id => ['is_default' => true, 'assigned_at' => now(), 'assigned_by' => $super->id],
        ]);

        // Admin Reseller demo — diarahkan ke brand Telulas (TLS)
        if ($brandTelulas) {
            $adminReseller = User::updateOrCreate(
                ['email' => 'reseller@nisreport.local'],
                [
                    'name' => 'Admin Reseller (Demo)',
                    'password' => Hash::make('password'),
                    'phone' => '081555555555',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $adminReseller->syncRoles(['admin_reseller']);
            $adminReseller->brands()->sync([
                $brandTelulas->id => ['is_default' => true, 'assigned_at' => now(), 'assigned_by' => $super->id],
            ]);
        }

        // Admin Produksi
        $produksi = User::updateOrCreate(
            ['email' => 'produksi@nisreport.local'],
            [
                'name' => 'Admin Produksi',
                'password' => Hash::make('password'),
                'phone' => '081666666666',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $produksi->syncRoles(['admin_produksi']);
        $produksi->brands()->syncWithoutDetaching([
            $brandAlg->id => ['is_default' => true, 'assigned_at' => now(), 'assigned_by' => $super->id],
        ]);

        // Admin Keuangan
        $keuangan = User::updateOrCreate(
            ['email' => 'keuangan@nisreport.local'],
            [
                'name' => 'Admin Keuangan',
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

        // Supervisor (Akses multi-brand untuk audit/unlock PO)
        $supervisor = User::updateOrCreate(
            ['email' => 'supervisor@nisreport.local'],
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
