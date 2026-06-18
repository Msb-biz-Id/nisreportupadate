<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('master_jenis_pembayarans', function (Blueprint $table) {
            $table->text('deskripsi')->nullable()->after('efek_tagihan');
        });

        // Seed default descriptions for existing rows
        DB::table('master_jenis_pembayarans')->where('nama', 'DP')->update(['deskripsi' => 'Pembayaran uang muka untuk memulai proses pengerjaan order.']);
        DB::table('master_jenis_pembayarans')->where('nama', 'Pelunasan')->update(['deskripsi' => 'Pembayaran akhir untuk melunasi sisa tagihan order sebelum pengiriman.']);
        DB::table('master_jenis_pembayarans')->where('nama', 'Ongkir')->update(['deskripsi' => 'Biaya pengiriman barang ke alamat penerima.']);
        DB::table('master_jenis_pembayarans')->where('nama', 'Tambahan Produk')->update(['deskripsi' => 'Biaya tambahan untuk produk tambahan atau upgrade spesifikasi.']);
        DB::table('master_jenis_pembayarans')->where('nama', 'Lainnya')->update(['deskripsi' => 'Pembayaran kas lainnya yang tidak termasuk kategori utama.']);
        DB::table('master_jenis_pembayarans')->where('nama', 'Cashback')->update(['deskripsi' => 'Dana pengembalian atau reward yang diberikan ke pelanggan.']);
        DB::table('master_jenis_pembayarans')->where('nama', 'Return')->update(['deskripsi' => 'Potongan tagihan karena pengembalian barang atau pembatalan sebagian item.']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_jenis_pembayarans', function (Blueprint $table) {
            $table->dropColumn('deskripsi');
        });
    }
};
