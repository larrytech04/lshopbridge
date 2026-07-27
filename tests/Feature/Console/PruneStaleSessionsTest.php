<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PruneStaleSessionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_only_sessions_older_than_the_configured_lifetime(): void
    {
        $lifetimeMinutes = (int) config('session.lifetime');

        DB::table('sessions')->insert([
            ['id' => 'stale-one', 'last_activity' => now()->subMinutes($lifetimeMinutes + 10)->getTimestamp(), 'payload' => 'x'],
            ['id' => 'stale-two', 'last_activity' => now()->subDays(30)->getTimestamp(), 'payload' => 'x'],
            ['id' => 'fresh-one', 'last_activity' => now()->getTimestamp(), 'payload' => 'x'],
        ]);

        $this->artisan('sessions:prune')->assertSuccessful();

        $this->assertDatabaseMissing('sessions', ['id' => 'stale-one']);
        $this->assertDatabaseMissing('sessions', ['id' => 'stale-two']);
        $this->assertDatabaseHas('sessions', ['id' => 'fresh-one']);
    }
}
