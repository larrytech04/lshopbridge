@php
    // Merge config defaults with this user's overrides/enabled flag into the
    // flat list the JS ShortcutManager consumes. Route-backed actions get
    // their URL resolved here (server-side) rather than in JS.
    $shortcutsEnabled = auth()->user()->shortcuts_enabled ?? true;
    $overrides = auth()->user()->shortcut_overrides ?? [];
    $resolvedShortcuts = $shortcutsEnabled
        ? collect(config('shortcuts.defaults'))
            ->filter(fn ($s) => empty($s['role']) || (auth()->user()->role->value ?? auth()->user()->role) === $s['role']
                || ($s['role'] === 'admin' && in_array(auth()->user()->role->value ?? auth()->user()->role, ['admin', 'super_admin'], true)))
            ->map(function ($s) use ($overrides) {
                $s['key'] = $overrides[$s['action'].'|'.($s['route'] ?? '')] ?? $s['key'];
                if (($s['action'] ?? null) === 'navigate' && ! empty($s['route'])) {
                    $s['url'] = route($s['route']);
                }
                return $s;
            })->values()
        : collect();
@endphp
<script>window.__SHORTCUTS__ = @json($resolvedShortcuts);</script>

{{-- Toast notifications, fired whenever a shortcut executes --}}
<div class="pointer-events-none fixed bottom-5 left-1/2 z-[200] flex -translate-x-1/2 flex-col items-center gap-2" x-data>
    <template x-for="t in $store.toast.items" :key="t.id">
        <div x-show="true" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-2 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="glass-strong rounded-full px-4 py-2 text-sm font-semibold text-strong shadow-lg" x-text="t.message"></div>
    </template>
</div>

@include('partials.shortcuts-help')
