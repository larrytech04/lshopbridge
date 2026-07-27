<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Real enforcement for the `maintenance_mode` setting, which existed as a
 * toggle in Platform Settings with zero effect before this — flipping it on
 * did nothing. Admins and the login page stay reachable so staff can turn it
 * back off; webhooks and the health check are never blocked.
 */
class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! setting('maintenance_mode', false)) {
            return $next($request);
        }

        if ($request->is('webhooks/*', 'up', 'admin', 'admin/*', 'login', 'logout')) {
            return $next($request);
        }

        if ($request->user()?->isAdmin()) {
            return $next($request);
        }

        return response()->view('errors.maintenance', [], 503);
    }
}
