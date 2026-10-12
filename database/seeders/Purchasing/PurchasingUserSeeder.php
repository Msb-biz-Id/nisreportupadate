<?php

namespace Database\Seeders\Purchasing;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class PurchasingUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan role admin_purchasing dan staff_purchasing tersedia
        Role::firstOrCreate(['name' => 'admin_purchasing', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'staff_purchasing', 'guard_name' => 'web']);

        $brands = Brand::all();

        // 1. Akun Admin Utama Purchasing
        $adminPurchasing = User::updateOrCreate(
            ['email' => 'purchasing@nisgroup.id'],
            [
                'name' => 'Admin Purchasing NIS',
                'password' => Hash::make('password123'),
                'phone' => '081234567899',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $adminPurchasing->syncRoles(['admin_purchasing']);

        // 2. Akun Staff Purchasing
        $staffPurchasing = User::updateOrCreate(
            ['email' => 'staff.purchasing@nisgroup.id'],
            [
                'name' => 'Staff Purchasing NIS',
                'password' => Hash::make('password123'),
                'phone' => '081234567898',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $staffPurchasing->syncRoles(['staff_purchasing']);

        // Berikan akses brand aktif agar context brand dashboard tersedia
        if ($brands->isNotEmpty()) {
            $brandSync = [];
            foreach ($brands as $index => $brand) {
                $brandSync[$brand->id] = [
                    'is_default' => $index === 0,
                    'assigned_at' => now(),
                ];
            }
            $adminPurchasing->brands()->sync($brandSync);
            $staffPurchasing->brands()->sync($brandSync);
        }

        $this->command?->info("Akun Purchasing berhasil dibuat / diperbarui: purchasing@nisgroup.id (Password: password)");
    }
}
