@php
    $obCountries = \App\Models\Country::active()->get(['name', 'iso2']);
    $geo = geo_country();
    $geoDefault = $geo && $obCountries->firstWhere('iso2', $geo) ? $geo : region()['iso'];
@endphp

<div x-data="onboarding(@js($geoDefault), @js((bool) $geo))" x-init="init()" data-onboard-url="{{ route('locale.onboard') }}">
    <x-sheet max-width="sm:max-w-md" class="p-5 sm:p-7">
        <div class="text-center">
            <span class="mx-auto grid h-10 w-10 place-items-center rounded-xl bg-brand-600 text-white"><x-icon name="globe" class="h-5 w-5" /></span>
            <h3 class="mt-2 text-base font-bold text-strong sm:text-lg">{{ __('Welcome to :app', ['app' => setting('site_name', config('platform.name'))]) }}</h3>
            <p class="mt-0.5 text-xs text-muted sm:text-sm">{{ __('Set your country, language and theme — you can change these anytime.') }}</p>
        </div>

        <div class="mt-4 space-y-3 text-left">
            {{-- Country (searchable, with flags) --}}
            <div>
                <label for="ob-country" class="mb-1 block text-xs font-medium text-muted">{{ __('Country') }}</label>
                <select id="ob-country" data-pbselect="country" data-empty="{{ __('No matches') }}" data-search="{{ __('Search…') }}" autocomplete="off">
                    @foreach ($obCountries as $c)
                        <option value="{{ $c->iso2 }}" @selected($c->iso2 === $geoDefault)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Language (searchable) --}}
            <div>
                <label for="ob-locale" class="mb-1 block text-xs font-medium text-muted">{{ __('Language') }}</label>
                <select id="ob-locale" data-pbselect="lang" data-empty="{{ __('No matches') }}" data-search="{{ __('Search…') }}" autocomplete="off">
                    @foreach (supported_locales() as $code => $label)
                        <option value="{{ $code }}" @selected($code === current_locale())>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Theme --}}
            <div>
                <label class="mb-1 block text-xs font-medium text-muted">{{ __('Theme') }}</label>
                @php
                    // Match the header theme-toggle: assets for Light + Extra dark, glyphs for Dark/System.
                    $themeOpts = [
                        'light'  => ['Light-Mode-Dark-Light--Streamline-Ultimate.png', true, __('Light')],
                        'system' => ['monitor', false, __('System')],
                        'dark'   => ['moon', false, __('Dark')],
                        'night'  => ['Halloween-Grim-Reaper--Streamline-Ultimate.png', true, __('Extra dark')],
                    ];
                @endphp
                <div class="grid grid-cols-2 gap-1 sm:grid-cols-4">
                    @foreach ($themeOpts as $mode => [$icon, $isAsset, $label])
                        <button type="button" @click="setTheme('{{ $mode }}')"
                                class="flex flex-col items-center gap-0.5 rounded-lg border px-1 py-1.5 text-[10px] font-medium leading-tight transition"
                                :class="theme === '{{ $mode }}' ? 'border-brand-400 surface-2 text-strong' : 'border-app text-body hover:surface'">
                            @if ($isAsset)<x-img-icon :name="$icon" class="h-3.5 w-3.5" />@else<x-icon :name="$icon" class="h-3.5 w-3.5" />@endif
                            <span class="whitespace-nowrap">{{ $label }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-5 flex items-center justify-between">
            <button type="button" @click="skip()" class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-muted hover:text-strong">{{ __('Skip') }}</button>
            <button type="button" @click="finish()" class="btn btn-primary px-4 py-2 text-xs">
                {{ __('Continue') }} <x-icon name="arrow-right" class="h-3.5 w-3.5" />
            </button>
        </div>
    </x-sheet>
</div>
