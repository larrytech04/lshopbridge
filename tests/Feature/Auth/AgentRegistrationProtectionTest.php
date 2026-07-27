<?php

namespace Tests\Feature\Auth;

use App\Models\Country;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AgentRegistrationProtectionTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Country $country): array
    {
        return [
            'name' => 'New Agent',
            'email' => 'newagent@example.com',
            'phone' => '+237600000099',
            'country_id' => $country->id,
            'business_name' => 'Guangzhou Shipping Co',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'terms' => '1',
        ];
    }

    public function test_registration_succeeds_when_turnstile_is_not_configured(): void
    {
        $country = Country::factory()->create(['is_active' => true]);

        $response = $this->post(route('register.agent'), $this->payload($country));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'newagent@example.com']);
        $this->assertDatabaseHas('agents', ['business_name' => 'Guangzhou Shipping Co']);
    }

    public function test_registration_is_blocked_when_turnstile_is_enabled_and_verification_fails(): void
    {
        config(['services.turnstile.site_key' => '1x00000000000000000000AA', 'services.turnstile.secret_key' => '1x0000000000000000000000000000AA']);
        Setting::create(['key' => 'turnstile_enabled', 'value' => '1', 'type' => 'bool', 'group' => 'general']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false, 'error-codes' => ['invalid-input-response']])]);
        $country = Country::factory()->create(['is_active' => true]);

        $response = $this->post(route('register.agent'), $this->payload($country));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('users', ['email' => 'newagent@example.com']);
    }

    public function test_registration_protection_setting_off_skips_the_turnstile_check(): void
    {
        config(['services.turnstile.site_key' => '1x00000000000000000000AA', 'services.turnstile.secret_key' => '1x0000000000000000000000000000AA']);
        Setting::create(['key' => 'turnstile_enabled', 'value' => '1', 'type' => 'bool', 'group' => 'general']);
        Setting::create(['key' => 'agent_registration_protection', 'value' => '0', 'type' => 'bool', 'group' => 'general']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);
        $country = Country::factory()->create(['is_active' => true]);

        $response = $this->post(route('register.agent'), $this->payload($country));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'newagent@example.com']);
    }
}
