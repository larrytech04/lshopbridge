{{-- Compact social trigger that rides the sidebar border (top); fans a panel
     downward with social / contact links. Links read from settings (fallback "#"). --}}
@php
    $social = [
        ['WhatsApp', setting('social_whatsapp', '#'), '#25D366',
            '<svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22c5.46 0 9.91-4.45 9.91-9.91C21.95 6.45 17.5 2 12.04 2zm5.8 14.06c-.24.68-1.4 1.3-1.94 1.38-.5.08-1.13.11-1.82-.11-.42-.13-.96-.31-1.65-.61-2.9-1.25-4.8-4.17-4.95-4.36-.14-.19-1.18-1.58-1.18-3.02 0-1.43.75-2.13 1.02-2.42.27-.29.58-.36.78-.36h.56c.18 0 .42-.07.66.5.24.59.82 2.04.89 2.19.07.15.12.32.02.51-.1.19-.15.31-.29.48-.15.17-.31.39-.44.52-.15.15-.3.31-.13.6.17.29.76 1.25 1.63 2.02 1.12 1 2.06 1.31 2.35 1.46.29.15.46.12.63-.07.17-.19.73-.85.92-1.14.19-.29.39-.24.66-.15.27.1 1.71.81 2 .96.29.15.49.22.56.34.07.12.07.68-.17 1.36z"/></svg>'],
        ['X', setting('social_x', '#'), '#000000',
            '<svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M18.24 2.25h3.31l-7.23 8.26L23.13 21.75h-6.66l-5.21-6.82-5.97 6.82H1.98l7.73-8.84L.87 2.25h6.83l4.71 6.23 5.83-6.23Zm-1.16 17.52h1.83L7.01 4.13H5.05l12.03 15.64Z"/></svg>'],
        ['Instagram', setting('social_instagram', '#'), '#E4405F',
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1.2" fill="currentColor" stroke="none"/></svg>'],
        ['Facebook', setting('social_facebook', '#'), '#1877F2',
            '<svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path d="M14 8.5V6.8c0-.8.2-1.3 1.4-1.3H17V2.6c-.3 0-1.3-.1-2.4-.1-2.4 0-4 1.5-4 4.2v1.8H8v3h2.6V22H14v-8.5h2.5l.4-3H14Z"/></svg>'],
        ['TikTok', setting('social_tiktok', '#'), '#010101',
            '<svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path d="M16.5 2c.3 2.1 1.5 3.7 3.5 4v2.5c-1.3.1-2.5-.3-3.6-1v6.7c0 3.4-2.7 6.1-6 6.1s-6-2.7-6-6.1 2.7-6.1 6-6.1c.3 0 .6 0 .9.1v2.7c-.3-.1-.6-.2-.9-.2a3.4 3.4 0 1 0 3.4 3.4V2h2.7Z"/></svg>'],
        ['Discord', setting('social_discord', '#'), '#5865F2',
            '<svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path d="M20.32 4.37A19.8 19.8 0 0 0 15.45 3c-.2.38-.45.9-.62 1.31a18.3 18.3 0 0 0-5.66 0C9 3.9 8.74 3.38 8.53 3a19.7 19.7 0 0 0-4.87 1.37C.56 9.04-.28 13.58.14 18.06a19.9 19.9 0 0 0 6.05 3.06c.49-.67.93-1.38 1.3-2.13-.71-.27-1.4-.6-2.04-.99l.5-.4c3.92 1.83 8.15 1.83 12.02 0l.5.4c-.64.39-1.33.72-2.04.99.37.75.8 1.46 1.29 2.13a19.9 19.9 0 0 0 6.06-3.06c.5-5.18-.85-9.68-3.56-13.69zM8.02 15.33c-1.18 0-2.15-1.09-2.15-2.42s.95-2.42 2.15-2.42 2.17 1.09 2.15 2.42c0 1.33-.96 2.42-2.15 2.42zm7.96 0c-1.18 0-2.15-1.09-2.15-2.42s.95-2.42 2.15-2.42 2.17 1.09 2.15 2.42c0 1.33-.95 2.42-2.15 2.42z"/></svg>'],
        [__('Email'), setting('social_email', '#'), '#EA4335',
            '<svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm9 7L4 6.6V18h16V6.6L12 12z"/></svg>'],
        [__('Phone'), setting('social_phone', '#'), 'var(--color-brand-600)',
            '<svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path d="M6.6 10.8c1.4 2.8 3.8 5.2 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.4.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.4 0 .8-.3 1l-2.2 2.2z"/></svg>'],
    ];
@endphp
<div x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false"
     class="relative" aria-label="{{ __('Social links') }}">
    {{-- Tiny FAB toggle (social icon): transparent on the sidebar border, grey circle in the mobile header --}}
    <button type="button" @click="open = !open" :aria-expanded="open" aria-label="{{ __('Social links') }}"
            class="grid place-items-center rounded-full transition-transform duration-300 {{ ($circled ?? false) ? 'h-9 w-9 border border-app surface text-muted' : 'h-6 w-6 bg-transparent text-brand-600' }}"
            :class="open ? 'rotate-90' : 'hover:scale-110'">
        <x-img-icon name="Social-Media-1--Streamline-Milano.png" class="{{ ($circled ?? false) ? 'h-4.5 w-4.5' : 'h-5 w-5' }}" />
    </button>

    {{-- Panel fans downward along the border --}}
    <div class="absolute left-0 flex w-max flex-col items-start gap-2 {{ ($circled ?? false) ? 'top-11' : 'top-8' }}">
        <div x-show="open" x-cloak x-transition class="inline-flex items-center gap-2 rounded-full glass-strong px-3 py-1 text-xs font-semibold text-strong shadow ring-1 ring-app">
            {{ __('Connect with us') }}
            <button type="button" @click="open = false" aria-label="{{ __('Close') }}" class="grid h-5 w-5 place-items-center rounded-full text-muted transition hover:bg-slate-500/15 hover:text-strong"><x-icon name="x" class="h-3.5 w-3.5" /></button>
        </div>

        @foreach ($social as $i => [$label, $href, $color, $svg])
            <a href="{{ $href }}" @if (! str_starts_with($href, '#') && ! str_starts_with($href, 'tel:') && ! str_starts_with($href, 'mailto:')) target="_blank" rel="noopener" @endif
               title="{{ $label }}" x-show="open" x-cloak
               x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2 scale-90" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
               x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 -translate-y-1"
               style="transition-delay: {{ $i * 35 }}ms"
               class="group flex items-center">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full text-white shadow-md transition-transform group-hover:scale-105" style="background: {{ $color }}">{!! $svg !!}</span>
                <span class="max-w-0 overflow-hidden whitespace-nowrap rounded-r-lg py-2 text-sm font-semibold text-white shadow-md transition-all duration-300 group-hover:max-w-[9rem] group-hover:px-3" style="background: {{ $color }}">{{ $label }}</span>
            </a>
        @endforeach
    </div>
</div>
