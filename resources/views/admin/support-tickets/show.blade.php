@extends('layouts.admin')
@section('page-title', 'Guest support ticket')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <a href="{{ route('admin.support-tickets.index') }}" class="text-sm text-brand-600 hover:text-brand-700">← Guest support tickets</a>

    <x-glass-card>
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="font-mono text-xs text-faint">{{ $ticket->reference }}</p>
                <p class="mt-1 font-semibold text-strong">{{ $ticket->subject }}</p>
            </div>
            <x-status-badge :status="$ticket->status" />
        </div>
        <dl class="mt-4 grid gap-3 sm:grid-cols-2 text-sm">
            <div><dt class="text-faint">From</dt><dd class="text-body">{{ $ticket->name }} ({{ $ticket->email }})</dd></div>
            <div><dt class="text-faint">Phone</dt><dd class="text-body">{{ $ticket->phone ?: '-' }}</dd></div>
            <div><dt class="text-faint">Category</dt><dd class="text-body">{{ ucfirst($ticket->category) }}</dd></div>
            <div><dt class="text-faint">Assigned to</dt><dd class="text-body">{{ $ticket->assignee->name ?? 'Unassigned' }}</dd></div>
        </dl>
        <div class="mt-4 rounded-xl border border-app surface p-4 text-sm text-body">{{ $ticket->description }}</div>
        @if ($ticket->attachment_path)
            <a href="{{ route('files.show', ['kind' => 'guest-support-attachment', 'id' => $ticket->id]) }}" target="_blank" class="mt-3 inline-flex items-center gap-1.5 text-sm text-brand-600 hover:text-brand-700">
                <x-icon name="doc" class="h-4 w-4" /> {{ __('View attachment') }}
            </a>
        @endif

        @if ($ticket->status !== 'resolved' && $ticket->status !== 'closed')
            <div class="mt-4 flex flex-wrap gap-2 border-t border-app pt-4">
                @if (! $ticket->assigned_to)
                    <form method="POST" action="{{ route('admin.support-tickets.assign', $ticket) }}">@csrf<button class="btn btn-ghost text-xs">Assign to me</button></form>
                @endif
                <form method="POST" action="{{ route('admin.support-tickets.convert', $ticket) }}" onsubmit="return confirm('Convert to a tracked dispute? The person must already have an account with this email.')">@csrf<button class="btn btn-ghost text-xs">Convert to dispute</button></form>
            </div>
            <form method="POST" action="{{ route('admin.support-tickets.resolve', $ticket) }}" class="mt-4 space-y-2 border-t border-app pt-4">
                @csrf
                <label class="label">Resolution notes</label>
                <textarea name="resolution" rows="3" required class="field"></textarea>
                <button class="btn btn-primary text-xs">Mark resolved</button>
            </form>
        @elseif ($ticket->resolution)
            <div class="mt-4 rounded-xl border border-emerald-400/30 bg-emerald-500/10 p-4 text-sm text-emerald-700">{{ $ticket->resolution }}</div>
        @endif

        @if ($ticket->convertedToDispute)
            <p class="mt-3 text-xs text-faint">Converted to <a href="{{ route('admin.disputes.show', $ticket->convertedToDispute) }}" class="text-brand-600">dispute {{ $ticket->convertedToDispute->reference }}</a>.</p>
        @endif
    </x-glass-card>
</div>
@endsection
