<?php

namespace App\Http\Controllers;

use App\Services\Security\PinResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Self-service "forgot your transaction PIN" flow: re-confirm the account
 * password, then clear an emailed code, then land back on the Security
 * Center free to set a new PIN without knowing the old one (see
 * SecurityController::updatePin() for where that permission is consumed).
 */
class ForgotPinController extends Controller
{
    public function __construct(private PinResetService $pinReset) {}

    /** Step 1: re-confirm the account password before emailing a code. */
    public function confirm(Request $request): View
    {
        abort_unless($request->user()->hasTransactionPin(), 404);

        return view('security.pin-forgot');
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasTransactionPin(), 404);

        $request->validate(['password' => ['required', 'current_password']]);

        $this->pinReset->sendCode($user);

        // Marks THIS session (not the account) as having just cleared step
        // 1 — stops the code-entry page being reached directly without
        // having re-proven the password first.
        $request->session()->put('pin_reset.password_confirmed', true);

        return redirect()->route('security.pin.forgot.code');
    }

    public function code(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $request->session()->get('pin_reset.password_confirmed')) {
            return redirect()->route('security.pin.forgot');
        }

        return view('security.pin-forgot-code', [
            'maskedEmail' => $this->pinReset->maskedEmail($user),
            'resendWait' => $this->pinReset->resendWaitSeconds($user),
        ]);
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $request->session()->get('pin_reset.password_confirmed')) {
            return redirect()->route('security.pin.forgot');
        }

        $data = $request->validate(['code' => ['required', 'string']]);

        if (! $this->pinReset->verifyCode($user, $data['code'])) {
            return back()->withErrors(['code' => __('That code is incorrect or has expired.')]);
        }

        $request->session()->forget('pin_reset.password_confirmed');

        return redirect()->route('security.index', ['tab' => 'pin'])
            ->with('pin_reset_verified', true);
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $request->session()->get('pin_reset.password_confirmed')) {
            return redirect()->route('security.pin.forgot');
        }

        if (! $this->pinReset->canResend($user)) {
            return back()->withErrors(['code' => __('Please wait before requesting another code.')]);
        }

        $this->pinReset->sendCode($user);

        return back()->with('success', __('A new code has been sent.'));
    }
}
