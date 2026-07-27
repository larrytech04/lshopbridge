{{-- Business Insights: deterministic facts computed from real data — explicitly not AI. --}}
<x-glass-card solid>
    <div class="flex items-center justify-between">
        <h3 class="font-semibold text-strong">Business insights</h3>
        <span class="pill surface text-[10px] text-faint">Computed analytics, not AI</span>
    </div>
    <div class="mt-3 space-y-2.5">
        @foreach ($insights as $line)
            <div class="flex items-start gap-2 text-sm">
                <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-500"></span>
                <p class="text-body">{{ $line }}</p>
            </div>
        @endforeach
    </div>
    <p class="mt-4 text-[11px] text-faint">Every line above is calculated directly from platform data for {{ $period['label'] }} — no fabricated conclusions.</p>
</x-glass-card>
