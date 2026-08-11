<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Notifications\SecurityAlert;
use App\Services\Security\LoginSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoginSecurityServiceTest extends TestCase
{
    use RefreshDatabase;

    private function requestFrom(string $userAgent): Request
    {
        return Request::create('/login', 'POST', server: ['HTTP_USER_AGENT' => $userAgent]);
    }

    public function test_the_very_first_login_is_not_treated_as_a_new_device_and_sends_no_alert(): void
    {
        Notification::fake();
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $attempt = app(LoginSecurityService::class)->recordSuccess($user, $this->requestFrom('UA-1'));

        $this->assertTrue($attempt->was_new_device);
        Notification::assertNothingSent();
    }

    public function test_a_login_from_a_never_seen_user_agent_is_flagged_and_alerts_the_user(): void
    {
        Notification::fake();
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        app(LoginSecurityService::class)->recordSuccess($user, $this->requestFrom('UA-1'));

        $second = app(LoginSecurityService::class)->recordSuccess($user, $this->requestFrom('UA-2'));

        $this->assertTrue($second->was_new_device);
        Notification::assertSentTo($user, SecurityAlert::class);
    }

    public function test_a_login_from_a_previously_seen_user_agent_is_not_flagged(): void
    {
        Notification::fake();
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        app(LoginSecurityService::class)->recordSuccess($user, $this->requestFrom('UA-1'));

        $second = app(LoginSecurityService::class)->recordSuccess($user, $this->requestFrom('UA-1'));

        $this->assertFalse($second->was_new_device);
        Notification::assertNotSentTo($user, SecurityAlert::class);
    }

    public function test_last_login_was_new_device_reflects_the_most_recent_successful_attempt(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $service = app(LoginSecurityService::class);
        $service->recordSuccess($user, $this->requestFrom('UA-1'));
        $service->recordSuccess($user, $this->requestFrom('UA-1'));

        $this->assertFalse($service->lastLoginWasNewDevice($user));

        $service->recordSuccess($user, $this->requestFrom('UA-Brand-New'));

        $this->assertTrue($service->lastLoginWasNewDevice($user));
    }

    private function requestFromIp(string $userAgent, string $ip): Request
    {
        return Request::create('/login', 'POST', server: ['HTTP_USER_AGENT' => $userAgent, 'REMOTE_ADDR' => $ip]);
    }

    public function test_country_is_not_recorded_when_geoip_is_not_configured(): void
    {
        config(['services.ipinfo.api_key' => null]);
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $attempt = app(LoginSecurityService::class)->recordSuccess($user, $this->requestFromIp('UA-1', '1.1.1.1'));

        $this->assertNull($attempt->country);
        $this->assertNull($attempt->was_new_country);
    }

    public function test_a_login_from_a_new_country_is_flagged_when_geoip_is_configured(): void
    {
        config(['services.ipinfo.api_key' => 'token']);
        Notification::fake();
        Http::fake([
            'ipinfo.io/1.1.1.1/privacy*' => Http::response([], 403),
            'ipinfo.io/1.1.1.1*' => Http::response(['country' => 'US'], 200),
            'ipinfo.io/2.2.2.2/privacy*' => Http::response([], 403),
            'ipinfo.io/2.2.2.2*' => Http::response(['country' => 'FR'], 200),
        ]);

        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $service = app(LoginSecurityService::class);
        $service->recordSuccess($user, $this->requestFromIp('UA-1', '1.1.1.1'));

        $second = $service->recordSuccess($user, $this->requestFromIp('UA-1', '2.2.2.2'));

        $this->assertSame('FR', $second->country);
        $this->assertTrue($second->was_new_country);
        Notification::assertSentTo($user, SecurityAlert::class);
    }

    public function test_a_login_from_a_previously_seen_country_is_not_flagged(): void
    {
        config(['services.ipinfo.api_key' => 'token']);
        Notification::fake();
        Http::fake([
            'ipinfo.io/1.1.1.1/privacy*' => Http::response([], 403),
            'ipinfo.io/1.1.1.1*' => Http::response(['country' => 'US'], 200),
        ]);

        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $service = app(LoginSecurityService::class);
        $service->recordSuccess($user, $this->requestFromIp('UA-1', '1.1.1.1'));

        $second = $service->recordSuccess($user, $this->requestFromIp('UA-1', '1.1.1.1'));

        $this->assertFalse($second->was_new_country);
        Notification::assertNotSentTo($user, SecurityAlert::class);
    }

    public function test_a_new_device_login_to_an_admin_account_also_posts_a_critical_ops_alert(): void
    {
        config(['services.discord.webhook_url' => 'https://discord.example/webhook']);
        Notification::fake();
        Http::fake();

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);
        $service = app(LoginSecurityService::class);
        $service->recordSuccess($admin, $this->requestFrom('UA-1'));

        $service->recordSuccess($admin, $this->requestFrom('UA-2'));

        Http::assertSent(fn ($request) => $request->url() === 'https://discord.example/webhook');
    }

    public function test_a_new_device_login_to_a_regular_account_does_not_post_a_critical_ops_alert(): void
    {
        config(['services.discord.webhook_url' => 'https://discord.example/webhook']);
        Notification::fake();
        Http::fake();

        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $service = app(LoginSecurityService::class);
        $service->recordSuccess($user, $this->requestFrom('UA-1'));

        $service->recordSuccess($user, $this->requestFrom('UA-2'));

        Http::assertNothingSent();
    }
}
