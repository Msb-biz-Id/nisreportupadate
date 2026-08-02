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

    public function test_logs_are_automatically_deleted_when_po_status_becomes_selesai(): void
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

        $order = Order::create([
            'no_po' => 'PO-LOG-TEST-01',
            'nama_po' => 'Jersey Test',
            'brand_id' => $brand->id,
            'pelanggan_id' => $customer->id,
            'status_po' => 'on_progress',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'total_tagihan' => 500000,
            'created_by' => $user->id,
        ]);

        POChangeLog::create([
            'order_id' => $order->id,
            'changed_by' => $user->id,
            'change_reason' => 'Perubahan jumlah',
            'field_changed' => 'total_pcs',
            'old_value' => '10',
            'new_value' => '12',
        ]);

        $this->assertDatabaseHas('po_change_logs', ['order_id' => $order->id]);

        // Change status to selesai
        $order->update(['status_po' => 'selesai']);

        // Assert POChangeLog entries for this order are purged
        $this->assertDatabaseMissing('po_change_logs', ['order_id' => $order->id]);
    }

    public function test_artisan_command_cleans_completed_po_logs(): void
    {
        $user = User::factory()->create();
        $brand = Brand::factory()->create(['created_by' => $user->id]);
        $customer = Customer::create([
            'nama' => 'Test Customer 2',
            'kode' => 'CUST-002',
            'nomor_hp' => '08987654321',
            'brand_id' => $brand->id,
            'created_by' => $user->id
        ]);

        $completedOrder = Order::create([
            'no_po' => 'PO-LOG-TEST-02',
            'nama_po' => 'Jersey Completed Test',
            'brand_id' => $brand->id,
            'pelanggan_id' => $customer->id,
            'status_po' => 'selesai',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'total_tagihan' => 750000,
            'created_by' => $user->id,
        ]);

        POChangeLog::create([
            'order_id' => $completedOrder->id,
            'changed_by' => $user->id,
            'change_reason' => 'Audit log lama',
            'field_changed' => 'catatan',
            'old_value' => 'old',
            'new_value' => 'new',
        ]);

        $this->artisan('po:clean-logs')
            ->expectsOutputToContain('Berhasil menghapus')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('po_change_logs', ['order_id' => $completedOrder->id]);
    }
}
