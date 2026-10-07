<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Hcm\HcmMasterCategory;
use App\Models\Hcm\HcmMasterOption;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $category = HcmMasterCategory::firstOrCreate(
            ['code' => 'divisi'],
            [
                'name' => 'Divisi',
                'group' => 'Kepegawaian & Struktur',
                'icon' => 'Building2',
                'description' => 'Divisi atau unit departemen kerja perusahaan',
                'order_index' => 2,
                'is_active' => true,
            ]
        );

        $officialDepartments = [
            ['name' => 'Human Capital Management', 'code' => 'HCM', 'old_name' => 'Human Capital Management', 'order' => 1],
            ['name' => 'Finance & Accounting', 'code' => 'FIN', 'old_name' => 'Keuangan', 'order' => 2],
            ['name' => 'Brand & Marketing', 'code' => 'BRM', 'old_name' => 'Marketing', 'order' => 3],
            ['name' => 'Support & Control Produksi', 'code' => 'SCP', 'old_name' => null, 'order' => 4],
            ['name' => 'Produksi', 'code' => 'PRD', 'old_name' => 'Produksi', 'order' => 5],
            ['name' => 'Media Internal', 'code' => 'MIN', 'old_name' => 'Media Internal', 'order' => 6],
            ['name' => 'Media Eksternal', 'code' => 'MEX', 'old_name' => 'Media Eksternal', 'order' => 7],
        ];

        foreach ($officialDepartments as $dept) {
            // Check if old name exists in options
            $existingOption = null;
            if ($dept['old_name']) {
                $existingOption = HcmMasterOption::where('category_id', $category->id)
                    ->where('name', $dept['old_name'])
                    ->first();
            }

            if ($existingOption) {
                $existingOption->update([
                    'name' => $dept['name'],
                    'code' => $dept['code'],
                    'order_index' => $dept['order'],
                    'is_active' => true,
                ]);
            } else {
                HcmMasterOption::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'name' => $dept['name'],
                    ],
                    [
                        'code' => $dept['code'],
                        'order_index' => $dept['order'],
                        'is_active' => true,
                    ]
                );
            }

            // Sync legacy names across tables if name changed
            if ($dept['old_name'] && $dept['old_name'] !== $dept['name']) {
                $affectedTables = [
                    'hcm_employees',
                    'hcm_meal_allowance_items',
                    'hcm_job_postings',
                    'hcm_internal_documents',
                    'hcm_onboardings',
                ];

                foreach ($affectedTables as $table) {
                    if (Schema::hasTable($table) && Schema::hasColumn($table, 'department')) {
                        DB::table($table)
                            ->where('department', $dept['old_name'])
                            ->update(['department' => $dept['name']]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversible if needed
    }
};
