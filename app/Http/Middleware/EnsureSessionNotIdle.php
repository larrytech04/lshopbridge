<?php

namespace App\Http\Middleware;

use App\Services\Security\ReauthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idle on an authenticated session locks it in place, right where it is,
 * until an emailed code is entered — 24 hours for a customer/agent, only 30
 * minutes for an admin/super_admin (see ReauthService::armIfIdle()). The
 * session is never destroyed and nothing else is asked for. A real logout
 * always goes through the normal login on the next visit; this middleware
 * has no opinion on that path at all.
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

        $this->reauth->armIfIdle($request, $user);

        if ($this->reauth->isLocked($request)) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Session locked, re-authentication required.', 'redirect' => route('reauth.email')], 423)
                : redirect()->route('reauth.email');
        }

        $this->reauth->touch($request);

        return $next($request);
    }
}
