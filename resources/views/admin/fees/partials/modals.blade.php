{{-- ============ FEE DETAILS DRAWER (details + tiers + history + schedules) ============ --}}
<div x-show="drawerOpen" x-cloak class="fixed inset-0 z-50 flex justify-end bg-black/50">
    <div class="h-full w-full max-w-xl overflow-y-auto card-solid border-l border-app p-6" @click.outside="drawerOpen=false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
        <template x-if="!drawer">
            <div class="space-y-3"><div class="skel-block h-8 w-40"></div><div class="skel-block h-24"></div><div class="skel-block h-24"></div></div>
        </template>
        <template x-if="drawer">
            <div class="space-y-5">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-strong" x-text="drawer.fee.name"></h2>
                        <p class="text-xs text-faint" x-text="(drawer.fee.code || 'No code') + ' · Updated by ' + (drawer.fee.updated_by || 'system') + ' · ' + drawer.fee.updated_at"></p>
                    </div>
                    <button type="button" class="rounded-lg p-1.5 hover:surface-2" @click="drawerOpen=false"><x-icon name="x" class="h-4 w-4" /></button>
                </div>

                <template x-if="drawer.fee.description"><p class="text-sm text-body" x-text="drawer.fee.description"></p></template>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div><p class="text-xs text-faint">Applies to</p><p class="text-body" x-text="drawer.fee.applies_to"></p></div>
                    <div><p class="text-xs text-faint">Fee type</p><p class="text-body" x-text="drawer.fee.type"></p></div>
                    <div><p class="text-xs text-faint">Value</p><p class="font-mono text-body" x-text="drawer.fee.value"></p></div>
                    <div><p class="text-xs text-faint">Fixed value</p><p class="font-mono text-body" x-text="drawer.fee.fixed_value ?? '—'"></p></div>
                    <div><p class="text-xs text-faint">Min / max fee</p><p class="text-body" x-text="drawer.fee.min_fee + ' – ' + (drawer.fee.max_fee ?? 'no max')"></p></div>
                    <div><p class="text-xs text-faint">Min / max transaction amount</p><p class="text-body" x-text="(drawer.fee.min_amount ?? 'no min') + ' – ' + (drawer.fee.max_amount ?? 'no max')"></p></div>
                    <div><p class="text-xs text-faint">Currency</p><p class="text-body" x-text="drawer.fee.currency || 'Any'"></p></div>
                    <div><p class="text-xs text-faint">Country</p><p class="text-body" x-text="drawer.fee.country || 'Any'"></p></div>
                    <div><p class="text-xs text-faint">Payment method / provider</p><p class="text-body" x-text="(drawer.fee.scope || '—') + ' / ' + (drawer.fee.payment_provider || '—')"></p></div>
                    <div><p class="text-xs text-faint">China wallet type</p><p class="text-body" x-text="drawer.fee.china_wallet_type || 'Any'"></p></div>
                    <div><p class="text-xs text-faint">Customer role / KYC level</p><p class="text-body" x-text="(drawer.fee.customer_role || 'Any') + ' / ' + (drawer.fee.kyc_level ?? 'Any')"></p></div>
                    <div><p class="text-xs text-faint">Fee payer</p><p class="capitalize text-body" x-text="drawer.fee.fee_payer"></p></div>
                    <div><p class="text-xs text-faint">Priority (lower = evaluated first)</p><p class="text-body" x-text="drawer.fee.sort"></p></div>
                    <div><p class="text-xs text-faint">Status</p><p class="text-body" x-text="drawer.fee.status_label"></p></div>
                </div>

                <p class="rounded-lg surface-2 px-3 py-2 text-xs text-body" x-text="
                    drawer.fee.applies_to === 'funding' ? 'Applied as: added on top of the amount the customer sends.'
                    : drawer.fee.applies_to === 'deposit' ? 'Applied as: deducted from the deposited amount before crediting the wallet.'
                    : 'Not yet wired to a live transaction flow in this codebase — configured for reference / future use.'
                "></p>

                <template x-if="drawer.fee.type === 'provider_passed'">
                    <p class="rounded-lg bg-amber-500/10 px-3 py-2 text-xs text-amber-700">No payment provider in this platform exposes real-time fee data — this value is a manually configured estimate, not a live provider fee.</p>
                </template>

                {{-- Tiers --}}
                <template x-if="drawer.fee.type === 'tiered'">
                    <div class="border-t border-app pt-3">
                        <p class="text-xs font-semibold uppercase text-faint">Tiers</p>
                        <div class="mt-2 space-y-1.5">
                            <template x-for="(t, i) in drawer.tiers" :key="i">
                                <div class="flex items-center justify-between rounded-lg surface-2 px-2 py-1.5 text-xs">
                                    <span class="text-body" x-text="t.min_amount + ' – ' + (t.max_amount ?? '∞')"></span>
                                    <span class="font-mono text-body" x-text="t.percent + '% + ' + t.fixed"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <div class="flex flex-wrap gap-2 border-t border-app pt-3">
                    <button type="button" class="qa-btn" @click="drawerOpen=false; openEdit(drawer.fee.id)">Edit fee</button>
                    <button type="button" class="qa-btn" @click="testTarget=drawer.fee.id; testOpen=true">Test fee</button>
                    <button type="button" class="qa-btn" @click="scheduleTarget=drawer.fee.id; scheduleModal=true">Schedule change</button>
                </div>

                {{-- Scheduled changes --}}
                <div class="border-t border-app pt-3">
                    <p class="text-xs font-semibold uppercase text-faint">Scheduled changes</p>
                    <template x-if="drawer.schedules.length === 0"><p class="mt-2 text-xs text-faint">No scheduled changes for this fee.</p></template>
                    <div class="mt-2 space-y-1.5">
                        <template x-for="s in drawer.schedules" :key="s.id">
                            <div class="rounded-lg surface-2 p-2 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-body" x-text="'New value ' + (s.new_value ?? '—') + ' from ' + s.effective_start_date + (s.effective_end_date ? ' to ' + s.effective_end_date : '')"></span>
                                    <span class="pill text-[10px]" x-text="s.status"></span>
                                </div>
                                <p class="mt-0.5 text-faint" x-text="(s.reason || '—') + ' · ' + (s.created_by || 'system')"></p>
                                <form method="POST" :action="`/admin/fees/schedules/${s.id}/cancel`" class="mt-1" x-show="s.status === 'scheduled'">
                                    @csrf
                                    <button class="text-rose-500 hover:underline">Cancel this schedule</button>
                                </form>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- History --}}
                <div class="border-t border-app pt-3">
                    <p class="text-xs font-semibold uppercase text-faint">Fee history</p>
                    <p class="mt-1 text-[11px] text-faint">Immutable audit trail — every change is recorded, never overwritten or deleted.</p>
                    <div class="mu-timeline mt-2">
                        <template x-for="h in drawer.history" :key="h.at + h.event">
                            <div class="mu-timeline-item">
                                <span class="mu-timeline-dot"></span>
                                <p class="text-sm font-medium capitalize text-body" x-text="h.event.replace(/_/g,' ') + ' — value ' + h.value"></p>
                                <p class="text-xs text-muted" x-text="'Type ' + h.type + ' · min ' + h.min_fee + ' · max ' + (h.max_fee ?? 'none') + (h.reason ? ' · ' + h.reason : '')"></p>
                                <p class="text-xs text-faint" x-text="h.changed_by + ' · ' + h.at"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

