<?php

namespace Tests\Feature\Brand;

use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_list_brands(): void
    {
        $sa = $this->makeUser('superadmin');
        $this->makeBrand(['nama_brand' => 'Alpha']);

        $this->actingAs($sa)->get(route('brands.index'))->assertOk();
    }

    public function test_superadmin_can_create_brand(): void
    {
        $sa = $this->makeUser('superadmin');

        $this->actingAs($sa)
            ->post(route('brands.store'), [
                'nama_brand' => 'Brand Baru',
                'kode' => 'NEW',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('brands', ['kode' => 'NEW', 'nama_brand' => 'Brand Baru']);
    }

    public function test_brand_kode_must_be_unique(): void
    {
        $sa = $this->makeUser('superadmin');
        $this->makeBrand(['kode' => 'DUP']);

        $this->actingAs($sa)
            ->post(route('brands.store'), [
                'nama_brand' => 'Other',
                'kode' => 'DUP',
            ])
            ->assertSessionHasErrors('kode');
    }

    public function test_admin_brand_cannot_create_brand(): void
    {
        $user = $this->makeUser('admin_brand', [$this->makeBrand()]);

        $this->actingAs($user)
            ->post(route('brands.store'), ['nama_brand' => 'X', 'kode' => 'X'])
            ->assertForbidden();
    }

    public function test_toggle_brand_active(): void
    {
        $sa = $this->makeUser('superadmin');
        $brand = $this->makeBrand(['is_active' => true]);

        $this->actingAs($sa)
            ->post(route('brands.toggle', $brand->id))
            ->assertRedirect();

        $this->assertFalse($brand->fresh()->is_active);
    }

    public function test_cannot_delete_brand_with_users(): void
    {
        $sa = $this->makeUser('superadmin');
        $brand = $this->makeBrand();
        $this->makeUser('admin_brand', [$brand]);

        $this->actingAs($sa)
            ->delete(route('brands.destroy', $brand->id));

        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'deleted_at' => null]);
    }
}
