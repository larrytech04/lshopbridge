{{-- Customer Operations. --}}
<x-glass-card solid>
    <h3 class="font-semibold text-strong">Customer operations</h3>
    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
        <div><p class="text-xs text-faint">Total users</p><p class="text-lg font-bold text-strong">{{ number_format($customer['total']) }}</p></div>
        <div><p class="text-xs text-faint">Active</p><p class="text-lg font-bold text-strong">{{ number_format($customer['active']) }}</p></div>
        <div><p class="text-xs text-faint">Online now</p><p class="text-lg font-bold text-strong">{{ number_format($customer['online']) }}</p></div>
        <div><p class="text-xs text-faint">New this period</p><p class="text-lg font-bold text-strong">{{ number_format($customer['newInPeriod']) }}</p></div>
        <div><p class="text-xs text-faint">KYC completion</p><p class="text-lg font-bold text-strong">{{ $customer['kycRate'] }}%</p></div>
        <div><p class="text-xs text-faint">Inactive 30d+</p><p class="text-lg font-bold text-strong">{{ number_format($customer['inactive30d']) }}</p></div>
    </div>

    <div class="mt-4 border-t border-app pt-3">
        <p class="mb-2 text-xs font-semibold uppercase text-faint">Users by role</p>
        <div class="flex flex-wrap gap-2">
            @foreach ($customer['byRole'] as $role => $n)
                <span class="pill surface text-xs text-body">{{ ucfirst(str_replace('_', ' ', $role)) }}: {{ $n }}</span>
            @endforeach
        </div>
    </div>

    <div class="mt-4 border-t border-app pt-3">
        <p class="mb-2 text-xs font-semibold uppercase text-faint">Attention lists</p>
        <div class="space-y-1.5 text-sm">
            <a href="{{ route('admin.users.index', ['kyc_status' => 'pending']) }}" class="flex items-center justify-between rounded-lg px-2 py-1.5 hover:surface"><span class="text-body">Incomplete KYC</span><span class="font-semibold text-strong">{{ $customer['incompleteKyc'] }}</span></a>
            <a href="{{ route('admin.users.index') }}" class="flex items-center justify-between rounded-lg px-2 py-1.5 hover:surface"><span class="text-body">Frozen wallets</span><span class="font-semibold text-strong">{{ $customer['frozenWallets'] }}</span></a>
            <a href="{{ route('admin.users.index') }}" class="flex items-center justify-between rounded-lg px-2 py-1.5 hover:surface"><span class="text-body">Users with failed deposits</span><span class="font-semibold text-strong">{{ $customer['failedDeposits'] }}</span></a>
            <a href="{{ route('admin.users.index') }}" class="flex items-center justify-between rounded-lg px-2 py-1.5 hover:surface"><span class="text-body">Unresolved support tickets</span><span class="font-semibold text-strong">{{ $customer['unresolvedTickets'] }}</span></a>
        </div>
    </div>
</x-glass-card>
