@props(['url' => null])
@php $discordUrl = $url ?: (setting('social_discord_support') ?: setting('social_discord', '#')); @endphp
{{-- Live member/online counts via Discord's public invite API (no bot, no auth, no
     server settings to change) — polls every 60s while the card is on screen. --}}
<div x-data="discordLive(@js($discordUrl))"
     {{ $attributes->merge(['class' => 'glass rounded-2xl p-5']) }}>
    <div class="flex items-center gap-3">
        <span class="relative grid h-11 w-11 shrink-0 place-items-center rounded-xl text-white" style="background:#5865F2">
            <template x-if="icon">
                <img :src="icon" alt="" class="h-full w-full rounded-2xl object-cover">
            </template>
            <template x-if="!icon">
                <svg viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6"><path d="M20.32 4.37A19.8 19.8 0 0 0 15.45 3c-.2.38-.45.9-.62 1.31a18.3 18.3 0 0 0-5.66 0C9 3.9 8.74 3.38 8.53 3a19.7 19.7 0 0 0-4.87 1.37C.56 9.04-.28 13.58.14 18.06a19.9 19.9 0 0 0 6.05 3.06c.49-.67.93-1.38 1.3-2.13-.71-.27-1.4-.6-2.04-.99l.5-.4c3.92 1.83 8.15 1.83 12.02 0l.5.4c-.64.39-1.33.72-2.04.99.37.75.8 1.46 1.29 2.13a19.9 19.9 0 0 0 6.06-3.06c.5-5.18-.85-9.68-3.56-13.69zM8.02 15.33c-1.18 0-2.15-1.09-2.15-2.42s.95-2.42 2.15-2.42 2.17 1.09 2.15 2.42c0 1.33-.96 2.42-2.15 2.42zm7.96 0c-1.18 0-2.15-1.09-2.15-2.42s.95-2.42 2.15-2.42 2.17 1.09 2.15 2.42c0 1.33-.95 2.42-2.15 2.42z"/></svg>
            </template>
            {{-- Live pulse dot, only once we have a real reading --}}
            <span x-show="!loading && !failed" x-cloak class="absolute -bottom-1 -right-1 grid h-4.5 w-4.5 place-items-center rounded-full bg-white">
                <span class="relative flex h-3 w-3">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex h-3 w-3 rounded-full bg-emerald-500"></span>
                </span>
            </span>
        </span>
        <div class="min-w-0">
            <p class="truncate font-semibold text-strong" x-text="name || '{{ __('Join our Discord') }}'"></p>
            <template x-if="loading">
                <span class="mt-1 inline-block h-4 w-36 animate-pulse rounded surface-2"></span>
            </template>
            <template x-if="!loading && !failed">
                <p class="mt-0.5 text-sm text-muted">
                    <span class="font-semibold text-emerald-600" x-text="online"></span> {{ __('online') }}
                    <span class="text-faint">&middot;</span>
                    <span x-text="members"></span> {{ __('members') }}
                </p>
            </template>
            <template x-if="!loading && failed">
                <p class="mt-0.5 text-sm text-muted">{{ __('Chat with the team and community in real time for fast support.') }}</p>
            </template>
        </div>
    </div>
    <a href="{{ $discordUrl }}" target="_blank" rel="noopener" class="btn btn-primary mt-4 w-full" style="background-color:#5865F2">
        {{ __('Join Discord') }} <x-icon name="arrow-right" class="h-4 w-4" />
    </a>
</div>
