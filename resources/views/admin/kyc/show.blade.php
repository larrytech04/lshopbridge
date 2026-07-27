@extends('layouts.admin')
@section('page-title', 'KYC · '.($kyc->user->name ?? $kyc->full_name))

@section('content')
@php
    $docFields = ['kyc-front' => ['id_front_path', 'ID front'], 'kyc-back' => ['id_back_path', 'ID back'], 'kyc-selfie' => ['selfie_path', 'Selfie'], 'kyc-proof' => ['proof_of_address_path', 'Proof of address']];
    $maskedDoc = $kyc->document_number ? (strlen($kyc->document_number) <= 4 ? str_repeat('•', strlen($kyc->document_number)) : str_repeat('•', strlen($kyc->document_number) - 4).substr($kyc->document_number, -4)) : '—';
    $decisionMeta = [
        'approve' => ['label' => 'Approve', 'color' => 'emerald', 'customer' => false, 'internal' => false],
        'approve_limited' => ['label' => 'Approve with limitation', 'color' => 'emerald', 'customer' => true, 'internal' => false],
        'request_more_info' => ['label' => 'Request more info', 'color' => 'amber', 'customer' => true, 'internal' => false],
        'return_for_correction' => ['label' => 'Return for correction', 'color' => 'amber', 'customer' => true, 'internal' => false],
        'reject' => ['label' => 'Reject', 'color' => 'rose', 'customer' => true, 'internal' => true],
        'escalate' => ['label' => 'Escalate', 'color' => 'purple', 'customer' => false, 'internal' => true],
        'hold' => ['label' => 'Hold', 'color' => 'gray', 'customer' => false, 'internal' => false],
        'flag_suspicious' => ['label' => 'Flag as suspicious', 'color' => 'rose', 'customer' => false, 'internal' => true],
        'freeze_account' => ['label' => 'Freeze account', 'color' => 'rose', 'customer' => false, 'internal' => false],
    ];
@endphp

