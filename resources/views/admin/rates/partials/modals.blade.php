{{-- ============ RATE DETAILS DRAWER (details + history + schedules) ============ --}}
<div x-show="drawerOpen" x-cloak class="fixed inset-0 z-50 flex justify-end bg-black/50">
    <div class="h-full w-full max-w-xl overflow-y-auto card-solid border-l border-app p-6" @click.outside="drawerOpen=false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
        <template x-if="!drawer">
            <div class="space-y-3"><div class="skel-block h-8 w-40"></div><div class="skel-block h-24"></div><div class="skel-block h-24"></div></div>
        </template>
        <template x-if="drawer">
            <div class="space-y-5">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="flex items-center gap-2 text-lg font-bold text-strong">
                            <span x-text="drawer.rate.base_currency + ' → ' + drawer.rate.quote_currency"></span>
                        </h2>
                        <p class="text-xs text-faint" x-text="'Updated by ' + (drawer.rate.updated_by || 'system') + ' · ' + drawer.rate.updated_at"></p>
                    </div>
                    <button type="button" class="rounded-lg p-1.5 hover:surface-2" @click="drawerOpen=false"><x-icon name="x" class="h-4 w-4" /></button>
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div><p class="text-xs text-faint">Base rate</p><p class="font-mono text-body" x-text="drawer.rate.rate"></p></div>
                    <div><p class="text-xs text-faint">Effective customer rate</p><p class="font-mono font-semibold text-body" x-text="drawer.rate.effective_rate"></p></div>
                    <div><p class="text-xs text-faint">Margin type</p><p class="capitalize text-body" x-text="drawer.rate.margin_type"></p></div>
                    <div><p class="text-xs text-faint">Margin value</p><p class="text-body" x-text="drawer.rate.margin_type === 'percentage' ? drawer.rate.margin_percent + '%' : (drawer.rate.margin_type === 'fixed' ? drawer.rate.margin_fixed : drawer.rate.custom_effective_rate)"></p></div>
                    <div><p class="text-xs text-faint">Update method</p><p class="capitalize text-body" x-text="drawer.rate.rate_source.replace('_',' ')"></p></div>
                    <div><p class="text-xs text-faint">Status</p><p class="text-body" x-text="drawer.rate.status_label"></p></div>
                    <div><p class="text-xs text-faint">Min funding amount</p><p class="text-body" x-text="drawer.rate.min_amount !== null ? drawer.rate.min_amount.toLocaleString() : 'No minimum'"></p></div>
                    <div><p class="text-xs text-faint">Max funding amount</p><p class="text-body" x-text="drawer.rate.max_amount !== null ? drawer.rate.max_amount.toLocaleString() : 'No maximum'"></p></div>
                </div>

                <template x-if="drawer.rate.rate_source === 'provider'">
                    <p class="rounded-lg bg-amber-500/10 px-3 py-2 text-xs text-amber-700">No automatic FX provider is connected in this platform. This value is still entered and kept up to date manually.</p>
                </template>

                <template x-if="drawer.rate.notes">
                    <div class="border-t border-app pt-3"><p class="text-xs font-semibold uppercase text-faint">Internal note</p><p class="mt-1 text-sm text-body" x-text="drawer.rate.notes"></p></div>
                </template>

                <div class="flex flex-wrap gap-2 border-t border-app pt-3">
                    <button type="button" class="qa-btn" @click="drawerOpen=false; openEdit(drawer.rate.id, drawer.rate)">Edit rate</button>
                    <button type="button" class="qa-btn" @click="calcBase=drawer.rate.base_currency; calcQuote=drawer.rate.quote_currency; calcOpen=true">Open calculator</button>
                    <button type="button" class="qa-btn" @click="scheduleTarget=drawer.rate.id; scheduleBase=drawer.rate.base_currency; scheduleQuote=drawer.rate.quote_currency; scheduleModal=true">Schedule change</button>
                </div>

                {{-- Scheduled changes --}}
                <div class="border-t border-app pt-3">
                    <p class="text-xs font-semibold uppercase text-faint">Scheduled changes</p>
                    <template x-if="drawer.schedules.length === 0"><p class="mt-2 text-xs text-faint">No scheduled changes for this pair.</p></template>
                    <div class="mt-2 space-y-1.5">
                        <template x-for="s in drawer.schedules" :key="s.id">
                            <div class="rounded-lg surface-2 p-2 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-body" x-text="'Effective rate ' + s.effective_rate + ' from ' + s.effective_from + (s.effective_to ? ' to ' + s.effective_to : '')"></span>
                                    <span class="pill text-[10px]" x-text="s.status"></span>
                                </div>
                                <p class="mt-0.5 text-faint" x-text="(s.reason || '—') + ' · ' + (s.created_by || 'system')"></p>
                                <form method="POST" :action="`/admin/rates/schedules/${s.id}/cancel`" class="mt-1" x-show="s.status === 'scheduled'">
                                    @csrf
                                    <button class="text-rose-500 hover:underline">Cancel this schedule</button>
                                </form>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- History --}}
                <div class="border-t border-app pt-3">
                    <p class="text-xs font-semibold uppercase text-faint">Rate history</p>
                    <p class="mt-1 text-[11px] text-faint">Immutable audit trail — every change is recorded, never overwritten or deleted.</p>
                    <div class="mu-timeline mt-2">
                        <template x-for="h in drawer.history" :key="h.at + h.event">
                            <div class="mu-timeline-item">
                                <span class="mu-timeline-dot"></span>
                                <p class="text-sm font-medium capitalize text-body" x-text="h.event.replace(/_/g,' ') + ' — effective ' + h.effective_rate"></p>
                                <p class="text-xs text-muted" x-text="'Base ' + h.rate + ' · margin ' + (h.margin_type === 'percentage' ? h.margin_percent + '%' : h.margin_type) + (h.reason ? ' · ' + h.reason : '')"></p>
                                <p class="text-xs text-faint" x-text="h.changed_by + ' · ' + h.at"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

