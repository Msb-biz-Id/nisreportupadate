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

    public function test_free_item_specification_inheritance(): void
    {
        $items = collect([
            // Core item - Non-free
            [
                'nama_produk' => 'PEMAIN',
                'varian_label' => 'Jersey',
                'harga_satuan' => 150000,
                'quantity' => 10,
                'jenis_setelan_id' => 1,
                'pola_produksi_id' => 2,
                'bahan_kain_id' => 3,
                'warna' => 'Merah',
                'logo_id' => 4,
                'jenis_rib' => 'V-Neck',
                'is_addon' => false,
                'gambar_desain' => 'core_design.png',
                'gambar_kerah' => 'core_collar.png',
            ],
            // Free item - should inherit core specifications except images
            [
                'nama_produk' => 'PEMAIN',
                'varian_label' => 'Jersey',
                'harga_satuan' => 0,
                'quantity' => 2,
                'jenis_setelan_id' => null, // empty, should inherit 1
                'pola_produksi_id' => null, // empty, should inherit 2
                'bahan_kain_id' => null, // empty, should inherit 3
                'warna' => null, // empty, should inherit 'Merah'
                'logo_id' => null, // empty, should inherit 4
                'jenis_rib' => null, // empty, should inherit 'V-Neck'
                'is_addon' => false,
                'gambar_desain' => null, // empty, should NOT inherit
                'gambar_kerah' => null, // empty, should NOT inherit
            ],
            // Free item with override - should keep its custom specifications and NOT inherit core specifications for overwritten fields
            [
                'nama_produk' => 'PEMAIN',
                'varian_label' => 'Jersey',
                'harga_satuan' => 0,
                'quantity' => 1,
                'jenis_setelan_id' => null, // empty, should inherit 1
                'pola_produksi_id' => null, // empty, should inherit 2
                'bahan_kain_id' => null, // empty, should inherit 3
                'warna' => 'Biru', // OVERRIDE: not empty, should keep 'Biru'
                'logo_id' => null, // empty, should inherit 4
                'jenis_rib' => null, // empty, should inherit 'V-Neck'
                'is_addon' => false,
                'gambar_desain' => null, // empty, should NOT inherit
                'gambar_kerah' => null, // empty, should NOT inherit
            ]
        ]);

        $grouped = \App\Support\PoGroupHelper::group($items);

        // We expect 3 groups:
        // Group 1: Core item (Paid, quantity = 10)
        // Group 2: First Free item (Free, quantity = 2, inherited specs & images)
        // Group 3: Second Free item (Free, quantity = 1, overridden 'warna' = 'Biru')
        $this->assertCount(3, $grouped);

        $group1 = $grouped->first();
        $this->assertEquals(10, $group1['quantity']);
        $this->assertEquals(1, $group1['jenis_setelan_id']);
        $this->assertEquals(2, $group1['pola_produksi_id']);
        $this->assertEquals(3, $group1['bahan_kain_id']);
        $this->assertEquals('Merah', $group1['warna']);
        $this->assertEquals(4, $group1['logo_id']);
        $this->assertEquals('V-Neck', $group1['jenis_rib']);

        $group2 = $grouped->get(1);
        $this->assertEquals(2, $group2['quantity']);
        $this->assertEquals('Merah', $group2['warna']);
        $this->assertEquals('core_design.png', $group2['gambar_desain']);
        $this->assertEquals('core_collar.png', $group2['gambar_kerah']);

        $group3 = $grouped->last();
        $this->assertEquals(1, $group3['quantity']);
        $this->assertEquals(1, $group3['jenis_setelan_id']); // inherited
        $this->assertEquals('Biru', $group3['warna']); // override preserved
        $this->assertEquals('core_design.png', $group3['gambar_desain']); // inherited
    }
}

