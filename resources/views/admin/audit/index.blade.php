@extends('layouts.admin')
@section('page-title', 'Audit logs')

@section('content')
<div class="space-y-5">
    <div>
        <h1 class="text-2xl font-bold text-strong">Audit logs</h1>
        <p class="text-sm text-muted">Tamper-evident record of every admin action, hash-chained so edits or deletions after the fact are detectable.</p>
    </div>

    <div class="flex flex-wrap items-start justify-between gap-3">
        <form method="GET" class="glass flex flex-wrap gap-3 rounded-2xl p-4">
            <input name="action" value="{{ $filters['action'] ?? '' }}" class="field max-w-[180px]" placeholder="Action contains…">
            <select name="module" class="field max-w-[160px]">
                <option value="">All modules</option>
                @foreach ($modules as $m)<option value="{{ $m }}" @selected(($filters['module'] ?? '')===$m)>{{ ucfirst(str_replace('_',' ',$m)) }}</option>@endforeach
            </select>
            <select name="actor" class="field max-w-[160px]">
                <option value="">All actors</option>
                @foreach ($actors as $a)<option value="{{ $a->id }}" @selected((string)($filters['actor'] ?? '')===(string)$a->id)>{{ $a->name }}</option>@endforeach
            </select>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="field max-w-[150px]">
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="field max-w-[150px]">
            <button class="btn btn-primary"><x-icon name="search" class="h-4 w-4" /> Filter</button>
            @if (array_filter($filters))
                <a href="{{ route('admin.audit.index') }}" class="btn btn-ghost">Clear</a>
            @endif
        </form>
        <form method="POST" action="{{ route('admin.audit.verify') }}">
            @csrf
            <button class="btn btn-ghost"><x-icon name="shield" class="h-4 w-4" /> Verify integrity</button>
        </form>
    </div>
    <x-glass-card padding="p-0">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="border-b border-app text-muted"><tr><th class="px-5 py-3">Action</th><th class="px-5 py-3">Description</th><th class="px-5 py-3">Actor</th><th class="px-5 py-3">IP</th><th class="px-5 py-3">When</th><th class="px-5 py-3"></th></tr></thead>
            <tbody class="divide-y divide-app">
                @forelse ($logs as $log)
                    <tr>
                        <td class="px-5 py-3"><span class="pill surface text-body ring-1 ring-white/10">{{ $log->action }}</span></td>
                        <td class="px-5 py-3 text-body">{{ $log->description }}</td>
                        <td class="px-5 py-3 text-muted">{{ $log->user->name ?? 'System' }}</td>
                        <td class="px-5 py-3 font-mono text-xs text-faint">{{ $log->ip }}</td>
                        <td class="px-5 py-3 text-muted">{{ $log->created_at->format('M j, H:i') }}</td>
                        <td class="px-5 py-3 text-right">
                            @if ($log->properties)
                                <a href="{{ route('admin.audit.show', $log) }}" class="text-brand-600">Details →</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-faint">No audit entries yet.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </x-glass-card>
    <div>{{ $logs->links() }}</div>
</div>
@endsection
