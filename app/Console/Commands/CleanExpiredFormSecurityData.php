<?php

namespace App\Console\Commands;

use App\Models\FormSecurityEvent;
use App\Models\ProtectedFormSubmission;
use App\Models\SpamReviewCase;
use Illuminate\Console\Command;

/** Enforces the configurable "Security event retention period" setting. */
class CleanExpiredFormSecurityData extends Command
{
    protected $signature = 'forms:clean-security-data';

    protected $description = 'Delete form security events, submission ledger rows, and resolved review cases past the configured retention period';

    public function handle(): void
    {
        $cutoff = now()->subDays((int) setting('security_event_retention_days', 90));

        $events = FormSecurityEvent::where('created_at', '<', $cutoff)->delete();
        $submissions = ProtectedFormSubmission::where('created_at', '<', $cutoff)->delete();
        $reviewCases = SpamReviewCase::where('created_at', '<', $cutoff)
            ->whereIn('status', ['legitimate', 'spam', 'archived'])
            ->delete();

        $this->info("Deleted {$events} security event(s), {$submissions} submission ledger row(s), {$reviewCases} resolved review case(s) older than {$cutoff->toDateString()}.");
    }
}
