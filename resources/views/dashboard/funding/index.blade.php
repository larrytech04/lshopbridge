@extends('layouts.app')
@section('page-title', 'Fund China wallet')

@section('content')
<div class="space-y-6">
    <x-page-header :title="__('Fund China wallet')" :subtitle="__('Your China wallet funding requests.')">
        <x-slot:actions>
            <a href="{{ route('funding.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> {{ __('New funding') }}</a>
        </x-slot:actions>
    </x-page-header>

    <div class="rounded-3xl border border-app">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-app text-muted">
                    <tr>
                        <th class="px-5 py-3 font-medium">{{ __('Reference') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Recipient') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('You paid') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Delivered') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-app">
                    @forelse ($requests as $f)
                        <tr class="hover:surface-2">
                            <td class="px-5 py-3 font-mono text-xs text-muted">{{ $f->reference }}</td>
                            <td class="px-5 py-3"><span class="text-body">{{ $f->recipient_account }}</span><br><span class="text-xs text-faint">{{ $f->app_type->label() }}</span></td>
                            <td class="px-5 py-3 text-body">{{ money($f->total_charged, $f->source_currency) }}</td>
                            <td class="px-5 py-3 font-semibold text-strong">{{ money($f->target_amount, $f->target_currency) }}</td>
                            <td class="px-5 py-3"><x-status-badge :status="$f->status" /></td>
                            <td class="px-5 py-3 text-right"><a href="{{ route('funding.show', $f) }}" class="text-brand-500 hover:text-brand-600">View →</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12"><x-empty icon="fund" title="{{ __('No funding requests yet') }}" message="{{ __('Fund your first Alipay or WeChat wallet.') }}" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div>{{ $requests->links() }}</div>
</div>
@endsection
