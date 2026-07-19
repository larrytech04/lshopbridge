@extends('layouts.app')
@section('page-title', 'Verification')

@php
    $emailDone = (bool) $user->hasVerifiedEmail();
    $phoneDone = $user->isPhoneVerified();
    $kycDone = $user->kyc_level >= 2;
    $kycUnlocked = $emailDone && $phoneDone;

    $sourceOfFundsOptions = [
        'salary' => __('Salary / employment'),
        'business' => __('Business income'),
        'savings' => __('Personal savings'),
        'investments' => __('Investments'),
        'gifts' => __('Gifts / inheritance'),
        'other' => __('Other'),
    ];

    $currentLevel = $levels->firstWhere('level', $user->kyc_level) ?? $levels->first();
    $statusLabel = $kycDone ? __('Fully verified') : ($phoneDone ? __('Phone verified') : ($emailDone ? __('Email verified') : __('Getting started')));
@endphp

@section('content')
<x-page-header :title="__('Identity verification')" :subtitle="__('Verify your account to raise your transaction limits and unlock higher tiers.')" />

<div class="space-y-6">

    {{-- Hero: current level + what unlocks next --}}
    <div class="rounded-3xl border border-app p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-brand-600 text-xl font-bold text-white">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 1)) }}</span>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-faint">{{ __('Your verification level') }}</p>
                    <p class="text-lg font-bold text-strong">L{{ $user->kyc_level }} · {{ $currentLevel->name ?? __('Registered') }}</p>
                </div>
            </div>
            <span class="pill bg-emerald-500/15 text-[11px] font-bold uppercase tracking-wide text-emerald-600 ring-1 ring-emerald-400/30">{{ $statusLabel }}</span>
        </div>
        <div class="mt-5 grid gap-3 border-t border-app pt-4 sm:grid-cols-2">
            <div class="flex items-start gap-2 text-sm">
                <x-icon name="{{ $kycUnlocked ? 'check' : 'chevron-right' }}" class="mt-0.5 h-4 w-4 shrink-0 {{ $kycUnlocked ? 'text-emerald-500' : 'text-faint' }}" />
                <span class="{{ $kycUnlocked ? 'text-body' : 'text-muted' }}">{{ __('Email + phone verification unlocks :name.', ['name' => 'L1 · '.($levels->firstWhere('level', 1)->name ?? '')]) }}</span>
            </div>
            <div class="flex items-start gap-2 text-sm">
                <x-icon name="{{ $kycDone ? 'check' : 'chevron-right' }}" class="mt-0.5 h-4 w-4 shrink-0 {{ $kycDone ? 'text-emerald-500' : 'text-faint' }}" />
                <span class="{{ $kycDone ? 'text-body' : 'text-muted' }}">{{ __('ID verification unlocks :name and raises your limits.', ['name' => 'L2 · '.($levels->firstWhere('level', 2)->name ?? '')]) }}</span>
            </div>
        </div>
    </div>

    {{-- Step 1: Email --}}
    <div class="rounded-3xl border border-app p-6">
        <div class="flex items-center gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl {{ $emailDone ? 'bg-emerald-500/15 text-emerald-600' : 'bg-slate-500/12 text-slate-500' }}">
                <x-icon name="{{ $emailDone ? 'check' : 'mail' }}" class="h-5 w-5" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="font-semibold text-strong">{{ __('Confirm your email address') }}</p>
                <p class="truncate text-sm text-muted">{{ $user->email }}</p>
            </div>
            @if ($emailDone)
                <span class="pill shrink-0 bg-emerald-500/15 text-[11px] font-bold uppercase text-emerald-600 ring-1 ring-emerald-400/30">{{ __('Verified') }}</span>
            @endif
        </div>
        @unless ($emailDone)
            <form method="POST" action="{{ route('verification.send') }}" class="mt-3">
                @csrf
                <button class="btn btn-ghost"><x-icon name="mail" class="h-4 w-4" /> {{ __('Resend verification email') }}</button>
            </form>
        @endunless
    </div>

    {{-- Step 2: Phone --}}
    <div class="rounded-3xl border border-app p-6">
        <div class="flex items-center gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl {{ $phoneDone ? 'bg-emerald-500/15 text-emerald-600' : 'bg-slate-500/12 text-slate-500' }}">
                <x-icon name="{{ $phoneDone ? 'check' : 'phone' }}" class="h-5 w-5" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="font-semibold text-strong">{{ __('Confirm your phone number') }}</p>
                <p class="truncate text-sm text-muted">{{ $user->phone ?? __('No phone number on file') }}</p>
            </div>
            @if ($phoneDone)
                <span class="pill shrink-0 bg-emerald-500/15 text-[11px] font-bold uppercase text-emerald-600 ring-1 ring-emerald-400/30">{{ __('Verified') }}</span>
            @else
                <span class="pill shrink-0 bg-amber-500/15 text-[11px] font-bold uppercase text-amber-600 ring-1 ring-amber-400/30">{{ __('Required') }}</span>
            @endif
        </div>
        @unless ($phoneDone)
            <div class="mt-3 flex flex-wrap gap-3">
                <form method="POST" action="{{ route('verification.phone.send') }}">@csrf
                    <button class="btn btn-ghost"><x-icon name="phone" class="h-4 w-4" /> {{ __('Send code') }}</button>
                </form>
                <form method="POST" action="{{ route('verification.phone.verify') }}" class="flex gap-2">@csrf
                    <input name="code" inputmode="numeric" maxlength="6" class="field max-w-[140px]" placeholder="{{ __('6-digit code') }}">
                    <button class="btn btn-primary">{{ __('Verify') }}</button>
                </form>
            </div>
        @endunless
    </div>

    {{-- Step 3: KYC, locked until email + phone are done --}}
    <div class="rounded-3xl border border-app p-6 {{ $kycUnlocked ? '' : 'opacity-60' }}">
        <div class="flex items-center justify-between gap-2">
            <h3 class="font-semibold text-strong">{{ __('Identity verification (KYC)') }}</h3>
            <x-status-badge :status="$user->kyc_status" class="text-[11px] font-bold uppercase" />
        </div>

        @if (! $kycUnlocked)
            <p class="mt-3 flex items-center gap-2 text-sm text-muted"><x-icon name="lock" class="h-4 w-4 shrink-0" /> {{ __('Complete email and phone verification above to unlock this step.') }}</p>
        @elseif ($latest && $latest->status === 'pending')
            <div class="mt-4 rounded-xl border border-amber-400/30 bg-amber-500/10 p-3 text-sm text-amber-700">{{ __('Your documents are under review. We\'ll notify you once verified.') }}</div>
        @elseif ($kycDone)
            <div class="mt-4 rounded-xl border border-emerald-400/30 bg-emerald-500/10 p-3 text-sm text-emerald-700">{{ __('Your identity is verified. Higher limits unlocked.') }}</div>
        @else
            @if ($latest && $latest->status === 'rejected')
                <div class="mt-4 rounded-xl border border-rose-400/30 bg-rose-500/10 p-3 text-sm text-rose-700">{{ __('Rejected: :reason', ['reason' => $latest->rejection_reason]) }}</div>
            @endif
            <form method="POST" action="{{ route('verification.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">{{ __('Full legal name') }}</label><input name="full_name" value="{{ $user->name }}" required class="field"></div>
                    <div><label class="label">{{ __('Date of birth') }}</label><input type="date" name="date_of_birth" required class="field"></div>
                    <div><label class="label">{{ __('Country of residence') }}</label>
                        <select name="country_id" required class="field">
                            @foreach (\App\Models\Country::active()->get() as $c)<option value="{{ $c->id }}" @selected($user->country_id == $c->id)>{{ $c->name }}</option>@endforeach
                        </select>
                    </div>
                    <div><label class="label">{{ __('City') }}</label><input name="city" value="{{ $user->city }}" required class="field"></div>
                    <div class="sm:col-span-2"><label class="label">{{ __('Address') }}</label><input name="address" value="{{ $user->address }}" required class="field"></div>
                    <div><label class="label">{{ __('Document type') }}</label>
                        <select name="document_type" required class="field">
                            <option value="national_id">{{ __('National ID') }}</option>
                            <option value="passport">{{ __('Passport') }}</option>
                            <option value="drivers_license">{{ __('Driver\'s license') }}</option>
                        </select>
                    </div>
                    <div><label class="label">{{ __('Document number') }}</label><input name="document_number" required class="field" placeholder="{{ __('The number printed on your document') }}"></div>
                    <div><label class="label">{{ __('Occupation') }}</label><input name="occupation" required class="field" placeholder="{{ __('e.g. Software engineer') }}"></div>
                    <div><label class="label">{{ __('Source of funds') }}</label>
                        <select name="source_of_funds" required class="field">
                            @foreach ($sourceOfFundsOptions as $val => $lbl)<option value="{{ $val }}">{{ $lbl }}</option>@endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <p class="label">{{ __('Upload your documents') }}</p>
                    <p class="text-xs text-faint">{{ __('Clear photos or scans, JPG or PNG, under 5 MB each.') }}</p>
                    <div class="mt-2 grid gap-3 sm:grid-cols-3">
                        <label class="group relative flex cursor-pointer flex-col items-center gap-2 rounded-2xl border-2 border-dashed border-app p-5 text-center transition hover:border-brand-400/60 hover:surface-2" x-data="{ f: '' }">
                            <input type="file" name="id_front" accept=".jpg,.jpeg,.png,.pdf" required class="sr-only" @change="f = $event.target.files[0]?.name || ''">
                            <span class="grid h-10 w-10 place-items-center rounded-full surface-2 text-muted transition group-hover:text-brand-500"><x-icon name="upload" class="h-5 w-5" /></span>
                            <span class="text-sm font-semibold text-strong">{{ __('Document front') }}</span>
                            <span class="line-clamp-1 text-xs text-faint" x-text="f || '{{ __('Tap to upload') }}'"></span>
                        </label>
                        <label class="group relative flex cursor-pointer flex-col items-center gap-2 rounded-2xl border-2 border-dashed border-app p-5 text-center transition hover:border-brand-400/60 hover:surface-2" x-data="{ f: '' }">
                            <input type="file" name="id_back" accept=".jpg,.jpeg,.png,.pdf" class="sr-only" @change="f = $event.target.files[0]?.name || ''">
                            <span class="grid h-10 w-10 place-items-center rounded-full surface-2 text-muted transition group-hover:text-brand-500"><x-icon name="upload" class="h-5 w-5" /></span>
                            <span class="text-sm font-semibold text-strong">{{ __('Document back') }}</span>
                            <span class="line-clamp-1 text-xs text-faint" x-text="f || '{{ __('Optional') }}'"></span>
                        </label>
                        <label class="group relative flex cursor-pointer flex-col items-center gap-2 rounded-2xl border-2 border-dashed border-app p-5 text-center transition hover:border-brand-400/60 hover:surface-2" x-data="{ f: '' }">
                            <input type="file" name="selfie" accept=".jpg,.jpeg,.png" required class="sr-only" @change="f = $event.target.files[0]?.name || ''">
                            <span class="grid h-10 w-10 place-items-center rounded-full surface-2 text-muted transition group-hover:text-brand-500"><x-icon name="upload" class="h-5 w-5" /></span>
                            <span class="text-sm font-semibold text-strong">{{ __('Selfie with document') }}</span>
                            <span class="line-clamp-1 text-xs text-faint" x-text="f || '{{ __('Tap to upload') }}'"></span>
                        </label>
                    </div>
                    <label class="group relative mt-3 flex cursor-pointer items-center gap-3 rounded-2xl border-2 border-dashed border-app p-4 transition hover:border-brand-400/60 hover:surface-2" x-data="{ f: '' }">
                        <input type="file" name="proof_of_address" accept=".jpg,.jpeg,.png,.pdf" class="sr-only" @change="f = $event.target.files[0]?.name || ''">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full surface-2 text-muted transition group-hover:text-brand-500"><x-icon name="upload" class="h-4 w-4" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-strong">{{ __('Proof of address') }}</span>
                            <span class="line-clamp-1 block text-xs text-faint" x-text="f || '{{ __('Optional, utility bill or bank statement') }}'"></span>
                        </span>
                    </label>
                </div>

                <label class="flex items-start gap-2.5 rounded-xl border border-app surface p-3 text-sm text-body">
                    <input type="checkbox" name="is_pep" value="1" required class="mt-0.5 h-4 w-4 shrink-0 rounded border-app text-brand-600 focus:ring-brand-500">
                    {{ __('I confirm I am not a politically exposed person (PEP), nor a close associate or family member of one.') }}
                </label>

                <p class="flex items-center gap-2 rounded-xl surface p-3 text-xs text-muted"><x-icon name="lock" class="h-3.5 w-3.5 shrink-0" /> {{ __('Your documents are used only to verify your identity and are stored securely.') }}</p>

                <button class="btn btn-primary w-full"><x-icon name="shield" class="h-4 w-4" /> {{ __('Submit for verification') }}</button>
            </form>
        @endif
    </div>

    {{-- Transaction limits per level --}}
    <div>
        <h3 class="font-semibold text-strong">{{ __('Transaction limits') }}</h3>
        <p class="text-sm text-muted">{{ __('Verified accounts can transact more. Your current level is highlighted.') }}</p>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            @foreach ($levels as $lvl)
                @php $isCurrent = $user->kyc_level === $lvl->level; @endphp
                <div class="rounded-3xl p-5 {{ $isCurrent ? 'bg-brand-600 text-white shadow-lg shadow-brand-600/25' : 'border border-app' }}">
                    <div class="flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-bold {{ $isCurrent ? 'text-white' : 'text-strong' }}">L{{ $lvl->level }} · {{ $lvl->name }}</p>
                        </div>
                        @if ($isCurrent)
                            <span class="pill shrink-0 bg-white/20 text-[10px] font-bold uppercase text-white">{{ __('Current') }}</span>
                        @elseif ($user->kyc_level >= $lvl->level)
                            <x-icon name="check-circle" class="h-5 w-5 shrink-0 text-emerald-500" />
                        @else
                            <span class="pill shrink-0 bg-slate-500/12 text-[10px] font-bold uppercase text-slate-500">{{ __('Locked') }}</span>
                        @endif
                    </div>
                    <div class="mt-3 space-y-1.5 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="{{ $isCurrent ? 'text-white/75' : 'text-muted' }}">{{ __('Per transaction') }}</span>
                            <span class="font-semibold {{ $isCurrent ? 'text-white' : 'text-strong' }}">{{ money($lvl->per_transaction_limit, $lvl->currency) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="{{ $isCurrent ? 'text-white/75' : 'text-muted' }}">{{ __('Daily') }}</span>
                            <span class="font-semibold {{ $isCurrent ? 'text-white' : 'text-strong' }}">{{ money($lvl->daily_limit, $lvl->currency) }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
