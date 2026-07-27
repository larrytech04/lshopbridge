@extends('layouts.admin')
@section('page-title', 'System overview')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-strong">System overview</h1>
        <p class="text-sm text-muted">Real infrastructure signals only. Where nothing in the codebase measures a thing, it's labelled honestly instead of invented.</p>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-white" style="background: {{ $system['database'] ? '#10B981' : '#EF4444' }}"><x-icon name="check-circle" class="h-4 w-4" /></span>
                <p class="truncate text-[11px] text-faint">Database</p>
            </div>
            <p class="mt-2 text-lg font-bold text-strong">{{ $system['database'] ? 'Connected' : 'Unreachable' }}</p>
        </div>
        <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-white" style="background: {{ $system['cache'] ? '#10B981' : '#EF4444' }}"><x-icon name="refresh" class="h-4 w-4" /></span>
                <p class="truncate text-[11px] text-faint">Cache</p>
            </div>
            <p class="mt-2 text-lg font-bold text-strong">{{ $system['cache'] ? 'Working' : 'Unreachable' }}</p>
        </div>
        <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-white" style="background: #64748B"><x-icon name="clock" class="h-4 w-4" /></span>
                <p class="truncate text-[11px] text-faint">Queue backlog</p>
            </div>
            <p class="mt-2 text-lg font-bold text-strong">{{ $system['queueBacklog'] }}</p>
        </div>
        <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-white" style="background: {{ $system['failedJobs'] > 0 ? '#EF4444' : '#10B981' }}"><x-icon name="ban" class="h-4 w-4" /></span>
                <p class="truncate text-[11px] text-faint">Failed jobs</p>
            </div>
            <p class="mt-2 text-lg font-bold text-strong">{{ $system['failedJobs'] }}</p>
        </div>
        <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-white" style="background: #64748B"><x-icon name="doc" class="h-4 w-4" /></span>
                <p class="truncate text-[11px] text-faint">Disk used</p>
            </div>
            <p class="mt-2 text-lg font-bold text-strong">{{ $system['diskUsedPct'] !== null ? $system['diskUsedPct'].'%' : 'Data unavailable' }}</p>
        </div>
        <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-white" style="background: #64748B"><x-icon name="users" class="h-4 w-4" /></span>
                <p class="truncate text-[11px] text-faint">Active sessions</p>
            </div>
            <p class="mt-2 text-lg font-bold text-strong">{{ $system['activeSessions'] }}</p>
        </div>
        <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-white" style="background: {{ $providersTotal > 0 && $providersOnline === $providersTotal ? '#10B981' : '#F59E0B' }}"><x-icon name="webhook" class="h-4 w-4" /></span>
                <p class="truncate text-[11px] text-faint">API services online</p>
            </div>
            <p class="mt-2 text-lg font-bold text-strong">{{ $providersOnline }}/{{ $providersTotal }}</p>
        </div>
        <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-white" style="background: {{ $webhookFailures24h > 0 ? '#F59E0B' : '#10B981' }}"><x-icon name="alert" class="h-4 w-4" /></span>
                <p class="truncate text-[11px] text-faint">Webhook failures (24h)</p>
            </div>
            <p class="mt-2 text-lg font-bold text-strong">{{ $webhookFailures24h }}</p>
        </div>
    </div>

    <section>
        <h3 class="mb-3 font-semibold text-strong">Not tracked yet</h3>
        <div class="grid gap-3 lg:grid-cols-3">
            <div class="card-solid rounded-2xl border border-dashed border-app p-4">
                <p class="text-sm font-semibold text-strong">Email delivery rate</p>
                <p class="mt-1 text-xs text-faint">Not configured — no delivery-webhook or provider analytics integration exists yet.</p>
            </div>
            <div class="card-solid rounded-2xl border border-dashed border-app p-4">
                <p class="text-sm font-semibold text-strong">Latest successful backup</p>
                <p class="mt-1 text-xs text-faint">Not configured — no backup process exists in this deployment yet.</p>
            </div>
            <div class="card-solid rounded-2xl border border-dashed border-app p-4">
                <p class="text-sm font-semibold text-strong">Open system errors</p>
                <p class="mt-1 text-xs text-faint">Data unavailable — no error-tracking integration (e.g. Sentry) is wired up yet.</p>
            </div>
        </div>
    </section>

    <section>
        <h3 class="mb-3 font-semibold text-strong">Jump to</h3>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <a href="{{ route('admin.api-health.index') }}" class="card-solid rounded-2xl border border-app p-4 shadow-sm hover:surface-2"><p class="font-semibold text-strong">API & provider health</p><p class="text-xs text-faint">Per-provider webhook success rate</p></a>
            <a href="{{ route('admin.queues.index') }}" class="card-solid rounded-2xl border border-app p-4 shadow-sm hover:surface-2"><p class="font-semibold text-strong">Jobs & queues</p><p class="text-xs text-faint">Backlog and failed jobs</p></a>
            <a href="{{ route('admin.scheduler.index') }}" class="card-solid rounded-2xl border border-app p-4 shadow-sm hover:surface-2">
                <p class="font-semibold text-strong">Scheduler & cron</p>
                <p class="text-xs text-faint">
                    @if ($lastSchedulerRun)
                        Last run {{ $lastSchedulerRun->started_at->diffForHumans() }}
                    @else
                        No recorded runs yet
                    @endif
                </p>
            </a>
            <a href="{{ route('admin.storage.index') }}" class="card-solid rounded-2xl border border-app p-4 shadow-sm hover:surface-2"><p class="font-semibold text-strong">Storage & files</p><p class="text-xs text-faint">Disk usage by disk</p></a>
            <a href="{{ route('admin.cache.index') }}" class="card-solid rounded-2xl border border-app p-4 shadow-sm hover:surface-2"><p class="font-semibold text-strong">Cache management</p><p class="text-xs text-faint">Clear application caches</p></a>
            <a href="{{ route('admin.webhooks.index') }}" class="card-solid rounded-2xl border border-app p-4 shadow-sm hover:surface-2"><p class="font-semibold text-strong">Webhook monitor</p><p class="text-xs text-faint">Inbound webhook log & retry</p></a>
            <a href="{{ route('admin.audit.index') }}" class="card-solid rounded-2xl border border-app p-4 shadow-sm hover:surface-2"><p class="font-semibold text-strong">Audit logs</p><p class="text-xs text-faint">Tamper-evident admin activity</p></a>
            <a href="{{ route('admin.system-info.index') }}" class="card-solid rounded-2xl border border-app p-4 shadow-sm hover:surface-2"><p class="font-semibold text-strong">System information</p><p class="text-xs text-faint">Versions, drivers, environment</p></a>
        </div>
    </section>
</div>
@endsection
