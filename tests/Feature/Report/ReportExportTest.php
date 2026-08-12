<?php

namespace Tests\Feature\Report;

use App\Models\Brand;
use App\Models\Master\Customer;
use App\Models\Order\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_export_kinerja_produksi_excel_and_pdf(): void
    {
        $superadmin = $this->makeUser('superadmin');
        $brand = Brand::create(['kode' => 'BRD01', 'nama_brand' => 'Brand Test', 'is_active' => true]);
        $pelanggan = Customer::create(['kode' => 'PLG01', 'nama' => 'Pelanggan Test', 'nomor_hp' => '08123456789', 'is_active' => true]);

        Order::create([
            'brand_id' => $brand->id,
            'pelanggan_id' => $pelanggan->id,
            'no_po' => 'PO-EXPORT-001',
            'nama_po' => 'PO Export Test',
            'status_po' => 'selesai',
            'tanggal_masuk' => now()->subDays(10),
            'end_production_date' => now()->subDays(3)->toDateString(),
            'deadline_customer' => now()->subDays(2)->toDateString(),
            'packing_completed_at' => now()->subDays(4),
            'completed_at' => now()->subDays(2),
            'created_by' => $superadmin->id,
        ]);

        $this->actingAs($superadmin)
            ->get(route('reports.export.excel', 'kinerja-produksi'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($superadmin)
            ->get(route('reports.export.pdf', 'kinerja-produksi'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
