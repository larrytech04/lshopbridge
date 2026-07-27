@extends(auth()->check() ? 'layouts.app' : 'layouts.public')
@section('title', 'eSIM Device Compatibility Checker · '.config('platform.name'))
@section('page-title', __('Device Compatibility Checker'))

@section('content')
<div class="mx-auto max-w-xl px-4 py-10 sm:px-6"
     x-data="esimCompatibilityChecker(@js(['modelsUrl' => route('esim.compatibility.models'), 'checkUrl' => route('esim.compatibility.check')]), @js($brands))">
    <h1 class="text-2xl font-bold text-strong">{{ __('Will my phone work with an eSIM?') }}</h1>
    <p class="mt-1 text-sm text-muted">{{ __('Select your device to check real eSIM support before you buy. If your exact model isn\'t listed, check your device settings for "eSIM" or "Add Mobile Plan".') }}</p>

    <div class="card-solid mt-6 space-y-4 rounded-3xl border border-app p-6 shadow-sm">
        <div>
            <label class="label">{{ __('Brand') }}</label>
            <select x-model="brand" @change="onBrandChange()" class="field">
                <option value="">{{ __('Select a brand…') }}</option>
                <template x-for="b in brands" :key="b">
                    <option :value="b" x-text="b"></option>
                </template>
            </select>
        </div>

        <div x-show="brand" x-cloak>
            <label class="label">{{ __('Model') }}</label>
            <select x-model="model" @change="check()" class="field">
                <option value="">{{ __('Select a model…') }}</option>
                <template x-for="m in models" :key="m.id">
                    <option :value="m.model" x-text="m.model"></option>
                </template>
            </select>
        </div>

        <p x-show="checking" x-cloak class="text-sm text-muted">{{ __('Checking…') }}</p>

        <template x-if="result && !checking">
            <div class="space-y-2 rounded-2xl p-4 text-sm" :class="result.found && result.esim_supported ? 'bg-emerald-500/10' : 'bg-rose-500/10'">
                <template x-if="!result.found">
                    <p class="text-body" x-text="result.message"></p>
                </template>
                <template x-if="result.found">
                    <div class="space-y-1.5">
                        <p class="font-semibold" :class="result.esim_supported ? 'text-emerald-700' : 'text-rose-700'"
                           x-text="result.esim_supported ? '{{ __('eSIM supported') }}' : '{{ __('eSIM not supported on this device') }}'"></p>
                        <p x-show="result.regional_restriction" class="text-xs text-muted" x-text="result.regional_restriction"></p>
                        <p x-show="result.carrier_lock_note" class="text-xs text-muted" x-text="result.carrier_lock_note"></p>
                        <p x-show="result.min_os_version" class="text-xs text-faint">{{ __('Minimum OS version:') }} <span x-text="result.min_os_version"></span></p>
                        <p x-show="result.max_active_esims" class="text-xs text-faint">{{ __('Max active eSIMs:') }} <span x-text="result.max_active_esims"></span></p>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <p class="mt-4 text-xs text-faint">{{ __('Compatibility data is curated from manufacturer documentation and updated periodically. It is not a guarantee your specific carrier or firmware version supports eSIM.') }}</p>
</div>
@endsection
