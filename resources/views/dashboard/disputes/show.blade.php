@extends('layouts.app')
@section('page-title', 'Ticket '.$dispute->reference)

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <a href="{{ route('disputes.index') }}" class="text-sm text-brand-300 hover:text-brand-200">← {{ __('Back to support') }}</a>

    <x-glass-card>
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-strong">{{ $dispute->subject }}</h2>
                <p class="text-xs text-faint">{{ $dispute->reference }} · {{ ucfirst($dispute->category) }}</p>
            </div>
            <x-status-badge :status="$dispute->status" />
        </div>
        <p class="mt-4 text-body">{{ $dispute->description }}</p>
        @if ($dispute->resolution)
            <div class="mt-4 rounded-xl border border-emerald-400/30 bg-emerald-500/10 p-3 text-sm text-emerald-100"><span class="font-semibold">{{ __('Resolution:') }}</span> {{ $dispute->resolution }}</div>
        @endif
    </x-glass-card>

    <x-glass-card>
        <h3 class="font-semibold text-strong">{{ __('Conversation') }}</h3>
        <div class="mt-4 space-y-3">
            @forelse ($dispute->messages as $m)
                <div class="flex {{ $m->is_staff ? 'justify-start' : 'justify-end' }}">
                    <div class="max-w-[80%] rounded-2xl px-4 py-2.5 {{ $m->is_staff ? 'surface text-body' : 'bg-brand-600/30 text-strong' }}">
                        <p class="text-xs font-semibold {{ $m->is_staff ? 'text-brand-300' : 'text-accent-300' }}">{{ $m->is_staff ? 'Support' : $m->user->name }}</p>
                        <p class="mt-1 text-sm">{{ $m->message }}</p>
                        @if ($m->attachment_path)<a href="{{ route('files.show', ['kind'=>'dispute-attachment','id'=>$m->id]) }}" target="_blank" class="mt-1 inline-block text-xs underline">{{ __('View attachment') }}</a>@endif
                        <p class="mt-1 text-[10px] text-faint">{{ $m->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            @empty
                <p class="py-4 text-center text-sm text-faint">{{ __('No replies yet.') }}</p>
            @endforelse
        </div>

        @if (! in_array($dispute->status->value, ['closed']))
            <form method="POST" action="{{ route('disputes.reply', $dispute) }}" enctype="multipart/form-data" class="mt-5 space-y-3 border-t border-app pt-4">
                @csrf
                <textarea name="message" rows="3" required class="field" placeholder="{{ __('Type your reply…') }}"></textarea>
                <div class="flex items-center gap-3">
                    <input type="file" name="attachment" class="field max-w-xs text-xs">
                    <button class="btn btn-primary ml-auto">{{ __('Send reply') }}</button>
                </div>
            </form>
        @endif
    </x-glass-card>
</div>
@endsection
