@extends('layouts.admin')
@section('page-title', 'Agent Management')

@section('content')
@php
    $tabs = [
        'all' => ['All', $counts['all']],
        'pending' => ['Pending', $counts['pending']],
        'approved' => ['Approved', $counts['approved']],
        'rejected' => ['Rejected', $counts['rejected']],
        'suspended' => ['Suspended', $counts['suspended']],
        'featured' => ['Featured', $counts['featured']],
    ];
    $summary = [
        ['Total agents', $counts['all'], 'users', 'slate', null],
        ['Approved', $counts['approved'], 'check-circle', 'emerald', 'approved'],
        ['Pending', $counts['pending'], 'clock', 'amber', 'pending'],
        ['Suspended', $counts['suspended'], 'ban', 'rose', 'suspended'],
        ['Featured', $counts['featured'], 'star', 'purple', 'featured'],
        ['Active this month', $counts['active_month'], 'signal', 'sky', null],
        ['Average rating', number_format($counts['avg_rating'], 2), 'star', 'amber', null],
        ['Open agent complaints', $counts['open_complaints'], 'alert', 'rose', null],
    ];
@endphp

<div x-data="agentsConsole()" x-init="init()" class="space-y-5">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-strong">Agent Management</h1>
            <p class="text-sm text-muted">Review, approve, monitor, and manage verified sourcing and shipping agents.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="qa-btn qa-btn-good" @click="addOpen = true"><x-icon name="plus" class="h-3.5 w-3.5" /> Add agent</button>
            <a href="{{ route('admin.agents.export', request()->query()) }}" class="qa-btn"><x-icon name="download" class="h-3.5 w-3.5" /> Export</a>
            <button type="button" class="qa-btn" @click="window.location.reload()"><x-icon name="refresh" class="h-3.5 w-3.5" /> Refresh</button>
            <a href="{{ route('admin.settings.index') }}" class="qa-btn"><x-icon name="cog" class="h-3.5 w-3.5" /> Agent settings</a>
        </div>
    </div>

    {{-- ============ SUMMARY CARDS ============ --}}
    <div class="no-scrollbar grid grid-flow-col auto-cols-[minmax(9rem,1fr)] gap-3 overflow-x-auto pb-1 lg:grid-flow-row lg:auto-cols-auto lg:grid-cols-4 xl:grid-cols-8">
        @foreach ($summary as [$label, $value, $icon, $tint, $tabTarget])
            <a href="{{ $tabTarget ? route('admin.agents.index', ['tab' => $tabTarget]) : route('admin.agents.index') }}" class="card-solid rounded-2xl border border-app p-3.5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center gap-2">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-{{ $tint }}-500/15 text-{{ $tint }}-600"><x-icon :name="$icon" class="h-4 w-4" /></span>
                    <p class="truncate text-[11px] text-faint">{{ $label }}</p>
                </div>
                <p class="mt-2 text-lg font-bold text-strong">{{ $value }}</p>
            </a>
        @endforeach
    </div>

    {{-- ============ TABS ============ --}}
    <div class="no-scrollbar flex gap-1.5 overflow-x-auto rounded-2xl border border-app p-1.5" style="background: var(--surface-1);">
        @foreach ($tabs as $key => [$label, $count])
            <a href="{{ route('admin.agents.index', ['tab' => $key]) }}" class="mu-tab {{ $tab === $key ? 'mu-tab-active' : '' }} whitespace-nowrap">
                {{ $label }} <span class="ml-1 text-[10px] opacity-70">{{ $count }}</span>
            </a>
        @endforeach
    </div>

    {{-- ============ SEARCH + FILTERS + BULK BAR ============ --}}
    <div class="card-solid space-y-4 rounded-3xl border border-app p-5 shadow-sm">
        <form method="GET" id="filter-form" class="space-y-4">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative min-w-0 flex-1">
                    <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" />
                    <input x-ref="searchInput" name="q" value="{{ $q }}" placeholder="Search business, owner, email, phone, country, city, agent ID…"
                           class="field !rounded-full pl-11 pr-4" @input.debounce.500ms="$el.form.requestSubmit()">
                </div>
                <button type="button" class="qa-btn" @click="filtersOpen = !filtersOpen"><x-icon name="filter" class="h-3.5 w-3.5" /> Filters <span x-show="activeFilterCount() > 0" x-text="'(' + activeFilterCount() + ')'"></span></button>
                <button type="button" class="qa-btn" @click="clearFilters()">Clear filters</button>
            </div>

            <div x-show="filtersOpen" x-collapse x-cloak class="grid gap-3 border-t border-app pt-4 sm:grid-cols-2 lg:grid-cols-4">
                <select name="agent_type" class="field"><option value="">Any agent type</option>@foreach (\App\Enums\AgentType::cases() as $t)<option value="{{ $t->value }}" @selected(request('agent_type')===$t->value)>{{ $t->label() }}</option>@endforeach</select>
                <select name="country_id" class="field"><option value="">Any country</option>@foreach ($countries as $c)<option value="{{ $c->id }}" @selected(request('country_id') == $c->id)>{{ $c->name }}</option>@endforeach</select>
                <select name="rating_min" class="field"><option value="">Any rating</option>@foreach ([4.5=>'4.5+',4=>'4.0+',3=>'3.0+',2=>'2.0+'] as $val=>$lbl)<option value="{{ $val }}" @selected(request('rating_min')==$val)>{{ $lbl }}</option>@endforeach</select>
                <label class="field flex items-center gap-2 !py-2.5"><input type="checkbox" name="featured" value="1" @checked(request('featured')) class="rounded"> Featured only</label>
                <div class="flex gap-2 sm:col-span-2"><input type="date" name="joined_from" value="{{ request('joined_from') }}" class="field" title="Joined from"><input type="date" name="joined_to" value="{{ request('joined_to') }}" class="field" title="Joined to"></div>
                <div class="flex gap-2 sm:col-span-2">
                    <button class="btn btn-primary flex-1 text-sm">Apply filters</button>
                    <button type="button" class="btn btn-ghost flex-1 text-sm" @click="savePreset()">Save preset</button>
                </div>
            </div>

            <div x-show="filtersOpen && presets.length" x-collapse x-cloak class="flex flex-wrap items-center gap-2 border-t border-app pt-3">
                <span class="text-xs font-semibold text-faint">Presets:</span>
                <template x-for="p in presets" :key="p.name">
                    <span class="inline-flex items-center gap-1 rounded-full surface-2 px-2.5 py-1 text-xs">
                        <a :href="p.query" class="text-body hover:text-strong" x-text="p.name"></a>
                        <button type="button" @click="deletePreset(p.name)" class="text-faint hover:text-rose-500"><x-icon name="x" class="h-3 w-3" /></button>
                    </span>
                </template>
            </div>
        </form>

        <div x-show="selected.length > 0" x-collapse x-cloak class="flex flex-wrap items-center gap-2 border-t border-app pt-3">
            <span class="text-xs font-semibold text-strong" x-text="selected.length + ' selected'"></span>
            <button type="button" class="qa-btn qa-btn-good" @click="runBulk('approve')"><x-icon name="check" class="h-3.5 w-3.5" /> Approve</button>
            <button type="button" class="qa-btn qa-btn-warn" @click="bulkModal = 'suspend'"><x-icon name="ban" class="h-3.5 w-3.5" /> Suspend</button>
            <button type="button" class="qa-btn qa-btn-good" @click="runBulk('restore')"><x-icon name="refresh" class="h-3.5 w-3.5" /> Restore</button>
            <button type="button" class="qa-btn" @click="runBulk('feature')"><x-icon name="star" class="h-3.5 w-3.5" /> Feature</button>
            <button type="button" class="qa-btn" @click="runBulk('unfeature')">Remove featured</button>
            <button type="button" class="qa-btn" @click="bulkModal = 'notify'"><x-icon name="bell" class="h-3.5 w-3.5" /> Notify</button>
        </div>
    </div>

    {{-- ============ TABLE ============ --}}
    <div class="overflow-x-auto rounded-2xl border border-app">
        <table class="w-full min-w-[1200px] text-left text-sm">
            <thead class="sticky top-0 z-10 border-b border-app text-muted" style="background: var(--surface-1);">
                <tr>
                    <th class="px-3 py-3"><input type="checkbox" @change="toggleAll($event.target.checked)" class="rounded"></th>
                    <th class="px-3 py-3 font-medium">Business</th>
                    <th class="px-3 py-3 font-medium">Owner</th>
                    <th class="px-3 py-3 font-medium">Agent type</th>
                    <th class="px-3 py-3 font-medium">Country</th>
                    <th class="px-3 py-3 font-medium">Rating</th>
                    <th class="px-3 py-3 font-medium">Completed</th>
                    <th class="px-3 py-3 font-medium">Success rate</th>
                    <th class="px-3 py-3 font-medium">Status</th>
                    <th class="px-3 py-3 font-medium">Featured</th>
                    <th class="px-3 py-3 font-medium">Last active</th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-app">
                @forelse ($items as $agent)
                    <tr class="hover:surface cursor-pointer" @click="openDrawer('{{ $agent->slug }}')">
                        <td class="px-3 py-3" @click.stop><input type="checkbox" value="{{ $agent->id }}" x-model="selected" class="rounded"></td>
                        <td class="px-3 py-3">
                            <div class="flex items-center gap-2.5">
                                @if ($agent->logo_path)
                                    <img src="{{ asset('storage/'.$agent->logo_path) }}" class="h-8 w-8 shrink-0 rounded-full object-cover" alt="">
                                @else
                                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-brand-600 text-xs font-bold text-white">{{ strtoupper(substr($agent->business_name, 0, 2)) }}</span>
                                @endif
                                <div class="min-w-0">
                                    <p class="flex items-center gap-1 truncate font-medium text-strong">
                                        {{ $agent->business_name }}
                                        @if ($agent->status->value === 'approved')<x-verified-tick class="h-3.5 w-3.5 shrink-0" />@endif
                                    </p>
                                    <p class="truncate text-xs text-faint">{{ $agent->warehouse_city }}@if($agent->warehouse_city && $agent->warehouseCountry), @endif{{ $agent->warehouseCountry?->name }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-3 text-xs text-body">{{ $agent->user?->name ?? '—' }}</td>
                        <td class="px-3 py-3 text-xs text-body">{{ $agent->agent_type->label() }}</td>
                        <td class="px-3 py-3 text-xs text-body">
                            @if ($agent->warehouseCountry)<span class="inline-flex items-center gap-1.5"><x-flag :iso="$agent->warehouseCountry->iso2" class="h-3 w-4.5" /> {{ $agent->warehouseCountry->name }}</span>@else—@endif
                        </td>
                        <td class="px-3 py-3 text-xs text-body"><x-icon name="star" class="inline h-3 w-3 text-amber-500" /> {{ number_format((float) $agent->rating, 1) }} <span class="text-faint">({{ $agent->reviews_count }})</span></td>
                        <td class="px-3 py-3 text-xs text-body">{{ $agent->completed_orders }}</td>
                        <td class="px-3 py-3 text-xs text-body">{{ $agent->successRate() !== null ? $agent->successRate().'%' : '—' }}</td>
                        <td class="px-3 py-3"><x-status-badge :status="$agent->status" class="text-[10px]" /></td>
                        <td class="px-3 py-3">@if ($agent->is_featured)<span class="pill bg-purple-500/15 text-purple-600 text-[10px]">Featured</span>@else<span class="text-xs text-faint">—</span>@endif</td>
                        <td class="px-3 py-3 text-xs text-faint">{{ $agent->user?->last_seen_at?->diffForHumans() ?? 'Never' }}</td>
                        <td class="px-3 py-3 text-right" @click.stop>
                            <div class="relative inline-block" x-data="{ open: false }" @click.outside="open = false">
                                <button type="button" @click="open = !open" class="rounded-lg p-1.5 hover:surface-2"><x-icon name="chevron-down" class="h-4 w-4" /></button>
                                <div x-show="open" x-cloak x-transition class="card-solid absolute right-0 z-20 mt-1 w-56 rounded-xl border border-app p-1.5 text-left shadow-lg">
                                    <button type="button" @click="openDrawer('{{ $agent->slug }}'); open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-body hover:surface"><x-icon name="eye" class="h-4 w-4" /> View agent</button>
                                    <a href="{{ route('admin.agents.show', $agent) }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-body hover:surface"><x-icon name="doc" class="h-4 w-4" /> Edit profile</a>
                                    @if ($agent->status->value === 'pending')
                                        <form method="POST" action="{{ route('admin.agents.approve', $agent) }}" onsubmit="return confirm('Approve {{ $agent->business_name }}?')">@csrf<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="check" class="h-4 w-4" /> Approve</button></form>
                                        <button type="button" @click="rejectTarget='{{ $agent->slug }}'; rejectModal=true; open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-rose-500 hover:surface"><x-icon name="x" class="h-4 w-4" /> Reject</button>
                                    @endif
                                    @if ($agent->status->value !== 'suspended')
                                        <button type="button" @click="suspendTarget='{{ $agent->slug }}'; suspendModal=true; open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-amber-600 hover:surface"><x-icon name="ban" class="h-4 w-4" /> Suspend</button>
                                    @else
                                        <form method="POST" action="{{ route('admin.agents.restore', $agent) }}" onsubmit="return confirm('Restore {{ $agent->business_name }}?')">@csrf<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="refresh" class="h-4 w-4" /> Restore</button></form>
                                    @endif
                                    @if ($agent->status->value === 'approved')
                                        <form method="POST" action="{{ route('admin.agents.feature', $agent) }}">@csrf<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="star" class="h-4 w-4" /> {{ $agent->is_featured ? 'Remove featured' : 'Feature agent' }}</button></form>
                                    @endif
                                    <a href="{{ route('admin.agents.show', $agent) }}#reviews" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-body hover:surface"><x-icon name="star" class="h-4 w-4" /> View reviews</a>
                                    @if ($agent->user)<a href="{{ route('admin.users.show', $agent->user) }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-body hover:surface"><x-icon name="user-circle" class="h-4 w-4" /> Open owner profile</a>@endif
                                    <button type="button" @click="messageTarget='{{ $agent->slug }}'; messageModal=true; open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-body hover:surface"><x-icon name="bell" class="h-4 w-4" /> Send message</button>
                                    <button type="button" @click="deleteTarget='{{ $agent->slug }}'; deleteModal=true; open=false" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-rose-500 hover:surface"><x-icon name="trash" class="h-4 w-4" /> Delete agent</button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="p-0">
                        <x-empty icon="users" title="No agents found" message="No agents match the selected filters.">
                            <x-slot:action>
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('admin.agents.index') }}" class="qa-btn">Clear filters</a>
                                    <button type="button" class="qa-btn qa-btn-good" @click="addOpen = true">Add agent</button>
                                    <button type="button" class="qa-btn" @click="window.location.reload()">Refresh</button>
                                </div>
                            </x-slot:action>
                        </x-empty>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $items->links() }}</div>

    {{-- ============ BULK / REJECT / SUSPEND / MESSAGE / DELETE FORMS ============ --}}
    <form :action="'{{ route('admin.agents.bulk-action') }}'" method="POST" x-ref="bulkForm" class="hidden">
        @csrf
        <input type="hidden" name="action" x-bind:value="bulkActionType">
        <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
        <input type="hidden" name="reason" x-bind:value="bulkReason">
        <input type="hidden" name="subject" x-bind:value="bulkSubject">
        <input type="hidden" name="body" x-bind:value="bulkBody">
    </form>

    <div x-show="bulkModal" x-cloak @click.self="bulkModal=null" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" style="display:none">
        <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6">
            <h3 class="font-semibold text-strong" x-text="'Bulk ' + bulkModal + ' — ' + selected.length + ' agents'"></h3>
            <div class="mt-4 space-y-3">
                <template x-if="bulkModal === 'suspend'"><div><label class="label">Reason</label><textarea x-model="bulkReason" rows="2" class="field" required></textarea></div></template>
                <template x-if="bulkModal === 'notify'">
                    <div class="space-y-3">
                        <div><label class="label">Subject</label><input x-model="bulkSubject" class="field"></div>
                        <div><label class="label">Message</label><textarea x-model="bulkBody" rows="3" class="field"></textarea></div>
                    </div>
                </template>
                <div class="flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="bulkModal=null">Cancel</button><button type="button" class="btn btn-primary flex-1" @click="runBulk(bulkModal)">Apply</button></div>
            </div>
        </div>
    </div>

    <form method="POST" :action="`/admin/agents/${rejectTarget}/reject`" x-show="rejectModal" x-cloak @click.self="rejectModal=false" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" style="display:none">
        @csrf
        <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
            <h3 class="font-semibold text-rose-600">Reject agent application</h3>
            <textarea name="reason" required rows="3" class="field mt-3" placeholder="Reason (shown to the applicant)"></textarea>
            <div class="mt-4 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="rejectModal=false">Cancel</button><button class="btn btn-danger flex-1">Reject</button></div>
        </div>
    </form>

    <form method="POST" :action="`/admin/agents/${suspendTarget}/suspend`" x-show="suspendModal" x-cloak @click.self="suspendModal=false" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" style="display:none">
        @csrf
        <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
            <h3 class="font-semibold text-amber-600">Suspend agent</h3>
            <textarea name="reason" required rows="3" class="field mt-3" placeholder="Reason (shown to the agent)"></textarea>
            <div class="mt-4 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="suspendModal=false">Cancel</button><button class="btn btn-danger flex-1">Suspend</button></div>
        </div>
    </form>

    <form method="POST" :action="`/admin/agents/${messageTarget}/notify`" x-show="messageModal" x-cloak @click.self="messageModal=false" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" style="display:none">
        @csrf
        <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
            <h3 class="font-semibold text-strong">Send message to agent</h3>
            <input name="subject" required class="field mt-3" placeholder="Subject">
            <textarea name="body" required rows="3" class="field mt-2" placeholder="Message"></textarea>
            <div class="mt-4 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="messageModal=false">Cancel</button><button class="btn btn-primary flex-1">Send</button></div>
        </div>
    </form>

    <form method="POST" :action="`/admin/agents/${deleteTarget}`" x-show="deleteModal" x-cloak @click.self="deleteModal=false" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" style="display:none">
        @csrf @method('DELETE')
        <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
            <h3 class="font-semibold text-rose-600">Delete agent</h3>
            <p class="mt-1 text-xs text-muted">This removes the agent from the directory. Their order and review history is preserved for records.</p>
            <textarea name="reason" required rows="2" class="field mt-3" placeholder="Reason for deletion"></textarea>
            <div class="mt-4 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="deleteModal=false">Cancel</button><button class="btn btn-danger flex-1">Delete</button></div>
        </div>
    </form>

    {{-- ============ ADD AGENT (redirect to registration) ============ --}}
    <div x-show="addOpen" x-cloak @click.self="addOpen=false" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" style="display:none">
        <div class="card-solid w-full max-w-sm rounded-2xl border border-app p-6 text-center" @click.stop>
            <h3 class="font-semibold text-strong">Add an agent</h3>
            <p class="mt-2 text-sm text-muted">New agents apply through the public agent registration form, then appear here for review.</p>
            <a href="{{ route('register.agent') }}" target="_blank" class="btn btn-primary mt-4 w-full">Open registration form</a>
            <button type="button" class="btn btn-ghost mt-2 w-full" @click="addOpen=false">Close</button>
        </div>
    </div>

    {{-- ============ AGENT QUICK-PREVIEW DRAWER ============ --}}
    <div x-show="drawerOpen" x-cloak class="fixed inset-0 z-50 flex justify-end bg-black/50" @keydown.window.escape="drawerOpen=false">
        <div class="h-full w-full max-w-xl overflow-y-auto card-solid border-l border-app p-6" @click.outside="drawerOpen=false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
            <template x-if="!drawer">
                <div class="space-y-3"><div class="skel-block h-8 w-40"></div><div class="skel-block h-24"></div><div class="skel-block h-24"></div></div>
            </template>
            <template x-if="drawer">
                <div class="space-y-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-strong" x-text="drawer.agent.business_name"></h2>
                            <p class="text-xs text-faint" x-text="drawer.agent.agent_type + ' · #' + drawer.agent.id"></p>
                        </div>
                        <button type="button" class="rounded-lg p-1.5 hover:surface-2" @click="drawerOpen=false"><x-icon name="x" class="h-4 w-4" /></button>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div><p class="text-xs text-faint">Owner</p><p class="text-body" x-text="drawer.agent.owner"></p></div>
                        <div><p class="text-xs text-faint">Email</p><p class="text-body" x-text="drawer.agent.email"></p></div>
                        <div><p class="text-xs text-faint">Phone</p><p class="text-body" x-text="drawer.agent.phone || '—'"></p></div>
                        <div><p class="text-xs text-faint">WhatsApp</p><p class="text-body" x-text="drawer.agent.whatsapp || '—'"></p></div>
                        <div><p class="text-xs text-faint">Country / City</p><p class="text-body" x-text="(drawer.agent.country || '—') + ' · ' + (drawer.agent.city || '—')"></p></div>
                        <div><p class="text-xs text-faint">Address</p><p class="text-body" x-text="drawer.agent.address || '—'"></p></div>
                        <div><p class="text-xs text-faint">Joined</p><p class="text-body" x-text="drawer.agent.joined"></p></div>
                        <div><p class="text-xs text-faint">Rating</p><p class="text-body" x-text="'★ ' + drawer.agent.rating.toFixed(1) + ' (' + drawer.agent.reviews_count + ')'"></p></div>
                    </div>

                    <p class="text-sm text-body" x-text="drawer.agent.bio"></p>

                    <div>
                        <p class="text-xs font-semibold uppercase text-faint">Performance</p>
                        <div class="mt-2 grid grid-cols-4 gap-2">
                            <div class="rounded-xl surface-2 p-2 text-center"><p class="text-lg font-bold text-strong" x-text="drawer.performance.total_leads"></p><p class="text-[10px] text-faint">Total orders</p></div>
                            <div class="rounded-xl surface-2 p-2 text-center"><p class="text-lg font-bold text-emerald-600" x-text="drawer.performance.completed"></p><p class="text-[10px] text-faint">Completed</p></div>
                            <div class="rounded-xl surface-2 p-2 text-center"><p class="text-lg font-bold text-sky-600" x-text="drawer.performance.active"></p><p class="text-[10px] text-faint">Active</p></div>
                            <div class="rounded-xl surface-2 p-2 text-center"><p class="text-lg font-bold text-slate-500" x-text="drawer.performance.closed"></p><p class="text-[10px] text-faint">Closed</p></div>
                            <div class="rounded-xl surface-2 p-2 text-center"><p class="text-lg font-bold text-strong" x-text="(drawer.performance.success_rate ?? '—') + (drawer.performance.success_rate !== null ? '%' : '')"></p><p class="text-[10px] text-faint">Success rate</p></div>
                            <div class="rounded-xl surface-2 p-2 text-center"><p class="text-lg font-bold text-strong" x-text="drawer.performance.avg_response_hours !== null ? drawer.performance.avg_response_hours + 'h' : '—'"></p><p class="text-[10px] text-faint">Response time</p></div>
                            <div class="rounded-xl surface-2 p-2 text-center"><p class="text-lg font-bold text-strong" x-text="drawer.agent.reviews_count"></p><p class="text-[10px] text-faint">Reviews</p></div>
                            <div class="rounded-xl surface-2 p-2 text-center"><p class="text-lg font-bold text-strong">—</p><p class="text-[10px] text-faint">Earnings (not tracked)</p></div>
                        </div>
                        <div class="mt-3 flex h-16 items-end gap-1.5">
                            <template x-for="m in drawer.performance.monthly" :key="m.label">
                                <div class="flex-1 rounded-t bg-brand-500/70" :style="`height: ${Math.max(3, (m.count / Math.max(1, Math.max(...drawer.performance.monthly.map(x=>x.count)))) * 100)}%`" :title="m.label + ': ' + m.count"></div>
                            </template>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase text-faint">Verification checklist</p>
                        <div class="mt-2 grid grid-cols-2 gap-1.5 text-xs">
                            <template x-for="[key, val] in Object.entries(drawer.agent.checklist)" :key="key">
                                <div class="flex items-center gap-1.5">
                                    <span x-show="val === true" class="text-emerald-600">✓</span>
                                    <span x-show="val === false" class="text-rose-500">✕</span>
                                    <span x-show="val === null" class="text-faint">—</span>
                                    <span class="capitalize text-body" x-text="key.replace('_',' ')"></span>
                                    <span x-show="val === null" class="text-[10px] text-faint">(not tracked)</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 border-t border-app pt-3">
                        <template x-if="drawer.agent.status === 'pending'">
                            <span class="flex flex-wrap gap-2">
                                <button type="button" class="qa-btn qa-btn-good" @click="approveFromDrawer()">Approve</button>
                                <button type="button" class="qa-btn qa-btn-danger" @click="rejectTarget = drawer.agent.slug; rejectModal = true">Reject</button>
                                <button type="button" class="qa-btn" @click="requestInfoTarget = drawer.agent.slug; requestInfoModal = true">Request more info</button>
                            </span>
                        </template>
                        <template x-if="drawer.agent.status !== 'suspended'">
                            <button type="button" class="qa-btn qa-btn-warn" @click="suspendTarget = drawer.agent.slug; suspendModal = true">Suspend</button>
                        </template>
                        <template x-if="drawer.agent.status === 'suspended'">
                            <button type="button" class="qa-btn qa-btn-good" @click="restoreFromDrawer()">Restore</button>
                        </template>
                        <a :href="`/admin/agents/${drawer.agent.slug}`" class="qa-btn">Edit profile</a>
                    </div>

                    <div class="border-t border-app pt-3">
                        <p class="text-xs font-semibold uppercase text-faint">Featured settings</p>
                        <form method="POST" :action="`/admin/agents/${drawer.agent.slug}/feature-settings`" class="mt-2 grid grid-cols-2 gap-2">
                            @csrf
                            <input type="date" name="featured_from" :value="drawer.agent.featured_from" class="field text-xs" placeholder="Start date">
                            <input type="date" name="featured_until" :value="drawer.agent.featured_until" class="field text-xs" placeholder="End date">
                            <input type="number" name="featured_priority" :value="drawer.agent.featured_priority" min="0" max="100" class="field text-xs" placeholder="Priority">
                            <input type="text" name="featured_label" :value="drawer.agent.featured_label" maxlength="40" class="field text-xs" placeholder="Label (e.g. Top rated)">
                            <button class="qa-btn col-span-2" :disabled="drawer.agent.status !== 'approved'">Save &amp; feature</button>
                        </form>
                    </div>

                    <div class="border-t border-app pt-3">
                        <p class="text-xs font-semibold uppercase text-faint">Reviews</p>
                        <div class="mt-2 space-y-2 text-sm">
                            <template x-if="drawer.reviews.length === 0"><p class="text-xs text-faint">No reviews yet.</p></template>
                            <template x-for="r in drawer.reviews" :key="r.date + r.customer">
                                <div class="rounded-lg surface-2 p-2">
                                    <p class="text-xs text-body"><span class="font-semibold" x-text="r.customer"></span> · <span x-text="'★'.repeat(r.rating)"></span> · <span class="text-faint" x-text="r.date"></span></p>
                                    <p class="text-xs text-muted" x-text="r.comment"></p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="border-t border-app pt-3">
                        <p class="text-xs font-semibold uppercase text-faint">Complaints</p>
                        <p class="mt-1 text-xs text-faint">Complaints are not yet linked to specific agents in this platform — only a platform-wide open-agent-complaint count is available on the summary card above.</p>
                    </div>

                    <div class="border-t border-app pt-3">
                        <p class="text-xs font-semibold uppercase text-faint">Internal notes (private, never shown to the agent)</p>
                        <form method="POST" :action="`/admin/agents/${drawer.agent.slug}/notes`" class="mt-2">
                            @csrf
                            <textarea name="admin_notes" rows="3" class="field text-sm" x-text="drawer.agent.admin_notes"></textarea>
                            <button class="qa-btn mt-2">Save notes</button>
                        </form>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <form method="POST" :action="`/admin/agents/${requestInfoTarget}/request-info`" x-show="requestInfoModal" x-cloak @click.self="requestInfoModal=false" class="fixed inset-0 z-[60] grid place-items-center bg-black/50 p-4" style="display:none">
        @csrf
        <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.stop>
            <h3 class="font-semibold text-strong">Request more information</h3>
            <textarea name="message" required rows="3" class="field mt-3" placeholder="What do you need from the agent?"></textarea>
            <div class="mt-4 flex gap-2"><button type="button" class="btn btn-ghost flex-1" @click="requestInfoModal=false">Cancel</button><button class="btn btn-primary flex-1">Send request</button></div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function agentsConsole() {
    return {
        filtersOpen: false,
        selected: [],
        addOpen: false,
        drawerOpen: false, drawer: null,
        rejectModal: false, rejectTarget: null,
        suspendModal: false, suspendTarget: null,
        messageModal: false, messageTarget: null,
        deleteModal: false, deleteTarget: null,
        requestInfoModal: false, requestInfoTarget: null,
        bulkModal: null, bulkActionType: '', bulkReason: '', bulkSubject: '', bulkBody: '',
        presets: JSON.parse(localStorage.getItem('admin-agents-presets') || '[]'),
        init() {
            if (window.ShortcutManager) {
                window.ShortcutManager.registerAction('agents-search', () => this.$refs.searchInput?.focus());
                window.ShortcutManager.registerAction('agents-filters', () => { this.filtersOpen = !this.filtersOpen; });
                window.ShortcutManager.registerAction('agents-add', () => { this.addOpen = true; });
            }
            window.addEventListener('close-overlays', () => { this.drawerOpen = false; this.bulkModal = null; this.rejectModal = false; this.suspendModal = false; this.messageModal = false; this.deleteModal = false; this.requestInfoModal = false; this.addOpen = false; });
        },
        activeFilterCount() {
            const p = new URLSearchParams(window.location.search);
            return ['agent_type', 'country_id', 'rating_min', 'featured', 'joined_from', 'joined_to'].filter((k) => p.get(k)).length;
        },
        clearFilters() { window.location = '{{ route('admin.agents.index', ['tab' => $tab]) }}'; },
        savePreset() {
            const name = prompt('Name this filter preset:');
            if (!name) return;
            this.presets.push({ name, query: window.location.href });
            localStorage.setItem('admin-agents-presets', JSON.stringify(this.presets));
        },
        deletePreset(name) {
            this.presets = this.presets.filter((p) => p.name !== name);
            localStorage.setItem('admin-agents-presets', JSON.stringify(this.presets));
        },
        toggleAll(checked) { this.selected = checked ? @json($items->pluck('id')) : []; },
        runBulk(action) {
            if (this.selected.length === 0) return;
            this.bulkActionType = action;
            this.bulkModal = null;
            this.$nextTick(() => this.$refs.bulkForm.submit());
        },
        async openDrawer(slug) {
            this.drawerOpen = true;
            this.drawer = null;
            try {
                const res = await fetch(`/admin/agents/${slug}/row-detail`);
                this.drawer = await res.json();
            } catch (e) { this.drawerOpen = false; }
        },
        approveFromDrawer() {
            if (!confirm('Approve this agent?')) return;
            const f = document.createElement('form');
            f.method = 'POST'; f.action = `/admin/agents/${this.drawer.agent.slug}/approve`;
            f.innerHTML = '@csrf';
            document.body.appendChild(f); f.submit();
        },
        restoreFromDrawer() {
            if (!confirm('Restore this agent?')) return;
            const f = document.createElement('form');
            f.method = 'POST'; f.action = `/admin/agents/${this.drawer.agent.slug}/restore`;
            f.innerHTML = '@csrf';
            document.body.appendChild(f); f.submit();
        },
    };
}
</script>
@endpush
