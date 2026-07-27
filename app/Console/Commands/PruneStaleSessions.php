<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Laravel's own session GC (config/session.php's `lottery`) only fires
 * probabilistically (2% of requests by default), so on a low-traffic site
 * expired session rows can sit around for a long time between lottery wins.
 * This gives a deterministic guarantee via the scheduler instead.
 */
class PruneStaleSessions extends Command
{
    protected $signature = 'sessions:prune';

    protected $description = 'Delete session rows older than the configured session lifetime';

    public function handle(): int
    {
        $cutoff = now()->subMinutes((int) config('session.lifetime'))->getTimestamp();

        $deleted = DB::table(config('session.table', 'sessions'))
            ->where('last_activity', '<', $cutoff)
            ->delete();

        $this->info("Pruned {$deleted} stale session row(s).");

        return self::SUCCESS;
    }
}
