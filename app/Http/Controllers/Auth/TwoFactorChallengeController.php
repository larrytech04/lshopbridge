<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Security\LoginSecurityService;
use App\Services\Security\ReauthService;
use App\Services\Security\TotpService;
use App\Services\Security\WebauthnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Webauthn\Exception\AuthenticatorResponseVerificationException;

/**
 * The second step of login for any account with TOTP and/or a passkey
 * registered. The password step (AuthenticatedSessionController) only
 * validates credentials via Auth::validate() — it never establishes a
 * session — so nobody is "logged in" until a valid TOTP code, recovery
 * code, or passkey assertion is presented here.
 */
class TwoFactorChallengeController extends Controller
{
    public function __construct(private ReauthService $reauth) {}

    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('mfa_user_id')) {
            return redirect()->route('login');
        }

        $challengeUser = User::find($request->session()->get('mfa_user_id'));

        return view('auth.two-factor-challenge', [
            'hasTotp' => $challengeUser?->hasMfaEnabled() ?? false,
            'hasPasskeys' => $challengeUser?->hasPasskeys() ?? false,
        ]);
    }

    public function verify(Request $request, TotpService $totp, LoginSecurityService $loginSecurity): RedirectResponse
    {
        $userId = $request->session()->get('mfa_user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($userId);
        $key = 'mfa-challenge|'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'code' => 'Too many attempts. Please try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        $data = $request->validate(['code' => ['required', 'string', 'max:20']]);
        $code = trim($data['code']);

        $verified = $totp->verify((string) $user->two_factor_secret, $code)
            || $this->consumeRecoveryCode($user, $code);

        if (! $verified) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['code' => 'That code is incorrect or has expired.']);
        }

        RateLimiter::clear($key);

        return $this->completeLogin($request, $user, $loginSecurity);
    }

    public function passkeyOptions(Request $request, WebauthnService $webauthn)
    {
        $userId = $request->session()->get('mfa_user_id');
        if (! $userId) {
            return response()->json(['message' => 'Your login session expired. Please sign in again.'], 419);
        }

        $user = User::findOrFail($userId);
        $result = $webauthn->requestOptionsFor($user);
        $request->session()->put('mfa_passkey_challenge', $result['challenge']);

        return response()->json($result['options']);
    }

    public function passkeyVerify(Request $request, WebauthnService $webauthn, LoginSecurityService $loginSecurity): RedirectResponse
    {
        $userId = $request->session()->get('mfa_user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $challenge = $request->session()->get('mfa_passkey_challenge');
        $data = $request->validate(['response' => ['required', 'array']]);

        if (! $challenge) {
            throw ValidationException::withMessages(['response' => 'Your login session expired. Please sign in again.']);
        }

        $user = User::findOrFail($userId);
        $key = 'mfa-challenge|'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'response' => 'Too many attempts. Please try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        try {
            $webauthn->verifyAssertion($data['response'], $challenge, $user, $request);
        } catch (AuthenticatorResponseVerificationException $e) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['response' => 'That passkey could not be verified.']);
        }

        RateLimiter::clear($key);
        $request->session()->forget('mfa_passkey_challenge');

        return $this->completeLogin($request, $user, $loginSecurity);
    }

    /** The user isn't authenticated yet at this step (Auth::validate() never logs them in), so this just clears the pending challenge state — not a real logout. */
    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget(['mfa_user_id', 'mfa_remember', 'mfa_passkey_challenge']);

        return redirect()->route('login');
    }

    private function completeLogin(Request $request, User $user, LoginSecurityService $loginSecurity): RedirectResponse
    {
        $remember = (bool) $request->session()->pull('mfa_remember', false);
        $request->session()->forget('mfa_user_id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();
        $loginSecurity->recordSuccess($user, $request);

        if ($this->reauth->applyPendingCodeRequirement($request, $user)) {
            return redirect()->route('reauth.email');
        }

        return redirect()->intended($this->home($user));
    }

    private function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];
        $normalized = strtoupper($code);

        foreach ($codes as $i => $hashed) {
            if (Hash::check($normalized, $hashed)) {
                unset($codes[$i]);
                $user->update(['two_factor_recovery_codes' => array_values($codes)]);

                return true;
            }
        }

        return false;
    }

    private function home(User $user): string
    {
        return match (true) {
            $user->isAdmin() => route('admin.dashboard'),
            $user->isAgent() => route('agent.dashboard'),
            default => route('dashboard'),
        };
    }
}
