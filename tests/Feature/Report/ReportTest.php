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
}

