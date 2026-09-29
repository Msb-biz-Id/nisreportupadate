<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\User;
use App\Support\FileReferenceChecker;
use App\Support\PdfHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImagePipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_image_preserves_png_and_jpg_format()
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
        $storage = Storage::fake('public');
        $user = $this->makeUser('superadmin');

        // Test PNG Upload
        $pngFile = UploadedFile::fake()->image('test_logo.png', 400, 300);
        $response = $this->actingAs($user)->postJson(route('uploads.image'), [
            'file' => $pngFile,
            'purpose' => 'brands',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $pathPng = $response->json('path');
        $this->assertStringEndsWith('.png', $pathPng);
        $storage->assertExists($pathPng);

        // Test JPG Upload
        $jpgFile = UploadedFile::fake()->image('test_photo.jpg', 1600, 1200);
        $responseJpg = $this->actingAs($user)->postJson(route('uploads.image'), [
            'file' => $jpgFile,
            'purpose' => 'products',
        ]);

        $responseJpg->assertStatus(200);
        $pathJpg = $responseJpg->json('path');
        $this->assertStringEndsWith('.jpg', $pathJpg);
        $storage->assertExists($pathJpg);
    }

    public function test_file_reference_checker_prevents_unintended_physical_deletion()
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
        $storage = Storage::fake('public');
        $user = $this->makeUser('superadmin');

        $imagePath = 'orders/test-brand/po-001/sample.jpg';
        $storage->put($imagePath, 'fake-image-binary');

        // Initially no DB reference
        $this->assertFalse(FileReferenceChecker::isReferenced($imagePath));

        // Create Brand referencing this image
        $brand = $this->makeBrand(['logo' => $imagePath]);
        $this->assertTrue(FileReferenceChecker::isReferenced($imagePath));

        // Attempt deletion via controller
        $response = $this->actingAs($user)->deleteJson(route('uploads.image.destroy'), [
            'path' => $imagePath,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'deleted_physical' => false]);
        
        // Physical file must still exist in storage disk!
        $storage->assertExists($imagePath);

        // Remove DB reference
        $brand->update(['logo' => null]);
        $this->assertFalse(FileReferenceChecker::isReferenced($imagePath));

        // Attempt deletion again
        $response2 = $this->actingAs($user)->deleteJson(route('uploads.image.destroy'), [
            'path' => $imagePath,
        ]);

        $response2->assertStatus(200);
        $response2->assertJson(['success' => true, 'deleted_physical' => true]);
        
        // Physical file should now be removed from storage disk
        $storage->assertMissing($imagePath);
    }

    public function test_pdf_helper_resolves_image_to_physical_uri()
    {
        Storage::fake('public');
        
        $jpgPath = 'orders/sample.jpg';
        Storage::disk('public')->put($jpgPath, 'fake-jpg-content');
        
        $diskPath = Storage::disk('public')->path($jpgPath);
        $resolvedJpg = PdfHelper::resolveImageForPdf($diskPath);

        $this->assertStringStartsWith('file:///', $resolvedJpg);
    }
}
