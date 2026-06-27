<?php

namespace Tests\Feature\Brand;

use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_reseller_hub_delegates_header_brand_resolution_to_parent_brand(): void
    {
        // 1. Create a regular parent brand
        $parentBrand = Brand::create([
            'nama_brand' => 'Regular Parent Brand',
            'kode' => 'RPB',
            'tagline' => 'Parent Tagline',
            'email' => 'parent@brand.com',
            'no_hp' => '0812345678',
            'alamat' => 'Parent Address',
            'brand_type' => Brand::TYPE_REGULAR,
            'is_active' => true,
        ]);

        // 2. Create a child reseller hub brand
        $resellerHub = Brand::create([
            'nama_brand' => 'Reseller Hub Brand',
            'kode' => 'RHB',
            'tagline' => 'Hub Tagline',
            'email' => 'hub@brand.com',
            'no_hp' => '0898765432',
            'alamat' => 'Hub Address',
            'brand_type' => Brand::TYPE_RESELLER_HUB,
            'parent_brand_id' => $parentBrand->id,
            'is_active' => true,
        ]);

        // 3. Assert parent brand returns itself (header brand is not delegated further)
        $parentHeader = $parentBrand->getHeaderBrand();
        $this->assertEquals($parentBrand->id, $parentHeader->id);
        $this->assertEquals('Regular Parent Brand', $parentHeader->nama_brand);
        $this->assertEquals('parent@brand.com', $parentHeader->email);

        // 4. Assert reseller hub delegates to the parent brand
        $hubHeader = $resellerHub->getHeaderBrand();
        $this->assertEquals($parentBrand->id, $hubHeader->id);
        $this->assertEquals('Regular Parent Brand', $hubHeader->nama_brand);
        $this->assertEquals('parent@brand.com', $hubHeader->email);
        $this->assertEquals('Parent Tagline', $hubHeader->tagline);
    }

    public function test_reseller_branch_delegates_header_brand_resolution_recursively(): void
    {
        // 1. Create a regular parent brand
        $parentBrand = Brand::create([
            'nama_brand' => 'Regular Parent Brand',
            'kode' => 'RPB',
            'tagline' => 'Parent Tagline',
            'email' => 'parent@brand.com',
            'brand_type' => Brand::TYPE_REGULAR,
            'is_active' => true,
        ]);

        // 2. Create a child reseller hub brand
        $resellerHub = Brand::create([
            'nama_brand' => 'Reseller Hub Brand',
            'kode' => 'RHB',
            'brand_type' => Brand::TYPE_RESELLER_HUB,
            'parent_brand_id' => $parentBrand->id,
            'is_active' => true,
        ]);

        // 3. Create a grandchild reseller branch brand
        $resellerBranch = Brand::create([
            'nama_brand' => 'Reseller Branch Brand',
            'kode' => 'RBB',
            'brand_type' => Brand::TYPE_RESELLER_BRANCH,
            'parent_brand_id' => $resellerHub->id,
            'is_active' => true,
        ]);

        // 4. Assert reseller branch delegates recursively to parentBrand->parentBrand (the regular parent)
        $branchHeader = $resellerBranch->getHeaderBrand();
        $this->assertEquals($parentBrand->id, $branchHeader->id);
        $this->assertEquals('Regular Parent Brand', $branchHeader->nama_brand);
        $this->assertEquals('parent@brand.com', $branchHeader->email);
        $this->assertEquals('Parent Tagline', $branchHeader->tagline);
    }
}
