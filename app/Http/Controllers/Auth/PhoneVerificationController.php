<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Phone OTP verification. The OTP structure is real (hashed codes, expiry,
 * attempt limits); sending the SMS is the single integration point.
 *
 * TODO[live]: dispatch the code via an SMS provider (Twilio, Termii, Africa's
 * Talking...) instead of flashing it to the session.
 */
class PhoneVerificationController extends Controller
{
    public function send(Request $request)
    {
        $user = $request->user();

        if (! $user->phone) {
            return back()->withErrors(['phone' => 'Add a phone number to your profile first.']);
        }

        $code = (string) random_int(100000, 999999);

        OtpCode::create([
            'user_id' => $user->id,
            'channel' => 'sms',
            'destination' => $user->phone,
            'purpose' => 'phone_verification',
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        // TODO[live]: send $code by SMS. For now we surface it (sandbox only).
        $sandbox = config('platform.provider_mode') !== 'live';

        return back()->with('success', 'A verification code was sent to '.$user->phone.'.')
            ->with($sandbox ? 'otp_debug' : 'noop', $sandbox ? "Sandbox code: {$code}" : null);
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']]);
        $user = $request->user();

        $otp = OtpCode::where('user_id', $user->id)
            ->where('purpose', 'phone_verification')
            ->whereNull('consumed_at')
            ->latest()
            ->first();

        if (! $otp || $otp->isExpired()) {
            return back()->withErrors(['code' => 'The code has expired. Please request a new one.']);
        }

        if ($otp->attempts >= 5) {
            return back()->withErrors(['code' => 'Too many attempts. Request a new code.']);
        }

        $otp->increment('attempts');

        if (! Hash::check($request->code, $otp->code_hash)) {
            return back()->withErrors(['code' => 'Incorrect code.']);
        }

        $otp->update(['consumed_at' => now()]);

        $user->forceFill([
            'phone_verified_at' => now(),
            'kyc_level' => max($user->kyc_level, 1),
            'kyc_status' => $user->kyc_status->value === 'unverified' ? 'pending' : $user->kyc_status->value,
        ])->save();

        return back()->with('success', 'Your phone number is verified. Level 1 unlocked.');
    }
}
