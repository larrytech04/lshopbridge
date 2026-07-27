@extends('layouts.admin')
@section('page-title', 'Jobs & queues')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-strong">Jobs & queues</h1>
        <p class="text-sm text-muted">Real backlog from the database queue driver. This shows what's queued, not whether a worker process is currently running.</p>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-white" style="background: #64748B"><x-icon name="clock" class="h-4 w-4" /></span>
                <p class="truncate text-[11px] text-faint">Pending jobs</p>
            </div>
            <p class="mt-2 text-lg font-bold text-strong">{{ $pending->count() }}</p>
        </div>
        <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-white" style="background: #EF4444"><x-icon name="ban" class="h-4 w-4" /></span>
                <p class="truncate text-[11px] text-faint">Failed jobs</p>
            </div>
            <p class="mt-2 text-lg font-bold text-strong">{{ $failed->count() }}</p>
        </div>
        <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-white" style="background: #64748B"><x-icon name="cog" class="h-4 w-4" /></span>
                <p class="truncate text-[11px] text-faint">Queue driver</p>
            </div>
            <p class="mt-2 text-lg font-bold text-strong">{{ config('queue.default') }}</p>
        </div>
    </div>

    <section>
        <h3 class="mb-3 font-semibold text-strong">Failed jobs</h3>
        <div class="card-solid overflow-hidden rounded-2xl border border-app shadow-sm">
            @forelse ($failed as $job)
                <div class="flex items-start gap-3 border-b border-app px-5 py-4 last:border-0">
                    <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-rose-500/15 text-rose-600">
                        <x-icon name="ban" class="h-4.5 w-4.5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-mono text-sm text-strong">{{ $job['display_name'] }}</p>
                        <p class="text-xs text-faint">{{ $job['queue'] }} · failed {{ \Illuminate\Support\Carbon::parse($job['failed_at'])->diffForHumans() }}</p>
                        <details class="mt-1">
                            <summary class="cursor-pointer text-xs text-muted">View exception</summary>
                            <pre class="mt-2 max-h-48 overflow-auto rounded-xl bg-black/40 p-3 text-[11px] text-body">{{ \Illuminate\Support\Str::limit($job['exception'], 2000) }}</pre>
                        </details>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <form method="POST" action="{{ route('admin.queues.retry', $job['uuid']) }}">@csrf<button class="btn btn-primary py-1.5 text-xs">Retry</button></form>
                        <form method="POST" action="{{ route('admin.queues.destroy', $job['uuid']) }}" onsubmit="return confirm('Discard this failed job permanently?')">@csrf @method('DELETE')<button class="btn btn-ghost py-1.5 text-xs">Discard</button></form>
                    </div>
                </div>
            @empty
                <div class="p-2">
                    <x-empty icon="check-circle" title="No failed jobs" message="Nothing here right now — jobs that exhaust their retries will show up in this list." />
                </div>
            @endforelse
        </div>
    </section>

    <section>
        <h3 class="mb-3 font-semibold text-strong">Pending backlog</h3>
        <div class="card-solid overflow-hidden rounded-2xl border border-app shadow-sm">
            @forelse ($pending as $job)
                <div class="flex items-center gap-3 border-b border-app px-5 py-3 last:border-0">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-slate-500/15 text-slate-600">
                        <x-icon name="clock" class="h-4.5 w-4.5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-mono text-sm text-strong">{{ $job['display_name'] }}</p>
                        <p class="text-xs text-faint">{{ $job['queue'] }} · queued {{ $job['created_at']->diffForHumans() }} · {{ $job['attempts'] }} attempt(s)</p>
                    </div>
                </div>
            @empty
                <div class="p-2">
                    <x-empty icon="check-circle" title="Backlog is empty" message="No jobs are currently queued." />
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection
