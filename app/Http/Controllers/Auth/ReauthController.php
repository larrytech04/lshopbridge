<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WebauthnCredential;
use App\Services\Security\LoginSecurityService;
use App\Services\Security\ReauthService;
use App\Services\Security\WebauthnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Webauthn\Exception\AuthenticatorResponseVerificationException;

/**
 * The idle-session lock screens (see ReauthService / EnsureSessionNotIdle).
 * pin()/email() and friends are reachable only while a session is actually
 * locked — landing here any other way just bounces to the dashboard, there's
 * nothing to challenge. identify() and friends are the separate, unauthenticated
 * "welcome back" flow reached only via a 15+ minute hard logout redirect.
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

    /**
     * Passwordless re-entry after a 15+ minute idle hard logout, on the same
     * browser that was just signed out (see EnsureSessionNotIdle) — a
     * password was already proven minutes ago, so this asks only for the
     * email address, then proves the return visitor still controls that
     * inbox via the same 6-character code. Reachable only via that specific
     * redirect, not as a general alternative to /login.
     */
    public function identify(Request $request): View|RedirectResponse
    {
        if (! $request->session()->get('reauth.identify_only')) {
            return redirect()->route('login');
        }

        return view('auth.reauth-identify');
    }

    public function identifySubmit(Request $request): RedirectResponse
    {
        if (! $request->session()->get('reauth.identify_only')) {
            return redirect()->route('login');
        }

        $data = $request->validate(['email' => ['required', 'email']]);

        // Keyed by IP alone, not per-email: this screen exists precisely to
        // check "does this email have an account", so an email-scoped key
        // would do nothing to stop someone sweeping many different emails
        // from one IP to enumerate accounts.
        $key = 'reauth-identify|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 8)) {
            throw ValidationException::withMessages([
                'email' => __('Too many attempts. Please try again in :seconds seconds.', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }
        RateLimiter::hit($key, 600);

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            return redirect()->route('register')->withInput(['email' => $data['email']])
                ->with('info', __("We couldn't find an account with that email. Let's get you set up."));
        }

        // Admin/staff accounts never get the passwordless shortcut, hard
        // idle-logout or not — they always go back through the full admin
        // login (password + the secret URL + MFA).
        if ($user->isAdmin()) {
            return redirect()->route('admin.login')
                ->with('info', __('Admin accounts sign in from the admin portal.'));
        }

        $request->session()->put('reauth.pending_user_id', $user->id);

        if (! $user->reauth_code_expires_at || $user->reauth_code_expires_at->isPast()) {
            $this->reauth->sendCode($user);
        }

        return redirect()->route('reauth.identify.code');
    }

    public function identifyCode(Request $request): View|RedirectResponse
    {
        $user = $this->pendingIdentifyUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        return view('auth.reauth-identify-code', [
            'maskedEmail' => $this->reauth->maskedEmail($user),
            'resendWait' => $this->reauth->resendWaitSeconds($user),
        ]);
    }

    public function identifyVerify(Request $request, LoginSecurityService $loginSecurity): RedirectResponse
    {
        $user = $this->pendingIdentifyUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        $data = $request->validate(['code' => ['required', 'string', 'size:6']]);

        if (! $this->reauth->verifyCode($request, $user, $data['code'])) {
            return back()->withErrors(['code' => __('That code is incorrect or has expired.')]);
        }

        $request->session()->forget(['reauth.pending_user_id', 'reauth.identify_only']);

        Auth::login($user);
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();
        $loginSecurity->recordSuccess($user, $request);

        return redirect()->route('dashboard')->with('welcome_back', true);
    }

    public function identifyResend(Request $request): RedirectResponse
    {
        $user = $this->pendingIdentifyUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $this->reauth->canResend($user)) {
            return back()->withErrors(['code' => __('Please wait before requesting another code.')]);
        }

        $this->reauth->sendCode($user);

        return back()->with('success', __('A new code is on its way.'));
    }

    private function pendingIdentifyUser(Request $request): ?User
    {
        $userId = $request->session()->get('reauth.pending_user_id');

        return $userId ? User::find($userId) : null;
    }
}
