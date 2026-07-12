@extends('layouts.admin')
@section('page-title', 'Disputes')

@section('content')
<div class="space-y-5">
    <div class="flex gap-2">
        <a href="{{ route('admin.disputes.index') }}" class="pill {{ !$status ? 'bg-brand-600/40 text-strong ring-1 ring-white/10' : 'surface text-body ring-1 ring-white/10' }}">All</a>
        @foreach (['open','in_progress','resolved','closed'] as $s)
            <a href="{{ route('admin.disputes.index', ['status'=>$s]) }}" class="pill {{ $status===$s ? 'bg-brand-600/40 text-strong ring-1 ring-white/10' : 'surface text-body ring-1 ring-white/10' }}">{{ ucfirst(str_replace('_',' ',$s)) }}</a>
        @endforeach
    </div>
    <x-glass-card padding="p-0">
        <div class="divide-y divide-app">
            @forelse ($disputes as $d)
                <a href="{{ route('admin.disputes.show', $d) }}" class="flex items-center justify-between px-5 py-4 hover:bg-white/[0.02]">
                    <div><p class="font-medium text-strong">{{ $d->subject }}</p><p class="text-xs text-faint">{{ $d->reference }} · {{ $d->user->name }} · {{ ucfirst($d->category) }}</p></div>
                    <x-status-badge :status="$d->status" />
                </a>
            @empty
                <p class="px-5 py-10 text-center text-faint">No disputes.</p>
            @endforelse
        </div>
    </x-glass-card>
    <div>{{ $disputes->links() }}</div>
</div>
@endsection
