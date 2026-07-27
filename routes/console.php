<?php

use App\Models\ScheduledTaskRun;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// This file is required fresh by every new Application instance (e.g. once
// per test case), so a plain top-level `function` declaration here would
// fatal with "Cannot redeclare" the second time. Guard it like a class.
if (! function_exists('recordScheduledRun')) {
    /** Records a real ScheduledTaskRun row around a scheduled command, for the admin Scheduler & Cron page. */
    function recordScheduledRun(Event $event, string $command): void
    {
        $runId = null;
        $event->before(function () use (&$runId, $command) {
            $runId = ScheduledTaskRun::create(['command' => $command, 'started_at' => now()])->id;
        });
        $event->after(function () use (&$runId, $event) {
            ScheduledTaskRun::whereKey($runId)->update(['finished_at' => now(), 'successful' => $event->exitCode === 0]);
        });
    }
}

// Deterministic complement to Laravel's probabilistic session-GC lottery.
recordScheduledRun(Schedule::command('sessions:prune')->daily(), 'sessions:prune');

// Forms & bot protection maintenance.
recordScheduledRun(Schedule::command('forms:expire-restrictions')->daily(), 'forms:expire-restrictions');
recordScheduledRun(Schedule::command('forms:clean-security-data')->daily(), 'forms:clean-security-data');
