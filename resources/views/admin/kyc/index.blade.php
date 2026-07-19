@extends('layouts.admin')
@section('page-title', 'KYC review')

@section('content')
<div class="space-y-5">
    <div class="flex gap-2">
        @foreach (['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $k=>$v)
            <a href="{{ route('admin.kyc.index', ['status'=>$k]) }}" class="pill {{ $status===$k ? 'bg-brand-600/40 text-strong ring-1 ring-white/10' : 'surface text-body ring-1 ring-white/10' }}">{{ $v }}</a>
        @endforeach
    </div>

    <x-glass-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-app text-muted"><tr><th class="px-5 py-3">User</th><th class="px-5 py-3">Document</th><th class="px-5 py-3">Country</th><th class="px-5 py-3">Submitted</th><th class="px-5 py-3"></th></tr></thead>
                <tbody class="divide-y divide-app">
                    @forelse ($items as $kyc)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-5 py-3"><p class="text-strong">{{ $kyc->user->name }}</p><p class="text-xs text-faint">{{ $kyc->full_name }}</p></td>
                            <td class="px-5 py-3 text-body">{{ ucfirst(str_replace('_',' ',$kyc->document_type)) }}</td>
                            <td class="px-5 py-3 text-body">{{ $kyc->country->name ?? '-' }}</td>
                            <td class="px-5 py-3 text-muted">{{ $kyc->created_at->diffForHumans() }}</td>
                            <td class="px-5 py-3 text-right"><a href="{{ route('admin.kyc.show', $kyc) }}" class="text-brand-300 hover:text-brand-200">Review →</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-faint">Nothing to review.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-glass-card>
    <div>{{ $items->links() }}</div>
</div>
@endsection
