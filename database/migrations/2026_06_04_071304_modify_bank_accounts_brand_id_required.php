<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, assign any bank accounts with NULL brand_id to the first brand if it exists
        $firstBrand = \Illuminate\Support\Facades\DB::table('brands')->first();
        if ($firstBrand) {
            \Illuminate\Support\Facades\DB::table('bank_accounts')
                ->whereNull('brand_id')
                ->update(['brand_id' => $firstBrand->id]);
        } else {
            // If no brands exist, delete them to avoid foreign key failure
            \Illuminate\Support\Facades\DB::table('bank_accounts')
                ->whereNull('brand_id')
                ->delete();
        }

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->foreignUuid('brand_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->foreignUuid('brand_id')->nullable()->change();
        });
    }
};
