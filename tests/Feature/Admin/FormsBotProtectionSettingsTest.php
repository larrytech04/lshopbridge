<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FormsBotProtectionSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // CACHE_STORE=array in testing isn't reset by RefreshDatabase, so a
        // setting cached by an earlier test in this run would otherwise leak
        // into this one whenever a test writes a Setting row directly instead
        // of through SettingsService::set() (which flushes it itself).
        app(\App\Services\Settings\SettingsService::class)->flush();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
    }

    /** Minimal valid payload for the whitelisted schema so the PUT doesn't fail other tabs' required fields. */
    private function baseSettingsPayload(array $overrides = []): array
    {
        return array_merge([
            'site_name' => 'LshopBridge',
            'mail_encryption' => 'tls',
        ], $overrides);
    }

    public function test_settings_index_shows_the_forms_bot_protection_tab(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.settings.index'));

        $response->assertOk();
        $response->assertSee('data-settings-tab="forms_bot_protection"', false);
        $response->assertSeeText('Forms & bot protection');
    }

    public function test_saving_turns_on_honeypot_and_persists_it(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->baseSettingsPayload([
            'honeypot_enabled' => '1',
            'bot_protection_enabled' => '1',
        ]));

        $this->assertTrue((bool) setting('honeypot_enabled'));
    }

    public function test_unchecked_boolean_settings_are_saved_as_false(): void
    {
        Setting::create(['key' => 'rate_limiting_enabled', 'value' => '1', 'type' => 'bool', 'group' => 'general']);

        $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->baseSettingsPayload());

        $this->assertFalse((bool) setting('rate_limiting_enabled'));
    }

    public function test_admin_alert_threshold_persists_as_an_integer(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->baseSettingsPayload(['admin_alert_threshold' => '12']));

        $this->assertSame(12, setting('admin_alert_threshold'));
    }

    public function test_turning_off_bot_protection_sends_a_configuration_change_alert(): void
    {
        config(['services.discord.webhook_url' => 'https://discord.example/webhook']);
        Http::fake();
        Setting::create(['key' => 'bot_protection_enabled', 'value' => '1', 'type' => 'bool', 'group' => 'general']);

        $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->baseSettingsPayload(['bot_protection_enabled' => '0']));

        Http::assertSent(fn ($request) => $request->url() === 'https://discord.example/webhook');
    }

    public function test_turning_off_bot_protection_when_already_off_does_not_alert_again(): void
    {
        config(['services.discord.webhook_url' => 'https://discord.example/webhook']);
        Http::fake();
        // Seed all four watched keys as already off, so the payload (which
        // leaves every checkbox unchecked) represents "staying off" for all
        // of them, not a fresh true->false transition on the other three.
        foreach (['bot_protection_enabled', 'rate_limiting_enabled', 'turnstile_enabled', 'honeypot_enabled'] as $key) {
            Setting::create(['key' => $key, 'value' => '0', 'type' => 'bool', 'group' => 'general']);
        }

        $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->baseSettingsPayload());

        Http::assertNothingSent();
    }

    public function test_turnstile_secret_status_is_shown_without_ever_printing_the_secret_value(): void
    {
        config(['services.turnstile.secret_key' => '1x0000000000000000000000000000AA']);

        $response = $this->actingAs($this->admin())->get(route('admin.settings.index'));

        $response->assertOk();
        $response->assertDontSee('1x0000000000000000000000000000AA');
        $response->assertSeeText('Configured');
    }
}
