<?php

namespace Tests\Feature\Order;

use App\Models\Brand;
use App\Models\Master\Customer;
use App\Models\Order\Order;
use App\Models\Order\POChangeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleanCompletedPoLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_are_retained_when_po_is_completed_and_deleted_only_after_30_days(): void
    {
        $user = User::factory()->create();
        $brand = Brand::factory()->create(['created_by' => $user->id]);
        $customer = Customer::create([
            'nama' => 'Test Customer',
            'kode' => 'CUST-001',
            'nomor_hp' => '08123456789',
            'brand_id' => $brand->id,
            'created_by' => $user->id
        ]);

        // 1. PO Baru Selesai Hari Ini
        $recentlyCompletedOrder = Order::create([
            'no_po' => 'PO-LOG-RECENT',
            'nama_po' => 'Jersey Selesai Baru',
            'brand_id' => $brand->id,
            'pelanggan_id' => $customer->id,
            'status_po' => 'selesai',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'total_tagihan' => 500000,
            'created_by' => $user->id,
        ]);

        POChangeLog::create([
            'order_id' => $recentlyCompletedOrder->id,
            'changed_by' => $user->id,
            'change_reason' => 'Perubahan jumlah',
            'field_changed' => 'total_pcs',
            'old_value' => '10',
            'new_value' => '12',
        ]);

        // 2. PO Selesai 35 Hari yang Lalu
        $oldCompletedOrder = Order::create([
            'no_po' => 'PO-LOG-OLD',
            'nama_po' => 'Jersey Selesai Lama',
            'brand_id' => $brand->id,
            'pelanggan_id' => $customer->id,
            'status_po' => 'selesai',
            'tanggal_masuk' => now()->subDays(40)->toDateString(),
            'deadline_customer' => now()->subDays(35)->toDateString(),
            'total_tagihan' => 750000,
            'created_by' => $user->id,
        ]);

        POChangeLog::create([
            'order_id' => $oldCompletedOrder->id,
            'changed_by' => $user->id,
            'change_reason' => 'Audit log lama',
            'field_changed' => 'catatan',
            'old_value' => 'old',
            'new_value' => 'new',
        ]);

        // Force update timestamp di database ke 35 hari lalu
        Order::where('id', $oldCompletedOrder->id)->update(['updated_at' => now()->subDays(35)]);

        // Jalankan perintah pembersihan log 30 hari
        $this->artisan('po:clean-logs --days=30')
            ->expectsOutputToContain('Berhasil menghapus')
            ->assertExitCode(0);

        // PO yang baru selesai hari ini: Log MASIH ADA (Retained 30 days)
        $this->assertDatabaseHas('po_change_logs', ['order_id' => $recentlyCompletedOrder->id]);

        // PO yang selesai 35 hari lalu: Log SUDAH DIHAPUS
        $this->assertDatabaseMissing('po_change_logs', ['order_id' => $oldCompletedOrder->id]);
    }
}
