<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Brand;
use App\Models\Master\BankAccount;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Loop through all brands and ensure each has a CASH bank account
        foreach (Brand::all() as $brand) {
            $hasCash = BankAccount::where('brand_id', $brand->id)
                ->where('bank', 'CASH')
                ->exists();

            if (!$hasCash) {
                BankAccount::create([
                    'brand_id' => $brand->id,
                    'bank' => 'CASH',
                    'atas_nama' => 'Cash',
                    'nomor_rekening' => 'CASH',
                    'is_active' => true,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We can optionally delete the seeded CASH bank accounts, but typically keep them
    }
};
