@extends('layouts.admin')
@section('page-title', 'Audit entry')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <a href="{{ route('admin.audit.index') }}" class="text-sm text-brand-600 hover:text-brand-700">← Audit logs</a>

    <x-glass-card>
        <div class="flex items-start justify-between gap-3">
            <div>
                <span class="pill surface text-body ring-1 ring-white/10">{{ $log->action }}</span>
                <p class="mt-2 font-semibold text-strong">{{ $log->description }}</p>
            </div>
            <p class="shrink-0 text-xs text-faint">{{ $log->created_at->format('M j, Y H:i:s') }}</p>
        </div>
        <dl class="mt-4 grid gap-3 sm:grid-cols-2 text-sm">
            <div><dt class="text-faint">Actor</dt><dd class="text-body">{{ $log->user->name ?? 'System' }}</dd></div>
            <div><dt class="text-faint">IP address</dt><dd class="font-mono text-xs text-body">{{ $log->ip }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-faint">User agent</dt><dd class="truncate text-xs text-body">{{ $log->user_agent }}</dd></div>
        </dl>
    </x-glass-card>

    @php $properties = $log->properties ?? []; @endphp

    @if (array_key_exists('before', $properties) || array_key_exists('after', $properties))
        @php
            $before = $properties['before'] ?? [];
            $after = $properties['after'] ?? [];
            $keys = collect(array_keys($before))->merge(array_keys($after))->unique()->sort()->values();
        @endphp
        <x-glass-card>
            <h3 class="font-semibold text-strong">Changes</h3>
            <div class="mt-3 overflow-x-auto"><table class="w-full text-left text-sm">
                <thead class="border-b border-app text-muted"><tr><th class="py-2 pr-4">Field</th><th class="py-2 pr-4">Before</th><th class="py-2">After</th></tr></thead>
                <tbody class="divide-y divide-app">
                    @foreach ($keys as $key)
                        @php
                            $b = $before[$key] ?? null;
                            $a = $after[$key] ?? null;
                            $changed = $b !== $a;
                        @endphp
                        <tr>
                            <td class="py-2 pr-4 font-mono text-xs text-faint">{{ $key }}</td>
                            <td class="py-2 pr-4 text-xs {{ $changed ? 'text-rose-600' : 'text-muted' }}">{{ is_scalar($b) ? $b : json_encode($b) }}</td>
                            <td class="py-2 text-xs {{ $changed ? 'text-emerald-600' : 'text-muted' }}">{{ is_scalar($a) ? $a : json_encode($a) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
        </x-glass-card>
    @elseif (! empty($properties))
        <x-glass-card>
            <h3 class="font-semibold text-strong">Context</h3>
            <dl class="mt-3 grid gap-2 text-sm">
                @foreach ($properties as $key => $value)
                    <div class="flex items-start justify-between gap-3 border-b border-app pb-2 last:border-0">
                        <dt class="font-mono text-xs text-faint">{{ $key }}</dt>
                        <dd class="text-right text-xs text-body">{{ is_scalar($value) ? $value : json_encode($value) }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-glass-card>
    @endif
</div>
@endsection
