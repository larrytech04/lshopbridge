<?php

namespace Tests\Feature\Admin;

use App\Models\ScheduledTaskRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SchedulerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'status' => 'active', 'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);
    }

    public function test_index_lists_every_real_scheduled_command_with_no_history_yet(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.scheduler.index'));

        $response->assertOk();
        $response->assertSeeText('sessions:prune');
        $response->assertSeeText('forms:expire-restrictions');
        $response->assertSeeText('forms:clean-security-data');
        // No command has run yet, so every card shows the "no history" state.
        $response->assertSeeTextInOrder(['No recorded runs yet', 'No recorded runs yet', 'No recorded runs yet']);
    }

    public function test_firing_the_schedule_writes_a_real_run_history_row(): void
    {
        Artisan::call('schedule:test', ['--name' => 'sessions:prune']);

        $this->assertDatabaseCount('scheduled_task_runs', 1);
        $run = ScheduledTaskRun::first();
        $this->assertSame('sessions:prune', $run->command);
        $this->assertNotNull($run->started_at);
        $this->assertNotNull($run->finished_at);
        $this->assertIsBool($run->successful);

        $response = $this->actingAs($this->admin())->get(route('admin.scheduler.index'));
        $response->assertOk();
        // The OTHER two commands legitimately still have no history — only
        // assert sessions:prune's own "last run" no longer says so.
        $response->assertSeeText('Run history');
        $html = $response->getContent();
        $sessionsPruneCard = substr($html, strpos($html, 'sessions:prune'), 600);
        $this->assertStringNotContainsString('No recorded runs yet', $sessionsPruneCard);
    }
}
