@extends('layouts.public')
@section('title', 'Fees & rates · '.config('platform.name'))

@section('content')
<section class="mx-auto max-w-4xl px-4 pt-20 text-center sm:px-6">
    <h1 class="text-4xl font-extrabold text-strong sm:text-5xl">{{ __('Fees & exchange rates') }}</h1>
    <p class="mx-auto mt-4 max-w-2xl text-lg text-body">{{ __('Simple and transparent. The exact fee is always shown before you confirm.') }}</p>
</section>

<section class="mx-auto mt-12 max-w-4xl px-4 sm:px-6">
    <div class="glass rounded-2xl p-6 text-center">
        <p class="text-sm text-muted">{{ __('Current reference rate') }}</p>
        <p class="mt-2 text-3xl font-bold text-strong">1 {{ config('platform.base_currency') }} = {{ rtrim(rtrim(number_format($rate, 6), '0'), '.') }} {{ config('platform.target_currency') }}</p>
    </div>

    <div class="glass mt-6 overflow-hidden rounded-2xl">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-app text-muted">
                <tr><th class="px-5 py-3 font-medium">{{ __('Fee') }}</th><th class="px-5 py-3 font-medium">{{ __('Applies to') }}</th><th class="px-5 py-3 font-medium">{{ __('Amount') }}</th></tr>
            </thead>
            <tbody class="divide-y divide-app">
                @forelse ($fees as $fee)
                    <tr>
                        <td class="px-5 py-3 font-medium text-strong">{{ $fee->name }}</td>
                        <td class="px-5 py-3 text-body">{{ ucfirst($fee->applies_to) }}</td>
                        <td class="px-5 py-3 text-body">{{ $fee->type === 'percent' ? rtrim(rtrim(number_format($fee->value, 2), '0'), '.').'%' : money($fee->value, $fee->currency ?? config('platform.base_currency')) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-5 py-8 text-center text-faint">{{ __('No fees configured.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
