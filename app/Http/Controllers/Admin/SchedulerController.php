<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduledTaskRun;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\View\View;

/**
 * Introspects the real Schedule definition from routes/console.php for the
 * command list + next-run time, and pairs it with ScheduledTaskRun rows for
 * actual run history. There is no way to verify from inside the app that a
 * system cron is invoking `schedule:run` in whatever environment this
 * deploys to — so "last run" is only ever shown when a row genuinely exists,
 * never inferred from the schedule definition alone.
 */
class SchedulerController extends Controller
{
    public function index(Schedule $schedule): View
    {
        // routes/console.php is only required when the console kernel's
        // commands() method runs, which normally only happens for `artisan`
        // invocations. On a plain web request nothing ever calls it, so
        // Schedule::events() would silently come back empty. bootstrap()
        // is idempotent (guarded by $commandsLoaded) and cheap once the app
        // is already booted, so it's safe to call from here.
        app(ConsoleKernel::class)->bootstrap();

        $timezone = config('app.timezone');

        $commands = collect($schedule->events())->map(function ($event) use ($timezone) {
            // $event->command is a shell-escaped string like `"php" "artisan" sessions:prune`
            // (Symfony ProcessUtils quotes each argument) — strip the binary + "artisan" prefix.
            $command = trim(preg_replace('/^.*?artisan[\'"]?\s+/', '', $event->command ?? ''));

            $lastRun = ScheduledTaskRun::where('command', $command)->latest('started_at')->first();

            return [
                'command' => $command ?: $event->command,
                'expression' => $event->getExpression(),
                'next_run' => $event->nextRunDate()->setTimezone($timezone),
                'last_run' => $lastRun,
            ];
        })->values();

        $history = ScheduledTaskRun::latest('started_at')->paginate(20);

        return view('admin.scheduler.index', [
            'commands' => $commands,
            'history' => $history,
        ]);
    }
}
