<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
    }

    public function test_index_shows_the_active_driver_and_available_actions(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.cache.index'));

        $response->assertOk();
        $response->assertSeeText(config('cache.default'));
        $response->assertSeeText('Application cache');
    }

    public function test_clearing_settings_cache_actually_flushes_the_settings_cache(): void
    {
        Setting::create(['key' => 'site_name', 'value' => 'Cached Name', 'type' => 'string', 'group' => 'general']);
        app(\App\Services\Settings\SettingsService::class)->all();
        $this->assertTrue(Cache::has('platform.settings'));

        $response = $this->actingAs($this->admin())->post(route('admin.cache.clear', 'settings'));

        $response->assertRedirect();
        $this->assertFalse(Cache::has('platform.settings'));
    }

    public function test_unknown_cache_key_404s(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.cache.clear', 'not-a-real-key'));

        $response->assertNotFound();
    }
}
