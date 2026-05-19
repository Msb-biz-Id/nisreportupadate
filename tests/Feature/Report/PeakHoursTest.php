<?php

namespace Tests\Feature\Report;

use App\Models\Master\Customer;
use App\Models\Order\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeakHoursTest extends TestCase
{
    use RefreshDatabase;

    public function test_peak_hours_report_loads()
    {
        $brand = $this->makeBrand();
        $user  = $this->makeUser('admin_brand', [$brand]);

        $this->actingAsWithBrand($user, $brand)
            ->get(route('reports.show', 'peak-hours'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('Report/Show')
                ->where('config.slug', 'peak-hours')
                ->where('config.chart.type', 'heatmap')
                ->has('heatmapSeries')
            );
    }

    public function test_peak_hours_returns_heatmap_series_structure()
    {
        $brand = $this->makeBrand();
        $user  = $this->makeUser('superadmin', [$brand]);
        Customer::create(['brand_id' => $brand->id, 'kode' => 'C1', 'nama' => 'T', 'nomor_hp' => '081', 'is_active' => true]);

        Order::create([
            'brand_id'         => $brand->id,
            'no_po'            => 'PO-PH-1',
            'nama_po'          => 'Peak test',
            'status_po'        => 'published',
            'tanggal_masuk'    => now()->toDateString(),
            'deadline_customer'=> now()->addDays(5)->toDateString(),
            'pelanggan_id'     => Customer::first()->id,
            'total_tagihan'    => 100000,
            'published_at'     => now(),
            'created_by'       => $user->id,
        ]);

        $this->actingAsWithBrand($user, $brand)
            ->get(route('reports.show', 'peak-hours'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->where('heatmapSeries', fn ($series) =>
                    count($series) === 7 &&           // 7 hari
                    count($series[0]['data']) === 24  // 24 jam per hari
                )
            );
    }

    public function test_peak_hours_respects_brand_isolation()
    {
        $brand1 = $this->makeBrand(['kode' => 'B1']);
        $brand2 = $this->makeBrand(['kode' => 'B2']);
        $user   = $this->makeUser('superadmin', [$brand1, $brand2]);

        Customer::create(['brand_id' => $brand1->id, 'kode' => 'C1', 'nama' => 'T', 'nomor_hp' => '081', 'is_active' => true]);
        Customer::create(['brand_id' => $brand2->id, 'kode' => 'C2', 'nama' => 'T', 'nomor_hp' => '082', 'is_active' => true]);

        Order::create([
            'brand_id' => $brand1->id, 'no_po' => 'PO-PH-B1', 'nama_po' => 'B1',
            'status_po' => 'published', 'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(5)->toDateString(),
            'pelanggan_id' => Customer::where('brand_id', $brand1->id)->first()->id,
            'total_tagihan' => 100000, 'published_at' => now(), 'created_by' => $user->id,
        ]);

        // brand2 tidak ada order → total di heatmap = 0
        $this->actingAsWithBrand($user, $brand2)
            ->get(route('reports.show', 'peak-hours'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->where('summary.0.value', 0)
            );
    }

    public function test_guest_cannot_access_peak_hours()
    {
        $this->get(route('reports.show', 'peak-hours'))
            ->assertRedirect(route('login'));
    }
}
