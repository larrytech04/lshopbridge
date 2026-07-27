<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogFiltersTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
    }

    public function test_module_filter_only_matches_that_module_prefix(): void
    {
        $actor = $this->admin();
        AuditLog::create(['user_id' => $actor->id, 'action' => 'admin.settings.updated', 'description' => 'Settings change']);
        AuditLog::create(['user_id' => $actor->id, 'action' => 'admin.banner.created', 'description' => 'Banner change']);

        $response = $this->actingAs($actor)->get(route('admin.audit.index', ['module' => 'settings']));

        $response->assertOk();
        $response->assertSeeText('Settings change');
        $response->assertDontSeeText('Banner change');
    }

    public function test_actor_filter_scopes_to_the_selected_user(): void
    {
        $actorA = $this->admin();
        $actorB = $this->admin();
        AuditLog::create(['user_id' => $actorA->id, 'action' => 'admin.settings.updated', 'description' => 'By A']);
        AuditLog::create(['user_id' => $actorB->id, 'action' => 'admin.settings.updated', 'description' => 'By B']);

        $response = $this->actingAs($actorA)->get(route('admin.audit.index', ['actor' => $actorA->id]));

        $response->assertOk();
        $response->assertSeeText('By A');
        $response->assertDontSeeText('By B');
    }

    public function test_date_range_filter_excludes_entries_outside_the_range(): void
    {
        $actor = $this->admin();
        $inRange = AuditLog::create(['user_id' => $actor->id, 'action' => 'admin.settings.updated', 'description' => 'Recent']);
        $outOfRange = AuditLog::create(['user_id' => $actor->id, 'action' => 'admin.settings.updated', 'description' => 'Ancient']);
        $outOfRange->forceFill(['created_at' => now()->subYears(2)])->save();

        $response = $this->actingAs($actor)->get(route('admin.audit.index', ['from' => now()->subDay()->toDateString()]));

        $response->assertOk();
        $response->assertSeeText('Recent');
        $response->assertDontSeeText('Ancient');
    }

    public function test_show_renders_a_before_after_diff_for_a_changed_field(): void
    {
        $actor = $this->admin();
        $log = AuditLog::create([
            'user_id' => $actor->id,
            'action' => 'admin.settings.updated',
            'description' => 'Settings change',
            'properties' => ['before' => ['site_name' => 'Old'], 'after' => ['site_name' => 'New']],
        ]);

        $response = $this->actingAs($actor)->get(route('admin.audit.show', $log));

        $response->assertOk();
        $response->assertSeeText('site_name');
        $response->assertSeeText('Old');
        $response->assertSeeText('New');
    }
}
