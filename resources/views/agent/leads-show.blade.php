@extends('layouts.app')
@section('page-title', __('Lead').' '.$lead->reference)

@section('content')
<div class="space-y-6">
    <a href="{{ route('agent.leads.index') }}" class="text-sm text-brand-500 hover:text-brand-600">← {{ __('All leads') }}</a>

    <div class="rounded-3xl border border-app p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="font-semibold text-strong">{{ $lead->user->name }} <span class="text-xs text-faint">· {{ $lead->reference }}</span></p>
                <p class="text-xs text-faint">{{ $lead->created_at->diffForHumans() }} @if($lead->shipping_method)· {{ ucfirst($lead->shipping_method) }}@endif</p>
            </div>
            <x-status-badge :status="$lead->status" />
        </div>
        <p class="mt-3 text-sm text-body">{{ $lead->message }}</p>

        <form method="POST" action="{{ route('agent.leads.update', $lead) }}" class="mt-4 flex items-center gap-2 border-t border-app pt-4">
            @csrf @method('PUT')
            <select name="status" class="field max-w-[180px]">
                @foreach (['new','contacted','in_progress','completed','closed'] as $s)<option value="{{ $s }}" @selected($lead->status === $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach
            </select>
            <button class="btn btn-ghost">{{ __('Update status') }}</button>
            @if ($lead->customer_confirmed_at)
                <span class="ml-auto flex items-center gap-1.5 text-sm font-semibold text-emerald-600"><x-icon name="check-circle" class="h-4 w-4" /> {{ __('Customer confirmed delivery') }}</span>
            @endif
        </form>
    </div>

    <div class="rounded-3xl border border-app p-6"
         x-data="leadChat({{ $lead->id }}, '{{ route('agent.leads.poll', $lead) }}', @js(['messages' => $lead->messages->map(fn ($m) => ['is_agent' => $m->is_agent, 'name' => $m->user->name, 'message' => $m->message, 'time' => $m->created_at->diffForHumans()])]))">
        <h3 class="font-semibold text-strong">{{ __('Chat with :customer', ['customer' => $lead->user->name]) }}</h3>

        <div x-ref="thread" class="mt-4 max-h-96 space-y-3 overflow-y-auto">
            <template x-for="(m, i) in messages" :key="i">
                <div class="flex" :class="m.is_agent ? 'justify-end' : 'justify-start'">
                    <div class="max-w-[80%] rounded-2xl px-4 py-2.5" :class="m.is_agent ? 'bg-brand-600 text-white' : 'surface-2 text-body'">
                        <p class="text-xs font-semibold" :class="m.is_agent ? 'text-white/80' : 'text-brand-500'" x-text="m.name"></p>
                        <p class="mt-1 whitespace-pre-line text-sm" x-text="m.message"></p>
                        <p class="mt-1 text-[10px] opacity-70" x-text="m.time"></p>
                    </div>
                </div>
            </template>
            <p x-show="messages.length === 0" class="py-4 text-center text-sm text-faint">{{ __('No messages yet.') }}</p>
        </div>

        <form method="POST" action="{{ route('agent.leads.message', $lead) }}" class="mt-4 flex items-center gap-2 border-t border-app pt-4">
            @csrf
            <input type="text" name="message" required maxlength="1500" class="field flex-1" placeholder="{{ __('Type a message…') }}" autocomplete="off">
            <button class="btn btn-primary shrink-0">{{ __('Send') }}</button>
        </form>
    </div>
</div>
@endsection
