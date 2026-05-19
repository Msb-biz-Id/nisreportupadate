<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master data yang terisolasi per brand (brand_id NULL = master global reseller).
     * Struktur: nama + deskripsi + is_active + brand_id nullable.
     */
    private array $tables = [
        'kategori_orders',
        'sumber_orders',
    ];

    public function up(): void
    {
        foreach ($this->tables as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('brand_id')->nullable()->constrained('brands')->cascadeOnDelete();
                $table->string('nama', 100);
                $table->text('deskripsi')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index('brand_id');
                $table->index('is_active');
            });
        }

        Schema::create('customer_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('brand_id')->nullable()->constrained('brands')->cascadeOnDelete();
            $table->string('nama', 100);
            $table->decimal('diskon_default', 5, 2)->default(0);
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('brand_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_types');
        foreach (array_reverse($this->tables) as $name) {
            Schema::dropIfExists($name);
        }
    }
};
