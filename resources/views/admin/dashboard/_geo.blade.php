{{-- Geographic breakdown. Note: the schema has no lat/lng or region/state data anywhere
     (only a country_id on users), so this is an honest country-level ranking rather than
     a fabricated interactive map — see summary for what a real map would require. --}}
<x-glass-card solid>
    <div class="flex items-center justify-between">
        <h3 class="font-semibold text-strong">Geographic breakdown</h3>
        <span class="text-xs text-faint">Country-level · {{ $period['label'] }}</span>
    </div>

    <div class="mt-4 grid gap-5 sm:grid-cols-3">
        <div>
            <p class="mb-2 text-xs font-semibold uppercase text-faint">Top countries by new users</p>
            <div class="space-y-1.5">
                @forelse ($geo['byUsers'] as $c)
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-1.5 text-body"><x-flag :iso="$c->iso2" class="h-3 w-4.5" /> {{ $c->name }}</span>
                        <span class="font-semibold text-strong">{{ $c->users_count }}</span>
                    </div>
                @empty
                    <p class="text-xs text-faint">No new users in this period.</p>
                @endforelse
            </div>
        </div>
        <div>
            <p class="mb-2 text-xs font-semibold uppercase text-faint">Top countries by deposits</p>
            <div class="space-y-1.5">
                @forelse ($geo['byDeposits'] as $c)
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-1.5 text-body"><x-flag :iso="$c->iso2" class="h-3 w-4.5" /> {{ $c->name }}</span>
                        <span class="font-semibold text-strong">{{ money($c->total, $currency) }}</span>
                    </div>
                @empty
                    <p class="text-xs text-faint">No confirmed deposits in this period.</p>
                @endforelse
            </div>
        </div>
        <div>
            <p class="mb-2 text-xs font-semibold uppercase text-faint">Top countries by orders</p>
            <div class="space-y-1.5">
                @forelse ($geo['byOrders'] as $c)
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-1.5 text-body"><x-flag :iso="$c->iso2" class="h-3 w-4.5" /> {{ $c->name }}</span>
                        <span class="font-semibold text-strong">{{ $c->order_count }} orders</span>
                    </div>
                @empty
                    <p class="text-xs text-faint">No paid orders in this period.</p>
                @endforelse
            </div>
        </div>
    </div>
    <p class="mt-4 text-[11px] text-faint">A pin-level map would require storing coordinates or a region/state field — not currently in the schema. This ranks real activity by registered country instead of showing fabricated locations.</p>
</x-glass-card>
