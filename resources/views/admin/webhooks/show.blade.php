@extends('layouts.admin')
@section('page-title', 'Webhook event')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <a href="{{ route('admin.webhooks.index') }}" class="text-sm text-brand-600 hover:text-brand-700">← Webhook monitor</a>

    <x-glass-card>
        <div class="flex items-center justify-between">
            <div><p class="font-semibold text-strong">{{ $event->provider_code }}</p><p class="text-xs text-faint">{{ $event->event_id }}</p></div>
            <div class="flex items-center gap-3">
                <x-status-badge :status="$event->status" />
                @if ($event->status === \App\Enums\WebhookStatus::Failed)
                    <form method="POST" action="{{ route('admin.webhooks.retry', $event) }}" onsubmit="return confirm('Reprocess this webhook event?')">@csrf
                        <button class="btn btn-ghost py-1.5 text-xs">Retry</button>
                    </form>
                @endif
            </div>
        </div>
        <dl class="mt-4 grid gap-3 sm:grid-cols-2 text-sm">
            <div><dt class="text-faint">Event type</dt><dd class="text-body">{{ $event->event_type ?? '-' }}</dd></div>
            <div><dt class="text-faint">Reference</dt><dd class="font-mono text-xs text-body">{{ $event->reference ?? '-' }}</dd></div>
            <div><dt class="text-faint">Signature valid</dt><dd>{{ $event->signature_valid ? 'Yes' : 'No' }}</dd></div>
            <div><dt class="text-faint">IP</dt><dd class="text-body">{{ $event->ip }}</dd></div>
            <div><dt class="text-faint">Processed at</dt><dd class="text-body">{{ optional($event->processed_at)->format('M j, Y H:i') ?? '-' }}</dd></div>
            <div><dt class="text-faint">Related</dt><dd class="text-body">{{ class_basename($event->related_type ?? '') ?: '-' }} #{{ $event->related_id }}</dd></div>
            <div><dt class="text-faint">Retry count</dt><dd class="text-body">{{ $event->retry_count }}</dd></div>
            <div><dt class="text-faint">Last retried</dt><dd class="text-body">{{ optional($event->last_retried_at)->format('M j, Y H:i') ?? '-' }}</dd></div>
        </dl>
        @if ($event->error)<div class="mt-3 rounded-xl border border-rose-400/30 bg-rose-500/10 p-3 text-sm text-rose-700">{{ $event->error }}</div>@endif
    </x-glass-card>

    <x-glass-card>
        <h3 class="font-semibold text-strong">Payload</h3>
        <pre class="mt-3 overflow-x-auto rounded-xl bg-black/40 p-4 text-xs text-body">{{ json_encode($event->payload, JSON_PRETTY_PRINT) }}</pre>
    </x-glass-card>
</div>
@endsection
