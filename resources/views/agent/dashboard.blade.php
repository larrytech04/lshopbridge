@extends('layouts.app')
@section('page-title', 'Agent dashboard')

@section('content')
<div class="space-y-6">
    @if ($agent->status->value !== 'approved')
        <div class="glass flex flex-wrap items-center gap-4 rounded-2xl border-l-4 border-amber-400/60 p-4">
            <x-icon name="shield" class="h-6 w-6 text-amber-300" />
            <div class="flex-1">
                <p class="font-medium text-strong">Your agent profile is {{ $agent->status->label() }}</p>
                <p class="text-sm text-muted">{{ __('Complete verification to get listed in the marketplace.') }}</p>
            </div>
            <a href="{{ route('agent.verification') }}" class="btn btn-primary">{{ __('Complete verification') }}</a>
        </div>
    @endif

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Rating" :value="number_format((float)$agent->rating,1)" icon="star" hint="{{ $agent->reviews_count }} reviews" />
        <x-stat label="Points" :value="$agent->points" :counter="true" icon="sparkles" />
        <x-stat label="New leads" :value="$stats['newLeads']" :counter="true" icon="bell" />
        <x-stat label="Completed" :value="$stats['completed']" :counter="true" icon="check-circle" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-glass-card>
            <div class="flex items-center justify-between"><h3 class="font-semibold text-strong">{{ __('Recent leads') }}</h3><a href="{{ route('agent.leads.index') }}" class="text-sm text-brand-300">{{ __('View all') }}</a></div>
            <div class="mt-4 space-y-2">
                @forelse ($leads as $lead)
                    <div class="rounded-xl border border-app surface p-3">
                        <div class="flex items-center justify-between"><span class="text-sm font-medium text-strong">{{ $lead->user->name }}</span><x-status-badge :status="$lead->status" /></div>
                        <p class="mt-1 line-clamp-1 text-sm text-muted">{{ $lead->message }}</p>
                    </div>
                @empty
                    <x-empty icon="list" title="{{ __('No leads yet') }}" message="Leads from buyers will appear here." />
                @endforelse
            </div>
        </x-glass-card>

        <x-glass-card>
            <div class="flex items-center justify-between"><h3 class="font-semibold text-strong">{{ __('Recent reviews') }}</h3><a href="{{ route('agent.reviews.index') }}" class="text-sm text-brand-300">{{ __('View all') }}</a></div>
            <div class="mt-4 space-y-2">
                @forelse ($reviews as $review)
                    <div class="rounded-xl border border-app surface p-3">
                        <div class="flex items-center justify-between"><span class="text-sm font-medium text-strong">{{ $review->reviewerName() }}</span><span class="text-amber-300">@for($i=0;$i<$review->rating;$i++)★@endfor</span></div>
                        @if ($review->comment)<p class="mt-1 text-sm text-muted">{{ $review->comment }}</p>@endif
                    </div>
                @empty
                    <x-empty icon="star" title="{{ __('No reviews yet') }}" message="Reviews appear after completed orders." />
                @endforelse
            </div>
        </x-glass-card>
    </div>
</div>
@endsection
