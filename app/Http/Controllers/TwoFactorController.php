<?php

namespace App\Http\Controllers;

use App\Notifications\SecurityAlert;
use App\Services\Security\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function show(Request $request, TotpService $totp): View
    {
        $user = $request->user();

        if ($user->hasMfaEnabled()) {
            return view('security.two-factor.manage', ['user' => $user]);
        }

        // A fresh secret per visit to this page, held only in the session until
        // confirmed with a live code — nothing is written to the user record yet.
        $secret = $request->session()->get('pending_2fa_secret') ?: $totp->generateSecret();
        $request->session()->put('pending_2fa_secret', $secret);

        $uri = $totp->provisioningUri($user->email, $secret, config('platform.name'));

        return view('security.two-factor.enroll', [
            'secret' => $secret,
            'uri' => $uri,
            'qrCode' => $totp->qrCodeDataUri($uri),
        ]);
    }

    public function confirm(Request $request, TotpService $totp)
    {
        $user = $request->user();
        $secret = $request->session()->get('pending_2fa_secret');

        if (! $secret) {
            return redirect()->route('security.two-factor.show')->withErrors(['code' => 'Your enrollment session expired, please start again.']);
        }

        $data = $request->validate(['code' => ['required', 'string', 'max:10']]);

        if (! $totp->verify($secret, $data['code'])) {
            return back()->withErrors(['code' => 'That code is incorrect. Check your authenticator app and try again.']);
        }

        $plainCodes = $totp->generateRecoveryCodes();
        $hashedCodes = array_map(fn ($c) => Hash::make($c), $plainCodes);

        $user->update([
            'two_factor_secret' => $secret,
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $hashedCodes,
            'two_factor_disabled_at' => null,
        ]);
        $request->session()->forget('pending_2fa_secret');

        $user->notify(new SecurityAlert(
            title: 'Two-factor authentication enabled',
            message: "Two-factor authentication was just turned on for your account.\n\nIf you didn't do this, secure your account immediately.",
            actionLabel: 'Review account security',
            actionUrl: route('security.index'),
        ));

        // Recovery codes are shown exactly once, passed via a one-time session flash — never stored in plain text.
        return redirect()->route('security.two-factor.show')->with('recovery_codes', $plainCodes);
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $user = $request->user();
        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_disabled_at' => now(),
        ]);

        $user->notify(new SecurityAlert(
            title: 'Two-factor authentication turned off',
            message: "Two-factor authentication was just turned off for your account.\n\nIf you didn't do this, secure your account immediately.",
            actionLabel: 'Review account security',
            actionUrl: route('security.index'),
        ));

        return redirect()->route('security.two-factor.show')->with('success', 'Two-factor authentication has been turned off.');
    }

    public function regenerateRecoveryCodes(Request $request, TotpService $totp)
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $user = $request->user();
        abort_unless($user->hasMfaEnabled(), 400);

        $plainCodes = $totp->generateRecoveryCodes();
        $user->update(['two_factor_recovery_codes' => array_map(fn ($c) => Hash::make($c), $plainCodes)]);

        $user->notify(new SecurityAlert(
            title: 'Recovery codes regenerated',
            message: "Your two-factor recovery codes were just regenerated. Your old codes no longer work.\n\nIf you didn't do this, secure your account immediately.",
            actionLabel: 'Review account security',
            actionUrl: route('security.index'),
        ));

        return redirect()->route('security.two-factor.show')->with('recovery_codes', $plainCodes);
    }
}
