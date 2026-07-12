@extends('layouts.admin')
@section('page-title', 'Deposits')

@section('content')
<div class="space-y-5">
    <form method="GET" class="glass flex flex-wrap gap-3 rounded-2xl p-4">
        <input name="q" value="{{ $filters['q'] ?? '' }}" class="field max-w-xs" placeholder="Reference…">
        <select name="status" class="field max-w-[180px]">
            <option value="">All statuses</option>
            @foreach (['pending','under_review','processing','confirmed','rejected','failed'] as $s)<option value="{{ $s }}" @selected(($filters['status'] ?? '')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach
        </select>
        <button class="btn btn-primary"><x-icon name="filter" class="h-4 w-4" /> Filter</button>
    </form>

    <x-glass-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-app text-muted"><tr><th class="px-5 py-3">Reference</th><th class="px-5 py-3">User</th><th class="px-5 py-3">Method</th><th class="px-5 py-3">Amount</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr></thead>
                <tbody class="divide-y divide-app">
                    @forelse ($deposits as $d)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-5 py-3 font-mono text-xs text-muted">{{ $d->reference }}</td>
                            <td class="px-5 py-3 text-body">{{ $d->user->name }}</td>
                            <td class="px-5 py-3 text-body">{{ $d->paymentMethod->name ?? '—' }} @if($d->is_automated)<span class="pill bg-emerald-500/15 text-emerald-300">auto</span>@endif</td>
                            <td class="px-5 py-3 font-semibold text-strong">{{ money($d->net_amount, $d->currency) }}</td>
                            <td class="px-5 py-3"><x-status-badge :status="$d->status" /></td>
                            <td class="px-5 py-3 text-right"><a href="{{ route('admin.deposits.show', $d) }}" class="text-brand-300 hover:text-brand-200">Open →</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-faint">No deposits.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-glass-card>
    <div>{{ $deposits->links() }}</div>
</div>
@endsection
