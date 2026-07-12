@extends('layouts.admin')
@section('page-title', 'Risk & fraud')

@section('content')
<div class="space-y-8">
    <section>
        <div class="mb-3 flex gap-2">
            @foreach (['open'=>'Open','reviewed'=>'Reviewed','dismissed'=>'Dismissed'] as $k=>$v)
                <a href="{{ route('admin.risk.index', ['status'=>$k]) }}" class="pill {{ $status===$k ? 'bg-brand-600/40 text-strong ring-1 ring-white/10' : 'surface text-body ring-1 ring-white/10' }}">{{ $v }}</a>
            @endforeach
        </div>
        <x-glass-card padding="p-0">
            <div class="divide-y divide-app">
                @forelse ($flags as $flag)
                    <div class="flex items-start justify-between gap-3 px-5 py-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="pill {{ $flag->severity==='high' ? 'bg-rose-500/15 text-rose-300' : ($flag->severity==='medium' ? 'bg-amber-500/15 text-amber-300' : 'bg-slate-400/15 text-body') }}">{{ ucfirst($flag->severity) }}</span>
                                <span class="font-medium text-strong">{{ $flag->rule_code }}</span>
                            </div>
                            <p class="mt-1 text-sm text-muted">{{ $flag->reason }}</p>
                            <p class="text-xs text-faint">{{ $flag->user->name ?? 'Unknown' }} · {{ $flag->created_at->diffForHumans() }}</p>
                        </div>
                        @if ($flag->status === 'open')
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('admin.risk.flags.resolve', $flag) }}">@csrf<input type="hidden" name="status" value="reviewed"><button class="btn btn-success text-xs py-1.5">Reviewed</button></form>
                                <form method="POST" action="{{ route('admin.risk.flags.resolve', $flag) }}">@csrf<input type="hidden" name="status" value="dismissed"><button class="btn btn-ghost text-xs py-1.5">Dismiss</button></form>
                            </div>
                        @else
                            <x-status-badge :status="$flag->status" />
                        @endif
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-faint">No risk flags.</p>
                @endforelse
            </div>
        </x-glass-card>
        <div class="mt-3">{{ $flags->links() }}</div>
    </section>

    <section>
        <h3 class="mb-3 font-semibold text-strong">Risk rules</h3>
        <div class="grid gap-4 lg:grid-cols-2">
            @foreach ($rules as $rule)
                <x-glass-card>
                    <form method="POST" action="{{ route('admin.risk.rules.update', $rule) }}" class="space-y-2">@csrf @method('PUT')
                        <div class="flex items-center justify-between"><span class="font-medium text-strong">{{ $rule->name }}</span><span class="text-xs text-faint">{{ $rule->code }}</span></div>
                        <p class="text-sm text-muted">{{ $rule->description }}</p>
                        <input type="hidden" name="code" value="{{ $rule->code }}"><input type="hidden" name="name" value="{{ $rule->name }}">
                        <div class="flex flex-wrap items-center gap-3">
                            <select name="action" class="field max-w-[130px]">@foreach (['flag','review','block'] as $a)<option value="{{ $a }}" @selected($rule->action===$a)>{{ ucfirst($a) }}</option>@endforeach</select>
                            <select name="severity" class="field max-w-[130px]">@foreach (['low','medium','high'] as $s)<option value="{{ $s }}" @selected($rule->severity===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
                            <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_active" value="1" @checked($rule->is_active) class="rounded surface-2"> Active</label>
                            <button class="btn btn-primary text-xs">Save</button>
                        </div>
                    </form>
                </x-glass-card>
            @endforeach
        </div>
    </section>
</div>
@endsection
