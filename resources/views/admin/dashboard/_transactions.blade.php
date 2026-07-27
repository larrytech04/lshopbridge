{{-- Live Transaction Monitor: unified recent deposits/funding/orders, click → side drawer. --}}
<div id="transactions">
    <div class="mb-3 flex items-center justify-between">
        <h2 class="text-lg font-bold text-strong">Live transaction monitor</h2>
        <a href="{{ route('admin.deposits.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">View all deposits →</a>
    </div>
    <div class="overflow-x-auto rounded-2xl border border-app">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="border-b border-app text-muted" style="background: var(--surface-1);">
                <tr>
                    <th class="px-3 py-2.5 font-medium">Time</th>
                    <th class="px-3 py-2.5 font-medium">Reference</th>
                    <th class="px-3 py-2.5 font-medium">Customer</th>
                    <th class="px-3 py-2.5 font-medium">Country</th>
                    <th class="px-3 py-2.5 font-medium">Type</th>
                    <th class="px-3 py-2.5 font-medium">Amount</th>
                    <th class="px-3 py-2.5 font-medium">Status</th>
                    <th class="px-3 py-2.5 font-medium">Risk</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-app">
                @forelse ($transactions as $t)
                    <tr class="cursor-pointer hover:surface" @click="openTransaction('{{ $t['kind'] }}', {{ $t['id'] }})">
                        <td class="px-3 py-2.5 text-xs text-faint">{{ $t['time']->diffForHumans() }}</td>
                        <td class="px-3 py-2.5 text-xs text-body">{{ $t['ref'] }}</td>
                        <td class="px-3 py-2.5 text-body">{{ $t['user']->name ?? '—' }}</td>
                        <td class="px-3 py-2.5 text-xs text-body">{{ $t['user']->country->name ?? '—' }}</td>
                        <td class="px-3 py-2.5"><span class="pill surface text-[10px] text-body">{{ $t['type'] }}</span></td>
                        <td class="px-3 py-2.5 text-body">{{ money($t['amount'], $t['currency']) }}</td>
                        <td class="px-3 py-2.5"><x-status-badge :status="$t['status']" class="text-[10px]" /></td>
                        <td class="px-3 py-2.5">@if ($t['risk'])<span class="pill bg-rose-500/15 text-rose-600 text-[10px]">Flagged</span>@else<span class="text-xs text-faint">—</span>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="p-0"></td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($transactions->isEmpty())
            <x-empty icon="receipt" title="No recent transactions" message="Deposits, funding, and orders will appear here as they happen." />
        @endif
    </div>
</div>
