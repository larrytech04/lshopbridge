@extends('layouts.admin')
@section('page-title', 'Referral leads')

@section('content')
<div class="space-y-5">
    <div>
        <h1 class="text-2xl font-bold text-strong">Referral leads</h1>
        <p class="text-sm text-muted">People interested in becoming an agent, via the public interest form.</p>
    </div>

    <div class="flex gap-2">
        @foreach (['new'=>'New','contacted'=>'Contacted','converted'=>'Converted','declined'=>'Declined'] as $k=>$v)
            <a href="{{ route('admin.referral-leads.index', ['status'=>$k]) }}" class="pill {{ $status===$k ? 'bg-brand-600/40 text-strong ring-1 ring-white/10' : 'surface text-body ring-1 ring-white/10' }}">{{ $v }}</a>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse ($leads as $lead)
            <div class="card-solid rounded-2xl border border-app p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-strong">{{ $lead->name }}</p>
                        <p class="text-xs text-faint">{{ $lead->email }} @if($lead->phone) · {{ $lead->phone }} @endif @if($lead->country) · {{ $lead->country->name }} @endif</p>
                        @if ($lead->message)<p class="mt-2 text-sm text-muted">{{ $lead->message }}</p>@endif
                    </div>
                    <x-status-badge :status="$lead->status" />
                </div>
                <form method="POST" action="{{ route('admin.referral-leads.update', $lead) }}" class="mt-4 flex flex-wrap items-end gap-2 border-t border-app pt-4">
                    @csrf @method('PUT')
                    <select name="status" class="field max-w-[160px]">
                        @foreach (['new','contacted','converted','declined'] as $s)
                            <option value="{{ $s }}" @selected($lead->status === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                    <input name="notes" value="{{ $lead->notes }}" placeholder="Notes" class="field flex-1 min-w-[160px]">
                    <button class="btn btn-primary text-xs">Save</button>
                </form>
            </div>
        @empty
            <x-empty icon="users" title="No {{ $status }} leads" />
        @endforelse
    </div>
    <div>{{ $leads->links() }}</div>
</div>
@endsection
