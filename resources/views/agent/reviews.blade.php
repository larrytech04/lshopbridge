@extends('layouts.app')
@section('page-title', 'Reviews')

@section('content')
<div class="space-y-3">
    <div class="glass flex items-center gap-6 rounded-2xl p-5">
        <div class="text-center">
            <p class="text-4xl font-extrabold text-strong">{{ number_format((float)$agent->rating, 1) }}</p>
            <p class="text-amber-300">@for($i=0;$i<round($agent->rating);$i++)★@endfor</p>
            <p class="mt-1 text-xs text-faint">{{ $agent->reviews_count }} reviews</p>
        </div>
        <div class="text-sm text-muted">{{ __('Maintain great service to earn more points and feature placement.') }}</div>
    </div>

    @forelse ($reviews as $review)
        <div class="glass rounded-2xl p-5">
            <div class="flex items-center justify-between">
                <span class="font-medium text-strong">{{ $review->reviewerName() }}</span>
                <div class="flex items-center gap-2"><span class="text-amber-300">@for($i=0;$i<$review->rating;$i++)★@endfor</span><x-status-badge :status="$review->status" /></div>
            </div>
            @if ($review->comment)<p class="mt-2 text-sm text-muted">{{ $review->comment }}</p>@endif
        </div>
    @empty
        <x-empty icon="star" title="{{ __('No reviews yet') }}" />
    @endforelse
    <div>{{ $reviews->links() }}</div>
</div>
@endsection
