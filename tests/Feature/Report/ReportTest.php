<?php

namespace Tests\Feature\Report;

use App\Support\ReportRegistry;
use App\Models\Master\Customer;
use App\Models\Order\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_reports_accessible_to_superadmin(): void
    {
        $sa = $this->makeUser('superadmin', [$this->makeBrand()]);

        foreach (array_keys(ReportRegistry::all()) as $slug) {
            $this->actingAs($sa)
                ->get(route('reports.show', $slug))
                ->assertOk();
        }
    }

    public function test_invalid_report_slug_returns_404(): void
    {
        $sa = $this->makeUser('superadmin', [$this->makeBrand()]);
        $this->actingAs($sa)
            ->get(route('reports.show', 'tidak-ada'))
            ->assertNotFound();
    }

    public function test_admin_brand_can_view_reports(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('admin_brand', [$brand]);

        $this->actingAsWithBrand($user, $brand)
            ->get(route('reports.show', 'penjualan-produk'))
            ->assertOk();
    }

    public function test_excel_export_returns_xlsx_response(): void
    {
        $sa = $this->makeUser('superadmin', [$this->makeBrand()]);

        $response = $this->actingAs($sa)
            ->get(route('reports.export.excel', 'penjualan-produk'));

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('content-type'));
    }

    public function test_pdf_export_returns_pdf_response(): void
    {
        $sa = $this->makeUser('superadmin', [$this->makeBrand()]);

        $response = $this->actingAs($sa)
            ->get(route('reports.export.pdf', 'penjualan-produk'));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_crm_churn_report_data(): void
    {
        $brand = $this->makeBrand();
        $sa = $this->makeUser('superadmin', [$brand]);

        $c1 = Customer::create([
            'brand_id' => $brand->id,
            'kode' => 'C01',
            'nama' => 'Pelanggan Aman',
            'nomor_hp' => '08123456789',
            'is_active' => true
        ]);

        $c2 = Customer::create([
            'brand_id' => $brand->id,
            'kode' => 'C02',
            'nama' => 'Pelanggan Churn',
            'nomor_hp' => '08987654321',
            'is_active' => true
        ]);

        // C1: Aman (Order terakhir baru saja masuk)
        Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-001',
            'nama_po' => 'PO 1',
            'status_po' => 'published',
            'tanggal_masuk' => now()->subDays(40)->toDateString(),
            'deadline_customer' => now()->toDateString(),
            'pelanggan_id' => $c1->id,
            'total_tagihan' => 1000000,
            'created_by' => $sa->id,
        ]);
        Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-002',
            'nama_po' => 'PO 2',
            'status_po' => 'published',
            'tanggal_masuk' => now()->subDays(10)->toDateString(),
            'deadline_customer' => now()->toDateString(),
            'pelanggan_id' => $c1->id,
            'total_tagihan' => 1000000,
            'created_by' => $sa->id,
        ]);

        // C2: Churn (Order terakhir 90 hari yang lalu)
        Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-003',
            'nama_po' => 'PO 3',
            'status_po' => 'published',
            'tanggal_masuk' => now()->subDays(120)->toDateString(),
            'deadline_customer' => now()->toDateString(),
            'pelanggan_id' => $c2->id,
            'total_tagihan' => 2000000,
            'created_by' => $sa->id,
        ]);
        Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-004',
            'nama_po' => 'PO 4',
            'status_po' => 'published',
            'tanggal_masuk' => now()->subDays(90)->toDateString(),
            'deadline_customer' => now()->toDateString(),
            'pelanggan_id' => $c2->id,
            'total_tagihan' => 2000000,
            'created_by' => $sa->id,
        ]);

        $response = $this->actingAs($sa)
            ->get(route('reports.show', 'crm-churn'));

        $response->assertOk();

        // Verifikasi props data yang dikirim ke Inertia
        $page = $response->original->getData()['page'] ?? [];
        $props = $page['props'] ?? [];

        $this->assertNotEmpty($props['rows']);
        $this->assertNotEmpty($props['summary']);

        // Pastikan order sorting menempatkan High Risk (C2) di atas Safe (C1)
        $this->assertEquals('Pelanggan Churn', $props['rows'][0]['nama']);
        $this->assertEquals('High Risk', $props['rows'][0]['risk_level']);

        $this->assertEquals('Pelanggan Aman', $props['rows'][1]['nama']);
        $this->assertEquals('Safe', $props['rows'][1]['risk_level']);
    }

    public function test_crm_seasonal_report_data()
    {
        $brand = $this->makeBrand();
        $sa = $this->makeUser('superadmin', [$brand]);

        // Pelanggan 1: Order 1 tahun lalu (bulan yang sama), tidak ada order baru
        $c1 = Customer::create([
            'brand_id' => $brand->id,
            'kode' => 'C_SEAS_01',
            'nama' => 'Pelanggan Musiman Aktif',
            'nomor_hp' => '081234567890',
        ]);

        Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-S01',
            'nama_po' => 'PO Seasonal 1',
            'status_po' => 'published',
            'tanggal_masuk' => now()->subYear()->toDateString(), // Tepat 1 tahun lalu (bulan yang sama)
            'deadline_customer' => now()->toDateString(),
            'pelanggan_id' => $c1->id,
            'total_tagihan' => 1500000,
            'created_by' => $sa->id,
        ]);

        // Pelanggan 2: Order 1 tahun lalu tapi sudah order lagi 10 hari lalu
        $c2 = Customer::create([
            'brand_id' => $brand->id,
            'kode' => 'C_SEAS_02',
            'nama' => 'Pelanggan Musiman Sudah Order',
            'nomor_hp' => '081234567891',
        ]);

        Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-S02',
            'nama_po' => 'PO Seasonal 2',
            'status_po' => 'published',
            'tanggal_masuk' => now()->subYear()->toDateString(),
            'deadline_customer' => now()->toDateString(),
            'pelanggan_id' => $c2->id,
            'total_tagihan' => 2000000,
            'created_by' => $sa->id,
        ]);

        Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-S03',
            'nama_po' => 'PO Seasonal 3',
            'status_po' => 'published',
            'tanggal_masuk' => now()->subDays(10)->toDateString(), // Order baru
            'deadline_customer' => now()->toDateString(),
            'pelanggan_id' => $c2->id,
            'total_tagihan' => 2500000,
            'created_by' => $sa->id,
        ]);

        // Pelanggan 3: Order 1 tahun lalu tapi di bulan berbeda
        $c3 = Customer::create([
            'brand_id' => $brand->id,
            'kode' => 'C_SEAS_03',
            'nama' => 'Pelanggan Luar Musim',
            'nomor_hp' => '081234567892',
        ]);

        Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-S04',
            'nama_po' => 'PO Seasonal 4',
            'status_po' => 'published',
            'tanggal_masuk' => now()->subYear()->subMonths(3)->toDateString(), // 3 bulan sebelum bulan target
            'deadline_customer' => now()->toDateString(),
            'pelanggan_id' => $c3->id,
            'total_tagihan' => 3000000,
            'created_by' => $sa->id,
        ]);

        $response = $this->actingAs($sa)
            ->get(route('reports.show', 'crm-seasonal'));

        $response->assertOk();

        // Verifikasi props data yang dikirim ke Inertia
        $page = $response->original->getData()['page'] ?? [];
        $props = $page['props'] ?? [];

        $this->assertNotEmpty($props['rows']);
        $this->assertNotEmpty($props['summary']);

        // Pastikan hanya Pelanggan 1 yang terdeteksi
        $detectedNames = collect($props['rows'])->pluck('nama')->toArray();
        $this->assertContains('Pelanggan Musiman Aktif', $detectedNames);
        $this->assertNotContains('Pelanggan Musiman Sudah Order', $detectedNames);
        $this->assertNotContains('Pelanggan Luar Musim', $detectedNames);

        // Pastikan isi data baris pertama sesuai target
        $row = $props['rows'][0];
        $this->assertEquals('C_SEAS_01', $row['kode']);
        $this->assertEquals('Pelanggan Musiman Aktif', $row['nama']);
        $this->assertEquals('PO-S01', $row['order_tahun_lalu']);
        $this->assertEquals(1500000, $row['nilai_order_lalu']);
        $this->assertEquals('seasonal', $row['whatsapp_action']['type']);
    }

    public function test_report_brand_filter_scoping_by_role(): void
    {
        $brand1 = $this->makeBrand(['nama_brand' => 'Brand One']);
        $brand2 = $this->makeBrand(['nama_brand' => 'Brand Two']);

        $owner = $this->makeUser('owner', [$brand1, $brand2]);
        $adminBrand1 = $this->makeUser('admin_brand', [$brand1]);

        // Verify that global user (owner) receives all active brands in the dropdown list
        $response = $this->actingAsWithBrand($owner, $brand1)
            ->get(route('reports.show', 'penjualan-produk'));
        $response->assertOk();
        $props = $response->original->getData()['page']['props'];
        $brandIdsInDropdown = collect($props['brands'])->pluck('id')->toArray();
        $this->assertContains($brand1->id, $brandIdsInDropdown);
        $this->assertContains($brand2->id, $brandIdsInDropdown);

        // Verify that brand-restricted user only receives their assigned brand in the dropdown
        $response = $this->actingAsWithBrand($adminBrand1, $brand1)
            ->get(route('reports.show', 'penjualan-produk'));
        $response->assertOk();
        $props = $response->original->getData()['page']['props'];
        $brandIdsInDropdown = collect($props['brands'])->pluck('id')->toArray();
        $this->assertContains($brand1->id, $brandIdsInDropdown);
        $this->assertNotContains($brand2->id, $brandIdsInDropdown);

        // Verify query brand scoping resolves correctly for owner (all when no brand_id filter)
        $response = $this->actingAsWithBrand($owner, $brand1)
            ->get(route('reports.show', ['slug' => 'penjualan-produk', 'brand_id' => '__all__']));
        $response->assertOk();
        $this->assertNull($response->original->getData()['page']['props']['filters']['brand_id'] ?? null);
    }

    public function test_monitoring_deadline_report_data_and_grouping(): void
    {
        $brand = $this->makeBrand();
        $sa = $this->makeUser('superadmin', [$brand]);

        $c = Customer::create([
            'brand_id' => $brand->id,
            'kode' => 'C_MD_01',
            'nama' => 'Pelanggan MD',
            'nomor_hp' => '08123456789',
            'is_active' => true
        ]);

        $printing = \App\Models\Master\Printing::create([
            'nama' => 'Sublimation',
            'deskripsi' => 'Full print sublim',
            'is_active' => true
        ]);

        $deadlineDate = now()->addDays(3)->toDateString();

        $o1 = Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-MD1',
            'nama_po' => 'Jersey MD 1',
            'status_po' => 'on_progress',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => $deadlineDate,
            'pelanggan_id' => $c->id,
            'printing_ids' => [$printing->id],
            'total_tagihan' => 1000000,
            'created_by' => $sa->id,
        ]);
        $o1->items()->create([
            'product_id' => null,
            'nama_produk' => 'Jersey Custom',
            'quantity' => 12,
            'harga_satuan' => 80000,
            'total_harga' => 960000,
        ]);

        $o2 = Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-MD2',
            'nama_po' => 'Jersey MD 2',
            'status_po' => 'on_progress',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => $deadlineDate,
            'pelanggan_id' => $c->id,
            'printing_ids' => [$printing->id],
            'total_tagihan' => 1000000,
            'created_by' => $sa->id,
        ]);
        $o2->items()->create([
            'product_id' => null,
            'nama_produk' => 'Jersey Custom 2',
            'quantity' => 8,
            'harga_satuan' => 80000,
            'total_harga' => 640000,
        ]);

        $response = $this->actingAs($sa)
            ->get(route('reports.show', ['slug' => 'monitoring-deadline', 'threshold' => 7]));

        $response->assertOk();

        $page = $response->original->getData()['page'] ?? [];
        $props = $page['props'] ?? [];

        $this->assertNotEmpty($props['rows']);
        
        $rows = $props['rows'];
        $this->assertCount(4, $rows);

        // Header Row
        $this->assertTrue($rows[0]['is_group_header']);
        $this->assertEquals($deadlineDate, $rows[0]['deadline']);

        // Order 1 Row
        $this->assertFalse($rows[1]['is_group_header']);
        $this->assertFalse($rows[1]['is_group_total']);
        $this->assertEquals('PO-MD1', $rows[1]['no_po']);
        $this->assertEquals('Jersey MD 1', $rows[1]['nama_po']);
        $this->assertEquals($brand->nama_brand, $rows[1]['brand_nama']);
        $this->assertEquals('Pelanggan MD', $rows[1]['pelanggan']);
        $this->assertEquals(12, $rows[1]['pcs']);
        $this->assertEquals('Sublimation', $rows[1]['jenis_printing']);

        // Order 2 Row
        $this->assertFalse($rows[2]['is_group_header']);
        $this->assertFalse($rows[2]['is_group_total']);
        $this->assertEquals('PO-MD2', $rows[2]['no_po']);
        $this->assertEquals(8, $rows[2]['pcs']);

        // Total Row
        $this->assertTrue($rows[3]['is_group_total']);
        $this->assertEquals('TOTAL PCS', $rows[3]['pelanggan']);
        $this->assertEquals(20, $rows[3]['pcs']);
    }

    public function test_kinerja_produksi_report_data(): void
    {
        $brand = $this->makeBrand();
        $sa = $this->makeUser('superadmin', [$brand]);

        $c = Customer::create([
            'brand_id' => $brand->id,
            'kode' => 'C_KP_01',
            'nama' => 'Pelanggan KP',
            'nomor_hp' => '08123456789',
            'is_active' => true
        ]);

        $progress = \App\Models\Master\Progress::create([
            'nama_progress' => 'SETTING',
            'warna' => '#6B7280',
            'urutan' => 1,
            'is_skippable' => false,
            'is_active' => true
        ]);

        $o1 = Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-KP1',
            'nama_po' => 'Jersey KP 1',
            'status_po' => 'on_progress',
            'tanggal_masuk' => now()->subDays(5)->toDateString(),
            'deadline_customer' => now()->addDays(2)->toDateString(),
            'pelanggan_id' => $c->id,
            'created_by' => $sa->id,
        ]);
        $o1->items()->create([
            'product_id' => null,
            'nama_produk' => 'Jersey Custom',
            'quantity' => 10,
            'harga_satuan' => 80000,
            'subtotal' => 800000, // order sum uses items.subtotal or similar, let's set it
        ]);

        $detail = \App\Models\Order\OrderProgressDetail::create([
            'order_id' => $o1->id,
            'progress_id' => $progress->id,
            'status' => 'selesai',
            'started_at' => now()->subDays(4),
            'completed_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($sa)
            ->get(route('reports.show', [
                'slug' => 'kinerja-produksi',
                'from' => now()->subDays(10)->toDateString(),
                'to' => now()->toDateString()
            ]));

        $response->assertOk();

        $page = $response->original->getData()['page'] ?? [];
        $props = $page['props'] ?? [];

        $this->assertNotEmpty($props['rows']);
        $row = $props['rows'][0];

        $this->assertEquals('PO-KP1', $row['no_po']);
        $this->assertEquals('Jersey KP 1', $row['nama_po']);
        $this->assertEquals('Sisa 2 days', str_replace('hari', 'days', $row['keterlambatan'])); // "Sisa 2 hari" -> "Sisa 2 days"
        
        $key = 'progress_setting';
        $this->assertArrayHasKey($key, $row);
        $this->assertEquals('2 days', str_replace('hari', 'days', $row[$key])); // "2 hari" -> "2 days"
    }
}

