@extends('layouts.admin')
@section('page-title', 'Payment methods')

@section('content')
<div class="space-y-5">
    <div class="flex justify-end"><a href="{{ route('admin.methods.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> Add method</a></div>
    <x-glass-card padding="p-0">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="border-b border-app text-muted"><tr><th class="px-5 py-3">Name</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Provider</th><th class="px-5 py-3">Mode</th><th class="px-5 py-3">Active</th><th class="px-5 py-3"></th></tr></thead>
            <tbody class="divide-y divide-app">
                @forelse ($methods as $m)
                    <tr>
                        <td class="px-5 py-3 font-medium text-strong">{{ $m->name }} <span class="text-xs text-faint">{{ $m->code }}</span></td>
                        <td class="px-5 py-3 text-body">{{ ucfirst($m->type) }}</td>
                        <td class="px-5 py-3 text-body">{{ $m->provider_code ?? '—' }}</td>
                        <td class="px-5 py-3">@if($m->is_automated)<span class="pill bg-emerald-500/15 text-emerald-300">Automated</span>@else<span class="pill bg-amber-500/15 text-amber-300">Manual</span>@endif</td>
                        <td class="px-5 py-3">@if($m->is_active)<span class="pill bg-emerald-500/15 text-emerald-300">Yes</span>@else<span class="pill bg-slate-400/15 text-body">No</span>@endif</td>
                        <td class="px-5 py-3 text-right"><div class="flex justify-end gap-3"><a href="{{ route('admin.methods.edit', $m) }}" class="text-brand-300">Edit</a><form method="POST" action="{{ route('admin.methods.destroy', $m) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-rose-300">Delete</button></form></div></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-faint">No payment methods.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </x-glass-card>
</div>
@endsection
