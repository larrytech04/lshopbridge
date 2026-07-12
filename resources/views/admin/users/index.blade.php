@extends('layouts.admin')
@section('page-title', 'Users')

@section('content')
<div class="space-y-5">
    <form method="GET" class="glass flex flex-wrap gap-3 rounded-2xl p-4">
        <input name="q" value="{{ $filters['q'] ?? '' }}" class="field max-w-xs" placeholder="Search name or email…">
        <select name="role" class="field max-w-[160px]">
            <option value="">All roles</option>
            @foreach (['user','agent','admin','super_admin'] as $r)<option value="{{ $r }}" @selected(($filters['role'] ?? '')===$r)>{{ ucfirst(str_replace('_',' ',$r)) }}</option>@endforeach
        </select>
        <select name="status" class="field max-w-[150px]">
            <option value="">All status</option>
            @foreach (['active','suspended','blocked'] as $s)<option value="{{ $s }}" @selected(($filters['status'] ?? '')===$s)>{{ ucfirst($s) }}</option>@endforeach
        </select>
        <button class="btn btn-primary"><x-icon name="search" class="h-4 w-4" /> Filter</button>
    </form>

    <x-glass-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-app text-muted"><tr>
                    <th class="px-5 py-3 font-medium">User</th><th class="px-5 py-3 font-medium">Role</th><th class="px-5 py-3 font-medium">KYC</th><th class="px-5 py-3 font-medium">Status</th><th class="px-5 py-3 font-medium">Activity</th><th class="px-5 py-3"></th>
                </tr></thead>
                <tbody class="divide-y divide-app">
                    @forelse ($users as $u)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-5 py-3"><p class="font-medium text-strong">{{ $u->name }}</p><p class="text-xs text-faint">{{ $u->email }}</p></td>
                            <td class="px-5 py-3"><span class="pill surface text-body ring-1 ring-white/10">{{ $u->role->label() }}</span></td>
                            <td class="px-5 py-3 text-body">L{{ $u->kyc_level }} <x-status-badge :status="$u->kyc_status" class="ml-1" /></td>
                            <td class="px-5 py-3"><x-status-badge :status="$u->status" /></td>
                            <td class="px-5 py-3 text-xs text-muted">{{ $u->deposits_count }} deposits · {{ $u->funding_requests_count }} funding</td>
                            <td class="px-5 py-3 text-right"><a href="{{ route('admin.users.show', $u) }}" class="text-brand-300 hover:text-brand-200">Manage →</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-faint">No users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-glass-card>
    <div>{{ $users->links() }}</div>
</div>
@endsection
