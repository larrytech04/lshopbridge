@extends('layouts.admin')
@section('page-title', 'Webhook logs')

@section('content')
<div class="space-y-5">
    <form method="GET" class="glass flex flex-wrap gap-3 rounded-2xl p-4">
        <input name="provider" value="{{ $filters['provider'] ?? '' }}" class="field max-w-[200px]" placeholder="Provider code">
        <select name="status" class="field max-w-[200px]">
            <option value="">All statuses</option>
            @foreach (['received','processed','duplicate','invalid_signature','failed','ignored'] as $s)<option value="{{ $s }}" @selected(($filters['status'] ?? '')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach
        </select>
        <button class="btn btn-primary"><x-icon name="filter" class="h-4 w-4" /> Filter</button>
    </form>

    <x-glass-card padding="p-0">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="border-b border-app text-muted"><tr><th class="px-5 py-3">Provider</th><th class="px-5 py-3">Event</th><th class="px-5 py-3">Reference</th><th class="px-5 py-3">Signature</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">When</th><th class="px-5 py-3"></th></tr></thead>
            <tbody class="divide-y divide-app">
                @forelse ($events as $e)
                    <tr class="hover:bg-white/[0.02]">
                        <td class="px-5 py-3 text-strong">{{ $e->provider_code }}</td>
                        <td class="px-5 py-3 text-body">{{ $e->event_type ?? '—' }}</td>
                        <td class="px-5 py-3 font-mono text-xs text-muted">{{ $e->reference ?? '—' }}</td>
                        <td class="px-5 py-3">@if($e->signature_valid)<span class="text-emerald-300">✓ valid</span>@else<span class="text-rose-300">✕</span>@endif</td>
                        <td class="px-5 py-3"><x-status-badge :status="$e->status" /></td>
                        <td class="px-5 py-3 text-muted">{{ $e->created_at->diffForHumans() }}</td>
                        <td class="px-5 py-3 text-right"><a href="{{ route('admin.webhooks.show', $e) }}" class="text-brand-300">Inspect →</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-faint">No webhook events yet.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </x-glass-card>
    <div>{{ $events->links() }}</div>
</div>
@endsection
