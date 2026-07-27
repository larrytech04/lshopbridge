@extends('layouts.app')
@section('page-title', $agent->business_name)

@section('content')
<div class="space-y-6">
    <a href="{{ route('marketplace.index') }}" class="text-sm text-brand-500 hover:text-brand-600">← {{ __('All agents') }}</a>

    <div class="rounded-3xl border border-app p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <span class="relative grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-2xl bg-brand-600 text-xl font-bold text-white">
                @if ($agent->logo_path)<img src="{{ Storage::url($agent->logo_path) }}" class="h-full w-full object-cover" alt="">@else{{ strtoupper(substr($agent->business_name,0,2)) }}@endif
                <span class="absolute -bottom-0.5 -right-0.5 h-4 w-4 rounded-full border-2 {{ $agent->isOnline() ? 'bg-emerald-500' : 'bg-slate-400' }}" style="border-color: var(--bg);"></span>
            </span>
            <div class="flex-1">
                <h2 class="text-xl font-bold text-strong">{{ $agent->business_name }} @if ($agent->verified_at)<x-verified-tick class="ml-1 inline h-4 w-4" />@endif</h2>
                <p class="text-sm text-muted">{{ $agent->warehouseCountry?->name ?? 'China' }} · {{ $agent->warehouse_city }}</p>
                <p class="mt-0.5 flex items-center gap-1.5 text-xs font-medium {{ $agent->isOnline() ? 'text-emerald-600' : 'text-faint' }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $agent->isOnline() ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                    @if ($agent->isOnline())
                        {{ __('Online now') }}
                    @elseif ($agent->user?->last_seen_at)
                        {{ __('Last seen :time', ['time' => $agent->user->last_seen_at->diffForHumans()]) }}
                    @else
                        {{ __('Offline') }}
                    @endif
                </p>
                <div class="mt-2 flex items-center gap-1 text-amber-400"><x-icon name="star" class="h-4 w-4 fill-current" /> <span class="font-semibold text-strong">{{ number_format((float)$agent->rating,1) }}</span> <span class="text-xs text-faint">({{ $agent->reviews_count }})</span></div>
            </div>
        </div>
        @if ($agent->bio)<p class="mt-4 text-body">{{ $agent->bio }}</p>@endif
    </div>

    @if ($lead)
        {{-- Ongoing engagement: direct chat with the agent --}}
        <div class="rounded-3xl border border-app p-6"
             x-data="leadChat({{ $lead->id }}, '{{ route('marketplace.leads.poll', $lead) }}', @js(['messages' => $lead->messages->map(fn ($m) => ['is_agent' => $m->is_agent, 'name' => $m->user->name, 'message' => $m->message, 'time' => $m->created_at->diffForHumans()])]))">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="font-semibold text-strong">{{ __('Chat with :agent', ['agent' => $agent->business_name]) }}</h3>
                <x-status-badge :status="$lead->status" />
            </div>

            <div x-ref="thread" class="mt-4 max-h-96 space-y-3 overflow-y-auto">
                <template x-for="(m, i) in messages" :key="i">
                    <div class="flex" :class="m.is_agent ? 'justify-start' : 'justify-end'">
                        <div class="max-w-[80%] rounded-2xl px-4 py-2.5" :class="m.is_agent ? 'surface-2 text-body' : 'bg-brand-600 text-white'">
                            <p class="text-xs font-semibold" :class="m.is_agent ? 'text-brand-500' : 'text-white/80'" x-text="m.is_agent ? @js($agent->business_name) : m.name"></p>
                            <p class="mt-1 whitespace-pre-line text-sm" x-text="m.message"></p>
                            <p class="mt-1 text-[10px] opacity-70" x-text="m.time"></p>
                        </div>
                    </div>
                </template>
                <p x-show="messages.length === 0" class="py-4 text-center text-sm text-faint">{{ __('No messages yet. Say hello!') }}</p>
            </div>

            <form method="POST" action="{{ route('marketplace.leads.message', $lead) }}" class="mt-4 flex items-center gap-2 border-t border-app pt-4">
                @csrf
                <input type="text" name="message" required maxlength="1500" class="field flex-1" placeholder="{{ __('Type a message…') }}" autocomplete="off">
                <button class="btn btn-primary shrink-0">{{ __('Send') }}</button>
            </form>

            @if (! $lead->customer_confirmed_at && $lead->status !== 'closed')
                <form method="POST" action="{{ route('marketplace.leads.complete', $lead) }}" class="mt-3"
                      onsubmit="return confirm('{{ __('Confirm you received your delivery from this agent?') }}')">
                    @csrf
                    <button class="btn btn-ghost w-full text-emerald-600"><x-icon name="check-circle" class="h-4 w-4" /> {{ __('Mark delivery as completed') }}</button>
                </form>
            @elseif ($lead->customer_confirmed_at)
                <p class="mt-3 flex items-center justify-center gap-1.5 text-center text-sm font-semibold text-emerald-600"><x-icon name="check-circle" class="h-4 w-4" /> {{ __('You confirmed this delivery is complete.') }}</p>
            @endif
        </div>

        {{-- Review --}}
        <div class="rounded-3xl border border-app p-6" x-data="{ rating: 5 }">
            <h3 class="font-semibold text-strong">{{ __('Leave a review') }}</h3>
            <form method="POST" action="{{ route('marketplace.review', $agent) }}" class="mt-4 space-y-3">
                @csrf
                <div class="flex gap-1">
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button" @click="rating = {{ $i }}" class="text-2xl" :class="rating >= {{ $i }} ? 'text-amber-400' : 'text-faint'">★</button>
                    @endfor
                    <input type="hidden" name="rating" :value="rating">
                </div>
                <textarea name="comment" rows="3" class="field" placeholder="{{ __('Share your experience…') }}"></textarea>
                <button class="btn btn-primary w-full">{{ __('Submit review') }}</button>
            </form>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Contact --}}
            <div class="rounded-3xl border border-app p-6">
                <h3 class="font-semibold text-strong">{{ __('Request a quote') }}</h3>
                <form method="POST" action="{{ route('marketplace.contact', $agent) }}" class="mt-4 space-y-3">
                    @csrf
                    <select name="shipping_method" class="field">
                        <option value="">{{ __('Any method') }}</option>
                        <option value="air">{{ __('Air') }}</option><option value="sea">{{ __('Sea') }}</option><option value="express">{{ __('Express') }}</option>
                    </select>
                    <textarea name="message" rows="3" required class="field" placeholder="{{ __('What do you need shipped?') }}"></textarea>
                    <button class="btn btn-primary w-full">{{ __('Send request') }}</button>
                </form>
            </div>

            {{-- Review --}}
            <div class="rounded-3xl border border-app p-6" x-data="{ rating: 5 }">
                <h3 class="font-semibold text-strong">{{ __('Leave a review') }}</h3>
                <form method="POST" action="{{ route('marketplace.review', $agent) }}" class="mt-4 space-y-3">
                    @csrf
                    <div class="flex gap-1">
                        @for ($i = 1; $i <= 5; $i++)
                            <button type="button" @click="rating = {{ $i }}" class="text-2xl" :class="rating >= {{ $i }} ? 'text-amber-400' : 'text-faint'">★</button>
                        @endfor
                        <input type="hidden" name="rating" :value="rating">
                    </div>
                    <textarea name="comment" rows="3" class="field" placeholder="{{ __('Share your experience…') }}"></textarea>
                    <button class="btn btn-primary w-full">{{ __('Submit review') }}</button>
                </form>
            </div>
        </div>
    @endif

    @if ($agent->shippingRates->isNotEmpty())
        <div class="overflow-hidden rounded-3xl border border-app">
            <h3 class="p-5 font-semibold text-strong">{{ __('Shipping rates') }}</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-y border-app text-muted"><tr><th class="px-5 py-3">{{ __('Method') }}</th><th class="px-5 py-3">{{ __('Destination') }}</th><th class="px-5 py-3">{{ __('Price') }}</th><th class="px-5 py-3">{{ __('ETA') }}</th></tr></thead>
                    <tbody class="divide-y divide-app">
                        @foreach ($agent->shippingRates->where('is_active', true) as $r)
                            <tr class="hover:surface-2"><td class="px-5 py-3 text-strong">{{ ucfirst($r->method) }}</td><td class="px-5 py-3 text-body">{{ $r->destinationCountry?->name ?? 'Various' }}</td><td class="px-5 py-3 text-body">@if($r->price_per_kg){{ money($r->price_per_kg,$r->currency) }}/kg @endif</td><td class="px-5 py-3 text-body">{{ $r->estimated_days_min }}–{{ $r->estimated_days_max }}d</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="rounded-3xl border border-app p-6">
        <h3 class="font-semibold text-strong">{{ __('Reviews') }}</h3>
        <div class="mt-4 space-y-3">
            @forelse ($reviews as $review)
                <div class="rounded-xl border border-app p-4">
                    <div class="flex items-center justify-between"><span class="font-medium text-strong">{{ $review->reviewerName() }}</span><span class="text-amber-400">@for($i=0;$i<$review->rating;$i++)★@endfor</span></div>
                    @if ($review->comment)<p class="mt-1 text-sm text-muted">{{ __($review->comment) }}</p>@endif
                </div>
            @empty
                <p class="py-4 text-center text-sm text-faint">{{ __('No reviews yet.') }}</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
