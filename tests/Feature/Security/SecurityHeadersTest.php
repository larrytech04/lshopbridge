<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_baseline_security_headers_are_present_on_every_response(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_csp_allows_unsafe_eval_because_alpine_js_requires_it(): void
    {
        // A CSP without 'unsafe-eval' silently breaks every Alpine.js
        // component sitewide (x-data/@click expressions are evaluated via
        // `new Function(...)` in Alpine's default build) — this shipped
        // broken once already and was only caught by testing in a real
        // browser. Guard against it regressing silently again.
        $csp = $this->get('/login')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("'unsafe-eval'", $csp);

        // Fonts are self-hosted (bundled from @fontsource via Vite). The old
        // fonts.bunny.net <link> was render-blocking and hung every page load
        // for 20-30s when that host was unreachable; keep it out of the CSP so
        // it cannot quietly come back.
        $this->assertStringNotContainsString('fonts.bunny.net', $csp);
    }
}