{{-- ============ ADD / EDIT RATE MODAL ============ --}}
<form method="POST" :action="formAction()" x-show="formOpen" x-cloak @click.self="formOpen=false" class="fixed inset-0 z-[60] grid place-items-center bg-black/50 p-4" style="display:none">
    @csrf
    <input type="hidden" name="_method" :value="formMode === 'edit' ? 'PUT' : 'POST'">
    <div class="card-solid max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-app p-6" @click.stop>
        <h3 class="font-semibold text-strong" x-text="formMode === 'add' ? 'Add exchange rate' : 'Edit exchange rate'"></h3>

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
            <div><label class="label">Source currency</label><input name="base_currency" x-model="form.base_currency" maxlength="3" required class="field uppercase" :disabled="formMode === 'edit'"></div>
            <div><label class="label">Destination currency</label><input name="quote_currency" x-model="form.quote_currency" maxlength="3" required class="field uppercase" :disabled="formMode === 'edit'"></div>
            <div class="col-span-2"><label class="label">Base rate</label><input type="number" step="0.00000001" min="0.00000001" name="rate" x-model.number="form.rate" required class="field"></div>

            <div class="col-span-2"><label class="label">Margin type</label>
                <select name="margin_type" x-model="form.margin_type" class="field">
                    @foreach ($marginTypes as $mt)<option value="{{ $mt->value }}">{{ $mt->label() }}</option>@endforeach
                </select>
            </div>
            <div class="col-span-2" x-show="form.margin_type === 'percentage'"><label class="label">Margin (%)</label><input type="number" step="0.0001" min="0" name="margin_percent" x-model.number="form.margin_percent" class="field"></div>
            <div class="col-span-2" x-show="form.margin_type === 'fixed'"><label class="label">Fixed adjustment</label><input type="number" step="0.00000001" name="margin_fixed" x-model.number="form.margin_fixed" class="field"></div>
            <div class="col-span-2" x-show="form.margin_type === 'custom'"><label class="label">Custom effective rate</label><input type="number" step="0.00000001" min="0.00000001" name="custom_effective_rate" x-model.number="form.custom_effective_rate" class="field"></div>

            <div class="col-span-2 rounded-lg surface-2 p-2 text-xs">
                <span class="text-faint">Effective rate preview: </span>
                <span class="font-mono font-semibold text-body" x-text="
                    form.margin_type === 'percentage' ? (form.rate * (1 - (form.margin_percent||0)/100)).toFixed(8)
                    : form.margin_type === 'fixed' ? (form.rate - (form.margin_fixed||0)).toFixed(8)
                    : (form.custom_effective_rate || 0)
                "></span>
            </div>

            <div class="col-span-2"><label class="label">Rate source</label>
                <select name="rate_source" x-model="form.rate_source" class="field">
                    <option value="manual">Manual</option>
                    <option value="provider">Automatic provider</option>
                    <option value="scheduled_manual">Scheduled manual rate</option>
                </select>
            </div>
            <div><label class="label">Min funding amount</label><input type="number" step="0.01" min="0" name="min_amount" x-model.number="form.min_amount" class="field"></div>
            <div><label class="label">Max funding amount</label><input type="number" step="0.01" min="0" name="max_amount" x-model.number="form.max_amount" class="field"></div>

            <div class="col-span-2"><label class="label">Internal note</label><textarea name="notes" x-model="form.notes" rows="2" class="field" placeholder="Reason for this configuration (internal only)"></textarea></div>
            <input type="hidden" name="reason" :value="form.notes">

            <div class="col-span-2 flex items-center gap-2"><input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded"><label class="text-sm text-body">Active</label></div>
        </div>

        <div class="mt-5 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="formOpen=false">Cancel</button><button class="btn btn-primary flex-1" x-text="formMode === 'add' ? 'Create rate' : 'Save changes'"></button></div>
    </div>
