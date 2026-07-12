@extends('layouts.app')
@section('page-title', 'Support')

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-3">
        @forelse ($disputes as $d)
            <a href="{{ route('disputes.show', $d) }}" class="glass glass-hover flex items-center justify-between rounded-2xl p-5">
                <div>
                    <p class="font-medium text-strong">{{ $d->subject }}</p>
                    <p class="text-xs text-faint">{{ $d->reference }} · {{ ucfirst($d->category) }} · {{ $d->created_at->diffForHumans() }}</p>
                </div>
                <x-status-badge :status="$d->status" />
            </a>
        @empty
            <x-empty icon="info" title="{{ __('No support tickets') }}" message="Open a ticket if you need help with a transaction." />
        @endforelse
        <div>{{ $disputes->links() }}</div>
    </div>

    <div>
        <x-glass-card>
            <h3 class="font-semibold text-strong">{{ __('Open a ticket') }}</h3>
            <form method="POST" action="{{ route('disputes.store') }}" class="mt-4 space-y-3">
                @csrf
                <div><label class="label">{{ __('Subject') }}</label><input name="subject" required class="field"></div>
                <div><label class="label">{{ __('Category') }}</label>
                    <select name="category" class="field">
                        <option value="general">{{ __('General') }}</option>
                        <option value="deposit">{{ __('Deposit') }}</option>
                        <option value="funding">{{ __('Funding') }}</option>
                        <option value="agent">{{ __('Agent') }}</option>
                    </select>
                </div>
                <div><label class="label">{{ __('Describe the issue') }}</label><textarea name="description" rows="4" required class="field"></textarea></div>
                <button class="btn btn-primary w-full">{{ __('Submit ticket') }}</button>
            </form>
        </x-glass-card>
    </div>
</div>
@endsection
