<?php

namespace Tests\Feature\Public;

use App\Models\Agent;
use App\Models\Guide;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FooterTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_create_account_as_the_primary_action(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('Create Account');
    }

    public function test_logged_in_customer_sees_deposit_as_the_primary_action_not_create_account(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee('Deposit Funds');
        $response->assertDontSee('Create Account');
    }

    public function test_agent_sees_agent_workspace_action(): void
    {
        $agentUser = User::factory()->create(['role' => 'agent', 'status' => 'active']);
        Agent::factory()->approved()->create(['user_id' => $agentUser->id]);

        $response = $this->actingAs($agentUser)->get(route('home'));

        $response->assertOk()->assertSee('Open Agent Workspace');
    }

    public function test_footer_never_shows_visa_mastercard_without_a_real_active_card_method(): void
    {
        // No hardcoding: Visa/Mastercard/Apple Pay/Google Pay are only ever
        // shown as the real accepted networks of an actually active, country-
        // available "card" PaymentMethod (see payment-strip.blade.php) — never
        // unconditionally. Scoped to <footer>: the homepage's own separate
        // payment marquee (public/home.blade.php) is unrelated to this footer.
        PaymentMethod::query()->delete();
        PaymentMethod::factory()->create(['code' => 'test_momo', 'name' => 'Test Mobile Money', 'type' => 'momo', 'is_active' => true, 'marketplace_enabled' => true]);

        $html = $this->get(route('home'))->assertOk()->getContent();
        preg_match('/<footer.*<\/footer>/s', $html, $m);
        $footerHtml = $m[0] ?? '';

        $this->assertNotSame('', $footerHtml, 'Footer markup not found in response.');
        $this->assertStringNotContainsString('Visa', $footerHtml);
        $this->assertStringNotContainsString('Mastercard', $footerHtml);
    }

    public function test_footer_shows_visa_mastercard_apple_pay_as_the_real_card_methods_networks(): void
    {
        PaymentMethod::query()->delete();
        PaymentMethod::factory()->create(['code' => 'test_card', 'name' => 'Card', 'type' => 'card', 'is_active' => true, 'marketplace_enabled' => true]);

        $html = $this->get(route('home'))->assertOk()->getContent();
        preg_match('/<footer.*<\/footer>/s', $html, $m);
        $footerHtml = $m[0] ?? '';

        $this->assertNotSame('', $footerHtml, 'Footer markup not found in response.');
        $this->assertStringContainsString('Visa', $footerHtml);
        $this->assertStringContainsString('Mastercard', $footerHtml);
        $this->assertStringContainsString('Apple Pay', $footerHtml);
        $this->assertStringNotContainsString('Google Pay', $footerHtml);
    }

    public function test_footer_never_shows_crypto_or_bank_transfer(): void
    {
        PaymentMethod::query()->delete();
        PaymentMethod::factory()->create(['code' => 'test_crypto', 'name' => 'Test Crypto', 'type' => 'crypto', 'is_active' => true, 'marketplace_enabled' => true]);
        PaymentMethod::factory()->create(['code' => 'test_bank', 'name' => 'Test Bank Transfer', 'type' => 'bank', 'is_active' => true, 'marketplace_enabled' => true]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('Test Crypto');
        $response->assertDontSee('Test Bank Transfer');
    }

    public function test_footer_payment_row_never_shows_more_than_five_methods(): void
    {
        PaymentMethod::query()->delete();
        foreach (range(1, 8) as $i) {
            PaymentMethod::factory()->create(['code' => "test_momo_{$i}", 'name' => "Test Momo {$i}", 'type' => 'momo', 'is_active' => true, 'marketplace_enabled' => true, 'sort' => $i]);
        }

        $html = $this->get(route('home'))->assertOk()->getContent();
        preg_match('/<footer.*<\/footer>/s', $html, $m);
        $footerHtml = $m[0] ?? '';

        $shown = 0;
        foreach (range(1, 8) as $i) {
            if (str_contains($footerHtml, "Test Momo {$i}")) {
                $shown++;
            }
        }

        $this->assertSame(5, $shown);
    }

    public function test_footer_shows_real_active_payment_method_names(): void
    {
        PaymentMethod::query()->delete();
        PaymentMethod::factory()->create(['code' => 'test_momo', 'name' => 'Test Mobile Money', 'type' => 'momo', 'is_active' => true, 'marketplace_enabled' => true]);

        $this->get(route('home'))->assertOk()->assertSee('Test Mobile Money');
    }

    public function test_footer_hides_inactive_payment_methods(): void
    {
        PaymentMethod::query()->delete();
        PaymentMethod::factory()->create(['code' => 'hidden_method', 'name' => 'Hidden Method', 'is_active' => false, 'marketplace_enabled' => true]);

        $this->get(route('home'))->assertOk()->assertDontSee('Hidden Method');
    }

    public function test_footer_only_shows_a_country_restricted_method_in_its_real_countries(): void
    {
        PaymentMethod::query()->delete();
        PaymentMethod::factory()->create([
            'code' => 'test_momo', 'name' => 'Test Regional Momo', 'type' => 'momo',
            'is_active' => true, 'marketplace_enabled' => true, 'countries' => ['NG'],
        ]);

        $this->withSession(['region' => ['iso' => 'NG', 'name' => 'Nigeria', 'flag' => '🇳🇬']])
            ->get(route('home'))->assertOk()->assertSee('Test Regional Momo');

        $this->withSession(['region' => ['iso' => 'CM', 'name' => 'Cameroon', 'flag' => '🇨🇲']])
            ->get(route('home'))->assertOk()->assertDontSee('Test Regional Momo');
    }

    public function test_footer_shows_an_unrestricted_method_regardless_of_country(): void
    {
        PaymentMethod::query()->delete();
        PaymentMethod::factory()->create([
            'code' => 'test_global', 'name' => 'Test Global Momo', 'type' => 'momo',
            'is_active' => true, 'marketplace_enabled' => true, 'countries' => null,
        ]);

        $this->withSession(['region' => ['iso' => 'KE', 'name' => 'Kenya', 'flag' => '🇰🇪']])
            ->get(route('home'))->assertOk()->assertSee('Test Global Momo');
    }

    public function test_footer_hides_social_links_that_are_not_configured(): void
    {
        // No social_* settings seeded in a fresh test DB — the old footer's
        // decorative icon row would still show 4 unlinked icons regardless.
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('Connect with LshopBridge');
    }

    public function test_footer_shows_a_configured_social_link(): void
    {
        app(SettingsService::class)->set('social_whatsapp', 'https://wa.me/1234567890', 'string');

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Connect with LshopBridge');
        $response->assertSee('https://wa.me/1234567890', false);
    }

    public function test_footer_shows_no_status_badge_when_maintenance_mode_is_off(): void
    {
        app(SettingsService::class)->set('maintenance_mode', '0', 'bool');

        $this->get(route('shop.index'))->assertOk()->assertDontSee('All Systems Operational');
    }

    public function test_footer_shows_a_maintenance_warning_when_maintenance_mode_is_on(): void
    {
        app(SettingsService::class)->set('maintenance_mode', '1', 'bool');
        $admin = \App\Models\User::factory()->create(['role' => 'admin', 'status' => 'active', 'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);

        // Maintenance mode 503s the public site for guests, so the footer
        // itself is only reachable (and worth checking) as the admin who
        // bypasses the block to turn it back off. Uses the home route
        // specifically: it always renders layouts.public (and therefore the
        // real Network Footer), unlike shop.index which switches to the
        // authenticated app shell's own, different footer once logged in.
        $this->actingAs($admin)->get(route('home'))->assertOk()->assertSee('Scheduled Maintenance');
    }

    public function test_status_badge_never_shows_a_fabricated_uptime_percentage(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('99.9%');
        $response->assertDontSee('99.99%');
    }

    public function test_footer_never_shows_unverified_certification_claims(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        foreach (['PCI Certified', 'SOC 2 Certified', 'ISO Certified', 'Bank-Level Security', 'Licensed Financial Institution', 'Government Approved'] as $claim) {
            $response->assertDontSee($claim);
        }
    }

    public function test_footer_never_shows_fake_app_store_links(): void
    {
        // No app-store URL setting exists anywhere in this app (confirmed in
        // the pre-build audit) — the old footer showed static badges anyway.
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('apps.apple.com', false);
        $response->assertDontSee('play.google.com', false);
    }

    public function test_legal_bar_links_to_the_real_legal_center(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(route('legal.index'), false);
    }

    public function test_footer_discover_column_lists_real_links_without_a_promotional_guide_card(): void
    {
        Guide::query()->update(['is_featured' => false]);
        Guide::factory()->create(['title' => 'How To Pick A Reliable Agent', 'is_featured' => true, 'is_published' => true, 'read_minutes' => 4, 'category' => 'shipping']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Discover LshopBridge');
        // The featured-guide promo card was cut for a shorter footer — the
        // Discover column links to the Learning Center itself instead.
        $response->assertDontSee('How To Pick A Reliable Agent');
    }

    public function test_newsletter_subscription_stores_selected_interests(): void
    {
        $response = $this->post(route('newsletter.subscribe'), [
            'email' => 'brief-subscriber@example.com',
            'interests' => ['china_shopping', 'wallet_funding'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'brief-subscriber@example.com']);
        $subscriber = \App\Models\NewsletterSubscriber::where('email', 'brief-subscriber@example.com')->first();
        $this->assertSame(['china_shopping', 'wallet_funding'], $subscriber->interests);
    }

    public function test_newsletter_rejects_an_interest_key_that_does_not_exist(): void
    {
        $response = $this->post(route('newsletter.subscribe'), [
            'email' => 'bad-interest@example.com',
            'interests' => ['not_a_real_interest'],
        ]);

        $response->assertSessionHasErrors('interests.0');
    }

public function test_footer_renders_without_horizontal_overflow_markup_regressions_on_public_pages(): void
    {
        foreach ([route('home'), route('shop.index'), route('legal.index')] as $url) {
            $this->get($url)->assertOk()->assertSee('footer-shell', false);
        }
    }
}
