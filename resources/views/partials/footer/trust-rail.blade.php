{{--
    Trust & Operations Rail — links to real pages that back each statement,
    never a certification badge (PCI/SOC2/ISO/"bank-level"/"licensed
    financial institution") since no verified evidence for any such claim
    exists in Settings. Deliberately no VerifiedTrustClaim admin/approval
    model yet (Phase 2) — the safe default until one exists is to show
    nothing unverifiable at all, only real links to real explanation pages.
--}}
@php
    use Illuminate\Support\Facades\Route;

    $trustItems = array_filter([
        Route::has('legal.show') ? ['label' => __('Privacy Controls'), 'href' => route('legal.show', 'privacy'), 'icon' => 'lock'] : null,
        auth()->check() && Route::has('security.index') ? ['label' => __('Account Security'), 'href' => route('security.index'), 'icon' => 'shield'] : null,
        Route::has('verification.index') && auth()->check() ? ['label' => __('Identity Verification'), 'href' => route('verification.index'), 'icon' => 'user-circle'] : null,
        Route::has('agents.index') ? ['label' => __('Verified Agent Marketplace'), 'href' => route('agents.index'), 'icon' => 'check-circle'] : null,
        Route::has('legal.index') ? ['label' => __('Legal Center'), 'href' => route('legal.index'), 'icon' => 'doc'] : null,
        Route::has('disputes.index') ? ['label' => __('Report an Issue'), 'href' => route('disputes.index'), 'icon' => 'alert'] : null,
    ]);
@endphp
@if (count($trustItems))
    <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs font-medium text-muted lg:shrink-0 lg:flex-nowrap">
        @foreach ($trustItems as $item)
            <a href="{{ $item['href'] }}" class="flex shrink-0 items-center gap-1.5 hover:text-strong">
                <x-icon :name="$item['icon']" class="h-3.5 w-3.5 text-faint" />
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
@endif
