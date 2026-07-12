<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureKycLevel
{
    /**
     * Usage: ->middleware('kyc:2') — requires kyc_level >= 2.
     */
    public function handle(Request $request, Closure $next, int $level = 1): Response
    {
        $user = $request->user();

        if (! $user || $user->kyc_level < $level) {
            return redirect()->route('verification.index')
                ->with('warning', "This action requires identity verification (level {$level}).");
        }

        return $next($request);
    }
}
