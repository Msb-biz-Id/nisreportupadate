<?php

namespace Tests\Feature\Comparison;

use App\Models\Master\Customer;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComparisonReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_view_comparison_with_2_brands(): void
    {
        $sa = $this->makeUser('superadmin');
        $b1 = $this->makeBrand(['kode' => 'A1']);
        $b2 = $this->makeBrand(['kode' => 'B2']);

        $this->actingAs($sa)
            ->get(route('comparison.show', ['brand_ids' => [$b1->id, $b2->id]]))
            ->assertOk();
    }

    public function test_dump_db_orders(): void
    {
        $res = [
            'total_orders' => \App\Models\Order\Order::count(),
            'statuses' => \App\Models\Order\Order::selectRaw('status_po, count(*) as count')->groupBy('status_po')->get()->toArray(),
            'brands' => \App\Models\Order\Order::selectRaw('brand_id, count(*) as count')->groupBy('brand_id')->get()->toArray(),
            'dates' => \App\Models\Order\Order::selectRaw('min(tanggal_masuk) as min_date, max(tanggal_masuk) as max_date')->get()->toArray(),
            'customer_count' => \App\Models\Master\Customer::count(),
            'order_items' => \App\Models\Order\OrderItem::count(),
            'all_brands' => \App\Models\Brand::all()->toArray(),
        ];
        $this->assertIsInt($res['total_orders']);
    }

    public function test_owner_with_one_brand_sees_not_eligible(): void
    {
        $brand = $this->makeBrand();
        $owner = $this->makeUser('owner', [$brand]);

        $response = $this->actingAs($owner)->get(route('comparison.show'));
        $response->assertOk();
        $this->assertStringContainsString('Comparison/NotEligible', $response->viewData('page')['component']);
    }

    public function test_comparison_calculates_metrics_correctly(): void
    {
        $sa = $this->makeUser('superadmin');
        $b1 = $this->makeBrand(['kode' => 'METRIC1']);
        $b2 = $this->makeBrand(['kode' => 'METRIC2']);

        // Setup data: brand 1 punya 2 PO published, brand 2 punya 1 PO published
        $c1 = Customer::create(['brand_id' => $b1->id, 'kode' => 'C1', 'nama' => 'T1', 'nomor_hp' => '081', 'is_active' => true]);
        $c2 = Customer::create(['brand_id' => $b2->id, 'kode' => 'C2', 'nama' => 'T2', 'nomor_hp' => '082', 'is_active' => true]);

        Order::create([
            'brand_id' => $b1->id, 'no_po' => 'PO-M1-001', 'nama_po' => 'X1',
            'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => $c1->id, 'total_tagihan' => 1000000,
            'published_at' => now(), 'created_by' => $sa->id,
        ]);
        Order::create([
            'brand_id' => $b1->id, 'no_po' => 'PO-M1-002', 'nama_po' => 'X2',
            'status_po' => 'on_progress',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => $c1->id, 'total_tagihan' => 500000,
            'published_at' => now(), 'created_by' => $sa->id,
        ]);
        Order::create([
            'brand_id' => $b2->id, 'no_po' => 'PO-M2-001', 'nama_po' => 'X3',
            'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => $c2->id, 'total_tagihan' => 800000,
            'published_at' => now(), 'created_by' => $sa->id,
        ]);

        $response = $this->actingAs($sa)
            ->get(route('comparison.show', [
                'brand_ids' => [$b1->id, $b2->id],
                'from' => now()->subDay()->toDateString(),
                'to' => now()->addDay()->toDateString(),
            ]));

        $response->assertOk();
        // Verify props
        $props = $response->viewData('page')['props'];
        $brands = $props['result']['brands'];
        $this->assertCount(2, $brands);

        $b1Result = collect($brands)->firstWhere('kode', 'METRIC1');
        $b2Result = collect($brands)->firstWhere('kode', 'METRIC2');

        $this->assertEquals(2, $b1Result['po_count']);
        $this->assertEquals(1500000, $b1Result['revenue']);
        $this->assertTrue($b1Result['is_winner_revenue']); // 1.5jt > 800k
        $this->assertTrue($b1Result['is_winner_po']);      // 2 > 1

        $this->assertEquals(1, $b2Result['po_count']);
        $this->assertEquals(800000, $b2Result['revenue']);
    }

    public function test_superadmin_can_export_comparison_excel(): void
    {
        $sa = $this->makeUser('superadmin');
        $b1 = $this->makeBrand(['nama_brand' => 'Brand 1', 'kode' => 'A1']);
        $b2 = $this->makeBrand(['nama_brand' => 'Brand 2', 'kode' => 'B2']);

        $response = $this->actingAs($sa)
            ->get(route('comparison.export.excel', [
                'mode' => 'brands',
                'brand_ids' => [$b1->id, $b2->id],
                'year' => (int) now()->year
            ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('content-type'));
    }
}
