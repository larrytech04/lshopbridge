@extends('layouts.admin')
@section('page-title', 'Newsletter subscribers')

@section('content')
<div class="space-y-5">
    <div>
        <h1 class="text-2xl font-bold text-strong">Newsletter subscribers</h1>
        <p class="text-sm text-muted">{{ number_format($totalSubscribed) }} active subscriber(s) from the footer signup form.</p>
    </div>

    <div class="flex gap-2">
        @foreach (['subscribed'=>'Subscribed','unsubscribed'=>'Unsubscribed'] as $k=>$v)
            <a href="{{ route('admin.newsletter.index', ['status'=>$k]) }}" class="pill {{ $status===$k ? 'bg-brand-600/40 text-strong ring-1 ring-white/10' : 'surface text-body ring-1 ring-white/10' }}">{{ $v }}</a>
        @endforeach
    </div>

    <x-glass-card padding="p-0">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="border-b border-app text-muted"><tr><th class="px-5 py-3">Email</th><th class="px-5 py-3">Source</th><th class="px-5 py-3">Subscribed</th></tr></thead>
            <tbody class="divide-y divide-app">
                @forelse ($subscribers as $sub)
                    <tr>
                        <td class="px-5 py-3 text-body">{{ $sub->email }}</td>
                        <td class="px-5 py-3 text-muted">{{ $sub->source ?: '-' }}</td>
                        <td class="px-5 py-3 text-muted">{{ optional($sub->subscribed_at)->diffForHumans() ?? $sub->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-5 py-10 text-center text-faint">No {{ $status }} subscribers yet.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </x-glass-card>
    <div>{{ $subscribers->links() }}</div>
</div>
@endsection
