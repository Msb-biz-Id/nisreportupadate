<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Services\CacheService;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mereset deadline produksi (end_production_date) dan start_production_date
     * pada repeat order yang statusnya masih 'draft' atau 'published'
     * dan belum pernah memulai tahapan produksi (belum ada progress on_progress/selesai).
     *
     * Ini memastikan bahwa semua repeat order mendarat di kolom "Baru Masuk (Isi Deadline)"
     * di Kanban Produksi, bukan nyelonong ke "Review Produksi" dengan deadline lama dari order induk.
     */
    public function up(): void
    {
        // 1. Ambil order_id yang sudah memiliki tahapan produksi aktif (on_progress atau selesai)
        $inProgressOrderIds = DB::table('order_progress_details')
            ->whereIn('status', ['on_progress', 'selesai'])
            ->distinct()
            ->pluck('order_id')
            ->toArray();

        // 2. Query target: PO Repeat yang masih draft atau published,
        // dan produksinya belum dimulai sama sekali (belum ada progress aktif),
        // tetapi end_production_date atau start_production_date-nya terisi.
        $affectedQuery = DB::table('orders')
            ->where(function ($q) {
                $q->where('is_repeat_order', 1)
                  ->orWhereNotNull('repeat_from_po_id');
            })
            ->whereIn('status_po', ['draft', 'published'])
            ->where(function ($q) {
                $q->whereNotNull('end_production_date')
                  ->orWhereNotNull('start_production_date');
            });

        if (!empty($inProgressOrderIds)) {
            $affectedQuery->whereNotIn('id', $inProgressOrderIds);
        }

        $affectedBrandIds = (clone $affectedQuery)->distinct()->pluck('brand_id')->toArray();

        $affectedQuery->update([
            'end_production_date'   => null,
            'start_production_date' => null,
            'production_days_late'  => 0,
            'updated_at'            => now(),
        ]);

        // 3. Invalidate cache kanban & dashboard untuk brand-brand yang terpengaruh
        try {
            CacheService::forgetDashboard();
            foreach ($affectedBrandIds as $bId) {
                CacheService::forgetProductionViews($bId);
            }
            CacheService::forgetProductionViews(null);
            CacheService::forgetProductionViews('all');
        } catch (\Throwable $e) {
            // Safe fallback during console migration
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data reset tidak dapat di-rollback secara otomatis karena nilai lama adalah data korup/salah warisan PO induk
    }
};

