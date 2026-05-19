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

    public function test_calendar_respects_brand_isolation()
    {
        $brand1 = $this->makeBrand(['kode' => 'BX1']);
        $brand2 = $this->makeBrand(['kode' => 'BX2']);
        $user   = $this->makeUser('superadmin', [$brand1, $brand2]);

        Customer::create(['brand_id' => $brand1->id, 'kode' => 'C1', 'nama' => 'T', 'nomor_hp' => '081', 'is_active' => true]);
        Customer::create(['brand_id' => $brand2->id, 'kode' => 'C2', 'nama' => 'T', 'nomor_hp' => '082', 'is_active' => true]);

        Order::create([
            'brand_id' => $brand1->id, 'no_po' => 'PO-CX1', 'nama_po' => 'Brand1',
            'status_po' => 'published', 'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(5)->toDateString(),
            'pelanggan_id' => Customer::where('brand_id', $brand1->id)->first()->id,
            'total_tagihan' => 100000, 'published_at' => now(), 'created_by' => $user->id,
        ]);
        Order::create([
            'brand_id' => $brand2->id, 'no_po' => 'PO-CX2', 'nama_po' => 'Brand2',
            'status_po' => 'published', 'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(5)->toDateString(),
            'pelanggan_id' => Customer::where('brand_id', $brand2->id)->first()->id,
            'total_tagihan' => 100000, 'published_at' => now(), 'created_by' => $user->id,
        ]);

        $this->actingAsWithBrand($user, $brand1)
            ->get(route('kalender.index'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->where('events', fn ($ev) =>
                count($ev) === 1 && $ev[0]['noPo'] === 'PO-CX1'
            ));
    }

    public function test_all_roles_with_order_view_can_access_calendar()
    {
        foreach (['admin_brand', 'reseller', 'admin_produksi', 'admin_keuangan'] as $role) {
            $brand = $this->makeBrand();
            $user  = $this->makeUser($role, [$brand]);

            $this->actingAsWithBrand($user, $brand)
                ->get(route('kalender.index'))
                ->assertOk();
        }
    }
}
