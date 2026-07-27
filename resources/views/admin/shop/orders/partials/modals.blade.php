{{-- ============ ORDER QUICK VIEW DRAWER ============ --}}
<div x-show="drawerOpen" x-cloak class="fixed inset-0 z-50 flex justify-end bg-black/50">
    <div class="h-full w-full max-w-2xl overflow-y-auto card-solid border-l border-app p-6" @click.outside="drawerOpen=false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
        <template x-if="!drawer">
            <div class="space-y-3"><div class="skel-block h-8 w-40"></div><div class="skel-block h-24"></div><div class="skel-block h-24"></div></div>
        </template>
        <template x-if="drawer">
            <div class="space-y-5">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="flex items-center gap-2 text-lg font-bold text-strong">
                            <span x-text="drawer.order.reference"></span>
                            <span class="pill text-[10px]" x-text="drawer.order.status_label"></span>
                        </h2>
                        <p class="text-xs text-faint" x-text="drawer.order.customer + ' · ' + drawer.order.email"></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a :href="`/admin/shop/orders/${drawer.order.id}`" class="qa-btn">Open workspace</a>
                        <button type="button" class="rounded-lg p-1.5 hover:surface-2" @click="drawerOpen=false"><x-icon name="x" class="h-4 w-4" /></button>
                    </div>
                </div>

                <template x-if="drawer.order.risk_flagged">
                    <div class="rounded-xl bg-rose-500/10 p-3 text-xs text-rose-700">
                        <p class="font-semibold">Flagged for review</p>
                        <p x-text="drawer.order.manual_review_reason"></p>
                        <p class="mt-1 text-[11px]">Warning only — review carefully, this never auto-cancels the order.</p>
                    </div>
                </template>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div><p class="text-xs text-faint">Total</p><p class="font-semibold text-body" x-text="drawer.order.total.toLocaleString() + ' ' + drawer.order.currency"></p></div>
                    <div><p class="text-xs text-faint">Fee</p><p class="text-body" x-text="drawer.order.fee.toLocaleString() + ' ' + drawer.order.currency"></p></div>
                    <div><p class="text-xs text-faint">Payment source</p><p class="capitalize text-body" x-text="drawer.order.payment_source"></p></div>
                    <div><p class="text-xs text-faint">Paid at</p><p class="text-body" x-text="drawer.order.paid_at || '—'"></p></div>
                    <div><p class="text-xs text-faint">Tracking</p><p class="text-body" x-text="(drawer.order.tracking_number || '—') + (drawer.order.carrier ? ' · ' + drawer.order.carrier : '')"></p></div>
                    <div><p class="text-xs text-faint">Assigned staff</p><p class="text-body" x-text="drawer.order.assigned_to_name || 'Unassigned'"></p></div>
                    <div><p class="text-xs text-faint">Refundable amount</p><p class="text-body" x-text="drawer.order.refundable_amount.toLocaleString() + ' ' + drawer.order.currency"></p></div>
                    <div><p class="text-xs text-faint">Already refunded</p><p class="text-body" x-text="drawer.order.total_refunded.toLocaleString() + ' ' + drawer.order.currency"></p></div>
                </div>

                {{-- Items --}}
                <div class="border-t border-app pt-3">
                    <p class="text-xs font-semibold uppercase text-faint">Items</p>
                    <div class="mt-2 space-y-1.5">
                        <template x-for="i in drawer.items" :key="i.id">
                            <div class="rounded-lg surface-2 p-2 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-body" x-text="i.name + ' ×' + i.quantity"></span>
                                    <span class="pill text-[10px]" x-text="i.status"></span>
                                </div>
                                <p class="mt-0.5 text-faint" x-text="i.unit_price.toLocaleString() + ' each · ' + i.line_total.toLocaleString() + ' total'"></p>
                                <template x-if="i.delivered && i.delivered.length">
                                    <p class="mt-1 text-faint">Delivered: <span class="font-mono">••••••••</span> (masked)</p>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-wrap gap-2 border-t border-app pt-3">
                    <form method="POST" :action="`/admin/shop/orders/${drawer.order.id}/start-processing`" x-show="drawer.order.status === 'paid'"><button class="qa-btn">Start processing</button></form>
                    <button type="button" class="qa-btn" x-show="['paid','processing'].includes(drawer.order.status)" @click="shipModal = true">Mark shipped</button>
                    <form method="POST" :action="`/admin/shop/orders/${drawer.order.id}/mark-delivered`" x-show="drawer.order.status === 'shipped'"><button class="qa-btn qa-btn-good">Mark delivered</button></form>
                    <form method="POST" :action="`/admin/shop/orders/${drawer.order.id}/resend-delivery`" x-show="['paid','processing','fulfilled','delivered'].includes(drawer.order.status)"><button class="qa-btn">Resend digital delivery</button></form>
                    <button type="button" class="qa-btn" @click="assignModal = true">Assign staff</button>
                    <button type="button" class="qa-btn" @click="noteModal = true">Add note</button>
                    <button type="button" class="qa-btn qa-btn-danger" x-show="drawer.order.can_cancel" @click="cancelModal = true">Cancel order</button>
                    <button type="button" class="qa-btn" style="color:#7c3aed" x-show="drawer.order.can_refund" @click="refundModal = true">Refund</button>
                </div>

                {{-- Timeline --}}
                <div class="border-t border-app pt-3">
                    <p class="text-xs font-semibold uppercase text-faint">Order timeline</p>
                    <div class="mu-timeline mt-2">
                        <template x-for="e in drawer.events" :key="e.at + e.event">
                            <div class="mu-timeline-item">
                                <span class="mu-timeline-dot"></span>
                                <p class="text-sm font-medium capitalize text-body" x-text="e.event.replace(/_/g,' ') + (e.from_status && e.to_status && e.from_status !== e.to_status ? ' (' + e.from_status + ' → ' + e.to_status + ')' : '')"></p>
                                <p class="text-xs text-muted" x-text="e.reason"></p>
                                <p class="text-xs text-faint" x-text="e.actor + ' · ' + e.at"></p>
                            </div>
                        </template>
                    </div>
                </div>

                <template x-if="drawer.refunds.length">
                    <div class="border-t border-app pt-3">
                        <p class="text-xs font-semibold uppercase text-faint">Refunds</p>
                        <div class="mt-2 space-y-1.5">
                            <template x-for="r in drawer.refunds" :key="r.at">
                                <div class="flex items-center justify-between rounded-lg surface-2 px-2 py-1.5 text-xs">
                                    <span class="text-body" x-text="r.amount.toLocaleString() + ' ' + drawer.order.currency + ' · ' + r.reason"></span>
                                    <span class="text-faint" x-text="r.status + ' · ' + r.at"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <template x-if="drawer.order.admin_notes">
                    <div class="border-t border-app pt-3"><p class="text-xs font-semibold uppercase text-faint">Internal notes</p><p class="mt-1 whitespace-pre-line text-sm text-body" x-text="drawer.order.admin_notes"></p></div>
                </template>
            </div>
        </template>
    </div>
