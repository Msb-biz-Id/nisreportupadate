<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->longText('metadata'); // JSON payload of the PO state, items, and namesets
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('change_reason', 255)->nullable();
            $table->timestamps();
 
            $table->unique(['order_id', 'version']);
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('po_versions');
    }
};
