@extends('layouts.admin')
@section('page-title', 'Shop orders')

@section('content')
<div class="space-y-5">
    <form method="GET" class="glass flex flex-wrap gap-3 rounded-2xl p-4">
        <input name="q" value="{{ $filters['q'] ?? '' }}" class="field max-w-xs" placeholder="Reference…">
        <select name="status" class="field max-w-[180px]">
            <option value="">All statuses</option>
            @foreach (['pending','paid','fulfilled','failed','refunded'] as $s)<option value="{{ $s }}" @selected(($filters['status'] ?? '')===$s)>{{ ucfirst($s) }}</option>@endforeach
        </select>
        <button class="btn btn-primary"><x-icon name="filter" class="h-4 w-4" /> Filter</button>
    </form>

    <x-glass-card padding="p-0">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="border-b border-app text-muted"><tr><th class="px-5 py-3">Reference</th><th class="px-5 py-3">Customer</th><th class="px-5 py-3">Items</th><th class="px-5 py-3">Total</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr></thead>
            <tbody>
                @forelse ($orders as $o)
                    <tr class="border-t border-app">
                        <td class="px-5 py-3 font-mono text-xs text-muted">{{ $o->reference }}</td>
                        <td class="px-5 py-3 text-body">{{ $o->user->name }}</td>
                        <td class="px-5 py-3 text-body">{{ $o->items_count }}</td>
                        <td class="px-5 py-3 font-semibold text-strong">{{ money($o->total, $o->currency) }}</td>
                        <td class="px-5 py-3"><x-status-badge :status="$o->status" /></td>
                        <td class="px-5 py-3 text-right"><a href="{{ route('admin.shop.orders.show', $o) }}" class="text-brand-400">Open →</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-muted">No orders.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </x-glass-card>
    <div>{{ $orders->links() }}</div>
</div>
@endsection
