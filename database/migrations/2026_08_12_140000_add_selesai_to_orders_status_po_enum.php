<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status_po ENUM('draft', 'published', 'on_progress', 'selesai_produksi', 'siap_dikirim', 'sudah_dikirim', 'delay', 'hold', 'selesai') NOT NULL DEFAULT 'draft'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status_po ENUM('draft', 'published', 'on_progress', 'selesai_produksi', 'siap_dikirim', 'sudah_dikirim', 'delay', 'hold') NOT NULL DEFAULT 'draft'");
        }
    }
};
