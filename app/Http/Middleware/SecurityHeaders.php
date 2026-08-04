<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security response headers, sent on every response.
 *
 * The CSP allows 'unsafe-inline' for scripts and styles: the app relies on
 * Alpine.js x-data/@click attributes and inline <style>/style= usage
 * throughout every Blade view, and there is no build-time nonce/hash
 * pipeline wired up to eliminate that. It also allows 'unsafe-eval': Alpine's
 * default (non-CSP) build evaluates x-data/@click expression strings via
 * `new Function(...)` internally, so blocking eval breaks every Alpine
 * component on the site, not just inline scripts — confirmed by hand (this
 * shipped broken once already: the header without 'unsafe-eval' silently
 * broke the shortcuts-help modal, theme switcher, and other Alpine UI
 * sitewide, caught by re-testing in a real browser, not just curl). A
 * stricter CSP is real future work (see the security hardening checklist)
 * but needs Alpine's CSP-safe build plus reworking how every view is
 * written, not just adding a header, so it's out of scope here rather than
 * shipped half-broken.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Google sign-in (Socialite) is a full-page redirect, not an embedded
        // widget, so it needs no script-src/frame-src allowance here. Turnstile
        // and the analytics tag are the only third-party scripts actually
        // loaded by any view (see resources/views/components/turnstile.blade.php
        // and partials/theme-head.blade.php). ipapi.co is a real pre-existing
        // client-side call (the country-picker's IP-based default in app.js's
        // onboarding/welcomeIntro components) — missed in the original audit,
        // which silently broke it once already (fails closed with a caught
        // rejection, so it degraded gracefully rather than erroring, but the
        // feature stopped working).
        // Real-time notifications connect to the Reverb WebSocket server, whose
        // host/port/scheme are whatever REVERB_* is set to (localhost:8080 in
        // dev, the production domain/port once deployed) — read from the same
        // env vars Echo's own connection config is built from, so this never
        // drifts out of sync with the actual server it's allowed to reach.
        $reverbScheme = env('REVERB_SCHEME', 'https') === 'https' ? 'wss' : 'ws';
        $reverbConnect = "{$reverbScheme}://".env('REVERB_HOST', 'localhost').':'.env('REVERB_PORT', 8080);

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://challenges.cloudflare.com https://www.googletagmanager.com",
            "style-src 'self' 'unsafe-inline'",
            "font-src 'self' data:",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' https://www.google-analytics.com https://ipapi.co {$reverbConnect}",
            "frame-src 'self' https://challenges.cloudflare.com",
            "object-src 'none'",
            "base-uri 'self'",
        ]));

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
