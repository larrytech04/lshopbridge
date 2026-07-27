<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Real backlog view over the `jobs`/`failed_jobs` tables — framed as a
 * backlog, not "worker health", since nothing in this codebase proves a
 * queue worker process is actually running in whatever environment this
 * deploys to. An empty backlog here just means no jobs are pending; it is
 * not evidence a worker is up.
 */
class QueueController extends Controller
{
    public function index(): View
    {
        $pending = DB::table('jobs')->orderBy('created_at')->get()->map(function ($job) {
            $payload = json_decode($job->payload, true) ?? [];

            return [
                'id' => $job->id,
                'queue' => $job->queue,
                'display_name' => $payload['displayName'] ?? 'Unknown',
                'attempts' => $job->attempts,
                'available_at' => \Illuminate\Support\Carbon::createFromTimestamp($job->available_at),
                'created_at' => \Illuminate\Support\Carbon::createFromTimestamp($job->created_at),
            ];
        });

        $failed = DB::table('failed_jobs')->orderByDesc('failed_at')->get()->map(function ($job) {
            $payload = json_decode($job->payload, true) ?? [];

            return [
                'uuid' => $job->uuid,
                'queue' => $job->queue,
                'display_name' => $payload['displayName'] ?? 'Unknown',
                'exception' => $job->exception,
                'failed_at' => $job->failed_at,
            ];
        });

        return view('admin.queues.index', [
            'pending' => $pending,
            'failed' => $failed,
        ]);
    }

    public function retry(string $uuid): RedirectResponse
    {
        if (! DB::table('failed_jobs')->where('uuid', $uuid)->exists()) {
            abort(404);
        }

        Artisan::call('queue:retry', ['id' => [$uuid]]);

        return back()->with('success', 'Job queued for retry.');
    }

    public function destroy(string $uuid): RedirectResponse
    {
        if (! DB::table('failed_jobs')->where('uuid', $uuid)->exists()) {
            abort(404);
        }

        Artisan::call('queue:forget', ['id' => $uuid]);

        return back()->with('success', 'Failed job discarded.');
    }
}
