@extends('layouts.app')
@section('title', 'Install your eSIM · '.config('platform.name'))
@section('page-title', __('Install your eSIM'))

@section('content')
<div class="mx-auto max-w-2xl px-4 py-10 sm:px-6">
    <a href="{{ route('esim.mine.index') }}" class="text-sm font-semibold text-muted hover:text-strong">← {{ __('My eSIMs') }}</a>

    <div class="mt-4 flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-strong">{{ $provisioning->orderItem->name }}</h1>
            <p class="text-sm text-muted">{{ __('Order') }} {{ $provisioning->orderItem->order->reference }}</p>
        </div>
        <x-status-badge :status="$provisioning->status" />
    </div>

    @if ($provisioning->status === 'ready' && $provisioning->hasWorkingActivationData())
        <div class="card-solid mt-6 space-y-5 rounded-3xl border border-app p-6 shadow-sm">
            <div class="flex flex-col items-center gap-3 rounded-2xl surface-2 p-6 text-center">
                <img src="{{ route('esim.mine.qr', $provisioning) }}" alt="{{ __('eSIM QR code') }}" class="h-56 w-56 rounded-xl bg-white p-3 shadow">
                <p class="text-xs text-muted">{{ __('Scan this with a second device, or follow manual setup below.') }}</p>
            </div>

            @if ($provisioning->direct_install_url)
                <a href="{{ $provisioning->direct_install_url }}" class="btn btn-primary w-full">{{ __('One-tap install on this iPhone') }}</a>
            @endif

            <div x-data="{ show: false }" class="space-y-2 rounded-2xl border border-app p-4 text-sm">
                <button type="button" class="flex w-full items-center justify-between font-semibold text-strong" @click="show = !show">
                    {{ __('Manual entry details') }}
                    <x-icon name="chevron-down" class="h-4 w-4" />
                </button>
                <div x-show="show" x-collapse style="display:none" class="space-y-2 pt-2">
                    @if ($provisioning->sm_dp_address)
                        <div class="flex items-center justify-between gap-2 rounded-xl surface-2 px-3 py-2" x-data>
                            <div class="min-w-0"><p class="text-[10px] uppercase text-faint">{{ __('SM-DP+ address') }}</p><p class="truncate font-mono text-xs text-strong">{{ $provisioning->sm_dp_address }}</p></div>
                            <button type="button" class="shrink-0 text-faint hover:text-strong" @click="navigator.clipboard.writeText(@js($provisioning->sm_dp_address))"><x-icon name="copy" class="h-4 w-4" /></button>
                        </div>
                    @endif
                    @if ($provisioning->activation_code)
                        <div class="flex items-center justify-between gap-2 rounded-xl surface-2 px-3 py-2" x-data>
                            <div class="min-w-0"><p class="text-[10px] uppercase text-faint">{{ __('Activation code') }}</p><p class="truncate font-mono text-xs text-strong">{{ $provisioning->activation_code }}</p></div>
                            <button type="button" class="shrink-0 text-faint hover:text-strong" @click="navigator.clipboard.writeText(@js($provisioning->activation_code))"><x-icon name="copy" class="h-4 w-4" /></button>
                        </div>
                    @endif
                    @if ($provisioning->confirmation_code)
                        <div class="flex items-center justify-between gap-2 rounded-xl surface-2 px-3 py-2" x-data>
                            <div class="min-w-0"><p class="text-[10px] uppercase text-faint">{{ __('Confirmation code') }}</p><p class="truncate font-mono text-xs text-strong">{{ $provisioning->confirmation_code }}</p></div>
                            <button type="button" class="shrink-0 text-faint hover:text-strong" @click="navigator.clipboard.writeText(@js($provisioning->confirmation_code))"><x-icon name="copy" class="h-4 w-4" /></button>
                        </div>
                    @endif
                </div>
            </div>

            <div class="space-y-3 text-sm">
                <p class="font-semibold text-strong">{{ __('How to install') }}</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border border-app p-4">
                        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-faint">{{ __('iPhone') }}</p>
                        <ol class="list-decimal space-y-1 pl-4 text-xs text-muted">
                            <li>{{ __('Open Settings → Cellular/Mobile Service → Add eSIM.') }}</li>
                            <li>{{ __('Tap "Use QR Code" and scan the code above, or choose "Enter Details Manually".') }}</li>
                            <li>{{ __('Follow the prompts, then label the new line (e.g. "Travel").') }}</li>
                        </ol>
                    </div>
                    <div class="rounded-2xl border border-app p-4">
                        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-faint">{{ __('Android') }}</p>
                        <ol class="list-decimal space-y-1 pl-4 text-xs text-muted">
                            <li>{{ __('Open Settings → Network & Internet → SIMs → Add SIM / Download a SIM.') }}</li>
                            <li>{{ __('Choose "Scan QR code from carrier" and scan the code above.') }}</li>
                            <li>{{ __('Confirm and wait for activation to finish.') }}</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl bg-amber-500/10 p-4 text-xs text-amber-700">
                {{ __(esim_activation_policy_label($provisioning->activation_policy)) }}
                @if ($provisioning->installation_deadline_at)
                    {{ __('Install before :date.', ['date' => $provisioning->installation_deadline_at->format('M j, Y')]) }}
                @endif
            </div>
        </div>
    @elseif ($provisioning->status === 'failed')
        <div class="card-solid mt-6 rounded-3xl border border-app p-6 text-center shadow-sm">
            <x-icon name="alert" class="mx-auto h-8 w-8 text-rose-500" />
            <p class="mt-3 font-semibold text-strong">{{ __('We could not provision this eSIM') }}</p>
            <p class="mt-1 text-sm text-muted">{{ __('Our team has been notified. Contact support for a replacement or refund.') }}</p>
            <a href="{{ route('disputes.index') }}" class="btn btn-primary mt-4">{{ __('Contact support') }}</a>
        </div>
    @else
        <div class="card-solid mt-6 rounded-3xl border border-app p-6 text-center shadow-sm">
            <x-icon name="clock" class="mx-auto h-8 w-8 text-muted" />
            <p class="mt-3 font-semibold text-strong">{{ __('Your eSIM is being prepared') }}</p>
            <p class="mt-1 text-sm text-muted">{{ __('We\'ll email you the moment it\'s ready to install.') }}</p>
        </div>
    @endif
</div>
@endsection
