<?php

namespace Tests\Feature\Admin;

use App\Models\SystemSettingRevision;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class SettingsHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
    }

    public function test_index_renders_the_tabbed_workspace(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.settings.index'));

        $response->assertOk();
        $response->assertSee('data-settings-tab="branding"', false);
        $response->assertSee('Change history');
    }

    public function test_changing_a_setting_records_a_revision_with_the_actor(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        app(SettingsService::class)->set('site_name', 'New Site Name', 'string', 'general');

        $this->assertDatabaseHas('system_setting_revisions', [
            'key' => 'site_name',
            'new_value' => 'New Site Name',
            'changed_by' => $admin->id,
        ]);
    }

    public function test_saving_the_same_value_again_does_not_create_a_duplicate_revision(): void
    {
        app(SettingsService::class)->set('site_name', 'Same Name', 'string', 'general');
        app(SettingsService::class)->set('site_name', 'Same Name', 'string', 'general');

        $this->assertSame(1, SystemSettingRevision::where('key', 'site_name')->count());
    }

    public function test_sensitive_key_values_are_masked_in_revision_history(): void
    {
        app(SettingsService::class)->set('mail_password', Crypt::encryptString('super-secret'), 'string', 'general');

        $revision = SystemSettingRevision::where('key', 'mail_password')->firstOrFail();

        $this->assertSame('••••••••', $revision->new_value);
        $this->assertStringNotContainsString('super-secret', $revision->new_value ?? '');
    }

    public function test_history_drawer_lists_recorded_revisions(): void
    {
        $admin = $this->admin();
        app(SettingsService::class)->set('site_name', 'Visible In Drawer', 'string', 'general');

        $response = $this->actingAs($admin)->get(route('admin.settings.index'));

        $response->assertOk();
        $response->assertSeeText('site_name');
        $response->assertSeeText('Visible In Drawer');
    }
}
