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
            // MultiFontTestSeeder::class,
        ]);
    }
}
