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
        Schema::table('purchasing_orders', function (Blueprint $table) {
            // Pilar 2: Tanggal dan Nominal Kas Bon Diterima dari Keuangan
            $table->date('disbursed_at')->nullable()->after('payment_type');
            $table->decimal('disbursed_amount', 15, 2)->default(0.00)->after('disbursed_at');

            // Pilar 3: Tanggal Belanja Riil dan Nominal Pengeluaran Sesuai Struk/Nota
            $table->date('realized_at')->nullable()->after('disbursed_amount');
            $table->decimal('realized_amount', 15, 2)->default(0.00)->after('realized_at');

            // Pilar 4: Kalkulasi Sisa Uang (disbursed_amount - realized_amount)
            $table->decimal('remaining_amount', 15, 2)->default(0.00)->after('realized_amount');
            $table->string('settlement_status', 50)->default('NONE')->after('remaining_amount');
            // Nilai settlement_status:
            // - NONE: Belum dicairkan / masih pengajuan awal
            // - DISBURSED_PENDING_RECEIPT: Dana kas bon telah diterima, belanja/nota belum dilaporkan
            // - REFUND_PENDING: Realisasi < Kas Bon (Sisa uang lebih/kembalian wajib disetor ke Kas CV)
            // - REIMBURSE_PENDING: Realisasi > Kas Bon (Nombok/defisit, kekurangan wajib dicairkan Finance)
            // - SETTLED: Selesai penuh (Sisa pas atau kembalian/reimburse sudah diselesaikan)

            $table->text('settlement_notes')->nullable()->after('settlement_status');
            $table->timestamp('settled_at')->nullable()->after('settlement_notes');
            $table->foreignId('settled_by')->nullable()->after('settled_at')->constrained('users')->nullOnDelete();

            $table->index(['settlement_status', 'disbursed_at'], 'idx_po_settlement_disbursed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchasing_orders', function (Blueprint $table) {
            $table->dropForeign(['settled_by']);
            $table->dropIndex('idx_po_settlement_disbursed');
            $table->dropColumn([
                'disbursed_at',
                'disbursed_amount',
                'realized_at',
                'realized_amount',
                'remaining_amount',
                'settlement_status',
                'settlement_notes',
                'settled_at',
                'settled_by',
            ]);
        });
    }
};
