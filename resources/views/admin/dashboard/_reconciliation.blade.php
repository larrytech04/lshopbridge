{{-- Financial Reconciliation: wallet ledger internal-consistency check. --}}
@php
    $statusMap = ['balanced' => ['Balanced', 'emerald'], 'minor' => ['Small discrepancy', 'amber'], 'critical' => ['Critical discrepancy', 'rose']];
    [$statusLabel, $statusColor] = $statusMap[$reconciliation['status']];
@endphp
<x-glass-card solid id="reconciliation">
    <div class="flex items-center justify-between">
        <h3 class="font-semibold text-strong">Financial reconciliation</h3>
        <span class="pill bg-{{ $statusColor }}-500/15 text-{{ $statusColor }}-600 text-[10px] uppercase">{{ $statusLabel }}</span>
    </div>
    <div class="mt-4 space-y-2 text-sm">
        <div class="flex items-center justify-between"><span class="text-muted">Wallet balances (liabilities)</span><span class="font-semibold text-strong">{{ money($reconciliation['balance'], $currency) }}</span></div>
        <div class="flex items-center justify-between"><span class="text-muted">Locked balances</span><span class="font-semibold text-strong">{{ money($reconciliation['locked'], $currency) }}</span></div>
        <div class="flex items-center justify-between"><span class="text-muted">Available (unlocked)</span><span class="font-semibold text-strong">{{ money($reconciliation['available'], $currency) }}</span></div>
        <div class="flex items-center justify-between"><span class="text-muted">Pending settlement</span><span class="font-semibold text-strong">{{ money($reconciliation['pendingSettlement'], $currency) }}</span></div>
        <div class="border-t border-app pt-2"></div>
        <div class="flex items-center justify-between"><span class="text-muted">Lifetime deposits</span><span class="text-body">{{ money($reconciliation['lifetimeDeposits'], $currency) }}</span></div>
        <div class="flex items-center justify-between"><span class="text-muted">Lifetime funding sent</span><span class="text-body">{{ money($reconciliation['lifetimeFunding'], 'CNY') }}</span></div>
        <div class="flex items-center justify-between"><span class="text-muted">Lifetime shop spend</span><span class="text-body">{{ money($reconciliation['lifetimeShopSpend'], $currency) }}</span></div>
        <div class="flex items-center justify-between"><span class="text-muted">Lifetime refunds</span><span class="text-body">{{ money($reconciliation['lifetimeRefunds'], $currency) }}</span></div>
        <div class="border-t border-app pt-2"></div>
        <div class="flex items-center justify-between"><span class="text-muted">Expected balance (from ledger)</span><span class="text-body">{{ money($reconciliation['expected'], $currency) }}</span></div>
        <div class="flex items-center justify-between"><span class="font-semibold text-strong">Reconciliation gap</span><span class="font-bold text-{{ $statusColor }}-600">{{ money($reconciliation['gap'], $currency) }}</span></div>
    </div>
    <p class="mt-3 text-[11px] text-faint">Gap = live wallet balances vs. what the wallet_transactions ledger implies. This is an internal-consistency check, not a claim about external payment-provider settlement — the platform doesn't yet track provider-held receivables separately.</p>
</x-glass-card>
