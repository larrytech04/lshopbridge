@extends('layouts.admin')
@section('page-title', 'Audit logs')

@section('content')
<div class="space-y-5">
    <form method="GET" class="glass flex gap-3 rounded-2xl p-4">
        <input name="action" value="{{ $filters['action'] ?? '' }}" class="field max-w-xs" placeholder="Filter by action…">
        <button class="btn btn-primary"><x-icon name="search" class="h-4 w-4" /> Filter</button>
    </form>
    <x-glass-card padding="p-0">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="border-b border-app text-muted"><tr><th class="px-5 py-3">Action</th><th class="px-5 py-3">Description</th><th class="px-5 py-3">Actor</th><th class="px-5 py-3">IP</th><th class="px-5 py-3">When</th></tr></thead>
            <tbody class="divide-y divide-app">
                @forelse ($logs as $log)
                    <tr>
                        <td class="px-5 py-3"><span class="pill surface text-body ring-1 ring-white/10">{{ $log->action }}</span></td>
                        <td class="px-5 py-3 text-body">{{ $log->description }}</td>
                        <td class="px-5 py-3 text-muted">{{ $log->user->name ?? 'System' }}</td>
                        <td class="px-5 py-3 font-mono text-xs text-faint">{{ $log->ip }}</td>
                        <td class="px-5 py-3 text-muted">{{ $log->created_at->format('M j, H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-faint">No audit entries yet.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </x-glass-card>
    <div>{{ $logs->links() }}</div>
</div>
@endsection
