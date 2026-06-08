<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->foreignUuid('master_jenis_pembayaran_id')
                  ->nullable()
                  ->after('payment_type')
                  ->constrained('master_jenis_pembayarans')
                  ->nullOnDelete();
        });

        // Migrate existing data (optional, mapping payment_type string to new ID)
        $masters = DB::table('master_jenis_pembayarans')->get();
        foreach ($masters as $master) {
            // Because original payment_type might be "tambahan_produk" instead of "Tambahan Produk",
            // let's create a map
            $map = [
                'dp' => 'DP',
                'pelunasan' => 'Pelunasan',
                'ongkir' => 'Ongkir',
                'tambahan_produk' => 'Tambahan Produk',
                'cashback' => 'Cashback',
                'return' => 'Return',
                'lainnya' => 'Lainnya',
            ];
            $originalType = array_search($master->nama, $map);
            if ($originalType) {
                DB::table('order_payments')
                    ->where('payment_type', $originalType)
                    ->update(['master_jenis_pembayaran_id' => $master->id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->dropForeign(['master_jenis_pembayaran_id']);
            $table->dropColumn('master_jenis_pembayaran_id');
        });
    }
};
