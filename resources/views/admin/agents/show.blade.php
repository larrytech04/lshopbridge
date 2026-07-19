@extends('layouts.admin')
@section('page-title', $agent->business_name)

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('admin.agents.index') }}" class="text-sm text-brand-300 hover:text-brand-200">← All agents</a>

    <x-glass-card>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-strong">{{ $agent->business_name }}</h2>
                <p class="text-sm text-muted">{{ $agent->user->name }} · {{ $agent->user->email }}</p>
                <p class="text-xs text-faint">{{ $agent->warehouseCountry?->name }} · {{ $agent->warehouse_city }} · Reg #{{ $agent->registration_number ?? '-' }}</p>
            </div>
            <x-status-badge :status="$agent->status" />
        </div>
        @if ($agent->bio)<p class="mt-4 text-body">{{ $agent->bio }}</p>@endif

        <div class="mt-4 flex flex-wrap gap-3">
            @if ($agent->business_doc_path)<a href="{{ route('files.show', ['kind'=>'agent-business','id'=>$agent->id]) }}" target="_blank" class="btn btn-ghost text-sm"><x-icon name="doc" class="h-4 w-4" /> Business doc</a>@endif
            @if ($agent->id_doc_path)<a href="{{ route('files.show', ['kind'=>'agent-id','id'=>$agent->id]) }}" target="_blank" class="btn btn-ghost text-sm"><x-icon name="doc" class="h-4 w-4" /> ID doc</a>@endif
        </div>
    </x-glass-card>

    <div class="flex flex-wrap gap-3">
        @if ($agent->status->value !== 'approved')
            <form method="POST" action="{{ route('admin.agents.approve', $agent) }}">@csrf<button class="btn btn-success"><x-icon name="check" class="h-4 w-4" /> Approve & list</button></form>
        @endif
        <form method="POST" action="{{ route('admin.agents.reject', $agent) }}" class="flex gap-2">@csrf
            <input name="reason" class="field max-w-xs" placeholder="Rejection reason" required>
            <button class="btn btn-danger">Reject</button>
        </form>
        <form method="POST" action="{{ route('admin.agents.feature', $agent) }}">@csrf<button class="btn btn-ghost"><x-icon name="star" class="h-4 w-4" /> {{ $agent->is_featured ? 'Unfeature' : 'Feature' }}</button></form>
    </div>

    @if ($agent->shippingRates->isNotEmpty())
        <x-glass-card padding="p-0">
            <h3 class="p-5 font-semibold text-strong">Shipping rates</h3>
            <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-y border-app text-muted"><tr><th class="px-5 py-3">Method</th><th class="px-5 py-3">Destination</th><th class="px-5 py-3">Price</th></tr></thead>
                <tbody class="divide-y divide-app">@foreach ($agent->shippingRates as $r)<tr><td class="px-5 py-3 text-strong">{{ ucfirst($r->method) }}</td><td class="px-5 py-3 text-body">{{ $r->destinationCountry?->name ?? 'Various' }}</td><td class="px-5 py-3 text-body">@if($r->price_per_kg){{ money($r->price_per_kg,$r->currency) }}/kg @endif</td></tr>@endforeach</tbody>
            </table></div>
        </x-glass-card>
    @endif
</div>
@endsection
