<?php

namespace Tests\Feature\Master;

use App\Models\Brand;
use App\Models\Master\Customer;
use App\Models\Master\CustomerType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CustomerImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Enable import by default for testing
        \App\Models\Settings\SystemSetting::set('system', 'customer_import_enabled', '1');
    }

    public function test_admin_brand_can_download_import_template(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('admin_brand', [$brand]);

        $response = $this->actingAsWithBrand($user, $brand)
            ->get(route('master.pelanggan.import-template'));

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'attachment; filename="format_baku_pelanggan.csv"');
        $this->assertStringContainsString('customer_nama,customer_kode,customer_nomor_hp', $response->streamedContent());
    }

    public function test_admin_brand_can_import_customers_via_csv(): void
    {
        $brand = $this->makeBrand(['kode' => 'ALG', 'nama_brand' => 'Allegiant']);
        $user = $this->makeUser('admin_brand', [$brand]);

        $csvContent = "customer_nama,customer_kode,customer_nomor_hp,customer_email,customer_type,customer_detail_alamat,customer_kodepos,customer_notes,provinsi_nama,kabupaten_nama,kecamatan_nama,desa_nama,brand_code\n";
        $csvContent .= "Test Customer One,CUST-IMP-001,081234567801,one@example.com,VIP,Jl. Merdeka 1,12345,Important,JAWA BARAT,KOTA BANDUNG,COBLONG,DAGO,ALG\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tempFile, $csvContent);

        $file = new UploadedFile(
            $tempFile,
            'customers.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->actingAsWithBrand($user, $brand)
            ->post(route('master.pelanggan.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check if customer type was created
        $cType = CustomerType::where('brand_id', $brand->id)->where('nama', 'VIP')->first();
        $this->assertNotNull($cType);

        // Check if customer was created
        $customer = Customer::where('nomor_hp', '081234567801')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Test Customer One', $customer->nama);
        $this->assertEquals('CUST-IMP-001', $customer->kode);
        $this->assertEquals($brand->id, $customer->brand_id);

        @unlink($tempFile);
    }

    public function test_admin_brand_cannot_import_customer_if_brand_missing(): void
    {
        $brand = $this->makeBrand(['kode' => 'ALG']);
        $user = $this->makeUser('admin_brand', [$brand]);

        // Brand code NOTFOUND doesn't exist
        $csvContent = "customer_nama,customer_kode,customer_nomor_hp,customer_email,customer_type,customer_detail_alamat,customer_kodepos,customer_notes,provinsi_nama,kabupaten_nama,kecamatan_nama,desa_nama,brand_code\n";
        $csvContent .= "Test Customer One,CUST-IMP-001,081234567801,one@example.com,VIP,Jl. Merdeka 1,12345,Important,JAWA BARAT,KOTA BANDUNG,COBLONG,DAGO,NOTFOUND\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tempFile, $csvContent);

        $file = new UploadedFile(
            $tempFile,
            'customers.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->actingAsWithBrand($user, $brand)
            ->post(route('master.pelanggan.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect();
        // Since brand wasn't found, it should return with warning
        $response->assertSessionHas('warning');

        // Check customer was NOT created
        $customer = Customer::where('nomor_hp', '081234567801')->first();
        $this->assertNull($customer);

        @unlink($tempFile);
    }

    public function test_admin_brand_can_download_brand_import_template(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('superadmin', [$brand]);

        $response = $this->actingAsWithBrand($user, $brand)
            ->get(route('brands.import-template'));

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'attachment; filename="format_baku_brand.csv"');
        $this->assertStringContainsString('kode,nama_brand,brand_type', $response->streamedContent());
    }

    public function test_admin_brand_can_import_brands_via_csv(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('superadmin', [$brand]);

        $csvContent = "kode,nama_brand,brand_type,tagline,deskripsi,email,no_hp,alamat,warna_primary\n";
        $csvContent .= "NEWBRD,Brand Baru,regular,Tagline Baru,Deskripsi Baru,new@brand.local,08123456,Bandung,#ff0000\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tempFile, $csvContent);

        $file = new UploadedFile(
            $tempFile,
            'brands.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->actingAsWithBrand($user, $brand)
            ->post(route('brands.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check if brand was created
        $newBrand = Brand::where('kode', 'NEWBRD')->first();
        $this->assertNotNull($newBrand);
        $this->assertEquals('Brand Baru', $newBrand->nama_brand);
        $this->assertEquals('regular', $newBrand->brand_type);
        $this->assertEquals('#ff0000', $newBrand->warna_primary);

        @unlink($tempFile);
    }

    public function test_artisan_command_can_import_brands(): void
    {
        $csvContent = "kode,nama_brand,brand_type,tagline,deskripsi,email,no_hp,alamat,warna_primary\n";
        $csvContent .= "CMDBRD,Brand Cmd,reseller_hub,Tagline,Deskripsi,cmd@brand.local,08123456,Jakarta,#00ff00\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tempFile, $csvContent);

        $exitCode = Artisan::call('import:brands', [
            'file' => $tempFile,
        ]);

        $this->assertEquals(0, $exitCode);

        // Check if brand was created
        $newBrand = Brand::where('kode', 'CMDBRD')->first();
        $this->assertNotNull($newBrand);
        $this->assertEquals('Brand Cmd', $newBrand->nama_brand);
        $this->assertEquals('reseller_hub', $newBrand->brand_type);

        @unlink($tempFile);
    }

    public function test_artisan_command_can_import_customers(): void
    {
        $brand = $this->makeBrand(['kode' => 'ALG']);

        $csvContent = "customer_nama,customer_kode,customer_nomor_hp,customer_email,customer_type,customer_detail_alamat,customer_kodepos,customer_notes,provinsi_nama,kabupaten_nama,kecamatan_nama,desa_nama,brand_code\n";
        $csvContent .= "Test Cmd Customer,CUST-CMD-01,081234567802,cmd@example.com,Reseller,Jl. Sudirman 2,54321,Notes,JAWA BARAT,KOTA BANDUNG,COBLONG,DAGO,ALG\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tempFile, $csvContent);

        $exitCode = Artisan::call('import:customers', [
            'file' => $tempFile,
        ]);

        $this->assertEquals(0, $exitCode);

        // Check if customer was created
        $customer = Customer::where('nomor_hp', '081234567802')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Test Cmd Customer', $customer->nama);
        $this->assertEquals('CUST-CMD-01', $customer->kode);
        $this->assertEquals($brand->id, $customer->brand_id);

        @unlink($tempFile);
    }
}
