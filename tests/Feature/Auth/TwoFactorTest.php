<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function makeG2fa(): Google2FA
    {
        return new Google2FA;
    }

    public function test_user_without_2fa_logs_in_directly()
    {
        $brand = $this->makeBrand();
        $user  = $this->makeUser('admin_brand', [$brand]);

        $this->post(route('login'), [
            'email'    => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_with_2fa_is_redirected_to_challenge()
    {
        $brand  = $this->makeBrand();
        $user   = $this->makeUser('admin_brand', [$brand]);
        $secret = $this->makeG2fa()->generateSecretKey();

        $user->forceFill([
            'two_factor_enabled' => true,
            'two_factor_secret'  => Crypt::encryptString($secret),
        ])->save();

        $this->post(route('login'), [
            'email'    => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge'));

        $this->assertGuest();
    }

    public function test_valid_otp_completes_login()
    {
        $brand  = $this->makeBrand();
        $user   = $this->makeUser('admin_brand', [$brand]);
        $g2fa   = $this->makeG2fa();
        $secret = $g2fa->generateSecretKey();

        $user->forceFill([
            'two_factor_enabled' => true,
            'two_factor_secret'  => Crypt::encryptString($secret),
        ])->save();

        // Simulasi sesi pending setelah login
        $this->withSession(['2fa_user_id' => $user->id])
            ->post(route('two-factor.challenge.store'), [
                'code' => $g2fa->getCurrentOtp($secret),
            ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_otp_returns_error()
    {
        $brand  = $this->makeBrand();
        $user   = $this->makeUser('admin_brand', [$brand]);
        $secret = $this->makeG2fa()->generateSecretKey();

        $user->forceFill([
            'two_factor_enabled' => true,
            'two_factor_secret'  => Crypt::encryptString($secret),
        ])->save();

        $this->withSession(['2fa_user_id' => $user->id])
            ->post(route('two-factor.challenge.store'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_challenge_without_session_redirects_to_login()
    {
        $this->get(route('two-factor.challenge'))
            ->assertRedirect(route('login'));
    }

    public function test_setup_generates_secret_and_qr()
    {
        $brand = $this->makeBrand();
        $user  = $this->makeUser('admin_brand', [$brand]);

        $this->actingAsWithBrand($user, $brand)
            ->getJson(route('two-factor.setup'))
            ->assertOk()
            ->assertJsonStructure(['secret', 'qr_svg']);
    }

    public function test_enable_with_valid_otp_activates_2fa()
    {
        $brand  = $this->makeBrand();
        $user   = $this->makeUser('admin_brand', [$brand]);
        $g2fa   = $this->makeG2fa();
        $secret = $g2fa->generateSecretKey();

        $this->actingAsWithBrand($user, $brand)
            ->withSession(['2fa_pending_secret' => $secret])
            ->post(route('two-factor.enable'), [
                'code' => $g2fa->getCurrentOtp($secret),
            ])->assertRedirect();

        $this->assertTrue($user->fresh()->two_factor_enabled);
    }

    public function test_enable_with_invalid_otp_returns_error()
    {
        $brand  = $this->makeBrand();
        $user   = $this->makeUser('admin_brand', [$brand]);
        $secret = $this->makeG2fa()->generateSecretKey();

        $this->actingAsWithBrand($user, $brand)
            ->withSession(['2fa_pending_secret' => $secret])
            ->post(route('two-factor.enable'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertFalse((bool) $user->fresh()->two_factor_enabled);
    }

    public function test_disable_with_valid_otp_deactivates_2fa()
    {
        $brand  = $this->makeBrand();
        $user   = $this->makeUser('admin_brand', [$brand]);
        $g2fa   = $this->makeG2fa();
        $secret = $g2fa->generateSecretKey();

        $user->forceFill([
            'two_factor_enabled' => true,
            'two_factor_secret'  => Crypt::encryptString($secret),
        ])->save();

        $this->actingAsWithBrand($user, $brand)
            ->post(route('two-factor.disable'), [
                'code' => $g2fa->getCurrentOtp($secret),
            ])->assertRedirect();

        $user->refresh();
        $this->assertFalse((bool) $user->two_factor_enabled);
        $this->assertNull($user->two_factor_secret);
    }
}
