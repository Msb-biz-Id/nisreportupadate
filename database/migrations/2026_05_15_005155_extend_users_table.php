<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('avatar');
            $table->uuid('last_brand_id')->nullable()->after('is_active');
            $table->timestamp('last_login_at')->nullable()->after('last_brand_id');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->boolean('two_factor_enabled')->default(false)->after('last_login_ip');
            $table->string('two_factor_secret')->nullable()->after('two_factor_enabled');

            $table->index('is_active');
            $table->index('last_brand_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['last_brand_id']);
            $table->dropColumn([
                'phone', 'avatar', 'is_active', 'last_brand_id',
                'last_login_at', 'last_login_ip',
                'two_factor_enabled', 'two_factor_secret',
            ]);
        });
    }
};
