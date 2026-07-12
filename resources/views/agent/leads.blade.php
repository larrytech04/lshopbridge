@extends('layouts.app')
@section('page-title', 'Orders & leads')

@section('content')
<div class="space-y-3">
    @forelse ($leads as $lead)
        <div class="glass rounded-2xl p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="font-semibold text-strong">{{ $lead->user->name }} <span class="text-xs text-faint">· {{ $lead->reference }}</span></p>
                    <p class="text-xs text-faint">{{ $lead->created_at->diffForHumans() }} @if($lead->shipping_method)· {{ ucfirst($lead->shipping_method) }}@endif</p>
                    <p class="mt-2 text-sm text-body">{{ $lead->message }}</p>
                </div>
                <form method="POST" action="{{ route('agent.leads.update', $lead) }}" class="flex items-center gap-2">@csrf @method('PUT')
                    <select name="status" class="field max-w-[160px]">
                        @foreach (['new','contacted','in_progress','completed','closed'] as $s)<option value="{{ $s }}" @selected($lead->status === $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach
                    </select>
                    <button class="btn btn-ghost">{{ __('Update') }}</button>
                </form>
            </div>
        </div>
    @empty
        <x-empty icon="list" title="{{ __('No leads yet') }}" message="When buyers request quotes, they show up here." />
    @endforelse
    <div>{{ $leads->links() }}</div>
</div>
@endsection
