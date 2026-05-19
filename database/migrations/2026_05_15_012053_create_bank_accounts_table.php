<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('brand_id')->nullable()->constrained('brands')->cascadeOnDelete();
            $table->string('bank', 100);
            $table->string('atas_nama', 255);
            $table->string('nomor_rekening', 50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('brand_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
