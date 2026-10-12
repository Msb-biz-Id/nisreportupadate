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
            EkspedisiSeeder::class,
            OrderSeeder::class,
            HcmMasterDataSeeder::class,
            HcmUserSeeder::class,
            HcmEmployeeSeeder::class,
            HcmAttendanceAndLeaveSeeder::class,
            HcmOvertimeSeeder::class,
            HcmMealAllowanceAndRewardSeeder::class,
            HcmRecruitmentAndDocumentSeeder::class,
            HcmCompanyEventSeeder::class,
            \Database\Seeders\Purchasing\PurchasingUserSeeder::class,
            \Database\Seeders\Purchasing\PurchasingMasterOptionSeeder::class,
            \Database\Seeders\Purchasing\PurchasingAssetCategorySeeder::class,
            \Database\Seeders\Purchasing\PurchasingAssetSuggestionSeeder::class,
            \Database\Seeders\Purchasing\PurchasingVendorSeeder::class,
            \Database\Seeders\Purchasing\PurchasingOrderSeeder::class,
            \Database\Seeders\Purchasing\PurchasingAssetSeeder::class,
            \Database\Seeders\Purchasing\PurchasingMaterialStockSeeder::class,
            // MultiFontTestSeeder::class,
        ]);
    }
}
