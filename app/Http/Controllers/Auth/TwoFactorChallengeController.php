<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorChallengeController extends Controller
{
    public function show(Request $request)
    {
        if (! $request->session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    public function store(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $userId = $request->session()->get('2fa_user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (! $user || ! $user->two_factor_enabled || ! $user->two_factor_secret) {
            $request->session()->forget('2fa_user_id');
            return redirect()->route('login');
        }

        $secret = Crypt::decryptString($user->two_factor_secret);
        $valid  = (new Google2FA)->verifyKey($secret, $request->code);

        if (! $valid) {
            return back()->withErrors(['code' => 'Kode OTP tidak valid atau sudah kadaluarsa.']);
        }

        $request->session()->forget('2fa_user_id');
        Auth::login($user);
        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->saveQuietly();

        \App\Services\ActivityLogger::log('login', 'auth', $user, 'Login berhasil (2FA)');

        return redirect()->intended(route('dashboard'));
    }
}
