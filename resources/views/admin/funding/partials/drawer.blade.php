{{-- ============ FUNDING DETAILS DRAWER ============ --}}
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
                            <span x-text="drawer.funding.reference"></span>
                            <span class="pill text-[10px] bg-slate-400/15 text-slate-600" x-text="drawer.funding.automation_type"></span>
                        </h2>
                        <p class="text-xs text-faint" x-text="drawer.funding.customer + ' · #' + drawer.funding.user_id"></p>
                    </div>
                    <button type="button" class="rounded-lg p-1.5 hover:surface-2" @click="drawerOpen=false"><x-icon name="x" class="h-4 w-4" /></button>
                </div>

                <template x-if="drawer.duplicates.length > 0">
                    <div class="rounded-xl bg-amber-500/10 p-3 text-xs text-amber-700">
                        <p class="font-semibold">Possible duplicate detected</p>
                        <template x-for="d in drawer.duplicates" :key="d.funding_request_id">
                            <p x-text="d.reference + ' — ' + d.match"></p>
                        </template>
                        <p class="mt-1 text-[11px]">Warning only — review carefully, this never auto-rejects.</p>
                    </div>
                </template>

                <template x-if="drawer.funding.risk_flagged">
                    <div class="rounded-xl bg-rose-500/10 p-3 text-xs text-rose-700">
                        <p class="font-semibold">Risk flagged</p>
                        <p x-text="drawer.funding.manual_review_reason"></p>
                        <template x-for="f in drawer.risk_flags" :key="f.rule">
                            <p x-text="f.rule + ' (' + f.severity + '): ' + f.reason"></p>
                        </template>
                    </div>
                </template>

                <template x-if="drawer.disputes.length > 0">
                    <div class="rounded-xl bg-purple-500/10 p-3 text-xs text-purple-700">
                        <p class="font-semibold">This request has related support disputes</p>
                        <template x-for="d in drawer.disputes" :key="d.id">
                            <p x-text="'Dispute #' + d.id + ' — ' + d.status"></p>
                        </template>
                    </div>
                </template>

                {{-- Identity & amounts --}}
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div><p class="text-xs text-faint">Provider reference</p><p class="text-body" x-text="drawer.funding.provider_reference || '—'"></p></div>
                    <div><p class="text-xs text-faint">Status</p><p class="text-body" x-text="drawer.funding.status_label"></p></div>
                    <div><p class="text-xs text-faint">Country</p><p class="text-body" x-text="drawer.funding.country || '—'"></p></div>
                    <div><p class="text-xs text-faint">Funding method</p><p class="text-body capitalize" x-text="drawer.funding.funding_source.replace('_',' ')"></p></div>
                    <div><p class="text-xs text-faint">Source amount</p><p class="text-body" x-text="drawer.funding.source_amount.toLocaleString() + ' ' + drawer.funding.source_currency"></p></div>
                    <div><p class="text-xs text-faint">Exchange rate</p><p class="text-body" x-text="drawer.funding.exchange_rate"></p></div>
                    <div><p class="text-xs text-faint">Fee</p><p class="text-body" x-text="drawer.funding.fee.toLocaleString() + ' ' + drawer.funding.source_currency"></p></div>
                    <div><p class="text-xs text-faint">Total wallet deduction</p><p class="text-body font-semibold" x-text="drawer.funding.total_charged.toLocaleString() + ' ' + drawer.funding.source_currency"></p></div>
                    <div><p class="text-xs text-faint">CNY requested / delivered</p><p class="text-body font-semibold" x-text="drawer.funding.target_amount.toLocaleString() + ' ' + drawer.funding.target_currency"></p></div>
                    <div><p class="text-xs text-faint">Related deposit</p><p class="text-body" x-text="drawer.funding.deposit_reference || 'Wallet balance (no linked deposit)'"></p></div>
                    <div><p class="text-xs text-faint">Created / Completed</p><p class="text-body" x-text="drawer.funding.created + (drawer.funding.processed ? ' / ' + drawer.funding.processed : '')"></p></div>
                    <div><p class="text-xs text-faint">Assigned reviewer</p><p class="text-body" x-text="drawer.funding.assigned_to_name || 'Unassigned'"></p></div>
                </div>

                {{-- Recipient verification --}}
                <div class="border-t border-app pt-3">
                    <p class="text-xs font-semibold uppercase text-faint">Recipient verification</p>
                    <div class="mt-2 grid grid-cols-2 gap-3 text-sm">
                        <div><p class="text-xs text-faint">China wallet type</p><p class="text-body" x-text="drawer.recipient.app_type || '—'"></p></div>
                        <div><p class="text-xs text-faint">Recipient name</p><p class="text-body" x-text="drawer.funding.recipient_name"></p></div>
                        <div><p class="text-xs text-faint">Wallet identifier</p><p class="text-body font-mono" x-text="drawer.funding.recipient_masked"></p></div>
                        <div><p class="text-xs text-faint">Recipient account status</p><p class="text-body capitalize" x-text="drawer.recipient.status || 'Unknown'"></p></div>
                        <div><p class="text-xs text-faint">Previous successful funding</p><p class="text-body" x-text="drawer.recipient.previous_successful + ' request(s)'"></p></div>
                        <div><p class="text-xs text-faint">Last successful funding</p><p class="text-body" x-text="drawer.recipient.last_successful || '—'"></p></div>
                    </div>
                    <template x-if="drawer.recipient.status !== 'approved'">
                        <p class="mt-2 rounded-lg bg-rose-500/10 px-2 py-1.5 text-xs text-rose-600">This recipient account is not currently approved — funding to it should not be completed.</p>
                    </template>
                </div>

                {{-- Limits & compliance --}}
                <div class="border-t border-app pt-3">
                    <p class="text-xs font-semibold uppercase text-faint">Limits &amp; compliance</p>
                    <div class="mt-2 grid grid-cols-2 gap-2 text-xs">
                        <div class="rounded-lg surface-2 p-2"><p class="text-faint">Per-transaction limit</p><p class="font-semibold text-body" x-text="drawer.limits.per_transaction !== null ? drawer.limits.per_transaction.toLocaleString() : 'No limit set'"></p></div>
                        <div class="rounded-lg surface-2 p-2"><p class="text-faint">KYC level</p><p class="font-semibold text-body" x-text="'Level ' + drawer.limits.kyc_level"></p></div>
                        <div class="rounded-lg surface-2 p-2"><p class="text-faint">Daily used / limit</p><p class="font-semibold text-body" x-text="drawer.limits.daily_used.toLocaleString() + ' / ' + (drawer.limits.daily_limit !== null ? drawer.limits.daily_limit.toLocaleString() : 'no limit')"></p></div>
                        <div class="rounded-lg surface-2 p-2"><p class="text-faint">Monthly used / limit</p><p class="font-semibold text-body" x-text="drawer.limits.monthly_used.toLocaleString() + ' / ' + (drawer.limits.monthly_limit !== null ? drawer.limits.monthly_limit.toLocaleString() : 'no limit')"></p></div>
                    </div>
                    <p class="mt-1 text-[11px] text-faint">Approximate location shown is the customer's declared country only — no IP or device data is captured for funding requests in this platform.</p>
                </div>

                {{-- Wallet credit control --}}
                <div class="border-t border-app pt-3">
                    <p class="text-xs font-semibold uppercase text-faint">Customer wallet ledger</p>
                    <div class="mt-2 space-y-1.5 text-xs">
                        <template x-if="drawer.wallet_transactions.length === 0"><p class="text-faint">No wallet ledger entries yet.</p></template>
                        <template x-for="t in drawer.wallet_transactions" :key="t.reference">
                            <div class="flex items-center justify-between rounded-lg surface-2 px-2 py-1.5">
                                <span class="text-body" x-text="t.reference + ' · ' + t.type"></span>
                                <span class="font-semibold text-body" x-text="t.amount.toLocaleString() + ' ' + t.currency + ' → ' + t.balance_after.toLocaleString()"></span>
                            </div>
                        </template>
                    </div>
                    <p class="mt-1 text-[11px] text-faint">This platform debits the wallet immediately at request creation rather than reserving funds — <span x-text="'WalletService::hold()/release() are defined but not used in this flow.'"></span></p>
                </div>

                {{-- Automated processing panel --}}
                <template x-if="drawer.funding.funding_source === 'wallet' || drawer.intents.length > 0">
                    <div class="border-t border-app pt-3">
                        <p class="text-xs font-semibold uppercase text-faint">Automated processing</p>
                        <div class="mt-2 space-y-1.5 text-xs">
                            <template x-if="drawer.intents.length === 0"><p class="text-faint">No payment intent recorded (this request was paid directly from the wallet).</p></template>
                            <template x-for="i in drawer.intents" :key="i.reference">
                                <div class="rounded-lg surface-2 p-2">
                                    <p class="text-body" x-text="i.reference + ' · ' + i.status + ' · attempt ' + i.attempts"></p>
                                    <p class="text-faint" x-text="'Provider ref: ' + (i.provider_reference || '—') + (i.last_error ? ' · Error: ' + i.last_error : '')"></p>
                                </div>
                            </template>
                            <template x-if="drawer.webhook_events.length > 0">
                                <div>
                                    <p class="mt-1 font-semibold text-body">Webhook history</p>
                                    <template x-for="w in drawer.webhook_events" :key="w.at + w.event_type">
                                        <div class="flex items-center justify-between rounded-lg surface-2 px-2 py-1.5">
                                            <span class="text-body" x-text="w.event_type + ' · signature ' + (w.signature_valid ? 'valid' : 'invalid')"></span>
                                            <span class="text-faint" x-text="w.status + ' · ' + w.at"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button type="button" class="qa-btn" @click="requery(drawer.funding.id)">Requery known state</button>
                        </div>
                        <p class="mt-1 text-[11px] text-faint">No funding driver in this platform exposes a live payout-status API — this refreshes what's already recorded from intents/webhooks. Outbound delivery-confirmation webhooks are not implemented yet (the receiver endpoint is a stub), so automated requests are confirmed via the synchronous provider response.</p>
                    </div>
                </template>

                {{-- Manual completion / proof of delivery --}}
                <div class="border-t border-app pt-3">
                    <p class="text-xs font-semibold uppercase text-faint">Proof of delivery</p>
                    <template x-if="drawer.funding.receipt_url">
                        <a :href="drawer.funding.receipt_url" target="_blank" class="qa-btn mt-2 inline-flex"><x-icon name="doc" class="h-3.5 w-3.5" /> View receipt / proof</a>
                    </template>
                    <template x-if="!drawer.funding.receipt_url"><p class="mt-2 text-xs text-faint">No proof of delivery uploaded.</p></template>
                    <template x-if="drawer.funding.notes"><p class="mt-2 text-xs text-body" x-text="drawer.funding.notes"></p></template>
                </div>

                {{-- Reconciliation --}}
                <div class="border-t border-app pt-3">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase text-faint">Reconciliation</p>
                        <span class="pill text-[10px]" x-text="drawer.funding.reconciliation_status.replace(/_/g,' ')"></span>
                    </div>
                    <form method="POST" :action="`/admin/funding/${drawer.funding.id}/reconcile`" class="mt-2 flex gap-2">
                        @csrf
                        <select name="status" class="field text-xs">
                            <option value="matched">Matched</option>
                            <option value="unmatched">Unmatched</option>
                            <option value="amount_mismatch">Amount mismatch</option>
                            <option value="recipient_mismatch">Recipient mismatch</option>
                            <option value="provider_pending">Provider pending</option>
                            <option value="manually_reconciled">Manually reconciled</option>
                            <option value="requires_investigation">Requires investigation</option>
                        </select>
                        <input name="note" class="field text-xs" placeholder="Note (optional)">
                        <button class="qa-btn">Save</button>
                    </form>
                    <p class="mt-1 text-[11px] text-faint">Computed proxy — no external settlement feed exists in this platform to diff against.</p>
                </div>

                {{-- Decision actions --}}
                <div class="flex flex-wrap gap-2 border-t border-app pt-3">
                    <template x-if="!drawer.funding.is_settled">
                        <span class="flex flex-wrap gap-2">
                            <button type="button" class="qa-btn qa-btn-good" @click="completeTarget=drawer.funding.id; completeModal=true">Mark completed</button>
                            <button type="button" class="qa-btn" @click="retry(drawer.funding.id)">Retry / start processing</button>
                            <button type="button" class="qa-btn" @click="placeUnderReview(drawer.funding.id)">Place under review</button>
                            <button type="button" class="qa-btn" @click="requestInfoTarget=drawer.funding.id; requestInfoModal=true">Request info</button>
                            <button type="button" class="qa-btn qa-btn-danger" @click="failTarget=drawer.funding.id; failModal=true">Mark failed</button>
                            <button type="button" class="qa-btn" @click="cancelTarget=drawer.funding.id; cancelModal=true">Cancel</button>
                        </span>
                    </template>
                    <template x-if="drawer.funding.can_refund">
                        <button type="button" class="qa-btn" style="color:#0d9488" @click="refundTarget=drawer.funding.id; refundModal=true">Refund</button>
                    </template>
                    <button type="button" class="qa-btn qa-btn-warn" @click="escalateTarget=drawer.funding.id; escalateModal=true">Escalate</button>
                    <button type="button" class="qa-btn" @click="investigate(drawer.funding.id)">Mark for investigation</button>
                    <button type="button" class="qa-btn" @click="noteTarget=drawer.funding.id; noteModal=true">Add note</button>
                </div>

                {{-- Assignment --}}
                <div class="border-t border-app pt-3">
                    <p class="text-xs font-semibold uppercase text-faint">Assigned reviewer</p>
                    <form method="POST" :action="`/admin/funding/${drawer.funding.id}/assign`" class="mt-2 flex gap-2">
                        @csrf
                        <select name="reviewer_id" class="field text-xs" onchange="this.form.submit()">
                            <option value="">Unassigned</option>
                            @foreach ($reviewers as $r)<option value="{{ $r->id }}" x-bind:selected="drawer.funding.assigned_to === {{ $r->id }}">{{ $r->name }}</option>@endforeach
                        </select>
                    </form>
                </div>

                {{-- Timeline --}}
                <div class="border-t border-app pt-3">
                    <p class="text-xs font-semibold uppercase text-faint">Funding timeline</p>
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
                    <p class="mt-2 text-sm text-body" x-text="drawer.funding.admin_notes || 'No notes yet.'"></p>
                </div>
            </div>
        </template>
    </div>
</div>

<form method="POST" :action="`/admin/funding/${requestInfoTarget}/request-info`" x-show="requestInfoModal" x-cloak @click.self="requestInfoModal=false" class="fixed inset-0 z-[60] grid place-items-center bg-black/50 p-4" style="display:none">
    @csrf
    <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
        <h3 class="font-semibold text-strong">Request more information</h3>
        <textarea name="message" required rows="3" class="field mt-3" placeholder="What do you need from the customer?"></textarea>
        <div class="mt-4 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="requestInfoModal=false">Cancel</button><button class="btn btn-primary flex-1">Send request</button></div>
    </div>
</form>

<form method="POST" :action="`/admin/funding/${escalateTarget}/escalate`" x-show="escalateModal" x-cloak @click.self="escalateModal=false" class="fixed inset-0 z-[60] grid place-items-center bg-black/50 p-4" style="display:none">
    @csrf
    <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
        <h3 class="font-semibold text-amber-600">Escalate funding request</h3>
        <textarea name="reason" required rows="3" class="field mt-3" placeholder="Reason for escalation"></textarea>
        <div class="mt-4 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="escalateModal=false">Cancel</button><button class="btn btn-primary flex-1">Escalate</button></div>
    </div>
</form>
