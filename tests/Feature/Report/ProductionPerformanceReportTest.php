<?php

namespace Tests\Feature\Report;

use App\Models\Brand;
use App\Models\Master\Customer;
use App\Models\Order\Order;
use App\Models\User;
use App\Services\Reports\ReportRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionPerformanceReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_kinerja_produksi_report_calculates_dual_lateness_accurately(): void
    {
        $brand = Brand::create(['kode' => 'BRD01', 'nama_brand' => 'Brand Test', 'is_active' => true]);
        $pelanggan = Customer::create(['kode' => 'PLG01', 'nama' => 'Pelanggan Test', 'nomor_hp' => '08123456789', 'is_active' => true]);

        $user = $this->makeUser('superadmin');

        // 1. PO Selesai Packing Tepat Waktu (Produksi), Selesai Admin Tepat Waktu (Customer)
        $order1 = Order::create([
            'brand_id' => $brand->id,
            'pelanggan_id' => $pelanggan->id,
            'no_po' => 'PO-TEST-001',
            'nama_po' => 'PO Test Tepat Waktu',
            'status_po' => 'selesai',
            'tanggal_masuk' => now()->subDays(10),
            'end_production_date' => now()->subDays(3)->toDateString(),
            'deadline_customer' => now()->subDays(2)->toDateString(),
            'packing_completed_at' => now()->subDays(4),
            'completed_at' => now()->subDays(2),
            'created_by' => $user->id,
        ]);

        // 2. PO Selesai Packing Telat 2 Hari, Selesai Admin Telat 3 Hari
        $order2 = Order::create([
            'brand_id' => $brand->id,
            'pelanggan_id' => $pelanggan->id,
            'no_po' => 'PO-TEST-002',
            'nama_po' => 'PO Test Telat',
            'status_po' => 'selesai',
            'tanggal_masuk' => now()->subDays(10),
            'end_production_date' => now()->subDays(5)->toDateString(),
            'deadline_customer' => now()->subDays(4)->toDateString(),
            'packing_completed_at' => now()->subDays(3), // Telat 2 hari vs end_production_date
            'completed_at' => now()->subDays(1), // Telat 3 hari vs deadline_customer
            'created_by' => $user->id,
        ]);

        $reportRunner = new ReportRunner();
        $result = $reportRunner->run('kinerja-produksi', null, [
            'from' => now()->subDays(15)->toDateString(),
            'to' => now()->addDays(1)->toDateString(),
        ]);

        $this->assertArrayHasKey('rows', $result);
        $rows = collect($result['rows']);

        $row1 = $rows->firstWhere('no_po', 'PO-TEST-001');
        $this->assertNotNull($row1);
        $this->assertEquals('Tepat Waktu', $row1['keterlambatan_produksi']);
        $this->assertEquals('Tepat Waktu', $row1['keterlambatan_customer']);

        $row2 = $rows->firstWhere('no_po', 'PO-TEST-002');
        $this->assertNotNull($row2);
        $this->assertEquals('Telat 2 hari', $row2['keterlambatan_produksi']);
        $this->assertEquals('Telat 3 hari', $row2['keterlambatan_customer']);
    }
}
