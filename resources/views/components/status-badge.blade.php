@props(['status', 'label' => null])

@php
    if (is_object($status) && method_exists($status, 'color')) {
        $classes = $status->color();
        $text = $label ?? (method_exists($status, 'label') ? $status->label() : $status->value);
    } else {
        $value = is_object($status) ? ($status->value ?? '') : (string) $status;
        $text = $label ?? ucfirst(str_replace('_', ' ', $value));
        $classes = match ($value) {
            'active', 'approved', 'confirmed', 'completed', 'funding_successful', 'processed', 'resolved' => 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-400/30',
            'pending', 'under_review', 'manual_review', 'new', 'open', 'payment_pending' => 'bg-amber-500/15 text-amber-300 ring-1 ring-amber-400/30',
            'processing', 'funding_processing', 'in_progress', 'contacted', 'received' => 'bg-sky-500/15 text-sky-300 ring-1 ring-sky-400/30',
            'rejected', 'failed', 'blocked', 'suspended', 'funding_failed', 'invalid_signature', 'closed' => 'bg-rose-500/15 text-rose-300 ring-1 ring-rose-400/30',
            'refunded' => 'bg-violet-500/15 text-violet-300 ring-1 ring-violet-400/30',
            default => 'bg-slate-400/15 text-slate-300 ring-1 ring-slate-400/30',
        };
    }
@endphp

<span {{ $attributes->merge(['class' => "pill {$classes}"]) }}>{{ $text }}</span>
