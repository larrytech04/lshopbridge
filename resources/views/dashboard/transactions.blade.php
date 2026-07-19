@extends('layouts.app')
@section('page-title', 'Transactions')

@section('content')
<div class="space-y-6">
    <x-page-header :title="__('Transactions')" />

    <form method="GET" class="glass flex flex-wrap gap-3 rounded-2xl p-4">
        <select name="type" class="field max-w-[180px]">
            <option value="">{{ __('All types') }}</option>
            <option value="credit" @selected(($filters['type'] ?? '')==='credit')>{{ __('Credit') }}</option>
            <option value="debit" @selected(($filters['type'] ?? '')==='debit')>{{ __('Debit') }}</option>
        </select>
        <select name="category" class="field max-w-[180px]">
            <option value="">{{ __('All categories') }}</option>
            @foreach (['deposit','funding','refund','fee','adjustment'] as $c)<option value="{{ $c }}" @selected(($filters['category'] ?? '')===$c)>{{ ucfirst($c) }}</option>@endforeach
        </select>
        <button class="btn btn-primary"><x-icon name="filter" class="h-4 w-4" /> {{ __('Filter') }}</button>
    </form>

    <div class="rounded-3xl border border-app">
        @include('dashboard.partials.txn-table', ['transactions' => $transactions])
        <div class="p-4">{{ $transactions->links() }}</div>
    </div>
</div>
@endsection
