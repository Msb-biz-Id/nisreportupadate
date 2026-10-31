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
        Schema::create('purchasing_vendors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('vendor_code', 50)->unique();
            $table->string('name', 150)->unique();
            $table->string('category', 50)->comment('e_commerce, offline, contract, software');
            $table->string('item_category', 100)->nullable();
            $table->string('item_name', 150)->nullable();
            $table->text('specification')->nullable();
            $table->text('address')->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_no', 100)->nullable();
            $table->string('bank_account_holder', 100)->nullable();
            $table->unsignedInteger('default_top_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchasing_vendor_contacts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vendor_id')->constrained('purchasing_vendors')->cascadeOnDelete();
            $table->string('pic_name', 100);
            $table->string('role_title', 100)->nullable();
            $table->string('phone', 50);
            $table->string('email', 100)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'is_primary'], 'idx_pvc_vendor_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchasing_vendor_contacts');
        Schema::dropIfExists('purchasing_vendors');
    }
};