<div x-data="kycWorkspace({{ $kyc->id }}, {{ $lockedByOther ? 'true' : 'false' }})" x-init="init()" class="mx-auto max-w-[1600px] space-y-4">

    {{-- ============ CASE HEADER ============ --}}
    <div class="card-solid rounded-3xl border border-app p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <a href="{{ route('admin.kyc.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">← Back to queue</a>
                <h1 class="mt-1 flex items-center gap-2 text-xl font-bold text-strong">
                    {{ $kyc->user->name ?? $kyc->full_name }}
                    <x-status-badge :status="$kyc->status" />
                    <x-status-badge :status="$priority" />
                </h1>
                <p class="text-sm text-muted">Case #{{ $kyc->id }} · {{ $kyc->user->email ?? 'no linked account' }} · submitted {{ $kyc->created_at->diffForHumans() }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs {{ $slaBreached ? 'font-semibold text-rose-500' : 'text-faint' }}">Waiting {{ $waitingHours }}h @if($slaBreached) · SLA breached @endif</span>
                <form method="POST" action="{{ route('admin.kyc.assign', $kyc) }}" class="flex items-center gap-1">
                    @csrf
                    <select name="assignee_id" class="field !w-auto text-xs" onchange="this.form.submit()">
                        <option value="">Unassigned</option>
                        @foreach ($reviewers as $r)<option value="{{ $r->id }}" @selected($kyc->assigned_to === $r->id)>{{ $r->name }}</option>@endforeach
                    </select>
                </form>
                <form method="POST" action="{{ route('admin.kyc.priority', $kyc) }}" class="flex items-center gap-1">
                    @csrf
                    <select name="priority" class="field !w-auto text-xs" onchange="this.form.submit()">
                        <option value="">Auto priority</option>
                        @foreach (\App\Enums\KycPriority::cases() as $p)<option value="{{ $p->value }}" @selected($kyc->priority === $p)>{{ $p->label() }}</option>@endforeach
                    </select>
                </form>
            </div>
        </div>

        <div x-show="lockedByOther" x-cloak class="mt-3 flex items-center gap-2 rounded-xl bg-amber-500/10 px-3 py-2 text-xs font-medium text-amber-600">
            <x-icon name="lock" class="h-4 w-4" /> This case is currently being reviewed by another admin. You can view everything, but decision actions are disabled until it's released.
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        {{-- ============ LEFT: TABS ============ --}}
        <div class="min-w-0 space-y-4 xl:col-span-2">
            <div class="flex gap-1.5 overflow-x-auto rounded-2xl border border-app p-1.5" style="background: var(--surface-1);">
                <button type="button" class="mu-tab" :class="tab==='identity' ? 'mu-tab-active' : ''" @click="tab='identity'">Identity &amp; documents</button>
                <button type="button" class="mu-tab" :class="tab==='checks' ? 'mu-tab-active' : ''" @click="tab='checks'">Verification checks</button>
                <button type="button" class="mu-tab" :class="tab==='risk' ? 'mu-tab-active' : ''" @click="tab='risk'">Risk &amp; history</button>
                <button type="button" class="mu-tab" :class="tab==='timeline' ? 'mu-tab-active' : ''" @click="tab='timeline'">Timeline &amp; notes</button>
            </div>

            {{-- ---------------------------------------------------------- IDENTITY & DOCUMENTS --}}
            <div x-show="tab==='identity'" x-cloak class="space-y-4">
                <x-glass-card solid>
                    <h3 class="font-semibold text-strong">Identity summary</h3>
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-xs text-faint">Account</dt><dd class="text-body">{{ $kyc->user->name ?? '—' }} ({{ $kyc->user->email ?? '—' }})</dd></div>
                        <div><dt class="text-xs text-faint">Legal name on document</dt><dd class="text-body">{{ $kyc->full_name }}</dd></div>
                        <div><dt class="text-xs text-faint">Date of birth</dt><dd class="text-body">{{ optional($kyc->date_of_birth)->format('M j, Y') }} ({{ optional($kyc->date_of_birth)->age }} yrs)</dd></div>
                        <div>
                            <dt class="text-xs text-faint">Document</dt>
                            <dd class="flex items-center gap-2 text-body">
                                {{ ucfirst(str_replace('_', ' ', $kyc->document_type)) }} ·
                                <span x-show="!revealed.document_number">{{ $maskedDoc }}</span>
                                <span x-show="revealed.document_number" x-cloak>{{ $kyc->document_number }}</span>
                                <button type="button" class="text-brand-600 hover:text-brand-700" @click="reveal('document_number')"><x-icon name="eye" class="h-3.5 w-3.5" /></button>
                            </dd>
                        </div>
                        <div><dt class="text-xs text-faint">Document expiry</dt>
                            <dd class="text-body">{{ $kyc->document_expiry_date?->format('M j, Y') ?? 'Not recorded' }}</dd>
                        </div>
                        <div><dt class="text-xs text-faint">Address</dt><dd class="text-body">{{ $kyc->address }}, {{ $kyc->city }}, {{ $kyc->country->name ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-faint">Occupation / source of funds</dt><dd class="text-body">{{ $kyc->occupation ?? '—' }} · {{ $kyc->source_of_funds ? ucfirst($kyc->source_of_funds) : '—' }}</dd></div>
                        <div><dt class="text-xs text-faint">Self-declared PEP status</dt><dd class="text-body">{{ $kyc->is_pep ? 'Declared a PEP' : 'Declared NOT a PEP' }}</dd></div>
                        <div><dt class="text-xs text-faint">Target verification level</dt><dd class="text-body">Level {{ $kyc->target_level }}</dd></div>
                    </dl>
                    <form method="POST" action="{{ route('admin.kyc.expiry', $kyc) }}" class="mt-3 flex flex-wrap items-center gap-2 border-t border-app pt-3">
                        @csrf
                        <label class="text-xs text-faint">Record the expiry date printed on the document:</label>
                        <input type="date" name="document_expiry_date" value="{{ $kyc->document_expiry_date?->toDateString() }}" class="field !w-auto text-xs">
                        <button class="qa-btn">Save</button>
                    </form>
                </x-glass-card>

                <x-glass-card solid>
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-strong">Secure document viewer</h3>
                        <span class="text-[11px] text-faint">Streamed from the private disk. Never a public URL. Views are access-logged.</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        @foreach ($docFields as $kind => [$field, $label])
                            <button type="button" class="flex flex-col items-center gap-2 rounded-xl border border-app surface p-3 text-xs text-body hover:surface-2 disabled:opacity-40"
                                    @click="openDoc('{{ $kind }}', '{{ $label }}')" @if(!$kyc->$field) disabled @endif>
                                <x-icon name="doc" class="h-6 w-6 text-brand-600" />
                                {{ $label }}
                                @unless($kyc->$field)<span class="text-[10px] text-faint">Not submitted</span>@endunless
                            </button>
                        @endforeach
                    </div>
                </x-glass-card>

                <x-glass-card solid>
                    <h3 class="font-semibold text-strong">OCR extraction comparison</h3>
                    <div class="mt-3 rounded-xl border border-dashed border-app p-4 text-sm text-muted">
                        <p class="font-medium text-body">No OCR provider is connected.</p>
                        <p class="mt-1 text-xs">This build does not fabricate extracted-field results. Compare the submitted values below against the document images yourself:</p>
                        <ul class="mt-2 grid gap-1 text-xs sm:grid-cols-2">
                            <li>Name: <strong class="text-body">{{ $kyc->full_name }}</strong></li>
                            <li>Document number: <strong class="text-body">{{ $maskedDoc }}</strong></li>
                            <li>Date of birth: <strong class="text-body">{{ optional($kyc->date_of_birth)->format('M j, Y') }}</strong></li>
                            <li>Document type: <strong class="text-body">{{ ucfirst(str_replace('_', ' ', $kyc->document_type)) }}</strong></li>
                        </ul>
                    </div>
                </x-glass-card>
            </div>

            {{-- ---------------------------------------------------------- VERIFICATION CHECKS --}}
            <div x-show="tab==='checks'" x-cloak class="space-y-4">
                @foreach ([
                    'face_match' => ['Face &amp; liveness verification', 'Manual comparison only — no automated face-match or liveness provider is connected. A reviewer must confirm before any decision relies on this.', ['not_checked' => 'Not checked', 'match' => 'Looks like a match', 'no_match' => 'Does not match', 'unclear' => 'Unclear / inconclusive']],
                    'document_authenticity' => ['Document authenticity', 'No forensic document-authentication provider is connected. Use visual inspection: consistent fonts, no signs of tampering, security features present, photo not altered.', ['not_checked' => 'Not checked', 'passed' => 'No concerns', 'concerns' => 'Concerns found']],
                    'address_verification' => ['Address verification', 'Compare the submitted address against the proof-of-address document.', ['not_checked' => 'Not checked', 'verified' => 'Verified', 'mismatch' => 'Mismatch', 'unclear' => 'Unclear']],
                    'aml_screening' => ['AML / PEP / sanctions screening', 'No automated sanctions or PEP screening provider is connected. Record the outcome of a manual check against your compliance team\'s screening tool. Do not treat a similar name as a confirmed match without review.', ['not_screened' => 'Not screened', 'clear' => 'Clear', 'potential_match' => 'Potential match — needs review', 'confirmed_match' => 'Confirmed match']],
                ] as $key => [$title, $note, $options])
                    @php
                        $check = $kyc->reviewCheck($key);
                        $checkStatus = $check['status'] ?? 'not_checked';
                        $checkColor = match ($checkStatus) {
                            'passed', 'verified', 'match', 'clear' => 'emerald',
                            'concerns', 'mismatch', 'no_match', 'confirmed_match' => 'rose',
                            'unclear', 'potential_match' => 'amber',
                            default => 'slate',
                        };
                    @endphp
                    <x-glass-card solid>
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-strong">{!! $title !!}</h3>
                            <span class="pill bg-{{ $checkColor }}-500/15 text-{{ $checkColor }}-600 text-[10px]">{{ $options[$checkStatus] ?? ucfirst(str_replace('_', ' ', $checkStatus)) }}</span>
                        </div>
                        <p class="mt-1 text-xs text-muted">{{ $note }}</p>
                        @if (($check['status'] ?? 'not_checked') !== 'not_checked')
                            <p class="mt-2 text-xs text-faint">Last recorded {{ isset($check['checked_at']) ? \Illuminate\Support\Carbon::parse($check['checked_at'])->diffForHumans() : '—' }}@if(!empty($check['notes'])) — "{{ $check['notes'] }}"@endif</p>
                        @endif
                        <form method="POST" action="{{ route('admin.kyc.review-check', $kyc) }}" class="mt-3 grid gap-2 sm:grid-cols-[auto_1fr_auto]">
                            @csrf
                            <input type="hidden" name="key" value="{{ $key }}">
                            <select name="status" class="field text-xs">
                                @foreach ($options as $val => $lbl)<option value="{{ $val }}" @selected(($check['status'] ?? 'not_checked') === $val)>{{ $lbl }}</option>@endforeach
                            </select>
                            <input name="notes" placeholder="Reviewer notes for this check…" class="field text-xs" value="{{ $check['notes'] ?? '' }}">
                            <button class="qa-btn">Save</button>
                        </form>
                    </x-glass-card>
                @endforeach
            </div>

            {{-- ---------------------------------------------------------- RISK & HISTORY --}}
            <div x-show="tab==='risk'" x-cloak class="space-y-4">
                <x-glass-card solid>
                    <h3 class="font-semibold text-strong">Account &amp; behavioural risk</h3>
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-xs text-faint">Account age</dt><dd class="text-body">{{ $riskSignals['account_age_days'] ?? '—' }} days</dd></div>
                        <div><dt class="text-xs text-faint">Previous KYC submissions</dt><dd class="text-body">{{ $riskSignals['previous_submissions'] }}</dd></div>
                        <div><dt class="text-xs text-faint">Previous rejections</dt><dd class="text-body {{ $riskSignals['previous_rejections'] > 0 ? 'text-rose-600 font-semibold' : '' }}">{{ $riskSignals['previous_rejections'] }}</dd></div>
                        <div><dt class="text-xs text-faint">Open risk flags</dt><dd class="text-body {{ $riskSignals['open_risk_flags'] > 0 ? 'text-rose-600 font-semibold' : '' }}">{{ $riskSignals['open_risk_flags'] }}</dd></div>
                        <div><dt class="text-xs text-faint">Registered country vs. document country</dt><dd class="text-body">{{ $riskSignals['country_mismatch'] ? 'Mismatch' : 'Consistent' }}</dd></div>
                        <div><dt class="text-xs text-faint">Lifetime deposits / funding sent</dt><dd class="text-body">{{ money($riskSignals['lifetime_deposits'], config('platform.base_currency')) }} · {{ money($riskSignals['lifetime_funding'], 'CNY') }}</dd></div>
                    </dl>
                    <p class="mt-3 text-[11px] text-faint">Only account-level signals available in this platform are shown. No IP geolocation or device-fingerprint data is collected today, so only the applicant's declared location is used here — never precise coordinates.</p>
                </x-glass-card>

                <x-glass-card solid>
                    <h3 class="font-semibold text-strong">Open risk flags</h3>
                    <div class="mt-3 space-y-2">
                        @forelse ($kyc->riskFlags->where('status', 'open') as $flag)
                            <div class="flex items-center justify-between rounded-lg bg-rose-500/5 px-3 py-2 text-sm">
                                <span class="text-body">{{ $flag->reason }}</span>
                                <span class="pill bg-rose-500/15 text-rose-600 text-[10px]">{{ ucfirst($flag->severity) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-faint">No open risk flags on this case.</p>
                        @endforelse
                    </div>
                </x-glass-card>

                <x-glass-card solid>
                    <h3 class="font-semibold text-strong">Previous KYC history</h3>
                    <div class="mt-3 space-y-2">
                        @forelse ($history as $h)
                            <a href="{{ route('admin.kyc.show', $h) }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-sm hover:surface">
                                <span class="text-body">{{ $h->created_at->format('M j, Y') }} · {{ ucfirst(str_replace('_', ' ', $h->document_type)) }}</span>
                                <x-status-badge :status="$h->status" class="text-[10px]" />
                            </a>
                        @empty
                            <p class="text-sm text-faint">This is the applicant's first submission.</p>
                        @endforelse
                    </div>
                </x-glass-card>
            </div>

            {{-- ---------------------------------------------------------- TIMELINE & NOTES --}}
            <div x-show="tab==='timeline'" x-cloak class="space-y-4">
                <x-glass-card solid>
                    <h3 class="font-semibold text-strong">Reviewer notes</h3>
                    <p class="text-xs text-faint">Private. Never shown to the applicant.</p>
                    <form method="POST" action="{{ route('admin.kyc.notes', $kyc) }}" class="mt-3 flex gap-2">
                        @csrf
                        <textarea name="body" rows="2" required placeholder="Add an internal note…" class="field text-sm"></textarea>
                        <button class="qa-btn self-end">Add</button>
                    </form>
                    <div class="mu-timeline mt-4">
                        @forelse ($kyc->notes as $note)
                            <div class="mu-timeline-item">
                                <span class="mu-timeline-dot"></span>
                                <p class="text-sm text-body">{{ $note->body }}</p>
                                <p class="text-xs text-faint">{{ $note->user->name ?? 'Unknown' }} · {{ $note->created_at->diffForHumans() }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-faint">No notes yet.</p>
                        @endforelse
                    </div>
                </x-glass-card>

                <x-glass-card solid>
                    <h3 class="font-semibold text-strong">Complete timeline</h3>
                    <div class="mu-timeline mt-4">
                        @foreach ($timeline as $event)
                            <div class="mu-timeline-item">
                                <span class="mu-timeline-dot"></span>
                                <p class="text-sm font-medium text-body">{{ $event['label'] }}</p>
                                @if ($event['detail'])<p class="text-xs text-muted">{{ $event['detail'] }}</p>@endif
                                <p class="text-xs text-faint">{{ $event['actor'] }} · {{ $event['at']->diffForHumans() }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-glass-card>
            </div>
        </div>

        {{-- ============ RIGHT: STICKY DECISION PANEL ============ --}}
        <div class="xl:col-span-1">
            {{-- lg-only: pinning this on mobile (where it's a full-width block
                 stacked below the main content, not a real sidebar) would
                 block the rest of the page while scrolling. --}}
            <div class="card-solid lg:sticky lg:top-20 space-y-3 rounded-3xl border border-app p-5 shadow-sm">
                <h3 class="font-semibold text-strong">Decision</h3>
                <p class="text-xs text-faint">Every action is logged immutably and requires confirmation. Final identity decisions cannot be undone from here — they can only be followed by a new decision.</p>

                <div class="grid gap-2">
                    @foreach ($decisionMeta as $type => $meta)
                        <button type="button" class="qa-btn justify-center {{ $meta['color'] === 'emerald' ? 'qa-btn-good' : ($meta['color'] === 'rose' ? 'qa-btn-danger' : ($meta['color'] === 'amber' ? 'qa-btn-warn' : '')) }}"
                                :disabled="lockedByOther" @click="openDecision('{{ $type }}')">
                            {{ $meta['label'] }}
                        </button>
                    @endforeach
                </div>

                <div class="border-t border-app pt-3 text-xs text-faint">
                    <p>Reviewed by: {{ $kyc->reviewedBy->name ?? '—' }}</p>
                    <p>Assigned to: {{ $kyc->assignedTo->name ?? 'Unassigned' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ DOCUMENT VIEWER MODAL ============ --}}
    <div x-show="docOpen" x-cloak x-transition @click.self="closeDoc()" class="fixed inset-0 z-50 grid place-items-center bg-black/70 p-4" style="display:none">
        <div class="card-solid w-full max-w-3xl rounded-2xl border border-app p-4">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-strong" x-text="docLabel"></h3>
                <div class="flex items-center gap-1">
                    <button type="button" class="qa-btn !px-2" @click="zoom = Math.max(0.5, zoom - 0.25)"><x-icon name="minus" class="h-3.5 w-3.5" /></button>
                    <button type="button" class="qa-btn !px-2" @click="zoom = Math.min(3, zoom + 0.25)"><x-icon name="plus" class="h-3.5 w-3.5" /></button>
                    <button type="button" class="qa-btn !px-2" @click="rotate = (rotate + 90) % 360"><x-icon name="refresh" class="h-3.5 w-3.5" /></button>
                    <button type="button" class="qa-btn !px-2" @click="compare = !compare"><x-icon name="user" class="h-3.5 w-3.5" /> Compare with selfie</button>
                    <button type="button" class="qa-btn !px-2" @click="closeDoc()"><x-icon name="x" class="h-3.5 w-3.5" /></button>
                </div>
            </div>
            <div class="relative mt-3 max-h-[70vh] overflow-auto rounded-xl surface-2 p-4">
                <div class="flex items-center justify-center gap-4">
                    <img :src="docUrl" class="max-w-full select-none" :style="`transform: scale(${zoom}) rotate(${rotate}deg); transition: transform .15s;`" draggable="false">
                    <img x-show="compare" :src="selfieUrl" class="max-w-[40%] select-none rounded-lg ring-2 ring-brand-500" draggable="false">
                </div>
            </div>
            <p class="mt-2 text-center text-[11px] text-faint">Watermarked for internal review only. Not for redistribution.</p>
        </div>
    </div>

    {{-- ============ DECISION CONFIRMATION MODAL ============ --}}
    <div x-show="decisionModal" x-cloak x-transition @click.self="decisionModal=null" class="fixed inset-0 z-50 grid place-items-center bg-black/60 p-4" style="display:none">
        <form method="POST" action="{{ route('admin.kyc.decide', $kyc) }}" class="card-solid w-full max-w-lg rounded-2xl border border-app p-6" @click.outside="decisionModal=null">
            @csrf
            <input type="hidden" name="decision_type" :value="decisionModal">
            <h3 class="font-semibold text-strong" x-text="decisionLabel()"></h3>

            <template x-if="templatesFor(decisionModal).length">
                <div class="mt-3">
                    <label class="label">Reason template</label>
                    <select name="reason_template_id" class="field" @change="applyTemplate($event.target.value)">
                        <option value="">Custom reason…</option>
                        <template x-for="t in templatesFor(decisionModal)" :key="t.id">
                            <option :value="t.id" x-text="t.name"></option>
                        </template>
                    </select>
                </div>
            </template>

            <template x-if="needsInternal(decisionModal)">
                <div class="mt-3"><label class="label">Internal reason (never shown to the applicant)</label><textarea name="internal_reason" x-model="internalReason" rows="2" class="field" required></textarea></div>
            </template>
            <template x-if="!needsInternal(decisionModal)">
                <div class="mt-3"><label class="label">Internal reason (optional)</label><textarea name="internal_reason" x-model="internalReason" rows="2" class="field"></textarea></div>
            </template>

            <template x-if="needsCustomer(decisionModal)">
                <div class="mt-3"><label class="label">Message to applicant</label><textarea name="customer_message" x-model="customerMessage" rows="2" class="field" required placeholder="Written in plain, respectful language. Never mentions internal fraud/AML reasoning."></textarea></div>
            </template>

            <template x-if="decisionModal === 'flag_suspicious'">
                <div class="mt-3"><label class="label">Severity</label>
                    <select name="severity" class="field"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="critical">Critical</option></select>
                </div>
            </template>

            <p class="mt-3 text-xs text-faint" x-text="confirmCopy()"></p>
            <div class="mt-4 flex gap-2">
                <button type="button" class="btn btn-ghost flex-1" @click="decisionModal=null">Cancel</button>
                <button type="submit" class="btn btn-primary flex-1">Confirm</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function kycWorkspace(kycId, lockedByOtherInitial) {
    return {
        kycId, lockedByOther: lockedByOtherInitial,
        tab: 'identity',
        revealed: {},
        docOpen: false, docUrl: '', docLabel: '', selfieUrl: `/files/kyc-selfie/${kycId}`,
        zoom: 1, rotate: 0, compare: false,
        decisionModal: null, internalReason: '', customerMessage: '',
        templates: @json($templates),
        decisionMeta: @json($decisionMeta),
        heartbeatTimer: null,
        init() {
            if (!this.lockedByOther) {
                fetch(`/admin/kyc/${this.kycId}/lock`, { method: 'POST', headers: this.headers() });
                this.heartbeatTimer = setInterval(() => {
                    fetch(`/admin/kyc/${this.kycId}/heartbeat`, { method: 'POST', headers: this.headers() });
                }, 60000);
                window.addEventListener('beforeunload', () => {
                    navigator.sendBeacon(`/admin/kyc/${this.kycId}/unlock`, new Blob([JSON.stringify({ _token: this.token() })], { type: 'application/json' }));
                });
            }
            if (window.ShortcutManager) {
                window.ShortcutManager.registerAction('kyc-approve', () => this.openDecision('approve'));
                window.ShortcutManager.registerAction('kyc-reject', () => this.openDecision('reject'));
                window.ShortcutManager.registerAction('kyc-note', () => { this.tab = 'timeline'; });
                window.ShortcutManager.registerAction('kyc-escalate', () => this.openDecision('escalate'));
            }
            // "esc" already dispatches this globally via the shared close-overlay action.
            window.addEventListener('close-overlays', () => { this.docOpen = false; this.decisionModal = null; });
        },
        token() { return document.querySelector('meta[name="csrf-token"]')?.content || ''; },
        headers() { return { 'X-CSRF-TOKEN': this.token(), 'Accept': 'application/json' }; },
        openDoc(kind, label) {
            this.docUrl = `/files/${kind}/${this.kycId}`;
            this.docLabel = label;
            this.zoom = 1; this.rotate = 0; this.compare = false;
            this.docOpen = true;
        },
        closeDoc() { this.docOpen = false; },
        reveal(field) {
            fetch(`/admin/kyc/${this.kycId}/reveal`, { method: 'POST', headers: { ...this.headers(), 'Content-Type': 'application/json' }, body: JSON.stringify({ field }) });
            this.revealed[field] = true;
        },
        openDecision(type) {
            this.internalReason = '';
            this.customerMessage = '';
            this.decisionModal = type;
        },
        decisionLabel() { return this.decisionMeta[this.decisionModal]?.label || ''; },
        needsInternal(type) { return ['reject', 'escalate', 'flag_suspicious'].includes(type); },
        needsCustomer(type) { return this.decisionMeta[type]?.customer || false; },
        templatesFor(type) { return this.templates[type] || []; },
        applyTemplate(id) {
            const t = this.templatesFor(this.decisionModal).find(t => t.id == id);
            if (t) { this.internalReason = t.internal_reason || ''; this.customerMessage = t.customer_message || ''; }
        },
        confirmCopy() {
            return this.lockedByOther
                ? 'This case is locked by another reviewer — decisions are disabled.'
                : 'This will be recorded immutably in the case timeline and cannot be edited afterwards.';
        },
    };
}
</script>
@endpush
