<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_jenis_pembayarans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');
            $table->enum('tipe_keuangan', ['pemasukan', 'pengeluaran']);
            $table->enum('efek_tagihan', ['penambahan', 'pengurangan', 'netral']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Seed default values
        DB::table('master_jenis_pembayarans')->insert([
            ['id' => Str::uuid()->toString(), 'nama' => 'DP', 'tipe_keuangan' => 'pemasukan', 'efek_tagihan' => 'netral', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => Str::uuid()->toString(), 'nama' => 'Pelunasan', 'tipe_keuangan' => 'pemasukan', 'efek_tagihan' => 'netral', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => Str::uuid()->toString(), 'nama' => 'Ongkir', 'tipe_keuangan' => 'pemasukan', 'efek_tagihan' => 'penambahan', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => Str::uuid()->toString(), 'nama' => 'Tambahan Produk', 'tipe_keuangan' => 'pemasukan', 'efek_tagihan' => 'penambahan', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => Str::uuid()->toString(), 'nama' => 'Lainnya', 'tipe_keuangan' => 'pemasukan', 'efek_tagihan' => 'netral', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => Str::uuid()->toString(), 'nama' => 'Cashback', 'tipe_keuangan' => 'pengeluaran', 'efek_tagihan' => 'pengurangan', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => Str::uuid()->toString(), 'nama' => 'Return', 'tipe_keuangan' => 'pengeluaran', 'efek_tagihan' => 'pengurangan', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('master_jenis_pembayarans');
    }
};
