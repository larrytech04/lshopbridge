<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\WebauthnCredential;
use App\Services\Security\ReauthService;
use App\Services\Security\WebauthnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Webauthn\Exception\AuthenticatorResponseVerificationException;

/**
 * The idle-session lock screens (see ReauthService / EnsureSessionNotIdle).
 * Reachable only while a session is actually locked — landing here any other
 * way just bounces to the dashboard, there's nothing to challenge.
 */
class ReauthController extends Controller
{
    public function __construct(private ReauthService $reauth) {}

    public function pin(Request $request): View|RedirectResponse
    {
        if (! $this->reauth->isLocked($request)) {
            return redirect()->route('dashboard');
        }

        if ($this->reauth->stage($request) !== 'pin') {
            return redirect()->route('reauth.email');
        }

        return view('auth.reauth-pin', [
            'hasPasskey' => WebauthnCredential::where('user_id', $request->user()->id)->exists(),
        ]);
    }

    /** A registered passkey (Face ID / fingerprint / device unlock) skips the PIN entirely. */
    public function passkeyOptions(Request $request, WebauthnService $webauthn): JsonResponse
    {
        if (! $this->reauth->isLocked($request) || $this->reauth->stage($request) !== 'pin') {
            return response()->json(['message' => 'Nothing to verify.'], 419);
        }

        $result = $webauthn->requestOptionsFor($request->user());
        $request->session()->put('reauth.passkey_challenge', $result['challenge']);

        return response()->json($result['options']);
    }

    public function passkeyVerify(Request $request, WebauthnService $webauthn): RedirectResponse
    {
        if (! $this->reauth->isLocked($request) || $this->reauth->stage($request) !== 'pin') {
            return redirect()->route('dashboard');
        }

        $user = $request->user();
        $challenge = $request->session()->get('reauth.passkey_challenge');
        $data = $request->validate(['response' => ['required', 'array']]);

        if (! $challenge) {
            throw ValidationException::withMessages(['response' => __('Your session expired. Please try again.')]);
        }

        $key = "reauth-passkey:{$user->id}";
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['response' => __('Too many attempts. Please try again shortly.')]);
        }

        try {
            $webauthn->verifyAssertion($data['response'], $challenge, $user, $request);
        } catch (AuthenticatorResponseVerificationException $e) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['response' => __('That passkey could not be verified.')]);
        }

        RateLimiter::clear($key);
        $request->session()->forget('reauth.passkey_challenge');
        $request->session()->put('reauth.stage', 'email');

        $this->reauth->sendCode($user);

        return redirect()->route('reauth.email');
    }

    public function verifyPin(Request $request): RedirectResponse
    {
        if (! $this->reauth->isLocked($request) || $this->reauth->stage($request) !== 'pin') {
            return redirect()->route('dashboard');
        }

        $data = $request->validate(['pin' => ['required', 'digits:4']]);

        if (! $this->reauth->verifyPin($request, $request->user(), $data['pin'])) {
            return back()->withErrors(['pin' => __('Incorrect PIN.')]);
        }

        $this->reauth->sendCode($request->user());

        return redirect()->route('reauth.email');
    }

    public function email(Request $request): View|RedirectResponse
    {
        if (! $this->reauth->isLocked($request)) {
            return redirect()->route('dashboard');
        }

        $user = $request->user();

        // First arrival at this stage in this lock cycle — send the code now.
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

        return redirect($this->reauth->intendedUrl($request))->with('success', __('Welcome back.'));
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
