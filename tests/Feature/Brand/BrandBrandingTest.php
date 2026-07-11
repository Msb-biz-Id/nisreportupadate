<?php

namespace Tests\Feature\Brand;

use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_reseller_hub_isolates_branding_from_regular_parent_brand(): void
    {
        // 1. Create a regular parent brand
        $parentBrand = Brand::create([
            'nama_brand' => 'Regular Parent Brand',
            'kode' => 'RPB',
            'tagline' => 'Parent Tagline',
            'email' => 'parent@brand.com',
            'no_hp' => '0812345678',
            'alamat' => 'Parent Address',
            'logo' => 'regular_logo.png',
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
            'logo' => null,
            'brand_type' => Brand::TYPE_RESELLER_HUB,
            'parent_brand_id' => $parentBrand->id,
            'is_active' => true,
        ]);

        // 3. Assert parent brand returns itself
        $parentHeader = $parentBrand->getHeaderBrand();
        $this->assertEquals($parentBrand->id, $parentHeader->id);
        $this->assertEquals('Regular Parent Brand', $parentHeader->nama_brand);
        $this->assertEquals('regular_logo.png', $parentHeader->logo);

        // 4. Assert reseller hub does NOT delegate to the regular parent brand
        $hubHeader = $resellerHub->getHeaderBrand();
        $this->assertNotEquals($parentBrand->id, $hubHeader->id);
        $this->assertEquals('Reseller Hub Brand', $hubHeader->nama_brand);
        $this->assertEquals('hub@brand.com', $hubHeader->email);
        $this->assertEquals('Hub Tagline', $hubHeader->tagline);
        // Logo must not inherit the parent regular brand logo, nor fall back to logo.svg (if logo.svg is system default)
        $this->assertNull($hubHeader->logo);
    }

    public function test_reseller_branch_delegates_header_brand_resolution_to_reseller_hub_only(): void
    {
        // 1. Create a reseller hub parent brand
        $resellerHub = Brand::create([
            'nama_brand' => 'Reseller Hub Brand',
            'kode' => 'RHB',
            'tagline' => 'Hub Tagline',
            'email' => 'hub@brand.com',
            'logo' => 'hub_logo.png',
            'brand_type' => Brand::TYPE_RESELLER_HUB,
            'is_active' => true,
        ]);

        // 2. Create a grandchild reseller branch brand
        $resellerBranch = Brand::create([
            'nama_brand' => 'Reseller Branch Brand',
            'kode' => 'RBB',
            'brand_type' => Brand::TYPE_RESELLER_BRANCH,
            'parent_brand_id' => $resellerHub->id,
            'is_active' => true,
        ]);

        // 3. Assert reseller branch delegates to reseller hub parent
        $branchHeader = $resellerBranch->getHeaderBrand();
        $this->assertEquals($resellerHub->id, $branchHeader->id);
        $this->assertEquals('Reseller Hub Brand', $branchHeader->nama_brand);
        $this->assertEquals('hub@brand.com', $branchHeader->email);
        $this->assertEquals('Hub Tagline', $branchHeader->tagline);
        $this->assertEquals('hub_logo.png', $branchHeader->logo);
    }

    public function test_pdf_rendering_shows_initials_for_brand_with_no_logo(): void
    {
        $brand = Brand::create([
            'nama_brand' => 'Drive Sportwear',
            'kode' => 'DRV',
            'tagline' => 'No Logo Brand',
            'email' => 'drive@test.com',
            'brand_type' => Brand::TYPE_REGULAR,
            'logo' => null,
            'is_active' => true,
        ]);

        $viewHtml = view('pdf.components.kop', [
            'brand' => $brand,
            'logoData' => '', // Simulated from logoDataUri(null)
        ])->render();

        $this->assertStringContainsString('background-color', $viewHtml);
        $this->assertStringContainsString('D', $viewHtml); // The initial "D" from "Drive Sportwear"
        $this->assertStringNotContainsString('<img', $viewHtml);
    }

    public function test_reseller_branding_resolution_bypasses_system_logo_and_renders_initials(): void
    {
        // 1. Seed reseller_branding with logo.svg
        \App\Models\Settings\SystemSetting::set('reseller_branding', 'logo', 'logo.svg');
        \App\Models\Settings\SystemSetting::set('reseller_branding', 'nama_brand', 'INDOWAREHOUSE');

        // 2. Create reseller hub brand without its own logo
        $resellerHub = Brand::create([
            'nama_brand' => 'Telulas Reseller',
            'kode' => 'TLS',
            'brand_type' => Brand::TYPE_RESELLER_HUB,
            'logo' => null,
            'is_active' => true,
        ]);

        $headerBrand = $resellerHub->getHeaderBrand();
        
        // The resolved header brand should have no logo (logo.svg is bypassed)
        $this->assertNull($headerBrand->logo);

        // Render the kop view component
        $viewHtml = view('pdf.components.kop', [
            'brand' => $headerBrand,
            'logoData' => '', // Resolved via logoDataUri(null)
        ])->render();

        $this->assertStringContainsString('background-color', $viewHtml);
        $this->assertStringContainsString('T', $viewHtml); // The initial "T" from "Telulas Reseller"
        $this->assertStringNotContainsString('<img', $viewHtml);
    }
}
