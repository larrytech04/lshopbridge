<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Security\LoginSecurityService;
use App\Services\Security\ReauthService;
use App\Services\Security\TurnstileVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The ONLY place an admin/super_admin account can authenticate. Reachable
 * exclusively at the secret admin URL prefix (config('platform.admin_path'))
 * — the public /login form explicitly rejects these accounts (see
 * AuthenticatedSessionController::store()), so a correct admin password by
 * itself is never enough to reach the panel; the URL is also required.
 */
class AdminSessionController extends Controller
{
    /**
     * Marks this browser/device as "has logged in as an admin before" — a
     * plain boolean, never anything that could authenticate on its own (it's
     * also encrypted at rest by Laravel's cookie middleware like every other
     * cookie this app sets). It only changes which login FORM a guest lands
     * on by default (see bootstrap/app.php's redirectGuestsTo and
     * AuthenticatedSessionController::create()) — actually getting in still
     * requires the real password, Turnstile, and MFA, exactly as before.
     */
    public const DEVICE_COOKIE = 'pb_admin_device';

    public static function markDevice(): void
    {
        Cookie::queue(Cookie::forever(self::DEVICE_COOKIE, '1'));
    }

    public function create(): View
    {
        return view('auth.admin-login', [
            'requireTurnstile' => $this->requireTurnstileFor(),
        ]);
    }

    public function store(Request $request, LoginSecurityService $loginSecurity, ReauthService $reauth, TurnstileVerificationService $turnstile)
    {
        // Unlike the public login, this always runs when Turnstile is
        // enabled — no "conditional" appearance mode. A secret, low-traffic,
        // high-value entry point is exactly where the extra friction is
        // worth it on every attempt, not just after a failed one.
        if ($this->requireTurnstileFor() && ! $turnstile->verify($request, 'admin-login')->success) {
            throw ValidationException::withMessages(['email' => __('Please complete the bot-protection challenge.')]);
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $key = 'admin-login|'.Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => __('Too many login attempts. Please try again in :seconds seconds.', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        $user = User::where('email', $credentials['email'])->first();

        // One generic error for every failure shape: wrong password, unknown
        // email, or a perfectly valid password on a non-admin account. This
        // page never confirms which emails exist or which ones are admin.
        if (! $user || ! $user->isAdmin() || ! Auth::validate($credentials)) {
            RateLimiter::hit($key, 60);
            $loginSecurity->recordFailure($credentials['email'], $request);

            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        RateLimiter::clear($key);

        if ($user->requiresMfaChallenge()) {
            $request->session()->put('mfa_user_id', $user->id);
            $request->session()->put('mfa_remember', $request->boolean('remember'));

            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        self::markDevice();

        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();
        $loginSecurity->recordSuccess($user, $request);

        if ($reauth->applyPendingCodeRequirement($request, $user)) {
            return redirect()->route('reauth.email');
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    private function requireTurnstileFor(): bool
    {
        return (bool) setting('login_protection', true);
    }
}
