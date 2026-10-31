<?php

namespace Tests\Feature\Master;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegionTest extends TestCase
{
    use RefreshDatabase;

    public function test_provinces_endpoint_queries_local_database(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('superadmin', [$brand]);

        // Seed a temporary province
        DB::table('indonesia_provinces')->insert([
            'code' => '11',
            'name' => 'ACEH',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('regions.provinces'))
            ->assertOk();

        $response->assertJsonFragment([
            'code' => '11',
            'name' => 'ACEH',
        ]);
    }

    public function test_provinces_endpoint_falls_back_to_api_when_empty(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('superadmin', [$brand]);

        // Ensure table is empty
        DB::table('indonesia_provinces')->truncate();

        // Mock external API
        Http::fake([
            '*/provinces.json' => Http::response([
                ['id' => '12', 'name' => 'SUMATERA UTARA']
            ], 200)
        ]);

        $response = $this->actingAs($user)
            ->get(route('regions.provinces'))
            ->assertOk();

        $response->assertJsonFragment([
            'code' => '12',
            'name' => 'SUMATERA UTARA',
        ]);
    }

    public function test_cities_endpoint_queries_local_database(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('superadmin', [$brand]);

        DB::table('indonesia_provinces')->insert([
            'code' => '11',
            'name' => 'ACEH',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('indonesia_cities')->insert([
            'code' => '1101',
            'province_code' => '11',
            'name' => 'KABUPATEN ACEH SELATAN',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('regions.cities', ['province' => '11']))
            ->assertOk();

        $response->assertJsonFragment([
            'code' => '1101',
            'name' => 'KABUPATEN ACEH SELATAN',
        ]);
    }

    public function test_cities_endpoint_falls_back_to_api_when_empty(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('superadmin', [$brand]);

        DB::table('indonesia_cities')->truncate();

        Http::fake([
            '*/regencies/11.json' => Http::response([
                ['id' => '1101', 'name' => 'KABUPATEN ACEH SELATAN']
            ], 200)
        ]);

        $response = $this->actingAs($user)
            ->get(route('regions.cities', ['province' => '11']))
            ->assertOk();

        $response->assertJsonFragment([
            'code' => '1101',
            'name' => 'KABUPATEN ACEH SELATAN',
        ]);
    }

    public function test_districts_endpoint_queries_local_database(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('superadmin', [$brand]);

        DB::table('indonesia_provinces')->insert([
            'code' => '11',
            'name' => 'ACEH',
        ]);

        DB::table('indonesia_cities')->insert([
            'code' => '1101',
            'province_code' => '11',
            'name' => 'KABUPATEN ACEH SELATAN',
        ]);

        DB::table('indonesia_districts')->insert([
            'code' => '1101010',
            'city_code' => '1101',
            'name' => 'BAKONGAN',
        ]);

        $response = $this->actingAs($user)
            ->get(route('regions.districts', ['city' => '1101']))
            ->assertOk();

        $response->assertJsonFragment([
            'code' => '1101010',
            'name' => 'BAKONGAN',
        ]);
    }

    public function test_villages_endpoint_queries_local_database(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('superadmin', [$brand]);

        DB::table('indonesia_provinces')->insert([
            'code' => '11',
            'name' => 'ACEH',
        ]);

        DB::table('indonesia_cities')->insert([
            'code' => '1101',
            'province_code' => '11',
            'name' => 'KABUPATEN ACEH SELATAN',
        ]);

        DB::table('indonesia_districts')->insert([
            'code' => '1101010',
            'city_code' => '1101',
            'name' => 'BAKONGAN',
        ]);

        DB::table('indonesia_villages')->insert([
            'code' => '1101010001',
            'district_code' => '1101010',
            'name' => 'KEUDE BAKONGAN',
        ]);

        $response = $this->actingAs($user)
            ->get(route('regions.villages', ['district' => '1101010']))
            ->assertOk();

        $response->assertJsonFragment([
            'code' => '1101010001',
            'name' => 'KEUDE BAKONGAN',
        ]);
    }
}
