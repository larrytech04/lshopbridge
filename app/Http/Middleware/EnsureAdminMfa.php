<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mandatory, not a toggle: every admin/staff account must have a confirmed
 * authenticator app or passkey before reaching any admin page. Anyone not
 * yet enrolled is redirected to set it up rather than blocked outright — the
 * enrollment page itself lives outside the admin URL prefix, so it is never
 * blocked by this same check.
 */
class EnsureAdminMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isAdmin() && ! $user->requiresMfaChallenge()) {
            return redirect()->route('security.two-factor.show')
                ->with('error', 'Admin accounts require two-factor authentication. Set it up to continue.');
        }

        return $next($request);
    }
}
