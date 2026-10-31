<?php

namespace Tests\Feature\Brand;

use App\Models\Brand;
use App\Support\BrandContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_switch_to_all_brands_if_authorized(): void
    {
        $brand1 = $this->makeBrand(['nama_brand' => 'Brand Alpha']);
        $brand2 = $this->makeBrand(['nama_brand' => 'Brand Beta']);
        
        $owner = $this->makeUser('owner', [$brand1, $brand2]);

        $this->actingAsWithBrand($owner, $brand1);

        // Switch to 'all'
        $this->post(route('brand.switch', 'all'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals('all', session('current_brand_id'));
    }

    public function test_user_cannot_switch_to_unauthorized_brand(): void
    {
        $brand1 = $this->makeBrand();
        $brand2 = $this->makeBrand();
        
        $user = $this->makeUser('admin_brand', [$brand1]);

        $this->actingAsWithBrand($user, $brand1);

        // Try to switch to unauthorized brand2
        $this->post(route('brand.switch', $brand2->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertEquals($brand1->id, session('current_brand_id'));
    }
}
