<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class HcmUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan role admin_hcm dan staff_hcm tersedia
        $adminHcmRole = Role::firstOrCreate(['name' => 'admin_hcm']);
        $staffHcmRole = Role::firstOrCreate(['name' => 'staff_hcm']);

        $brands = Brand::all();

        // 1. Akun Admin Utama HCM
        $adminHcm = User::updateOrCreate(
            ['email' => 'hcm@nisgroup.id'],
            [
                'name' => 'Admin HCM NISGroup',
                'password' => Hash::make('password123'),
                'phone' => '081234567890',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $adminHcm->syncRoles(['admin_hcm']);

        // 2. Akun Staff HCM
        $staffHcm = User::updateOrCreate(
            ['email' => 'staff.hcm@nisgroup.id'],
            [
                'name' => 'Staff HR & Legalitas',
                'password' => Hash::make('password123'),
                'phone' => '081234567891',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $staffHcm->syncRoles(['staff_hcm']);

        // Berikan akses semua brand secara default agar dashboard tidak null
        if ($brands->isNotEmpty()) {
            $brandSync = [];
            foreach ($brands as $index => $brand) {
                $brandSync[$brand->id] = [
                    'is_default' => $index === 0,
                    'assigned_at' => now(),
                ];
            }
            $adminHcm->brands()->sync($brandSync);
            $staffHcm->brands()->sync($brandSync);
        }

        $this->command?->info("Akun HCM berhasil dibuat: hcm@nisgroup.id (Password: password123)");
    }
}
