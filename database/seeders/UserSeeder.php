<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $brandAlg = Brand::where(['kode' => 'ALG'])->first();
        $brandCrl = Brand::where(['kode' => 'CRL'])->first();
        $brandDrv = Brand::where(['kode' => 'DRV'])->first();
        $brandTelulas = Brand::where(['kode' => 'TLS'])->first();

        // Normalisasi superadmin jika terdapat variasi casing (ITidwarehouse vs itidwarehouse)
        $superUsers = User::whereRaw('LOWER(email) = ?', ['itidwarehouse@gmail.com'])->orderBy('id')->get();
        if ($superUsers->count() > 1) {
            $primarySuper = $superUsers->first();
            $duplicates = $superUsers->slice(1);
            foreach ($duplicates as $duplicate) {
                \Illuminate\Support\Facades\DB::table('activity_logs')->where('user_id', $duplicate->id)->update(['user_id' => $primarySuper->id]);
                \Illuminate\Support\Facades\DB::table('notifications')->where('notifiable_id', $duplicate->id)->where('notifiable_type', User::class)->update(['notifiable_id' => $primarySuper->id]);
                $duplicate->delete();
            }
            $primarySuper->email = 'itidwarehouse@gmail.com';
            $primarySuper->save();
        }

        $allBrands = Brand::all();

        // Helper to update/create user and track password
        $seedUser = function(string $email, string $name, string $phone, array $roles, $brands, string $roleLabel, string $password = 'password123') use (&$credentials) {
            $user = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
            if ($user) {
                $user->update([
                    'name' => $name,
                    'email' => strtolower($email),
                    'password' => Hash::make($password),
                    'phone' => $phone,
                    'is_active' => true,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            } else {
                $user = User::create([
                    'email' => strtolower($email),
                    'name' => $name,
                    'password' => Hash::make($password),
                    'phone' => $phone,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
            }
            $user->syncRoles($roles);
            
            $syncData = [];
            foreach ($brands as $index => $brand) {
                if ($brand) {
                    $syncData[$brand->id] = [
                        'is_default' => $index === 0,
                        'assigned_at' => now(),
                    ];
                }
            }
            if (!empty($syncData)) {
                $user->brands()->sync($syncData);
            }

            $credentials[] = [
                'role' => $roleLabel,
                'name' => $name,
                'email' => strtolower($email),
                'password' => $password,
            ];

            return $user;
        };

        // 1. Superadmin (akses semua brand)
        $super = $seedUser('itidwarehouse@gmail.com', 'Super Administrator', '081111111111', ['superadmin'], $allBrands, 'Superadmin');

        // 2. Owner
        $seedUser('owner@nisreport.local', 'Pemilik Bisnis', '081222222222', ['owner'], [$brandAlg, $brandCrl, $brandDrv], 'Owner');

        // 3. Admin Brand ALG (Apparel Allegiant)
        $seedUser('allegiant.id@gmail.com', 'Admin Brand Allegiant', '08113027007', ['admin_brand'], [$brandAlg], 'Admin Brand Allegiant');

        // 4. Admin Brand CRL (Circle Sportwear)
        $seedUser('circlesportwear@gmail.com', 'Admin Brand Circle', '082139220211', ['admin_brand'], [$brandCrl], 'Admin Brand Circle');

        // 5. Admin Brand DRV (Drive Sportwear)
        $seedUser('sportweardrive@gmail.com', 'Admin Brand Drive', '085143849390', ['admin_brand'], [$brandDrv], 'Admin Brand Drive');

        // 6. Admin Reseller demo — INDOWAREHOUSE (IDW)
        $brandIdw = Brand::where(['kode' => 'IDW'])->first();
        if ($brandIdw) {
            $seedUser('indonesiasportwarehouse@gmail.com', 'INDOWAREHOUSE', '62 858-5027-3293', ['admin_reseller'], [$brandIdw], 'Admin Reseller');
        }

        // 7. PIC Produksi
        $seedUser('produksi.nisgroup@gmail.com', 'PIC Produksi', '081666666666', ['admin_produksi'], [$brandAlg], 'PIC Produksi');

        // 8. Admin Produksi
        $seedUser('adminproduksi.nisgroup@gmail.com', 'Admin Produksi', '081666666667', ['admin_produksi'], [$brandAlg], 'Admin Produksi');

        // 9. Keuangan
        $seedUser('keuangan.nisgroup@gmail.com', 'Keuangan', '081777777777', ['admin_keuangan'], [$brandAlg, $brandCrl, $brandDrv], 'Keuangan');

        // 10. Finance
        $seedUser('finance.nisgroup@gmail.com', 'Finance', '081777777778', ['admin_keuangan'], [$brandAlg, $brandCrl, $brandDrv], 'Finance');

        // 11. Supervisor
        $seedUser('supervisor.nisgroup@gmail.com', 'Supervisor Utama', '081999999999', ['supervisor'], [$brandAlg, $brandCrl, $brandDrv], 'Supervisor');

        // Save to file
        $recapFile = base_path('credentials_recap.md');
        $content = "# Rekapitulasi Kredensial User (Hasil Seeder)\n\n";
        $content .= "Dibuat pada: " . Carbon::now()->toDateTimeString() . " (Waktu Lokal)\n\n";
        $content .= "| Peran / Role | Nama | Email | Kata Sandi (Password) |\n";
        $content .= "| --- | --- | --- | --- |\n";
        foreach ($credentials as $cred) {
            $content .= "| {$cred['role']} | {$cred['name']} | `{$cred['email']}` | `{$cred['password']}` |\n";
        }
        
        file_put_contents($recapFile, $content);
        $this->command?->info("Kredensial dev disimpan ke: " . $recapFile);
    }
}
