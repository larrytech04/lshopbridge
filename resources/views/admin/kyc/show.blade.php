@extends('layouts.admin')
@section('page-title', 'KYC · '.$kyc->user->name)

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <a href="{{ route('admin.kyc.index') }}" class="text-sm text-brand-300 hover:text-brand-200">← Back</a>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-glass-card>
            <h3 class="font-semibold text-strong">Applicant</h3>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-muted">Account</dt><dd class="text-body">{{ $kyc->user->name }} ({{ $kyc->user->email }})</dd></div>
                <div class="flex justify-between"><dt class="text-muted">Legal name</dt><dd class="text-body">{{ $kyc->full_name }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">Document</dt><dd class="text-body">{{ ucfirst(str_replace('_',' ',$kyc->document_type)) }} · {{ $kyc->document_number }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">DOB</dt><dd class="text-body">{{ optional($kyc->date_of_birth)->format('M j, Y') }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">Address</dt><dd class="text-body text-right">{{ $kyc->address }}, {{ $kyc->city }}, {{ $kyc->country->name ?? '' }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">Status</dt><dd><x-status-badge :status="$kyc->status" /></dd></div>
            </dl>
        </x-glass-card>

        <x-glass-card>
            <h3 class="font-semibold text-strong">Documents</h3>
            <div class="mt-4 grid grid-cols-2 gap-3">
                @foreach (['kyc-front'=>'ID front','kyc-back'=>'ID back','kyc-selfie'=>'Selfie','kyc-proof'=>'Proof of address'] as $kind=>$label)
                    @php $field = ['kyc-front'=>'id_front_path','kyc-back'=>'id_back_path','kyc-selfie'=>'selfie_path','kyc-proof'=>'proof_of_address_path'][$kind]; @endphp
                    @if ($kyc->$field)
                        <a href="{{ route('files.show', ['kind'=>$kind,'id'=>$kyc->id]) }}" target="_blank" class="flex items-center gap-2 rounded-xl border border-app surface p-3 text-sm text-body hover:bg-white/5"><x-icon name="eye" class="h-4 w-4 text-brand-200" /> {{ $label }}</a>
                    @endif
                @endforeach
            </div>
        </x-glass-card>
    </div>

    @if ($kyc->status === 'pending')
        <div class="grid gap-4 sm:grid-cols-2">
            <form method="POST" action="{{ route('admin.kyc.approve', $kyc) }}"><x-glass-card>@csrf
                <h3 class="font-semibold text-emerald-300">Approve</h3>
                <p class="mt-1 text-sm text-muted">Sets the user to verified (Level 2) and unlocks higher limits.</p>
                <button class="btn btn-success mt-4 w-full"><x-icon name="check" class="h-4 w-4" /> Approve KYC</button>
            </x-glass-card></form>
            <form method="POST" action="{{ route('admin.kyc.reject', $kyc) }}"><x-glass-card>@csrf
                <h3 class="font-semibold text-rose-300">Reject</h3>
                <input name="reason" required class="field mt-3" placeholder="Reason (shown to user)">
                <button class="btn btn-danger mt-3 w-full"><x-icon name="x" class="h-4 w-4" /> Reject KYC</button>
            </x-glass-card></form>
        </div>
    @endif
</div>
@endsection
