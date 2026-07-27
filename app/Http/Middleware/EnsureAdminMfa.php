<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Only active when an admin has turned on the "require admin MFA" toggle in
 * Settings (default off, to avoid locking out every admin the moment this
 * feature ships). While on, any admin/staff account without a confirmed
 * authenticator app is redirected to enroll before reaching any /admin page.
 * The enrollment page itself lives outside the /admin prefix, so it is never
 * blocked by this same check.
 */
class EnsureAdminMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isAdmin() && setting('require_admin_mfa', false) && ! $user->hasMfaEnabled()) {
            return redirect()->route('security.two-factor.show')
                ->with('error', 'Your role requires two-factor authentication. Set it up to continue.');
        }

        return $next($request);
    }
}
