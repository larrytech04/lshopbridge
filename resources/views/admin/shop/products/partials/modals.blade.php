{{-- ============ PRODUCT QUICK VIEW DRAWER ============ --}}
<div x-show="drawerOpen" x-cloak class="fixed inset-0 z-50 flex justify-end bg-black/50">
    <div class="h-full w-full max-w-xl overflow-y-auto card-solid border-l border-app p-6" @click.outside="drawerOpen=false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
        <template x-if="!drawer">
            <div class="space-y-3"><div class="skel-block h-8 w-40"></div><div class="skel-block h-24"></div><div class="skel-block h-24"></div></div>
        </template>
        <template x-if="drawer">
            <div class="space-y-5">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-strong" x-text="drawer.product.name"></h2>
                        <p class="text-xs text-faint" x-text="drawer.product.type_label + ' · ' + (drawer.product.category || 'Uncategorized')"></p>
                    </div>
                    <button type="button" class="rounded-lg p-1.5 hover:surface-2" @click="drawerOpen=false"><x-icon name="x" class="h-4 w-4" /></button>
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div><p class="text-xs text-faint">Brand</p><p class="text-body" x-text="drawer.product.brand || '—'"></p></div>
                    <div><p class="text-xs text-faint">Supplier</p><p class="text-body" x-text="drawer.product.supplier || '—'"></p></div>
                    <div><p class="text-xs text-faint">Source</p><p class="capitalize text-body" x-text="drawer.product.source"></p></div>
                    <div><p class="text-xs text-faint">Status</p><p class="capitalize text-body" x-text="drawer.product.display_status"></p></div>
                    <div><p class="text-xs text-faint">Visibility</p><p class="text-body" x-text="drawer.product.is_active ? 'Visible' : 'Hidden'"></p></div>
                    <div><p class="text-xs text-faint">Units sold</p><p class="text-body" x-text="drawer.product.sales_count"></p></div>
                    <div><p class="text-xs text-faint">Provider status</p><p class="text-body" x-text="drawer.product.provider_status || 'Not provider-synced'"></p></div>
                    <div><p class="text-xs text-faint">Last synchronized</p><p class="text-body" x-text="drawer.product.last_synced_at || 'Never'"></p></div>
                </div>

                <template x-if="drawer.product.scheduled_publish_at">
                    <p class="rounded-lg bg-sky-500/10 px-3 py-2 text-xs text-sky-700" x-text="'Scheduled to publish: ' + drawer.product.scheduled_publish_at"></p>
                </template>

                {{-- Variants --}}
                <div class="border-t border-app pt-3">
                    <p class="text-xs font-semibold uppercase text-faint">Variants</p>
                    <div class="mt-2 space-y-1.5">
                        <template x-for="v in drawer.variants" :key="v.id">
                            <div class="rounded-lg surface-2 p-2 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-body" x-text="v.name + (v.sku ? ' · ' + v.sku : '')"></span>
                                    <span class="pill text-[10px]" x-text="v.is_active ? 'Active' : 'Inactive'"></span>
                                </div>
                                <div class="mt-1 flex flex-wrap gap-3 text-faint">
                                    <span x-text="'Price: ' + v.currency + ' ' + v.price"></span>
                                    <span x-show="v.cost_price !== null" x-text="'Cost: ' + v.currency + ' ' + v.cost_price"></span>
                                    <span x-show="v.profit_margin !== null" x-text="'Margin: ' + v.profit_margin + '%'"></span>
                                    <span x-text="'Stock: ' + (v.stock ?? 'Unlimited')"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 border-t border-app pt-3">
                    <a :href="`/admin/shop/products/${drawer.product.slug}/edit`" class="qa-btn">Edit</a>
                    <button type="button" class="qa-btn" @click="scheduleTarget=drawer.product.slug; scheduleModal=true">Schedule publish</button>
                </div>

                {{-- Recent orders --}}
                <div class="border-t border-app pt-3">
                    <p class="text-xs font-semibold uppercase text-faint">Recent orders</p>
                    <template x-if="drawer.recent_orders.length === 0"><p class="mt-2 text-xs text-faint">No orders yet for this product.</p></template>
                    <div class="mt-2 space-y-1.5">
                        <template x-for="o in drawer.recent_orders" :key="o.reference + o.at">
                            <div class="flex items-center justify-between rounded-lg surface-2 px-2 py-1.5 text-xs">
                                <span class="text-body" x-text="o.reference + ' · qty ' + o.quantity"></span>
                                <span class="text-faint" x-text="o.status + ' · ' + o.at"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <template x-if="drawer.product.admin_notes">
                    <div class="border-t border-app pt-3"><p class="text-xs font-semibold uppercase text-faint">Internal notes</p><p class="mt-1 whitespace-pre-line text-sm text-body" x-text="drawer.product.admin_notes"></p></div>
                </template>
            </div>
        </template>
    </div>
</div>

{{-- ============ SCHEDULE PUBLISH MODAL ============ --}}
<form method="POST" :action="`/admin/shop/products/${scheduleTarget}/schedule`" x-show="scheduleModal" x-cloak @click.self="scheduleModal=false" class="fixed inset-0 z-[60] grid place-items-center bg-black/50 p-4" style="display:none">
    @csrf
    <div class="card-solid w-full max-w-sm rounded-2xl border border-app p-6" @click.stop>
        <h3 class="font-semibold text-strong">Schedule publish</h3>
        <p class="mt-1 text-xs text-muted">The product stays a draft until this date/time, then goes active automatically the next time the page loads.</p>
        <div class="mt-3"><label class="label">Publish at</label><input type="datetime-local" name="publish_at" required class="field"></div>
        <div class="mt-5 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="scheduleModal=false">Cancel</button><button class="btn btn-primary flex-1">Schedule</button></div>
    </div>
</form>
