<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Modul 9 — Arsip Korespondensi Eksternal (plan/HRIS.md).
     * Surat-menyurat dengan instansi eksternal (BPJS-TK, Disnaker, Bank, Mitra).
     */
    public function up(): void
    {
        Schema::create('hcm_external_letters', function (Blueprint $table) {
            $table->id();
            $table->string('registration_no', 50)->unique();   // DOC-EXT-001
            $table->date('letter_date');
            $table->string('direction', 50)->default('Surat Masuk'); // Surat Masuk / Surat Keluar
            $table->string('external_letter_no', 100);
            $table->string('sender', 150);
            $table->string('recipient', 150);
            $table->text('subject');
            $table->string('file_scan_url', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['direction', 'letter_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hcm_external_letters');
    }
};
