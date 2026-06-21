<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        return [
            'nama_brand' => fake()->company(),
            'kode' => strtoupper(fake()->unique()->lexify('BRAND???')),
            'tagline' => fake()->sentence(),
            'deskripsi' => fake()->paragraph(),
            'email' => fake()->companyEmail(),
            'no_hp' => fake()->phoneNumber(),
            'alamat' => fake()->address(),
            'instagram' => 'https://instagram.com/' . fake()->userName(),
            'facebook' => 'https://facebook.com/' . fake()->userName(),
            'tiktok' => '@' . fake()->userName(),
            'whatsapp' => fake()->phoneNumber(),
            'website' => fake()->url(),
            'timezone' => 'Asia/Jakarta',
            'currency' => 'IDR',
            'warna_primary' => fake()->hexColor(),
            'is_active' => true,
            'brand_type' => Brand::TYPE_REGULAR,
            'parent_brand_id' => null,
            'min_dp_percentage' => 30.0,
            'created_by' => 1,
        ];
    }

    public function resellerHub(): static
    {
        return $this->state(fn (array $attributes) => [
            'brand_type' => Brand::TYPE_RESELLER_HUB,
        ]);
    }

    public function resellerBranch(): static
    {
        return $this->state(fn (array $attributes) => [
            'brand_type' => Brand::TYPE_RESELLER_BRANCH,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}