@php
    $shortcutGroups = collect(config('shortcuts.defaults'))
        ->filter(fn ($s) => empty($s['role']) || in_array(auth()->user()->role->value ?? auth()->user()->role, ['admin', 'super_admin'], true))
        ->groupBy('category')
        ->map(fn ($items, $cat) => [
            'category' => config("shortcuts.categories.$cat", $cat),
            'items' => $items->map(fn ($s) => ['key' => $s['key'], 'label' => $s['label']])->values(),
        ])->values();
@endphp

<div x-data="shortcutsHelp(@js($shortcutGroups))" x-on:keydown.window="if ($event.key === '?' && open) { /* already open, no-op */ }">
    <div x-show="open" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center p-4" x-transition.opacity>
        <div class="absolute inset-0 bg-black/50" style="backdrop-filter: blur(4px);" @click="close()"></div>

        <div x-show="open" @click.outside="close()"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
             class="glass-strong relative flex max-h-[80vh] w-full max-w-2xl flex-col overflow-hidden rounded-3xl shadow-2xl">

            <div class="flex items-center justify-between gap-3 border-b border-app px-5 py-4">
                <h2 class="text-lg font-bold text-strong">{{ __('Keyboard shortcuts') }}</h2>
                <div class="flex items-center gap-2">
                    <button type="button" @click="window.print()" class="rounded-full border border-app px-3 py-1.5 text-xs font-semibold text-body transition hover:surface-2">{{ __('Print') }}</button>
                    <button type="button" @click="close()" aria-label="{{ __('Close') }}" class="grid h-8 w-8 place-items-center rounded-full transition hover:surface-2">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <div class="border-b border-app px-5 py-3">
                <input x-model="q" type="text" autocomplete="off" placeholder="{{ __('Search shortcuts…') }}"
                       class="w-full bg-transparent text-sm text-strong placeholder:text-faint focus:outline-none">
            </div>

            <div class="flex-1 space-y-5 overflow-y-auto p-5">
                <template x-for="group in filtered()" :key="group.category">
                    <div>
                        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-faint" x-text="group.category"></p>
                        <div class="space-y-1">
                            <template x-for="item in group.items" :key="item.key">
                                <div class="flex items-center justify-between gap-3 rounded-xl px-2 py-1.5 hover:surface-2">
                                    <span class="text-sm text-body" x-text="item.label"></span>
                                    <div class="flex items-center gap-1.5">
                                        <template x-for="part in formatKey(item.key)" :key="part">
                                            <kbd class="rounded-md border border-app surface px-1.5 py-0.5 text-[11px] font-semibold text-strong" x-text="part"></kbd>
                                        </template>
                                        <button type="button" @click="copyKey(item.key)" class="ml-1 grid h-6 w-6 place-items-center rounded-full text-faint transition hover:text-strong" :aria-label="'{{ __('Copy') }}'">
                                            <x-icon name="doc" class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
                <template x-if="filtered().length === 0">
                    <p class="py-8 text-center text-sm text-muted">{{ __('No shortcuts match your search.') }}</p>
                </template>
            </div>
        </div>
    </div>
</div>
