@extends('layouts.admin')
@section('page-title', 'Risk & fraud')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-strong">Risk & fraud</h1>
        <p class="text-sm text-muted">Review flagged activity and tune the automated rules that raise them.</p>
    </div>

    {{-- ============ STATS ============ --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-white" style="background: #F59E0B"><x-icon name="alert" class="h-4 w-4" /></span>
                <p class="truncate text-[11px] text-faint">Open flags</p>
            </div>
            <p class="mt-2 text-lg font-bold text-strong">{{ $stats['open'] }}</p>
        </div>
        <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-white" style="background: #EF4444"><x-icon name="ban" class="h-4 w-4" /></span>
                <p class="truncate text-[11px] text-faint">High severity, open</p>
            </div>
            <p class="mt-2 text-lg font-bold text-strong">{{ $stats['high_open'] }}</p>
        </div>
        <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-white" style="background: #10B981"><x-icon name="check-circle" class="h-4 w-4" /></span>
                <p class="truncate text-[11px] text-faint">Active rules</p>
            </div>
            <p class="mt-2 text-lg font-bold text-strong">{{ $stats['rules_active'] }}</p>
        </div>
        <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-white" style="background: #64748B"><x-icon name="gauge" class="h-4 w-4" /></span>
                <p class="truncate text-[11px] text-faint">Rules total</p>
            </div>
            <p class="mt-2 text-lg font-bold text-strong">{{ $stats['rules_total'] }}</p>
        </div>
    </div>

    {{-- ============ FLAGGED ACTIVITY ============ --}}
    <section>
        <h3 class="mb-3 font-semibold text-strong">Flagged activity</h3>
        <div class="mb-3 flex gap-2">
            @foreach (['open'=>'Open','reviewed'=>'Reviewed','dismissed'=>'Dismissed'] as $k=>$v)
                <a href="{{ route('admin.risk.index', ['status'=>$k]) }}" class="pill {{ $status===$k ? 'bg-brand-600/40 text-strong ring-1 ring-white/10' : 'surface text-body ring-1 ring-white/10' }}">{{ $v }}</a>
            @endforeach
        </div>
        <div class="card-solid overflow-hidden rounded-2xl border border-app shadow-sm">
            <div class="divide-y divide-app">
                @forelse ($flags as $flag)
                    @php
                        $severityColor = $flag->severity === 'high' ? 'rose' : ($flag->severity === 'medium' ? 'amber' : 'slate');
                    @endphp
                    <div class="flex items-start gap-3 px-5 py-4">
                        <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-{{ $severityColor }}-500/15 text-{{ $severityColor }}-600">
                            <x-icon name="alert" class="h-4.5 w-4.5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="pill bg-{{ $severityColor }}-500/15 text-{{ $severityColor }}-600 text-[10px] font-bold uppercase">{{ $flag->severity }}</span>
                                <span class="font-mono text-xs text-faint">{{ $flag->rule_code }}</span>
                            </div>
                            <p class="mt-1 text-sm text-body">{{ $flag->reason }}</p>
                            <p class="text-xs text-faint">{{ $flag->user->name ?? 'Unknown' }} · {{ $flag->created_at->diffForHumans() }}</p>
                        </div>
                        @if ($flag->status === 'open')
                            <div class="flex shrink-0 gap-2">
                                <form method="POST" action="{{ route('admin.risk.flags.resolve', $flag) }}">@csrf<input type="hidden" name="status" value="reviewed"><button class="btn btn-success py-1.5 text-xs">Reviewed</button></form>
                                <form method="POST" action="{{ route('admin.risk.flags.resolve', $flag) }}">@csrf<input type="hidden" name="status" value="dismissed"><button class="btn btn-ghost py-1.5 text-xs">Dismiss</button></form>
                            </div>
                        @else
                            <x-status-badge :status="$flag->status" class="shrink-0" />
                        @endif
                    </div>
                @empty
                    <div class="p-2">
                        <x-empty icon="check-circle" title="No {{ $status }} flags" message="Nothing here right now — flagged activity from the risk rules below will show up in this list." />
                    </div>
                @endforelse
            </div>
        </div>
        @if ($flags->hasPages())
            <div class="mt-3">{{ $flags->links() }}</div>
        @endif
    </section>

    {{-- ============ RISK RULES ============ --}}
    <section>
        <h3 class="mb-3 font-semibold text-strong">Risk rules</h3>
        <div class="grid gap-4 lg:grid-cols-2">
            @foreach ($rules as $rule)
                @php
                    $severityColor = $rule->severity === 'high' ? 'rose' : ($rule->severity === 'medium' ? 'amber' : 'slate');
                @endphp
                <div class="card-solid rounded-2xl border border-app p-5 shadow-sm">
                    <form method="POST" action="{{ route('admin.risk.rules.update', $rule) }}">@csrf @method('PUT')
                        <input type="hidden" name="code" value="{{ $rule->code }}"><input type="hidden" name="name" value="{{ $rule->name }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-{{ $severityColor }}-500/15 text-{{ $severityColor }}-600">
                                    <x-icon name="gauge" class="h-5 w-5" />
                                </span>
                                <div class="min-w-0">
                                    <p class="font-semibold text-strong">{{ $rule->name }}</p>
                                    <p class="truncate font-mono text-xs text-faint">{{ $rule->code }}</p>
                                </div>
                            </div>
                            <label class="flex shrink-0 items-center gap-1.5 text-xs text-muted">
                                <input type="checkbox" name="is_active" value="1" @checked($rule->is_active) class="rounded"> Active
                            </label>
                        </div>
                        <p class="mt-3 text-sm text-muted">{{ $rule->description }}</p>
                        <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-app pt-4">
                            <select name="action" class="field max-w-[130px]">@foreach (['flag','review','block'] as $a)<option value="{{ $a }}" @selected($rule->action===$a)>{{ ucfirst($a) }}</option>@endforeach</select>
                            <select name="severity" class="field max-w-[130px]">@foreach (['low','medium','high'] as $s)<option value="{{ $s }}" @selected($rule->severity===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
                            <button class="btn btn-primary ml-auto text-xs">Save</button>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>
    </section>
</div>
@endsection
