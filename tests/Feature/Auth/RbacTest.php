<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_bypasses_all_permissions(): void
    {
        $superadmin = $this->makeUser('superadmin', [$this->makeBrand()]);

        $this->assertTrue($superadmin->can('brand.delete'));
        $this->assertTrue($superadmin->can('user.delete'));
        $this->assertTrue($superadmin->can('order.delete'));
        $this->assertTrue($superadmin->can('settings.system'));
    }

    public function test_admin_brand_role_has_correct_permissions(): void
    {
        $user = $this->makeUser('admin_brand', [$this->makeBrand()]);

        $this->assertTrue($user->can('order.view'));
        $this->assertTrue($user->can('order.create'));
        $this->assertTrue($user->can('master.brand'));
        $this->assertFalse($user->can('brand.create'));
        $this->assertFalse($user->can('user.delete'));
        $this->assertFalse($user->can('settings.system'));
    }

    public function test_admin_produksi_role_has_correct_permissions(): void
    {
        $user = $this->makeUser('admin_produksi', [$this->makeBrand()]);

        $this->assertTrue($user->can('production.update-progress'));
        $this->assertTrue($user->can('production.add-reject'));
        $this->assertFalse($user->can('order.create'));
        $this->assertFalse($user->can('order.delete'));
    }

    public function test_admin_keuangan_role_has_correct_permissions(): void
    {
        $user = $this->makeUser('admin_keuangan', [$this->makeBrand()]);

        $this->assertTrue($user->can('finance.view'));
        $this->assertTrue($user->can('finance.manage-invoice'));
        $this->assertTrue($user->can('finance.manage-refund'));
        $this->assertFalse($user->can('order.create'));
    }

    public function test_unauthorized_user_gets_403_on_brand_create(): void
    {
        $user = $this->makeUser('admin_brand', [$this->makeBrand()]);

        $this->actingAs($user)
            ->post(route('brands.store'), [
                'nama_brand' => 'Hack',
                'kode' => 'HCK',
            ])
            ->assertForbidden();
    }
}
