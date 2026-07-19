{{-- Shown once, automatically, on a visitor's first visit anywhere on the site. --}}
@php
    $wiCountries = \App\Models\Country::active()->get(['name', 'iso2']);
    $wiGeo = geo_country();
    $wiGeoDefault = $wiGeo && $wiCountries->firstWhere('iso2', $wiGeo) ? $wiGeo : region()['iso'];
    $wiGeoName = $wiCountries->firstWhere('iso2', $wiGeoDefault)?->name;
@endphp

<div x-data="welcomeIntro(@js($wiGeoDefault), @js((bool) $wiGeo))" data-onboard-url="{{ route('locale.onboard') }}">
    <x-sheet max-width="sm:max-w-lg" class="!p-0">
        {{-- Header banner (solid brand) --}}
        <div class="relative rounded-t-3xl bg-brand-900 px-6 py-6 sm:px-8 sm:py-7">
            <button type="button" @click="skip()" class="absolute right-4 top-4 grid h-8 w-8 place-items-center rounded-full bg-white/15 text-white transition hover:bg-white/25">
                <x-icon name="x" class="h-4 w-4" />
            </button>
            <div class="flex items-center gap-5">
                <div class="min-w-0 flex-1 pr-10">
                    <h3 class="text-xl font-extrabold leading-tight text-white sm:text-2xl">{{ __('Shopping from :country?', ['country' => $wiGeoName ?? __('your country')]) }}</h3>
                    <p class="mt-2 text-sm text-white/80">{{ __('Set your real location for accurate wallet funding rates, or switch to the United States / United Kingdom to browse our most recommended gift cards.') }}</p>
                </div>
                <img src="{{ asset('assets/'.rawurlencode('gift card small guy1.png')) }}" alt="" class="h-20 w-auto shrink-0 sm:h-28" loading="lazy">
            </div>
        </div>

        {{-- Body --}}
        <div class="px-6 py-6 sm:px-8 sm:py-7">
            <p class="text-xs text-muted sm:text-sm">
                {{ __('Tip: tap the country pill at the top of the page anytime to switch again, no need to come back here.') }}
            </p>

            <div class="mt-4">
                <label for="wi-country" class="mb-1 block text-xs font-medium text-muted">{{ __('Your country') }}</label>
                <select id="wi-country" data-pbselect="country" data-empty="{{ __('No matches') }}" data-search="{{ __('Search…') }}" autocomplete="off">
                    @foreach ($wiCountries as $c)
                        <option value="{{ $c->iso2 }}" @selected($c->iso2 === $wiGeoDefault)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Value props strip --}}
            <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1.5 rounded-2xl bg-slate-500/8 px-4 py-3 text-xs font-medium text-body ring-1 ring-slate-500/15">
                <span class="inline-flex items-center gap-1.5"><x-icon name="gauge" class="h-3.5 w-3.5 text-brand-600" /> {{ __('Accurate rates for your country') }}</span>
                <span class="inline-flex items-center gap-1.5"><x-icon name="wallet" class="h-3.5 w-3.5 text-brand-600" /> {{ __('Pay in your currency') }}</span>
                <span class="inline-flex items-center gap-1.5"><x-icon name="bolt" class="h-3.5 w-3.5 text-brand-600" /> {{ __('Region-priced gift cards, delivered instantly') }}</span>
            </div>

            <div class="mt-5 flex items-center justify-between">
                <button type="button" @click="skip()" class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-muted hover:text-strong">{{ __('Skip') }}</button>
                <button type="button" @click="finish()" class="btn btn-primary px-4 py-2 text-xs">
                    {{ __('Switch country') }} <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                </button>
            </div>
        </div>
    </x-sheet>
</div>
