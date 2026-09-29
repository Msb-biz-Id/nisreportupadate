<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_page(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_guest_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = $this->makeUser('admin_brand', [$this->makeBrand()]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        $user = $this->makeUser('admin_brand', [$this->makeBrand()]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ])->assertSessionHasErrors();

        $this->assertGuest();
    }

    public function test_login_updates_last_login_timestamp(): void
    {
        $user = $this->makeUser('admin_brand', [$this->makeBrand()]);
        $this->assertNull($user->last_login_at);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_logged_in_user_can_access_dashboard(): void
    {
        $user = $this->makeUser('admin_brand', [$this->makeBrand()]);

        $this->actingAsWithBrand($user)
            ->get('/dashboard')
            ->assertOk();
    }
}
