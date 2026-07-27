@extends('layouts.admin')
@section('page-title', 'Cache management')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-strong">Cache management</h1>
        <p class="text-sm text-muted">Active driver: <span class="font-mono text-body">{{ $driver }}</span>. These actions run the real Laravel cache commands, nothing is simulated.</p>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($actions as $key => $action)
            <div class="card-solid flex items-center justify-between gap-3 rounded-2xl border border-app p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-500/15 text-brand-600">
                        <x-icon name="refresh" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="font-semibold text-strong">{{ $action['label'] }}</p>
                        <p class="font-mono text-xs text-faint">{{ $action['command'] ?? 'SettingsService::flush()' }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.cache.clear', $key) }}" onsubmit="return confirm('Clear {{ $action['label'] }}?')">
                    @csrf
                    <button class="btn btn-ghost text-xs">Clear</button>
                </form>
            </div>
        @endforeach
    </div>
</div>
@endsection
