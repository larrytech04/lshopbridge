@extends('layouts.admin')
@section('page-title', 'Security events')

@section('content')
<div class="space-y-6" x-data="{ tab: '{{ $tab }}' }">
    <div>
        <h1 class="text-2xl font-bold text-strong">Security events</h1>
        <p class="text-sm text-muted">Forms & bot protection activity. Detection rules are never shown to visitors — only outcomes and safe metadata are recorded here.</p>
    </div>

    <nav class="flex flex-wrap gap-2">
        @foreach (['overview' => 'Overview', 'events' => 'Events log', 'review' => 'Review queue', 'allowlist' => 'Allowlist'] as $key => $label)
            <button type="button" @click="tab = '{{ $key }}'" class="pill" :class="tab === '{{ $key }}' ? 'bg-brand-600/40 text-strong ring-1 ring-white/10' : 'surface text-body ring-1 ring-white/10'">{{ $label }}</button>
        @endforeach
    </nav>

    {{-- ============ OVERVIEW ============ --}}
    <div x-show="tab === 'overview'" x-cloak class="space-y-6">
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ([
                ['Submissions today', $stats['submissions_today'], '#64748B', 'doc'],
                ['Accepted', $stats['accepted'], '#10B981', 'check-circle'],
                ['Turnstile challenges', $stats['challenge_required'], '#F59E0B', 'shield'],
                ['Turnstile failures', $stats['turnstile_failures'], '#EF4444', 'ban'],
                ['Honeypot detections', $stats['honeypot_hits'], '#EF4444', 'eye-off'],
                ['Silently discarded', $stats['silently_discarded'], '#EF4444', 'x'],
                ['Rate limited', $stats['rate_limited'], '#F59E0B', 'clock'],
                ['Duplicate submissions', $stats['duplicates'], '#F59E0B', 'copy'],
                ['Held for review', $stats['held_for_review'], '#F59E0B', 'help'],
                ['Active IP restrictions', $stats['active_restrictions'], '#EF4444', 'lock'],
                ['False-positive reports', $stats['false_positive_reports'], '#64748B', 'flag'],
            ] as [$label, $value, $color, $icon])
                <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
                    <div class="flex items-center gap-2">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-white" style="background: {{ $color }}"><x-icon name="{{ $icon }}" class="h-4 w-4" /></span>
                        <p class="truncate text-[11px] text-faint">{{ $label }}</p>
                    </div>
                    <p class="mt-2 text-lg font-bold text-strong">{{ number_format($value) }}</p>
                </div>
            @endforeach
        </div>

        {{-- Submission traffic over time --}}
        <section class="card-solid rounded-2xl border border-app p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="font-semibold text-strong">Submission traffic — last 14 days</h3>
                <div class="flex items-center gap-4 text-xs text-muted">
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span> Accepted</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span> Blocked/challenged</span>
                </div>
            </div>
            @php $maxTraffic = max(1, $trafficSeries->max(fn ($d) => $d['accepted'] + $d['blocked'])); @endphp
            <div class="flex h-40 items-end gap-1.5">
                @foreach ($trafficSeries as $day)
                    @php
                        $acceptedH = round(($day['accepted'] / $maxTraffic) * 100);
                        $blockedH = round(($day['blocked'] / $maxTraffic) * 100);
                    @endphp
                    <div class="group relative flex flex-1 flex-col items-center justify-end gap-0.5" style="height: 100%">
                        <div class="w-full rounded-t-sm bg-rose-500" style="height: {{ $blockedH }}%; min-height: {{ $day['blocked'] > 0 ? '2px' : '0' }}"></div>
                        <div class="w-full rounded-b-sm bg-emerald-500" style="height: {{ $acceptedH }}%; min-height: {{ $day['accepted'] > 0 ? '2px' : '0' }}"></div>
                        <div class="pointer-events-none absolute -top-9 z-10 hidden whitespace-nowrap rounded-lg bg-black/80 px-2 py-1 text-[10px] text-white group-hover:block">
                            {{ \Illuminate\Support\Carbon::parse($day['day'])->format('M j') }}: {{ $day['accepted'] }} ok / {{ $day['blocked'] }} blocked
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 flex justify-between text-[10px] text-faint">
                <span>{{ \Illuminate\Support\Carbon::parse($trafficSeries->first()['day'])->format('M j') }}</span>
                <span>{{ \Illuminate\Support\Carbon::parse($trafficSeries->last()['day'])->format('M j') }}</span>
            </div>
        </section>

        <div class="grid gap-4 lg:grid-cols-3">
            {{-- Most targeted forms --}}
            <section class="card-solid rounded-2xl border border-app p-5 shadow-sm">
                <h3 class="mb-3 font-semibold text-strong">Most targeted forms</h3>
                @forelse ($mostTargetedForms as $row)
                    @php $pct = $mostTargetedForms->max('c') > 0 ? round($row->c / $mostTargetedForms->max('c') * 100) : 0; @endphp
                    <div class="mb-2.5">
                        <div class="mb-1 flex justify-between text-xs"><span class="text-body">{{ ucfirst(str_replace('_', ' ', $row->form_type)) }}</span><span class="text-faint">{{ $row->c }}</span></div>
                        <div class="h-1.5 rounded-full surface-2"><div class="h-1.5 rounded-full bg-brand-600" style="width: {{ max(4, $pct) }}%"></div></div>
                    </div>
                @empty
                    <x-empty icon="doc" title="No traffic yet" />
                @endforelse
            </section>

            {{-- Bot traffic by country --}}
            <section class="card-solid rounded-2xl border border-app p-5 shadow-sm">
                <h3 class="mb-3 font-semibold text-strong">Blocked/challenged traffic by country</h3>
                @forelse ($trafficByCountry as $row)
                    @php $pct = $trafficByCountry->max('c') > 0 ? round($row->c / $trafficByCountry->max('c') * 100) : 0; @endphp
                    <div class="mb-2.5">
                        <div class="mb-1 flex justify-between text-xs"><span class="text-body">{{ $row->country }}</span><span class="text-faint">{{ $row->c }}</span></div>
                        <div class="h-1.5 rounded-full surface-2"><div class="h-1.5 rounded-full bg-amber-500" style="width: {{ max(4, $pct) }}%"></div></div>
                    </div>
                @empty
                    <x-empty icon="globe" title="Not configured" message="Requires IPINFO_API_KEY to resolve countries." />
                @endforelse
            </section>

            {{-- Most triggered rules --}}
            <section class="card-solid rounded-2xl border border-app p-5 shadow-sm">
                <h3 class="mb-3 font-semibold text-strong">Most triggered rules</h3>
                @forelse ($mostTriggeredRules as $rule => $count)
                    @php $pct = $mostTriggeredRules->max() > 0 ? round($count / $mostTriggeredRules->max() * 100) : 0; @endphp
                    <div class="mb-2.5">
                        <div class="mb-1 flex justify-between text-xs"><span class="font-mono text-body">{{ $rule }}</span><span class="text-faint">{{ $count }}</span></div>
                        <div class="h-1.5 rounded-full surface-2"><div class="h-1.5 rounded-full bg-slate-500" style="width: {{ max(4, $pct) }}%"></div></div>
                    </div>
                @empty
                    <x-empty icon="shield" title="No rules triggered yet" />
                @endforelse
            </section>
        </div>
    </div>

    {{-- ============ EVENTS LOG ============ --}}
    <div x-show="tab === 'events'" x-cloak class="space-y-4">
        <form method="GET" class="glass flex flex-wrap gap-3 rounded-2xl p-4">
            <input type="hidden" name="tab" value="events">
            <input name="event_type" value="{{ request('event_type') }}" placeholder="Event type contains…" class="field max-w-[200px]">
            <input name="form_type" value="{{ request('form_type') }}" placeholder="Form type" class="field max-w-[160px]">
            <select name="risk_level" class="field max-w-[140px]">
                <option value="">All risk levels</option>
                @foreach (['low','medium','high','critical'] as $l)<option value="{{ $l }}" @selected(request('risk_level')===$l)>{{ ucfirst($l) }}</option>@endforeach
            </select>
            <button class="btn btn-primary"><x-icon name="search" class="h-4 w-4" /> Filter</button>
        </form>
        <x-glass-card padding="p-0">
            <div class="overflow-x-auto"><table class="w-full text-left text-sm">
                <thead class="border-b border-app text-muted"><tr><th class="px-5 py-3">Reference</th><th class="px-5 py-3">Event</th><th class="px-5 py-3">Form</th><th class="px-5 py-3">Risk</th><th class="px-5 py-3">Action taken</th><th class="px-5 py-3">Country</th><th class="px-5 py-3">When</th><th class="px-5 py-3"></th></tr></thead>
                <tbody class="divide-y divide-app">
                    @forelse ($events as $event)
                        <tr>
                            <td class="px-5 py-3 font-mono text-xs text-faint">{{ $event->reference }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-body">{{ $event->event_type }}</td>
                            <td class="px-5 py-3 text-body">{{ $event->form_type }}</td>
                            <td class="px-5 py-3"><span class="pill {{ ['critical'=>'bg-rose-500/15 text-rose-600','high'=>'bg-amber-500/15 text-amber-600','medium'=>'bg-sky-500/15 text-sky-600'][$event->risk_level] ?? 'bg-slate-400/15 text-slate-600' }} text-[10px] font-bold uppercase">{{ $event->risk_level }}</span></td>
                            <td class="px-5 py-3 text-muted">{{ str_replace('_', ' ', $event->action_taken) }}</td>
                            <td class="px-5 py-3 text-muted">{{ $event->country ?: '-' }}</td>
                            <td class="px-5 py-3 text-muted">{{ $event->created_at->diffForHumans() }}</td>
                            <td class="px-5 py-3 text-right">
                                @if ($event->status !== 'false_positive')
                                    <form method="POST" action="{{ route('admin.security-events.false-positive', $event) }}">@csrf<button class="text-xs text-brand-600">Mark false positive</button></form>
                                @else
                                    <span class="text-xs text-faint">False positive</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-10 text-center text-faint">No events recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </x-glass-card>
        <div>{{ $events->links() }}</div>
    </div>

    {{-- ============ REVIEW QUEUE ============ --}}
    <div x-show="tab === 'review'" x-cloak class="space-y-4">
        @forelse ($reviewCases as $case)
            <div class="card-solid rounded-2xl border border-app p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-mono text-xs text-faint">{{ $case->reference }} · {{ $case->form_type }}</p>
                        <p class="mt-1 font-semibold text-strong">{{ $case->sender_name ?: 'Unknown' }} @if($case->sender_email) <span class="font-normal text-muted">({{ $case->sender_email }})</span>@endif</p>
                        @if (!empty($case->safe_payload['message']) || !empty($case->safe_payload['description']) || !empty($case->safe_payload['comment']))
                            <p class="mt-2 max-w-2xl text-sm text-muted">{{ \Illuminate\Support\Str::limit($case->safe_payload['message'] ?? $case->safe_payload['description'] ?? $case->safe_payload['comment'] ?? '', 240) }}</p>
                        @endif
                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach ($case->triggered_rules ?? [] as $rule)
                                <span class="pill surface text-[10px] text-faint ring-1 ring-white/10">{{ $rule }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="pill {{ ['critical'=>'bg-rose-500/15 text-rose-600','high'=>'bg-amber-500/15 text-amber-600'][$case->risk_level] ?? 'bg-sky-500/15 text-sky-600' }} text-[10px] font-bold uppercase">{{ $case->risk_level }} · {{ $case->risk_score }}</span>
                        <p class="mt-1 text-xs text-faint">{{ $case->created_at->diffForHumans() }} · {{ $case->country ?: 'Unknown' }}</p>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2 border-t border-app pt-4">
                    <form method="POST" action="{{ route('admin.security-events.review.legitimate', $case) }}">@csrf<button class="btn btn-success py-1.5 text-xs">Mark legitimate</button></form>
                    <form method="POST" action="{{ route('admin.security-events.review.spam', $case) }}">@csrf<button class="btn btn-danger py-1.5 text-xs">Mark spam</button></form>
                    <form method="POST" action="{{ route('admin.security-events.review.block-fingerprint', $case) }}" onsubmit="return confirm('Blocklist this fingerprint? Future identical submissions will be silently discarded.')">@csrf<button class="btn btn-ghost py-1.5 text-xs">Blocklist fingerprint</button></form>
                    @if ($case->sender_email)
                        <form method="POST" action="{{ route('admin.security-events.review.allow-sender', $case) }}">@csrf<button class="btn btn-ghost py-1.5 text-xs">Allow sender domain</button></form>
                    @endif
                    <form method="POST" action="{{ route('admin.security-events.review.archive', $case) }}">@csrf<button class="btn btn-ghost py-1.5 text-xs">Archive</button></form>
                </div>
            </div>
        @empty
            <x-empty icon="check-circle" title="Nothing waiting for review" message="Medium-confidence submissions that need a human decision will show up here." />
        @endforelse
        <div>{{ $reviewCases->links() }}</div>
    </div>

    {{-- ============ ALLOWLIST ============ --}}
    <div x-show="tab === 'allowlist'" x-cloak class="space-y-4">
        <p class="text-sm text-muted">Allowlisted requests skip the Turnstile/honeypot/timing/rate-limit challenge only — CSRF, validation, authorization, and injection protection always stay active.</p>
        <x-glass-card padding="p-0">
            <div class="overflow-x-auto"><table class="w-full text-left text-sm">
                <thead class="border-b border-app text-muted"><tr><th class="px-5 py-3">Type</th><th class="px-5 py-3">Value</th><th class="px-5 py-3">Reason</th><th class="px-5 py-3">Expires</th><th class="px-5 py-3"></th></tr></thead>
                <tbody class="divide-y divide-app">
                    @forelse ($allowlist as $entry)
                        <tr>
                            <td class="px-5 py-3 text-body">{{ $entry->subject_type === 'ip' ? 'IP' : 'Email domain' }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-body">{{ $entry->subject_value }}</td>
                            <td class="px-5 py-3 text-muted">{{ $entry->reason ?: '-' }}</td>
                            <td class="px-5 py-3 text-muted">{{ $entry->expires_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="px-5 py-3 text-right"><form method="POST" action="{{ route('admin.security-events.allowlist.destroy', $entry) }}" onsubmit="return confirm('Remove this allowlist entry?')">@csrf @method('DELETE')<button class="text-xs text-rose-600">Remove</button></form></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-faint">No allowlist entries. Add one from a review case with "Allow sender domain".</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </x-glass-card>
    </div>
</div>
@endsection
