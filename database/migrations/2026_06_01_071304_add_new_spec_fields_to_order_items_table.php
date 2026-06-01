<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('pola', 50)->nullable()->after('jenis_setelan');
            $table->string('jml_atasan', 100)->nullable()->after('warna');
            $table->string('jml_bawahan', 100)->nullable()->after('jml_atasan');
            $table->string('jenis_rib', 100)->nullable()->after('logo_id');
            $table->string('tutup_kerah', 100)->nullable()->after('jenis_rib');
            $table->string('list_kerah', 100)->nullable()->after('tutup_kerah');
            $table->string('list_lengan', 100)->nullable()->after('list_kerah');
            $table->string('list_samping_celana', 100)->nullable()->after('list_lengan');
            $table->string('list_bawah_celana', 100)->nullable()->after('list_samping_celana');
            $table->string('jahitan_list_lengan', 50)->nullable()->after('pola_jahitan_pundak_id');
            $table->foreignUuid('pola_jahitan_id')->nullable()->constrained('pola_jahitans')->nullOnDelete()->after('pola_jahitan_pundak_id');
            $table->text('ket_atasan')->nullable()->after('gambar_desain');
            $table->text('ket_bawahan')->nullable()->after('ket_atasan');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pola_jahitan_id');
            $table->dropColumn([
                'pola', 'jml_atasan', 'jml_bawahan', 'jenis_rib',
                'tutup_kerah', 'list_kerah', 'list_lengan',
                'list_samping_celana', 'list_bawah_celana',
                'jahitan_list_lengan', 'ket_atasan', 'ket_bawahan',
            ]);
        });
    }
};
