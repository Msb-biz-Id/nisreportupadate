<?php

namespace Tests;

use App\Models\Brand;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    /**
     * Seed roles+permissions sebelum membuat user yang butuh role.
     * Aman dipanggil berkali-kali (firstOrCreate inside).
     */
    protected function seedRoles(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Bikin user dengan role + optional brand access.
     */
    protected function makeUser(string $role = 'admin_brand', array $brands = [], array $attrs = []): User
    {
        $this->seedRoles();

        $user = User::factory()->create(array_merge([
            'password' => Hash::make('password'),
            'is_active' => true,
        ], $attrs));

        $user->syncRoles([$role]);

        if (! empty($brands)) {
            $sync = [];
            foreach ($brands as $i => $brand) {
                $sync[$brand->id] = ['is_default' => $i === 0, 'assigned_at' => now()];
            }
            $user->brands()->sync($sync);
        }

        return $user->fresh(['roles', 'brands']);
    }

    /**
     * Quick brand factory.
     */
    protected function makeBrand(array $attrs = []): Brand
    {
        return Brand::create(array_merge([
            'nama_brand' => 'Test Brand',
            'kode' => 'TST' . strtoupper(substr(uniqid(), -4)),
            'is_active' => true,
        ], $attrs));
    }

    /**
     * Login user dan setel current brand di session.
     */
    protected function actingAsWithBrand(User $user, ?Brand $brand = null): self
    {
        $this->actingAs($user);

        if ($brand) {
            session(['current_brand_id' => $brand->id]);
        } elseif ($user->brands->isNotEmpty()) {
            session(['current_brand_id' => $user->brands->first()->id]);
        }

        return $this;
    }
}
