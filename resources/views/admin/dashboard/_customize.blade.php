{{-- Dashboard customization: hide/show sections, saved per admin (users.preferences). --}}
<div x-show="customizeOpen" x-cloak @click.self="customizeOpen=false" class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" style="display:none">
    <div class="card-solid w-full max-w-md rounded-2xl border border-app p-6" @click.outside="customizeOpen=false">
        <h3 class="font-semibold text-strong">Customize dashboard</h3>
        <p class="text-xs text-muted">Hide sections you don't need. Saved to your admin account.</p>
        <div class="mt-4 grid max-h-96 grid-cols-2 gap-2 overflow-y-auto">
            @foreach ([
                'attention' => 'Attention Center', 'kpis' => 'Executive KPIs', 'financial' => 'Financial Performance',
                'reconciliation' => 'Reconciliation', 'geo' => 'Geographic Breakdown', 'insights' => 'Business Insights',
                'transactions' => 'Transaction Monitor', 'customer' => 'Customer Operations', 'compliance' => 'Compliance & Risk',
                'marketplace' => 'Marketplace Operations', 'agents' => 'Agent Network', 'providers' => 'Payment Provider Health',
                'system' => 'System Health', 'support' => 'Support Operations', 'activity' => 'Admin Activity',
            ] as $key => $label)
                <label class="flex items-center gap-2 rounded-xl surface-2 px-3 py-2 text-sm text-body">
                    <input type="checkbox" :checked="!hidden.includes('{{ $key }}')" @change="toggleHidden('{{ $key }}')" class="rounded"> {{ $label }}
                </label>
            @endforeach
        </div>
        <div class="mt-4 flex gap-2">
            <button type="button" class="btn btn-ghost flex-1" @click="hidden = []; saveLayout()">Restore default</button>
            <button type="button" class="btn btn-primary flex-1" @click="saveLayout()">Save layout</button>
        </div>
    </div>
</div>
