<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_brand_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->boolean('is_default')->default(false);
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'brand_id']);
            $table->index('brand_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_brand_access');
    }
};
