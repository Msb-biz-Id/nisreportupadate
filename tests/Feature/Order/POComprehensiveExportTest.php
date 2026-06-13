<?php

namespace Tests\Feature\Order;

use App\Models\Master\Customer;
use App\Models\Order\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class POComprehensiveExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_and_archive_po_filtering_in_index(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('admin_brand', [$brand]);

        $customer = Customer::create([
            'brand_id' => $brand->id,
            'kode' => 'C01',
            'nama' => 'Test Customer',
            'nomor_hp' => '08123456789',
            'is_active' => true
        ]);

        // Create an active PO
        $activeOrder = Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-ACTIVE-001',
            'nama_po' => 'Active PO',
            'status_po' => 'on_progress',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(5)->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 1500000,
            'created_by' => $user->id,
        ]);

        // Create an archived PO (completed status 'sudah_dikirim')
        $archivedOrder = Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-ARCHIVE-002',
            'nama_po' => 'Archived PO',
            'status_po' => 'sudah_dikirim',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(5)->toDateString(),
            'pelanggan_id' => $customer->id,
            'total_tagihan' => 2500000,
            'created_by' => $user->id,
        ]);

        // Default / active view: activeOrder should be present, archivedOrder should NOT
        $responseActive = $this->actingAsWithBrand($user, $brand)
            ->get(route('orders.index', ['tab' => 'active']));

        $responseActive->assertOk();
        $ordersActive = $responseActive->viewData('page')['props']['orders']['data'];
        $activeIds = collect($ordersActive)->pluck('id')->all();

        $this->assertContains($activeOrder->id, $activeIds);
        $this->assertNotContains($archivedOrder->id, $activeIds);

        // Archive view: archivedOrder should be present, activeOrder should NOT
        $responseArchive = $this->actingAsWithBrand($user, $brand)
            ->get(route('orders.index', ['tab' => 'archive']));

        $responseArchive->assertOk();
        $ordersArchive = $responseArchive->viewData('page')['props']['orders']['data'];
        $archiveIds = collect($ordersArchive)->pluck('id')->all();

        $this->assertNotContains($activeOrder->id, $archiveIds);
        $this->assertContains($archivedOrder->id, $archiveIds);
    }

    public function test_comprehensive_excel_export_is_accessible(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('admin_brand', [$brand]);

        $response = $this->actingAsWithBrand($user, $brand)
            ->get(route('orders.export-comprehensive', ['tab' => 'active']));

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('content-type'));
    }
}
