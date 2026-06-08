<?php

namespace Tests\Feature\Master;

use App\Models\Master\BahanKain;
use App\Models\Master\SumberOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_brand_can_list_brand_master_data(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('admin_brand', [$brand]);

        $this->actingAsWithBrand($user, $brand)
            ->get(route('master.index', 'sumber-order'))
            ->assertOk();
    }

    public function test_admin_brand_cannot_access_global_master(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('admin_brand', [$brand]);

        $this->actingAsWithBrand($user, $brand)
            ->get(route('master.index', 'bahan-kain'))
            ->assertForbidden();
    }

    public function test_admin_brand_can_create_brand_scoped_master(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('admin_brand', [$brand]);

        $this->actingAsWithBrand($user, $brand)
            ->post(route('master.store', 'sumber-order'), [
                'nama' => 'Sumber Toko',
                'is_active' => true,
            ])
            ->assertRedirect();

        $sumber = SumberOrder::where('nama', 'Sumber Toko')->first();
        $this->assertNotNull($sumber);
        $this->assertEquals($brand->id, $sumber->brand_id);
    }

    public function test_admin_brand_cannot_create_global_master(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('admin_brand', [$brand]);

        $this->actingAsWithBrand($user, $brand)
            ->post(route('master.store', 'bahan-kain'), [
                'nama' => 'Microfiber Test',
                'is_active' => true,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('bahan_kains', ['nama' => 'Microfiber Test']);
    }

    public function test_owner_can_create_global_master(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('owner', [$brand]);

        $this->actingAsWithBrand($user, $brand)
            ->post(route('master.store', 'bahan-kain'), [
                'nama' => 'Microfiber Owner',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('bahan_kains', ['nama' => 'Microfiber Owner']);
    }

    public function test_admin_produksi_can_manage_global_master_data(): void
    {
        // admin_produksi punya master.production → bisa akses group global (bahan kain, size, dll)
        $brand = $this->makeBrand();
        $user = $this->makeUser('admin_produksi', [$brand]);

        $this->actingAsWithBrand($user, $brand)
            ->post(route('master.store', 'bahan-kain'), ['nama' => 'Test Bahan'])
            ->assertRedirect();
    }

    public function test_invalid_master_slug_returns_404(): void
    {
        $sa = $this->makeUser('superadmin', [$this->makeBrand()]);

        $this->actingAs($sa)
            ->get(route('master.index', 'tidak-ada-slug'))
            ->assertNotFound();
    }
}