</div>

{{-- ============ SHIP MODAL ============ --}}
<form method="POST" :action="`/admin/shop/orders/${drawer?.order?.id}/mark-shipped`" x-show="shipModal" x-cloak @click.self="shipModal=false" class="fixed inset-0 z-[60] grid place-items-center bg-black/50 p-4" style="display:none">
    @csrf
    <div class="card-solid w-full max-w-sm rounded-2xl border border-app p-6" @click.stop>
        <h3 class="font-semibold text-strong">Add tracking &amp; mark shipped</h3>
        <div class="mt-3"><label class="label">Tracking number</label><input name="tracking_number" required class="field"></div>
        <div class="mt-2"><label class="label">Carrier (optional)</label><input name="carrier" class="field"></div>
        <div class="mt-5 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="shipModal=false">Cancel</button><button class="btn btn-primary flex-1">Mark shipped</button></div>
    </div>
</form>

{{-- ============ ASSIGN MODAL ============ --}}
<form method="POST" :action="`/admin/shop/orders/${drawer?.order?.id}/assign`" x-show="assignModal" x-cloak @click.self="assignModal=false" class="fixed inset-0 z-[60] grid place-items-center bg-black/50 p-4" style="display:none">
    @csrf
    <div class="card-solid w-full max-w-sm rounded-2xl border border-app p-6" @click.stop>
        <h3 class="font-semibold text-strong">Assign staff</h3>
        <select name="staff_id" class="field mt-3">
            <option value="">Unassigned</option>
            @foreach ($staff as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
        </select>
        <div class="mt-5 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="assignModal=false">Cancel</button><button class="btn btn-primary flex-1">Save</button></div>
    </div>
</form>

{{-- ============ NOTE MODAL ============ --}}
<form method="POST" :action="`/admin/shop/orders/${drawer?.order?.id}/notes`" x-show="noteModal" x-cloak @click.self="noteModal=false" class="fixed inset-0 z-[60] grid place-items-center bg-black/50 p-4" style="display:none">
    @csrf
    <div class="card-solid w-full max-w-sm rounded-2xl border border-app p-6" @click.stop>
        <h3 class="font-semibold text-strong">Add internal note</h3>
        <textarea name="note" required rows="3" class="field mt-3" placeholder="Never shown to the customer"></textarea>
        <div class="mt-5 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="noteModal=false">Cancel</button><button class="btn btn-primary flex-1">Save note</button></div>
    </div>
</form>

{{-- ============ CANCEL MODAL ============ --}}
<form method="POST" :action="`/admin/shop/orders/${drawer?.order?.id}/cancel`" x-show="cancelModal" x-cloak @click.self="cancelModal=false" class="fixed inset-0 z-[60] grid place-items-center bg-black/50 p-4" style="display:none">
    @csrf
    <div class="card-solid w-full max-w-sm rounded-2xl border border-app p-6" @click.stop>
        <h3 class="font-semibold text-strong">Cancel order</h3>
        <p class="mt-1 text-xs text-muted">If this order was already paid, the amount is refunded to the customer's wallet automatically.</p>
        <textarea name="reason" required rows="3" class="field mt-3" placeholder="Cancellation reason"></textarea>
        <div class="mt-5 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="cancelModal=false">Back</button><button class="btn btn-danger flex-1">Confirm cancellation</button></div>
    </div>
</form>

{{-- ============ REFUND MODAL ============ --}}
<form method="POST" :action="`/admin/shop/orders/${drawer?.order?.id}/refund`" x-show="refundModal" x-cloak @click.self="refundModal=false" class="fixed inset-0 z-[60] grid place-items-center bg-black/50 p-4" style="display:none">
    @csrf
    <div class="card-solid w-full max-w-sm rounded-2xl border border-app p-6" @click.stop>
        <h3 class="font-semibold text-strong">Refund order</h3>
        <p class="mt-1 text-xs text-muted" x-text="'Refundable balance: ' + (drawer?.order?.refundable_amount ?? 0).toLocaleString() + ' ' + (drawer?.order?.currency ?? '')"></p>
        <div class="mt-3"><label class="label">Amount</label><input type="number" step="0.01" min="0.01" name="amount" :max="drawer?.order?.refundable_amount" :value="drawer?.order?.refundable_amount" required class="field"></div>
        <textarea name="reason" required rows="2" class="field mt-2" placeholder="Refund reason"></textarea>
        <div class="mt-5 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="refundModal=false">Cancel</button><button class="btn btn-danger flex-1">Confirm refund</button></div>
    </div>
</form>
