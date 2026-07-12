@extends('layouts.app')
@section('page-title', 'Agent verification')

@section('content')
<div class="mx-auto max-w-2xl">
    <x-glass-card>
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-strong">{{ __('Business verification') }}</h3>
            <x-status-badge :status="$agent->status" />
        </div>

        @if ($agent->status->value === 'approved')
            <div class="mt-4 rounded-xl border border-emerald-400/30 bg-emerald-500/10 p-3 text-sm text-emerald-100">{{ __('Your business is verified and listed. 🎉') }}</div>
        @else
            @if ($agent->rejection_reason)<div class="mt-4 rounded-xl border border-rose-400/30 bg-rose-500/10 p-3 text-sm text-rose-200">{{ $agent->rejection_reason }}</div>@endif
            <p class="mt-3 text-sm text-muted">{{ __('Upload your business registration and a government ID. Documents are stored securely and never shown publicly.') }}</p>
            <form method="POST" action="{{ route('agent.verification.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                <div><label class="label">{{ __('Business registration number') }}</label><input name="registration_number" value="{{ old('registration_number', $agent->registration_number) }}" class="field"></div>
                <div><label class="label">{{ __('Business document') }}</label><input type="file" name="business_doc" accept=".jpg,.jpeg,.png,.pdf" required class="field"></div>
                <div><label class="label">{{ __('Government ID') }}</label><input type="file" name="id_doc" accept=".jpg,.jpeg,.png,.pdf" required class="field"></div>
                <button class="btn btn-primary"><x-icon name="shield" class="h-4 w-4" /> {{ __('Submit for verification') }}</button>
            </form>
        @endif
    </x-glass-card>
</div>
@endsection
