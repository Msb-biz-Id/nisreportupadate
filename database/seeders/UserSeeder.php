<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'password123';

    public function run(): void
    {
        $brands = Brand::whereIn('kode', ['ALG', 'CRL', 'DRV', 'TLS', 'IDW'])
            ->get()
            ->keyBy('kode');

        // Helper update/create user + role + brand assignment
        $seedUser = function (string $email, string $name, string $phone, array $roles, array $brandKodes) use ($brands) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make(self::DEFAULT_PASSWORD),
                    'phone' => $phone,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles($roles);

            $syncData = [];
            foreach ($brandKodes as $index => $kode) {
                $brand = $brands->get($kode);
                if ($brand) {
                    $syncData[$brand->id] = [
                        'is_default' => $index === 0,
                        'assigned_at' => now(),
                    ];
                }
            }
            if (!empty($syncData)) {
                $user->brands()->syncWithoutDetaching($syncData);
            }

            return $user;
        };

        // 1. Superadmin (akses semua brand)
        $seedUser('itidwarehouse@gmail.com', 'Super Administrator', '081111111111', ['superadmin'], ['ALG', 'CRL', 'DRV']);

        // 2. Owner
        $seedUser('owner@nisreport.local', 'Pemilik Bisnis', '081222222222', ['owner'], ['ALG', 'CRL', 'DRV']);

        // 3. Admin Brand ALG (Apparel Allegiant)
        $seedUser('allegiant.id@gmail.com', 'Admin Brand Allegiant', '08113027007', ['admin_brand'], ['ALG']);

        // 4. Admin Brand CRL (Circle Sportwear)
        $seedUser('circlesportwear@gmail.com', 'Admin Brand Circle', '082139220211', ['admin_brand'], ['CRL']);

        // 5. Admin Brand DRV (Drive Sportwear)
        $seedUser('sportweardrive@gmail.com', 'Admin Brand Drive', '085143849390', ['admin_brand'], ['DRV']);

        // 6. Admin Reseller demo — INDOWAREHOUSE (IDW)
        if ($brands->has('IDW')) {
            $seedUser('indonesiasportwarehouse@gmail.com', 'INDOWAREHOUSE', '62 858-5027-3293', ['admin_reseller'], ['IDW']);
        }

        // 7. PIC Produksi
        $seedUser('produksi.nisgroup@gmail.com', 'PIC Produksi', '081666666666', ['admin_produksi'], ['ALG']);

        // 8. Admin Produksi
        $seedUser('adminproduksi.nisgroup@gmail.com', 'Admin Produksi', '081666666667', ['admin_produksi'], ['ALG']);

        // 9. Keuangan
        $seedUser('keuangan.nisgroup@gmail.com', 'Keuangan', '081777777777', ['admin_keuangan'], ['ALG', 'CRL', 'DRV']);

        // 10. Finance
        $seedUser('finance.nisgroup@gmail.com', 'Finance', '081777777778', ['admin_keuangan'], ['ALG', 'CRL', 'DRV']);

        // 11. Supervisor
        $seedUser('supervisor.nisgroup@gmail.com', 'Supervisor Utama', '081999999999', ['supervisor'], ['ALG', 'CRL', 'DRV']);

        $this->command->info('Akun seeder siap — semua password: ' . self::DEFAULT_PASSWORD);
    }
}
