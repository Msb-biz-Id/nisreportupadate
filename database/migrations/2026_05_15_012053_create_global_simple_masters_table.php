<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master data global yang strukturnya seragam (nama + deskripsi + is_active).
     * Dipakai oleh: Bahan Kain, Logo, Resleting, Printing, Paket Order, Tipe Order.
     */
    private array $simpleTables = [
        'bahan_kains',
        'logos',
        'resletings',
        'printings',
        'paket_orders',
        'tipe_orders',
    ];

    public function up(): void
    {
        foreach ($this->simpleTables as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('nama', 100);
                $table->text('deskripsi')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index('is_active');
                $table->index('nama');
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->simpleTables) as $name) {
            Schema::dropIfExists($name);
        }
    }
};
