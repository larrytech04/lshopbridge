@extends('layouts.admin')
@section('page-title', 'Funding requests')

@section('content')
<div class="space-y-5">
    <form method="GET" class="glass flex flex-wrap gap-3 rounded-2xl p-4">
        <input name="q" value="{{ $filters['q'] ?? '' }}" class="field max-w-xs" placeholder="Reference…">
        <select name="status" class="field max-w-[200px]">
            <option value="">All statuses</option>
            @foreach (['payment_pending','payment_successful','funding_processing','funding_successful','manual_review','funding_failed','refunded'] as $s)
                <option value="{{ $s }}" @selected(($filters['status'] ?? '')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary"><x-icon name="filter" class="h-4 w-4" /> Filter</button>
    </form>

    <x-glass-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-app text-muted"><tr><th class="px-5 py-3">Reference</th><th class="px-5 py-3">User</th><th class="px-5 py-3">Recipient</th><th class="px-5 py-3">Delivered</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr></thead>
                <tbody class="divide-y divide-app">
                    @forelse ($requests as $f)
                        <tr class="hover:bg-white/[0.02] {{ $f->risk_flagged ? 'bg-amber-500/[0.04]' : '' }}">
                            <td class="px-5 py-3 font-mono text-xs text-muted">{{ $f->reference }}</td>
                            <td class="px-5 py-3 text-body">{{ $f->user->name }}</td>
                            <td class="px-5 py-3"><p class="text-body">{{ $f->recipient_account }}</p><p class="text-xs text-faint">{{ $f->app_type->label() }}</p></td>
                            <td class="px-5 py-3 font-semibold text-strong">{{ money($f->target_amount, $f->target_currency) }}</td>
                            <td class="px-5 py-3"><x-status-badge :status="$f->status" /></td>
                            <td class="px-5 py-3 text-right"><a href="{{ route('admin.funding.show', $f) }}" class="text-brand-300 hover:text-brand-200">Open →</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-faint">No funding requests.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-glass-card>
    <div>{{ $requests->links() }}</div>
</div>
@endsection
