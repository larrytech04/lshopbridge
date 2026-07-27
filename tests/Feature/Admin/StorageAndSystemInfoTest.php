<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorageAndSystemInfoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
    }

    public function test_storage_index_reports_real_disk_usage_for_each_local_disk(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.storage.index'));

        $response->assertOk();
        $response->assertSeeText('local');
        $response->assertSeeText('public');
        $response->assertSeeText('private');
        // local and private share a root in filesystems.php — the page must say so, not hide it.
        $response->assertSeeText('Shares a physical folder with');
    }

    public function test_system_info_shows_the_real_running_versions_and_drivers(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.system-info.index'));

        $response->assertOk();
        $response->assertSeeText('v'.config('platform.version'));
        $response->assertSeeText(app()->version());
        $response->assertSeeText(PHP_VERSION);
        $response->assertSeeText(config('cache.default'));
    }

    public function test_system_overview_reuses_provider_health_service_signals(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.system.index'));

        $response->assertOk();
        $response->assertSeeText('Database');
        $response->assertSeeText('Not tracked yet');
        $response->assertSeeText('Email delivery rate');
    }

    public function test_footer_version_matches_the_single_config_source_of_truth(): void
    {
        config(['platform.version' => '9.9.9']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSeeText('v9.9.9');
    }
}
