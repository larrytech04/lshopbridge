<?php

namespace App\Console\Commands;

use App\Models\TemporaryFormRestriction;
use Illuminate\Console\Command;

/**
 * Restrictions already stop applying the moment expires_at passes (see
 * TemporaryFormRestriction::isRestricted()) — this only prunes rows that
 * expired a while ago, so recent restriction history stays inspectable in
 * the admin UI for a short grace window instead of disappearing instantly.
 */
class ExpireTemporaryFormRestrictions extends Command
{
    protected $signature = 'forms:expire-restrictions';

    protected $description = 'Delete temporary form restrictions that expired more than 7 days ago';

    public function handle(): void
    {
        $deleted = TemporaryFormRestriction::where('expires_at', '<', now()->subDays(7))->delete();

        $this->info("Deleted {$deleted} expired form restriction(s).");
    }
}
