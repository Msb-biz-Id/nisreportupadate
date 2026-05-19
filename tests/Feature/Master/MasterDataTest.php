<?php

namespace Tests\Feature\Master;

use App\Models\Master\BahanKain;
use App\Models\Master\KategoriOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_brand_can_list_master_data(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('admin_brand', [$brand]);

        BahanKain::create(['nama' => 'Polyester', 'is_active' => true]);

        $this->actingAsWithBrand($user, $brand)
            ->get(route('master.index', 'bahan-kain'))
            ->assertOk();
    }

    public function test_admin_brand_can_create_global_master(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('admin_brand', [$brand]);

        $this->actingAsWithBrand($user, $brand)
            ->post(route('master.store', 'bahan-kain'), [
                'nama' => 'Microfiber Test',
                'deskripsi' => 'Test bahan',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('bahan_kains', ['nama' => 'Microfiber Test']);
    }

    public function test_brand_scoped_master_assigns_brand_id_automatically(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('admin_brand', [$brand]);

        $this->actingAsWithBrand($user, $brand)
            ->post(route('master.store', 'kategori-order'), [
                'nama' => 'Kategori Tim',
                'is_active' => true,
            ])
            ->assertRedirect();

        $kategori = KategoriOrder::where('nama', 'Kategori Tim')->first();
        $this->assertNotNull($kategori);
        $this->assertEquals($brand->id, $kategori->brand_id);
    }

    public function test_reseller_cannot_manage_master_data(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('reseller', [$brand]);

        $this->actingAsWithBrand($user, $brand)
            ->post(route('master.store', 'bahan-kain'), ['nama' => 'Test'])
            ->assertForbidden();
    }

    public function test_invalid_master_slug_returns_404(): void
    {
        $sa = $this->makeUser('superadmin', [$this->makeBrand()]);

        $this->actingAs($sa)
            ->get(route('master.index', 'tidak-ada-slug'))
            ->assertNotFound();
    }
}
