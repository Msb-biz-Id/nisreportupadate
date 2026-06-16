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
        Schema::table('po_lock_status', function (Blueprint $table) {
            $table->foreignId('unlock_requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('unlock_request_reason')->nullable();
            $table->timestamp('unlock_requested_at')->nullable();
            
            $table->foreignId('relock_requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('relock_request_reason')->nullable();
            $table->timestamp('relock_requested_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('po_lock_status', function (Blueprint $table) {
            $table->dropForeign(['unlock_requested_by']);
            $table->dropColumn(['unlock_requested_by', 'unlock_request_reason', 'unlock_requested_at']);
            
            $table->dropForeign(['relock_requested_by']);
            $table->dropColumn(['relock_requested_by', 'relock_request_reason', 'relock_requested_at']);
        });
    }
};
