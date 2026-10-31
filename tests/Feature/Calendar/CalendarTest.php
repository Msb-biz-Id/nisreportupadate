<?php

namespace Tests\Feature\Calendar;

use App\Models\Master\Customer;
use App\Models\Order\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_calendar()
    {
        $brand = $this->makeBrand();
        $user  = $this->makeUser('admin_brand', [$brand]);

        $this->actingAsWithBrand($user, $brand)
            ->get(route('kalender.index'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Calendar/Index'));
    }

    public function test_guest_cannot_access_calendar()
    {
        $this->get(route('kalender.index'))
            ->assertRedirect(route('login'));
    }

    public function test_calendar_returns_events_with_required_fields()
    {
        $brand = $this->makeBrand();
        $user  = $this->makeUser('admin_brand', [$brand]);
        Customer::create(['brand_id' => $brand->id, 'kode' => 'C1', 'nama' => 'Pelanggan Test', 'nomor_hp' => '081', 'is_active' => true]);

        Order::create([
            'brand_id'         => $brand->id,
            'no_po'            => 'PO-CAL-1',
            'nama_po'          => 'Test Calendar Order',
            'status_po'        => 'on_progress',
            'tanggal_masuk'    => now()->toDateString(),
            'deadline_customer'=> now()->addDays(7)->toDateString(),
            'pelanggan_id'     => Customer::first()->id,
            'total_tagihan'    => 150000,
            'published_at'     => now(),
            'created_by'       => $user->id,
        ]);

        $this->actingAsWithBrand($user, $brand)
            ->get(route('kalender.index'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('Calendar/Index')
                ->where('events.0.noPo', 'PO-CAL-1')
                ->has('events.0.start')
                ->has('events.0.end')
                ->has('events.0.color')
                ->has('events.0.statusLabel')
                ->has('events.0.detailUrl')
                ->has('events.0.progressUrl')
            );
    }

    public function test_calendar_excludes_draft_orders()
    {
        $brand = $this->makeBrand();
        $user  = $this->makeUser('admin_brand', [$brand]);
        Customer::create(['brand_id' => $brand->id, 'kode' => 'C1', 'nama' => 'T', 'nomor_hp' => '081', 'is_active' => true]);

        Order::create([
            'brand_id' => $brand->id, 'no_po' => 'PO-DRAFT', 'nama_po' => 'Draft',
            'status_po' => 'draft', 'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(5)->toDateString(),
            'pelanggan_id' => Customer::first()->id, 'total_tagihan' => 100000,
            'created_by' => $user->id,
        ]);

        $this->actingAsWithBrand($user, $brand)
            ->get(route('kalender.index'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->where('events', []));
    }

    public function test_calendar_isolates_by_brand()
    {
        $brand1 = $this->makeBrand(['kode' => 'BX1', 'nama_brand' => 'Brand Satu']);
        $brand2 = $this->makeBrand(['kode' => 'BX2', 'nama_brand' => 'Brand Dua']);
        $user   = $this->makeUser('superadmin', [$brand1, $brand2]);

        Customer::create(['brand_id' => $brand1->id, 'kode' => 'C1', 'nama' => 'T', 'nomor_hp' => '081', 'is_active' => true]);
        Customer::create(['brand_id' => $brand2->id, 'kode' => 'C2', 'nama' => 'T', 'nomor_hp' => '082', 'is_active' => true]);

        Order::create([
            'brand_id' => $brand1->id, 'no_po' => 'PO-CX1', 'nama_po' => 'PO 1',
            'status_po' => 'published', 'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(5)->toDateString(),
            'pelanggan_id' => Customer::where('brand_id', $brand1->id)->first()->id,
            'total_tagihan' => 100000, 'published_at' => now(), 'created_by' => $user->id,
        ]);
        Order::create([
            'brand_id' => $brand2->id, 'no_po' => 'PO-CX2', 'nama_po' => 'PO 2',
            'status_po' => 'published', 'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(5)->toDateString(),
            'pelanggan_id' => Customer::where('brand_id', $brand2->id)->first()->id,
            'total_tagihan' => 100000, 'published_at' => now(), 'created_by' => $user->id,
        ]);

        $this->actingAsWithBrand($user, $brand1)
            ->get(route('kalender.index'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->where('events', fn ($ev) =>
                count($ev) === 1 &&
                collect($ev)->contains('noPo', 'PO-CX1') &&
                !collect($ev)->contains('noPo', 'PO-CX2')
            ));
    }

    public function test_all_roles_with_order_view_can_access_calendar()
    {
        foreach (['admin_brand', 'admin_reseller', 'admin_produksi', 'admin_keuangan'] as $role) {
            $brand = $this->makeBrand();
            $user  = $this->makeUser($role, [$brand]);

            $this->actingAsWithBrand($user, $brand)
                ->get(route('kalender.index'))
                ->assertOk();
        }
    }

    public function test_calendar_prioritizes_production_dates_over_customer_dates(): void
    {
        $brand = $this->makeBrand();
        $user  = $this->makeUser('admin_brand', [$brand]);
        Customer::create(['brand_id' => $brand->id, 'kode' => 'C1', 'nama' => 'T', 'nomor_hp' => '081', 'is_active' => true]);

        // PO with production dates set
        Order::create([
            'brand_id'             => $brand->id,
            'no_po'                => 'PO-PROD-1',
            'nama_po'              => 'PO 1',
            'status_po'            => 'published',
            'tanggal_masuk'        => '2026-06-10',
            'deadline_customer'    => '2026-06-30',
            'start_production_date'=> '2026-06-12',
            'end_production_date'  => '2026-06-28',
            'pelanggan_id'         => Customer::first()->id,
            'total_tagihan'        => 100000,
            'published_at'         => now(),
            'created_by'           => $user->id,
        ]);

        // PO without production dates set (fallback to customer dates)
        Order::create([
            'brand_id'             => $brand->id,
            'no_po'                => 'PO-PROD-2',
            'nama_po'              => 'PO 2',
            'status_po'            => 'published',
            'tanggal_masuk'        => '2026-06-10',
            'deadline_customer'    => '2026-06-30',
            'pelanggan_id'         => Customer::first()->id,
            'total_tagihan'        => 100000,
            'published_at'         => now(),
            'created_by'           => $user->id,
        ]);

        $this->actingAsWithBrand($user, $brand)
            ->get(route('kalender.index'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('Calendar/Index')
                ->where('events', function ($ev) {
                    $po1 = collect($ev)->firstWhere('noPo', 'PO-PROD-1');
                    $po2 = collect($ev)->firstWhere('noPo', 'PO-PROD-2');

                    return $po1['start'] === '2026-06-12'
                        && $po1['end'] === '2026-06-28'
                        && $po2['start'] === '2026-06-10'
                        && $po2['end'] === '2026-06-30';
                })
            );
    }

    public function test_calendar_filters_by_brand_query_parameter(): void
    {
        $brandA = $this->makeBrand(['nama_brand' => 'Brand A']);
        $brandB = $this->makeBrand(['nama_brand' => 'Brand B']);
        $user = $this->makeUser('superadmin');

        $customerA = Customer::create([
            'brand_id' => $brandA->id,
            'kode' => 'CUST-A', 'nama' => 'Customer A', 'nomor_hp' => '081111',
            'is_active' => true,
        ]);

        $customerB = Customer::create([
            'brand_id' => $brandB->id,
            'kode' => 'CUST-B', 'nama' => 'Customer B', 'nomor_hp' => '082222',
            'is_active' => true,
        ]);

        $orderA = Order::create([
            'brand_id' => $brandA->id,
            'pelanggan_id' => $customerA->id,
            'no_po' => 'PO-A-001',
            'nama_po' => 'PO Brand A',
            'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(14)->toDateString(),
            'total_tagihan' => 100000,
            'created_by' => $user->id,
        ]);

        $orderB = Order::create([
            'brand_id' => $brandB->id,
            'pelanggan_id' => $customerB->id,
            'no_po' => 'PO-B-001',
            'nama_po' => 'PO Brand B',
            'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(14)->toDateString(),
            'total_tagihan' => 200000,
            'created_by' => $user->id,
        ]);

        // 1. Visit calendar without filter (all brands)
        $response = $this->actingAsWithBrand($user, $brandA)
            ->get(route('kalender.index', ['brand_id' => 'all']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Calendar/Index')
            ->has('events', 2)
            ->where('filters.brand_id', 'all')
        );

        // 2. Filter by Brand A
        $response = $this->actingAsWithBrand($user, $brandA)
            ->get(route('kalender.index', ['brand_id' => $brandA->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Calendar/Index')
            ->has('events', 1)
            ->where('events.0.id', $orderA->id)
            ->where('filters.brand_id', $brandA->id)
        );
    }
}
