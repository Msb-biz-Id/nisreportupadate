<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('master_jenis_pembayarans')
            ->where('nama', 'Return')
            ->update([
                'nama' => 'Refund',
                'deskripsi' => 'Potongan tagihan karena pengembalian barang atau pembatalan sebagian item (Refund).',
            ]);
    }

    public function down(): void
    {
        DB::table('master_jenis_pembayarans')
            ->where('nama', 'Refund')
            ->update([
                'nama' => 'Return',
                'deskripsi' => 'Potongan tagihan karena pengembalian barang atau pembatalan sebagian item.',
            ]);
    }
};
