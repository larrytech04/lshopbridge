@extends('layouts.admin')
@section('page-title', 'Review moderation')

@section('content')
<div class="space-y-5">
    <div class="flex gap-2">
        @foreach (['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $k=>$v)
            <a href="{{ route('admin.reviews.index', ['status'=>$k]) }}" class="pill {{ $status===$k ? 'bg-brand-600/40 text-strong ring-1 ring-white/10' : 'surface text-body ring-1 ring-white/10' }}">{{ $v }}</a>
        @endforeach
    </div>
    @forelse ($reviews as $review)
        <div class="glass rounded-2xl p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-medium text-strong">{{ $review->user->name }} → {{ $review->agent->business_name }}</p>
                    <p class="text-amber-300">@for($i=0;$i<$review->rating;$i++)★@endfor</p>
                    @if ($review->comment)<p class="mt-1 text-sm text-muted">{{ $review->comment }}</p>@endif
                </div>
                @if ($review->status === 'pending')
                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('admin.reviews.approve', $review) }}">@csrf<button class="btn btn-success text-xs py-1.5">Approve</button></form>
                        <form method="POST" action="{{ route('admin.reviews.reject', $review) }}">@csrf<button class="btn btn-danger text-xs py-1.5">Reject</button></form>
                    </div>
                @else
                    <x-status-badge :status="$review->status" />
                @endif
            </div>
        </div>
    @empty
        <x-empty icon="star" title="No reviews" />
    @endforelse
    <div>{{ $reviews->links() }}</div>
</div>
@endsection
