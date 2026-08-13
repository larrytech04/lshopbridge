<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(SettingsService::class)->flush();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'status' => 'active', 'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);
    }

    private function baseSettingsPayload(array $overrides = []): array
    {
        return array_merge([
            'site_name' => 'LshopBridge',
            'mail_encryption' => 'tls',
        ], $overrides);
    }

    public function test_settings_index_shows_the_seo_tab(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.settings.index'));

        $response->assertOk();
        $response->assertSee('data-settings-tab="seo"', false);
        $response->assertSeeText('SEO');
    }

    public function test_a_guest_cannot_view_or_update_seo_settings(): void
    {
        // Guests hitting any route under the admin path prefix land on the
        // admin login specifically, not the generic public one — see
        // bootstrap/app.php's redirectGuestsTo.
        $this->get(route('admin.settings.index'))->assertRedirect(route('admin.login'));
        $this->put(route('admin.settings.update'), $this->baseSettingsPayload())->assertRedirect(route('admin.login'));
    }

    public function test_a_regular_user_cannot_update_seo_settings(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)
            ->put(route('admin.settings.update'), $this->baseSettingsPayload(['seo_canonical_domain' => 'evil.example']))
            ->assertForbidden();

        $this->assertNull(setting('seo_canonical_domain'));
    }

    public function test_an_admin_can_save_the_canonical_domain(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.settings.update'), $this->baseSettingsPayload(['seo_canonical_domain' => 'lshopbridge.com']))
            ->assertRedirect();

        $this->assertSame('lshopbridge.com', setting('seo_canonical_domain'));
    }

    public function test_an_admin_can_save_the_twitter_handle_and_gtm_id(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->baseSettingsPayload([
            'seo_twitter_handle' => '@lshopbridge',
            'google_tag_manager_id' => 'GTM-ABC1234',
        ]))->assertRedirect();

        $this->assertSame('@lshopbridge', setting('seo_twitter_handle'));
        $this->assertSame('GTM-ABC1234', setting('google_tag_manager_id'));
    }

    public function test_the_indexing_toggle_defaults_to_enabled(): void
    {
        $this->assertTrue((bool) setting('seo_indexing_enabled', true));
    }

    public function test_an_admin_can_turn_off_the_indexing_toggle(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.settings.update'), $this->baseSettingsPayload())
            ->assertRedirect();

        // The checkbox was omitted from the payload — an unchecked checkbox
        // submits nothing at all, which must read as false, same as every
        // other toggle on this form.
        $this->assertFalse((bool) setting('seo_indexing_enabled'));
    }

    public function test_saving_seo_settings_is_recorded_in_the_change_history(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->baseSettingsPayload(['seo_canonical_domain' => 'lshopbridge.com']));

        $this->assertDatabaseHas('system_setting_revisions', ['key' => 'seo_canonical_domain', 'new_value' => 'lshopbridge.com']);
    }
}
