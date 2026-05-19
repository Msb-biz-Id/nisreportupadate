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
        $brandShu = Brand::where('kode', 'SHU')->first();
        $brandNis = Brand::where('kode', 'NIS')->first();

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
            $brandShu->id => ['is_default' => true, 'assigned_at' => now()],
            $brandNis->id => ['is_default' => false, 'assigned_at' => now()],
        ]);

        // Owner multi-brand (akses SHU & NIS, default SHU)
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
            $brandShu->id => ['is_default' => true, 'assigned_at' => now(), 'assigned_by' => $super->id],
            $brandNis->id => ['is_default' => false, 'assigned_at' => now(), 'assigned_by' => $super->id],
        ]);

        // Admin Brand SHU
        $adminShu = User::updateOrCreate(
            ['email' => 'admin.shu@nisreport.local'],
            [
                'name' => 'Admin Brand Shubuh',
                'password' => Hash::make('password'),
                'phone' => '081333333333',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $adminShu->syncRoles(['admin_brand']);
        $adminShu->brands()->syncWithoutDetaching([
            $brandShu->id => ['is_default' => true, 'assigned_at' => now(), 'assigned_by' => $super->id],
        ]);

        // Admin Brand NIS
        $adminNis = User::updateOrCreate(
            ['email' => 'admin.nis@nisreport.local'],
            [
                'name' => 'Admin Brand Nisha',
                'password' => Hash::make('password'),
                'phone' => '081444444444',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $adminNis->syncRoles(['admin_brand']);
        $adminNis->brands()->syncWithoutDetaching([
            $brandNis->id => ['is_default' => true, 'assigned_at' => now(), 'assigned_by' => $super->id],
        ]);

        // Reseller (terhubung ke salah satu brand; di Phase berikutnya master data reseller global)
        $reseller = User::updateOrCreate(
            ['email' => 'reseller@nisreport.local'],
            [
                'name' => 'Reseller Demo',
                'password' => Hash::make('password'),
                'phone' => '081555555555',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $reseller->syncRoles(['reseller']);
        $reseller->brands()->syncWithoutDetaching([
            $brandShu->id => ['is_default' => true, 'assigned_at' => now(), 'assigned_by' => $super->id],
        ]);

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
            $brandShu->id => ['is_default' => true, 'assigned_at' => now(), 'assigned_by' => $super->id],
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
            $brandShu->id => ['is_default' => true, 'assigned_at' => now(), 'assigned_by' => $super->id],
            $brandNis->id => ['is_default' => false, 'assigned_at' => now(), 'assigned_by' => $super->id],
        ]);
    }
}
