@extends('layouts.app')
@section('page-title', __('Saved Payment Methods'))

@section('content')
<x-page-header :title="__('Saved Payment Methods')" />

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-4">
        @forelse ($saved as $method)
            <div class="glass rounded-2xl p-5" x-data="{ edit: false }">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="grid h-11 w-11 place-items-center rounded-xl surface text-brand-200"><x-icon name="card" class="h-5 w-5" /></span>
                        <div>
                            <p class="font-semibold text-strong">{{ $method->label }}
                                @if ($method->is_default)<span class="pill ml-1 bg-slate-500/20 text-brand-200 ring-1 ring-brand-400/30">{{ __('Default') }}</span>@endif
                            </p>
                            <p class="text-sm text-muted">{{ $method->paymentMethod?->name }}@if ($method->maskedAccountRef()) &middot; {{ $method->maskedAccountRef() }}@endif</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    @unless ($method->is_default)
                        <form method="POST" action="{{ route('payment-methods.default', $method) }}">@csrf
                            <button class="btn btn-ghost text-xs">{{ __('Make default') }}</button>
                        </form>
                    @endunless
                    <button @click="edit = !edit" class="btn btn-ghost text-xs"><x-icon name="cog" class="h-3.5 w-3.5" /> {{ __('Edit') }}</button>
                    <form method="POST" action="{{ route('payment-methods.destroy', $method) }}" onsubmit="return confirm('{{ __('Remove this payment method?') }}')">@csrf @method('DELETE')
                        <button class="btn btn-ghost text-xs text-rose-300"><x-icon name="x" class="h-3.5 w-3.5" /> {{ __('Remove') }}</button>
                    </form>
                </div>

                <div x-show="edit" x-collapse style="display:none">
                    <form method="POST" action="{{ route('payment-methods.update', $method) }}" class="mt-4 grid gap-3 border-t border-app pt-4 sm:grid-cols-2">
                        @csrf @method('PUT')
                        <div><label class="label">{{ __('Nickname') }}</label><input name="label" value="{{ $method->label }}" required class="field"></div>
                        <div><label class="label">{{ __('Phone / account number') }}</label><input name="account_ref" value="{{ $method->account_ref }}" class="field"></div>
                        <div class="sm:col-span-2"><button class="btn btn-primary">{{ __('Save changes') }}</button></div>
                    </form>
                </div>
            </div>
        @empty
            <x-empty icon="card" title="{{ __('No saved payment methods yet') }}" message="{{ __('Save a mobile money number so you can fund faster next time.') }}" />
        @endforelse
    </div>

    <div>
        <x-glass-card>
            <h3 class="font-semibold text-strong">{{ __('Add a payment method') }}</h3>
            @if ($methods->isEmpty())
                <p class="mt-4 text-sm text-muted">{{ __('No deposit methods are available to save right now.') }}</p>
            @else
                <form method="POST" action="{{ route('payment-methods.store') }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label class="label">{{ __('Provider') }}</label>
                        <select name="payment_method_id" required class="field">
                            @foreach ($methods as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
                        </select>
                    </div>
                    <div><label class="label">{{ __('Nickname') }}</label><input name="label" required class="field" placeholder="{{ __('e.g. My Orange Money') }}"></div>
                    <div><label class="label">{{ __('Phone / account number') }}</label><input name="account_ref" class="field"></div>
                    <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_default" value="1" class="rounded border-app surface-2 text-brand-500"> {{ __('Set as default') }}</label>
                    <button class="btn btn-primary w-full"><x-icon name="plus" class="h-4 w-4" /> {{ __('Save method') }}</button>
                </form>
            @endif
            <p class="mt-3 text-xs text-faint">{{ __('We only store the details needed to speed up future deposits — never full card numbers.') }}</p>
        </x-glass-card>
    </div>
</div>
@endsection
