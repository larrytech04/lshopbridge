@extends('layouts.admin')
@section('page-title', 'Exchange rates')

@section('content')
<div class="space-y-5">
    <div class="flex justify-end"><a href="{{ route('admin.rates.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> Add rate</a></div>
    <x-glass-card padding="p-0">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="border-b border-app text-muted"><tr><th class="px-5 py-3">Pair</th><th class="px-5 py-3">Rate</th><th class="px-5 py-3">Margin</th><th class="px-5 py-3">Effective</th><th class="px-5 py-3">Active</th><th class="px-5 py-3"></th></tr></thead>
            <tbody class="divide-y divide-app">
                @forelse ($rates as $rate)
                    <tr>
                        <td class="px-5 py-3 font-medium text-strong">{{ $rate->base_currency }} → {{ $rate->quote_currency }}</td>
                        <td class="px-5 py-3 text-body">{{ rtrim(rtrim(number_format($rate->rate,6),'0'),'.') }}</td>
                        <td class="px-5 py-3 text-body">{{ $rate->margin_percent }}%</td>
                        <td class="px-5 py-3 text-body">{{ rtrim(rtrim(number_format($rate->effectiveRate(),6),'0'),'.') }}</td>
                        <td class="px-5 py-3">@if($rate->is_active)<span class="pill bg-emerald-500/15 text-emerald-300">Yes</span>@else<span class="pill bg-slate-400/15 text-body">No</span>@endif</td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('admin.rates.edit', $rate) }}" class="text-brand-300">Edit</a>
                                <form method="POST" action="{{ route('admin.rates.destroy', $rate) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-rose-300">Delete</button></form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-faint">No rates. Add one to enable funding.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </x-glass-card>
</div>
@endsection
