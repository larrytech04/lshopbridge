<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class QueueManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
    }

    private function insertFailedJob(): string
    {
        $uuid = (string) Str::uuid();

        DB::table('failed_jobs')->insert([
            'uuid' => $uuid,
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\RunProductImport', 'job' => 'Illuminate\\Queue\\CallQueuedHandler@call', 'data' => []]),
            'exception' => "Exception: Something went wrong\nstack trace...",
            'failed_at' => now(),
        ]);

        return $uuid;
    }

    public function test_index_lists_pending_and_failed_jobs_with_readable_names(): void
    {
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\RunProductImport']),
            'attempts' => 0,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);
        $this->insertFailedJob();

        $response = $this->actingAs($this->admin())->get(route('admin.queues.index'));

        $response->assertOk();
        $response->assertSeeText('RunProductImport');
    }

    public function test_retry_moves_the_job_back_onto_the_queue_and_clears_the_failed_row(): void
    {
        $uuid = $this->insertFailedJob();

        $response = $this->actingAs($this->admin())->post(route('admin.queues.retry', $uuid));

        $response->assertRedirect();
        $this->assertDatabaseMissing('failed_jobs', ['uuid' => $uuid]);
        $this->assertSame(1, DB::table('jobs')->count());
    }

    public function test_retry_404s_for_an_unknown_uuid(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.queues.retry', (string) Str::uuid()));

        $response->assertNotFound();
    }

    public function test_destroy_discards_the_failed_job_without_queueing_it(): void
    {
        $uuid = $this->insertFailedJob();

        $response = $this->actingAs($this->admin())->delete(route('admin.queues.destroy', $uuid));

        $response->assertRedirect();
        $this->assertDatabaseMissing('failed_jobs', ['uuid' => $uuid]);
        $this->assertSame(0, DB::table('jobs')->count());
    }
}
