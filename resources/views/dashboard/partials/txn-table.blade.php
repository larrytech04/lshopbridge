<div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead class="border-y border-app text-muted">
            <tr>
                <th class="px-5 py-3 font-medium">{{ __('Reference') }}</th>
                <th class="px-5 py-3 font-medium">{{ __('Description') }}</th>
                <th class="px-5 py-3 font-medium">{{ __('Category') }}</th>
                <th class="px-5 py-3 text-right font-medium">{{ __('Amount') }}</th>
                <th class="px-5 py-3 text-right font-medium">{{ __('Balance') }}</th>
                <th class="px-5 py-3 font-medium">{{ __('Date') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-app">
            @forelse ($transactions as $tx)
                <tr class="hover:surface-2">
                    <td class="px-5 py-3 font-mono text-xs text-muted">{{ $tx->reference }}</td>
                    <td class="px-5 py-3 text-body">{{ $tx->description }}</td>
                    <td class="px-5 py-3"><span class="pill surface text-body ring-1 ring-white/10">{{ ucfirst($tx->category) }}</span></td>
                    <td class="px-5 py-3 text-right font-bold {{ $tx->isCredit() ? 'text-emerald-600' : 'text-rose-600' }}">
                        {{ $tx->isCredit() ? '+' : '−' }}{{ disp($tx->amount) }}
                    </td>
                    <td class="px-5 py-3 text-right text-body">{{ disp($tx->balance_after) }}</td>
                    <td class="px-5 py-3 text-muted">{{ $tx->created_at->format('M j, H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-faint">{{ __('No transactions yet.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
