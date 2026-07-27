@extends('layouts.admin')
@section('page-title', 'Scheduler & cron')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-strong">Scheduler & cron</h1>
        <p class="text-sm text-muted">The real schedule defined in routes/console.php, and the actual run history recorded each time it fires.</p>
    </div>

    <section>
        <h3 class="mb-3 font-semibold text-strong">Scheduled commands</h3>
        <div class="grid gap-4 lg:grid-cols-2">
            @foreach ($commands as $cmd)
                <div class="card-solid rounded-2xl border border-app p-5 shadow-sm">
                    <div class="flex items-start gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-500/15 text-brand-600">
                            <x-icon name="clock" class="h-5 w-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-mono text-sm font-semibold text-strong">{{ $cmd['command'] }}</p>
                            <p class="font-mono text-xs text-faint">{{ $cmd['expression'] }}</p>
                        </div>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 border-t border-app pt-4 text-sm">
                        <div><dt class="text-faint">Next run</dt><dd class="text-body">{{ $cmd['next_run']->format('M j, H:i') }}</dd></div>
                        <div>
                            <dt class="text-faint">Last run</dt>
                            <dd class="text-body">
                                @if ($cmd['last_run'])
                                    {{ $cmd['last_run']->started_at->diffForHumans() }}
                                    @if ($cmd['last_run']->successful === true)
                                        <span class="pill bg-emerald-500/15 text-emerald-600 text-[10px] font-bold uppercase">OK</span>
                                    @elseif ($cmd['last_run']->successful === false)
                                        <span class="pill bg-rose-500/15 text-rose-600 text-[10px] font-bold uppercase">Failed</span>
                                    @endif
                                @else
                                    <span class="text-faint">No recorded runs yet</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            @endforeach
        </div>
    </section>

    <section>
        <h3 class="mb-3 font-semibold text-strong">Run history</h3>
        <div class="card-solid overflow-hidden rounded-2xl border border-app shadow-sm">
            @forelse ($history as $run)
                <div class="flex items-center gap-3 border-b border-app px-5 py-3 last:border-0">
                    @php
                        $color = $run->successful === true ? 'emerald' : ($run->successful === false ? 'rose' : 'slate');
                    @endphp
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-{{ $color }}-500/15 text-{{ $color }}-600">
                        <x-icon name="{{ $run->successful === false ? 'ban' : 'check-circle' }}" class="h-4 w-4" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-mono text-sm text-strong">{{ $run->command }}</p>
                        <p class="text-xs text-faint">Started {{ $run->started_at->format('M j, Y H:i:s') }}@if ($run->finished_at) · finished {{ $run->finished_at->format('H:i:s') }}@endif</p>
                    </div>
                </div>
            @empty
                <div class="p-2">
                    <x-empty icon="clock" title="No scheduled runs recorded yet" message="This table only fills in once a system cron actually invokes schedule:run in this environment. An empty history is an honest signal that hasn't happened yet, not a bug." />
                </div>
            @endforelse
        </div>
        @if ($history->hasPages())
            <div class="mt-3">{{ $history->links() }}</div>
        @endif
    </section>
</div>
@endsection
