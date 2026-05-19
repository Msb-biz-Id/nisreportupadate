<?php

namespace Tests\Feature\Report;

use App\Support\ReportRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_reports_accessible_to_superadmin(): void
    {
        $sa = $this->makeUser('superadmin', [$this->makeBrand()]);

        foreach (array_keys(ReportRegistry::all()) as $slug) {
            $this->actingAs($sa)
                ->get(route('reports.show', $slug))
                ->assertOk();
        }
    }

    public function test_invalid_report_slug_returns_404(): void
    {
        $sa = $this->makeUser('superadmin', [$this->makeBrand()]);
        $this->actingAs($sa)
            ->get(route('reports.show', 'tidak-ada'))
            ->assertNotFound();
    }

    public function test_admin_brand_can_view_reports(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('admin_brand', [$brand]);

        $this->actingAsWithBrand($user, $brand)
            ->get(route('reports.show', 'penjualan-produk'))
            ->assertOk();
    }

    public function test_excel_export_returns_xlsx_response(): void
    {
        $sa = $this->makeUser('superadmin', [$this->makeBrand()]);

        $response = $this->actingAs($sa)
            ->get(route('reports.export.excel', 'penjualan-produk'));

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('content-type'));
    }

    public function test_pdf_export_returns_pdf_response(): void
    {
        $sa = $this->makeUser('superadmin', [$this->makeBrand()]);

        $response = $this->actingAs($sa)
            ->get(route('reports.export.pdf', 'penjualan-produk'));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }
}