</form>

{{-- ============ RATE CALCULATOR MODAL ============ --}}
<div x-show="calcOpen" x-cloak @click.self="calcOpen=false" class="fixed inset-0 z-[60] grid place-items-center bg-black/50 p-4" style="display:none">
    <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-strong">Rate calculator</h3>
            <button type="button" class="rounded-lg p-1.5 hover:surface-2" @click="calcOpen=false"><x-icon name="x" class="h-4 w-4" /></button>
        </div>
        <p class="mt-1 text-xs text-faint">Uses the same calculation service as real transactions — no formula is duplicated here.</p>

        <div class="mt-4 grid grid-cols-2 gap-3">
            <div><label class="label">Source currency</label><input x-model="calcBase" maxlength="3" class="field uppercase"></div>
            <div><label class="label">Destination currency</label><input x-model="calcQuote" maxlength="3" class="field uppercase"></div>
            <div class="col-span-2"><label class="label">Amount</label><input type="number" step="0.01" min="0" x-model.number="calcAmount" class="field"></div>
            <div class="col-span-2"><label class="label">Additional fee (optional)</label><input type="number" step="0.01" min="0" x-model.number="calcFee" class="field"></div>
        </div>

        <template x-if="calcResult">
            <div class="mt-4 space-y-1.5 rounded-lg surface-2 p-3 text-sm">
                <div class="flex justify-between"><span class="text-faint">Base rate</span><span class="font-mono text-body" x-text="calcResult.base_rate"></span></div>
                <div class="flex justify-between"><span class="text-faint">Effective rate</span><span class="font-mono text-body" x-text="calcResult.effective_rate"></span></div>
                <div class="flex justify-between"><span class="text-faint">Margin amount</span><span class="font-mono text-body" x-text="calcResult.margin_amount"></span></div>
                <div class="flex justify-between border-t border-app pt-1.5"><span class="font-semibold text-strong">Delivered amount</span><span class="font-mono font-semibold text-strong" x-text="calcResult.delivered_amount + ' ' + calcResult.quote_currency"></span></div>
            </div>
        </template>
        <template x-if="!calcResult"><p class="mt-4 text-xs text-faint">No active rate found for this pair.</p></template>
    </div>
</div>

{{-- ============ SCHEDULE CHANGE MODAL ============ --}}
<form method="POST" action="{{ route('admin.rates.schedules.store') }}" x-show="scheduleModal" x-cloak @click.self="scheduleModal=false" class="fixed inset-0 z-[60] grid place-items-center bg-black/50 p-4" style="display:none">
    @csrf
    <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
        <h3 class="font-semibold text-strong">Schedule a rate change</h3>
        <p class="mt-1 text-xs text-muted">Applied automatically once the effective date is reached. Conflicting schedules for the same pair and date range are rejected.</p>
        <input type="hidden" name="base_currency" x-bind:value="scheduleBase">
        <input type="hidden" name="quote_currency" x-bind:value="scheduleQuote">
        <div class="mt-4 grid grid-cols-2 gap-3">
            <div class="col-span-2"><label class="label">New base rate</label><input type="number" step="0.00000001" min="0.00000001" name="rate" required class="field"></div>
            <div class="col-span-2"><label class="label">Margin type</label>
                <select name="margin_type" class="field">
                    @foreach ($marginTypes as $mt)<option value="{{ $mt->value }}">{{ $mt->label() }}</option>@endforeach
                </select>
            </div>
            <div><label class="label">Margin (%)</label><input type="number" step="0.0001" min="0" name="margin_percent" class="field"></div>
            <div><label class="label">Fixed adjustment</label><input type="number" step="0.00000001" name="margin_fixed" class="field"></div>
            <div><label class="label">Effective from</label><input type="date" name="effective_from" required class="field"></div>
            <div><label class="label">Effective to (optional)</label><input type="date" name="effective_to" class="field"></div>
            <div class="col-span-2"><label class="label">Reason</label><textarea name="reason" required rows="2" class="field" placeholder="Why is this change scheduled?"></textarea></div>
        </div>
        <div class="mt-5 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="scheduleModal=false">Cancel</button><button class="btn btn-primary flex-1">Schedule change</button></div>
    </div>
</form>
