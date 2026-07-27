{{-- Attention Center: everything pending across the platform, ranked by severity. --}}
<div id="attention">
    <div class="mb-3 flex items-center justify-between">
        <h2 class="text-lg font-bold text-strong">Needs attention</h2>
        <span class="text-xs text-faint">{{ count($attention) }} item{{ count($attention) === 1 ? '' : 's' }}</span>
    </div>
    @if (empty($attention))
        <x-empty icon="check-circle" title="Nothing needs attention" message="All queues are clear right now." />
    @else
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($attention as $item)
                @php
                    $sevColor = match ($item['severity']) { 'critical' => 'rose', 'high' => 'amber', 'medium' => 'sky', default => 'slate' };
                @endphp
                <a href="{{ $item['href'] }}" class="card-solid block rounded-2xl border border-app p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="pill bg-{{ $sevColor }}-500/15 text-{{ $sevColor }}-600 text-[10px] uppercase">{{ $item['severity'] }}</span>
                        <span class="text-lg font-bold text-strong">{{ $item['count'] }}</span>
                    </div>
                    <p class="mt-2 text-sm font-medium text-strong">{{ $item['label'] }}</p>
                    <div class="mt-1 flex items-center justify-between text-[11px] text-faint">
                        @if ($item['amount'])<span>{{ money($item['amount'], $currency) }}</span>@else<span></span>@endif
                        @if ($item['oldest'])<span>since {{ \Illuminate\Support\Carbon::parse($item['oldest'])->diffForHumans() }}</span>@endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
