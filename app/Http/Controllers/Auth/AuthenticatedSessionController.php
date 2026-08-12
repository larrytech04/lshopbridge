<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Security\LoginSecurityService;
use App\Services\Security\ReauthService;
use App\Services\Security\TurnstileVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        // A device known to belong to an admin (bookmarked/typed the public
        // /login URL directly rather than being redirected here) still lands
        // on the admin login instead — same "which form to default to" hint
        // as the guest-redirect logic in bootstrap/app.php.
        if ($request->cookie(AdminSessionController::DEVICE_COOKIE)) {
            return redirect()->route('admin.login');
        }

        return view('auth.login', [
            'requireTurnstile' => $this->requireTurnstileFor($request),
        ]);
    }

    public function store(Request $request, LoginSecurityService $loginSecurity, TurnstileVerificationService $turnstile, ReauthService $reauth)
    {
        if ($this->requireTurnstileFor($request) && ! $turnstile->verify($request, 'login')->success) {
            throw ValidationException::withMessages(['email' => 'Please complete the bot-protection challenge.']);
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Login throttling: 5 attempts / minute per email+ip.
        $key = Str::lower($request->input('email')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Please try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        // Auth::validate() checks the credentials without establishing a session —
        // nobody is actually logged in until either the no-MFA path below calls
        // Auth::login(), or the MFA challenge does after a valid code.
        if (! Auth::validate($credentials)) {
            RateLimiter::hit($key, 60);
            $loginSecurity->recordFailure($credentials['email'], $request);
            // Conditional Turnstile: once this email+IP has shown suspicious
            // behaviour (a failed attempt), require the challenge on the next try.
            $request->session()->put('force_login_turnstile', true);

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $request->session()->forget('force_login_turnstile');

        $user = \App\Models\User::where('email', $credentials['email'])->firstOrFail();

        // Admin/staff accounts can only authenticate at the dedicated admin
        // login (see AdminSessionController) — never here, even with a
        // completely correct password. Same generic message and the same
        // rate-limit/failure bookkeeping as a wrong password, so this page
        // never confirms an email belongs to an admin account.
        if ($user->isAdmin()) {
            RateLimiter::hit($key, 60);
            $loginSecurity->recordFailure($credentials['email'], $request);

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
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

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();
        $loginSecurity->recordSuccess($user, $request);

        if ($reauth->applyPendingCodeRequirement($request, $user)) {
            return redirect()->route('reauth.email');
        }

        return redirect()->intended($this->home($user));
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function home($user): string
    {
        return match (true) {
            $user->isAdmin() => route('admin.dashboard'),
            $user->isAgent() => route('agent.dashboard'),
            default => route('dashboard'),
        };
    }

    /**
     * When appearance mode is "conditional", most visitors never see a
     * challenge on login — it's only required once this browser session has
     * already produced a failed attempt (real suspicious behaviour), per the
     * "Login after suspicious behaviour" requirement. Managed/invisible modes
     * keep the original always-verify behaviour.
     */
    private function requireTurnstileFor(Request $request): bool
    {
        if (! setting('login_protection', true)) {
            return false;
        }

        if (setting('turnstile_appearance_mode', 'managed') !== 'conditional') {
            return true;
        }

        return (bool) $request->session()->get('force_login_turnstile', false);
    }
}
