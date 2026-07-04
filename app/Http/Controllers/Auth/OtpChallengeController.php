<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailOtpMail;

class OtpChallengeController extends Controller
{
    /**
     * Display the OTP verification view.
     */
    public function show(Request $request)
    {
        if (!$request->session()->has('email_otp_user_id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/OtpChallenge');
    }

    /**
     * Handle the OTP verification request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $userId = $request->session()->get('email_otp_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (!$user) {
            $request->session()->forget(['email_otp_user_id', 'email_otp', 'email_otp_expires_at', 'email_otp_remember']);
            return redirect()->route('login');
        }

        $otp = $request->session()->get('email_otp');
        $expiresAt = $request->session()->get('email_otp_expires_at');

        if (!$otp || !$expiresAt || now()->isAfter($expiresAt)) {
            return back()->withErrors(['code' => 'Kode OTP telah kedaluwarsa. Silakan kirim ulang.']);
        }

        if ($request->code !== (string)$otp) {
            return back()->withErrors(['code' => 'Kode OTP tidak valid.']);
        }

        // Remember login?
        $remember = $request->session()->get('email_otp_remember', false);

        // Login user
        Auth::login($user, $remember);

        // Clean up session
        $request->session()->forget(['email_otp_user_id', 'email_otp', 'email_otp_expires_at', 'email_otp_remember']);
        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->saveQuietly();

        \App\Services\ActivityLogger::log('login', 'auth', $user, 'Login berhasil (OTP Email)');

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Resend the OTP verification code.
     */
    public function resend(Request $request)
    {
        $userId = $request->session()->get('email_otp_user_id');
        if (!$userId) {
            return back()->withErrors(['code' => 'Sesi login telah kedaluwarsa. Silakan login kembali.']);
        }

        $user = User::find($userId);
        if (!$user) {
            return back()->withErrors(['code' => 'Pengguna tidak ditemukan.']);
        }

        // Generate new OTP
        $otp = rand(100000, 999999);

        // Store new OTP in session
        $request->session()->put('email_otp', $otp);
        $request->session()->put('email_otp_expires_at', now()->addMinutes(5));

        // Resend email
        Mail::to($user->email)->send(new EmailOtpMail($otp, $user));

        return back()->with('status', 'otp-resent');
    }
}
