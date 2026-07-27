{{-- ============ DEPOSIT DETAILS DRAWER ============ --}}
<div x-show="drawerOpen" x-cloak class="fixed inset-0 z-50 flex justify-end bg-black/50">
    <div class="h-full w-full max-w-2xl overflow-y-auto card-solid border-l border-app p-6" @click.outside="drawerOpen=false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
        <template x-if="!drawer">
            <div class="space-y-3"><div class="skel-block h-8 w-40"></div><div class="skel-block h-24"></div><div class="skel-block h-24"></div><div class="skel-block h-24"></div></div>
        </template>
        <template x-if="drawer">
            <div class="space-y-5">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="flex items-center gap-2 text-lg font-bold text-strong">
                            <span x-text="drawer.deposit.reference"></span>
                            <span class="pill text-[10px]" :class="drawer.deposit.is_automated ? 'bg-sky-500/15 text-sky-600' : 'bg-slate-400/15 text-slate-600'" x-text="drawer.deposit.is_automated ? 'Auto' : 'Manual'"></span>
                        </h2>
                        <p class="text-xs text-faint" x-text="drawer.deposit.customer + ' · #' + drawer.deposit.user_id"></p>
                    </div>
                    <button type="button" class="rounded-lg p-1.5 hover:surface-2" @click="drawerOpen=false"><x-icon name="x" class="h-4 w-4" /></button>
                </div>

                <template x-if="drawer.duplicates.length > 0">
                    <div class="rounded-xl bg-amber-500/10 p-3 text-xs text-amber-700">
                        <p class="font-semibold">Possible duplicate detected</p>
                        <template x-for="d in drawer.duplicates" :key="d.deposit_id">
                            <p x-text="d.reference + ' — ' + d.match"></p>
                        </template>
                        <p class="mt-1 text-[11px]">Warning only — review carefully, this never auto-rejects.</p>
                    </div>
                </template>

                <template x-if="drawer.deposit.risk_flagged || drawer.risk_flags.length > 0">
                    <div class="rounded-xl bg-rose-500/10 p-3 text-xs text-rose-700">
                        <p class="font-semibold">Risk flagged</p>
                        <template x-for="f in drawer.risk_flags" :key="f.rule">
                            <p x-text="f.rule + ' (' + f.severity + '): ' + f.reason"></p>
                        </template>
                    </div>
                </template>

                <template x-if="drawer.disputes.length > 0">
                    <div class="rounded-xl bg-purple-500/10 p-3 text-xs text-purple-700">
                        <p class="font-semibold">This deposit has related support disputes</p>
                        <template x-for="d in drawer.disputes" :key="d.id">
                            <p x-text="'Dispute #' + d.id + ' — ' + d.status"></p>
                        </template>
                    </div>
                </template>

                {{-- Identity & amounts --}}
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div><p class="text-xs text-faint">Provider reference</p><p class="text-body" x-text="drawer.deposit.provider_reference || '—'"></p></div>
                    <div><p class="text-xs text-faint">Status</p><p class="text-body" x-text="drawer.deposit.status_label"></p></div>
                    <div><p class="text-xs text-faint">Country</p><p class="text-body" x-text="drawer.deposit.country || '—'"></p></div>
                    <div><p class="text-xs text-faint">Method / Provider</p><p class="text-body" x-text="(drawer.deposit.method || '—') + ' · ' + (drawer.deposit.provider_code || '—')"></p></div>
                    <div><p class="text-xs text-faint">Amount / Fee</p><p class="text-body" x-text="drawer.deposit.amount.toLocaleString() + ' ' + drawer.deposit.currency + ' / ' + drawer.deposit.fee.toLocaleString()"></p></div>
                    <div><p class="text-xs text-faint">Net wallet credit</p><p class="text-body font-semibold" x-text="drawer.deposit.net_amount.toLocaleString() + ' ' + drawer.deposit.currency"></p></div>
                    <div>
                        <p class="text-xs text-faint">Reporting-currency amount</p>
                        <p class="text-body" x-text="drawer.deposit.reporting_amount !== null ? drawer.deposit.reporting_amount.toLocaleString() + ' ' + drawer.deposit.base_currency : 'Not converted — no exchange rate recorded'"></p>
                    </div>
                    <div><p class="text-xs text-faint">Created / Confirmed</p><p class="text-body" x-text="drawer.deposit.created + (drawer.deposit.confirmed ? ' / ' + drawer.deposit.confirmed : '')"></p></div>
                    <div><p class="text-xs text-faint">Last updated</p><p class="text-body" x-text="drawer.deposit.updated"></p></div>
                    <div><p class="text-xs text-faint">Assigned reviewer</p><p class="text-body" x-text="drawer.deposit.assigned_to_name || 'Unassigned'"></p></div>
                </div>

                <p class="text-[11px] text-faint">IP address, device, and browser are not captured for deposits in this platform today — nothing to show honestly.</p>

                {{-- Wallet credit control --}}
                <div class="border-t border-app pt-3">
                    <p class="text-xs font-semibold uppercase text-faint">Wallet credit status</p>
                    <div class="mt-2 space-y-1.5 text-xs">
                        <template x-if="drawer.wallet_transactions.length === 0"><p class="text-faint">Not credited yet.</p></template>
                        <template x-for="t in drawer.wallet_transactions" :key="t.reference">
                            <div class="flex items-center justify-between rounded-lg surface-2 px-2 py-1.5">
                                <span class="text-body" x-text="t.reference + ' · ' + t.type"></span>
                                <span class="font-semibold text-body" x-text="t.amount.toLocaleString() + ' ' + t.currency + ' → ' + t.balance_after.toLocaleString()"></span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Automated deposit panel --}}
                <template x-if="drawer.deposit.is_automated">
                    <div class="border-t border-app pt-3">
                        <p class="text-xs font-semibold uppercase text-faint">Automated deposit</p>
                        <div class="mt-2 space-y-1.5 text-xs">
                            <template x-if="drawer.intents.length === 0"><p class="text-faint">No payment intent recorded.</p></template>
                            <template x-for="i in drawer.intents" :key="i.reference">
                                <div class="rounded-lg surface-2 p-2">
                                    <p class="text-body" x-text="i.reference + ' · ' + i.status + ' · attempt ' + i.attempts"></p>
                                    <p class="text-faint" x-text="'Provider ref: ' + (i.provider_reference || '—') + (i.last_error ? ' · Error: ' + i.last_error : '')"></p>
                                </div>
                            </template>
                            <p class="mt-1 font-semibold text-body">Webhook history</p>
                            <template x-if="drawer.webhook_events.length === 0"><p class="text-faint">No webhook events recorded.</p></template>
                            <template x-for="w in drawer.webhook_events" :key="w.at + w.event_type">
                                <div class="flex items-center justify-between rounded-lg surface-2 px-2 py-1.5">
                                    <span class="text-body" x-text="w.event_type + ' · signature ' + (w.signature_valid ? 'valid' : 'invalid')"></span>
                                    <span class="text-faint" x-text="w.status + ' · ' + w.at"></span>
                                </div>
                            </template>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button type="button" class="qa-btn" @click="requery(drawer.deposit.id)">Requery known state</button>
                        </div>
                        <p class="mt-1 text-[11px] text-faint">No payment driver in this platform exposes a live provider status API — this refreshes what the platform already recorded from intents/webhooks, not a live call.</p>
                    </div>
                </template>

                {{-- Manual deposit review panel --}}
                <template x-if="!drawer.deposit.is_automated">
                    <div class="border-t border-app pt-3">
                        <p class="text-xs font-semibold uppercase text-faint">Manual deposit review</p>
                        <div class="mt-2 grid grid-cols-2 gap-2 text-xs">
                            <div><p class="text-faint">Declared amount</p><p class="text-body" x-text="drawer.deposit.amount.toLocaleString() + ' ' + drawer.deposit.currency"></p></div>
                            <div><p class="text-faint">Payer details</p><p class="text-body" x-text="drawer.deposit.payer_details ? JSON.stringify(drawer.deposit.payer_details) : 'Not provided'"></p></div>
                        </div>
                        <template x-if="drawer.deposit.proof_url">
                            <a :href="drawer.deposit.proof_url" target="_blank" class="qa-btn mt-2 inline-flex"><x-icon name="doc" class="h-3.5 w-3.5" /> View uploaded receipt</a>
                        </template>
                        <template x-if="!drawer.deposit.proof_url"><p class="mt-2 text-xs text-faint">No proof of payment uploaded.</p></template>
                    </div>
                </template>

                {{-- Reconciliation --}}
                <div class="border-t border-app pt-3">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase text-faint">Reconciliation</p>
                        <span class="pill text-[10px]" x-text="drawer.deposit.reconciliation_status.replace(/_/g,' ')"></span>
                    </div>
                    <form method="POST" :action="`/admin/deposits/${drawer.deposit.id}/reconcile`" class="mt-2 flex gap-2">
                        @csrf
                        <select name="status" class="field text-xs">
                            <option value="matched">Matched</option>
                            <option value="unmatched">Unmatched</option>
                            <option value="amount_mismatch">Amount mismatch</option>
                            <option value="provider_pending">Provider pending</option>
                            <option value="manually_reconciled">Manually reconciled</option>
                            <option value="requires_investigation">Requires investigation</option>
                        </select>
                        <input name="note" class="field text-xs" placeholder="Note (optional)">
                        <button class="qa-btn">Save</button>
                    </form>
                    <p class="mt-1 text-[11px] text-faint">Computed proxy — no external bank/settlement feed exists in this platform to diff against.</p>
                </div>

                {{-- Decision actions --}}
                <div class="flex flex-wrap gap-2 border-t border-app pt-3">
                    <template x-if="!drawer.deposit.is_settled">
                        <span class="flex flex-wrap gap-2">
                            <button type="button" class="qa-btn qa-btn-good" @click="approve(drawer.deposit.id)">Confirm</button>
                            <button type="button" class="qa-btn" @click="placeUnderReview(drawer.deposit.id)">Place under review</button>
                            <button type="button" class="qa-btn" @click="requestInfoTarget=drawer.deposit.id; requestInfoModal=true">Request info</button>
                            <button type="button" class="qa-btn qa-btn-danger" @click="rejectTarget=drawer.deposit.id; rejectModal=true">Reject</button>
                        </span>
                    </template>
                    <template x-if="drawer.deposit.can_refund_or_reverse">
                        <span class="flex flex-wrap gap-2">
                            <button type="button" class="qa-btn" style="color:#0d9488" @click="refundTarget=drawer.deposit.id; refundModal=true">Refund</button>
                            <button type="button" class="qa-btn" style="color:#9333ea" @click="reverseTarget=drawer.deposit.id; reverseModal=true">Reverse</button>
                        </span>
                    </template>
                    <button type="button" class="qa-btn qa-btn-warn" @click="escalateTarget=drawer.deposit.id; escalateModal=true">Escalate</button>
                    <button type="button" class="qa-btn" @click="investigate(drawer.deposit.id)">Mark for investigation</button>
                    <button type="button" class="qa-btn" @click="noteTarget=drawer.deposit.id; noteModal=true">Add note</button>
                </div>

                {{-- Assignment --}}
                <div class="border-t border-app pt-3">
                    <p class="text-xs font-semibold uppercase text-faint">Assigned reviewer</p>
                    <form method="POST" :action="`/admin/deposits/${drawer.deposit.id}/assign`" class="mt-2 flex gap-2">
                        @csrf
                        <select name="reviewer_id" class="field text-xs" onchange="this.form.submit()">
                            <option value="">Unassigned</option>
                            @foreach ($reviewers as $r)<option value="{{ $r->id }}" x-bind:selected="drawer.deposit.assigned_to === {{ $r->id }}">{{ $r->name }}</option>@endforeach
                        </select>
                    </form>
                </div>

                {{-- Timeline --}}
                <div class="border-t border-app pt-3">
                    <p class="text-xs font-semibold uppercase text-faint">Deposit timeline</p>
                    <div class="mu-timeline mt-2">
                        <template x-for="e in drawer.events" :key="e.at + e.event">
                            <div class="mu-timeline-item">
                                <span class="mu-timeline-dot"></span>
                                <p class="text-sm font-medium capitalize text-body" x-text="e.event.replace(/_/g,' ') + (e.from && e.to && e.from !== e.to ? ' (' + e.from + ' → ' + e.to + ')' : '')"></p>
                                <p class="text-xs text-muted" x-text="e.reason"></p>
                                <p class="text-xs text-faint" x-text="e.actor + ' · ' + e.at"></p>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="border-t border-app pt-3">
                    <p class="text-xs font-semibold uppercase text-faint">Internal notes (private, never shown to the customer)</p>
                    <p class="mt-2 text-sm text-body" x-text="drawer.deposit.admin_notes || 'No notes yet.'"></p>
                </div>
            </div>
        </template>
    </div>
</div>

<form method="POST" :action="`/admin/deposits/${requestInfoTarget}/request-info`" x-show="requestInfoModal" x-cloak @click.self="requestInfoModal=false" class="fixed inset-0 z-[60] grid place-items-center bg-black/50 p-4" style="display:none">
    @csrf
    <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
        <h3 class="font-semibold text-strong">Request more information</h3>
        <textarea name="message" required rows="3" class="field mt-3" placeholder="What do you need from the customer?"></textarea>
        <div class="mt-4 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="requestInfoModal=false">Cancel</button><button class="btn btn-primary flex-1">Send request</button></div>
    </div>
</form>

<form method="POST" :action="`/admin/deposits/${escalateTarget}/escalate`" x-show="escalateModal" x-cloak @click.self="escalateModal=false" class="fixed inset-0 z-[60] grid place-items-center bg-black/50 p-4" style="display:none">
    @csrf
    <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
        <h3 class="font-semibold text-amber-600">Escalate deposit</h3>
        <textarea name="reason" required rows="3" class="field mt-3" placeholder="Reason for escalation"></textarea>
        <div class="mt-4 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="escalateModal=false">Cancel</button><button class="btn btn-primary flex-1">Escalate</button></div>
    </div>
</form>
