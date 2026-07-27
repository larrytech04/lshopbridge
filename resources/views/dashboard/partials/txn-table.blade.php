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
        @forelse ($transactions as $tx)
            <tbody x-data="{ open: false }" class="divide-y divide-app">
                <tr @click="open = !open" class="cursor-pointer hover:surface-2">
                    <td class="px-5 py-3 font-mono text-xs text-muted">{{ $tx->reference }}</td>
                    <td class="px-5 py-3 text-body">{{ $tx->description }}</td>
                    <td class="px-5 py-3"><span class="pill surface text-body ring-1 ring-white/10">{{ ucfirst($tx->category) }}</span></td>
                    <td class="px-5 py-3 text-right font-bold {{ $tx->isCredit() ? 'text-emerald-600' : 'text-rose-600' }}">
                        {{ $tx->isCredit() ? '+' : '−' }}{{ disp($tx->amount) }}
                    </td>
                    <td class="px-5 py-3 text-right text-body">{{ disp($tx->balance_after) }}</td>
                    <td class="px-5 py-3 text-muted">{{ $tx->created_at->format('M j, H:i') }}</td>
                </tr>
                <tr x-show="open" style="display:none">
                    <td colspan="6" class="surface-2 px-5 py-4">
                        <div class="grid gap-x-6 gap-y-1.5 text-xs sm:grid-cols-2">
                            <p class="text-muted">{{ __('Type') }}: <span class="font-semibold text-body">{{ ucfirst($tx->type) }}</span></p>
                            <p class="text-muted">{{ __('Currency') }}: <span class="font-semibold text-body">{{ $tx->currency }}</span></p>
                            <p class="text-muted">{{ __('Full reference') }}: <span class="font-mono text-body">{{ $tx->reference }}</span></p>
                            <p class="text-muted">{{ __('Recorded') }}: <span class="text-body">{{ $tx->created_at->format('M j, Y g:ia') }}</span></p>
                        </div>
                        @if ($tx->sourceUrl())
                            <a href="{{ $tx->sourceUrl() }}" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-brand-500 hover:text-brand-400">
                                {{ __('View related record') }} <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                            </a>
                        @endif
                    </td>
                </tr>
            </tbody>
        @empty
            <tbody><tr><td colspan="6" class="px-5 py-10 text-center text-faint">{{ __('No transactions yet.') }}</td></tr></tbody>
        @endforelse
    </table>
</div>
