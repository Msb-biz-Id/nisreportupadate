<?php

namespace Tests\Feature\Production;

use App\Models\Master\Customer;
use App\Models\Order\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KanbanMoveTest extends TestCase
{
    use RefreshDatabase;

    private function makePoWithStatus(string $status): Order
    {
        $brand = $this->makeBrand();
        Customer::create(['brand_id' => $brand->id, 'kode' => 'C1', 'nama' => 'T', 'nomor_hp' => '081', 'is_active' => true]);

        return Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-MV-' . strtoupper(uniqid()),
            'nama_po' => 'Move test',
            'status_po' => $status,
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => Customer::first()->id,
            'total_tagihan' => 100000,
            'published_at' => now(),
            'created_by' => $this->makeUser('superadmin')->id,
        ]);
    }

    public function test_valid_transition_published_to_on_progress(): void
    {
        $order = $this->makePoWithStatus('published');
        $produksi = $this->makeUser('admin_produksi', [$order->brand]);

        $this->actingAsWithBrand($produksi, $order->brand)
            ->putJson(route('produksi.move-status', $order->id), ['to_status' => 'on_progress'])
            ->assertOk()
            ->assertJson(['success' => true, 'from' => 'published', 'to' => 'on_progress']);

        $this->assertEquals('on_progress', $order->fresh()->status_po);
    }

    public function test_valid_transition_siap_dikirim_to_sudah_dikirim(): void
    {
        $order = $this->makePoWithStatus('siap_dikirim');
        $order->update(['is_lunas' => true]);
        $produksi = $this->makeUser('admin_produksi', [$order->brand]);

        $this->actingAsWithBrand($produksi, $order->brand)
            ->putJson(route('produksi.move-status', $order->id), ['to_status' => 'sudah_dikirim'])
            ->assertOk();

        $this->assertEquals('sudah_dikirim', $order->fresh()->status_po);
    }

    public function test_transition_siap_dikirim_to_sudah_dikirim_blocked_if_not_lunas(): void
    {
        $order = $this->makePoWithStatus('siap_dikirim');
        $produksi = $this->makeUser('admin_produksi', [$order->brand]);

        $this->actingAsWithBrand($produksi, $order->brand)
            ->putJson(route('produksi.move-status', $order->id), ['to_status' => 'sudah_dikirim'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonFragment(['error' => 'Gagal memindahkan. Konfirmasi LUNAS dari Keuangan diperlukan terlebih dahulu sebelum pesanan dapat dikirim.']);

        $this->assertNotEquals('sudah_dikirim', $order->fresh()->status_po);
    }

    public function test_transition_siap_dikirim_to_sudah_dikirim_allowed_if_special_order_even_if_not_lunas(): void
    {
        $order = $this->makePoWithStatus('siap_dikirim');
        $order->update(['is_special_order' => true]);
        $produksi = $this->makeUser('admin_produksi', [$order->brand]);

        $this->actingAsWithBrand($produksi, $order->brand)
            ->putJson(route('produksi.move-status', $order->id), ['to_status' => 'sudah_dikirim'])
            ->assertOk();

        $this->assertEquals('sudah_dikirim', $order->fresh()->status_po);
    }

    public function test_invalid_transition_rejected_with_422(): void
    {
        $order = $this->makePoWithStatus('published');
        $produksi = $this->makeUser('admin_produksi', [$order->brand]);

        $this->actingAsWithBrand($produksi, $order->brand)
            ->putJson(route('produksi.move-status', $order->id), ['to_status' => 'sudah_dikirim'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        // Status tidak berubah
        $this->assertEquals('published', $order->fresh()->status_po);
    }

    public function test_terminal_status_sudah_dikirim_cannot_be_moved(): void
    {
        $order = $this->makePoWithStatus('sudah_dikirim');
        $produksi = $this->makeUser('admin_produksi', [$order->brand]);

        $this->actingAsWithBrand($produksi, $order->brand)
            ->putJson(route('produksi.move-status', $order->id), ['to_status' => 'published'])
            ->assertStatus(422);
    }

    public function test_non_production_user_forbidden(): void
    {
        $order = $this->makePoWithStatus('published');
        $adminBrand = $this->makeUser('admin_brand', [$order->brand]);

        $this->actingAsWithBrand($adminBrand, $order->brand)
            ->putJson(route('produksi.move-status', $order->id), ['to_status' => 'on_progress'])
            ->assertForbidden();
    }
}
