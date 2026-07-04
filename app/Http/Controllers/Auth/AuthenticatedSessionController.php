<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();

        // Check if OTP by email is enabled
        if (config('auth.otp_email_enabled')) {
            // Log out the user from the current session state
            Auth::guard('web')->logout();

            // Generate OTP
            $otp = rand(100000, 999999);

            // Store verification details in session
            $request->session()->put('email_otp', $otp);
            $request->session()->put('email_otp_expires_at', now()->addMinutes(5));
            $request->session()->put('email_otp_user_id', $user->id);
            $request->session()->put('email_otp_remember', $request->boolean('remember'));

            // Send Email OTP
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\EmailOtpMail($otp, $user));

            return redirect()->route('otp.challenge');
        }

        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->saveQuietly();

        \App\Services\ActivityLogger::log('login', 'auth', $user, 'Login berhasil');

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
