@extends('layouts.admin')
@section('page-title', 'Guest support tickets')

@section('content')
<div class="space-y-5">
    <div>
        <h1 class="text-2xl font-bold text-strong">Guest support tickets</h1>
        <p class="text-sm text-muted">Requests from visitors without an account, via the Contact and Guest Support forms.</p>
    </div>

    <div class="flex gap-2">
        @foreach (['open'=>'Open','in_progress'=>'In progress','resolved'=>'Resolved','closed'=>'Closed'] as $k=>$v)
            <a href="{{ route('admin.support-tickets.index', ['status'=>$k]) }}" class="pill {{ $status===$k ? 'bg-brand-600/40 text-strong ring-1 ring-white/10' : 'surface text-body ring-1 ring-white/10' }}">{{ $v }}</a>
        @endforeach
    </div>

    <x-glass-card padding="p-0">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="border-b border-app text-muted"><tr><th class="px-5 py-3">Reference</th><th class="px-5 py-3">From</th><th class="px-5 py-3">Subject</th><th class="px-5 py-3">Category</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">When</th><th class="px-5 py-3"></th></tr></thead>
            <tbody class="divide-y divide-app">
                @forelse ($tickets as $ticket)
                    <tr>
                        <td class="px-5 py-3 font-mono text-xs text-faint">{{ $ticket->reference }}</td>
                        <td class="px-5 py-3 text-body">{{ $ticket->name }}<br><span class="text-xs text-faint">{{ $ticket->email }}</span></td>
                        <td class="px-5 py-3 text-body">{{ $ticket->subject }}</td>
                        <td class="px-5 py-3"><span class="pill surface text-body ring-1 ring-white/10">{{ ucfirst($ticket->category) }}</span></td>
                        <td class="px-5 py-3"><x-status-badge :status="$ticket->status" /></td>
                        <td class="px-5 py-3 text-muted">{{ $ticket->created_at->diffForHumans() }}</td>
                        <td class="px-5 py-3 text-right"><a href="{{ route('admin.support-tickets.show', $ticket) }}" class="text-brand-600">Open →</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-faint">No {{ str_replace('_',' ',$status) }} tickets.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </x-glass-card>
    <div>{{ $tickets->links() }}</div>
</div>
@endsection
