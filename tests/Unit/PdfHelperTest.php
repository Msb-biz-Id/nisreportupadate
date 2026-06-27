<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Support\PdfHelper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Order\OrderController;
use App\Http\Controllers\Order\InvoiceController;
use ReflectionMethod;

class PdfHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Setup local storage disk for testing if needed
        Storage::fake('public');
    }

    public function test_resolve_image_returns_empty_string_for_null_or_empty_path(): void
    {
        $this->assertEquals('', PdfHelper::resolveImageForPdf(null));
        $this->assertEquals('', PdfHelper::resolveImageForPdf(''));
    }

    public function test_resolve_image_returns_empty_string_for_non_existent_file(): void
    {
        $this->assertEquals('', PdfHelper::resolveImageForPdf('non_existent_image.png'));
    }

    public function test_resolve_image_resolves_valid_file_path(): void
    {
        // Place a dummy file in the public storage path
        $fileName = 'test_image.png';
        $fullPath = storage_path('app/public/' . $fileName);
        
        // Ensure folder exists
        @mkdir(dirname($fullPath), 0777, true);
        file_put_contents($fullPath, 'dummy data');

        try {
            $resolved = PdfHelper::resolveImageForPdf($fileName);
            $this->assertNotEmpty($resolved);
            $this->assertStringStartsWith('data:', $resolved);
            $this->assertStringContainsString('base64,', $resolved);
        } finally {
            @unlink($fullPath);
        }
    }

    public function test_resolve_image_resolves_urls(): void
    {
        $fileName = 'test_image_url.png';
        $fullPath = storage_path('app/public/' . $fileName);
        
        @mkdir(dirname($fullPath), 0777, true);
        file_put_contents($fullPath, 'dummy data');

        try {
            $url = 'http://localhost/storage/' . $fileName;
            $resolved = PdfHelper::resolveImageForPdf($url);
            $this->assertNotEmpty($resolved);
            $this->assertStringStartsWith('data:', $resolved);
            $this->assertStringContainsString('base64,', $resolved);
        } finally {
            @unlink($fullPath);
        }
    }

    public function test_logo_data_uri_in_order_controller(): void
    {
        $fileName = 'test_logo.png';
        $fullPath = storage_path('app/public/' . $fileName);
        
        @mkdir(dirname($fullPath), 0777, true);
        file_put_contents($fullPath, 'dummy content');

        try {
            $controller = app(OrderController::class);
            $method = new ReflectionMethod(OrderController::class, 'logoDataUri');
            $method->setAccessible(true);

            // Test non-existent logo
            $emptyResult = $method->invoke($controller, 'missing_logo.png');
            $this->assertEquals('', $emptyResult);

            // Test existent logo
            $logoPath = 'brand_logos/' . $fileName;
            $fullLogoPath = storage_path('app/public/' . $logoPath);
            @mkdir(dirname($fullLogoPath), 0777, true);
            file_put_contents($fullLogoPath, 'logo data');

            $result = $method->invoke($controller, $logoPath);
            $this->assertStringStartsWith('data:image/png;base64,', $result);
            @unlink($fullLogoPath);
        } finally {
            @unlink($fullPath);
        }
    }

    public function test_logo_data_uri_in_invoice_controller(): void
    {
        $fileName = 'test_logo.png';
        
        $controller = app(InvoiceController::class);
        $method = new ReflectionMethod(InvoiceController::class, 'logoDataUri');
        $method->setAccessible(true);

        // Test non-existent logo
        $emptyResult = $method->invoke($controller, 'missing_logo.png');
        $this->assertEquals('', $emptyResult);

        // Test existent logo
        $logoPath = 'brand_logos/' . $fileName;
        $fullLogoPath = storage_path('app/public/' . $logoPath);
        @mkdir(dirname($fullLogoPath), 0777, true);
        file_put_contents($fullLogoPath, 'logo data');

        try {
            $result = $method->invoke($controller, $logoPath);
            $this->assertStringStartsWith('data:image/png;base64,', $result);
        } finally {
            @unlink($fullLogoPath);
        }
    }

    public function test_format_text_detects_javanese_script(): void
    {
        $javaneseText = "ꦲꦤꦕꦼꦫꦏꦴ"; // Hanacaraka characters
        $formatted = PdfHelper::formatText($javaneseText);
        $this->assertStringContainsString('class="javanese-font"', $formatted);

        $formattedWeb = PdfHelper::formatTextWeb($javaneseText);
        $this->assertStringContainsString('class="javanese-font"', $formattedWeb);
    }
}
