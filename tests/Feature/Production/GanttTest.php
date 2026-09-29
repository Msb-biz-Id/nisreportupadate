<?php

namespace Tests\Feature\Production;

use App\Models\Master\Customer;
use App\Models\Order\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GanttTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_produksi_can_view_gantt()
    {
        $brand = $this->makeBrand();
        $user  = $this->makeUser('admin_produksi', [$brand]);

        $this->actingAsWithBrand($user, $brand)
            ->get(route('produksi.gantt'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Production/Gantt'));
    }

    public function test_gantt_returns_published_orders_only()
    {
        $brand = $this->makeBrand();
        $user  = $this->makeUser('admin_produksi', [$brand]);

        Customer::create(['brand_id' => $brand->id, 'kode' => 'C1', 'nama' => 'T', 'nomor_hp' => '081', 'is_active' => true]);

        Order::create([
            'brand_id' => $brand->id, 'no_po' => 'PO-GA-1', 'nama_po' => 'A',
            'status_po' => 'on_progress', 'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(5)->toDateString(),
            'pelanggan_id' => Customer::first()->id, 'total_tagihan' => 100000,
            'published_at' => now(), 'created_by' => $user->id,
        ]);

        // Draft — tidak boleh muncul
        Order::create([
            'brand_id' => $brand->id, 'no_po' => 'PO-GA-2', 'nama_po' => 'B',
            'status_po' => 'draft', 'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(5)->toDateString(),
            'pelanggan_id' => Customer::first()->id, 'total_tagihan' => 100000,
            'created_by' => $user->id,
        ]);

        $this->actingAsWithBrand($user, $brand)
            ->get(route('produksi.gantt'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('Production/Gantt')
                ->where('items', fn ($items) => count($items) === 1 && $items[0]['no_po'] === 'PO-GA-1')
            );
    }

    public function test_gantt_item_has_required_fields()
    {
        $brand = $this->makeBrand();
        $user  = $this->makeUser('admin_produksi', [$brand]);
        Customer::create(['brand_id' => $brand->id, 'kode' => 'C1', 'nama' => 'T', 'nomor_hp' => '081', 'is_active' => true]);

        $startDate = now()->subDays(2)->toDateString();
        Order::create([
            'brand_id' => $brand->id, 'no_po' => 'PO-GA-F', 'nama_po' => 'Field test',
            'status_po' => 'on_progress', 'tanggal_masuk' => $startDate,
            'start_production_date' => $startDate,
            'deadline_customer' => now()->addDays(8)->toDateString(),
            'pelanggan_id' => Customer::first()->id, 'total_tagihan' => 150000,
            'published_at' => now(), 'created_by' => $user->id,
        ]);

        $this->actingAsWithBrand($user, $brand)
            ->get(route('produksi.gantt'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Production/Gantt')
                ->where('items.0.no_po', 'PO-GA-F')
                ->where('items.0.start', $startDate)
                ->has('items.0.color')
                ->has('items.0.status_label')
                ->has('items.0.detail_url')
            );
    }

    public function test_guest_cannot_access_gantt()
    {
        $this->get(route('produksi.gantt'))
            ->assertRedirect(route('login'));
    }

    public function test_gantt_respects_brand_isolation()
    {
        $brand1 = $this->makeBrand(['kode' => 'SHU', 'nama_brand' => 'Brand SHU']);
        $brand2 = $this->makeBrand(['kode' => 'NIS', 'nama_brand' => 'Brand NIS']);
        $user   = $this->makeUser('superadmin', [$brand1, $brand2]);

        Customer::create(['brand_id' => $brand1->id, 'kode' => 'C1', 'nama' => 'T', 'nomor_hp' => '081', 'is_active' => true]);
        Customer::create(['brand_id' => $brand2->id, 'kode' => 'C2', 'nama' => 'T', 'nomor_hp' => '082', 'is_active' => true]);

        Order::create([
            'brand_id' => $brand1->id, 'no_po' => 'PO-B1', 'nama_po' => 'Brand1',
            'status_po' => 'on_progress', 'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(5)->toDateString(),
            'pelanggan_id' => Customer::where('brand_id', $brand1->id)->first()->id,
            'total_tagihan' => 100000, 'published_at' => now(), 'created_by' => $user->id,
        ]);

        Order::create([
            'brand_id' => $brand2->id, 'no_po' => 'PO-B2', 'nama_po' => 'Brand2',
            'status_po' => 'on_progress', 'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(5)->toDateString(),
            'pelanggan_id' => Customer::where('brand_id', $brand2->id)->first()->id,
            'total_tagihan' => 100000, 'published_at' => now(), 'created_by' => $user->id,
        ]);

        // Aktif di brand1 — hanya PO-B1 yang muncul
        $this->actingAsWithBrand($user, $brand1)
            ->get(route('produksi.gantt'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->where('items', fn ($items) => count($items) === 1 && $items[0]['no_po'] === 'PO-B1')
            );
    }
}
