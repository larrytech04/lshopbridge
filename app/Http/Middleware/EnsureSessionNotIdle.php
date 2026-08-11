<?php

namespace App\Http\Middleware;

use App\Services\Security\ReauthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * 15-30 minutes idle on an authenticated session locks it in place; 30+
 * destroys it outright — see ReauthService for the full two-tier rule.
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

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('success', __("You were signed out after being away a while. Sign back in, we'll email you a code to confirm it's you."));
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
