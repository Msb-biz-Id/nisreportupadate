<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_create_user_with_role_and_brands(): void
    {
        $sa = $this->makeUser('superadmin');
        $b1 = $this->makeBrand(['kode' => 'B1']);
        $b2 = $this->makeBrand(['kode' => 'B2']);

        $this->actingAs($sa)
            ->post(route('users.store'), [
                'name' => 'Owner Baru',
                'email' => 'owner.new@test.local',
                'password' => 'rahasia12',
                'password_confirmation' => 'rahasia12',
                'role' => 'owner',
                'brand_ids' => [$b1->id, $b2->id],
                'default_brand_id' => $b1->id,
                'is_active' => true,
            ])
            ->assertRedirect(route('users.index'));

        $u = User::where('email', 'owner.new@test.local')->first();
        $this->assertNotNull($u);
        $this->assertTrue($u->hasRole('owner'));
        $this->assertCount(2, $u->brands);
        $this->assertEquals($b1->id, $u->brands()->wherePivot('is_default', true)->first()->id);
    }

    public function test_default_brand_must_be_in_assigned_brands(): void
    {
        $sa = $this->makeUser('superadmin');
        $b1 = $this->makeBrand(['kode' => 'A']);
        $b2 = $this->makeBrand(['kode' => 'B']);

        $this->actingAs($sa)
            ->post(route('users.store'), [
                'name' => 'X',
                'email' => 'x@test.local',
                'password' => 'rahasia12',
                'password_confirmation' => 'rahasia12',
                'role' => 'admin_brand',
                'brand_ids' => [$b1->id],
                'default_brand_id' => $b2->id, // bukan di brand_ids
                'is_active' => true,
            ])
            ->assertSessionHasErrors('default_brand_id');
    }

    public function test_owner_cannot_assign_superadmin_role(): void
    {
        $brand = $this->makeBrand();
        $owner = $this->makeUser('owner', [$brand]);

        $this->actingAs($owner)
            ->post(route('users.store'), [
                'name' => 'Sneaky',
                'email' => 'sneaky@test.local',
                'password' => 'rahasia12',
                'password_confirmation' => 'rahasia12',
                'role' => 'superadmin',
                'brand_ids' => [$brand->id],
                'default_brand_id' => $brand->id,
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_user_cannot_delete_self(): void
    {
        $sa = $this->makeUser('superadmin');

        $this->actingAs($sa)
            ->delete(route('users.destroy', $sa->id))
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $sa->id, ]);
    }
}
