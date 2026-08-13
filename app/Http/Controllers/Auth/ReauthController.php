<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Security\ReauthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The idle-session lock screen (see ReauthService / EnsureSessionNotIdle).
 * Reachable only while a session is actually locked — landing here any
 * other way just bounces to the dashboard, there's nothing to challenge.
 */
class ReauthController extends Controller
{
    public function __construct(private ReauthService $reauth) {}

    public function email(Request $request): View|RedirectResponse
    {
        if (! $this->reauth->isLocked($request)) {
            return redirect()->route('dashboard');
        }

        $user = $request->user();

        // First arrival at this lock in this cycle — send the code now.
        if (! $user->reauth_code_expires_at || $user->reauth_code_expires_at->isPast()) {
            $this->reauth->sendCode($user);
        }

        return view('auth.reauth-email', [
            'maskedEmail' => $this->reauth->maskedEmail($user),
            'resendWait' => $this->reauth->resendWaitSeconds($user),
        ]);
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        if (! $this->reauth->isLocked($request)) {
            return redirect()->route('dashboard');
        }

        $data = $request->validate(['code' => ['required', 'string', 'size:6']]);

        if (! $this->reauth->verifyCode($request, $request->user(), $data['code'])) {
            return back()->withErrors(['code' => __('That code is incorrect or has expired.')]);
        }

        // Not a generic flash banner — the dashboard's own greeting types
        // "Welcome back" as its first line instead (see dashboard/index).
        return redirect($this->reauth->intendedUrl($request))->with('welcome_back', true);
    }

    public function resendCode(Request $request): RedirectResponse
    {
        if (! $this->reauth->isLocked($request)) {
            return redirect()->route('dashboard');
        }

        $user = $request->user();

        if (! $this->reauth->canResend($user)) {
            return back()->withErrors(['code' => __('Please wait before requesting another code.')]);
        }

        $this->reauth->sendCode($user);

        return back()->with('success', __('A new code is on its way.'));
    }
}
