{{-- Dropdown panel: anchored directly below the search bar (shares the parent's Alpine scope).
     No dim/blur overlay, just a crisp, solid card, so the rest of the page and the search
     bar itself stay perfectly clear. Outside-click closing lives on the outer wrapper (covers
     the field + button too), so clicking the field/button to open it isn't itself treated as
     an "outside" click. --}}
<div x-show="open" x-cloak
     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
     class="card-solid fixed inset-x-3 top-[4.25rem] z-[100] overflow-hidden rounded-2xl border border-app shadow-2xl lg:absolute lg:inset-x-0 lg:top-full lg:mt-2">

        {{-- Mobile only: there's no persistent header field to type into on this breakpoint,
             so the dropdown carries its own input here (desktop types in the field above it). --}}
        <div class="flex items-center gap-2 border-b border-app px-2.5 py-2 lg:hidden">
            <input x-ref="mobileInput" x-model="q" @input.debounce.250ms="search()"
                   type="text" autocomplete="off" placeholder="{{ __('Search…') }}"
                   class="min-w-0 flex-1 bg-transparent text-sm text-strong placeholder:text-faint focus:outline-none">
            <button type="button" @click="close()" aria-label="{{ __('Close') }}" class="grid h-6 w-6 shrink-0 place-items-center rounded-full text-faint transition hover:surface-2 hover:text-strong">
                <x-icon name="x" class="h-4 w-4" />
            </button>
        </div>

        {{-- Quick-jump tabs --}}
        <div class="no-scrollbar flex items-center gap-1 overflow-x-auto border-b border-app px-2 py-1.5 lg:gap-1.5 lg:px-2.5 lg:py-2">
            <template x-for="t in tabs" :key="t.key">
                <a :href="t.url" class="shrink-0 whitespace-nowrap rounded-full border px-2 py-0.5 text-[11px] font-semibold transition lg:px-2.5 lg:py-1 lg:text-xs"
                   :class="t.active ? 'border-slate-900 bg-slate-900 text-white' : 'border-app text-body hover:surface-2'"
                   x-text="t.label"></a>
            </template>
        </div>

        <div class="max-h-[38vh] overflow-y-auto p-1 lg:max-h-[45vh] lg:p-1.5">
            {{-- Most used (shown while the query is empty) --}}
            <template x-if="q === ''">
                <div>
                    <p class="px-2 pb-1 pt-1 text-[10px] font-semibold uppercase tracking-wide text-faint lg:px-2.5 lg:pt-1.5 lg:text-[11px]">{{ __('Most used') }}</p>
                    <template x-for="(item, i) in mostUsed" :key="'mu-'+i">
                        <a :href="item.url" class="flex items-center gap-2 rounded-xl p-1.5 transition lg:gap-2.5 lg:p-2" :class="flatIndex('most', i) === selectedIndex ? 'surface-2' : 'hover:surface-2'">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-slate-500/12 text-slate-500 lg:h-8 lg:w-8" x-html="iconSvg(item.icon)"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[13px] font-bold text-strong lg:text-sm" x-text="item.title"></span>
                                <span class="block truncate text-[11px] text-muted lg:text-xs" x-text="item.description"></span>
                            </span>
                            <x-icon name="chevron-right" class="h-3.5 w-3.5 shrink-0 text-faint lg:h-4 lg:w-4" />
                        </a>
                    </template>
                </div>
            </template>

            {{-- Live search results (grouped) --}}
            <template x-if="q !== ''">
                <div>
                    <template x-if="loading"><p class="p-3 text-sm text-muted">{{ __('Searching…') }}</p></template>
                    <template x-if="!loading && groups.length === 0"><p class="p-3 text-sm text-muted">{{ __('No matches. Try a different search.') }}</p></template>
                    <template x-for="group in groups" :key="group.key">
                        <div class="mb-1">
                            <p class="px-2 pb-1 pt-1 text-[10px] font-semibold uppercase tracking-wide text-faint lg:px-2.5 lg:pt-1.5 lg:text-[11px]" x-text="group.label"></p>
                            <template x-for="(item, i) in group.items" :key="group.key + i">
                                <a :href="item.url" class="flex items-center gap-2 rounded-xl p-1.5 transition lg:gap-2.5 lg:p-2" :class="flatIndex(group.key, i) === selectedIndex ? 'surface-2' : 'hover:surface-2'">
                                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-slate-500/12 text-slate-500 lg:h-8 lg:w-8" x-html="iconSvg(item.icon)"></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-[13px] font-semibold text-strong lg:text-sm" x-text="item.title"></span>
                                        <span class="block truncate text-[11px] text-muted lg:text-xs" x-text="item.description"></span>
                                    </span>
                                </a>
                            </template>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <div class="border-t border-app px-3 py-2.5 text-xs text-faint">
            {{ __('Type above to search products, brands, orders or account settings.') }}
        </div>
</div>
