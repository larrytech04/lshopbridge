@extends('layouts.app')
@section('page-title', 'Notifications')

@section('content')
<div class="mx-auto max-w-3xl space-y-4">
    <div class="flex justify-end">
        <form method="POST" action="{{ route('notifications.readAll') }}">@csrf<button class="btn btn-ghost text-sm"><x-icon name="check" class="h-4 w-4" /> {{ __('Mark all read') }}</button></form>
    </div>
    @forelse ($notifications as $n)
        <div class="glass flex items-start gap-4 rounded-2xl p-4 {{ $n->read_at ? 'opacity-60' : '' }}">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl surface text-brand-200"><x-icon :name="$n->data['icon'] ?? 'bell'" class="h-5 w-5" /></span>
            <div class="flex-1">
                <p class="font-medium text-strong">{{ $n->data['title'] ?? 'Notification' }}</p>
                <p class="text-sm text-muted">{{ $n->data['message'] ?? '' }}</p>
                <p class="mt-1 text-xs text-faint">{{ $n->created_at->diffForHumans() }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if (!empty($n->data['url']))<a href="{{ $n->data['url'] }}" class="text-sm text-brand-300 hover:text-brand-200">{{ __('Open') }}</a>@endif
                @unless ($n->read_at)<form method="POST" action="{{ route('notifications.read', $n->id) }}">@csrf<button class="text-xs text-muted hover:text-white">{{ __('Mark read') }}</button></form>@endunless
            </div>
        </div>
    @empty
        <x-empty icon="bell" title="{{ __('No notifications') }}" message="You're all caught up." />
    @endforelse
    <div>{{ $notifications->links() }}</div>
</div>
@endsection
