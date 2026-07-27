{{-- Transaction detail drawer (AJAX, no full page reload). --}}
<div x-show="drawerOpen" x-cloak class="fixed inset-0 z-50 flex justify-end bg-black/50" style="display:none">
    <div class="absolute inset-0" @click="drawerOpen = false"></div>
    <div class="card-solid relative h-full w-full max-w-md overflow-y-auto border-l border-app p-6 shadow-2xl"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-strong">Transaction detail</h3>
            <button type="button" @click="drawerOpen = false" class="grid h-8 w-8 place-items-center rounded-full hover:surface-2"><x-icon name="x" class="h-4 w-4" /></button>
        </div>
        <template x-if="drawerLoading"><div class="mt-6 animate-pulse space-y-3"><div class="h-4 w-1/2 rounded surface-2"></div><div class="h-4 w-3/4 rounded surface-2"></div><div class="h-4 w-2/3 rounded surface-2"></div></div></template>
        <template x-if="!drawerLoading && drawerData && !drawerData.error">
            <div class="mt-5 space-y-4 text-sm">
                <div><p class="text-xs text-faint">Reference</p><p class="font-semibold text-strong" x-text="drawerData.reference"></p></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><p class="text-xs text-faint">Customer</p><p class="text-body" x-text="drawerData.user"></p></div>
                    <div><p class="text-xs text-faint">Email</p><p class="text-body" x-text="drawerData.email"></p></div>
                    <div><p class="text-xs text-faint">Country</p><p class="text-body" x-text="drawerData.country || '—'"></p></div>
                    <div><p class="text-xs text-faint">Status</p><p class="text-body" x-text="drawerData.status"></p></div>
                    <div><p class="text-xs text-faint">Amount</p><p class="font-semibold text-strong" x-text="drawerData.amount + ' ' + drawerData.currency"></p></div>
                    <div><p class="text-xs text-faint">Date</p><p class="text-body" x-text="drawerData.created_at"></p></div>
                    <div x-show="drawerData.provider"><p class="text-xs text-faint">Provider</p><p class="text-body" x-text="drawerData.provider"></p></div>
                    <div x-show="drawerData.provider_reference"><p class="text-xs text-faint">Provider reference</p><p class="text-body" x-text="drawerData.provider_reference"></p></div>
                </div>
            </div>
        </template>
        <template x-if="!drawerLoading && drawerData && drawerData.error"><p class="mt-6 text-sm text-rose-600">Couldn't load this transaction.</p></template>
    </div>
</div>
