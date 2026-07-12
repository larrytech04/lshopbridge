@extends('layouts.admin')
@section('page-title', 'Dispute '.$dispute->reference)

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <a href="{{ route('admin.disputes.index') }}" class="text-sm text-brand-300 hover:text-brand-200">← Disputes</a>

    <x-glass-card>
        <div class="flex items-center justify-between">
            <div><h2 class="text-xl font-bold text-strong">{{ $dispute->subject }}</h2><p class="text-xs text-faint">{{ $dispute->reference }} · {{ $dispute->user->name }} · {{ ucfirst($dispute->category) }}</p></div>
            <x-status-badge :status="$dispute->status" />
        </div>
        <p class="mt-4 text-body">{{ $dispute->description }}</p>
    </x-glass-card>

    <x-glass-card>
        <h3 class="font-semibold text-strong">Conversation</h3>
        <div class="mt-4 space-y-3">
            @forelse ($dispute->messages as $m)
                <div class="rounded-xl p-3 {{ $m->is_staff ? 'bg-brand-600/20' : 'surface' }}">
                    <p class="text-xs font-semibold {{ $m->is_staff ? 'text-brand-300' : 'text-muted' }}">{{ $m->is_staff ? 'Support' : $m->user->name }} · {{ $m->created_at->diffForHumans() }}</p>
                    <p class="mt-1 text-sm text-body">{{ $m->message }}</p>
                </div>
            @empty
                <p class="py-3 text-center text-sm text-faint">No messages.</p>
            @endforelse
        </div>
        <form method="POST" action="{{ route('admin.disputes.reply', $dispute) }}" class="mt-4 space-y-2 border-t border-app pt-4">@csrf
            <textarea name="message" rows="3" required class="field" placeholder="Reply to the user…"></textarea>
            <button class="btn btn-primary">Send reply</button>
        </form>
    </x-glass-card>

    @unless ($dispute->status->value === 'closed')
        <x-glass-card>
            <h3 class="font-semibold text-strong">Resolve</h3>
            <form method="POST" action="{{ route('admin.disputes.resolve', $dispute) }}" class="mt-3 space-y-3">@csrf
                <textarea name="resolution" rows="2" required class="field" placeholder="Resolution summary"></textarea>
                <div class="flex gap-2">
                    <select name="status" class="field max-w-[160px]"><option value="resolved">Resolved</option><option value="closed">Closed</option></select>
                    <button class="btn btn-success">Mark resolved</button>
                </div>
            </form>
        </x-glass-card>
    @endunless
</div>
@endsection