{{-- ============ ADD / EDIT FEE MODAL ============ --}}
<form method="POST" :action="formAction()" x-show="formOpen" x-cloak @click.self="formOpen=false" class="fixed inset-0 z-[60] grid place-items-center bg-black/50 p-4" style="display:none">
    @csrf
    <input type="hidden" name="_method" :value="formMode === 'edit' ? 'PUT' : 'POST'">
    <div class="card-solid max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-app p-6" @click.stop>
        <h3 class="font-semibold text-strong" x-text="formMode === 'add' ? 'Add fee' : 'Edit fee'"></h3>

        <template x-if="formErrors">
            <ul class="mt-3 space-y-1 rounded-lg bg-rose-500/10 px-3 py-2 text-xs text-rose-600">
                <template x-for="(msg, key) in formErrors" :key="key"><li x-text="msg"></li></template>
            </ul>
        </template>
        <template x-if="formWarnings && formWarnings.length">
            <div class="mt-3 space-y-1 rounded-lg bg-amber-500/10 px-3 py-2 text-xs text-amber-700">
                <template x-for="w in formWarnings" :key="w"><p x-text="w"></p></template>
                <label class="mt-1 flex items-center gap-2"><input type="checkbox" name="confirmed" value="1" class="rounded"> I've reviewed this change and want to proceed anyway.</label>
            </div>
        </template>

        <div class="mt-4 grid grid-cols-2 gap-3">
            <div><label class="label">Fee name</label><input name="name" x-model="form.name" required class="field"></div>
            <div><label class="label">Fee code</label><input name="code" x-model="form.code" class="field" placeholder="Optional, unique"></div>
            <div class="col-span-2"><label class="label">Description</label><textarea name="description" x-model="form.description" rows="2" class="field"></textarea></div>

            <div><label class="label">Applies to</label>
                <select name="applies_to" x-model="form.applies_to" class="field">
                    @foreach ($categories as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    <option value="all">All categories</option>
                </select>
            </div>
            <div><label class="label">Fee type</label>
                <select name="type" x-model="form.type" class="field">
                    @foreach ($feeTypes as $t)<option value="{{ $t->value }}">{{ $t->label() }}</option>@endforeach
                </select>
            </div>

            <template x-if="form.type !== 'tiered'">
                <div class="col-span-2 grid grid-cols-2 gap-3">
                    <div x-show="['percent','fixed_plus_percent','provider_passed'].includes(form.type)"><label class="label">Percentage value</label><input type="number" step="0.0001" min="0" name="value" x-model.number="form.value" class="field"></div>
                    <div x-show="form.type === 'fixed'"><label class="label">Fixed value</label><input type="number" step="0.01" min="0" name="value" x-model.number="form.value" class="field"></div>
                    <div x-show="form.type === 'fixed_plus_percent'"><label class="label">Fixed component</label><input type="number" step="0.01" min="0" name="fixed_value" x-model.number="form.fixed_value" class="field"></div>
                    <div x-show="form.type === 'provider_passed'"><label class="label">Platform markup (%)</label><input type="number" step="0.0001" min="0" name="provider_markup_percent" x-model.number="form.provider_markup_percent" class="field"></div>
                </div>
            </template>

            <template x-if="form.type === 'tiered'">
                <div class="col-span-2 space-y-2 rounded-xl surface-2 p-3">
                    <div class="flex items-center justify-between"><p class="text-xs font-semibold uppercase text-faint">Tiers</p><button type="button" class="qa-btn" @click="addTier()">+ Add tier</button></div>
                    <template x-for="(tier, i) in tiers" :key="i">
                        <div class="grid grid-cols-5 items-end gap-2">
                            <div><label class="label">Min amount</label><input type="number" step="0.01" :name="`tiers[${i}][min_amount]`" x-model.number="tier.min_amount" class="field"></div>
                            <div><label class="label">Max amount</label><input type="number" step="0.01" :name="`tiers[${i}][max_amount]`" x-model.number="tier.max_amount" class="field" placeholder="No limit"></div>
                            <div><label class="label">Percent</label><input type="number" step="0.0001" :name="`tiers[${i}][percent]`" x-model.number="tier.percent" class="field"></div>
                            <div><label class="label">Fixed</label><input type="number" step="0.01" :name="`tiers[${i}][fixed]`" x-model.number="tier.fixed" class="field"></div>
                            <button type="button" class="qa-btn qa-btn-danger" @click="removeTier(i)">Remove</button>
                        </div>
                    </template>
                </div>
            </template>

            <div class="col-span-2 rounded-lg surface-2 p-2 text-xs">
                <span class="text-faint">Live preview on 100,000: </span>
                <span class="font-mono font-semibold text-body" x-text="
                    form.type === 'percent' ? (100000 * (form.value||0) / 100).toFixed(2)
                    : form.type === 'fixed' ? (form.value||0).toFixed(2)
                    : form.type === 'fixed_plus_percent' ? ((form.fixed_value||0) + 100000 * (form.value||0) / 100).toFixed(2)
                    : form.type === 'provider_passed' ? (100000 * ((form.value||0) + (form.provider_markup_percent||0)) / 100).toFixed(2)
                    : 'see tiers'
                "></span>
                <span class="text-faint"> then clamped to min/max fee below.</span>
            </div>

            <div><label class="label">Minimum fee</label><input type="number" step="0.01" min="0" name="min_fee" x-model.number="form.min_fee" class="field"></div>
            <div><label class="label">Maximum fee</label><input type="number" step="0.01" min="0" name="max_fee" x-model.number="form.max_fee" class="field" placeholder="No maximum"></div>
            <div><label class="label">Min transaction amount</label><input type="number" step="0.01" min="0" name="min_amount" x-model.number="form.min_amount" class="field"></div>
            <div><label class="label">Max transaction amount</label><input type="number" step="0.01" min="0" name="max_amount" x-model.number="form.max_amount" class="field"></div>

            <div><label class="label">Currency</label><input name="currency" x-model="form.currency" maxlength="3" class="field uppercase"></div>
            <div><label class="label">Country</label><input name="country" x-model="form.country" maxlength="2" class="field uppercase" placeholder="Any"></div>
            <div><label class="label">Payment method / scope</label><input name="scope" x-model="form.scope" class="field" placeholder="e.g. mtn_momo"></div>
            <div><label class="label">Payment provider</label><input name="payment_provider" x-model="form.payment_provider" class="field"></div>
            <div><label class="label">China wallet type</label><input name="china_wallet_type" x-model="form.china_wallet_type" class="field" placeholder="alipay / wechat"></div>
            <div><label class="label">Customer role</label><input name="customer_role" x-model="form.customer_role" class="field" placeholder="user / agent"></div>
            <div><label class="label">KYC level</label><input type="number" min="0" max="3" name="kyc_level" x-model.number="form.kyc_level" class="field"></div>
            <div><label class="label">Fee payer</label>
                <select name="fee_payer" x-model="form.fee_payer" class="field">
                    @foreach ($feePayers as $p)<option value="{{ $p->value }}">{{ $p->label() }}</option>@endforeach
                </select>
            </div>

            <div><label class="label">Effective start date</label><input type="date" name="effective_start_date" x-model="form.effective_start_date" class="field"></div>
            <div><label class="label">Effective end date</label><input type="date" name="effective_end_date" x-model="form.effective_end_date" class="field"></div>

            <div class="col-span-2"><label class="label">Internal note</label><textarea name="notes" x-model="form.notes" rows="2" class="field"></textarea></div>
            <input type="hidden" name="reason" :value="form.notes">
            <div class="col-span-2"><label class="label">Priority (lower = evaluated first among matching rules)</label><input type="number" name="sort" x-model.number="form.sort" class="field"></div>

            <div class="col-span-2 flex flex-wrap gap-4">
                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded"> Active</label>
                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="taxable" value="1" x-model="form.taxable" class="rounded"> Taxable</label>
            </div>
        </div>

        <div class="mt-5 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="formOpen=false">Cancel</button><button class="btn btn-primary flex-1" x-text="formMode === 'add' ? 'Create fee' : 'Save changes'"></button></div>
    </div>
</form>

{{-- ============ FEE CALCULATOR MODAL ============ --}}
<div x-show="calcOpen" x-cloak @click.self="calcOpen=false" class="fixed inset-0 z-[60] grid place-items-center bg-black/50 p-4" style="display:none">
    <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-strong">Fee calculator</h3>
            <button type="button" class="rounded-lg p-1.5 hover:surface-2" @click="calcOpen=false"><x-icon name="x" class="h-4 w-4" /></button>
        </div>
        <p class="mt-1 text-xs text-faint">Uses the same FeeCalculationService as real transactions — no formula is duplicated here.</p>

        <div class="mt-4 grid grid-cols-2 gap-3">
            <div class="col-span-2"><label class="label">Service</label>
                <select x-model="calcAppliesTo" class="field">
                    @foreach ($categories as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
            </div>
            <div><label class="label">Amount</label><input type="number" step="0.01" min="0" x-model.number="calcAmount" class="field"></div>
            <div><label class="label">Payment method (optional)</label><input x-model="calcScope" class="field"></div>
            <div class="col-span-2"><label class="label">Country (optional)</label><input x-model="calcCountry" maxlength="2" class="field uppercase"></div>
        </div>

        <template x-if="calcResult">
            <div class="mt-4 space-y-1.5 rounded-lg surface-2 p-3 text-sm">
                <template x-if="calcResult.exempt"><p class="text-emerald-600" x-text="'Exempt: ' + calcResult.exemption_reason"></p></template>
                <div class="flex justify-between"><span class="text-faint">Matched rule</span><span class="text-body" x-text="calcResult.matched_fee_name || 'No matching rule'"></span></div>
                <div class="flex justify-between"><span class="text-faint">Base amount</span><span class="font-mono text-body" x-text="calcResult.base_amount"></span></div>
                <div class="flex justify-between"><span class="text-faint">Percentage charge</span><span class="font-mono text-body" x-text="calcResult.percent_rate + '%'"></span></div>
                <div class="flex justify-between"><span class="text-faint">Fixed charge</span><span class="font-mono text-body" x-text="calcResult.fixed_charge"></span></div>
                <div class="flex justify-between"><span class="text-faint">Provider markup</span><span class="font-mono text-body" x-text="calcResult.provider_markup_percent + '%'"></span></div>
                <div class="flex justify-between"><span class="text-faint">Tax</span><span class="font-mono text-body" x-text="calcResult.tax + ' (no tax engine configured)'"></span></div>
                <div class="flex justify-between border-t border-app pt-1.5"><span class="font-semibold text-strong">Calculated fee</span><span class="font-mono font-semibold text-strong" x-text="calcResult.calculated_fee"></span></div>
                <div class="flex justify-between"><span class="text-faint">If added on top</span><span class="font-mono text-body" x-text="calcResult.amount_plus_fee"></span></div>
                <div class="flex justify-between"><span class="text-faint">If deducted from amount</span><span class="font-mono text-body" x-text="calcResult.amount_minus_fee"></span></div>
            </div>
        </template>
    </div>
</div>

{{-- ============ TEST FEE MODAL ============ --}}
<div x-show="testOpen" x-cloak @click.self="testOpen=false" class="fixed inset-0 z-[60] grid place-items-center bg-black/50 p-4" style="display:none">
    <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-strong">Test fee</h3>
            <button type="button" class="rounded-lg p-1.5 hover:surface-2" @click="testOpen=false"><x-icon name="x" class="h-4 w-4" /></button>
        </div>
        <p class="mt-1 text-xs text-faint">Runs this specific fee rule against a sample amount. Logged to the audit trail.</p>
        <div class="mt-3"><label class="label">Amount</label><input type="number" step="0.01" min="0" x-model.number="testAmount" class="field"></div>
        <template x-if="testResult">
            <div class="mt-4 space-y-1.5 rounded-lg surface-2 p-3 text-sm">
                <div class="flex justify-between"><span class="text-faint">Calculated fee</span><span class="font-mono font-semibold text-strong" x-text="testResult.calculated_fee"></span></div>
                <div class="flex justify-between"><span class="text-faint">If added on top</span><span class="font-mono text-body" x-text="testResult.amount_plus_fee"></span></div>
                <div class="flex justify-between"><span class="text-faint">If deducted</span><span class="font-mono text-body" x-text="testResult.amount_minus_fee"></span></div>
            </div>
        </template>
    </div>
</div>

{{-- ============ SCHEDULE CHANGE MODAL ============ --}}
<form method="POST" action="{{ route('admin.fees.schedules.store') }}" x-show="scheduleModal" x-cloak @click.self="scheduleModal=false" class="fixed inset-0 z-[60] grid place-items-center bg-black/50 p-4" style="display:none">
    @csrf
    <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
        <h3 class="font-semibold text-strong">Schedule a fee change</h3>
        <p class="mt-1 text-xs text-muted">Applied automatically once the effective date is reached. Conflicting schedules for the same fee are rejected.</p>
        <input type="hidden" name="fee_id" x-bind:value="scheduleTarget">
        <div class="mt-4 grid grid-cols-2 gap-3">
            <div><label class="label">New value (optional)</label><input type="number" step="0.0001" min="0" name="new_value" class="field"></div>
            <div><label class="label">New min fee (optional)</label><input type="number" step="0.01" min="0" name="new_min_fee" class="field"></div>
            <div><label class="label">New max fee (optional)</label><input type="number" step="0.01" min="0" name="new_max_fee" class="field"></div>
            <div><label class="label">Effective from</label><input type="date" name="effective_start_date" required class="field"></div>
            <div class="col-span-2"><label class="label">Effective to (optional)</label><input type="date" name="effective_end_date" class="field"></div>
            <div class="col-span-2"><label class="label">Reason</label><textarea name="reason" required rows="2" class="field" placeholder="Why is this change scheduled?"></textarea></div>
        </div>
        <div class="mt-5 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="scheduleModal=false">Cancel</button><button class="btn btn-primary flex-1">Schedule change</button></div>
    </div>
</form>
