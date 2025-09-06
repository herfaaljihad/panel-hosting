<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FALaravel\Google2FA;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\User;

class TwoFactorController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $google2fa = app('pragmarx.google2fa');
        
        // Generate a secret key if not exists
        if (!$user->google2fa_secret) {
            $secretKey = $google2fa->generateSecretKey();
            $user->update(['google2fa_secret' => $secretKey]);
        }

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->google2fa_secret
        );

        $qrCode = QrCode::size(200)->generate($qrCodeUrl);

        return view('two-factor.show', compact('qrCode', 'user'));
    }

    public function enable(Request $request)
    {
        $request->validate([
            'one_time_password' => 'required|digits:6',
        ]);

        $user = Auth::user();
        $google2fa = app('pragmarx.google2fa');

        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->one_time_password);

        if ($valid) {
            $user->update([
                'google2fa_enabled' => true
            ]);

            return redirect()->back()->with('success', '2FA has been enabled successfully!');
        }

        return redirect()->back()->with('error', 'Invalid verification code. Please try again.');
    }

    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = Auth::user();
        $user->update([
            'google2fa_enabled' => false,
            'google2fa_secret' => null
        ]);

        return redirect()->back()->with('success', '2FA has been disabled successfully!');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'one_time_password' => 'required|digits:6',
        ]);

        $user = Auth::user();
        $google2fa = app('pragmarx.google2fa');

        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->one_time_password);

        if ($valid) {
            session(['2fa_verified' => true]);
            return redirect()->intended('/dashboard');
        }

        return redirect()->back()->with('error', 'Invalid verification code. Please try again.');
    }

    public function showVerifyForm()
    {
        return view('two-factor.verify');
    }
}
