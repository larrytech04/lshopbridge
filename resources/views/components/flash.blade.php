@php
    $map = [
        'success' => ['cls' => 'border-emerald-400/30 bg-emerald-500/10 text-emerald-200', 'icon' => 'check-circle'],
        'error' => ['cls' => 'border-rose-400/30 bg-rose-500/10 text-rose-200', 'icon' => 'alert'],
        'warning' => ['cls' => 'border-amber-400/30 bg-amber-500/10 text-amber-200', 'icon' => 'alert'],
        'info' => ['cls' => 'border-sky-400/30 bg-sky-500/10 text-sky-200', 'icon' => 'info'],
    ];
@endphp

@foreach ($map as $key => $cfg)
    @if (session($key))
        <div x-data="{ show: true }" x-show="show" x-transition.duration.300ms
             class="mb-4 flex items-start gap-3 rounded-2xl border px-4 py-3 {{ $cfg['cls'] }}">
            <x-icon name="{{ $cfg['icon'] }}" class="mt-0.5 h-5 w-5 shrink-0" />
            <p class="flex-1 text-sm">{{ session($key) }}</p>
            <button type="button" @click="show = false" class="opacity-70 hover:opacity-100"><x-icon name="x" class="h-4 w-4" /></button>
        </div>
    @endif
@endforeach

@if (session('otp_debug'))
    <div class="mb-4 flex items-center gap-3 rounded-2xl border border-brand-400/30 bg-slate-500/10 px-4 py-3 text-brand-200">
        <x-icon name="phone-device" class="h-5 w-5" />
        <p class="text-sm font-medium">{{ session('otp_debug') }}</p>
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded-2xl border border-rose-400/30 bg-rose-500/10 px-4 py-3 text-rose-200">
        <div class="flex items-center gap-2 text-sm font-semibold"><x-icon name="alert" class="h-5 w-5" /> {{ __('Please fix the following:') }}</div>
        <ul class="mt-1.5 list-disc space-y-0.5 pl-8 text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
