<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\SecurityAlert;
use App\Services\Security\CriticalAlertService;
use App\Services\Security\GeoIpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SecurityAlertChannelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_alert_via_always_includes_database(): void
    {
        $user = User::factory()->create(['preferences' => ['notify_security_alerts' => false]]);
        $notification = new SecurityAlert('Title', 'Message');

        $this->assertContains('database', $notification->via($user));
    }

    public function test_security_alert_skips_mail_and_sms_when_the_preference_is_off(): void
    {
        $user = User::factory()->create(['preferences' => ['notify_security_alerts' => false]]);
        $notification = new SecurityAlert('Title', 'Message');

        $channels = $notification->via($user);

        $this->assertNotContains('mail', $channels);
        $this->assertNotContains(SmsChannel::class, $channels);
    }

    public function test_security_alert_includes_mail_and_sms_channel_by_default(): void
    {
        $user = User::factory()->create(['preferences' => []]);
        $notification = new SecurityAlert('Title', 'Message');

        $channels = $notification->via($user);

        $this->assertContains('mail', $channels);
        $this->assertContains(SmsChannel::class, $channels);
    }

    public function test_a_requires_review_alert_still_emails_even_with_the_preference_off(): void
    {
        $user = User::factory()->create(['preferences' => ['notify_security_alerts' => false]]);
        $notification = new SecurityAlert('New device', 'Message', requiresReview: true);

        $channels = $notification->via($user);

        $this->assertContains('mail', $channels);
        // SMS stays preference-gated even for a requires-review alert.
        $this->assertNotContains(SmsChannel::class, $channels);
    }

    public function test_sms_channel_is_a_noop_when_provider_is_not_configured(): void
    {
        config(['services.sms.provider' => null]);
        Http::fake();

        $user = User::factory()->create(['phone' => '+15551234567', 'phone_verified_at' => now()]);
        $channel = app(SmsChannel::class);

        $channel->send($user, new SecurityAlert('Title', 'Message'));

        Http::assertNothingSent();
    }

    public function test_sms_channel_sends_via_twilio_when_configured_and_phone_verified(): void
    {
        config([
            'services.sms.provider' => 'twilio',
            'services.sms.account_sid' => 'ACxxx',
            'services.sms.api_key' => 'secret',
            'services.sms.sender' => '+10000000000',
        ]);
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SMxxx'], 201)]);

        $user = User::factory()->create(['phone' => '+15551234567', 'phone_verified_at' => now()]);
        $channel = app(SmsChannel::class);

        $channel->send($user, new SecurityAlert('Title', 'Message'));

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.twilio.com')
            && $request['To'] === '+15551234567'
            && $request['From'] === '+10000000000');
    }

    public function test_sms_channel_skips_users_without_a_verified_phone(): void
    {
        config([
            'services.sms.provider' => 'twilio',
            'services.sms.account_sid' => 'ACxxx',
            'services.sms.api_key' => 'secret',
            'services.sms.sender' => '+10000000000',
        ]);
        Http::fake();

        $user = User::factory()->create(['phone' => null, 'phone_verified_at' => null]);
        $channel = app(SmsChannel::class);

        $channel->send($user, new SecurityAlert('Title', 'Message'));

        Http::assertNothingSent();
    }

    public function test_critical_alert_service_is_a_noop_when_no_webhooks_are_configured(): void
    {
        config(['services.discord.webhook_url' => null, 'services.slack_alerts.webhook_url' => null]);
        Http::fake();

        app(CriticalAlertService::class)->send('Title', 'Message');

        Http::assertNothingSent();
    }

    public function test_critical_alert_service_posts_to_configured_webhooks(): void
    {
        config([
            'services.discord.webhook_url' => 'https://discord.example/webhook',
            'services.slack_alerts.webhook_url' => 'https://slack.example/webhook',
        ]);
        Http::fake();

        app(CriticalAlertService::class)->send('Title', 'Message');

        Http::assertSent(fn ($request) => $request->url() === 'https://discord.example/webhook');
        Http::assertSent(fn ($request) => $request->url() === 'https://slack.example/webhook');
    }

    public function test_geoip_service_is_a_noop_without_an_api_key(): void
    {
        config(['services.ipinfo.api_key' => null]);
        Http::fake();

        $result = app(GeoIpService::class)->lookup('8.8.8.8');

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    public function test_geoip_service_skips_private_ip_ranges_even_when_configured(): void
    {
        config(['services.ipinfo.api_key' => 'token']);
        Http::fake();

        $result = app(GeoIpService::class)->lookup('127.0.0.1');

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    public function test_geoip_service_returns_country_when_configured(): void
    {
        config(['services.ipinfo.api_key' => 'token']);
        Http::fake([
            'ipinfo.io/8.8.8.8/privacy*' => Http::response([], 403),
            'ipinfo.io/8.8.8.8*' => Http::response(['country' => 'US'], 200),
        ]);

        $result = app(GeoIpService::class)->lookup('8.8.8.8');

        $this->assertSame('US', $result['country']);
        $this->assertNull($result['is_vpn']);
    }
}
