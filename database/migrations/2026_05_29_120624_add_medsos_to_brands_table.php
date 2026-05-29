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
        Schema::table('brands', function (Blueprint $table) {
            $table->string('instagram', 100)->nullable()->after('alamat');
            $table->string('facebook', 100)->nullable()->after('instagram');
            $table->string('tiktok', 100)->nullable()->after('facebook');
            $table->string('whatsapp', 20)->nullable()->after('tiktok');
            $table->string('website', 255)->nullable()->after('whatsapp');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn(['instagram', 'facebook', 'tiktok', 'whatsapp', 'website']);
        });
    }
};
