@extends('layouts.app')
@section('page-title', 'China wallets')

@section('content')
<x-page-header :title="__('China wallets')" />

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-4">
        @forelse ($accounts as $account)
            <div class="glass rounded-2xl p-5" x-data="{ edit: false }">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="grid h-11 w-11 place-items-center rounded-xl surface text-brand-200"><x-icon name="card" class="h-5 w-5" /></span>
                        <div>
                            <p class="font-semibold text-strong">{{ $account->app_type->label() }}, {{ $account->account_name }}
                                @if ($account->is_default)<span class="pill ml-1 bg-slate-500/20 text-brand-200 ring-1 ring-brand-400/30">{{ __('Default') }}</span>@endif
                            </p>
                            <p class="text-sm text-muted">{{ $account->account_id }}</p>
                        </div>
                    </div>
                    <x-status-badge :status="$account->status" />
                </div>

                @if ($account->rejection_reason)
                    <p class="mt-3 rounded-lg border border-rose-400/30 bg-rose-500/10 p-2 text-xs text-rose-200">{{ $account->rejection_reason }}</p>
                @endif

                <div class="mt-4 flex flex-wrap gap-2">
                    @if (! $account->is_default && $account->status->value === 'approved')
                        <form method="POST" action="{{ route('beneficiaries.default', $account) }}">@csrf
                            <button class="btn btn-ghost text-xs">{{ __('Make default') }}</button>
                        </form>
                    @endif
                    <button @click="edit = !edit" class="btn btn-ghost text-xs"><x-icon name="cog" class="h-3.5 w-3.5" /> {{ __('Edit') }}</button>
                    <form method="POST" action="{{ route('beneficiaries.destroy', $account) }}" onsubmit="return confirm('Remove this China wallet?')">@csrf @method('DELETE')
                        <button class="btn btn-ghost text-xs text-rose-300"><x-icon name="x" class="h-3.5 w-3.5" /> {{ __('Remove') }}</button>
                    </form>
                </div>

                <div x-show="edit" x-collapse style="display:none">
                    <form method="POST" action="{{ route('beneficiaries.update', $account) }}" enctype="multipart/form-data" class="mt-4 grid gap-3 border-t border-app pt-4 sm:grid-cols-2">
                        @csrf @method('PUT')
                        <div><label class="label">{{ __('Account name') }}</label><input name="account_name" value="{{ $account->account_name }}" required class="field"></div>
                        <div><label class="label">{{ __('Account ID / phone / email') }}</label><input name="account_id" value="{{ $account->account_id }}" required class="field"></div>
                        <div class="sm:col-span-2"><label class="label">{{ __('Replace QR (optional)') }}</label><input type="file" name="qr" accept=".jpg,.jpeg,.png" class="field"></div>
                        <div class="sm:col-span-2"><button class="btn btn-primary">{{ __('Save changes') }}</button></div>
                    </form>
                </div>
            </div>
        @empty
            <x-empty icon="card" title="{{ __('No China wallets yet') }}" message="Add your Alipay or WeChat Pay account to start funding." />
        @endforelse
    </div>

    <div>
        <x-glass-card>
            <h3 class="font-semibold text-strong">{{ __('Add a China wallet') }}</h3>
            <form method="POST" action="{{ route('beneficiaries.store') }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="label">{{ __('App') }}</label>
                    <select name="app_type" required class="field">
                        @foreach ($apps as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div><label class="label">{{ __('Account name') }}</label><input name="account_name" required class="field" placeholder="{{ __('As shown in the app') }}"></div>
                <div><label class="label">{{ __('Account ID / phone / email') }}</label><input name="account_id" required class="field"></div>
                <div><label class="label">{{ __('QR code (optional)') }}</label><input type="file" name="qr" accept=".jpg,.jpeg,.png" class="field"></div>
                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_default" value="1" class="rounded border-app surface-2 text-brand-500"> {{ __('Set as default') }}</label>
                <button class="btn btn-primary w-full"><x-icon name="plus" class="h-4 w-4" /> {{ __('Add wallet') }}</button>
            </form>
            <p class="mt-3 text-xs text-faint">{{ __('New wallets are verified before they can receive funding.') }}</p>
        </x-glass-card>
    </div>
</div>
@endsection
