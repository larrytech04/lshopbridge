@php
    // Real, admin-managed strip banners (Content -> Banners & Promotions, type
    // "strip"), respecting the same targeting/date enforcement as the homepage
    // hero. Falls back to the built-in defaults when none are active/visible —
    // never an empty bar, never more than one shown at once.
    $stripBanner = app(\App\Services\Admin\BannerAdminService::class)->firstVisible(
        \App\Models\Banner::active()->where('type', 'strip')->get(),
        auth()->user(),
    );

    $annMsgs = $stripBanner
        ? [trim(($stripBanner->title ?? '').($stripBanner->subtitle ? ' — '.$stripBanner->subtitle : ''))]
        : [
            __('Built for African shoppers funding China accounts'),
            __('MoMo, bank, card & crypto funding in one place'),
            __('Trusted payment support for China shopping'),
            __('Simple funding. Clear rates. Reliable support.'),
        ];
@endphp
<div x-data="announceBar(@js($annMsgs))" x-show="open" x-cloak x-collapse class="announce-bar relative z-50 text-white">
    <div class="mx-auto flex max-w-none items-center gap-2 px-4 py-1 sm:px-6">
        <x-icon name="star" class="hidden h-3 w-3 shrink-0 fill-current text-white/90 sm:block" />
        <div class="ann-track min-w-0 flex-1 overflow-hidden text-center">
            @if ($stripBanner && $stripBanner->cta_url)
                <a href="{{ $stripBanner->cta_url }}" class="ann-msg block truncate text-[11px] font-semibold tracking-tight hover:underline sm:text-xs">{{ $annMsgs[0] }}{{ $stripBanner->cta_label ? ' — '.$stripBanner->cta_label : '' }}</a>
            @else
                <p class="ann-msg truncate text-[11px] font-semibold tracking-tight sm:text-xs" :class="{ 'is-flipping': flipping }" x-text="messages[i]"></p>
            @endif
        </div>
        <button type="button" @click="dismiss()" aria-label="{{ __('Dismiss') }}"
                class="-mr-1 shrink-0 rounded-full p-1 text-white/80 transition hover:bg-white/15 hover:text-white">
            <x-icon name="x" class="h-3.5 w-3.5" />
        </button>
    </div>
</div>
