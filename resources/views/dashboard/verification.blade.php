@extends('layouts.app')
@section('page-title', 'Verification')

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-6">
        {{-- Phone OTP --}}
        <x-glass-card>
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-strong">{{ __('Phone verification') }}</h3>
                @if ($user->isPhoneVerified())<span class="pill bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-400/30">{{ __('Verified') }}</span>@else<span class="pill bg-amber-500/15 text-amber-300 ring-1 ring-amber-400/30">{{ __('Required for funding') }}</span>@endif
            </div>
            @unless ($user->isPhoneVerified())
                <p class="mt-2 text-sm text-muted">Verify {{ $user->phone ?? 'your phone number' }} to unlock Level 1 and start funding.</p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('verification.phone.send') }}">@csrf
                        <button class="btn btn-ghost"><x-icon name="phone" class="h-4 w-4" /> {{ __('Send code') }}</button>
                    </form>
                    <form method="POST" action="{{ route('verification.phone.verify') }}" class="flex gap-2">@csrf
                        <input name="code" inputmode="numeric" maxlength="6" class="field max-w-[140px]" placeholder="{{ __('6-digit code') }}">
                        <button class="btn btn-primary">{{ __('Verify') }}</button>
                    </form>
                </div>
            @else
                <p class="mt-2 text-sm text-emerald-300">{{ __('Your phone is verified. ✅') }}</p>
            @endunless
        </x-glass-card>

        {{-- KYC --}}
        <x-glass-card>
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-strong">{{ __('Identity verification (KYC)') }}</h3>
                <x-status-badge :status="$user->kyc_status" />
            </div>

            @if ($latest && $latest->status === 'pending')
                <div class="mt-4 rounded-xl border border-amber-400/30 bg-amber-500/10 p-3 text-sm text-amber-100">{{ __('Your documents are under review. We\'ll notify you once verified.') }}</div>
            @elseif ($user->kyc_level >= 2)
                <div class="mt-4 rounded-xl border border-emerald-400/30 bg-emerald-500/10 p-3 text-sm text-emerald-100">{{ __('Your identity is verified. Higher limits unlocked.') }}</div>
            @else
                @if ($latest && $latest->status === 'rejected')
                    <div class="mt-4 rounded-xl border border-rose-400/30 bg-rose-500/10 p-3 text-sm text-rose-200">Rejected: {{ $latest->rejection_reason }}</div>
                @endif
                <form method="POST" action="{{ route('verification.store') }}" enctype="multipart/form-data" class="mt-4 grid gap-4 sm:grid-cols-2">
                    @csrf
                    <div><label class="label">{{ __('Document type') }}</label>
                        <select name="document_type" required class="field">
                            <option value="national_id">{{ __('National ID') }}</option>
                            <option value="passport">{{ __('Passport') }}</option>
                            <option value="drivers_license">{{ __('Driver\'s license') }}</option>
                        </select>
                    </div>
                    <div><label class="label">{{ __('Document number') }}</label><input name="document_number" required class="field"></div>
                    <div><label class="label">{{ __('Full legal name') }}</label><input name="full_name" value="{{ $user->name }}" required class="field"></div>
                    <div><label class="label">{{ __('Date of birth') }}</label><input type="date" name="date_of_birth" required class="field"></div>
                    <div><label class="label">{{ __('Country') }}</label>
                        <select name="country_id" required class="field">
                            @foreach (\App\Models\Country::active()->get() as $c)<option value="{{ $c->id }}" @selected($user->country_id == $c->id)>{{ $c->name }}</option>@endforeach
                        </select>
                    </div>
                    <div><label class="label">{{ __('City') }}</label><input name="city" value="{{ $user->city }}" required class="field"></div>
                    <div class="sm:col-span-2"><label class="label">{{ __('Address') }}</label><input name="address" value="{{ $user->address }}" required class="field"></div>
                    <div><label class="label">{{ __('ID front') }}</label><input type="file" name="id_front" accept=".jpg,.jpeg,.png,.pdf" required class="field"></div>
                    <div><label class="label">{{ __('ID back (optional)') }}</label><input type="file" name="id_back" accept=".jpg,.jpeg,.png,.pdf" class="field"></div>
                    <div><label class="label">{{ __('Selfie with ID') }}</label><input type="file" name="selfie" accept=".jpg,.jpeg,.png" required class="field"></div>
                    <div><label class="label">{{ __('Proof of address (optional)') }}</label><input type="file" name="proof_of_address" accept=".jpg,.jpeg,.png,.pdf" class="field"></div>
                    <div class="sm:col-span-2"><button class="btn btn-primary"><x-icon name="shield" class="h-4 w-4" /> {{ __('Submit for verification') }}</button></div>
                </form>
            @endif
        </x-glass-card>
    </div>

    {{-- Levels --}}
    <div>
        <x-glass-card>
            <h3 class="font-semibold text-strong">{{ __('Verification levels') }}</h3>
            <div class="mt-4 space-y-3">
                @foreach ($levels as $lvl)
                    <div class="rounded-2xl border p-4 {{ $user->kyc_level >= $lvl->level ? 'border-emerald-400/30 bg-emerald-500/5' : 'border-app surface' }}">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-strong">L{{ $lvl->level }} · {{ $lvl->name }}</span>
                            @if ($user->kyc_level >= $lvl->level)<x-icon name="check-circle" class="h-5 w-5 text-emerald-400" />@endif
                        </div>
                        <p class="mt-1 text-xs text-muted">Up to {{ money($lvl->per_transaction_limit, $lvl->currency) }}/tx · {{ money($lvl->daily_limit, $lvl->currency) }}/day</p>
                    </div>
                @endforeach
            </div>
        </x-glass-card>
    </div>
</div>
@endsection
