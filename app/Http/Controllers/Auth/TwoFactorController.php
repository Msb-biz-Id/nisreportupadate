<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    private Google2FA $g2fa;

    public function __construct()
    {
        $this->g2fa = new Google2FA;
    }

    /** GET /two-factor/setup — generate secret + QR, return ke profile */
    public function setup(Request $request)
    {
        $user   = $request->user();
        $secret = $this->g2fa->generateSecretKey();

        // Simpan ke session sementara (belum di-enable sampai verify)
        $request->session()->put('2fa_pending_secret', $secret);

        $otpUrl = $this->g2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret,
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd,
        );
        $qrSvg = (new Writer($renderer))->writeString($otpUrl);

        return response()->json([
            'secret' => $secret,
            'qr_svg' => base64_encode($qrSvg),
        ]);
    }

    /** POST /two-factor/enable — verifikasi OTP lalu aktifkan */
    public function enable(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $secret = $request->session()->get('2fa_pending_secret');
        if (! $secret) {
            return back()->withErrors(['code' => 'Session setup 2FA kadaluarsa. Coba lagi.']);
        }

        $valid = $this->g2fa->verifyKey($secret, $request->code);
        if (! $valid) {
            return back()->withErrors(['code' => 'Kode OTP tidak valid.']);
        }

        $request->user()->forceFill([
            'two_factor_secret'  => Crypt::encryptString($secret),
            'two_factor_enabled' => true,
        ])->save();

        $request->session()->forget('2fa_pending_secret');

        return back()->with('status', '2fa-enabled');
    }

    /** POST /two-factor/disable — nonaktifkan 2FA (perlu verifikasi OTP) */
    public function disable(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $user = $request->user();
        if (! $user->two_factor_enabled || ! $user->two_factor_secret) {
            return back()->withErrors(['code' => '2FA tidak aktif.']);
        }

        $secret = Crypt::decryptString($user->two_factor_secret);
        $valid  = $this->g2fa->verifyKey($secret, $request->code);

        if (! $valid) {
            return back()->withErrors(['code' => 'Kode OTP tidak valid.']);
        }

        $user->forceFill([
            'two_factor_secret'  => null,
            'two_factor_enabled' => false,
        ])->save();

        return back()->with('status', '2fa-disabled');
    }
}
