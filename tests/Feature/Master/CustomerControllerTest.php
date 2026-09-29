<?php

namespace Tests\Feature\Master;

use App\Models\Brand;
use App\Models\Master\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_update_handles_null_or_empty_kode(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('admin_brand', [$brand]);

        $customer = Customer::create([
            'brand_id' => $brand->id,
            'kode' => 'CUST-001',
            'nama' => 'John Doe',
            'nomor_hp' => '08123456789',
            'is_active' => true,
        ]);

        // Request update with empty kode
        $response = $this->actingAsWithBrand($user, $brand)
            ->put(route('master.pelanggan.update', $customer), [
                'nama' => 'John Doe Updated',
                'nomor_hp' => '08123456789',
                'kode' => '', // Empty string, which Laravel converts to null
                'is_active' => true,
            ]);

        $response->assertRedirect();
        
        $customer->refresh();
        $this->assertEquals('John Doe Updated', $customer->nama);
        // The code should not be changed to null, it should keep the old one
        $this->assertEquals('CUST-001', $customer->kode);
    }
}
