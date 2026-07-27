@extends('layouts.app')
@section('page-title', 'Support')

@section('content')
<x-page-header :title="__('Support')" :subtitle="__('Open a ticket and our team will get back to you.')" />

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-3">
        @forelse ($disputes as $d)
            <a href="{{ route('disputes.show', $d) }}" class="card-solid flex items-center justify-between rounded-2xl border border-app p-5 transition hover:border-brand-400/50 hover:shadow-md">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="truncate font-medium text-strong">{{ $d->subject }}</p>
                        @if ($d->priority === 'high')<span class="pill shrink-0 bg-rose-500/15 text-[10px] font-bold uppercase text-rose-600 ring-1 ring-rose-400/30">{{ __('High') }}</span>@endif
                    </div>
                    <p class="text-xs text-faint">{{ $d->reference }} · {{ ucfirst($d->category) }} · {{ $d->created_at->diffForHumans() }}</p>
                </div>
                <x-status-badge :status="$d->status" class="shrink-0" />
            </a>
        @empty
            <x-empty icon="info" title="{{ __('No support tickets') }}" message="{{ __('Open a ticket if you need help with a transaction.') }}" />
        @endforelse
        <div>{{ $disputes->links() }}</div>
    </div>

    <div>
        <div class="card-solid rounded-3xl border border-app p-6 shadow-sm">
            <h3 class="font-semibold text-strong">{{ __('Open a ticket') }}</h3>
            <p class="mt-1 text-sm text-muted">{{ __('The more detail you give us, the faster we can help.') }}</p>

            <form method="POST" action="{{ route('disputes.store') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="label">{{ __('Subject') }}</label>
                    <input name="subject" required class="field" placeholder="{{ __('Short summary of the issue') }}">
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">{{ __('Category') }}</label>
                        <select name="category" class="field">
                            <option value="general">{{ __('General') }}</option>
                            <option value="deposit">{{ __('Deposit') }}</option>
                            <option value="funding">{{ __('Funding') }}</option>
                            <option value="agent">{{ __('Agent') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">{{ __('Priority') }}</label>
                        <select name="priority" class="field">
                            <option value="low">{{ __('Low') }}</option>
                            <option value="normal" selected>{{ __('Normal') }}</option>
                            <option value="high">{{ __('High, urgent / blocking') }}</option>
                        </select>
                    </div>
                </div>

                @if ($related['deposit']->isNotEmpty() || $related['funding']->isNotEmpty() || $related['order']->isNotEmpty())
                    <div>
                        <label class="label">{{ __('Related to (optional)') }}</label>
                        <select name="related" class="field">
                            <option value="">{{ __('Not related to a specific transaction') }}</option>
                            @if ($related['deposit']->isNotEmpty())
                                <optgroup label="{{ __('Deposits') }}">
                                    @foreach ($related['deposit'] as $dep)
                                        <option value="deposit:{{ $dep->id }}">{{ $dep->reference }} · {{ money($dep->net_amount, $dep->currency) }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if ($related['funding']->isNotEmpty())
                                <optgroup label="{{ __('Funding requests') }}">
                                    @foreach ($related['funding'] as $f)
                                        <option value="funding:{{ $f->id }}">{{ $f->reference }} · {{ money($f->target_amount, $f->target_currency) }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if ($related['order']->isNotEmpty())
                                <optgroup label="{{ __('Shop orders') }}">
                                    @foreach ($related['order'] as $o)
                                        <option value="order:{{ $o->id }}">{{ $o->reference }} · {{ disp($o->total) }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                    </div>
                @endif

                <div>
                    <label class="label">{{ __('Describe the issue') }}</label>
                    <textarea name="description" rows="5" required class="field" placeholder="{{ __('What happened, when, and what you expected instead. Include any error messages you saw.') }}"></textarea>
                </div>

                <div>
                    <label class="label">{{ __('Attachment (optional)') }}</label>
                    <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf" class="field">
                    <p class="mt-1 text-xs text-faint">{{ __('A screenshot or receipt helps us resolve it faster.') }}</p>
                </div>

                <button class="btn btn-primary w-full">{{ __('Submit ticket') }}</button>
            </form>
        </div>

        <p class="mt-3 flex items-center gap-1.5 text-xs text-faint">
            <x-icon name="clock" class="h-3.5 w-3.5 shrink-0" />
            {{ __('Typical response time: a few hours for high priority, within 1-2 days otherwise.') }}
        </p>

        <x-discord-card class="mt-4" />
    </div>
</div>
@endsection
