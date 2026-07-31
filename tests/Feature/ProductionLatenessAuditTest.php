<?php

namespace Tests\Feature;

use App\Models\Master\Progress;
use App\Models\Order\Order;
use App\Models\Order\OrderProgressDetail;
use App\Models\User;
use App\Services\POStatusManager;
use App\Support\ReportRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProductionLatenessAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_po_completion_records_persistent_lateness_fields()
    {
        /** @var POStatusManager $statusManager */
        $statusManager = app(POStatusManager::class);
        $user = $this->makeUser('superadmin');
        $brand = $this->makeBrand();
        $customer = \App\Models\Master\Customer::create(['brand_id' => $brand->id, 'nama' => 'Test Customer', 'kode' => 'CUST-001', 'nomor_hp' => '08123456789']);

        // Create an order with deadline yesterday (late)
        $order = Order::create([
            'brand_id' => $brand->id,
            'pelanggan_id' => $customer->id,
            'no_po' => 'PO-LATE-001',
            'nama_po' => 'Jersey Late Test',
            'status_po' => 'published',
            'tanggal_masuk' => Carbon::now()->subDays(10)->toDateString(),
            'deadline_customer' => Carbon::now()->subDays(2)->toDateString(),
            'published_at' => now(),
            'created_by' => $user->id,
        ]);

        // Create Packing progress stage
        $packingProgress = Progress::create([
            'nama_progress' => 'PACKING',
            'urutan' => 1,
            'is_active' => true,
        ]);

        $detail = OrderProgressDetail::create([
            'order_id' => $order->id,
            'progress_id' => $packingProgress->id,
            'status' => 'pending',
            'updated_by' => $user->id,
        ]);

        // Mark packing as selesai
        $statusManager->updateProgressDetail(
            $order->fresh(),
            $detail,
            'selesai',
            'Packing beres',
            null,
            null,
            $user
        );

        $order->refresh();

        // Assert that PO status is siap_dikirim and lateness is recorded persistently
        $this->assertEquals('siap_dikirim', $order->status_po);
        $this->assertTrue($order->was_delayed_on_completion);
        $this->assertGreaterThanOrEqual(1, $order->days_late_on_completion);
        $this->assertNotNull($order->end_production_date);
    }

    public function test_all_reports_and_exports_support_auto_totals()
    {
        $user = $this->makeUser('superadmin');
        $brand = $this->makeBrand();
        $customer = \App\Models\Master\Customer::create(['brand_id' => $brand->id, 'nama' => 'Test Customer 2', 'kode' => 'CUST-002', 'nomor_hp' => '08123456780']);

        Order::create([
            'brand_id' => $brand->id,
            'pelanggan_id' => $customer->id,
            'no_po' => 'PO-TEST-TOTALS',
            'nama_po' => 'Totals Test PO',
            'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(5)->toDateString(),
            'total_tagihan' => 500000,
            'published_at' => now(),
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('reports.show', 'status-po'));
        $response->assertStatus(200);

        $excelResponse = $this->actingAs($user)->get(route('reports.export.excel', ['slug' => 'status-po']));
        $excelResponse->assertStatus(200);

        $pdfResponse = $this->actingAs($user)->get(route('reports.export.pdf', ['slug' => 'status-po']));
        $pdfResponse->assertStatus(200);
    }
}
