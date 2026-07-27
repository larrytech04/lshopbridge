@extends('layouts.admin')
@section('page-title', 'System information')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-strong">System information</h1>
        <p class="text-sm text-muted">Live environment and driver configuration, read directly from the running application.</p>
    </div>

    <section>
        <h3 class="mb-3 font-semibold text-strong">Application</h3>
        <div class="card-solid rounded-2xl border border-app p-5 shadow-sm">
            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div><dt class="text-xs text-faint">App version</dt><dd class="mt-0.5 font-mono text-sm text-strong">v{{ $appVersion }}</dd></div>
                <div><dt class="text-xs text-faint">Laravel</dt><dd class="mt-0.5 font-mono text-sm text-strong">{{ $laravelVersion }}</dd></div>
                <div><dt class="text-xs text-faint">PHP</dt><dd class="mt-0.5 font-mono text-sm text-strong">{{ $phpVersion }}</dd></div>
                <div><dt class="text-xs text-faint">Environment</dt><dd class="mt-0.5"><span class="pill {{ $environment === 'production' ? 'bg-emerald-500/15 text-emerald-600' : 'bg-amber-500/15 text-amber-600' }} text-xs font-semibold">{{ ucfirst($environment) }}</span></dd></div>
                <div><dt class="text-xs text-faint">Debug mode</dt><dd class="mt-0.5"><span class="pill {{ $debugMode ? 'bg-rose-500/15 text-rose-600' : 'bg-emerald-500/15 text-emerald-600' }} text-xs font-semibold">{{ $debugMode ? 'Enabled' : 'Disabled' }}</span></dd></div>
                <div><dt class="text-xs text-faint">OPcache</dt><dd class="mt-0.5"><span class="pill {{ $opcacheEnabled ? 'bg-emerald-500/15 text-emerald-600' : 'bg-slate-400/15 text-slate-600' }} text-xs font-semibold">{{ $opcacheEnabled ? 'Enabled' : 'Disabled' }}</span></dd></div>
                <div><dt class="text-xs text-faint">Timezone</dt><dd class="mt-0.5 font-mono text-sm text-strong">{{ $timezone }}</dd></div>
                <div><dt class="text-xs text-faint">Locale</dt><dd class="mt-0.5 font-mono text-sm text-strong">{{ $locale }}</dd></div>
                <div><dt class="text-xs text-faint">Server software</dt><dd class="mt-0.5 truncate font-mono text-sm text-strong">{{ $serverSoftware }}</dd></div>
            </dl>
        </div>
    </section>

    <section>
        <h3 class="mb-3 font-semibold text-strong">Drivers</h3>
        <div class="card-solid rounded-2xl border border-app p-5 shadow-sm">
            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div><dt class="text-xs text-faint">Database</dt><dd class="mt-0.5 font-mono text-sm text-strong">{{ $dbConnection }} ({{ $dbDriver }})</dd></div>
                <div><dt class="text-xs text-faint">Cache</dt><dd class="mt-0.5 font-mono text-sm text-strong">{{ $cacheDriver }}</dd></div>
                <div><dt class="text-xs text-faint">Queue</dt><dd class="mt-0.5 font-mono text-sm text-strong">{{ $queueDriver }}</dd></div>
                <div><dt class="text-xs text-faint">Session</dt><dd class="mt-0.5 font-mono text-sm text-strong">{{ $sessionDriver }} ({{ $sessionLifetime }} min)</dd></div>
                <div><dt class="text-xs text-faint">Mail</dt><dd class="mt-0.5 font-mono text-sm text-strong">{{ $mailDriver }}</dd></div>
                <div><dt class="text-xs text-faint">Filesystem</dt><dd class="mt-0.5 font-mono text-sm text-strong">{{ $filesystemDriver }}</dd></div>
            </dl>
        </div>
    </section>
</div>
@endsection
