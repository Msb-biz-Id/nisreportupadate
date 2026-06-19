<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            BrandSeeder::class,
            UserSeeder::class,
            MasterDataSeeder::class,
            FinanceSeeder::class,
            CustomerSeeder::class,
            OrderSeeder::class,
        ]);
    }
}
