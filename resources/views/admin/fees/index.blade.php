@extends('layouts.admin')
@section('page-title', 'Fees')

@section('content')
<div class="space-y-5">
    <div class="flex justify-end"><a href="{{ route('admin.fees.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> Add fee</a></div>
    <x-glass-card padding="p-0">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="border-b border-app text-muted"><tr><th class="px-5 py-3">Name</th><th class="px-5 py-3">Applies to</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Value</th><th class="px-5 py-3">Active</th><th class="px-5 py-3"></th></tr></thead>
            <tbody class="divide-y divide-app">
                @forelse ($fees as $fee)
                    <tr>
                        <td class="px-5 py-3 font-medium text-strong">{{ $fee->name }}</td>
                        <td class="px-5 py-3 text-body">{{ ucfirst($fee->applies_to) }}@if($fee->scope) ({{ $fee->scope }})@endif</td>
                        <td class="px-5 py-3 text-body">{{ ucfirst($fee->type) }}</td>
                        <td class="px-5 py-3 text-body">{{ $fee->type==='percent' ? $fee->value.'%' : money($fee->value, $fee->currency ?? config('platform.base_currency')) }}</td>
                        <td class="px-5 py-3">@if($fee->is_active)<span class="pill bg-emerald-500/15 text-emerald-300">Yes</span>@else<span class="pill bg-slate-400/15 text-body">No</span>@endif</td>
                        <td class="px-5 py-3 text-right"><div class="flex justify-end gap-3"><a href="{{ route('admin.fees.edit', $fee) }}" class="text-brand-300">Edit</a><form method="POST" action="{{ route('admin.fees.destroy', $fee) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-rose-300">Delete</button></form></div></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-faint">No fees configured.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </x-glass-card>
</div>
@endsection
