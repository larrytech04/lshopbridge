
@extends('layouts.admin')
@section('page-title', 'Agents')

@section('content')
<div class="space-y-5">
    <div class="flex gap-2">
        <a href="{{ route('admin.agents.index') }}" class="pill {{ !$status ? 'bg-brand-600/40 text-strong ring-1 ring-white/10' : 'surface text-body ring-1 ring-white/10' }}">All</a>
        @foreach (['pending','approved','rejected','suspended'] as $s)
            <a href="{{ route('admin.agents.index', ['status'=>$s]) }}" class="pill {{ $status===$s ? 'bg-brand-600/40 text-strong ring-1 ring-white/10' : 'surface text-body ring-1 ring-white/10' }}">{{ ucfirst($s) }}</a>
        @endforeach
    </div>

    <x-glass-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-app text-muted"><tr><th class="px-5 py-3">Business</th><th class="px-5 py-3">Owner</th><th class="px-5 py-3">Rating</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr></thead>
                <tbody class="divide-y divide-app">
                    @forelse ($agents as $agent)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-5 py-3"><p class="text-strong">{{ $agent->business_name }}</p><p class="text-xs text-faint">{{ $agent->warehouseCountry?->name }}</p></td>
                            <td class="px-5 py-3 text-body">{{ $agent->user->name }}</td>
                            <td class="px-5 py-3 text-body">★ {{ number_format((float)$agent->rating,1) }} ({{ $agent->reviews_count }})</td>
                            <td class="px-5 py-3"><x-status-badge :status="$agent->status" /> @if($agent->is_featured)<span class="pill bg-accent-500/15 text-accent-300">Featured</span>@endif</td>
                            <td class="px-5 py-3 text-right"><a href="{{ route('admin.agents.show', $agent) }}" class="text-brand-300 hover:text-brand-200">Manage →</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-faint">No agents.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-glass-card>
    <div>{{ $agents->links() }}</div>
</div>
@endsection
