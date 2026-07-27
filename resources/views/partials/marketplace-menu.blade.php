{{--
    Mobile-only Marketplace drawer, triggered from the bottom dock's Menu sheet.
    Desktop has no panel: the Marketplace entry in the primary sidebar expands
    inline on hover instead (see partials/nav-user.blade.php). This drawer uses
    the shell's `ctx`/`openCtx`/`closeCtx` state and renders nothing on desktop.
--}}
@php
    $megaMenuCategories = $megaMenuCategories ?? collect();
    $megaMenuAccentClass = fn (?string $a) => match ($a) {
        'emerald' => 'bg-emerald-500/15 text-emerald-500',
        'amber' => 'bg-amber-500/15 text-amber-500',
        'rose' => 'bg-rose-500/15 text-rose-500',
        'slate' => 'bg-slate-500/15 text-slate-500',
        'sky' => 'bg-sky-500/15 text-sky-500',
        'violet' => 'bg-violet-500/15 text-violet-500',
        'orange' => 'bg-orange-500/15 text-orange-500',
        default => 'bg-brand-500/15 text-brand-500',
    };
    $megaMenuBadgeClass = fn (?string $s) => match ($s) {
        'emerald' => 'bg-emerald-500',
        'amber' => 'bg-amber-500',
        'rose' => 'bg-rose-500',
        'slate' => 'bg-slate-500',
        default => 'bg-brand-500',
    };
    $flatten = function ($cats) use (&$flatten) {
        return $cats->flatMap(fn ($c) => $c->relationLoaded('children') ? collect([$c])->concat($flatten($c->children)) : [$c]);
    };
    $megaMenuFlat = $flatten($megaMenuCategories)->values();
@endphp

<div x-data="marketplaceMenu(@js($megaMenuFlat->map(fn ($c) => ['id' => $c->id, 'slug' => $c->slug, 'name' => __($c->name), 'url' => route('shop.category', $c->slug)])))" class="contents">
    {{-- Trigger tile, matches the other Menu-sheet shortcuts --}}
    <button type="button" id="nav-trigger-marketplace-mobile" @click="$dispatch('close-dock-menu'); openCtx('marketplace')"
            :aria-expanded="(ctx === 'marketplace').toString()" aria-haspopup="true" aria-controls="marketplace-mobile-drawer"
            class="group flex flex-col items-center gap-1 rounded-xl p-1 text-center text-[11px] font-medium leading-tight text-body transition hover:-translate-y-0.5">
        <span class="grid h-9 w-9 place-items-center rounded-full text-white shadow-sm transition group-hover:shadow-lg" style="background: #EC4899">
            <x-img-icon name="Shop-Sign-Bag--Streamline-Ultimate.png" class="h-4 w-4" />
        </span>
        <span class="line-clamp-2">{{ __('Marketplace') }}</span>
    </button>

    {{-- Full-screen mobile drawer --}}
    <div x-show="ctx === 'marketplace'" x-cloak class="fixed inset-0 z-[60] bg-black/50 lg:hidden" @click="closeCtx()"></div>
    <div x-show="ctx === 'marketplace'" x-cloak id="marketplace-mobile-drawer" role="dialog" aria-label="{{ __('Marketplace') }}"
         x-transition:enter="transition ease-out duration-250" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 z-[61] flex w-full flex-col surface lg:hidden" style="display:none">
        <div class="sticky top-0 flex items-center gap-3 border-b border-app p-4" style="background: var(--header-bg);">
            <button type="button" @click="closeCtx()" aria-label="{{ __('Back') }}" class="grid h-9 w-9 place-items-center rounded-full text-muted hover:surface-2">
                <x-icon name="arrow-right" class="h-5 w-5 rotate-180" />
            </button>
            <p class="font-bold text-strong">{{ __('Marketplace') }}</p>
        </div>
        <div class="border-b border-app p-4">
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
                <input x-model="q" type="text" placeholder="{{ __('Search products & categories…') }}" class="field pl-9 text-sm" aria-label="{{ __('Search products & categories') }}">
            </div>
        </div>
        <div class="flex-1 overflow-y-auto p-4">
            <div class="mb-4 grid grid-cols-3 gap-2" x-show="!q.trim()">
                <a href="{{ route('shop.index') }}" class="flex flex-col items-center gap-1.5 rounded-2xl border border-app p-3 text-center"><x-icon name="bag" class="h-5 w-5 text-brand-500" /><span class="text-[11px] font-medium text-body">{{ __('All Products') }}</span></a>
                @if (\Illuminate\Support\Facades\Route::has('esim.index'))
                    <a href="{{ route('esim.index') }}" class="flex flex-col items-center gap-1.5 rounded-2xl border border-app p-3 text-center"><x-icon name="sim" class="h-5 w-5 text-brand-500" /><span class="text-[11px] font-medium text-body">{{ __('Travel eSIMs') }}</span></a>
                @endif
                <a href="{{ route('shop.orders.index') }}" class="flex flex-col items-center gap-1.5 rounded-2xl border border-app p-3 text-center"><x-icon name="receipt" class="h-5 w-5 text-brand-500" /><span class="text-[11px] font-medium text-body">{{ __('My Orders') }}</span></a>
                @if (\Illuminate\Support\Facades\Route::has('wishlist.index'))
                    <a href="{{ route('wishlist.index') }}" class="flex flex-col items-center gap-1.5 rounded-2xl border border-app p-3 text-center"><x-icon name="heart" class="h-5 w-5 text-brand-500" /><span class="text-[11px] font-medium text-body">{{ __('Wishlist') }}</span></a>
                @endif
            </div>
            <p class="mb-2 px-1 text-[11px] font-bold uppercase tracking-wider text-faint">{{ __('Categories') }}</p>
            <div class="space-y-2">
                <template x-for="c in (q.trim() ? filtered() : categories)" :key="'m-'+c.slug">
                    <a :href="c.url" @click="visit(c.slug)" class="flex items-center gap-3 rounded-2xl border border-app p-3">
                        <span class="min-w-0 flex-1">
                            <span class="truncate text-sm font-semibold text-strong" x-text="c.name"></span>
                        </span>
                        <x-icon name="chevron-right" class="h-4 w-4 shrink-0 text-faint" />
                    </a>
                </template>
            </div>
        </div>
    </div>
</div>
