@extends('layouts.admin')
@section('page-title', $agent->business_name)

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('admin.agents.index') }}" class="text-sm text-brand-600 hover:text-brand-700">← All agents</a>

    <x-glass-card>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                @if ($agent->logo_path)
                    <img src="{{ asset('storage/'.$agent->logo_path) }}" class="h-12 w-12 rounded-full object-cover" alt="">
                @else
                    <span class="grid h-12 w-12 place-items-center rounded-full bg-brand-600 text-sm font-bold text-white">{{ strtoupper(substr($agent->business_name, 0, 2)) }}</span>
                @endif
                <div>
                    <h2 class="flex items-center gap-1.5 text-xl font-bold text-strong">{{ $agent->business_name }} @if ($agent->is_featured)<span class="pill bg-purple-500/15 text-purple-600 text-[10px]">Featured</span>@endif</h2>
                    <p class="text-sm text-muted">{{ $agent->agent_type->label() }} · {{ $agent->user->name }} · {{ $agent->user->email }}</p>
                    <p class="text-xs text-faint">{{ $agent->warehouseCountry?->name }} · {{ $agent->warehouse_city }} · Reg #{{ $agent->registration_number ?? '-' }}</p>
                </div>
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
        @if ($agent->status->value === 'pending')
            <form method="POST" action="{{ route('admin.agents.approve', $agent) }}">@csrf<button class="btn btn-success"><x-icon name="check" class="h-4 w-4" /> Approve &amp; list</button></form>
            <form method="POST" action="{{ route('admin.agents.reject', $agent) }}" class="flex gap-2">@csrf
                <input name="reason" class="field max-w-xs" placeholder="Rejection reason" required>
                <button class="btn btn-danger">Reject</button>
            </form>
        @endif
        @if ($agent->status->value !== 'suspended')
            <form method="POST" action="{{ route('admin.agents.suspend', $agent) }}" class="flex gap-2" onsubmit="return confirm('Suspend this agent?')">@csrf
                <input name="reason" class="field max-w-xs" placeholder="Suspension reason" required>
                <button class="btn btn-ghost text-amber-600"><x-icon name="ban" class="h-4 w-4" /> Suspend</button>
            </form>
        @else
            <form method="POST" action="{{ route('admin.agents.restore', $agent) }}" onsubmit="return confirm('Restore this agent?')">@csrf<button class="btn btn-success"><x-icon name="refresh" class="h-4 w-4" /> Restore</button></form>
        @endif
        @if ($agent->status->value === 'approved')
            <form method="POST" action="{{ route('admin.agents.feature', $agent) }}">@csrf<button class="btn btn-ghost"><x-icon name="star" class="h-4 w-4" /> {{ $agent->is_featured ? 'Unfeature' : 'Feature' }}</button></form>
        @endif
    </div>

    @if ($agent->shippingRates->isNotEmpty())
        <x-glass-card padding="p-0">
            <h3 class="p-5 font-semibold text-strong">Shipping rates</h3>
            <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-y border-app text-muted"><tr><th class="px-5 py-3">Method</th><th class="px-5 py-3">Destination</th><th class="px-5 py-3">Price</th></tr></thead>
                <tbody class="divide-y divide-app">@foreach ($agent->shippingRates as $r)<tr><td class="px-5 py-3 text-strong">{{ ucfirst($r->method) }}</td><td class="px-5 py-3 text-body">{{ $r->destinationCountry?->name ?? 'Various' }}</td><td class="px-5 py-3 text-body">@if($r->price_per_kg){{ money($r->price_per_kg,$r->currency) }}/kg @endif</td></tr>@endforeach</tbody>
            </table></div>
        </x-glass-card>
    @endif

    <x-glass-card id="reviews">
        <h3 class="font-semibold text-strong">Reviews ({{ $agent->reviews_count }}, ★ {{ number_format((float) $agent->rating, 1) }})</h3>
        <div class="mt-3 space-y-2">
            @forelse ($agent->reviews as $r)
                <div class="rounded-lg surface-2 p-3 text-sm">
                    <p class="text-body"><span class="font-semibold">{{ $r->user->name ?? 'Unknown' }}</span> · {{ str_repeat('★', $r->rating) }} · <span class="text-faint">{{ $r->created_at->format('M j, Y') }}</span> · <x-status-badge :status="$r->status" class="text-[10px]" /></p>
                    @if ($r->comment)<p class="text-muted">{{ $r->comment }}</p>@endif
                </div>
            @empty
                <p class="text-sm text-faint">No reviews yet.</p>
            @endforelse
        </div>
    </x-glass-card>

    <x-glass-card>
        <h3 class="font-semibold text-strong">Internal notes</h3>
        <p class="text-xs text-faint">Private — never shown to the agent.</p>
        <form method="POST" action="{{ route('admin.agents.notes', $agent) }}" class="mt-2">
            @csrf
            <textarea name="admin_notes" rows="3" class="field">{{ $agent->admin_notes }}</textarea>
            <button class="btn btn-ghost mt-2 text-sm">Save notes</button>
        </form>
    </x-glass-card>

    @include('admin.partials.seo-fields', ['model' => $agent, 'type' => 'agent'])
</div>
@endsection
