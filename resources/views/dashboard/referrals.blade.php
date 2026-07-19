@extends('layouts.app')
@section('page-title', 'Referrals')

@php
    $refLink = route('register', ['ref' => $user->referral_code]);
    $referrerPoints = config('platform.referrals.referrer_points');
    $referredPoints = config('platform.referrals.referred_points');
    $pointsEarned = $verifiedCount * $referrerPoints;
@endphp

@section('content')
<x-page-header :title="__('Referrals')" />

<div x-data="{ tab: 'earn' }">

    {{-- Tabs --}}
    <div class="flex items-center gap-1 rounded-2xl border border-app p-1.5">
        <button type="button" @click="tab = 'earn'" class="flex-1 rounded-xl px-4 py-2.5 text-sm font-semibold transition" :class="tab === 'earn' ? 'surface-2 text-strong' : 'text-muted hover:text-strong'">{{ __('Refer & earn') }}</button>
        <button type="button" @click="tab = 'list'" class="flex-1 rounded-xl px-4 py-2.5 text-sm font-semibold transition" :class="tab === 'list' ? 'surface-2 text-strong' : 'text-muted hover:text-strong'">{{ __('My referrals') }}</button>
    </div>

    {{-- Refer & earn --}}
    <div x-show="tab === 'earn'" x-cloak class="mt-4 space-y-6">

        {{-- Hero: link + copy --}}
        <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-brand-900 p-6 text-white">
            <div class="animate-pulse-glow absolute -right-10 -bottom-10 h-40 w-40 rounded-full bg-accent-500/30 blur-3xl"></div>
            <div class="pointer-events-none absolute -left-8 -top-8 h-28 w-28 rounded-full bg-white/10 blur-2xl"></div>
            <div class="relative">
                <h3 class="text-xl font-bold text-white">{{ __('Invite friends, earn :n Coins', ['n' => $referrerPoints]) }}</h3>
                <p class="mt-1.5 text-sm text-white/80">{{ __('Share your link. When a friend signs up and verifies their identity, you both earn LshopBridge Coins.') }}</p>
                <div class="mt-4 flex flex-col gap-2 sm:flex-row" x-data>
                    <span class="flex-1 truncate rounded-xl bg-white/10 px-4 py-3 text-sm text-white/90 ring-1 ring-white/15">{{ $refLink }}</span>
                    <button type="button" @click="navigator.clipboard.writeText(@js($refLink)); $el.querySelector('span').textContent='{{ __('Copied') }}'" class="shrink-0 rounded-xl bg-white px-5 py-3 text-sm font-bold text-brand-900 transition hover:bg-white/90"><span>{{ __('Copy link') }}</span></button>
                </div>
                <p class="mt-3 flex items-center gap-1.5 text-xs text-white/70">
                    {{ __('Or share your code:') }}
                    <span class="rounded-md bg-white/10 px-1.5 py-0.5 font-mono font-semibold text-white">{{ $user->referral_code }}</span>
                    <button type="button" @click="navigator.clipboard.writeText(@js($user->referral_code))" class="font-semibold underline hover:text-white">{{ __('Copy') }}</button>
                </p>
            </div>
        </div>

        {{-- How it works --}}
        <div>
            <h3 class="font-semibold text-strong">{{ __('How referrals work') }}</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-app p-5">
                    <span class="grid h-9 w-9 place-items-center rounded-full surface-2 text-sm font-bold text-strong">1</span>
                    <p class="mt-3 font-semibold text-strong">{{ __('You send an invite') }}</p>
                    <p class="mt-1 text-sm text-muted">{{ __('Share your link or code with a friend, by chat, email, or social media.') }}</p>
                </div>
                <div class="rounded-2xl border border-app p-5">
                    <span class="grid h-9 w-9 place-items-center rounded-full surface-2 text-sm font-bold text-strong">2</span>
                    <p class="mt-3 font-semibold text-strong">{{ __('They sign up & verify') }}</p>
                    <p class="mt-1 text-sm text-muted">{{ __('Your friend creates an account with your link and completes identity verification.') }}</p>
                </div>
                <div class="rounded-2xl border border-app p-5">
                    <span class="grid h-9 w-9 place-items-center rounded-full surface-2 text-sm font-bold text-strong">3</span>
                    <p class="mt-3 font-semibold text-strong">{{ __('You both earn') }}</p>
                    <p class="mt-1 text-sm text-muted">{{ __(':you Coins for you, :them for your friend, automatically credited.', ['you' => $referrerPoints, 'them' => $referredPoints]) }}</p>
                </div>
            </div>
            <p class="mt-3 flex items-center gap-1.5 text-xs text-faint">
                <x-icon name="info" class="h-3.5 w-3.5 shrink-0" />
                {{ __('Coins are credited once your friend\'s identity verification (KYC) is approved.') }}
            </p>
        </div>

        {{-- Your own stats, real numbers, not platform-wide marketing figures --}}
        <div class="grid grid-cols-3 divide-x divide-app overflow-hidden rounded-3xl border border-app text-center">
            <div class="p-5">
                <p class="text-2xl font-extrabold text-strong">{{ $referredCount }}</p>
                <p class="mt-1 text-xs text-muted">{{ __('Friends referred') }}</p>
            </div>
            <div class="p-5">
                <p class="text-2xl font-extrabold text-strong">{{ $verifiedCount }}</p>
                <p class="mt-1 text-xs text-muted">{{ __('Verified') }}</p>
            </div>
            <div class="p-5">
                <p class="text-2xl font-extrabold text-strong">{{ number_format($pointsEarned) }}</p>
                <p class="mt-1 text-xs text-muted">{{ __('Coins earned') }}</p>
            </div>
        </div>
    </div>

    {{-- My referrals --}}
    <div x-show="tab === 'list'" x-cloak class="mt-4 rounded-3xl border border-app">
        @forelse ($referrals as $r)
            <div class="flex items-center gap-3 border-b border-app p-4 last:border-b-0">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-brand-600 text-sm font-bold text-white">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($r->name, 0, 1)) }}</span>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-semibold text-strong">{{ $r->name }}</p>
                    <p class="text-xs text-faint">{{ __('Joined :time', ['time' => $r->created_at->diffForHumans()]) }}</p>
                </div>
                @if ($r->kyc_level >= 2)
                    <span class="pill shrink-0 bg-emerald-500/15 text-[10px] font-bold uppercase text-emerald-600 ring-1 ring-emerald-400/30">{{ __('Verified · +:n', ['n' => $referrerPoints]) }}</span>
                @else
                    <span class="pill shrink-0 bg-amber-500/15 text-[10px] font-bold uppercase text-amber-600 ring-1 ring-amber-400/30">{{ __('Pending verification') }}</span>
                @endif
            </div>
        @empty
            <x-empty icon="users" title="{{ __('No referrals yet') }}" message="{{ __('Share your link above, friends you invite will show up here.') }}" />
        @endforelse
    </div>
</div>
@endsection
