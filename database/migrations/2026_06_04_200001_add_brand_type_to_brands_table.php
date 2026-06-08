<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->string('brand_type', 20)->default('regular')->after('is_active');
            $table->uuid('parent_brand_id')->nullable()->after('brand_type');
            $table->foreign('parent_brand_id')->references('id')->on('brands')->nullOnDelete();
            $table->index('brand_type');
            $table->index('parent_brand_id');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropForeign(['parent_brand_id']);
            $table->dropIndex(['brand_type']);
            $table->dropIndex(['parent_brand_id']);
            $table->dropColumn(['brand_type', 'parent_brand_id']);
        });
    }
};
