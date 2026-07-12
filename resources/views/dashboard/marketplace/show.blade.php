@extends('layouts.app')
@section('page-title', $agent->business_name)

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('marketplace.index') }}" class="text-sm text-brand-300 hover:text-brand-200">← {{ __('All agents') }}</a>

    <x-glass-card>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <span class="grid h-16 w-16 place-items-center overflow-hidden rounded-2xl bg-brand-600 text-xl font-bold text-strong">
                @if ($agent->logo_path)<img src="{{ Storage::url($agent->logo_path) }}" class="h-full w-full object-cover" alt="">@else{{ strtoupper(substr($agent->business_name,0,2)) }}@endif
            </span>
            <div class="flex-1">
                <h2 class="text-xl font-bold text-strong">{{ $agent->business_name }} <x-icon name="check-circle" class="ml-1 inline h-4 w-4 text-emerald-400" /></h2>
                <p class="text-sm text-muted">{{ $agent->warehouseCountry?->name ?? 'China' }} · {{ $agent->warehouse_city }}</p>
                <div class="mt-2 flex items-center gap-1 text-amber-300"><x-icon name="star" class="h-4 w-4 fill-current" /> <span class="font-semibold text-strong">{{ number_format((float)$agent->rating,1) }}</span> <span class="text-xs text-faint">({{ $agent->reviews_count }})</span></div>
            </div>
        </div>
        @if ($agent->bio)<p class="mt-4 text-body">{{ $agent->bio }}</p>@endif
    </x-glass-card>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Contact --}}
        <x-glass-card>
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
        </x-glass-card>

        {{-- Review --}}
        <x-glass-card x-data="{ rating: 5 }">
            <h3 class="font-semibold text-strong">{{ __('Leave a review') }}</h3>
            <form method="POST" action="{{ route('marketplace.review', $agent) }}" class="mt-4 space-y-3">
                @csrf
                <div class="flex gap-1">
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button" @click="rating = {{ $i }}" class="text-2xl" :class="rating >= {{ $i }} ? 'text-amber-300' : 'text-faint'">★</button>
                    @endfor
                    <input type="hidden" name="rating" :value="rating">
                </div>
                <textarea name="comment" rows="3" class="field" placeholder="{{ __('Share your experience…') }}"></textarea>
                <button class="btn btn-ghost w-full">{{ __('Submit review') }}</button>
            </form>
        </x-glass-card>
    </div>

    @if ($agent->shippingRates->isNotEmpty())
        <x-glass-card padding="p-0">
            <h3 class="p-5 font-semibold text-strong">{{ __('Shipping rates') }}</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-y border-app text-muted"><tr><th class="px-5 py-3">{{ __('Method') }}</th><th class="px-5 py-3">{{ __('Destination') }}</th><th class="px-5 py-3">{{ __('Price') }}</th><th class="px-5 py-3">{{ __('ETA') }}</th></tr></thead>
                    <tbody class="divide-y divide-app">
                        @foreach ($agent->shippingRates->where('is_active', true) as $r)
                            <tr><td class="px-5 py-3 text-strong">{{ ucfirst($r->method) }}</td><td class="px-5 py-3 text-body">{{ $r->destinationCountry?->name ?? 'Various' }}</td><td class="px-5 py-3 text-body">@if($r->price_per_kg){{ money($r->price_per_kg,$r->currency) }}/kg @endif</td><td class="px-5 py-3 text-body">{{ $r->estimated_days_min }}–{{ $r->estimated_days_max }}d</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-glass-card>
    @endif

    <x-glass-card>
        <h3 class="font-semibold text-strong">{{ __('Reviews') }}</h3>
        <div class="mt-4 space-y-3">
            @forelse ($reviews as $review)
                <div class="rounded-xl border border-app surface p-4">
                    <div class="flex items-center justify-between"><span class="font-medium text-strong">{{ $review->user->name }}</span><span class="text-amber-300">@for($i=0;$i<$review->rating;$i++)★@endfor</span></div>
                    @if ($review->comment)<p class="mt-1 text-sm text-muted">{{ __($review->comment) }}</p>@endif
                </div>
            @empty
                <p class="py-4 text-center text-sm text-faint">{{ __('No reviews yet.') }}</p>
            @endforelse
        </div>
    </x-glass-card>
</div>
@endsection
