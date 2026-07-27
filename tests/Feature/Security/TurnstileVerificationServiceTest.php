<?php

namespace Tests\Feature\Security;

use App\Services\Security\TurnstileVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TurnstileVerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function configureTurnstile(): void
    {
        config([
            'services.turnstile.site_key' => '1x00000000000000000000AA',
            'services.turnstile.secret_key' => '1x0000000000000000000000000000AA',
        ]);
        \App\Models\Setting::create(['key' => 'turnstile_enabled', 'value' => '1', 'type' => 'bool', 'group' => 'general']);
    }

    public function test_verification_is_a_noop_success_when_disabled(): void
    {
        Http::fake();
        $request = Request::create('/contact', 'POST', ['cf-turnstile-response' => 'anything']);

        $result = app(TurnstileVerificationService::class)->verify($request);

        $this->assertTrue($result->success);
        Http::assertNothingSent();
    }

    public function test_missing_token_fails_without_calling_cloudflare(): void
    {
        $this->configureTurnstile();
        Http::fake();
        $request = Request::create('/contact', 'POST', []);

        $result = app(TurnstileVerificationService::class)->verify($request);

        $this->assertFalse($result->success);
        $this->assertSame('missing-input-response', $result->reasonCode());
        Http::assertNothingSent();
    }

    public function test_valid_token_succeeds(): void
    {
        $this->configureTurnstile();
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true, 'hostname' => 'localhost', 'action' => 'contact', 'challenge_ts' => now()->toIso8601String()]),
        ]);
        $request = Request::create('http://localhost/contact', 'POST', ['cf-turnstile-response' => 'good-token']);

        $result = app(TurnstileVerificationService::class)->verify($request, 'contact');

        $this->assertTrue($result->success);
    }

    public function test_invalid_token_fails(): void
    {
        $this->configureTurnstile();
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => false, 'error-codes' => ['invalid-input-response']]),
        ]);
        $request = Request::create('/contact', 'POST', ['cf-turnstile-response' => 'bad-token']);

        $result = app(TurnstileVerificationService::class)->verify($request);

        $this->assertFalse($result->success);
        $this->assertSame('invalid-input-response', $result->reasonCode());
    }

    public function test_expired_or_reused_token_is_surfaced_from_cloudflares_own_error_code(): void
    {
        $this->configureTurnstile();
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => false, 'error-codes' => ['timeout-or-duplicate']]),
        ]);
        $request = Request::create('/contact', 'POST', ['cf-turnstile-response' => 'reused-token']);

        $result = app(TurnstileVerificationService::class)->verify($request);

        $this->assertFalse($result->success);
        $this->assertSame('timeout-or-duplicate', $result->reasonCode());
    }

    public function test_wrong_hostname_is_rejected_even_on_a_successful_cloudflare_response(): void
    {
        $this->configureTurnstile();
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true, 'hostname' => 'someone-elses-site.com', 'action' => 'contact']),
        ]);
        $request = Request::create('http://localhost/contact', 'POST', ['cf-turnstile-response' => 'good-token']);

        $result = app(TurnstileVerificationService::class)->verify($request, 'contact');

        $this->assertFalse($result->success);
        $this->assertSame('hostname-mismatch', $result->reasonCode());
    }

    public function test_wrong_action_is_rejected(): void
    {
        $this->configureTurnstile();
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true, 'hostname' => 'localhost', 'action' => 'login']),
        ]);
        $request = Request::create('http://localhost/contact', 'POST', ['cf-turnstile-response' => 'good-token']);

        $result = app(TurnstileVerificationService::class)->verify($request, 'contact');

        $this->assertFalse($result->success);
        $this->assertSame('action-mismatch', $result->reasonCode());
    }

    public function test_cloudflare_timeout_is_handled_safely_and_flagged_as_provider_unavailable(): void
    {
        $this->configureTurnstile();
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
        });
        $request = Request::create('/contact', 'POST', ['cf-turnstile-response' => 'token']);

        $result = app(TurnstileVerificationService::class)->verify($request);

        $this->assertFalse($result->success);
        $this->assertTrue($result->providerUnavailable);
    }

    public function test_never_sends_the_secret_key_in_a_loggable_way_and_only_posts_expected_fields(): void
    {
        $this->configureTurnstile();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);
        $request = Request::create('/contact', 'POST', ['cf-turnstile-response' => 'token']);

        app(TurnstileVerificationService::class)->verify($request);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $data['secret'] === '1x0000000000000000000000000000AA'
                && $data['response'] === 'token'
                && array_key_exists('remoteip', $data);
        });
    }
}
