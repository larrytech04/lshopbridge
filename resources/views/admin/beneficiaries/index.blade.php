@extends('layouts.admin')
@section('page-title', 'China wallets')

@section('content')
<div class="space-y-5">
    <div class="flex gap-2">
        @foreach (['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $k=>$v)
            <a href="{{ route('admin.beneficiaries.index', ['status'=>$k]) }}" class="pill {{ $status===$k ? 'bg-brand-600/40 text-strong ring-1 ring-white/10' : 'surface text-body ring-1 ring-white/10' }}">{{ $v }}</a>
        @endforeach
    </div>

    <x-glass-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-app text-muted"><tr><th class="px-5 py-3">User</th><th class="px-5 py-3">App</th><th class="px-5 py-3">Account</th><th class="px-5 py-3">QR</th><th class="px-5 py-3">Actions</th></tr></thead>
                <tbody class="divide-y divide-app">
                    @forelse ($accounts as $a)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-5 py-3 text-strong">{{ $a->user->name }}</td>
                            <td class="px-5 py-3 text-body">{{ $a->app_type->label() }}</td>
                            <td class="px-5 py-3"><p class="text-body">{{ $a->account_name }}</p><p class="text-xs text-faint">{{ $a->account_id }}</p></td>
                            <td class="px-5 py-3">@if($a->qr_path)<a href="{{ route('files.show', ['kind'=>'beneficiary-qr','id'=>$a->id]) }}" target="_blank" class="text-brand-300"><x-icon name="eye" class="h-4 w-4" /></a>@else, @endif</td>
                            <td class="px-5 py-3">
                                @if ($a->status->value === 'pending')
                                    <div class="flex gap-2">
                                        <form method="POST" action="{{ route('admin.beneficiaries.approve', $a) }}">@csrf<button class="btn btn-success text-xs py-1.5">Approve</button></form>
                                        <form method="POST" action="{{ route('admin.beneficiaries.reject', $a) }}" class="flex gap-1">@csrf<input name="reason" class="field max-w-[120px] py-1.5 text-xs" placeholder="Reason" required><button class="btn btn-danger text-xs py-1.5">Reject</button></form>
                                    </div>
                                @else
                                    <x-status-badge :status="$a->status" />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-faint">Nothing here.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-glass-card>
    <div>{{ $accounts->links() }}</div>
</div>
@endsection
