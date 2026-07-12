{{-- Shown whenever a visitor lands on the gift cards category or a gift-card product page. --}}
@php $gcCountries = \App\Models\Country::active()->get(['name', 'iso2']); @endphp
<div x-data="{ open: false }" x-init="if (! sessionStorage.getItem('gc_intro_dismissed') && localStorage.getItem('pb-onboarded')) { open = true }">
    <x-sheet max-width="sm:max-w-xl" class="!p-0">
        {{-- Header banner (solid brand) --}}
        <div class="relative rounded-t-3xl bg-brand-900 px-6 py-6 sm:rounded-t-2xl sm:px-8 sm:py-7">
            <button type="button" @click="open = false; sessionStorage.setItem('gc_intro_dismissed', '1')"
                    class="absolute right-4 top-4 grid h-8 w-8 place-items-center rounded-full bg-white/15 text-white transition hover:bg-white/25">
                <x-icon name="x" class="h-4 w-4" />
            </button>
            <div class="flex items-center gap-5">
                <div class="min-w-0 flex-1 pr-10">
                    <h3 class="text-xl font-extrabold leading-tight text-white sm:text-2xl">{{ __('Gift cards for everything you love') }}</h3>
                    <p class="mt-2 text-sm text-white/80">{{ __('Amazon, Apple, Steam, Google Play, Netflix and more — delivered to your account in seconds.') }}</p>
                </div>
                <img src="{{ asset('assets/'.rawurlencode('gift card small guy1.png')) }}" alt="" class="h-24 w-auto shrink-0 sm:h-32" loading="lazy">
            </div>
        </div>

        {{-- Body (on the card's normal surface — readable in both themes) --}}
        <div class="px-6 py-6 sm:px-8 sm:py-7">
            <p class="text-sm text-body">{{ __('Shopping from your country? Switch your region below to see the gift cards available and priced for where you are.') }}</p>

            <div class="mt-4 rounded-2xl bg-slate-500/8 p-4 ring-1 ring-slate-500/15">
                <label for="gc-country" class="mb-1.5 block text-xs font-semibold text-strong">{{ __('Your country') }}</label>
                <select id="gc-country" data-pbselect="country" data-nav="{{ route('region.set', '__VALUE__') }}"
                        data-empty="{{ __('No matches') }}" data-search="{{ __('Search…') }}" autocomplete="off">
                    @foreach ($gcCountries as $c)
                        <option value="{{ $c->iso2 }}" @selected(region()['iso'] === $c->iso2)>{{ $c->name }}</option>
                    @endforeach
                </select>

                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1.5 border-t border-slate-500/15 pt-3 text-xs font-medium text-body">
                    <span class="inline-flex items-center gap-1.5"><x-icon name="bolt" class="h-3.5 w-3.5 text-brand-600" /> {{ __('Instant delivery') }}</span>
                    <span class="inline-flex items-center gap-1.5"><x-icon name="shield" class="h-3.5 w-3.5 text-brand-600" /> {{ __('Clear refund policy') }}</span>
                    <span class="inline-flex items-center gap-1.5"><x-icon name="check-circle" class="h-3.5 w-3.5 text-brand-600" /> {{ __('Redeem in minutes') }}</span>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap gap-3">
                <button type="button" @click="open = false; sessionStorage.setItem('gc_intro_dismissed', '1')" class="btn btn-ghost">{{ __('Got it') }}</button>
                <a href="{{ route('shop.category', 'gift-cards') }}" class="btn btn-primary">{{ __('Browse gift cards') }} <x-icon name="arrow-right" class="h-4 w-4" /></a>
            </div>
        </div>
    </x-sheet>
</div>
