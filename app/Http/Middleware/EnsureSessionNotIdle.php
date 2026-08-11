<?php

namespace App\Http\Middleware;

use App\Services\Security\ReauthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * 5-15 minutes idle on an authenticated session locks it in place; 15+
 * destroys it outright and sends the same browser to a passwordless
 * "welcome back" email + emailed-code screen — see ReauthService for the
 * full two-tier rule and ReauthController::identify() for that screen.
 */
class EnsureSessionNotIdle
{
    // Always reachable while locked, otherwise there's no way to clear the
    // lock (or to leave) at all.
    private const EXEMPT_ROUTES = ['reauth.*', 'logout'];

    public function __construct(private ReauthService $reauth) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($request->routeIs(self::EXEMPT_ROUTES)) {
            return $next($request);
        }

        if ($this->reauth->shouldHardLogout($request)) {
            $this->reauth->markPendingCodeRequirement($user);
            $isAdmin = $user->isAdmin();

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Admins never get the passwordless shortcut — straight back to
            // the dedicated admin login (password + the secret URL + MFA).
            if ($isAdmin) {
                return redirect()->route('admin.login')->with('success', __('You were signed out after being away a while. Sign back in to continue.'));
            }

            // Marks this specific browser as the one that was just idle-timed
            // out, so the "welcome back" screen (email + emailed code, no
            // password) is reachable — see ReauthController::identify().
            $request->session()->put('reauth.identify_only', true);

            return redirect()->route('reauth.identify')->with('success', __("You were signed out after being away a while. Enter your email and we'll send a code to confirm it's you."));
        }

        $this->reauth->armIfIdle($request, $user);

        if ($this->reauth->isLocked($request)) {
            $route = $this->reauth->stage($request) === 'email' ? 'reauth.email' : 'reauth.pin';

            return $request->expectsJson()
                ? response()->json(['message' => 'Session locked, re-authentication required.', 'redirect' => route($route)], 423)
                : redirect()->route($route);
        }

        $this->reauth->touch($request);

        return $next($request);
    }
}
